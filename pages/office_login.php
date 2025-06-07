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
    <title>Office Login - Campus Connect</title>
    <link rel="stylesheet" href="../assets/css/office_login.css">
</head>
<body>
    <!-- Logo -->
    <div class="logo-wrapper">
        <a href="../index.php" class="logo-brand">
            <img src="https://static.vecteezy.com/system/resources/previews/032/414/564/original/3d-rendering-of-speech-bubble-3d-pastel-chat-with-question-mark-icon-set-png.png" alt="Logo" class="logo-img" />
            <span class="logo-text">Campus Connect</span>
        </a>
    </div>

    <div class="container">
        <h2>Office Login</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages" style="background-color: #ffe6e6; border: 1px solid #ff9999; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                <?php foreach ($errors as $error): ?>
                    <p style="color: #cc0000; margin: 5px 0;"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form action="../login_process.php" method="POST">
            <input type="hidden" name="user_type" value="office">
            <input type="email" name="email" placeholder="Office Email" value="<?php echo htmlspecialchars($login_email); ?>" required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Login as Office</button>
        </form>
        <div class="back-link">
            <a href="../index.php">&larr; Back to Main Login</a>
        </div>
    </div>
</body>
</html>