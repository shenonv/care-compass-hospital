<?php
require_once '../includes/config.php';

try {
    $db = getDBConnection();

    // Drop existing tables if they exist
    $tables = ['test_results', 'test_bookings', 'lab_tests', 'medical_records', 'prescriptions'];
    foreach ($tables as $table) {
        $db->exec("DROP TABLE IF EXISTS $table");
    }

    // Create Lab Tests Table
    $db->exec('
        CREATE TABLE lab_tests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ');

    // Create Test Bookings Table
    $db->exec('
        CREATE TABLE test_bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_id INTEGER NOT NULL,
            test_id INTEGER NOT NULL,
            booking_date DATE NOT NULL,
            status TEXT DEFAULT "pending",
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id),
            FOREIGN KEY (test_id) REFERENCES lab_tests(id)
        )
    ');

    // Create Test Results Table
    $db->exec('
        CREATE TABLE test_results (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_id INTEGER NOT NULL,
            test_id INTEGER NOT NULL,
            test_date DATE NOT NULL,
            results TEXT NOT NULL,
            reference_range TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id),
            FOREIGN KEY (test_id) REFERENCES lab_tests(id)
        )
    ');

    // Create Medical Records Table
    $db->exec('
        CREATE TABLE medical_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_id INTEGER NOT NULL,
            doctor_id INTEGER NOT NULL,
            diagnosis TEXT NOT NULL,
            treatment TEXT NOT NULL,
            notes TEXT,
            record_date DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id),
            FOREIGN KEY (doctor_id) REFERENCES doctors(id)
        )
    ');

    // Create Prescriptions Table
    $db->exec('
        CREATE TABLE prescriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            patient_id INTEGER NOT NULL,
            doctor_id INTEGER NOT NULL,
            medications TEXT NOT NULL,
            dosage TEXT NOT NULL,
            duration TEXT NOT NULL,
            notes TEXT,
            prescribed_date DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES users(id),
            FOREIGN KEY (doctor_id) REFERENCES doctors(id)
        )
    ');

    // Insert sample lab tests
    $sample_tests = [
        [
            'name' => 'Complete Blood Count (CBC)',
            'description' => 'Measures different components of blood including red cells, white cells, and platelets',
            'price' => 500.00
        ],
        [
            'name' => 'Blood Sugar Test',
            'description' => 'Measures glucose levels in blood to diagnose and monitor diabetes',
            'price' => 300.00
        ],
        [
            'name' => 'Lipid Profile',
            'description' => 'Measures cholesterol and triglycerides in blood',
            'price' => 800.00
        ],
        [
            'name' => 'Thyroid Function Test',
            'description' => 'Measures thyroid hormone levels to check thyroid function',
            'price' => 1200.00
        ],
        [
            'name' => 'Liver Function Test',
            'description' => 'Assesses liver function and screens for liver diseases',
            'price' => 1000.00
        ],
        [
            'name' => 'Kidney Function Test',
            'description' => 'Evaluates kidney function and detects kidney diseases',
            'price' => 900.00
        ],
        [
            'name' => 'Urine Analysis',
            'description' => 'Analyzes urine sample for various health conditions',
            'price' => 400.00
        ],
        [
            'name' => 'HbA1c Test',
            'description' => 'Measures average blood sugar levels over past 3 months',
            'price' => 700.00
        ]
    ];

    $stmt = $db->prepare('INSERT INTO lab_tests (name, description, price) VALUES (:name, :description, :price)');
    foreach ($sample_tests as $test) {
        $stmt->bindValue(':name', $test['name'], SQLITE3_TEXT);
        $stmt->bindValue(':description', $test['description'], SQLITE3_TEXT);
        $stmt->bindValue(':price', $test['price'], SQLITE3_FLOAT);
        $stmt->execute();
    }

    echo "Medical tables created successfully!\n";
    echo "Sample lab tests added successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
