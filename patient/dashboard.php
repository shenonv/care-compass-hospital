<?php
$page_title = "Patient Dashboard";
require_once '../includes/config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/header.php';

try {
    $db = new SQLite3('../db/hospital.db');
    
    // First ensure the table exists
    $db->exec(file_get_contents('../db/init_database.sql'));
    
    $stmt = $db->prepare('SELECT * FROM doctors');
    if ($stmt === false) {
        throw new Exception($db->lastErrorMsg());
    }
    
    $result = $stmt->execute();
    // Ensure the appointments table exists
    createTablesIfNotExist($db);

    // Get user details
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    // If user not found, log them out
    if (!$user) {
        session_destroy();
        header('Location: ../login.php');
        exit;
    }

    // Prepare the SQL statement
    $stmt = $db->prepare('SELECT * FROM appointments WHERE patient_id = :patient_id');
    if (!$stmt) {
        die("Error: Unable to prepare the SQL statement.");
    }

    // Bind the patient_id parameter
    $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);

    // Execute the statement and fetch results
    $result = $stmt->execute();
    if (!$result) {
        die("Error: Unable to execute the SQL statement.");
    }

    // Get upcoming appointments
    $stmt = $db->prepare('
        SELECT a.*, d.name as doctor_name, d.specialty, d.consultation_fee 
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.id 
        WHERE a.patient_id = :patient_id 
        AND a.appointment_date >= datetime("now") 
        AND a.status != "cancelled"
        ORDER BY a.appointment_date ASC 
        LIMIT 5
    ');
    $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $upcoming_appointments = $stmt->execute();

    // Get recent test bookings
    $stmt = $db->prepare('
        SELECT b.*, t.name as test_name, t.price 
        FROM test_bookings b 
        JOIN lab_tests t ON b.test_id = t.id 
        WHERE b.patient_id = :patient_id 
        ORDER BY b.booking_date DESC 
        LIMIT 5
    ');
    $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $recent_tests = $stmt->execute();

    // Get recent payments
    $stmt = $db->prepare('
        SELECT * FROM payments 
        WHERE patient_id = :patient_id 
        ORDER BY created_at DESC 
        LIMIT 5
    ');
    $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $recent_payments = $stmt->execute();

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="container py-4">
    <?php if (isset($_GET['welcome']) && $_GET['welcome'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <h4 class="alert-heading">Welcome to Care Compass Hospitals!</h4>
        <p>Thank you for registering with us. Your account has been created successfully, and you're now logged in.</p>
        <p class="mb-0">You can now book appointments with our expert doctors, schedule lab tests, and manage your healthcare journey.</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col">
            <h2>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h2>
        </div>
    </div>

    <div class="row g-4">
        <!-- Quick Actions -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="book_appointment.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                        </a>
                        <a href="lab_tests.php" class="btn btn-outline-primary">
                            <i class="fas fa-flask me-2"></i>Schedule Lab Test
                        </a>
                        <a href="medical_records.php" class="btn btn-outline-primary">
                            <i class="fas fa-file-medical me-2"></i>View Medical Records
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="col-md-6 col-lg-9">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Upcoming Appointments</h5>
                        <a href="appointments.php" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                    <?php
                    $has_appointments = false;
                    while ($appointment = $upcoming_appointments->fetchArray(SQLITE3_ASSOC)):
                        $has_appointments = true;
                    ?>
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <h6 class="mb-0">
                                        Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($appointment['specialty']); ?>
                                    </small>
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-calendar-day me-2"></i>
                                    <?php echo date('d M Y, h:i A', strtotime($appointment['appointment_date'])); ?>
                                </div>
                                <div class="col-md-2">
                                    <span class="badge bg-<?php 
                                        echo $appointment['status'] === 'confirmed' ? 'success' : 
                                            ($appointment['status'] === 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </div>
                                <div class="col-md-2 text-end">
                                    <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php if (!$has_appointments): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-day fa-3x text-muted mb-3"></i>
                        <p class="mb-0">No upcoming appointments</p>
                        <a href="book_appointment.php" class="btn btn-primary mt-3">
                            Book an Appointment
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Lab Tests -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Recent Lab Tests</h5>
                        <a href="lab_tests.php" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                    <?php
                    $has_tests = false;
                    while ($test = $recent_tests->fetchArray(SQLITE3_ASSOC)):
                        $has_tests = true;
                    ?>
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-5">
                                    <h6 class="mb-0">
                                        <?php echo htmlspecialchars($test['test_name']); ?>
                                    </h6>
                                    <small class="text-muted">
                                        ₹<?php echo number_format($test['price'], 2); ?>
                                    </small>
                                </div>
                                <div class="col-md-4">
                                    <i class="fas fa-calendar-day me-2"></i>
                                    <?php echo date('d M Y', strtotime($test['booking_date'])); ?>
                                </div>
                                <div class="col-md-3 text-end">
                                    <span class="badge bg-<?php 
                                        echo $test['status'] === 'completed' ? 'success' : 
                                            ($test['status'] === 'pending' ? 'warning' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($test['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php if (!$has_tests): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-flask fa-3x text-muted mb-3"></i>
                        <p class="mb-0">No recent lab tests</p>
                        <a href="lab_tests.php" class="btn btn-primary mt-3">
                            Schedule a Test
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Recent Payments</h5>
                        <a href="payments.php" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                    <?php
                    $has_payments = false;
                    while ($payment = $recent_payments->fetchArray(SQLITE3_ASSOC)):
                        $has_payments = true;
                    ?>
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <h6 class="mb-0">₹<?php echo number_format($payment['amount'], 2); ?></h6>
                                    <small class="text-muted">
                                        <?php echo ucfirst($payment['payment_type']); ?>
                                    </small>
                                </div>
                                <div class="col-md-5">
                                    <small class="text-muted">
                                        <?php echo date('d M Y, h:i A', strtotime($payment['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="col-md-3 text-end">
                                    <a href="view_payment.php?id=<?php echo $payment['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php if (!$has_payments): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i>
                        <p class="mb-0">No recent payments</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
