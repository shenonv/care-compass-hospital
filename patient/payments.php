<?php
require_once '../includes/config.php';
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$db = getDBConnection();

// Get pending payments
$stmt = $db->prepare('
    SELECT 
        p.id,
        p.amount,
        p.payment_type,
        p.status,
        p.created_at,
        CASE 
            WHEN p.payment_type = "appointment" THEN 
                (SELECT d.name FROM appointments a 
                JOIN doctors d ON a.doctor_id = d.id 
                WHERE a.id = p.reference_id)
            WHEN p.payment_type = "lab_test" THEN 
                (SELECT name FROM lab_tests t 
                JOIN test_bookings b ON t.id = b.test_id 
                WHERE b.id = p.reference_id)
        END as service_name
    FROM payments p
    WHERE p.patient_id = :patient_id
    ORDER BY p.created_at DESC
');
$stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$payments = $stmt->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Care Compass Hospitals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/payments.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Care Compass</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="appointments.php">Appointments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="lab-tests.php">Lab Tests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="payments.php">Payments</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-8">
                <h2 class="mb-4">My Payments</h2>
                
                <?php while ($payment = $payments->fetchArray(SQLITE3_ASSOC)): ?>
                <div class="card payment-card mb-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="card-title">
                                    <?php echo ucfirst($payment['payment_type']); ?> - 
                                    <?php echo htmlspecialchars($payment['service_name']); ?>
                                </h5>
                                <p class="card-text">
                                    Amount: $<?php echo number_format($payment['amount'], 2); ?>
                                </p>
                                <small class="text-muted">
                                    <?php echo date('Y-m-d H:i', strtotime($payment['created_at'])); ?>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <span class="badge bg-<?php echo $payment['status'] === 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($payment['status']); ?>
                                </span>
                            </div>
                            <div class="col-md-3 text-end">
                                <?php if ($payment['status'] === 'pending'): ?>
                                <button class="btn btn-primary" onclick="processPayment(<?php echo $payment['id']; ?>)">
                                    Pay Now
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Payment Information</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-credit-card text-primary me-2"></i>
                                We accept all major credit cards
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-lock text-primary me-2"></i>
                                Secure payment processing
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-receipt text-primary me-2"></i>
                                Digital receipts available
                            </li>
                        </ul>
                        <hr>
                        <h6>Need Help?</h6>
                        <p class="mb-0">
                            Contact our billing department:<br>
                            <i class="fas fa-phone me-1"></i> (123) 456-7890<br>
                            <i class="fas fa-envelope me-1"></i> billing@carecompass.com
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Process Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <input type="hidden" name="payment_id" id="payment_id">
                        
                        <div class="mb-3">
                            <label for="card_number" class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="card_number" name="card_number" 
                                   pattern="[0-9]{16}" required placeholder="1234567890123456">
                            <div class="form-text">Enter 16 digits without spaces</div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col">
                                <label for="expiry" class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="expiry" name="expiry" 
                                       pattern="(0[1-9]|1[0-2])\/[0-9]{2}" required placeholder="MM/YY">
                                <div class="form-text">Format: MM/YY</div>
                            </div>
                            <div class="col">
                                <label for="cvv" class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cvv" name="cvv" 
                                       pattern="[0-9]{3,4}" required placeholder="123">
                                <div class="form-text">3 or 4 digits</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="card_name" class="form-label">Name on Card</label>
                            <input type="text" class="form-control" id="card_name" name="card_name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" onclick="submitPayment()" class="btn btn-primary">Process Payment</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function processPayment(paymentId) {
            document.getElementById('payment_id').value = paymentId;
            var paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            paymentModal.show();
        }

        function submitPayment() {
            const form = document.getElementById('paymentForm');
            
            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);

            fetch('process_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Payment successful!');
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('An error occurred while processing the payment');
            });
        }

        // Format card number input
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 16) value = value.slice(0, 16);
            e.target.value = value;
        });

        // Format expiry date input
        document.getElementById('expiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            e.target.value = value;
        });

        // Format CVV input
        document.getElementById('cvv').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 4) value = value.slice(0, 4);
            e.target.value = value;
        });
    </script>
</body>
</html>
