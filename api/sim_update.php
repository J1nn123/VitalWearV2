<?php
/**
 * api/sim_update.php
 * Receives simulated device data and writes heart_rate_logs rows.
 * Called by the JS simulation engine every 5 seconds.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['responder', 'admin']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);
    exit;
}

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!isset($body['patients']) || !is_array($body['patients'])) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'Invalid payload']);
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare("
    INSERT INTO heart_rate_logs (patient_id, heart_rate, status, timestamp)
    VALUES (:pid, :hr, :status, NOW())
");

$inserted = 0;
foreach ($body['patients'] as $p) {
    $id = (int)($p['id'] ?? 0);
    $hr = (int)($p['heart_rate'] ?? 0);
    if ($id <= 0 || $hr <= 0) continue;

    // Derive status server-side (don't trust client blindly)
    if ($hr >= 60 && $hr <= 99)        $status = 'normal';
    elseif ($hr >= 100 && $hr <= 120)  $status = 'warning';
    else                               $status = 'critical';

    $stmt->execute([':pid'=>$id, ':hr'=>$hr, ':status'=>$status]);
    $inserted++;
}

echo json_encode([
    'success'  => true,
    'inserted' => $inserted,
    'timestamp'=> date('Y-m-d H:i:s')
]);