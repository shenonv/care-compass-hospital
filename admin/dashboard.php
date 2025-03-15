<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get admin information
$admin_id = $_SESSION['user_id'];
$db = getDBConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND user_type = 'admin'");
$stmt->bindValue(':id', $admin_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$admin = $result->fetchArray(SQLITE3_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Care Compass Hospitals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-card {
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
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

    <div class="container my-5">
        <h2 class="mb-4">Welcome, <?php echo htmlspecialchars($admin['username']); ?>!</h2>
        
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-md fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Manage Doctors</h5>
                        <p class="card-text">Add, edit, or remove doctors from the system</p>
                        <a href="manage_doctors.php" class="btn btn-primary">Manage Doctors</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Manage Staff</h5>
                        <p class="card-text">Manage hospital staff and their roles</p>
                        <a href="manage_staff.php" class="btn btn-primary">Manage Staff</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Register Staff</h5>
                        <p class="card-text">Add new doctors or hospital staff</p>
                        <a href="register_staff.php" class="btn btn-primary">Register Staff</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-hospital fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Manage Services</h5>
                        <p class="card-text">Update hospital services and departments</p>
                        <a href="manage_services.php" class="btn btn-primary">Manage Services</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
