<?php
/**
 * SpendSmart - Registration Handler
 * Processes new user registration requests
 * Validates input and creates new user accounts
 */
require_once 'auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$firstName = isset($input['firstName']) ? sanitize($input['firstName']) : '';
$lastName = isset($input['lastName']) ? sanitize($input['lastName']) : '';
$email = isset($input['email']) ? sanitize($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$defaultCurrency = isset($input['defaultCurrency']) ? strtoupper(trim((string)sanitize($input['defaultCurrency']))) : 'EUR';

// Validate required fields
if (empty($firstName)) {
    jsonResponse(['success' => false, 'message' => 'First name is required'], 400);
}
if (empty($lastName)) {
    jsonResponse(['success' => false, 'message' => 'Last name is required'], 400);
}
if (empty($email)) {
    jsonResponse(['success' => false, 'message' => 'Email is required'], 400);
}
if (empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Password is required'], 400);
}

if ($defaultCurrency === '') {
    $defaultCurrency = 'EUR';
}

if (!currencyCodeExists($defaultCurrency)) {
    jsonResponse(['success' => false, 'message' => 'Invalid currency'], 400);
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Invalid email format'], 400);
}

// Validate password strength
$pwCheck = validatePasswordStrength($password);
if (!$pwCheck['ok']) {
    jsonResponse(['success' => false, 'message' => $pwCheck['message']], 400);
}

// Read existing users
$users = readJsonFile('users.json');

// Check if email already exists
foreach ($users as $user) {
    if (strtolower($user['email']) === strtolower($email)) {
        jsonResponse(['success' => false, 'message' => 'An account with this email already exists'], 409);
    }
}

// Create new user
$newUser = [
    'id' => generateId('usr'),
    'firstName' => $firstName,
    'lastName' => $lastName,
    'email' => strtolower($email),
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'defaultCurrency' => $defaultCurrency,
    'createdAt' => date('c'),
    'updatedAt' => date('c')
];

// Add to users array and save
$users[] = $newUser;
if (!writeJsonFile('users.json', $users)) {
    jsonResponse(['success' => false, 'message' => 'Failed to save user data'], 500);
}

// Auto-login after registration
$_SESSION['user_id'] = $newUser['id'];
$_SESSION['user_email'] = $newUser['email'];
$_SESSION['user_name'] = $newUser['firstName'] . ' ' . $newUser['lastName'];
$_SESSION['guest_mode'] = false;

jsonResponse([
    'success' => true,
    'message' => 'Account created successfully',
    'user' => [
        'id' => $newUser['id'],
        'firstName' => $newUser['firstName'],
        'lastName' => $newUser['lastName'],
        'email' => $newUser['email'],
        'defaultCurrency' => $newUser['defaultCurrency']
    ]
], 201);
?>