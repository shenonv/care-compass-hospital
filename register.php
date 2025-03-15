<?php
session_start();
require_once 'includes/config.php';
$page_title = 'Register';

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
            
            error_log("DEBUG: Database connection established");
            error_log("DEBUG: Attempting to register user: $username");
            
            // Verify database exists
            if (!file_exists(DB_FILE)) {
                error_log("DEBUG: Database file does not exist at: " . DB_FILE);
                throw new Exception('Database file not found');
            }

            // Start transaction
            $db->exec('BEGIN TRANSACTION');

            // Create users table if not exists
            $db->exec('
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT UNIQUE NOT NULL,
                    password TEXT NOT NULL,
                    email TEXT UNIQUE NOT NULL,
                    first_name TEXT NOT NULL,
                    last_name TEXT,
                    phone TEXT,
                    user_type TEXT NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_login DATETIME
                )
            ');

            // Check if username exists
            $stmt = $db->prepare('SELECT id FROM users WHERE username = :username');
            if (!$stmt) {
                throw new Exception($db->lastErrorMsg());
            }
            
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception($db->lastErrorMsg());
            }

            if ($result->fetchArray()) {
                $error_message = 'Username already exists.';
            } else {
                // Check if email exists
                $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
                if (!$stmt) {
                    throw new Exception($db->lastErrorMsg());
                }

                $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                $result = $stmt->execute();
                if (!$result) {
                    throw new Exception($db->lastErrorMsg());
                }

                if ($result->fetchArray()) {
                    $error_message = 'Email already exists.';
                } else {
                    // Create new user with explicit columns
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
                    
                    if (!$stmt) {
                        throw new Exception($db->lastErrorMsg());
                    }

                    // Split full name into first and last name
                    $name_parts = explode(' ', $full_name, 2);
                    $first_name = $name_parts[0];
                    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

                    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                    $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
                    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                    $stmt->bindValue(':first_name', $first_name, SQLITE3_TEXT);
                    $stmt->bindValue(':last_name', $last_name, SQLITE3_TEXT);
                    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
                    $result = $stmt->execute();
                    if (!$result) {
                        throw new Exception($db->lastErrorMsg());
                    }

                    // Get the inserted ID
                    $user_id = $db->lastInsertRowID();
                    if (!$user_id) {
                        throw new Exception('Failed to get last insert ID');
                    }

                    // Commit transaction
                    $db->exec('COMMIT');

                    // Auto login after registration
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['user_type'] = 'patient';
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['last_name'] = $last_name;

                    header('Location: patient/dashboard.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->exec('ROLLBACK');
            error_log('Registration error: ' . $e->getMessage());
            $error_message = 'Registration failed: ' . $e->getMessage();
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
                        <h2 class="card-title">Create Your Account</h2>
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
                            <i class="fas fa-user-plus me-2"></i>Create Account
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
    // Form validation
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html>
