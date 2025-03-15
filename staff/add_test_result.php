<?php
require_once '../includes/config.php';
session_start();

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header('Location: ../patient/login.php');
    exit;
}

$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? '';
    $test_id = $_POST['test_id'] ?? '';
    $results = $_POST['results'] ?? '';
    $reference_range = $_POST['reference_range'] ?? '';
    
    if (!empty($patient_id) && !empty($test_id) && !empty($results) && !empty($reference_range)) {
        try {
            $stmt = $db->prepare('
                INSERT INTO test_results (patient_id, test_id, results, reference_range)
                VALUES (:patient_id, :test_id, :results, :reference_range)
            ');
            $stmt->bindValue(':patient_id', $patient_id, SQLITE3_INTEGER);
            $stmt->bindValue(':test_id', $test_id, SQLITE3_INTEGER);
            $stmt->bindValue(':results', $results, SQLITE3_TEXT);
            $stmt->bindValue(':reference_range', $reference_range, SQLITE3_TEXT);
            $stmt->execute();
            
            // Update test booking status if exists
            $stmt = $db->prepare('
                UPDATE test_bookings 
                SET status = "completed" 
                WHERE patient_id = :patient_id 
                AND test_id = :test_id 
                AND status = "pending"
            ');
            $stmt->bindValue(':patient_id', $patient_id, SQLITE3_INTEGER);
            $stmt->bindValue(':test_id', $test_id, SQLITE3_INTEGER);
            $stmt->execute();
            
            header('Location: add_test_result.php?success=1');
            exit;
        } catch (Exception $e) {
            $error = 'Failed to add test result. Please try again.';
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

// Get all patients with pending test bookings
$patients = $db->query('
    SELECT DISTINCT u.id, u.name 
    FROM users u 
    JOIN test_bookings b ON u.id = b.patient_id 
    WHERE b.status = "pending" 
    ORDER BY u.name
');

// Get all lab tests
$tests = $db->query('SELECT id, name FROM lab_tests ORDER BY name');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Test Result - Care Compass Hospitals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Staff Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="add_test_result.php">Add Test Result</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_test_bookings.php">View Test Bookings</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../patient/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <h2 class="mb-4">Add Test Result</h2>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Test result added successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="patient_id" class="form-label">Patient</label>
                        <select class="form-select" id="patient_id" name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php while ($patient = $patients->fetchArray(SQLITE3_ASSOC)): ?>
                            <option value="<?php echo $patient['id']; ?>">
                                <?php echo htmlspecialchars($patient['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="test_id" class="form-label">Lab Test</label>
                        <select class="form-select" id="test_id" name="test_id" required>
                            <option value="">Select Test</option>
                            <?php while ($test = $tests->fetchArray(SQLITE3_ASSOC)): ?>
                            <option value="<?php echo $test['id']; ?>">
                                <?php echo htmlspecialchars($test['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="results" class="form-label">Test Results</label>
                        <textarea class="form-control" id="results" name="results" rows="5" required
                                placeholder="Enter each parameter on a new line in format: Parameter: Value"></textarea>
                        <div class="form-text">
                            Example:
                            Hemoglobin: 14.5 g/dL
                            White Blood Cells: 7500 /µL
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reference_range" class="form-label">Reference Range</label>
                        <textarea class="form-control" id="reference_range" name="reference_range" rows="3" required
                                placeholder="Enter the normal range for the test parameters"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Add Test Result</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
