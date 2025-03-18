<?php
$page_title = "Staff Profile";
require_once '../includes/config.php';

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/header.php';

$db = getDBConnection();
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
        $last_name = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
        $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Start transaction
        $db->exec('BEGIN TRANSACTION');

        // Check if email already exists for other users
        $stmt = $db->prepare('SELECT id FROM users WHERE email = :email AND id != :user_id');
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        if ($result->fetchArray()) {
            throw new Exception('Email already exists for another user.');
        }

        // If changing password
        if (!empty($current_password)) {
            // Verify current password
            $stmt = $db->prepare('SELECT password FROM users WHERE id = :user_id');
            $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $user = $result->fetchArray(SQLITE3_ASSOC);

            if (!password_verify($current_password, $user['password'])) {
                throw new Exception('Current password is incorrect.');
            }

            if (empty($new_password) || empty($confirm_password)) {
                throw new Exception('New password and confirmation are required.');
            }

            if ($new_password !== $confirm_password) {
                throw new Exception('New password and confirmation do not match.');
            }

            // Update user with new password
            $stmt = $db->prepare('
                UPDATE users 
                SET email = :email,
                    first_name = :first_name,
                    last_name = :last_name,
                    phone = :phone,
                    password = :password
                WHERE id = :user_id
            ');
            $stmt->bindValue(':password', password_hash($new_password, PASSWORD_DEFAULT), SQLITE3_TEXT);
        } else {
            // Update user without changing password
            $stmt = $db->prepare('
                UPDATE users 
                SET email = :email,
                    first_name = :first_name,
                    last_name = :last_name,
                    phone = :phone
                WHERE id = :user_id
            ');
        }

        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':first_name', $first_name, SQLITE3_TEXT);
        $stmt->bindValue(':last_name', $last_name, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);

        $stmt->execute();
        $db->exec('COMMIT');
        $success_message = "Profile updated successfully!";
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        $error_message = $e->getMessage();
    }
}

// Get user details
$stmt = $db->prepare('
    SELECT username, email, first_name, last_name, phone, specialization, created_at 
    FROM users 
    WHERE id = :user_id
');
$stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Staff Profile</h5>
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

                    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="specialization" class="form-label">Department</label>
                                <input type="text" class="form-control" id="specialization" value="<?php echo htmlspecialchars($user['specialization']); ?>" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3">Change Password (optional)</h6>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            <div class="col-md-4">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="col-md-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 