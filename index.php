<?php
session_start();

// Get any stored form data and errors
$login_email = isset($_SESSION['login_email']) ? $_SESSION['login_email'] : '';
$errors = isset($_SESSION['login_errors']) ? $_SESSION['login_errors'] : [];
$success_message = isset($_SESSION['signup_success']) ? $_SESSION['signup_success'] : '';

// Clear session data after retrieving
unset($_SESSION['login_email']);
unset($_SESSION['login_errors']);
unset($_SESSION['signup_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Sign Up - Campus Connect</title>
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
    <!-- Logo at top-left -->
    <div class="logo-wrapper">
        <a href="#" class="logo-brand">
            <img src="https://static.vecteezy.com/system/resources/previews/032/414/564/original/3d-rendering-of-speech-bubble-3d-pastel-chat-with-question-mark-icon-set-png.png" alt="Logo" class="logo-img" />
            <span class="logo-text">Campus Connect</span>
        </a>
    </div>

    <!-- Admin & Office login buttons at top-right -->
    <div class="login-buttons">
        <a href="pages/admin_login.php" class="login-btn">Login as Admin</a>
        <a href="pages/office_login.php" class="login-btn">Login as Office</a>
    </div>

    <div class="container">
        <div class="form-box">
            <?php if (!empty($success_message)): ?>
                <div class="success-message" style="background-color: #e6ffe6; border: 1px solid #99ff99; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                    <p style="color: #008000; margin: 5px 0;"><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-messages" style="background-color: #ffe6e6; border: 1px solid #ff9999; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                    <?php foreach ($errors as $error): ?>
                        <p style="color: #cc0000; margin: 5px 0;"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form id="auth-form" method="post" action="login_process.php">
                <input type="hidden" name="user_type" value="student">
                <h2 id="form-title">Login as Student</h2>
                <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($login_email); ?>" required />
                <input type="password" name="password" placeholder="Password" required />
                <button id="continue-btn" type="submit">Continue</button>
                <p>
                    <span id="toggle-text">Don't have an account?</span>
                    <a href="pages/signup.php">Sign Up</a>
                </p>
            </form>
        </div>
    </div>

    <!-- Loading overlay -->
    <div id="loading-overlay">
        <div id="lottie-loading"></div>
    </div>
</body>
</html>