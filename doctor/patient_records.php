<?php
require_once '../includes/config.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'doctor') {
    header('Location: ../login.php');
    exit;
}

$db = getDBConnection();
$doctor_id = $_SESSION['user_id'];

// Get patients who have appointments with this doctor
$stmt = $db->prepare('
    SELECT DISTINCT 
        u.id,
        u.first_name,
        u.last_name,
        u.phone,
        u.email,
        (SELECT COUNT(*) FROM appointments 
         WHERE patient_id = u.id 
         AND doctor_id = :doctor_id) as visit_count,
        (SELECT appointment_date FROM appointments 
         WHERE patient_id = u.id 
         AND doctor_id = :doctor_id 
         ORDER BY appointment_date DESC LIMIT 1) as last_visit
    FROM users u
    INNER JOIN appointments a ON u.id = a.patient_id
    WHERE a.doctor_id = :doctor_id AND u.user_type = "patient"
    GROUP BY u.id
    ORDER BY last_visit DESC
');
$stmt->bindValue(':doctor_id', $doctor_id, SQLITE3_INTEGER);
$result = $stmt->execute();

$patients = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $patients[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Records - Doctor Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Care Compass Hospital</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-user-injured"></i> Patient Records</h2>
            </div>
            <div class="col-auto">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search patients...">
                    <button class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Total Visits</th>
                                <th>Last Visit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($patients)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No patient records found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['visit_count']); ?></td>
                                    <td><?php echo $patient['last_visit'] ? date('Y-m-d', strtotime($patient['last_visit'])) : 'N/A'; ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="view_medical_history.php?patient_id=<?php echo $patient['id']; ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-file-medical"></i> Medical History
                                            </a>
                                            <a href="add_medical_record.php?patient_id=<?php echo $patient['id']; ?>" 
                                               class="btn btn-outline-success">
                                                <i class="fas fa-plus"></i> Add Record
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Simple search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const table = document.querySelector('table tbody');
        const rows = table.getElementsByTagName('tr');

        for (let row of rows) {
            const cells = row.getElementsByTagName('td');
            let found = false;
            
            for (let cell of cells) {
                if (cell.textContent.toLowerCase().includes(searchText)) {
                    found = true;
                    break;
                }
            }
            
            row.style.display = found ? '' : 'none';
        }
    });
    </script>
</body>
</html>
