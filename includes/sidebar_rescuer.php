<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* ═══════════════════════════════════════════════════
   ROOT — Coral / Warm Palette  (matches responder UI)
═══════════════════════════════════════════════════ */
:root {
    --c-coral      : #EF6C52;
    --c-coral-deep : #948f8e;
    --c-coral-soft : #FF9A7B;
    --c-coral-glow : rgba(239,108,82,.35);
    --c-coral-muted: rgba(239,108,82,.12);
    --c-coral-border: rgba(239,108,82,.22);

    --c-navy       : #1E2450;
    --c-navy-light : #2D3478;

    --sb-bg        : #1C1014;
    --sb-border    : rgba(255,255,255,.06);
    --sb-surface   : rgba(255,255,255,.04);
    --sb-surface-h : rgba(255,255,255,.07);
    --sb-text      : #F1ECE9;
    --sb-muted     : #6B5F5A;
    --sb-label     : #3D2F2A;
}

/* ── Layout ── */
.main-content { margin-left: 0 !important; }

/* ═══════════════════════════════════════════════════
   SIDEBAR
═══════════════════════════════════════════════════ */
.sidebar {
    width: 248px;
    background: var(--sb-bg);
    display: flex;
    flex-direction: column;
    height: 100vh;
    position: sticky;
    top: 0;
    overflow-y: auto;
    z-index: 200;
    flex-shrink: 0;
    transition: transform 0.3s cubic-bezier(.4,0,.2,1);
    border-right: 1px solid var(--sb-border);
    box-shadow: 2px 0 24px rgba(0,0,0,.25);
}

/* Warm grain overlay */
.sidebar::before {
    content: '';
    position: absolute; inset: 0; pointer-events: none; z-index: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    background-size: 200px;
    border-radius: inherit;
    opacity: .6;
}
.sidebar > * { position: relative; z-index: 1; }

/* Header */
.sidebar-header {
    padding: 20px 16px 14px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--sb-border);
}
.sidebar-logo { display: flex; align-items: center; gap: 10px; }
.logo-icon {
    width: 34px; height: 34px;
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep));
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 16px var(--c-coral-glow);
    overflow: hidden;
}
.logo-text { font-size: 17px; font-weight: 700; color: var(--sb-text); letter-spacing: -.3px; }

.sidebar-close {
    display: none;
    background: var(--sb-surface); border: none;
    color: #94a3b8; cursor: pointer;
    padding: 6px 8px; border-radius: 6px; font-size: 16px;
    transition: all .2s;
}
.sidebar-close:hover { background: rgba(239,68,68,.15); color: #f87171; }

/* User row */
.sidebar-user {
    padding: 14px 16px;
    display: flex; align-items: center; gap: 10px;
    border-bottom: 1px solid var(--sb-border);
}
.user-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px;
}
.rescuer-avatar {
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep));
    color: #fff;
    box-shadow: 0 0 10px var(--c-coral-glow);
}
.user-info .user-name { font-size: 13px; font-weight: 600; color: var(--sb-text); }
.user-role-badge {
    font-size: 10px; padding: 2px 7px; border-radius: 20px;
    font-weight: 600; margin-top: 2px; display: inline-block;
}
.rescuer-badge {
    background: var(--c-coral-muted);
    color: var(--c-coral-soft);
    border: 1px solid var(--c-coral-border);
}

/* Nav */
.sidebar-nav { padding: 12px 8px; flex: 1; display: flex; flex-direction: column; gap: 2px; }
.nav-label {
    font-size: 10px; font-weight: 700; color: var(--sb-label);
    letter-spacing: 1px; text-transform: uppercase;
    padding: 10px 8px 4px;
}
.nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px; border-radius: 8px;
    color: var(--sb-muted); text-decoration: none;
    font-size: 13px; font-weight: 500;
    transition: all 0.18s; position: relative;
    cursor: pointer; background: none; border: none;
    width: 100%; text-align: left;
    font-family: inherit;
}
.nav-item i { width: 18px; text-align: center; font-size: 14px; flex-shrink: 0; }
.nav-item:hover { background: var(--sb-surface-h); color: var(--sb-text); }
.nav-item.active {
    background: var(--c-coral-muted);
    color: var(--c-coral);
    border: 1px solid var(--c-coral-border);
}
.nav-item.active i { color: var(--c-coral); }
.nav-badge {
    margin-left: auto; background: #ef4444; color: #fff;
    font-size: 10px; font-weight: 700; padding: 1px 7px;
    border-radius: 10px; min-width: 18px; text-align: center;
}
.nav-badge.hidden { display: none; }
.nav-badge-coral { background: var(--c-coral) !important; }
.logout-item { color: var(--sb-muted); }
.logout-item:hover { background: rgba(239,68,68,.08) !important; color: #f87171 !important; }

/* ═══════════════════════════════════════════════════
   MOBILE BOTTOM NAVBAR
═══════════════════════════════════════════════════ */
.mobile-bottom-nav {
    display: none; position: fixed;
    bottom: 0; left: 0; right: 0; height: 64px;
    background: var(--sb-bg);
    border-top: 1px solid var(--sb-border);
    z-index: 9999;
    padding-bottom: env(safe-area-inset-bottom, 0);
    box-shadow: 0 -4px 24px rgba(0,0,0,.4);
}
.mobile-bottom-nav-inner { display: flex; height: 100%; align-items: stretch; }
.mob-nav-item {
    flex: 1; display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 3px; text-decoration: none;
    color: #4A3530; font-size: 10px; font-weight: 600;
    letter-spacing: .2px; padding: 6px 2px;
    transition: color .2s; position: relative;
    cursor: pointer; background: none; border: none;
    font-family: inherit;
}
.mob-nav-item i { font-size: 18px; transition: all .2s; }
.mob-nav-item.active { color: var(--c-coral); }
.mob-nav-item.active i { color: var(--c-coral); }
.mob-nav-item.active::before {
    content: ''; position: absolute;
    top: 0; left: 20%; right: 20%; height: 2px;
    background: var(--c-coral); border-radius: 0 0 3px 3px;
}
.mob-nav-item:hover { color: #7A5F58; }
.mob-nav-badge {
    position: absolute; top: 5px; right: calc(50% - 20px);
    background: #ef4444; color: #fff;
    font-size: 9px; font-weight: 700; min-width: 16px; height: 16px;
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
    padding: 0 3px; border: 1px solid var(--sb-bg);
}
.mob-nav-badge.hidden { display: none; }
.mob-nav-badge.coral-badge { background: var(--c-coral); }

/* ═══════════════════════════════════════════════════
   DASHBOARD CORAL OVERRIDES
═══════════════════════════════════════════════════ */
body {
    --green        : var(--c-coral);
    --green-bg     : var(--c-coral-muted);
    --green-border : var(--c-coral-border);
    --indigo       : var(--c-navy-light);
    --indigo-border: rgba(45,52,120,.25);

    --text-primary  : #1E2450 !important;
    --text-secondary: #374151 !important;
    --text-muted    : #6B7280 !important;
    --text-label    : #9CA3AF !important;
    color: #1E2450;
}

.main-content, .page-content, .section-card, .chart-card, .stat-card {
    color: #1E2450 !important;
}
table { color: #1E2450 !important; }
table td { color: #1E2450 !important; }
table thead th { color: #fff !important; }
table td span[style*="font-weight:600"],
table td span[style*="font-weight: 600"] { color: #1E2450 !important; font-weight: 700 !important; }
.td-muted { color: #6B7280 !important; }

.stat-value  { color: #1E2450 !important; }
.stat-label  { color: #6B7280 !important; }
.stat-sub    { color: #9CA3AF !important; }
.section-title    { color: #1E2450 !important; }
.section-subtitle { color: #6B7280 !important; }
.page-title   { color: #1E2450 !important; }
.topbar-time  { color: #6B7280 !important; }
.btn-ghost    { color: #374151 !important; }
.btn-ghost:hover { color: #494a4d !important; }
.chart-card-title { color: #1E2450 !important; }
.bpm-unit { color: #9CA3AF !important; }

/* Topbar live dot → coral */
.live-dot {
    background: var(--c-coral) !important;
    box-shadow: 0 0 6px var(--c-coral-glow) !important;
}
.live-indicator { color: var(--c-coral) !important; font-weight: 700; }

.stat-card { background: #fff; }
.text-green { color: var(--c-coral) !important; }
.text-red   { /* keep red for critical */ }

.btn-primary {
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep)) !important;
    color: #fff !important;
    box-shadow: 0 2px 12px var(--c-coral-glow) !important;
}
.btn-primary:hover { filter: brightness(1.08) !important; transform: translateY(-1px); }

.btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
}

.badge-normal {
    background: var(--c-coral-muted) !important;
    color: var(--c-coral) !important;
    border-color: var(--c-coral-border) !important;
}
.fill-normal { background: var(--c-coral) !important; }
.bpm-normal  { color: var(--c-coral) !important; }
.bpm-critical { color: #ef4444 !important; animation: bpm-pulse 1s infinite; }

.page-content { background: #F8F4F2 !important; }
.main-content { background: #F8F4F2 !important; }

.topbar {
    background: #fff !important;
    border-bottom: 1px solid rgba(239,108,82,.12) !important;
    box-shadow: 0 1px 12px rgba(239,108,82,.08) !important;
}
.section-card {
    background: #dcd9d7 !important;
    border: 1px solid rgba(28, 27, 27, 0.1) !important;
}
.chart-card {
    background: #cbc9c8 !important;
    border: 1px solid rgba(239,108,82,.1) !important;
}
table tbody tr { border-bottom: 1px solid rgba(239,108,82,.07) !important; }
table tbody tr:hover { background: rgba(239,108,82,.03) !important; }

/* Tabs */
.tab-btn.active {
    color: var(--c-coral) !important;
    border-bottom-color: var(--c-coral) !important;
}
.tab-btn:hover { color: var(--c-coral-deep) !important; }

/* Critical card glow */
.big-patient-card.critical-card { border-left-color: #ef4444 !important; }
.big-patient-card.warning-card  { border-left-color: #f59e0b !important; }
.big-patient-card.normal-card   { border-left-color: var(--c-coral) !important; }

/* Severity badges in reports table */
.severity-high     { background: rgba(246, 164, 164, 0.1);    color: #ef4444;    border: 1px solid rgba(239,68,68,.2); }
.severity-medium   { background: rgba(245,158,11,.1);   color: #f59e0b;    border: 1px solid rgba(245,158,11,.2); }
.severity-low      { background: var(--c-coral-muted);  color: var(--c-coral); border: 1px solid var(--c-coral-border); }
.severity-critical { background: rgba(239,68,68,.1);    color: #ef4444;    border: 1px solid rgba(239,68,68,.2); animation: bpm-pulse 1s infinite; }

/* Alert inbox unread dot */
.unread-dot { background: var(--c-coral) !important; }
.alert-item.unread { border-left-color: var(--c-coral) !important; background: var(--c-coral-muted) !important; }
.nav-alert-badge { background: var(--c-coral) !important; }

/* Form elements */
.form-input:focus, .form-select:focus, .form-textarea:focus {
    border-color: var(--c-coral) !important;
    box-shadow: 0 0 0 3px var(--c-coral-muted) !important;
}

/* ═══════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════ */
@media (max-width: 768px) {
    .sidebar {
        position: fixed; top: 0; left: 0;
        width: 280px; height: 100%;
        transform: translateX(-100%); z-index: 9998;
    }
    .sidebar.open { transform: translateX(0); }
    .sidebar-close { display: flex !important; }
    .mobile-bottom-nav { display: flex; }
    .page-content { padding-bottom: 80px !important; }
}
@media (min-width: 769px) {
    .mobile-bottom-nav { display: none !important; }
}
</style>

<!-- ── DESKTOP SIDEBAR ────────────────────────────────── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-icon">
                <img src="/VitalWearV2/assets/css/image.png" alt="VitalWear Logo" width="34" height="34">
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
        <div class="user-avatar rescuer-avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="user-role-badge rescuer-badge">
                <i class="fa-solid fa-shield-heart" style="font-size:9px;margin-right:3px"></i>Rescuer
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

        <div class="nav-label">Account</div>

        <a href="../api/login.php?action=logout" class="nav-item logout-item">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>

<!-- ── MOBILE BOTTOM NAVBAR ──────────────────────────── -->
<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <div class="mobile-bottom-nav-inner">
        <a href="rescuer_dashboard.php"
           class="mob-nav-item <?= (!isset($_GET['tab']) || $_GET['tab'] === 'overview') ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
        </a>
        <a href="rescuer_dashboard.php?tab=patients"
           class="mob-nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'patients') ? 'active' : '' ?>">
            <i class="fa-solid fa-user-injured"></i>
            <span>Patients</span>
        </a>
        <a href="rescuer_dashboard.php?tab=alerts"
           class="mob-nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'alerts') ? 'active' : '' ?>">
            <i class="fa-solid fa-bell"></i>
            <span>Alerts</span>
            <span class="mob-nav-badge coral-badge hidden" id="mobAlertBadge">0</span>
        </a>
        <a href="rescuer_dashboard.php?tab=report"
           class="mob-nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'report') ? 'active' : '' ?>">
            <i class="fa-solid fa-file-medical"></i>
            <span>Reports</span>
        </a>
        <a href="../api/login.php?action=logout" class="mob-nav-item">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>

<script>
// Sync alert badge in sidebar + mobile nav
function updateRescuerAlertBadge(count) {
    ['sidebarAlertBadge', 'mobAlertBadge'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = count;
        el.classList.toggle('hidden', count === 0);
    });
}
</script> 