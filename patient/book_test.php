<?php
$page_title = "Book Lab Test";
require_once '../includes/config.php';
require_once '../includes/header.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php');
    exit;
}

// Handle test booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_id = $_POST['test_id'] ?? '';
    $booking_date = $_POST['booking_date'] ?? '';
    $patient_id = $_SESSION['user_id'];

    $error = '';
    
    // Validate input
    if (empty($test_id) || empty($booking_date)) {
        $error = 'Please provide all required information.';
    } elseif (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
        $error = 'Please select a future date.';
    }

    if (empty($error)) {
        try {
            $db = getDBConnection();

            // Check if test exists 
            $stmt = $db->prepare('SELECT id, price FROM lab_tests WHERE id = :id');
            $stmt->bindValue(':id', $test_id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $test = $result->fetchArray(SQLITE3_ASSOC);

            if (!$test) {
                $error = 'Selected test is not available.';
            } else {
                // Create the booking
                $stmt = $db->prepare('
                    INSERT INTO test_bookings (patient_id, test_id, booking_date, status)
                    VALUES (:patient_id, :test_id, :booking_date, "pending")
                ');
                $stmt->bindValue(':patient_id', $patient_id, SQLITE3_INTEGER);
                $stmt->bindValue(':test_id', $test_id, SQLITE3_INTEGER);
                $stmt->bindValue(':booking_date', $booking_date, SQLITE3_TEXT);
                $stmt->execute();

                // Redirect back to lab tests page with success message
                header('Location: lab_tests.php?message=booking_success');
                exit;
            }
        } catch (Exception $e) {
            $error = 'An error occurred while booking the test. Please try again.';
        }
    }

    // If there was an error, redirect back with error message
    if ($error) {
        header('Location: lab_tests.php?error=' . urlencode($error));
        exit;
    }
} else {
    // If accessed directly without POST data, redirect to lab tests page
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
                    <select class="form-select" name="test_id" required>
                        <option value="">Choose a test...</option>
                        <?php 
                        $db = getDBConnection();
                        $tests = $db->query('SELECT * FROM lab_tests ORDER BY name');
                        while ($test = $tests->fetchArray(SQLITE3_ASSOC)): ?>
                        <option value="<?php echo $test['id']; ?>">
                            <?php echo htmlspecialchars($test['name']); ?> 
                            ($<?php echo number_format($test['price'], 2); ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Preferred Date & Time</label>
                    <input type="datetime-local" class="form-control" name="booking_date" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Book Test</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
