<?php
$page_title = "Book Lab Test";
require_once '../includes/config.php';
require_once '../includes/header.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: lab_tests.php');
    exit;
}

// Validate input
if (empty($_POST['test_type']) || empty($_POST['test_date'])) {
    $_SESSION['error_message'] = "Please fill in all required fields.";
    header('Location: lab_tests.php');
    exit;
}

// Get test details from the available tests array
$available_tests = [
    'Complete Blood Count (CBC)' => ['description' => 'Measures different components of blood', 'cost' => 1500.00],
    'Blood Glucose Test' => ['description' => 'Measures blood sugar levels', 'cost' => 800.00],
    'Lipid Profile' => ['description' => 'Measures cholesterol and triglycerides', 'cost' => 2000.00],
    'Liver Function Test' => ['description' => 'Assesses liver function and health', 'cost' => 2500.00],
    'Thyroid Function Test' => ['description' => 'Checks thyroid hormone levels', 'cost' => 1800.00],
    'Urine Analysis' => ['description' => 'Analyzes urine composition', 'cost' => 500.00]
];

$test_name = $_POST['test_type'];
if (!isset($available_tests[$test_name])) {
    $_SESSION['error_message'] = "Invalid test selected.";
    header('Location: lab_tests.php');
    exit;
}

$test_info = $available_tests[$test_name];
$test_date = $_POST['test_date'];
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// Validate test date
$test_date_obj = new DateTime($test_date);
$today = new DateTime();
if ($test_date_obj < $today) {
    $_SESSION['error_message'] = "Test date cannot be in the past.";
    header('Location: lab_tests.php');
    exit;
}

try {
    $db = getDBConnection();
    
    // Start transaction
    $db->exec('BEGIN TRANSACTION');

    // Insert lab test record
    $stmt = $db->prepare('
        INSERT INTO lab_tests (
            patient_id,
            test_name,
            test_description,
            test_date,
            status,
            notes,
            cost,
            payment_status,
            created_at
        ) VALUES (
            :patient_id,
            :test_name,
            :test_description,
            :test_date,
            "pending",
            :notes,
            :cost,
            "pending",
            CURRENT_TIMESTAMP
        )
    ');

    $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':test_name', $test_name, SQLITE3_TEXT);
    $stmt->bindValue(':test_description', $test_info['description'], SQLITE3_TEXT);
    $stmt->bindValue(':test_date', $test_date, SQLITE3_TEXT);
    $stmt->bindValue(':notes', $notes, SQLITE3_TEXT);
    $stmt->bindValue(':cost', $test_info['cost'], SQLITE3_FLOAT);
    
    $stmt->execute();

    // Commit transaction
    $db->exec('COMMIT');

    $_SESSION['success_message'] = "Lab test booked successfully! Please proceed with the payment.";
    header('Location: lab_tests.php');
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $db->exec('ROLLBACK');
    
    error_log('Error booking lab test: ' . $e->getMessage());
    $_SESSION['error_message'] = "Failed to book lab test. Please try again.";
    header('Location: lab_tests.php');
    exit;
}
?>

<div class="container py-4">
    <h2 class="mb-4">Book a Lab Test</h2>
    
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <li><?php echo htmlspecialchars($error); ?></li>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Select Test</label>
                    <select class="form-select" name="test_type" required>
                        <option value="">Choose a test...</option>
                        <?php 
                        foreach ($available_tests as $test_name => $test_info): ?>
                        <option value="<?php echo $test_name; ?>">
                            <?php echo htmlspecialchars($test_name); ?> 
                            ($<?php echo number_format($test_info['cost'], 2); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Preferred Date & Time</label>
                    <input type="datetime-local" class="form-control" name="test_date" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Book Test</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
