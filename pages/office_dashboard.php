<?php
require_once '../auth_check.php';
check_auth('officer');
$current_user = get_user_info();

require_once '../database/db.php';
require_once '../database/helpers.php';

// Handle form submissions
$success_message = '';
$error_message = '';

// Handle new announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_announcement'])) {
    $subject = trim($_POST['subject']);
    $content = trim($_POST['content']);
    $department_only = isset($_POST['department_only']) ? $current_user['department'] : NULL;
    
    if (!empty($subject) && !empty($content)) {
        $sql = "INSERT INTO announcement (officerID, date, time, subject, content, department) VALUES (?, CURDATE(), CURTIME(), ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $current_user['id'], $subject, $content, $department_only);
        
        if ($stmt->execute()) {
            $success_message = "Announcement posted successfully!";
        } else {
            $error_message = "Error posting announcement. Please try again.";
        }
    } else {
        $error_message = "Subject and content are required.";
    }
}

// Handle edit announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_announcement'])) {
    $announcement_id = (int)$_POST['announcement_id'];
    $subject = trim($_POST['edit_subject']);
    $content = trim($_POST['edit_content']);
    
    if (!empty($subject) && !empty($content)) {
        $sql = "UPDATE announcement SET subject = ?, content = ? WHERE announcementID = ? AND officerID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $subject, $content, $announcement_id, $current_user['id']);
        
        if ($stmt->execute()) {
            $success_message = "Announcement updated successfully!";
        } else {
            $error_message = "Error updating announcement. Please try again.";
        }
    } else {
        $error_message = "Subject and content are required.";
    }
}

// Handle delete announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    $announcement_id = (int)$_POST['announcement_id'];
    
    $sql = "DELETE FROM announcement WHERE announcementID = ? AND officerID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $announcement_id, $current_user['id']);
    
    if ($stmt->execute()) {
        $success_message = "Announcement deleted successfully!";
    } else {
        $error_message = "Error deleting announcement. Please try again.";
    }
}

// Get all announcements by this officer
$announcements_sql = "SELECT * FROM announcement WHERE officerID = ? ORDER BY date DESC, time DESC";
$announcements_stmt = $conn->prepare($announcements_sql);
$announcements_stmt->bind_param("i", $current_user['id']);
$announcements_stmt->execute();
$announcements_result = $announcements_stmt->get_result();
$announcements = $announcements_result->fetch_all(MYSQLI_ASSOC);

// Get dashboard statistics
$stats = [
    'total_announcements' => count($announcements),
    'recent_announcements' => 0,
    'active_chats' => 0
];

// Count recent announcements (last 7 days)
foreach ($announcements as $announcement) {
    $announcement_date = new DateTime($announcement['date']);
    $week_ago = new DateTime('-7 days');
    if ($announcement_date >= $week_ago) {
        $stats['recent_announcements']++;
    }
}

// Count active chats (students who have messaged this officer)
$chat_count_sql = "SELECT COUNT(DISTINCT studentID) as count FROM chat WHERE officerID = ?";
$chat_count_stmt = $conn->prepare($chat_count_sql);
$chat_count_stmt->bind_param("i", $current_user['id']);
$chat_count_stmt->execute();
$chat_count_result = $chat_count_stmt->get_result();
$chat_count_row = $chat_count_result->fetch_assoc();
$stats['active_chats'] = $chat_count_row['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard - CampusConnect</title>
    <link rel="stylesheet" href="../assets/css/office_dashboard.css">
    <style>
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            flex: 1;
            border: 1px solid #dee2e6;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #6c757d;
            margin-top: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .announcement-subject {
            font-weight: bold;
            font-size: 1.1em;
            color: #333;
        }
        .announcement-meta {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
        }
        .announcement-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .edit-form {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 3px;
            cursor: pointer;
            border: none;
        }
        .btn-edit {
            background: #28a745;
            color: white;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header-section">
            <h1>Welcome, <?php echo htmlspecialchars($current_user['name']); ?>!</h1>
            <div class="btn-group">
                <form action="../logout.php" method="POST" style="margin: 0;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
                <a href="office_chat.php" class="chat-btn-link" title="Chat Support">Chat Support</a>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Dashboard Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_announcements']; ?></div>
                <div class="stat-label">Total Announcements</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['recent_announcements']; ?></div>
                <div class="stat-label">This Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active_chats']; ?></div>
                <div class="stat-label">Active Chats</div>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        <h2>Post a New Announcement</h2>
        <form method="POST">
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" name="subject" id="subject" required placeholder="Enter announcement subject...">
            </div>
            <div class="form-group">
                <label for="content">Content</label>
                <textarea name="content" id="content" required placeholder="Write your announcement here..." rows="5"></textarea>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" name="department_only" id="department_only" value="1">
                <label for="department_only">Restrict to <?php echo htmlspecialchars($current_user['department']); ?> department only</label>
            </div>
            <button type="submit" name="new_announcement" class="save-btn">Post Announcement</button>
        </form>
    </div>

    <div class="dashboard-container">
        <h2>Your Announcements (<?php echo count($announcements); ?>)</h2>
        
        <?php if (empty($announcements)): ?>
            <p>No announcements posted yet.</p>
        <?php else: ?>
            <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-box">
                    <div class="announcement-header">
                        <div class="announcement-subject"><?php echo htmlspecialchars($announcement['subject']); ?></div>
                        <div class="announcement-actions">
                            <button class="btn-small btn-edit" onclick="toggleEdit(<?php echo $announcement['announcementID']; ?>)">Edit</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this announcement?')">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement['announcementID']; ?>">
                                <button type="submit" name="delete_announcement" class="btn-small btn-delete">Delete</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="announcement-meta">
                        Posted on <?php echo date('F j, Y \a\t g:i A', strtotime($announcement['date'] . ' ' . $announcement['time'])); ?>
                        <?php if ($announcement['department']): ?>
                            • Department: <?php echo htmlspecialchars($announcement['department']); ?>
                        <?php else: ?>
                            • All Departments
                        <?php endif; ?>
                    </div>
                    
                    <div class="announcement-content">
                        <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                    </div>

                    <!-- Edit Form (Hidden by default) -->
                    <form method="POST" class="edit-form" id="edit-form-<?php echo $announcement['announcementID']; ?>">
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="edit_subject" value="<?php echo htmlspecialchars($announcement['subject']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Content</label>
                            <textarea name="edit_content" rows="4" required><?php echo htmlspecialchars($announcement['content']); ?></textarea>
                        </div>
                        <input type="hidden" name="announcement_id" value="<?php echo $announcement['announcementID']; ?>">
                        <button type="submit" name="edit_announcement" class="btn-small btn-edit">Update</button>
                        <button type="button" class="btn-small btn-cancel" onclick="toggleEdit(<?php echo $announcement['announcementID']; ?>)">Cancel</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function toggleEdit(announcementId) {
            const editForm = document.getElementById('edit-form-' + announcementId);
            if (editForm.style.display === 'none' || editForm.style.display === '') {
                editForm.style.display = 'block';
            } else {
                editForm.style.display = 'none';
            }
        }
    </script>
</body>
</html>