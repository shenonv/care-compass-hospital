<?php
require_once '../includes/config.php';
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? '';
    
    if (empty($appointment_id)) {
        echo json_encode(['success' => false, 'message' => 'Appointment ID is required']);
        exit;
    }
    
    $db = getDBConnection();
    
    // Verify the appointment belongs to the current patient
    $stmt = $db->prepare('
        SELECT id, status, appointment_date 
        FROM appointments 
        WHERE id = :appointment_id 
        AND patient_id = :patient_id
    ');
    $stmt->bindValue(':appointment_id', $appointment_id, SQLITE3_INTEGER);
    $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $appointment = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    
    // Check if appointment is in the past
    if (strtotime($appointment['appointment_date']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Cannot cancel past appointments']);
        exit;
    }
    
    // Check if appointment is already cancelled
    if ($appointment['status'] === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'Appointment is already cancelled']);
        exit;
    }
    
    // Cancel the appointment
    $stmt = $db->prepare('
        UPDATE appointments 
        SET status = "cancelled" 
        WHERE id = :appointment_id
    ');
    $stmt->bindValue(':appointment_id', $appointment_id, SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit;
