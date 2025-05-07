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

// Get filter parameter
$filterStatus = $_GET['status'] ?? 'all';

// Get goals based on filter
if ($filterStatus === 'active') {
    $goals = Goal::getActiveByUserId($userId);
} else {
    $goals = Goal::getByUserId($userId);
}

// Set page title
$page_title = "Financial Goals";

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Financial Goals</h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Add Goal
    </a>
</div>

<!-- Filter Section -->
<div class="card mb-4">
    <div class="card-body">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="GET" class="row g-3">
            <!-- Status Filter -->
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Goals</option>
                    <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active Goals Only</option>
                </select>
            </div>
        </form>
    </div>
</div>

<!-- Goals List -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Your Financial Goals</h5>
    </div>
    <div class="card-body">
        <?php if (empty($goals)): ?>
            <div class="text-center py-4">
                <i class="fas fa-bullseye fa-3x text-muted mb-3"></i>
                <p>No goals found.</p>
                <a href="add.php" class="btn btn-primary">Add Goal</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($goals as $goalData): ?>
                    <?php 
                        $goal = new Goal($goalData['Goal_ID']);
                        $percentage = $goal->getPercentage();
                        $daysRemaining = $goal->getDaysRemaining();
                        
                        // Get status class
                        switch($goalData['Status']) {
                            case 'completed':
                                $statusClass = 'bg-success';
                                break;
                            case 'in_progress':
                                $statusClass = 'bg-primary';
                                break;
                            default:
                                $statusClass = 'bg-warning';
                                break;
                        }
                    ?>
                    <div class="col-md-6 mb-4">
                        <div class="card goal-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bullseye text-primary me-2"></i>
                                    Goal #<?php echo $goalData['Goal_ID']; ?>
                                </h5>
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo ucfirst($goalData['Status']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold">Target Amount:</span>
                                        <span><?php echo formatCurrency($goalData['Target_Amount']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold">Current Progress:</span>
                                        <span><?php echo formatCurrency($goalData['Progress']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold">Target Date:</span>
                                        <span><?php echo formatDate($goalData['Target_Date']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold">Time Remaining:</span>
                                        <span><?php echo $daysRemaining; ?> days</span>
                                    </div>
                                </div>
                                
                                <!-- Progress Bar -->
                                <div class="progress goal-progress">
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
                                
                                <!-- Goal Actions -->
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <a href="update_progress.php?id=<?php echo $goalData['Goal_ID']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="fas fa-plus-circle me-1"></i>Update Progress
                                    </a>
                                    <div>
                                        <a href="edit.php?id=<?php echo $goalData['Goal_ID']; ?>" class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $goalData['Goal_ID']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-bs-toggle="tooltip" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/../../includes/footer.php';
?>