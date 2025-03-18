<?php
$page_title = "Medical Records";
require_once '../includes/config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/header.php';

$db = getDBConnection();
$patient_id = $_SESSION['user_id'];

// Get medical records
$stmt = $db->prepare('
    SELECT 
        mr.*,
        d.first_name as doctor_first_name,
        d.last_name as doctor_last_name,
        d.specialization
    FROM medical_records mr
    JOIN users d ON mr.doctor_id = d.id
    WHERE mr.patient_id = :patient_id
    ORDER BY mr.visit_date DESC
');
$stmt->bindValue(':patient_id', $patient_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$medical_records = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $medical_records[] = $row;
}

// Get lab test results
$stmt = $db->prepare('
    SELECT 
        lt.*
    FROM lab_tests lt
    WHERE lt.patient_id = :patient_id
    ORDER BY lt.test_date DESC
');
$stmt->bindValue(':patient_id', $patient_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$lab_tests = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $lab_tests[] = $row;
}
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h2>My Medical Records</h2>
        </div>
        <div class="col-auto">
            <a href="lab_tests.php" class="btn btn-primary">
                <i class="fas fa-flask me-2"></i>Book Lab Test
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Medical Records -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Consultation Records</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($medical_records)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-medical fa-3x text-muted mb-3"></i>
                        <p class="mb-0">No consultation records found</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Diagnosis</th>
                                    <th>Treatment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medical_records as $record): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($record['visit_date'])); ?></td>
                                    <td>Dr. <?php echo htmlspecialchars($record['doctor_first_name'] . ' ' . $record['doctor_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['diagnosis']); ?></td>
                                    <td><?php echo htmlspecialchars($record['prescription']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Test Results -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Laboratory Test Results</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($lab_tests)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-flask fa-3x text-muted mb-3"></i>
                        <p class="mb-0">No test results found</p>
                        <a href="lab_tests.php" class="btn btn-primary mt-3">
                            <i class="fas fa-flask me-2"></i>Book a Lab Test
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Test</th>
                                    <th>Doctor</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lab_tests as $test): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($test['test_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                    <td>Lab Staff</td>
                                    <td>
                                        <?php if ($test['status'] === 'completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif ($test['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo ucfirst($test['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($test['status'] === 'completed' && !empty($test['results'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#resultModal<?php echo $test['id']; ?>">
                                                <i class="fas fa-eye"></i> View Result
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($lab_tests)): ?>
    <?php foreach ($lab_tests as $test): ?>
        <?php if ($test['status'] === 'completed' && !empty($test['results'])): ?>
        <!-- Result Modal -->
        <div class="modal fade" id="resultModal<?php echo $test['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Test Results - <?php echo htmlspecialchars($test['test_name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Test Date:</strong> <?php echo date('F j, Y', strtotime($test['test_date'])); ?></p>
                        <p><strong>Staff:</strong> Lab Staff</p>
                        <div class="mt-3">
                            <strong>Results:</strong><br>
                            <?php echo nl2br(htmlspecialchars($test['results'])); ?>
                        </div>
                        <?php if (!empty($test['notes'])): ?>
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
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
