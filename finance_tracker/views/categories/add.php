<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Category.php';

// Require login
requireLogin();

// Get current user ID
$userId = $_SESSION['user_id'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setMessage('error', 'Invalid form submission. Please try again.');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Get form data
    $categoryName = clean_input($_POST['category_name'] ?? '');
    
    // Validate form data
    $errors = [];
    
    if (empty($categoryName)) {
        $errors[] = 'Category name is required';
    }
    
    // If no errors, add category
    if (empty($errors)) {
        $result = Category::create($categoryName, $userId);
        
        if ($result['success']) {
            setMessage('success', $result['message']);
            
            // Redirect based on where the user came from
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'view.php';
            header("Location: $redirect");
            exit;
        } else {
            setMessage('error', $result['message']);
        }
    } else {
        setMessage('error', implode('<br>', $errors));
    }
}

// Get all user categories
$categories = Category::getByUserId($userId);

// Set page title
$page_title = "Add Category";

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Add Category</h1>
    <?php if (!isset($_GET['redirect'])): ?>
        <a href="view.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Categories
        </a>
    <?php else: ?>
        <a href="<?php echo $_GET['redirect']; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Go Back
        </a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form action="<?php echo $_SERVER['PHP_SELF'] . (isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''); ?>" method="POST" class="needs-validation" novalidate>
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Category Name -->
                    <div class="mb-3">
                        <label for="category_name" class="form-label required">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" value="<?php echo $_POST['category_name'] ?? ''; ?>" required>
                        <div class="invalid-feedback">Please enter a category name</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Existing Categories</h5>
            </div>
            <div class="card-body">
                <?php if (empty($categories)): ?>
                    <p class="text-center">No categories found. Create your first category.</p>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($categories as $category): ?>
                            <div class="col-md-6 mb-2">
                                <div class="d-flex align-items-center p-2 border rounded">
                                    <i class="fas fa-tag text-primary me-2"></i>
                                    <span><?php echo htmlspecialchars($category['Category_Name']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/../../includes/footer.php';
?>