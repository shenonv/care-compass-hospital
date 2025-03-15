<?php
session_start();
require_once '../includes/config.php';
$page_title = 'Register Staff';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $user_type = trim($_POST['user_type']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($full_name) || empty($username) || empty($email) || empty($phone) || empty($password) || empty($confirm_password) || empty($user_type)) {
        $error_message = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (!in_array($user_type, ['staff', 'doctor'])) {
        $error_message = 'Invalid user type selected.';
    } else {
        try {
            $db = getDBConnection();
            
            // Start transaction
            $db->exec('BEGIN TRANSACTION');

            // Check if username exists
            $stmt = $db->prepare('SELECT id FROM users WHERE username = :username');
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $result = $stmt->execute();

            if ($result->fetchArray()) {
                $error_message = 'Username already exists.';
            } else {
                // Check if email exists
                $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
                $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                $result = $stmt->execute();

                if ($result->fetchArray()) {
                    $error_message = 'Email already exists.';
                } else {
                    // Split full name into first and last name
                    $name_parts = explode(' ', $full_name, 2);
                    $first_name = $name_parts[0];
                    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

                    // Create new staff user
                    $stmt = $db->prepare('
                        INSERT INTO users (
                            username,
                            password,
                            email,
                            first_name,
                            last_name,
                            phone,
                            user_type,
                            created_at
                        ) VALUES (
                            :username,
                            :password,
                            :email,
                            :first_name,
                            :last_name,
                            :phone,
                            :user_type,
                            datetime("now")
                        )
                    ');
                    
                    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                    $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
                    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                    $stmt->bindValue(':first_name', $first_name, SQLITE3_TEXT);
                    $stmt->bindValue(':last_name', $last_name, SQLITE3_TEXT);
                    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
                    $stmt->bindValue(':user_type', $user_type, SQLITE3_TEXT);
                    
                    if ($stmt->execute()) {
                        // Commit transaction
                        $db->exec('COMMIT');
                        $success_message = ucfirst($user_type) . ' account created successfully!';
                    } else {
                        throw new Exception($db->lastErrorMsg());
                    }
                }
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->exec('ROLLBACK');
            error_log('Staff registration error: ' . $e->getMessage());
            $error_message = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Care Compass Hospitals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                            <h2 class="card-title">Register Hospital Staff</h2>
                            <p class="text-muted">Create new staff or doctor account</p>
                        </div>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="user_type" class="form-label">Staff Type</label>
                                <select class="form-select" id="user_type" name="user_type" required>
                                    <option value="">Select Staff Type</option>
                                    <option value="doctor">Doctor</option>
                                    <option value="staff">Hospital Staff</option>
                                </select>
                                <div class="invalid-feedback">Please select a staff type.</div>
                            </div>

                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                                <div class="invalid-feedback">Please enter the full name.</div>
                            </div>

                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <div class="invalid-feedback">Please choose a username.</div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                                <div class="invalid-feedback">Please enter a phone number.</div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="invalid-feedback">Password must be at least 6 characters.</div>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div class="invalid-feedback">Passwords must match.</div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
    </script>
</body>
</html> 