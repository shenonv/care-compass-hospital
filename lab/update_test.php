<?php
$page_title = "Update Lab Test";
require_once '../includes/config.php';

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/header.php';

$db = getDBConnection();
$test_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $status = $_POST['status'];
        $results = $_POST['results'];
        $notes = $_POST['notes'];
        
        $stmt = $db->prepare('
            UPDATE lab_tests 
            SET status = :status,
                results = :results,
                notes = :notes,
                updated_at = DATETIME("now")
            WHERE id = :id
        ');
        
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':results', $results, SQLITE3_TEXT);
        $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
        $stmt->bindValue(':id', $test_id, SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            $success_message = "Lab test updated successfully!";
        } else {
            throw new Exception($db->lastErrorMsg());
        }
    } catch (Exception $e) {
        error_log('Lab test update error: ' . $e->getMessage());
        $error_message = "Failed to update lab test. Please try again.";
    }
}

// Get test details
$stmt = $db->prepare('
    SELECT lt.*, 
           u.first_name, 
           u.last_name,
           u.email,
           u.phone
    FROM lab_tests lt
    JOIN users u ON lt.patient_id = u.id
    WHERE lt.id = :id
');
$stmt->bindValue(':id', $test_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$test = $result->fetchArray(SQLITE3_ASSOC);

// If test not found, redirect to dashboard
if (!$test) {
    header('Location: ../staff/dashboard.php');
    exit;
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Update Lab Test</h5>
                    <a href="../staff/dashboard.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
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

                    <div class="patient-info mb-4">
                        <h6 class="text-primary mb-3">Patient Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($test['first_name'] . ' ' . $test['last_name']); ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($test['email']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($test['phone']); ?></p>
                                <p class="mb-1"><strong>Test Date:</strong> <?php echo date('M j, Y', strtotime($test['test_date'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <form action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $test_id; ?>" method="POST">
                        <div class="mb-3">
                            <label for="test_name" class="form-label">Test Name</label>
                            <input type="text" class="form-control" id="test_name" value="<?php echo htmlspecialchars($test['test_name']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending" <?php echo $test['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_progress" <?php echo $test['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $test['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $test['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="results" class="form-label">Test Results</label>
                            <textarea class="form-control" id="results" name="results" rows="4"><?php echo htmlspecialchars($test['results'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($test['notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Lab Test
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 