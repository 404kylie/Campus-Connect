<?php
require_once '../auth_check.php';
check_auth('student');
$current_user = get_user_info();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CampusConnect - Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/student_dashboard.css" />
    <style>
      .sidebar ul li a {
        text-decoration: none;
        color: inherit;
        display: block;
        padding: 10px 15px;
      }
      .user-info {
        background: #f8f9fa;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        border-left: 4px solid #007bff;
      }
      .user-info h3 {
        margin: 0 0 10px 0;
        color: #333;
      }
      .user-info p {
        margin: 5px 0;
        color: #666;
      }
    </style>
  </head>

  <body>
    <header>
      <div class="logo-title">
        <img
          src="https://static.vecteezy.com/system/resources/previews/032/414/564/original/3d-rendering-of-speech-bubble-3d-pastel-chat-with-question-mark-icon-set-png.png"
          alt="Logo"
          class="logo"
        />
        <span class="app-title">CampusConnect</span>
      </div>
    </header>

    <div class="dashboard-container">
      <aside class="sidebar">
        <ul>
          <li class="active"><a href="student_dashboard.php">🏠 About</a></li>
          <li><a href="announcement.php">📢 Announcements</a></li>
          <li><a href="chat.php">💬 Chat Support</a></li>
          <li><a href="manage-account.php">👤 My Account</a></li>
          <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
      </aside>

      <main class="content">
        <section id="overview" class="section active">
          <!-- USER INFO
        <div class="user-info">
            <h3>Welcome back, <?php echo htmlspecialchars($current_user['name']); ?>!</h3>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($current_user['email']); ?></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($current_user['department']); ?></p>
            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($current_user['id']); ?></p>
          </div> -->
          
          <div class="project-overview">
            <h2>Project Overview</h2>
            <p>
              CampusConnect is a web-based communication system that enhances
              interaction between university offices and students.
            </p>
            <p>
              It provides a centralized platform for posting official updates,
              improving accessibility and real-time information sharing across
              the campus.
            </p>
            
            <div class="features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 30px;">
              <div class="feature-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #28a745;">
                <h3 style="margin-top: 0; color: #28a745;">📢 Announcements</h3>
                <p>Stay updated with the latest announcements from your department and university offices.</p>
                <a href="announcement.php" style="color: #28a745; font-weight: bold; text-decoration: none;">View Announcements →</a>
              </div>
              
              <div class="feature-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #17a2b8;">
                <h3 style="margin-top: 0; color: #17a2b8;">💬 Chat Support</h3>
                <p>Get instant help and support from university offices through our chat system.</p>
                <a href="chat.php" style="color: #17a2b8; font-weight: bold; text-decoration: none;">Start Chatting →</a>
              </div>
              
              <div class="feature-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #ffc107;">
                <h3 style="margin-top: 0; color: #e68900;">👤 My Account</h3>
                <p>Manage your account settings, update your profile, and change your password.</p>
                <a href="manage-account.php" style="color: #e68900; font-weight: bold; text-decoration: none;">Manage Account →</a>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </body>
</html>