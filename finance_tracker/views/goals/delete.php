<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Goal.php';

// Require login
requireLogin();

// Get current user ID
$userId = $_SESSION['user_id'];

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMessage('error', 'Goal ID is missing.');
    header("Location: view.php");
    exit;
}

$goalId = clean_input($_GET['id']);

// Get goal data
$goalData = Goal::getById($goalId);

// Check if goal exists and belongs to current user
if (!$goalData || $goalData['User_ID'] != $userId) {
    setMessage('error', 'Goal not found or access denied.');
    header("Location: view.php");
    exit;
}

// Create a Goal instance
$goal = new Goal($goalId);

// Delete the goal
$result = $goal->delete();

if ($result['success']) {
    setMessage('success', 'Goal deleted successfully.');
} else {
    setMessage('error', 'Failed to delete goal. Please try again.');
}

// Redirect back to goals list
header("Location: view.php");
exit;
?>