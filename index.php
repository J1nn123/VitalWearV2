<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

try { initDatabase(); } catch (Exception $e) { }

if (isLoggedIn()) {
    $user = getCurrentUser();
    header('Location: ' . getDashboardUrl($user['role']));
    exit;
}

$error = '';
$msg   = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid':       $error = 'Invalid username or password.'; break;
        case 'empty':         $error = 'Please enter both username and password.'; break;
        case 'unauthorized':  $error = 'You do not have permission to access that page.'; break;
        default:              $error = 'An error occurred. Please try again.';
    }
}
if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out') {
    $msg = 'You have been logged out successfully.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VitalWear — Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --coral       : #EF6C52;
        --coral-deep  : #E05A3A;
        --coral-soft  : #FF9A7B;
        --coral-glow  : rgba(239,108,82,.35);
        --coral-muted : rgba(239,108,82,.10);
        --coral-border: rgba(239,108,82,.28);
        --bg-page     : #F8F4F2;
        --bg-card     : #FFFFFF;
        --navy        : #1E2450;
        --text-primary: #1E2450;
        --text-muted  : #6B7280;
        --text-label  : #9CA3AF;
        --card-shadow : 0 8px 40px rgba(239,108,82,.18), 0 2px 12px rgba(30,36,80,.10);
    }
    body {
        font-family: 'DM Sans', sans-serif;
        background: var(--bg-page);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        -webkit-font-smoothing: antialiased;
        background-image:
            radial-gradient(circle at 20% 20%, rgba(239,108,82,.08) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(239,108,82,.06) 0%, transparent 50%);
    }
    .ecg-bg {
        position: fixed; bottom: 0; left: 50%;
        transform: translateX(-50%);
        width: 100%; max-width: 800px;
        opacity: 0.035; pointer-events: none;
    }
    .login-card {
        background: var(--bg-card);
        border-radius: 20px;
        border: 1.5px solid var(--coral-border);
        box-shadow: var(--card-shadow);
        padding: 40px 44px 36px;
        width: 100%; max-width: 420px;
        position: relative; z-index: 1;
    }
    @media (max-width: 480px) {
        .login-card { padding: 32px 24px 28px; border-radius: 16px; }
    }
    .login-logo {
        display: flex; align-items: center; gap: 13px;
        margin-bottom: 6px;
    }
    .login-logo-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; overflow: hidden;
    }
    .login-logo-icon img {
        width: 100%; height: 100%; object-fit: contain;
    }
    .login-logo-text {
        font-size: 22px; font-weight: 800; color: var(--text-primary);
        letter-spacing: -.5px;
    }
    .login-subtitle {
        font-size: 13px; color: var(--text-muted); font-weight: 500;
        margin-bottom: 28px; padding-left: 2px;
    }
    .login-divider {
        height: 1px; background: var(--coral-border); margin-bottom: 28px;
    }
    .login-alert {
        padding: 12px 16px; border-radius: 10px;
        font-size: 13px; font-weight: 600;
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 20px;
    }
    .login-alert.error {
        background: rgba(239,68,68,.08);
        border: 1.5px solid rgba(239,68,68,.25);
        color: #ef4444;
    }
    .login-alert.success {
        background: rgba(239,108,82,.08);
        border: 1.5px solid rgba(239,108,82,.28);
        color: var(--coral);
    }
    .login-group { margin-bottom: 18px; }
    .login-label {
        display: block; font-size: 11px; font-weight: 700;
        color: var(--text-muted); margin-bottom: 7px;
        text-transform: uppercase; letter-spacing: .7px;
    }
    .login-input {
        width: 100%; padding: 12px 16px;
        background: #F9FAFB;
        border: 1.5px solid #C4C9D4;
        border-radius: 10px; color: var(--text-primary);
        font-size: 14px; font-family: 'DM Sans', sans-serif;
        transition: all .2s;
        box-shadow: inset 0 1px 3px rgba(0,0,0,.04);
    }
    .login-input:focus {
        outline: none; border-color: var(--coral);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(239,108,82,.14);
    }
    .login-input::placeholder { color: #B0B7C3; }
    .login-btn {
        width: 100%; padding: 14px;
        background: linear-gradient(135deg, var(--coral), var(--coral-deep));
        color: #fff; border: none; border-radius: 12px;
        font-size: 15px; font-weight: 800;
        cursor: pointer; font-family: 'DM Sans', sans-serif;
        box-shadow: 0 4px 18px var(--coral-glow);
        transition: all .2s; margin-top: 4px;
        display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .login-btn:hover { filter: brightness(1.07); transform: translateY(-1px); box-shadow: 0 6px 24px var(--coral-glow); }
    .login-btn:active { transform: translateY(0); }
    .login-demo {
        margin-top: 28px; padding-top: 22px;
        border-top: 1px solid var(--coral-border);
    }
    .login-demo-title {
        font-size: 10px; font-weight: 700; color: var(--text-label);
        text-transform: uppercase; letter-spacing: 1px;
        text-align: center; margin-bottom: 14px;
    }
    .demo-grid {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .demo-btn {
        padding: 10px 12px; border-radius: 10px;
        border: 1.5px solid transparent;
        cursor: pointer; font-size: 12px; font-weight: 700;
        font-family: 'DM Sans', sans-serif;
        display: flex; align-items: center; gap: 7px;
        transition: all .2s; text-align: left;
    }
    .demo-btn:hover { transform: translateY(-1px); }
    .demo-dot {
        width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
    }
    .demo-btn-admin     { background: rgba(239,68,68,.08);   color: #ef4444;      border-color: rgba(239,68,68,.22); }
    .demo-btn-manager   { background: rgba(59,130,246,.08);  color: #3b82f6;      border-color: rgba(59,130,246,.22); }
    .demo-btn-rescuer   { background: rgba(239,108,82,.08);  color: var(--coral); border-color: var(--coral-border); }
    .demo-btn-responder { background: rgba(16,185,129,.08);  color: #10b981;      border-color: rgba(16,185,129,.22); }
    .demo-dot-admin     { background: #ef4444; }
    .demo-dot-manager   { background: #3b82f6; }
    .demo-dot-rescuer   { background: var(--coral); }
    .demo-dot-responder { background: #10b981; }
    </style>
</head>
<body>

<!-- Decorative ECG line -->
<svg class="ecg-bg" viewBox="0 0 800 80" fill="none" xmlns="http://www.w3.org/2000/svg">
    <polyline points="0,40 80,40 100,40 115,10 125,70 135,40 160,40 210,40 225,10 235,70 245,40 270,40 330,40 345,10 355,70 365,40 390,40 450,40 465,10 475,70 485,40 510,40 570,40 585,10 595,70 605,40 630,40 690,40 710,40 800,40"
        stroke="#EF6C52" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
</svg>

<div class="login-card">

    <!-- Logo -->
    <div class="login-logo">
        <div class="login-logo-icon">
           <img src="assets/image.png" alt="VitalWear Logo">
        </div>
        <span class="login-logo-text">VitalWear</span>
    </div>
    <p class="login-subtitle">Heart Rate Monitoring System — Sign in to continue</p>

    <div class="login-divider"></div>

    <!-- Error -->
    <?php if ($error): ?>
    <div class="login-alert error">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Success -->
    <?php if ($msg): ?>
    <div class="login-alert success">
        <i class="fa-solid fa-circle-check"></i>
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="api/login.php" autocomplete="off">
        <div class="login-group">
            <label class="login-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="login-input"
                   placeholder="Enter your username" required autocomplete="username" autofocus>
        </div>
        <div class="login-group">
            <label class="login-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="login-input"
                   placeholder="Enter your password" required autocomplete="current-password">
        </div>
        <button type="submit" class="login-btn">
            <i class="fa-solid fa-right-to-bracket"></i> Sign In
        </button>
    </form>

    <!-- Demo accounts -->
    <div class="login-demo">
        <div class="login-demo-title">Quick Demo Access</div>
        <div class="demo-grid">
            <button type="button" class="demo-btn demo-btn-admin" onclick="fillLogin('admin','admin123')">
                <span class="demo-dot demo-dot-admin"></span> Admin
            </button>
            <button type="button" class="demo-btn demo-btn-manager" onclick="fillLogin('manager1','manager123')">
                <span class="demo-dot demo-dot-manager"></span> Manager
            </button>
            <button type="button" class="demo-btn demo-btn-rescuer" onclick="fillLogin('rescuer1','rescuer123')">
                <span class="demo-dot demo-dot-rescuer"></span> Rescuer
            </button>
            <button type="button" class="demo-btn demo-btn-responder" onclick="fillLogin('responder1','responder123')">
                <span class="demo-dot demo-dot-responder"></span> Responder
            </button>
        </div>
    </div>

</div><!-- /.login-card -->

<script>
function fillLogin(username, password) {
    document.getElementById('username').value = username;
    document.getElementById('password').value = password;
    document.getElementById('password').focus();
}
document.getElementById('username').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('password').focus(); }
});
</script>
</body>
</html>