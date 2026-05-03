<?php
/**
 * API: Simulate IoT Heart Rate Data
 * Generates random BPM values for all patients
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $pdo  = getDB();
    $stmt = $pdo->query("SELECT id, name FROM patients");
    $patients = $stmt->fetchAll();

    $logStmt = $pdo->prepare("INSERT INTO heart_rate_logs (patient_id, bpm, status) VALUES (?, ?, ?)");
    $updStmt = $pdo->prepare("UPDATE patients SET status = ? WHERE id = ?");

    $updated = [];
    foreach ($patients as $patient) {
        // Simulate realistic BPM: mostly normal, sometimes warning/critical
        $rand = rand(1, 100);
        if ($rand <= 70) {
            $bpm = rand(65, 99);   // normal
        } elseif ($rand <= 88) {
            $bpm = rand(100, 119); // warning
        } else {
            $bpm = rand(1, 2) === 1 ? rand(45, 59) : rand(121, 135); // critical
        }

        $status = getBpmStatus($bpm);
        $logStmt->execute([$patient['id'], $bpm, $status]);
        $updStmt->execute([$status, $patient['id']]);

        $updated[] = ['id' => $patient['id'], 'name' => $patient['name'], 'bpm' => $bpm, 'status' => $status];
    }

    // Keep only last 100 logs per patient (cleanup)
    $pdo->exec("
        DELETE FROM heart_rate_logs
        WHERE id NOT IN (
            SELECT id FROM (
                SELECT id FROM heart_rate_logs
                ORDER BY recorded_at DESC
                LIMIT 800
            ) tmp
        )
    ");

    echo json_encode(['success' => true, 'updated' => $updated, 'count' => count($updated)]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}