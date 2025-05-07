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

// Get categories grouped by type
$incomeCategories = Category::getByUserIdAndType($userId, 'income');
$expenseCategories = Category::getByUserIdAndType($userId, 'expense');

// If no specific categories for a type, get all categories
if (empty($incomeCategories) || empty($expenseCategories)) {
    $allCategories = Category::getByUserId($userId);
}

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
    $type = clean_input($_POST['type'] ?? '');
    $date = clean_input($_POST['date'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $categoryId = clean_input($_POST['category_id'] ?? '');
    
    // Validate form data
    $errors = [];
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = 'Please enter a valid amount';
    }
    
    if (empty($type) || !in_array($type, ['income', 'expense'])) {
        $errors[] = 'Please select a valid transaction type';
    }
    
    if (empty($date)) {
        $errors[] = 'Please select a date';
    }
    
    if (empty($categoryId)) {
        $errors[] = 'Please select a category';
    }
    
    // If no errors, add transaction
    if (empty($errors)) {
        $result = Transaction::create($amount, $type, $date, $description, $categoryId, $userId);
        
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
$page_title = "Add Transaction";

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Add Transaction</h1>
    <a href="view.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Transactions
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="needs-validation" novalidate>
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Transaction Type -->
                    <div class="mb-3">
                        <label class="form-label required">Transaction Type</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type_income" value="income" required <?php echo (isset($_POST['type']) && $_POST['type'] === 'income') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="type_income">
                                <i class="fas fa-arrow-down text-success me-1"></i>Income
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type_expense" value="expense" required <?php echo (isset($_POST['type']) && $_POST['type'] === 'expense') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="type_expense">
                                <i class="fas fa-arrow-up text-danger me-1"></i>Expense
                            </label>
                        </div>
                        <div class="invalid-feedback">Please select a transaction type</div>
                    </div>
                    
                    <!-- Amount -->
                    <div class="mb-3">
                        <label for="amount" class="form-label required">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="amount" name="amount" min="0.01" step="0.01" value="<?php echo $_POST['amount'] ?? ''; ?>" required>
                            <div class="invalid-feedback">Please enter a valid amount</div>
                        </div>
                    </div>
                    
                    <!-- Date -->
                    <div class="mb-3">
                        <label for="date" class="form-label required">Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $_POST['date'] ?? date('Y-m-d'); ?>" required>
                        <div class="invalid-feedback">Please select a date</div>
                    </div>
                    
                   
<!-- Category -->
<div class="mb-3" id="category-select-group">
    <label for="category_id" class="form-label required">Category</label>
    <div class="dropdown">
        <!-- Combined Search Input and Display -->
        <div class="input-group">
            <input type="text" class="form-control" id="category_search" placeholder="Search category..." 
                   data-bs-toggle="dropdown" aria-expanded="false" autocomplete="off">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-chevron-down"></i>
            </button>
            
            <!-- Hidden actual select input for form submission -->
            <input type="hidden" id="category_id" name="category_id" value="<?php echo $_POST['category_id'] ?? ''; ?>" required>
            
            <!-- Dropdown menu with categories -->
            <ul class="dropdown-menu w-100" id="category_dropdown">
                <li><div class="dropdown-header">Select a category</div></li>
                
                <?php
                // Get all categories
                $allCategories = Category::getByUserId($userId);
                
                // Display all categories in dropdown
                foreach ($allCategories as $category): 
                    // Determine if this is an income or expense category
                    $isIncome = false;
                    foreach ($incomeCategories as $incomeCat) {
                        if ($incomeCat['Category_ID'] == $category['Category_ID']) {
                            $isIncome = true;
                            break;
                        }
                    }
                    $categoryType = $isIncome ? 'income' : 'expense';
                ?>
                    <li>
                        <a class="dropdown-item category-item" href="#" 
                           data-id="<?php echo $category['Category_ID']; ?>"
                           data-type="<?php echo $categoryType; ?>"
                           data-name="<?php echo htmlspecialchars($category['Category_Name']); ?>">
                            <?php echo htmlspecialchars($category['Category_Name']); ?> 
                            <span class="badge bg-<?php echo $isIncome ? 'success' : 'danger'; ?> ms-1">
                                <?php echo $isIncome ? 'Income' : 'Expense'; ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
                
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-primary" href="../categories/add.php">
                        <i class="fas fa-plus-circle me-1"></i>Add New Category
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <div class="invalid-feedback">Please select a category</div>
</div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $_POST['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Transaction</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Transaction Tips</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        <strong>Income:</strong> Money you receive (salary, gifts, etc.)
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        <strong>Expense:</strong> Money you spend (bills, groceries, etc.)
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Select the appropriate category to better track your finances
                    </li>
                    <li>
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Add a detailed description to remember the transaction purpose
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
