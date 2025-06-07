<?php
require_once 'db.php';

/**
 * Get student by email
 */
function get_student_by_email($email) {
    global $conn;
    $sql = "SELECT * FROM student WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get officer by email
 */
function get_officer_by_email($email) {
    global $conn;
    $sql = "SELECT * FROM officer WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get admin by name
 */
function get_admin_by_name($name) {
    global $conn;
    $sql = "SELECT * FROM admin WHERE name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get department representative for a specific department
 */
function get_department_representative($department) {
    global $conn;
    $sql = "SELECT * FROM officer WHERE department = ? AND isRepresentative = 1 LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $department);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get announcements for a specific department
 */
function get_announcements_for_department($department, $limit = null) {
    global $conn;
    $sql = "SELECT a.*, o.name as officerName 
            FROM announcement a 
            JOIN officer o ON a.officerID = o.officerID 
            WHERE a.department = ? OR a.department IS NULL 
            ORDER BY a.date DESC, a.time DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
    }
    
    $stmt = $conn->prepare($sql);
    if ($limit) {
        $stmt->bind_param("si", $department, $limit);
    } else {
        $stmt->bind_param("s", $department);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get chat messages between student and officer
 */
function get_chat_messages($student_id, $officer_id) {
    global $conn;
    $sql = "SELECT c.*, s.name as senderName, 'student' as senderType 
            FROM chat c 
            JOIN student s ON c.studentID = s.studentID 
            WHERE c.studentID = ? AND c.officerID = ?
            UNION
            SELECT c.*, o.name as senderName, 'officer' as senderType 
            FROM chat c 
            JOIN officer o ON c.officerID = o.officerID 
            WHERE c.studentID = ? AND c.officerID = ?
            ORDER BY date ASC, time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $student_id, $officer_id, $student_id, $officer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Insert new chat message
 */
function insert_chat_message($officer_id, $student_id, $message) {
    global $conn;
    $sql = "INSERT INTO chat (officerID, studentID, date, time, message) VALUES (?, ?, CURDATE(), CURTIME(), ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $officer_id, $student_id, $message);
    return $stmt->execute();
}

/**
 * Update student profile
 */
function update_student_profile($student_id, $name, $email) {
    global $conn;
    $sql = "UPDATE student SET name = ?, email = ? WHERE studentID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $email, $student_id);
    return $stmt->execute();
}

/**
 * Update student password
 */
function update_student_password($student_id, $hashed_password) {
    global $conn;
    $sql = "UPDATE student SET password = ? WHERE studentID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $student_id);
    return $stmt->execute();
}

/**
 * Check if email exists for another student
 */
function email_exists_for_other_student($email, $student_id) {
    global $conn;
    $sql = "SELECT studentID FROM student WHERE email = ? AND studentID != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $email, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

/**
 * Verify student password
 */
function verify_student_password($student_id, $password) {
    global $conn;
    $sql = "SELECT password FROM student WHERE studentID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        return password_verify($password, $user['password']);
    }
    return false;
}

/**
 * Get recent announcements count for dashboard
 */
function get_recent_announcements_count($department, $days = 7) {
    global $conn;
    $sql = "SELECT COUNT(*) as count 
            FROM announcement 
            WHERE (department = ? OR department IS NULL) 
            AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $department, $days);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Get unread messages count for student
 */
function get_unread_messages_count($student_id) {
    global $conn;
    // This would require adding a 'read' column to the chat table
    // For now, return 0 as a placeholder
    return 0;
}
?>