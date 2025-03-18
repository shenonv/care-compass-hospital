<?php
require_once '../includes/config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit;
}

$db = getDBConnection();
$doctor_id = $_SESSION['user_id'];

// Get doctor's information
$stmt = $db->prepare('SELECT * FROM users WHERE id = :id AND user_type = "doctor"');
$stmt->bindValue(':id', $doctor_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$doctor = $result->fetchArray(SQLITE3_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Doctor Dashboard - Care Compass Hospital</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-card {
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Care Compass Hospital</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user-md"></i> Dr. <?php echo htmlspecialchars($doctor['first_name']); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h2 class="text-primary"><i class="fas fa-hospital-user"></i> Doctor Dashboard</h2>
                <p class="text-muted lead">Welcome back, Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card dashboard-card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class="fas fa-calendar-check fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">Today's Appointments</h5>
                            <p class="card-text small text-muted mb-0">Manage your appointments</p>
                            <a href="appointments.php" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card dashboard-card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="fas fa-user-injured fa-2x text-success"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">Patient Records</h5>
                            <p class="card-text small text-muted mb-0">View and manage patient records</p>
                            <a href="patient_records.php" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card dashboard-card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                            <i class="fas fa-user-cog fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-1">My Profile</h5>
                            <p class="card-text small text-muted mb-0">Update your profile information</p>
                            <a href="profile.php" class="stretched-link"></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4 g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0"><i class="fas fa-calendar-day text-primary"></i> Upcoming Appointments</h5>
                    </div>
                    <div class="card-body">
                        <!-- Add upcoming appointments list here -->
                        <p class="text-muted">No upcoming appointments</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="card-title mb-0"><i class="fas fa-bell text-primary"></i> Notifications</h5>
                    </div>
                    <div class="card-body">
                        <!-- Add notifications here -->
                        <p class="text-muted">No new notifications</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
