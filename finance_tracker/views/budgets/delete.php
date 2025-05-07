<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Budget.php';

requireLogin();

// Get current user ID
$userId = $_SESSION['user_id'];

// Get budget ID
if (!isset($_GET['id'])) {
    die('Budget ID not provided.');
}

$budgetId = (int)$_GET['id'];

// Fetch budget details
$budget = Budget::getById($budgetId);

if (!$budget || $budget['User_ID'] != $userId) {
    die('Budget not found or unauthorized.');
}

// Delete budget using static method
$result = Budget::delete($budgetId);  // Memanggil delete secara statis

if ($result) {
    // Redirect to the budget list with a success message
    header('Location: view.php?message=Budget deleted successfully.');
} else {
    die('Failed to delete budget.');
}

exit;
?>
