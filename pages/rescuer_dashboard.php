<?php
/**
 * Rescuer Dashboard — Field Operations
 */
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
// FIX: Use patients.assigned_to so it matches what admin/manager write to.
//      The old query used INNER JOIN patient_assignments which is never written
//      to by the admin/manager dashboards, causing rescuers to see 0 patients.
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
    <title>Rescuer Dashboard — HeartCare</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
    .patient-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:14px; }
    @media(max-width:640px){ .patient-grid{ grid-template-columns:1fr; } }

    .big-patient-card { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:18px; transition:var(--transition); }
    .big-patient-card:hover { background:var(--bg-card-hover); }
    .big-patient-card.critical-card { border-left:4px solid var(--red);    background:rgba(239,68,68,0.04); }
    .big-patient-card.warning-card  { border-left:4px solid var(--yellow); background:rgba(245,158,11,0.03); }
    .big-patient-card.normal-card   { border-left:4px solid var(--green);  }

    .bpm-large { font-size:42px; font-weight:800; line-height:1; }

    .severity-high     { background:var(--red-bg);    color:var(--red);    border:1px solid var(--red-border); }
    .severity-medium   { background:var(--yellow-bg); color:var(--yellow); border:1px solid var(--yellow-border); }
    .severity-low      { background:var(--green-bg);  color:var(--green);  border:1px solid var(--green-border); }
    .severity-critical { background:var(--red-bg);    color:var(--red);    border:1px solid var(--red-border); animation:bpm-pulse 1s infinite; }

    /* Alert inbox */
    .alert-inbox { display:flex; flex-direction:column; gap:10px; }
    .alert-item { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:14px 16px; }
    .alert-item.unread { border-left:4px solid #6366f1; background:rgba(99,102,241,0.06); }
    .alert-item-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
    .alert-item-patient { font-weight:700; font-size:14px; color:var(--text-primary); }
    .alert-item-time { font-size:11px; color:var(--text-muted); }
    .alert-item-from { font-size:12px; color:var(--text-muted); margin-bottom:6px; }
    .alert-item-msg { font-size:13px; color:var(--text-secondary); line-height:1.5; }
    .unread-dot { width:8px; height:8px; border-radius:50%; background:#6366f1; display:inline-block; margin-right:5px; }

    /* Nav badge */
    .nav-alert-badge { background:#6366f1; color:#fff; font-size:10px; font-weight:800; border-radius:20px; padding:1px 6px; margin-left:5px; }
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
                <div class="live-indicator" style="background:var(--red-bg);border-color:var(--red-border);color:var(--red);">
                    <div class="live-dot" style="background:var(--red)"></div>
                    <?= $criticalCount ?> CRITICAL
                </div>
                <?php endif; ?>
                <div class="live-indicator"><div class="live-dot"></div>LIVE</div>
                <button onclick="goToAlerts()" class="btn btn-ghost btn-sm" style="position:relative;font-size:13px">
                    🔔 Alerts
                    <span id="alertNavBadge" class="nav-alert-badge" style="<?= $unreadAlerts === 0 ? 'display:none' : '' ?>"><?= $unreadAlerts ?></span>
                </button>
                <span class="topbar-time" id="liveClock"></span>
            </div>
        </div>

        <div class="page-content">

            <?php if ($msg): ?>
            <div style="background:<?= $msgType==='success'?'var(--green-bg)':'var(--red-bg)' ?>;border:1px solid <?= $msgType==='success'?'var(--green-border)':'var(--red-border)' ?>;color:<?= $msgType==='success'?'var(--green)':'var(--red)' ?>;border-radius:var(--radius-sm);padding:12px 16px;font-size:13px;margin-bottom:16px">
                <?= $msgType==='success'?'✓':'⚠️' ?> <?= htmlspecialchars($msg) ?>
            </div>
            <?php endif; ?>

            <!-- TABS -->
            <div class="section-card" style="margin-bottom:20px">
                <div class="tabs">
                    <button class="tab-btn <?= $tab==='overview'?'active':'' ?>" onclick="location.href='?tab=overview'">Dashboard</button>
                    <button class="tab-btn <?= $tab==='patients'?'active':'' ?>" onclick="location.href='?tab=patients'">My Patients (<?= count($patients) ?>)</button>
                    <button class="tab-btn <?= $tab==='alerts'?'active':'' ?>" onclick="location.href='?tab=alerts'">
                        Alerts
                        <?php if ($unreadAlerts > 0): ?>
                        <span class="nav-alert-badge"><?= $unreadAlerts ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="tab-btn <?= $tab==='report'?'active':'' ?>" onclick="location.href='?tab=report'">Reports</button>
                </div>
            </div>

            <?php if ($tab === 'overview'): ?>
            <!-- ── OVERVIEW TAB ── -->
            <div class="stats-grid stats-grid-4 mb-6">
                <div class="stat-card card-blue">
                    <div class="stat-label">Assigned Patients</div>
                    <div class="stat-value"><?= count($patients) ?></div>
                    <div class="stat-sub">Under your care</div>
                </div>
                <div class="stat-card card-red">
                    <div class="stat-label">Critical Now</div>
                    <div class="stat-value text-red" id="criticalBadge"><?= $criticalCount ?></div>
                    <div class="stat-sub">Immediate attention</div>
                </div>
                <div class="stat-card card-yellow">
                    <div class="stat-label">Warning</div>
                    <div class="stat-value text-yellow"><?= $warningCount ?></div>
                    <div class="stat-sub">Monitor closely</div>
                </div>
                <div class="stat-card card-blue">
                    <div class="stat-label">Unread Alerts</div>
                    <div class="stat-value" id="statUnread" style="color:#6366f1"><?= $unreadAlerts ?></div>
                    <div class="stat-sub">From responder</div>
                </div>
            </div>

            <?php $criticals = array_filter($patients, fn($p) => ($p['hr_status']??'') === 'critical');
            if (count($criticals) > 0): ?>
            <div class="section-card" style="margin-bottom:20px;border-left:4px solid var(--red)">
                <div class="section-header" style="background:rgba(239,68,68,0.06)">
                    <div class="section-title" style="color:var(--red)">🚨 Critical — Immediate Action Required</div>
                </div>
                <div style="padding:16px"><div class="patient-grid">
                <?php foreach ($criticals as $p):
                    $bpm     = $p['bpm'] ?? 0;
                    $lastUpd = $p['last_updated'] ? date('H:i:s', strtotime($p['last_updated'])) : '—';
                ?>
                <div class="big-patient-card critical-card" id="bigcard-<?= $p['id'] ?>">
                    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:12px">
                        <div>
                            <div style="font-weight:700;font-size:16px"><?= htmlspecialchars($p['name']) ?></div>
                            <div style="font-size:12px;color:var(--text-muted)">Age <?= $p['age'] ?></div>
                        </div>
                        <span class="badge badge-critical">CRITICAL</span>
                    </div>
                    <div class="bpm-large bpm-critical bpm-display"><?= $bpm ?><span style="font-size:16px;font-weight:400;color:var(--text-muted)"> BPM</span></div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:8px" class="last-upd-<?= $p['id'] ?>">Updated: <?= $lastUpd ?></div>
                    <div style="margin-top:12px">
                        <button class="btn btn-danger btn-sm w-full"
                            onclick="quickReport(<?= $p['id'] ?>,'<?= htmlspecialchars($p['name'],ENT_QUOTES) ?>')">
                            Report Incident
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                </div></div>
            </div>
            <?php endif; ?>

            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">All My Patients</div>
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
                            <div style="font-weight:700;font-size:15px"><?= htmlspecialchars($p['name']) ?></div>
                            <div style="font-size:12px;color:var(--text-muted)">Age <?= $p['age'] ?></div>
                        </div>
                        <span class="badge badge-<?= $st ?> pcard-badge"><?= ucfirst($st) ?></span>
                    </div>
                    <div class="bpm-large <?= $bpmCls ?> bpm-display"><?= $bpm ?><span style="font-size:14px;font-weight:400;color:var(--text-muted)"> BPM</span></div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:8px;display:flex;justify-content:space-between">
                        <span><?= htmlspecialchars($p['medical_condition']??'—') ?></span>
                        <span class="last-upd-<?= $p['id'] ?>"><?= $lastUpd ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                </div></div>
            </div>

            <?php elseif ($tab === 'patients'): ?>
            <!-- ── PATIENTS TAB ── -->
            <?php if (count($patients) === 0): ?>
            <div class="section-card"><div style="padding:60px;text-align:center;color:var(--text-muted)">No patients assigned to you yet.</div></div>
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
                        <div style="font-weight:700;font-size:16px"><?= htmlspecialchars($p['name']) ?></div>
                        <div style="font-size:12px;color:var(--text-muted)">Age <?= $p['age'] ?></div>
                        <div style="font-size:12px;color:var(--text-secondary);margin-top:2px"><?= htmlspecialchars($p['medical_condition']??'No condition noted') ?></div>
                    </div>
                    <span class="badge badge-<?= $st ?>"><?= ucfirst($st) ?></span>
                </div>
                <div class="bpm-large <?= $bpmCls ?>"><?= $bpm ?><span style="font-size:16px;font-weight:400;color:var(--text-muted)"> BPM</span></div>
                <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:12px;color:var(--text-muted)">Updated: <?= $lastUpd ?></span>
                    <button class="btn btn-danger btn-sm"
                        onclick="quickReport(<?= $p['id'] ?>,'<?= htmlspecialchars($p['name'],ENT_QUOTES) ?>')">
                        Report Incident
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
                        <div class="section-title">🔔 Alerts from Responder</div>
                        <div class="section-subtitle">Real-time alerts sent to you about your patients</div>
                    </div>
                    <button class="btn btn-ghost btn-sm" onclick="markAllRead()">Mark all as read</button>
                </div>
                <div style="padding:16px">
                    <div class="alert-inbox" id="alertInbox">
                        <div style="text-align:center;padding:40px;color:var(--text-muted)">Loading alerts...</div>
                    </div>
                </div>
            </div>

            <?php elseif ($tab === 'report'): ?>
            <!-- ── REPORT TAB ── -->
            <div class="section-card" style="margin-bottom:20px">
                <div class="section-header"><div class="section-title">Submit Incident Report</div></div>
                <div style="padding:20px">
                    <form method="POST" action="?tab=report">
                        <input type="hidden" name="action" value="submit_report">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                            <div class="form-group">
                                <label class="form-label">Patient *</label>
                                <select name="patient_id" class="form-select" required>
                                    <option value="">Select patient</option>
                                    <?php foreach ($patients as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Severity *</label>
                                <select name="severity" class="form-select">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div class="form-group" style="grid-column:1/-1">
                                <label class="form-label">Report Title *</label>
                                <input type="text" name="title" class="form-input" placeholder="e.g., Sudden BPM spike detected" required>
                            </div>
                            <div class="form-group" style="grid-column:1/-1">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-textarea" placeholder="Describe the incident in detail..."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-danger" style="width:100%;padding:12px;font-size:15px;margin-top:6px">Submit Report</button>
                    </form>
                </div>
            </div>
            <div class="section-card">
                <div class="section-header">
                    <div class="section-title">My Submitted Reports</div>
                    <div class="section-subtitle"><?= count($reports) ?> total</div>
                </div>
                <?php if (count($reports) === 0): ?>
                <div style="padding:40px;text-align:center;color:var(--text-muted)">No reports submitted yet.</div>
                <?php else: ?>
                <div class="table-container"><table>
                    <thead><tr><th>Patient</th><th>Title</th><th>Severity</th><th>Submitted</th></tr></thead>
                    <tbody>
                    <?php foreach ($reports as $r): ?>
                    <tr>
                        <td style="font-weight:600"><?= htmlspecialchars($r['patient_name']) ?></td>
                        <td class="td-muted"><?= htmlspecialchars($r['incident_type']) ?></td>
                        <td><span class="badge severity-<?= $r['severity'] ?>"><?= ucfirst($r['severity']) ?></span></td>
                        <td class="td-muted"><?= date('M d, H:i', strtotime($r['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- QUICK REPORT MODAL -->
<div class="modal-overlay" id="quickReportModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">Quick Incident Report</div>
            <button class="modal-close" onclick="closeModal('quickReportModal')">×</button>
        </div>
        <form method="POST" action="?tab=report">
            <input type="hidden" name="action" value="submit_report">
            <input type="hidden" name="patient_id" id="quickPatientId">
            <div class="modal-body">
                <div style="margin-bottom:14px;padding:12px;background:var(--red-bg);border:1px solid var(--red-border);border-radius:var(--radius-sm)">
                    <div style="font-size:13px;font-weight:700;color:var(--red)">Patient: <span id="quickPatientName"></span></div>
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
                    <textarea name="description" class="form-textarea" style="min-height:80px" placeholder="Additional details..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('quickReportModal')">Cancel</button>
                <button type="submit" class="btn btn-danger">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="../assets/js/scripts.js"></script>
<script>
const MY_PATIENT_IDS = [<?= implode(',', array_column($patients, 'id') ?: [0]) ?>];

// ═══════════════════════════════════════════════════
// LIVE PATIENT REFRESH
// ═══════════════════════════════════════════════════
async function fetchLiveDataRescuer() {
    try {
        const res  = await fetch('../api/get_heart_rate.php');
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        data.patients.forEach(p => {
            if (!MY_PATIENT_IDS.includes(p.id)) return;
            const st      = p.status || 'normal';
            const hr      = p.heart_rate;
            const bpmCls  = {normal:'bpm-normal',warning:'bpm-warning',critical:'bpm-critical'}[st]||'bpm-normal';
            const badgeCls= {normal:'badge-normal',warning:'badge-warning',critical:'badge-critical'}[st]||'badge-normal';
            const lastUpd = p.last_updated ? new Date(p.last_updated).toLocaleTimeString() : '—';

            ['pcard-','bigcard-','pcard2-'].forEach(prefix => {
                const card = document.getElementById(prefix + p.id);
                if (!card) return;
                card.className = card.className.replace(/\b(critical|warning|normal)-card\b/, `${st}-card`);
                const bpmEl = card.querySelector('.bpm-display, .bpm-large');
                if (bpmEl) { bpmEl.className = bpmEl.className.replace(/bpm-(normal|warning|critical)\b/, bpmCls); bpmEl.childNodes[0].textContent = hr; }
                const badge = card.querySelector('.pcard-badge, .badge');
                if (badge) { badge.className = `badge ${badgeCls}${badge.classList.contains('pcard-badge')?' pcard-badge':''}`; badge.textContent = st.charAt(0).toUpperCase()+st.slice(1); }
                const lu = card.querySelector('.last-upd-' + p.id);
                if (lu) lu.textContent = lastUpd;
            });
        });

        const cb = document.getElementById('criticalBadge');
        if (cb) cb.textContent = data.summary.critical;

        if (typeof handleAlerts === 'function') handleAlerts(data.patients);
        if (typeof updateCriticalNavBadge === 'function') updateCriticalNavBadge(data.summary.critical);

    } catch(err) { console.warn('Rescuer live update failed:', err); }
}

// ═══════════════════════════════════════════════════
// ALERT INBOX POLLING
// ═══════════════════════════════════════════════════
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

        // Toast + sound for new alerts
        if (unread > lastAlertCount && lastAlertCount >= 0) {
            if (typeof playAlertSound === 'function') playAlertSound();
            if (typeof showToast === 'function') {
                const newest = data.alerts.find(a => a.is_read == 0);
                if (newest) showToast('🔔 New Alert', `${newest.patient_name}: ${newest.message.substring(0,60)}...`, 'warning', 6000);
            }
        }
        lastAlertCount = unread;

        // Render inbox only when on alerts tab
        const inbox = document.getElementById('alertInbox');
        if (!inbox) return;

        if (!data.alerts || data.alerts.length === 0) {
            inbox.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted)">No alerts received yet.</div>';
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
                <div class="alert-item-from">From: ${escapeHtml(a.sent_by_name)}</div>
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
    if (typeof openModal === 'function') openModal('quickReportModal');
}

// Auto-load alerts immediately when on alerts tab
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