<?php
session_start();

// Get any stored form data and errors
$login_email = isset($_SESSION['login_email']) ? $_SESSION['login_email'] : '';
$errors = isset($_SESSION['login_errors']) ? $_SESSION['login_errors'] : [];

// Clear session data after retrieving
unset($_SESSION['login_email']);
unset($_SESSION['login_errors']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Campus Connect</title>
    <link rel="stylesheet" href="../assets/css/index.css">
    <style>
        /* Override some styles for admin login page */
        .login-buttons {
            display: none;
            /* Hide the login buttons on this page */
        }

        .form-box h2 {
            color: #dc3545;
            /* Red color for admin login */
        }

        .back-link {
            text-align: center;
            margin-top: 1rem;
        }

        .back-link a {
            color: #f76c2f;
            font-weight: 600;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .admin-note {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <!-- Logo at top-left -->
    <div class="logo-wrapper">
        <a href="../index.php" class="logo-brand">
            <img src="https://static.vecteezy.com/system/resources/previews/032/414/564/original/3d-rendering-of-speech-bubble-3d-pastel-chat-with-question-mark-icon-set-png.png"
                alt="Logo" class="logo-img" />
            <span class="logo-text">Campus Connect</span>
        </a>
    </div>

    <div class="container">
        <div class="form-box">
            <div class="admin-note">
                <strong>Admin Access:</strong> Use your admin username and password to log in.
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-messages"
                    style="background-color: #ffe6e6; border: 1px solid #ff9999; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                    <?php foreach ($errors as $error): ?>
                        <p style="color: #cc0000; margin: 5px 0;"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form id="auth-form" method="post" action="../login_process.php">
                <input type="hidden" name="user_type" value="admin">
                <h2 id="form-title">Admin Login</h2>
                <input type="text" name="email" placeholder="Admin Username"
                    value="<?php echo htmlspecialchars($login_email); ?>" required />
                <input type="password" name="password" placeholder="Password" required />
                <button id="continue-btn" type="submit">Login as Admin</button>
            </form>

            <div class="back-link">
                <a href="../index.php">&larr; Back to Main Login</a>
            </div>
        </div>
    </div>

    <!-- Loading overlay (if you have this in your CSS) -->
    <div id="loading-overlay" style="display: none;">
        <div id="lottie-loading"></div>
    </div>
</body>

</html>