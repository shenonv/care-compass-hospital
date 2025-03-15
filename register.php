<?php
session_start();
require_once 'includes/config.php';
$page_title = 'Patient Registration';

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    $redirect_url = $_SESSION['user_type'] === 'admin' ? 'admin/dashboard.php' : 'patient/dashboard.php';
    header("Location: $redirect_url");
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($full_name) || empty($username) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error_message = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
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

                    // Create new patient user
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
                            "patient",
                            datetime("now")
                        )
                    ');
                    
                    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                    $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
                    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                    $stmt->bindValue(':first_name', $first_name, SQLITE3_TEXT);
                    $stmt->bindValue(':last_name', $last_name, SQLITE3_TEXT);
                    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
                    
                    if ($stmt->execute()) {
                        // Get the inserted ID
                        $user_id = $db->lastInsertRowID();

                        // Commit transaction
                        $db->exec('COMMIT');

                        // Set session variables
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['user_type'] = 'patient';
                        $_SESSION['first_name'] = $first_name;
                        $_SESSION['last_name'] = $last_name;

                        // Redirect to patient dashboard
                        header('Location: patient/dashboard.php');
                        exit;
                    } else {
                        throw new Exception($db->lastErrorMsg());
                    }
                }
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->exec('ROLLBACK');
            error_log('Registration error: ' . $e->getMessage());
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
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-hospital-alt fa-3x text-primary mb-3"></i>
                        <h2 class="card-title">Patient Registration</h2>
                        <p class="text-muted">Create your patient account at Care Compass Hospitals</p>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   placeholder="Full Name" required>
                            <label for="full_name">Full Name</label>
                            <div class="invalid-feedback">Please enter your full name.</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Username" required>
                            <label for="username">Username</label>
                            <div class="invalid-feedback">Please choose a username.</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="Email" required>
                            <label for="email">Email Address</label>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   placeholder="Phone Number" required>
                            <label for="phone">Phone Number</label>
                            <div class="invalid-feedback">Please enter your phone number.</div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Password" required>
                            <label for="password">Password</label>
                            <div class="invalid-feedback">Password must be at least 6 characters.</div>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm Password" required>
                            <label for="confirm_password">Confirm Password</label>
                            <div class="invalid-feedback">Passwords must match.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-user-plus me-2"></i>Create Patient Account
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="mb-0">Already have an account? 
                            <a href="login.php">Login here</a>
                        </p>
                        <a href="index.php" class="d-block mt-3">
                            <i class="fas fa-home me-1"></i>Back to Home
                        </a>
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
