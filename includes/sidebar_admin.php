<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════════
   ROOT — Coral / Warm Palette  (Admin version)
═══════════════════════════════════════════════════ */
:root {
    --c-coral      : #EF6C52;
    --c-coral-deep : #E05A3A;
    --c-coral-soft : #FF9A7B;
    --c-coral-glow : rgba(239,108,82,.35);
    --c-coral-muted: rgba(239,108,82,.12);
    --c-coral-border: rgba(239,108,82,.30);

    --c-navy       : #1E2450;
    --c-navy-light : #2D3478;

    /* Sidebar */
    --sb-bg        : #1C1014;
    --sb-border    : rgba(255,255,255,.07);
    --sb-surface   : rgba(255,255,255,.05);
    --sb-surface-h : rgba(255,255,255,.09);
    --sb-text      : #F1ECE9;
    --sb-muted     : #7A6A65;
    --sb-label     : #4D3830;

    /* Main content */
    --bg-page      : #F8F4F2;
    --bg-card      : #FFFFFF;
    --text-primary : #1E2450;
    --text-secondary: #374151;
    --text-muted   : #6B7280;
    --text-label   : #9CA3AF;

    /* Card borders & shadows */
    --card-border  : rgba(239,108,82,.30);
    --card-shadow  : 0 4px 20px rgba(239,108,82,.18), 0 1px 6px rgba(30,36,80,.10);
    --card-shadow-hover: 0 12px 40px rgba(239,108,82,.28), 0 4px 12px rgba(30,36,80,.14);

    --bg-surface   : #0f172a;
    --bg-input     : #0f172a;
    --border       : #334155;
    --green        : #10b981;
    --green-bg     : rgba(16,185,129,.12);
    --green-border : rgba(16,185,129,.3);
    --red          : #ef4444;
    --red-bg       : rgba(239,68,68,.12);
    --red-border   : rgba(239,68,68,.3);
    --yellow       : #f59e0b;
    --blue         : #3b82f6;
    --radius       : 14px;
    --radius-sm    : 10px;
}

* { box-sizing: border-box; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg-page);
    color: var(--text-primary);
    margin: 0;
}

/* ── Layout ── */
.layout        { display: flex; min-height: 100vh; }
/* FIX: overflow-x: clip instead of hidden so position:sticky works on children */
.main-content  { flex: 1; min-width: 0; overflow-x: clip; }

/* ═══════════════════════════════════════════════════
   SIDEBAR
═══════════════════════════════════════════════════ */
.sidebar {
    width: 252px;
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
    box-shadow: 4px 0 32px rgba(0,0,0,.32);
    scrollbar-width: none;
}
.sidebar::-webkit-scrollbar { display: none; }

/* Warm grain overlay */
.sidebar::before {
    content: '';
    position: absolute; inset: 0; pointer-events: none; z-index: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    background-size: 200px; opacity: .6;
}
.sidebar > * { position: relative; z-index: 1; }

/* Header */
.sidebar-header {
    padding: 22px 18px 16px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--sb-border);
}
.sidebar-logo { display: flex; align-items: center; gap: 11px; }
.logo-icon {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep));
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 20px var(--c-coral-glow);
    overflow: hidden;
}
.logo-text {
    font-size: 17px; font-weight: 800; color: var(--sb-text); letter-spacing: -.4px;
}
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
    padding: 16px 18px;
    display: flex; align-items: center; gap: 11px;
    border-bottom: 1px solid var(--sb-border);
    background: rgba(239,108,82,.04);
}
.user-avatar {
    width: 38px; height: 38px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 15px;
}
.admin-avatar {
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep));
    color: #fff;
    box-shadow: 0 0 14px var(--c-coral-glow), 0 2px 6px rgba(0,0,0,.3);
}
.user-info .user-name {
    font-size: 13px; font-weight: 700; color: var(--sb-text); line-height: 1.3;
}
.user-role-badge {
    font-size: 10px; padding: 2px 8px; border-radius: 20px;
    font-weight: 700; margin-top: 3px; display: inline-flex;
    align-items: center; gap: 4px; letter-spacing: .2px;
}
.admin-badge {
    background: var(--c-coral-muted);
    color: var(--c-coral-soft);
    border: 1px solid var(--c-coral-border);
}

/* Nav */
.sidebar-nav { padding: 14px 10px; flex: 1; display: flex; flex-direction: column; gap: 2px; }
.nav-label {
    font-size: 10px; font-weight: 700; color: var(--sb-label);
    letter-spacing: 1.2px; text-transform: uppercase;
    padding: 12px 8px 5px;
}
.nav-item {
    display: flex; align-items: center; gap: 11px;
    padding: 10px 13px; border-radius: 9px;
    color: var(--sb-muted); text-decoration: none;
    font-size: 13px; font-weight: 600;
    transition: all 0.18s; position: relative;
    cursor: pointer; background: none; border: none;
    width: 100%; text-align: left;
    font-family: 'DM Sans', sans-serif;
}
.nav-item i { width: 18px; text-align: center; font-size: 14px; flex-shrink: 0; transition: color .18s; }
.nav-item svg { flex-shrink: 0; transition: color .18s; }
.nav-item:hover {
    background: var(--sb-surface-h);
    color: var(--sb-text);
}
.nav-item:hover i, .nav-item:hover svg { color: var(--c-coral-soft); stroke: var(--c-coral-soft); }
.nav-item.active {
    background: var(--c-coral-muted);
    color: var(--c-coral-soft);
    border: 1px solid var(--c-coral-border);
    box-shadow: 0 2px 12px rgba(239,108,82,.12);
}
.nav-item.active i, .nav-item.active svg { color: var(--c-coral); stroke: var(--c-coral); }

.nav-badge {
    margin-left: auto; background: #746c6c; color: #fff;
    font-size: 10px; font-weight: 700; padding: 2px 7px;
    border-radius: 10px; min-width: 20px; text-align: center;
}
.sidebar-divider { height: 1px; background: var(--sb-border); margin: 6px 14px; }
.logout-item { color: var(--sb-muted); }
.logout-item:hover { background: rgba(239,68,68,.10) !important; color: #f87171 !important; }
.logout-item:hover i, .logout-item:hover svg { color: #f87171 !important; stroke: #f87171 !important; }

/* ═══════════════════════════════════════════════════
   TOPBAR
═══════════════════════════════════════════════════ */
.topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 28px; height: 60px;
    background: #eadddd;
    border-bottom: 2px solid rgba(70, 65, 65, 0.2);
    box-shadow: 0 2px 20px rgba(70, 65, 65, 0.2);
    position: sticky; top: 0; z-index: 100;
}
.topbar-left { display: flex; align-items: center; gap: 14px; }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.menu-toggle {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); padding: 6px; border-radius: 8px;
    display: flex; align-items: center; transition: background .2s, color .2s;
}
.menu-toggle:hover { background: var(--c-coral-muted); color: var(--c-coral); }
.page-title { font-size: 16px; font-weight: 700; color: var(--text-primary); }
.live-indicator {
    display: flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 800; color: var(--c-coral); letter-spacing: .5px;
}
.live-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--c-coral);
    box-shadow: 0 0 8px var(--c-coral-glow);
    animation: livePulse 1.4s ease-in-out infinite;
}
@keyframes livePulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.6;transform:scale(1.3)} }
.topbar-time { font-size: 13px; font-weight: 600; color: var(--text-muted); font-variant-numeric: tabular-nums; }

/* ═══════════════════════════════════════════════════
   PAGE CONTENT
═══════════════════════════════════════════════════ */
.page-content {
    padding: 28px;
    background: var(--bg-page);
    min-height: calc(100vh - 60px);
}

/* ═══════════════════════════════════════════════════
   STAT CARDS
═══════════════════════════════════════════════════ */
.stats-grid   { display: grid; gap: 18px; margin-bottom: 24px; }
.stats-grid-4 { grid-template-columns: repeat(4,1fr); }
.stats-grid-3 { grid-template-columns: repeat(3,1fr); }
.stats-grid-2 { grid-template-columns: repeat(2,1fr); }
.mb-6 { margin-bottom: 24px; }

.stat-card {
    background: var(--bg-card);
    border-radius: 14px;
    padding: 22px 22px 18px;
    border: 1.5px solid var(--card-border);
    box-shadow: var(--card-shadow);
    transition: transform .2s, box-shadow .2s;
    position: relative; overflow: hidden;
}
.stat-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    border-radius: 14px 14px 0 0;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover); }
.card-blue::before   { background: linear-gradient(90deg,#3b82f6,#6366f1); }
.card-green::before  { background: linear-gradient(90deg,var(--c-coral),var(--c-coral-soft)); }
.card-yellow::before { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
.card-red::before    { background: linear-gradient(90deg,#ef4444,#f87171); }
.card-purple::before { background: linear-gradient(90deg,#8b5cf6,#a78bfa); }

.stat-card-header { margin-bottom: 12px; }
.stat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
}
.stat-icon.blue   { background: rgba(59,130,246,.12);  color: #3b82f6; }
.stat-icon.green  { background: var(--c-coral-muted);  color: var(--c-coral); }
.stat-icon.yellow { background: rgba(245,158,11,.12);  color: #f59e0b; }
.stat-icon.red    { background: rgba(239,68,68,.12);   color: #ef4444; }
.stat-icon.purple { background: rgba(139,92,246,.12);  color: #8b5cf6; }
.stat-icon svg { width: 20px; height: 20px; }
.stat-label  { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .8px; }
.stat-value  { font-size: 32px; font-weight: 800; color: var(--text-primary); line-height: 1.1; margin: 4px 0; }
.stat-sub    { font-size: 12px; color: var(--text-label); font-weight: 500; }
.text-green  { color: var(--c-coral) !important; }
.text-yellow { color: #f59e0b !important; }
.text-red    { color: #ef4444 !important; }
.text-blue   { color: #3b82f6 !important; }

/* ═══════════════════════════════════════════════════
   SECTION CARDS
═══════════════════════════════════════════════════ */
.section-card {
    background: var(--bg-card);
    border-radius: 14px;
    border: 1.5px solid var(--card-border);
    box-shadow: var(--card-shadow);
    overflow: hidden;
    margin-bottom: 20px;
}
.section-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px 16px;
    border-bottom: 1px solid rgba(239,108,82,.12);
}
.section-title    { font-size: 15px; font-weight: 700; color: var(--text-primary); }
.section-subtitle { font-size: 12px; color: var(--text-muted); margin-top: 2px; font-weight: 500; }

/* Charts */
.chart-container { padding: 16px 20px; }
.chart-wrapper   { position: relative; }

/* ═══════════════════════════════════════════════════
   TABS  — FIX: proper scrolling + all tabs visible
═══════════════════════════════════════════════════ */
.tabs {
    display: flex;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    -ms-overflow-style: none;
    scrollbar-width: none;
    gap: 2px;
    padding: 12px 12px 0;
    border-bottom: 2px solid rgba(239,108,82,.18);
    /* prevent tab bar from shrinking its children */
    flex-wrap: nowrap;
}
.tabs::-webkit-scrollbar { display: none; }

.tab-btn {
    white-space: nowrap;
    flex-shrink: 0;          /* IMPORTANT: never shrink — force scroll instead */
    color: var(--text-muted);
    font-weight: 600;
    font-size: 13px;
    border: none;
    background: none;
    cursor: pointer;
    padding: 10px 16px 12px;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all .2s;
    font-family: 'DM Sans', sans-serif;
    border-radius: 8px 8px 0 0;
    display: flex;
    align-items: center;
    gap: 5px;
}
.tab-btn:hover  { color: var(--c-coral); background: var(--c-coral-muted); }
.tab-btn.active {
    color: var(--c-coral);
    border-bottom-color: var(--c-coral);
    font-weight: 700;
    background: rgba(239,108,82,.06);
}

/* On very small screens: tighten padding, hide icons to save room */
@media (max-width: 480px) {
    .tab-btn { padding: 10px 11px 12px; font-size: 12px; }
    .tab-btn i { display: none; }
}

/* ═══════════════════════════════════════════════════
   TABLE
═══════════════════════════════════════════════════ */
.table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
table { width: 100%; border-collapse: collapse; }
table thead th {
    background: linear-gradient(135deg, #1E2450, #2D3478);
    color: #fff !important;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px;
    padding: 13px 18px; text-align: left;
}
table tbody tr {
    border-bottom: 1px solid rgba(239,108,82,.09);
    transition: background .15s;
}
table tbody tr:hover { background: rgba(239,108,82,.05) !important; }
table tbody td { padding: 14px 18px; font-size: 13px; color: var(--text-primary); }
.td-muted  { color: var(--text-muted) !important; }
.font-mono { font-family: monospace; }

/* ═══════════════════════════════════════════════════
   BADGES
═══════════════════════════════════════════════════ */
.badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700; white-space: nowrap;
    border: 1px solid transparent;
}
.badge-normal   { background: var(--c-coral-muted); color: var(--c-coral); border-color: var(--c-coral-border); }
.badge-warning  { background: rgba(245,158,11,.12);  color: #d97706; border-color: rgba(245,158,11,.25); }
.badge-critical { background: rgba(239,68,68,.12);   color: #ef4444; border-color: rgba(239,68,68,.25); }
.badge-admin    { background: rgba(239,108,82,.12);  color: var(--c-coral); border-color: var(--c-coral-border); }
.badge-manager  { background: rgba(59,130,246,.12);  color: #3b82f6; border-color: rgba(59,130,246,.25); }
.badge-rescuer  { background: rgba(245,158,11,.12);  color: #d97706; border-color: rgba(245,158,11,.25); }
.badge-responder{ background: rgba(139,92,246,.12);  color: #8b5cf6; border-color: rgba(139,92,246,.25); }

/* Severity badges */
.sev-badge    { padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;white-space:nowrap; }
.sev-low      { background:var(--c-coral-muted);color:var(--c-coral);border:1px solid var(--c-coral-border); }
.sev-medium   { background:rgba(245,158,11,.12);color:#d97706;border:1px solid rgba(245,158,11,.25); }
.sev-high     { background:rgba(239,68,68,.12);color:#ef4444;border:1px solid rgba(239,68,68,.25); }
.sev-critical { background:rgba(185,28,28,.1);color:#b91c1c;border:1px solid rgba(185,28,28,.25); }

/* ═══════════════════════════════════════════════════
   BPM DISPLAY
═══════════════════════════════════════════════════ */
.bpm-value    { font-weight: 800; font-size: 17px; font-variant-numeric: tabular-nums; }
.bpm-normal   { color: var(--c-coral) !important; }
.bpm-warning  { color: #f59e0b !important; }
.bpm-critical { color: #ef4444 !important; }

/* ═══════════════════════════════════════════════════
   BUTTONS
═══════════════════════════════════════════════════ */
.btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px; border-radius: 9px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    border: 1px solid transparent; transition: all .2s;
    font-family: 'DM Sans', sans-serif; line-height: 1;
    white-space: nowrap; text-decoration: none;
}
.btn-sm { padding: 6px 13px; font-size: 12px; }
.btn-primary {
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep)) !important;
    color: #fff !important;
    box-shadow: 0 2px 14px var(--c-coral-glow), 0 1px 4px rgba(0,0,0,.1) !important;
    border-color: transparent !important;
}
.btn-primary:hover { filter: brightness(1.07); transform: translateY(-1px); box-shadow: 0 6px 22px var(--c-coral-glow) !important; }
.btn-ghost {
    background: transparent; color: var(--text-secondary) !important;
    border-color: rgba(0,0,0,.1);
}
.btn-ghost:hover { background: var(--c-coral-muted); color: var(--c-coral) !important; border-color: var(--c-coral-border); }
.btn-danger { background: rgba(239,68,68,.1); color: #ef4444; border-color: rgba(239,68,68,.2); }
.btn-danger:hover { background: rgba(239,68,68,.2); }

/* ═══════════════════════════════════════════════════
   DEVICE STATS GRID & STATUS
═══════════════════════════════════════════════════ */
.device-stats-grid {
    display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 22px;
}
.device-mini-card {
    background: var(--bg-card); border-radius: 12px; padding: 18px 20px;
    text-align: center; border: 1.5px solid var(--card-border);
    box-shadow: var(--card-shadow); transition: transform .2s, box-shadow .2s;
}
.device-mini-card:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover); }
.device-mini-card .dmc-count { font-size: 30px; font-weight: 800; margin: 6px 0 4px; line-height: 1; }
.device-mini-card .dmc-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .7px; }
.device-mini-card .dmc-sub   { font-size: 11px; color: var(--text-label); margin-top: 2px; }

.status-select {
    background: #F9FAFB !important; border: 1.5px solid #D1D5DB !important;
    color: var(--text-primary) !important; border-radius: 8px !important;
    padding: 5px 10px !important; font-size: 12px !important; font-weight: 600;
    cursor: pointer; font-family: 'DM Sans', sans-serif;
}
.status-select:focus { outline: none; border-color: var(--c-coral) !important; box-shadow: 0 0 0 3px var(--c-coral-muted) !important; }

.action-btn { padding:5px 13px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid transparent;margin-left:4px;transition:all .18s;font-family:'DM Sans',sans-serif; }
.action-btn-blue   { background:var(--c-coral-muted);color:var(--c-coral);border-color:var(--c-coral-border); }
.action-btn-blue:hover { background:rgba(239,108,82,.2); }
.action-btn-yellow { background:rgba(245,158,11,.1);color:#d97706;border-color:rgba(245,158,11,.25); }
.action-btn-yellow:hover { background:rgba(245,158,11,.2); }

/* ═══════════════════════════════════════════════════
   MODALS — FIX: strong visible borders & dividers
═══════════════════════════════════════════════════ */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.55); backdrop-filter: blur(4px);
    z-index: 1000; align-items: center; justify-content: center;
}
.modal-overlay.open, .modal-overlay.active { display: flex; }
.modal {
    background: #fff;
    border-radius: 16px;
    width: 100%; max-width: 480px;
    /* FIX: much stronger border so the modal outline is clearly visible */
    box-shadow: 0 24px 64px rgba(0,0,0,.22), 0 4px 16px rgba(239,108,82,.18), 0 0 0 1.5px rgba(239,108,82,.50);
    border: 2px solid rgba(239,108,82,.45);
    max-height: 90vh; display: flex; flex-direction: column;
}
.modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 22px 26px 18px;
    /* FIX: stronger header divider */
    border-bottom: 1.5px solid rgba(239,108,82,.28);
    flex-shrink: 0;
}
.modal-title { font-size: 16px; font-weight: 800; color: var(--text-primary); margin: 0; }
.modal-close {
    background: rgba(239,108,82,.08); border: 1px solid rgba(239,108,82,.20);
    font-size: 20px; color: var(--text-muted); cursor: pointer;
    padding: 2px 8px; line-height: 1; border-radius: 6px;
    transition: all .2s;
}
.modal-close:hover { background: rgba(239,68,68,.12); color: #ef4444; border-color: rgba(239,68,68,.30); }
.modal-body { padding: 22px 26px; overflow-y: auto; flex: 1; }
.modal-footer {
    padding: 16px 26px;
    /* FIX: stronger footer divider */
    border-top: 1.5px solid rgba(239,108,82,.22);
    display: flex; gap: 10px; justify-content: flex-end; flex-shrink: 0;
    background: rgba(239,108,82,.03);
}

/* ── Form elements — FIX: stronger visible borders ── */
.form-group  { margin-bottom: 16px; }
.form-label  {
    display: block; font-size: 11px; font-weight: 700;
    color: var(--text-muted); margin-bottom: 6px;
    text-transform: uppercase; letter-spacing: .6px;
}
.form-input, .form-select {
    width: 100%; padding: 10px 14px;
    background: #F9FAFB;
    /* FIX: darker border so fields are clearly defined */
    border: 1.5px solid #C9CDD4;
    border-radius: 9px; color: var(--text-primary); font-size: 13px;
    font-family: 'DM Sans', sans-serif; transition: all .2s;
    box-shadow: inset 0 1px 3px rgba(0,0,0,.05);
}
.form-input:focus, .form-select:focus {
    outline: none; border-color: var(--c-coral);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(239,108,82,.15), inset 0 1px 3px rgba(0,0,0,.03);
}
.form-input::placeholder { color: #B0B7C3; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

/* Report cards */
.report-card { background:#F9FAFB;border:1.5px solid rgba(239,108,82,.20);border-radius:11px;padding:16px 18px;margin-bottom:12px;box-shadow:0 1px 6px rgba(0,0,0,.05); }
.report-card:last-child { margin-bottom:0; }
.report-card-header { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;gap:10px; }
.report-card-title  { font-size:14px;font-weight:700;color:var(--text-primary);flex:1; }
.report-card-meta   { font-size:12px;color:var(--text-muted);margin-bottom:6px;display:flex;gap:14px;flex-wrap:wrap; }
.report-card-desc   { font-size:13px;color:var(--text-secondary);line-height:1.6;white-space:pre-wrap; }
.report-empty       { text-align:center;padding:50px 20px;color:var(--text-muted);font-size:14px; }
.patient-report-info { display:flex;gap:12px;align-items:center;padding:12px 16px;background:rgba(239,108,82,.05);border-radius:10px;margin-bottom:16px;border:1.5px solid rgba(239,108,82,.20); }
.pri-name { font-size:16px;font-weight:800;color:var(--text-primary); }
.pri-sub  { font-size:12px;color:var(--text-muted);margin-top:2px; }
.report-count-badge { display:inline-flex;align-items:center;justify-content:center;background:var(--c-coral-muted);color:var(--c-coral);border:1px solid var(--c-coral-border);border-radius:20px;font-size:11px;font-weight:700;padding:2px 8px;margin-left:6px; }

/* Toast */
.toast-container { position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px; }

/* Sidebar overlay */
.sidebar-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9997; }
.sidebar-overlay.open { display:block; }

/* ═══════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════ */
@media (max-width: 1100px) {
    .stats-grid-4    { grid-template-columns: repeat(2,1fr); }
    .device-stats-grid{ grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 900px) {
    .stats-grid-3 { grid-template-columns: repeat(2,1fr); }
    .stats-grid-2 { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .sidebar {
        position: fixed; top: 0; left: 0;
        width: 280px; height: 100%;
        transform: translateX(-100%); z-index: 9998;
    }
    .sidebar.open { transform: translateX(0); }
    .sidebar-close { display: flex !important; }
    .page-content { padding: 16px; }
    .topbar { padding: 0 16px; }
    /* Modal full-width on mobile */
    .modal { max-width: calc(100vw - 24px); margin: 0 12px; }
}
@media (max-width: 600px) {
    .stats-grid-4, .stats-grid-3 { grid-template-columns: repeat(2,1fr); }
    .device-stats-grid { grid-template-columns: repeat(2,1fr); }
    .section-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .form-grid { grid-template-columns: 1fr; }
}
/* ── Hide hamburger on desktop ── */
@media (min-width: 769px) {
    .menu-toggle { display: none !important; }
}
</style>

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

