<?php
$page_title = "Staff Dashboard";
require_once '../includes/config.php';

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/header.php';
?>
<link rel="stylesheet" href="../css/dashboard.css">
<?php

$db = getDBConnection();

// Get staff member's details
$stmt = $db->prepare('
    SELECT 
        first_name, 
        last_name, 
        email, 
        phone, 
        user_type,
        specialization,
        last_login,
        created_at 
    FROM users 
    WHERE id = :user_id
');
$stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$staff = $result->fetchArray(SQLITE3_ASSOC);

// Add this for debugging - you can remove it later
echo "<!-- Debug info: " . print_r($staff, true) . " -->";

// Get today's appointments for staff's department
$stmt = $db->prepare('
    SELECT a.*, 
           p.first_name as patient_first_name, 
           p.last_name as patient_last_name,
           d.first_name as doctor_first_name,
           d.last_name as doctor_last_name
    FROM appointments a
    JOIN users p ON a.patient_id = p.id
    JOIN users d ON a.doctor_id = d.id
    WHERE DATE(a.appointment_date) = DATE("now")
    ORDER BY a.appointment_time ASC
');
$appointments = $stmt->execute();

// Get recent lab tests
$stmt = $db->prepare('
    SELECT lt.*, u.first_name, u.last_name
    FROM lab_tests lt
    JOIN users u ON lt.patient_id = u.id
    WHERE lt.status = "pending"
    ORDER BY lt.test_date ASC
    LIMIT 5
');
$lab_tests = $stmt->execute();
?>

<div class="container dashboard-container">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="welcome-card">
                <h4 class="mb-2">Welcome, <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>!</h4>
                <p class="mb-1">Department: <?php echo !empty($staff['specialization']) ? htmlspecialchars($staff['specialization']) : 'General'; ?></p>
                <p class="text-light mb-0">Last login: <?php echo date('M j, Y g:i A', strtotime($staff['created_at'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h5 class="card-title">Today's Appointments</h5>
                <?php
                $appointment_count = 0;
                while ($appointments->fetchArray()) {
                    $appointment_count++;
                }
                $appointments->reset();
                ?>
                <h2 class="mb-0"><?php echo $appointment_count; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-flask"></i>
                </div>
                <h5 class="card-title">Pending Lab Tests</h5>
                <?php
                $test_count = 0;
                while ($lab_tests->fetchArray()) {
                    $test_count++;
                }
                $lab_tests->reset();
                ?>
                <h2 class="mb-0"><?php echo $test_count; ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-hospital"></i>
                </div>
                <h5 class="card-title">Department</h5>
                <h2 class="mb-0"><?php echo !empty($staff['specialization']) ? htmlspecialchars($staff['specialization']) : 'General'; ?></h2>
            </div>
        </div>
    </div>

    <!-- Today's Appointments -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="data-table-card">
                <div class="card-header">
                    <h5 class="mb-0">Today's Appointments</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $appointments->fetchArray(SQLITE3_ASSOC)): ?>
                                <tr>
                                    <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($appointment['status']) {
                                                'pending' => 'warning',
                                                'confirmed' => 'success',
                                                'cancelled' => 'danger',
                                                'completed' => 'info',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="../appointments/view.php?id=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($appointment_count === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-calendar-check fa-2x mb-3"></i>
                                            <p class="mb-0">No appointments scheduled for today.</p>
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
    </div>

    <!-- Pending Lab Tests -->
    <div class="row">
        <div class="col-md-12">
            <div class="data-table-card">
                <div class="card-header">
                    <h5 class="mb-0">Pending Lab Tests</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Test Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($test = $lab_tests->fetchArray(SQLITE3_ASSOC)): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($test['test_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($test['first_name'] . ' ' . $test['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <?php echo ucfirst($test['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="../lab/update_test.php?id=<?php echo $test['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Update
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($test_count === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-flask fa-2x mb-3"></i>
                                            <p class="mb-0">No pending lab tests.</p>
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
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 