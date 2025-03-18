<?php
require_once 'config.php';

$db = getDBConnection();

// Insert sample data into medical_history table
$query = "
INSERT INTO medical_history (patient_id, date, diagnosis, treatment, notes) VALUES
(1, '2023-01-01', 'Flu', 'Rest and hydration', 'Patient recovering well'),
(1, '2023-02-15', 'Sprained Ankle', 'Ice and elevation', 'Follow-up in 2 weeks'),
(2, '2023-03-10', 'Hypertension', 'Medication prescribed', 'Monitor blood pressure regularly')
";
$db->exec($query);

echo "Sample data inserted successfully.";
?>
