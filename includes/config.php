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
        // Create users table with specialization
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
                consultation_fee DECIMAL(10,2) DEFAULT 0.00,
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

        // Create services table
        $db->exec('
            CREATE TABLE IF NOT EXISTS services (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                department TEXT,
                price DECIMAL(10,2) DEFAULT 0.00,
                icon TEXT,
                is_active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME
            )
        ');

        // Create lab_tests table
        $db->exec('
            CREATE TABLE IF NOT EXISTS lab_tests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                test_name TEXT NOT NULL,
                test_date DATE NOT NULL,
                status TEXT NOT NULL CHECK(status IN ("pending", "in_progress", "completed", "cancelled")),
                results TEXT,
                notes TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME,
                FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
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

        // Create payments table
        $db->exec('
            CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_type TEXT NOT NULL CHECK(payment_type IN ("appointment", "lab_test", "medicine")),
                reference_id INTEGER NOT NULL,
                status TEXT NOT NULL CHECK(status IN ("pending", "completed", "failed", "refunded")),
                payment_date DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ');

        // Create departments table
        $db->exec('
            CREATE TABLE IF NOT EXISTS departments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                head_doctor_id INTEGER,
                is_active BOOLEAN DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (head_doctor_id) REFERENCES users(id)
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

        // Add consultation_fee column to users table for doctors
        $hasConsultationFee = false;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if ($row['name'] == 'consultation_fee') {
                $hasConsultationFee = true;
                break;
            }
        }
        if (!$hasConsultationFee) {
            $db->exec('ALTER TABLE users ADD COLUMN consultation_fee DECIMAL(10,2) DEFAULT 0.00');
        }

        // Add consultation fees to sample doctors
        $db->exec("
            UPDATE users 
            SET consultation_fee = CASE 
                WHEN specialization = 'Cardiology' THEN 4800.00
                WHEN specialization = 'Pediatrics' THEN 3200.00
                ELSE 2560.00
            END
            WHERE user_type = 'doctor' AND consultation_fee = 0.00
        ");

        return true;
    } catch (Exception $e) {
        error_log("Table creation error: " . $e->getMessage());
        return false;
    }
}

function createDoctorsTable($db) {
    $query = "CREATE TABLE IF NOT EXISTS doctors (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        specialty TEXT,
        qualifications TEXT,
        availability TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $db->exec($query);
}

function getDBConnection() {
    $dbPath = __DIR__ . '/../database/hospital.db'; // Adjust path as needed
    
    try {
        $db = new SQLite3($dbPath);
        $db->enableExceptions(true);
        return $db;
    } catch (Exception $e) {
        error_log("Database connection failed: " . $e->getMessage());
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
if ($db) {
    // Create all tables first
    createTablesIfNotExist($db);
    // Then create doctors table and insert sample data
    createDoctorsTable($db);
    insertSampleData($db);
}

function insertSampleData($db) {
    try {
        // Begin transaction
        $db->exec('BEGIN TRANSACTION');

        // Check if we already have sample data
        $result = $db->query('SELECT COUNT(*) as count FROM users');
        $row = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($row['count'] > 0) {
            // Check if departments table has data
            $deptResult = $db->query('SELECT COUNT(*) as count FROM departments');
            $deptRow = $deptResult->fetchArray(SQLITE3_ASSOC);
            
            if ($deptRow['count'] == 0) {
                // Insert sample departments
                $departments = [
                    ['Cardiology', 'Specialized in heart and cardiovascular treatments'],
                    ['Pediatrics', 'Children healthcare department'],
                    ['Emergency', 'Emergency medical care unit'],
                    ['Laboratory', 'Medical testing and diagnostics'],
                    ['Radiology', 'Medical imaging services'],
                    ['Pharmacy', 'Medication dispensing and management'],
                    ['Surgery', 'Surgical procedures department'],
                    ['Neurology', 'Brain and nervous system care']
                ];

                foreach ($departments as $dept) {
                    $db->exec("
                        INSERT INTO departments (name, description)
                        VALUES ('" . SQLite3::escapeString($dept[0]) . "', '" . SQLite3::escapeString($dept[1]) . "')
                    ");
                }
            }
        }

        // Insert admin user
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("
            INSERT INTO users (username, password, email, first_name, last_name, phone, user_type)
            VALUES ('admin', '$adminPass', 'admin@carecompass.com', 'Admin', 'User', '1234567890', 'admin')
        ");

        // Insert sample doctors
        $doctorPass = password_hash('doctor123', PASSWORD_DEFAULT);
        $doctors = [
            ['dr.smith', 'smith@carecompass.com', 'John', 'Smith', '1234567891', 'Cardiology', 4800.00],
            ['dr.jones', 'jones@carecompass.com', 'Emily', 'Jones', '1234567892', 'Pediatrics', 3200.00],
            ['dr.brown', 'brown@carecompass.com', 'Michael', 'Brown', '1234567893', 'Neurology', 5700.00]
        ];

        foreach ($doctors as $doctor) {
            $db->exec("
                INSERT INTO users (username, password, email, first_name, last_name, phone, user_type, specialization, consultation_fee)
                VALUES ('{$doctor[0]}', '$doctorPass', '{$doctor[1]}', '{$doctor[2]}', '{$doctor[3]}', '{$doctor[4]}', 'doctor', '{$doctor[5]}', {$doctor[6]})
            ");
        }

        // Insert sample staff members
        $staffPass = password_hash('staff123', PASSWORD_DEFAULT);
        $staffMembers = [
            ['nurse1', 'nurse1@carecompass.com', 'Sarah', 'Johnson', '1234567893', 'Nursing'],
            ['lab1', 'lab1@carecompass.com', 'Michael', 'Brown', '1234567894', 'Laboratory']
        ];

        foreach ($staffMembers as $staff) {
            $db->exec("
                INSERT INTO users (username, password, email, first_name, last_name, phone, user_type, specialization)
                VALUES ('{$staff[0]}', '$staffPass', '{$staff[1]}', '{$staff[2]}', '{$staff[3]}', '{$staff[4]}', 'staff', '{$staff[5]}')
            ");
        }

        // Insert sample patients
        $patientPass = password_hash('patient123', PASSWORD_DEFAULT);
        $patients = [
            ['patient1', 'patient1@email.com', 'Robert', 'Wilson', '1234567895'],
            ['patient2', 'patient2@email.com', 'Mary', 'Davis', '1234567896']
        ];

        foreach ($patients as $patient) {
            $db->exec("
                INSERT INTO users (username, password, email, first_name, last_name, phone, user_type)
                VALUES ('{$patient[0]}', '$patientPass', '{$patient[1]}', '{$patient[2]}', '{$patient[3]}', '{$patient[4]}', 'patient')
            ");
        }

        // Insert sample services
        $services = [
            ['Emergency Care', 'Round-the-clock emergency medical services with state-of-the-art facilities.', 'Emergency', 1600.00, 'fa-ambulance'],
            ['Laboratory Services', 'Comprehensive diagnostic testing and laboratory services.', 'Laboratory', 6400.00, 'fa-flask'],
            ['Cardiology', 'Expert cardiac care with advanced diagnostic and treatment options.', 'Cardiology', 1280.00, 'fa-heartbeat'],
            ['Pediatrics', 'Specialized healthcare services for infants, children, and adolescents.', 'Pediatrics', 9600.00, 'fa-child'],
            ['Radiology', 'Advanced imaging services including X-ray, MRI, and CT scans.', 'Radiology', 1120.00, 'fa-x-ray'],
            ['Pharmacy', '24/7 pharmacy services with prescription and OTC medications.', 'Pharmacy', 3200.00, 'fa-pills']
        ];

        foreach ($services as $service) {
            $db->exec("
                INSERT INTO services (name, description, department, price, icon)
                VALUES ('{$service[0]}', '{$service[1]}', '{$service[2]}', {$service[3]}, '{$service[4]}')
            ");
        }

        // Insert sample lab tests
        $lab_tests = [
            [
                'patient_id' => 5, // Robert Wilson
                'test_name' => 'Complete Blood Count',
                'test_date' => date('Y-m-d'),
                'status' => 'pending',
                'cost' => 4800.00
            ],
            [
                'patient_id' => 5, // Robert Wilson
                'test_name' => 'Blood Glucose Test',
                'test_date' => date('Y-m-d'),
                'status' => 'pending',
                'cost' => 2560.00
            ],
            [
                'patient_id' => 6, // Mary Davis
                'test_name' => 'Lipid Panel',
                'test_date' => date('Y-m-d'),
                'status' => 'pending',
                'cost' => 6400.00
            ]
        ];

        $stmt = $db->prepare('
            INSERT INTO lab_tests (patient_id, test_name, test_date, status, cost)
            VALUES (:patient_id, :test_name, :test_date, :status, :cost)
        ');

        foreach ($lab_tests as $test) {
            $stmt->bindValue(':patient_id', $test['patient_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':test_name', $test['test_name'], SQLITE3_TEXT);
            $stmt->bindValue(':test_date', $test['test_date'], SQLITE3_TEXT);
            $stmt->bindValue(':status', $test['status'], SQLITE3_TEXT);
            $stmt->bindValue(':cost', $test['cost'], SQLITE3_TEXT);
            $stmt->execute();
        }

        // Commit transaction
        $db->exec('COMMIT');
        return true;
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        error_log("Sample data insertion error: " . $e->getMessage());
        return false;
    }
}

function initializeSpecialties($db) {
    $db->exec('
        CREATE TABLE IF NOT EXISTS specialties (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');

    // Check if specialties table is empty
    $result = $db->query('SELECT COUNT(*) as count FROM specialties');
    $count = $result->fetchArray(SQLITE3_ASSOC)['count'];

    if ($count == 0) {
        $db->exec("
            INSERT INTO specialties (name) VALUES
            ('General Medicine'),
            ('Pediatrics'),
            ('Cardiology'),
            ('Dermatology'),
            ('Orthopedics'),
            ('Neurology'),
            ('Gynecology'),
            ('ENT'),
            ('Ophthalmology'),
            ('Psychiatry')
        ");
    }
}

$db = getDBConnection();
initializeSpecialties($db);
?>
