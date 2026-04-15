<?php
/**
 * API: Create Medical Record
 * POST /api/doctor/records/create
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
    if (empty($input['patient_id']) || empty($input['record_date']) || empty($input['visit_type']) || empty($input['doctor_notes'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
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
    $patientId = (int)$input['patient_id'];
    $recordDate = $input['record_date'];
    $visitType = $input['visit_type'];
    $chiefComplaint = $input['chief_complaint'] ?? '';
    $diagnosis = $input['diagnosis'] ?? '';
    $treatment = $input['treatment'] ?? '';
    $doctorNotes = $input['doctor_notes'];

    // Validate patient exists
    $patientCheck = $db->table('patients')->where('patient_id', $patientId)->get();
    if (!$patientCheck) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
        exit;
    }

    // Create record
    $result = $db->table('health_records')->insert([
        'patient_id' => $patientId,
        'doctor_id' => $doctorId,
        'record_date' => $recordDate,
        'visit_type' => $visitType,
        'chief_complaint' => $chiefComplaint,
        'diagnosis' => $diagnosis,
        'treatment' => $treatment,
        'doctor_notes' => $doctorNotes,
        'status' => 'Final'
    ]);

    if ($result) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Medical record created successfully',
            'record_id' => $result
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create record']);
    }
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit;
}
?>
