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
<style>
    /* Search Container Styles */
    .search-container {
        padding: 1rem 1.5rem;
        background: white;
        border-bottom: 1px solid #e9ecef;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .search-box {
        position: relative;
        display: flex;
        align-items: center;
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .search-box:hover {
        border-color: #f76c2f;
        box-shadow: 0 4px 12px rgba(247, 108, 47, 0.1);
    }

    .search-box:focus-within {
        border-color: #f76c2f;
        background: white;
        box-shadow: 0 0 0 3px rgba(247, 108, 47, 0.1);
        transform: translateY(-1px);
    }

    .search-icon {
        color: #6c757d;
        margin-right: 0.75rem;
        flex-shrink: 0;
        transition: color 0.3s ease;
    }

    .search-box:focus-within .search-icon {
        color: #f76c2f;
    }

    #student-search {
        flex: 1;
        border: none;
        background: transparent;
        font-size: 0.95rem;
        color: #333;
        outline: none;
        padding: 0;
        font-family: inherit;
    }

    #student-search::placeholder {
        color: #6c757d;
        font-style: italic;
    }

    #student-search:focus {
        outline: none;
    }

    .clear-search {
        background: none;
        border: none;
        color: #6c757d;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 50%;
        margin-left: 0.5rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        flex-shrink: 0;
    }

    .clear-search:hover {
        background: rgba(247, 108, 47, 0.1);
        color: #f76c2f;
        transform: scale(1.1);
    }

    .clear-search:active {
        transform: scale(0.95);
    }

    /* Search Highlight Styles */
    .search-highlight {
        background: linear-gradient(135deg, #f76c2f, #fdae6b);
        color: white;
        padding: 0.1rem 0.3rem;
        border-radius: 4px;
        font-weight: 600;
        box-shadow: 0 1px 3px rgba(247, 108, 47, 0.3);
        animation: highlightPulse 0.5s ease-out;
    }

    @keyframes highlightPulse {
        0% {
            background: linear-gradient(135deg, #f76c2f, #fdae6b);
            transform: scale(1);
        }

        50% {
            background: linear-gradient(135deg, #fdae6b, #f76c2f);
            transform: scale(1.05);
        }

        100% {
            background: linear-gradient(135deg, #f76c2f, #fdae6b);
            transform: scale(1);
        }
    }

    /* Search Results State */
    .search-active .chat-item {
        transition: all 0.3s ease;
    }

    .search-active .chat-item:not([style*="display: flex"]) {
        opacity: 0;
        transform: translateX(-10px);
    }

    /* Empty Search Results */
    .no-search-results {
        padding: 2rem 1.5rem;
        text-align: center;
        color: #6c757d;
        font-style: italic;
    }

    .no-search-results .search-icon-large {
        width: 48px;
        height: 48px;
        color: #dee2e6;
        margin: 0 auto 1rem;
        opacity: 0.5;
    }

    /* Search Animation */
    .search-box::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, transparent, rgba(247, 108, 47, 0.1), transparent);
        border-radius: 10px;
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }

    .search-box:focus-within::before {
        opacity: 1;
        animation: searchShimmer 2s ease-in-out infinite;
    }

    @keyframes searchShimmer {
        0% {
            transform: translateX(-100%);
        }

        100% {
            transform: translateX(100%);
        }
    }

    /* Responsive Search Styles */
    @media (max-width: 768px) {
        .search-container {
            padding: 0.75rem 1rem;
        }

        .search-box {
            padding: 0.6rem 0.8rem;
            border-radius: 10px;
        }

        .search-icon {
            margin-right: 0.5rem;
            width: 14px;
            height: 14px;
        }

        #student-search {
            font-size: 0.9rem;
        }

        .clear-search {
            width: 24px;
            height: 24px;
            margin-left: 0.25rem;
        }

        .search-highlight {
            padding: 0.05rem 0.2rem;
            border-radius: 3px;
            font-size: 0.9rem;
        }
    }

    @media (max-width: 480px) {
        .search-container {
            padding: 0.5rem 0.8rem;
        }

        .search-box {
            padding: 0.5rem 0.7rem;
            border-radius: 8px;
        }

        #student-search {
            font-size: 0.85rem;
        }

        .search-icon {
            width: 12px;
            height: 12px;
        }

        .clear-search {
            width: 20px;
            height: 20px;
        }
    }

    /* Search Results Counter */
    .search-results-info {
        padding: 0.5rem 1.5rem;
        background: rgba(247, 108, 47, 0.05);
        border-bottom: 1px solid #e9ecef;
        font-size: 0.85rem;
        color: #6c757d;
        font-style: italic;
        display: none;
    }

    .search-results-info.active {
        display: block;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Enhanced Chat Item Styles for Search */
    .chat-item {
        position: relative;
        overflow: hidden;
    }

    .chat-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(247, 108, 47, 0.1), transparent);
        transition: left 0.5s ease;
        pointer-events: none;
    }

    .chat-item:hover::before {
        left: 100%;
    }

    /* Search Input Focus Effects */
    #student-search:focus {
        font-weight: 500;
    }

    /* Improved No Results State */
    .search-no-results {
        padding: 3rem 1.5rem;
        text-align: center;
        color: #6c757d;
        display: none;
    }

    .search-no-results.show {
        display: block;
        animation: fadeInUp 0.4s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .search-no-results .no-results-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1rem;
        opacity: 0.3;
    }

    .search-no-results h4 {
        color: #495057;
        margin: 0 0 0.5rem 0;
        font-size: 1.1rem;
    }

    .search-no-results p {
        margin: 0;
        font-size: 0.9rem;
        line-height: 1.4;
    }
</style>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Hub - CampusConnect</title>
    <link rel="stylesheet" href="../assets/css/office_chat.css">
</head>

<body>
    <!-- Header -->
    <header>
        <div class="logo-title">
            <img src="https://static.vecteezy.com/system/resources/previews/032/414/564/original/3d-rendering-of-speech-bubble-3d-pastel-chat-with-question-mark-icon-set-png.png"
                alt="Logo" class="logo" />
            <span class="app-title">CampusConnect</span>
        </div>
        <div class="header-actions">
            <a href="office_dashboard.php" class="back-btn">← Back to Dashboard</a>
            <button class="refresh-btn" onclick="refreshChats()">🔄 Refresh</button>
        </div>
    </header>

    <!-- Main Dashboard Container -->
    <div class="dashboard-container">
        <!-- Page Title Section -->
        <div class="page-header">
            <h1>Chat Support Hub</h1>
            <span class="chat-count" id="chat-count"><?php echo count($chat_list); ?> active chats</span>
        </div>

        <!-- Chat Hub Container -->
        <div class="chat-hub-container">
            <!-- Left Sidebar - Student Chat List -->
            <div class="chat-list-sidebar">
                <div class="sidebar-header">
                    <h3>Student Conversations</h3>
                </div>

                <!-- Search Bar -->
                <div class="search-container">
                    <div class="search-box">
                        <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" id="student-search" placeholder="Search students..." autocomplete="off"
                            oninput="searchStudents(this.value)">
                        <button class="clear-search" id="clear-search" onclick="clearSearch()" style="display: none;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
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
                                data-student-name="<?php echo strtolower(htmlspecialchars($chat['name'])); ?>"
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
                            <input type="hidden" id="selected-student-id"
                                value="<?php echo !empty($chat_list) ? $chat_list[0]['studentID'] : ''; ?>">
                            <input id="chat-input" type="text" placeholder="Type your response..." autocomplete="off"
                                required maxlength="1000" />
                            <button type="submit">Send</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="no-chat-selected" id="no-chat-selected">
                        <div class="no-chat-message">
                            <h3>No Active Conversations</h3>
                            <p>Students from your department (<?php echo htmlspecialchars($officer_department); ?>) can
                                start conversations with you.</p>
                            <p>When they send messages, they will appear here.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
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
                messageDiv.className = `message ${msg.sender_type === 'officer' ? 'me' : 'other'}`;

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
                    <div class="message-header">
                        <span class="sender">${msg.sender_type === 'officer' ? 'You' : 'Student'}</span>
                        <span class="timestamp">${timeString}</span>
                    </div>
                    <span class="text">${msg.message.replace(/\n/g, '<br>')}</span>
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

        // Enhanced search functionality
        function searchStudents(query) {
            const chatItems = document.querySelectorAll('.chat-item');
            const clearBtn = document.getElementById('clear-search');
            const chatList = document.getElementById('chat-list');

            // Show/hide clear button with smooth transition
            if (query.trim() !== '') {
                clearBtn.style.display = 'flex';
                clearBtn.style.opacity = '0';
                setTimeout(() => {
                    clearBtn.style.opacity = '1';
                }, 50);
            } else {
                clearBtn.style.opacity = '0';
                setTimeout(() => {
                    clearBtn.style.display = 'none';
                }, 200);
            }

            const searchTerm = query.toLowerCase().trim();
            let visibleCount = 0;
            let hasResults = false;

            // Add search active class for styling
            if (searchTerm !== '') {
                chatList.classList.add('search-active');
            } else {
                chatList.classList.remove('search-active');
            }

            chatItems.forEach((item, index) => {
                const studentName = item.getAttribute('data-student-name');
                const nameElement = item.querySelector('.student-name');
                const emailElement = item.querySelector('.student-info');
                const originalName = nameElement.getAttribute('data-original-name') || nameElement.textContent;
                const originalEmail = emailElement.getAttribute('data-original-email') || emailElement.textContent;

                // Store original content if not already stored
                if (!nameElement.getAttribute('data-original-name')) {
                    nameElement.setAttribute('data-original-name', originalName);
                }
                if (!emailElement.getAttribute('data-original-email')) {
                    emailElement.setAttribute('data-original-email', originalEmail);
                }

                if (searchTerm === '') {
                    // Show all items and remove highlights
                    showChatItem(item, index);
                    nameElement.innerHTML = originalName;
                    emailElement.innerHTML = originalEmail;
                    visibleCount++;
                } else {
                    // Check if search term matches name or email
                    const nameMatch = studentName.includes(searchTerm);
                    const emailMatch = originalEmail.toLowerCase().includes(searchTerm);

                    if (nameMatch || emailMatch) {
                        // Show matching items and highlight the search term
                        showChatItem(item, index);
                        hasResults = true;
                        visibleCount++;

                        // Highlight matching text in name
                        if (nameMatch) {
                            const nameRegex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
                            const highlightedName = originalName.replace(nameRegex, '<mark class="search-highlight">$1</mark>');
                            nameElement.innerHTML = highlightedName;
                        } else {
                            nameElement.innerHTML = originalName;
                        }

                        // Highlight matching text in email
                        if (emailMatch) {
                            const emailRegex = new RegExp(`(${escapeRegExp(searchTerm)})`, 'gi');
                            const highlightedEmail = originalEmail.replace(emailRegex, '<mark class="search-highlight">$1</mark>');
                            emailElement.innerHTML = highlightedEmail;
                        } else {
                            emailElement.innerHTML = originalEmail;
                        }
                    } else {
                        // Hide non-matching items
                        hideChatItem(item);
                        nameElement.innerHTML = originalName;
                        emailElement.innerHTML = originalEmail;
                    }
                }
            });

            // Show/hide no results message
            updateSearchResults(searchTerm, visibleCount, hasResults);
        }

        function showChatItem(item, index) {
            item.style.display = 'flex';
            item.style.opacity = '0';
            item.style.transform = 'translateX(-10px)';

            // Stagger the animation for a smooth reveal effect
            setTimeout(() => {
                item.style.transition = 'all 0.3s ease';
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
            }, index * 50);
        }

        function hideChatItem(item) {
            item.style.transition = 'all 0.3s ease';
            item.style.opacity = '0';
            item.style.transform = 'translateX(-10px)';

            setTimeout(() => {
                item.style.display = 'none';
            }, 300);
        }

        function updateSearchResults(searchTerm, visibleCount, hasResults) {
            // Remove existing no results message
            const existingNoResults = document.querySelector('.search-no-results');
            if (existingNoResults) {
                existingNoResults.remove();
            }

            // If searching and no results, show no results message
            if (searchTerm !== '' && !hasResults) {
                const chatList = document.getElementById('chat-list');
                const noResultsDiv = document.createElement('div');
                noResultsDiv.className = 'search-no-results show';
                noResultsDiv.innerHTML = `
            <svg class="no-results-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
                <line x1="11" y1="8" x2="11" y2="12"></line>
                <line x1="11" y1="16" x2="11.01" y2="16"></line>
            </svg>
            <h4>No students found</h4>
            <p>Try searching with a different name or email address</p>
        `;
                chatList.appendChild(noResultsDiv);
            }
        }

        function clearSearch() {
            const searchInput = document.getElementById('student-search');
            const clearBtn = document.getElementById('clear-search');

            // Animate the clear action
            clearBtn.style.transform = 'rotate(90deg) scale(0.8)';
            setTimeout(() => {
                clearBtn.style.transform = 'rotate(0deg) scale(1)';
            }, 150);

            searchInput.value = '';
            searchStudents('');
            searchInput.focus();

            // Add a subtle shake animation to the search box
            const searchBox = document.querySelector('.search-box');
            searchBox.style.animation = 'shake 0.3s ease-in-out';
            setTimeout(() => {
                searchBox.style.animation = '';
            }, 300);
        }

        // Add shake animation keyframes dynamically
        const shakeKeyframes = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-2px); }
        75% { transform: translateX(2px); }
    }
`;

        // Add the keyframes to the document
        const style = document.createElement('style');
        style.textContent = shakeKeyframes;
        document.head.appendChild(style);

        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        // Enhanced keyboard shortcuts for search
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('student-search');

            // Add keyboard shortcuts
            document.addEventListener('keydown', function (e) {
                // Ctrl/Cmd + F to focus search
                if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }

                // Escape to clear search
                if (e.key === 'Escape' && document.activeElement === searchInput) {
                    clearSearch();
                }
            });

            // Add search input enhancements
            searchInput.addEventListener('input', function (e) {
                const query = e.target.value;
                searchStudents(query);

                // Add typing indicator effect
                const searchBox = e.target.closest('.search-box');
                searchBox.classList.add('typing');
                clearTimeout(searchBox.typingTimeout);
                searchBox.typingTimeout = setTimeout(() => {
                    searchBox.classList.remove('typing');
                }, 500);
            });

            // Add focus/blur effects
            searchInput.addEventListener('focus', function () {
                this.parentElement.classList.add('focused');
            });

            searchInput.addEventListener('blur', function () {
                this.parentElement.classList.remove('focused');
            });
        });

        // Add CSS for typing indicator
        const typingCSS = `
    .search-box.typing {
        border-color: #f76c2f;
        box-shadow: 0 0 0 3px rgba(247, 108, 47, 0.1);
    }
    
    .search-box.typing .search-icon {
        animation: searchPulse 1s ease-in-out infinite;
    }
    
    @keyframes searchPulse {
        0%, 100% { opacity: 0.5; }
        50% { opacity: 1; }
    }
`;

        const typingStyle = document.createElement('style');
        typingStyle.textContent = typingCSS;
        document.head.appendChild(typingStyle);

        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function () {
            if (currentStudentId) {
                loadChatHistory(currentStudentId);
            }
        });

        // Auto-refresh every 10 seconds
        setInterval(function () {
            const input = document.getElementById('chat-input');
            const searchInput = document.getElementById('student-search');
            if (document.activeElement !== input && document.activeElement !== searchInput ||
                (input.value === '' && searchInput.value === '')) {
                refreshChats();
            }
        }, 10000);
    </script>
</body>

</html>