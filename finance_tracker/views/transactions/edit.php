<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Transaction.php';
require_once __DIR__ . '/../../classes/Category.php';

requireLogin();

// Get user id
$userId = $_SESSION['user_id'];

// Get transaction ID
if (!isset($_GET['id'])) {
    die('Transaction ID not provided.');
}

$transactionId = (int)$_GET['id'];

// Fetch transaction
$transaction = new Transaction($transactionId);

if (!$transaction || $transaction->getUserId() != $userId) {
    die('Transaction not found or unauthorized.');
}

// Fetch categories
$categories = Category::getByUserId($userId);

// If form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $amount = (float)$_POST['amount'];

    $result = $transaction->update($amount, $type, $date, $description, $category);

    if ($result['success']) {
        header('Location: view.php?message=Transaction updated successfully.');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Include header
$page_title = "Edit Transaction";
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <h1 class="h3 mb-4">Edit Transaction</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="date" class="form-label">Date</label>
            <input type="date" name="date" class="form-control" id="date" required value="<?php echo htmlspecialchars($transaction->getDate()); ?>">
        </div>

        <div class="mb-3">
            <label for="category" class="form-label">Category</label>
            <select name="category" class="form-select" id="category" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['Category_ID']; ?>" <?php echo $transaction->getCategoryId() == $cat['Category_ID'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['Category_Name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select" required>
                <option value="income" <?php echo $transaction->getType() === 'income' ? 'selected' : ''; ?>>Income</option>
                <option value="expense" <?php echo $transaction->getType() === 'expense' ? 'selected' : ''; ?>>Expense</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="amount" class="form-label">Amount</label>
            <input type="number" step="0.01" name="amount" class="form-control" id="amount" required value="<?php echo htmlspecialchars($transaction->getAmount()); ?>">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" class="form-control" id="description"><?php echo htmlspecialchars($transaction->getDescription()); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="view.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php
include_once __DIR__ . '/../../includes/footer.php';
?>
