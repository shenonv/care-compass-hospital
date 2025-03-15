<?php
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['id'])) {
    $db = getDBConnection();
    
    try {
        // Start transaction
        $db->exec('BEGIN TRANSACTION');
        
        // Get staff member details for confirmation
        $stmt = $db->prepare('SELECT first_name, last_name FROM users WHERE id = :id AND user_type = "staff"');
        $stmt->bindValue(':id', $_GET['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $staff = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$staff) {
            throw new Exception('Staff member not found');
        }
        
        // Delete the staff member
        $stmt = $db->prepare('DELETE FROM users WHERE id = :id AND user_type = "staff"');
        $stmt->bindValue(':id', $_GET['id'], SQLITE3_INTEGER);
        $stmt->execute();
        
        if ($db->changes() === 0) {
            throw new Exception('Failed to delete staff member');
        }
        
        // Commit transaction
        $db->exec('COMMIT');
        
        $_SESSION['success_message'] = 'Staff member ' . htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) . ' deleted successfully';
        header('Location: manage_staff.php');
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction
        $db->exec('ROLLBACK');
        
        $_SESSION['error_message'] = 'Error deleting staff member: ' . $e->getMessage();
        header('Location: manage_staff.php');
        exit;
    }
}

// If no id provided, redirect back
header('Location: manage_staff.php');
exit; 