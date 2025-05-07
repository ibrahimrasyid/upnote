<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/User.php';

// Logout user
$result = User::logout();

// Set success message
setMessage('success', $result['message']);

// Redirect to login page
header("Location: login.php");
exit;