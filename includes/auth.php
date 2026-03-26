<?php
/**
 * Authentication & Session Management
 * Heart Rate Monitoring System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently logged in.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Return the current logged-in user array from the session.
 */
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Redirect to login page if the user is not logged in.
 */
function requireLogin($redirect = '../index.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

/**
 * Require the current user to have one of the allowed roles.
 * Accepts a single role string or an array of roles.
 */
function requireRole($allowedRoles, $redirect = '../index.php') {
    requireLogin($redirect);
    $user = getCurrentUser();

    if (!$user || !in_array($user['role'], (array)$allowedRoles)) {
        header("Location: $redirect?error=unauthorized");
        exit;
    }
}

/**
 * Attempt to log in with a username and password.
 * Returns the user array on success, false on failure.
 */
function login($username, $password) {
    require_once __DIR__ . '/db.php';

    if (empty($username) || empty($password)) {
        return false;
    }

    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        // Store a minimal, safe subset in the session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user']    = [
            'id'        => $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'] ?? 'Unknown',
            'role'      => $user['role'],
            'email'     => $user['email'] ?? '',
        ];

        logAction($user['id'], 'LOGIN', 'User logged in successfully');

        return $user;

    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log out the current user and destroy the session.
 */
function logout() {
    try {
        $user = getCurrentUser();
        if ($user) {
            logAction($user['id'], 'LOGOUT', 'User logged out');
        }
    } catch (Exception $e) {
        error_log("Logout logAction error: " . $e->getMessage());
    }

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Return the dashboard URL for a given role.
 */
function getDashboardUrl($role) {
    $map = [
        'admin'     => 'pages/admin_dashboard.php',
        'manager'   => 'pages/manager_dashboard.php',
        'rescuer'   => 'pages/rescuer_dashboard.php',
        'responder' => 'pages/responder_dashboard.php',
    ];
    return $map[$role] ?? 'index.php';
}

/**
 * Create a new user from the admin dashboard.
 *
 * This is the single authoritative function for user creation.
 * The admin_dashboard.php POST handler should call this instead of
 * running its own inline INSERT, so validation stays consistent.
 *
 * Returns ['success' => true, 'user_id' => int]
 *      or ['success' => false, 'error'   => string]
 */
function createUser($username, $password, $full_name, $role, $email = '') {
    require_once __DIR__ . '/db.php';

    // Basic validation
    if (empty($username) || empty($password) || empty($full_name) || empty($role)) {
        return ['success' => false, 'error' => 'Username, password, full name and role are required.'];
    }

    $allowedRoles = ['admin', 'manager', 'rescuer', 'responder'];
    if (!in_array($role, $allowedRoles)) {
        return ['success' => false, 'error' => 'Invalid role selected.'];
    }

    try {
        $pdo = getDB();

        // Duplicate username check
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            return ['success' => false, 'error' => 'Username already exists.'];
        }

        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, full_name, role, email)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $full_name,
            $role,
            $email,
        ]);

        return ['success' => true, 'user_id' => (int)$pdo->lastInsertId()];

    } catch (Exception $e) {
        error_log("createUser error: " . $e->getMessage());
        return ['success' => false, 'error' => 'A database error occurred.'];
    }
}