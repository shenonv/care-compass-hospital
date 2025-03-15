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
    header('Location: my_appointments.php?cancelled=1');
    exit;
}

// Get all appointments for the current patient
$stmt = $db->prepare('
    SELECT 
        a.*,
        d.name as doctor_name,
        d.specialty,
        d.consultation_fee,
        COALESCE(p.status, "pending") as payment_status
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    LEFT JOIN payments p ON p.reference_id = a.id AND p.payment_type = "appointment"
    WHERE a.patient_id = :patient_id
    ORDER BY a.appointment_date DESC
');
$stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$appointments = $stmt->execute();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Appointments</h2>
        <a href="book_appointment.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> Book New Appointment
        </a>
    </div>

    <?php if (isset($_GET['cancelled'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Appointment cancelled successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Specialty</th>
                            <th>Date & Time</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $has_appointments = false;
                        while ($appointment = $appointments->fetchArray(SQLITE3_ASSOC)):
                            $has_appointments = true;
                            $appointment_date = new DateTime($appointment['appointment_date']);
                            $is_future = $appointment_date > new DateTime();
                            $can_cancel = $is_future && $appointment['status'] !== 'cancelled';
                        ?>
                        <tr>
                            <td>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['specialty']); ?></td>
                            <td>
                                <div><?php echo $appointment_date->format('F j, Y'); ?></div>
                                <div class="text-muted"><?php echo $appointment_date->format('g:i A'); ?></div>
                            </td>
                            <td>
                                <?php if ($appointment['consultation_fee'] > 0): ?>
                                    ₹<?php echo number_format($appointment['consultation_fee'], 2); ?>
                                <?php else: ?>
                                    <span class="text-muted">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $appointment['status'] === 'confirmed' ? 'success' : 
                                        ($appointment['status'] === 'cancelled' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($appointment['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($appointment['consultation_fee'] > 0): ?>
                                    <?php if ($appointment['payment_status'] === 'completed'): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php elseif ($appointment['status'] !== 'cancelled'): ?>
                                        <a href="make_payment.php?type=appointment&id=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-sm btn-warning">
                                            Pay Now
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Cancelled</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($can_cancel): ?>
                                <form method="POST" class="d-inline" 
                                      onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                    <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                    <button type="submit" name="cancel_appointment" class="btn btn-sm btn-danger">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if (!$has_appointments): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted mb-3">
                                    <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                    <p>You don't have any appointments yet.</p>
                                </div>
                                <a href="book_appointment.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Book Your First Appointment
                                </a>
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
