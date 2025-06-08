<?php
require_once '../database/db.php';
require_once '../auth_check.php';

// Check if user is logged in as admin using the proper auth system
check_auth('admin');

// Get admin info using the auth system
$admin_info = get_user_info();
$admin_name = $admin_info['name'] ?? 'Admin';

// Helper functions for admin operations
function get_all_users_for_admin()
{
    global $conn;

    // Get all students
    $students_sql = "SELECT studentID as id, name, email, 'student' as role, 'active' as status, department FROM student ORDER BY name";
    $students_result = $conn->query($students_sql);
    $students = $students_result->fetch_all(MYSQLI_ASSOC);

    // Get all officers
    $officers_sql = "SELECT officerID as id, name, email, 'officer' as role, 
                    CASE WHEN isRepresentative = 1 THEN 'Representative' ELSE 'active' END as status, 
                    department FROM officer ORDER BY name";
    $officers_result = $conn->query($officers_sql);
    $officers = $officers_result->fetch_all(MYSQLI_ASSOC);

    // Combine both arrays
    return array_merge($students, $officers);
}

function add_student_as_admin($studentID, $name, $email, $hashed_password, $department)
{
    global $conn;
    $sql = "INSERT INTO student (studentID, name, email, password, department) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $studentID, $name, $email, $hashed_password, $department);
    return $stmt->execute();
}

function add_officer_as_admin($officerID, $name, $email, $hashed_password, $department, $isRepresentative)
{
    global $conn;
    $sql = "INSERT INTO officer (officerID, name, email, password, department, isRepresentative) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $officerID, $name, $email, $hashed_password, $department, $isRepresentative);
    return $stmt->execute();
}

function update_user_as_admin($id, $name, $email, $new_role, $department, $status_or_representative, $old_role)
{
    global $conn;

    // If role hasn't changed, just update the existing record
    if ($new_role == $old_role) {
        if ($new_role == 'student') {
            $sql = "UPDATE student SET name = ?, email = ?, department = ? WHERE studentID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $email, $department, $id);
        } else if ($new_role == 'officer') {
            $isRepresentative = ($status_or_representative == 'Representative') ? 1 : 0;
            $sql = "UPDATE officer SET name = ?, email = ?, department = ?, isRepresentative = ? WHERE officerID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssis", $name, $email, $department, $isRepresentative, $id);
        }
        return $stmt->execute();
    }

    // Role has changed - need to move the record between tables
    $conn->begin_transaction();

    try {
        // Get the current password from the old table
        if ($old_role == 'student') {
            $password_sql = "SELECT password FROM student WHERE studentID = ?";
        } else {
            $password_sql = "SELECT password FROM officer WHERE officerID = ?";
        }

        $password_stmt = $conn->prepare($password_sql);
        $password_stmt->bind_param("s", $id);
        $password_stmt->execute();
        $password_result = $password_stmt->get_result();
        $password_data = $password_result->fetch_assoc();
        $password = $password_data['password'];

        // Delete from old table
        if ($old_role == 'student') {
            $delete_sql = "DELETE FROM student WHERE studentID = ?";
        } else {
            $delete_sql = "DELETE FROM officer WHERE officerID = ?";
        }

        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("s", $id);
        $delete_stmt->execute();

        // Insert into new table
        if ($new_role == 'student') {
            $insert_sql = "INSERT INTO student (studentID, name, email, password, department) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $id, $name, $email, $password, $department);
        } else if ($new_role == 'officer') {
            $isRepresentative = ($status_or_representative == 'Representative') ? 1 : 0;
            $insert_sql = "INSERT INTO officer (officerID, name, email, password, department, isRepresentative) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssssi", $id, $name, $email, $password, $department, $isRepresentative);
        }

        $insert_stmt->execute();

        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function delete_user_as_admin($id, $role)
{
    global $conn;

    if ($role == 'student') {
        $sql = "DELETE FROM student WHERE studentID = ?";
    } else if ($role == 'officer') {
        $sql = "DELETE FROM officer WHERE officerID = ?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id); // Changed from "i" to "s" since IDs are now strings
    return $stmt->execute();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['edit_user'])) {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $new_role = $_POST['role'];
        $old_role = $_POST['old_role']; // Get the original role
        $status = $_POST['status'];
        $department = trim($_POST['department']);

        try {
            // Update user (handles role changes automatically)
            update_user_as_admin($id, $name, $email, $new_role, $department, $status, $old_role);
            $_SESSION['success_message'] = "User updated successfully!";
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error updating user: " . $e->getMessage();
        }

        header("Location: admin_dashboard.php");
        exit();
    }

    if (isset($_POST['delete_user'])) {
        $id = $_POST['id'];
        $role = $_POST['old_role']; // Use old_role for deletion

        delete_user_as_admin($id, $role);
        $_SESSION['success_message'] = "User deleted successfully!";
        header("Location: admin_dashboard.php");
        exit();
    }

    if (isset($_POST['add_user'])) {
        $userID = trim($_POST['user_id']); // New field for custom ID
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        $department = trim($_POST['department']);
        $status = $_POST['status'];

        // Validate that user ID is provided
        if (empty($userID)) {
            $_SESSION['error_message'] = "User ID is required!";
            header("Location: admin_dashboard.php");
            exit();
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            if ($role == 'student') {
                // Check if student ID already exists
                $check_sql = "SELECT studentID FROM student WHERE studentID = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("s", $userID);
                $check_stmt->execute();
                $result = $check_stmt->get_result();

                if ($result->num_rows > 0) {
                    $_SESSION['error_message'] = "Student ID already exists!";
                    header("Location: admin_dashboard.php");
                    exit();
                }

                add_student_as_admin($userID, $name, $email, $hashed_password, $department);
            } else if ($role == 'officer') {
                // Check if officer ID already exists
                $check_sql = "SELECT officerID FROM officer WHERE officerID = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("s", $userID);
                $check_stmt->execute();
                $result = $check_stmt->get_result();

                if ($result->num_rows > 0) {
                    $_SESSION['error_message'] = "Officer ID already exists!";
                    header("Location: admin_dashboard.php");
                    exit();
                }

                $isRepresentative = ($status == 'Representative') ? 1 : 0;
                add_officer_as_admin($userID, $name, $email, $hashed_password, $department, $isRepresentative);
            }

            $_SESSION['success_message'] = "User added successfully!";
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error adding user: " . $e->getMessage();
        }

        header("Location: admin_dashboard.php");
        exit();
    }
}

// Get all users (students and officers)
$all_users = get_all_users_for_admin();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CampusConnect</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($admin_name); ?>!</h1>
        <p>You are logged in as admin.</p>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message"
                style="background-color: #ffe6e6; border: 1px solid #ff9999; padding: 10px; margin-bottom: 15px; border-radius: 5px; color: #cc0000;">
                <?php
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>

        <form action="../logout.php" method="POST" class="logout-form">
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>

    <div class="dashboard-container">
        <h2>Add New User</h2>
        <div class="add-user-form">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="user_id">User ID:</label>
                        <input type="text" id="user_id" name="user_id" placeholder="Enter Student ID or Officer ID"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role:</label>
                        <select id="role" name="role" required onchange="toggleStatusOptionsForAdd()">
                            <option value="">Select Role</option>
                            <option value="student">Student</option>
                            <option value="officer">Officer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="department">Department:</label>
                        <select id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Electronics and Communication">Electronics and Communication</option>
                            <option value="Mechanical Engineering">Mechanical Engineering</option>
                            <option value="Civil Engineering">Civil Engineering</option>
                            <option value="Electrical Engineering">Electrical Engineering</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="Representative" id="rep-option" class="rep-option" style="display: none;">
                                Representative</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_user" class="btn-primary">Add User</button>
            </form>
        </div>
    </div>

    <div class="dashboard-container">
        <h2>User Management</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                        <tr>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to save these changes?');">
                                <td data-label="ID"><?php echo htmlspecialchars($user['id']); ?></td>

                                <td data-label="Name">
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>"
                                        required class="table-input">
                                </td>

                                <td data-label="Email">
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                                        required class="table-input">
                                </td>

                                <td data-label="Role">
                                    <select name="role" required onchange="toggleStatusOptionsInRow(this)"
                                        class="table-select">
                                        <option value="student" <?php echo ($user['role'] == 'student') ? 'selected' : ''; ?>>
                                            Student</option>
                                        <option value="officer" <?php echo ($user['role'] == 'officer') ? 'selected' : ''; ?>>
                                            Officer</option>
                                    </select>
                                </td>

                                <td data-label="Status">
                                    <select name="status" required class="table-select">
                                        <option value="active" <?php echo ($user['status'] == 'active') ? 'selected' : ''; ?>>
                                            Active</option>
                                        <option value="inactive" <?php echo ($user['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                        <?php if ($user['role'] == 'officer'): ?>
                                            <option value="Representative" <?php echo ($user['status'] == 'Representative') ? 'selected' : ''; ?>>Representative</option>
                                        <?php endif; ?>
                                    </select>
                                </td>

                                <td data-label="Department">
                                    <input type="text" name="department"
                                        value="<?php echo htmlspecialchars($user['department']); ?>" class="table-input">
                                </td>

                                <td data-label="Actions">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="old_role" value="<?php echo $user['role']; ?>">
                                    <div class="action-buttons">
                                        <button type="submit" name="edit_user" class="action-btn btn-primary">Save</button>
                                        <button type="submit" name="delete_user" class="action-btn btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">Delete</button>
                                    </div>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleStatusOptionsForAdd() {
            const roleSelect = document.getElementById('role');
            const statusSelect = document.getElementById('status');
            const repOption = document.getElementById('rep-option');
            const userIdField = document.getElementById('user_id');

            if (roleSelect.value === 'officer') {
                repOption.style.display = 'block';
                userIdField.placeholder = 'Enter Officer ID';
            } else if (roleSelect.value === 'student') {
                repOption.style.display = 'none';
                userIdField.placeholder = 'Enter Student ID';
                // Reset to active if Representative was selected
                if (statusSelect.value === 'Representative') {
                    statusSelect.value = 'active';
                }
            } else {
                repOption.style.display = 'none';
                userIdField.placeholder = 'Enter Student ID or Officer ID';
            }
        }

        function toggleStatusOptionsInRow(roleSelect) {
            const row = roleSelect.closest('tr');
            const statusSelect = row.querySelector('select[name="status"]');
            const repOption = statusSelect.querySelector('option[value="Representative"]');

            if (roleSelect.value === 'officer') {
                if (!repOption) {
                    const newOption = document.createElement('option');
                    newOption.value = 'Representative';
                    newOption.textContent = 'Representative';
                    statusSelect.appendChild(newOption);
                }
            } else {
                if (repOption) {
                    repOption.remove();
                }
                // Reset to active if Representative was selected
                if (statusSelect.value === 'Representative') {
                    statusSelect.value = 'active';
                }
            }
        }

        // Initialize the form on page load
        document.addEventListener('DOMContentLoaded', function () {
            toggleStatusOptionsForAdd();
        });
    </script>
</body>

</html>