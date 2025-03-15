<?php
$page_title = "Lab Tests";
require_once '../includes/config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/header.php';

$db = getDBConnection();

// Handle messages
$success_message = '';
$error_message = '';

if (isset($_GET['message']) && $_GET['message'] === 'booking_success') {
    $success_message = 'Your test has been booked successfully.';
}

if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

// Get all active lab tests
$stmt = $db->prepare('SELECT * FROM lab_tests ORDER BY name ASC');
$available_tests = $stmt->execute();

// Get user's test bookings
$stmt = $db->prepare('
    SELECT b.*, t.name as test_name, t.price, t.description
    FROM test_bookings b 
    JOIN lab_tests t ON b.test_id = t.id 
    WHERE b.patient_id = :patient_id 
    ORDER BY b.booking_date DESC
');
$stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$bookings = $stmt->execute();
?>

<div class="container py-4">
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col">
            <h2>Laboratory Tests</h2>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bookTestModal">
                <i class="fas fa-flask me-2"></i>Book New Test
            </button>
        </div>
    </div>

    <!-- Test Bookings -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">My Test Bookings</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Test Name</th>
                            <th>Booking Date</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $has_bookings = false;
                        while ($booking = $bookings->fetchArray(SQLITE3_ASSOC)):
                            $has_bookings = true;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['test_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></td>
                            <td>₹<?php echo number_format($booking['price'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $booking['status'] === 'completed' ? 'success' : 
                                        ($booking['status'] === 'pending' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($booking['status'] === 'completed' && $booking['result_file']): ?>
                                <a href="../uploads/results/<?php echo htmlspecialchars($booking['result_file']); ?>" 
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-file-medical me-1"></i>View Result
                                </a>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                    <i class="fas fa-clock me-1"></i>Pending
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$has_bookings): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <i class="fas fa-flask fa-3x text-muted mb-3 d-block"></i>
                                <p class="mb-0">No test bookings found</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Available Tests -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Available Tests</h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <?php 
                $has_tests = false;
                while ($test = $available_tests->fetchArray(SQLITE3_ASSOC)):
                    $has_tests = true;
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title text-primary"><?php echo htmlspecialchars($test['name']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($test['description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="h5 mb-0">₹<?php echo number_format($test['price'], 2); ?></span>
                                <button type="button" class="btn btn-primary btn-sm book-test" 
                                        data-test-id="<?php echo $test['id']; ?>"
                                        data-test-name="<?php echo htmlspecialchars($test['name']); ?>"
                                        data-test-price="<?php echo $test['price']; ?>">
                                    <i class="fas fa-calendar-plus me-1"></i>Book Now
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if (!$has_tests): ?>
                <div class="col-12 text-center py-4">
                    <i class="fas fa-vial-circle-xmark fa-3x text-muted mb-3 d-block"></i>
                    <p class="mb-0">No tests available at the moment</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Book Test Modal -->
<div class="modal fade" id="bookTestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="book_test.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Book Test Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="test_id" id="test_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Selected Test</label>
                        <input type="text" class="form-control" id="test_name" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="text" class="form-control" id="test_price" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="booking_date" class="form-label">Preferred Date</label>
                        <input type="date" class="form-control" id="booking_date" name="booking_date" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                        <div class="form-text">Please select your preferred date for the test.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-check me-1"></i>Confirm Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle test booking button clicks
    document.querySelectorAll('.book-test').forEach(button => {
        button.addEventListener('click', function() {
            const testId = this.dataset.testId;
            const testName = this.dataset.testName;
            const testPrice = this.dataset.testPrice;

            document.getElementById('test_id').value = testId;
            document.getElementById('test_name').value = testName;
            document.getElementById('test_price').value = testPrice;

            const modal = new bootstrap.Modal(document.getElementById('bookTestModal'));
            modal.show();
        });
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

<?php require_once '../includes/footer.php'; ?>
