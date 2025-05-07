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

// Get all user categories
$categories = Category::getByUserId($userId);

// Set page title
$page_title = "Categories";

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Categories</h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Add Category
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Manage Categories</h5>
    </div>
    <div class="card-body">
        <?php if (empty($categories)): ?>
            <div class="text-center py-4">
                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                <p>No categories found.</p>
                <a href="add.php" class="btn btn-primary">Add Category</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-tag text-primary me-2"></i>
                                    <?php echo htmlspecialchars($category['Category_Name']); ?>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo $category['Category_ID']; ?>" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="tooltip" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $category['Category_ID']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-bs-toggle="tooltip" title="Delete">
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