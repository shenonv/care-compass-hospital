<?php
require_once '../includes/config.php';
require_once '../includes/header.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($phone)) {
        $error_message = 'Name, email, and phone are required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            $db = getDBConnection();
            
            // Start transaction
            $db->exec('BEGIN');
            
            // Check if email exists for other users
            $stmt = $db->prepare('SELECT id FROM users WHERE email = :email AND id != :user_id');
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            
            if ($result->fetchArray()) {
                $error_message = 'Email address is already in use by another account.';
            } else {
                // Update user information
                $stmt = $db->prepare('UPDATE users SET full_name = :name, email = :email, phone = :phone, address = :address WHERE id = :user_id');
                $stmt->bindValue(':name', $name, SQLITE3_TEXT);
                $stmt->bindValue(':email', $email, SQLITE3_TEXT);
                $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
                $stmt->bindValue(':address', $address, SQLITE3_TEXT);
                $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
                $stmt->execute();

                // Handle password change if requested
                if (!empty($current_password)) {
                    // Verify current password
                    $stmt = $db->prepare('SELECT password FROM users WHERE id = :user_id');
                    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
                    $result = $stmt->execute();
                    $user = $result->fetchArray(SQLITE3_ASSOC);

                    if (password_verify($current_password, $user['password'])) {
                        if (empty($new_password) || empty($confirm_password)) {
                            $error_message = 'Please enter both new password and confirmation.';
                        } elseif ($new_password !== $confirm_password) {
                            $error_message = 'New password and confirmation do not match.';
                        } else {
                            // Update password
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $db->prepare('UPDATE users SET password = :password WHERE id = :user_id');
                            $stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT);
                            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
                            $stmt->execute();
                            $success_message = 'Profile and password updated successfully.';
                        }
                    } else {
                        $error_message = 'Current password is incorrect.';
                    }
                } else {
                    $success_message = 'Profile updated successfully.';
                }
            }

            if (empty($error_message)) {
                $db->exec('COMMIT');
            } else {
                $db->exec('ROLLBACK');
            }
        } catch (Exception $e) {
            $db->exec('ROLLBACK');
            $error_message = 'An error occurred while updating your profile.';
        }
    }
}

// Get current user data
try {
    $db = getDBConnection();
    $stmt = $db->prepare('SELECT first_name, last_name, email, phone FROM users WHERE id = :id');
    if (!$stmt) {
        die("Error: Unable to prepare the SQL statement.");
    }

    // Bind the user ID parameter
    $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);

    // Execute the statement and fetch the user profile
    $result = $stmt->execute();
    if (!$result) {
        die("Error: Unable to execute the SQL statement.");
    }

    $user = $result->fetchArray(SQLITE3_ASSOC);
    if (!$user) {
        die("Error: User not found.");
    }
} catch (Exception $e) {
    $error_message = 'An error occurred while fetching your profile.';
}

$page_title = 'My Profile';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-user-circle me-2"></i>My Profile</h4>
                </div>
                <div class="card-body">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"
                                    ><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">Change Password</h5>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                            <div class="form-text">Leave blank if you don't want to change your password.</div>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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

<?php require_once '../includes/footer.php'; ?>
