<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════════
   ROOT — Coral / Warm Palette (matches responder UI)
═══════════════════════════════════════════════════ */
:root {
    --c-coral      : #EF6C52;
    --c-coral-deep : #E05A3A;
    --c-coral-soft : #FF9A7B;
    --c-coral-glow : rgba(239,108,82,.30);
    --c-coral-muted: rgba(239,108,82,.10);
    --c-coral-border: rgba(239,108,82,.22);

    --c-navy       : #1E2450;
    --c-navy-light : #2D3478;

    --sb-bg        : #1A0F0C;
    --sb-border    : rgba(255,255,255,.07);
    --sb-surface   : rgba(255,255,255,.05);
    --sb-surface-h : rgba(255,255,255,.09);
    --sb-text      : #F5EDE9;
    --sb-muted     : #8A7570;
    --sb-label     : #4A3830;

    /* Main content palette — LIGHT, HIGH CONTRAST */
    --bg-page      : #F8F4F2;
    --bg-card      : #FFFFFF;
    --text-primary : #1A1A2E;
    --text-secondary: #2D3748;
    --text-muted   : #5A6272;
    --text-label   : #8A94A6;
    --card-border  : rgba(239,108,82,.14);
    --card-shadow  : 0 2px 20px rgba(30,36,80,.10), 0 1px 6px rgba(30,36,80,.07);
    --card-shadow-hover: 0 10px 40px rgba(239,108,82,.18), 0 4px 12px rgba(30,36,80,.12);
    --card-shadow-strong: 0 4px 28px rgba(30,36,80,.14), 0 1px 8px rgba(30,36,80,.09);
}

* { box-sizing: border-box; }

body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg-page);
    color: var(--text-primary);
    margin: 0;
    -webkit-font-smoothing: antialiased;
}

/* ── Layout ── */
.layout { display: flex; min-height: 100vh; }
.main-content { flex: 1; min-width: 0; overflow-x: hidden; }

/* ═══════════════════════════════════════════════════
   SIDEBAR
═══════════════════════════════════════════════════ */
.sidebar {
    width: 256px;
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
    box-shadow: 4px 0 32px rgba(0,0,0,.28);
    scrollbar-width: none;
}
.sidebar::-webkit-scrollbar { display: none; }

/* Warm grain overlay */
.sidebar::before {
    content: '';
    position: absolute; inset: 0; pointer-events: none; z-index: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.025'/%3E%3C/svg%3E");
    background-size: 200px; opacity: .5;
}
.sidebar > * { position: relative; z-index: 1; }

/* Header */
.sidebar-header {
    padding: 22px 18px 16px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--sb-border);
    flex-shrink: 0;
}
.sidebar-logo { display: flex; align-items: center; gap: 11px; text-decoration: none; }
.logo-icon {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep));
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 20px var(--c-coral-glow);
    overflow: hidden; flex-shrink: 0;
}
.logo-text {
    font-size: 17px; font-weight: 800; color: var(--sb-text); letter-spacing: -.4px;
}

/* Close button — only shown on mobile */
.sidebar-close {
    display: none;
    background: var(--sb-surface); border: none;
    color: #94a3b8; cursor: pointer;
    width: 32px; height: 32px; border-radius: 8px;
    align-items: center; justify-content: center; font-size: 14px;
    transition: all .2s; flex-shrink: 0;
}
.sidebar-close:hover { background: rgba(239,68,68,.15); color: #f87171; }

/* User row */
.sidebar-user {
    padding: 16px 18px;
    display: flex; align-items: center; gap: 11px;
    border-bottom: 1px solid var(--sb-border);
    background: rgba(239,108,82,.04);
    flex-shrink: 0;
}
.user-avatar {
    width: 38px; height: 38px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 15px; flex-shrink: 0;
}
.manager-avatar {
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep));
    color: #fff;
    box-shadow: 0 0 14px var(--c-coral-glow), 0 2px 6px rgba(0,0,0,.3);
}
.user-info { min-width: 0; }
.user-info .user-name {
    font-size: 13px; font-weight: 700; color: var(--sb-text);
    line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.user-role-badge {
    font-size: 10px; padding: 2px 8px; border-radius: 20px;
    font-weight: 700; margin-top: 3px; display: inline-flex; align-items: center; gap: 4px;
    letter-spacing: .2px;
}
.manager-badge {
    background: var(--c-coral-muted);
    color: var(--c-coral-soft);
    border: 1px solid var(--c-coral-border);
}

/* Nav */
.sidebar-nav { padding: 12px 10px; flex: 1; display: flex; flex-direction: column; gap: 1px; }
.nav-label {
    font-size: 10px; font-weight: 700; color: var(--sb-label);
    letter-spacing: 1.2px; text-transform: uppercase;
    padding: 12px 9px 5px;
}
.nav-item {
    display: flex; align-items: center; gap: 11px;
    padding: 10px 13px; border-radius: 9px;
    color: var(--sb-muted); text-decoration: none;
    font-size: 13px; font-weight: 600;
    transition: all 0.15s; position: relative;
    cursor: pointer; background: none; border: none;
    width: 100%; text-align: left;
    font-family: 'DM Sans', sans-serif;
    white-space: nowrap;
}
.nav-item i {
    width: 18px; text-align: center; font-size: 14px;
    flex-shrink: 0; transition: color .15s;
}
.nav-item:hover {
    background: var(--sb-surface-h);
    color: var(--sb-text);
}
.nav-item:hover i { color: var(--c-coral-soft); }
.nav-item.active {
    background: var(--c-coral-muted);
    color: var(--c-coral-soft);
    border: 1px solid var(--c-coral-border);
    box-shadow: 0 2px 12px rgba(239,108,82,.12);
}
.nav-item.active i { color: var(--c-coral); }

.nav-badge {
    margin-left: auto; background: #746d6d; color: #fff;
    font-size: 10px; font-weight: 700; padding: 2px 7px;
    border-radius: 10px; min-width: 20px; text-align: center;
}
.nav-badge.hidden { display: none; }

.logout-item { color: var(--sb-muted); }
.logout-item:hover { background: rgba(239,68,68,.10) !important; color: #f87171 !important; }
.logout-item:hover i { color: #f87171 !important; }

/* ── Sidebar divider ── */
.sidebar-divider {
    height: 1px; background: var(--sb-border);
    margin: 6px 14px;
}

/* Sidebar overlay for mobile */
.sidebar-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.55); z-index: 9997;
    backdrop-filter: blur(2px);
}
.sidebar-overlay.open { display: block; }

/* ═══════════════════════════════════════════════════
   TOPBAR
═══════════════════════════════════════════════════ */
.topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 28px; height: 60px;
    background: #fff;
    border-bottom: 1.5px solid rgba(16, 14, 14, 0.13);
    box-shadow: 0 2px 20px rgba(34, 34, 38, 0.08);
    position: sticky; top: 0; z-index: 100;
}
.topbar-left { display: flex; align-items: center; gap: 14px; }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.menu-toggle {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); padding: 7px; border-radius: 8px;
    display: flex; align-items: center;
    transition: background .2s, color .2s;
}
.menu-toggle:hover { background: var(--c-coral-muted); color: var(--c-coral); }
.page-title {
    font-size: 16px; font-weight: 700; color: var(--text-primary);
}
.live-indicator {
    display: flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 800; color: var(--c-coral);
    letter-spacing: .5px;
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
   STAT CARDS — high contrast, strong shadows
═══════════════════════════════════════════════════ */
.stats-grid { display: grid; gap: 18px; margin-bottom: 24px; }
.stats-grid-4 { grid-template-columns: repeat(4,1fr); }
.stats-grid-3 { grid-template-columns: repeat(3,1fr); }
.stats-grid-2 { grid-template-columns: repeat(2,1fr); }
.mb-6 { margin-bottom: 24px; }

.stat-card {
    background: var(--bg-card);
    border-radius: 16px;
    padding: 24px 22px 20px;
    border: 1px solid var(--card-border);
    box-shadow: var(--card-shadow-strong);
    transition: transform .22s, box-shadow .22s;
    position: relative;
    overflow: hidden;
}
.stat-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 3.5px;
    border-radius: 16px 16px 0 0;
}
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--card-shadow-hover);
}
.card-blue::before  { background: linear-gradient(90deg,#3b82f6,#6366f1); }
.card-green::before { background: linear-gradient(90deg,var(--c-coral),var(--c-coral-soft)); }
.card-yellow::before{ background: linear-gradient(90deg,#f59e0b,#fbbf24); }
.card-red::before   { background: linear-gradient(90deg,#ef4444,#f87171); }
.card-purple::before{ background: linear-gradient(90deg,#8b5cf6,#a78bfa); }

.stat-card-header { margin-bottom: 14px; }
.stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 10px rgba(0,0,0,.08);
}
.stat-icon.blue   { background: rgba(59,130,246,.12);  color: #3b82f6; }
.stat-icon.green  { background: var(--c-coral-muted);  color: var(--c-coral); }
.stat-icon.yellow { background: rgba(245,158,11,.12);  color: #d97706; }
.stat-icon.red    { background: rgba(239,68,68,.12);   color: #ef4444; }
.stat-icon.purple { background: rgba(139,92,246,.12);  color: #8b5cf6; }

/* HIGH CONTRAST text */
.stat-label  { font-size: 11px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: .9px; }
.stat-value  { font-size: 34px; font-weight: 800; color: #1A1A2E; line-height: 1.1; margin: 5px 0; }
.stat-sub    { font-size: 12px; color: #8A94A6; font-weight: 500; }
.text-green  { color: var(--c-coral) !important; }
.text-yellow { color: #d97706 !important; }
.text-red    { color: #ef4444 !important; }
.text-blue   { color: #3b82f6 !important; }

/* ═══════════════════════════════════════════════════
   SECTION CARDS — strong shadows, visible borders
═══════════════════════════════════════════════════ */
.section-card {
    background: var(--bg-card);
    border-radius: 16px;
    border: 1px solid rgba(239,108,82,.12);
    box-shadow: var(--card-shadow-strong);
    overflow: hidden;
    margin-bottom: 22px;
}
.section-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px 16px;
    border-bottom: 1.5px solid rgba(239,108,82,.09);
    background: linear-gradient(135deg, rgba(239,108,82,.03), transparent);
}
.section-title    { font-size: 15px; font-weight: 700; color: #1A1A2E; }
.section-subtitle { font-size: 12px; color: #6B7280; margin-top: 3px; font-weight: 500; }

/* Charts */
.chart-card {
    background: var(--bg-card);
    border-radius: 16px;
    border: 1px solid rgba(239,108,82,.12);
    box-shadow: var(--card-shadow-strong);
    overflow: hidden;
    padding-bottom: 20px;
    margin-bottom: 22px;
}
.chart-card-title {
    padding: 18px 22px 14px;
    font-size: 14px; font-weight: 700; color: #1A1A2E;
    display: flex; align-items: center; gap: 8px;
    border-bottom: 1.5px solid rgba(239,108,82,.08);
    background: linear-gradient(135deg, rgba(239,108,82,.02), transparent);
}
.chart-container { padding: 16px 20px; }
.chart-wrapper   { position: relative; }

/* ═══════════════════════════════════════════════════
   TABS — high contrast
═══════════════════════════════════════════════════ */
.tabs {
    display: flex;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    gap: 2px;
    padding: 14px 20px 0;
    border-bottom: 2px solid rgba(239,108,82,.14);
    background: linear-gradient(135deg, rgba(239,108,82,.03), transparent);
}
.tabs::-webkit-scrollbar { display: none; }
.tab-btn {
    white-space: nowrap; flex-shrink: 0;
    color: #6B7280; font-weight: 600; font-size: 13px;
    border: none; background: none; cursor: pointer;
    padding: 10px 18px 12px;
    border-bottom: 2.5px solid transparent;
    margin-bottom: -2px;
    transition: all .2s;
    font-family: 'DM Sans', sans-serif;
    border-radius: 8px 8px 0 0;
    display: flex; align-items: center; gap: 6px;
}
.tab-btn:hover  { color: var(--c-coral); background: rgba(239,108,82,.06); }
.tab-btn.active {
    color: var(--c-coral);
    border-bottom-color: var(--c-coral);
    font-weight: 700;
    background: rgba(239,108,82,.06);
}

/* ═══════════════════════════════════════════════════
   TABLE — strong contrast
═══════════════════════════════════════════════════ */
.table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
table { width: 100%; border-collapse: collapse; }
table thead th {
    background: linear-gradient(135deg, #1E2450, #2D3478);
    color: #fff !important;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .8px;
    padding: 13px 18px; text-align: left;
}
table tbody tr {
    border-bottom: 1px solid rgba(239,108,82,.07);
    transition: background .15s;
}
table tbody tr:hover { background: rgba(239,108,82,.04) !important; }
table tbody td { padding: 14px 18px; font-size: 13px; color: #1A1A2E; }
.td-muted { color: #5A6272 !important; font-weight: 500; }

/* ═══════════════════════════════════════════════════
   BADGES — high contrast
═══════════════════════════════════════════════════ */
.badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 11px; border-radius: 20px;
    font-size: 11px; font-weight: 700; white-space: nowrap;
    border: 1px solid transparent;
}
.badge-normal   { background: rgba(239,108,82,.12); color: #C45030; border-color: rgba(239,108,82,.28); }
.badge-warning  { background: rgba(245,158,11,.13);  color: #B45309; border-color: rgba(245,158,11,.30); }
.badge-critical { background: rgba(239,68,68,.12);   color: #B91C1C; border-color: rgba(239,68,68,.28); }

/* Severity badges */
.sev-low      { background: rgba(239,108,82,.12); color: #C45030; border: 1px solid rgba(239,108,82,.28); }
.sev-medium   { background: rgba(245,158,11,.13);  color: #B45309; border: 1px solid rgba(245,158,11,.30); }
.sev-high     { background: rgba(239,68,68,.12);   color: #B91C1C; border: 1px solid rgba(239,68,68,.28); }
.sev-critical { background: rgba(185,28,28,.12);   color: #991B1B; border: 1px solid rgba(185,28,28,.28); }

/* ═══════════════════════════════════════════════════
   BPM DISPLAY
═══════════════════════════════════════════════════ */
.bpm-value   { font-weight: 800; font-size: 17px; font-variant-numeric: tabular-nums; }
.bpm-normal  { color: #C45030 !important; }
.bpm-warning { color: #B45309 !important; }
.bpm-critical{ color: #B91C1C !important; }
.bpm-bar { height: 5px; background: rgba(0,0,0,.07); border-radius: 3px; margin-top: 5px; overflow: hidden; }
.bpm-bar-fill { height: 100%; border-radius: 3px; transition: width .5s ease; }
.fill-normal  { background: linear-gradient(90deg, var(--c-coral), var(--c-coral-soft)); }
.fill-warning { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.fill-critical{ background: linear-gradient(90deg, #ef4444, #f87171); }

/* ═══════════════════════════════════════════════════
   BUTTONS
═══════════════════════════════════════════════════ */
.btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 17px; border-radius: 9px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    border: 1px solid transparent; transition: all .2s;
    font-family: 'DM Sans', sans-serif; line-height: 1; white-space: nowrap;
    text-decoration: none;
}
.btn-sm { padding: 6px 13px; font-size: 12px; border-radius: 8px; }
.btn-primary {
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep));
    color: #fff;
    box-shadow: 0 2px 14px var(--c-coral-glow), 0 1px 4px rgba(0,0,0,.12);
    border-color: transparent;
}
.btn-primary:hover { filter: brightness(1.07); transform: translateY(-1px); box-shadow: 0 6px 22px var(--c-coral-glow); }
.btn-ghost {
    background: #F5F5F7; color: #374151;
    border-color: #E5E7EB;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.btn-ghost:hover { background: rgba(239,108,82,.08); color: var(--c-coral); border-color: var(--c-coral-border); }
.btn-danger { background: rgba(239,68,68,.10); color: #B91C1C; border-color: rgba(239,68,68,.25); }
.btn-danger:hover { background: rgba(239,68,68,.18); }

/* ═══════════════════════════════════════════════════
   DEVICE STATUS BADGES
═══════════════════════════════════════════════════ */
.device-status-badge { display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px; }
.device-status-badge::before { content:'';width:7px;height:7px;border-radius:50%;flex-shrink:0; }
.ds-usable      { background:rgba(16,185,129,.10); color:#065F46;border:1px solid rgba(16,185,129,.25); } .ds-usable::before      { background:#10b981; }
.ds-in-use      { background:rgba(59,130,246,.10); color:#1E40AF;border:1px solid rgba(59,130,246,.25); } .ds-in-use::before      { background:#3b82f6; }
.ds-maintenance { background:rgba(245,158,11,.10); color:#92400E;border:1px solid rgba(245,158,11,.25); } .ds-maintenance::before { background:#f59e0b; }
.ds-disposable  { background:rgba(239,68,68,.10);  color:#B91C1C;border:1px solid rgba(239,68,68,.25);  } .ds-disposable::before  { background:#ef4444; }

/* ═══════════════════════════════════════════════════
   DEVICE STATS GRID
═══════════════════════════════════════════════════ */
.device-stats-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px; }
.device-mini-card {
    background: var(--bg-card);
    border-radius: 14px;
    padding: 20px 20px 18px;
    text-align: center;
    border: 1px solid rgba(239,108,82,.12);
    box-shadow: var(--card-shadow-strong);
    transition: transform .2s, box-shadow .2s;
}
.device-mini-card:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover); }
.device-mini-card .dmc-count { font-size: 32px; font-weight: 800; margin: 7px 0 5px; line-height: 1; }
.device-mini-card .dmc-label { font-size: 11px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: .7px; }
.device-mini-card .dmc-sub   { font-size: 11px; color: #9CA3AF; margin-top: 3px; font-weight: 500; }

/* Status select */
.status-select {
    background: #F5F5F7;
    border: 1.5px solid #E5E7EB;
    color: #1A1A2E;
    border-radius: 8px;
    padding: 5px 10px;
    font-size: 12px; font-weight: 600;
    cursor: pointer; font-family: 'DM Sans', sans-serif;
    transition: border-color .2s;
}
.status-select:focus { outline: none; border-color: var(--c-coral); box-shadow: 0 0 0 3px var(--c-coral-muted); }

/* Action buttons */
.action-btn { padding:5px 13px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid transparent;margin-left:4px;transition:all .18s;font-family:'DM Sans',sans-serif; }
.action-btn-blue   { background:rgba(239,108,82,.10);color:#C45030;border-color:rgba(239,108,82,.25); }
.action-btn-blue:hover { background:rgba(239,108,82,.18); }
.action-btn-yellow { background:rgba(245,158,11,.10);color:#B45309;border-color:rgba(245,158,11,.25); }
.action-btn-yellow:hover { background:rgba(245,158,11,.18); }
.row-actions { display:flex;flex-wrap:wrap;gap:5px;align-items:center; }

/* ═══════════════════════════════════════════════════
   MODALS
═══════════════════════════════════════════════════ */
.modal-backdrop {
    display:none;position:fixed;inset:0;
    background:rgba(0,0,0,.5);backdrop-filter:blur(5px);
    z-index:1000;align-items:center;justify-content:center;padding:16px;
}
.modal-backdrop.open { display:flex; }
.modal-box {
    background:#fff;
    border-radius:18px;padding:28px 30px;
    width:100%;max-width:480px;
    box-shadow:0 24px 64px rgba(0,0,0,.18),0 4px 20px rgba(239,108,82,.10);
    border:1px solid rgba(239,108,82,.12);
    max-height:90vh;overflow-y:auto;
}
.modal-title { font-size:16px;font-weight:800;margin-bottom:20px;color:#1A1A2E; }
.modal-actions { display:flex;gap:10px;justify-content:flex-end;margin-top:22px; }

.modal-overlay {
    display:none;position:fixed;inset:0;
    background:rgba(0,0,0,.5);backdrop-filter:blur(5px);
    z-index:1000;align-items:center;justify-content:center;padding:16px;
}
.modal-overlay.open { display:flex; }
.modal {
    background:#fff;
    border-radius:18px;width:100%;max-width:480px;
    box-shadow:0 24px 64px rgba(0,0,0,.18),0 4px 20px rgba(239,108,82,.10);
    border:1px solid rgba(239,108,82,.12);
    max-height:90vh;display:flex;flex-direction:column;
}
.modal-header {
    display:flex;align-items:center;justify-content:space-between;
    padding:22px 26px 16px;
    border-bottom:1.5px solid rgba(239,108,82,.10);
    flex-shrink:0;
}
.modal-header .modal-title { font-size:16px;font-weight:800;color:#1A1A2E;margin:0; }
.modal-close {
    background:none;border:none;font-size:22px;
    color:#9CA3AF;cursor:pointer;
    padding:0 4px;line-height:1;transition:color .2s;
}
.modal-close:hover { color:#ef4444; }
.modal-body { padding:22px 26px;overflow-y:auto;flex:1; }
.modal-footer {
    padding:16px 26px;
    border-top:1.5px solid rgba(239,108,82,.09);
    display:flex;gap:10px;justify-content:flex-end;
    flex-shrink:0;
}

/* Form elements */
.form-group { margin-bottom:16px; }
.form-label { display:block;font-size:12px;font-weight:700;color:#6B7280;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px; }
.form-input, .form-select, .form-textarea {
    width:100%;padding:10px 14px;
    background:#F5F5F7;border:1.5px solid #E5E7EB;
    border-radius:9px;color:#1A1A2E;font-size:13px;
    font-family:'DM Sans',sans-serif;transition:all .2s;
    font-weight: 500;
}
.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline:none;border-color:var(--c-coral);
    background:#fff;box-shadow:0 0 0 3px rgba(239,108,82,.12);
}
.form-textarea { min-height:90px;resize:vertical; }
.form-grid { display:grid;grid-template-columns:1fr 1fr;gap:14px; }

/* Report cards */
.report-card {
    background:#F8F4F2;border:1px solid rgba(239,108,82,.12);
    border-radius:12px;padding:16px 18px;margin-bottom:12px;
    box-shadow:0 2px 8px rgba(0,0,0,.05);
}
.report-card:last-child { margin-bottom:0; }
.report-card-header { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;gap:10px; }
.report-card-title  { font-size:14px;font-weight:700;color:#1A1A2E;flex:1; }
.report-card-meta   { font-size:12px;color:#6B7280;margin-bottom:6px;display:flex;gap:14px;flex-wrap:wrap; }
.report-card-desc   { font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap; }
.report-empty       { text-align:center;padding:50px 20px;color:#9CA3AF;font-size:14px; }

.sev-badge { padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;white-space:nowrap; }

/* Patient report info */
.patient-report-info {
    display:flex;gap:12px;align-items:center;
    padding:12px 16px;background:rgba(239,108,82,.05);
    border-radius:10px;margin-bottom:16px;border:1px solid rgba(239,108,82,.14);
}
.pri-name { font-size:16px;font-weight:800;color:#1A1A2E; }
.pri-sub  { font-size:12px;color:#6B7280;margin-top:2px; }
.report-count-badge {
    display:inline-flex;align-items:center;justify-content:center;
    background:rgba(239,108,82,.12);color:#C45030;
    border:1px solid rgba(239,108,82,.25);
    border-radius:20px;font-size:11px;font-weight:700;padding:2px 8px;margin-left:6px;
}

/* History chart */
.history-controls { display:flex;align-items:center;gap:12px;padding:16px 22px 0;flex-wrap:wrap; }
.history-controls label { font-size:12px;font-weight:700;color:#6B7280; }
.history-patient-select {
    flex:1;min-width:180px;max-width:300px;padding:9px 13px;
    border-radius:9px;border:1.5px solid #E5E7EB;
    background:#F5F5F7;color:#1A1A2E;font-size:13px;
    font-family:'DM Sans',sans-serif;font-weight:500;
}
.history-patient-select:focus { outline:none;border-color:var(--c-coral);box-shadow:0 0 0 3px rgba(239,108,82,.12); }
.history-legend { display:flex;gap:16px;font-size:12px;color:#6B7280;padding:8px 22px 12px;flex-wrap:wrap;font-weight:500; }
.history-legend span { display:flex;align-items:center;gap:5px; }
.history-legend span::before { content:'';display:inline-block;width:22px;height:3px;border-radius:2px; }
.legend-normal::before  { background:var(--c-coral); }
.legend-warning::before { background:#f59e0b; }
.legend-critical::before{ background:#ef4444; }
.no-history-msg { text-align:center;padding:48px 20px;color:#9CA3AF;font-size:14px; }

/* Toast */
.toast-container { position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px; }

/* Alert banner */
.alert-banner {
    display:flex;align-items:center;gap:14px;
    background:linear-gradient(135deg,rgba(239,108,82,.08),rgba(224,90,58,.04));
    border:1.5px solid rgba(239,108,82,.22);border-left:4px solid var(--c-coral);
    border-radius:12px;padding:14px 20px;margin-bottom:20px;
    box-shadow:0 3px 16px rgba(239,108,82,.12);
}
.alert-title { font-weight:800;font-size:14px;color:#1A1A2E; }
.alert-desc  { font-size:13px;color:#374151;margin-top:2px;font-weight:500; }

/* ═══════════════════════════════════════════════════
   LOAD BAR
═══════════════════════════════════════════════════ */
.load-bar-wrap { display:flex;align-items:center;gap:10px; }
.load-bar { flex:1;height:7px;background:rgba(0,0,0,.07);border-radius:4px;overflow:hidden; }
.load-bar-fill { height:100%;border-radius:4px;transition:width .5s; }
.load-low    .load-bar-fill { background:linear-gradient(90deg,#EF6C52,#FF9A7B); }
.load-medium .load-bar-fill { background:linear-gradient(90deg,#f59e0b,#fbbf24); }
.load-high   .load-bar-fill { background:linear-gradient(90deg,#ef4444,#f87171); }

/* Table toolbar */
.table-toolbar { display:flex;align-items:center;gap:10px;flex-wrap:wrap; }

/* ═══════════════════════════════════════════════════
   RESPONSIVE — NO MOBILE BOTTOM NAV (web-only)
═══════════════════════════════════════════════════ */

/* Large desktop */
@media (max-width: 1280px) {
    .stats-grid-4 { grid-template-columns: repeat(2, 1fr); }
    .device-stats-grid { grid-template-columns: repeat(2, 1fr); }
}

/* Medium — tablet landscape */
@media (max-width: 1024px) {
    .page-content { padding: 20px; }
    .topbar { padding: 0 20px; }
}

/* Tablet portrait — sidebar collapses */
@media (max-width: 900px) {
    .stats-grid-3 { grid-template-columns: repeat(2, 1fr); }

    .sidebar {
        position: fixed; top: 0; left: 0;
        width: 280px; height: 100%;
        transform: translateX(-100%); z-index: 9998;
    }
    .sidebar.open { transform: translateX(0); box-shadow: 8px 0 40px rgba(0,0,0,.4); }
    .sidebar-close { display: flex !important; }
}

/* Small tablet */
@media (max-width: 768px) {
    .page-content { padding: 16px; }
    .topbar { padding: 0 16px; }
    .stats-grid-4 { grid-template-columns: repeat(2, 1fr); }
    .device-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .section-header { flex-direction: column; align-items: flex-start; gap: 12px; }
    .table-toolbar { width: 100%; justify-content: flex-start; }
    .modal-box { padding: 20px 18px; }
    .form-grid { grid-template-columns: 1fr; }
    .history-controls { flex-direction: column; align-items: flex-start; }
    .history-patient-select { max-width: 100%; width: 100%; }
}

/* Mobile */
@media (max-width: 600px) {
    .stats-grid-4, .stats-grid-3 { grid-template-columns: repeat(2, 1fr); }
    .device-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .stat-value { font-size: 28px; }
    .tabs { padding: 10px 12px 0; }
    .tab-btn { padding: 8px 12px 10px; font-size: 12px; }
    .modal { margin: 0 8px; }
}

/* Extra small */
@media (max-width: 380px) {
    .stats-grid-4, .stats-grid-3, .stats-grid-2 { grid-template-columns: 1fr; }
    .device-stats-grid { grid-template-columns: 1fr 1fr; }
}
</style>

<!-- ── SIDEBAR ─────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="manager_dashboard.php" class="sidebar-logo">
            <div class="logo-icon">
                <img src="/VitalWearV2/assets/css/image.png" alt="VitalWear" width="36" height="36" style="object-fit:cover;">
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
                <i class="fa-solid fa-briefcase" style="font-size:9px"></i>Manager
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Analytics</div>

        <a href="manager_dashboard.php"
           class="nav-item <?= ($currentPage === 'manager_dashboard.php' && (!isset($_GET['tab']) || $_GET['tab'] === 'overview')) ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Overview</span>
        </a>

        <a href="manager_dashboard.php?tab=trends"
           class="nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'trends') ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Heart Rate Trends</span>
        </a>

        <a href="manager_dashboard.php?tab=patients"
           class="nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'patients') ? 'active' : '' ?>">
            <i class="fa-solid fa-user-injured"></i>
            <span>Patients</span>
        </a>

        <a href="manager_dashboard.php?tab=rescuers"
           class="nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'rescuers') ? 'active' : '' ?>">
            <i class="fa-solid fa-people-group"></i>
            <span>Rescuer Performance</span>
        </a>

        <a href="manager_dashboard.php?tab=devices"
           class="nav-item <?= (isset($_GET['tab']) && $_GET['tab'] === 'devices') ? 'active' : '' ?>">
            <i class="fa-solid fa-microchip"></i>
            <span>Devices</span>
        </a>

        <div class="sidebar-divider"></div>
        <div class="nav-label">Account</div>

        <a href="../api/login.php?action=logout" class="nav-item logout-item">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>

<!-- NO MOBILE BOTTOM NAV — this is a web-based app -->