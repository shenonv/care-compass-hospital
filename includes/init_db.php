<?php
require_once 'config.php';

try {
    // Create database directory if it doesn't exist
    $db_dir = dirname($database_path);
    if (!is_dir($db_dir)) {
        mkdir($db_dir, 0755, true);
    }

    // Verify directory permissions
    if (!is_writable($db_dir)) {
        throw new Exception("Database directory is not writable");
    }

    $db = new SQLite3($database_path);
    
    // Enable foreign keys
    $db->exec('PRAGMA foreign_keys = ON;');

    // Create users table
    $result = $db->exec('
        CREATE TABLE IF NOT EXISTS users (
            user_id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            full_name TEXT NOT NULL,
            user_type TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');
    
    // Verify table creation
    $verify = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    if (!$verify->fetchArray()) {
        throw new Exception("Failed to create users table");
    }

    // Create appointments table
    $result = $db->exec('
        CREATE TABLE IF NOT EXISTS appointments (
            appointment_id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_id INTEGER NOT NULL,
            doctor_id INTEGER NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time TIME NOT NULL,
            status TEXT DEFAULT "pending",
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(user_id),
            FOREIGN KEY (doctor_id) REFERENCES users(user_id)
        )
    ');

    // Verify appointments table
    $verify = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='appointments'");
    if (!$verify->fetchArray()) {
        throw new Exception("Failed to create appointments table");
    }

    // Create doctor_schedule table
    $result = $db->exec('
        CREATE TABLE IF NOT EXISTS doctor_schedule (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            doctor_id INTEGER NOT NULL,
            day_of_week TEXT NOT NULL,
            start_time TEXT NOT NULL,
            end_time TEXT NOT NULL,
            is_available INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (doctor_id) REFERENCES users(user_id),
            UNIQUE(doctor_id, day_of_week)
        )
    ');

    // Verify doctor_schedule table
    $verify = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='doctor_schedule'");
    if (!$verify->fetchArray()) {
        throw new Exception("Failed to create doctor_schedule table");
    }

    // Close the database connection
    $db->close();

    echo "Database and tables created successfully!";
} catch (Exception $e) {
    die("Database initialization failed: " . $e->getMessage());
}
?>
