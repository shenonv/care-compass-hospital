<?php
require_once '../includes/config.php';
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();

// Get list of doctors
$doctors = $db->query('SELECT id, name, specialty, availability FROM doctors ORDER BY name');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_POST['doctor_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    $errors = [];
    
    // Validate input
    if (empty($doctor_id)) {
        $errors[] = "Please select a doctor";
    }
    if (empty($appointment_date)) {
        $errors[] = "Please select an appointment date";
    }
    if (empty($appointment_time)) {
        $errors[] = "Please select an appointment time";
    }
    
    // Combine date and time
    $appointment_datetime = $appointment_date . ' ' . $appointment_time;
    
    // Check if the appointment is in the future
    if (strtotime($appointment_datetime) < time()) {
        $errors[] = "Appointment must be in the future";
    }
    
    if (empty($errors)) {
        // Check doctor availability
        $stmt = $db->prepare('
            SELECT COUNT(*) as count 
            FROM appointments 
            WHERE doctor_id = :doctor_id 
            AND appointment_date = :appointment_date 
            AND status != "cancelled"
        ');
        $stmt->bindValue(':doctor_id', $doctor_id, SQLITE3_INTEGER);
        $stmt->bindValue(':appointment_date', $appointment_datetime, SQLITE3_TEXT);
        $result = $stmt->execute();
        $existing = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($existing['count'] > 0) {
            $errors[] = "This time slot is already booked. Please choose another time.";
        } else {
            // Create new appointment
            $stmt = $db->prepare('
                INSERT INTO appointments (
                    patient_id, 
                    doctor_id, 
                    appointment_date, 
                    notes, 
                    status
                ) VALUES (
                    :patient_id,
                    :doctor_id,
                    :appointment_date,
                    :notes,
                    "pending"
                )
            ');
            
            $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':doctor_id', $doctor_id, SQLITE3_INTEGER);
            $stmt->bindValue(':appointment_date', $appointment_datetime, SQLITE3_TEXT);
            $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
            
            if ($stmt->execute()) {
                // Get the appointment ID
                $appointment_id = $db->lastInsertRowID();
                
                // Create a payment record
                $stmt = $db->prepare('
                    INSERT INTO payments (
                        patient_id,
                        amount,
                        payment_type,
                        reference_id,
                        status
                    ) VALUES (
                        :patient_id,
                        32000.00,
                        "appointment",
                        :reference_id,
                        "pending"
                    )
                ');
                
                $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
                $stmt->bindValue(':reference_id', $appointment_id, SQLITE3_INTEGER);
                $stmt->execute();
                
                header('Location: dashboard.php?appointment=scheduled');
                exit;
            } else {
                $errors[] = "Failed to schedule appointment";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Appointment - Care Compass Hospitals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/schedule_appointment.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Care Compass</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="schedule_appointment.php">Schedule Appointment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../lab-tests.php">Lab Tests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payments.php">Payments</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Schedule an Appointment</h2>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="doctor_id" class="form-label">Select Doctor</label>
                                <select class="form-select" id="doctor_id" name="doctor_id" required>
                                    <option value="">Choose a doctor...</option>
                                    <?php while ($doctor = $doctors->fetchArray(SQLITE3_ASSOC)): ?>
                                        <option value="<?php echo $doctor['id']; ?>">
                                            Dr. <?php echo htmlspecialchars($doctor['name']); ?> - 
                                            <?php echo htmlspecialchars($doctor['specialty']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="appointment_date" class="form-label">Appointment Date</label>
                                <input type="date" class="form-control" id="appointment_date" name="appointment_date" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="appointment_time" class="form-label">Appointment Time</label>
                                <select class="form-select" id="appointment_time" name="appointment_time" required>
                                    <option value="">Select time...</option>
                                    <?php
                                    $start = 9; // 9 AM
                                    $end = 17; // 5 PM
                                    for ($hour = $start; $hour < $end; $hour++) {
                                        $time = sprintf('%02d:00', $hour);
                                        echo "<option value=\"{$time}:00\">{$time}</option>";
                                        $time = sprintf('%02d:30', $hour);
                                        echo "<option value=\"{$time}:00\">{$time}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Any specific concerns or conditions..."></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Schedule Appointment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Get today's date in YYYY-MM-DD format
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('appointment_date').min = today;
        
        // Disable weekends
        document.getElementById('appointment_date').addEventListener('input', function(e) {
            const date = new Date(this.value);
            const day = date.getDay();
            
            if (day === 0 || day === 6) { // 0 = Sunday, 6 = Saturday
                alert('Please select a weekday. We are closed on weekends.');
                this.value = '';
            }
        });
    </script>
</body>
</html>
