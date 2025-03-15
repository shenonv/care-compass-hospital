<?php
require_once '../includes/config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit;
}

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "No appointment specified.";
    header('Location: appointments.php');
    exit;
}

$appointment_id = (int)$_GET['id'];
$db = getDBConnection();

try {
    // Start transaction
    $db->exec('BEGIN TRANSACTION');

    // Get appointment details
    $stmt = $db->prepare('
        SELECT * FROM appointments 
        WHERE id = :appointment_id AND patient_id = :patient_id AND status = "pending"
    ');
    $stmt->bindValue(':appointment_id', $appointment_id, SQLITE3_INTEGER);
    $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $appointment = $result->fetchArray(SQLITE3_ASSOC);

    if (!$appointment) {
        throw new Exception("Appointment not found or cannot be cancelled.");
    }

    // Update appointment status
    $stmt = $db->prepare('
        UPDATE appointments 
        SET status = "cancelled" 
        WHERE id = :appointment_id
    ');
    $stmt->bindValue(':appointment_id', $appointment_id, SQLITE3_INTEGER);
    $stmt->execute();

    // Update related payment status if exists
    $stmt = $db->prepare('
        UPDATE payments 
        SET status = "refunded" 
        WHERE reference_id = :appointment_id 
        AND payment_type = "appointment" 
        AND status = "pending"
    ');
    $stmt->bindValue(':appointment_id', $appointment_id, SQLITE3_INTEGER);
    $stmt->execute();

    // Commit transaction
    $db->exec('COMMIT');

    $_SESSION['success_message'] = "Appointment cancelled successfully.";
    header('Location: appointments.php');
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $db->exec('ROLLBACK');
    
    $_SESSION['error_message'] = "Failed to cancel appointment: " . $e->getMessage();
    header('Location: view_appointment.php?id=' . $appointment_id);
    exit;
}
