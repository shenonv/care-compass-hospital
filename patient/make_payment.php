<?php
$page_title = "Make Payment";
require_once '../includes/config.php';
require_once '../includes/header.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();
$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';
$payment = null;
$service_name = '';
$amount = 0;

// Validate payment type and get payment details
if ($type === 'appointment') {
    $stmt = $db->prepare('
        SELECT 
            p.*,
            d.name as doctor_name,
            d.specialty,
            d.consultation_fee as amount,
            a.appointment_date
        FROM payments p
        JOIN appointments a ON p.reference_id = a.id
        JOIN doctors d ON a.doctor_id = d.id
        WHERE p.id = :id AND p.patient_id = :patient_id AND p.payment_type = "appointment"
    ');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $payment = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($payment) {
        $service_name = "Appointment with Dr. " . $payment['doctor_name'] . " (" . $payment['specialty'] . ")";
        $service_date = (new DateTime($payment['appointment_date']))->format('F j, Y g:i A');
        $amount = $payment['amount'];
    }
} elseif ($type === 'test') {
    $stmt = $db->prepare('
        SELECT 
            p.*,
            t.name as test_name,
            t.price as amount,
            b.booking_date
        FROM payments p
        JOIN test_bookings b ON p.reference_id = b.id
        JOIN lab_tests t ON b.test_id = t.id
        WHERE p.id = :id AND p.patient_id = :patient_id AND p.payment_type = "lab_test"
    ');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $payment = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($payment) {
        $service_name = $payment['test_name'] . " Lab Test";
        $service_date = (new DateTime($payment['booking_date']))->format('F j, Y g:i A');
        $amount = $payment['amount'];
    }
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $payment) {
    $card_number = $_POST['card_number'] ?? '';
    $expiry = $_POST['expiry'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    $errors = [];
    
    // Basic validation
    if (empty($card_number) || strlen($card_number) < 16) {
        $errors[] = "Invalid card number";
    }
    if (empty($expiry) || !preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
        $errors[] = "Invalid expiry date (MM/YY)";
    }
    if (empty($cvv) || strlen($cvv) !== 3) {
        $errors[] = "Invalid CVV";
    }
    
    if (empty($errors)) {
        // Update payment status
        $stmt = $db->prepare('UPDATE payments SET status = "completed" WHERE id = :id');
        $stmt->bindValue(':id', $payment['id'], SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            // Update service status
            if ($type === 'appointment') {
                $stmt = $db->prepare('UPDATE appointments SET status = "confirmed" WHERE id = :id');
            } else {
                $stmt = $db->prepare('UPDATE test_bookings SET status = "confirmed" WHERE id = :id');
            }
            $stmt->bindValue(':id', $payment['reference_id'], SQLITE3_INTEGER);
            $stmt->execute();
            
            // Redirect based on payment type
            $redirect = $type === 'appointment' ? 'my_appointments.php' : 'my_tests.php';
            header("Location: $redirect?payment=success");
            exit;
        }
    }
}

// If no valid payment found, redirect to dashboard
if (!$payment) {
    header('Location: dashboard.php');
    exit;
}
?>

<div class="container py-4">
    <h2 class="mb-4">Make Payment</h2>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Payment Details</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Service</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($service_name); ?></dd>
                        
                        <dt class="col-sm-4">Date & Time</dt>
                        <dd class="col-sm-8"><?php echo $service_date; ?></dd>
                        
                        <dt class="col-sm-4">Amount</dt>
                        <dd class="col-sm-8">$<?php echo number_format($amount, 2); ?></dd>
                    </dl>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payment Method</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Card Number</label>
                            <input type="text" class="form-control" name="card_number" 
                                   placeholder="1234 5678 9012 3456" required
                                   pattern="\d{16}" maxlength="16">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" name="expiry" 
                                       placeholder="MM/YY" required
                                       pattern="\d{2}/\d{2}" maxlength="5">
                            </div>
                            <div class="col-6">
                                <label class="form-label">CVV</label>
                                <input type="text" class="form-control" name="cvv" 
                                       placeholder="123" required
                                       pattern="\d{3}" maxlength="3">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            Pay $<?php echo number_format($amount, 2); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payment Instructions</h5>
                </div>
                <div class="card-body">
                    <p>Please note:</p>
                    <ul class="mb-0">
                        <li>All payments are processed securely</li>
                        <li>Your card details are not stored on our servers</li>
                        <li>You will receive a confirmation email after successful payment</li>
                        <li>For any issues, please contact our support team</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add input formatting
document.querySelector('input[name="expiry"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.slice(0, 2) + '/' + value.slice(2);
    }
    e.target.value = value;
});

// Basic form validation
document.querySelector('form').addEventListener('submit', function(e) {
    if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
    }
    this.classList.add('was-validated');
});
</script>

<?php require_once '../includes/footer.php'; ?>
