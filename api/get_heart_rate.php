<?php
/**
 * API: Get Real-Time Heart Rate Data
 * Polls the actual database — used by all dashboards for live updates.
 */
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Must be logged in
$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$pdo = getDB();

try {
    // Fetch latest heart_rate per patient from real DB
    $stmt = $pdo->query("
        SELECT
            p.id,
            p.name,
            p.age,
            p.medical_condition,
            p.assigned_to,
            h.heart_rate,
            h.status,
            h.timestamp AS last_updated,
            u.full_name AS rescuer_name
        FROM patients p
        LEFT JOIN heart_rate_logs h ON h.id = (
            SELECT id FROM heart_rate_logs
            WHERE patient_id = p.id
            ORDER BY id DESC LIMIT 1
        )
        LEFT JOIN users u ON u.id = p.assigned_to
        ORDER BY
            CASE h.status
                WHEN 'critical' THEN 1
                WHEN 'warning'  THEN 2
                ELSE 3
            END,
            p.name ASC
    ");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build summary counts
    $summary = ['total' => 0, 'normal' => 0, 'warning' => 0, 'critical' => 0];
    foreach ($patients as $p) {
        $summary['total']++;
        $s = $p['status'] ?? 'normal';
        if (isset($summary[$s])) $summary[$s]++;
    }

    // Format for frontend
    $formatted = array_map(function($p) {
        $hr  = $p['heart_rate'] ?? 0;
        $st  = $p['status'] ?? 'normal';
        $pct = min(100, max(0, (($hr - 40) / 120) * 100));
        return [
            'id'                => (int)$p['id'],
            'name'              => $p['name'],
            'age'               => (int)$p['age'],
            'medical_condition' => $p['medical_condition'] ?? '',
            'heart_rate'        => (int)$hr,
            'bpm'               => (int)$hr,   // alias for legacy JS
            'status'            => $st,
            'last_updated'      => $p['last_updated'],
            'rescuer_name'      => $p['rescuer_name'] ?? 'Unassigned',
            'bar_pct'           => round($pct),
        ];
    }, $patients);

    echo json_encode([
        'success'   => true,
        'patients'  => $formatted,
        'summary'   => $summary,
        'timestamp' => date('H:i:s'),
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}