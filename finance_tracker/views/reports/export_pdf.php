<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Report.php';
require_once __DIR__ . '/../../classes/Transaction.php';

// Require login
requireLogin();

// Check if report ID is provided
if (!isset($_GET['id'])) {
    die('Report ID not provided.');
}

$reportId = (int) $_GET['id'];
$userId = $_SESSION['user_id'];

// Fetch report
$report = Report::getById($reportId);

if (!$report || $report['User_ID'] != $userId) {
    die('Unauthorized access or report not found.');
}

// Get transactions
$periodStart = $report['Period_Start'];
$periodEnd = $report['Period_End'];
$transactions = Transaction::getByDateRange($userId, $periodStart, $periodEnd);

// Set headers to output as PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="financial_report_' . date('Ymd') . '.pdf"');

// Simple HTML for PDF
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Financial Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <h1>Financial Report</h1>
    <p><strong>Period:</strong> <?php echo htmlspecialchars($periodStart); ?> to <?php echo htmlspecialchars($periodEnd); ?></p>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Category</th>
                <th>Description</th>
                <th>Type</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
            <tr>
                <td><?php echo htmlspecialchars($transaction['Date']); ?></td>
                <td><?php echo htmlspecialchars($transaction['Category_Name']); ?></td>
                <td><?php echo htmlspecialchars($transaction['Description']); ?></td>
                <td><?php echo ucfirst(htmlspecialchars($transaction['Type'])); ?></td>
                <td><?php echo formatCurrency($transaction['Amount']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
