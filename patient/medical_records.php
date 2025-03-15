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
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col">
            <h2>My Medical Records</h2>
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
                    <div class="text-center py-4">
                        <i class="fas fa-file-medical fa-3x text-muted mb-3"></i>
                        <p class="mb-0">No consultation records found</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prescriptions -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Prescriptions</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="fas fa-prescription fa-3x text-muted mb-3"></i>
                        <p class="mb-0">No prescriptions found</p>
                    </div>
                </div>
            </div>

            <!-- Test Results -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Laboratory Test Results</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="fas fa-flask fa-3x text-muted mb-3"></i>
                        <p class="mb-0">No test results found</p>
                        <a href="lab_tests.php" class="btn btn-primary mt-3">
                            <i class="fas fa-flask me-2"></i>Book a Lab Test
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
