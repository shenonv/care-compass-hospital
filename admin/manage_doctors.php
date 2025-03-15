<?php
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../logout.php');
    exit;
}

$db = getDBConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $name = $_POST['name'] ?? '';
        $specialty = $_POST['specialty'] ?? '';
        $qualifications = $_POST['qualifications'] ?? '';
        $availability = $_POST['availability'] ?? '';
        $doctor_id = $_POST['doctor_id'] ?? '';
        
        if ($action === 'add') {
            $stmt = $db->prepare('
                INSERT INTO doctors (name, specialty, qualifications, availability)
                VALUES (:name, :specialty, :qualifications, :availability)
            ');
        } else {
            $stmt = $db->prepare('
                UPDATE doctors 
                SET name = :name, 
                    specialty = :specialty, 
                    qualifications = :qualifications, 
                    availability = :availability
                WHERE id = :doctor_id
            ');
            $stmt->bindValue(':doctor_id', $doctor_id, SQLITE3_INTEGER);
        }
        
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':specialty', $specialty, SQLITE3_TEXT);
        $stmt->bindValue(':qualifications', $qualifications, SQLITE3_TEXT);
        $stmt->bindValue(':availability', $availability, SQLITE3_TEXT);
        
        $stmt->execute();
        header('Location: manage_doctors.php?success=1');
        exit;
    }
    
    if ($action === 'delete') {
        $doctor_id = $_POST['doctor_id'] ?? '';
        if (!empty($doctor_id)) {
            $stmt = $db->prepare('DELETE FROM doctors WHERE id = :doctor_id');
            $stmt->bindValue(':doctor_id', $doctor_id, SQLITE3_INTEGER);
            $stmt->execute();
        }
        header('Location: manage_doctors.php?success=1');
        exit;
    }
}

// Replace the existing query with this:
$sql = "SELECT * FROM users WHERE user_type = 'staff' ORDER BY created_at DESC";
$result = $db->query($sql);

if ($result === false) {
    die("Database error: " . $db->lastErrorMsg());
}

$doctors = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $doctors[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors - Care Compass Hospitals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/manage_doctors.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_doctors.php">Doctors</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_staff.php">Staff</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_services.php">Services</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Doctors</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                <i class="fas fa-plus"></i> Add New Doctor
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Operation completed successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Specialty</th>
                                <th>Qualifications</th>
                                <th>Availability</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($doctors)): ?>
                                <?php foreach ($doctors as $doctor): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['specialty']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['qualifications']); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['availability']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="editDoctor(<?php echo htmlspecialchars(json_encode($doctor)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteDoctor(<?php echo $doctor['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No hospital staff found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Doctor Modal -->
    <div class="modal fade" id="addDoctorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Doctor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addDoctorForm" action="" method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="specialty" class="form-label">Specialty</label>
                            <input type="text" class="form-control" id="specialty" name="specialty" required>
                        </div>
                        <div class="mb-3">
                            <label for="qualifications" class="form-label">Qualifications</label>
                            <textarea class="form-control" id="qualifications" name="qualifications" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="availability" class="form-label">Availability</label>
                            <input type="text" class="form-control" id="availability" name="availability" 
                                   placeholder="e.g., Mon-Fri 9AM-5PM" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="addDoctorForm" class="btn btn-primary">Add Doctor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Doctor Modal -->
    <div class="modal fade" id="editDoctorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Doctor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editDoctorForm" action="" method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="doctor_id" id="edit_doctor_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_specialty" class="form-label">Specialty</label>
                            <input type="text" class="form-control" id="edit_specialty" name="specialty" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_qualifications" class="form-label">Qualifications</label>
                            <textarea class="form-control" id="edit_qualifications" name="qualifications" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_availability" class="form-label">Availability</label>
                            <input type="text" class="form-control" id="edit_availability" name="availability" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" form="editDoctorForm" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Doctor Form -->
    <form id="deleteDoctorForm" action="" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="doctor_id" id="delete_doctor_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editDoctor(doctor) {
            document.getElementById('edit_doctor_id').value = doctor.id;
            document.getElementById('edit_name').value = doctor.name;
            document.getElementById('edit_specialty').value = doctor.specialty;
            document.getElementById('edit_qualifications').value = doctor.qualifications;
            document.getElementById('edit_availability').value = doctor.availability;
            
            new bootstrap.Modal(document.getElementById('editDoctorModal')).show();
        }
        
        function deleteDoctor(doctorId) {
            if (confirm('Are you sure you want to delete this doctor?')) {
                document.getElementById('delete_doctor_id').value = doctorId;
                document.getElementById('deleteDoctorForm').submit();
            }
        }
    </script>
</body>
</html>
