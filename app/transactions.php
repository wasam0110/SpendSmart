<?php
/**
 * SpendSmart - Transaction Handler
 * CRUD operations for financial transactions
 * Supports add, edit, delete, and list operations
 */
require_once 'auth.php';
requireAuth();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetTransactions();
        break;
    case 'POST':
        handleAddTransaction();
        break;
    case 'PUT':
        handleEditTransaction();
        break;
    case 'DELETE':
        handleDeleteTransaction();
        break;
    default:
        jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

/**
 * Get transactions for current user
 * Supports filtering by type, category, date range
 */
function handleGetTransactions() {
    // If guest mode, use session-based storage (starts empty, not saved to file)
    if (isGuestMode()) {
        if (!isset($_SESSION['guest_transactions'])) {
            $_SESSION['guest_transactions'] = [];
        }
        $userTransactions = $_SESSION['guest_transactions'];

        // Apply optional filters
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $type = sanitize($_GET['type']);
            $userTransactions = array_values(array_filter($userTransactions, function($t) use ($type) {
                return $t['type'] === $type;
            }));
        }
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $category = sanitize($_GET['category']);
            $userTransactions = array_values(array_filter($userTransactions, function($t) use ($category) {
                return $t['category'] === $category;
            }));
        }

        usort($userTransactions, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        jsonResponse(['success' => true, 'transactions' => $userTransactions]);
    }
    
    $transactions = readJsonFile('transactions.json');
    $userId = $_SESSION['user_id'];
    
    // Filter transactions for current user
    $userTransactions = array_values(array_filter($transactions, function($t) use ($userId) {
        return $t['userId'] === $userId;
    }));
    
    // Apply optional filters
    if (isset($_GET['type']) && !empty($_GET['type'])) {
        $type = sanitize($_GET['type']);
        $userTransactions = array_values(array_filter($userTransactions, function($t) use ($type) {
            return $t['type'] === $type;
        }));
    }
    
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $category = sanitize($_GET['category']);
        $userTransactions = array_values(array_filter($userTransactions, function($t) use ($category) {
            return $t['category'] === $category;
        }));
    }
    
    if (isset($_GET['startDate']) && !empty($_GET['startDate'])) {
        $startDate = sanitize($_GET['startDate']);
        $userTransactions = array_values(array_filter($userTransactions, function($t) use ($startDate) {
            return $t['date'] >= $startDate;
        }));
    }
    
    if (isset($_GET['endDate']) && !empty($_GET['endDate'])) {
        $endDate = sanitize($_GET['endDate']);
        $userTransactions = array_values(array_filter($userTransactions, function($t) use ($endDate) {
            return $t['date'] <= $endDate;
        }));
    }
    
    // Sort by date descending (most recent first)
    usort($userTransactions, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
    
    jsonResponse(['success' => true, 'transactions' => $userTransactions]);
}

/**
 * Add a new transaction
 */
function handleAddTransaction() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // Validate required fields
    $required = ['date', 'name', 'category', 'amount', 'currency', 'type'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            jsonResponse(['success' => false, 'message' => ucfirst($field) . ' is required'], 400);
        }
    }

    $name = sanitize($input['name']);
    $date = sanitize($input['date']);
    $category = sanitize($input['category']);
    $amount = floatval($input['amount']);
    $currency = sanitize($input['currency']);
    $type = sanitize($input['type']);

    if (!currencyCodeExists($currency)) {
        jsonResponse(['success' => false, 'message' => 'Invalid currency'], 400);
    }

    // Validate amount
    if ($amount <= 0) {
        jsonResponse(['success' => false, 'message' => 'Amount must be greater than zero'], 400);
    }

    // Validate type
    if (!in_array($type, ['income', 'expense'])) {
        jsonResponse(['success' => false, 'message' => 'Type must be income or expense'], 400);
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        jsonResponse(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD'], 400);
    }

    if (!isValidCategoryForType($category, $type)) {
        jsonResponse(['success' => false, 'message' => 'Selected category does not match the transaction type'], 400);
    }

    // Create transaction
    $transaction = [
        'id' => generateId('txn'),
        'userId' => $_SESSION['user_id'],
        'date' => $date,
        'name' => $name,
        'category' => $category,
        'amount' => $amount,
        'currency' => $currency,
        'type' => $type,
        'createdAt' => date('c')
    ];

    // Guest mode: store in session (temporary, not saved to file)
    if (isGuestMode()) {
        if (!isset($_SESSION['guest_transactions'])) {
            $_SESSION['guest_transactions'] = [];
        }
        $_SESSION['guest_transactions'][] = $transaction;
        jsonResponse(['success' => true, 'message' => 'Transaction added (Guest Mode - not saved permanently)', 'transaction' => $transaction], 201);
    }

    $transactions = readJsonFile('transactions.json');
    $transactions[] = $transaction;
    
    if (!writeJsonFile('transactions.json', $transactions)) {
        jsonResponse(['success' => false, 'message' => 'Failed to save transaction'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'Transaction added successfully', 'transaction' => $transaction], 201);
}

/**
 * Edit an existing transaction
 */
function handleEditTransaction() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || empty($input['id'])) {
        jsonResponse(['success' => false, 'message' => 'Transaction ID is required'], 400);
    }

    // Guest mode: edit in session storage
    if (isGuestMode()) {
        if (!isset($_SESSION['guest_transactions'])) {
            $_SESSION['guest_transactions'] = [];
        }
        $found = false;
        foreach ($_SESSION['guest_transactions'] as &$txn) {
            if ($txn['id'] === $input['id']) {
                $updatedType = isset($input['type']) ? sanitize($input['type']) : $txn['type'];
                $updatedCategory = isset($input['category']) ? sanitize($input['category']) : $txn['category'];

                if (!in_array($updatedType, ['income', 'expense'], true)) {
                    jsonResponse(['success' => false, 'message' => 'Type must be income or expense'], 400);
                }

                if (!isValidCategoryForType($updatedCategory, $updatedType)) {
                    jsonResponse(['success' => false, 'message' => 'Selected category does not match the transaction type'], 400);
                }

                if (isset($input['date'])) $txn['date'] = sanitize($input['date']);
                if (isset($input['name'])) $txn['name'] = sanitize($input['name']);
                if (isset($input['category'])) $txn['category'] = $updatedCategory;
                if (isset($input['amount'])) $txn['amount'] = floatval($input['amount']);
                if (isset($input['currency'])) {
                    $newCur = sanitize($input['currency']);
                    if (!currencyCodeExists($newCur)) {
                        jsonResponse(['success' => false, 'message' => 'Invalid currency'], 400);
                    }
                    $txn['currency'] = $newCur;
                }
                if (isset($input['type'])) $txn['type'] = $updatedType;
                $txn['updatedAt'] = date('c');
                $found = true;
                break;
            }
        }
        unset($txn);
        if (!$found) {
            jsonResponse(['success' => false, 'message' => 'Transaction not found'], 404);
        }
        jsonResponse(['success' => true, 'message' => 'Transaction updated (Guest Mode)']);
    }

    $transactions = readJsonFile('transactions.json');
    $found = false;

    foreach ($transactions as &$transaction) {
        if ($transaction['id'] === $input['id'] && $transaction['userId'] === $_SESSION['user_id']) {
            $updatedType = isset($input['type']) ? sanitize($input['type']) : $transaction['type'];
            $updatedCategory = isset($input['category']) ? sanitize($input['category']) : $transaction['category'];

            if (!in_array($updatedType, ['income', 'expense'], true)) {
                jsonResponse(['success' => false, 'message' => 'Type must be income or expense'], 400);
            }

            if (!isValidCategoryForType($updatedCategory, $updatedType)) {
                jsonResponse(['success' => false, 'message' => 'Selected category does not match the transaction type'], 400);
            }

            // Update fields if provided
            if (isset($input['date'])) $transaction['date'] = sanitize($input['date']);
            if (isset($input['name'])) $transaction['name'] = sanitize($input['name']);
            if (isset($input['category'])) $transaction['category'] = $updatedCategory;
            if (isset($input['amount'])) {
                $amount = floatval($input['amount']);
                if ($amount <= 0) {
                    jsonResponse(['success' => false, 'message' => 'Amount must be greater than zero'], 400);
                }
                $transaction['amount'] = $amount;
            }
            if (isset($input['currency'])) {
                $newCur = sanitize($input['currency']);
                if (!currencyCodeExists($newCur)) {
                    jsonResponse(['success' => false, 'message' => 'Invalid currency'], 400);
                }
                $transaction['currency'] = $newCur;
            }
            if (isset($input['type'])) {
                $transaction['type'] = $updatedType;
            }
            $transaction['updatedAt'] = date('c');
            $found = true;
            break;
        }
    }
    unset($transaction);

    if (!$found) {
        jsonResponse(['success' => false, 'message' => 'Transaction not found'], 404);
    }

    if (!writeJsonFile('transactions.json', $transactions)) {
        jsonResponse(['success' => false, 'message' => 'Failed to update transaction'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'Transaction updated successfully']);
}

/**
 * Delete a transaction
 */
function handleDeleteTransaction() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || empty($input['id'])) {
        jsonResponse(['success' => false, 'message' => 'Transaction ID is required'], 400);
    }

    // Guest mode: delete from session storage
    if (isGuestMode()) {
        if (!isset($_SESSION['guest_transactions'])) {
            $_SESSION['guest_transactions'] = [];
        }
        $initialCount = count($_SESSION['guest_transactions']);
        $_SESSION['guest_transactions'] = array_values(array_filter($_SESSION['guest_transactions'], function($t) use ($input) {
            return $t['id'] !== $input['id'];
        }));
        if (count($_SESSION['guest_transactions']) === $initialCount) {
            jsonResponse(['success' => false, 'message' => 'Transaction not found'], 404);
        }
        jsonResponse(['success' => true, 'message' => 'Transaction deleted (Guest Mode)']);
    }

    $transactions = readJsonFile('transactions.json');
    $initialCount = count($transactions);

    $transactions = array_values(array_filter($transactions, function($t) use ($input) {
        return !($t['id'] === $input['id'] && $t['userId'] === $_SESSION['user_id']);
    }));

    if (count($transactions) === $initialCount) {
        jsonResponse(['success' => false, 'message' => 'Transaction not found'], 404);
    }

    if (!writeJsonFile('transactions.json', $transactions)) {
        jsonResponse(['success' => false, 'message' => 'Failed to delete transaction'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'Transaction deleted successfully']);
}

function isValidCategoryForType($categoryName, $type) {
    $categories = getAvailableCategoriesForCurrentSession();

    foreach ($categories as $category) {
        if (strcasecmp($category['name'], $categoryName) === 0) {
            return getTransactionCategoryType($category) === $type;
        }
    }

    return false;
}

function getAvailableCategoriesForCurrentSession() {
    return normalizeTransactionCategories(getCategoriesForCurrentSession());
}

function normalizeTransactionCategories($categories) {
    if (!is_array($categories)) {
        return [];
    }

    return array_map(function ($category) {
        if (!is_array($category)) {
            $category = [];
        }

        $category['name'] = isset($category['name']) ? $category['name'] : '';
        $category['icon'] = isset($category['icon']) ? $category['icon'] : 'tag';
        $category['type'] = getTransactionCategoryType($category);
        return $category;
    }, $categories);
}

function getTransactionCategoryType($category) {
    $type = isset($category['type']) ? strtolower(trim((string) $category['type'])) : '';
    if (in_array($type, ['income', 'expense'], true)) {
        return $type;
    }

    $name = strtolower(isset($category['name']) ? (string) $category['name'] : '');
    $icon = strtolower(isset($category['icon']) ? (string) $category['icon'] : '');
    $incomeIcons = ['wallet', 'money-bill'];
    $incomeKeywords = ['income', 'salary', 'freelance', 'bonus', 'allowance', 'interest', 'refund', 'revenue'];

    if (in_array($icon, $incomeIcons, true)) {
        return 'income';
    }

    foreach ($incomeKeywords as $keyword) {
        if ($name !== '' && strpos($name, $keyword) !== false) {
            return 'income';
        }
    }

    return 'expense';
}

/**
 * Sample transactions for guest/demo mode
 */
function getSampleTransactions() {
    return [
        ['id' => 'demo_1', 'userId' => 'guest', 'date' => '2026-03-12', 'name' => 'Grocery Store', 'category' => 'Food', 'amount' => 85.50, 'currency' => 'USD', 'type' => 'expense', 'createdAt' => '2026-03-12T10:00:00'],
        ['id' => 'demo_2', 'userId' => 'guest', 'date' => '2026-03-11', 'name' => 'Salary Deposit', 'category' => 'Salary', 'amount' => 3200.00, 'currency' => 'USD', 'type' => 'income', 'createdAt' => '2026-03-11T09:00:00'],
        ['id' => 'demo_3', 'userId' => 'guest', 'date' => '2026-03-11', 'name' => 'Electric Bill', 'category' => 'Utilities', 'amount' => 120.00, 'currency' => 'USD', 'type' => 'expense', 'createdAt' => '2026-03-11T14:00:00'],
        ['id' => 'demo_4', 'userId' => 'guest', 'date' => '2026-03-10', 'name' => 'Netflix Subscription', 'category' => 'Entertainment', 'amount' => 15.99, 'currency' => 'USD', 'type' => 'expense', 'createdAt' => '2026-03-10T08:00:00'],
        ['id' => 'demo_5', 'userId' => 'guest', 'date' => '2026-03-09', 'name' => 'Coffee Shop', 'category' => 'Food', 'amount' => 4.50, 'currency' => 'USD', 'type' => 'expense', 'createdAt' => '2026-03-09T07:30:00'],
        ['id' => 'demo_6', 'userId' => 'guest', 'date' => '2026-03-08', 'name' => 'Freelance Payment', 'category' => 'Income', 'amount' => 500.00, 'currency' => 'USD', 'type' => 'income', 'createdAt' => '2026-03-08T16:00:00'],
        ['id' => 'demo_7', 'userId' => 'guest', 'date' => '2026-03-07', 'name' => 'Gas Station', 'category' => 'Transport', 'amount' => 45.00, 'currency' => 'USD', 'type' => 'expense', 'createdAt' => '2026-03-07T12:00:00'],
        ['id' => 'demo_8', 'userId' => 'guest', 'date' => '2026-03-06', 'name' => 'Textbook Purchase', 'category' => 'Education', 'amount' => 39.99, 'currency' => 'USD', 'type' => 'expense', 'createdAt' => '2026-03-06T11:00:00'],
        ['id' => 'demo_9', 'userId' => 'guest', 'date' => '2026-03-05', 'name' => 'Student Finance', 'category' => 'Income', 'amount' => 1000.00, 'currency' => 'USD', 'type' => 'income', 'createdAt' => '2026-03-05T09:00:00'],
        ['id' => 'demo_10', 'userId' => 'guest', 'date' => '2026-03-04', 'name' => 'Phone Bill', 'category' => 'Utilities', 'amount' => 35.00, 'currency' => 'USD', 'type' => 'expense', 'createdAt' => '2026-03-04T10:00:00'],
        ['id' => 'demo_11', 'userId' => 'guest', 'date' => '2026-02-28', 'name' => 'Salary Deposit', 'category' => 'Salary', 'amount' => 3200.00, 'currency' => 'USD', 'type' => 'income', 'createdAt' => '2026-02-28T09:00:00'],
        ['id' => 'demo_12', 'userId' => 'guest', 'date' => '2026-02-25', 'name' => 'Restaurant Dinner', 'category' => 'Food', 'amount' => 62.00, 'currency' => 'USD', 'type' => 'expense', 'createdAt' => '2026-02-25T19:00:00'],
        ['id' => 'demo_13', 'userId' => 'guest', 'date' => '2026-02-20', 'name' => 'Gym Membership', 'category' => 'Healthcare', 'amount' => 29.99, 'currency' => 'USD', 'type' => 'expense', 'createdAt' => '2026-02-20T08:00:00'],
        ['id' => 'demo_14', 'userId' => 'guest', 'date' => '2026-02-15', 'name' => 'Shopping Mall', 'category' => 'Shopping', 'amount' => 150.00, 'currency' => 'USD', 'type' => 'expense', 'createdAt' => '2026-02-15T14:00:00'],
        ['id' => 'demo_15', 'userId' => 'guest', 'date' => '2026-01-31', 'name' => 'Salary Deposit', 'category' => 'Salary', 'amount' => 3200.00, 'currency' => 'USD', 'type' => 'income', 'createdAt' => '2026-01-31T09:00:00']
    ];
}
?>