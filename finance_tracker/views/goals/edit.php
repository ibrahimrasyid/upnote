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
    
    // If no errors, update goal
    if (empty($errors)) {
        $result = $goal->update($targetAmount, $targetDate);
        
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
$page_title = "Edit Financial Goal";

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Edit Financial Goal</h1>
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
                            <p class="mb-1"><strong>Current Progress:</strong> <?php echo formatCurrency($goalData['Progress']); ?></p>
                            <p class="mb-1"><strong>Status:</strong> <?php echo ucfirst($goalData['Status']); ?></p>
                        </div>
                        <div class="col-md-6">
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
                    </div>
                </div>
                
                <form action="<?php echo $_SERVER['PHP_SELF'] . "?id=$goalId"; ?>" method="POST" class="needs-validation" novalidate>
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Target Amount -->
                    <div class="mb-3">
                        <label for="target_amount" class="form-label required">Target Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="target_amount" name="target_amount" min="0.01" step="0.01" value="<?php echo $goalData['Target_Amount']; ?>" required>
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
                        <input type="date" class="form-control" id="target_date" name="target_date" min="<?php echo $minDate; ?>" value="<?php echo $goalData['Target_Date']; ?>" required>
                        <div class="invalid-feedback">Please select a future date</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Goal</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Edit Tips</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Adjust your target amount if your financial situation has changed
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        The target date must be set in the future
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Your current progress will remain unchanged when editing the goal
                    </li>
                    <li>
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        The goal status will be recalculated based on the new target
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