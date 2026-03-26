<?php
/**
 * HeartCare - Login Page
 * Improved version with better error handling
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';


// Try to initialize database
try {
    initDatabase();
} catch (Exception $e) {
    // Ignore on first load
}

// Redirect if already logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    $dashboardUrl = getDashboardUrl($user['role']);
    header('Location: ' . $dashboardUrl);
    exit;
}

// Handle error messages
$error = '';
$msg = '';

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid':
            $error = 'Invalid username or password.';
            break;
        case 'empty':
            $error = 'Please enter both username and password.';
            break;
        case 'unauthorized':
            $error = 'You do not have permission to access that page.';
            break;
        default:
            $error = 'An error occurred. Please try again.';
    }
}

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'logged_out':
            $msg = 'You have been logged out successfully.';
            break;
        default:
            $msg = '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VitalWear Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .ecg-bg {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            max-width: 600px;
            opacity: 0.04;
            pointer-events: none;
        }

        .login-error {
            background-color: #fee2e2;
            border: 1px solid #fca5a5;
            color: #dc2626;
            border-radius: 6px;
            padding: 12px 14px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .login-success {
            background-color: #dcfce7;
            border: 1px solid #86efac;
            color: #16a34a;
            border-radius: 6px;
            padding: 12px 14px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .demo-btn {
            background: none;
            border: none;
            padding: 8px 12px;
            margin: 4px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .demo-btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
    </style>
</head>
<body>
<div class="login-page">
    <!-- ECG decorative background -->
    <svg class="ecg-bg" viewBox="0 0 600 80" fill="none">
        <polyline points="0,40 60,40 80,40 90,10 100,70 110,40 130,40 150,40 165,5 175,75 185,40 210,40 250,40 265,10 275,70 285,40 310,40 350,40 365,5 375,75 385,40 410,40 450,40 465,10 475,70 485,40 510,40 550,40 560,40"
            stroke="white" stroke-width="2" fill="none"/>
    </svg>

    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" fill="#ef4444" stroke="#ef4444" stroke-width="1"/>
                </svg>
            </div>
            <span class="login-logo-text">VitalWear</span>
        </div>
        <p class="login-subtitle">Heart Rate Monitoring System — Sign in to continue</p>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="login-error">
                <span>⚠️</span>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if ($msg): ?>
            <div class="login-success">
                <span>✓</span>
                <span><?= htmlspecialchars($msg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="api/login.php" autocomplete="off">
            <div class="login-group">
                <label class="login-label">Username</label>
                <input 
                    type="text" 
                    name="username" 
                    class="login-input" 
                    placeholder="Enter your username" 
                    required 
                    autocomplete="username"
                    autofocus>
            </div>

            <div class="login-group">
                <label class="login-label">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    class="login-input" 
                    placeholder="Enter your password" 
                    required 
                    autocomplete="current-password">
            </div>

            <button type="submit" class="login-btn">Sign In</button>
        </form>

        <!-- Demo Accounts Section -->
        <div class="login-demo">
            <div class="login-demo-title">Demo Accounts</div>
            <p style="font-size: 12px; color: #666; margin-bottom: 12px;">Click to fill demo credentials:</p>
            <div class="demo-accounts">
                <button type="button" class="demo-btn" onclick="fillLogin('admin','admin123')" style="background-color: rgba(239, 68, 68, 0.1); color: #dc2626;">🔴 Admin</button>
                <button type="button" class="demo-btn" onclick="fillLogin('manager1','manager123')" style="background-color: rgba(59, 130, 246, 0.1); color: #2563eb;">🔵 Manager</button>
                <button type="button" class="demo-btn" onclick="fillLogin('rescuer1','rescuer123')" style="background-color: rgba(251, 146, 60, 0.1); color: #ea580c;">🟡 Rescuer</button>
                <button type="button" class="demo-btn" onclick="fillLogin('responder1','responder123')" style="background-color: rgba(34, 197, 94, 0.1); color: #16a34a;">🟢 Responder</button>
            </div>
        </div>
    </div>
</div>

<script>
function fillLogin(username, password) {
    const usernameInput = document.querySelector('input[name="username"]');
    const passwordInput = document.querySelector('input[name="password"]');
    const submitBtn = document.querySelector('.login-btn');
    
    if (usernameInput && passwordInput) {
        usernameInput.value = username;
        passwordInput.value = password;
        usernameInput.focus();
    }
}

// Optional: Auto-focus password field after username is filled
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.querySelector('input[name="username"]');
    const passwordInput = document.querySelector('input[name="password"]');
    
    if (usernameInput && passwordInput) {
        usernameInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                passwordInput.focus();
                e.preventDefault();
            }
        });
    }
});
</script>
</body>
</html>