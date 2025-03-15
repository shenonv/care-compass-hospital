<?php
$page_title = "My Appointments";
require_once '../includes/config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/header.php';

$db = getDBConnection();

// Handle appointment cancellation
if (isset($_POST['cancel_appointment']) && isset($_POST['appointment_id'])) {
    $stmt = $db->prepare('UPDATE appointments SET status = "cancelled" WHERE id = :id AND patient_id = :patient_id');
    $stmt->bindValue(':id', $_POST['appointment_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: appointments.php?cancelled=1');
    exit;
}

// Get all appointments for the current patient
$stmt = $db->prepare('
    SELECT 
        a.*,
        d.specialty,
        d.consultation_fee,
        u.first_name as doctor_first_name,
        u.last_name as doctor_last_name,
        p.status as payment_status
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    JOIN doctors d ON d.user_id = u.id
    LEFT JOIN payments p ON p.reference_id = a.id AND p.payment_type = "appointment"
    WHERE a.patient_id = :patient_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
');

$stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$appointments = $stmt->execute();

// Get success message if any
$success = isset($_GET['booked']) ? "Appointment booked successfully!" : null;
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Appointments</h2>
        <a href="book_appointment.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Book New Appointment
        </a>
    </div>

    <?php if (isset($_GET['cancelled'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Appointment cancelled successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Specialty</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($appointment = $appointments->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['specialty']); ?></td>
                            <td>
                                <?php
                                $status_badges = [
                                    'pending' => 'warning',
                                    'confirmed' => 'success',
                                    'cancelled' => 'danger',
                                    'completed' => 'info'
                                ];
                                $badge_color = $status_badges[$appointment['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badge_color; ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $payment_status = $appointment['payment_status'] ?? 'pending';
                                $payment_badges = [
                                    'pending' => 'warning',
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                    'refunded' => 'info'
                                ];
                                $badge_color = $payment_badges[$payment_status] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badge_color; ?>">
                                    <?php echo ucfirst($payment_status); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($appointment['status'] === 'pending'): ?>
                                    <a href="cancel_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                       class="btn btn-outline-danger"
                                       onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$appointments->fetchArray()): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-calendar-times fa-2x mb-3"></i>
                                    <p class="mb-0">No appointments found.</p>
                                    <a href="book_appointment.php" class="btn btn-primary mt-3">
                                        Book Your First Appointment
                                    </a>
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

<?php require_once '../includes/footer.php'; ?>
