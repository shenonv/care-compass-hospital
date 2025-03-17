<?php
$page_title = "Appointment Details";
require_once '../includes/config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit;
}

// Check if appointment ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "No appointment specified.";
    header('Location: appointments.php');
    exit;
}

$appointment_id = (int)$_GET['id'];
$db = getDBConnection();

// Get appointment details with doctor information
$stmt = $db->prepare('
    SELECT 
        a.*,
        u.first_name as doctor_first_name,
        u.last_name as doctor_last_name,
        u.email as doctor_email,
        u.phone as doctor_phone,
        u.specialization as specialty,
        u.consultation_fee,
        p.status as payment_status,
        p.payment_date
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    LEFT JOIN payments p ON p.reference_id = a.id AND p.payment_type = "appointment"
    WHERE a.id = :appointment_id AND a.patient_id = :patient_id
');

$stmt->bindValue(':appointment_id', $appointment_id, SQLITE3_INTEGER);
$stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$appointment = $result->fetchArray(SQLITE3_ASSOC);

if (!$appointment) {
    $_SESSION['error_message'] = "Appointment not found or access denied.";
    header('Location: appointments.php');
    exit;
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Appointment Details</h3>
                    <a href="appointments.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Back to Appointments
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h4 class="mb-3">Doctor Information</h4>
                            <p><strong>Name:</strong> Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></p>
                            <p><strong>Specialty:</strong> <?php echo htmlspecialchars($appointment['specialty']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($appointment['doctor_email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($appointment['doctor_phone']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h4 class="mb-3">Appointment Information</h4>
                            <p><strong>Date:</strong> <?php echo date('l, F j, Y', strtotime($appointment['appointment_date'])); ?></p>
                            <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></p>
                            <p><strong>Consultation Fee:</strong> Rs. <?php echo number_format($appointment['consultation_fee'], 2); ?></p>
                            <?php if (!empty($appointment['notes'])): ?>
                            <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></p>
                            <?php endif; ?>
                            <p>
                                <strong>Status:</strong> 
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
                            </p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h4 class="mb-3">Payment Information</h4>
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
                            <p>
                                <strong>Payment Status:</strong>
                                <span class="badge bg-<?php echo $badge_color; ?>">
                                    <?php echo ucfirst($payment_status); ?>
                                </span>
                            </p>
                            <?php if ($payment_status === 'completed' && !empty($appointment['payment_date'])): ?>
                                <p><strong>Payment Date:</strong> <?php echo date('F j, Y g:i A', strtotime($appointment['payment_date'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <?php if ($appointment['status'] === 'pending'): ?>
                                    <?php if ($payment_status === 'pending'): ?>
                                        <a href="process_payment.php?appointment_id=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-success">
                                            <i class="fas fa-credit-card me-2"></i>Process Payment
                                        </a>
                                    <?php endif; ?>
                                    <form method="POST" action="appointments.php" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to cancel this appointment?');">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                        <button type="submit" name="cancel_appointment" class="btn btn-danger">
                                            <i class="fas fa-times me-2"></i>Cancel Appointment
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 