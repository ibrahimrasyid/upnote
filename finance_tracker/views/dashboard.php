<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Transaction.php';
require_once __DIR__ . '/../classes/Budget.php';
require_once __DIR__ . '/../classes/Goal.php';
require_once __DIR__ . '/../classes/Report.php';

// Require login
requireLogin();

// Get current user ID
$userId = $_SESSION['user_id'];

// Get current month
$currentMonth = getCurrentMonth();

// Get monthly summary
$monthlySummary = Transaction::getSummaryByMonth($userId, $currentMonth);
$income = $monthlySummary['income'] ?: 0;
$expense = $monthlySummary['expense'] ?: 0;
$balance = $monthlySummary['balance'] ?: 0;

// Get budget summary
$budgetSummary = Budget::getBudgetSummary($userId, $currentMonth);

// Get active goals
$activeGoals = Goal::getActiveByUserId($userId);

// Get recent transactions
$recentTransactions = Transaction::getByUserId($userId, 5);

// Get monthly comparison data for chart
$monthlyData = Report::getMonthlyComparisonData($userId);

// Format data for chart
$chartLabels = array_column($monthlyData, 'month');
$incomeData = array_column($monthlyData, 'income');
$expenseData = array_column($monthlyData, 'expense');

// Set page title
$page_title = "Dashboard";

// Include header
include_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Dashboard</h1>
    <div>
        <a href="transactions/add.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add Transaction
        </a>
    </div>
</div>

<!-- Monthly Summary Cards -->
<div class="row">
    <div class="col-md-4">
        <div class="card card-income">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="icon-box icon-income">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">Monthly Income</h6>
                        <h2 class="card-title mb-0 text-income"><?php echo formatCurrency($income); ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-expense">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="icon-box icon-expense">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">Monthly Expense</h6>
                        <h2 class="card-title mb-0 text-expense"><?php echo formatCurrency($expense); ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-balance">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="icon-box icon-balance">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">Monthly Balance</h6>
                        <h2 class="card-title mb-0 <?php echo $balance >= 0 ? 'text-income' : 'text-expense'; ?>">
                            <?php echo formatCurrency($balance); ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Monthly Chart -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Income vs Expenses</h5>
                <div class="text-muted small">Last 6 months</div>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="incomeExpenseChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Budget Overview -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Budget Overview</h5>
                <a href="budgets/view.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($budgetSummary)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <p>No budgets set for this month.</p>
                        <a href="budgets/add.php" class="btn btn-sm btn-primary">Set Budget</a>
                    </div>
                <?php else: ?>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($budgetSummary as $budget): ?>
                            <?php 
                                $percentage = $budget['budget_amount'] > 0 ? 
                                    ($budget['spent_amount'] / $budget['budget_amount']) * 100 : 0;
                                $percentage = min(100, $percentage);
                                
                                if ($percentage < 70) {
                                    $progressClass = 'budget-safe';
                                } else if ($percentage < 90) {
                                    $progressClass = 'budget-warning';
                                } else {
                                    $progressClass = 'budget-danger';
                                }
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span><?php echo $budget['Category_Name']; ?></span>
                                    <span class="small">
                                        <?php echo formatCurrency($budget['spent_amount']); ?> / 
                                        <?php echo formatCurrency($budget['budget_amount']); ?>
                                    </span>
                                </div>
                                <div class="progress budget-progress-bar">
                                    <div class="progress-bar <?php echo $progressClass; ?>" 
                                        role="progressbar" 
                                        style="width: <?php echo $percentage; ?>%" 
                                        aria-valuenow="<?php echo $percentage; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Transactions -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Transactions</h5>
                <a href="transactions/view.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentTransactions)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                        <p>No transactions found.</p>
                        <a href="transactions/add.php" class="btn btn-sm btn-primary">Add Transaction</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo formatDate($transaction['Date']); ?></td>
                                        <td><?php echo $transaction['Category_Name']; ?></td>
                                        <td><?php echo $transaction['Description']; ?></td>
                                        <td class="<?php echo $transaction['Type'] === 'income' ? 'text-income' : 'text-expense'; ?>">
                                            <?php echo $transaction['Type'] === 'income' ? '+' : '-'; ?>
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
    </div>
    
    <!-- Financial Goals -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Financial Goals</h5>
                <a href="goals/view.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($activeGoals)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
                        <p>No active goals found.</p>
                        <a href="goals/add.php" class="btn btn-sm btn-primary">Add Goal</a>
                    </div>
                <?php else: ?>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($activeGoals as $goal): ?>
                            <?php 
                                $goalObj = new Goal($goal['Goal_ID']);
                                $percentage = $goalObj->getPercentage();
                                $daysRemaining = $goalObj->getDaysRemaining();
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span><?php echo htmlspecialchars($goal['Target_Amount']); ?></span>
                                    <span class="badge <?php echo $goal['Status'] === 'in_progress' ? 'bg-primary' : 'bg-warning'; ?>">
                                        <?php echo ucfirst($goal['Status']); ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1 small">
                                    <span>
                                        Progress: <?php echo formatCurrency($goal['Progress']); ?> / 
                                        <?php echo formatCurrency($goal['Target_Amount']); ?>
                                    </span>
                                    <span><?php echo $daysRemaining; ?> days left</span>
                                </div>
                                <div class="progress goal-progress">
                                    <div class="progress-bar bg-success" 
                                        role="progressbar" 
                                        style="width: <?php echo $percentage; ?>%" 
                                        aria-valuenow="<?php echo $percentage; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Initialize Charts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Income vs Expense Chart
    createBarChart('incomeExpenseChart', 
        <?php echo json_encode($chartLabels); ?>,
        [
            {
                label: 'Income',
                data: <?php echo json_encode($incomeData); ?>,
                backgroundColor: chartColors.income
            },
            {
                label: 'Expense',
                data: <?php echo json_encode($expenseData); ?>,
                backgroundColor: chartColors.expense
            }
        ]
    );
});
</script>

<?php
// Include footer
include_once __DIR__ . '/../includes/footer.php';
?>