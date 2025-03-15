<?php
$page_title = "Process Payment";
require_once '../includes/config.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: ../login.php');
    exit;
}

// Check if appointment ID is provided
if (!isset($_GET['appointment_id'])) {
    $_SESSION['error_message'] = "No appointment specified.";
    header('Location: appointments.php');
    exit;
}

$appointment_id = (int)$_GET['appointment_id'];
$db = getDBConnection();

// Get appointment details
$stmt = $db->prepare('
    SELECT 
        a.*,
        u.consultation_fee,
        p.id as payment_id,
        p.status as payment_status
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

// Check if payment is already completed
if (isset($appointment['payment_status']) && $appointment['payment_status'] === 'completed') {
    $_SESSION['error_message'] = "Payment has already been completed for this appointment.";
    header('Location: view_appointment.php?id=' . $appointment_id);
    exit;
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->exec('BEGIN TRANSACTION');

        // Create or update payment record
        if (isset($appointment['payment_id'])) {
            $stmt = $db->prepare('
                UPDATE payments 
                SET status = "completed",
                    payment_date = CURRENT_TIMESTAMP
                WHERE id = :payment_id
            ');
            $stmt->bindValue(':payment_id', $appointment['payment_id'], SQLITE3_INTEGER);
        } else {
            $stmt = $db->prepare('
                INSERT INTO payments (
                    patient_id,
                    amount,
                    payment_type,
                    reference_id,
                    status,
                    payment_date,
                    created_at
                ) VALUES (
                    :patient_id,
                    :amount,
                    "appointment",
                    :reference_id,
                    "completed",
                    CURRENT_TIMESTAMP,
                    CURRENT_TIMESTAMP
                )
            ');
            $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':amount', $appointment['consultation_fee'], SQLITE3_FLOAT);
            $stmt->bindValue(':reference_id', $appointment_id, SQLITE3_INTEGER);
        }
        
        $stmt->execute();

        // Update appointment status to confirmed
        $stmt = $db->prepare('
            UPDATE appointments 
            SET status = "confirmed" 
            WHERE id = :appointment_id
        ');
        $stmt->bindValue(':appointment_id', $appointment_id, SQLITE3_INTEGER);
        $stmt->execute();

        $db->exec('COMMIT');
        
        $_SESSION['success_message'] = "Payment processed successfully!";
        header('Location: view_appointment.php?id=' . $appointment_id);
        exit;
    } catch (Exception $e) {
        $db->exec('ROLLBACK');
        $_SESSION['error_message'] = "Payment processing failed. Please try again.";
        error_log("Payment processing error: " . $e->getMessage());
    }
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
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
                <div class="card-header">
                    <h3 class="card-title mb-0">Process Payment</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5>Payment Details</h5>
                        <p class="mb-0">Consultation Fee: ₹<?php echo number_format($appointment['consultation_fee'], 2); ?></p>
                    </div>

                    <form method="POST" class="mt-4">
                        <div class="mb-3">
                            <label class="form-label">Card Number</label>
                            <input type="text" class="form-control" required pattern="[0-9]{16}" maxlength="16" placeholder="Enter 16-digit card number">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" required pattern="(0[1-9]|1[0-2])\/[0-9]{2}" placeholder="MM/YY">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CVV</label>
                                <input type="text" class="form-control" required pattern="[0-9]{3}" maxlength="3" placeholder="Enter 3-digit CVV">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Card Holder Name</label>
                            <input type="text" class="form-control" required placeholder="Enter name as on card">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                Pay ₹<?php echo number_format($appointment['consultation_fee'], 2); ?>
                            </button>
                            <a href="view_appointment.php?id=<?php echo $appointment_id; ?>" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Format expiry date input
document.querySelector('input[placeholder="MM/YY"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.slice(0, 2) + '/' + value.slice(2);
    }
    e.target.value = value;
});

// Format card number with spaces
document.querySelector('input[placeholder="Enter 16-digit card number"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    e.target.value = value;
});
</script>

<?php require_once '../includes/footer.php'; ?>
