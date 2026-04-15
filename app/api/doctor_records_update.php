<?php
/**
 * API: Update Medical Record
 * POST /api/doctor/records/update
 * Returns JSON
 */

// Set JSON response header FIRST before anything else
header('Content-Type: application/json; charset=utf-8');
http_response_code(200);

try {
    // Check authentication
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Get JSON input
    $jsonInput = file_get_contents('php://input');
    $input = json_decode($jsonInput, true);

    // Validate required fields
    if (empty($input['record_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing record_id']);
        exit;
    }

    // Database is already loaded from index.php
    if (!class_exists('Database')) {
        require_once __DIR__ . '/../../scheme/Database.php';
    }
    $db = new Database();

    // Get doctor_id
    $doctorRow = $db->table('doctors')->where('user_id', $_SESSION['user_id'])->get();
    if (!$doctorRow) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Doctor record not found']);
        exit;
    }

    $doctorId = $doctorRow['doctor_id'];
    $recordId = (int)$input['record_id'];

    // Verify doctor owns this record
    $recordCheck = $db->table('health_records')
        ->where('record_id', $recordId)
        ->where('doctor_id', $doctorId)
        ->get();

    if (!$recordCheck) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Record not found or unauthorized']);
        exit;
    }

    // Prepare update data
    $updateData = [];
    
    if (!empty($input['doctor_notes'])) {
        $updateData['doctor_notes'] = $input['doctor_notes'];
    }
    if (!empty($input['visit_type'])) {
        $updateData['visit_type'] = $input['visit_type'];
    }
    if (!empty($input['chief_complaint'])) {
        $updateData['chief_complaint'] = $input['chief_complaint'];
    }
    if (!empty($input['diagnosis'])) {
        $updateData['diagnosis'] = $input['diagnosis'];
    }
    if (!empty($input['treatment'])) {
        $updateData['treatment'] = $input['treatment'];
    }
    if (!empty($input['record_date'])) {
        $updateData['record_date'] = $input['record_date'];
    }

    if (empty($updateData)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No update data provided']);
        exit;
    }

    // Update record
    $result = $db->table('health_records')
        ->where('record_id', $recordId)
        ->update($updateData);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Medical record updated successfully',
        'record_id' => $recordId
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
?>
