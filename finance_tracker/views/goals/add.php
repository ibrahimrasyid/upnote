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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setMessage('error', 'Invalid form submission. Please try again.');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Get form data
    $targetAmount = clean_input($_POST['target_amount'] ?? '');
    $targetDate = clean_input($_POST['target_date'] ?? '');
    
    // Validate form data
    $errors = [];
    
    if (empty($targetAmount) || !is_numeric($targetAmount) || $targetAmount <= 0) {
        $errors[] = 'Please enter a valid target amount';
    }
    
    if (empty($targetDate)) {
        $errors[] = 'Please select a target date';
    } else {
        // Check if target date is in the future
        $today = new DateTime();
        $targetDateTime = new DateTime($targetDate);
        if ($targetDateTime <= $today) {
            $errors[] = 'Target date must be in the future';
        }
    }
    
    // If no errors, add goal
    if (empty($errors)) {
        $result = Goal::create($targetAmount, $targetDate, $userId);
        
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
$page_title = "Add Financial Goal";

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Add Financial Goal</h1>
    <a href="view.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Back to Goals
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="needs-validation" novalidate>
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Target Amount -->
                    <div class="mb-3">
                        <label for="target_amount" class="form-label required">Target Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="target_amount" name="target_amount" min="0.01" step="0.01" value="<?php echo $_POST['target_amount'] ?? ''; ?>" required>
                            <div class="invalid-feedback">Please enter a valid target amount</div>
                        </div>
                    </div>
                    
                    <!-- Target Date -->
                    <div class="mb-3">
                        <label for="target_date" class="form-label required">Target Date</label>
                        <?php 
                            // Set minimum date to tomorrow
                            $tomorrow = new DateTime('tomorrow');
                            $minDate = $tomorrow->format('Y-m-d');
                        ?>
                        <input type="date" class="form-control" id="target_date" name="target_date" min="<?php echo $minDate; ?>" value="<?php echo $_POST['target_date'] ?? ''; ?>" required>
                        <div class="invalid-feedback">Please select a future date</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Goal</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Goal Setting Tips</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Set specific and measurable financial goals
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Choose a realistic timeframe for achieving your goal
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Break larger goals into smaller, achievable milestones
                    </li>
                    <li>
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Regularly track your progress and adjust as needed
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