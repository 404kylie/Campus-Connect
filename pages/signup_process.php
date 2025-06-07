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
    
    // Role-specific validation
    if ($role === "Student") {
        $student_id = trim($_POST['student_id']);
        if (empty($student_id)) {
            $errors[] = "Student ID is required";
        }
    } else if ($role === "Office") {
        $officer_id = trim($_POST['student_id']); // This field is reused for officer ID
        if (empty($officer_id)) {
            $errors[] = "Officer ID is required";
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
            $check_sql = "SELECT email FROM student WHERE email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['signup_errors'] = ["Email already registered as student"];
                $_SESSION['form_data'] = $_POST;
                header("Location: signup.php");
                exit();
            }
            
            // Insert into student table
            $sql = "INSERT INTO student (email, password, name, department) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $email, $hashed_password, $name, $department);
            
        } else if ($role === "Office") {
            // Check if email already exists in officer table
            $check_sql = "SELECT email FROM officer WHERE email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $_SESSION['signup_errors'] = ["Email already registered as officer"];
                $_SESSION['form_data'] = $_POST;
                header("Location: signup.php");
                exit();
            }
            
            // Insert into officer table
            $sql = "INSERT INTO officer (email, password, name, department) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $email, $hashed_password, $name, $department);
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