<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/rescuer_sidebar.css">

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="rescuer_dashboard.php" class="sidebar-logo">
            <div class="logo-icon">
               <img src="<?= BASE_URL ?>/assets/image.png" alt="VitalWear Logo" width="36" height="36">
            </div>
            <span class="logo-text">VitalWear</span>
        </a>
        <button class="sidebar-close" id="sidebarClose" onclick="toggleSidebar()">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="user-avatar rescuer-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="user-role-badge rescuer-badge">
                <i class="fa-solid fa-shield-heart" style="font-size:9px"></i> Rescuer
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Field Operations</div>

        <a href="rescuer_dashboard.php"
           class="nav-item <?= ($currentPage === 'rescuer_dashboard.php' && (!isset($_GET['tab']) || $_GET['tab'] === 'overview')) ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>

        <a href="rescuer_dashboard.php?tab=patients"
           class="nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'patients') ? 'active' : '' ?>">
            <i class="fa-solid fa-user-injured"></i>
            <span>My Patients</span>
        </a>

        <a href="rescuer_dashboard.php?tab=alerts"
           class="nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'alerts') ? 'active' : '' ?>">
            <i class="fa-solid fa-bell"></i>
            <span>Alerts</span>
            <span class="nav-badge nav-badge-coral hidden" id="sidebarAlertBadge">0</span>
        </a>

        <a href="rescuer_dashboard.php?tab=report"
           class="nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'report') ? 'active' : '' ?>">
            <i class="fa-solid fa-file-medical"></i>
            <span>Incident Reports</span>
        </a>

        <div class="sidebar-divider"></div>
        <div class="nav-label">Account</div>

        <a href="../api/login.php?action=logout" class="nav-item logout-item">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>

<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <div class="mobile-bottom-nav-inner">
        <a href="rescuer_dashboard.php"
           class="mob-nav-item <?= (!isset($_GET['tab']) || $_GET['tab'] === 'overview') ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
        </a>
        <a href="rescuer_dashboard.php?tab=patients"
           class="mob-nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'patients') ? 'active' : '' ?>">
            <i class="fa-solid fa-user-injured"></i><span>Patients</span>
        </a>
        <a href="rescuer_dashboard.php?tab=alerts"
           class="mob-nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'alerts') ? 'active' : '' ?>">
            <i class="fa-solid fa-bell"></i><span>Alerts</span>
            <span class="mob-nav-badge coral-badge hidden" id="mobAlertBadge">0</span>
        </a>
        <a href="rescuer_dashboard.php?tab=report"
           class="mob-nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'report') ? 'active' : '' ?>">
            <i class="fa-solid fa-file-medical"></i><span>Reports</span>
        </a>
        <a href="../api/login.php?action=logout" class="mob-nav-item">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
        </a>
    </div>
</nav>

<script>
function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    if (!sb) return;
    const open = sb.classList.toggle('open');
    if (ov) ov.classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
}

function updateRescuerAlertBadge(count) {
    ['sidebarAlertBadge', 'mobAlertBadge'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = count;
        el.classList.toggle('hidden', count === 0);
    });
}
</script>