<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Transaction.php';

requireLogin();

// Get user id
$userId = $_SESSION['user_id'];

// Get transaction ID
if (!isset($_GET['id'])) {
    die('Transaction ID not provided.');
}

$transactionId = (int)$_GET['id'];

// Fetch transaction
$transaction = new Transaction($transactionId);

if (!$transaction || $transaction->getUserId() != $userId) {
    die('Transaction not found or unauthorized.');
}

// Delete transaction
$result = $transaction->delete();

if ($result['success']) {
    header('Location: view.php?message=Transaction deleted successfully.');
} else {
    die('Failed to delete transaction.');
}
exit;
?>
