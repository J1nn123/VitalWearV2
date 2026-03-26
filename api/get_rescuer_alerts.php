    <?php
/**
 * api/get_rescuer_alerts.php
 * Responder fetches alerts sent FROM rescuers (direction = to_responder).
 * GET: fetch alerts | GET?mark_read=1: mark all as read
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['responder', 'admin']);

header('Content-Type: application/json');
$pdo  = getDB();
$user = getCurrentUser();

// Mark all as read
if (isset($_GET['mark_read'])) {
    $pdo->prepare("UPDATE alerts SET is_read = 1 WHERE direction = 'to_responder' AND is_read = 0")
        ->execute();
    echo json_encode(['success' => true]);
    exit;
}

// Fetch alerts from rescuers
$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.message,
        a.is_read,
        a.created_at,
        a.latitude,
        a.longitude,
        p.name  AS patient_name,
        u.full_name AS rescuer_name,
        h.heart_rate,
        h.status AS hr_status
    FROM alerts a
    JOIN patients p ON p.id = a.patient_id
    JOIN users u    ON u.id = a.rescuer_id
    LEFT JOIN heart_rate_logs h ON h.id = (
        SELECT id FROM heart_rate_logs WHERE patient_id = a.patient_id ORDER BY id DESC LIMIT 1
    )
    WHERE a.direction = 'to_responder'
    ORDER BY a.created_at DESC
    LIMIT 50
");
$stmt->execute();
$alerts = $stmt->fetchAll();

$unread = 0;
foreach ($alerts as $a) { if (!$a['is_read']) $unread++; }

echo json_encode([
    'success'      => true,
    'alerts'       => $alerts,
    'unread_count' => $unread,
]);