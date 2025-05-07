<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Ubah sesuai dengan username MySQL Anda
define('DB_PASS', '');            // Ubah sesuai dengan password MySQL Anda
define('DB_NAME', 'finance_tracker');

// Create connection using mysqli
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");

// Function to clean input data
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Function to safely execute queries
function execute_query($sql) {
    global $conn;
    $result = $conn->query($sql);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    return $result;
}

// Function to fetch one row
function fetch_row($sql) {
    $result = execute_query($sql);
    return $result->fetch_assoc();
}

// Function to fetch multiple rows
function fetch_all($sql) {
    $result = execute_query($sql);
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

// Function to get last inserted ID
function last_insert_id() {
    global $conn;
    return $conn->insert_id;
}
