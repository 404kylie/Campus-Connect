<?php
// Function to check if user is logged in and has correct role
function check_auth($required_role = null) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit();
    }
    
    // Check if specific role is required
    if ($required_role && $_SESSION['user_type'] !== $required_role) {
        // Redirect to appropriate dashboard based on user type
        switch ($_SESSION['user_type']) {
            case 'student':
                header("Location: student_dashboard.php");
                break;
            case 'office':
                header("Location: office_dashboard.php");
                break;
            case 'admin':
                header("Location: admin_dashboard.php");
                break;
            default:
                header("Location: ../index.php");
        }
        exit();
    }
    
    return true;
}

// Function to get current user info
function get_user_info() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    return [
        'type' => $_SESSION['user_type'] ?? null,
        'id' => $_SESSION['user_id'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'department' => $_SESSION['user_department'] ?? null,
        'is_representative' => $_SESSION['is_representative'] ?? false
    ];
}
?>