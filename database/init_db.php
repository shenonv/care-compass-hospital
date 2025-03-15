<?php
require_once dirname(__DIR__) . '/includes/config.php';

try {
    $db = getDBConnection();
    
    // Enable foreign keys
    $db->exec('PRAGMA foreign_keys = ON');

    // Drop existing tables if they exist
    $tables = ['medical_records', 'appointments', 'doctors', 'payments', 'users', 'lab_tests'];
    foreach ($tables as $table) {
        $db->exec("DROP TABLE IF EXISTS $table");
    }

    // Create users table with consistent schema
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
            department TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME
        )
    ');

    // Create doctors table
    $db->exec('
        CREATE TABLE IF NOT EXISTS doctors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            specialty TEXT NOT NULL,
            qualification TEXT NOT NULL,
            experience_years INTEGER DEFAULT 0,
            consultation_fee DECIMAL(10,2) DEFAULT 0.00,
            available_days TEXT,
            available_hours TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ');

    // Create appointments table
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
            diagnosis TEXT NOT NULL,
            treatment TEXT NOT NULL,
            notes TEXT,
            record_date DATE NOT NULL DEFAULT CURRENT_DATE,
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
            payment_type TEXT NOT NULL CHECK(payment_type IN ("appointment", "lab_test", "medicine", "other")),
            reference_id INTEGER NOT NULL,
            status TEXT NOT NULL CHECK(status IN ("pending", "completed", "failed", "refunded")),
            payment_date DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ');

    // Create lab_tests table
    $db->exec('
        CREATE TABLE lab_tests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_id INTEGER NOT NULL,
            test_name TEXT NOT NULL,
            test_description TEXT,
            test_date DATE NOT NULL,
            result_date DATE,
            status TEXT NOT NULL DEFAULT "pending",
            result TEXT,
            notes TEXT,
            cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payment_status TEXT NOT NULL DEFAULT "pending",
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id)
        )
    ');

    // Insert default admin user if not exists
    $stmt = $db->prepare('SELECT id FROM users WHERE username = :username AND user_type = "admin" LIMIT 1');
    $stmt->bindValue(':username', 'admin', SQLITE3_TEXT);
    $result = $stmt->execute();
    
    if (!$result->fetchArray()) {
        $stmt = $db->prepare('
            INSERT INTO users (username, password, email, first_name, last_name, user_type)
            VALUES (:username, :password, :email, :first_name, :last_name, "admin")
        ');
        
        $stmt->bindValue(':username', 'admin', SQLITE3_TEXT);
        $stmt->bindValue(':password', password_hash('admin123', PASSWORD_DEFAULT), SQLITE3_TEXT);
        $stmt->bindValue(':email', 'admin@carecompass.com', SQLITE3_TEXT);
        $stmt->bindValue(':first_name', 'System', SQLITE3_TEXT);
        $stmt->bindValue(':last_name', 'Administrator', SQLITE3_TEXT);
        $stmt->execute();
    }

    // Insert sample doctors
    $sample_doctors = [
        [
            'username' => 'dr.smith',
            'password' => 'doctor123',
            'email' => 'dr.smith@carecompass.com',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'phone' => '1234567890',
            'specialty' => 'Cardiology',
            'qualification' => 'MD, FACC',
            'experience_years' => 15,
            'consultation_fee' => 150.00,
            'available_days' => 'Monday,Tuesday,Wednesday,Thursday,Friday',
            'available_hours' => '09:00-17:00'
        ],
        [
            'username' => 'dr.jones',
            'password' => 'doctor123',
            'email' => 'dr.jones@carecompass.com',
            'first_name' => 'Sarah',
            'last_name' => 'Jones',
            'phone' => '0987654321',
            'specialty' => 'Pediatrics',
            'qualification' => 'MD, FAAP',
            'experience_years' => 10,
            'consultation_fee' => 100.00,
            'available_days' => 'Monday,Wednesday,Friday',
            'available_hours' => '10:00-18:00'
        ]
    ];

    foreach ($sample_doctors as $doctor) {
        // Insert user record
        $stmt = $db->prepare('
            INSERT INTO users (username, password, email, first_name, last_name, phone, user_type)
            VALUES (:username, :password, :email, :first_name, :last_name, :phone, "doctor")
        ');
        
        $stmt->bindValue(':username', $doctor['username'], SQLITE3_TEXT);
        $stmt->bindValue(':password', password_hash($doctor['password'], PASSWORD_DEFAULT), SQLITE3_TEXT);
        $stmt->bindValue(':email', $doctor['email'], SQLITE3_TEXT);
        $stmt->bindValue(':first_name', $doctor['first_name'], SQLITE3_TEXT);
        $stmt->bindValue(':last_name', $doctor['last_name'], SQLITE3_TEXT);
        $stmt->bindValue(':phone', $doctor['phone'], SQLITE3_TEXT);
        $stmt->execute();
        
        // Get the inserted user ID
        $user_id = $db->lastInsertRowID();
        
        // Insert doctor record
        $stmt = $db->prepare('
            INSERT INTO doctors (
                user_id, specialty, qualification, experience_years, 
                consultation_fee, available_days, available_hours
            )
            VALUES (
                :user_id, :specialty, :qualification, :experience_years,
                :consultation_fee, :available_days, :available_hours
            )
        ');
        
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':specialty', $doctor['specialty'], SQLITE3_TEXT);
        $stmt->bindValue(':qualification', $doctor['qualification'], SQLITE3_TEXT);
        $stmt->bindValue(':experience_years', $doctor['experience_years'], SQLITE3_INTEGER);
        $stmt->bindValue(':consultation_fee', $doctor['consultation_fee'], SQLITE3_FLOAT);
        $stmt->bindValue(':available_days', $doctor['available_days'], SQLITE3_TEXT);
        $stmt->bindValue(':available_hours', $doctor['available_hours'], SQLITE3_TEXT);
        $stmt->execute();
    }

    // Insert some sample lab tests
    $db->exec("
        INSERT INTO lab_tests (patient_id, test_name, test_description, test_date, status, cost) VALUES 
        (2, 'Complete Blood Count (CBC)', 'Measures different components of blood', '2024-03-15', 'pending', 1500.00),
        (2, 'Blood Glucose Test', 'Measures blood sugar levels', '2024-03-16', 'pending', 800.00),
        (2, 'Lipid Profile', 'Measures cholesterol and triglycerides', '2024-03-17', 'pending', 2000.00)
    ");

    echo "Database initialized successfully!\n";
    
} catch (Exception $e) {
    die("Error initializing database: " . $e->getMessage() . "\n");
} 