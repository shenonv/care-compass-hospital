<?php
$page_title = "My Lab Tests";
require_once '../includes/config.php';
require_once '../includes/header.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();

// Get all test bookings for the current patient
$stmt = $db->prepare('
    SELECT 
        tb.*,
        lt.name as test_name,
        lt.price,
        p.status as payment_status
    FROM test_bookings tb
    JOIN lab_tests lt ON tb.test_id = lt.id
    LEFT JOIN payments p ON p.reference_id = tb.id AND p.payment_type = "lab_test"
    WHERE tb.patient_id = :patient_id
    ORDER BY tb.booking_date DESC
');
$stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$bookings = $stmt->execute();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Lab Tests</h2>
        <a href="book_test.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Book New Test
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Test booked successfully! Please complete the payment to confirm your booking.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Test Name</th>
                            <th>Date & Time</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                            <th>Results</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $bookings->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['test_name']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($booking['booking_date'])); ?></td>
                            <td>$<?php echo number_format($booking['price'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $booking['status'] === 'completed' ? 'success' : 
                                        ($booking['status'] === 'pending' ? 'warning' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($booking['payment_status'] === 'completed'): ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php else: ?>
                                    <a href="make_payment.php?type=test&id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-sm btn-warning">
                                        Pay Now
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($booking['results']): ?>
                                    <button class="btn btn-sm btn-info" onclick="viewResults(<?php 
                                        echo htmlspecialchars(json_encode($booking['results'])); 
                                    ?>)">
                                        View Results
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Results Modal -->
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="resultContent" class="bg-light p-3 rounded"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewResults(results) {
    document.getElementById('resultContent').textContent = results;
    new bootstrap.Modal(document.getElementById('resultsModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
