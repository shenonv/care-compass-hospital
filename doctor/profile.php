<?php
require_once '../includes/config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit;
}

$db = getDBConnection();
$doctor_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->exec('BEGIN TRANSACTION');
        
        // Update profile information
        $stmt = $db->prepare('
            UPDATE users SET 
                email = :email,
                first_name = :first_name,
                last_name = :last_name,
                phone = :phone
            WHERE id = :doctor_id
        ');
        
        $stmt->bindValue(':email', $_POST['email'], SQLITE3_TEXT);
        $stmt->bindValue(':first_name', $_POST['first_name'], SQLITE3_TEXT);
        $stmt->bindValue(':last_name', $_POST['last_name'], SQLITE3_TEXT);
        $stmt->bindValue(':phone', $_POST['phone'], SQLITE3_TEXT);
        $stmt->bindValue(':doctor_id', $doctor_id, SQLITE3_INTEGER);
        
        $stmt->execute();
        
        // Handle password change if requested
        if (!empty($_POST['new_password'])) {
            if (empty($_POST['current_password'])) {
                throw new Exception('Current password is required to change password');
            }
            
            // Verify current password
            $stmt = $db->prepare('SELECT password FROM users WHERE id = :doctor_id');
            $stmt->bindValue(':doctor_id', $doctor_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $user = $result->fetchArray(SQLITE3_ASSOC);
            
            if (!password_verify($_POST['current_password'], $user['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Update password
            $stmt = $db->prepare('UPDATE users SET password = :password WHERE id = :doctor_id');
            $stmt->bindValue(':password', password_hash($_POST['new_password'], PASSWORD_DEFAULT), SQLITE3_TEXT);
            $stmt->bindValue(':doctor_id', $doctor_id, SQLITE3_INTEGER);
            $stmt->execute();
        }
        
        $db->exec('COMMIT');
        $_SESSION['success_message'] = 'Profile updated successfully';
        
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        $_SESSION['error_message'] = 'Error updating profile: ' . $e->getMessage();
    }
}

// Get doctor's current information
$stmt = $db->prepare('SELECT * FROM users WHERE id = :id AND user_type = "doctor"');
$stmt->bindValue(':id', $doctor_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$doctor = $result->fetchArray(SQLITE3_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile - Doctor Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Care Compass Hospital</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
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
                <h2><i class="fas fa-user-md"></i> My Profile</h2>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Edit Profile Information</h5>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" 
                                       value="<?php echo htmlspecialchars($doctor['first_name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" 
                                       value="<?php echo htmlspecialchars($doctor['last_name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($doctor['phone']); ?>">
                            </div>

                            <h5 class="mt-4 mb-3">Change Password</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password">
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Professional Information</h5>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($doctor['username']); ?></p>
                        <p><strong>Specialization:</strong> <?php echo htmlspecialchars($doctor['specialization']); ?></p>
                        <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($doctor['created_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
