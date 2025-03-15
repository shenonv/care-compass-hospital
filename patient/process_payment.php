<?php
require_once '../includes/config.php';
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$payment_id = $_POST['payment_id'] ?? '';

// Basic check for payment_id
if (empty($payment_id)) {
    echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
    exit;
}

$db = getDBConnection();

// Update payment status to paid
$stmt = $db->prepare('UPDATE payments SET status = "paid" WHERE id = :payment_id AND patient_id = :patient_id');
$stmt->bindValue(':payment_id', $payment_id, SQLITE3_INTEGER);
$stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Payment processed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to process payment']);
}
exit;
