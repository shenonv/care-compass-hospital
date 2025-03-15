<?php
require_once 'includes/config.php';

try {
    $db = getDBConnection();
    
    // Get all users
    $result = $db->query('SELECT id, username, email, user_type FROM users');
    
    echo "Registered Users:\n";
    echo "----------------\n";
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        echo sprintf(
            "ID: %d\nUsername: %s\nEmail: %s\nType: %s\n\n",
            $row['id'],
            $row['username'],
            $row['email'],
            $row['user_type']
        );
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 