<?php
require_once '../auth_check.php';
check_auth('student');
$current_user = get_user_info();

// Include database connection
require_once '../database/db.php';

// Fetch announcements for the student's department
$department = $current_user['department'];
$sql = "SELECT a.*, o.name as officerName 
        FROM announcement a 
        JOIN officer o ON a.officerID = o.officerID 
        WHERE a.department = ? OR a.department IS NULL 
        ORDER BY a.date DESC, a.time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();
$announcements = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CampusConnect Portal</title>
    <link rel="stylesheet" href="../assets/css/announcement.css" />
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
          <li><a href="student_dashboard.php">🏠 Project Overview</a></li>
          <li class="active"><a href="announcement.php">📢 Announcements</a></li>
          <li><a href="chat.php">💬 Chat Support</a></li>
          <li><a href="manage-account.php">👤 My Account</a></li>
          <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
      </aside>

      <main class="content">
        <section class="section" id="announcements">
          <h1>📢 Latest Announcements</h1>
          <div id="announcement-list">
            <?php if (empty($announcements)): ?>
              <div class="no-announcements">
                <p>No announcements available at this time.</p>
              </div>
            <?php else: ?>
              <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-item">
                  <div class="announcement-header">
                    <h3 class="announcement-title">
                      <?php echo htmlspecialchars($announcement['subject']); ?>
                    </h3>
                    <div class="announcement-datetime">
                      <p class="announcement-date">
                        <?php echo date('F d, Y', strtotime($announcement['date'])); ?>
                      </p>
                      <p class="announcement-time">
                        <?php echo date('g:i A', strtotime($announcement['time'])); ?>
                      </p>
                    </div>
                  </div>
                  <p class="announcement-author">
                    Posted by: <span class="announcement-department">
                      <?php echo htmlspecialchars($announcement['officerName']); ?> | 
                      <?php echo htmlspecialchars($announcement['department'] ?: 'General'); ?>
                    </span>
                  </p>
                  <p class="announcement-content">
                    <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                  </p>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </section>
      </main>
    </div>

    <script>
      // Auto-refresh announcements every 30 seconds
      setInterval(function() {
        location.reload();
      }, 30000);
    </script>
  </body>
</html>