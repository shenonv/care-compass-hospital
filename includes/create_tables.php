<?php
require_once 'config.php';

$db = getDBConnection();

// Create medical_history table
$query = "
CREATE TABLE IF NOT EXISTS medical_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id INTEGER NOT NULL,
    date TEXT NOT NULL,
    diagnosis TEXT NOT NULL,
    treatment TEXT NOT NULL,
    notes TEXT,
    FOREIGN KEY (patient_id) REFERENCES patients(id)
)";
$db->exec($query);

echo "Tables created successfully.";
?>
