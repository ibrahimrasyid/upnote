<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Report.php';
require_once __DIR__ . '/../../classes/Transaction.php';

// Require login
requireLogin();

// Get current user ID
$userId = $_SESSION['user_id'];

// Get date parameters
$periodStart = $_GET['period_start'] ?? date('Y-m-01'); // First day of current month
$periodEnd = $_GET['period_end'] ?? date('Y-m-t'); // Last day of current month

// Generate or update report
$reportResult = Report::generate($userId, $periodStart, $periodEnd);

if ($reportResult['success']) {
    $reportId = $reportResult['report_id'];
    $report = Report::getById($reportId);
    
    // Get report details
    $income = $report['Total_Income'];
    $expense = $report['Total_Expense'];
    $balance = $income - $expense;
    
    // Get transactions for this period
    $transactions = Transaction::getByDateRange($userId, $periodStart, $periodEnd);
    
    // Get category breakdown
    $expenseCategories = Report::getCategoryBreakdown($userId, $periodStart, $periodEnd, 'expense');
    $incomeCategories = Report::getCategoryBreakdown($userId, $periodStart, $periodEnd, 'income');
    
    // Prepare data for expense pie chart
    $expenseCategoryNames = array_column($expenseCategories, 'Category_Name');
    $expenseCategoryAmounts = array_column($expenseCategories, 'total');
    
    // Prepare data for income pie chart
    $incomeCategoryNames = array_column($incomeCategories, 'Category_Name');
    $incomeCategoryAmounts = array_column($incomeCategories, 'total');
}

// Set page title
$page_title = "Financial Report";

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Financial Report</h1>
    <?php if (isset($reportId)): ?>
        <a href="export.php?id=<?php echo $reportId; ?>" class="btn btn-success" target="_blank">
            <i class="fas fa-file-export me-1"></i>Export Report
        </a>
        <!--<a href="export_pdf.php?id=<?php echo $reportId; ?>" class="btn btn-danger" target="_blank">
    <i class="fas fa-file-pdf me-1"></i>Export to PDF
</a> -->

    <?php endif; ?>
</div>

<!-- Date Range Selection Form -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" class="row g-3">
            <div class="col-md-5">
                <label for="period_start" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="period_start" name="period_start" value="<?php echo $periodStart; ?>" required>
            </div>
            <div class="col-md-5">
                <label for="period_end" class="form-label">End Date</label>
                <input type="date" class="form-control" id="period_end" name="period_end" value="<?php echo $periodEnd; ?>" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<?php if (isset($reportResult) && $reportResult['success']): ?>
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-income">
                <div class="card-body">
                    <h5 class="card-title">Total Income</h5>
                    <h3 class="text-income"><?php echo formatCurrency($income); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-expense">
                <div class="card-body">
                    <h5 class="card-title">Total Expense</h5>
                    <h3 class="text-expense"><?php echo formatCurrency($expense); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-balance">
                <div class="card-body">
                    <h5 class="card-title">Balance</h5>
                    <h3 class="<?php echo $balance >= 0 ? 'text-income' : 'text-expense'; ?>">
                        <?php echo formatCurrency($balance); ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <!-- Expense Breakdown -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Expense Breakdown</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($expenseCategories)): ?>
                        <div class="text-center py-4">
                            <p>No expense data available for this period.</p>
                        </div>
                    <?php else: ?>
                        <div style="height: 300px;">
                            <canvas id="expensePieChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Income Breakdown -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Income Breakdown</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($incomeCategories)): ?>
                        <div class="text-center py-4">
                            <p>No income data available for this period.</p>
                        </div>
                    <?php else: ?>
                        <div style="height: 300px;">
                            <canvas id="incomePieChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Transactions for Selected Period</h5>
        </div>
        <div class="card-body">
            <?php if (empty($transactions)): ?>
                <div class="text-center py-4">
                    <p>No transactions found for this period.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
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
                                    <td><?php echo formatDate($transaction['Date']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Category_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Description']); ?></td>
                                    <td>
                                        <?php if ($transaction['Type'] === 'income'): ?>
                                            <span class="badge bg-success">Income</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Expense</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="<?php echo $transaction['Type'] === 'income' ? 'text-income' : 'text-expense'; ?>">
                                        <?php echo formatCurrency($transaction['Amount']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Initialize Charts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($expenseCategories)): ?>
            // Expense Pie Chart
            createPieChart('expensePieChart', 
                <?php echo json_encode($expenseCategoryNames); ?>,
                <?php echo json_encode($expenseCategoryAmounts); ?>
            );
        <?php endif; ?>
        
        <?php if (!empty($incomeCategories)): ?>
            // Income Pie Chart
            createPieChart('incomePieChart', 
                <?php echo json_encode($incomeCategoryNames); ?>,
                <?php echo json_encode($incomeCategoryAmounts); ?>
            );
        <?php endif; ?>
    });
    </script>
<?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Select a date range and click "Generate Report" to view your financial summary.
    </div>
<?php endif; ?>

<?php
// Include footer
include_once __DIR__ . '/../../includes/footer.php';
?>