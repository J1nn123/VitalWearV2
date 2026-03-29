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
    background: var(--bg-page);
}

/* Sidebar overlay */
.sidebar-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.50);
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    z-index: 9997;
}
.sidebar-overlay.open { display: block; }


.sidebar {
    width: 252px;
    background: var(--sb-bg);
    display: flex; flex-direction: column;
    height: 100vh; position: sticky; top: 0;
    overflow-y: auto; z-index: 200; flex-shrink: 0;
    transition: transform 0.3s cubic-bezier(.4,0,.2,1);
    border-right: 1px solid var(--sb-border);
    box-shadow: 4px 0 32px rgba(0,0,0,.28);
    scrollbar-width: none;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}
.sidebar::-webkit-scrollbar { display: none; }
.sidebar::before {
    content: ''; position: absolute; inset: 0; pointer-events: none; z-index: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.025'/%3E%3C/svg%3E");
    background-size: 200px; opacity: .5;
}
.sidebar > * { position: relative; z-index: 1; }

.sidebar-header {
    padding: 22px 18px 16px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--sb-border); flex-shrink: 0;
}
.sidebar-logo { display: flex; align-items: center; gap: 11px; text-decoration: none; }
.logo-icon {
    width: 36px; height: 36px;
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep));
    border-radius: 10px; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 20px var(--c-coral-glow); overflow: hidden; flex-shrink: 0;
}
.logo-text { font-size: 17px; font-weight: 800; color: var(--sb-text); letter-spacing: -.4px; }

.sidebar-close {
    display: none;
    background: var(--sb-surface); border: none; color: #94a3b8; cursor: pointer;
    width: 32px; height: 32px; border-radius: 8px;
    align-items: center; justify-content: center; font-size: 14px; transition: all .2s;
}
.sidebar-close:hover { background: rgba(239,68,68,.15); color: #f87171; }

.sidebar-user {
    padding: 16px 18px; display: flex; align-items: center; gap: 11px;
    border-bottom: 1px solid var(--sb-border);
    background: rgba(239,108,82,.04); flex-shrink: 0;
}
.user-avatar { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; flex-shrink: 0; }
.manager-avatar { background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep)); color: #fff; box-shadow: 0 0 14px var(--c-coral-glow), 0 2px 6px rgba(0,0,0,.3); }
.user-info { min-width: 0; }
.user-info .user-name { font-size: 13px; font-weight: 700; color: var(--sb-text); line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-role-badge { font-size: 10px; padding: 2px 8px; border-radius: 20px; font-weight: 700; margin-top: 3px; display: inline-flex; align-items: center; gap: 4px; letter-spacing: .2px; }
.manager-badge { background: var(--c-coral-muted); color: var(--c-coral-soft); border: 1px solid var(--c-coral-border); }

.sidebar-nav { padding: 12px 10px; flex: 1; display: flex; flex-direction: column; gap: 1px; }
.nav-label { font-size: 10px; font-weight: 700; color: var(--sb-label); letter-spacing: 1.2px; text-transform: uppercase; padding: 12px 9px 5px; }
.nav-item {
    display: flex; align-items: center; gap: 11px; padding: 10px 13px; border-radius: 9px;
    color: var(--sb-muted); text-decoration: none; font-size: 13px; font-weight: 600;
    transition: all 0.15s; position: relative; cursor: pointer; background: none; border: none;
    width: 100%; text-align: left; font-family: 'DM Sans', sans-serif; white-space: nowrap;
}
.nav-item i { width: 18px; text-align: center; font-size: 14px; flex-shrink: 0; transition: color .15s; }
.nav-item:hover { background: rgba(255,255,255,.06); color: var(--sb-text); }
.nav-item:hover i { color: var(--c-coral-soft); }
.nav-item.active { background: var(--c-coral-muted); color: var(--c-coral-soft); border: 1px solid var(--c-coral-border); box-shadow: 0 2px 12px rgba(239,108,82,.12); }
.nav-item.active i { color: var(--c-coral); }
.nav-badge { margin-left: auto; background: #746d6d; color: #fff; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 10px; min-width: 20px; text-align: center; }
.nav-badge.hidden { display: none; }
.logout-item { color: var(--sb-muted); }
.logout-item:hover { background: rgba(239,68,68,.08) !important; color: #f87171 !important; }
.logout-item:hover i { color: #f87171 !important; }
.sidebar-divider { height: 1px; background: var(--sb-border); margin: 6px 14px; }


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
.menu-toggle { background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 7px; border-radius: 8px; display: flex; align-items: center; transition: all .2s; }
.menu-toggle:hover { background: var(--c-coral-muted); color: var(--c-coral); }
.page-title { font-size: 16px; font-weight: 700; color: var(--text-primary); }
.live-indicator { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 800; color: var(--c-coral); letter-spacing: .5px; }
.live-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--c-coral); box-shadow: 0 0 8px var(--c-coral-glow); animation: livePulse 1.4s ease-in-out infinite; }
@keyframes livePulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.6;transform:scale(1.3)} }
.topbar-time { font-size: 13px; font-weight: 600; color: var(--text-muted); font-variant-numeric: tabular-nums; }


.page-content { padding: 28px; background: var(--bg-page); min-height: calc(100vh - 60px); }


.stats-grid   { display: grid; gap: 18px; margin-bottom: 24px; }
.stats-grid-4 { grid-template-columns: repeat(4,1fr); }
.stats-grid-3 { grid-template-columns: repeat(3,1fr); }
.stats-grid-2 { grid-template-columns: repeat(2,1fr); }
.mb-6 { margin-bottom: 24px; }

.stat-card {
    background: var(--bg-card); border-radius: 16px; padding: 22px 20px 18px;
    border: 1.5px solid rgba(239,108,82,.25);
    box-shadow: var(--card-shadow-strong);
    transition: transform .2s, box-shadow .2s;
    position: relative; overflow: hidden;
}
.stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 16px 16px 0 0; }
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover); }
.card-blue::before   { background: linear-gradient(90deg,#3b82f6,#6366f1); }
.card-green::before  { background: linear-gradient(90deg,var(--c-coral),var(--c-coral-soft)); }
.card-yellow::before { background: linear-gradient(90deg,#f59e0b,#fbbf24); }
.card-red::before    { background: linear-gradient(90deg,#ef4444,#f87171); }
.card-purple::before { background: linear-gradient(90deg,#8b5cf6,#a78bfa); }

.stat-card-header { margin-bottom: 12px; }
.stat-icon { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.stat-icon svg { width: 20px; height: 20px; }
.stat-icon.blue   { background: rgba(59,130,246,.12); color: #3b82f6; }
.stat-icon.green  { background: var(--c-coral-muted); color: var(--c-coral); }
.stat-icon.yellow { background: rgba(245,158,11,.12); color: #d97706; }
.stat-icon.red    { background: rgba(239,68,68,.12);  color: #ef4444; }
.stat-icon.purple { background: rgba(139,92,246,.12); color: #8b5cf6; }

.stat-label  { font-size: 11px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: .8px; }
.stat-value  { font-size: 32px; font-weight: 800; color: #1A1A2E; line-height: 1.1; margin: 4px 0; }
.stat-sub    { font-size: 12px; color: #9CA3AF; font-weight: 500; }
.text-green  { color: var(--c-coral) !important; }
.text-yellow { color: #d97706 !important; }
.text-red    { color: #ef4444 !important; }
.text-blue   { color: #3b82f6 !important; }


.section-card {
    background: var(--bg-card); border-radius: 16px;
    border: 1.5px solid rgba(239,108,82,.20);
    box-shadow: var(--card-shadow-strong);
    overflow: hidden; margin-bottom: 22px;
}
/* Tab container variant — needs visible overflow for scroll */
.section-card.tab-container {
    overflow: visible;  /* allow tab scrollbar to show */
}
.section-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 22px 14px;
    border-bottom: 1px solid rgba(239,108,82,.10);
}
.section-title    { font-size: 15px; font-weight: 700; color: #1A1A2E; }
.section-subtitle { font-size: 12px; color: #6B7280; margin-top: 3px; font-weight: 500; }

/* Charts */
.chart-card {
    background: var(--bg-card); border-radius: 16px;
    border: 1.5px solid rgba(239,108,82,.20);
    box-shadow: var(--card-shadow-strong);
    overflow: hidden; padding-bottom: 20px; margin-bottom: 22px;
}
.chart-card-title {
    padding: 16px 20px 12px; font-size: 14px; font-weight: 700; color: #1A1A2E;
    display: flex; align-items: center; gap: 8px;
    border-bottom: 1px solid rgba(239,108,82,.08);
}
.chart-container { padding: 16px 20px; }
.chart-wrapper      { position: relative; height: 260px; }
.chart-wrapper-tall { position: relative; height: 260px; }


.tabs {
    display: flex;
    overflow-x: auto;                     /* ← scrolls horizontally */
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;                /* hide scrollbar Firefox */
    -ms-overflow-style: none;             /* hide scrollbar IE */
    flex-wrap: nowrap;                    /* never wrap */
    gap: 2px;
    padding: 12px 14px 0;
    border-bottom: 2px solid rgba(239,108,82,.18);
    background: #fff;
    border-radius: 16px 16px 0 0;
}
.tabs::-webkit-scrollbar { display: none; } /* hide scrollbar Chrome */

.tab-btn {
    flex-shrink: 0;                       /* ← never shrink/clip */
    white-space: nowrap;                  /* ← never wrap text */
    color: #6B7280; font-weight: 600; font-size: 13px;
    border: none; background: none; cursor: pointer;
    padding: 10px 16px 12px;
    border-bottom: 2.5px solid transparent;
    margin-bottom: -2px;
    transition: all .2s;
    font-family: 'DM Sans', sans-serif;
    border-radius: 8px 8px 0 0;
    display: inline-flex; align-items: center; gap: 6px;
}
.tab-btn:hover  { color: var(--c-coral); background: rgba(239,108,82,.06); }
.tab-btn.active { color: var(--c-coral); border-bottom-color: var(--c-coral); font-weight: 700; background: rgba(239,108,82,.06); }
/* Hide icon labels on very small screens to save space */
@media (max-width: 480px) {
    .tab-btn { padding: 10px 12px 12px; font-size: 12px; }
    .tab-btn i { display: none; }
    .tab-label-long { display: none; }
}

.table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
table { width: 100%; border-collapse: collapse; min-width: 520px; }
table thead th {
    background: linear-gradient(135deg, #1E2450, #2D3478);
    color: #fff !important; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .8px;
    padding: 12px 16px; text-align: left; white-space: nowrap;
}
table tbody tr { border-bottom: 1px solid rgba(239,108,82,.07); transition: background .15s; }
table tbody tr:hover { background: rgba(239,108,82,.04) !important; }
table tbody td { padding: 13px 16px; font-size: 13px; color: #1A1A2E; }
.td-muted { color: #5A6272 !important; font-weight: 500; }
.table-toolbar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

/* Scroll hint */
.table-scroll-hint {
    display: none;
    font-size: 11px; color: #9CA3AF; padding: 8px 20px 0;
    align-items: center; gap: 5px;
}

.badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; border: 1px solid transparent; }
.badge-normal   { background: rgba(239,108,82,.12); color: #C45030; border-color: rgba(239,108,82,.28); }
.badge-warning  { background: rgba(245,158,11,.13);  color: #B45309; border-color: rgba(245,158,11,.30); }
.badge-critical { background: rgba(239,68,68,.12);   color: #B91C1C; border-color: rgba(239,68,68,.28); }

.sev-badge    { padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; white-space: nowrap; }
.sev-low      { background: rgba(239,108,82,.12); color: #C45030; border: 1px solid rgba(239,108,82,.28); }
.sev-medium   { background: rgba(245,158,11,.13);  color: #B45309; border: 1px solid rgba(245,158,11,.30); }
.sev-high     { background: rgba(239,68,68,.12);   color: #B91C1C; border: 1px solid rgba(239,68,68,.28); }
.sev-critical { background: rgba(185,28,28,.12);   color: #991B1B; border: 1px solid rgba(185,28,28,.28); }

/* BPM */
.bpm-value   { font-weight: 800; font-size: 17px; font-variant-numeric: tabular-nums; }
.bpm-normal  { color: #C45030 !important; }
.bpm-warning { color: #B45309 !important; }
.bpm-critical{ color: #B91C1C !important; }
.bpm-bar { height: 5px; background: rgba(0,0,0,.07); border-radius: 3px; margin-top: 5px; overflow: hidden; }
.bpm-bar-fill { height: 100%; border-radius: 3px; transition: width .5s ease; }
.fill-normal  { background: linear-gradient(90deg, var(--c-coral), var(--c-coral-soft)); }
.fill-warning { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.fill-critical{ background: linear-gradient(90deg, #ef4444, #f87171); }

.btn { display: inline-flex; align-items: center; gap: 7px; padding: 8px 16px; border-radius: 9px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: all .2s; font-family: 'DM Sans', sans-serif; line-height: 1; white-space: nowrap; text-decoration: none; }
.btn-sm { padding: 6px 12px; font-size: 12px; }
.btn-primary { background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep)) !important; color: #fff !important; font-weight: 700 !important; box-shadow: 0 2px 14px var(--c-coral-glow) !important; border-color: transparent !important; }
.btn-primary:hover { filter: brightness(1.07); transform: translateY(-1px); }
.btn-ghost { background: #F5F5F7; color: #374151 !important; border-color: #E5E7EB; }
.btn-ghost:hover { background: rgba(239,108,82,.08); color: var(--c-coral) !important; border-color: var(--c-coral-border); }
.btn-danger { background: rgba(239,68,68,.10); color: #B91C1C; border-color: rgba(239,68,68,.25); }
.btn-danger:hover { background: rgba(239,68,68,.18); }


.device-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px; margin-bottom: 22px;
}
.device-mini-card {
    background: var(--bg-card); border-radius: 14px; padding: 18px 16px;
    text-align: center; border: 1.5px solid rgba(239,108,82,.20);
    box-shadow: var(--card-shadow-strong); transition: transform .2s, box-shadow .2s;
}
.device-mini-card:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover); }
.device-mini-card .dmc-count { font-size: 30px; font-weight: 800; margin: 6px 0 4px; line-height: 1; }
.device-mini-card .dmc-label { font-size: 11px; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: .7px; }
.device-mini-card .dmc-sub   { font-size: 11px; color: #9CA3AF; margin-top: 3px; }

.modal-backdrop, .modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.52); backdrop-filter: blur(5px);
    z-index: 1000; align-items: center; justify-content: center; padding: 16px;
}
.modal-backdrop.open, .modal-overlay.open { display: flex; }
.modal-box, .modal {
    background: #fff; border-radius: 16px;
    width: 100%; max-width: 480px;
    box-shadow: 0 24px 64px rgba(0,0,0,.20), 0 6px 24px rgba(239,108,82,.25);
    border: 2px solid rgba(239,108,82,.50);
    max-height: 90vh; overflow-y: auto;
}
.modal { overflow: visible; display: flex; flex-direction: column; }
.modal-box { padding: 26px 28px; }
.modal-title { font-size: 16px; font-weight: 800; margin-bottom: 20px; color: #1A1A2E; }
.modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
.modal-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px 16px;
    border-bottom: 1.5px solid rgba(239,108,82,.25); flex-shrink: 0;
}
.modal-header .modal-title { font-size: 16px; font-weight: 800; color: #1A1A2E; margin: 0; }
.modal-close { background: none; border: none; font-size: 22px; color: #9CA3AF; cursor: pointer; padding: 0 4px; line-height: 1; transition: color .2s; }
.modal-close:hover { color: #ef4444; }
.modal-body { padding: 20px 24px; overflow-y: auto; flex: 1; }
.modal-footer { padding: 14px 24px; border-top: 1.5px solid rgba(239,108,82,.20); display: flex; gap: 10px; justify-content: flex-end; flex-shrink: 0; background: rgba(239,108,82,.03); }

/* Form */
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 11px; font-weight: 700; color: #6B7280; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
.form-input, .form-select, .form-textarea {
    width: 100%; padding: 10px 14px;
    background: #F9FAFB; border: 1.5px solid #C4C9D4;
    border-radius: 9px; color: #1A1A2E; font-size: 13px;
    font-family: 'DM Sans', sans-serif; transition: all .2s; font-weight: 500;
    box-sizing: border-box;
}
.form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: var(--c-coral); background: #fff; box-shadow: 0 0 0 3px rgba(239,108,82,.14); }
.form-textarea { min-height: 90px; resize: vertical; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

/* Alert banner */
.alert-banner { display: flex; align-items: center; gap: 14px; background: rgba(239,108,82,.06); border: 1.5px solid rgba(239,108,82,.22); border-left: 4px solid var(--c-coral); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; }
.alert-title { font-weight: 800; font-size: 14px; color: #1A1A2E; }
.alert-desc  { font-size: 13px; color: #374151; margin-top: 2px; }

/* Status select */
.status-select { background: #F9FAFB; border: 1.5px solid #C4C9D4; color: #1A1A2E; border-radius: 8px; padding: 5px 10px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif; }
.status-select:focus { outline: none; border-color: var(--c-coral); box-shadow: 0 0 0 3px rgba(239,108,82,.12); }

/* Action buttons */
.action-btn { padding: 5px 12px; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid transparent; margin-left: 4px; transition: all .18s; font-family: 'DM Sans', sans-serif; }
.action-btn-blue   { background: rgba(239,108,82,.10); color: #C45030; border-color: rgba(239,108,82,.25); }
.action-btn-blue:hover { background: rgba(239,108,82,.18); }
.action-btn-yellow { background: rgba(245,158,11,.10); color: #B45309; border-color: rgba(245,158,11,.25); }
.action-btn-yellow:hover { background: rgba(245,158,11,.18); }
.row-actions { display: flex; flex-wrap: wrap; gap: 5px; align-items: center; }

/* Load bar */
.load-bar-wrap { display: flex; align-items: center; gap: 10px; }
.load-bar { flex: 1; height: 7px; background: rgba(0,0,0,.07); border-radius: 4px; overflow: hidden; }
.load-bar-fill { height: 100%; border-radius: 4px; transition: width .5s; }
.load-low    .load-bar-fill { background: linear-gradient(90deg, #EF6C52, #FF9A7B); }
.load-medium .load-bar-fill { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.load-high   .load-bar-fill { background: linear-gradient(90deg, #ef4444, #f87171); }

/* Report cards */
.report-card { background: #F8F4F2; border: 1px solid rgba(239,108,82,.12); border-radius: 12px; padding: 16px 18px; margin-bottom: 12px; }
.report-card:last-child { margin-bottom: 0; }
.report-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; gap: 10px; }
.report-card-title  { font-size: 14px; font-weight: 700; color: #1A1A2E; flex: 1; }
.report-card-meta   { font-size: 12px; color: #6B7280; margin-bottom: 6px; display: flex; gap: 14px; flex-wrap: wrap; }
.report-card-desc   { font-size: 13px; color: #374151; line-height: 1.6; white-space: pre-wrap; }
.report-empty       { text-align: center; padding: 50px 20px; color: #9CA3AF; font-size: 14px; }
.patient-report-info { display: flex; gap: 12px; align-items: center; padding: 12px 16px; background: rgba(239,108,82,.05); border-radius: 10px; margin-bottom: 16px; border: 1px solid rgba(239,108,82,.14); }
.pri-name { font-size: 16px; font-weight: 800; color: #1A1A2E; }
.pri-sub  { font-size: 12px; color: #6B7280; margin-top: 2px; }
.report-count-badge { display: inline-flex; align-items: center; justify-content: center; background: rgba(239,108,82,.12); color: #C45030; border: 1px solid rgba(239,108,82,.25); border-radius: 20px; font-size: 11px; font-weight: 700; padding: 2px 8px; margin-left: 6px; }

/* History chart */
.history-controls { display: flex; align-items: center; gap: 12px; padding: 16px 22px 0; flex-wrap: wrap; }
.history-controls label { font-size: 12px; font-weight: 700; color: #6B7280; }
.history-patient-select { flex: 1; min-width: 180px; max-width: 300px; padding: 9px 13px; border-radius: 9px; border: 1.5px solid #C4C9D4; background: #F9FAFB; color: #1A1A2E; font-size: 13px; font-family: 'DM Sans', sans-serif; font-weight: 500; }
.history-patient-select:focus { outline: none; border-color: var(--c-coral); box-shadow: 0 0 0 3px rgba(239,108,82,.12); }
.history-legend { display: flex; gap: 16px; font-size: 12px; color: #6B7280; padding: 8px 22px 12px; flex-wrap: wrap; font-weight: 500; }
.history-legend span { display: flex; align-items: center; gap: 5px; }
.history-legend span::before { content: ''; display: inline-block; width: 22px; height: 3px; border-radius: 2px; }
.legend-normal::before  { background: var(--c-coral); }
.legend-warning::before { background: #f59e0b; }
.legend-critical::before{ background: #ef4444; }
.no-history-msg { text-align: center; padding: 48px 20px; color: #9CA3AF; font-size: 14px; }

/* Charts 2-col grid */
.charts-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 22px; }

/* Empty state */
.empty-state { text-align: center; padding: 60px 20px; color: #9CA3AF; font-size: 14px; font-weight: 500; }
.empty-state i { font-size: 36px; margin-bottom: 14px; display: block; opacity: .35; }

/* Toast */
.toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 99999; display: flex; flex-direction: column; gap: 10px; }

/* ═══════════════════════════════════════════════════
   RESPONSIVE — complete breakpoints
═══════════════════════════════════════════════════ */
@media (max-width: 1200px) {
    .stats-grid-4     { grid-template-columns: repeat(2, 1fr); }
    .device-stats-grid{ grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 1024px) {
    .page-content { padding: 20px; }
    .topbar { padding: 0 20px; }
    .charts-grid-2 { grid-template-columns: 1fr; }
}
@media (max-width: 900px) {
    .stats-grid-3 { grid-template-columns: repeat(2, 1fr); }
    .sidebar {
        position: fixed !important; top: 0; left: 0;
        width: 280px; height: 100%;
        transform: translateX(-100%); z-index: 9998;
        box-shadow: none;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
    .sidebar.open { transform: translateX(0); box-shadow: 8px 0 40px rgba(0,0,0,.4); }
    .sidebar-close { display: flex !important; }
}
@media (max-width: 768px) {
    .page-content { padding: 14px; }
    .topbar { padding: 0 14px; }
    .stats-grid-4 { grid-template-columns: repeat(2, 1fr); }
    .device-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .section-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .table-toolbar { width: 100%; }
    .form-grid { grid-template-columns: 1fr; }
    .history-controls { flex-direction: column; align-items: flex-start; }
    .history-patient-select { max-width: 100%; width: 100%; }
    .modal-box { padding: 18px 16px; }
    .modal { margin: 0 8px; max-width: calc(100vw - 16px); }
    .table-scroll-hint { display: flex !important; }
    table { min-width: 460px; }
}
@media (max-width: 600px) {
    .stats-grid-4, .stats-grid-3 { grid-template-columns: repeat(2, 1fr); }
    .device-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .stat-value { font-size: 26px; }
    .charts-grid-2 { grid-template-columns: 1fr; }
}
@media (max-width: 400px) {
    .stats-grid-4, .stats-grid-3, .stats-grid-2 { grid-template-columns: 1fr; }
    .device-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .tab-btn { padding: 8px 10px 10px; font-size: 11px; }
}
@media (min-width: 901px) {
    .menu-toggle { display: none !important; }
}
</style>

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