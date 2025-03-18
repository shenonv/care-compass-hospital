<?php
require_once '../includes/config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit;
}

// Check if test ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid request.";
    header('Location: lab_tests.php');
    exit;
}

$test_id = (int)$_GET['id'];
$patient_id = $_SESSION['user_id'];

try {
    $db = getDBConnection();
    
    // Start transaction
    $db->exec('BEGIN TRANSACTION');

    // Check if the test exists and belongs to the patient
    $stmt = $db->prepare('
        SELECT lt.status, p.status as payment_status 
        FROM lab_tests lt
        LEFT JOIN payments p ON p.reference_id = lt.id AND p.payment_type = "lab_test"
        WHERE lt.id = :test_id AND lt.patient_id = :patient_id
    ');
    $stmt->bindValue(':test_id', $test_id, SQLITE3_INTEGER);
    $stmt->bindValue(':patient_id', $patient_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $test = $result->fetchArray(SQLITE3_ASSOC);

    if (!$test) {
        throw new Exception("Test not found or unauthorized.");
    }

    // Only allow cancellation of pending tests
    if ($test['status'] !== 'pending') {
        throw new Exception("Only pending tests can be cancelled.");
    }

    // Update test status to cancelled
    $stmt = $db->prepare('
        UPDATE lab_tests 
        SET status = "cancelled"
        WHERE id = :test_id
    ');
    $stmt->bindValue(':test_id', $test_id, SQLITE3_INTEGER);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update test status.");
    }

    // If payment is pending, update payment status to failed
    if ($test['payment_status'] === 'pending') {
        $stmt = $db->prepare('
            UPDATE payments 
            SET status = "failed"
            WHERE reference_id = :test_id 
            AND payment_type = "lab_test"
        ');
        $stmt->bindValue(':test_id', $test_id, SQLITE3_INTEGER);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update payment status.");
        }
    }

    // Commit transaction
    $db->exec('COMMIT');

    $_SESSION['success_message'] = "Lab test cancelled successfully.";
    header('Location: lab_tests.php');
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $db->exec('ROLLBACK');
    
    error_log('Error cancelling lab test: ' . $e->getMessage());
    $_SESSION['error_message'] = "Failed to cancel lab test: " . $e->getMessage();
    header('Location: lab_tests.php');
    exit;
} 