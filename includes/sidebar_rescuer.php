<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/rescuer_sidebar.css">

<style>
/* ==========================================================
   VitalWear — Rescuer Sidebar Styles
   ========================================================== */

:root {
    --c-coral:        #EF6C52;
    --c-coral-deep:   #E05A3A;
    --c-coral-glow:   rgba(239,108,82,.35);
    --c-coral-border: rgba(239,108,82,.25);
}

@keyframes slideUp    { from{transform:translateY(100%);opacity:0} to{transform:translateY(0);opacity:1} }
@keyframes badgePulse { 0%,100%{opacity:1}50%{opacity:.6} }
@keyframes mobAlertPulse { 0%,100%{box-shadow:0 0 0 0 rgba(239,108,82,0)}50%{box-shadow:0 0 0 3px rgba(239,108,82,.2)} }

/* ════════════════════════════════════════════════════════
   MOBILE BOTTOM NAV — Matches Responder Style Exactly
   ════════════════════════════════════════════════════════ */
.mobile-bottom-nav {
    display:         none;
    position:        fixed;
    bottom:          0; left:0; right:0;
    width:           100%;
    z-index:         10000 !important;
    background:      #110a0c;
    border-top:      1px solid rgba(255,255,255,.09);
    padding-bottom:  env(safe-area-inset-bottom, 0px);
    pointer-events:  all !important;
    /* No blur — hard rule */
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}
.mobile-bottom-nav * { pointer-events: auto !important; }

.mobile-bottom-nav-inner {
    display:     flex;
    width:       100%;
    align-items: stretch;
    height:      60px;
}

/* ── Base nav item ────────────────────────────────────── */
.mob-nav-item {
    flex:            1;
    min-width:       0;
    display:         flex;
    flex-direction:  column;
    align-items:     center;
    justify-content: center;
    gap:             4px;
    padding:         0;
    border:          none;
    background:      transparent;
    color:           #4a3a40;
    font-family:     'DM Sans', sans-serif;
    font-size:       10px;
    font-weight:     600;
    letter-spacing:  0.1px;
    cursor:          pointer;
    text-decoration: none;
    transition:      color 0.15s ease;
    position:        relative;
    -webkit-tap-highlight-color: transparent;
    overflow:        visible;
}
.mob-nav-item i              { font-size: 20px; line-height: 1; }
.mob-nav-item span.mob-label { font-size: 10px; font-weight: 600; line-height: 1; white-space: nowrap; }
.mob-nav-item:active         { opacity: .65; }

/* Active state — coral with top bar indicator */
.mob-nav-item.active { color: #EF6C52; }
.mob-nav-item.active::before {
    content:      '';
    position:     absolute;
    top:          0; left: 20%; right: 20%;
    height:       2.5px;
    border-radius:0 0 3px 3px;
    background:   #EF6C52;
    box-shadow:   0 0 6px rgba(239,108,82,.5);
}

/* ── Center "Alerts" feature button ─────────────────────
   Mirrors the Responder's mob-sim-btn style              */
.mob-alerts-btn {
    flex:         1.1;
    background:   rgba(239,108,82,.1);
    border-radius:12px;
    margin:       8px 3px;
    color:        #EF6C52 !important;
    border:       1px solid rgba(239,108,82,.22);
    transition:   all .2s ease;
}
.mob-alerts-btn i { color: #EF6C52 !important; }
.mob-alerts-btn.has-alerts {
    background:   rgba(239,68,68,.12) !important;
    border-color: rgba(239,68,68,.3) !important;
    color:        #f87171 !important;
    animation:    mobAlertPulse 2s ease-in-out infinite;
}
.mob-alerts-btn.has-alerts i { color: #f87171 !important; }

/* ── Nav Badges ─────────────────────────────────────────── */
.mob-nav-badge {
    position:      absolute;
    top:           4px;
    right:         calc(50% - 20px);
    min-width:     16px;
    height:        16px;
    padding:       0 4px;
    border-radius: 8px;
    font-size:     9px;
    font-weight:   800;
    line-height:   16px;
    text-align:    center;
    background:    #ef4444;
    color:         #fff;
    border:        1.5px solid #110a0c;
    pointer-events:none;
}
.mob-nav-badge.coral  { background: #EF6C52; }
.mob-nav-badge.hidden { display: none; }

@media (max-width: 900px) {
    .mobile-bottom-nav { display: block; }
}
</style>

<!-- ════════════════ SIDEBAR ════════════════ -->
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

<!-- ════════════════ MOBILE BOTTOM NAV ════════════════
     Styled to match the Responder mobile nav exactly:
     • Same dark bg, border, height, font
     • Active indicator bar at top
     • Center feature button (Alerts) with coral highlight
     • Badges on Alerts
     ════════════════════════════════════════════════════ -->
<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <div class="mobile-bottom-nav-inner">

        <!-- Dashboard -->
        <a href="rescuer_dashboard.php"
           class="mob-nav-item <?= (!isset($_GET['tab']) || $_GET['tab'] === 'overview') ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span class="mob-label">Dashboard</span>
        </a>

        <!-- My Patients -->
        <a href="rescuer_dashboard.php?tab=patients"
           class="mob-nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'patients') ? 'active' : '' ?>">
            <i class="fa-solid fa-user-injured"></i>
            <span class="mob-label">Patients</span>
        </a>

        <!-- CENTER — Alerts feature button (mirrors mob-sim-btn style) -->
        <a href="rescuer_dashboard.php?tab=alerts"
           class="mob-nav-item mob-alerts-btn <?= (isset($_GET['tab']) && $_GET['tab'] === 'alerts') ? 'active' : '' ?>"
           id="mobAlertsCenterBtn">
            <i class="fa-solid fa-bell" id="mobAlertsCenterIcon"></i>
            <span class="mob-label" id="mobAlertsCenterLabel">Alerts</span>
            <span class="mob-nav-badge coral hidden" id="mobAlertBadge">0</span>
        </a>

        <!-- Incident Reports -->
        <a href="rescuer_dashboard.php?tab=report"
           class="mob-nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'report') ? 'active' : '' ?>">
            <i class="fa-solid fa-file-medical"></i>
            <span class="mob-label">Reports</span>
        </a>

        <!-- Logout -->
        <a href="../api/login.php?action=logout" class="mob-nav-item">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span class="mob-label">Logout</span>
        </a>

    </div>
</nav>

<script>
/* ── Sidebar toggle ──────────────────────────────────────────── */
function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    if (!sb) return;
    const open = sb.classList.toggle('open');
    if (ov) ov.classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
}

/* ── Alert badge sync ────────────────────────────────────────── */
function updateRescuerAlertBadge(count) {
    // Sidebar badge
    const sidebarBadge = document.getElementById('sidebarAlertBadge');
    if (sidebarBadge) {
        sidebarBadge.textContent = count;
        sidebarBadge.classList.toggle('hidden', count === 0);
    }
    // Mobile center badge
    const mobBadge = document.getElementById('mobAlertBadge');
    if (mobBadge) {
        mobBadge.textContent = count;
        mobBadge.classList.toggle('hidden', count === 0);
    }
    // Pulse the center button when there are alerts
    const centerBtn = document.getElementById('mobAlertsCenterBtn');
    if (centerBtn) {
        count > 0 ? centerBtn.classList.add('has-alerts') : centerBtn.classList.remove('has-alerts');
    }
}

/* Expose globally so dashboard pages can call it */
window.updateRescuerAlertBadge = updateRescuerAlertBadge;
</script>