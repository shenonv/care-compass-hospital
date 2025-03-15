<?php
require_once '../includes/config.php';
session_start();

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../patient/login.php');
    exit;
}

$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $treatment = $_POST['treatment'] ?? '';
    $medications = $_POST['medications'] ?? '';
    
    if (!empty($patient_id) && !empty($diagnosis) && !empty($treatment)) {
        // Start transaction
        $db->exec('BEGIN TRANSACTION');
        
        try {
            // Add medical record
            $stmt = $db->prepare('
                INSERT INTO medical_records (patient_id, doctor_id, diagnosis, treatment)
                VALUES (:patient_id, :doctor_id, :diagnosis, :treatment)
            ');
            $stmt->bindValue(':patient_id', $patient_id, SQLITE3_INTEGER);
            $stmt->bindValue(':doctor_id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':diagnosis', $diagnosis, SQLITE3_TEXT);
            $stmt->bindValue(':treatment', $treatment, SQLITE3_TEXT);
            $stmt->execute();
            
            // Add prescription if medications are provided
            if (!empty($medications)) {
                $stmt = $db->prepare('
                    INSERT INTO prescriptions (patient_id, doctor_id, medications)
                    VALUES (:patient_id, :doctor_id, :medications)
                ');
                $stmt->bindValue(':patient_id', $patient_id, SQLITE3_INTEGER);
                $stmt->bindValue(':doctor_id', $_SESSION['user_id'], SQLITE3_INTEGER);
                $stmt->bindValue(':medications', $medications, SQLITE3_TEXT);
                $stmt->execute();
            }
            
            $db->exec('COMMIT');
            header('Location: add_medical_record.php?success=1');
            exit;
        } catch (Exception $e) {
            $db->exec('ROLLBACK');
            $error = 'Failed to add medical record. Please try again.';
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

// Get all patients
$patients = $db->query('SELECT id, name FROM users WHERE user_type = "patient" ORDER BY name');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Medical Record - Care Compass Hospitals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Doctor Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="add_medical_record.php">Add Medical Record</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_appointments.php">View Appointments</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../patient/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <h2 class="mb-4">Add Medical Record</h2>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Medical record added successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="patient_id" class="form-label">Patient</label>
                        <select class="form-select" id="patient_id" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php while ($patient = $patients->fetchArray(SQLITE3_ASSOC)): ?>
                            <option value="<?php echo $patient['id']; ?>">
                                <?php echo htmlspecialchars($patient['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="diagnosis" class="form-label">Diagnosis</label>
                        <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="treatment" class="form-label">Treatment Plan</label>
                        <textarea class="form-control" id="treatment" name="treatment" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="medications" class="form-label">Prescribed Medications</label>
                        <textarea class="form-control" id="medications" name="medications" rows="3" 
                                placeholder="Enter each medication on a new line with dosage and instructions"></textarea>
                        <div class="form-text">
                            Leave blank if no medications are prescribed.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Add Medical Record</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
