<?php
session_start();

function check_auth($required_role = null) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        header("Location: ../index.html");
        exit();
    }
    
    if ($required_role && $_SESSION['user_type'] !== $required_role) {
        header("Location: ../index.html");
        exit();
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
        header("Location: ../index.html");
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
            header("Location: ../index.html");
    }
    exit();
}
?>