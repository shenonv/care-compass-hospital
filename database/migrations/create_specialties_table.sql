CREATE TABLE IF NOT EXISTS specialties (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert some common medical specialties
INSERT OR IGNORE INTO specialties (name) VALUES
    ('General Medicine'),
    ('Pediatrics'),
    ('Cardiology'),
    ('Dermatology'),
    ('Orthopedics'),
    ('Neurology'),
    ('Gynecology'),
    ('ENT'),
    ('Ophthalmology'),
    ('Psychiatry');
