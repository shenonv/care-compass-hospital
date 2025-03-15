<?php
session_start();
$page_title = "Patient Dashboard";
require_once '../includes/config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit;
}

try {
    $db = getDBConnection();

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

    // Get upcoming appointments
    $stmt = $db->prepare('
        SELECT a.* FROM appointments a 
        WHERE a.patient_id = :patient_id 
        AND a.appointment_date >= date("now")
        AND a.status != "cancelled"
        ORDER BY a.appointment_date ASC 
        LIMIT 5
    ');
    $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $upcoming_appointments = $stmt->execute();

} catch (Exception $e) {
    error_log("Patient dashboard error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Care Compass Hospitals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Patient Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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
                                        <h6 class="mb-0">Appointment #<?php echo $appointment['id']; ?></h6>
                                    </div>
                                    <div class="col-md-4">
                                        <i class="fas fa-calendar-day me-2"></i>
                                        <?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?>
                                        <br>
                                        <i class="fas fa-clock me-2"></i>
                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
