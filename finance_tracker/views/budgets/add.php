<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Budget.php';
require_once __DIR__ . '/../../classes/Category.php';

// Require login
requireLogin();

// Get current user ID
$userId = $_SESSION['user_id'];

// Get all expense categories
$categories = Category::getByUserIdAndType($userId, 'expense');

// If no expense categories, get all categories
if (empty($categories)) {
    $categories = Category::getByUserId($userId);
}

// Get available months
$months = getMonthsList();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setMessage('error', 'Invalid form submission. Please try again.');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Get form data
    $amount = clean_input($_POST['amount'] ?? '');
    $month = clean_input($_POST['month'] ?? '');
    $categoryId = clean_input($_POST['category_id'] ?? '');
    
    // Validate form data
    $errors = [];
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = 'Please enter a valid amount';
    }
    
    if (empty($month)) {
        $errors[] = 'Please select a month';
    }
    
    if (empty($categoryId)) {
        $errors[] = 'Please select a category';
    }
    
    // Check if budget already exists
    if (Budget::exists($userId, $categoryId, $month)) {
        $errors[] = 'A budget already exists for this category and month';
    }
    
    // If no errors, add budget
    if (empty($errors)) {
        $result = Budget::create($amount, $month, $categoryId, $userId);
        
        if ($result['success']) {
            setMessage('success', $result['message']);
            header("Location: view.php");
            exit;
        } else {
            setMessage('error', $result['message']);
        }
    } else {
        setMessage('error', implode('<br>', $errors));
    }
}

// Set page title
$page_title = "Add Budget";

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Add Budget</h1>
    <a href="view.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Budgets
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <?php if (empty($categories)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                        <p>You need to add categories before setting up budgets.</p>
                        <a href="../categories/add.php?redirect=<?php echo urlencode($_SERVER['PHP_SELF']); ?>" class="btn btn-primary">Add Category</a>
                    </div>
                <?php else: ?>
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="needs-validation" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <!-- Month -->
                        <div class="mb-3">
                            <label for="month" class="form-label required">Month</label>
                            <select class="form-select" id="month" name="month" required>
                                <option value="">Select Month</option>
                                <?php foreach ($months as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo (isset($_POST['month']) && $_POST['month'] === $value) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a month</div>
                        </div>
                        
                        <!-- Category -->
                        <div class="mb-3">
                            <label for="category_id" class="form-label required">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['Category_ID']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['Category_ID']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['Category_Name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a category</div>
                            <div class="mt-2">
                                <a href="../categories/add.php?redirect=<?php echo urlencode($_SERVER['PHP_SELF']); ?>" class="text-primary small">
                                    <i class="fas fa-plus-circle me-1"></i>Add New Category
                                </a>
                            </div>
                        </div>
                        
                        <!-- Amount -->
                        <div class="mb-3">
                            <label for="amount" class="form-label required">Budget Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" id="amount" name="amount" min="0.01" step="0.01" value="<?php echo $_POST['amount'] ?? ''; ?>" required>
                                <div class="invalid-feedback">Please enter a valid amount</div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Add Budget</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Budget Tips</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Set realistic budget limits for each category
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        You can set different budgets for different months
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Track your spending against your budget to stay on target
                    </li>
                    <li>
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Adjust your budgets as needed based on your spending patterns
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/../../includes/footer.php';
?>