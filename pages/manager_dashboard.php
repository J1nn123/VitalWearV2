<?php
/**
 * Manager Dashboard — Analytics & Reports + Device Management
 * + Incident Reports viewer (role-based, same as Admin)
 * + Full mobile-responsive UI
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['manager','admin']);

$user = getCurrentUser();
$pdo  = getDB();
$tab  = $_GET['tab'] ?? 'overview';

// ─── Device Management Actions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_device') {
        $label  = trim($_POST['device_label'] ?? '');
        $type   = trim($_POST['device_type']  ?? '');
        $serial = trim($_POST['serial_number'] ?? '');
        if ($label && $type) {
            $pdo->prepare("INSERT INTO devices (label, type, serial_number, serial_status) VALUES (?, ?, ?, 'usable')")
                ->execute([$label, $type, $serial]);
        }
        header("Location: ?tab=devices"); exit;
    }

    if ($action === 'update_status') {
        $id      = (int)($_POST['device_id'] ?? 0);
        $status  = $_POST['status'] ?? '';
        $allowed = ['usable','in-use','maintenance','disposable'];
        if ($id && in_array($status, $allowed)) {
            if ($status !== 'in-use') {
                $pdo->prepare("UPDATE devices SET serial_status=?, assigned_to_patient=NULL, assigned_to_user=NULL WHERE id=?")
                    ->execute([$status, $id]);
            } else {
                $pdo->prepare("UPDATE devices SET serial_status=? WHERE id=?")->execute([$status, $id]);
            }
        }
        header("Location: ?tab=devices"); exit;
    }

    if ($action === 'assign_device') {
        $id        = (int)($_POST['device_id']  ?? 0);
        $patientId = (int)($_POST['patient_id'] ?? 0) ?: null;
        $userId    = (int)($_POST['user_id']    ?? 0) ?: null;
        if ($id) {
            $pdo->prepare("UPDATE devices SET serial_status='in-use', assigned_to_patient=?, assigned_to_user=? WHERE id=?")
                ->execute([$patientId, $userId, $id]);
        }
        header("Location: ?tab=devices"); exit;
    }

    if ($action === 'unassign_device') {
        $id = (int)($_POST['device_id'] ?? 0);
        if ($id) {
            $pdo->prepare("UPDATE devices SET serial_status='usable', assigned_to_patient=NULL, assigned_to_user=NULL WHERE id=?")
                ->execute([$id]);
        }
        header("Location: ?tab=devices"); exit;
    }

    if ($action === 'add_patient') {
        $nm   = trim($_POST['name']        ?? '');
        $age  = (int)($_POST['age']        ?? 0);
        $cond = trim($_POST['condition']   ?? '');
        $rid  = (int)($_POST['rescuer_id'] ?? 0) ?: null;
        if ($nm && $age) {
            $pdo->prepare("INSERT INTO patients (name, age, medical_condition, assigned_to) VALUES (?, ?, ?, ?)")
                ->execute([$nm, $age, $cond, $rid]);
        }
        header("Location: ?tab=patients"); exit;
    }

    if ($action === 'edit_patient') {
        $id   = (int)($_POST['patient_id'] ?? 0);
        $nm   = trim($_POST['name']        ?? '');
        $age  = (int)($_POST['age']        ?? 0);
        $cond = trim($_POST['condition']   ?? '');
        $rid  = (int)($_POST['rescuer_id'] ?? 0) ?: null;
        if ($id && $nm && $age) {
            $pdo->prepare("UPDATE patients SET name=?, age=?, medical_condition=?, assigned_to=? WHERE id=?")
                ->execute([$nm, $age, $cond, $rid, $id]);
        }
        header("Location: ?tab=patients"); exit;
    }
}

if (isset($_GET['delete_patient'])) {
    $id = (int)$_GET['delete_patient'];
    $pdo->prepare("DELETE FROM patients WHERE id=?")->execute([$id]);
    header("Location: ?tab=patients"); exit;
}

// ─── Summary Stats ─────────────────────────────────────────────────────────────
$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$totalRescuers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='rescuer'")->fetchColumn();
$totalAlerts   = $pdo->query("SELECT COUNT(*) FROM heart_rate_logs WHERE status='critical'")->fetchColumn();
$totalLogs     = $pdo->query("SELECT COUNT(*) FROM heart_rate_logs")->fetchColumn();
$alertToday    = $pdo->query("SELECT COUNT(*) FROM heart_rate_logs WHERE status='critical' AND DATE(timestamp)=CURDATE()")->fetchColumn();

$normalCnt = (int)$pdo->query("SELECT COUNT(*) FROM heart_rate_logs WHERE status='normal'")->fetchColumn();
$warnCnt   = (int)$pdo->query("SELECT COUNT(*) FROM heart_rate_logs WHERE status='warning'")->fetchColumn();
$critCnt   = (int)$pdo->query("SELECT COUNT(*) FROM heart_rate_logs WHERE status='critical'")->fetchColumn();

$trendData = $pdo->query("
    SELECT p.name,
           AVG(h.heart_rate)  AS avg_bpm,
           MAX(h.heart_rate)  AS max_bpm,
           MIN(h.heart_rate)  AS min_bpm,
           SUM(CASE WHEN h.status='critical' THEN 1 ELSE 0 END) AS critical_count
    FROM patients p
    JOIN heart_rate_logs h ON h.patient_id = p.id
    WHERE h.timestamp >= NOW() - INTERVAL 1 HOUR
    GROUP BY p.id, p.name
    ORDER BY critical_count DESC
")->fetchAll();

$hourlyData = $pdo->query("
    SELECT DATE_FORMAT(timestamp,'%H:00')                                       AS hour_label,
           ROUND(AVG(heart_rate),1)                                             AS avg_bpm,
           SUM(CASE WHEN status='critical' THEN 1 ELSE 0 END)                  AS critical_count,
           SUM(CASE WHEN status='warning'  THEN 1 ELSE 0 END)                  AS warning_count,
           SUM(CASE WHEN status='normal'   THEN 1 ELSE 0 END)                  AS normal_count
    FROM heart_rate_logs
    WHERE timestamp >= NOW() - INTERVAL 12 HOUR
    GROUP BY DATE_FORMAT(timestamp,'%H:00')
    ORDER BY timestamp ASC
")->fetchAll();

$rescuerPerf = $pdo->query("
    SELECT u.full_name, u.id,
           COUNT(DISTINCT p.id)  AS patient_count,
           COALESCE((SELECT COUNT(*) FROM incident_reports ir WHERE ir.rescuer_id=u.id),0) AS report_count
    FROM users u
    LEFT JOIN patients p ON p.assigned_to = u.id
    WHERE u.role = 'rescuer'
    GROUP BY u.id, u.full_name
")->fetchAll();

$patientList = $pdo->query("
    SELECT DISTINCT p.id, p.name
    FROM patients p
    JOIN heart_rate_logs h ON h.patient_id = p.id
    WHERE h.timestamp >= NOW() - INTERVAL 24 HOUR
    ORDER BY p.name
")->fetchAll();

$patientHistoryMap = [];
if (!empty($patientList)) {
    $in = implode(',', array_map('intval', array_column($patientList,'id')));
    $histRows = $pdo->query("
        SELECT patient_id,
               DATE_FORMAT(timestamp,'%H:00')   AS hour_label,
               ROUND(AVG(heart_rate),1)         AS avg_bpm
        FROM heart_rate_logs
        WHERE patient_id IN ($in)
          AND timestamp >= NOW() - INTERVAL 24 HOUR
        GROUP BY patient_id, DATE_FORMAT(timestamp,'%H:00')
        ORDER BY timestamp ASC
    ")->fetchAll();
    foreach ($histRows as $row) {
        $patientHistoryMap[$row['patient_id']][] = [
            'hour'    => $row['hour_label'],
            'avg_bpm' => (float)$row['avg_bpm'],
        ];
    }
}

$allPatients = $pdo->query("
    SELECT p.*,
           u.full_name AS rescuer_name,
           (SELECT heart_rate FROM heart_rate_logs WHERE patient_id=p.id ORDER BY id DESC LIMIT 1) AS heart_rate,
           (SELECT status     FROM heart_rate_logs WHERE patient_id=p.id ORDER BY id DESC LIMIT 1) AS hr_status
    FROM patients p
    LEFT JOIN users u ON u.id = p.assigned_to
    ORDER BY p.name
")->fetchAll();

$devices = $pdo->query("
    SELECT d.*,
           p.name       AS patient_name,
           u.full_name  AS user_name
    FROM devices d
    LEFT JOIN patients p ON p.id = d.assigned_to_patient
    LEFT JOIN users    u ON u.id = d.assigned_to_user
    ORDER BY d.serial_status ASC, d.label ASC
")->fetchAll();

$deviceStats = ['usable'=>0,'in-use'=>0,'maintenance'=>0,'disposable'=>0];
foreach ($devices as $d) {
    if (isset($deviceStats[$d['serial_status']])) $deviceStats[$d['serial_status']]++;
}

$patients = $pdo->query("SELECT id, name FROM patients ORDER BY name")->fetchAll();
$rescuers = $pdo->query("SELECT id, full_name FROM users WHERE role='rescuer' ORDER BY full_name")->fetchAll();

// ─── Incident Reports (all, grouped by patient_id) ────────────────────────────
$incidentReports = $pdo->query("
    SELECT ir.*,
           p.name        AS patient_name,
           p.age         AS patient_age,
           u.full_name   AS rescuer_name,
           u.role        AS rescuer_role
    FROM incident_reports ir
    JOIN patients p ON p.id = ir.patient_id
    JOIN users    u ON u.id = ir.rescuer_id
    ORDER BY ir.created_at DESC
")->fetchAll();

$reportsByPatient = [];
foreach ($incidentReports as $ir) {
    $reportsByPatient[$ir['patient_id']][] = $ir;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard — HeartCare</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <style>
        /* ── Device Status Badges ──────────────────────────────────────────── */
        .device-status-badge { display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px; }
        .device-status-badge::before { content:'';width:7px;height:7px;border-radius:50%;flex-shrink:0; }
        .ds-usable      { background:rgba(16,185,129,.15);color:#10b981; } .ds-usable::before      { background:#10b981; }
        .ds-in-use      { background:rgba(59,130,246,.15);color:#3b82f6; } .ds-in-use::before      { background:#3b82f6; }
        .ds-maintenance { background:rgba(245,158,11,.15);color:#f59e0b; } .ds-maintenance::before { background:#f59e0b; }
        .ds-disposable  { background:rgba(239,68,68,.15);color:#ef4444;  } .ds-disposable::before  { background:#ef4444; }

        /* ── Manager-local Modal (for devices) ─────────────────────────────── */
        .modal-backdrop { display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;align-items:center;justify-content:center; }
        .modal-backdrop.open { display:flex; }
        .modal-box { background:var(--bg-card,#1e293b);border-radius:12px;padding:28px 32px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.4);max-height:90vh;overflow-y:auto; }
        .modal-title { font-size:17px;font-weight:700;margin-bottom:18px;color:var(--text-primary,#f1f5f9); }
        .modal-actions { display:flex;gap:10px;justify-content:flex-end;margin-top:20px; }

        /* ── Device Grid & Table ─────────────────────────────────────────────── */
        .device-stats-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px; }
        .device-mini-card  { background:var(--bg-card,#1e293b);border-radius:10px;padding:16px 18px;text-align:center;border:1px solid var(--border,#334155); }
        .device-mini-card .dmc-count { font-size:28px;font-weight:800;margin:4px 0 2px; }
        .device-mini-card .dmc-label { font-size:12px;font-weight:600;color:var(--text-muted,#94a3b8);text-transform:uppercase; }
        .status-select { padding:4px 8px;border-radius:6px;font-size:12px;font-weight:600;border:1px solid var(--border,#334155);background:var(--bg-input,#0f172a);color:var(--text-primary,#f1f5f9);cursor:pointer; }
        .action-btn { padding:5px 12px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;border:none;margin-left:4px; }
        .action-btn-blue   { background:rgba(59,130,246,.15);color:#3b82f6; }
        .action-btn-yellow { background:rgba(245,158,11,.15);color:#f59e0b; }

        /* ── Patient BPM History ─────────────────────────────────────────────── */
        .history-controls  { display:flex;align-items:center;gap:12px;padding:16px 20px 0;flex-wrap:wrap; }
        .history-controls label { font-size:13px;font-weight:600;color:var(--text-muted,#94a3b8); }
        .history-patient-select { flex:1;min-width:180px;max-width:300px;padding:8px 12px;border-radius:8px;border:1px solid var(--border,#334155);background:var(--bg-input,#0f172a);color:var(--text-primary,#f1f5f9);font-size:14px; }
        .history-legend { display:flex;gap:16px;font-size:12px;color:var(--text-muted,#94a3b8);padding:8px 20px 12px;flex-wrap:wrap; }
        .history-legend span { display:flex;align-items:center;gap:5px; }
        .history-legend span::before { content:'';display:inline-block;width:20px;height:3px;border-radius:2px; }
        .legend-normal::before  { background:#10b981; }
        .legend-warning::before { background:#f59e0b; }
        .legend-critical::before{ background:#ef4444; }
        .no-history-msg { text-align:center;padding:48px 20px;color:var(--text-muted,#94a3b8);font-size:14px; }

        /* ── Incident Report Cards (shared with Admin) ───────────────────────── */
        .report-card {
            background: var(--bg-surface, #0f172a);
            border: 1px solid var(--border, #334155);
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 12px;
        }
        .report-card:last-child { margin-bottom: 0; }
        .report-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            gap: 10px;
        }
        .report-card-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary, #f1f5f9);
            flex: 1;
        }
        .report-card-meta {
            font-size: 12px;
            color: var(--text-muted, #94a3b8);
            margin-bottom: 6px;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }
        .report-card-desc {
            font-size: 13px;
            color: var(--text-secondary, #cbd5e1);
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .report-empty {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-muted, #64748b);
            font-size: 14px;
        }
        .sev-badge {
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .sev-low      { background:rgba(16,185,129,.15); color:#10b981; border:1px solid rgba(16,185,129,.3); }
        .sev-medium   { background:rgba(59,130,246,.15);  color:#3b82f6; border:1px solid rgba(59,130,246,.3); }
        .sev-high     { background:rgba(245,158,11,.15);  color:#f59e0b; border:1px solid rgba(245,158,11,.3); }
        .sev-critical { background:rgba(239,68,68,.15);   color:#ef4444; border:1px solid rgba(239,68,68,.3); }

        /* ── Patient Reports Modal ───────────────────────────────────────────── */
        #patientReportsModal .modal {
            max-width: 680px;
            width: 95vw;
        }
        #patientReportsModal .modal-body {
            max-height: 65vh;
            overflow-y: auto;
        }
        .patient-report-info {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 12px 16px;
            background: var(--bg-surface, #0f172a);
            border-radius: 8px;
            margin-bottom: 16px;
            border: 1px solid var(--border, #334155);
        }
        .patient-report-info .pri-name { font-size: 16px; font-weight: 700; }
        .patient-report-info .pri-sub  { font-size: 12px; color: var(--text-muted, #94a3b8); }
        .report-count-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(99,102,241,.15);
            color: #818cf8;
            border: 1px solid rgba(99,102,241,.3);
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            margin-left: 6px;
        }

        /* ══════════════════════════════════════════════════════════════════════
           MOBILE / RESPONSIVE OVERRIDES
        ══════════════════════════════════════════════════════════════════════ */

        /* Scrollable tab bar on small screens */
        .tabs {
            display: flex;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            gap: 4px;
            padding-bottom: 2px;
        }
        .tabs::-webkit-scrollbar { display: none; }
        .tab-btn { white-space: nowrap; flex-shrink: 0; }

        /* Action buttons in table rows — wrap cleanly */
        .row-actions { display:flex;flex-wrap:wrap;gap:4px;align-items:center; }

        @media (max-width: 900px) {
            .device-stats-grid { grid-template-columns: repeat(2, 1fr); }
            .stats-grid-4 { grid-template-columns: repeat(2, 1fr) !important; }
            .stats-grid-3 { grid-template-columns: repeat(2, 1fr) !important; }
            .stats-grid-2 { grid-template-columns: 1fr !important; }
        }

        @media (max-width: 600px) {
            /* Page title */
            .page-title { font-size: 15px; }

            /* Stats collapse to 1 col */
            .stats-grid-4, .stats-grid-3, .stats-grid-2 {
                grid-template-columns: 1fr !important;
            }
            .device-stats-grid { grid-template-columns: repeat(2, 1fr); }

            /* Section card padding */
            .section-card { border-radius: 10px; }
            .section-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }
            .section-header .btn { align-self: flex-start; }

            /* Tables: horizontal scroll */
            .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            table { min-width: 520px; }
            table th, table td { font-size: 12px; padding: 10px 10px; }

            /* Charts */
            .chart-wrapper { height: 200px !important; }

            /* Topbar */
            .topbar-right .topbar-time { display: none; }

            /* Modal */
            .modal-box { padding: 18px 16px; }
            #patientReportsModal .modal-body { max-height: 55vh; }
            .modal { max-width: 96vw !important; margin: 0 8px; }
            .modal-footer { flex-direction: column; gap: 8px; }
            .modal-footer .btn { width: 100%; text-align: center; }

            /* Patient info strip */
            .patient-report-info { flex-direction: column; align-items: flex-start; gap: 8px; }

            /* Report card meta wrap */
            .report-card-meta { flex-direction: column; gap: 4px; }

            /* History controls */
            .history-controls { flex-direction: column; align-items: flex-start; }
            .history-patient-select { max-width: 100%; width: 100%; }

            /* Assign modal */
            .modal-actions { flex-direction: column; }
            .modal-actions .btn { width: 100%; }

            /* Action buttons */
            .action-btn { padding: 6px 10px; font-size: 11px; }

            /* BPM value sizing */
            .bpm-value { font-size: 14px !important; }

            /* Tab label shortening handled via overflow-x scroll above */
        }

        @media (max-width: 400px) {
            .device-stats-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
            .device-mini-card { padding: 12px 10px; }
            .device-mini-card .dmc-count { font-size: 22px; }
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

            <!-- TABS -->
            <div class="section-card" style="margin-bottom:20px">
                <div class="tabs">
                    <button class="tab-btn <?= $tab==='overview'  ?'active':'' ?>" onclick="location.href='?tab=overview'">Overview</button>
                    <button class="tab-btn <?= $tab==='trends'    ?'active':'' ?>" onclick="location.href='?tab=trends'">Trends</button>
                    <button class="tab-btn <?= $tab==='patients'  ?'active':'' ?>" onclick="location.href='?tab=patients'">Patients</button>
                    <button class="tab-btn <?= $tab==='rescuers'  ?'active':'' ?>" onclick="location.href='?tab=rescuers'">Rescuer Performance</button>
                    <button class="tab-btn <?= $tab==='devices'   ?'active':'' ?>" onclick="location.href='?tab=devices'">Devices</button>
                </div>
            </div>

            <!-- ══ OVERVIEW ══ -->
            <?php if ($tab === 'overview'): ?>
            <div class="stats-grid stats-grid-4 mb-6">
                <div class="stat-card card-blue">
                    <div class="stat-card-header"><div class="stat-icon blue"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4" stroke-width="2"/></svg></div></div>
                    <div class="stat-label">Patients</div><div class="stat-value"><?= $totalPatients ?></div><div class="stat-sub">Registered</div>
                </div>
                <div class="stat-card card-yellow">
                    <div class="stat-card-header"><div class="stat-icon yellow"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3"/></svg></div></div>
                    <div class="stat-label">Rescuers</div><div class="stat-value"><?= $totalRescuers ?></div><div class="stat-sub">Field operators</div>
                </div>
                <div class="stat-card card-red">
                    <div class="stat-card-header"><div class="stat-icon red"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg></div></div>
                    <div class="stat-label">Alerts Today</div><div class="stat-value text-red"><?= $alertToday ?></div><div class="stat-sub">Critical events</div>
                </div>
                <div class="stat-card card-purple">
                    <div class="stat-card-header"><div class="stat-icon purple"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div></div>
                    <div class="stat-label">Total Readings</div><div class="stat-value"><?= number_format($totalLogs) ?></div><div class="stat-sub">All time logs</div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header"><div class="section-title">Hourly Heart Rate Overview (Last 12 Hours)</div></div>
                <?php if (empty($hourlyData)): ?>
                    <div style="text-align:center;padding:60px 20px;color:#64748b">No data recorded in the last 12 hours.</div>
                <?php else: ?>
                <div class="chart-container"><div class="chart-wrapper" style="height:260px"><canvas id="hourlyChart"></canvas></div></div>
                <?php endif; ?>
            </div>

            <?php
            $normalPct = $totalLogs > 0 ? round($normalCnt / $totalLogs * 100) : 0;
            $warnPct   = $totalLogs > 0 ? round($warnCnt   / $totalLogs * 100) : 0;
            $critPct   = 100 - $normalPct - $warnPct;
            ?>
            <div class="stats-grid stats-grid-3" style="margin-top:20px">
                <div class="stat-card card-green"  style="text-align:center"><div class="stat-label">Normal Readings</div><div class="stat-value text-green"><?= $normalPct ?>%</div><div class="stat-sub">60–99 BPM</div></div>
                <div class="stat-card card-yellow" style="text-align:center"><div class="stat-label">Warning Readings</div><div class="stat-value text-yellow"><?= $warnPct ?>%</div><div class="stat-sub">100–120 BPM</div></div>
                <div class="stat-card card-red"    style="text-align:center"><div class="stat-label">Critical Readings</div><div class="stat-value text-red"><?= $critPct ?>%</div><div class="stat-sub">&lt;60 or &gt;120 BPM</div></div>
            </div>

            <!-- ══ TRENDS ══ -->
            <?php elseif ($tab === 'trends'): ?>
            <div class="stats-grid stats-grid-2 mb-6">
                <div class="section-card">
                    <div class="section-header"><div class="section-title">Patient BPM Summary (Last Hour)</div></div>
                    <?php if (empty($trendData)): ?>
                        <div style="text-align:center;padding:60px 20px;color:#64748b;font-size:14px">No heart rate data in the last hour.</div>
                    <?php else: ?>
                        <div class="chart-container"><div class="chart-wrapper" style="height:260px"><canvas id="patientBpmChart"></canvas></div></div>
                    <?php endif; ?>
                </div>
                <div class="section-card">
                    <div class="section-header"><div class="section-title">Alert Distribution (All Time)</div></div>
                    <?php if (($normalCnt + $warnCnt + $critCnt) === 0): ?>
                        <div style="text-align:center;padding:60px 20px;color:#64748b;font-size:14px">No data available.</div>
                    <?php else: ?>
                    <div class="chart-container" style="display:flex;align-items:center;justify-content:center;padding:20px;height:260px">
                        <canvas id="alertPieChart" style="max-height:220px;max-width:220px"></canvas>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section-card" style="margin-bottom:20px">
                <div class="section-header">
                    <div class="section-title">Patient BPM History (Last 24 Hours)</div>
                    <div style="font-size:12px;color:var(--text-muted)">Hourly average per patient</div>
                </div>
                <?php if (empty($patientList)): ?>
                    <div class="no-history-msg">No BPM history data in the last 24 hours.</div>
                <?php else: ?>
                <div class="history-controls">
                    <label for="patientHistorySelect">Select Patient:</label>
                    <select id="patientHistorySelect" class="history-patient-select" onchange="updateHistoryChart(this.value)">
                        <?php foreach ($patientList as $pl): ?>
                        <option value="<?= $pl['id'] ?>"><?= htmlspecialchars($pl['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="history-legend">
                    <span class="legend-normal">Normal (60–99 BPM)</span>
                    <span class="legend-warning">Warning (100–120 BPM)</span>
                    <span class="legend-critical">Critical (&lt;60 or &gt;120 BPM)</span>
                </div>
                <div class="chart-container"><div class="chart-wrapper" style="height:280px;padding:0 16px 16px"><canvas id="patientHistoryChart"></canvas></div></div>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <div class="section-header"><div class="section-title">Patient BPM Detail (Last Hour)</div></div>
                <?php if (empty($trendData)): ?>
                    <div style="text-align:center;padding:40px 20px;color:#64748b">No data in the last hour.</div>
                <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Patient</th><th>Avg BPM</th><th>Min BPM</th><th>Max BPM</th><th>Critical Events</th></tr></thead>
                        <tbody>
                        <?php foreach ($trendData as $t): ?>
                        <tr>
                            <td style="font-weight:600"><?= htmlspecialchars($t['name']) ?></td>
                            <td><span class="bpm-value <?= $t['avg_bpm']<60||$t['avg_bpm']>120?'bpm-critical':($t['avg_bpm']>=100?'bpm-warning':'bpm-normal') ?>" style="font-size:15px"><?= round($t['avg_bpm']) ?></span></td>
                            <td><span style="color:var(--green);font-weight:600"><?= $t['min_bpm'] ?></span></td>
                            <td><span style="color:var(--red);font-weight:600"><?= $t['max_bpm'] ?></span></td>
                            <td><?php if ($t['critical_count']>0): ?><span class="badge badge-critical"><?= $t['critical_count'] ?> events</span><?php else: ?><span class="badge badge-normal">None</span><?php endif; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- ══ PATIENTS ══ -->
            <?php elseif ($tab === 'patients'): ?>
            <div class="section-card">
                <div class="section-header">
                    <div>
                        <div class="section-title">All Patients</div>
                        <div class="section-subtitle"><?= count($allPatients) ?> registered</div>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addPatientModal')">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Patient
                    </button>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Name</th><th>Age</th><th>Condition</th><th>Rescuer</th><th>Heart Rate</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($allPatients as $p):
                            $hr = $p['heart_rate'] ?? '—';
                            $st = $p['hr_status']  ?? 'normal';
                            $bc = ['normal'=>'badge-normal','warning'=>'badge-warning','critical'=>'badge-critical'][$st] ?? 'badge-normal';
                            $bl = ['normal'=>'Normal','warning'=>'Warning','critical'=>'Critical'][$st] ?? 'Normal';
                            $rptCount = isset($reportsByPatient[$p['id']]) ? count($reportsByPatient[$p['id']]) : 0;
                        ?>
                        <tr>
                            <td style="font-weight:600"><?= htmlspecialchars($p['name']) ?></td>
                            <td class="td-muted"><?= $p['age'] ?></td>
                            <td class="td-muted"><?= htmlspecialchars($p['medical_condition'] ?? '—') ?></td>
                            <td class="td-muted"><?= htmlspecialchars($p['rescuer_name'] ?? 'Unassigned') ?></td>
                            <td>
                                <?php if (is_numeric($hr)): ?>
                                <span class="bpm-value <?= ['normal'=>'bpm-normal','warning'=>'bpm-warning','critical'=>'bpm-critical'][$st] ?? 'bpm-normal' ?>" style="font-size:16px"><?= $hr ?></span>
                                <span style="font-size:11px;color:var(--text-muted)"> BPM</span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><span class="badge <?= $bc ?>"><?= $bl ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <!-- View Reports Button -->
                                    <button class="btn btn-ghost btn-sm"
                                        onclick="openPatientReports(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>', <?= $p['age'] ?>, '<?= htmlspecialchars($p['medical_condition']??'—', ENT_QUOTES) ?>')"
                                        style="color:#818cf8;border-color:rgba(99,102,241,.3)">
                                        📋 Reports<?php if ($rptCount > 0): ?><span class="report-count-badge"><?= $rptCount ?></span><?php endif; ?>
                                    </button>
                                    <button class="btn btn-ghost btn-sm" onclick="openEditPatient(<?= htmlspecialchars(json_encode($p)) ?>)">Edit</button>
                                    <a href="?tab=patients&delete_patient=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete patient <?= htmlspecialchars($p['name'], ENT_QUOTES) ?>?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ══ RESCUERS ══ -->
            <?php elseif ($tab === 'rescuers'): ?>
            <div class="section-card">
                <div class="section-header"><div class="section-title">Rescuer Performance</div></div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Rescuer</th><th>Patients Assigned</th><th>Incident Reports</th><th>Load Level</th></tr></thead>
                        <tbody>
                        <?php foreach ($rescuerPerf as $r):
                            $load = $r['patient_count'] >= 4 ? 'High' : ($r['patient_count'] >= 2 ? 'Medium' : 'Low');
                            $lc   = $r['patient_count'] >= 4 ? 'badge-critical' : ($r['patient_count'] >= 2 ? 'badge-warning' : 'badge-normal');
                        ?>
                        <tr>
                            <td style="font-weight:600"><?= htmlspecialchars($r['full_name']) ?></td>
                            <td><span style="font-size:18px;font-weight:700;color:var(--blue)"><?= $r['patient_count'] ?></span></td>
                            <td><?= $r['report_count'] ?></td>
                            <td><span class="badge <?= $lc ?>"><?= $load ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ══ DEVICES ══ -->
            <?php elseif ($tab === 'devices'): ?>
            <div class="device-stats-grid">
                <div class="device-mini-card"><div class="dmc-label">Usable</div><div class="dmc-count" style="color:#10b981"><?= $deviceStats['usable'] ?></div><div style="font-size:11px;color:#64748b">Ready to deploy</div></div>
                <div class="device-mini-card"><div class="dmc-label">In-Use</div><div class="dmc-count" style="color:#3b82f6"><?= $deviceStats['in-use'] ?></div><div style="font-size:11px;color:#64748b">Currently assigned</div></div>
                <div class="device-mini-card"><div class="dmc-label">Maintenance</div><div class="dmc-count" style="color:#f59e0b"><?= $deviceStats['maintenance'] ?></div><div style="font-size:11px;color:#64748b">Under repair</div></div>
                <div class="device-mini-card"><div class="dmc-label">Disposable</div><div class="dmc-count" style="color:#ef4444"><?= $deviceStats['disposable'] ?></div><div style="font-size:11px;color:#64748b">Decommissioned</div></div>
            </div>
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">All Devices</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addDeviceModal')">+ Add Device</button>
                </div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>#</th><th>Label</th><th>Type</th><th>Serial No.</th><th>Status</th><th>Assigned To</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php if (empty($devices)): ?>
                            <tr><td colspan="7" style="text-align:center;color:#64748b;padding:32px">No devices found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($devices as $i => $d):
                            $assignedTo = $d['patient_name'] ? '👤 '.htmlspecialchars($d['patient_name'])
                                        : ($d['user_name']   ? '🚑 '.htmlspecialchars($d['user_name']) : '—');
                        ?>
                        <tr>
                            <td style="color:#64748b;font-size:13px"><?= $i+1 ?></td>
                            <td style="font-weight:700"><?= htmlspecialchars($d['label']) ?></td>
                            <td style="color:#94a3b8"><?= htmlspecialchars($d['type']) ?></td>
                            <td style="font-family:monospace;font-size:13px;color:#64748b"><?= htmlspecialchars($d['serial_number']??'—') ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="device_id" value="<?= $d['id'] ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="usable"      <?= $d['serial_status']==='usable'      ?'selected':'' ?>>✅ Usable</option>
                                        <option value="in-use"      <?= $d['serial_status']==='in-use'      ?'selected':'' ?>>🔵 In-Use</option>
                                        <option value="maintenance" <?= $d['serial_status']==='maintenance' ?'selected':'' ?>>🔧 Maintenance</option>
                                        <option value="disposable"  <?= $d['serial_status']==='disposable'  ?'selected':'' ?>>🗑️ Disposable</option>
                                    </select>
                                </form>
                            </td>
                            <td><?= $assignedTo ?></td>
                            <td>
                                <div class="row-actions">
                                <?php if ($d['serial_status'] !== 'disposable'): ?>
                                    <?php if ($d['serial_status'] !== 'in-use'): ?>
                                        <button class="action-btn action-btn-blue" onclick="openAssignModal(<?= $d['id'] ?>,'<?= htmlspecialchars(addslashes($d['label'])) ?>')">Assign</button>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="action" value="unassign_device">
                                            <input type="hidden" name="device_id" value="<?= $d['id'] ?>">
                                            <button type="submit" class="action-btn action-btn-yellow" onclick="return confirm('Unassign this device?')">Unassign</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?><span style="color:#475569;font-size:12px">Decommissioned</span><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.page-content -->
    </div><!-- /.main-content -->
</div><!-- /.layout -->

<!-- ══ MODAL: Add Device ══ -->
<div class="modal-backdrop" id="addDeviceModal">
    <div class="modal-box">
        <div class="modal-title">Add New Device</div>
        <form method="POST">
            <input type="hidden" name="action" value="add_device">
            <div class="form-group"><label class="form-label">Device Label *</label><input type="text" name="device_label" class="form-input" placeholder="e.g. Heart Monitor Unit A1" required></div>
            <div class="form-group"><label class="form-label">Device Type *</label>
                <select name="device_type" class="form-select" required>
                    <option value="">— Select Type —</option>
                    <option value="Heart Rate Monitor">Heart Rate Monitor</option>
                    <option value="Pulse Oximeter">Pulse Oximeter</option>
                    <option value="AED">AED (Defibrillator)</option>
                    <option value="ECG Machine">ECG Machine</option>
                    <option value="BP Monitor">BP Monitor</option>
                    <option value="Wearable Sensor">Wearable Sensor</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Serial Number</label><input type="text" name="serial_number" class="form-input" placeholder="e.g. SN-2024-00123"></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addDeviceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Device</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ MODAL: Assign Device ══ -->
<div class="modal-backdrop" id="assignDeviceModal">
    <div class="modal-box">
        <div class="modal-title">Assign Device: <span id="assignDeviceName" style="color:#3b82f6"></span></div>
        <form method="POST">
            <input type="hidden" name="action" value="assign_device">
            <input type="hidden" name="device_id" id="assignDeviceId">
            <p style="font-size:13px;color:#94a3b8;margin-bottom:16px">Assign to a patient or a rescuer. Leave the other blank.</p>
            <div class="form-group"><label class="form-label">Assign to Patient</label>
                <select name="patient_id" class="form-select">
                    <option value="">— No patient —</option>
                    <?php foreach ($patients as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label class="form-label">— or — Assign to Rescuer</label>
                <select name="user_id" class="form-select">
                    <option value="">— No rescuer —</option>
                    <?php foreach ($rescuers as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" onclick="closeModal('assignDeviceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Confirm Assignment</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ MODAL: Add Patient ══ -->
<div class="modal-overlay" id="addPatientModal">
    <div class="modal">
        <div class="modal-header"><div class="modal-title">Add New Patient</div><button class="modal-close" onclick="closeModal('addPatientModal')">×</button></div>
        <form method="POST" action="?tab=patients">
            <input type="hidden" name="action" value="add_patient">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Age *</label><input type="number" name="age" min="1" max="120" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Condition</label><input type="text" name="condition" class="form-input"></div>
                    <div class="form-group"><label class="form-label">Assigned Rescuer</label>
                        <select name="rescuer_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($rescuers as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addPatientModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Patient</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ MODAL: Edit Patient ══ -->
<div class="modal-overlay" id="editPatientModal">
    <div class="modal">
        <div class="modal-header"><div class="modal-title">Edit Patient</div><button class="modal-close" onclick="closeModal('editPatientModal')">×</button></div>
        <form method="POST" action="?tab=patients">
            <input type="hidden" name="action" value="edit_patient">
            <input type="hidden" name="patient_id" id="editPatientId">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="name" id="editPatientName" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Age *</label><input type="number" name="age" id="editPatientAge" min="1" max="120" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Condition</label><input type="text" name="condition" id="editPatientCondition" class="form-input"></div>
                    <div class="form-group"><label class="form-label">Assigned Rescuer</label>
                        <select name="rescuer_id" id="editPatientRescuer" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($rescuers as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('editPatientModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ MODAL: Patient Incident Reports ══ -->
<div class="modal-overlay" id="patientReportsModal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <div class="modal-title">📋 Incident Reports</div>
                <div id="prModalSub" style="font-size:12px;color:var(--text-muted);margin-top:2px"></div>
            </div>
            <button class="modal-close" onclick="closeModal('patientReportsModal')">×</button>
        </div>
        <div class="modal-body">
            <div class="patient-report-info">
                <div style="width:40px;height:40px;border-radius:50%;background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">👤</div>
                <div>
                    <div class="pri-name" id="prPatientName">—</div>
                    <div class="pri-sub"  id="prPatientMeta">—</div>
                </div>
            </div>
            <div id="prReportsList"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('patientReportsModal')">Close</button>
        </div>
    </div>
</div>

<!-- Embed all reports as JS data -->
<script>
const ALL_REPORTS = <?= json_encode($reportsByPatient) ?>;
</script>

<div id="toastContainer" class="toast-container"></div>
<script src="../assets/js/scripts.js"></script>
<script>
(function tick(){ const el=document.getElementById('liveClock'); if(el) el.textContent=new Date().toLocaleTimeString(); setTimeout(tick,1000); })();

// ── Modal helpers ─────────────────────────────────────────────────────────────
function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('open','active');
    el.style.display = 'flex';
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('open','active');
    el.style.display = '';
}
// Close modal-backdrop on outside click
document.querySelectorAll('.modal-backdrop').forEach(m =>
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); })
);
// Close modal-overlay on outside click
document.querySelectorAll('.modal-overlay').forEach(m =>
    m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); })
);

function openAssignModal(deviceId, deviceLabel) {
    document.getElementById('assignDeviceId').value         = deviceId;
    document.getElementById('assignDeviceName').textContent = deviceLabel;
    openModal('assignDeviceModal');
}
function openEditPatient(p) {
    document.getElementById('editPatientId').value        = p.id;
    document.getElementById('editPatientName').value      = p.name;
    document.getElementById('editPatientAge').value       = p.age;
    document.getElementById('editPatientCondition').value = p.medical_condition || '';
    document.getElementById('editPatientRescuer').value   = p.assigned_to || '';
    openModal('editPatientModal');
}

// ── Patient Reports Modal ─────────────────────────────────────────────────────
function openPatientReports(patientId, patientName, patientAge, patientCondition) {
    document.getElementById('prPatientName').textContent = patientName;
    document.getElementById('prPatientMeta').textContent =
        'Age: ' + patientAge + '  •  Condition: ' + (patientCondition || '—');

    const reports = ALL_REPORTS[patientId] || [];
    document.getElementById('prModalSub').textContent =
        reports.length + ' report' + (reports.length !== 1 ? 's' : '') + ' submitted by rescuer';

    const list = document.getElementById('prReportsList');

    if (reports.length === 0) {
        list.innerHTML = '<div class="report-empty">📭 No incident reports found for this patient.</div>';
    } else {
        const sevClass = { low:'sev-low', medium:'sev-medium', high:'sev-high', critical:'sev-critical' };
        list.innerHTML = reports.map(function(r) {
            const dt      = new Date(r.created_at);
            const dateStr = dt.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
            const timeStr = dt.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' });
            const sc      = sevClass[r.severity] || 'sev-medium';
            return '<div class="report-card">'
                + '<div class="report-card-header">'
                +   '<div class="report-card-title">' + escHtml(r.incident_type) + '</div>'
                +   '<span class="sev-badge ' + sc + '">' + capFirst(r.severity) + '</span>'
                + '</div>'
                + '<div class="report-card-meta">'
                +   '<span>👤 ' + escHtml(r.rescuer_name) + ' <span style="opacity:.6;font-size:11px">(' + capFirst(r.rescuer_role) + ')</span></span>'
                +   '<span>📅 ' + dateStr + ' at ' + timeStr + '</span>'
                + '</div>'
                + (r.description
                    ? '<div class="report-card-desc">' + escHtml(r.description) + '</div>'
                    : '<div style="font-size:12px;color:var(--text-muted);font-style:italic">No additional description provided.</div>')
                + '</div>';
        }).join('');
    }
    openModal('patientReportsModal');
}

function escHtml(str) {
    return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function capFirst(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

// ── Chart.js defaults ─────────────────────────────────────────────────────────
Chart.defaults.color       = '#94a3b8';
Chart.defaults.borderColor = '#1e293b';

// ── Overview: Hourly line chart ───────────────────────────────────────────────
<?php if ($tab === 'overview' && count($hourlyData) > 0): ?>
new Chart(document.getElementById('hourlyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($hourlyData,'hour_label')) ?>,
        datasets: [{
            label: 'Avg Heart Rate',
            data: <?= json_encode(array_column($hourlyData,'avg_bpm')) ?>,
            borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,0.08)',
            borderWidth:2, tension:0.4, fill:true, pointRadius:3,
        },{
            label: 'Critical Count',
            data: <?= json_encode(array_column($hourlyData,'critical_count')) ?>,
            borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,0.08)',
            borderWidth:2, tension:0.4, fill:true, yAxisID:'y2', pointRadius:3,
        }]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ position:'top' } },
        scales:{
            x:  { grid:{ color:'#1e293b' } },
            y:  { grid:{ color:'#1e293b' }, title:{ display:true, text:'BPM' } },
            y2: { position:'right', grid:{ display:false }, title:{ display:true, text:'Critical Count' } }
        }
    }
});
<?php endif; ?>

// ── Trends: patient bar chart ─────────────────────────────────────────────────
<?php if ($tab === 'trends' && count($trendData) > 0): ?>
new Chart(document.getElementById('patientBpmChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($t) => substr($t['name'],0,14), $trendData)) ?>,
        datasets: [{
            label: 'Avg Heart Rate',
            data: <?= json_encode(array_map(fn($t) => round($t['avg_bpm']), $trendData)) ?>,
            backgroundColor: <?= json_encode(array_map(fn($t) =>
                $t['avg_bpm']<60||$t['avg_bpm']>120 ? 'rgba(239,68,68,0.7)'
                : ($t['avg_bpm']>=100 ? 'rgba(245,158,11,0.7)' : 'rgba(16,185,129,0.7)'),
            $trendData)) ?>,
            borderRadius:6,
        }]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ display:false } },
        scales:{ x:{ grid:{ color:'#1e293b' } }, y:{ grid:{ color:'#1e293b' }, min:0, max:160 } }
    }
});
<?php endif; ?>

<?php if ($tab === 'trends' && ($normalCnt + $warnCnt + $critCnt) > 0): ?>
new Chart(document.getElementById('alertPieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Normal', 'Warning', 'Critical'],
        datasets: [{
            data: [<?= (int)$normalCnt ?>, <?= (int)$warnCnt ?>, <?= (int)$critCnt ?>],
            backgroundColor: ['#10b981','#f59e0b','#ef4444'],
            borderWidth: 0, hoverOffset: 6
        }]
    },
    options: { responsive:false, plugins:{ legend:{ position:'bottom' } }, cutout:'65%' }
});
<?php endif; ?>

// ── Trends: Patient BPM History line chart ─────────────────────────────────
<?php if ($tab === 'trends' && !empty($patientList)): ?>
const patientHistoryData = <?= json_encode($patientHistoryMap) ?>;

function bpmColor(bpm) {
    if (bpm < 60 || bpm > 120) return '#ef4444';
    if (bpm >= 100)             return '#f59e0b';
    return '#10b981';
}

let historyChart = null;
function updateHistoryChart(patientId) {
    const canvas = document.getElementById('patientHistoryChart');
    if (!canvas) return;
    const rows   = patientHistoryData[patientId] || [];
    const labels = rows.map(r => r.hour);
    const bpms   = rows.map(r => r.avg_bpm);
    const avg    = bpms.length ? bpms.reduce((a,b)=>a+b,0)/bpms.length : 80;
    const lc     = bpmColor(avg);
    const pc     = bpms.map(v => bpmColor(v));

    if (historyChart) {
        historyChart.data.labels                            = labels;
        historyChart.data.datasets[0].data                 = bpms;
        historyChart.data.datasets[0].borderColor          = lc;
        historyChart.data.datasets[0].pointBackgroundColor = pc;
        historyChart.update(); return;
    }

    historyChart = new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label:'Avg Heart Rate', data:bpms,
                borderColor:lc, backgroundColor:'rgba(59,130,246,0.06)',
                borderWidth:2.5, tension:0.4, fill:true,
                pointRadius:5, pointHoverRadius:7,
                pointBackgroundColor:pc, pointBorderColor:'#0f172a', pointBorderWidth:2,
            }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins:{
                legend:{ display:false },
                tooltip:{ callbacks:{ label: ctx => {
                    const v=ctx.parsed.y;
                    const s=v<60||v>120?'🔴 Critical':(v>=100?'🟡 Warning':'🟢 Normal');
                    return ` ${v} BPM — ${s}`;
                }}}
            },
            scales:{
                x:{ grid:{ color:'#1e293b' }, title:{ display:true, text:'Hour of Day' } },
                y:{ grid:{ color:'#1e293b' }, min:40, max:160, title:{ display:true, text:'BPM' } }
            }
        },
        plugins:[{
            id:'bpmZones',
            beforeDraw(chart) {
                const { ctx, chartArea:{ top, bottom, left, right }, scales:{ y } } = chart;
                if (!y) return;
                const zones = [
                    { from:40,  to:60,  color:'rgba(239,68,68,0.07)'  },
                    { from:60,  to:100, color:'rgba(16,185,129,0.06)' },
                    { from:100, to:120, color:'rgba(245,158,11,0.07)' },
                    { from:120, to:160, color:'rgba(239,68,68,0.07)'  },
                ];
                ctx.save();
                zones.forEach(z => {
                    const yT=y.getPixelForValue(z.to), yB=y.getPixelForValue(z.from);
                    ctx.fillStyle=z.color;
                    ctx.fillRect(left, yT, right-left, yB-yT);
                });
                ctx.restore();
            }
        }]
    });
}
(function(){ const sel=document.getElementById('patientHistorySelect'); if(sel) updateHistoryChart(sel.value); })();
<?php endif; ?>
</script>
</body>
</html>