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
        // First, insert/update into users table
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
        
        if ($action === 'add') {
            // Insert into users table first
            $stmt = $db->prepare('
                INSERT INTO users (first_name, last_name, email, username, password, user_type)
                VALUES (:first_name, :last_name, :email, :username, :password, "doctor")
            ');
            
            $stmt->bindValue(':first_name', $first_name, SQLITE3_TEXT);
            $stmt->bindValue(':last_name', $last_name, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':password', $password, SQLITE3_TEXT);
            
            $stmt->execute();
            $user_id = $db->lastInsertRowID();
            
            // Then insert into doctors table
            $stmt = $db->prepare('
                INSERT INTO doctors (user_id, specialty, qualifications, availability)
                VALUES (:user_id, :specialty, :qualifications, :availability)
            ');
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        } else {
            // Update users table
            $user_id = $_POST['doctor_id'] ?? '';
            $stmt = $db->prepare('
                UPDATE users 
                SET first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    username = :username
                WHERE id = :user_id
            ');
            
            $stmt->bindValue(':first_name', $first_name, SQLITE3_TEXT);
            $stmt->bindValue(':last_name', $last_name, SQLITE3_TEXT);
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $stmt->execute();
            
            // Update doctors table
            $stmt = $db->prepare('
                UPDATE doctors 
                SET specialty = :specialty,
                    qualifications = :qualifications,
                    availability = :availability
                WHERE user_id = :user_id
            ');
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        }
        
        $stmt->bindValue(':specialty', $_POST['specialty'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':qualifications', $_POST['qualifications'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':availability', $_POST['availability'] ?? '', SQLITE3_TEXT);
        
        $stmt->execute();
        header('Location: manage_doctors.php?success=1');
        exit;
    }
    
    if ($action === 'delete') {
        $user_id = $_POST['doctor_id'] ?? '';
        if (!empty($user_id)) {
            // Delete from doctors table first (foreign key constraint)
            $stmt = $db->prepare('DELETE FROM doctors WHERE user_id = :user_id');
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $stmt->execute();
            
            // Then delete from users table
            $stmt = $db->prepare('DELETE FROM users WHERE id = :user_id');
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $stmt->execute();
        }
        header('Location: manage_doctors.php?success=1');
        exit;
    }
}

// Update the query to use specialization from users table
$stmt = $db->prepare("
    SELECT users.* 
    FROM users 
    WHERE users.user_type = 'doctor'
    ORDER BY users.first_name, users.last_name
");

$result = $stmt->execute();

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
                                        <td><?php echo htmlspecialchars($doctor['specialization'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['qualifications'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($doctor['availability'] ?? 'N/A'); ?></td>
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
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="specialty" class="form-label">Specialization</label>
                            <select class="form-control" id="specialty" name="specialization" required>
                                <option value="">Select Specialization</option>
                                <option value="Cardiology">Cardiology</option>
                                <option value="Neurology">Neurology</option>
                                <option value="Pediatrics">Pediatrics</option>
                                <option value="Orthopedics">Orthopedics</option>
                                <option value="Dermatology">Dermatology</option>
                                <option value="Ophthalmology">Ophthalmology</option>
                                <option value="General Medicine">General Medicine</option>
                                <option value="Dental Care">Dental Care</option>
                            </select>
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
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_specialty" class="form-label">Specialization</label>
                            <select class="form-control" id="edit_specialty" name="specialization" required>
                                <option value="">Select Specialization</option>
                                <option value="Cardiology">Cardiology</option>
                                <option value="Neurology">Neurology</option>
                                <option value="Pediatrics">Pediatrics</option>
                                <option value="Orthopedics">Orthopedics</option>
                                <option value="Dermatology">Dermatology</option>
                                <option value="Ophthalmology">Ophthalmology</option>
                                <option value="General Medicine">General Medicine</option>
                                <option value="Dental Care">Dental Care</option>
                            </select>
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
            document.getElementById('edit_username').value = doctor.username;
            document.getElementById('edit_first_name').value = doctor.first_name;
            document.getElementById('edit_last_name').value = doctor.last_name;
            document.getElementById('edit_email').value = doctor.email;
            document.getElementById('edit_specialty').value = doctor.specialization;
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
