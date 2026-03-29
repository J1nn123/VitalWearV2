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

    /* Sidebar — matches admin exactly */
    --sb-bg        : #1C1014;
    --sb-border    : rgba(255,255,255,.07);
    --sb-surface   : rgba(255,255,255,.05);
    --sb-surface-h : rgba(255,255,255,.09);
    --sb-text      : #F1ECE9;
    --sb-muted     : #7A6A65;
    --sb-label     : #4D3830;

    /* Main content — matches admin */
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
}

* { box-sizing: border-box; }
body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg-page);
    color: var(--text-primary);
    margin: 0;
    -webkit-font-smoothing: antialiased;
}

/* ── Override anything from styles.css that might push content ── */
.layout {
    display: flex !important;
    min-height: 100vh;
}
.main-content {
    flex: 1 !important;
    min-width: 0;
    overflow-x: clip;
    margin-left: 0 !important;  /* kill any legacy margin-left from styles.css */
    padding-left: 0 !important;
    width: auto !important;
}

/* ═══════════════════════════════════════════════════
   SIDEBAR — identical to admin
═══════════════════════════════════════════════════ */
.sidebar {
    width: 252px;
    background: var(--sb-bg);
    display: flex; flex-direction: column;
    height: 100vh; position: sticky; top: 0;
    overflow-y: auto; z-index: 200; flex-shrink: 0;
    transition: transform 0.3s cubic-bezier(.4,0,.2,1);
    border-right: 1px solid var(--sb-border);
    box-shadow: 4px 0 32px rgba(0,0,0,.32);
    scrollbar-width: none;
}
.sidebar::-webkit-scrollbar { display: none; }

.sidebar::before {
    content: ''; position: absolute; inset: 0; pointer-events: none; z-index: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
    background-size: 200px; opacity: .6;
}
.sidebar > * { position: relative; z-index: 1; }

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
    box-shadow: 0 0 20px var(--c-coral-glow); overflow: hidden; flex-shrink: 0;
}
.logo-text { font-size: 17px; font-weight: 800; color: var(--sb-text); letter-spacing: -.4px; }

.sidebar-close {
    display: none;
    background: var(--sb-surface); border: none; color: #94a3b8; cursor: pointer;
    padding: 6px 8px; border-radius: 6px; font-size: 16px; transition: all .2s;
}
.sidebar-close:hover { background: rgba(239,68,68,.15); color: #f87171; }

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
.rescuer-avatar {
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
.rescuer-badge {
    background: var(--c-coral-muted); color: var(--c-coral-soft);
    border: 1px solid var(--c-coral-border);
}

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

/* ═══════════════════════════════════════════════════
   TOPBAR — matches admin (#eadddd)
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
   STAT CARDS — matches admin exactly
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
    display: flex; align-items: center; justify-content: center; font-size: 18px;
}
.stat-icon.blue   { background: rgba(59,130,246,.12);  color: #3b82f6; }
.stat-icon.green  { background: var(--c-coral-muted);  color: var(--c-coral); }
.stat-icon.yellow { background: rgba(245,158,11,.12);  color: #f59e0b; }
.stat-icon.red    { background: rgba(239,68,68,.12);   color: #ef4444; }
.stat-icon.coral  { background: var(--c-coral-muted);  color: var(--c-coral); }

.stat-label  { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .8px; }
.stat-value  { font-size: 32px; font-weight: 800; color: var(--text-primary); line-height: 1.1; margin: 4px 0; }
.stat-sub    { font-size: 12px; color: var(--text-label); font-weight: 500; }
.text-green  { color: var(--c-coral) !important; }
.text-yellow { color: #f59e0b !important; }
.text-red    { color: #ef4444 !important; }
.text-blue   { color: #3b82f6 !important; }

/* ═══════════════════════════════════════════════════
   SECTION CARDS — matches admin
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

/* ═══════════════════════════════════════════════════
   STICKY TAB BAR — matches admin
═══════════════════════════════════════════════════ */
.tab-sticky-wrapper {
    position: sticky; top: 60px; z-index: 90;
    background: #fff;
    border-radius: 14px;
    border: 1.5px solid var(--card-border);
    box-shadow: var(--card-shadow);
    margin-bottom: 24px;
}
.tabs {
    display: flex;
    overflow-x: auto; -webkit-overflow-scrolling: touch;
    -ms-overflow-style: none; scrollbar-width: none;
    gap: 2px; padding: 12px 12px 0;
    border-bottom: 2px solid rgba(239,108,82,.25);
    border-radius: 14px 14px 0 0;
    flex-wrap: nowrap;
}
.tabs::-webkit-scrollbar { display: none; }
.tab-btn {
    white-space: nowrap; flex-shrink: 0;
    color: var(--text-muted); font-weight: 600; font-size: 13px;
    border: none; background: none; cursor: pointer;
    padding: 10px 16px 12px;
    border-bottom: 2px solid transparent; margin-bottom: -2px;
    transition: all .2s; font-family: 'DM Sans', sans-serif;
    border-radius: 8px 8px 0 0;
    display: inline-flex; align-items: center; gap: 5px;
}
.tab-btn:hover  { color: var(--c-coral); background: var(--c-coral-muted); }
.tab-btn.active {
    color: var(--c-coral); border-bottom-color: var(--c-coral);
    font-weight: 700; background: rgba(239,108,82,.06);
}
@media(max-width:520px){
    .tab-btn { padding: 10px 12px 12px; font-size: 12px; }
    .tab-btn i { display: none; }
}

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
.nav-alert-badge {
    background: var(--c-coral); color: #fff;
    font-size: 10px; font-weight: 800; border-radius: 20px; padding: 1px 6px; margin-left: 5px;
}

/* ═══════════════════════════════════════════════════
   BPM DISPLAY
═══════════════════════════════════════════════════ */
.bpm-value   { font-weight: 800; font-size: 17px; font-variant-numeric: tabular-nums; }
.bpm-normal  { color: var(--c-coral) !important; }
.bpm-warning { color: #f59e0b !important; }
.bpm-critical{ color: #ef4444 !important; }

/* ═══════════════════════════════════════════════════
   BUTTONS — matches admin
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
    box-shadow: 0 2px 14px var(--c-coral-glow) !important;
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
.w-full { width: 100%; justify-content: center; }

/* ═══════════════════════════════════════════════════
   MODALS — matches admin (strong borders)
═══════════════════════════════════════════════════ */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.55); backdrop-filter: blur(5px);
    z-index: 1000; align-items: center; justify-content: center;
}
.modal-overlay.open, .modal-overlay.active { display: flex; }
.modal {
    background: #fff; border-radius: 16px;
    width: 100%; max-width: 480px;
    box-shadow: 0 24px 64px rgba(0,0,0,.22), 0 6px 24px rgba(239,108,82,.30), 0 0 0 1px rgba(239,108,82,.10);
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

/* ═══════════════════════════════════════════════════
   FORM ELEMENTS — matches admin (strong borders)
═══════════════════════════════════════════════════ */
.form-group  { margin-bottom: 16px; }
.form-label  {
    display: block; font-size: 11px; font-weight: 700;
    color: var(--text-muted); margin-bottom: 6px;
    text-transform: uppercase; letter-spacing: .6px;
}
.form-input, .form-select, .form-textarea {
    width: 100%; padding: 10px 14px;
    background: #F9FAFB;
    border: 1.5px solid #C4C9D4;
    border-radius: 9px; color: var(--text-primary); font-size: 13px;
    font-family: 'DM Sans', sans-serif; transition: all .2s;
    box-shadow: inset 0 1px 3px rgba(0,0,0,.05);
    box-sizing: border-box;
}
.form-textarea { min-height: 80px; resize: vertical; }
.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none; border-color: var(--c-coral);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(239,108,82,.14), inset 0 1px 3px rgba(0,0,0,.03);
}
.form-input::placeholder, .form-textarea::placeholder { color: #B0B7C3; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media(max-width:520px){ .form-grid { grid-template-columns: 1fr; } }

/* ═══════════════════════════════════════════════════
   TABLE — matches admin
═══════════════════════════════════════════════════ */
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

/* ═══════════════════════════════════════════════════
   PATIENT CARDS
═══════════════════════════════════════════════════ */
.patient-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:14px; }
@media(max-width:640px){ .patient-grid{ grid-template-columns:1fr; } }

.big-patient-card {
    background: var(--bg-card);
    border: 1.5px solid var(--card-border);
    border-radius: 14px; padding: 18px;
    box-shadow: var(--card-shadow);
    transition: transform .2s, box-shadow .2s;
}
.big-patient-card:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover); }
.big-patient-card.critical-card { border-left: 4px solid #ef4444; background: rgba(239,68,68,.02); animation: criticalCardPulse 2s ease-in-out infinite; }
.big-patient-card.warning-card  { border-left: 4px solid #f59e0b; background: rgba(245,158,11,.02); }
.big-patient-card.normal-card   { border-left: 4px solid var(--c-coral); }
@keyframes criticalCardPulse {
    0%,100%{ box-shadow:0 4px 20px rgba(239,68,68,.12); border-color:rgba(239,68,68,.4); }
    50%    { box-shadow:0 4px 30px rgba(239,68,68,.28); border-color:rgba(239,68,68,.7); }
}
.bpm-large { font-size: 42px; font-weight: 800; line-height: 1; }

/* Alert inbox */
.alert-inbox { display:flex; flex-direction:column; gap:10px; }
.alert-item {
    background: var(--bg-card);
    border: 1.5px solid rgba(239,108,82,.15);
    border-radius: var(--radius); padding: 14px 16px;
    box-shadow: 0 2px 8px rgba(30,36,80,.06);
}
.alert-item.unread {
    border-left: 4px solid var(--c-coral);
    background: rgba(239,108,82,.04);
}
.alert-item-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
.alert-item-patient { font-weight:700; font-size:14px; color: var(--text-primary); }
.alert-item-time    { font-size:11px; color:#9CA3AF; }
.alert-item-from    { font-size:12px; color: var(--c-coral); margin-bottom:6px; }
.alert-item-msg     { font-size:13px; color: var(--text-secondary); line-height:1.5; }
.unread-dot { width:8px; height:8px; border-radius:50%; background: var(--c-coral); display:inline-block; margin-right:5px; }

.btn-mark-read {
    background: var(--c-coral-muted); border: 1px solid var(--c-coral-border);
    color: var(--c-coral); border-radius: 8px; padding: 6px 14px;
    font-size: 12px; font-weight: 600; cursor: pointer; transition: all .2s;
    font-family: 'DM Sans', sans-serif;
}
.btn-mark-read:hover { background: rgba(239,108,82,.2); }

/* Severity badges */
.severity-high     { background:rgba(239,68,68,.1);     color:#ef4444;  border:1px solid rgba(239,68,68,.2); }
.severity-medium   { background:rgba(245,158,11,.1);    color:#f59e0b;  border:1px solid rgba(245,158,11,.2); }
.severity-low      { background:rgba(239,108,82,.12);   color:#EF6C52;  border:1px solid rgba(239,108,82,.22); }
.severity-critical { background:rgba(239,68,68,.1);     color:#ef4444;  border:1px solid rgba(239,68,68,.2); animation:bpm-pulse 1s infinite; }
@keyframes bpm-pulse { 0%,100%{opacity:1} 50%{opacity:.6} }

.sev-badge    { padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;white-space:nowrap; }
.sev-low      { background:var(--c-coral-muted);color:var(--c-coral);border:1px solid var(--c-coral-border); }
.sev-medium   { background:rgba(245,158,11,.12);color:#d97706;border:1px solid rgba(245,158,11,.25); }
.sev-high     { background:rgba(239,68,68,.12);color:#ef4444;border:1px solid rgba(239,68,68,.25); }
.sev-critical { background:rgba(185,28,28,.1);color:#b91c1c;border:1px solid rgba(185,28,28,.25); }

/* Toast */
.toast-container { position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px; }

/* Sidebar overlay */
.sidebar-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9997; }
.sidebar-overlay.open { display:block; }

/* ═══════════════════════════════════════════════════
   MOBILE BOTTOM NAVBAR
═══════════════════════════════════════════════════ */
.mobile-bottom-nav {
    display: none; position: fixed; bottom: 0; left: 0; right: 0; height: 64px;
    background: var(--sb-bg); border-top: 1px solid var(--sb-border);
    z-index: 9999; padding-bottom: env(safe-area-inset-bottom, 0);
    box-shadow: 0 -4px 24px rgba(0,0,0,.4);
}
.mobile-bottom-nav-inner { display: flex; height: 100%; align-items: stretch; }
.mob-nav-item {
    flex: 1; display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 3px; text-decoration: none;
    color: #4A3530; font-size: 10px; font-weight: 700;
    letter-spacing: .2px; padding: 6px 2px;
    transition: color .2s; position: relative;
    cursor: pointer; background: none; border: none; font-family: 'DM Sans', sans-serif;
}
.mob-nav-item i { font-size: 18px; transition: all .2s; }
.mob-nav-item.active { color: var(--c-coral); }
.mob-nav-item.active i { color: var(--c-coral); }
.mob-nav-item.active::before {
    content: ''; position: absolute; top: 0; left: 20%; right: 20%; height: 2px;
    background: var(--c-coral); border-radius: 0 0 3px 3px;
}
.mob-nav-badge {
    position: absolute; top: 5px; right: calc(50% - 20px);
    background: #ef4444; color: #fff; font-size: 9px; font-weight: 700;
    min-width: 16px; height: 16px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    padding: 0 3px; border: 1px solid var(--sb-bg);
}
.mob-nav-badge.hidden { display: none; }
.mob-nav-badge.coral-badge { background: var(--c-coral); }

/* ═══════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════ */
@media (max-width: 1100px) {
    .stats-grid-4 { grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 900px) {
    .stats-grid-3 { grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 768px) {
    .sidebar { position: fixed; top: 0; left: 0; width: 280px; height: 100%; transform: translateX(-100%); z-index: 9998; }
    .sidebar.open { transform: translateX(0); box-shadow: 8px 0 40px rgba(0,0,0,.4); }
    .sidebar-close { display: flex !important; }
    .mobile-bottom-nav { display: flex; }
    .page-content { padding: 16px; padding-bottom: 80px !important; }
    .topbar { padding: 0 16px; }
    .modal { max-width: calc(100vw - 24px); margin: 0 12px; }
}
@media (min-width: 769px) {
    .mobile-bottom-nav { display: none !important; }
    .menu-toggle { display: none !important; }
}
@media (max-width: 600px) {
    .stats-grid-4 { grid-template-columns: repeat(2,1fr); }
    .section-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .form-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ── DESKTOP SIDEBAR ── -->
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

<!-- ── MOBILE BOTTOM NAVBAR ── -->
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
function updateRescuerAlertBadge(count) {
    ['sidebarAlertBadge', 'mobAlertBadge'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = count;
        el.classList.toggle('hidden', count === 0);
    });
}
</script>