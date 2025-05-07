<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../classes/Goal.php';

// Require login
requireLogin();

// Get current user ID
$userId = $_SESSION['user_id'];

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setMessage('error', 'Goal ID is missing.');
    header("Location: view.php");
    exit;
}

$goalId = clean_input($_GET['id']);

// Get goal data
$goalData = Goal::getById($goalId);

// Check if goal exists and belongs to current user
if (!$goalData || $goalData['User_ID'] != $userId) {
    setMessage('error', 'Goal not found or access denied.');
    header("Location: view.php");
    exit;
}

// Create goal object
$goal = new Goal($goalId);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setMessage('error', 'Invalid form submission. Please try again.');
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=$goalId");
        exit;
    }
    
    // Get form data
    $progress = clean_input($_POST['progress'] ?? '');
    
    // Validate form data
    $errors = [];
    
    if (!is_numeric($progress) || $progress < 0) {
        $errors[] = 'Please enter a valid progress amount';
    }
    
    // If no errors, update goal progress
    if (empty($errors)) {
        $result = $goal->updateProgress($progress);
        
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
$page_title = "Update Goal Progress";

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Update Goal Progress</h1>
    <a href="view.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Goals
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bullseye text-primary me-2"></i>
                    Goal #<?php echo $goalData['Goal_ID']; ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Target Amount:</strong> <?php echo formatCurrency($goalData['Target_Amount']); ?></p>
                            <p class="mb-1"><strong>Current Progress:</strong> <?php echo formatCurrency($goalData['Progress']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Target Date:</strong> <?php echo formatDate($goalData['Target_Date']); ?></p>
                            <p class="mb-1"><strong>Status:</strong> <?php echo ucfirst($goalData['Status']); ?></p>
                        </div>
                    </div>
                    
                    <?php 
                        $percentage = $goal->getPercentage();
                    ?>
                    <!-- Progress Bar -->
                    <div class="progress goal-progress mt-3">
                        <div class="progress-bar bg-success" 
                            role="progressbar" 
                            style="width: <?php echo $percentage; ?>%" 
                            aria-valuenow="<?php echo $percentage; ?>" 
                            aria-valuemin="0" 
                            aria-valuemax="100"></div>
                    </div>
                    <small class="d-block text-center mt-1">
                        <?php echo round($percentage, 1); ?>% completed
                    </small>
                </div>
                
                <form action="<?php echo $_SERVER['PHP_SELF'] . "?id=$goalId"; ?>" method="POST" class="needs-validation" novalidate>
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Progress Amount -->
                    <div class="mb-3">
                        <label for="progress" class="form-label required">Current Progress Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="progress" name="progress" min="0" step="0.01" value="<?php echo $goalData['Progress']; ?>" required>
                            <div class="invalid-feedback">Please enter a valid amount</div>
                        </div>
                        <small class="form-text text-muted">
                            Enter the total current amount saved towards this goal.
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Progress</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Progress Tips</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Enter the total amount you've saved so far, not just the additional amount.
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Regular updates will help you stay motivated and track your progress.
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        If you've already reached your goal, congratulations! The status will automatically update to "completed".
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