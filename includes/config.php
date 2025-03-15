<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set database path
define('DB_FILE', __DIR__ . '/../database/hospital.db');

// Create database directory
$db_dir = dirname(DB_FILE);
if (!file_exists($db_dir)) {
    mkdir($db_dir, 0777, true);
}

function createTablesIfNotExist($db) {
    try {
        // Create users table with last_login column
        $db->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                first_name TEXT NOT NULL,
                last_name TEXT,
                phone TEXT,
                user_type TEXT NOT NULL CHECK(user_type IN ("admin", "doctor", "patient", "staff")),
                specialization TEXT,
                last_login DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Create appointments table with better constraints
        $db->exec('
            CREATE TABLE IF NOT EXISTS appointments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                doctor_id INTEGER NOT NULL,
                appointment_date DATE NOT NULL,
                appointment_time TIME NOT NULL,
                status TEXT NOT NULL CHECK(status IN ("pending", "confirmed", "cancelled", "completed")),
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ');

        // Create medical_records table
        $db->exec('
            CREATE TABLE IF NOT EXISTS medical_records (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                doctor_id INTEGER NOT NULL,
                diagnosis TEXT,
                prescription TEXT,
                notes TEXT,
                visit_date DATE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ');

        // Add last_login column if it doesn't exist
        $result = $db->query("PRAGMA table_info(users)");
        $hasLastLogin = false;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if ($row['name'] == 'last_login') {
                $hasLastLogin = true;
                break;
            }
        }
        if (!$hasLastLogin) {
            $db->exec('ALTER TABLE users ADD COLUMN last_login DATETIME');
        }

        return true;
    } catch (Exception $e) {
        error_log("Table creation error: " . $e->getMessage());
        return false;
    }
}

function getDBConnection() {
    try {
        $db = new SQLite3(DB_FILE);
        $db->enableExceptions(true);
        
        // Create all required tables
        if (!createTablesIfNotExist($db)) {
            throw new Exception('Failed to create database tables');
        }
        
        return $db;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

// Add this new function for user registration
function registerUser($username, $password, $email, $firstName, $lastName, $phone, $userType) {
    try {
        $db = getDBConnection();
        if (!$db) {
            throw new Exception('Database connection failed');
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare('INSERT INTO users (username, password, email, first_name, last_name, phone, user_type) 
                             VALUES (:username, :password, :email, :firstName, :lastName, :phone, :userType)');
        
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':firstName', $firstName, SQLITE3_TEXT);
        $stmt->bindValue(':lastName', $lastName, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':userType', $userType, SQLITE3_TEXT);
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return false;
    }
}

// Replace the existing validateLogin function with this improved version
function validateLogin($username, $password, $userType) {
    try {
        $db = getDBConnection();
        if (!$db) {
            throw new Exception('Database connection failed');
        }

        // Sanitize inputs
        $username = filter_var($username, FILTER_SANITIZE_STRING);
        $userType = filter_var($userType, FILTER_SANITIZE_STRING);

        $stmt = $db->prepare('SELECT * FROM users WHERE username = :username AND user_type = :userType');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':userType', $userType, SQLITE3_TEXT);
        
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login time
            $updateStmt = $db->prepare('UPDATE users SET last_login = datetime("now") WHERE id = :id');
            $updateStmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
            $updateStmt->execute();

            // Remove password from session data
            unset($user['password']);
            return $user;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

// Initialize database
$db = getDBConnection();
?>
