<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Transaction.php';
require_once __DIR__ . '/../../classes/Category.php';

// Require login
requireLogin();

// Get current user ID
$userId = $_SESSION['user_id'];

// Get filter parameters
$filterMonth = $_GET['month'] ?? getCurrentMonth();
$filterType = $_GET['type'] ?? 'all';
$filterCategory = $_GET['category'] ?? 'all';

// Get categories for filter
$categories = Category::getByUserId($userId);

// Apply filters to get transactions
if ($filterType !== 'all' && $filterCategory !== 'all') {
    // Filter by type and category
    $transactions = Transaction::getByCategory($userId, $filterCategory);
    $transactions = array_filter($transactions, function($transaction) use ($filterType) {
        return $transaction['Type'] === $filterType;
    });
} elseif ($filterType !== 'all') {
    // Filter by type only
    $transactions = Transaction::getByType($userId, $filterType);
} elseif ($filterCategory !== 'all') {
    // Filter by category only
    $transactions = Transaction::getByCategory($userId, $filterCategory);
} else {
    // Filter by month only (default)
    $transactions = Transaction::getByMonth($userId, $filterMonth);
}

// Get months for filter dropdown
$months = getMonthsList();

// Calculate totals
$totalIncome = 0;
$totalExpense = 0;

foreach ($transactions as $transaction) {
    if ($transaction['Type'] === 'income') {
        $totalIncome += $transaction['Amount'];
    } else {
        $totalExpense += $transaction['Amount'];
    }
}

$balance = $totalIncome - $totalExpense;

// Set page title
$page_title = "Transactions";

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Transactions</h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Add Transaction
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
            
            <!-- Type Filter -->
            <div class="col-md-4">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                    <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="income" <?php echo $filterType === 'income' ? 'selected' : ''; ?>>Income</option>
                    <option value="expense" <?php echo $filterType === 'expense' ? 'selected' : ''; ?>>Expense</option>
                </select>
            </div>
            
            <!-- Category Filter -->
            <div class="col-md-4">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category" onchange="this.form.submit()">
                    <option value="all" <?php echo $filterCategory === 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['Category_ID']; ?>" <?php echo $filterCategory == $category['Category_ID'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['Category_Name']); ?>
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
        <div class="card card-income">
            <div class="card-body">
                <h5 class="card-title">Total Income</h5>
                <h3 class="text-income"><?php echo formatCurrency($totalIncome); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-expense">
            <div class="card-body">
                <h5 class="card-title">Total Expense</h5>
                <h3 class="text-expense"><?php echo formatCurrency($totalExpense); ?></h3>
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

<!-- Transactions Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Transactions List</h5>
    </div>
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <div class="text-center py-4">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <p>No transactions found matching your filters.</p>
                <a href="add.php" class="btn btn-primary">Add Transaction</a>
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
                            <th>Actions</th>
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
                                <td>
                                    <a href="edit.php?id=<?php echo $transaction['Transaction_ID']; ?>" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="tooltip" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $transaction['Transaction_ID']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-bs-toggle="tooltip" title="Delete">
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