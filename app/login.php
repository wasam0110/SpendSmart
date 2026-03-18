<?php
/**
 * SpendSmart - Login Handler
 * Processes user login requests via POST
 * Validates credentials against stored user data
 */
require_once 'auth.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

// Get POST data (supports both form data and JSON)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$email = isset($input['email']) ? sanitize($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';

// Validate required fields
if (empty($email) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Email and password are required'], 400);
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Invalid email format'], 400);
}

// Read users data
$users = readJsonFile('users.json');

// Find user by email
$foundUser = null;
foreach ($users as $user) {
    if (strtolower($user['email']) === strtolower($email)) {
        $foundUser = $user;
        break;
    }
}

if (!$foundUser) {
    jsonResponse(['success' => false, 'message' => 'User not found'], 401);
}

// Verify password - check both hashed and plain text (for demo account)
$passwordValid = false;
if (password_verify($password, $foundUser['password'])) {
    $passwordValid = true;
} elseif ($foundUser['email'] === 'demo@spendsmart.com' && $password === 'demo123') {
    $passwordValid = true;
}

if (!$passwordValid) {
    jsonResponse(['success' => false, 'message' => 'Incorrect password'], 401);
}

// Set session variables
$_SESSION['user_id'] = $foundUser['id'];
$_SESSION['user_email'] = $foundUser['email'];
$_SESSION['user_name'] = $foundUser['firstName'] . ' ' . $foundUser['lastName'];
$_SESSION['guest_mode'] = false;

jsonResponse([
    'success' => true,
    'message' => 'Login successful',
    'user' => [
        'id' => $foundUser['id'],
        'firstName' => $foundUser['firstName'],
        'lastName' => $foundUser['lastName'],
        'email' => $foundUser['email'],
        'defaultCurrency' => $foundUser['defaultCurrency']
    ]
]);
?>