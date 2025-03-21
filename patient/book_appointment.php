<?php
$page_title = "Book Appointment";
require_once '../includes/config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/header.php';

$db = getDBConnection();

// Get selected doctor ID from URL
$selected_doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;

// Get all active doctors with their details
$doctors = $db->query('
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        IFNULL(s.name, "General Medicine") as specialty
    FROM users u
    LEFT JOIN specialties s ON u.specialization = s.id
    WHERE u.user_type = "doctor"
    ORDER BY u.last_name, u.first_name
');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_POST['doctor_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    $errors = [];
    
    // Validate input
    if (empty($doctor_id)) {
        $errors[] = "Please select a doctor";
    }
    if (empty($appointment_date)) {
        $errors[] = "Please select appointment date and time";
    }
    
    // Check if the appointment date is in the future
    if (strtotime($appointment_date) < time()) {
        $errors[] = "Appointment date must be in the future";
    }
    
    if (empty($errors)) {
        // Get doctor details
        $stmt = $db->prepare('
            SELECT u.*, s.name as specialty_name 
            FROM users u
            LEFT JOIN specialties s ON u.specialization = s.id
            WHERE u.id = :doctor_id AND u.user_type = "doctor"
        ');
        $stmt->bindValue(':doctor_id', $doctor_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $doctor = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$doctor) {
            $errors[] = "Invalid doctor selected";
        } else {
            // Create new appointment
            $stmt = $db->prepare('
                INSERT INTO appointments (
                    patient_id,
                    doctor_id,
                    appointment_date,
                    appointment_time,
                    status,
                    notes,
                    created_at
                ) VALUES (
                    :patient_id,
                    :doctor_id,
                    :appointment_date,
                    :appointment_time,
                    "pending",
                    :notes,
                    CURRENT_TIMESTAMP
                )
            ');
            
            $appointment_date_obj = new DateTime($appointment_date);
            $date = $appointment_date_obj->format('Y-m-d');
            $time = $appointment_date_obj->format('H:i:s');
            
            $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':doctor_id', $doctor['id'], SQLITE3_INTEGER);
            $stmt->bindValue(':appointment_date', $date, SQLITE3_TEXT);
            $stmt->bindValue(':appointment_time', $time, SQLITE3_TEXT);
            $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
            
            if ($stmt->execute()) {
                header('Location: appointments.php?booked=1');
                exit;
            } else {
                $errors[] = "Failed to book appointment. Please try again.";
            }
        }
    }
}

// Get min and max dates for appointment booking
$min_date = date('Y-m-d\TH:i', strtotime('+1 hour'));
$max_date = date('Y-m-d\TH:i', strtotime('+30 days'));
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Book an Appointment</h2>
        <a href="appointments.php" class="btn btn-outline-primary">
            <i class="fas fa-calendar-alt me-2"></i>My Appointments
        </a>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label class="form-label">Select Doctor</label>
                    <select class="form-select" name="doctor_id" required>
                        <option value="">Choose a doctor...</option>
                        <?php while ($doctor = $doctors->fetchArray(SQLITE3_ASSOC)): ?>
                        <option value="<?php echo $doctor['id']; ?>" 
                                <?php echo ($selected_doctor_id === $doctor['id']) ? 'selected' : ''; ?>>
                            Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?> 
                            (<?php echo htmlspecialchars($doctor['specialty']); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <div class="invalid-feedback">Please select a doctor.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Appointment Date & Time</label>
                    <input type="datetime-local" 
                           class="form-control" 
                           name="appointment_date" 
                           min="<?php echo $min_date; ?>" 
                           max="<?php echo $max_date; ?>" 
                           required>
                    <div class="invalid-feedback">Please select a valid appointment date and time.</div>
                    <small class="text-muted">
                        Appointments are available for the next 30 days during hospital hours (9:00 AM - 5:00 PM)
                    </small>
                </div>

                <div class="mb-4">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea class="form-control" 
                              name="notes" 
                              rows="3" 
                              placeholder="Any specific concerns or additional information for the doctor..."></textarea>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-calendar-check me-2"></i>Book Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();

// Restrict appointment times to hospital hours (9 AM - 5 PM)
document.querySelector('input[name="appointment_date"]').addEventListener('change', function(e) {
    const date = new Date(this.value);
    const hours = date.getHours();
    
    if (hours < 9 || hours >= 17) {
        alert('Please select a time between 9:00 AM and 5:00 PM');
        this.value = '';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
