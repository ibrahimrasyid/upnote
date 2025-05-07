<?php
// Start Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';

// Authentication Functions
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /finance_tracker/views/auth/login.php");
        exit;
    }
}

// Redirect if already logged in
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header("Location: /finance_tracker/views/dashboard.php");
        exit;
    }
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
    return true;
}

// Format currency
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 2, ',', '.');
}

// Format date
function formatDate($date) {
    return date('d F Y', strtotime($date));
}

// Get current month and year (YYYY-MM)
function getCurrentMonth() {
    return date('Y-m');
}

// Get list of all months in the past year
function getMonthsList() {
    $months = [];
    for ($i = 0; $i < 12; $i++) {
        $month = date('Y-m', strtotime("-$i months"));
        $months[$month] = date('F Y', strtotime("-$i months"));
    }
    return $months;
}

// Format month (YYYY-MM) to readable format
function formatMonth($month) {
    return date('F Y', strtotime($month . '-01'));
}

// Calculate progress percentage
function calculateProgressPercentage($current, $target) {
    if ($target <= 0) return 0;
    $percentage = ($current / $target) * 100;
    return min(100, $percentage); // Cap at 100%
}

// Handle error and success messages
function setMessage($type, $message) {
    $_SESSION[$type . '_message'] = $message;
}

function displayMessages() {
    $output = '';
    
    if (isset($_SESSION['error_message'])) {
        $output .= '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
        unset($_SESSION['error_message']);
    }
    
    if (isset($_SESSION['success_message'])) {
        $output .= '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']);
    }
    
    return $output;
}

// Get user data by ID
function getUserById($userId) {
    return fetch_row("SELECT * FROM USER WHERE User_ID = $userId");
}

// Get current logged-in user data
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $userId = $_SESSION['user_id'];
    return getUserById($userId);
}