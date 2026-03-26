<?php
/**
 * Admin Dashboard — Full Management + Analytics + Devices
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$user = getCurrentUser();
$pdo  = getDB();
$tab  = $_GET['tab'] ?? 'overview';

$msg = $msgType = '';

// ─── POST Actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── User Management ────────────────────────────────────────────────────────
    if ($action === 'add_user') {
        $un   = trim($_POST['username']  ?? '');
        $pw   = trim($_POST['password']  ?? '');
        $fn   = trim($_POST['full_name'] ?? '');
        $role = trim($_POST['role']      ?? '');
        $em   = trim($_POST['email']     ?? '');
        $allowed_roles = ['admin','manager','rescuer','responder'];
        if (!$un || !$pw || !$fn || !$role) {
            $msg = 'Username, password, full name and role are all required.'; $msgType = 'error';
        } elseif (!in_array($role, $allowed_roles)) {
            $msg = 'Invalid role selected.'; $msgType = 'error';
        } else {
            try {
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $check->execute([$un]);
                if ($check->fetch()) {
                    $msg = 'Error: Username already exists.'; $msgType = 'error';
                } else {
                    $pdo->prepare("INSERT INTO users (username,password,full_name,role,email) VALUES (?,?,?,?,?)")
                        ->execute([$un, password_hash($pw, PASSWORD_DEFAULT), $fn, $role, $em]);
                    logAction($user['id'], 'ADD_USER', "Added user: $un ($role)");
                    $msg = "User '$un' added successfully."; $msgType = 'success';
                }
            } catch (Exception $e) { $msg = 'Database error: '.$e->getMessage(); $msgType='error'; }
        }
        $tab = 'users';
    }

    if ($action === 'edit_user') {
        $id     = (int)($_POST['user_id']  ?? 0);
        $fn     = trim($_POST['full_name'] ?? '');
        $role   = trim($_POST['role']      ?? '');
        $em     = trim($_POST['email']     ?? '');
        $status = trim($_POST['status']    ?? 'active');
        try {
            $pdo->prepare("UPDATE users SET full_name=?,role=?,email=?,status=? WHERE id=?")
                ->execute([$fn,$role,$em,$status,$id]);
            if (!empty($_POST['password'])) {
                $pdo->prepare("UPDATE users SET password=? WHERE id=?")
                    ->execute([password_hash($_POST['password'], PASSWORD_DEFAULT), $id]);
            }
            logAction($user['id'], 'EDIT_USER', "Edited user ID: $id");
            $msg = 'User updated successfully.'; $msgType = 'success';
        } catch (Exception $e) { $msg = 'Database error: '.$e->getMessage(); $msgType='error'; }
        $tab = 'users';
    }

    // ── Patient Management ─────────────────────────────────────────────────────
    if ($action === 'add_patient') {
        $nm   = trim($_POST['name']        ?? '');
        $age  = (int)($_POST['age']        ?? 0);
        $cond = trim($_POST['condition']   ?? '');
        $rid  = (int)($_POST['rescuer_id'] ?? 0) ?: null;
        if (!$nm || !$age) {
            $msg = 'Patient name and age are required.'; $msgType = 'error';
        } else {
            try {
                $pdo->prepare("INSERT INTO patients (name,age,medical_condition,assigned_to) VALUES (?,?,?,?)")
                    ->execute([$nm,$age,$cond,$rid]);
                logAction($user['id'], 'ADD_PATIENT', "Added patient: $nm");
                $msg = "Patient '$nm' added successfully."; $msgType = 'success';
            } catch (Exception $e) { $msg = 'Database error: '.$e->getMessage(); $msgType='error'; }
        }
        $tab = 'patients';
    }

    if ($action === 'edit_patient') {
        $id   = (int)($_POST['patient_id'] ?? 0);
        $nm   = trim($_POST['name']        ?? '');
        $age  = (int)($_POST['age']        ?? 0);
        $cond = trim($_POST['condition']   ?? '');
        $rid  = (int)($_POST['rescuer_id'] ?? 0) ?: null;
        if ($id && $nm && $age) {
            try {
                $pdo->prepare("UPDATE patients SET name=?,age=?,medical_condition=?,assigned_to=? WHERE id=?")
                    ->execute([$nm,$age,$cond,$rid,$id]);
                logAction($user['id'], 'EDIT_PATIENT', "Edited patient ID: $id");
                $msg = 'Patient updated successfully.'; $msgType = 'success';
            } catch (Exception $e) { $msg = 'Database error: '.$e->getMessage(); $msgType='error'; }
        }
        $tab = 'patients';
    }

    // ── Device Management ──────────────────────────────────────────────────────
    if ($action === 'add_device') {
        $label  = trim($_POST['device_label']  ?? '');
        $type   = trim($_POST['device_type']   ?? '');
        $serial = trim($_POST['serial_number'] ?? '');
        if ($label && $type) {
            $pdo->prepare("INSERT INTO devices (label,type,serial_number,serial_status) VALUES (?,?,?,'usable')")
                ->execute([$label,$type,$serial]);
            logAction($user['id'], 'ADD_DEVICE', "Added device: $label");
            $msg = "Device '$label' added."; $msgType = 'success';
        }
        $tab = 'devices';
    }

    if ($action === 'update_device_status') {
        $id      = (int)($_POST['device_id'] ?? 0);
        $status  = $_POST['status'] ?? '';
        $allowed = ['usable','in-use','maintenance','disposable'];
        if ($id && in_array($status, $allowed)) {
            if ($status !== 'in-use') {
                $pdo->prepare("UPDATE devices SET serial_status=?,assigned_to_patient=NULL,assigned_to_user=NULL WHERE id=?")
                    ->execute([$status,$id]);
            } else {
                $pdo->prepare("UPDATE devices SET serial_status=? WHERE id=?")->execute([$status,$id]);
            }
        }
        header("Location: ?tab=devices"); exit;
    }

    if ($action === 'assign_device') {
        $id  = (int)($_POST['device_id']  ?? 0);
        $pid = (int)($_POST['patient_id'] ?? 0) ?: null;
        $uid = (int)($_POST['user_id']    ?? 0) ?: null;
        if ($id) {
            $pdo->prepare("UPDATE devices SET serial_status='in-use',assigned_to_patient=?,assigned_to_user=? WHERE id=?")
                ->execute([$pid,$uid,$id]);
        }
        header("Location: ?tab=devices"); exit;
    }

    if ($action === 'unassign_device') {
        $id = (int)($_POST['device_id'] ?? 0);
        if ($id) {
            $pdo->prepare("UPDATE devices SET serial_status='usable',assigned_to_patient=NULL,assigned_to_user=NULL WHERE id=?")
                ->execute([$id]);
        }
        header("Location: ?tab=devices"); exit;
    }
}

// ─── GET Deletes ──────────────────────────────────────────────────────────────
if (isset($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    if ($id !== (int)$user['id']) {
        try {
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
            logAction($user['id'], 'DELETE_USER', "Deleted user ID: $id");
            $msg = 'User deleted.'; $msgType = 'success';
        } catch (Exception $e) { $msg = 'Database error: '.$e->getMessage(); $msgType='error'; }
    } else { $msg = 'You cannot delete your own account.'; $msgType='error'; }
    $tab = 'users';
}

if (isset($_GET['delete_patient'])) {
    $id = (int)$_GET['delete_patient'];
    try {
        $pdo->prepare("DELETE FROM patients WHERE id=?")->execute([$id]);
        logAction($user['id'], 'DELETE_PATIENT', "Deleted patient ID: $id");
        $msg = 'Patient deleted.'; $msgType = 'success';
    } catch (Exception $e) { $msg = 'Database error: '.$e->getMessage(); $msgType='error'; }
    $tab = 'patients';
}

if (isset($_GET['delete_device'])) {
    $id = (int)$_GET['delete_device'];
    try {
        $pdo->prepare("DELETE FROM devices WHERE id=?")->execute([$id]);
        logAction($user['id'], 'DELETE_DEVICE', "Deleted device ID: $id");
        $msg = 'Device deleted.'; $msgType = 'success';
    } catch (Exception $e) { $msg = 'Database error: '.$e->getMessage(); $msgType='error'; }
    $tab = 'devices';
}

// ─── Data Fetching ────────────────────────────────────────────────────────────
$users    = $pdo->query("SELECT * FROM users ORDER BY role, full_name")->fetchAll();
$patients = $pdo->query("
    SELECT p.*, u.full_name AS rescuer_name,
           (SELECT heart_rate FROM heart_rate_logs WHERE patient_id=p.id ORDER BY id DESC LIMIT 1) AS heart_rate,
           (SELECT status     FROM heart_rate_logs WHERE patient_id=p.id ORDER BY id DESC LIMIT 1) AS hr_status
    FROM patients p
    LEFT JOIN users u ON u.id = p.assigned_to
    ORDER BY p.name
")->fetchAll();
$logs     = $pdo->query("
    SELECT sl.*, u.full_name, u.role
    FROM system_logs sl
    LEFT JOIN users u ON u.id = sl.user_id
    ORDER BY sl.timestamp DESC LIMIT 100
")->fetchAll();
$rescuers = $pdo->query("SELECT id, full_name FROM users WHERE role='rescuer' ORDER BY full_name")->fetchAll();

// Devices
$devices = $pdo->query("
    SELECT d.*, p.name AS patient_name, u.full_name AS user_name
    FROM devices d
    LEFT JOIN patients p ON p.id = d.assigned_to_patient
    LEFT JOIN users    u ON u.id = d.assigned_to_user
    ORDER BY d.serial_status ASC, d.label ASC
")->fetchAll();
$deviceStats = ['usable'=>0,'in-use'=>0,'maintenance'=>0,'disposable'=>0];
foreach ($devices as $d) { if (isset($deviceStats[$d['serial_status']])) $deviceStats[$d['serial_status']]++; }

// ─── Incident Reports (all, grouped by patient_id for admin view) ─────────────
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

// Group reports by patient_id for JS lookup
$reportsByPatient = [];
foreach ($incidentReports as $ir) {
    $reportsByPatient[$ir['patient_id']][] = $ir;
}

// Analytics
$totalLogs  = $pdo->query("SELECT COUNT(*) FROM heart_rate_logs")->fetchColumn();
$alertToday = $pdo->query("SELECT COUNT(*) FROM heart_rate_logs WHERE status='critical' AND DATE(timestamp)=CURDATE()")->fetchColumn();
$normalCnt  = (int)$pdo->query("SELECT COUNT(*) FROM heart_rate_logs WHERE status='normal'")->fetchColumn();
$warnCnt    = (int)$pdo->query("SELECT COUNT(*) FROM heart_rate_logs WHERE status='warning'")->fetchColumn();
$critCnt    = (int)$pdo->query("SELECT COUNT(*) FROM heart_rate_logs WHERE status='critical'")->fetchColumn();

$hourlyData = $pdo->query("
    SELECT DATE_FORMAT(timestamp,'%H:00') AS hour_label,
           ROUND(AVG(heart_rate),1)       AS avg_bpm,
           SUM(CASE WHEN status='critical' THEN 1 ELSE 0 END) AS critical_count
    FROM heart_rate_logs
    WHERE timestamp >= NOW() - INTERVAL 12 HOUR
    GROUP BY DATE_FORMAT(timestamp,'%H:00')
    ORDER BY timestamp ASC
")->fetchAll();

$rescuerPerf = $pdo->query("
    SELECT u.full_name, u.id,
           COUNT(DISTINCT p.id) AS patient_count,
           COALESCE((SELECT COUNT(*) FROM incident_reports ir WHERE ir.rescuer_id=u.id),0) AS report_count
    FROM users u
    LEFT JOIN patients p ON p.assigned_to=u.id
    WHERE u.role='rescuer'
    GROUP BY u.id, u.full_name
")->fetchAll();

// Summary counts
$totalUsers    = count($users);
$totalPatients = count($patients);
$activeAlerts  = count(array_filter($patients, fn($p) => ($p['hr_status'] ?? '') === 'critical'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — HeartCare</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <style>
        .device-stats-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px; }
        .device-mini-card  { background:var(--bg-card,#1e293b);border-radius:10px;padding:16px 18px;text-align:center;border:1px solid var(--border,#334155); }
        .device-mini-card .dmc-count { font-size:28px;font-weight:800;margin:4px 0 2px; }
        .device-mini-card .dmc-label { font-size:12px;font-weight:600;color:var(--text-muted,#94a3b8);text-transform:uppercase; }
        .status-select { padding:4px 8px;border-radius:6px;font-size:12px;font-weight:600;border:1px solid var(--border,#334155);background:var(--bg-input,#0f172a);color:var(--text-primary,#f1f5f9);cursor:pointer; }
        .action-btn { padding:5px 12px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;border:none;margin-left:4px; }
        .action-btn-blue   { background:rgba(59,130,246,.15);color:#3b82f6; }
        .action-btn-yellow { background:rgba(245,158,11,.15);color:#f59e0b; }

        /* ── Patient Reports Modal ── */
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
        .sev-low      { background: rgba(16,185,129,.15); color:#10b981; border:1px solid rgba(16,185,129,.3); }
        .sev-medium   { background: rgba(59,130,246,.15);  color:#3b82f6; border:1px solid rgba(59,130,246,.3); }
        .sev-high     { background: rgba(245,158,11,.15);  color:#f59e0b; border:1px solid rgba(245,158,11,.3); }
        .sev-critical { background: rgba(239,68,68,.15);   color:#ef4444; border:1px solid rgba(239,68,68,.3); }

        /* Wide modal for reports */
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
            margin-left: 8px;
        }

        @media(max-width:700px){ .device-stats-grid{ grid-template-columns:repeat(2,1fr); } }
    </style>
</head>
<body>
<div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>
<div class="layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div class="main-content">

        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <span class="page-title">Admin Dashboard</span>
            </div>
            <div class="topbar-right">
                <div class="live-indicator"><div class="live-dot"></div>LIVE</div>
                <span class="topbar-time" id="liveClock"></span>
            </div>
        </div>

        <div class="page-content">

            <?php if ($msg): ?>
            <div style="background:<?= $msgType==='success'?'var(--green-bg)':'var(--red-bg)' ?>;border:1px solid <?= $msgType==='success'?'var(--green-border)':'var(--red-border)' ?>;color:<?= $msgType==='success'?'var(--green)':'var(--red)' ?>;border-radius:var(--radius-sm);padding:12px 16px;font-size:13px;margin-bottom:16px;">
                <?= $msgType==='success'?'✓':'⚠️' ?> <?= htmlspecialchars($msg) ?>
            </div>
            <?php endif; ?>

            <!-- TABS -->
            <div class="section-card" style="margin-bottom:0;border-radius:var(--radius) var(--radius) 0 0">
                <div class="tabs" style="flex-wrap:wrap">
                    <button class="tab-btn <?= $tab==='overview' ?'active':'' ?>" onclick="location.href='?tab=overview'">Overview</button>
                    <button class="tab-btn <?= $tab==='users'    ?'active':'' ?>" onclick="location.href='?tab=users'">Users</button>
                    <button class="tab-btn <?= $tab==='patients' ?'active':'' ?>" onclick="location.href='?tab=patients'">Patients</button>
                    <button class="tab-btn <?= $tab==='devices'  ?'active':'' ?>" onclick="location.href='?tab=devices'">Devices</button>
                    <button class="tab-btn <?= $tab==='analytics'?'active':'' ?>" onclick="location.href='?tab=analytics'">Analytics</button>
                    <button class="tab-btn <?= $tab==='logs'     ?'active':'' ?>" onclick="location.href='?tab=logs'">System Logs</button>
                </div>
            </div>

            <!-- ══ OVERVIEW ══ -->
            <?php if ($tab === 'overview'): ?>
            <div style="padding:24px 0 0">
                <div class="stats-grid stats-grid-4 mb-6">
                    <div class="stat-card card-blue">
                        <div class="stat-card-header"><div class="stat-icon blue"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div></div>
                        <div class="stat-label">Total Users</div><div class="stat-value"><?= $totalUsers ?></div><div class="stat-sub">All roles combined</div>
                    </div>
                    <div class="stat-card card-green">
                        <div class="stat-card-header"><div class="stat-icon green"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4" stroke-width="2"/></svg></div></div>
                        <div class="stat-label">Total Patients</div><div class="stat-value"><?= $totalPatients ?></div><div class="stat-sub">Registered patients</div>
                    </div>
                    <div class="stat-card card-red">
                        <div class="stat-card-header"><div class="stat-icon red"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg></div></div>
                        <div class="stat-label">Active Alerts</div><div class="stat-value text-red"><?= $activeAlerts ?></div><div class="stat-sub">Critical patients now</div>
                    </div>
                    <div class="stat-card card-purple">
                        <div class="stat-card-header"><div class="stat-icon purple"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21h8M12 17v4"/></svg></div></div>
                        <div class="stat-label">Total Devices</div><div class="stat-value"><?= count($devices) ?></div><div class="stat-sub">In inventory</div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-header"><div class="section-title">User Role Distribution</div></div>
                    <div style="padding:20px;display:grid;grid-template-columns:repeat(4,1fr);gap:14px">
                        <?php
                        $roles      = ['admin'=>0,'manager'=>0,'rescuer'=>0,'responder'=>0];
                        $roleColors = ['admin'=>'red','manager'=>'blue','rescuer'=>'yellow','responder'=>'green'];
                        foreach ($users as $u) { if (isset($roles[$u['role']])) $roles[$u['role']]++; }
                        foreach ($roles as $r => $count): ?>
                        <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px;text-align:center">
                            <div style="font-size:28px;font-weight:700;color:var(--<?= $roleColors[$r] ?>)"><?= $count ?></div>
                            <div style="font-size:12px;color:var(--text-muted);text-transform:capitalize;margin-top:4px"><?= ucfirst($r) ?>s</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick device summary -->
                <div class="section-card" style="margin-top:20px">
                    <div class="section-header">
                        <div class="section-title">Device Inventory Summary</div>
                        <button class="btn btn-ghost btn-sm" onclick="location.href='?tab=devices'">Manage Devices →</button>
                    </div>
                    <div style="padding:20px;display:grid;grid-template-columns:repeat(4,1fr);gap:14px">
                        <?php
                        $ds = [['label'=>'Usable','count'=>$deviceStats['usable'],'color'=>'#10b981'],['label'=>'In-Use','count'=>$deviceStats['in-use'],'color'=>'#3b82f6'],['label'=>'Maintenance','count'=>$deviceStats['maintenance'],'color'=>'#f59e0b'],['label'=>'Disposable','count'=>$deviceStats['disposable'],'color'=>'#ef4444']];
                        foreach ($ds as $d): ?>
                        <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px;text-align:center">
                            <div style="font-size:26px;font-weight:700;color:<?= $d['color'] ?>"><?= $d['count'] ?></div>
                            <div style="font-size:12px;color:var(--text-muted);margin-top:4px"><?= $d['label'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ══ USERS ══ -->
            <?php elseif ($tab === 'users'): ?>
            <div style="padding:20px 0 0">
                <div class="section-card">
                    <div class="section-header">
                        <div><div class="section-title">All Users</div><div class="section-subtitle"><?= count($users) ?> registered</div></div>
                        <button class="btn btn-primary btn-sm" onclick="openModal('addUserModal')">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add User
                        </button>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Email</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td style="font-weight:600"><?= htmlspecialchars($u['full_name']) ?></td>
                                <td class="td-muted font-mono"><?= htmlspecialchars($u['username']) ?></td>
                                <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                                <td class="td-muted"><?= htmlspecialchars($u['email']??'—') ?></td>
                                <td><span class="badge" style="<?= ($u['status']??'active')==='active'?'background:var(--green-bg);color:var(--green);border:1px solid var(--green-border)':'background:var(--red-bg);color:var(--red);border:1px solid var(--red-border)' ?>">
                                    <?= ucfirst($u['status']??'active') ?></span></td>
                                <td class="td-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-ghost btn-sm" onclick="openEditUser(<?= htmlspecialchars(json_encode($u)) ?>)">Edit</button>
                                    <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                                    <a href="?tab=users&delete_user=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete user <?= htmlspecialchars($u['full_name'], ENT_QUOTES) ?>?')">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ══ PATIENTS ══ -->
            <?php elseif ($tab === 'patients'): ?>
            <div style="padding:20px 0 0">
                <div class="section-card">
                    <div class="section-header">
                        <div><div class="section-title">All Patients</div><div class="section-subtitle"><?= count($patients) ?> registered</div></div>
                        <button class="btn btn-primary btn-sm" onclick="openModal('addPatientModal')">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Patient
                        </button>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead><tr><th>Name</th><th>Age</th><th>Condition</th><th>Rescuer</th><th>Heart Rate</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($patients as $p):
                                $hr = $p['heart_rate'] ?? '—';
                                $st = $p['hr_status']  ?? 'normal';
                                $bc = ['normal'=>'badge-normal','warning'=>'badge-warning','critical'=>'badge-critical'][$st] ?? 'badge-normal';
                                $bl = ['normal'=>'Normal','warning'=>'Warning','critical'=>'Critical'][$st] ?? 'Normal';
                                // Count reports for this patient
                                $rptCount = isset($reportsByPatient[$p['id']]) ? count($reportsByPatient[$p['id']]) : 0;
                            ?>
                            <tr>
                                <td style="font-weight:600"><?= htmlspecialchars($p['name']) ?></td>
                                <td class="td-muted"><?= $p['age'] ?></td>
                                <td class="td-muted"><?= htmlspecialchars($p['medical_condition']??'—') ?></td>
                                <td class="td-muted"><?= htmlspecialchars($p['rescuer_name']??'Unassigned') ?></td>
                                <td>
                                    <?php if (is_numeric($hr)): ?>
                                    <span class="bpm-value <?= ['normal'=>'bpm-normal','warning'=>'bpm-warning','critical'=>'bpm-critical'][$st]??'bpm-normal' ?>" style="font-size:16px"><?= $hr ?></span>
                                    <span style="font-size:11px;color:var(--text-muted)"> BPM</span>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td><span class="badge <?= $bc ?>"><?= $bl ?></span></td>
                                <td>
                                    <!-- View Reports Button -->
                                    <button class="btn btn-ghost btn-sm"
                                        onclick="openPatientReports(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>', <?= $p['age'] ?>, '<?= htmlspecialchars($p['medical_condition']??'—', ENT_QUOTES) ?>')"
                                        style="color:#818cf8;border-color:rgba(99,102,241,.3)">
                                        📋 Reports
                                        <?php if ($rptCount > 0): ?>
                                        <span class="report-count-badge"><?= $rptCount ?></span>
                                        <?php endif; ?>
                                    </button>
                                    <button class="btn btn-ghost btn-sm" onclick="openEditPatient(<?= htmlspecialchars(json_encode($p)) ?>)">Edit</button>
                                    <a href="?tab=patients&delete_patient=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete patient <?= htmlspecialchars($p['name'], ENT_QUOTES) ?>?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ══ DEVICES ══ -->
            <?php elseif ($tab === 'devices'): ?>
            <div style="padding:20px 0 0">
                <div class="device-stats-grid">
                    <div class="device-mini-card"><div class="dmc-label">Usable</div><div class="dmc-count" style="color:#10b981"><?= $deviceStats['usable'] ?></div><div style="font-size:11px;color:#64748b">Ready</div></div>
                    <div class="device-mini-card"><div class="dmc-label">In-Use</div><div class="dmc-count" style="color:#3b82f6"><?= $deviceStats['in-use'] ?></div><div style="font-size:11px;color:#64748b">Assigned</div></div>
                    <div class="device-mini-card"><div class="dmc-label">Maintenance</div><div class="dmc-count" style="color:#f59e0b"><?= $deviceStats['maintenance'] ?></div><div style="font-size:11px;color:#64748b">Repair</div></div>
                    <div class="device-mini-card"><div class="dmc-label">Disposable</div><div class="dmc-count" style="color:#ef4444"><?= $deviceStats['disposable'] ?></div><div style="font-size:11px;color:#64748b">Decommissioned</div></div>
                </div>
                <div class="section-card">
                    <div class="section-header">
                        <div><div class="section-title">All Devices</div><div class="section-subtitle"><?= count($devices) ?> in inventory</div></div>
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
                                        <input type="hidden" name="action" value="update_device_status">
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
                                    <?php if ($d['serial_status'] !== 'disposable'): ?>
                                        <?php if ($d['serial_status'] !== 'in-use'): ?>
                                            <button class="action-btn action-btn-blue" onclick="openAssignModal(<?= $d['id'] ?>,'<?= htmlspecialchars(addslashes($d['label'])) ?>')">Assign</button>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline">
                                                <input type="hidden" name="action" value="unassign_device">
                                                <input type="hidden" name="device_id" value="<?= $d['id'] ?>">
                                                <button type="submit" class="action-btn action-btn-yellow" onclick="return confirm('Unassign?')">Unassign</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <a href="?tab=devices&delete_device=<?= $d['id'] ?>" class="action-btn" style="background:rgba(239,68,68,.15);color:#ef4444"
                                       onclick="return confirm('Delete this device?')">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ══ ANALYTICS ══ -->
            <?php elseif ($tab === 'analytics'): ?>
            <div style="padding:20px 0 0">
                <?php
                $normalPct = $totalLogs > 0 ? round($normalCnt / $totalLogs * 100) : 0;
                $warnPct   = $totalLogs > 0 ? round($warnCnt   / $totalLogs * 100) : 0;
                $critPct   = 100 - $normalPct - $warnPct;
                ?>
                <div class="stats-grid stats-grid-4 mb-6">
                    <div class="stat-card card-purple"><div class="stat-label">Total Readings</div><div class="stat-value"><?= number_format($totalLogs) ?></div><div class="stat-sub">All time</div></div>
                    <div class="stat-card card-green"><div class="stat-label">Normal</div><div class="stat-value text-green"><?= $normalPct ?>%</div><div class="stat-sub">60–99 BPM</div></div>
                    <div class="stat-card card-yellow"><div class="stat-label">Warning</div><div class="stat-value text-yellow"><?= $warnPct ?>%</div><div class="stat-sub">100–120 BPM</div></div>
                    <div class="stat-card card-red"><div class="stat-label">Critical</div><div class="stat-value text-red"><?= $critPct ?>%</div><div class="stat-sub">&lt;60 or &gt;120 BPM</div></div>
                </div>

                <div class="stats-grid stats-grid-2 mb-6">
                    <div class="section-card">
                        <div class="section-header"><div class="section-title">Hourly Heart Rate (Last 12h)</div></div>
                        <?php if (empty($hourlyData)): ?>
                            <div style="text-align:center;padding:60px 20px;color:#64748b">No data in last 12 hours.</div>
                        <?php else: ?>
                        <div class="chart-container"><div class="chart-wrapper" style="height:260px"><canvas id="hourlyChart"></canvas></div></div>
                        <?php endif; ?>
                    </div>
                    <div class="section-card">
                        <div class="section-header"><div class="section-title">Alert Distribution</div></div>
                        <?php if (($normalCnt + $warnCnt + $critCnt) === 0): ?>
                            <div style="text-align:center;padding:60px 20px;color:#64748b">No data.</div>
                        <?php else: ?>
                        <div class="chart-container" style="display:flex;align-items:center;justify-content:center;height:260px">
                            <canvas id="alertPieChart" style="max-height:220px;max-width:220px"></canvas>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

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
            </div>

            <!-- ══ LOGS ══ -->
            <?php elseif ($tab === 'logs'): ?>
            <div style="padding:20px 0 0">
                <div class="section-card">
                    <div class="section-header"><div><div class="section-title">System Logs</div><div class="section-subtitle">Last 100 actions</div></div></div>
                    <div class="table-container">
                        <table>
                            <thead><tr><th>User</th><th>Role</th><th>Action</th><th>Details</th><th>Time</th></tr></thead>
                            <tbody>
                            <?php foreach ($logs as $l): ?>
                            <tr>
                                <td style="font-weight:600"><?= htmlspecialchars($l['full_name']??'System') ?></td>
                                <td><?php if (!empty($l['role'])): ?><span class="badge badge-<?= $l['role'] ?>"><?= ucfirst($l['role']) ?></span><?php endif; ?></td>
                                <td><span style="font-family:monospace;font-size:12px;background:var(--bg-input);padding:2px 6px;border-radius:4px;color:var(--blue)"><?= htmlspecialchars($l['action']) ?></span></td>
                                <td class="td-muted"><?= htmlspecialchars($l['details']??'—') ?></td>
                                <td class="td-muted"><?= date('M d H:i:s', strtotime($l['timestamp'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- MODAL: Add User -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header"><div class="modal-title">Add New User</div><button class="modal-close" onclick="closeModal('addUserModal')">×</button></div>
        <form method="POST" action="?tab=users">
            <input type="hidden" name="action" value="add_user">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Username *</label><input type="text" name="username" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Password *</label><input type="password" name="password" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="">Select role</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="rescuer">Rescuer</option>
                            <option value="responder">Responder</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1"><label class="form-label">Email</label><input type="email" name="email" class="form-input"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add User</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Edit User -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header"><div class="modal-title">Edit User</div><button class="modal-close" onclick="closeModal('editUserModal')">×</button></div>
        <form method="POST" action="?tab=users">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="user_id" id="editUserId">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Full Name *</label><input type="text" name="full_name" id="editFullName" class="form-input" required></div>
                    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="editEmail" class="form-input"></div>
                    <div class="form-group"><label class="form-label">Role *</label>
                        <select name="role" id="editRole" class="form-select">
                            <option value="admin">Admin</option><option value="manager">Manager</option>
                            <option value="rescuer">Rescuer</option><option value="responder">Responder</option>
                        </select>
                    </div>
                    <div class="form-group"><label class="form-label">Status</label>
                        <select name="status" id="editStatus" class="form-select">
                            <option value="active">Active</option><option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1"><label class="form-label">New Password (leave blank to keep)</label><input type="password" name="password" class="form-input"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Add Patient -->
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

<!-- MODAL: Edit Patient -->
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

<!-- MODAL: Add Device -->
<div class="modal-overlay" id="addDeviceModal">
    <div class="modal">
        <div class="modal-header"><div class="modal-title">Add New Device</div><button class="modal-close" onclick="closeModal('addDeviceModal')">×</button></div>
        <form method="POST" action="?tab=devices">
            <input type="hidden" name="action" value="add_device">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group"><label class="form-label">Device Label *</label><input type="text" name="device_label" class="form-input" placeholder="e.g. Heart Monitor Unit A1" required></div>
                    <div class="form-group"><label class="form-label">Device Type *</label>
                        <select name="device_type" class="form-select" required>
                            <option value="">— Select —</option>
                            <option value="Heart Rate Monitor">Heart Rate Monitor</option>
                            <option value="Pulse Oximeter">Pulse Oximeter</option>
                            <option value="AED">AED (Defibrillator)</option>
                            <option value="ECG Machine">ECG Machine</option>
                            <option value="BP Monitor">BP Monitor</option>
                            <option value="Wearable Sensor">Wearable Sensor</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1"><label class="form-label">Serial Number</label><input type="text" name="serial_number" class="form-input" placeholder="e.g. SN-2024-00123"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addDeviceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Device</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Assign Device -->
<div class="modal-overlay" id="assignDeviceModal">
    <div class="modal">
        <div class="modal-header"><div class="modal-title">Assign Device: <span id="assignDeviceName" style="color:#3b82f6"></span></div><button class="modal-close" onclick="closeModal('assignDeviceModal')">×</button></div>
        <form method="POST" action="?tab=devices">
            <input type="hidden" name="action" value="assign_device">
            <input type="hidden" name="device_id" id="assignDeviceId">
            <div class="modal-body">
                <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px">Assign to a patient or rescuer. Leave the other blank.</p>
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('assignDeviceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Confirm</button>
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
            <!-- Patient info strip -->
            <div class="patient-report-info">
                <div style="width:40px;height:40px;border-radius:50%;background:rgba(99,102,241,.15);border:1px solid rgba(99,102,241,.3);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">👤</div>
                <div>
                    <div class="pri-name" id="prPatientName">—</div>
                    <div class="pri-sub" id="prPatientMeta">—</div>
                </div>
            </div>
            <!-- Reports list -->
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

function openEditUser(u) {
    document.getElementById('editUserId').value   = u.id;
    document.getElementById('editFullName').value = u.full_name;
    document.getElementById('editEmail').value    = u.email || '';
    document.getElementById('editRole').value     = u.role;
    document.getElementById('editStatus').value   = u.status || 'active';
    openModal('editUserModal');
}
function openEditPatient(p) {
    document.getElementById('editPatientId').value        = p.id;
    document.getElementById('editPatientName').value      = p.name;
    document.getElementById('editPatientAge').value       = p.age;
    document.getElementById('editPatientCondition').value = p.medical_condition || '';
    document.getElementById('editPatientRescuer').value   = p.assigned_to || '';
    openModal('editPatientModal');
}
function openAssignModal(deviceId, deviceLabel) {
    document.getElementById('assignDeviceId').value         = deviceId;
    document.getElementById('assignDeviceName').textContent = deviceLabel;
    openModal('assignDeviceModal');
}

// ── Patient Reports Modal ────────────────────────────────────────────────────
function openPatientReports(patientId, patientName, patientAge, patientCondition) {
    document.getElementById('prPatientName').textContent = patientName;
    document.getElementById('prPatientMeta').textContent =
        'Age: ' + patientAge + '  •  Condition: ' + (patientCondition || '—');

    const reports = ALL_REPORTS[patientId] || [];
    const sub     = document.getElementById('prModalSub');
    sub.textContent = reports.length + ' report' + (reports.length !== 1 ? 's' : '') + ' submitted by rescuer';

    const list = document.getElementById('prReportsList');

    if (reports.length === 0) {
        list.innerHTML = '<div class="report-empty">📭 No incident reports found for this patient.</div>';
    } else {
        const sevClass = { low:'sev-low', medium:'sev-medium', high:'sev-high', critical:'sev-critical' };
        list.innerHTML = reports.map(function(r) {
            const dt = new Date(r.created_at);
            const dateStr = dt.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
            const timeStr = dt.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' });
            const sc = sevClass[r.severity] || 'sev-medium';
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
function capFirst(s) {
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
}

Chart.defaults.color       = '#94a3b8';
Chart.defaults.borderColor = '#1e293b';

<?php if ($tab === 'analytics' && count($hourlyData) > 0): ?>
new Chart(document.getElementById('hourlyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($hourlyData,'hour_label')) ?>,
        datasets: [{
            label:'Avg Heart Rate', data:<?= json_encode(array_column($hourlyData,'avg_bpm')) ?>,
            borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,0.08)',
            borderWidth:2, tension:0.4, fill:true, pointRadius:3,
        },{
            label:'Critical Count', data:<?= json_encode(array_column($hourlyData,'critical_count')) ?>,
            borderColor:'#ef4444', backgroundColor:'rgba(239,68,68,0.08)',
            borderWidth:2, tension:0.4, fill:true, yAxisID:'y2', pointRadius:3,
        }]
    },
    options:{
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ position:'top' } },
        scales:{
            x:  { grid:{ color:'#1e293b' } },
            y:  { grid:{ color:'#1e293b' }, title:{ display:true, text:'BPM' } },
            y2: { position:'right', grid:{ display:false }, title:{ display:true, text:'Critical' } }
        }
    }
});
<?php endif; ?>

<?php if ($tab === 'analytics' && ($normalCnt + $warnCnt + $critCnt) > 0): ?>
new Chart(document.getElementById('alertPieChart'), {
    type: 'doughnut',
    data: {
        labels: ['Normal','Warning','Critical'],
        datasets: [{ data:[<?= (int)$normalCnt ?>,<?= (int)$warnCnt ?>,<?= (int)$critCnt ?>], backgroundColor:['#10b981','#f59e0b','#ef4444'], borderWidth:0, hoverOffset:6 }]
    },
    options:{ responsive:false, plugins:{ legend:{ position:'bottom' } }, cutout:'65%' }
});
<?php endif; ?>
</script>
</body>
</html>