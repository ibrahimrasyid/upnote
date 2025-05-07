<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Budget.php';
require_once __DIR__ . '/../../classes/Category.php';

requireLogin();

// Get current user ID
$userId = $_SESSION['user_id'];

// Get budget ID
if (!isset($_GET['id'])) {
    die('Budget ID not provided.');
}

$budgetId = (int)$_GET['id'];

// Fetch budget details
$budget = Budget::getById($budgetId);

if (!$budget || $budget['User_ID'] != $userId) {
    die('Budget not found or unauthorized.');
}

// Fetch categories for dropdown
$categories = Category::getByUserId($userId);

// If form is submitted, update the budget
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = $_POST['category'];
    $budgetAmount = isset($_POST['budget_amount']) ? (float)$_POST['budget_amount'] : 0;
    $spentAmount = isset($_POST['spent_amount']) ? (float)$_POST['spent_amount'] : 0;

    // Update the budget
    $result = Budget::update($budgetId, $categoryId, $budgetAmount, $spentAmount);

    if ($result['success']) {
        header('Location: view.php?message=Budget updated successfully.');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Set page title
$page_title = "Edit Budget";

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <h1 class="h3 mb-4">Edit Budget</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <select name="category" class="form-select" id="category" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['Category_ID']; ?>" <?php echo $budget['Category_ID'] == $cat['Category_ID'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['Category_Name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="budget_amount" class="form-label">Budget Amount</label>
            <input type="number" step="0.01" name="budget_amount" class="form-control" id="budget_amount" required value="<?php echo htmlspecialchars($budget['budget_amount'] ?? 0); ?>">
        </div>

        <div class="mb-3">
            <label for="spent_amount" class="form-label">Spent Amount</label>
            <input type="number" step="0.01" name="spent_amount" class="form-control" id="spent_amount" required value="<?php echo htmlspecialchars($budget['spent_amount'] ?? 0); ?>">
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="view.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php
include_once __DIR__ . '/../../includes/footer.php';
?>
