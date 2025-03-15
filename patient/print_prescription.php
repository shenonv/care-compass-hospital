<?php
require_once '../includes/config.php';
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$prescription_id = $_GET['id'] ?? '';

if (empty($prescription_id)) {
    die('Prescription ID is required');
}

$db = getDBConnection();

// Get prescription details
$stmt = $db->prepare('
    SELECT 
        p.*,
        d.name as doctor_name,
        d.specialty,
        u.name as patient_name
    FROM prescriptions p
    JOIN doctors d ON p.doctor_id = d.id
    JOIN users u ON p.patient_id = u.id
    WHERE p.id = :prescription_id 
    AND p.patient_id = :patient_id
');
$stmt->bindValue(':prescription_id', $prescription_id, SQLITE3_INTEGER);
$stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$prescription = $result->fetchArray(SQLITE3_ASSOC);

if (!$prescription) {
    die('Prescription not found');
}

// Set content type to PDF
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="prescription.pdf"');

// Create PDF using TCPDF (in a real application)
// For this demo, we'll create a simple HTML version
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription - Care Compass Hospitals</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }
        .prescription-details {
            margin-bottom: 30px;
        }
        .footer {
            margin-top: 50px;
            text-align: right;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Care Compass Hospitals</h1>
        <p>123 Hospital Street, Medical District</p>
        <p>Phone: (123) 456-7890</p>
    </div>

    <div class="prescription-details">
        <p><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($prescription['prescribed_date'])); ?></p>
        <p><strong>Patient Name:</strong> <?php echo htmlspecialchars($prescription['patient_name']); ?></p>
        <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?></p>
        <p><strong>Specialty:</strong> <?php echo htmlspecialchars($prescription['specialty']); ?></p>
    </div>

    <div class="medications">
        <h3>Prescribed Medications</h3>
        <?php echo nl2br(htmlspecialchars($prescription['medications'])); ?>
    </div>

    <div class="footer">
        <p>Signature: _______________________</p>
        <p>Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?></p>
        <p><?php echo htmlspecialchars($prescription['specialty']); ?></p>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()">Print Prescription</button>
    </div>
</body>
</html>
