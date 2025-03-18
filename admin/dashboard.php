<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get database connection
$db = getDBConnection();
if (!$db) {
    die("Error: Database connection failed");
}

try {
    // Get admin information
    $admin_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND user_type = 'admin'");
    $stmt->bindValue(':id', $admin_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $admin = $result->fetchArray(SQLITE3_ASSOC);

    // Count doctors
    $doctorsStmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'doctor'");
    $doctorsResult = $doctorsStmt->execute();
    $doctorsCount = $doctorsResult->fetchArray(SQLITE3_ASSOC)['count'];

    // Count staff (excluding doctors and admins)
    $staffStmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE user_type = 'staff'");
    $staffResult = $staffStmt->execute();
    $staffCount = $staffResult->fetchArray(SQLITE3_ASSOC)['count'];

    // Count departments
    $departmentsStmt = $db->prepare("SELECT COUNT(*) as count FROM departments WHERE is_active = 1");
    $departmentsResult = $departmentsStmt->execute();
    $departmentsCount = $departmentsResult->fetchArray(SQLITE3_ASSOC)['count'];

    // Count services
    $servicesStmt = $db->prepare("SELECT COUNT(*) as count FROM services");
    $servicesResult = $servicesStmt->execute();
    $servicesCount = $servicesResult->fetchArray(SQLITE3_ASSOC)['count'];

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
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
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
        }
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            height: 100vh;
            background-color: var(--primary-color);
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            padding-top: 20px;
            color: white;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .stats-card {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .nav-link {
            color: #fff;
            padding: 10px 20px;
            margin: 5px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .nav-link:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        .card-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3 class="text-center mb-4">Care Compass</h3>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="#"><i class="fas fa-home me-2"></i> Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_doctors.php"><i class="fas fa-user-md me-2"></i> Doctors</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_staff.php"><i class="fas fa-users me-2"></i> Staff</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_services.php"><i class="fas fa-hospital me-2"></i> Services</a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">Welcome, <?php echo htmlspecialchars($admin['username']); ?>!</h2>
            
            <!-- Stats Overview -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <h3 class="h5">Total Doctors</h3>
                        <h2><?php echo $doctorsCount; ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <h3 class="h5">Total Staff</h3>
                        <h2><?php echo $staffCount; ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <h3 class="h5">Departments</h3>
                        <h2><?php echo $departmentsCount; ?></h2>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <h3 class="h5">Services</h3>
                        <h2><?php echo $servicesCount; ?></h2>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <h4 class="mb-4">Quick Actions</h4>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="dashboard-card h-100">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-user-md card-icon"></i>
                            <h5 class="card-title">Manage Doctors</h5>
                            <p class="card-text text-muted">Add, edit, or remove doctors from the system</p>
                            <a href="manage_doctors.php" class="btn btn-primary w-100">Manage Doctors</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="dashboard-card h-100">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-users card-icon"></i>
                            <h5 class="card-title">Manage Staff</h5>
                            <p class="card-text text-muted">Manage hospital staff and their roles</p>
                            <a href="manage_staff.php" class="btn btn-primary w-100">Manage Staff</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="dashboard-card h-100">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-user-plus card-icon"></i>
                            <h5 class="card-title">Register Staff</h5>
                            <p class="card-text text-muted">Add new doctors or hospital staff</p>
                            <a href="register_staff.php" class="btn btn-primary w-100">Register Staff</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="dashboard-card h-100">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-hospital card-icon"></i>
                            <h5 class="card-title">Manage Services</h5>
                            <p class="card-text text-muted">Update hospital services and departments</p>
                            <a href="manage_services.php" class="btn btn-primary w-100">Manage Services</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
