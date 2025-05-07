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

// Check if report ID is set
if (!isset($_GET['id'])) {
    die('Report ID not provided.');
}

$reportId = (int) $_GET['id'];
$userId = $_SESSION['user_id'];

// Fetch report by ID
$report = Report::getById($reportId);

if (!$report || $report['User_ID'] != $userId) {
    die('Unauthorized access or report not found.');
}

// Get transactions within report period
$periodStart = $report['Period_Start'];
$periodEnd = $report['Period_End'];
$transactions = Transaction::getByDateRange($userId, $periodStart, $periodEnd);

// Set headers to force download as CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="financial_report_' . date('Ymd') . '.csv"');

$output = fopen('php://output', 'w');

// =============================================
// Write Cover Information (template atas)
// =============================================

fputcsv($output, ['Financial Report']); 
fputcsv($output, ['Generated Date', date('Y-m-d')]);
fputcsv($output, ['Report Period', $periodStart . ' to ' . $periodEnd]);
fputcsv($output, []); // Empty line
fputcsv($output, []); // Empty line

// =============================================
// Write Table Header
// =============================================

fputcsv($output, ['Date', 'Category', 'Description', 'Type', 'Amount']);

// Initialize total counters
$totalIncome = 0;
$totalExpense = 0;

// =============================================
// Write Each Transaction
// =============================================

foreach ($transactions as $transaction) {
    fputcsv($output, [
        $transaction['Date'],
        $transaction['Category_Name'],
        $transaction['Description'],
        ucfirst($transaction['Type']),
        number_format($transaction['Amount'], 2)
    ]);

    // Update totals
    if ($transaction['Type'] === 'income') {
        $totalIncome += $transaction['Amount'];
    } else {
        $totalExpense += $transaction['Amount'];
    }
}

// Calculate balance
$balance = $totalIncome - $totalExpense;

// =============================================
// Footer Summary
// =============================================

fputcsv($output, []); // Empty line
fputcsv($output, []); // Empty line
fputcsv($output, ['Summary']);
fputcsv($output, ['Total Income', number_format($totalIncome, 2)]);
fputcsv($output, ['Total Expense', number_format($totalExpense, 2)]);
fputcsv($output, ['Balance', number_format($balance, 2)]);

fclose($output);
exit;
?>
