<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include functions
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to dashboard
    header("Location: views/dashboard.php");
    exit;
}

// Set page title
$page_title = "Welcome";

// Include header
include_once __DIR__ . '/includes/header.php';
?>

<div class="row mt-5">
    <div class="col-md-6">
        <h1 class="display-4">Manage Your Finances</h1>
        <p class="lead">Track your income and expenses, set budgets, and achieve your financial goals with our easy-to-use Finance Tracker.</p>
        <hr class="my-4">
        <p>Take control of your financial future today. Start by creating an account or logging in if you already have one.</p>
        <div class="d-flex">
            <a href="views/auth/register.php" class="btn btn-primary me-3">Create Account</a>
            <a href="views/auth/login.php" class="btn btn-outline-primary">Login</a>
        </div>
    </div>
    <div class="col-md-6">
        <img src="assets/img/finance-illustration.svg" alt="Finance Illustration" class="img-fluid">
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                <h3 class="card-title">Track Transactions</h3>
                <p class="card-text">Keep track of all your income and expenses in one place.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-money-bill-wave fa-3x text-primary mb-3"></i>
                <h3 class="card-title">Set Budgets</h3>
                <p class="card-text">Create monthly budgets for your spending categories.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-chart-pie fa-3x text-primary mb-3"></i>
                <h3 class="card-title">View Reports</h3>
                <p class="card-text">Generate visual reports to analyze your financial habits.</p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/footer.php';
?>