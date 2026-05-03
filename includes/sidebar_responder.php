<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">

<style>

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
    --card-border  : rgba(239,108,82,.30);
    --card-shadow  : 0 4px 20px rgba(239,108,82,.18), 0 1px 6px rgba(30,36,80,.10);
    --card-shadow-hover: 0 12px 40px rgba(239,108,82,.28), 0 4px 12px rgba(30,36,80,.14);
    --radius       : 14px;
    --radius-sm    : 10px;
    --transition   : all 0.2s ease;
    --indigo       : #6366f1;
    --indigo-border: rgba(99,102,241,.25);
    --red          : #ef4444;
    --red-bg       : rgba(239,68,68,.08);
    --red-border   : rgba(239,68,68,.2);
    --green        : var(--c-coral);
    --green-bg     : var(--c-coral-muted);
    --green-border : var(--c-coral-border);
    --border-light : rgba(239,108,82,.20);
    --bg-input     : #F9FAFB;
}

/* ── Global resets ── */
* { box-sizing: border-box; }
body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg-page);
    color: var(--text-primary);
    margin: 0;
    -webkit-font-smoothing: antialiased;
}


.layout {
    display: flex !important;
    min-height: 100vh;
}
.main-content {
    flex: 1 !important;
    min-width: 0;
    overflow-x: clip;
    margin-left: 0 !important;
    padding-left: 0 !important;
    width: auto !important;
    background: var(--bg-page);
}

/* ── Sidebar overlay: darken only, NO blur ── */
.sidebar-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.50);
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    z-index: 9997;
}
.sidebar-overlay.open { display: block; }

/* ═══════════════════════════════════════════════════
   SIDEBAR  — sticky on desktop, slide-in on mobile
   Key fix: position:sticky on desktop, NOT fixed,
   so it never blurs/covers the main content.
═══════════════════════════════════════════════════ */
.sidebar {
    width: 252px;
    background: var(--sb-bg);
    display: flex; flex-direction: column;
    height: 100vh;
    position: sticky;       /* desktop: part of normal flow */
    top: 0;
    overflow-y: auto;
    z-index: 200;
    flex-shrink: 0;
    transition: transform 0.3s cubic-bezier(.4,0,.2,1);
    border-right: 1px solid var(--sb-border);
    box-shadow: 4px 0 32px rgba(0,0,0,.32);
    scrollbar-width: none;
    /* Explicitly NO blur on the sidebar itself */
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}
.sidebar::-webkit-scrollbar { display: none; }

.sidebar::before {
    content: ''; position: absolute; inset: 0; pointer-events: none; z-index: 0;
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
.sidebar-logo { display: flex; align-items: center; gap: 11px; text-decoration: none; }
.logo-icon {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep));
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 20px var(--c-coral-glow); overflow: hidden;
}
.logo-text { font-size: 17px; font-weight: 800; color: var(--sb-text); letter-spacing: -.4px; }

.sidebar-close {
    display: none;
    background: var(--sb-surface); border: none; color: #94a3b8; cursor: pointer;
    padding: 6px 8px; border-radius: 6px; font-size: 16px; transition: all .2s;
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
    font-weight: 800; font-size: 15px; flex-shrink: 0;
}
.responder-avatar {
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep));
    color: #fff;
    box-shadow: 0 0 14px var(--c-coral-glow), 0 2px 6px rgba(0,0,0,.3);
}
.user-info .user-name {
    font-size: 13px; font-weight: 700; color: var(--sb-text); line-height: 1.3;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.user-role-badge {
    font-size: 10px; padding: 2px 8px; border-radius: 20px;
    font-weight: 700; margin-top: 3px; display: inline-flex; align-items: center; gap: 4px;
    letter-spacing: .2px;
}
.responder-badge {
    background: var(--c-coral-muted); color: var(--c-coral-soft);
    border: 1px solid var(--c-coral-border);
}

/* Nav */
.sidebar-nav { padding: 14px 10px; flex: 1; display: flex; flex-direction: column; gap: 2px; }
.nav-label {
    font-size: 10px; font-weight: 700; color: var(--sb-label);
    letter-spacing: 1.2px; text-transform: uppercase; padding: 12px 8px 5px;
}
.nav-item {
    display: flex; align-items: center; gap: 11px;
    padding: 10px 13px; border-radius: 9px;
    color: var(--sb-muted); text-decoration: none;
    font-size: 13px; font-weight: 600;
    transition: all 0.18s; position: relative;
    cursor: pointer; background: none; border: none;
    width: 100%; text-align: left;
    font-family: 'DM Sans', sans-serif; white-space: nowrap;
}
.nav-item i { width: 18px; text-align: center; font-size: 14px; flex-shrink: 0; transition: color .18s; }
.nav-item:hover { background: rgba(255,255,255,.06); color: var(--sb-text); }
.nav-item:hover i { color: var(--c-coral-soft); }
.nav-item.active {
    background: var(--c-coral-muted); color: var(--c-coral-soft);
    border: 1px solid var(--c-coral-border);
    box-shadow: 0 2px 12px rgba(239,108,82,.12);
}
.nav-item.active i { color: var(--c-coral); }
.nav-badge {
    margin-left: auto; background: #746c6c; color: #fff;
    font-size: 10px; font-weight: 700; padding: 2px 7px;
    border-radius: 10px; min-width: 20px; text-align: center;
}
.nav-badge.hidden { display: none; }
.nav-badge-coral { background: var(--c-coral) !important; }
.logout-item { color: var(--sb-muted); }
.logout-item:hover { background: rgba(239,68,68,.08) !important; color: #f87171 !important; }
.logout-item:hover i { color: #f87171 !important; }
.sidebar-divider { height: 1px; background: var(--sb-border); margin: 6px 14px; }

/* Sim device panel — keep dark (it's inside the dark sidebar) */
.sim-device-panel {
    margin: 10px 10px 14px; border-radius: 12px;
    background: rgba(239,108,82,.06); border: 1px solid var(--c-coral-border);
    padding: 14px; display: flex; flex-direction: column; gap: 10px;
}
.sim-device-panel .sim-title {
    font-size: 10px; font-weight: 700; color: var(--sb-muted);
    text-transform: uppercase; letter-spacing: 1px;
    display: flex; align-items: center; gap: 6px;
}
.sim-device-panel .sim-title i { color: var(--c-coral); }
.sim-patient-select {
    width: 100%; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.10); color: var(--sb-text);
    border-radius: 8px; padding: 8px 28px 8px 10px;
    font-size: 12px; font-weight: 600; cursor: pointer;
    font-family: 'DM Sans', sans-serif; transition: border-color .2s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23EF6C52' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center;
}
.sim-patient-select:focus { outline: none; border-color: var(--c-coral); }
.sim-patient-select option { background: #2A1A16; color: #F5EDE9; }
.sim-patient-label { font-size: 10px; font-weight: 700; color: var(--sb-muted); margin-bottom: -4px; letter-spacing: .4px; }
.sim-status-row { display: flex; align-items: center; gap: 8px; }
.sim-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,.12); transition: all .3s; flex-shrink: 0; }
.sim-dot.running { background: var(--c-coral); box-shadow: 0 0 8px var(--c-coral-glow); animation: simPulse 1.2s ease-in-out infinite; }
@keyframes simPulse { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.4);opacity:.7} }
.sim-status-text { font-size: 12px; color: var(--sb-muted); font-weight: 600; }
.sim-status-text.running { color: var(--c-coral); }
.btn-sim { width: 100%; padding: 9px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; border: none; display: flex; align-items: center; justify-content: center; gap: 7px; transition: all .2s; font-family: 'DM Sans', sans-serif; }
.btn-sim-start { background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep)); color: #fff; box-shadow: 0 2px 14px var(--c-coral-glow); }
.btn-sim-start:hover { transform: translateY(-1px); }
.btn-sim-stop { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; }
.btn-sim-stop:hover { transform: translateY(-1px); }

/* ═══════════════════════════════════════════════════
   TOPBAR — warm beige, matches admin
═══════════════════════════════════════════════════ */
.topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 28px; height: 60px;
    background: #eadddd;
    border-bottom: 2px solid rgba(70,65,65,.2);
    box-shadow: 0 2px 20px rgba(70,65,65,.2);
    position: sticky; top: 0; z-index: 100;
}
.topbar-left  { display: flex; align-items: center; gap: 14px; }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.menu-toggle {
    background: none; border: none; cursor: pointer;
    color: var(--text-muted); padding: 6px; border-radius: 8px;
    display: flex; align-items: center; transition: all .2s;
}
.menu-toggle:hover { background: var(--c-coral-muted); color: var(--c-coral); }
.page-title { font-size: 16px; font-weight: 700; color: var(--text-primary); }
.live-indicator {
    display: flex; align-items: center; gap: 6px;
    font-size: 11px; font-weight: 800; color: var(--c-coral); letter-spacing: .5px;
}
.live-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--c-coral); box-shadow: 0 0 8px var(--c-coral-glow);
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
   STAT CARDS — matches admin
═══════════════════════════════════════════════════ */
.stats-grid   { display: grid; gap: 18px; margin-bottom: 24px; }
.stats-grid-4 { grid-template-columns: repeat(4,1fr); }
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
.stat-card-header { margin-bottom: 12px; }
.stat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: 18px;
}
.stat-icon.blue   { background: rgba(59,130,246,.12); color: #3b82f6; }
.stat-icon.green  { background: var(--c-coral-muted); color: var(--c-coral); }
.stat-icon.yellow { background: rgba(245,158,11,.12); color: #f59e0b; }
.stat-icon.red    { background: rgba(239,68,68,.12);  color: #ef4444; }
.stat-label  { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .8px; }
.stat-value  { font-size: 32px; font-weight: 800; color: var(--text-primary); line-height: 1.1; margin: 4px 0; }
.stat-sub    { font-size: 12px; color: var(--text-label); font-weight: 500; }
.text-green  { color: var(--c-coral) !important; }
.text-yellow { color: #f59e0b !important; }
.text-red    { color: #ef4444 !important; }
.text-blue   { color: #3b82f6 !important; }

/* Section cards */
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
.section-subtitle { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

/* Charts */
.chart-card {
    background: var(--bg-card);
    border-radius: 14px;
    border: 1.5px solid var(--card-border);
    box-shadow: var(--card-shadow);
    padding: 20px;
}
.chart-card-title {
    font-size: 14px; font-weight: 700; color: var(--text-primary);
    margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
}

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
    color: #fff !important; font-weight: 700 !important;
    box-shadow: 0 2px 14px var(--c-coral-glow) !important;
    border-color: transparent !important;
}
.btn-primary:hover { filter: brightness(1.07); transform: translateY(-1px); }
.btn-ghost {
    background: #F5F5F7; color: var(--text-secondary) !important;
    border-color: #E5E7EB;
}
.btn-ghost:hover { background: var(--c-coral-muted); color: var(--c-coral) !important; border-color: var(--c-coral-border); }
.btn-danger { background: rgba(239,68,68,.1); color: #ef4444; border-color: rgba(239,68,68,.2); }
.btn-danger:hover { background: rgba(239,68,68,.18); }

/* ═══════════════════════════════════════════════════
   MODALS — LIGHT, matches admin exactly
═══════════════════════════════════════════════════ */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.55); backdrop-filter: blur(5px);
    z-index: 1000; align-items: center; justify-content: center;
}
.modal-overlay.open, .modal-overlay.active { display: flex; }
.modal {
    background: #fff;                          /* ← light, not dark */
    border-radius: 16px;
    width: 100%; max-width: 480px;
    box-shadow: 0 24px 64px rgba(0,0,0,.22),
                0 6px 24px rgba(239,108,82,.30),
                0 0 0 1px rgba(239,108,82,.10);
    border: 2px solid rgba(239,108,82,.55);
    max-height: 90vh; display: flex; flex-direction: column;
}
.modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 22px 26px 18px;
    border-bottom: 1.5px solid rgba(239,108,82,.30);
    flex-shrink: 0;
}
.modal-title { font-size: 16px; font-weight: 800; color: var(--text-primary); margin: 0; }
.modal-close {
    background: none; border: none; font-size: 22px;
    color: #9CA3AF; cursor: pointer; padding: 0 4px;
    line-height: 1; transition: color .2s;
}
.modal-close:hover { color: #ef4444; }
.modal-body { padding: 22px 26px; overflow-y: auto; flex: 1; }
.modal-footer {
    padding: 16px 26px;
    border-top: 1.5px solid rgba(239,108,82,.30);
    display: flex; gap: 10px; justify-content: flex-end; flex-shrink: 0;
    background: rgba(239,108,82,.03);
}

/* ── Form elements — light, strong borders ── */
.form-group  { margin-bottom: 16px; }
.form-label  {
    display: block; font-size: 11px; font-weight: 700;
    color: var(--text-muted); margin-bottom: 6px;
    text-transform: uppercase; letter-spacing: .6px;
}
.form-input, .form-select, .form-textarea {
    width: 100%; padding: 10px 14px;
    background: #F9FAFB;
    border: 1.5px solid #C4C9D4;          /* ← clearly visible */
    border-radius: 9px; color: var(--text-primary); font-size: 13px;
    font-family: 'DM Sans', sans-serif; transition: all .2s;
    box-shadow: inset 0 1px 3px rgba(0,0,0,.05);
    box-sizing: border-box;
}
.form-textarea { min-height: 90px; resize: vertical; }
.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none; border-color: var(--c-coral);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(239,108,82,.14);
}
.form-input::placeholder, .form-textarea::placeholder { color: #B0B7C3; }
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

/* Alert panel inside modal — light version */
.alert-panel {
    background: rgba(239,108,82,.06);
    border: 1.5px solid rgba(239,108,82,.25);
    border-radius: 10px; padding: 14px 18px; margin-bottom: 16px;
}
.alert-panel-title {
    font-size: 11px; font-weight: 700; color: var(--c-coral);
    margin-bottom: 8px; text-transform: uppercase;
    letter-spacing: .5px; display: flex; align-items: center; gap: 6px;
}

.form-error-box {
    background: rgba(239,68,68,.08);
    border: 1.5px solid rgba(239,68,68,.25);
    color: #ef4444; border-radius: 8px;
    padding: 10px 14px; font-size: 13px; margin-top: 10px;
}

/* Quick msg buttons */
.quick-msg-row { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
.quick-msg-row .btn { font-size: 11px; padding: 5px 10px; }

/* Table */
.table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
table { width: 100%; border-collapse: collapse; }
table thead th {
    background: linear-gradient(135deg, #1E2450, #2D3478);
    color: #fff !important; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px;
    padding: 13px 18px; text-align: left;
}
table tbody tr { border-bottom: 1px solid rgba(239,108,82,.09); transition: background .15s; }
table tbody tr:hover { background: rgba(239,108,82,.05) !important; }
table tbody td { padding: 14px 18px; font-size: 13px; color: var(--text-primary); }
.td-muted { color: var(--text-muted) !important; }
.table-toolbar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

/* Badges */
.badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700; white-space: nowrap;
    border: 1px solid transparent;
}
.badge-normal   { background: var(--c-coral-muted); color: var(--c-coral); border-color: var(--c-coral-border); }
.badge-warning  { background: rgba(245,158,11,.12);  color: #d97706; border-color: rgba(245,158,11,.25); }
.badge-critical { background: rgba(239,68,68,.12);   color: #ef4444; border-color: rgba(239,68,68,.25); }

/* BPM */
.bpm-value   { font-weight: 800; font-size: 17px; font-variant-numeric: tabular-nums; }
.bpm-unit    { font-size: 12px; color: var(--text-muted); }
.bpm-normal  { color: var(--c-coral) !important; }
.bpm-warning { color: #f59e0b !important; }
.bpm-critical{ color: #ef4444 !important; }
.bpm-cell { display: flex; flex-direction: column; gap: 4px; }
.bpm-bar { height: 5px; background: rgba(0,0,0,.07); border-radius: 3px; overflow: hidden; }
.bpm-bar-fill { height: 100%; border-radius: 3px; transition: width .5s ease; }
.fill-normal   { background: linear-gradient(90deg,var(--c-coral),var(--c-coral-soft)); }
.fill-warning  { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
.fill-critical { background: linear-gradient(90deg,#ef4444,#f87171); }

/* Alert banner */
.alert-banner {
    background: rgba(239,68,68,.06);
    border: 1.5px solid rgba(239,68,68,.25);
    border-left: 4px solid #ef4444;
    border-radius: 12px; padding: 14px 18px;
    margin-bottom: 20px;
    display: flex; align-items: flex-start; gap: 12px;
}
.alert-icon-wrap { color: #ef4444; flex-shrink: 0; margin-top: 1px; }
.alert-title { font-size: 14px; font-weight: 800; color: #1E2450; }
.alert-desc  { font-size: 13px; color: #374151; margin-top: 2px; }

/* Charts grid */
.charts-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 18px;
    margin-bottom: 20px;
}
.chart-wrapper     { height: 220px; position: relative; }
.chart-wrapper-tall{ height: 240px; position: relative; }
.donut-center-label {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    text-align: center; pointer-events: none;
}
.donut-center-label .num { display: block; font-size: 28px; font-weight: 800; color: var(--text-primary); }
.donut-center-label .lbl { display: block; font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }

/* Filter select */
.filter-select {
    background: #F9FAFB;
    border: 1.5px solid #C4C9D4;
    color: var(--text-primary);
    border-radius: 8px; padding: 7px 12px;
    font-size: 13px; cursor: pointer;
    font-family: 'DM Sans', sans-serif; transition: all .2s;
}
.filter-select:focus { outline: none; border-color: var(--c-coral); box-shadow: 0 0 0 3px rgba(239,108,82,.12); }

/* Patient cards (mobile) */
.patient-cards { display: none; flex-direction: column; gap: 14px; }
.patient-card {
    background: var(--bg-card);
    border: 1.5px solid var(--card-border);
    border-radius: 14px; padding: 16px 18px;
    box-shadow: var(--card-shadow);
    position: relative; overflow: hidden;
}
.patient-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 14px 14px 0 0;
}
.patient-card.card-normal::before  { background: var(--c-coral); }
.patient-card.card-warning::before { background: #f59e0b; }
.patient-card.card-critical::before{ background: #ef4444; }
.patient-card.card-critical { animation: critCardPulse 2s ease-in-out infinite; }
@keyframes critCardPulse { 0%,100%{box-shadow:0 4px 20px rgba(239,68,68,.12)} 50%{box-shadow:0 4px 30px rgba(239,68,68,.28)} }
.patient-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.patient-card-name   { font-size: 16px; font-weight: 700; color: var(--text-primary); }
.patient-card-body   { display: flex; flex-direction: column; gap: 8px; }
.patient-card-stat   { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: var(--text-muted); }
.patient-card-stat span { color: var(--text-primary); font-weight: 600; }

/* Desktop/mobile toggles */
.desktop-only { display: block; }


/* Toast */
.toast-container { position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px; }

/* ── Anti-blur nuclear option: kill any filter/backdrop-filter
   that styles.css might apply to main content when sidebar opens ── */
.main-content,
.page-content,
.layout,
body > .layout > .main-content {
    filter: none !important;
    -webkit-filter: none !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

/* ═══════════════════════════════════════════════════
   MOBILE BOTTOM NAVBAR
═══════════════════════════════════════════════════ */
.mobile-bottom-nav {
    display: none;
    position: fixed; bottom: 0; left: 0; right: 0; height: 64px;
    background: var(--sb-bg); border-top: 1px solid var(--sb-border);
    z-index: 10000; padding-bottom: env(safe-area-inset-bottom, 0);
    box-shadow: 0 -4px 24px rgba(0,0,0,.5); isolation: isolate;
}
.mobile-bottom-nav-inner {
    display: flex; height: 100%; align-items: stretch;
    position: relative; z-index: 1; pointer-events: auto;
}
.mob-nav-item {
    flex: 1; display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 3px; text-decoration: none;
    color: #4A3530; font-size: 10px; font-weight: 700;
    letter-spacing: .2px; padding: 6px 2px;
    transition: color .2s; position: relative;
    pointer-events: auto; cursor: pointer;
    background: none; border: none; font-family: 'DM Sans', sans-serif;
    -webkit-tap-highlight-color: rgba(239,108,82,.15);
}
.mob-nav-item i { font-size: 18px; transition: all .2s; pointer-events: none; }
.mob-nav-item span { pointer-events: none; }
.mob-nav-item.active { color: var(--c-coral); }
.mob-nav-item.active i { color: var(--c-coral); }
.mob-nav-item.active::before {
    content: ''; position: absolute; top: 0; left: 20%; right: 20%; height: 2px;
    background: var(--c-coral); border-radius: 0 0 3px 3px;
}
.mob-nav-item.sim-btn-mob { color: var(--c-coral); }
.mob-nav-item.sim-btn-mob.running { color: #ef4444; }
.mob-nav-badge {
    position: absolute; top: 5px; right: calc(50% - 20px);
    background: #ef4444; color: #fff; font-size: 9px; font-weight: 700;
    min-width: 16px; height: 16px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    padding: 0 3px; border: 1px solid var(--sb-bg); pointer-events: none;
}
.mob-nav-badge.hidden { display: none; }
.mob-nav-badge.coral-badge { background: var(--c-coral); }

/* Rescuer alert panel */
.rescuer-alert-panel {
    position: fixed; top: 70px; right: 16px; width: 340px;
    max-height: 480px; overflow-y: auto;
    background: #fff;                        /* ← light */
    border: 1.5px solid var(--card-border);
    border-radius: 16px; z-index: 10002;
    box-shadow: var(--card-shadow-hover); display: none;
}
.rescuer-alert-panel.open { display: block; }
.rescuer-alert-panel-header {
    padding: 14px 16px 10px;
    display: flex; justify-content: space-between; align-items: center;
    border-bottom: 1px solid rgba(239,108,82,.12);
    position: sticky; top: 0; background: #fff; z-index: 1;
}
.rescuer-alert-panel-title {
    font-size: 13px; font-weight: 700; color: var(--c-coral);
    display: flex; align-items: center; gap: 6px;
    font-family: 'DM Sans', sans-serif;
}
.rescuer-alert-list { padding: 10px; display: flex; flex-direction: column; gap: 8px; }
.rescuer-alert-item {
    background: #F9FAFB; border: 1px solid rgba(239,108,82,.12);
    border-radius: 10px; padding: 12px;
}
.rescuer-alert-item.has-location { border-left: 3px solid var(--c-coral); }
.rescuer-alert-item-name { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.rescuer-alert-item-time { font-size: 11px; color: var(--text-label); }
.rescuer-alert-item-from { font-size: 11px; color: var(--c-coral); margin-bottom: 5px; font-weight: 600; }
.rescuer-alert-item-msg  { font-size: 12px; color: var(--text-muted); line-height: 1.5; }
.location-link {
    display: inline-flex; align-items: center; gap: 4px; margin-top: 8px;
    padding: 5px 10px; background: var(--c-coral-muted);
    border: 1px solid var(--c-coral-border); border-radius: 6px;
    color: var(--c-coral); font-size: 11px; font-weight: 700; text-decoration: none;
    transition: all .2s;
}
.location-link:hover { background: rgba(239,108,82,.2); }

/* ═══════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════ */
@media (max-width: 900px) {
    /* Sidebar becomes a slide-in drawer on mobile */
    .sidebar {
        position: fixed !important;   /* override sticky */
        top: 0; left: 0;
        width: 280px; height: 100%;
        transform: translateX(-100%);
        z-index: 9998;
        /* Remove shadow when closed, no blur ever */
        box-shadow: none;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
    .sidebar.open {
        transform: translateX(0);
        box-shadow: 8px 0 40px rgba(0,0,0,.4);
    }
    .sidebar-close { display: flex !important; }
    .mobile-bottom-nav { display: flex !important; }
    .page-content { padding: 16px; padding-bottom: 80px !important; }
    .topbar { padding: 0 16px; }
    .modal { max-width: calc(100vw - 24px); margin: 0 12px; }
    .charts-grid { grid-template-columns: 1fr; }
    .desktop-only { display: none !important; }
    .patient-cards { display: flex !important; }
    .stats-grid-4 { grid-template-columns: repeat(2,1fr); }
    /* Ensure canvas never covers the bottom nav */
    canvas { position: relative !important; z-index: 0 !important; }
    .chart-card, .stat-card, .section-card, .layout, .main-content {
        position: relative; z-index: 0; isolation: isolate;
    }
    .quick-msg-row .btn { flex: 1 1 auto; text-align: center; justify-content: center; }
    .form-grid-2 { grid-template-columns: 1fr; }
}
@media (min-width: 901px) {
    .mobile-bottom-nav { display: none !important; }
    .menu-toggle { display: none !important; }
    .patient-cards { display: none !important; }
    .desktop-only  { display: block !important; }
}
@media (max-width: 1100px) {
    .stats-grid-4 { grid-template-columns: repeat(2,1fr); }
}
</style>

<!-- ── DESKTOP SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="responder_dashboard.php" class="sidebar-logo">
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
        <div class="user-avatar responder-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="user-role-badge responder-badge">
                <i class="fa-solid fa-shield-halved" style="font-size:9px"></i> Responder
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Monitoring</div>
        <a href="responder_dashboard.php" class="nav-item <?= ($currentPage==='responder_dashboard.php'&&!isset($_GET['view']))?'active':'' ?>">
            <i class="fa-solid fa-heart-pulse"></i><span>Live Monitor</span>
        </a>
        <a href="responder_dashboard.php?view=critical" class="nav-item <?= (isset($_GET['view'])&&$_GET['view']==='critical')?'active':'' ?>">
            <i class="fa-solid fa-triangle-exclamation"></i><span>Critical Alerts</span>
            <span class="nav-badge hidden" id="criticalNavBadge">0</span>
        </a>
        <a href="responder_dashboard.php?view=charts" class="nav-item <?= (isset($_GET['view'])&&$_GET['view']==='charts')?'active':'' ?>">
            <i class="fa-solid fa-chart-line"></i><span>Analytics</span>
        </a>
        <div class="sidebar-divider"></div>
        <div class="nav-label">Communication</div>
        <button onclick="toggleRescuerAlertPanel()" class="nav-item" style="font-family:'DM Sans',sans-serif">
            <i class="fa-solid fa-location-dot" style="color:var(--c-coral)"></i><span>Rescuer Alerts</span>
            <span class="nav-badge nav-badge-coral hidden" id="rescuerAlertBadge">0</span>
        </button>
        <div class="sidebar-divider"></div>
        <div class="nav-label">Account</div>
        <a href="../api/login.php?action=logout" class="nav-item logout-item">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
        </a>
    </nav>

    <div class="sim-device-panel">
        <div class="sim-title"><i class="fa-solid fa-microchip"></i> Wearable Simulator</div>
        <div>
            <div class="sim-patient-label">Simulate Patient</div>
            <select class="sim-patient-select" id="sidebarPatientSelect" onchange="syncPatientSelect('sidebar')">
                <option value="">— Pick a patient —</option>
            </select>
        </div>
        <div class="sim-status-row">
            <div class="sim-dot" id="sidebarSimDot"></div>
            <span class="sim-status-text" id="sidebarSimText">Device Offline</span>
        </div>
        <button class="btn-sim btn-sim-start" id="sidebarSimBtn" onclick="openSimModal()">
            <i class="fa-solid fa-play"></i> Start Simulation
        </button>
    </div>
</aside>

<!-- ── MOBILE BOTTOM NAVBAR ── -->
<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <div class="mobile-bottom-nav-inner">
        <a href="responder_dashboard.php"
           class="mob-nav-item <?= ($currentPage==='responder_dashboard.php'&&!isset($_GET['view']))?'active':'' ?>">
            <i class="fa-solid fa-heart-pulse"></i><span>Monitor</span>
        </a>
        <a href="responder_dashboard.php?view=critical"
           class="mob-nav-item <?= (isset($_GET['view'])&&$_GET['view']==='critical')?'active':'' ?>">
            <i class="fa-solid fa-triangle-exclamation"></i><span>Alerts</span>
            <span class="mob-nav-badge hidden" id="mobCriticalBadge">0</span>
        </a>
        <button class="mob-nav-item sim-btn-mob" id="mobSimBtn" onclick="openSimModal()">
            <i class="fa-solid fa-microchip" id="mobSimIcon"></i>
            <span id="mobSimLabel">Simulate</span>
        </button>
        <button class="mob-nav-item" onclick="toggleRescuerAlertPanel()">
            <i class="fa-solid fa-location-dot" style="color:var(--c-coral)"></i>
            <span style="color:var(--c-coral)">Location</span>
            <span class="mob-nav-badge coral-badge hidden" id="mobRescuerAlertBadge">0</span>
        </button>
        <a href="../api/login.php?action=logout" class="mob-nav-item">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
        </a>
    </div>
</nav>

<!-- ── RESCUER ALERT PANEL ── -->
<div class="rescuer-alert-panel" id="rescuerAlertPanel">
    <div class="rescuer-alert-panel-header">
        <div class="rescuer-alert-panel-title">
            <i class="fa-solid fa-location-dot"></i> Rescuer Location Alerts
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <button onclick="markRescuerAlertsRead()" style="background:none;border:none;color:var(--text-muted);font-size:11px;cursor:pointer;font-weight:700;font-family:'DM Sans',sans-serif">Mark read</button>
            <button onclick="toggleRescuerAlertPanel()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:22px;line-height:1">×</button>
        </div>
    </div>
    <div class="rescuer-alert-list" id="rescuerAlertList">
        <div style="text-align:center;padding:30px;color:var(--text-muted);font-size:13px">Loading…</div>
    </div>
</div>


<style>
.sim-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.65); backdrop-filter: blur(10px);
    z-index: 10001; align-items: flex-end; justify-content: center;
}
.sim-modal-overlay.open { display: flex; }
.sim-modal {
    background: #1E1216; border-radius: 24px 24px 0 0;
    width: 100%; max-width: 480px;
    max-height: calc(100svh - 64px - env(safe-area-inset-bottom,0px));
    display: flex; flex-direction: column;
    animation: slideUp .32s cubic-bezier(.34,1.56,.64,1);
    border-top: 1px solid rgba(239,108,82,.25);
    box-shadow: 0 -20px 60px rgba(0,0,0,.6);
}
@keyframes slideUp { from{transform:translateY(100%);opacity:0} to{transform:translateY(0);opacity:1} }
.sim-modal-handle { width:40px;height:4px;background:rgba(255,255,255,.15);border-radius:2px;margin:14px auto 0;flex-shrink:0; }
.sim-modal-header { padding:14px 20px 0;display:flex;align-items:center;justify-content:space-between;flex-shrink:0; }
.sim-modal-title  { font-size:16px;font-weight:800;color:#F1ECE9;display:flex;align-items:center;gap:8px;font-family:'DM Sans',sans-serif; }
.sim-modal-title i { color:var(--c-coral); }
.sim-modal-close  { background:rgba(255,255,255,.07);border:none;color:#94a3b8;width:30px;height:30px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;transition:all .2s; }
.sim-modal-close:hover { background:rgba(239,68,68,.2);color:#f87171; }
.sim-modal-body   { padding:14px 20px 24px;overflow-y:auto;flex:1;-webkit-overflow-scrolling:touch; }
.sim-modal-patient-wrap { margin-bottom:12px; }
.sim-modal-patient-label { font-size:11px;font-weight:700;color:var(--c-coral);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;display:flex;align-items:center;gap:5px; }
.sim-modal-patient-select { width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(239,108,82,.3);color:#F1ECE9;border-radius:10px;padding:10px 36px 10px 14px;font-size:13px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .2s;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23EF6C52' stroke-width='1.8' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center; }
.sim-modal-patient-select:focus { outline:none;border-color:var(--c-coral);box-shadow:0 0 0 3px rgba(239,108,82,.15); }
.sim-modal-patient-select option { background:#241416;color:#F5EDE9; }
.sim-modal-patient-select.locked { opacity:.75; }
.sim-switch-hint { font-size:10px;color:var(--c-coral);font-weight:600;text-align:center;margin-top:5px;display:none; }
.sim-switch-hint.visible { display:block; }
.sim-modal-status { display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;background:rgba(255,255,255,.04);margin-bottom:8px;border:1px solid rgba(255,255,255,.07); }
.sim-modal-status .label { font-size:12px;color:#7A6A65;flex:1;font-weight:500; }
.sim-modal-status .value { font-size:13px;font-weight:700;color:#F1ECE9; }
.sim-modal-actions { display:flex;gap:10px;margin-top:14px; }
.btn-sim-lg { flex:1;padding:14px;border-radius:12px;font-size:14px;font-weight:800;cursor:pointer;border:none;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;font-family:'DM Sans',sans-serif; }
.btn-sim-lg-start { background:linear-gradient(135deg,var(--c-coral),var(--c-coral-deep));color:#fff;box-shadow:0 4px 18px var(--c-coral-glow); }
.btn-sim-lg-start:hover { transform:translateY(-1px); }
.btn-sim-lg-stop { background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;box-shadow:0 4px 16px rgba(239,68,68,.3); }
.btn-sim-lg-stop:hover { transform:translateY(-1px); }
.btn-sim-lg:disabled { opacity:.45;cursor:not-allowed;transform:none !important; }
/* BPM display */
.sim-bpm-display { display:none;flex-direction:column;align-items:center;gap:0;padding:4px 0 0; }
.sim-bpm-display.visible { display:flex; }
.sim-bpm-ring-wrap { position:relative;width:168px;height:168px;margin:0 auto; }
.sim-bpm-ring { width:100%;height:100%;transform:rotate(-90deg); }
.ring-track { fill:none;stroke:rgba(255,255,255,.06);stroke-width:10; }
.ring-fill  { fill:none;stroke:var(--c-coral);stroke-width:10;stroke-linecap:round;stroke-dasharray:352;stroke-dashoffset:352;transition:stroke-dashoffset .7s cubic-bezier(.4,0,.2,1),stroke .4s ease;filter:drop-shadow(0 0 6px var(--c-coral-glow)); }
.ring-fill.status-warning  { stroke:#f59e0b; }
.ring-fill.status-critical { stroke:#ef4444;animation:critRingPulse 1s ease-in-out infinite; }
@keyframes critRingPulse { 0%,100%{filter:drop-shadow(0 0 6px rgba(239,68,68,.4))} 50%{filter:drop-shadow(0 0 14px rgba(239,68,68,.8))} }
.sim-bpm-center { position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px; }
.sim-bpm-heart  { font-size:18px;color:var(--c-coral);filter:drop-shadow(0 0 6px var(--c-coral-glow)); }
.sim-bpm-heart.beating { animation:heartBeat .6s ease-in-out; }
@keyframes heartBeat { 0%{transform:scale(1)} 30%{transform:scale(1.4)} 60%{transform:scale(1.1)} 100%{transform:scale(1)} }
.sim-bpm-heart.status-warning  { color:#f59e0b; }
.sim-bpm-heart.status-critical { color:#ef4444; }
.sim-bpm-num { font-size:42px;font-weight:800;line-height:1;color:#F1ECE9;font-variant-numeric:tabular-nums;letter-spacing:-2px;transition:color .4s ease; }
.sim-bpm-num.status-normal   { color:var(--c-coral); }
.sim-bpm-num.status-warning  { color:#f59e0b; }
.sim-bpm-num.status-critical { color:#ef4444; }
.sim-bpm-unit { font-size:11px;font-weight:700;color:#7A6A65;letter-spacing:1px;text-transform:uppercase; }
.sim-zone-bar-wrap { width:100%;margin:10px 0 6px; }
.sim-zone-bar-label { font-size:10px;color:#7A6A65;font-weight:700;text-align:center;margin-bottom:5px;letter-spacing:.4px;text-transform:uppercase; }
.sim-zone-bar { position:relative;height:8px;border-radius:4px;overflow:visible;background:linear-gradient(to right,#ef4444 0% 16.67%,#10b981 16.67% 49.17%,#f59e0b 49.17% 66.67%,#ef4444 66.67% 100%); }
.sim-zone-needle { position:absolute;top:50%;width:3px;height:16px;background:#fff;border-radius:2px;margin-left:-1.5px;transform:translateY(-50%);box-shadow:0 0 6px rgba(0,0,0,.5);transition:left .6s cubic-bezier(.34,1.56,.64,1); }
.sim-zone-labels { position:relative;height:16px;margin-top:4px;font-size:9px;color:#7A6A65;font-weight:600; }
.sim-zone-labels span { position:absolute;transform:translateX(-50%); }
.sim-wave-wrap { width:100%;border-radius:10px;overflow:hidden;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);margin:6px 0 0; }
#simWaveCanvas { width:100%;height:56px;display:block; }
.sim-stats-row { display:flex;width:100%;gap:6px;margin-top:8px; }
.sim-stat-item { flex:1;text-align:center;padding:8px 4px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:8px; }
.sim-stat-item .s-label { font-size:9px;color:#7A6A65;font-weight:700;text-transform:uppercase;letter-spacing:.5px; }
.sim-stat-item .s-val   { font-size:18px;font-weight:800;color:#F1ECE9;line-height:1.2;font-variant-numeric:tabular-nums; }
.sim-stat-item .s-unit  { font-size:9px;color:#7A6A65; }
.sim-stat-item.s-avg .s-val { color:var(--c-coral); }
.sim-stat-item.s-min .s-val { color:#60a5fa; }
.sim-stat-item.s-max .s-val { color:#ef4444; }
.sim-bpm-status-badge { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-top:2px;transition:all .3s ease; }
.sim-bpm-status-badge.status-normal   { background:rgba(239,108,82,.15);color:var(--c-coral);border:1px solid var(--c-coral-border); }
.sim-bpm-status-badge.status-warning  { background:rgba(245,158,11,.12);color:#f59e0b;border:1px solid rgba(245,158,11,.25); }
.sim-bpm-status-badge.status-critical { background:rgba(239,68,68,.12);color:#ef4444;border:1px solid rgba(239,68,68,.25);animation:badgePulse 1s ease-in-out infinite; }
@keyframes badgePulse { 0%,100%{opacity:1} 50%{opacity:.6} }
@media(min-width:901px){
    .sim-modal-overlay { align-items:center; }
    .sim-modal { border-radius:20px;max-width:440px;max-height:92vh;border:1px solid rgba(239,108,82,.18); }
}
</style>
<div class="sim-modal-overlay" id="simModalOverlay" onclick="handleSimModalBackdrop(event)">
    <div class="sim-modal">
        <div class="sim-modal-handle"></div>
        <div class="sim-modal-header">
            <div class="sim-modal-title"><i class="fa-solid fa-microchip"></i> Vital-Wear</div>
            <button class="sim-modal-close" onclick="closeSimModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="sim-modal-body">
            <div class="sim-modal-patient-wrap">
                <div class="sim-modal-patient-label"><i class="fa-solid fa-user-injured"></i> Select Patient to Simulate</div>
                <select class="sim-modal-patient-select" id="modalPatientSelect" onchange="syncPatientSelect('modal')">
                    <option value="">— Choose one patient —</option>
                </select>
                <div class="sim-switch-hint" id="simSwitchHint">
                    <i class="fa-solid fa-arrows-rotate"></i> Switched — simulation continues on new patient
                </div>
            </div>
            <div class="sim-modal-status" id="simStatusRow">
                <div class="sim-dot" id="modalSimDot" style="flex-shrink:0"></div>
                <div class="label">Simulation Status</div>
                <div class="value" id="modalSimStatus">Offline</div>
            </div>
            <div class="sim-bpm-display" id="simLiveBpm">
                <div class="sim-bpm-ring-wrap">
                    <svg class="sim-bpm-ring" viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
                        <circle class="ring-track" cx="70" cy="70" r="56"/>
                        <circle class="ring-fill" id="simBpmRingFill" cx="70" cy="70" r="56" style="stroke-dasharray:352;stroke-dashoffset:352"/>
                    </svg>
                    <div class="sim-bpm-center">
                        <i class="fa-solid fa-heart sim-bpm-heart" id="simBpmHeart"></i>
                        <div class="sim-bpm-num" id="simLiveBpmNum">--</div>
                        <div class="sim-bpm-unit">BPM</div>
                        <div class="sim-bpm-status-badge status-normal" id="simBpmStatusBadge">Normal</div>
                    </div>
                </div>
                <div class="sim-zone-bar-wrap">
                    <div class="sim-zone-bar-label">Heart Rate Zone</div>
                    <div class="sim-zone-bar">
                        <div class="sim-zone-needle" id="simZoneNeedle" style="left:0%"></div>
                    </div>
                    <div class="sim-zone-labels">
                        <span style="left:0%">40</span>
                        <span style="left:16.67%">60</span>
                        <span style="left:49.17%">100</span>
                        <span style="left:66.67%">120</span>
                        <span style="left:100%">160</span>
                    </div>
                </div>
                <div class="sim-wave-wrap"><canvas id="simWaveCanvas" height="56"></canvas></div>
                <div class="sim-stats-row">
                    <div class="sim-stat-item s-avg"><div class="s-label">Average</div><div class="s-val" id="simAvgBpm">--</div><div class="s-unit">BPM</div></div>
                    <div class="sim-stat-item s-min"><div class="s-label">Minimum</div><div class="s-val" id="simMinBpm">--</div><div class="s-unit">BPM</div></div>
                    <div class="sim-stat-item s-max"><div class="s-label">Maximum</div><div class="s-val" id="simMaxBpm">--</div><div class="s-unit">BPM</div></div>
                </div>
            </div>
            <div class="sim-modal-status" id="simUpdatesRow" style="display:none;margin-top:8px">
                <i class="fa-solid fa-database" style="color:var(--c-coral);font-size:14px"></i>
                <div class="label">DB Updates Sent</div>
                <div class="value" id="simUpdateCount">0</div>
            </div>
            <div class="sim-modal-actions">
                <button class="btn-sim-lg btn-sim-lg-start" id="modalStartBtn" onclick="startSimulation()">
                    <i class="fa-solid fa-play"></i> Start Device
                </button>
                <button class="btn-sim-lg btn-sim-lg-stop" id="modalStopBtn" onclick="stopSimulation()" style="display:none">
                    <i class="fa-solid fa-stop"></i> Stop &amp; Save
                </button>
            </div>
            <p style="font-size:11px;color:#7A6A65;margin-top:10px;text-align:center;line-height:1.5;font-family:'DM Sans',sans-serif">
                <i class="fa-solid fa-circle-info" style="margin-right:4px;color:var(--c-coral)"></i>
                Streams vitals for the <strong style="color:#FF9A7B">selected patient only</strong>.
                Switch patients anytime. "Stop &amp; Save" writes the final state to the database.
            </p>
        </div>
    </div>
</div>

<script>
const SIM_CIRC = 352;
let simPatientId = null, simPatientHR = 75;

function populatePatientSelects() {
    const rows = Array.from(document.querySelectorAll('#patientTableBody tr[id^="row-"]'));
    const opts = rows.map(r => ({ id: r.id.replace('row-',''), name: r.cells[0]?.textContent?.trim() || `Patient ${r.id.replace('row-','')}` }));
    ['sidebarPatientSelect','modalPatientSelect'].forEach(selId => {
        const sel = document.getElementById(selId); if (!sel) return;
        const prev = sel.value;
        sel.innerHTML = '<option value="">— Choose one patient —</option>';
        opts.forEach(o => { const opt = document.createElement('option'); opt.value = o.id; opt.textContent = o.name; if (String(o.id)===String(simPatientId||prev)) opt.selected=true; sel.appendChild(opt); });
    });
}

function syncPatientSelect(source) {
    const srcId = source==='sidebar'?'sidebarPatientSelect':'modalPatientSelect';
    const dstId = source==='sidebar'?'modalPatientSelect':'sidebarPatientSelect';
    const val   = document.getElementById(srcId)?.value;
    const newId = val ? parseInt(val) : null;
    const dst   = document.getElementById(dstId); if (dst) dst.value = val||'';
    if (simRunning && newId && newId !== simPatientId) {
        simPatientId = newId; simBpmHistory = [];
        const hint = document.getElementById('simSwitchHint');
        if (hint) { hint.classList.add('visible'); setTimeout(()=>hint.classList.remove('visible'),3000); }
    } else { simPatientId = newId; }
}

function openSimModal()  { populatePatientSelects(); document.getElementById('simModalOverlay').classList.add('open'); document.body.style.overflow='hidden'; if(simRunning)initWaveform(); }
function closeSimModal() { document.getElementById('simModalOverlay').classList.remove('open'); document.body.style.overflow=''; stopWaveform(); }
function handleSimModalBackdrop(e) { if(e.target===document.getElementById('simModalOverlay'))closeSimModal(); }

function getStatus(hr) { if(hr>=60&&hr<=99)return'normal'; if(hr>=100&&hr<=120)return'warning'; return'critical'; }
function bpmToRingOffset(hr) { return SIM_CIRC-(Math.min(1,Math.max(0,(hr-40)/120))*SIM_CIRC); }
function bpmToZoneNeedle(hr) { return Math.min(100,Math.max(0,((hr-40)/120)*100)); }

let simHeartTimeout=null;
function updateLiveBpmUI(hr) {
    const st=getStatus(hr);
    const numEl=document.getElementById('simLiveBpmNum'); if(numEl){numEl.textContent=hr;numEl.className=`sim-bpm-num status-${st}`;}
    const ring=document.getElementById('simBpmRingFill'); if(ring){ring.style.strokeDasharray=SIM_CIRC;ring.style.strokeDashoffset=bpmToRingOffset(hr);ring.className=`ring-fill status-${st}`;}
    const heart=document.getElementById('simBpmHeart'); if(heart){heart.className=`fa-solid fa-heart sim-bpm-heart status-${st} beating`;clearTimeout(simHeartTimeout);simHeartTimeout=setTimeout(()=>heart&&heart.classList.remove('beating'),700);}
    const needle=document.getElementById('simZoneNeedle'); if(needle)needle.style.left=bpmToZoneNeedle(hr)+'%';
    const badge=document.getElementById('simBpmStatusBadge'); if(badge){badge.textContent={normal:'✓ Normal',warning:'⚠ Warning',critical:'🚨 Critical'}[st]||st;badge.className=`sim-bpm-status-badge status-${st}`;}
    simBpmHistory.push(hr); if(simBpmHistory.length>60)simBpmHistory.shift();
    const min=Math.min(...simBpmHistory),max=Math.max(...simBpmHistory),avg=Math.round(simBpmHistory.reduce((a,b)=>a+b,0)/simBpmHistory.length);
    const el=id=>document.getElementById(id);
    if(el('simAvgBpm'))el('simAvgBpm').textContent=avg;
    if(el('simMinBpm'))el('simMinBpm').textContent=min;
    if(el('simMaxBpm'))el('simMaxBpm').textContent=max;
    pushWavePoint(hr,st);
}

let wavePoints=[],waveAnimId=null,waveCtx=null,waveRunning=false,waveCurrentHR=75,waveCurrentSt='normal';
const WAVE_W=380,WAVE_H=56;
function ecgShape(t){t=((t%1)+1)%1;if(t<0.05)return 0.5+t*2;if(t<0.10)return 0.6-(t-0.05)*2;if(t<0.35)return 0.5;if(t<0.38)return 0.5-(t-0.35)*10;if(t<0.42)return 0.2+(t-0.38)*50;if(t<0.46)return 2.2-(t-0.42)*50;if(t<0.48)return 0.2+(t-0.46)*15;if(t<0.65)return 0.5;if(t<0.72)return 0.5+Math.sin((t-0.65)/0.07*Math.PI)*0.15;return 0.5;}
function pushWavePoint(hr,st){waveCurrentHR=hr;waveCurrentSt=st;}
function initWaveform(){
    const canvas=document.getElementById('simWaveCanvas'); if(!canvas||waveRunning)return;
    canvas.width=WAVE_W;canvas.height=WAVE_H;waveCtx=canvas.getContext('2d');
    wavePoints=new Array(WAVE_W).fill(WAVE_H/2);waveRunning=true;
    let phase=0,speed=0.006;
    function frame(){
        if(!waveRunning)return;
        speed=0.004+(waveCurrentHR-40)/120*0.012;phase=(phase+speed)%1;wavePoints.shift();
        const y=ecgShape(phase);const color=waveCurrentSt==='critical'?'#ef4444':waveCurrentSt==='warning'?'#f59e0b':'#EF6C52';
        const mapped=WAVE_H-Math.min(WAVE_H-4,Math.max(4,y*WAVE_H*0.75+WAVE_H*0.12));wavePoints.push(mapped);
        waveCtx.clearRect(0,0,WAVE_W,WAVE_H);
        const grad=waveCtx.createLinearGradient(0,0,WAVE_W,0);grad.addColorStop(0,'transparent');grad.addColorStop(0.7,color+'22');grad.addColorStop(1,color+'cc');
        waveCtx.strokeStyle=grad;waveCtx.lineWidth=2;waveCtx.shadowColor=color;waveCtx.shadowBlur=6;waveCtx.lineJoin='round';waveCtx.lineCap='round';
        waveCtx.beginPath();wavePoints.forEach((p,i)=>i===0?waveCtx.moveTo(i,p):waveCtx.lineTo(i,p));waveCtx.stroke();
        waveCtx.beginPath();waveCtx.arc(WAVE_W-1,wavePoints[WAVE_W-1],3,0,Math.PI*2);waveCtx.fillStyle=color;waveCtx.shadowBlur=10;waveCtx.fill();waveCtx.shadowBlur=0;
        waveAnimId=requestAnimationFrame(frame);
    }
    frame();
}
function stopWaveform(){waveRunning=false;if(waveAnimId){cancelAnimationFrame(waveAnimId);waveAnimId=null;}}

function updateSimUI(){
    const dots=document.querySelectorAll('.sim-dot');
    const texts=document.querySelectorAll('.sim-status-text');
    const sbBtn=document.getElementById('sidebarSimBtn');
    const mobBtn=document.getElementById('mobSimBtn');
    const mobIcon=document.getElementById('mobSimIcon');
    const mobLabel=document.getElementById('mobSimLabel');
    const startBtn=document.getElementById('modalStartBtn');
    const stopBtn=document.getElementById('modalStopBtn');
    const statRow=document.getElementById('simStatusRow');
    const modalSt=document.getElementById('modalSimStatus');
    dots.forEach(d=>simRunning?d.classList.add('running'):d.classList.remove('running'));
    texts.forEach(t=>{t.textContent=simRunning?'Device Running…':'Device Offline';simRunning?t.classList.add('running'):t.classList.remove('running');});
    document.querySelectorAll('.sim-patient-select,.sim-modal-patient-select').forEach(s=>simRunning?s.classList.add('locked'):s.classList.remove('locked'));
    if(sbBtn){sbBtn.className=simRunning?'btn-sim btn-sim-stop':'btn-sim btn-sim-start';sbBtn.innerHTML=simRunning?'<i class="fa-solid fa-stop"></i> Stop Simulation':'<i class="fa-solid fa-play"></i> Start Simulation';sbBtn.onclick=simRunning?stopSimulation:openSimModal;}
    if(mobBtn){simRunning?mobBtn.classList.add('running'):mobBtn.classList.remove('running');if(mobIcon)mobIcon.className=simRunning?'fa-solid fa-stop':'fa-solid fa-microchip';if(mobLabel)mobLabel.textContent=simRunning?'Running':'Simulate';mobBtn.onclick=simRunning?stopSimulation:openSimModal;}
    if(startBtn)startBtn.style.display=simRunning?'none':'flex';
    if(stopBtn)stopBtn.style.display=simRunning?'flex':'none';
    if(statRow)statRow.style.display=simRunning?'none':'flex';
    if(modalSt){modalSt.textContent=simRunning?'🟠 Running':'⚫ Offline';modalSt.style.color=simRunning?'var(--c-coral)':'#94a3b8';}
    const bpmDisplay=document.getElementById('simLiveBpm');
    if(bpmDisplay)simRunning?bpmDisplay.classList.add('visible'):bpmDisplay.classList.remove('visible');
    document.getElementById('simUpdatesRow').style.display=simRunning?'flex':'none';
    if(simRunning){setTimeout(()=>initWaveform(),100);}else{
        stopWaveform();
        const ring=document.getElementById('simBpmRingFill');if(ring){ring.style.strokeDasharray=SIM_CIRC;ring.style.strokeDashoffset=SIM_CIRC;ring.className='ring-fill';}
        const numEl=document.getElementById('simLiveBpmNum');if(numEl){numEl.textContent='--';numEl.className='sim-bpm-num';}
        ['simAvgBpm','simMinBpm','simMaxBpm'].forEach(id=>{const el=document.getElementById(id);if(el)el.textContent='--';});
        const needle=document.getElementById('simZoneNeedle');if(needle)needle.style.left='0%';
    }
}

let simRunning=false,simInterval=null,simUpdateCount=0,simBpmHistory=[];
function generateRandomHR(){const r=Math.random();if(r<0.60)return Math.floor(Math.random()*40)+60;if(r<0.80)return Math.floor(Math.random()*21)+100;if(r<0.90)return Math.floor(Math.random()*20)+121;return Math.floor(Math.random()*20)+40;}
function generateNextHR(cur){const r=Math.random();if(r<0.08)return generateRandomHR();if(r<0.28)return Math.max(35,Math.min(160,cur+Math.round((Math.random()-.5)*40)));return Math.max(35,Math.min(160,cur+Math.round((Math.random()-.5)*10)));}
async function sendSimDataToDB(patientId,hr){try{await fetch('../api/sim_update.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({patients:[{id:patientId,heart_rate:hr,status:getStatus(hr)}]})});simUpdateCount++;const el=document.getElementById('simUpdateCount');if(el)el.textContent=simUpdateCount;}catch(e){console.warn('Sim DB write failed:',e);}}
function startSimulation(){
    if(simRunning)return;
    if(!simPatientId){const sel=document.getElementById('modalPatientSelect');if(sel){sel.style.borderColor='#ef4444';setTimeout(()=>{sel.style.borderColor='';},1500);}if(typeof showToast==='function')showToast('No Patient Selected','Please choose a patient before starting.','error');return;}
    simRunning=true;simUpdateCount=0;simBpmHistory=[];simPatientHR=generateRandomHR();
    updateSimUI();updateLiveBpmUI(simPatientHR);sendSimDataToDB(simPatientId,simPatientHR);
    simInterval=setInterval(()=>{simPatientHR=generateNextHR(simPatientHR);updateLiveBpmUI(simPatientHR);if(simPatientId)sendSimDataToDB(simPatientId,simPatientHR);},1500);
    const patName=document.getElementById('modalPatientSelect')?.selectedOptions[0]?.text||'Patient';
    if(typeof showToast==='function')showToast('🟠 Simulation Started',`Streaming vitals for ${patName}.`,'success');
}
async function stopSimulation(){if(!simRunning)return;simRunning=false;clearInterval(simInterval);simInterval=null;if(typeof fetchLiveData==='function')await fetchLiveData();updateSimUI();closeSimModal();if(typeof showToast==='function')showToast('⏹ Simulation Stopped','Final data saved to database.','info');}

document.getElementById('sidebarSimBtn').onclick=openSimModal;
updateSimUI();
setTimeout(populatePatientSelects,2000);
window.addEventListener('vitalwear:live-loaded',populatePatientSelects,{once:true});

let rescuerAlertPanelOpen=false,lastRescuerAlertCount=0;
function toggleRescuerAlertPanel(){rescuerAlertPanelOpen=!rescuerAlertPanelOpen;document.getElementById('rescuerAlertPanel').classList.toggle('open',rescuerAlertPanelOpen);if(rescuerAlertPanelOpen)fetchRescuerAlerts();}
async function fetchRescuerAlerts(){try{const res=await fetch('../api/get_rescuer_alerts.php');if(!res.ok)return;const data=await res.json();if(!data.success)return;const unread=data.unread_count||0;['rescuerAlertBadge','mobRescuerAlertBadge'].forEach(id=>{const el=document.getElementById(id);if(!el)return;el.textContent=unread;el.classList.toggle('hidden',unread===0);});if(unread>lastRescuerAlertCount&&lastRescuerAlertCount>=0){const newest=data.alerts?.[0];if(newest&&typeof showToast==='function')showToast('📍 Rescuer Alert',`${newest.rescuer_name}: ${newest.patient_name} — ${newest.message.substring(0,50)}`,'warning',7000);}lastRescuerAlertCount=unread;const list=document.getElementById('rescuerAlertList');if(!list)return;if(!data.alerts||data.alerts.length===0){list.innerHTML='<div style="text-align:center;padding:30px;color:var(--text-muted);font-size:13px">No alerts yet.</div>';return;}list.innerHTML=data.alerts.map(a=>{const hasLoc=a.latitude&&a.longitude;const mapsUrl=hasLoc?`https://www.google.com/maps?q=${a.latitude},${a.longitude}`:'';return`<div class="rescuer-alert-item ${hasLoc?'has-location':''}"><div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:6px"><div class="rescuer-alert-item-name">🧑‍⚕️ ${escapeHtml(a.patient_name)}</div><div class="rescuer-alert-item-time">${formatAlertTime(a.created_at)}</div></div><div class="rescuer-alert-item-from">From: ${escapeHtml(a.rescuer_name)}</div><div class="rescuer-alert-item-msg">${escapeHtml(a.message)}</div>${hasLoc?`<a href="${mapsUrl}" target="_blank" class="location-link"><i class="fa-solid fa-map-pin"></i> View on Map</a>`:''}</div>`;}).join('');}catch(e){console.warn('Rescuer alert fetch failed:',e);}}
async function markRescuerAlertsRead(){await fetch('../api/get_rescuer_alerts.php?mark_read=1');lastRescuerAlertCount=0;fetchRescuerAlerts();}
function formatAlertTime(dateStr){const diff=Math.floor((Date.now()-new Date(dateStr))/1000);if(diff<60)return'just now';if(diff<3600)return Math.floor(diff/60)+'m ago';if(diff<86400)return Math.floor(diff/3600)+'h ago';return new Date(dateStr).toLocaleDateString();}
function escapeHtml(str){return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
setInterval(fetchRescuerAlerts,10000);
setTimeout(fetchRescuerAlerts,1500);

function toggleSidebar(){
    const sb=document.getElementById('sidebar');
    const ov=document.getElementById('sidebarOverlay');
    if(!sb)return;
    const open=sb.classList.toggle('open');
    if(ov){
        ov.classList.toggle('open',open);
        // Inline style to guarantee no blur ever — overrides anything in styles.css
        ov.style.backdropFilter='none';
        ov.style.webkitBackdropFilter='none';
        ov.style.filter='none';
    }
    // Kill any blur applied to main content by scripts.js or styles.css
    const mc=document.querySelector('.main-content');
    if(mc){
        mc.style.filter='none';
        mc.style.backdropFilter='none';
        mc.style.webkitBackdropFilter='none';
    }
    document.body.style.overflow=open?'hidden':'';
}
</script>