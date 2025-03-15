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

// Get appointment and payment details
$stmt = $db->prepare('
    SELECT 
        a.*,
        d.consultation_fee,
        u.first_name as doctor_first_name,
        u.last_name as doctor_last_name,
        p.id as payment_id,
        p.status as payment_status
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    JOIN doctors d ON d.user_id = u.id
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

if ($appointment['payment_status'] === 'completed') {
    $_SESSION['error_message'] = "Payment has already been completed.";
    header('Location: view_appointment.php?id=' . $appointment_id);
    exit;
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Payment Details</h2>

                    <div class="alert alert-info">
                        <h5 class="alert-heading">Appointment Information</h5>
                        <p class="mb-0">
                            Doctor: Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?><br>
                            Date: <?php echo date('l, F j, Y', strtotime($appointment['appointment_date'])); ?><br>
                            Time: <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?><br>
                            Amount: ₹<?php echo number_format($appointment['consultation_fee'], 2); ?>
                        </p>
                    </div>

                    <form id="payment-form" method="POST" action="complete_payment.php">
                        <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                        
                        <div class="mb-3">
                            <label for="card_number" class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="card_number" name="card_number" 
                                   placeholder="1234 5678 9012 3456" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="expiry" class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="expiry" name="expiry" 
                                       placeholder="MM/YY" required>
                            </div>
                            <div class="col-md-6">
                                <label for="cvv" class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cvv" name="cvv" 
                                       placeholder="123" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="card_name" class="form-label">Name on Card</label>
                            <input type="text" class="form-control" id="card_name" name="card_name" 
                                   placeholder="John Doe" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-lock me-2"></i>Pay ₹<?php echo number_format($appointment['consultation_fee'], 2); ?>
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <a href="view_appointment.php?id=<?php echo $appointment_id; ?>" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-2"></i>Back to Appointment
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('payment-form');
    
    // Format card number input
    const cardNumber = document.getElementById('card_number');
    cardNumber.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.replace(/(.{4})/g, '$1 ').trim();
        e.target.value = value;
    });

    // Format expiry date input
    const expiry = document.getElementById('expiry');
    expiry.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
    });

    // Format CVV input
    const cvv = document.getElementById('cvv');
    cvv.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
    });

    // Form validation
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Here you would typically integrate with a payment gateway
        // For now, we'll just simulate a successful payment
        window.location.href = 'complete_payment.php?appointment_id=' + <?php echo $appointment_id; ?>;
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
