<?php
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();
    
    try {
        // Start transaction
        $db->exec('BEGIN TRANSACTION');
        
        // Check if username already exists
        $stmt = $db->prepare('SELECT id FROM users WHERE username = :username');
        $stmt->bindValue(':username', $_POST['username'], SQLITE3_TEXT);
        $result = $stmt->execute();
        
        if ($result->fetchArray()) {
            throw new Exception('Username already exists');
        }
        
        // Check if email already exists
        $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->bindValue(':email', $_POST['email'], SQLITE3_TEXT);
        $result = $stmt->execute();
        
        if ($result->fetchArray()) {
            throw new Exception('Email already exists');
        }

        // Validate user_type with more permissive validation
        $user_type = $_POST['user_type'] ?? '';
        if (empty($user_type)) {
            throw new Exception('User type is required');
        }
        
        // Insert new staff member
        $stmt = $db->prepare('
            INSERT INTO users (
                username,
                password,
                email,
                first_name,
                last_name,
                phone,
                specialization,
                user_type,
                created_at
            ) VALUES (
                :username,
                :password,
                :email,
                :first_name,
                :last_name,
                :phone,
                :specialization,
                :user_type,
                datetime("now")
            )
        ');
        
        $stmt->bindValue(':username', $_POST['username'], SQLITE3_TEXT);
        $stmt->bindValue(':password', password_hash($_POST['password'], PASSWORD_DEFAULT), SQLITE3_TEXT);
        $stmt->bindValue(':email', $_POST['email'], SQLITE3_TEXT);
        $stmt->bindValue(':first_name', $_POST['first_name'], SQLITE3_TEXT);
        $stmt->bindValue(':last_name', $_POST['last_name'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':phone', $_POST['phone'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':specialization', $_POST['specialization'], SQLITE3_TEXT);
        $stmt->bindValue(':user_type', $user_type, SQLITE3_TEXT);
        
        $stmt->execute();
        
        // Commit transaction
        $db->exec('COMMIT');
        
        $_SESSION['success_message'] = 'Staff member added successfully';
        header('Location: manage_staff.php');
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction
        $db->exec('ROLLBACK');
        
        $_SESSION['error_message'] = 'Error adding staff member: ' . $e->getMessage();
        header('Location: manage_staff.php');
        exit;
    }
}

// If not POST request, redirect back
header('Location: manage_staff.php');
exit;