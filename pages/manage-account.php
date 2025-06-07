<?php
require_once '../auth_check.php';
check_auth('student');
$current_user = get_user_info();

// Include database connection
require_once '../database/db.php';

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        
        if (!empty($name) && !empty($email)) {
            // Check if email is already taken by another student
            $check_sql = "SELECT studentID FROM student WHERE email = ? AND studentID != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $email, $current_user['id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $update_sql = "UPDATE student SET name = ?, email = ? WHERE studentID = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssi", $name, $email, $current_user['id']);
                
                if ($update_stmt->execute()) {
                    $success_message = "Profile updated successfully!";
                    // Update session data
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $current_user['name'] = $name;
                    $current_user['email'] = $email;
                } else {
                    $error_message = "Failed to update profile.";
                }
            } else {
                $error_message = "Email is already taken by another user.";
            }
        } else {
            $error_message = "Name and email are required.";
        }
    }
    
    if (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
            // Verify current password
            $verify_sql = "SELECT password FROM student WHERE studentID = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param("i", $current_user['id']);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $user_data = $verify_result->fetch_assoc();
            
            if (password_verify($current_password, $user_data['password'])) {
                if ($new_password === $confirm_password) {
                    if (strlen($new_password) >= 6) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_pass_sql = "UPDATE student SET password = ? WHERE studentID = ?";
                        $update_pass_stmt = $conn->prepare($update_pass_sql);
                        $update_pass_stmt->bind_param("si", $hashed_password, $current_user['id']);
                        
                        if ($update_pass_stmt->execute()) {
                            $success_message = "Password changed successfully!";
                        } else {
                            $error_message = "Failed to change password.";
                        }
                    } else {
                        $error_message = "New password must be at least 6 characters long.";
                    }
                } else {
                    $error_message = "New passwords do not match.";
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        } else {
            $error_message = "All password fields are required.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Account - CampusConnect</title>
    <link rel="stylesheet" href="../assets/css/student_dashboard.css">
    <style>
        .account-section {
            background: #fff;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo-title">
            <img src="https://static.vecteezy.com/system/resources/previews/032/414/564/original/3d-rendering-of-speech-bubble-3d-pastel-chat-with-question-mark-icon-set-png.png" alt="Logo" class="logo">
            <span class="app-title">CampusConnect</span>
        </div>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar">
            <ul>
                <li><a href="student_dashboard.php">🏠 Project Overview</a></li>
                <li><a href="announcement.php">📢 Announcements</a></li>
                <li><a href="chat.php">💬 Chat Support</a></li>
                <li class="active"><a href="manage-account.php">👤 My Account</a></li>
                <li><a href="../logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="content">
            <h1>My Account</h1>
            
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Current User Info -->
            <div class="account-section">
                <h2>Account Information</h2>
                <div class="user-info">
                    <p><strong>Student ID:</strong> <?php echo htmlspecialchars($current_user['id']); ?></p>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($current_user['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($current_user['email']); ?></p>
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($current_user['department']); ?></p>
                </div>
            </div>

            <!-- Update Profile -->
            <div class="account-section">
                <h2>Update Profile</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Full Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($current_user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn">Update Profile</button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="account-section">
                <h2>Change Password</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn">Change Password</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>