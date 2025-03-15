<?php
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['staff_id'])) {
    $db = getDBConnection();
    
    try {
        // Start transaction
        $db->exec('BEGIN TRANSACTION');
        
        // Check if email already exists for other users
        $stmt = $db->prepare('SELECT id FROM users WHERE email = :email AND id != :staff_id');
        $stmt->bindValue(':email', $_POST['email'], SQLITE3_TEXT);
        $stmt->bindValue(':staff_id', $_POST['staff_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        if ($result->fetchArray()) {
            throw new Exception('Email already exists');
        }
        
        // Build update query based on whether a new password is provided
        $sql = 'UPDATE users SET 
                first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone = :phone,
                specialization = :specialization';
        
        if (!empty($_POST['new_password'])) {
            $sql .= ', password = :password';
        }
        
        $sql .= ' WHERE id = :staff_id AND user_type = "staff"';
        
        $stmt = $db->prepare($sql);
        
        $stmt->bindValue(':first_name', $_POST['first_name'], SQLITE3_TEXT);
        $stmt->bindValue(':last_name', $_POST['last_name'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':email', $_POST['email'], SQLITE3_TEXT);
        $stmt->bindValue(':phone', $_POST['phone'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':specialization', $_POST['specialization'], SQLITE3_TEXT);
        $stmt->bindValue(':staff_id', $_POST['staff_id'], SQLITE3_INTEGER);
        
        if (!empty($_POST['new_password'])) {
            $stmt->bindValue(':password', password_hash($_POST['new_password'], PASSWORD_DEFAULT), SQLITE3_TEXT);
        }
        
        $stmt->execute();
        
        if ($db->changes() === 0) {
            throw new Exception('Staff member not found or no changes made');
        }
        
        // Commit transaction
        $db->exec('COMMIT');
        
        $_SESSION['success_message'] = 'Staff member updated successfully';
        header('Location: manage_staff.php');
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction
        $db->exec('ROLLBACK');
        
        $_SESSION['error_message'] = 'Error updating staff member: ' . $e->getMessage();
        header('Location: manage_staff.php');
        exit;
    }
}

// If not POST request or no staff_id, redirect back
header('Location: manage_staff.php');
exit; 