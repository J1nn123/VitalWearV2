<?php
/**
 * API: Patient CRUD
 * Handles POST (add), PUT (edit), DELETE for patients.
 * Writes rescuer assignment to patients.assigned_to — the single source
 * of truth used by admin, manager, rescuer, and responder dashboards.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Must be logged in
$user = getCurrentUser();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

// ── POST: Add patient ────────────────────────────────────────────────────────
if ($method === 'POST') {
    $name      = trim($body['name']      ?? '');
    $age       = (int)($body['age']      ?? 0);
    $condition = trim($body['condition'] ?? '');
    $rescuerId = isset($body['rescuer_id']) && $body['rescuer_id'] !== null
                    ? (int)$body['rescuer_id'] : null;

    if (!$name || !$age) {
        echo json_encode(['success' => false, 'message' => 'Name and age are required.']); exit;
    }

    try {
        // Write rescuer to patients.assigned_to — used by all dashboards
        $pdo->prepare("
            INSERT INTO patients (name, age, medical_condition, assigned_to)
            VALUES (?, ?, ?, ?)
        ")->execute([$name, $age, $condition ?: null, $rescuerId]);

        $newId = (int)$pdo->lastInsertId();
        logAction($user['id'], 'ADD_PATIENT', "Added patient: $name");

        echo json_encode(['success' => true, 'message' => 'Patient added.', 'id' => $newId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ── PUT: Edit patient ────────────────────────────────────────────────────────
if ($method === 'PUT') {
    $id        = (int)($body['id']       ?? 0);
    $name      = trim($body['name']      ?? '');
    $age       = (int)($body['age']      ?? 0);
    $condition = trim($body['condition'] ?? '');
    $rescuerId = isset($body['rescuer_id']) && $body['rescuer_id'] !== null
                    ? (int)$body['rescuer_id'] : null;

    if (!$id || !$name || !$age) {
        echo json_encode(['success' => false, 'message' => 'ID, name and age are required.']); exit;
    }

    try {
        // Update patients.assigned_to — single source of truth for all dashboards
        $pdo->prepare("
            UPDATE patients
            SET name = ?, age = ?, medical_condition = ?, assigned_to = ?
            WHERE id = ?
        ")->execute([$name, $age, $condition ?: null, $rescuerId, $id]);

        logAction($user['id'], 'EDIT_PATIENT', "Edited patient ID: $id");

        echo json_encode(['success' => true, 'message' => 'Patient updated.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ── DELETE: Remove patient ───────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = (int)($body['id'] ?? 0);

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Patient ID required.']); exit;
    }

    try {
        $pdo->prepare("DELETE FROM patients WHERE id = ?")->execute([$id]);
        logAction($user['id'], 'DELETE_PATIENT', "Deleted patient ID: $id");

        echo json_encode(['success' => true, 'message' => 'Patient deleted.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed.']);