<?php
require_once 'includes/config.php';

try {
    $db = getDBConnection();
    
    // Check tables
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    echo "Tables in database:\n";
    while ($table = $tables->fetchArray(SQLITE3_ASSOC)) {
        echo "- " . $table['name'] . "\n";
        
        // Get table schema
        $schema = $db->query("PRAGMA table_info(" . $table['name'] . ")");
        echo "  Columns:\n";
        while ($column = $schema->fetchArray(SQLITE3_ASSOC)) {
            echo "    * " . $column['name'] . " (" . $column['type'] . ")\n";
        }
        echo "\n";
        
        // Get row count
        $count = $db->querySingle("SELECT COUNT(*) FROM " . $table['name']);
        echo "  Row count: " . $count . "\n\n";
    }
    
    // Check users
    $users = $db->query("SELECT username, user_type, email FROM users");
    echo "Users in database:\n";
    while ($user = $users->fetchArray(SQLITE3_ASSOC)) {
        echo "- " . $user['username'] . " (" . $user['user_type'] . ") - " . $user['email'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
