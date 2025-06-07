<?php
session_start();

function check_auth($required_role = null) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        header("Location: ../index.php");
        exit();
    }
    
    // If a specific role is required, check if user has that role
    if ($required_role && $_SESSION['user_type'] !== $required_role) {
        // Redirect based on user type instead of just going to index
        redirect_by_role();
    }
}

function get_user_info() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'department' => $_SESSION['user_department'] ?? '',
        'type' => $_SESSION['user_type'] ?? ''
    ];
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function get_user_type() {
    return $_SESSION['user_type'] ?? null;
}

function redirect_by_role() {
    if (!is_logged_in()) {
        header("Location: ../index.php");
        exit();
    }
    
    switch ($_SESSION['user_type']) {
        case 'student':
            header("Location: pages/student_dashboard.php");
            break;
        case 'officer':
            header("Location: pages/office_dashboard.php");
            break;
        case 'admin':
            header("Location: pages/admin_dashboard.php");
            break;
        default:
            header("Location: ../index.php");
    }
    exit();
}

/**
 * Set session data for student login
 */
function set_student_session($student_data) {
    $_SESSION['user_id'] = $student_data['studentID'];
    $_SESSION['user_name'] = $student_data['name'];
    $_SESSION['user_email'] = $student_data['email'];
    $_SESSION['user_department'] = $student_data['department'];
    $_SESSION['user_type'] = 'student';
}

/**
 * Set session data for officer login
 */
function set_officer_session($officer_data) {
    $_SESSION['user_id'] = $officer_data['officerID'];
    $_SESSION['user_name'] = $officer_data['name'];
    $_SESSION['user_email'] = $officer_data['email'];
    $_SESSION['user_department'] = $officer_data['department'];
    $_SESSION['user_type'] = 'officer';
    $_SESSION['is_representative'] = $officer_data['isRepresentative'] ?? false;
}

/**
 * Set session data for admin login
 */
function set_admin_session($admin_data) {
    $_SESSION['user_id'] = $admin_data['adminID'];
    $_SESSION['user_name'] = $admin_data['name'];
    $_SESSION['user_email'] = ''; // Admins don't have email in your schema
    $_SESSION['user_department'] = ''; // Admins don't have department
    $_SESSION['user_type'] = 'admin';
}

/**
 * Clear all session data (logout)
 */
function logout() {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

/**
 * Check if user has access to specific department content
 */
function can_access_department($department) {
    if (!is_logged_in()) {
        return false;
    }
    
    $user_type = get_user_type();
    $user_info = get_user_info();
    
    // Admins can access everything
    if ($user_type === 'admin') {
        return true;
    }
    
    // Students and officers can only access their own department
    if ($user_type === 'student' || $user_type === 'officer') {
        return $user_info['department'] === $department;
    }
    
    return false;
}
?>