<?php
/**
 * api/get_alerts.php
 * Returns alerts for the currently logged-in rescuer.
 * Also handles ?mark_read=1 to mark all as read.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireRole(['rescuer', 'admin']);

$user = getCurrentUser();
$pdo  = getDB();

// ── Mark all as read ────────────────────────────────────────────────────────
if (isset($_GET['mark_read'])) {
    $pdo->prepare("UPDATE alerts SET is_read = 1 WHERE rescuer_id = ?")
        ->execute([$user['id']]);
    echo json_encode(['success' => true, 'message' => 'All alerts marked as read.']);
    exit;
}

// ── Fetch alerts for this rescuer ───────────────────────────────────────────
try {
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.message,
            a.is_read,
            a.created_at,
            p.name  AS patient_name,
            u.full_name AS sent_by_name
        FROM alerts a
        JOIN patients p ON p.id = a.patient_id
        JOIN users    u ON u.id = a.sent_by
        WHERE a.rescuer_id = ?
        ORDER BY a.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$user['id']]);
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Unread count
    $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM alerts WHERE rescuer_id = ? AND is_read = 0");
    $unreadStmt->execute([$user['id']]);
    $unreadCount = (int)$unreadStmt->fetchColumn();

    // Mark fetched alerts as read automatically
    $pdo->prepare("UPDATE alerts SET is_read = 1 WHERE rescuer_id = ? AND is_read = 0")
        ->execute([$user['id']]);

    echo json_encode([
        'success'      => true,
        'alerts'       => $alerts,
        'unread_count' => $unreadCount,
    ]);

} catch (PDOException $e) {
    error_log('get_alerts.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}