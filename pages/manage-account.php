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
    <title>My Account - Campus Connect</title>
    <link rel="stylesheet" href="../assets/css/manage-account.css">
</head>

<body>
    <header>
        <div class="logo-title">
            <img src="https://static.vecteezy.com/system/resources/previews/032/414/564/original/3d-rendering-of-speech-bubble-3d-pastel-chat-with-question-mark-icon-set-png.png"
                alt="Logo" class="logo">
            <span class="app-title">Campus Connect</span>
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
            <div class="account-container">
                <h1>My Account</h1>

                <?php if ($success_message): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <!-- Current User Info -->
                <div class="account-section">
                    <h2>Account Information</h2>
                    <div class="user-info">
                        <div class="info-item">
                            <span class="info-label">Student ID:</span>
                            <span class="info-value"><?php echo htmlspecialchars($current_user['id']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($current_user['name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($current_user['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Department:</span>
                            <span class="info-value"><?php echo htmlspecialchars($current_user['department']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Update Profile -->
                <div class="account-section">
                    <h2>Update Profile</h2>
                    <form method="POST" class="account-form">
                        <div class="form-group">
                            <label for="name">Full Name:</label>
                            <input type="text" id="name" name="name"
                                value="<?php echo htmlspecialchars($current_user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email"
                                value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="account-section">
                    <h2>Change Password</h2>
                    <form method="POST" class="account-form">
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
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>