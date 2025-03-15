<?php
require_once '../includes/config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit;
}

// Check if appointment ID is provided
if (!isset($_GET['appointment_id'])) {
    $_SESSION['error_message'] = "No appointment specified.";
    header('Location: appointments.php');
    exit;
}

$appointment_id = (int)$_GET['appointment_id'];
$db = getDBConnection();

try {
    // Start transaction
    $db->exec('BEGIN TRANSACTION');

    // Get appointment and payment details
    $stmt = $db->prepare('
        SELECT 
            a.*,
            d.consultation_fee,
            p.id as payment_id,
            p.status as payment_status
        FROM appointments a
        JOIN users u ON a.doctor_id = u.id
        JOIN doctors d ON d.user_id = u.id
        LEFT JOIN payments p ON p.reference_id = a.id AND p.payment_type = "appointment"
        WHERE a.id = :appointment_id AND a.patient_id = :patient_id
    ');
    
    $stmt->bindValue(':appointment_id', $appointment_id, SQLITE3_INTEGER);
    $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $appointment = $result->fetchArray(SQLITE3_ASSOC);

    if (!$appointment) {
        throw new Exception("Appointment not found or access denied.");
    }

    if ($appointment['payment_status'] === 'completed') {
        throw new Exception("Payment has already been completed.");
    }

    // Update or create payment record
    if ($appointment['payment_id']) {
        // Update existing payment
        $stmt = $db->prepare('
            UPDATE payments 
            SET status = "completed",
                payment_date = CURRENT_TIMESTAMP
            WHERE id = :payment_id
        ');
        $stmt->bindValue(':payment_id', $appointment['payment_id'], SQLITE3_INTEGER);
    } else {
        // Create new payment record
        $stmt = $db->prepare('
            INSERT INTO payments (
                patient_id,
                amount,
                payment_type,
                reference_id,
                status,
                payment_date,
                created_at
            ) VALUES (
                :patient_id,
                :amount,
                "appointment",
                :reference_id,
                "completed",
                CURRENT_TIMESTAMP,
                CURRENT_TIMESTAMP
            )
        ');
        
        $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':amount', $appointment['consultation_fee'], SQLITE3_FLOAT);
        $stmt->bindValue(':reference_id', $appointment_id, SQLITE3_INTEGER);
    }
    
    $stmt->execute();

    // Update appointment status to confirmed
    $stmt = $db->prepare('
        UPDATE appointments 
        SET status = "confirmed" 
        WHERE id = :appointment_id
    ');
    $stmt->bindValue(':appointment_id', $appointment_id, SQLITE3_INTEGER);
    $stmt->execute();

    // Commit transaction
    $db->exec('COMMIT');

    $_SESSION['success_message'] = "Payment completed successfully. Your appointment is now confirmed.";
    header('Location: view_appointment.php?id=' . $appointment_id);
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $db->exec('ROLLBACK');
    
    $_SESSION['error_message'] = "Failed to process payment: " . $e->getMessage();
    header('Location: process_payment.php?appointment_id=' . $appointment_id);
    exit;
} 