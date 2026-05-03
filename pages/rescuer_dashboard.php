<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['rescuer', 'admin']);

$user      = getCurrentUser();
$pdo       = getDB();
$tab       = $_GET['tab'] ?? 'overview';
$rescuerId = $user['id'];

// ── Handle incident report submission ───────────────────────────────────────
$msg = $msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_report') {
    $pid   = (int)($_POST['patient_id']  ?? 0);
    $title = trim($_POST['title']        ?? '');
    $desc  = trim($_POST['description']  ?? '');
    $sev   = trim($_POST['severity']     ?? 'medium');
    if ($pid && $title) {
        $pdo->prepare("INSERT INTO incident_reports (rescuer_id,patient_id,incident_type,description,severity) VALUES (?,?,?,?,?)")
            ->execute([$user['id'], $pid, $title, $desc, $sev]);
        logAction($user['id'], 'SUBMIT_REPORT', "Report: $title");
        $msg = 'Incident report submitted successfully.'; $msgType = 'success'; $tab = 'report';
    } else {
        $msg = 'Patient and title are required.'; $msgType = 'error'; $tab = 'report';
    }
}

// ── Fetch assigned patients ──────────────────────────────────────────────────
$patients = $pdo->prepare("
    SELECT p.*, h.heart_rate AS bpm, h.status AS hr_status, h.timestamp AS last_updated
    FROM patients p
    LEFT JOIN heart_rate_logs h ON h.id = (
        SELECT id FROM heart_rate_logs WHERE patient_id = p.id ORDER BY id DESC LIMIT 1
    )
    WHERE p.assigned_to = :rid
    ORDER BY CASE h.status WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END, p.name
");
$patients->execute([':rid' => $rescuerId]);
$patients = $patients->fetchAll();

$criticalCount = count(array_filter($patients, fn($p) => ($p['hr_status'] ?? '') === 'critical'));
$warningCount  = count(array_filter($patients, fn($p) => ($p['hr_status'] ?? '') === 'warning'));

// ── Fetch incident reports ──────────────────────────────────────────────────
$reports = $pdo->prepare("
    SELECT ir.*, p.name AS patient_name
    FROM incident_reports ir
    JOIN patients p ON p.id = ir.patient_id
    WHERE ir.rescuer_id = ?
    ORDER BY ir.created_at DESC LIMIT 20
");
$reports->execute([$user['id']]);
$reports = $reports->fetchAll();

// ── Unread alert count ──────────────────────────────────────────────────────
$unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM alerts WHERE rescuer_id = ? AND is_read = 0");
$unreadStmt->execute([$user['id']]);
$unreadAlerts = (int)$unreadStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Rescuer Dashboard — VitalWear</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
    /* ── Report tab form fields ── */
    .rfield-label {
        font-size: 12px; font-weight: 700; color: #374151;
        text-transform: uppercase; letter-spacing: .5px;
        margin-bottom: 7px; display: flex; align-items: center; gap: 6px;
    }
    .rfield-label i { color: #EF6C52; font-size: 11px; }
    .rfield {
        width: 100%; box-sizing: border-box;
        background: #F9FAFB;
        border: 1.5px solid #C4C9D4;
        border-radius: 10px; padding: 11px 14px;
        font-size: 14px; color: #1E2450;
        font-family: inherit; transition: border-color .2s, box-shadow .2s;
        outline: none; appearance: none;
    }
    .rfield::placeholder { color: #B0B7C3; }
    .rfield:focus {
        border-color: #EF6C52;
        box-shadow: 0 0 0 3px rgba(239,108,82,.14);
        background: #fff;
    }
    select.rfield {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23EF6C52' stroke-width='1.8' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat; background-position: right 14px center;
        padding-right: 36px; cursor: pointer;
    }
    textarea.rfield { min-height: 110px; resize: vertical; line-height: 1.6; }

    /* Severity radio pills */
    .severity-pills { display: flex; gap: 8px; flex-wrap: wrap; }
    .severity-pill input[type="radio"] { display: none; }
    .severity-pill label {
        display: flex; align-items: center; gap: 6px;
        padding: 8px 16px; border-radius: 30px;
        border: 1.5px solid #E5E7EB; background: #F9FAFB;
        font-size: 13px; font-weight: 600; cursor: pointer;
        transition: all .2s; color: #6B7280; user-select: none;
    }
    .severity-pill label:hover { border-color: #D1D5DB; background: #fff; }
    .severity-pill input[value="low"]:checked     + label { background: rgba(239,108,82,.1);  border-color: #EF6C52; color: #EF6C52; }
    .severity-pill input[value="medium"]:checked  + label { background: rgba(245,158,11,.1);  border-color: #f59e0b; color: #d97706; }
    .severity-pill input[value="high"]:checked    + label { background: rgba(239,68,68,.1);   border-color: #ef4444; color: #ef4444; }
    .severity-pill input[value="critical"]:checked + label { background: rgba(139,0,0,.08);   border-color: #b91c1c; color: #b91c1c; }
    .severity-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .dot-low      { background: #EF6C52; }
    .dot-medium   { background: #f59e0b; }
    .dot-high     { background: #ef4444; }
    .dot-critical { background: #b91c1c; }

    /* Submit button */
    .btn-submit-report {
        width: 100%; padding: 14px; border-radius: 12px;
        background: linear-gradient(135deg, #EF6C52, #E05A3A);
        color: #fff; font-size: 15px; font-weight: 700;
        border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        box-shadow: 0 4px 18px rgba(239,108,82,.3);
        transition: all .2s; margin-top: 4px; font-family: inherit;
    }
    .btn-submit-report:hover { filter: brightness(1.07); transform: translateY(-1px); box-shadow: 0 6px 22px rgba(239,108,82,.4); }
    .btn-submit-report:active { transform: translateY(0); }

    /* Report history */
    .report-history-list { display: flex; flex-direction: column; gap: 10px; }
    .report-history-item {
        background: #fff;
        border: 1.5px solid rgba(239,108,82,.20);
        border-radius: 12px; padding: 16px 18px;
        display: grid; grid-template-columns: auto 1fr auto; gap: 14px;
        align-items: center; transition: all .15s;
        box-shadow: 0 2px 8px rgba(30,36,80,.06);
    }
    .report-history-item:hover { border-color: rgba(239,108,82,.40); box-shadow: 0 4px 18px rgba(239,108,82,.12); }
    .report-history-item.sev-critical { border-left: 3px solid #b91c1c; }
    .report-history-item.sev-high     { border-left: 3px solid #ef4444; }
    .report-history-item.sev-medium   { border-left: 3px solid #f59e0b; }
    .report-history-item.sev-low      { border-left: 3px solid #EF6C52; }
    .rh-icon { width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0; }
    .rh-icon.sev-critical { background:rgba(185,28,28,.1);  color:#b91c1c; }
    .rh-icon.sev-high     { background:rgba(239,68,68,.1);   color:#ef4444; }
    .rh-icon.sev-medium   { background:rgba(245,158,11,.1);  color:#d97706; }
    .rh-icon.sev-low      { background:rgba(239,108,82,.1);  color:#EF6C52; }
    .rh-body-title { font-size:14px;font-weight:700;color:#1E2450;margin-bottom:3px; }
    .rh-body-meta  { font-size:12px;color:#9CA3AF;display:flex;align-items:center;gap:8px; }
    .rh-right { text-align:right;flex-shrink:0; }
    .rh-badge { display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.3px;margin-bottom:6px; }
    .rh-badge.sev-critical { background:rgba(185,28,28,.1);color:#b91c1c;border:1px solid rgba(185,28,28,.25); }
    .rh-badge.sev-high     { background:rgba(239,68,68,.1); color:#ef4444;border:1px solid rgba(239,68,68,.25); }
    .rh-badge.sev-medium   { background:rgba(245,158,11,.1);color:#d97706;border:1px solid rgba(245,158,11,.25); }
    .rh-badge.sev-low      { background:rgba(239,108,82,.1);color:#EF6C52;border:1px solid rgba(239,108,82,.25); }
    .rh-time { font-size:11px;color:#9CA3AF; }
    @media(max-width:600px){ .report-history-item{grid-template-columns:auto 1fr;} .rh-right{display:none;} }

    /* Critical section */
    .critical-section-header { background: rgba(239,68,68,.05) !important; }
    .critical-section-title  { color: #ef4444 !important; }

    /* Stat unread special color */
    #statUnread { color: #EF6C52 !important; }
    </style>
</head>
<body>
<div id="sidebarOverlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>
<div class="layout">
    <?php include __DIR__ . '/../includes/sidebar_rescuer.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <span class="page-title">Field Operations</span>
            </div>
            <div class="topbar-right">
                <?php if ($criticalCount > 0): ?>
                <div class="live-indicator" style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#ef4444;border-radius:8px;padding:4px 12px;font-size:12px;font-weight:700;display:flex;align-items:center;gap:6px">
                    <div class="live-dot" style="background:#ef4444;box-shadow:0 0 6px rgba(239,68,68,.5)"></div>
                    <?= $criticalCount ?> CRITICAL
                </div>
                <?php endif; ?>
                <div class="live-indicator"><div class="live-dot"></div>LIVE</div>
                <button onclick="goToAlerts()" class="btn btn-ghost btn-sm" style="position:relative;font-size:13px">
                    <i class="fa-solid fa-bell" style="color:#EF6C52;margin-right:4px"></i> Alerts
                    <span id="alertNavBadge" class="nav-alert-badge" style="<?= $unreadAlerts === 0 ? 'display:none' : '' ?>"><?= $unreadAlerts ?></span>
                </button>
                <span class="topbar-time" id="liveClock"></span>
            </div>
        </div>

        <div class="page-content">

            <?php if ($msg): ?>
            <div style="background:<?= $msgType==='success'?'rgba(239,108,82,.08)':'rgba(239,68,68,.08)' ?>;border:1.5px solid <?= $msgType==='success'?'rgba(239,108,82,.35)':'rgba(239,68,68,.35)' ?>;color:<?= $msgType==='success'?'#EF6C52':'#ef4444' ?>;border-radius:10px;padding:12px 16px;font-size:13px;font-weight:600;margin-bottom:20px;display:flex;align-items:center;gap:8px">
                <i class="fa-solid <?= $msgType==='success'?'fa-circle-check':'fa-triangle-exclamation' ?>"></i>
                <?= htmlspecialchars($msg) ?>
            </div>
            <?php endif; ?>

            <!-- ══ STICKY TAB BAR ══ -->
            <div class="tab-sticky-wrapper">
                <div class="tabs">
                    <button class="tab-btn <?= $tab==='overview'?'active':'' ?>" onclick="location.href='?tab=overview'">
                        <i class="fa-solid fa-gauge-high" style="font-size:11px"></i>Dashboard
                    </button>
                    <button class="tab-btn <?= $tab==='patients'?'active':'' ?>" onclick="location.href='?tab=patients'">
                        <i class="fa-solid fa-user-injured" style="font-size:11px"></i>My Patients (<?= count($patients) ?>)
                    </button>
                    <button class="tab-btn <?= $tab==='alerts'?'active':'' ?>" onclick="location.href='?tab=alerts'">
                        <i class="fa-solid fa-bell" style="font-size:11px"></i>Alerts
                        <?php if ($unreadAlerts > 0): ?>
                        <span class="nav-alert-badge"><?= $unreadAlerts ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="tab-btn <?= $tab==='report'?'active':'' ?>" onclick="location.href='?tab=report'">
                        <i class="fa-solid fa-file-medical" style="font-size:11px"></i>Reports
                    </button>
                </div>
            </div>

            <?php if ($tab === 'overview'): ?>
            <!-- ── OVERVIEW TAB ── -->
            <div class="stats-grid stats-grid-4 mb-6">
                <div class="stat-card card-blue">
                    <div class="stat-card-header">
                        <div class="stat-icon blue"><i class="fa-solid fa-user-injured"></i></div>
                    </div>
                    <div class="stat-label">Assigned Patients</div>
                    <div class="stat-value"><?= count($patients) ?></div>
                    <div class="stat-sub">Under your care</div>
                </div>
                <div class="stat-card card-red">
                    <div class="stat-card-header">
                        <div class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    </div>
                    <div class="stat-label">Critical Now</div>
                    <div class="stat-value text-red" id="criticalBadge"><?= $criticalCount ?></div>
                    <div class="stat-sub">Immediate attention</div>
                </div>
                <div class="stat-card card-yellow">
                    <div class="stat-card-header">
                        <div class="stat-icon yellow"><i class="fa-solid fa-circle-exclamation"></i></div>
                    </div>
                    <div class="stat-label">Warning</div>
                    <div class="stat-value text-yellow"><?= $warningCount ?></div>
                    <div class="stat-sub">Monitor closely</div>
                </div>
                <div class="stat-card card-blue">
                    <div class="stat-card-header">
                        <div class="stat-icon coral" style="background:rgba(239,108,82,.12);color:#EF6C52"><i class="fa-solid fa-bell"></i></div>
                    </div>
                    <div class="stat-label">Unread Alerts</div>
                    <div class="stat-value" id="statUnread"><?= $unreadAlerts ?></div>
                    <div class="stat-sub">From responder</div>
                </div>
            </div>

            <?php $criticals = array_filter($patients, fn($p) => ($p['hr_status']??'') === 'critical');
            if (count($criticals) > 0): ?>
            <div class="section-card" style="margin-bottom:20px;border-left:4px solid #ef4444;">
                <div class="section-header critical-section-header">
                    <div class="section-title critical-section-title">
                        <i class="fa-solid fa-triangle-exclamation" style="margin-right:6px"></i>Critical — Immediate Action Required
                    </div>
                </div>
                <div style="padding:16px"><div class="patient-grid">
                <?php foreach ($criticals as $p):
                    $bpm     = $p['bpm'] ?? 0;
                    $lastUpd = $p['last_updated'] ? date('H:i:s', strtotime($p['last_updated'])) : '—';
                ?>
                <div class="big-patient-card critical-card" id="bigcard-<?= $p['id'] ?>">
                    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px">
                        <div>
                            <div style="font-weight:700;font-size:16px;color:#1E2450"><?= htmlspecialchars($p['name']) ?></div>
                            <div style="font-size:12px;color:#9CA3AF">Age <?= $p['age'] ?></div>
                        </div>
                        <span class="badge badge-critical">CRITICAL</span>
                    </div>
                    <div class="bpm-large bpm-critical bpm-display"><?= $bpm ?><span style="font-size:16px;font-weight:400;color:#9CA3AF"> BPM</span></div>
                    <div style="font-size:12px;color:#9CA3AF;margin-top:8px" class="last-upd-<?= $p['id'] ?>">Updated: <?= $lastUpd ?></div>
                    <div style="margin-top:12px">
                        <button class="btn btn-danger w-full"
                            onclick="quickReport(<?= $p['id'] ?>,'<?= htmlspecialchars($p['name'],ENT_QUOTES) ?>')">
                            <i class="fa-solid fa-file-circle-exclamation"></i>Report Incident
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                </div></div>
            </div>
            <?php endif; ?>

            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">
                        <i class="fa-solid fa-heart-pulse" style="color:#EF6C52;margin-right:6px"></i>All My Patients
                    </div>
                    <div class="live-indicator"><div class="live-dot"></div>Live</div>
                </div>
                <div style="padding:16px"><div class="patient-grid">
                <?php foreach ($patients as $p):
                    $bpm     = $p['bpm'] ?? 0;
                    $st      = $p['hr_status'] ?? 'normal';
                    $bpmCls  = ['normal'=>'bpm-normal','warning'=>'bpm-warning','critical'=>'bpm-critical'][$st] ?? 'bpm-normal';
                    $lastUpd = $p['last_updated'] ? date('H:i:s', strtotime($p['last_updated'])) : '—';
                ?>
                <div class="big-patient-card <?= $st ?>-card" id="pcard-<?= $p['id'] ?>">
                    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:10px">
                        <div>
                            <div style="font-weight:700;font-size:15px;color:#1E2450"><?= htmlspecialchars($p['name']) ?></div>
                            <div style="font-size:12px;color:#9CA3AF">Age <?= $p['age'] ?></div>
                        </div>
                        <span class="badge badge-<?= $st ?> pcard-badge"><?= ucfirst($st) ?></span>
                    </div>
                    <div class="bpm-large <?= $bpmCls ?> bpm-display"><?= $bpm ?><span style="font-size:14px;font-weight:400;color:#9CA3AF"> BPM</span></div>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:8px;display:flex;justify-content:space-between">
                        <span style="color:#6B7280"><?= htmlspecialchars($p['medical_condition']??'—') ?></span>
                        <span class="last-upd-<?= $p['id'] ?>"><?= $lastUpd ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                </div></div>
            </div>

            <?php elseif ($tab === 'patients'): ?>
            <!-- ── PATIENTS TAB ── -->
            <?php if (count($patients) === 0): ?>
            <div class="section-card">
                <div style="padding:60px;text-align:center">
                    <i class="fa-solid fa-user-injured" style="font-size:40px;color:rgba(239,108,82,.3);margin-bottom:16px;display:block"></i>
                    <div style="color:#6B7280;font-size:15px">No patients assigned to you yet.</div>
                </div>
            </div>
            <?php else: ?>
            <div class="patient-grid">
            <?php foreach ($patients as $p):
                $bpm     = $p['bpm'] ?? 0;
                $st      = $p['hr_status'] ?? 'normal';
                $bpmCls  = ['normal'=>'bpm-normal','warning'=>'bpm-warning','critical'=>'bpm-critical'][$st] ?? 'bpm-normal';
                $lastUpd = $p['last_updated'] ? date('H:i:s', strtotime($p['last_updated'])) : '—';
            ?>
            <div class="big-patient-card <?= $st ?>-card" id="pcard2-<?= $p['id'] ?>">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px">
                    <div>
                        <div style="font-weight:700;font-size:16px;color:#1E2450"><?= htmlspecialchars($p['name']) ?></div>
                        <div style="font-size:12px;color:#9CA3AF">Age <?= $p['age'] ?></div>
                        <div style="font-size:12px;color:#6B7280;margin-top:2px"><?= htmlspecialchars($p['medical_condition']??'No condition noted') ?></div>
                    </div>
                    <span class="badge badge-<?= $st ?>"><?= ucfirst($st) ?></span>
                </div>
                <div class="bpm-large <?= $bpmCls ?>"><?= $bpm ?><span style="font-size:16px;font-weight:400;color:#9CA3AF"> BPM</span></div>
                <div style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(239,108,82,.1);display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:12px;color:#9CA3AF">Updated: <?= $lastUpd ?></span>
                    <button class="btn btn-danger btn-sm"
                        onclick="quickReport(<?= $p['id'] ?>,'<?= htmlspecialchars($p['name'],ENT_QUOTES) ?>')">
                        <i class="fa-solid fa-file-circle-exclamation" style="margin-right:4px"></i>Report
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php elseif ($tab === 'alerts'): ?>
            <!-- ── ALERTS TAB ── -->
            <div class="section-card">
                <div class="section-header">
                    <div>
                        <div class="section-title">
                            <i class="fa-solid fa-bell" style="color:#EF6C52;margin-right:6px"></i>Alerts from Responder
                        </div>
                        <div class="section-subtitle">Real-time alerts sent to you about your patients</div>
                    </div>
                    <button class="btn-mark-read" onclick="markAllRead()">
                        <i class="fa-solid fa-check-double" style="margin-right:5px"></i>Mark all as read
                    </button>
                </div>
                <div style="padding:16px">
                    <div class="alert-inbox" id="alertInbox">
                        <div style="text-align:center;padding:40px;color:#9CA3AF">
                            <i class="fa-solid fa-spinner fa-spin" style="font-size:24px;color:#EF6C52;margin-bottom:12px;display:block"></i>
                            Loading alerts…
                        </div>
                    </div>
                </div>
            </div>

            <?php elseif ($tab === 'report'): ?>
            <!-- ── REPORT TAB ── -->
            <div class="section-card" style="margin-bottom:20px">
                <div style="padding:20px 24px 0">
                    <div style="display:flex;align-items:center;gap:12px;padding-bottom:18px;border-bottom:1px solid rgba(239,108,82,.12)">
                        <div style="width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,rgba(239,108,82,.15),rgba(224,90,58,.08));border:1px solid rgba(239,108,82,.25);display:flex;align-items:center;justify-content:center">
                            <i class="fa-solid fa-file-medical" style="color:#EF6C52;font-size:18px"></i>
                        </div>
                        <div>
                            <div style="font-size:16px;font-weight:700;color:#1E2450">New Incident Report</div>
                            <div style="font-size:12px;color:#9CA3AF;margin-top:1px">Document and submit field incidents</div>
                        </div>
                    </div>
                </div>
                <div style="padding:20px 24px 24px">
                    <form method="POST" action="?tab=report">
                        <input type="hidden" name="action" value="submit_report">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                            <div>
                                <div class="rfield-label"><i class="fa-solid fa-user-injured"></i> Patient *</div>
                                <select name="patient_id" class="rfield" required>
                                    <option value="">— Select patient —</option>
                                    <?php foreach ($patients as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <div class="rfield-label"><i class="fa-solid fa-heading"></i> Report Title *</div>
                                <input type="text" name="title" class="rfield" placeholder="e.g. Sudden BPM spike" required>
                            </div>
                        </div>
                        <div style="margin-bottom:16px">
                            <div class="rfield-label"><i class="fa-solid fa-circle-exclamation"></i> Severity Level *</div>
                            <div class="severity-pills">
                                <div class="severity-pill">
                                    <input type="radio" name="severity" id="sev_low" value="low">
                                    <label for="sev_low"><span class="severity-dot dot-low"></span>Low</label>
                                </div>
                                <div class="severity-pill">
                                    <input type="radio" name="severity" id="sev_medium" value="medium" checked>
                                    <label for="sev_medium"><span class="severity-dot dot-medium"></span>Medium</label>
                                </div>
                                <div class="severity-pill">
                                    <input type="radio" name="severity" id="sev_high" value="high">
                                    <label for="sev_high"><span class="severity-dot dot-high"></span>High</label>
                                </div>
                                <div class="severity-pill">
                                    <input type="radio" name="severity" id="sev_critical" value="critical">
                                    <label for="sev_critical"><span class="severity-dot dot-critical"></span>Critical</label>
                                </div>
                            </div>
                        </div>
                        <div style="margin-bottom:20px">
                            <div class="rfield-label">
                                <i class="fa-solid fa-pen-to-square"></i> Description
                                <span style="font-weight:400;color:#B0B7C3;text-transform:none;letter-spacing:0;font-size:11px">(optional)</span>
                            </div>
                            <textarea name="description" class="rfield" placeholder="Describe what happened, observations, actions taken…"></textarea>
                        </div>
                        <button type="submit" class="btn-submit-report">
                            <i class="fa-solid fa-paper-plane"></i> Submit Incident Report
                        </button>
                    </form>
                </div>
            </div>

            <!-- REPORT HISTORY -->
            <div class="section-card">
                <div class="section-header">
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="width:36px;height:36px;border-radius:9px;background:rgba(239,108,82,.1);display:flex;align-items:center;justify-content:center">
                            <i class="fa-solid fa-clock-rotate-left" style="color:#EF6C52;font-size:15px"></i>
                        </div>
                        <div>
                            <div class="section-title" style="margin:0">My Submitted Reports</div>
                            <div class="section-subtitle" style="margin:0"><?= count($reports) ?> report<?= count($reports) !== 1 ? 's' : '' ?> total</div>
                        </div>
                    </div>
                </div>
                <div style="padding:0 20px 20px">
                    <?php if (count($reports) === 0): ?>
                    <div style="padding:50px 20px;text-align:center">
                        <div style="width:60px;height:60px;border-radius:50%;background:rgba(239,108,82,.08);border:2px dashed rgba(239,108,82,.2);display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                            <i class="fa-solid fa-file-circle-xmark" style="font-size:24px;color:rgba(239,108,82,.4)"></i>
                        </div>
                        <div style="font-size:15px;font-weight:600;color:#374151;margin-bottom:4px">No reports yet</div>
                        <div style="font-size:13px;color:#9CA3AF">Submit your first incident report using the form above.</div>
                    </div>
                    <?php else: ?>
                    <div class="report-history-list">
                    <?php
                    $sevIcons = ['critical'=>'fa-skull-crossbones','high'=>'fa-triangle-exclamation','medium'=>'fa-circle-exclamation','low'=>'fa-circle-info'];
                    foreach ($reports as $r):
                        $sev     = $r['severity'] ?? 'medium';
                        $icon    = $sevIcons[$sev] ?? 'fa-circle-exclamation';
                        $dateStr = date('M d', strtotime($r['created_at']));
                        $timeStr = date('H:i', strtotime($r['created_at']));
                    ?>
                    <div class="report-history-item sev-<?= $sev ?>">
                        <div class="rh-icon sev-<?= $sev ?>">
                            <i class="fa-solid <?= $icon ?>"></i>
                        </div>
                        <div class="rh-body">
                            <div class="rh-body-title"><?= htmlspecialchars($r['incident_type']) ?></div>
                            <div class="rh-body-meta">
                                <span><i class="fa-solid fa-user-injured" style="font-size:10px"></i> <?= htmlspecialchars($r['patient_name']) ?></span>
                                <span style="color:#D1D5DB">·</span>
                                <span><i class="fa-regular fa-clock" style="font-size:10px"></i> <?= $dateStr ?>, <?= $timeStr ?></span>
                            </div>
                        </div>
                        <div class="rh-right">
                            <div class="rh-badge sev-<?= $sev ?>"><?= ucfirst($sev) ?></div>
                            <div class="rh-time"><?= $dateStr ?> at <?= $timeStr ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- ══ MODAL: Quick Report — matches admin modal style ══ -->
<div class="modal-overlay" id="quickReportModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <i class="fa-solid fa-file-circle-exclamation" style="color:#ef4444;margin-right:8px"></i>Quick Incident Report
            </div>
            <button class="modal-close" onclick="closeModal('quickReportModal')">×</button>
        </div>
        <form method="POST" action="?tab=report">
            <input type="hidden" name="action" value="submit_report">
            <input type="hidden" name="patient_id" id="quickPatientId">
            <div class="modal-body">
                <div style="margin-bottom:14px;padding:12px 16px;background:rgba(239,68,68,.06);border:1.5px solid rgba(239,68,68,.25);border-radius:10px">
                    <div style="font-size:13px;font-weight:700;color:#ef4444">
                        <i class="fa-solid fa-user-injured" style="margin-right:5px"></i>Patient: <span id="quickPatientName"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Severity</label>
                    <select name="severity" class="form-select">
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-input" required placeholder="Brief incident description">
                </div>
                <div class="form-group">
                    <label class="form-label">Details</label>
                    <textarea name="description" class="form-textarea" placeholder="Additional details…"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('quickReportModal')">Cancel</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-paper-plane" style="margin-right:5px"></i>Submit Report
                </button>
            </div>
        </form>
    </div>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="../assets/js/scripts.js"></script>
<script>
const MY_PATIENT_IDS = [<?= implode(',', array_column($patients, 'id') ?: [0]) ?>];

// Clock
(function tick(){
    const el = document.getElementById('liveClock');
    if (el) el.textContent = new Date().toLocaleTimeString();
    setTimeout(tick, 1000);
})();

// Modal helpers — admin-style
function openModal(id)  { const el=document.getElementById(id); if(!el)return; el.classList.add('open','active'); el.style.display='flex'; }
function closeModal(id) { const el=document.getElementById(id); if(!el)return; el.classList.remove('open','active'); el.style.display=''; }
document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => { if(e.target===m) closeModal(m.id); }));

function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    if (!sb) return;
    const open = sb.classList.toggle('open');
    if (ov) ov.classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
}

async function fetchLiveDataRescuer() {
    try {
        const res  = await fetch('../api/get_heart_rate.php');
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        data.patients.forEach(p => {
            if (!MY_PATIENT_IDS.includes(p.id)) return;
            const st     = p.status || 'normal';
            const hr     = p.heart_rate;
            const bpmCls = {normal:'bpm-normal',warning:'bpm-warning',critical:'bpm-critical'}[st]||'bpm-normal';
            const badgeCls = {normal:'badge-normal',warning:'badge-warning',critical:'badge-critical'}[st]||'badge-normal';
            const lastUpd  = p.last_updated ? new Date(p.last_updated).toLocaleTimeString() : '—';

            ['pcard-','bigcard-','pcard2-'].forEach(prefix => {
                const card = document.getElementById(prefix + p.id);
                if (!card) return;
                card.className = card.className.replace(/\b(critical|warning|normal)-card\b/, `${st}-card`);
                const bpmEl = card.querySelector('.bpm-display, .bpm-large');
                if (bpmEl) {
                    bpmEl.className = bpmEl.className.replace(/bpm-(normal|warning|critical)\b/, bpmCls);
                    bpmEl.childNodes[0].textContent = hr;
                }
                const badge = card.querySelector('.pcard-badge, .badge');
                if (badge) {
                    badge.className = `badge ${badgeCls}${badge.classList.contains('pcard-badge')?' pcard-badge':''}`;
                    badge.textContent = st.charAt(0).toUpperCase()+st.slice(1);
                }
                const lu = card.querySelector('.last-upd-' + p.id);
                if (lu) lu.textContent = lastUpd;
            });
        });

        const cb = document.getElementById('criticalBadge');
        if (cb) cb.textContent = data.summary.critical;

    } catch(err) { console.warn('Rescuer live update failed:', err); }
}

let lastAlertCount = <?= $unreadAlerts ?>;

async function fetchAlerts() {
    try {
        const res  = await fetch('../api/get_alerts.php');
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        const badge  = document.getElementById('alertNavBadge');
        const statEl = document.getElementById('statUnread');
        const unread = data.unread_count;

        if (badge)  { badge.textContent = unread; badge.style.display = unread > 0 ? '' : 'none'; }
        if (statEl) statEl.textContent = unread;
        if (typeof updateRescuerAlertBadge === 'function') updateRescuerAlertBadge(unread);

        if (unread > lastAlertCount && lastAlertCount >= 0) {
            if (typeof playAlertSound === 'function') playAlertSound();
            if (typeof showToast === 'function') {
                const newest = data.alerts.find(a => a.is_read == 0);
                if (newest) showToast('🔔 New Alert', `${newest.patient_name}: ${newest.message.substring(0,60)}…`, 'warning', 6000);
            }
        }
        lastAlertCount = unread;

        const inbox = document.getElementById('alertInbox');
        if (!inbox) return;

        if (!data.alerts || data.alerts.length === 0) {
            inbox.innerHTML = `
                <div style="text-align:center;padding:40px">
                    <i class="fa-solid fa-bell-slash" style="font-size:36px;color:rgba(239,108,82,.3);margin-bottom:12px;display:block"></i>
                    <div style="color:#9CA3AF">No alerts received yet.</div>
                </div>`;
            return;
        }

        inbox.innerHTML = data.alerts.map(a => {
            const isUnread = a.is_read == 0;
            const timeAgo  = formatTimeAgo(a.created_at);
            return `
            <div class="alert-item ${isUnread ? 'unread' : ''}" id="alert-${a.id}">
                <div class="alert-item-header">
                    <div class="alert-item-patient">
                        ${isUnread ? '<span class="unread-dot"></span>' : ''}
                        ${escapeHtml(a.patient_name)}
                    </div>
                    <span class="alert-item-time">${timeAgo}</span>
                </div>
                <div class="alert-item-from">
                    <i class="fa-solid fa-shield-halved" style="margin-right:4px;font-size:10px"></i>From: ${escapeHtml(a.sent_by_name)}
                </div>
                <div class="alert-item-msg">${escapeHtml(a.message)}</div>
            </div>`;
        }).join('');

    } catch(err) { console.warn('Alert fetch failed:', err); }
}

async function markAllRead() {
    await fetch('../api/get_alerts.php?mark_read=1');
    lastAlertCount = 0;
    fetchAlerts();
}

function goToAlerts() { location.href = '?tab=alerts'; }

function formatTimeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)    return 'just now';
    if (diff < 3600)  return Math.floor(diff/60)+'m ago';
    if (diff < 86400) return Math.floor(diff/3600)+'h ago';
    return new Date(dateStr).toLocaleDateString();
}

function escapeHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function quickReport(patientId, patientName) {
    document.getElementById('quickPatientId').value         = patientId;
    document.getElementById('quickPatientName').textContent = patientName;
    openModal('quickReportModal');
}

<?php if ($tab === 'alerts'): ?>
document.addEventListener('DOMContentLoaded', fetchAlerts);
<?php endif; ?>

setInterval(fetchLiveDataRescuer, 5000);
setInterval(fetchAlerts, 8000);
setTimeout(fetchLiveDataRescuer, 500);
setTimeout(fetchAlerts, 800);
</script>
</body>
</html>
