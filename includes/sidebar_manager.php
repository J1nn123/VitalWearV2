<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">

 
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/manager_sidebar.css">
<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="manager_dashboard.php" class="sidebar-logo">
            <div class="logo-icon">
              <img src="<?= BASE_URL ?>/assets/image.png" alt="VitalWear Logo" width="36" height="36">
            </div>
            <span class="logo-text">VitalWear</span>
        </a>
        <button class="sidebar-close" id="sidebarClose" onclick="toggleSidebar()" title="Close menu">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar manager-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="user-role-badge manager-badge">
                <i class="fa-solid fa-briefcase" style="font-size:9px"></i> Manager
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Analytics</div>
        <a href="manager_dashboard.php"
           class="nav-item <?= ($currentPage==='manager_dashboard.php'&&(!isset($_GET['tab'])||$_GET['tab']==='overview'))?'active':'' ?>">
            <i class="fa-solid fa-chart-pie"></i><span>Overview</span>
        </a>
        <a href="manager_dashboard.php?tab=trends"
           class="nav-item <?= (isset($_GET['tab'])&&$_GET['tab']==='trends')?'active':'' ?>">
            <i class="fa-solid fa-chart-line"></i><span>Heart Rate Trends</span>
        </a>
        <a href="manager_dashboard.php?tab=patients"
           class="nav-item <?= (isset($_GET['tab'])&&$_GET['tab']==='patients')?'active':'' ?>">
            <i class="fa-solid fa-user-injured"></i><span>Patients</span>
        </a>
        <a href="manager_dashboard.php?tab=rescuers"
           class="nav-item <?= (isset($_GET['tab'])&&$_GET['tab']==='rescuers')?'active':'' ?>">
            <i class="fa-solid fa-people-group"></i><span>Rescuer Performance</span>
        </a>
        <a href="manager_dashboard.php?tab=devices"
           class="nav-item <?= (isset($_GET['tab'])&&$_GET['tab']==='devices')?'active':'' ?>">
            <i class="fa-solid fa-microchip"></i><span>Devices</span>
        </a>
        <div class="sidebar-divider"></div>
        <div class="nav-label">Account</div>
        <a href="../api/login.php?action=logout" class="nav-item logout-item">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
        </a>
    </nav>
</aside>