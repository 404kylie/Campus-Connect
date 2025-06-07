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
 * Get chat messages between student and officer (updated to support sender_type)
 */
function get_chat_messages($student_id, $officer_id) {
    global $conn;
    $sql = "SELECT c.*, s.name as studentName, o.name as officerName,
             CASE 
                 WHEN c.sender_type = 'student' THEN s.name
                 WHEN c.sender_type = 'officer' THEN o.name
             END as senderName
             FROM chat c 
             JOIN student s ON c.studentID = s.studentID 
             JOIN officer o ON c.officerID = o.officerID 
             WHERE c.studentID = ? AND c.officerID = ?
             ORDER BY c.date ASC, c.time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $student_id, $officer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Insert new chat message (updated to support sender_type)
 */
function insert_chat_message($officer_id, $student_id, $message, $sender_type = 'officer') {
    global $conn;
    $sql = "INSERT INTO chat (officerID, studentID, date, time, message, sender_type) VALUES (?, ?, CURDATE(), CURTIME(), ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $officer_id, $student_id, $message, $sender_type);
    return $stmt->execute();
}

/**
 * Get officer chat list for the chat hub (new function for office_chat.php)
 */
function get_officer_chat_list($officer_id, $department) {
    global $conn;
    
    $sql = "SELECT DISTINCT s.studentID, s.name, s.email, s.department,
                   c.message as last_message, 
                   c.date as last_date, 
                   c.time as last_time,
                   c.sender_type,
                   (SELECT COUNT(*) FROM chat c2 
                    WHERE c2.studentID = s.studentID 
                    AND c2.officerID = ? 
                    AND c2.sender_type = 'student'
                    AND c2.date = CURDATE()) as unread_count
            FROM student s
            LEFT JOIN chat c ON s.studentID = c.studentID AND c.officerID = ?
            WHERE s.department = ? AND c.chatID IS NOT NULL
            AND c.chatID = (
                SELECT MAX(c3.chatID) 
                FROM chat c3 
                WHERE c3.studentID = s.studentID AND c3.officerID = ?
            )
            ORDER BY c.date DESC, c.time DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisi", $officer_id, $officer_id, $department, $officer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all students with chat history for an officer (alternative function)
 */
function get_students_with_chat_history($officer_id, $department) {
    global $conn;
    
    $sql = "SELECT DISTINCT s.studentID, s.name, s.email, s.department,
                   (SELECT message FROM chat c 
                    WHERE c.studentID = s.studentID AND c.officerID = ? 
                    ORDER BY c.date DESC, c.time DESC LIMIT 1) as last_message,
                   (SELECT date FROM chat c 
                    WHERE c.studentID = s.studentID AND c.officerID = ? 
                    ORDER BY c.date DESC, c.time DESC LIMIT 1) as last_date,
                   (SELECT time FROM chat c 
                    WHERE c.studentID = s.studentID AND c.officerID = ? 
                    ORDER BY c.date DESC, c.time DESC LIMIT 1) as last_time,
                   (SELECT sender_type FROM chat c 
                    WHERE c.studentID = s.studentID AND c.officerID = ? 
                    ORDER BY c.date DESC, c.time DESC LIMIT 1) as sender_type,
                   (SELECT COUNT(*) FROM chat c 
                    WHERE c.studentID = s.studentID AND c.officerID = ? 
                    AND c.sender_type = 'student'
                    AND c.date = CURDATE()) as unread_count
            FROM student s
            WHERE s.department = ? 
            AND EXISTS (SELECT 1 FROM chat c WHERE c.studentID = s.studentID AND c.officerID = ?)
            ORDER BY last_date DESC, last_time DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiisi", $officer_id, $officer_id, $officer_id, $officer_id, $officer_id, $department, $officer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
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
 * Update officer profile (new function)
 */
function update_officer_profile($officer_id, $name, $email) {
    global $conn;
    $sql = "UPDATE officer SET name = ?, email = ? WHERE officerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $email, $officer_id);
    return $stmt->execute();
}

/**
 * Update officer password (new function)
 */
function update_officer_password($officer_id, $hashed_password) {
    global $conn;
    $sql = "UPDATE officer SET password = ? WHERE officerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $officer_id);
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
 * Check if email exists for another officer (new function)
 */
function email_exists_for_other_officer($email, $officer_id) {
    global $conn;
    $sql = "SELECT officerID FROM officer WHERE email = ? AND officerID != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $email, $officer_id);
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
 * Verify officer password (new function)
 */
function verify_officer_password($officer_id, $password) {
    global $conn;
    $sql = "SELECT password FROM officer WHERE officerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $officer_id);
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
    // Get count of messages from officers today that student hasn't responded to
    $sql = "SELECT COUNT(*) as count 
            FROM chat c1
            WHERE c1.studentID = ? 
            AND c1.sender_type = 'officer'
            AND c1.date = CURDATE()
            AND NOT EXISTS (
                SELECT 1 FROM chat c2 
                WHERE c2.studentID = c1.studentID 
                AND c2.officerID = c1.officerID
                AND c2.sender_type = 'student'
                AND c2.date >= c1.date 
                AND c2.time > c1.time
            )";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Get unread messages count for officer (new function)
 */
function get_unread_messages_count_for_officer($officer_id) {
    global $conn;
    // Get count of messages from students today that officer hasn't responded to
    $sql = "SELECT COUNT(*) as count 
            FROM chat c1
            WHERE c1.officerID = ? 
            AND c1.sender_type = 'student'
            AND c1.date = CURDATE()
            AND NOT EXISTS (
                SELECT 1 FROM chat c2 
                WHERE c2.studentID = c1.studentID 
                AND c2.officerID = c1.officerID
                AND c2.sender_type = 'officer'
                AND c2.date >= c1.date 
                AND c2.time > c1.time
            )";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $officer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Get total chat conversations count for officer (new function)
 */
function get_total_conversations_count($officer_id) {
    global $conn;
    $sql = "SELECT COUNT(DISTINCT studentID) as count 
            FROM chat 
            WHERE officerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $officer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Get students count in department (new function)
 */
function get_students_count_in_department($department) {
    global $conn;
    $sql = "SELECT COUNT(*) as count FROM student WHERE department = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $department);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Get announcements created by officer (new function)
 */
function get_announcements_by_officer($officer_id, $limit = null) {
    global $conn;
    $sql = "SELECT * FROM announcement WHERE officerID = ? ORDER BY date DESC, time DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
    }
    
    $stmt = $conn->prepare($sql);
    if ($limit) {
        $stmt->bind_param("ii", $officer_id, $limit);
    } else {
        $stmt->bind_param("i", $officer_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Insert new announcement (new function)
 */
function insert_announcement($officer_id, $subject, $content, $department = null) {
    global $conn;
    $sql = "INSERT INTO announcement (officerID, date, time, subject, content, department) VALUES (?, CURDATE(), CURTIME(), ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $officer_id, $subject, $content, $department);
    return $stmt->execute();
}

/**
 * Delete announcement (new function)
 */
function delete_announcement($announcement_id, $officer_id) {
    global $conn;
    $sql = "DELETE FROM announcement WHERE announcementID = ? AND officerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $announcement_id, $officer_id);
    return $stmt->execute();
}

/**
 * Get announcement by ID (new function)
 */
function get_announcement_by_id($announcement_id) {
    global $conn;
    $sql = "SELECT a.*, o.name as officerName 
            FROM announcement a 
            JOIN officer o ON a.officerID = o.officerID 
            WHERE a.announcementID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Update announcement (new function)
 */
function update_announcement($announcement_id, $officer_id, $subject, $content, $department = null) {
    global $conn;
    $sql = "UPDATE announcement SET subject = ?, content = ?, department = ? WHERE announcementID = ? AND officerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $subject, $content, $department, $announcement_id, $officer_id);
    return $stmt->execute();
}
?>