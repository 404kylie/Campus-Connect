<?php
session_start();

// Get any stored form data and errors
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
$errors = isset($_SESSION['signup_errors']) ? $_SESSION['signup_errors'] : [];

// Clear session data after retrieving
unset($_SESSION['form_data']);
unset($_SESSION['signup_errors']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Campus Connect</title>
    <link rel="stylesheet" href="../assets/css/signup.css">
</head>

<body>
    <!-- Logo at top-left -->
    <div class="logo-wrapper">
        <a href="#" class="logo-brand">
            <img src="https://static.vecteezy.com/system/resources/previews/032/414/564/original/3d-rendering-of-speech-bubble-3d-pastel-chat-with-question-mark-icon-set-png.png"
                alt="Logo" class="logo-img" />
            <span class="logo-text">Campus Connect</span>
        </a>
    </div>

    <div class="container">
        <div class="form-box">
            <?php if (!empty($errors)): ?>
                <div class="error-messages"
                    style="background-color: #ffe6e6; border: 1px solid #ff9999; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                    <?php foreach ($errors as $error): ?>
                        <p style="color: #cc0000; margin: 5px 0;"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form id="auth-form" method="post" action="signup_process.php">
                <h2 id="form-title">Sign Up</h2>

                <input type="email" name="email" placeholder="Email"
                    value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required />

                <input type="text" name="name" placeholder="Full Name"
                    value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" required />

                <select name="role" id="role" onchange="toggleFields()">
                    <option value="Student" <?php echo (isset($form_data['role']) && $form_data['role'] === 'Student') ? 'selected' : ''; ?>>Student</option>
                    <option value="Office" <?php echo (isset($form_data['role']) && $form_data['role'] === 'Office') ? 'selected' : ''; ?>>Office</option>
                </select>

                <input type="text" name="student_id" id="id-field" placeholder="Student ID"
                    value="<?php echo htmlspecialchars($form_data['student_id'] ?? ''); ?>" required />

                <select name="department" id="department-field" onchange="toggleDepartmentField()">
                    <option value="Bachelor of Science in Information Technology" <?php echo (isset($form_data['department']) && $form_data['department'] === 'Bachelor of Science in Information Technology') ? 'selected' : ''; ?>>Bachelor of Science in Information Technology
                    </option>
                    <option value="College of Arts and Sciences" <?php echo (isset($form_data['department']) && $form_data['department'] === 'College of Arts and Sciences') ? 'selected' : ''; ?>>College of Arts
                        and Sciences</option>
                    <option value="College of Education" <?php echo (isset($form_data['department']) && $form_data['department'] === 'College of Education') ? 'selected' : ''; ?>>College of Education
                    </option>
                    <option value="Bachelor of Industrial Technology" <?php echo (isset($form_data['department']) && $form_data['department'] === 'Bachelor of Industrial Technology') ? 'selected' : ''; ?>>Bachelor
                        of Industrial Technology</option>
                    <option value="College of Engineering" <?php echo (isset($form_data['department']) && $form_data['department'] === 'College of Engineering') ? 'selected' : ''; ?>>College of
                        Engineering</option>
                    <option value="College of Agriculture" <?php echo (isset($form_data['department']) && $form_data['department'] === 'College of Agriculture') ? 'selected' : ''; ?>>College of
                        Agriculture</option>
                    <option value="Hospitality Management" <?php echo (isset($form_data['department']) && $form_data['department'] === 'Hospitality Management') ? 'selected' : ''; ?>>Hospitality
                        Management</option>
                    <option value="Other" <?php echo (isset($form_data['department']) && $form_data['department'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>

                <input type="text" name="other_department" id="other-department-field"
                    placeholder="Please specify your department"
                    value="<?php echo htmlspecialchars($form_data['other_department'] ?? ''); ?>"
                    style="display: none;" />

                <input type="password" name="password" placeholder="Password" required />
                <input type="password" name="confirm_password" placeholder="Confirm Password" required />

                <button id="continue-btn" type="submit">Continue</button>

                <p>
                    <span id="toggle-text">Already have an account?</span>
                    <a href="../index.php">Log in</a>
                </p>
            </form>
        </div>
    </div>

    <!-- Loading overlay -->
    <div id="loading-overlay">
        <div id="lottie-loading"></div>
    </div>

    <script>
        function toggleFields() {
            const roleSelect = document.getElementById('role');
            const idField = document.getElementById('id-field');

            if (roleSelect.value === 'Student') {
                idField.placeholder = 'Student ID';
            } else if (roleSelect.value === 'Office') {
                idField.placeholder = 'Officer ID';
            }
        }

        function toggleDepartmentField() {
            const departmentSelect = document.getElementById('department-field');
            const otherDepartmentField = document.getElementById('other-department-field');

            if (departmentSelect.value === 'Other') {
                otherDepartmentField.style.display = 'block';
                otherDepartmentField.required = true;
            } else {
                otherDepartmentField.style.display = 'none';
                otherDepartmentField.required = false;
                otherDepartmentField.value = ''; // Clear the field when hidden
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            toggleFields();
            toggleDepartmentField(); // Also initialize department field visibility
        });

        // Form validation
        document.getElementById('auth-form').addEventListener('submit', function (e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }

            // Validate other department field if "Other" is selected
            const departmentSelect = document.getElementById('department-field');
            const otherDepartmentField = document.getElementById('other-department-field');

            if (departmentSelect.value === 'Other' && otherDepartmentField.value.trim() === '') {
                e.preventDefault();
                alert('Please specify your department!');
                return false;
            }
        });
    </script>
</body>

</html>