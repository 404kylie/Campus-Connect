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
    $announcement_id = (int) $_POST['announcement_id'];
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
    $announcement_id = (int) $_POST['announcement_id'];

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
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f6d365, #fda085);
            margin: 0;
            padding: 2rem;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .dashboard-container {
            max-width: 700px;
            margin: 0 auto 2rem auto;
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        h1,
        h2 {
            text-align: center;
            color: #f76c2f;
        }

        textarea {
            width: 100%;
            height: 120px;
            resize: none;
            border-radius: 8px;
            border: 1px solid #ccc;
            padding: 1rem;
            font-size: 1rem;
            font-family: inherit;
            box-sizing: border-box;
        }

        button.save-btn {
            background: linear-gradient(90deg, #f76c2f, #fdae6b);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            font-size: 1.1rem;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.3s ease, box-shadow 0.3s ease;
            margin-top: 1rem;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        button.save-btn:hover {
            background: linear-gradient(90deg, #fdae6b, #f76c2f);
            box-shadow: 0 4px 15px rgba(247, 108, 47, 0.5);
            transform: scale(1.05);
        }

        .announcement-box {
            background: #fff0e6;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid #f0d0b5;
        }

        .announcement-box textarea {
            flex-grow: 1;
            min-height: 80px;
        }

        .announcement-date {
            font-size: 0.85rem;
            color: #555;
            width: 100%;
            margin-top: 0.3rem;
            font-style: italic;
        }

        .delete-btn {
            background: #e74c3c;
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            border-radius: 8px;
            cursor: pointer;
            height: fit-content;
            transition: background 0.3s ease;
            align-self: center;
        }

        .delete-btn:hover {
            background: #c0392b;
        }

        .edit-form {
            flex-grow: 1;
        }

        .message {
            color: green;
            font-weight: 600;
            margin-bottom: 1rem;
            text-align: center;
        }

        /* Beautiful Success and Error Messages */
        .success-message {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            padding: 1.2rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 4px 15px rgba(21, 87, 36, 0.15);
            position: relative;
            overflow: hidden;
            font-weight: 600;
            font-size: 1rem;
            animation: slideInFromTop 0.5s ease-out;
        }

        .success-message::before {
            content: "✓";
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.4rem;
            font-weight: bold;
            color: #28a745;
            background: rgba(255, 255, 255, 0.8);
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .success-message::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #28a745, #20c997, #17a2b8);
            border-radius: 12px 12px 0 0;
        }

        .error-message {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            padding: 1.2rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 4px 15px rgba(114, 28, 36, 0.15);
            position: relative;
            overflow: hidden;
            font-weight: 600;
            font-size: 1rem;
            animation: slideInFromTop 0.5s ease-out;
        }

        .error-message::before {
            content: "⚠";
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.4rem;
            font-weight: bold;
            color: #dc3545;
            background: rgba(255, 255, 255, 0.8);
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }

        .error-message::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #dc3545, #e74c3c, #fd7e14);
            border-radius: 12px 12px 0 0;
        }

        /* Add padding to account for the icon */
        .success-message,
        .error-message {
            padding-left: 4rem;
        }

        /* Animation for messages */
        @keyframes slideInFromTop {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Pulse animation for icons */
        .success-message::before,
        .error-message::before {
            animation: iconPulse 2s ease-in-out infinite;
        }

        @keyframes iconPulse {

            0%,
            100% {
                transform: translateY(-50%) scale(1);
            }

            50% {
                transform: translateY(-50%) scale(1.1);
            }
        }

        /* Beautiful Dashboard Statistics Cards */
        .stats-container {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .stat-card {
            background: linear-gradient(135deg, #fff0e6, #ffe4d1);
            padding: 2rem 1.5rem;
            border-radius: 16px;
            text-align: center;
            flex: 1;
            min-width: 180px;
            border: 2px solid transparent;
            background-clip: padding-box;
            box-shadow: 0 8px 25px rgba(247, 108, 47, 0.15);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #f76c2f, #fdae6b, #fda085);
            border-radius: 16px 16px 0 0;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(247, 108, 47, 0.25);
            background: linear-gradient(135deg, #ffe4d1, #ffd7bf);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            background: linear-gradient(135deg, #f76c2f, #fdae6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(247, 108, 47, 0.1);
            animation: numberPulse 3s ease-in-out infinite;
        }

        .stat-label {
            color: #8b4513;
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 0.5rem;
            position: relative;
        }

        .stat-label::after {
            content: "";
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 2px;
            background: linear-gradient(90deg, #f76c2f, #fdae6b);
            border-radius: 2px;
        }

        /* Add icons to stat cards */
        .stat-card:nth-child(1)::after {
            content: "📢";
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.6;
        }

        .stat-card:nth-child(2)::after {
            content: "📅";
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.6;
        }

        .stat-card:nth-child(3)::after {
            content: "💬";
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.6;
        }

        /* Animation for numbers */
        @keyframes numberPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        /* Responsive design for stats */
        @media (max-width: 768px) {
            .stats-container {
                flex-direction: column;
                gap: 1rem;
            }

            .stat-card {
                min-width: unset;
                padding: 1.5rem;
            }

            .stat-number {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 480px) {
            .stat-number {
                font-size: 2rem;
            }

            .stat-label {
                font-size: 0.9rem;
            }
        }

        /* Added styles for buttons container */
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        button.logout-btn,
        a.chat-btn-link {
            background: linear-gradient(90deg, #f76c2f, #fdae6b);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            font-size: 1rem;
            border-radius: 8px;
            cursor: pointer;
            min-width: 120px;
            flex: 1 1 150px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        button.logout-btn:hover,
        a.chat-btn-link:hover {
            background: linear-gradient(90deg, #fdae6b, #f76c2f);
            box-shadow: 0 4px 15px rgba(247, 108, 47, 0.5);
            transform: scale(1.05);
            text-decoration: none;
            color: white;
        }

        /* Enhanced Announcement Header Styles */
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .announcement-subject {
            font-weight: bold;
            font-size: 1.2rem;
            color: #333;
            flex: 1;
            min-width: 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            line-height: 1.4;
        }

        .announcement-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0d0b5;
        }

        .announcement-content {
            color: #444;
            line-height: 1.6;
            margin-bottom: 1rem;
            white-space: pre-wrap;
        }

        /* Improved Action Buttons */
        .announcement-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
            align-items: flex-start;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            white-space: nowrap;
            min-width: 60px;
            text-align: center;
        }

        .btn-edit {
            background: linear-gradient(90deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
        }

        .btn-edit:hover {
            background: linear-gradient(90deg, #20c997, #28a745);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.4);
            transform: translateY(-1px);
        }

        .btn-delete {
            background: linear-gradient(90deg, #dc3545, #e74c3c);
            color: white;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }

        .btn-delete:hover {
            background: linear-gradient(90deg, #e74c3c, #dc3545);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.4);
            transform: translateY(-1px);
        }

        .btn-cancel {
            background: linear-gradient(90deg, #6c757d, #495057);
            color: white;
            box-shadow: 0 2px 4px rgba(108, 117, 125, 0.3);
        }

        .btn-cancel:hover {
            background: linear-gradient(90deg, #495057, #6c757d);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.4);
            transform: translateY(-1px);
        }

        /* Enhanced Edit Form */
        .edit-form {
            margin-top: 1rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            border: 1px solid #dee2e6;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #495057;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.95rem;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #f76c2f;
            box-shadow: 0 0 0 2px rgba(247, 108, 47, 0.2);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .dashboard-container {
                padding: 1.5rem;
            }

            .announcement-header {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }

            .announcement-actions {
                justify-content: flex-end;
                margin-top: 0.5rem;
            }

            .btn-small {
                padding: 0.6rem 0.8rem;
                font-size: 0.8rem;
                min-width: 55px;
            }

            .announcement-subject {
                font-size: 1.1rem;
                margin-bottom: 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .announcement-actions {
                flex-wrap: wrap;
                gap: 0.4rem;
            }

            .btn-small {
                flex: 1;
                min-width: 0;
            }

            .edit-form {
                padding: 1rem;
            }
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
                <textarea name="content" id="content" required placeholder="Write your announcement here..."
                    rows="5"></textarea>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" name="department_only" id="department_only" value="1">
                <label for="department_only">Restrict to <?php echo htmlspecialchars($current_user['department']); ?>
                    department only</label>
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
                            <button class="btn-small btn-edit"
                                onclick="toggleEdit(<?php echo $announcement['announcementID']; ?>)">Edit</button>
                            <form method="POST" style="display: inline;"
                                onsubmit="return confirm('Are you sure you want to delete this announcement?')">
                                <input type="hidden" name="announcement_id"
                                    value="<?php echo $announcement['announcementID']; ?>">
                                <button type="submit" name="delete_announcement" class="btn-small btn-delete">Delete</button>
                            </form>
                        </div>
                    </div>

                    <div class="announcement-meta">
                        Posted on
                        <?php echo date('F j, Y \a\t g:i A', strtotime($announcement['date'] . ' ' . $announcement['time'])); ?>
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
                            <input type="text" name="edit_subject"
                                value="<?php echo htmlspecialchars($announcement['subject']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Content</label>
                            <textarea name="edit_content" rows="4"
                                required><?php echo htmlspecialchars($announcement['content']); ?></textarea>
                        </div>
                        <input type="hidden" name="announcement_id" value="<?php echo $announcement['announcementID']; ?>">
                        <button type="submit" name="edit_announcement" class="btn-small btn-edit">Update</button>
                        <button type="button" class="btn-small btn-cancel"
                            onclick="toggleEdit(<?php echo $announcement['announcementID']; ?>)">Cancel</button>
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