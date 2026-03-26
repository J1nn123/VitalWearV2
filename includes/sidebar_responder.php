<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
/* ═══════════════════════════════════════════════════
   LAYOUT FIX — sidebar is sticky flex item (240px).
   Override styles.css margin-left so main content
   isn't double-offset.
═══════════════════════════════════════════════════ */
.main-content {
    margin-left: 0 !important;
}

/* ═══════════════════════════════════════════════════
   SIDEBAR — Dark slate, responder theme
═══════════════════════════════════════════════════ */
.sidebar {
    width: 240px;
    background: #0f172a;
    display: flex;
    flex-direction: column;
    height: 100vh;
    position: sticky;
    top: 0;
    overflow-y: auto;
    z-index: 200;
    flex-shrink: 0;
    transition: transform 0.3s ease;
    border-right: 1px solid rgba(255,255,255,0.06);
    box-shadow: 2px 0 20px rgba(0,0,0,0.2);
}
.sidebar-header {
    padding: 20px 16px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255,255,255,0.07);
}
.sidebar-logo { display: flex; align-items: center; gap: 10px; }
.logo-icon {
    width: 34px; height: 34px;
    background: linear-gradient(135deg,#10b981,#059669);
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 14px rgba(16,185,129,.35);
}
.logo-text { font-size: 17px; font-weight: 700; color: #f1f5f9; letter-spacing: -.3px; }

.sidebar-close {
    display: none;
    background: rgba(255,255,255,0.06);
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 6px 8px;
    border-radius: 6px;
    font-size: 16px;
    transition: all .2s;
}
.sidebar-close:hover { background: rgba(239,68,68,.15); color: #f87171; }

.sidebar-user {
    padding: 14px 16px;
    display: flex; align-items: center; gap: 10px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
}
.user-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; }
.responder-avatar { background: linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; }
.user-info .user-name { font-size: 13px; font-weight: 600; color: #e2e8f0; }
.user-role-badge { font-size: 10px; padding: 2px 7px; border-radius: 20px; font-weight: 600; margin-top: 2px; display: inline-block; }
.responder-badge { background: rgba(99,102,241,.2); color:#818cf8; border: 1px solid rgba(99,102,241,.25); }

.sidebar-nav { padding: 12px 8px; flex: 1; display: flex; flex-direction: column; gap: 2px; }
.nav-label {
    font-size: 10px; font-weight: 700; color: #334155;
    letter-spacing: 1px; text-transform: uppercase;
    padding: 10px 8px 4px;
}
.nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px; border-radius: 8px;
    color: #64748b; text-decoration: none;
    font-size: 13px; font-weight: 500;
    transition: all 0.18s; position: relative;
    cursor: pointer; background: none; border: none;
    width: 100%; text-align: left;
    font-family: 'Outfit', sans-serif;
}
.nav-item i { width: 18px; text-align: center; font-size: 14px; flex-shrink: 0; }
.nav-item:hover { background: rgba(255,255,255,0.05); color: #e2e8f0; }
.nav-item.active {
    background: rgba(16,185,129,.12);
    color: #10b981;
    border: 1px solid rgba(16,185,129,.15);
}
.nav-item.active i { color: #10b981; }
.nav-badge {
    margin-left: auto; background: #ef4444; color: #fff;
    font-size: 10px; font-weight: 700; padding: 1px 7px;
    border-radius: 10px; min-width: 18px; text-align: center;
}
.nav-badge.hidden { display: none; }
.nav-badge-purple { background: #6366f1 !important; }
.logout-item { color: #475569; }
.logout-item:hover { background: rgba(239,68,68,.08) !important; color: #f87171 !important; }

/* ═══════════════════════════════════════════════════
   SIMULATION PANEL (sidebar bottom)
═══════════════════════════════════════════════════ */
.sim-device-panel {
    margin: 8px;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(16,185,129,.06), rgba(99,102,241,.06));
    border: 1px solid rgba(16,185,129,.15);
    padding: 14px;
    display: flex; flex-direction: column; gap: 10px;
}
.sim-device-panel .sim-title {
    font-size: 10px; font-weight: 700; color: #475569;
    text-transform: uppercase; letter-spacing: .8px;
    display: flex; align-items: center; gap: 6px;
}
.sim-device-panel .sim-title i { color: #10b981; }
.sim-status-row { display: flex; align-items: center; gap: 8px; }
.sim-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #334155; transition: all .3s; flex-shrink: 0;
}
.sim-dot.running {
    background: #10b981;
    box-shadow: 0 0 6px rgba(16,185,129,.7);
    animation: simPulse 1.2s ease-in-out infinite;
}
@keyframes simPulse { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.4);opacity:.7} }
.sim-status-text { font-size: 12px; color: #475569; font-weight: 500; }
.sim-status-text.running { color: #10b981; }
.btn-sim {
    width: 100%; padding: 9px 12px; border-radius: 8px;
    font-size: 12px; font-weight: 600; cursor: pointer; border: none;
    display: flex; align-items: center; justify-content: center;
    gap: 7px; transition: all .2s; font-family: 'Outfit', sans-serif;
}
.btn-sim-start {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff; box-shadow: 0 2px 10px rgba(16,185,129,.25);
}
.btn-sim-start:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(16,185,129,.35); }
.btn-sim-stop {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff; box-shadow: 0 2px 10px rgba(239,68,68,.25);
}
.btn-sim-stop:hover { transform: translateY(-1px); }

/* ═══════════════════════════════════════════════════
   MOBILE BOTTOM NAVBAR
═══════════════════════════════════════════════════ */
.mobile-bottom-nav {
    display: none; position: fixed;
    bottom: 0; left: 0; right: 0;
    height: 64px;
    background: #0f172a;
    border-top: 1px solid rgba(255,255,255,0.06);
    z-index: 9999;
    padding-bottom: env(safe-area-inset-bottom, 0);
    box-shadow: 0 -4px 24px rgba(0,0,0,.4);
}
.mobile-bottom-nav-inner { display: flex; height: 100%; align-items: stretch; }
.mob-nav-item {
    flex: 1; display: flex; flex-direction: column; align-items: center;
    justify-content: center; gap: 3px; text-decoration: none;
    color: #334155; font-size: 10px; font-weight: 600;
    letter-spacing: .2px; padding: 6px 2px;
    transition: color .2s; position: relative;
    cursor: pointer; background: none; border: none;
    font-family: 'Outfit', sans-serif;
}
.mob-nav-item i { font-size: 18px; transition: all .2s; }
.mob-nav-item.active { color: #10b981; }
.mob-nav-item.active i { color: #10b981; }
.mob-nav-item.active::before {
    content: ''; position: absolute;
    top: 0; left: 20%; right: 20%; height: 2px;
    background: #10b981; border-radius: 0 0 3px 3px;
}
.mob-nav-item:hover { color: #64748b; }
.mob-nav-item.sim-btn-mob { color: #10b981; }
.mob-nav-item.sim-btn-mob.running { color: #ef4444; }
.mob-nav-item.sim-btn-mob.running i { animation: simPulse 1.2s ease-in-out infinite; }
.mob-nav-badge {
    position: absolute; top: 5px; right: calc(50% - 20px);
    background: #ef4444; color: #fff;
    font-size: 9px; font-weight: 700; min-width: 16px; height: 16px;
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
    padding: 0 3px; border: 1px solid #0f172a;
}
.mob-nav-badge.hidden { display: none; }
.mob-nav-badge.purple { background: #6366f1; }

/* ═══════════════════════════════════════════════════
   SIMULATION MODAL
═══════════════════════════════════════════════════ */
.sim-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.65); backdrop-filter: blur(4px);
    z-index: 99999; align-items: flex-end; justify-content: center;
}
.sim-modal-overlay.open { display: flex; }
.sim-modal {
    background: #1e293b; border-radius: 20px 20px 0 0;
    width: 100%; max-width: 480px;
    padding: 0 0 env(safe-area-inset-bottom,16px);
    animation: slideUp .3s ease;
    border-top: 1px solid rgba(255,255,255,0.08);
}
@keyframes slideUp { from{transform:translateY(100%)} to{transform:translateY(0)} }
.sim-modal-handle { width: 36px; height: 4px; background: #334155; border-radius: 2px; margin: 12px auto 0; }
.sim-modal-header { padding: 14px 20px 0; display: flex; align-items: center; justify-content: space-between; }
.sim-modal-title { font-size: 16px; font-weight: 700; color: #f1f5f9; display: flex; align-items: center; gap: 8px; }
.sim-modal-title i { color: #10b981; }
.sim-modal-close {
    background: rgba(255,255,255,.06); border: none; color: #94a3b8;
    width: 30px; height: 30px; border-radius: 50%; cursor: pointer;
    display: flex; align-items: center; justify-content: center; font-size: 13px;
    transition: all .2s;
}
.sim-modal-close:hover { background: rgba(239,68,68,.2); color: #f87171; }
.sim-modal-body { padding: 16px 20px; }
.sim-modal-status {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px; border-radius: 10px;
    background: rgba(255,255,255,.04); margin-bottom: 10px;
    border: 1px solid rgba(255,255,255,.06);
}
.sim-modal-status .label { font-size: 12px; color: #64748b; flex: 1; }
.sim-modal-status .value { font-size: 13px; font-weight: 600; color: #e2e8f0; }
.sim-modal-actions { display: flex; gap: 10px; margin-top: 14px; }
.btn-sim-lg {
    flex: 1; padding: 14px; border-radius: 12px;
    font-size: 14px; font-weight: 700; cursor: pointer; border: none;
    display: flex; align-items: center; justify-content: center;
    gap: 8px; transition: all .2s; font-family: 'Outfit', sans-serif;
}
.btn-sim-lg-start { background: linear-gradient(135deg,#10b981,#059669); color:#fff; box-shadow:0 4px 16px rgba(16,185,129,.3); }
.btn-sim-lg-start:hover { transform: translateY(-1px); }
.btn-sim-lg-stop  { background: linear-gradient(135deg,#ef4444,#dc2626); color:#fff; box-shadow:0 4px 16px rgba(239,68,68,.3); }
.btn-sim-lg-stop:hover  { transform: translateY(-1px); }

/* ═══════════════════════════════════════════════════
   RESCUER ALERT INBOX (dark)
═══════════════════════════════════════════════════ */
.rescuer-alert-panel {
    position: fixed; top: 70px; right: 16px;
    width: 340px; max-height: 480px; overflow-y: auto;
    background: #1e293b; border: 1px solid rgba(99,102,241,.25);
    border-radius: 14px; z-index: 5000;
    box-shadow: 0 8px 40px rgba(0,0,0,.5);
    display: none;
}
.rescuer-alert-panel.open { display: block; }
.rescuer-alert-panel-header {
    padding: 14px 16px 10px;
    display: flex; justify-content: space-between; align-items: center;
    border-bottom: 1px solid rgba(255,255,255,.07);
    position: sticky; top: 0; background: #1e293b; z-index: 1;
}
.rescuer-alert-panel-title { font-size: 13px; font-weight: 700; color: #818cf8; display: flex; align-items: center; gap: 6px; }
.rescuer-alert-list { padding: 10px; display: flex; flex-direction: column; gap: 8px; }
.rescuer-alert-item {
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 10px; padding: 12px;
}
.rescuer-alert-item.has-location { border-left: 3px solid #6366f1; }
.rescuer-alert-item-name { font-size: 13px; font-weight: 700; color: #e2e8f0; }
.rescuer-alert-item-time { font-size: 11px; color: #334155; }
.rescuer-alert-item-from { font-size: 11px; color: #6366f1; margin-bottom: 5px; }
.rescuer-alert-item-msg  { font-size: 12px; color: #64748b; line-height: 1.5; }
.location-link {
    display: inline-flex; align-items: center; gap: 4px;
    margin-top: 8px; padding: 5px 10px;
    background: rgba(99,102,241,.15); border: 1px solid rgba(99,102,241,.3);
    border-radius: 6px; color: #818cf8; font-size: 11px; font-weight: 600;
    text-decoration: none; transition: all .2s;
}
.location-link:hover { background: rgba(99,102,241,.3); }

/* ═══════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════ */
@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        top: 0; left: 0;
        width: 280px;
        height: 100%;
        transform: translateX(-100%);
        z-index: 9998;
    }
    .sidebar.open { transform: translateX(0); }
    .sidebar-close { display: flex !important; }
    .mobile-bottom-nav { display: flex; }
}

@media (min-width: 769px) {
    .mobile-bottom-nav { display: none !important; }
    .sim-modal-overlay { align-items: center; }
    .sim-modal {
        border-radius: 16px;
        max-width: 400px;
        border: 1px solid rgba(255,255,255,0.08);
        border-top-color: rgba(255,255,255,0.08);
    }
}
</style>

<!-- ── DESKTOP SIDEBAR ────────────────────────────────── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="logo-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" fill="#10b981" stroke="#10b981" stroke-width="1"/>
                </svg>
            </div>
            <span class="logo-text">VitalWear</span>
        </div>
        <button class="sidebar-close" onclick="toggleSidebar()">
            <i class="fa-solid fa-xmark"></i>
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
            <i class="fa-solid fa-location-dot" style="color:#6366f1"></i>
            <span>Rescuer Alerts</span>
            <span class="nav-badge nav-badge-purple hidden" id="rescuerAlertBadge">0</span>
        </button>

        <div class="nav-label">Account</div>
        <a href="../api/login.php?action=logout" class="nav-item logout-item">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </nav>

    <!-- SIMULATION DEVICE PANEL -->
    <div class="sim-device-panel">
        <div class="sim-title"><i class="fa-solid fa-microchip"></i> Wearable Simulator</div>
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
        <!-- CENTER SIM BUTTON -->
        <button class="mob-nav-item sim-btn-mob" id="mobSimBtn" onclick="openSimModal()">
            <i class="fa-solid fa-microchip" id="mobSimIcon"></i>
            <span id="mobSimLabel">Simulate</span>
        </button>
        <!-- Rescuer Alerts -->
        <button class="mob-nav-item" onclick="toggleRescuerAlertPanel()" style="background:none;border:none;cursor:pointer;">
            <i class="fa-solid fa-location-dot" style="color:#6366f1"></i>
            <span style="color:#6366f1">Location</span>
            <span class="mob-nav-badge purple hidden" id="mobRescuerAlertBadge">0</span>
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
            <button onclick="markRescuerAlertsRead()" style="background:none;border:none;color:#475569;font-size:11px;cursor:pointer;font-weight:600">Mark read</button>
            <button onclick="toggleRescuerAlertPanel()" style="background:none;border:none;color:#475569;cursor:pointer;font-size:16px;line-height:1">×</button>
        </div>
    </div>
    <div class="rescuer-alert-list" id="rescuerAlertList">
        <div style="text-align:center;padding:30px;color:#475569;font-size:13px">Loading...</div>
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
            <div class="sim-modal-status">
                <div class="sim-dot" id="modalSimDot" style="flex-shrink:0"></div>
                <div class="label">Simulation Status</div>
                <div class="value" id="modalSimStatus">Offline</div>
            </div>
            <div class="sim-modal-status" id="simIntervalRow" style="display:none">
                <i class="fa-regular fa-clock" style="color:#6366f1;font-size:14px"></i>
                <div class="label">Update Interval</div>
                <div class="value">Every 5 seconds</div>
            </div>
            <div class="sim-modal-status" id="simUpdatesRow" style="display:none">
                <i class="fa-solid fa-database" style="color:#10b981;font-size:14px"></i>
                <div class="label">DB Updates Sent</div>
                <div class="value" id="simUpdateCount">0</div>
            </div>
            <div class="sim-modal-actions">
                <button class="btn-sim-lg btn-sim-lg-start" id="modalStartBtn" onclick="startSimulation()">
                    <i class="fa-solid fa-play"></i> Start Device
                </button>
                <button class="btn-sim-lg btn-sim-lg-stop" id="modalStopBtn" onclick="stopSimulation()" style="display:none">
                    <i class="fa-solid fa-stop"></i> Stop & Save
                </button>
            </div>
            <p style="font-size:11px;color:#475569;margin-top:12px;text-align:center;line-height:1.5">
                <i class="fa-solid fa-circle-info" style="margin-right:4px;color:#6366f1"></i>
                Simulates a wearable heart rate device. "Stop & Save" writes the final state to the database.
            </p>
        </div>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════════
// SIMULATION ENGINE
// ═══════════════════════════════════════════════════
let simRunning = false;
let simInterval = null;
let simUpdateCount = 0;

function openSimModal() {
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

    dots.forEach(d  => simRunning ? d.classList.add('running')   : d.classList.remove('running'));
    texts.forEach(t => {
        t.textContent = simRunning ? 'Device Running…' : 'Device Offline';
        simRunning ? t.classList.add('running') : t.classList.remove('running');
    });

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
        modalStat.textContent = simRunning ? '🟢 Running' : '⚫ Offline';
        modalStat.style.color = simRunning ? '#10b981' : '#94a3b8';
    }
    document.getElementById('simIntervalRow').style.display = simRunning ? 'flex' : 'none';
    document.getElementById('simUpdatesRow').style.display  = simRunning ? 'flex' : 'none';
}

function generateRandomHR() {
    const r = Math.random();
    if (r < 0.70) return Math.floor(Math.random() * 40) + 60;
    if (r < 0.88) return Math.floor(Math.random() * 21) + 100;
    return Math.random() < 0.5
        ? Math.floor(Math.random() * 20) + 121
        : Math.floor(Math.random() * 20) + 40;
}
function getStatus(hr) {
    if (hr >= 60 && hr <= 99)   return 'normal';
    if (hr >= 100 && hr <= 120) return 'warning';
    return 'critical';
}

async function sendSimDataToDB(patients) {
    try {
        await fetch('../api/sim_update.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ patients: patients.map(p => ({
                id: p.id, heart_rate: p.sim_hr, status: getStatus(p.sim_hr)
            }))})
        });
        simUpdateCount++;
        const el = document.getElementById('simUpdateCount');
        if (el) el.textContent = simUpdateCount;
    } catch(e) { console.warn('Sim DB write failed:', e); }
}

function getPatientIds() {
    return Array.from(document.querySelectorAll('#patientTableBody tr[id^="row-"]'))
        .map(r => ({ id: parseInt(r.id.replace('row-','')) }));
}

function startSimulation() {
    if (simRunning) return;
    simRunning = true; simUpdateCount = 0;
    updateSimUI();
    const patients = getPatientIds();
    if (!patients.length) return;
    patients.forEach(p => p.sim_hr = generateRandomHR());
    sendSimDataToDB(patients);
    simInterval = setInterval(() => {
        patients.forEach(p => {
            const delta = Math.floor(Math.random() * 7) - 3;
            p.sim_hr = Math.max(35, Math.min(160, p.sim_hr + delta));
        });
        sendSimDataToDB(patients);
    }, 5000);
    if (typeof showToast === 'function') showToast('🟢 Simulation Started', 'Device data streaming live.', 'success');
}

async function stopSimulation() {
    if (!simRunning) return;
    simRunning = false;
    clearInterval(simInterval); simInterval = null;
    if (typeof fetchLiveData === 'function') await fetchLiveData();
    updateSimUI(); closeSimModal();
    if (typeof showToast === 'function') showToast('⏹ Simulation Stopped', 'Data saved to database.', 'info');
}

document.getElementById('sidebarSimBtn').onclick = openSimModal;
updateSimUI();

// ═══════════════════════════════════════════════════
// RESCUER → RESPONDER ALERT INBOX
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
            list.innerHTML = '<div style="text-align:center;padding:30px;color:#475569;font-size:13px">No alerts from rescuers yet.</div>';
            return;
        }

        list.innerHTML = data.alerts.map(a => {
            const hasLoc  = a.latitude && a.longitude;
            const mapsUrl = hasLoc ? `https://www.google.com/maps?q=${a.latitude},${a.longitude}` : '';
            const timeAgo = formatAlertTime(a.created_at);
            return `
            <div class="rescuer-alert-item ${hasLoc ? 'has-location' : ''}">
                <div class="rescuer-alert-item-top" style="display:flex;justify-content:space-between;align-items:start;margin-bottom:6px">
                    <div class="rescuer-alert-item-name">🧑‍⚕️ ${escapeHtml(a.patient_name)}</div>
                    <div class="rescuer-alert-item-time">${timeAgo}</div>
                </div>
                <div class="rescuer-alert-item-from">From: ${escapeHtml(a.rescuer_name)}</div>
                <div class="rescuer-alert-item-msg">${escapeHtml(a.message)}</div>
                ${hasLoc ? `
                <a href="${mapsUrl}" target="_blank" class="location-link">
                    <i class="fa-solid fa-map-pin"></i> View on Map
                    <span style="font-size:10px;color:#475569;margin-left:4px">${parseFloat(a.latitude).toFixed(4)}, ${parseFloat(a.longitude).toFixed(4)}</span>
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