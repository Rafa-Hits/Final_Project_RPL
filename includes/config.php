<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'Vibekos');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Base URL
define('BASE_URL', 'http://localhost/vibes-kost');

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function checkRole($required_role) {
    if (!isLoggedIn() || $_SESSION['role'] != $required_role) {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Function to redirect based on role
function redirectBasedOnRole() {
    if (isLoggedIn()) {
        switch ($_SESSION['role']) {
            case 'admin':
                header("Location: ../admin/dashboard.php");
                break;
            case 'pemilik':
                header("Location: ../pemilik/dashboard.php");
                break;
            case 'penyewa':
                header("Location: ../penyewa/dashboard.php");
                break;
        }
        exit();
    }
}
?>