<?php
require_once '../includes/config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit;
}

$db = getDBConnection();
$doctor_id = $_SESSION['user_id'];

// Get doctor's appointments
$stmt = $db->prepare('
    SELECT 
        a.*,
        p.first_name as patient_first_name,
        p.last_name as patient_last_name,
        p.phone as patient_phone
    FROM appointments a
    JOIN users p ON a.patient_id = p.id
    WHERE a.doctor_id = :doctor_id
    ORDER BY a.appointment_date DESC
');
$stmt->bindValue(':doctor_id', $doctor_id, SQLITE3_INTEGER);
$result = $stmt->execute();

$appointments = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $appointments[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointments - Doctor Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Care Compass Hospital</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-calendar-check"></i> My Appointments</h2>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Patient Name</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($appointments)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No appointments found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($appointment['appointment_date']))); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['patient_phone']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($appointment['status']) {
                                                'pending' => 'warning',
                                                'confirmed' => 'success',
                                                'cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($appointment['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-success"
                                                    onclick="updateStatus(<?php echo $appointment['id']; ?>, 'confirmed')">
                                                Confirm
                                            </button>
                                            <button type="button" class="btn btn-outline-danger"
                                                    onclick="updateStatus(<?php echo $appointment['id']; ?>, 'cancelled')">
                                                Cancel
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateStatus(appointmentId, status) {
        if (confirm('Are you sure you want to ' + status + ' this appointment?')) {
            // Add AJAX call to update appointment status
            // For now, just reload the page
            location.reload();
        }
    }
    </script>
</body>
</html>
