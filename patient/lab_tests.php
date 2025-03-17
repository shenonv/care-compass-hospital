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

// Get all available test types
$available_tests = [
    ['name' => 'Complete Blood Count (CBC)', 'description' => 'Measures different components of blood', 'cost' => 4800.00],
    ['name' => 'Blood Glucose Test', 'description' => 'Measures blood sugar levels', 'cost' => 2560.00],
    ['name' => 'Lipid Profile', 'description' => 'Measures cholesterol and triglycerides', 'cost' => 6400.00],
    ['name' => 'Liver Function Test', 'description' => 'Assesses liver function and health', 'cost' => 8000.00],
    ['name' => 'Thyroid Function Test', 'description' => 'Checks thyroid hormone levels', 'cost' => 5760.00],
    ['name' => 'Urine Analysis', 'description' => 'Analyzes urine composition', 'cost' => 1600.00]
];

// Get all lab tests for the current patient
$stmt = $db->prepare('
    SELECT 
        lt.*,
        p.status as payment_status,
        p.amount as cost
    FROM lab_tests lt
    LEFT JOIN payments p ON p.reference_id = lt.id AND p.payment_type = "lab_test"
    WHERE lt.patient_id = :patient_id 
    ORDER BY lt.test_date DESC, lt.created_at DESC
');

$stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Lab Tests</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bookTestModal">
            <i class="fas fa-flask me-2"></i>Book New Test
        </button>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Test Name</th>
                            <th>Description</th>
                            <th>Test Date</th>
                            <th>Status</th>
                            <th>Result</th>
                            <th>Cost</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $has_tests = false;
                        while ($test = $result->fetchArray(SQLITE3_ASSOC)):
                            $has_tests = true;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                            <td>
                                <?php 
                                // Get description from available tests array
                                $test_description = '';
                                foreach ($available_tests as $available_test) {
                                    if ($available_test['name'] === $test['test_name']) {
                                        $test_description = $available_test['description'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($test_description);
                                ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($test['test_date'])); ?></td>
                            <td>
                                <?php
                                $status_badges = [
                                    'pending' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $badge_color = $status_badges[$test['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badge_color; ?>">
                                    <?php echo ucfirst($test['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (isset($test['results']) && $test['results']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" data-bs-target="#resultModal<?php echo $test['id']; ?>">
                                        <i class="fas fa-eye"></i> View Result
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                // Get cost from available tests array if not in database
                                $test_cost = 0;
                                foreach ($available_tests as $available_test) {
                                    if ($available_test['name'] === $test['test_name']) {
                                        $test_cost = $available_test['cost'];
                                        break;
                                    }
                                }
                                echo 'Rs. ' . number_format($test_cost, 2);
                                ?>
                            </td>
                            <td>
                                <?php
                                $payment_status = isset($test['payment_status']) ? $test['payment_status'] : 'pending';
                                $payment_badges = [
                                    'pending' => 'warning',
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                    'refunded' => 'info'
                                ];
                                $payment_badge_color = $payment_badges[$payment_status] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $payment_badge_color; ?>">
                                    <?php echo ucfirst($payment_status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($test['status'] === 'pending'): ?>
                                    <a href="cancel_test.php?id=<?php echo $test['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Are you sure you want to cancel this test?');">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php if (isset($test['results']) && $test['results']): ?>
                        <!-- Result Modal -->
                        <div class="modal fade" id="resultModal<?php echo $test['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Test Results - <?php echo htmlspecialchars($test['test_name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php if (isset($test['result_date'])): ?>
                                            <p><strong>Result Date:</strong> <?php echo date('F j, Y', strtotime($test['result_date'])); ?></p>
                                        <?php endif; ?>
                                        <div class="mt-3">
                                            <?php echo nl2br(htmlspecialchars($test['results'])); ?>
                                        </div>
                                        <?php if (isset($test['notes']) && $test['notes']): ?>
                                            <div class="mt-3">
                                                <strong>Notes:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($test['notes'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php endwhile; ?>

                        <?php if (!$has_tests): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-flask fa-2x mb-3"></i>
                                    <p class="mb-0">No lab tests found.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Book Test Modal -->
<div class="modal fade" id="bookTestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Book a Lab Test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="book_test.php" method="POST" id="bookTestForm">
                    <div class="mb-3">
                        <label for="test_type" class="form-label">Select Test</label>
                        <select class="form-select" id="test_type" name="test_type" required>
                            <option value="">Choose a test...</option>
                            <?php foreach ($available_tests as $test): ?>
                            <option value="<?php echo htmlspecialchars($test['name']); ?>" 
                                    data-description="<?php echo htmlspecialchars($test['description']); ?>"
                                    data-cost="<?php echo $test['cost']; ?>">
                                <?php echo htmlspecialchars($test['name']); ?> - Rs. <?php echo number_format($test['cost'], 2); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="testDetails" class="card mb-3 d-none">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 text-muted">Test Details</h6>
                            <p id="testDescription" class="card-text"></p>
                            <p class="mb-0"><strong>Cost:</strong> Rs. <span id="testCost"></span></p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="test_date" class="form-label">Preferred Test Date</label>
                        <input type="date" class="form-control" id="test_date" name="test_date" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                        <div class="form-text">Please select your preferred date for the test.</div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="bookTestForm" class="btn btn-primary">
                    <i class="fas fa-check me-2"></i>Book Test
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const testSelect = document.getElementById('test_type');
    const testDetails = document.getElementById('testDetails');
    const testDescription = document.getElementById('testDescription');
    const testCost = document.getElementById('testCost');

    testSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            testDescription.textContent = selectedOption.dataset.description;
            testCost.textContent = parseFloat(selectedOption.dataset.cost).toFixed(2);
            testDetails.classList.remove('d-none');
        } else {
            testDetails.classList.add('d-none');
        }
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
