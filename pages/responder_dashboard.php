<?php
/**
 * Responder Dashboard — Live Monitor + Patient CRUD + Rescuer Filter + Send Alert
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['responder', 'admin']);

$user = getCurrentUser();
$pdo  = getDB();

$rescuers = $pdo->query("
    SELECT u.id, u.full_name AS name FROM users u
    WHERE u.role = 'rescuer' ORDER BY u.full_name
")->fetchAll();

$patients = $pdo->query("
    SELECT p.*, h.heart_rate, h.status, h.timestamp AS last_updated,
           p.assigned_to AS rescuer_id, u.full_name AS rescuer_name
    FROM patients p
    LEFT JOIN heart_rate_logs h ON h.id = (
        SELECT id FROM heart_rate_logs WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    LEFT JOIN users u ON u.id = p.assigned_to
    ORDER BY CASE h.status WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END, p.name
")->fetchAll();

$summary = ['total' => 0, 'normal' => 0, 'warning' => 0, 'critical' => 0];
foreach ($patients as $p) {
    $summary['total']++;
    $s = $p['status'] ?? 'normal';
    if (isset($summary[$s])) $summary[$s]++;
}

$criticalList = array_filter($patients, fn($p) => ($p['status'] ?? '') === 'critical');
$chartLabels  = json_encode(array_column($patients, 'name'));
$chartData    = json_encode(array_map(fn($p) => $p['heart_rate'] ?? 0, $patients));
$chartColors  = json_encode(array_map(function($p) {
    $s = $p['status'] ?? 'normal';
    return $s === 'critical' ? '#ef4444' : ($s === 'warning' ? '#f59e0b' : '#10b981');
}, $patients));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Live Monitor — VitalWear</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>

    .main-content,
    .page-content,
    .layout > .main-content {
        filter: none !important;
        -webkit-filter: none !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    /* Sidebar overlay — dark curtain only, NO blur */
    .sidebar-overlay {
        display: none !important;
        position: fixed !important; inset: 0 !important;
        background: rgba(0,0,0,.50) !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        filter: none !important;
        z-index: 9997 !important;
        cursor: pointer;
    }
    .sidebar-overlay.open { display: block !important; }

    /* Sidebar itself — never blurs */
    .sidebar {
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        filter: none !important;
    }

    /* Mobile bottom nav — always on top and always clickable */
    .mobile-bottom-nav {
        z-index: 10000 !important;
        pointer-events: auto !important;
        position: fixed !important;
    }
    .mobile-bottom-nav * { pointer-events: auto !important; }

    /* ── Page-specific ── */
    .quick-msg-row { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
    .quick-msg-row .btn { font-size: 11px; padding: 5px 10px; }
    @media (max-width: 768px) {
        .quick-msg-row .btn { flex: 1 1 auto; text-align: center; justify-content: center; }
    }
    .form-error-box {
        background: rgba(239,68,68,.08);
        border: 1.5px solid rgba(239,68,68,.25);
        color: #ef4444; border-radius: 8px;
        padding: 10px 14px; font-size: 13px; margin-top: 10px;
    }
    </style>
</head>
<body>

<div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

<div class="layout">
    <?php include __DIR__ . '/../includes/sidebar_responder.php'; ?>

    <div class="main-content">

        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="page-title">Live Monitor</span>
            </div>
            <div class="topbar-right">
                <div class="live-indicator"><div class="live-dot"></div>LIVE</div>
                <button id="soundToggle" onclick="toggleSound()" class="btn btn-ghost btn-sm">
                    🔔 <span id="soundLabel">Sound On</span>
                </button>
                <span class="topbar-time" id="liveClock"></span>
            </div>
        </div>

        <div class="page-content">

            <div id="alertBanner" class="alert-banner"
                 style="display:<?= count($criticalList) > 0 ? 'flex' : 'none' ?>">
                <div class="alert-icon-wrap">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div class="alert-text">
                    <div class="alert-title">🚨 CRITICAL ALERT — Immediate Attention Required</div>
                    <div class="alert-desc" id="alertText">
                        <?php if (count($criticalList) > 0):
                            echo count($criticalList).' critical patient(s): '.implode(', ', array_column(array_values($criticalList),'name'));
                        endif; ?>
                    </div>
                </div>
            </div>

            <!-- STATS -->
            <div class="stats-grid stats-grid-4 mb-6">
                <div class="stat-card card-blue">
                    <div class="stat-card-header"><div class="stat-icon blue"><i class="fa-solid fa-users"></i></div></div>
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-value" id="statTotal"><?= $summary['total'] ?></div>
                    <div class="stat-sub">Being monitored</div>
                </div>
                <div class="stat-card card-green">
                    <div class="stat-card-header"><div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div></div>
                    <div class="stat-label">Normal</div>
                    <div class="stat-value text-green" id="statNormal"><?= $summary['normal'] ?></div>
                    <div class="stat-sub">60–99 BPM</div>
                </div>
                <div class="stat-card card-yellow">
                    <div class="stat-card-header"><div class="stat-icon yellow"><i class="fa-solid fa-triangle-exclamation"></i></div></div>
                    <div class="stat-label">Warning</div>
                    <div class="stat-value text-yellow" id="statWarning"><?= $summary['warning'] ?></div>
                    <div class="stat-sub">100–120 BPM</div>
                </div>
                <div class="stat-card card-red">
                    <div class="stat-card-header"><div class="stat-icon red"><i class="fa-solid fa-heart-crack"></i></div></div>
                    <div class="stat-label">Critical</div>
                    <div class="stat-value text-red" id="statCritical"><?= $summary['critical'] ?></div>
                    <div class="stat-sub">&lt;60 or &gt;120 BPM</div>
                </div>
            </div>

            <!-- CHARTS -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-card-title"><i class="fa-solid fa-chart-pie"></i> Status Distribution</div>
                    <div class="chart-wrapper" style="position:relative">
                        <canvas id="statusDoughnutChart"></canvas>
                        <div class="donut-center-label">
                            <span class="num" id="donutTotal"><?= $summary['total'] ?></span>
                            <span class="lbl">Patients</span>
                        </div>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-card-title"><i class="fa-solid fa-chart-bar"></i> Heart Rate by Patient</div>
                    <div class="chart-wrapper-tall"><canvas id="bpmBarChart"></canvas></div>
                </div>
                <div class="chart-card" style="grid-column:1/-1">
                    <div class="chart-card-title">
                        <i class="fa-solid fa-chart-line"></i>
                        Live BPM Trend — <span style="color:var(--indigo)">All Patients Avg</span>
                        <span style="margin-left:auto;font-size:11px;font-weight:500;color:var(--text-label)">Updates every 3s</span>
                    </div>
                    <div class="chart-wrapper-tall"><canvas id="trendLineChart"></canvas></div>
                </div>
            </div>

            <!-- PATIENT TABLE (desktop) -->
            <div class="section-card desktop-only" style="margin-top:4px">
                <div class="section-header">
                    <div>
                        <div class="section-title">Patient Heart Rate Monitor</div>
                        <div class="section-subtitle">Auto-updates every 3 seconds &nbsp;·&nbsp; Last: <span id="lastRefreshTime" style="color:var(--text-primary)">—</span></div>
                    </div>
                    <div class="table-toolbar">
                        <select id="rescuerFilter" class="filter-select" onchange="applyRescuerFilter(this.value)">
                            <option value="">All Rescuers</option>
                            <?php foreach ($rescuers as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button onclick="fetchLiveData()" class="btn btn-ghost btn-sm">
                            <i class="fa-solid fa-rotate-right"></i> Refresh
                        </button>
                        <button onclick="openAddPatientModal()" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-user-plus"></i> Add Patient
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Patient</th><th>Age</th><th>Condition</th><th>Rescuer</th><th>Heart Rate</th><th>Status</th><th>Updated</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="patientTableBody">
                        <?php foreach ($patients as $p):
                            $hr=$p['heart_rate']??0; $st=$p['status']??'normal';
                            $pct=min(100,max(0,(($hr-40)/120)*100));
                            $bpmClass=['normal'=>'bpm-normal','warning'=>'bpm-warning','critical'=>'bpm-critical'][$st]??'bpm-normal';
                            $fillClass=['normal'=>'fill-normal','warning'=>'fill-warning','critical'=>'fill-critical'][$st]??'fill-normal';
                            $badgeClass=['normal'=>'badge-normal','warning'=>'badge-warning','critical'=>'badge-critical'][$st]??'badge-normal';
                            $lastUpd=$p['last_updated']?date('H:i:s',strtotime($p['last_updated'])):'—';
                        ?>
                        <tr id="row-<?= $p['id'] ?>">
                            <td><span style="font-weight:600"><?= htmlspecialchars($p['name']) ?></span></td>
                            <td class="td-muted"><?= $p['age'] ?></td>
                            <td class="td-muted"><?= htmlspecialchars($p['medical_condition']??'—') ?></td>
                            <td class="td-muted">
                                <?php if ($p['rescuer_name']): ?>
                                <span style="display:flex;align-items:center;gap:5px;font-size:12px">
                                    <span style="width:7px;height:7px;border-radius:50%;background:var(--green);display:inline-block;flex-shrink:0"></span>
                                    <?= htmlspecialchars($p['rescuer_name']) ?>
                                </span>
                                <?php else: ?><span style="color:var(--text-label);font-size:12px">Unassigned</span><?php endif; ?>
                            </td>
                            <td>
                                <div class="bpm-cell">
                                    <div><span class="bpm-value <?= $bpmClass ?>"><?= $hr ?></span><span class="bpm-unit"> BPM</span></div>
                                    <div class="bpm-bar"><div class="bpm-bar-fill <?= $fillClass ?>" style="width:<?= $pct ?>%"></div></div>
                                </div>
                            </td>
                            <td><span class="badge <?= $badgeClass ?>"><?= ucfirst($st) ?></span></td>
                            <td class="td-muted"><?= $lastUpd ?></td>
                            <td>
                                <div style="display:flex;gap:5px">
                                    <button class="btn btn-ghost btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($p),ENT_QUOTES) ?>)" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn btn-ghost btn-sm" onclick="openAlertModal(<?= $p['id'] ?>,'<?= htmlspecialchars($p['name'],ENT_QUOTES) ?>',<?= $p['rescuer_id']??'null' ?>)" title="Alert" style="color:var(--indigo)"><i class="fa-solid fa-bell"></i></button>
                                    <button class="btn btn-ghost btn-sm" onclick="deletePatient(<?= $p['id'] ?>,'<?= htmlspecialchars($p['name'],ENT_QUOTES) ?>')" title="Delete" style="color:var(--red)"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PATIENT CARDS (mobile) -->
            <div class="patient-cards" id="patientCardsMobile">
            <?php foreach ($patients as $p):
                $hr=$p['heart_rate']??0; $st=$p['status']??'normal';
                $bpmClass=['normal'=>'bpm-normal','warning'=>'bpm-warning','critical'=>'bpm-critical'][$st]??'bpm-normal';
                $badgeClass=['normal'=>'badge-normal','warning'=>'badge-warning','critical'=>'badge-critical'][$st]??'badge-normal';
                $lastUpd=$p['last_updated']?date('H:i:s',strtotime($p['last_updated'])):'—';
            ?>
            <div class="patient-card card-<?= $st ?>" id="card-<?= $p['id'] ?>">
                <div class="patient-card-header">
                    <div class="patient-card-name"><?= htmlspecialchars($p['name']) ?></div>
                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($st) ?></span>
                </div>
                <div class="patient-card-body">
                    <div class="patient-card-stat">Heart Rate <span class="bpm-value <?= $bpmClass ?>" style="font-size:20px"><?= $hr ?> <small style="font-size:12px;font-weight:400;color:var(--text-muted)">BPM</small></span></div>
                    <div class="patient-card-stat">Condition <span><?= htmlspecialchars($p['medical_condition']??'—') ?></span></div>
                    <div class="patient-card-stat">Rescuer <span><?= htmlspecialchars($p['rescuer_name']??'Unassigned') ?></span></div>
                    <div class="patient-card-stat">Updated <span class="last-upd-<?= $p['id'] ?>"><?= $lastUpd ?></span></div>
                </div>
                <div style="display:flex;gap:8px;margin-top:12px">
                    <button class="btn btn-ghost btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($p),ENT_QUOTES) ?>)" style="flex:1;justify-content:center"><i class="fa-solid fa-pen"></i> Edit</button>
                    <button class="btn btn-ghost btn-sm" onclick="openAlertModal(<?= $p['id'] ?>,'<?= htmlspecialchars($p['name'],ENT_QUOTES) ?>',<?= $p['rescuer_id']??'null' ?>)" style="flex:1;color:var(--indigo);border-color:var(--indigo-border);justify-content:center"><i class="fa-solid fa-bell"></i> Alert</button>
                    <button class="btn btn-ghost btn-sm" onclick="deletePatient(<?= $p['id'] ?>,'<?= htmlspecialchars($p['name'],ENT_QUOTES) ?>')" style="color:var(--red);border-color:var(--red-border)"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
            </div>

            <!-- FAB -->
            <button onclick="openAddPatientModal()" id="mobileAddFab"
                    style="display:none;position:fixed;bottom:80px;right:16px;width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#EF6C52,#E05A3A);color:#fff;border:none;font-size:22px;cursor:pointer;box-shadow:0 4px 18px rgba(239,108,82,.4);z-index:9000;align-items:center;justify-content:center">
                <i class="fa-solid fa-plus"></i>
            </button>

            <!-- BPM LEGEND -->
            <div class="section-card" style="margin-top:4px">
                <div style="padding:14px 20px;display:flex;gap:20px;flex-wrap:wrap;align-items:center">
                    <span style="font-size:11px;font-weight:700;color:var(--text-label);text-transform:uppercase;letter-spacing:.5px">BPM Reference:</span>
                    <span class="badge badge-normal">● Normal: 60–99</span>
                    <span class="badge badge-warning">● Warning: 100–120</span>
                    <span class="badge badge-critical">● Critical: &lt;60 or &gt;120</span>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- MODAL: ADD PATIENT -->
<div class="modal-overlay" id="addPatientModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-user-plus" style="color:#EF6C52;margin-right:8px"></i>Add New Patient</div>
            <button class="modal-close" onclick="closeModal('addPatientModal')">×</button>
        </div>
        <div class="modal-body">
            <div class="form-grid-2">
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Full Name *</label>
                    <input type="text" id="addName" class="form-input" placeholder="e.g. Maria Santos">
                </div>
                <div class="form-group">
                    <label class="form-label">Age *</label>
                    <input type="number" id="addAge" class="form-input" placeholder="e.g. 45" min="1" max="130">
                </div>
                <div class="form-group">
                    <label class="form-label">Assign Rescuer</label>
                    <select id="addRescuer" class="form-select">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($rescuers as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Medical Condition</label>
                    <input type="text" id="addCondition" class="form-input" placeholder="e.g. Hypertension">
                </div>
            </div>
            <div id="addError" class="form-error-box" style="display:none"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('addPatientModal')">Cancel</button>
            <button class="btn btn-primary" onclick="submitAddPatient()"><i class="fa-solid fa-plus"></i> Add Patient</button>
        </div>
    </div>
</div>

<!-- MODAL: EDIT PATIENT -->
<div class="modal-overlay" id="editPatientModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-pen" style="color:#EF6C52;margin-right:8px"></i>Edit Patient</div>
            <button class="modal-close" onclick="closeModal('editPatientModal')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editId">
            <div class="form-grid-2">
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Full Name *</label>
                    <input type="text" id="editName" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Age *</label>
                    <input type="number" id="editAge" class="form-input" min="1" max="130">
                </div>
                <div class="form-group">
                    <label class="form-label">Assign Rescuer</label>
                    <select id="editRescuer" class="form-select">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($rescuers as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label class="form-label">Medical Condition</label>
                    <input type="text" id="editCondition" class="form-input">
                </div>
            </div>
            <div id="editError" class="form-error-box" style="display:none"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('editPatientModal')">Cancel</button>
            <button class="btn btn-primary" onclick="submitEditPatient()"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </div>
    </div>
</div>

<!-- MODAL: SEND ALERT -->
<div class="modal-overlay" id="alertModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-bell" style="color:#EF6C52;margin-right:8px"></i>Send Alert to Rescuer</div>
            <button class="modal-close" onclick="closeModal('alertModal')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="alertPatientId">
            <div class="alert-panel">
                <div class="alert-panel-title"><i class="fa-solid fa-user-injured"></i> Patient</div>
                <div id="alertPatientName" style="font-size:16px;font-weight:700;color:var(--text-primary)"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Send Alert To *</label>
                <select id="alertRescuerId" class="form-select">
                    <option value="">— Select Rescuer —</option>
                    <?php foreach ($rescuers as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Message *</label>
                <textarea id="alertMessage" class="form-textarea" placeholder="e.g. Patient is critical — BPM at 145. Please respond immediately."></textarea>
            </div>
            <div class="quick-msg-row">
                <button class="btn btn-ghost btn-sm" onclick="setAlertMsg('Patient is in critical condition. Immediate response required.')">🚨 Critical</button>
                <button class="btn btn-ghost btn-sm" onclick="setAlertMsg('Heart rate warning detected. Please check on patient.')">⚠️ Warning</button>
                <button class="btn btn-ghost btn-sm" onclick="setAlertMsg('Please provide status update on this patient.')">📋 Status Check</button>
            </div>
            <div id="alertError" class="form-error-box" style="display:none;margin-top:12px"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('alertModal')">Cancel</button>
            <button class="btn btn-primary" onclick="submitAlert()"><i class="fa-solid fa-paper-plane"></i> Send Alert</button>
        </div>
    </div>
</div>

<div id="toastContainer" class="toast-container"></div>

<!-- scripts.js FIRST — then our overrides below -->
<script src="../assets/js/scripts.js"></script>

<script>
/* ══════════════════════════════════════════════════════
   TOGGLE SIDEBAR — defined HERE, after scripts.js,
   so we permanently override whatever scripts.js set.
   This is the definitive, blur-free implementation.
══════════════════════════════════════════════════════ */
function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    if (!sb) return;

    const isOpen = sb.classList.toggle('open');

    // Overlay — show/hide, zero blur ever
    if (ov) {
        if (isOpen) {
            ov.style.display = 'block';
        } else {
            ov.style.display = 'none';
        }
        // Inline style wins over any CSS, !important or not
        ov.style.backdropFilter    = 'none';
        ov.style.webkitBackdropFilter = 'none';
        ov.style.filter            = 'none';
    }

    // Main content — strip any blur classes/styles scripts.js may add
    const mc = document.querySelector('.main-content');
    if (mc) {
        mc.style.filter               = 'none';
        mc.style.backdropFilter       = 'none';
        mc.style.webkitBackdropFilter = 'none';
        // Remove common blur classes used in various CSS frameworks/themes
        mc.classList.remove('blurred','blur','sidebar-blur','content-blur');
    }

    // Body scroll lock while open
    document.body.style.overflow = isOpen ? 'hidden' : '';
}

// Also strip blur on DOMContentLoaded in case scripts.js set it on load
document.addEventListener('DOMContentLoaded', () => {
    const mc = document.querySelector('.main-content');
    if (mc) {
        mc.style.filter               = 'none';
        mc.style.backdropFilter       = 'none';
        mc.style.webkitBackdropFilter = 'none';
    }
});

/* ══════════════════════════════════════════════════════
   CHARTS
══════════════════════════════════════════════════════ */
Chart.defaults.color       = '#64748b';
Chart.defaults.borderColor = 'rgba(30,36,80,.06)';
Chart.defaults.font.family = "'DM Sans', sans-serif";

const statusDoughnut = new Chart(document.getElementById('statusDoughnutChart'), {
    type: 'doughnut',
    data: {
        labels: ['Normal','Warning','Critical'],
        datasets: [{
            data: [<?= $summary['normal'] ?>,<?= $summary['warning'] ?>,<?= $summary['critical'] ?>],
            backgroundColor: ['#EF6C52','#f59e0b','#ef4444'],
            borderColor: 'transparent', borderWidth: 0, hoverOffset: 8
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '70%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 16, boxWidth: 10, boxHeight: 10, borderRadius: 5, useBorderRadius: true } },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} patient${ctx.parsed!==1?'s':''}` } }
        }
    }
});

const bpmBarChart = new Chart(document.getElementById('bpmBarChart'), {
    type: 'bar',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [{ label: 'Heart Rate (BPM)', data: <?= $chartData ?>, backgroundColor: <?= $chartColors ?>, borderRadius: 6, borderSkipped: false }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.y} BPM` } } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 }, maxRotation: 30 } },
            y: { min: 40, max: 160, ticks: { stepSize: 20 } }
        }
    },
    plugins: [{
        id: 'refLines',
        afterDraw(chart) {
            const { ctx, chartArea: { left, right }, scales: { y } } = chart;
            [{val:60,color:'rgba(239,108,82,.20)'},{val:100,color:'rgba(245,158,11,.25)'},{val:120,color:'rgba(239,68,68,.25)'}]
            .forEach(({val,color}) => {
                const yPos = y.getPixelForValue(val);
                ctx.save(); ctx.beginPath(); ctx.setLineDash([4,4]);
                ctx.strokeStyle=color; ctx.lineWidth=1;
                ctx.moveTo(left,yPos); ctx.lineTo(right,yPos); ctx.stroke(); ctx.restore();
            });
        }
    }]
});

const trendLabels = [], trendData = [];
const trendLineChart = new Chart(document.getElementById('trendLineChart'), {
    type: 'line',
    data: {
        labels: trendLabels,
        datasets: [{ label:'Avg BPM', data:trendData, borderColor:'#6366f1', backgroundColor:'rgba(99,102,241,0.07)', borderWidth:2.5, pointRadius:3, pointBackgroundColor:'#6366f1', tension:0.45, fill:true }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, animation: { duration: 400 },
        plugins: { legend:{ display:false }, tooltip:{ callbacks:{ label: ctx => ` Avg: ${ctx.parsed.y.toFixed(1)} BPM` } } },
        scales: {
            x: { grid:{ display:false }, ticks:{ font:{ size:10 }, maxTicksLimit:8 } },
            y: { min:40, max:160, ticks:{ stepSize:20 } }
        }
    }
});

function pushTrendPoint(patients) {
    if (!patients?.length) return;
    const avg = patients.reduce((s,p)=>s+(p.heart_rate||0),0)/patients.length;
    const now = new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit',second:'2-digit'});
    trendLabels.push(now); trendData.push(parseFloat(avg.toFixed(1)));
    if (trendLabels.length > 20) { trendLabels.shift(); trendData.shift(); }
    trendLineChart.update('quiet');
}
function updateBarChart(patients) {
    if (!patients?.length) return;
    bpmBarChart.data.labels = patients.map(p=>p.name);
    bpmBarChart.data.datasets[0].data = patients.map(p=>p.heart_rate||0);
    bpmBarChart.data.datasets[0].backgroundColor = patients.map(p=>p.status==='critical'?'#ef4444':p.status==='warning'?'#f59e0b':'#EF6C52');
    bpmBarChart.update('quiet');
}
function updateDoughnut(s) {
    statusDoughnut.data.datasets[0].data=[s.normal,s.warning,s.critical];
    statusDoughnut.update('quiet');
    document.getElementById('donutTotal').textContent=s.total;
}

/* ══════════════════════════════════════════════════════
   LIVE POLLING
══════════════════════════════════════════════════════ */
let activeRescuerFilter = '';

async function fetchLiveData() {
    try {
        const url  = '../api/get_heart_rate.php' + (activeRescuerFilter ? `?rescuer_id=${activeRescuerFilter}` : '');
        const res  = await fetch(url);
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        window.dispatchEvent(new Event('vitalwear:live-loaded'));

        document.getElementById('statTotal').textContent    = data.summary.total;
        document.getElementById('statNormal').textContent   = data.summary.normal;
        document.getElementById('statWarning').textContent  = data.summary.warning;
        document.getElementById('statCritical').textContent = data.summary.critical;
        document.getElementById('lastRefreshTime').textContent = data.timestamp;

        updateDoughnut(data.summary);
        updateBarChart(data.patients);
        pushTrendPoint(data.patients);

        const criticals = [];
        data.patients.forEach(p => {
            const hr=p.heart_rate, st=p.status||'normal';
            const bpmCls  = {normal:'bpm-normal',warning:'bpm-warning',critical:'bpm-critical'}[st]||'bpm-normal';
            const fillCls = {normal:'fill-normal',warning:'fill-warning',critical:'fill-critical'}[st]||'fill-normal';
            const badgeCls= {normal:'badge-normal',warning:'badge-warning',critical:'badge-critical'}[st]||'badge-normal';
            const pct     = Math.min(100,Math.max(0,((hr-40)/120)*100));
            const lastUpd = p.last_updated ? new Date(p.last_updated).toLocaleTimeString() : '—';

            const row = document.getElementById('row-'+p.id);
            if (row) {
                row.cells[4].innerHTML=`<div class="bpm-cell"><div><span class="bpm-value ${bpmCls}">${hr}</span><span class="bpm-unit"> BPM</span></div><div class="bpm-bar"><div class="bpm-bar-fill ${fillCls}" style="width:${pct.toFixed(0)}%"></div></div></div>`;
                row.cells[5].innerHTML=`<span class="badge ${badgeCls}">${st.charAt(0).toUpperCase()+st.slice(1)}</span>`;
                row.cells[6].textContent=lastUpd;
            }
            const card = document.getElementById('card-'+p.id);
            if (card) {
                card.className=`patient-card card-${st}`;
                const badge=card.querySelector('.patient-card-header .badge');
                if(badge){badge.className=`badge ${badgeCls}`;badge.textContent=st.charAt(0).toUpperCase()+st.slice(1);}
                const bpmEl=card.querySelector('.bpm-value');
                if(bpmEl){bpmEl.className=`bpm-value ${bpmCls}`;bpmEl.innerHTML=`${hr} <small style="font-size:12px;font-weight:400;color:var(--text-muted)">BPM</small>`;}
                const updEl=card.querySelector('.last-upd-'+p.id);
                if(updEl)updEl.textContent=lastUpd;
            }
            if (st==='critical') criticals.push(p.name);
        });

        const banner=document.getElementById('alertBanner');
        if(criticals.length>0){banner.style.display='flex';document.getElementById('alertText').textContent=criticals.length+' critical patient(s): '+criticals.join(', ');}
        else{banner.style.display='none';}

        if(typeof updateCriticalNavBadge==='function')updateCriticalNavBadge(data.summary.critical);

    } catch(err) { console.warn('Live update error:',err); }
}

function applyRescuerFilter(rescuerId) { activeRescuerFilter=rescuerId; fetchLiveData(); }

function updateCriticalNavBadge(count) {
    ['criticalNavBadge','mobCriticalBadge'].forEach(id=>{
        const el=document.getElementById(id); if(!el)return;
        el.textContent=count; el.classList.toggle('hidden',count===0);
    });
}

/* ══════════════════════════════════════════════════════
   MODALS
══════════════════════════════════════════════════════ */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(el=>{
    el.addEventListener('click',e=>{if(e.target===el)el.classList.remove('open');});
});

function openAddPatientModal() {
    ['addName','addAge','addCondition'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('addRescuer').value='';
    document.getElementById('addError').style.display='none';
    openModal('addPatientModal');
}
async function submitAddPatient() {
    const name=document.getElementById('addName').value.trim();
    const age=document.getElementById('addAge').value;
    const condition=document.getElementById('addCondition').value.trim();
    const rescuerId=document.getElementById('addRescuer').value;
    const errEl=document.getElementById('addError');
    if(!name||!age){showErr(errEl,'Name and age are required.');return;}
    try {
        const res=await fetch('../api/patient_crud.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({name,age:parseInt(age),condition,rescuer_id:rescuerId?parseInt(rescuerId):null})});
        const data=await res.json();
        if(data.success){closeModal('addPatientModal');if(typeof showToast==='function')showToast('Patient Added','Patient added successfully.','success');fetchLiveData();}
        else showErr(errEl,data.message);
    } catch(e){showErr(errEl,'Network error. Please try again.');}
}

function openEditModal(patient) {
    document.getElementById('editId').value=patient.id;
    document.getElementById('editName').value=patient.name;
    document.getElementById('editAge').value=patient.age;
    document.getElementById('editCondition').value=patient.medical_condition||'';
    document.getElementById('editRescuer').value=patient.rescuer_id||'';
    document.getElementById('editError').style.display='none';
    openModal('editPatientModal');
}
async function submitEditPatient() {
    const id=document.getElementById('editId').value;
    const name=document.getElementById('editName').value.trim();
    const age=document.getElementById('editAge').value;
    const condition=document.getElementById('editCondition').value.trim();
    const rescuerId=document.getElementById('editRescuer').value;
    const errEl=document.getElementById('editError');
    if(!name||!age){showErr(errEl,'Name and age are required.');return;}
    try {
        const res=await fetch('../api/patient_crud.php',{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:parseInt(id),name,age:parseInt(age),condition,rescuer_id:rescuerId?parseInt(rescuerId):null})});
        const data=await res.json();
        if(data.success){closeModal('editPatientModal');if(typeof showToast==='function')showToast('Patient Updated','Changes saved successfully.','success');fetchLiveData();}
        else showErr(errEl,data.message);
    } catch(e){showErr(errEl,'Network error. Please try again.');}
}

async function deletePatient(id,name) {
    if(!confirm(`Delete patient "${name}"? This cannot be undone.`))return;
    try {
        const res=await fetch('../api/patient_crud.php',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
        const data=await res.json();
        if(data.success){
            document.getElementById('row-'+id)?.remove();
            document.getElementById('card-'+id)?.remove();
            if(typeof showToast==='function')showToast('Deleted',`${name} has been removed.`,'success');
            fetchLiveData();
        } else if(typeof showToast==='function') showToast('Error',data.message,'error');
    } catch(e){if(typeof showToast==='function')showToast('Error','Network error.','error');}
}

function openAlertModal(patientId,patientName,assignedRescuerId) {
    document.getElementById('alertPatientId').value=patientId;
    document.getElementById('alertPatientName').textContent=patientName;
    document.getElementById('alertMessage').value='';
    document.getElementById('alertError').style.display='none';
    document.getElementById('alertRescuerId').value=assignedRescuerId||'';
    openModal('alertModal');
}
function setAlertMsg(msg){document.getElementById('alertMessage').value=msg;}
async function submitAlert() {
    const patientId=document.getElementById('alertPatientId').value;
    const rescuerId=document.getElementById('alertRescuerId').value;
    const message=document.getElementById('alertMessage').value.trim();
    const errEl=document.getElementById('alertError');
    if(!rescuerId){showErr(errEl,'Please select a rescuer.');return;}
    if(!message){showErr(errEl,'Please enter a message.');return;}
    try {
        const res=await fetch('../api/send_alert.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({patient_id:parseInt(patientId),rescuer_id:parseInt(rescuerId),message})});
        const data=await res.json();
        if(data.success){closeModal('alertModal');if(typeof showToast==='function')showToast('Alert Sent',data.message,'success');}
        else showErr(errEl,data.message);
    } catch(e){showErr(errEl,'Network error. Please try again.');}
}

function showErr(el,msg){el.textContent=msg;el.style.display='block';}

// FAB
(function(){
    function checkFab(){const fab=document.getElementById('mobileAddFab');if(fab)fab.style.display=window.innerWidth<=768?'flex':'none';}
    checkFab(); window.addEventListener('resize',checkFab);
})();

// Clock
(function tick(){const el=document.getElementById('liveClock');if(el)el.textContent=new Date().toLocaleTimeString();setTimeout(tick,1000);})();

setInterval(fetchLiveData,3000);
setTimeout(fetchLiveData,500);
</script>
</body>
</html>