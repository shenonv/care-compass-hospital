<?php
require_once '../includes/config.php';
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    $errors = [];
    
    // Validate input
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    if (empty($errors)) {
        $db = getDBConnection();
        
        // Create new query
        $stmt = $db->prepare('
            INSERT INTO queries (
                patient_id,
                subject,
                message,
                status,
                created_at
            ) VALUES (
                :patient_id,
                :subject,
                :message,
                "pending",
                DATETIME("now")
            )
        ');
        
        $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':subject', $subject, SQLITE3_TEXT);
        $stmt->bindValue(':message', $message, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            header('Location: queries.php?success=1');
            exit;
        } else {
            $errors[] = "Failed to submit query";
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['query_errors'] = $errors;
        header('Location: queries.php?error=1');
        exit;
    }
}

// If not POST request, redirect to queries page
header('Location: queries.php');
exit;
