<?php
/**
 * SpendSmart - Account Handler
 * Manages user profile viewing and editing
 * Supports updating name, email, password, and default currency
 */
require_once 'auth.php';
requireAuth();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetAccount();
        break;
    case 'POST':
        handlePostAccount();
        break;
    case 'PUT':
        handleUpdateAccount();
        break;
    case 'DELETE':
        handleDeleteAccount();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

/**
 * Handle POST actions.
 * (Some servers block DELETE; this provides a safe fallback.)
 */
function handlePostAccount() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $action = isset($input['action']) ? strtolower(trim((string)$input['action'])) : '';
    if ($action === 'delete') {
        handleDeleteAccount();
    }

    jsonResponse(['success' => false, 'message' => 'Invalid account action'], 400);
}

/**
 * Get current user account details
 */
function handleGetAccount() {
    if (isGuestMode()) {
        jsonResponse([
            'success' => true,
            'user' => [
                'id' => 'guest',
                'firstName' => 'Guest',
                'lastName' => 'User',
                'email' => 'guest@spendsmart.com',
                'defaultCurrency' => 'EUR',
                'createdAt' => date('c')
            ],
            'currencies' => getCurrencies()
        ]);
    }

    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'User not found'], 404);
    }

    // Remove password from response
    unset($user['password']);

    jsonResponse([
        'success' => true,
        'user' => $user,
        'currencies' => getCurrencies()
    ]);
}

/**
 * Update user account details
 */
function handleUpdateAccount() {
    if (isGuestMode()) {
        jsonResponse(['success' => true, 'message' => 'Profile updated (Guest Mode - not saved)']);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        jsonResponse(['success' => false, 'message' => 'No data provided'], 400);
    }

    $users = readJsonFile('users.json');
    $found = false;

    // If we successfully convert transactions but later fail saving the user,
    // we attempt to roll back transactions to keep data consistent.
    $transactionsBackup = null;
    $transactionsWereConverted = false;

    foreach ($users as &$user) {
        if ($user['id'] === $_SESSION['user_id']) {
            $oldDefaultCurrency = strtoupper((string)($user['defaultCurrency'] ?? 'EUR'));

            // Update first name
            if (isset($input['firstName']) && !empty(trim($input['firstName']))) {
                $user['firstName'] = sanitize($input['firstName']);
            }
            // Update last name
            if (isset($input['lastName']) && !empty(trim($input['lastName']))) {
                $user['lastName'] = sanitize($input['lastName']);
            }
            // Update email
            if (isset($input['email']) && !empty(trim($input['email']))) {
                $newEmail = sanitize($input['email']);
                if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    jsonResponse(['success' => false, 'message' => 'Invalid email format'], 400);
                }
                // Check if email is taken by another user
                foreach ($users as $otherUser) {
                    if ($otherUser['id'] !== $_SESSION['user_id'] && 
                        strtolower($otherUser['email']) === strtolower($newEmail)) {
                        jsonResponse(['success' => false, 'message' => 'Email is already in use'], 409);
                    }
                }
                $user['email'] = strtolower($newEmail);
                $_SESSION['user_email'] = $user['email'];
            }
            // Update password
            if (isset($input['password']) && !empty($input['password'])) {
                if (strlen($input['password']) < 6) {
                    jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters'], 400);
                }
                $user['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            // Update default currency
            if (isset($input['defaultCurrency']) && !empty($input['defaultCurrency'])) {
                $newDefaultCurrency = strtoupper(trim((string)sanitize($input['defaultCurrency'])));
                if (!currencyCodeExists($newDefaultCurrency)) {
                    jsonResponse(['success' => false, 'message' => 'Invalid currency'], 400);
                }

                // Convert existing transactions for this user when switching the default currency.
                if ($newDefaultCurrency !== $oldDefaultCurrency) {
                    $transactionsBackup = readJsonFile('transactions.json');
                    $transactions = $transactionsBackup;
                    $userId = $_SESSION['user_id'];

                    for ($i = 0; $i < count($transactions); $i++) {
                        $t = $transactions[$i];
                        if (!is_array($t)) continue;
                        if (($t['userId'] ?? '') !== $userId) continue;

                        // Only convert transactions that match the *previous* default currency.
                        // This avoids corrupting any entries the user may have manually set to another currency.
                        $txnCurrency = strtoupper((string)($t['currency'] ?? $oldDefaultCurrency));
                        if ($txnCurrency !== $oldDefaultCurrency) continue;

                        $converted = convertCurrencyAmount($t['amount'] ?? 0, $oldDefaultCurrency, $newDefaultCurrency);
                        if ($converted === null) {
                            jsonResponse(['success' => false, 'message' => 'Currency conversion is not available for the selected currencies'], 400);
                        }

                        $transactions[$i]['amount'] = round(floatval($converted), 2);
                        $transactions[$i]['currency'] = $newDefaultCurrency;
                        $transactions[$i]['updatedAt'] = date('c');
                    }

                    if (!writeJsonFile('transactions.json', $transactions)) {
                        jsonResponse(['success' => false, 'message' => 'Failed to convert transactions currency'], 500);
                    }
                    $transactionsWereConverted = true;
                }

                $user['defaultCurrency'] = $newDefaultCurrency;
            }

            $user['updatedAt'] = date('c');
            $_SESSION['user_name'] = $user['firstName'] . ' ' . $user['lastName'];
            $found = true;
            break;
        }
    }
    unset($user);

    if (!$found) {
        jsonResponse(['success' => false, 'message' => 'User not found'], 404);
    }

    if (!writeJsonFile('users.json', $users)) {
        // Best-effort rollback
        if ($transactionsWereConverted && is_array($transactionsBackup)) {
            writeJsonFile('transactions.json', $transactionsBackup);
        }
        jsonResponse(['success' => false, 'message' => 'Failed to update profile'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'Profile updated successfully']);
}

/**
 * Delete the current user account and all user-owned data.
 */
function handleDeleteAccount() {
    if (isGuestMode()) {
        jsonResponse(['success' => false, 'message' => 'Guest Mode account cannot be deleted'], 400);
    }

    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
    }

    $userId = $_SESSION['user_id'];

    // Remove user from users.json
    $users = readJsonFile('users.json');
    $initialUserCount = count($users);
    $users = array_values(array_filter($users, function ($u) use ($userId) {
        return isset($u['id']) && $u['id'] !== $userId;
    }));

    if (count($users) === $initialUserCount) {
        jsonResponse(['success' => false, 'message' => 'User not found'], 404);
    }

    if (!writeJsonFile('users.json', $users)) {
        jsonResponse(['success' => false, 'message' => 'Failed to delete account'], 500);
    }

    // Remove user's transactions
    $transactions = readJsonFile('transactions.json');
    $transactions = array_values(array_filter($transactions, function ($t) use ($userId) {
        return !(isset($t['userId']) && $t['userId'] === $userId);
    }));

    if (!writeJsonFile('transactions.json', $transactions)) {
        jsonResponse(['success' => false, 'message' => 'Account deleted, but failed to clean up transactions'], 500);
    }

    // Remove per-user categories file (best-effort)
    $categoriesFile = getUserCategoriesFilename($userId);
    $categoriesPath = DATA_PATH . $categoriesFile;
    if (file_exists($categoriesPath)) {
        @unlink($categoriesPath);
    }

    // Destroy session
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();

    jsonResponse(['success' => true, 'message' => 'Account deleted successfully']);
}
?>