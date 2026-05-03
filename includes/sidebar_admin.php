<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

 <link rel="stylesheet" href="<?= BASE_URL ?>/assets/styles.css">

<!-- ── DESKTOP SIDEBAR ────────────────────────────────── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-icon">
              <img src="<?= BASE_URL ?>/assets/image.png" alt="VitalWear Logo" width="36" height="36">
            </div>
            <span class="logo-text">VitalWear</span>
        </div>
        <button class="sidebar-close" id="sidebarClose" onclick="toggleSidebar()">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar admin-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="user-role-badge admin-badge">
                <i class="fa-solid fa-shield-halved" style="font-size:9px"></i> Administrator
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="admin_dashboard.php"
           class="nav-item <?= ($currentPage === 'admin_dashboard.php' && (!isset($_GET['tab']) || $_GET['tab'] === 'overview')) ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Dashboard</span>
        </a>

        <div class="nav-label">Management</div>
        <a href="admin_dashboard.php?tab=users"
           class="nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'users') ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i>
            <span>Users</span>
        </a>
        <a href="admin_dashboard.php?tab=patients"
           class="nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'patients') ? 'active' : '' ?>">
            <i class="fa-solid fa-user-injured"></i>
            <span>Patients</span>
        </a>
        <a href="admin_dashboard.php?tab=devices"
           class="nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'devices') ? 'active' : '' ?>">
            <i class="fa-solid fa-microchip"></i>
            <span>Devices</span>
        </a>

        <div class="nav-label">Reports</div>
        <a href="admin_dashboard.php?tab=analytics"
           class="nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'analytics') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Analytics</span>
        </a>
        <a href="admin_dashboard.php?tab=logs"
           class="nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'logs') ? 'active' : '' ?>">
            <i class="fa-solid fa-clipboard-list"></i>
            <span>System Logs</span>
        </a>

        <div class="sidebar-divider"></div>
        <div class="nav-label">Account</div>
        <a href="../api/login.php?action=logout" class="nav-item logout-item">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>

