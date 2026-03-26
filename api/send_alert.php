<?php
/**
 * api/send_alert.php
 * Responder → sends an alert message to a rescuer about a patient.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
requireRole(['responder', 'admin']);

$user = getCurrentUser();
$pdo  = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

$patientId = (int)($body['patient_id'] ?? 0);
$rescuerId = (int)($body['rescuer_id'] ?? 0);
$message   = trim($body['message']     ?? '');

if (!$patientId || !$rescuerId || !$message) {
    echo json_encode(['success' => false, 'message' => 'Patient, rescuer, and message are required.']);
    exit;
}

// Verify patient exists
$patientStmt = $pdo->prepare("SELECT id, name FROM patients WHERE id = ?");
$patientStmt->execute([$patientId]);
$patient = $patientStmt->fetch();

if (!$patient) {
    echo json_encode(['success' => false, 'message' => 'Patient not found.']);
    exit;
}

// Verify rescuer exists
$rescuerStmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ? AND role = 'rescuer'");
$rescuerStmt->execute([$rescuerId]);
$rescuer = $rescuerStmt->fetch();

if (!$rescuer) {
    echo json_encode(['success' => false, 'message' => 'Rescuer not found.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO alerts (patient_id, rescuer_id, sent_by, message, is_read)
        VALUES (?, ?, ?, ?, 0)
    ");
    $stmt->execute([$patientId, $rescuerId, $user['id'], $message]);

    if (function_exists('logAction')) {
        logAction($user['id'], 'SEND_ALERT', "Alert to rescuer #{$rescuerId} for patient {$patient['name']}");
    }

    echo json_encode([
        'success' => true,
        'message' => "Alert sent to {$rescuer['full_name']} for patient {$patient['name']}."
    ]);

} catch (PDOException $e) {
    error_log('send_alert.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}