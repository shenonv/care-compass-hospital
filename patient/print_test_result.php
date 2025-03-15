<?php
require_once '../includes/config.php';
session_start();

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'patient') {
    header('Location: login.php');
    exit;
}

$result_id = $_GET['id'] ?? '';

if (empty($result_id)) {
    die('Test Result ID is required');
}

$db = getDBConnection();

// Get test result details
$stmt = $db->prepare('
    SELECT 
        r.*,
        t.name as test_name,
        t.description as test_description,
        u.name as patient_name
    FROM test_results r
    JOIN lab_tests t ON r.test_id = t.id
    JOIN users u ON r.patient_id = u.id
    WHERE r.id = :result_id 
    AND r.patient_id = :patient_id
');
$stmt->bindValue(':result_id', $result_id, SQLITE3_INTEGER);
$stmt->bindValue(':patient_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$test_result = $result->fetchArray(SQLITE3_ASSOC);

if (!$test_result) {
    die('Test result not found');
}

// Set content type to PDF
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="test_result.pdf"');

// Create PDF using TCPDF (in a real application)
// For this demo, we'll create a simple HTML version
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Result - Care Compass Hospitals</title>
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
        .result-details {
            margin-bottom: 30px;
        }
        .result-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .result-table th,
        .result-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .result-table th {
            background-color: #f5f5f5;
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
        <h2>Laboratory Test Report</h2>
        <p>123 Hospital Street, Medical District</p>
        <p>Phone: (123) 456-7890</p>
    </div>

    <div class="result-details">
        <p><strong>Patient Name:</strong> <?php echo htmlspecialchars($test_result['patient_name']); ?></p>
        <p><strong>Test Name:</strong> <?php echo htmlspecialchars($test_result['test_name']); ?></p>
        <p><strong>Test Date:</strong> <?php echo date('Y-m-d', strtotime($test_result['test_date'])); ?></p>
        <p><strong>Test Description:</strong> <?php echo htmlspecialchars($test_result['test_description']); ?></p>
    </div>

    <table class="result-table">
        <tr>
            <th>Test Parameter</th>
            <th>Result</th>
            <th>Reference Range</th>
        </tr>
        <?php
        $results = explode("\n", $test_result['results']);
        foreach ($results as $result_line):
            $parts = explode(":", $result_line);
            if (count($parts) >= 2):
        ?>
        <tr>
            <td><?php echo htmlspecialchars(trim($parts[0])); ?></td>
            <td><?php echo htmlspecialchars(trim($parts[1])); ?></td>
            <td><?php echo htmlspecialchars($test_result['reference_range']); ?></td>
        </tr>
        <?php 
            endif;
        endforeach;
        ?>
    </table>

    <div class="footer">
        <p>Report Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
        <p>Authorized Signature: _______________________</p>
        <p>Laboratory Director</p>
        <p>Care Compass Hospitals</p>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()">Print Report</button>
    </div>
</body>
</html>
