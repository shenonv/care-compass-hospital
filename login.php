<?php
session_start();
$page_title = "Login";
require_once 'includes/config.php';

// Clear any existing session
session_destroy();
session_start();

// Initialize error variable
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $userType = $_POST['user_type'] ?? '';

    error_log("DEBUG: Login attempt - Username: $username, Type: $userType");

    // Validate input
    if (empty($username) || empty($password) || empty($userType)) {
        $error = 'All fields are required';
    } else {
        try {
            $db = getDBConnection();
            
            // Verify database connection
            if (!$db) {
                throw new Exception('Database connection failed');
            }

            // Check if user exists and verify password
            $stmt = $db->prepare('
                SELECT * FROM users 
                WHERE username = :username 
                AND user_type = :userType
            ');
            
            if (!$stmt) {
                throw new Exception($db->lastErrorMsg());
            }

            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':userType', $userType, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception($db->lastErrorMsg());
            }

            $user = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                error_log("Login successful for user: $username");

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];

                // Update last login time
                $update = $db->prepare('UPDATE users SET last_login = datetime("now") WHERE id = :id');
                $update->bindValue(':id', $user['id'], SQLITE3_INTEGER);
                $update->execute();

                // Redirect based on user type
                switch($user['user_type']) {
                    case 'admin':
                        header('Location: admin/dashboard.php');
                        break;
                    case 'patient':
                        header('Location: patient/dashboard.php');
                        break;
                    case 'staff':
                        header('Location: staff/dashboard.php');
                        break;
                    default:
                        throw new Exception('Invalid user type');
                }
                exit;
            } else {
                $error = 'Invalid username or password';
                error_log("Login failed for user: $username of type: $userType");
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred during login. Please try again. Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Care Compass Hospitals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary">Welcome Back</h2>
                            <p class="text-muted">Login to Care Compass Hospitals</p>
                        </div>

                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <div class="form-floating">
                                    <select class="form-select" id="user_type" name="user_type" required>
                                        <option value="">Select User Type</option>
                                        <option value="patient">Patient</option>
                                        <option value="admin">Administrator</option>
                                        <option value="staff">Hospital Staff</option>
                                    </select>
                                    <label for="user_type">Login As</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="Username" required>
                                    <label for="username">Username</label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Password" required>
                                    <label for="password">Password</label>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>

                            <div class="text-center">
                                <p class="mb-0">Don't have an account? <a href="register.php" class="text-primary">Register</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Form validation
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
