<?php
require_once '../auth_check.php';
check_auth('officer');
$current_user = get_user_info();

require_once '../database/db.php';
require_once '../database/helpers.php';

// Get officer information
$officer_id = $current_user['id'];
$officer_department = $current_user['department'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_message':
                $student_id = intval($_POST['student_id']);
                $message = trim($_POST['message']);
                
                if (!empty($message) && $student_id > 0) {
                    if (insert_chat_message($officer_id, $student_id, $message, 'officer')) {
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Failed to send message']);
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid message or student ID']);
                }
                exit;
                
            case 'get_messages':
                $student_id = intval($_POST['student_id']);
                if ($student_id > 0) {
                    $messages = get_chat_messages($student_id, $officer_id);
                    echo json_encode(['success' => true, 'messages' => $messages]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid student ID']);
                }
                exit;
                
            case 'get_chat_list':
                $chat_list = get_officer_chat_list($officer_id, $officer_department);
                echo json_encode(['success' => true, 'chats' => $chat_list]);
                exit;
        }
    }
}

// Get initial chat list for the officer's department
$chat_list = get_officer_chat_list($officer_id, $officer_department);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Hub - CampusConnect</title>
    <link rel="stylesheet" href="../assets/css/office_chat.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="header-section">
            <h1>Chat Support Hub</h1>
            <div class="header-buttons">
                <a href="office_dashboard.php" class="back-btn">← Back to Dashboard</a>
                <button class="refresh-btn" onclick="refreshChats()">🔄 Refresh</button>
            </div>
        </div>
    </div>

    <div class="chat-hub-container">
        <!-- Left Sidebar - Student Chat List -->
        <div class="chat-list-sidebar">
            <div class="sidebar-header">
                <h3>Student Conversations</h3>
                <span class="chat-count" id="chat-count"><?php echo count($chat_list); ?> active chats</span>
            </div>
            
            <div class="chat-list" id="chat-list">
                <?php if (empty($chat_list)): ?>
                    <div class="no-chats">
                        <p>No student conversations yet.</p>
                        <p>Students from your department can start chatting with you.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($chat_list as $index => $chat): ?>
                        <div class="chat-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                             data-student-id="<?php echo $chat['studentID']; ?>" 
                             onclick="selectChat(<?php echo $chat['studentID']; ?>)">
                            <div class="student-avatar">
                                <?php echo strtoupper(substr($chat['name'], 0, 2)); ?>
                            </div>
                            <div class="chat-preview">
                                <div class="student-name"><?php echo htmlspecialchars($chat['name']); ?></div>
                                <div class="student-info">
                                    <?php echo htmlspecialchars($chat['department']); ?> • 
                                    <?php echo htmlspecialchars($chat['email']); ?>
                                </div>
                                <div class="last-message">
                                    <?php 
                                    $preview = strlen($chat['last_message']) > 50 
                                        ? substr($chat['last_message'], 0, 50) . '...' 
                                        : $chat['last_message'];
                                    echo htmlspecialchars($preview); 
                                    ?>
                                </div>
                                <div class="message-time">
                                    <?php 
                                    $messageDate = new DateTime($chat['last_date'] . ' ' . $chat['last_time']);
                                    $now = new DateTime();
                                    $diff = $now->diff($messageDate);
                                    
                                    if ($diff->days == 0) {
                                        if ($diff->h == 0 && $diff->i < 5) {
                                            echo "Just now";
                                        } elseif ($diff->h == 0) {
                                            echo $diff->i . " minutes ago";
                                        } else {
                                            echo $diff->h . " hours ago";
                                        }
                                    } else {
                                        echo $messageDate->format('M j');
                                    }
                                    ?>
                                </div>
                                <?php if ($chat['unread_count'] > 0): ?>
                                    <div class="unread-indicator"><?php echo $chat['unread_count']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Side - Chat Window -->
        <div class="chat-window-container">
            <?php if (!empty($chat_list)): ?>
                <div class="chat-window" id="chat-window">
                    <div class="chat-header" id="chat-header">
                        <div class="selected-student-info">
                            <div class="student-avatar-large" id="student-avatar-large">
                                <?php echo strtoupper(substr($chat_list[0]['name'], 0, 2)); ?>
                            </div>
                            <div class="student-details">
                                <div class="student-name-large" id="student-name-large">
                                    <?php echo htmlspecialchars($chat_list[0]['name']); ?>
                                </div>
                                <div class="student-email" id="student-email">
                                    <?php echo htmlspecialchars($chat_list[0]['email']); ?>
                                </div>
                                <div class="student-status">● Available</div>
                            </div>
                        </div>
                    </div>

                    <div class="messages" id="messages">
                        <!-- Messages will be loaded via JavaScript -->
                    </div>

                    <form class="chat-form" id="chat-form" onsubmit="sendMessage(event)">
                        <input type="hidden" id="selected-student-id" value="<?php echo !empty($chat_list) ? $chat_list[0]['studentID'] : ''; ?>">
                        <input
                            id="chat-input"
                            type="text"
                            placeholder="Type your response..."
                            autocomplete="off"
                            required
                            maxlength="1000"
                        />
                        <button type="submit">Send</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="no-chat-selected" id="no-chat-selected">
                    <div class="no-chat-message">
                        <h3>No Active Conversations</h3>
                        <p>Students from your department (<?php echo htmlspecialchars($officer_department); ?>) can start conversations with you.</p>
                        <p>When they send messages, they will appear here.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let currentStudentId = <?php echo !empty($chat_list) ? $chat_list[0]['studentID'] : 'null'; ?>;

        function selectChat(studentId) {
            // Remove active class from all chat items
            document.querySelectorAll('.chat-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to selected chat
            const selectedItem = document.querySelector(`[data-student-id="${studentId}"]`);
            if (selectedItem) {
                selectedItem.classList.add('active');
                
                // Update student info in header
                const avatar = selectedItem.querySelector('.student-avatar').textContent;
                const name = selectedItem.querySelector('.student-name').textContent;
                const email = selectedItem.querySelector('.student-info').textContent.split(' • ')[1];
                
                document.getElementById('student-avatar-large').textContent = avatar;
                document.getElementById('student-name-large').textContent = name;
                document.getElementById('student-email').textContent = email;
            }
            
            // Update current student ID
            currentStudentId = studentId;
            document.getElementById('selected-student-id').value = studentId;
            
            // Load chat history
            loadChatHistory(studentId);
            
            // Show chat window, hide no-chat-selected
            document.getElementById('chat-window').style.display = 'flex';
            const noChat = document.getElementById('no-chat-selected');
            if (noChat) noChat.style.display = 'none';
        }

        function loadChatHistory(studentId) {
            fetch('office_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_messages&student_id=${studentId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayMessages(data.messages);
                } else {
                    console.error('Error loading messages:', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function displayMessages(messages) {
            const messagesDiv = document.getElementById('messages');
            messagesDiv.innerHTML = '';

            if (messages.length === 0) {
                messagesDiv.innerHTML = '<div class="welcome-message" style="text-align: center; color: #666; padding: 20px;"><p>No messages yet. Start the conversation!</p></div>';
                return;
            }

            messages.forEach(msg => {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${msg.sender_type === 'officer' ? 'rep' : 'student'}`;
                
                const messageDate = new Date(msg.date + ' ' + msg.time);
                const timeString = messageDate.toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });

                messageDiv.innerHTML = `
                    <div class="message-content">
                        <span class="text">${msg.message.replace(/\n/g, '<br>')}</span>
                        <span class="time">${timeString}</span>
                    </div>
                `;
                
                messagesDiv.appendChild(messageDiv);
            });

            // Scroll to bottom
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }

        function sendMessage(event) {
            event.preventDefault();
            
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            const studentId = document.getElementById('selected-student-id').value;
            
            if (!message || !studentId) return;

            // Send message via AJAX
            fetch('office_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=send_message&student_id=${studentId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    // Reload messages to show the new message
                    loadChatHistory(studentId);
                    // Update chat list to show latest message
                    refreshChatList();
                } else {
                    alert('Error sending message: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error sending message. Please try again.');
            });
        }

        function refreshChats() {
            refreshChatList();
            if (currentStudentId) {
                loadChatHistory(currentStudentId);
            }
        }

        function refreshChatList() {
            fetch('office_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_chat_list'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update chat count
                    document.getElementById('chat-count').textContent = data.chats.length + ' active chats';
                    // Could update the entire chat list here if needed
                }
            })
            .catch(error => {
                console.error('Error refreshing chat list:', error);
            });
        }

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            if (currentStudentId) {
                loadChatHistory(currentStudentId);
            }
        });

        // Auto-refresh every 10 seconds
        setInterval(function() {
            const input = document.getElementById('chat-input');
            if (document.activeElement !== input || input.value === '') {
                refreshChats();
            }
        }, 10000);
    </script>
</body>
</html>