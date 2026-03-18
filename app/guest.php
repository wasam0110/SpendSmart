<?php
/**
 * SpendSmart - Guest Mode Handler
 * Enables guest/demo mode with sample data
 */
require_once 'auth.php';

// Set guest mode session
$_SESSION['guest_mode'] = true;
$_SESSION['user_id'] = 'guest';
$_SESSION['user_email'] = 'guest@spendsmart.com';
$_SESSION['user_name'] = 'Guest User';

// Start with empty data (guest can add their own transactions)
$_SESSION['guest_transactions'] = [];
unset($_SESSION['guest_categories']);

// If AJAX request, respond with JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    jsonResponse(['success' => true, 'message' => 'Guest mode activated']);
}

// Redirect to dashboard
header('Location: ../dashboard.php');
exit;
?>