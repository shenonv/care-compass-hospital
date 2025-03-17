<?php
require_once '../includes/config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit;
}

$db = getDBConnection();
$doctor_id = $_SESSION['user_id'];

// Get patient_id from query parameter
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

if ($patient_id === 0) {
    echo "Invalid patient ID.";
    exit;
}

// Get patient's medical history
$stmt = $db->prepare('
    SELECT 
        mr.*,
        d.first_name as doctor_first_name,
        d.last_name as doctor_last_name
    FROM medical_records mr
    JOIN users d ON mr.doctor_id = d.id
    WHERE mr.patient_id = :patient_id
    ORDER BY mr.visit_date DESC
');
$stmt->bindValue(':patient_id', $patient_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$medical_history = [];

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $medical_history[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Medical History - Care Compass Hospital</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <h2 class="text-primary"><i class="fas fa-notes-medical"></i> Patient Medical History</h2>
        <div class="card mt-4">
            <div class="card-body">
                <?php if (empty($medical_history)): ?>
                    <p class="text-muted">No medical history found for this patient.</p>
                <?php else: ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Doctor</th>
                                <th>Diagnosis</th>
                                <th>Treatment</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medical_history as $entry): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($entry['visit_date']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['doctor_first_name'] . ' ' . $entry['doctor_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['diagnosis']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['prescription']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['notes']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
