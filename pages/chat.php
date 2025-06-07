<?php
require_once '../auth_check.php';
check_auth('student');
$current_user = get_user_info();

// Include database connection
require_once '../database/db.php';

// Get department representative
$department = $current_user['department'];
$rep_sql = "SELECT * FROM officer WHERE department = ? AND isRepresentative = 1 LIMIT 1";
$rep_stmt = $conn->prepare($rep_sql);
$rep_stmt->bind_param("s", $department);
$rep_stmt->execute();
$rep_result = $rep_stmt->get_result();
$representative = $rep_result->fetch_assoc();

// If no representative found, show error message
if (!$representative) {
  $error_message = "No department representative found for your department.";
}

// Fetch chat messages between student and representative
$messages = [];
if ($representative) {
  // Updated query using the new sender_type column
  $chat_sql = "SELECT c.*, s.name as studentName, o.name as officerName,
                 CASE 
                     WHEN c.sender_type = 'student' THEN s.name
                     WHEN c.sender_type = 'officer' THEN o.name
                 END as senderName
                 FROM chat c 
                 JOIN student s ON c.studentID = s.studentID 
                 JOIN officer o ON c.officerID = o.officerID 
                 WHERE c.studentID = ? AND c.officerID = ?
                 ORDER BY c.date ASC, c.time ASC";

  $chat_stmt = $conn->prepare($chat_sql);
  $chat_stmt->bind_param("ii", $current_user['id'], $representative['officerID']);
  $chat_stmt->execute();
  $chat_result = $chat_stmt->get_result();
  $messages = $chat_result->fetch_all(MYSQLI_ASSOC);
}

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $representative) {
  $message = trim($_POST['message']);
  if (!empty($message)) {
    // Updated INSERT to include sender_type
    $insert_sql = "INSERT INTO chat (officerID, studentID, date, time, message, sender_type) VALUES (?, ?, CURDATE(), CURTIME(), ?, 'student')";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iis", $representative['officerID'], $current_user['id'], $message);

    if ($insert_stmt->execute()) {
      // Redirect to prevent form resubmission
      header("Location: chat.php");
      exit();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Chat Support - CampusConnect</title>
  <link rel="stylesheet" href="../assets/css/chat.css">
</head>

<body>
  <header>
    <div class="logo-title">
      <img
        src="https://static.vecteezy.com/system/resources/previews/032/414/564/original/3d-rendering-of-speech-bubble-3d-pastel-chat-with-question-mark-icon-set-png.png"
        alt="Logo" class="logo" />
      <span class="app-title">Campus Connect</span>
    </div>
  </header>

  <div class="dashboard-container">
    <aside class="sidebar">
      <ul>
        <li><a href="student_dashboard.php">🏠 Project Overview</a></li>
        <li><a href="announcement.php">📢 Announcements</a></li>
        <li class="active"><a href="chat.php">💬 Chat Support</a></li>
        <li><a href="manage-account.php">👤 My Account</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
      </ul>
    </aside>

    <main class="content">
      <section id="chat" class="section">
        <?php if (isset($error_message)): ?>
          <div class="error-message">
            <?php echo $error_message; ?>
          </div>
        <?php else: ?>
          <!-- Department Representative Info Card -->
          <div class="rep-info-card">
            <h3>Your Department Representative</h3>
            <div class="rep-details">
              <div class="rep-item">
                <span class="rep-label">Name:</span>
                <span class="rep-value"><?php echo htmlspecialchars($representative['name']); ?></span>
              </div>
              <div class="rep-item">
                <span class="rep-label">Email:</span>
                <span class="rep-value"><?php echo htmlspecialchars($representative['email']); ?></span>
              </div>
              <div class="rep-item">
                <span class="rep-label">Department:</span>
                <span class="rep-value"><?php echo htmlspecialchars($representative['department']); ?></span>
              </div>
            </div>
          </div>

          <div class="chat-window">
            <div class="chat-header">
              <span class="chat-title">Chat with Your Department Rep</span>
              <span class="online-status">● Available</span>
            </div>
            <div id="messages" class="messages">
              <?php if (empty($messages)): ?>
                <div class="welcome-message">
                  <p>Start a conversation with your department representative!</p>
                </div>
              <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                  <div class="message <?php echo $msg['sender_type'] === 'student' ? 'me' : 'other'; ?>">
                    <div class="message-header">
                      <span class="sender"><?php echo htmlspecialchars($msg['senderName']); ?></span>
                      <span class="timestamp">
                        <?php echo date('M j, Y g:i A', strtotime($msg['date'] . ' ' . $msg['time'])); ?>
                      </span>
                    </div>
                    <span class="text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <form method="POST" class="chat-form">
              <input name="message" type="text" placeholder="Type your message..." autocomplete="off" required
                maxlength="1000" />
              <button type="submit">Send</button>
            </form>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script>
    // Auto-scroll to bottom of messages
    function scrollToBottom() {
      const messagesDiv = document.getElementById('messages');
      messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    // Scroll to bottom on page load
    window.onload = function () {
      scrollToBottom();
    };

    // Auto-refresh messages every 10 seconds
    setInterval(function () {
      // Only refresh if user hasn't typed anything recently
      const input = document.querySelector('input[name="message"]');
      if (document.activeElement !== input || input.value === '') {
        location.reload();
      }
    }, 10000);
  </script>
</body>

</html>