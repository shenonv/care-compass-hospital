-- Lab Tests Table
CREATE TABLE IF NOT EXISTS lab_tests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Test Bookings Table
CREATE TABLE IF NOT EXISTS test_bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id INTEGER NOT NULL,
    test_id INTEGER NOT NULL,
    booking_date DATE NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id),
    FOREIGN KEY (test_id) REFERENCES lab_tests(id)
);

-- Test Results Table
CREATE TABLE IF NOT EXISTS test_results (
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
);

-- Medical Records Table
CREATE TABLE IF NOT EXISTS medical_records (
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
);

-- Prescriptions Table
CREATE TABLE IF NOT EXISTS prescriptions (
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
);

-- Insert some sample lab tests
INSERT INTO lab_tests (name, description, price, status) VALUES
('Complete Blood Count (CBC)', 'Measures different components of blood including red cells, white cells, and platelets', 500.00, 'active'),
('Blood Sugar Test', 'Measures glucose levels in blood to diagnose and monitor diabetes', 300.00, 'active'),
('Lipid Profile', 'Measures cholesterol and triglycerides in blood', 800.00, 'active'),
('Thyroid Function Test', 'Measures thyroid hormone levels to check thyroid function', 1200.00, 'active'),
('Liver Function Test', 'Assesses liver function and screens for liver diseases', 1000.00, 'active'),
('Kidney Function Test', 'Evaluates kidney function and detects kidney diseases', 900.00, 'active'),
('Urine Analysis', 'Analyzes urine sample for various health conditions', 400.00, 'active'),
('HbA1c Test', 'Measures average blood sugar levels over past 3 months', 700.00, 'active');
