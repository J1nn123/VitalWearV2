<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* ═══════════════════════════════════════════════════
   ROOT — Coral / Warm Palette  (matches reference UI)
   Primary coral  : #EF6C52
   Deep coral     : #E05A3A
   Warm orange    : #FF9A7B
   Dark sidebar bg: #1C1014
   Navy text      : #1E2450
   Light warm bg  : #FDF2EF
═══════════════════════════════════════════════════ */
:root {
    --c-coral      : #EF6C52;
    --c-coral-deep : #E05A3A;
    --c-coral-soft : #FF9A7B;
    --c-coral-glow : rgba(239,108,82,.35);
    --c-coral-muted: rgba(239,108,82,.12);
    --c-coral-border: rgba(239,108,82,.22);

    --c-navy       : #1E2450;
    --c-navy-light : #2D3478;

    --c-orange     : #FF7043;
    --c-orange-muted: rgba(255,112,67,.12);

    --sb-bg        : #1C1014;      /* sidebar background */
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

/* Warm grain overlay on sidebar */
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
.responder-avatar {
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep));
    color: #fff;
    box-shadow: 0 0 10px var(--c-coral-glow);
}
.user-info .user-name { font-size: 13px; font-weight: 600; color: var(--sb-text); }
.user-role-badge {
    font-size: 10px; padding: 2px 7px; border-radius: 20px;
    font-weight: 600; margin-top: 2px; display: inline-block;
}
.responder-badge {
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
    font-family: 'Outfit', sans-serif;
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
   SIMULATION PANEL (sidebar bottom)
═══════════════════════════════════════════════════ */
.sim-device-panel {
    margin: 8px;
    border-radius: 14px;
    background: linear-gradient(135deg, rgba(239,108,82,.07), rgba(255,112,67,.05));
    border: 1px solid var(--c-coral-border);
    padding: 14px;
    display: flex; flex-direction: column; gap: 10px;
}
.sim-device-panel .sim-title {
    font-size: 10px; font-weight: 700; color: var(--sb-muted);
    text-transform: uppercase; letter-spacing: .8px;
    display: flex; align-items: center; gap: 6px;
}
.sim-device-panel .sim-title i { color: var(--c-coral); }

/* Patient picker inside sim panel */
.sim-patient-select {
    width: 100%;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    color: var(--sb-text);
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    font-family: 'Outfit', sans-serif;
    transition: border-color .2s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23EF6C52' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 28px;
}
.sim-patient-select:focus { outline: none; border-color: var(--c-coral); }
.sim-patient-select option { background: #2A1A16; color: #F1ECE9; }
.sim-patient-select:disabled { opacity: .4; cursor: not-allowed; }

.sim-patient-label {
    font-size: 10px; font-weight: 600; color: var(--sb-muted);
    margin-bottom: -4px; letter-spacing: .4px;
}

.sim-status-row { display: flex; align-items: center; gap: 8px; }
.sim-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #3D2A26; transition: all .3s; flex-shrink: 0;
}
.sim-dot.running {
    background: var(--c-coral);
    box-shadow: 0 0 8px var(--c-coral-glow);
    animation: simPulse 1.2s ease-in-out infinite;
}
@keyframes simPulse { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.4);opacity:.7} }
.sim-status-text { font-size: 12px; color: var(--sb-muted); font-weight: 500; }
.sim-status-text.running { color: var(--c-coral); }

.btn-sim {
    width: 100%; padding: 9px 12px; border-radius: 8px;
    font-size: 12px; font-weight: 600; cursor: pointer; border: none;
    display: flex; align-items: center; justify-content: center;
    gap: 7px; transition: all .2s; font-family: 'Outfit', sans-serif;
}
.btn-sim-start {
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep));
    color: #fff;
    box-shadow: 0 2px 12px var(--c-coral-glow);
}
.btn-sim-start:hover { transform: translateY(-1px); box-shadow: 0 4px 18px var(--c-coral-glow); }
.btn-sim-stop {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff; box-shadow: 0 2px 10px rgba(239,68,68,.25);
}
.btn-sim-stop:hover { transform: translateY(-1px); }
.btn-sim:disabled { opacity: .45; cursor: not-allowed; transform: none !important; }

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
    font-family: 'Outfit', sans-serif;
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
.mob-nav-item.sim-btn-mob { color: var(--c-coral); }
.mob-nav-item.sim-btn-mob.running { color: #ef4444; }
.mob-nav-item.sim-btn-mob.running i { animation: simPulse 1.2s ease-in-out infinite; }
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
   SIMULATION MODAL — coral theme
═══════════════════════════════════════════════════ */
.sim-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.65); backdrop-filter: blur(4px);
    z-index: 99999; align-items: flex-end; justify-content: center;
}
.sim-modal-overlay.open { display: flex; }
.sim-modal {
    background: #241416; border-radius: 20px 20px 0 0;
    width: 100%; max-width: 480px;
    padding: 0 0 env(safe-area-inset-bottom,16px);
    animation: slideUp .3s ease;
    border-top: 1px solid rgba(239,108,82,.2);
}
@keyframes slideUp { from{transform:translateY(100%)} to{transform:translateY(0)} }
.sim-modal-handle { width: 36px; height: 4px; background: #3D2520; border-radius: 2px; margin: 12px auto 0; }
.sim-modal-header { padding: 14px 20px 0; display: flex; align-items: center; justify-content: space-between; }
.sim-modal-title { font-size: 16px; font-weight: 700; color: var(--sb-text); display: flex; align-items: center; gap: 8px; }
.sim-modal-title i { color: var(--c-coral); }
.sim-modal-close {
    background: rgba(255,255,255,.06); border: none; color: #94a3b8;
    width: 30px; height: 30px; border-radius: 50%; cursor: pointer;
    display: flex; align-items: center; justify-content: center; font-size: 13px;
    transition: all .2s;
}
.sim-modal-close:hover { background: rgba(239,68,68,.2); color: #f87171; }
.sim-modal-body { padding: 16px 20px; }

/* Patient picker inside modal */
.sim-modal-patient-wrap {
    margin-bottom: 12px;
}
.sim-modal-patient-label {
    font-size: 11px; font-weight: 700;
    color: var(--c-coral); text-transform: uppercase;
    letter-spacing: .6px; margin-bottom: 6px;
    display: flex; align-items: center; gap: 5px;
}
.sim-modal-patient-select {
    width: 100%;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(239,108,82,.3);
    color: var(--sb-text);
    border-radius: 10px;
    padding: 10px 36px 10px 14px;
    font-size: 13px; font-weight: 500;
    cursor: pointer;
    font-family: 'Outfit', sans-serif;
    transition: border-color .2s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23EF6C52' stroke-width='1.8' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
}
.sim-modal-patient-select:focus { outline: none; border-color: var(--c-coral); box-shadow: 0 0 0 3px var(--c-coral-muted); }
.sim-modal-patient-select option { background: #241416; color: #F1ECE9; }
.sim-modal-patient-select:disabled { opacity: .4; cursor: not-allowed; }

.sim-modal-status {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px; border-radius: 10px;
    background: rgba(255,255,255,.04); margin-bottom: 10px;
    border: 1px solid rgba(255,255,255,.06);
}
.sim-modal-status .label { font-size: 12px; color: var(--sb-muted); flex: 1; }
.sim-modal-status .value { font-size: 13px; font-weight: 600; color: var(--sb-text); }
.sim-modal-actions { display: flex; gap: 10px; margin-top: 14px; }
.btn-sim-lg {
    flex: 1; padding: 14px; border-radius: 12px;
    font-size: 14px; font-weight: 700; cursor: pointer; border: none;
    display: flex; align-items: center; justify-content: center;
    gap: 8px; transition: all .2s; font-family: 'Outfit', sans-serif;
}
.btn-sim-lg-start {
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep));
    color: #fff;
    box-shadow: 0 4px 18px var(--c-coral-glow);
}
.btn-sim-lg-start:hover { transform: translateY(-1px); }
.btn-sim-lg-stop  { background: linear-gradient(135deg,#ef4444,#dc2626); color:#fff; box-shadow:0 4px 16px rgba(239,68,68,.3); }
.btn-sim-lg-stop:hover  { transform: translateY(-1px); }
.btn-sim-lg:disabled { opacity: .45; cursor: not-allowed; transform: none !important; }

/* Live BPM preview inside modal */
.sim-live-bpm {
    display: none;
    align-items: center; justify-content: center; gap: 10px;
    padding: 12px; border-radius: 10px;
    background: var(--c-coral-muted);
    border: 1px solid var(--c-coral-border);
    margin-top: 10px;
}
.sim-live-bpm.visible { display: flex; }
.sim-live-bpm-num {
    font-size: 28px; font-weight: 800;
    color: var(--c-coral);
    font-variant-numeric: tabular-nums;
    line-height: 1;
}
.sim-live-bpm-label { font-size: 11px; color: var(--sb-muted); font-weight: 600; }
.sim-live-bpm-status { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px; }
.sim-live-bpm-status.normal  { background: rgba(16,185,129,.15); color: #10b981; }
.sim-live-bpm-status.warning { background: rgba(245,158,11,.15); color: #f59e0b; }
.sim-live-bpm-status.critical{ background: rgba(239,68,68,.15);  color: #ef4444; }

/* ═══════════════════════════════════════════════════
   RESCUER ALERT PANEL — warm tone
═══════════════════════════════════════════════════ */
.rescuer-alert-panel {
    position: fixed; top: 70px; right: 16px;
    width: 340px; max-height: 480px; overflow-y: auto;
    background: #ec1d1d; border: 1px solid var(--c-coral-border);
    border-radius: 14px; z-index: 5000;
    box-shadow: 0 8px 40px rgba(0,0,0,.5);
    display: none;
}
.rescuer-alert-panel.open { display: block; }
.rescuer-alert-panel-header {
    padding: 14px 16px 10px;
    display: flex; justify-content: space-between; align-items: center;
    border-bottom: 1px solid rgba(255,255,255,.07);
    position: sticky; top: 0; background: #1E1014; z-index: 1;
}
.rescuer-alert-panel-title { font-size: 13px; font-weight: 700; color: var(--c-coral-soft); display: flex; align-items: center; gap: 6px; }
.rescuer-alert-list { padding: 10px; display: flex; flex-direction: column; gap: 8px; }
.rescuer-alert-item {
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 10px; padding: 12px;
}
.rescuer-alert-item.has-location { border-left: 3px solid var(--c-coral); }
.rescuer-alert-item-name { font-size: 13px; font-weight: 700; color: #e2e8f0; }
.rescuer-alert-item-time { font-size: 11px; color: var(--sb-label); }
.rescuer-alert-item-from { font-size: 11px; color: var(--c-coral); margin-bottom: 5px; }
.rescuer-alert-item-msg  { font-size: 12px; color: var(--sb-muted); line-height: 1.5; }
.location-link {
    display: inline-flex; align-items: center; gap: 4px;
    margin-top: 8px; padding: 5px 10px;
    background: var(--c-coral-muted); border: 1px solid var(--c-coral-border);
    border-radius: 6px; color: var(--c-coral-soft); font-size: 11px; font-weight: 600;
    text-decoration: none; transition: all .2s;
}
.location-link:hover { background: rgba(239,108,82,.22); }

/* ═══════════════════════════════════════════════════
   DASHBOARD CORAL OVERRIDES
   Overrides CSS vars used across the main dashboard.
═══════════════════════════════════════════════════ */
body {
    --green        : var(--c-coral);
    --green-bg     : var(--c-coral-muted);
    --green-border : var(--c-coral-border);
    --indigo       : var(--c-navy-light);
    --indigo-border: rgba(45,52,120,.25);

    /* ── Text colors forced dark for light warm bg ── */
    --text-primary : #1E2450 !important;
    --text-secondary: #374151 !important;
    --text-muted   : #6B7280 !important;
    --text-label   : #9CA3AF !important;
    color: #1E2450;
}

/* ── Main content text ── */
.main-content,
.page-content,
.section-card,
.chart-card,
.stat-card {
    color: #1E2450 !important;
}

/* ── Table text ── */
table { color: #1E2450 !important; }
table td { color: #1E2450 !important; }
table td span { color: inherit; }
table thead th {
    color: #fff !important;          /* keep white on dark thead */
}

/* patient name bold cells */
table td span[style*="font-weight:600"],
table td span[style*="font-weight: 600"] {
    color: #1E2450 !important;
    font-weight: 700 !important;
}

/* muted cells (age, condition, updated) */
.td-muted { color: #6B7280 !important; }

/* rescuer name pill */
table td .td-muted span,
table td span[style*="font-size:12px"] { color: #6B7280 !important; }

/* ── Stat card numbers & labels ── */
.stat-value  { color: #1E2450 !important; }
.stat-label  { color: #6B7280 !important; }
.stat-sub    { color: #9CA3AF !important; }

/* ── Section headers ── */
.section-title    { color: #1E2450 !important; }
.section-subtitle { color: #6B7280 !important; }

/* ── Page title in topbar ── */
.page-title { color: #1E2450 !important; }

/* ── Topbar clock & buttons ── */
.topbar-time  { color: #6B7280 !important; }
.btn-ghost    { color: #374151 !important; }
.btn-ghost:hover { color: #1E2450 !important; }

/* ── Chart card titles ── */
.chart-card-title { color: #1E2450 !important; }

/* ── BPM unit text ── */
.bpm-unit { color: #9CA3AF !important; }

/* ── Alert banner text ── */
.alert-title { color: #1E2450 !important; }
.alert-desc  { color: #374151 !important; }

/* ── BPM reference legend ── */
.section-card span[style*="text-transform:uppercase"] { color: #6B7280 !important; }

/* Topbar live dot → coral pulse */
.live-dot {
    background: var(--c-coral) !important;
    box-shadow: 0 0 6px var(--c-coral-glow) !important;
}
.live-indicator { color: var(--c-coral) !important; font-weight: 700; }

/* Stat cards subtle warm bg */
.stat-card { background: #fff; }
.card-green .stat-icon.green { background: var(--c-coral-muted) !important; color: var(--c-coral) !important; }
.text-green { color: var(--c-coral) !important; }

/* Primary buttons → coral */
.btn-primary {
    background: linear-gradient(135deg, var(--c-coral), var(--c-coral-deep)) !important;
    color: #fff !important;
    box-shadow: 0 2px 12px var(--c-coral-glow) !important;
}
.btn-primary:hover { filter: brightness(1.08) !important; transform: translateY(-1px); }

/* Alert banner */
.alert-banner {
    border-left-color: var(--c-coral) !important;
    background: linear-gradient(135deg, rgba(173, 164, 162, 0.08), rgba(224,90,58,.05)) !important;
}

/* Badge normal → coral tint */
.badge-normal {
    background: var(--c-coral-muted) !important;
    color: var(--c-coral) !important;
    border-color: var(--c-coral-border) !important;
}

/* BPM bar & value colors */
.fill-normal  { background: var(--c-coral) !important; }
.bpm-normal   { color: var(--c-coral) !important; }

/* Page content light warm background */
.page-content { background: #FDF2EF !important; }
.main-content { background: #FDF2EF !important; }

/* Topbar warm */
.topbar {
    background: #dcd8d7 !important; 
    border-bottom: 1px solid rgba(239,108,82,.12) !important;
    box-shadow: 0 1px 12px rgba(239,108,82,.08) !important;
}

/* Section cards warm */
.section-card {
    background: #dfdada !important;
    border: 1px solid rgba(239,108,82,.1) !important;
}
.chart-card {
    background: #f1dfdf !important;
    border: 1px solid rgba(249, 242, 240, 0.1) !important;
}

/* Table row borders */
table tbody tr { border-bottom: 1px solid rgba(239,108,82,.07) !important; }
table tbody tr:hover { background: rgba(15, 15, 15, 0.03) !important; }

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
}
@media (min-width: 769px) {
    .mobile-bottom-nav { display: none !important; }
    .sim-modal-overlay { align-items: center; }
    .sim-modal {
        border-radius: 16px; max-width: 400px;
        border: 1px solid rgba(239,108,82,.15);
        border-top-color: rgba(239,108,82,.15);
    }
}
</style>

<!-- ── DESKTOP SIDEBAR ────────────────────────────────── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-icon">
                <img src="/VitalWearV2/assets/css/image.png" alt="VitalWear Logo" width="40" height="40">
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
        <div class="user-avatar responder-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
            <div class="user-role-badge responder-badge">
                <i class="fa-solid fa-shield-halved" style="font-size:9px;margin-right:3px"></i>Responder
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Monitoring</div>
        <a href="responder_dashboard.php" class="nav-item <?= $currentPage==='responder_dashboard.php'&&!isset($_GET['view'])?'active':'' ?>">
            <i class="fa-solid fa-heart-pulse"></i>
            <span>Live Monitor</span>
        </a>
        <a href="responder_dashboard.php?view=critical" class="nav-item <?= isset($_GET['view'])&&$_GET['view']==='critical'?'active':'' ?>">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Critical Alerts</span>
            <span class="nav-badge hidden" id="criticalNavBadge">0</span>
        </a>
        <a href="responder_dashboard.php?view=charts" class="nav-item <?= isset($_GET['view'])&&$_GET['view']==='charts'?'active':'' ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Analytics</span>
        </a>

        <div class="nav-label">Communication</div>
        <button onclick="toggleRescuerAlertPanel()" class="nav-item" style="width:100%;text-align:left;background:none;border:none;cursor:pointer;">
            <i class="fa-solid fa-location-dot" style="color:var(--c-coral)"></i>
            <span>Rescuer Alerts</span>
            <span class="nav-badge nav-badge-coral hidden" id="rescuerAlertBadge">0</span>
        </button>

        <div class="nav-label">Account</div>
        <a href="../api/login.php?action=logout" class="nav-item logout-item">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </nav>

    <!-- ── SIMULATION PANEL ── -->
    <div class="sim-device-panel">
        <div class="sim-title"><i class="fa-solid fa-microchip"></i> Wearable Simulator</div>

        <!-- Patient selector (populated from live table on open) -->
        <div>
            <div class="sim-patient-label">Simulate Patient</div>
            <select class="sim-patient-select" id="sidebarPatientSelect"
                    onchange="syncPatientSelect('sidebar')">
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

<!-- ── MOBILE BOTTOM NAVBAR ───────────────────────────── -->
<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <div class="mobile-bottom-nav-inner">
        <a href="responder_dashboard.php" class="mob-nav-item <?= $currentPage==='responder_dashboard.php'&&!isset($_GET['view'])?'active':'' ?>">
            <i class="fa-solid fa-heart-pulse"></i>
            <span>Monitor</span>
        </a>
        <a href="responder_dashboard.php?view=critical" class="mob-nav-item <?= isset($_GET['view'])&&$_GET['view']==='critical'?'active':'' ?>">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>Alerts</span>
            <span class="mob-nav-badge hidden" id="mobCriticalBadge">0</span>
        </a>
        <button class="mob-nav-item sim-btn-mob" id="mobSimBtn" onclick="openSimModal()">
            <i class="fa-solid fa-microchip" id="mobSimIcon"></i>
            <span id="mobSimLabel">Simulate</span>
        </button>
        <button class="mob-nav-item" onclick="toggleRescuerAlertPanel()" style="background:none;border:none;cursor:pointer;">
            <i class="fa-solid fa-location-dot" style="color:var(--c-coral)"></i>
            <span style="color:var(--c-coral)">Location</span>
            <span class="mob-nav-badge coral-badge hidden" id="mobRescuerAlertBadge">0</span>
        </button>
        <a href="../api/login.php?action=logout" class="mob-nav-item">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>

<!-- ── RESCUER ALERT PANEL ───────────────────────────── -->
<div class="rescuer-alert-panel" id="rescuerAlertPanel">
    <div class="rescuer-alert-panel-header">
        <div class="rescuer-alert-panel-title">
            <i class="fa-solid fa-location-dot"></i> Rescuer Location Alerts
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <button onclick="markRescuerAlertsRead()" style="background:none;border:none;color:var(--sb-muted);font-size:11px;cursor:pointer;font-weight:600">Mark read</button>
            <button onclick="toggleRescuerAlertPanel()" style="background:none;border:none;color:var(--sb-muted);cursor:pointer;font-size:16px;line-height:1">×</button>
        </div>
    </div>
    <div class="rescuer-alert-list" id="rescuerAlertList">
        <div style="text-align:center;padding:30px;color:var(--sb-muted);font-size:13px">Loading…</div>
    </div>
</div>

<!-- ── SIMULATION MODAL ───────────────────────────────── -->
<div class="sim-modal-overlay" id="simModalOverlay" onclick="handleSimModalBackdrop(event)">
    <div class="sim-modal">
        <div class="sim-modal-handle"></div>
        <div class="sim-modal-header">
            <div class="sim-modal-title">
                <i class="fa-solid fa-microchip"></i> Wearable Device Simulator
            </div>
            <button class="sim-modal-close" onclick="closeSimModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="sim-modal-body">

            <!-- Patient Picker -->
            <div class="sim-modal-patient-wrap">
                <div class="sim-modal-patient-label">
                    <i class="fa-solid fa-user-injured"></i> Select Patient to Simulate
                </div>
                <select class="sim-modal-patient-select" id="modalPatientSelect"
                        onchange="syncPatientSelect('modal')"
                        <?= '' /* disabled when running — set via JS */ ?>>
                    <option value="">— Choose one patient —</option>
                </select>
            </div>

            <!-- Status row -->
            <div class="sim-modal-status">
                <div class="sim-dot" id="modalSimDot" style="flex-shrink:0"></div>
                <div class="label">Simulation Status</div>
                <div class="value" id="modalSimStatus">Offline</div>
            </div>
            <div class="sim-modal-status" id="simIntervalRow" style="display:none">
                <i class="fa-regular fa-clock" style="color:var(--c-coral);font-size:14px"></i>
                <div class="label">Update Interval</div>
                <div class="value">Every 5 seconds</div>
            </div>
            <div class="sim-modal-status" id="simUpdatesRow" style="display:none">
                <i class="fa-solid fa-database" style="color:var(--c-coral);font-size:14px"></i>
                <div class="label">DB Updates Sent</div>
                <div class="value" id="simUpdateCount">0</div>
            </div>

            <!-- Live BPM preview -->
            <div class="sim-live-bpm" id="simLiveBpm">
                <div>
                    <div class="sim-live-bpm-num" id="simLiveBpmNum">—</div>
                    <div class="sim-live-bpm-label">BPM</div>
                </div>
                <div id="simLiveBpmStatus" class="sim-live-bpm-status normal">normal</div>
            </div>

            <!-- Actions -->
            <div class="sim-modal-actions">
                <button class="btn-sim-lg btn-sim-lg-start" id="modalStartBtn" onclick="startSimulation()">
                    <i class="fa-solid fa-play"></i> Start Device
                </button>
                <button class="btn-sim-lg btn-sim-lg-stop" id="modalStopBtn" onclick="stopSimulation()" style="display:none">
                    <i class="fa-solid fa-stop"></i> Stop & Save
                </button>
            </div>
            <p style="font-size:11px;color:var(--sb-muted);margin-top:12px;text-align:center;line-height:1.5">
                <i class="fa-solid fa-circle-info" style="margin-right:4px;color:var(--c-coral)"></i>
                Streams vitals for the <strong style="color:var(--c-coral-soft)">selected patient only</strong>.
                "Stop &amp; Save" writes the final state to the database.
            </p>
        </div>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════════
// PATIENT PICKER — populate both selects from the DOM
// ═══════════════════════════════════════════════════
let simPatientId   = null;   // currently selected patient ID
let simPatientHR   = 75;     // current simulated HR for that patient

function populatePatientSelects() {
    const rows = Array.from(document.querySelectorAll('#patientTableBody tr[id^="row-"]'));
    const opts = rows.map(r => {
        const id   = r.id.replace('row-', '');
        const name = r.cells[0]?.textContent?.trim() || `Patient ${id}`;
        return { id, name };
    });

    ['sidebarPatientSelect', 'modalPatientSelect'].forEach(selId => {
        const sel = document.getElementById(selId);
        if (!sel) return;
        const prev = sel.value;
        // keep placeholder
        sel.innerHTML = '<option value="">— Choose one patient —</option>';
        opts.forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.id; opt.textContent = o.name;
            if (String(o.id) === String(simPatientId)) opt.selected = true;
            sel.appendChild(opt);
        });
        // restore prev if still valid
        if (prev && opts.find(o => String(o.id) === String(prev))) sel.value = prev;
    });
}

/** Keep both selects in sync when one changes */
function syncPatientSelect(source) {
    const srcId  = source === 'sidebar' ? 'sidebarPatientSelect' : 'modalPatientSelect';
    const dstId  = source === 'sidebar' ? 'modalPatientSelect'   : 'sidebarPatientSelect';
    const val    = document.getElementById(srcId)?.value;
    simPatientId = val ? parseInt(val) : null;
    const dst    = document.getElementById(dstId);
    if (dst) dst.value = val || '';
}

// ═══════════════════════════════════════════════════
// MODAL OPEN / CLOSE
// ═══════════════════════════════════════════════════
function openSimModal() {
    populatePatientSelects();
    document.getElementById('simModalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeSimModal() {
    document.getElementById('simModalOverlay').classList.remove('open');
    document.body.style.overflow = '';
}
function handleSimModalBackdrop(e) {
    if (e.target === document.getElementById('simModalOverlay')) closeSimModal();
}

// ═══════════════════════════════════════════════════
// UPDATE ALL UI ELEMENTS
// ═══════════════════════════════════════════════════
function updateSimUI() {
    const dots      = document.querySelectorAll('.sim-dot');
    const texts     = document.querySelectorAll('.sim-status-text');
    const sbBtn     = document.getElementById('sidebarSimBtn');
    const mobBtn    = document.getElementById('mobSimBtn');
    const mobIcon   = document.getElementById('mobSimIcon');
    const mobLabel  = document.getElementById('mobSimLabel');
    const startBtn  = document.getElementById('modalStartBtn');
    const stopBtn   = document.getElementById('modalStopBtn');
    const modalStat = document.getElementById('modalSimStatus');
    const selects   = document.querySelectorAll('.sim-patient-select, .sim-modal-patient-select');

    dots.forEach(d  => simRunning ? d.classList.add('running')    : d.classList.remove('running'));
    texts.forEach(t => {
        t.textContent = simRunning ? 'Device Running…' : 'Device Offline';
        simRunning ? t.classList.add('running') : t.classList.remove('running');
    });
    // Lock selects while running
    selects.forEach(s => s.disabled = simRunning);

    if (sbBtn) {
        sbBtn.className = simRunning ? 'btn-sim btn-sim-stop' : 'btn-sim btn-sim-start';
        sbBtn.innerHTML = simRunning
            ? '<i class="fa-solid fa-stop"></i> Stop Simulation'
            : '<i class="fa-solid fa-play"></i> Start Simulation';
        sbBtn.onclick = simRunning ? stopSimulation : openSimModal;
    }
    if (mobBtn) {
        simRunning ? mobBtn.classList.add('running') : mobBtn.classList.remove('running');
        mobIcon.className  = simRunning ? 'fa-solid fa-stop' : 'fa-solid fa-microchip';
        mobLabel.textContent = simRunning ? 'Running' : 'Simulate';
        mobBtn.onclick = simRunning ? stopSimulation : openSimModal;
    }
    if (startBtn) startBtn.style.display = simRunning ? 'none' : 'flex';
    if (stopBtn)  stopBtn.style.display  = simRunning ? 'flex' : 'none';
    if (modalStat) {
        modalStat.textContent = simRunning ? '🟠 Running' : '⚫ Offline';
        modalStat.style.color = simRunning ? 'var(--c-coral)' : '#94a3b8';
    }
    document.getElementById('simIntervalRow').style.display = simRunning ? 'flex' : 'none';
    document.getElementById('simUpdatesRow').style.display  = simRunning ? 'flex' : 'none';

    const liveBpm = document.getElementById('simLiveBpm');
    if (liveBpm) simRunning ? liveBpm.classList.add('visible') : liveBpm.classList.remove('visible');
}

// ═══════════════════════════════════════════════════
// SIMULATION ENGINE — single patient
// ═══════════════════════════════════════════════════
let simRunning     = false;
let simInterval    = null;
let simUpdateCount = 0;

function generateRandomHR() {
    const r = Math.random();
    if (r < 0.70) return Math.floor(Math.random() * 40) + 60;   // normal
    if (r < 0.88) return Math.floor(Math.random() * 21) + 100;  // warning
    return Math.random() < 0.5
        ? Math.floor(Math.random() * 20) + 121  // critical high
        : Math.floor(Math.random() * 20) + 40;  // critical low
}
function getStatus(hr) {
    if (hr >= 60 && hr <= 99)   return 'normal';
    if (hr >= 100 && hr <= 120) return 'warning';
    return 'critical';
}

async function sendSimDataToDB(patientId, hr) {
    try {
        await fetch('../api/sim_update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ patients: [{ id: patientId, heart_rate: hr, status: getStatus(hr) }] })
        });
        simUpdateCount++;
        const el = document.getElementById('simUpdateCount');
        if (el) el.textContent = simUpdateCount;
    } catch(e) { console.warn('Sim DB write failed:', e); }
}

function updateLiveBpmUI(hr) {
    const st  = getStatus(hr);
    const num = document.getElementById('simLiveBpmNum');
    const lbl = document.getElementById('simLiveBpmStatus');
    if (num) num.textContent = hr;
    if (lbl) {
        lbl.className = `sim-live-bpm-status ${st}`;
        lbl.textContent = st;
    }
}

function startSimulation() {
    if (simRunning) return;

    // Ensure a patient is chosen
    if (!simPatientId) {
        // Flash the select
        const sel = document.getElementById('modalPatientSelect');
        if (sel) {
            sel.style.borderColor = '#ef4444';
            sel.style.boxShadow   = '0 0 0 3px rgba(239,68,68,.2)';
            setTimeout(() => { sel.style.borderColor = ''; sel.style.boxShadow = ''; }, 1500);
        }
        if (typeof showToast === 'function')
            showToast('No Patient Selected', 'Please choose a patient before starting.', 'error');
        return;
    }

    simRunning = true; simUpdateCount = 0;
    simPatientHR = generateRandomHR();
    updateSimUI();
    updateLiveBpmUI(simPatientHR);
    sendSimDataToDB(simPatientId, simPatientHR);

    simInterval = setInterval(() => {
        const delta  = Math.floor(Math.random() * 7) - 3;
        simPatientHR = Math.max(35, Math.min(160, simPatientHR + delta));
        updateLiveBpmUI(simPatientHR);
        sendSimDataToDB(simPatientId, simPatientHR);
    }, 5000);

    const patName = document.getElementById('modalPatientSelect')?.selectedOptions[0]?.text || 'Patient';
    if (typeof showToast === 'function')
        showToast('🟠 Simulation Started', `Streaming vitals for ${patName}.`, 'success');
}

async function stopSimulation() {
    if (!simRunning) return;
    simRunning = false;
    clearInterval(simInterval); simInterval = null;
    if (typeof fetchLiveData === 'function') await fetchLiveData();
    updateSimUI(); closeSimModal();
    if (typeof showToast === 'function')
        showToast('⏹ Simulation Stopped', 'Final data saved to database.', 'info');
}

// Initial UI state
document.getElementById('sidebarSimBtn').onclick = openSimModal;
updateSimUI();

// Refresh patient list on modal open
document.getElementById('simModalOverlay').addEventListener('transitionend', () => {
    if (document.getElementById('simModalOverlay').classList.contains('open')) populatePatientSelects();
});
// Also populate after first live data arrives
window.addEventListener('vitalwear:live-loaded', populatePatientSelects, { once: true });
// Fallback: populate after 2s
setTimeout(populatePatientSelects, 2000);

// ═══════════════════════════════════════════════════
// RESCUER ALERT INBOX
// ═══════════════════════════════════════════════════
let rescuerAlertPanelOpen = false;
let lastRescuerAlertCount = 0;

function toggleRescuerAlertPanel() {
    rescuerAlertPanelOpen = !rescuerAlertPanelOpen;
    document.getElementById('rescuerAlertPanel').classList.toggle('open', rescuerAlertPanelOpen);
    if (rescuerAlertPanelOpen) fetchRescuerAlerts();
}

async function fetchRescuerAlerts() {
    try {
        const res  = await fetch('../api/get_rescuer_alerts.php');
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        const unread = data.unread_count || 0;
        ['rescuerAlertBadge','mobRescuerAlertBadge'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = unread;
            el.classList.toggle('hidden', unread === 0);
        });

        if (unread > lastRescuerAlertCount && lastRescuerAlertCount >= 0) {
            const newest = data.alerts?.[0];
            if (newest && typeof showToast === 'function') {
                showToast('📍 Rescuer Alert', `${newest.rescuer_name}: ${newest.patient_name} — ${newest.message.substring(0,50)}`, 'warning', 7000);
            }
        }
        lastRescuerAlertCount = unread;

        const list = document.getElementById('rescuerAlertList');
        if (!list) return;
        if (!data.alerts || data.alerts.length === 0) {
            list.innerHTML = '<div style="text-align:center;padding:30px;color:var(--sb-muted);font-size:13px">No alerts from rescuers yet.</div>';
            return;
        }

        list.innerHTML = data.alerts.map(a => {
            const hasLoc  = a.latitude && a.longitude;
            const mapsUrl = hasLoc ? `https://www.google.com/maps?q=${a.latitude},${a.longitude}` : '';
            const timeAgo = formatAlertTime(a.created_at);
            return `
            <div class="rescuer-alert-item ${hasLoc ? 'has-location' : ''}">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:6px">
                    <div class="rescuer-alert-item-name">🧑‍⚕️ ${escapeHtml(a.patient_name)}</div>
                    <div class="rescuer-alert-item-time">${timeAgo}</div>
                </div>
                <div class="rescuer-alert-item-from">From: ${escapeHtml(a.rescuer_name)}</div>
                <div class="rescuer-alert-item-msg">${escapeHtml(a.message)}</div>
                ${hasLoc ? `
                <a href="${mapsUrl}" target="_blank" class="location-link">
                    <i class="fa-solid fa-map-pin"></i> View on Map
                    <span style="font-size:10px;color:var(--sb-muted);margin-left:4px">${parseFloat(a.latitude).toFixed(4)}, ${parseFloat(a.longitude).toFixed(4)}</span>
                </a>` : ''}
            </div>`;
        }).join('');
    } catch(e) { console.warn('Rescuer alert fetch failed:', e); }
}

async function markRescuerAlertsRead() {
    await fetch('../api/get_rescuer_alerts.php?mark_read=1');
    lastRescuerAlertCount = 0;
    fetchRescuerAlerts();
}

function formatAlertTime(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)    return 'just now';
    if (diff < 3600)  return Math.floor(diff/60)+'m ago';
    if (diff < 86400) return Math.floor(diff/3600)+'h ago';
    return new Date(dateStr).toLocaleDateString();
}
function escapeHtml(str) {
    return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

setInterval(fetchRescuerAlerts, 10000);
setTimeout(fetchRescuerAlerts, 1500);
</script>