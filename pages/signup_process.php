<?php
session_start();
require_once '../database/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $department = $_POST['department'];

    // Handle "Other" department
    if ($department === 'Other') {
        $other_department = trim($_POST['other_department']);
        if (empty($other_department)) {
            $errors[] = "Please specify your department";
        } else {
            $department = $other_department; // Use the custom department name
        }
    }

    // Validation
    $errors = [];

    // Check if passwords match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Validate password strength
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Check for required fields
    if (empty($name)) {
        $errors[] = "Full name is required";
    }

    // Validate department
    if (empty($department)) {
        $errors[] = "Department is required";
    }

    // Role-specific validation and ID extraction
    if ($role === "Student") {
        $student_id = trim($_POST['student_id']);
        if (empty($student_id)) {
            $errors[] = "Student ID is required";
        }
        // Optional: Add student ID format validation
        if (!empty($student_id) && !preg_match('/^[A-Za-z0-9\-]+$/', $student_id)) {
            $errors[] = "Student ID can only contain letters, numbers, and hyphens";
        }
    } else if ($role === "Office") {
        $officer_id = trim($_POST['student_id']); // This field is reused for officer ID
        if (empty($officer_id)) {
            $errors[] = "Officer ID is required";
        }
        // Optional: Add officer ID format validation
        if (!empty($officer_id) && !preg_match('/^[A-Za-z0-9\-]+$/', $officer_id)) {
            $errors[] = "Officer ID can only contain letters, numbers, and hyphens";
        }
    }

    // If there are validation errors, redirect back with errors
    if (!empty($errors)) {
        $_SESSION['signup_errors'] = $errors;
        $_SESSION['form_data'] = $_POST; // Preserve form data
        header("Location: signup.php");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        if ($role === "Student") {
            // Check if email already exists in student table
            $check_email_sql = "SELECT email FROM student WHERE email = ?";
            $check_email_stmt = $conn->prepare($check_email_sql);
            $check_email_stmt->bind_param("s", $email);
            $check_email_stmt->execute();
            $email_result = $check_email_stmt->get_result();

            if ($email_result->num_rows > 0) {
                $_SESSION['signup_errors'] = ["Email already registered as student"];
                $_SESSION['form_data'] = $_POST;
                header("Location: signup.php");
                exit();
            }

            // Check if student ID already exists
            $check_id_sql = "SELECT studentID FROM student WHERE studentID = ?";
            $check_id_stmt = $conn->prepare($check_id_sql);
            $check_id_stmt->bind_param("s", $student_id);
            $check_id_stmt->execute();
            $id_result = $check_id_stmt->get_result();

            if ($id_result->num_rows > 0) {
                $_SESSION['signup_errors'] = ["Student ID already exists"];
                $_SESSION['form_data'] = $_POST;
                header("Location: signup.php");
                exit();
            }

            // Insert into student table with custom studentID
            $sql = "INSERT INTO student (studentID, email, password, name, department) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $student_id, $email, $hashed_password, $name, $department);

        } else if ($role === "Office") {
            // Check if email already exists in officer table
            $check_email_sql = "SELECT email FROM officer WHERE email = ?";
            $check_email_stmt = $conn->prepare($check_email_sql);
            $check_email_stmt->bind_param("s", $email);
            $check_email_stmt->execute();
            $email_result = $check_email_stmt->get_result();

            if ($email_result->num_rows > 0) {
                $_SESSION['signup_errors'] = ["Email already registered as officer"];
                $_SESSION['form_data'] = $_POST;
                header("Location: signup.php");
                exit();
            }

            // Check if officer ID already exists
            $check_id_sql = "SELECT officerID FROM officer WHERE officerID = ?";
            $check_id_stmt = $conn->prepare($check_id_sql);
            $check_id_stmt->bind_param("s", $officer_id);
            $check_id_stmt->execute();
            $id_result = $check_id_stmt->get_result();

            if ($id_result->num_rows > 0) {
                $_SESSION['signup_errors'] = ["Officer ID already exists"];
                $_SESSION['form_data'] = $_POST;
                header("Location: signup.php");
                exit();
            }

            // Insert into officer table with custom officerID
            $sql = "INSERT INTO officer (officerID, email, password, name, department) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $officer_id, $email, $hashed_password, $name, $department);
        }

        if ($stmt->execute()) {
            $_SESSION['signup_success'] = "Account created successfully! You can now log in.";
            header("Location: ../index.php");
            exit();
        } else {
            throw new Exception("Failed to create account");
        }

    } catch (Exception $e) {
        $_SESSION['signup_errors'] = ["An error occurred while creating your account. Please try again."];
        $_SESSION['form_data'] = $_POST;
        header("Location: signup.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: signup.php");
    exit();
}
?>