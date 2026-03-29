<?php
/**
 * Manager Dashboard — Analytics & Reports + Device Management
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['manager','admin']);

$user = getCurrentUser();
$pdo  = getDB();
$tab  = $_GET['tab'] ?? 'overview';

// ─── Device / Patient Actions ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_device') {
        $label  = trim($_POST['device_label'] ?? '');
        $type   = trim($_POST['device_type']  ?? '');
        $serial = trim($_POST['serial_number'] ?? '');
        if ($label && $type) {
            $pdo->prepare("INSERT INTO devices (label,type,serial_number,serial_status) VALUES (?,?,?,'usable')")
                ->execute([$label,$type,$serial]);
        }
        header("Location: ?tab=devices"); exit;
    }
    if ($action === 'update_status') {
        $id=$_POST['device_id']??0; $status=$_POST['status']??'';
        $allowed=['usable','in-use','maintenance','disposable'];
        if ($id && in_array($status,$allowed)) {
            if ($status!=='in-use')
                $pdo->prepare("UPDATE devices SET serial_status=?,assigned_to_patient=NULL,assigned_to_user=NULL WHERE id=?")->execute([$status,$id]);
            else
                $pdo->prepare("UPDATE devices SET serial_status=? WHERE id=?")->execute([$status,$id]);
        }
        header("Location: ?tab=devices"); exit;
    }
    if ($action === 'assign_device') {
        $id=(int)($_POST['device_id']??0); $pid=(int)($_POST['patient_id']??0)?:null; $uid=(int)($_POST['user_id']??0)?:null;
        if ($id) $pdo->prepare("UPDATE devices SET serial_status='in-use',assigned_to_patient=?,assigned_to_user=? WHERE id=?")->execute([$pid,$uid,$id]);
        header("Location: ?tab=devices"); exit;
    }
    if ($action === 'unassign_device') {
        $id=(int)($_POST['device_id']??0);
        if ($id) $pdo->prepare("UPDATE devices SET serial_status='usable',assigned_to_patient=NULL,assigned_to_user=NULL WHERE id=?")->execute([$id]);
        header("Location: ?tab=devices"); exit;
    }
    if ($action === 'add_patient') {
        $nm=trim($_POST['name']??''); $age=(int)($_POST['age']??0); $cond=trim($_POST['condition']??''); $rid=(int)($_POST['rescuer_id']??0)?:null;
        if ($nm&&$age) $pdo->prepare("INSERT INTO patients (name,age,medical_condition,assigned_to) VALUES (?,?,?,?)")->execute([$nm,$age,$cond,$rid]);
        header("Location: ?tab=patients"); exit;
    }
    if ($action === 'edit_patient') {
        $id=(int)($_POST['patient_id']??0); $nm=trim($_POST['name']??''); $age=(int)($_POST['age']??0); $cond=trim($_POST['condition']??''); $rid=(int)($_POST['rescuer_id']??0)?:null;
        if ($id&&$nm&&$age) $pdo->prepare("UPDATE patients SET name=?,age=?,medical_condition=?,assigned_to=? WHERE id=?")->execute([$nm,$age,$cond,$rid,$id]);
        header("Location: ?tab=patients"); exit;
    }
}
if (isset($_GET['delete_patient'])) {
    $pdo->prepare("DELETE FROM patients WHERE id=?")->execute([(int)$_GET['delete_patient']]);
    header("Location: ?tab=patients"); exit;
}

// ─── Data ─────────────────────────────────────────────────────────────────────
$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$totalRescuers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='rescuer'")->fetchColumn();
$totalLogs     = $pdo->query("SELECT COUNT(*) FROM heart_rate_logs")->fetchColumn();
$alertToday    = $pdo->query("SELECT COUNT(*) FROM heart_rate_logs WHERE status='critical' AND DATE(timestamp)=CURDATE()")->fetchColumn();
$normalCnt     = (int)$pdo->query("SELECT COUNT(*) FROM heart_rate_logs WHERE status='normal'")->fetchColumn();
$warnCnt       = (int)$pdo->query("SELECT COUNT(*) FROM heart_rate_logs WHERE status='warning'")->fetchColumn();
$critCnt       = (int)$pdo->query("SELECT COUNT(*) FROM heart_rate_logs WHERE status='critical'")->fetchColumn();

$trendData = $pdo->query("SELECT p.name,AVG(h.heart_rate) AS avg_bpm,MAX(h.heart_rate) AS max_bpm,MIN(h.heart_rate) AS min_bpm,SUM(CASE WHEN h.status='critical' THEN 1 ELSE 0 END) AS critical_count FROM patients p JOIN heart_rate_logs h ON h.patient_id=p.id WHERE h.timestamp>=NOW()-INTERVAL 1 HOUR GROUP BY p.id,p.name ORDER BY critical_count DESC")->fetchAll();

$hourlyData = $pdo->query("SELECT DATE_FORMAT(timestamp,'%H:00') AS hour_label,ROUND(AVG(heart_rate),1) AS avg_bpm,SUM(CASE WHEN status='critical' THEN 1 ELSE 0 END) AS critical_count FROM heart_rate_logs WHERE timestamp>=NOW()-INTERVAL 12 HOUR GROUP BY DATE_FORMAT(timestamp,'%H:00') ORDER BY timestamp ASC")->fetchAll();

$rescuerPerf = $pdo->query("SELECT u.full_name,u.id,COUNT(DISTINCT p.id) AS patient_count,COALESCE((SELECT COUNT(*) FROM incident_reports ir WHERE ir.rescuer_id=u.id),0) AS report_count FROM users u LEFT JOIN patients p ON p.assigned_to=u.id WHERE u.role='rescuer' GROUP BY u.id,u.full_name")->fetchAll();

$patientList = $pdo->query("SELECT DISTINCT p.id,p.name FROM patients p JOIN heart_rate_logs h ON h.patient_id=p.id WHERE h.timestamp>=NOW()-INTERVAL 24 HOUR ORDER BY p.name")->fetchAll();

$patientHistoryMap = [];
if (!empty($patientList)) {
    $in = implode(',', array_map('intval', array_column($patientList,'id')));
    $histRows = $pdo->query("SELECT patient_id,DATE_FORMAT(timestamp,'%H:00') AS hour_label,ROUND(AVG(heart_rate),1) AS avg_bpm FROM heart_rate_logs WHERE patient_id IN ($in) AND timestamp>=NOW()-INTERVAL 24 HOUR GROUP BY patient_id,DATE_FORMAT(timestamp,'%H:00') ORDER BY timestamp ASC")->fetchAll();
    foreach ($histRows as $row) $patientHistoryMap[$row['patient_id']][] = ['hour'=>$row['hour_label'],'avg_bpm'=>(float)$row['avg_bpm']];
}

$allPatients = $pdo->query("SELECT p.*,u.full_name AS rescuer_name,(SELECT heart_rate FROM heart_rate_logs WHERE patient_id=p.id ORDER BY id DESC LIMIT 1) AS heart_rate,(SELECT status FROM heart_rate_logs WHERE patient_id=p.id ORDER BY id DESC LIMIT 1) AS hr_status FROM patients p LEFT JOIN users u ON u.id=p.assigned_to ORDER BY p.name")->fetchAll();

$devices = $pdo->query("SELECT d.*,p.name AS patient_name,u.full_name AS user_name FROM devices d LEFT JOIN patients p ON p.id=d.assigned_to_patient LEFT JOIN users u ON u.id=d.assigned_to_user ORDER BY d.serial_status ASC,d.label ASC")->fetchAll();
$deviceStats = ['usable'=>0,'in-use'=>0,'maintenance'=>0,'disposable'=>0];
foreach ($devices as $d) { if (isset($deviceStats[$d['serial_status']])) $deviceStats[$d['serial_status']]++; }

$patients = $pdo->query("SELECT id,name FROM patients ORDER BY name")->fetchAll();
$rescuers = $pdo->query("SELECT id,full_name FROM users WHERE role='rescuer' ORDER BY full_name")->fetchAll();

$incidentReports = $pdo->query("SELECT ir.*,p.name AS patient_name,p.age AS patient_age,u.full_name AS rescuer_name FROM incident_reports ir JOIN patients p ON p.id=ir.patient_id JOIN users u ON u.id=ir.rescuer_id ORDER BY ir.created_at DESC")->fetchAll();
$reportsByPatient = [];
foreach ($incidentReports as $ir) $reportsByPatient[$ir['patient_id']][] = $ir;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Manager Dashboard — VitalWear</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <style>
    /* ══════════════════════════════════════════════
       TAB CONTAINER — overflow:visible is the fix
       that makes "Devices" tab visible & scrollable
    ══════════════════════════════════════════════ */
    .tab-section-card {
        background: #fff;
        border-radius: 16px;
        border: 1.5px solid rgba(239,108,82,.20);
        box-shadow: 0 4px 28px rgba(30,36,80,.14), 0 1px 8px rgba(30,36,80,.09);
        overflow: visible;   /* ← MUST be visible, NOT hidden */
        margin-bottom: 24px;
    }

    /* Charts 2-col */
    .charts-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:22px; }
    @media (max-width:900px) { .charts-grid-2 { grid-template-columns:1fr; } }

    .stat-icon svg { width:20px; height:20px; }

    /* Empty state */
    .empty-state { text-align:center; padding:60px 20px; color:#9CA3AF; font-size:14px; }
    .empty-state i { font-size:36px; margin-bottom:14px; display:block; opacity:.35; }

    /* Scroll hint */
    .table-scroll-hint { display:none; font-size:11px; color:#9CA3AF; padding:8px 20px 0; align-items:center; gap:5px; }
    @media (max-width:768px) {
        .table-scroll-hint { display:flex !important; }
        .charts-grid-2 { grid-template-columns:1fr; }
        .chart-wrapper, .chart-wrapper-tall { height:200px !important; }
    }
    @media (max-width:600px) {
        .chart-wrapper, .chart-wrapper-tall { height:180px !important; }
    }
    </style>
</head>
<body>

<div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

<div class="layout">
    <?php include __DIR__ . '/../includes/sidebar_manager.php'; ?>

    <div class="main-content">

        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <span class="page-title">Analytics &amp; Reports</span>
            </div>
            <div class="topbar-right">
                <div class="live-indicator"><div class="live-dot"></div>LIVE</div>
                <span class="topbar-time" id="liveClock"></span>
            </div>
        </div>

        <div class="page-content">

            <!-- ══ TAB BAR — uses .tab-section-card (overflow:visible) ══ -->
            <div class="tab-section-card">
                <div class="tabs">
                    <button class="tab-btn <?= $tab==='overview'?'active':'' ?>" onclick="location.href='?tab=overview'">
                        <i class="fa-solid fa-chart-pie"></i>Overview
                    </button>
                    <button class="tab-btn <?= $tab==='trends'?'active':'' ?>" onclick="location.href='?tab=trends'">
                        <i class="fa-solid fa-chart-line"></i>Trends
                    </button>
                    <button class="tab-btn <?= $tab==='patients'?'active':'' ?>" onclick="location.href='?tab=patients'">
                        <i class="fa-solid fa-user-injured"></i>Patients
                    </button>
                    <button class="tab-btn <?= $tab==='rescuers'?'active':'' ?>" onclick="location.href='?tab=rescuers'">
                        <i class="fa-solid fa-people-group"></i><span class="tab-label-long">Rescuer</span> Performance
                    </button>
                    <button class="tab-btn <?= $tab==='devices'?'active':'' ?>" onclick="location.href='?tab=devices'">
                        <i class="fa-solid fa-microchip"></i>Devices
                    </button>
                </div>
            </div>

            <?php if ($tab === 'overview'): ?>
            <!-- ══ OVERVIEW ══ -->
            <div class="stats-grid stats-grid-4 mb-6">
                <div class="stat-card card-blue">
                    <div class="stat-card-header"><div class="stat-icon blue"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4" stroke-width="2"/></svg></div></div>
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-value"><?= $totalPatients ?></div>
                    <div class="stat-sub">Registered</div>
                </div>
                <div class="stat-card card-yellow">
                    <div class="stat-card-header"><div class="stat-icon yellow"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/></svg></div></div>
                    <div class="stat-label">Rescuers</div>
                    <div class="stat-value"><?= $totalRescuers ?></div>
                    <div class="stat-sub">Field operators</div>
                </div>
                <div class="stat-card card-red">
                    <div class="stat-card-header"><div class="stat-icon red"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg></div></div>
                    <div class="stat-label">Alerts Today</div>
                    <div class="stat-value text-red"><?= $alertToday ?></div>
                    <div class="stat-sub">Critical events</div>
                </div>
                <div class="stat-card card-purple">
                    <div class="stat-card-header"><div class="stat-icon purple"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div></div>
                    <div class="stat-label">Total Readings</div>
                    <div class="stat-value"><?= number_format($totalLogs) ?></div>
                    <div class="stat-sub">All-time logs</div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="fa-solid fa-chart-area" style="color:#EF6C52;margin-right:8px;font-size:13px"></i>Hourly Heart Rate Overview</div>
                        <div class="section-subtitle">Last 12 hours — avg BPM &amp; critical count</div>
                    </div>
                </div>
                <?php if (empty($hourlyData)): ?>
                <div class="empty-state"><i class="fa-solid fa-chart-line"></i>No data in the last 12 hours.</div>
                <?php else: ?>
                <div class="chart-container"><div class="chart-wrapper" style="height:280px"><canvas id="hourlyChart"></canvas></div></div>
                <?php endif; ?>
            </div>

            <?php $normalPct=$totalLogs>0?round($normalCnt/$totalLogs*100):0; $warnPct=$totalLogs>0?round($warnCnt/$totalLogs*100):0; $critPct=100-$normalPct-$warnPct; ?>
            <div class="stats-grid stats-grid-3">
                <div class="stat-card card-green" style="text-align:center">
                    <div class="stat-label">Normal Readings</div>
                    <div class="stat-value text-green"><?= $normalPct ?>%</div>
                    <div class="stat-sub">60–99 BPM · <?= number_format($normalCnt) ?> logs</div>
                </div>
                <div class="stat-card card-yellow" style="text-align:center">
                    <div class="stat-label">Warning Readings</div>
                    <div class="stat-value text-yellow"><?= $warnPct ?>%</div>
                    <div class="stat-sub">100–120 BPM · <?= number_format($warnCnt) ?> logs</div>
                </div>
                <div class="stat-card card-red" style="text-align:center">
                    <div class="stat-label">Critical Readings</div>
                    <div class="stat-value text-red"><?= $critPct ?>%</div>
                    <div class="stat-sub">&lt;60 or &gt;120 BPM · <?= number_format($critCnt) ?> logs</div>
                </div>
            </div>

            <?php elseif ($tab === 'trends'): ?>
            <!-- ══ TRENDS ══ -->
            <div class="charts-grid-2">
                <div class="chart-card">
                    <div class="chart-card-title"><i class="fa-solid fa-chart-bar" style="color:#EF6C52"></i> Patient BPM Summary (Last Hour)</div>
                    <?php if (empty($trendData)): ?><div class="empty-state"><i class="fa-solid fa-chart-bar"></i>No data in the last hour.</div>
                    <?php else: ?><div class="chart-container"><div class="chart-wrapper-tall" style="height:260px"><canvas id="patientBpmChart"></canvas></div></div><?php endif; ?>
                </div>
                <div class="chart-card">
                    <div class="chart-card-title"><i class="fa-solid fa-circle-half-stroke" style="color:#EF6C52"></i> Alert Distribution (All Time)</div>
                    <?php if (($normalCnt+$warnCnt+$critCnt)===0): ?><div class="empty-state"><i class="fa-solid fa-chart-pie"></i>No data available.</div>
                    <?php else: ?><div style="display:flex;align-items:center;justify-content:center;padding:20px;height:260px"><canvas id="alertPieChart" style="max-height:220px;max-width:220px"></canvas></div><?php endif; ?>
                </div>
            </div>

            <div class="section-card" style="margin-bottom:22px">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="fa-solid fa-clock-rotate-left" style="color:#EF6C52;margin-right:8px;font-size:13px"></i>Patient BPM History (Last 24 Hours)</div>
                        <div class="section-subtitle">Hourly average per patient</div>
                    </div>
                </div>
                <?php if (empty($patientList)): ?><div class="empty-state"><i class="fa-solid fa-chart-line"></i>No BPM history in the last 24 hours.</div>
                <?php else: ?>
                <div class="history-controls">
                    <label for="patientHistorySelect">Select Patient:</label>
                    <select id="patientHistorySelect" class="history-patient-select" onchange="updateHistoryChart(this.value)">
                        <?php foreach ($patientList as $pl): ?><option value="<?= $pl['id'] ?>"><?= htmlspecialchars($pl['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="history-legend">
                    <span class="legend-normal">Normal (60–99 BPM)</span>
                    <span class="legend-warning">Warning (100–120 BPM)</span>
                    <span class="legend-critical">Critical (&lt;60 or &gt;120 BPM)</span>
                </div>
                <div class="chart-container"><div class="chart-wrapper" style="height:280px"><canvas id="patientHistoryChart"></canvas></div></div>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <div class="section-title"><i class="fa-solid fa-table" style="color:#EF6C52;margin-right:8px;font-size:13px"></i>Patient BPM Detail (Last Hour)</div>
                </div>
                <?php if (empty($trendData)): ?><div class="empty-state"><i class="fa-solid fa-table"></i>No data in the last hour.</div>
                <?php else: ?>
                <div class="table-scroll-hint"><i class="fa-solid fa-left-right" style="font-size:10px"></i>Scroll to see all columns</div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Patient</th><th>Avg BPM</th><th>Min BPM</th><th>Max BPM</th><th>Critical Events</th></tr></thead>
                        <tbody>
                        <?php foreach ($trendData as $t):
                            $bc=$t['avg_bpm']<60||$t['avg_bpm']>120?'bpm-critical':($t['avg_bpm']>=100?'bpm-warning':'bpm-normal');
                        ?>
                        <tr>
                            <td style="font-weight:700"><?= htmlspecialchars($t['name']) ?></td>
                            <td><span class="bpm-value <?= $bc ?>"><?= round($t['avg_bpm']) ?></span></td>
                            <td><span style="color:#C45030;font-weight:700"><?= $t['min_bpm'] ?></span></td>
                            <td><span style="color:#B91C1C;font-weight:700"><?= $t['max_bpm'] ?></span></td>
                            <td><?php if ($t['critical_count']>0): ?><span class="badge badge-critical"><?= $t['critical_count'] ?> events</span><?php else: ?><span class="badge badge-normal">None</span><?php endif; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <?php elseif ($tab === 'patients'): ?>
            <!-- ══ PATIENTS ══ -->
            <div class="section-card">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="fa-solid fa-user-injured" style="color:#EF6C52;margin-right:8px;font-size:13px"></i>All Patients</div>
                        <div class="section-subtitle"><?= count($allPatients) ?> registered patients</div>
                    </div>
                    <div class="table-toolbar">
                        <button class="btn btn-primary btn-sm" onclick="openModal('addPatientModal')"><i class="fa-solid fa-plus"></i> Add Patient</button>
                    </div>
                </div>
                <div class="table-scroll-hint"><i class="fa-solid fa-left-right" style="font-size:10px"></i>Scroll to see all columns</div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Name</th><th>Age</th><th>Condition</th><th>Rescuer</th><th>Heart Rate</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($allPatients as $p):
                            $hr=$p['heart_rate']??'—'; $st=$p['hr_status']??'normal';
                            $bc=['normal'=>'badge-normal','warning'=>'badge-warning','critical'=>'badge-critical'][$st]??'badge-normal';
                            $bl=['normal'=>'Normal','warning'=>'Warning','critical'=>'Critical'][$st]??'Normal';
                            $bpmCls=['normal'=>'bpm-normal','warning'=>'bpm-warning','critical'=>'bpm-critical'][$st]??'bpm-normal';
                            $rptCount=isset($reportsByPatient[$p['id']])?count($reportsByPatient[$p['id']]):0;
                        ?>
                        <tr>
                            <td style="font-weight:700"><?= htmlspecialchars($p['name']) ?></td>
                            <td class="td-muted"><?= $p['age'] ?></td>
                            <td class="td-muted"><?= htmlspecialchars($p['medical_condition']??'—') ?></td>
                            <td class="td-muted"><?= htmlspecialchars($p['rescuer_name']??'Unassigned') ?></td>
                            <td>
                                <?php if (is_numeric($hr)): ?><span class="bpm-value <?= $bpmCls ?>"><?= $hr ?></span><span style="font-size:11px;color:#9CA3AF"> BPM</span>
                                <?php else: ?><span class="td-muted">—</span><?php endif; ?>
                            </td>
                            <td><span class="badge <?= $bc ?>"><?= $bl ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <button class="btn btn-ghost btn-sm" onclick="openPatientReports(<?= $p['id'] ?>,'<?= htmlspecialchars($p['name'],ENT_QUOTES) ?>',<?= $p['age'] ?>,'<?= htmlspecialchars($p['medical_condition']??'—',ENT_QUOTES) ?>')" style="color:#4F46E5;border-color:rgba(99,102,241,.25);background:rgba(99,102,241,.06)">
                                        <i class="fa-solid fa-clipboard-list"></i> Reports<?php if ($rptCount>0): ?><span class="report-count-badge"><?= $rptCount ?></span><?php endif; ?>
                                    </button>
                                    <button class="btn btn-ghost btn-sm" onclick="openEditPatient(<?= htmlspecialchars(json_encode($p)) ?>)"><i class="fa-solid fa-pen"></i> Edit</button>
                                    <a href="?tab=patients&delete_patient=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete <?= htmlspecialchars($p['name'],ENT_QUOTES) ?>?')"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php elseif ($tab === 'rescuers'): ?>
            <!-- ══ RESCUERS ══ -->
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title"><i class="fa-solid fa-people-group" style="color:#EF6C52;margin-right:8px;font-size:13px"></i>Rescuer Performance</div>
                </div>
                <div class="table-scroll-hint"><i class="fa-solid fa-left-right" style="font-size:10px"></i>Scroll to see all columns</div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Rescuer</th><th>Patients</th><th>Reports</th><th>Workload</th><th>Load Level</th></tr></thead>
                        <tbody>
                        <?php foreach ($rescuerPerf as $r):
                            $pct=min(100,($r['patient_count']/6)*100);
                            $load=$r['patient_count']>=4?'High':($r['patient_count']>=2?'Medium':'Low');
                            $lc=$r['patient_count']>=4?'badge-critical load-high':($r['patient_count']>=2?'badge-warning load-medium':'badge-normal load-low');
                        ?>
                        <tr>
                            <td style="font-weight:700"><?= htmlspecialchars($r['full_name']) ?></td>
                            <td><span style="font-size:22px;font-weight:800;color:#1A1A2E"><?= $r['patient_count'] ?></span></td>
                            <td><span style="font-size:14px;font-weight:700"><?= $r['report_count'] ?></span></td>
                            <td style="min-width:160px">
                                <div class="load-bar-wrap <?= explode(' ',$lc)[1] ?>">
                                    <div class="load-bar"><div class="load-bar-fill" style="width:<?= $pct ?>%"></div></div>
                                    <span style="font-size:11px;color:#9CA3AF;min-width:32px"><?= $r['patient_count'] ?>/6</span>
                                </div>
                            </td>
                            <td><span class="badge <?= explode(' ',$lc)[0] ?>"><?= $load ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php elseif ($tab === 'devices'): ?>
            <!-- ══ DEVICES ══ -->
            <div class="device-stats-grid">
                <div class="device-mini-card"><div class="dmc-label">Usable</div><div class="dmc-count" style="color:#065F46"><?= $deviceStats['usable'] ?></div><div class="dmc-sub">Ready to deploy</div></div>
                <div class="device-mini-card"><div class="dmc-label">In-Use</div><div class="dmc-count" style="color:#1E40AF"><?= $deviceStats['in-use'] ?></div><div class="dmc-sub">Currently assigned</div></div>
                <div class="device-mini-card"><div class="dmc-label">Maintenance</div><div class="dmc-count" style="color:#92400E"><?= $deviceStats['maintenance'] ?></div><div class="dmc-sub">Under repair</div></div>
                <div class="device-mini-card"><div class="dmc-label">Disposable</div><div class="dmc-count" style="color:#B91C1C"><?= $deviceStats['disposable'] ?></div><div class="dmc-sub">Decommissioned</div></div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="fa-solid fa-microchip" style="color:#EF6C52;margin-right:8px;font-size:13px"></i>All Devices</div>
                        <div class="section-subtitle"><?= count($devices) ?> devices registered</div>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addDeviceModal')"><i class="fa-solid fa-plus"></i> Add Device</button>
                </div>
                <div class="table-scroll-hint"><i class="fa-solid fa-left-right" style="font-size:10px"></i>Scroll to see all columns</div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>#</th><th>Label</th><th>Type</th><th>Serial No.</th><th>Status</th><th>Assigned To</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php if (empty($devices)): ?><tr><td colspan="7" style="text-align:center;color:#9CA3AF;padding:48px">No devices found.</td></tr><?php endif; ?>
                        <?php foreach ($devices as $i=>$d):
                            $at=$d['patient_name']?'👤 '.htmlspecialchars($d['patient_name']):($d['user_name']?'🚑 '.htmlspecialchars($d['user_name']):'—');
                        ?>
                        <tr>
                            <td class="td-muted"><?= $i+1 ?></td>
                            <td style="font-weight:700"><?= htmlspecialchars($d['label']) ?></td>
                            <td class="td-muted"><?= htmlspecialchars($d['type']) ?></td>
                            <td style="font-family:monospace;font-size:12px;color:#6B7280"><?= htmlspecialchars($d['serial_number']??'—') ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="device_id" value="<?= $d['id'] ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="usable"      <?= $d['serial_status']==='usable'?'selected':'' ?>>✅ Usable</option>
                                        <option value="in-use"      <?= $d['serial_status']==='in-use'?'selected':'' ?>>🔵 In-Use</option>
                                        <option value="maintenance" <?= $d['serial_status']==='maintenance'?'selected':'' ?>>🔧 Maintenance</option>
                                        <option value="disposable"  <?= $d['serial_status']==='disposable'?'selected':'' ?>>🗑️ Disposable</option>
                                    </select>
                                </form>
                            </td>
                            <td class="td-muted"><?= $at ?></td>
                            <td>
                                <div class="row-actions">
                                <?php if ($d['serial_status']!=='disposable'): ?>
                                    <?php if ($d['serial_status']!=='in-use'): ?>
                                        <button class="action-btn action-btn-blue" onclick="openAssignModal(<?= $d['id'] ?>,'<?= htmlspecialchars(addslashes($d['label'])) ?>')"><i class="fa-solid fa-link"></i> Assign</button>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="action" value="unassign_device">
                                            <input type="hidden" name="device_id" value="<?= $d['id'] ?>">
                                            <button type="submit" class="action-btn action-btn-yellow" onclick="return confirm('Unassign?')"><i class="fa-solid fa-link-slash"></i> Unassign</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#D1D5DB;font-size:12px">Decommissioned</span>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- MODAL: ADD DEVICE -->
<div class="modal-backdrop" id="addDeviceModal">
    <div class="modal-box">
        <div class="modal-title"><i class="fa-solid fa-microchip" style="color:#EF6C52;margin-right:8px"></i>Add New Device</div>
        <form method="POST">
            <input type="hidden" name="action" value="add_device">
            <div class="form-group"><label class="form-label">Device Label *</label><input type="text" name="device_label" class="form-input" placeholder="e.g. Heart Monitor Unit A1" required></div>
            <div class="form-group"><label class="form-label">Device Type *</label>
                <select name="device_type" class="form-input" required>
                    <option value="">— Select Type —</option>
                    <option>Heart Rate Monitor</option><option>Pulse Oximeter</option>
                    <option>AED</option><option>ECG Machine</option>
                    <option>BP Monitor</option><option>Wearable Sensor</option><option>Other</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Serial Number</label><input type="text" name="serial_number" class="form-input" placeholder="e.g. SN-2024-00123"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addDeviceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Device</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: ASSIGN DEVICE -->
<div class="modal-backdrop" id="assignDeviceModal">
    <div class="modal-box">
        <div class="modal-title"><i class="fa-solid fa-link" style="color:#EF6C52;margin-right:8px"></i>Assign Device: <span id="assignDeviceName" style="color:#EF6C52"></span></div>
        <form method="POST">
            <input type="hidden" name="action" value="assign_device">
            <input type="hidden" name="device_id" id="assignDeviceId">
            <p style="font-size:13px;color:#6B7280;margin-bottom:16px">Assign to a patient or a rescuer.</p>
            <div class="form-group"><label class="form-label">Patient</label>
                <select name="patient_id" class="form-input"><option value="">— No patient —</option>
                <?php foreach ($patients as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select>
            </div>
            <div class="form-group"><label class="form-label">— or — Rescuer</label>
                <select name="user_id" class="form-input"><option value="">— No rescuer —</option>
                <?php foreach ($rescuers as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option><?php endforeach; ?></select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" onclick="closeModal('assignDeviceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Confirm</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: ADD PATIENT -->
<div class="modal-overlay" id="addPatientModal">
    <div class="modal">
        <div class="modal-header"><div class="modal-title"><i class="fa-solid fa-user-plus" style="color:#EF6C52;margin-right:8px"></i>Add New Patient</div><button class="modal-close" onclick="closeModal('addPatientModal')">×</button></div>
        <form method="POST" action="?tab=patients"><input type="hidden" name="action" value="add_patient">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-input" placeholder="e.g. Maria Santos" required></div>
                    <div class="form-group"><label class="form-label">Age *</label><input type="number" name="age" min="1" max="120" class="form-input" placeholder="45" required></div>
                    <div class="form-group"><label class="form-label">Condition</label><input type="text" name="condition" class="form-input" placeholder="e.g. Hypertension"></div>
                    <div class="form-group" style="grid-column:1/-1"><label class="form-label">Assigned Rescuer</label>
                        <select name="rescuer_id" class="form-input"><option value="">None</option>
                        <?php foreach ($rescuers as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option><?php endforeach; ?></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addPatientModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Patient</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDIT PATIENT -->
<div class="modal-overlay" id="editPatientModal">
    <div class="modal">
        <div class="modal-header"><div class="modal-title"><i class="fa-solid fa-pen" style="color:#EF6C52;margin-right:8px"></i>Edit Patient</div><button class="modal-close" onclick="closeModal('editPatientModal')">×</button></div>
        <form method="POST" action="?tab=patients"><input type="hidden" name="action" value="edit_patient"><input type="hidden" name="patient_id" id="editPatientId">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1"><label class="form-label">Full Name *</label><input type="text" name="name" id="editPatientName" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Age *</label><input type="number" name="age" id="editPatientAge" min="1" max="120" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Condition</label><input type="text" name="condition" id="editPatientCondition" class="form-input"></div>
                    <div class="form-group" style="grid-column:1/-1"><label class="form-label">Assigned Rescuer</label>
                        <select name="rescuer_id" id="editPatientRescuer" class="form-input"><option value="">None</option>
                        <?php foreach ($rescuers as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option><?php endforeach; ?></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('editPatientModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: PATIENT REPORTS -->
<div class="modal-overlay" id="patientReportsModal">
    <div class="modal" style="max-width:680px;width:95vw">
        <div class="modal-header">
            <div><div class="modal-title"><i class="fa-solid fa-clipboard-list" style="color:#EF6C52;margin-right:8px"></i>Incident Reports</div><div id="prModalSub" style="font-size:12px;color:#9CA3AF;margin-top:3px"></div></div>
            <button class="modal-close" onclick="closeModal('patientReportsModal')">×</button>
        </div>
        <div class="modal-body" style="max-height:65vh;overflow-y:auto">
            <div class="patient-report-info">
                <div style="width:42px;height:42px;border-radius:50%;background:rgba(239,108,82,.10);border:1px solid rgba(239,108,82,.22);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">👤</div>
                <div><div class="pri-name" id="prPatientName">—</div><div class="pri-sub" id="prPatientMeta">—</div></div>
            </div>
            <div id="prReportsList"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('patientReportsModal')">Close</button></div>
    </div>
</div>

<script>const ALL_REPORTS = <?= json_encode($reportsByPatient) ?>;</script>
<div id="toastContainer" class="toast-container"></div>
<script src="../assets/js/scripts.js"></script>

<script>
/* ── Overrides AFTER scripts.js — these win ── */
(function tick(){ const el=document.getElementById('liveClock'); if(el) el.textContent=new Date().toLocaleTimeString(); setTimeout(tick,1000); })();

function toggleSidebar() {
    const sb=document.getElementById('sidebar'), ov=document.getElementById('sidebarOverlay');
    if (!sb) return;
    const isOpen=sb.classList.toggle('open');
    if (ov) { ov.classList.toggle('open',isOpen); ov.style.backdropFilter='none'; ov.style.webkitBackdropFilter='none'; ov.style.filter='none'; }
    const mc=document.querySelector('.main-content');
    if (mc) { mc.style.filter='none'; mc.style.backdropFilter='none'; mc.style.webkitBackdropFilter='none'; }
    document.body.style.overflow=isOpen?'hidden':'';
}

function openModal(id)  { const el=document.getElementById(id); if(!el)return; el.classList.add('open'); el.style.display='flex'; }
function closeModal(id) { const el=document.getElementById(id); if(!el)return; el.classList.remove('open'); el.style.display=''; }
document.querySelectorAll('.modal-backdrop,.modal-overlay').forEach(m=>m.addEventListener('click',e=>{ if(e.target===m) closeModal(m.id); }));

function openAssignModal(id,label) { document.getElementById('assignDeviceId').value=id; document.getElementById('assignDeviceName').textContent=label; openModal('assignDeviceModal'); }
function openEditPatient(p) { document.getElementById('editPatientId').value=p.id; document.getElementById('editPatientName').value=p.name; document.getElementById('editPatientAge').value=p.age; document.getElementById('editPatientCondition').value=p.medical_condition||''; document.getElementById('editPatientRescuer').value=p.assigned_to||''; openModal('editPatientModal'); }

function openPatientReports(patientId,patientName,patientAge,patientCondition) {
    document.getElementById('prPatientName').textContent=patientName;
    document.getElementById('prPatientMeta').textContent='Age: '+patientAge+' · Condition: '+(patientCondition||'—');
    const reports=ALL_REPORTS[patientId]||[];
    document.getElementById('prModalSub').textContent=reports.length+' report'+(reports.length!==1?'s':'')+' on file';
    const list=document.getElementById('prReportsList');
    const sevClass={low:'sev-low',medium:'sev-medium',high:'sev-high',critical:'sev-critical'};
    if(reports.length===0){ list.innerHTML='<div class="report-empty"><i class="fa-solid fa-inbox" style="font-size:32px;opacity:.25;display:block;margin-bottom:12px"></i>No incident reports found.</div>'; }
    else { list.innerHTML=reports.map(r=>{ const dt=new Date(r.created_at); const sc=sevClass[r.severity]||'sev-medium'; return '<div class="report-card"><div class="report-card-header"><div class="report-card-title">'+escHtml(r.incident_type)+'</div><span class="sev-badge '+sc+'">'+capFirst(r.severity)+'</span></div><div class="report-card-meta"><span>'+escHtml(r.rescuer_name)+'</span><span>'+dt.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})+'</span></div>'+(r.description?'<div class="report-card-desc">'+escHtml(r.description)+'</div>':'')+'</div>'; }).join(''); }
    openModal('patientReportsModal');
}
function escHtml(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function capFirst(s){return s?s.charAt(0).toUpperCase()+s.slice(1):'';}

Chart.defaults.color='#6B7280'; Chart.defaults.borderColor='rgba(30,36,80,.07)'; Chart.defaults.font.family="'DM Sans',sans-serif";

<?php if ($tab==='overview'&&count($hourlyData)>0): ?>
new Chart(document.getElementById('hourlyChart'),{type:'line',data:{labels:<?= json_encode(array_column($hourlyData,'hour_label')) ?>,datasets:[{label:'Avg Heart Rate (BPM)',data:<?= json_encode(array_column($hourlyData,'avg_bpm')) ?>,borderColor:'#EF6C52',backgroundColor:'rgba(239,108,82,0.09)',borderWidth:2.5,tension:0.4,fill:true,pointRadius:5,pointBackgroundColor:'#EF6C52',pointBorderColor:'#fff',pointBorderWidth:2},{label:'Critical Count',data:<?= json_encode(array_column($hourlyData,'critical_count')) ?>,borderColor:'#ef4444',backgroundColor:'rgba(239,68,68,0.07)',borderWidth:2,tension:0.4,fill:true,yAxisID:'y2',pointRadius:3}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{usePointStyle:true,padding:16}}},scales:{x:{grid:{color:'rgba(30,36,80,.06)'}},y:{grid:{color:'rgba(30,36,80,.06)'},title:{display:true,text:'BPM',color:'#9CA3AF'}},y2:{position:'right',grid:{display:false},title:{display:true,text:'Critical',color:'#9CA3AF'}}}}});
<?php endif; ?>
<?php if ($tab==='trends'&&count($trendData)>0): ?>
new Chart(document.getElementById('patientBpmChart'),{type:'bar',data:{labels:<?= json_encode(array_map(fn($t)=>substr($t['name'],0,14),$trendData)) ?>,datasets:[{label:'Avg BPM',data:<?= json_encode(array_map(fn($t)=>round($t['avg_bpm']),$trendData)) ?>,backgroundColor:<?= json_encode(array_map(fn($t)=>$t['avg_bpm']<60||$t['avg_bpm']>120?'rgba(239,68,68,0.80)':($t['avg_bpm']>=100?'rgba(245,158,11,0.80)':'rgba(239,108,82,0.80)'),$trendData)) ?>,borderRadius:8,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{font:{size:11},maxRotation:30}},y:{min:0,max:160}}}});
<?php endif; ?>
<?php if ($tab==='trends'&&($normalCnt+$warnCnt+$critCnt)>0): ?>
new Chart(document.getElementById('alertPieChart'),{type:'doughnut',data:{labels:['Normal','Warning','Critical'],datasets:[{data:[<?= (int)$normalCnt ?>,<?= (int)$warnCnt ?>,<?= (int)$critCnt ?>],backgroundColor:['#EF6C52','#f59e0b','#ef4444'],borderWidth:0,hoverOffset:10}]},options:{responsive:false,plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:16}}},cutout:'65%'}});
<?php endif; ?>
<?php if ($tab==='trends'&&!empty($patientList)): ?>
const patientHistoryData=<?= json_encode($patientHistoryMap) ?>;
function bpmColor(b){return b<60||b>120?'#ef4444':b>=100?'#f59e0b':'#EF6C52';}
let historyChart=null;
function updateHistoryChart(patientId){
    const rows=patientHistoryData[patientId]||[];
    const labels=rows.map(r=>r.hour), bpms=rows.map(r=>r.avg_bpm);
    const avg=bpms.length?bpms.reduce((a,b)=>a+b,0)/bpms.length:80;
    const lc=bpmColor(avg), pc=bpms.map(v=>bpmColor(v));
    if(historyChart){historyChart.data.labels=labels;historyChart.data.datasets[0].data=bpms;historyChart.data.datasets[0].borderColor=lc;historyChart.data.datasets[0].pointBackgroundColor=pc;historyChart.update();return;}
    historyChart=new Chart(document.getElementById('patientHistoryChart'),{type:'line',data:{labels,datasets:[{label:'Avg BPM',data:bpms,borderColor:lc,backgroundColor:'rgba(239,108,82,0.07)',borderWidth:2.5,tension:0.4,fill:true,pointRadius:5,pointHoverRadius:7,pointBackgroundColor:pc,pointBorderColor:'#fff',pointBorderWidth:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>{const v=ctx.parsed.y;const s=v<60||v>120?'🔴 Critical':v>=100?'🟡 Warning':'🟠 Normal';return ' '+v+' BPM — '+s;}}}},scales:{x:{grid:{color:'rgba(30,36,80,.06)'},title:{display:true,text:'Hour of Day',color:'#9CA3AF'}},y:{min:40,max:160,grid:{color:'rgba(30,36,80,.06)'},title:{display:true,text:'BPM',color:'#9CA3AF'}}}}});
}
(function(){const sel=document.getElementById('patientHistorySelect');if(sel)updateHistoryChart(sel.value);})();
<?php endif; ?>
</script>
</body>
</html>