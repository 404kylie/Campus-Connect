<?php
session_start();
require_once 'database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'] ?? 'student'; // Default to student

    // Validation
    $errors = [];

    if (empty($email)) {
        $errors[] = "Email is required";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    }

    // For admin, we don't need email validation since they use username
    if ($user_type !== 'admin' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        $_SESSION['login_email'] = $email;

        // Redirect back to appropriate login page
        switch ($user_type) {
            case 'admin':
                header("Location: pages/admin_login.php");
                break;
            case 'office':
                header("Location: pages/office_login.php");
                break;
            default:
                header("Location: index.php");
        }
        exit();
    }

    try {
        switch ($user_type) {
            case 'student':
                // Check student credentials
                $sql = "SELECT studentID, email, password, name, department FROM student WHERE email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        // Set session variables
                        $_SESSION['user_type'] = 'student';
                        $_SESSION['user_id'] = $user['studentID'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_department'] = $user['department'];

                        header("Location: pages/student_dashboard.php");
                        exit();
                    }
                }
                break;

            case 'office':
                // Check officer credentials
                $sql = "SELECT officerID, email, password, name, department, isRepresentative FROM officer WHERE email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        // Set session variables
                        $_SESSION['user_type'] = 'officer';
                        $_SESSION['user_id'] = $user['officerID'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_department'] = $user['department'];
                        $_SESSION['is_representative'] = $user['isRepresentative'];

                        header("Location: pages/office_dashboard.php");
                        exit();
                    }
                }
                break;

            case 'admin':
                // Check admin credentials
                // Admin login uses the name field as username (not email)
                $username = $email; // The form field is called 'email' but contains username for admin
                $sql = "SELECT adminID, name, password FROM admin WHERE name = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        // Set session variables for admin
                        $_SESSION['user_type'] = 'admin';
                        $_SESSION['user_id'] = $user['adminID'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = ''; // Admins don't have email in your schema
                        $_SESSION['user_department'] = ''; // Admins don't have department

                        // Also set the admin-specific session variables for backward compatibility
                        $_SESSION['admin_id'] = $user['adminID'];
                        $_SESSION['admin_name'] = $user['name'];

                        header("Location: pages/admin_dashboard.php");
                        exit();
                    }
                }
                break;
        }

        // If we reach here, login failed
        $_SESSION['login_errors'] = ["Invalid credentials"];
        $_SESSION['login_email'] = $email;

        // Redirect back to appropriate login page
        switch ($user_type) {
            case 'admin':
                header("Location: pages/admin_login.php");
                break;
            case 'office':
                header("Location: pages/office_login.php");
                break;
            default:
                header("Location: index.php");
        }
        exit();

    } catch (Exception $e) {
        $_SESSION['login_errors'] = ["An error occurred during login. Please try again."];
        $_SESSION['login_email'] = $email;

        // Redirect back to appropriate login page
        switch ($user_type) {
            case 'admin':
                header("Location: pages/admin_login.php");
                break;
            case 'office':
                header("Location: pages/office_login.php");
                break;
            default:
                header("Location: index.php");
        }
        exit();
    }

    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
} else {
    header("Location: index.php");
    exit();
}
?>