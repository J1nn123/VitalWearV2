<?php
/**
 * Login / Logout API
 * Improved version with better error handling
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Determine action
$action = $_GET['action'] ?? $_POST['action'] ?? 'login';

/**
 * Handle Logout
 */
if ($action === 'logout') {
    logout();
    header('Location: ../index.php?msg=logged_out');
    exit;
}

/**
 * Handle Login
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        header('Location: ../index.php?error=empty');
        exit;
    }
    
    // Attempt login
    $user = login($username, $password);
    
    if ($user) {
        // Login successful - redirect to dashboard
        $dashboardUrl = getDashboardUrl($user['role']);
        header("Location: ../$dashboardUrl");
        exit;
    } else {
        // Login failed
        header('Location: ../index.php?error=invalid');
        exit;
    }
}

// If not POST and not logout, redirect to login page
header('Location: ../index.php');
exit;