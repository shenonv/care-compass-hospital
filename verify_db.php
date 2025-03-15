<?php
require_once 'includes/config.php';

$db = getDBConnection();

// Get all tables
$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
echo "Tables in database:\n";
while ($table = $tables->fetchArray(SQLITE3_ASSOC)) {
    echo "- " . $table['name'] . "\n";
    
    // Get table schema
    $schema = $db->query("PRAGMA table_info(" . $table['name'] . ")");
    while ($column = $schema->fetchArray(SQLITE3_ASSOC)) {
        echo "  * " . $column['name'] . " (" . $column['type'] . ")\n";
    }
    echo "\n";
}
?>
