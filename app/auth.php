<?php
/**
 * SpendSmart - Session and Authentication Helper
 * Handles session management and user authentication functions
 */
session_start();

// Base path for data files
define('DATA_PATH', __DIR__ . '/../data/');

/**
 * Read JSON data from a file
 * Returns decoded array or empty array on failure
 */
function readJsonFile($filename) {
    $filepath = DATA_PATH . $filename;
    if (!file_exists($filepath)) {
        return [];
    }
    $content = file_get_contents($filepath);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Write JSON data to a file
 * Returns true on success, false on failure
 */
function writeJsonFile($filename, $data) {
    $filepath = DATA_PATH . $filename;
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

require_once __DIR__ . '/Currency.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is in guest mode
 */
function isGuestMode() {
    return isset($_SESSION['guest_mode']) && $_SESSION['guest_mode'] === true;
}

/**
 * Get current logged-in user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    $users = readJsonFile('users.json');
    foreach ($users as $user) {
        if ($user['id'] === $_SESSION['user_id']) {
            return $user;
        }
    }
    return null;
}

/**
 * Generate a unique ID with prefix
 */
function generateId($prefix = 'id') {
    return $prefix . '_' . bin2hex(random_bytes(8));
}

/**
 * Sanitize user input to prevent XSS
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth() {
    if (!isLoggedIn() && !isGuestMode()) {
        header('Location: ../login.php');
        exit;
    }
}

/**
 * Get currencies list
 */
function getCurrencies() {
    return Currency::listSupported();
}

/**
 * Get currency symbol by code
 */
function getCurrencySymbol($code) {
    return Currency::getSymbol((string)$code);
}

/**
 * Check whether a currency code exists in currencies.json.
 */
function currencyCodeExists($code) {
    return Currency::isSupported((string)$code);
}

/**
 * Get exchange rate to USD for a given currency.
 * Returns float|null.
 */
function getCurrencyRateToUSD($code) {
    $code = strtoupper(trim((string)$code));
    if ($code === '') return null;

    // Prefer data-driven rates in currencies.json
    $currencies = getCurrencies();
    foreach ($currencies as $currency) {
        if (!is_array($currency)) continue;
        if (strtoupper((string)($currency['code'] ?? '')) !== $code) continue;

        $rate = $currency['rateToUSD'] ?? null;
        if (is_numeric($rate) && floatval($rate) > 0) {
            return floatval($rate);
        }
        break;
    }

    // Backward-compatible fallback (in case currencies.json has no rates)
    $fallback = [
        'USD' => 1.0,
        'EUR' => 1.09,
        'GBP' => 1.28,
        'JPY' => 0.0067,
        'CAD' => 0.74
    ];

    return array_key_exists($code, $fallback) ? floatval($fallback[$code]) : null;
}

/**
 * Convert an amount between currencies using rateToUSD.
 * Returns float|null (null when a rate is missing).
 */
function convertCurrencyAmount($amount, $fromCode, $toCode) {
    return Currency::convert(floatval($amount), (string)$fromCode, (string)$toCode);
}

/**
 * Validate password strength.
 * Returns an array with keys: ok (bool), message (string).
 */
function validatePasswordStrength($password) {
    if (!is_string($password)) {
        return ['ok' => false, 'message' => 'Password is required'];
    }

    // Disallow whitespace-only passwords and spaces/tabs/newlines
    if (trim($password) === '' || preg_match('/\s/', $password)) {
        return ['ok' => false, 'message' => 'Password must not contain spaces'];
    }

    // Password rule
    // - at least 6 characters
    // - must include at least one letter
    // - must NOT be numbers-only (e.g. 12345678)
    if (strlen($password) < 6) {
        return ['ok' => false, 'message' => 'Password is too weak. Use at least 6 characters and include letters (not numbers only).'];
    }

    if (!preg_match('/[A-Za-z]/', $password)) {
        return ['ok' => false, 'message' => 'Password is too weak. Use at least 6 characters and include letters (not numbers only).'];
    }

    if (preg_match('/^[0-9]+$/', $password)) {
        return ['ok' => false, 'message' => 'Password is too weak. Use at least 6 characters and include letters (not numbers only).'];
    }

    return ['ok' => true, 'message' => ''];
}

/**
 * Create a safe suffix for filenames based on user ID.
 */
function getSafeUserFileSuffix($userId) {
    $userId = (string) $userId;
    // Keep only safe characters for filenames.
    $safe = preg_replace('/[^A-Za-z0-9_-]/', '', $userId);
    return $safe !== '' ? $safe : 'unknown';
}

/**
 * Get per-user categories filename.
 */
function getUserCategoriesFilename($userId) {
    return 'categories_' . getSafeUserFileSuffix($userId) . '.json';
}

/**
 * Initialize per-user categories file from the default categories.json.
 */
function ensureUserCategoriesInitialized($userId) {
    $filename = getUserCategoriesFilename($userId);
    $filepath = DATA_PATH . $filename;

    if (file_exists($filepath)) {
        return;
    }

    $defaultCategories = readJsonFile('categories.json');
    // Best-effort: create a private copy for the user.
    writeJsonFile($filename, is_array($defaultCategories) ? $defaultCategories : []);
}

/**
 * Add any newly introduced default categories into an existing user's category file.
 * This is additive only (does not remove or overwrite user edits).
 */
function syncUserCategoriesWithDefaults($userId) {
    $userId = (string) $userId;
    if ($userId === '') {
        return;
    }

    $defaultCategories = readJsonFile('categories.json');
    $userFilename = getUserCategoriesFilename($userId);
    $userCategories = readJsonFile($userFilename);

    if (!is_array($defaultCategories) || !is_array($userCategories)) {
        return;
    }

    $existing = [];
    foreach ($userCategories as $cat) {
        if (!is_array($cat)) continue;
        $name = strtolower(trim((string)($cat['name'] ?? '')));
        $type = strtolower(trim((string)($cat['type'] ?? '')));
        if ($name === '' || $type === '') continue;
        $existing[$type . '|' . $name] = true;
    }

    $changed = false;
    foreach ($defaultCategories as $cat) {
        if (!is_array($cat)) continue;
        if (empty($cat['isDefault'])) continue;
        $name = strtolower(trim((string)($cat['name'] ?? '')));
        $type = strtolower(trim((string)($cat['type'] ?? '')));
        if ($name === '' || $type === '') continue;
        $key = $type . '|' . $name;
        if (!isset($existing[$key])) {
            $userCategories[] = $cat;
            $existing[$key] = true;
            $changed = true;
        }
    }

    if ($changed) {
        writeJsonFile($userFilename, $userCategories);
    }
}

/**
 * Get categories array for the current session.
 * - Guest mode: stored in session
 * - Logged-in user: stored in per-user JSON file
 */
function getCategoriesForCurrentSession() {
    if (isGuestMode()) {
        if (!isset($_SESSION['guest_categories']) || !is_array($_SESSION['guest_categories'])) {
            $_SESSION['guest_categories'] = readJsonFile('categories.json');
        }
        return is_array($_SESSION['guest_categories']) ? $_SESSION['guest_categories'] : [];
    }

    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return [];
    }

    ensureUserCategoriesInitialized($_SESSION['user_id']);
    syncUserCategoriesWithDefaults($_SESSION['user_id']);
    return readJsonFile(getUserCategoriesFilename($_SESSION['user_id']));
}

/**
 * Save categories array for the current session.
 */
function saveCategoriesForCurrentSession($categories) {
    $categories = is_array($categories) ? $categories : [];

    if (isGuestMode()) {
        $_SESSION['guest_categories'] = $categories;
        return true;
    }

    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }

    ensureUserCategoriesInitialized($_SESSION['user_id']);
    return writeJsonFile(getUserCategoriesFilename($_SESSION['user_id']), $categories);
}
?>