<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">

<!-- Responder sidebar & dashboard styles (extracted from inline) -->
<link rel="stylesheet" href="../assets/responder_sidebar.css">

<!-- ═══════════════════════════════════════════════════════
     WEARABLE SIMULATOR STYLES — intentionally dark (sidebar widget)
     These are kept here because they are tightly coupled to the
     simulator modal JavaScript below.
═══════════════════════════════════════════════════════ -->
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

<!-- ── SIMULATOR MODAL ── -->
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
        ov.style.display=open?'block':'none';
        ov.style.backdropFilter='none';
        ov.style.webkitBackdropFilter='none';
        ov.style.filter='none';
    }
    const mc=document.querySelector('.main-content');
    if(mc){
        mc.style.filter='none';
        mc.style.backdropFilter='none';
        mc.style.webkitBackdropFilter='none';
    }
    document.body.style.overflow=open?'hidden':'';
}
</script>