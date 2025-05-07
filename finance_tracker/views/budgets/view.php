<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Budget.php';

// Require login
requireLogin();

// Get current user ID
$userId = $_SESSION['user_id'];

// Get filter parameters
$filterMonth = $_GET['month'] ?? getCurrentMonth();

// Get months for filter dropdown
$months = getMonthsList();

// Get budget summary for selected month
$budgets = Budget::getBudgetSummary($userId, $filterMonth);

// Calculate totals
$totalBudget = 0;
$totalSpent = 0;

foreach ($budgets as $budget) {
    $totalBudget += $budget['budget_amount'];
    $totalSpent += $budget['spent_amount'];
}

// Set page title
$page_title = "Budgets";

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Budgets</h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Add Budget
    </a>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" class="row g-3">
            <!-- Month Filter -->
            <div class="col-md-4">
                <label for="month" class="form-label">Month</label>
                <select class="form-select" id="month" name="month" onchange="this.form.submit()">
                    <?php foreach ($months as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $filterMonth === $value ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Total Budget</h5>
                <h3><?php echo formatCurrency($totalBudget); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Total Spent</h5>
                <h3 class="<?php echo $totalSpent > $totalBudget ? 'text-expense' : ''; ?>">
                    <?php echo formatCurrency($totalSpent); ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Remaining</h5>
                <h3 class="<?php echo ($totalBudget - $totalSpent) >= 0 ? 'text-income' : 'text-expense'; ?>">
                    <?php echo formatCurrency($totalBudget - $totalSpent); ?>
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- Budgets List -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Budget Details for <?php echo formatMonth($filterMonth); ?></h5>
    </div>
    <div class="card-body">
        <?php if (empty($budgets)): ?>
            <div class="text-center py-4">
                <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                <p>No budgets set for this month.</p>
                <a href="add.php" class="btn btn-primary">Add Budget</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Budget Amount</th>
                            <th>Spent Amount</th>
                            <th>Remaining</th>
                            <th>Progress</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($budgets as $budget): ?>
                            <?php 
                                $remaining = $budget['budget_amount'] - $budget['spent_amount'];
                                $percentage = $budget['budget_amount'] > 0 ? 
                                    ($budget['spent_amount'] / $budget['budget_amount']) * 100 : 0;
                                $percentage = min(100, $percentage);
                                
                                if ($percentage < 70) {
                                    $progressClass = 'bg-success';
                                } else if ($percentage < 90) {
                                    $progressClass = 'bg-warning';
                                } else {
                                    $progressClass = 'bg-danger';
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($budget['Category_Name']); ?></td>
                                <td><?php echo formatCurrency($budget['budget_amount']); ?></td>
                                <td><?php echo formatCurrency($budget['spent_amount']); ?></td>
                                <td class="<?php echo $remaining >= 0 ? 'text-income' : 'text-expense'; ?>">
                                    <?php echo formatCurrency($remaining); ?>
                                </td>
                                <td>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar <?php echo $progressClass; ?>" 
                                            role="progressbar" 
                                            style="width: <?php echo $percentage; ?>%" 
                                            aria-valuenow="<?php echo $percentage; ?>" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100"></div>
                                    </div>
                                    <small class="d-block mt-1"><?php echo round($percentage, 1); ?>% used</small>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo $budget['Budget_ID']; ?>" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="tooltip" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $budget['Budget_ID']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-bs-toggle="tooltip" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/../../includes/footer.php';
?>