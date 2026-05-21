<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Get all conversations
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            u.user_id,
            u.first_name,
            u.last_name,
            u.profile_picture,
            u.role,
            (SELECT message 
             FROM messages 
             WHERE (sender_id = ? AND receiver_id = u.user_id) 
                OR (sender_id = u.user_id AND receiver_id = ?)
             ORDER BY created_at DESC 
             LIMIT 1) as last_message,
            (SELECT created_at 
             FROM messages 
             WHERE (sender_id = ? AND receiver_id = u.user_id) 
                OR (sender_id = u.user_id AND receiver_id = ?)
             ORDER BY created_at DESC 
             LIMIT 1) as last_message_time,
            (SELECT COUNT(*) 
             FROM messages 
             WHERE sender_id = u.user_id 
                AND receiver_id = ? 
                AND is_read = 0) as unread_count
        FROM messages m
        JOIN users u ON (m.sender_id = u.user_id AND m.receiver_id = ?)
                      OR (m.receiver_id = u.user_id AND m.sender_id = ?)
        WHERE m.sender_id = ? OR m.receiver_id = ?
        GROUP BY u.user_id
        ORDER BY last_message_time DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    $conversations = $stmt->fetchAll();
    
    // Get messages for selected conversation
    $selected_user_id = isset($_GET['user']) ? (int)$_GET['user'] : null;
    $messages = [];
    $selected_user = null;
    
    if ($selected_user_id) {
        // Get selected user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$selected_user_id]);
        $selected_user = $stmt->fetch();
        
        if ($selected_user) {
            $stmt = $conn->prepare("
                SELECT m.*, 
                       u.first_name, u.last_name, u.profile_picture
                FROM messages m
                JOIN users u ON m.sender_id = u.user_id
                WHERE (m.sender_id = ? AND m.receiver_id = ?)
                   OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([$user_id, $selected_user_id, $selected_user_id, $user_id]);
            $messages = $stmt->fetchAll();
            
            // Mark messages as read
            $stmt = $conn->prepare("
                UPDATE messages 
                SET is_read = 1 
                WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
            ");
            $stmt->execute([$selected_user_id, $user_id]);
        }
    }
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && $selected_user_id) {
    try {
        $message = sanitize_input($_POST['message']);
        $attachment_url = null;
        
        // Handle file upload if present
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $file = $_FILES['attachment'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_error = $file['error'];
            
            // Get file extension
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Allowed file types
            $allowed = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png');
            
            if (in_array($file_ext, $allowed)) {
                if ($file_error === 0) {
                    if ($file_size <= MAX_FILE_SIZE) {
                        // Generate unique filename
                        $file_new_name = uniqid('message_') . '.' . $file_ext;
                        $file_destination = UPLOAD_PATH . $file_new_name;
                        
                        if (move_uploaded_file($file_tmp, $file_destination)) {
                            $attachment_url = $file_new_name;
                        }
                    }
                }
            }
        }
        
        // Insert message
        $stmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, message, attachment_url)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $selected_user_id, $message, $attachment_url]);
        
        // Redirect to refresh the page
        header("Location: messages.php?user=" . $selected_user_id);
        exit();
        
    } catch(PDOException $e) {
        $error = "Error sending message. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Mentor Platform</title>
    <link rel="icon" type="image/png" href="assets/images/Favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#362C7A',
                        secondary: '#735CC6',
                        tertiary: '#C6B6F7',
                        background: '#F9F5ED',
                        white: '#FFFFFF',
                        'message-sent': '#362C7A',
                        'message-received': '#F9F5ED'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-in': 'slideIn 0.5s ease-out'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideIn: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .hover-scale {
            transition: transform 0.3s ease;
        }
        .hover-scale:hover {
            transform: scale(1.02);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-background font-['Segoe_UI',_Tahoma,_Geneva,_Verdana,_sans-serif] h-screen overflow-hidden">
    <div class="flex h-screen animate-fade-in">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg py-5 flex-shrink-0 border-r border-tertiary overflow-y-auto">
            <div class="text-center mb-4">
                <img src="<?php echo $user['profile_picture'] ? UPLOAD_URL . $user['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                     alt="Profile Picture" 
                     class="w-[100px] h-[100px] rounded-full object-cover mx-auto mb-4 border-[3px] border-secondary shadow-lg transition-all duration-300 hover:scale-110 hover:border-primary">
                <h5 class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                <p class="text-secondary"><?php echo ucfirst($role); ?></p>
            </div>
            
            <nav class="space-y-1 px-3">
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="dashboard.php">
                    <i class="fas fa-home w-5 mr-2.5"></i> Dashboard
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="profile.php">
                    <i class="fas fa-user w-5 mr-2.5"></i> Profile
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary bg-tertiary" href="messages.php">
                    <i class="fas fa-comments w-5 mr-2.5"></i> Messages
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="sessions.php">
                    <i class="fas fa-calendar w-5 mr-2.5"></i> Sessions
                </a>
                <?php if ($role == 'mentor'): ?>
                    <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="resources.php">
                        <i class="fas fa-book w-5 mr-2.5"></i> Resources
                    </a>
                    <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="earnings.php">
                        <i class="fas fa-dollar-sign w-5 mr-2.5"></i> Earnings
                    </a>
                    <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="analytics.php">
                        <i class="fas fa-chart-line w-5 mr-2.5"></i> Analytics
                    </a>
                <?php else: ?>
                    <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="goals.php">
                        <i class="fas fa-bullseye w-5 mr-2.5"></i> Goals
                    </a>
                    <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="mentee-resources.php">
                        <i class="fas fa-book w-5 mr-2.5"></i> Resources
                    </a>
                    <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="find-mentor.php">
                        <i class="fas fa-search w-5 mr-2.5"></i> Find Mentor
                    </a>
                <?php endif; ?>
                <a class="flex items-center px-5 py-2.5 rounded-md text-white bg-red-500 transition-all duration-300 hover:bg-red-600" href="logout.php">
                    <i class="fas fa-sign-out-alt w-5 mr-2.5"></i> Logout
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col animate-slide-in">
            <!-- Conversations List -->
            <div class="flex h-full">
                <div class="w-80 bg-white border-r border-tertiary overflow-y-auto">
                    <div class="p-4 border-b border-tertiary">
                        <h2 class="text-lg font-semibold text-primary">Messages</h2>
                    </div>
                    
                    <div class="divide-y divide-tertiary">
                        <?php foreach ($conversations as $conv): ?>
                            <a href="?user=<?php echo $conv['user_id']; ?>" 
                               class="flex items-center p-4 hover:bg-background transition-all duration-300 <?php echo $selected_user_id == $conv['user_id'] ? 'bg-tertiary/30' : ''; ?>">
                                <img src="<?php echo $conv['profile_picture'] ? UPLOAD_URL . $conv['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                                     alt="Avatar" 
                                     class="w-12 h-12 rounded-full object-cover">
                                <div class="ml-4 flex-1">
                                    <div class="flex justify-between items-start">
                                        <h6 class="font-semibold text-primary"><?php echo htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']); ?></h6>
                                        <span class="text-xs text-secondary">
                                            <?php echo $conv['last_message_time'] ? date('g:i a', strtotime($conv['last_message_time'])) : ''; ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-secondary truncate"><?php echo htmlspecialchars($conv['last_message'] ?? ''); ?></p>
                                    <?php if ($conv['unread_count'] > 0): ?>
                                        <span class="inline-block bg-primary text-white text-xs rounded-full px-2 py-0.5 mt-1">
                                            <?php echo $conv['unread_count']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Chat Area -->
                <div class="flex-1 flex flex-col bg-white">
                    <?php if ($selected_user): ?>
                        <div class="flex flex-col h-full">
                            <!-- Chat Header -->
                            <div class="p-4 border-b border-tertiary flex items-center">
                                <img src="<?php echo $selected_user['profile_picture'] ? UPLOAD_URL . $selected_user['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                                     alt="Avatar" 
                                     class="w-12 h-12 rounded-full object-cover mr-4">
                                <div>
                                    <h6 class="font-semibold text-primary"><?php echo htmlspecialchars($selected_user['first_name'] . ' ' . $selected_user['last_name']); ?></h6>
                                    <p class="text-sm text-secondary"><?php echo ucfirst($selected_user['role']); ?></p>
                                </div>
                            </div>

                            <!-- Chat Messages -->
                            <div class="flex-1 p-5 overflow-y-auto min-h-0 bg-[url('data:image/svg+xml,%3Csvg width=\'20\' height=\'20\' viewBox=\'0 0 20 20\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'%23f9f9f9\' fill-opacity=\'1\' fill-rule=\'evenodd\'%3E%3Ccircle cx=\'3\' cy=\'3\' r=\'3\'/%3E%3Ccircle cx=\'13\' cy=\'13\' r=\'3\'/%3E%3C/g%3E%3C/svg%3E')]" id="chat-messages">
                                <?php foreach ($messages as $message): ?>
                                    <div class="flex items-end gap-2 mb-5 <?php echo $message['sender_id'] == $user_id ? 'flex-row-reverse' : ''; ?> <?php echo $message['sender_id'] == $user_id ? 'ml-auto' : ''; ?> max-w-[85%]">
                                        <img src="<?php echo $message['profile_picture'] ? UPLOAD_URL . $message['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                                             alt="Avatar" 
                                             class="w-8 h-8 rounded-full object-cover">
                                        <div class="<?php echo $message['sender_id'] == $user_id ? 'bg-primary text-white rounded-[20px] rounded-br-[4px]' : 'bg-tertiary/30 rounded-[20px] rounded-bl-[4px]'; ?> p-3">
                                            <?php echo htmlspecialchars($message['message']); ?>
                                            <?php if ($message['attachment_url']): ?>
                                                <div class="mt-2">
                                                    <?php
                                                    $ext = pathinfo($message['attachment_url'], PATHINFO_EXTENSION);
                                                    if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])): ?>
                                                        <img src="<?php echo UPLOAD_URL . $message['attachment_url']; ?>" 
                                                             alt="Attachment" 
                                                             class="max-w-[200px] rounded-lg">
                                                    <?php else: ?>
                                                        <a href="<?php echo UPLOAD_URL . $message['attachment_url']; ?>" 
                                                           target="_blank"
                                                           class="flex items-center gap-2 text-sm <?php echo $message['sender_id'] == $user_id ? 'text-white' : 'text-primary'; ?> hover:underline">
                                                            <i class="fas fa-file"></i>
                                                            Download Attachment
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <span class="block text-xs <?php echo $message['sender_id'] == $user_id ? 'text-white/80 text-right' : 'text-secondary'; ?> mt-1">
                                                <?php echo date('g:i a', strtotime($message['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Chat Input -->
                            <div class="p-4 border-t border-tertiary flex-shrink-0">
                                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                                    <div class="flex items-center gap-2 bg-background rounded-full p-1.5">
                                        <input type="text" 
                                               name="message" 
                                               class="flex-1 bg-transparent border-none focus:ring-0 px-4 py-2" 
                                               placeholder="Type your message..." 
                                               required>
                                        <label class="w-10 h-10 rounded-full bg-tertiary hover:bg-secondary/20 transition-all duration-300 flex items-center justify-center cursor-pointer">
                                            <i class="fas fa-paperclip text-primary"></i>
                                            <input type="file" name="attachment" class="hidden" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                        </label>
                                        <button type="submit" class="w-10 h-10 rounded-full bg-primary hover:bg-secondary transition-all duration-300 flex items-center justify-center text-white">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                    <div id="attachment-preview" class="hidden p-2.5 bg-background rounded-xl">
                                        <img src="" alt="Preview" class="max-w-[200px] max-h-[200px] rounded-xl">
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-xl shadow-lg h-full flex items-center justify-center">
                            <div class="text-center text-secondary">
                                <i class="fas fa-comments text-6xl mb-4"></i>
                                <p>Select a conversation to start messaging</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Preview attachment before upload
        document.querySelector('input[name="attachment"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('#attachment-preview img').src = e.target.result;
                    document.querySelector('#attachment-preview').classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                document.querySelector('#attachment-preview').classList.add('hidden');
            }
        });

        // Scroll to bottom of chat messages when page loads
        const chatMessages = document.getElementById('chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Scroll to bottom when new messages are added
        const observer = new MutationObserver(() => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });

        if (chatMessages) {
            observer.observe(chatMessages, {
                childList: true,
                subtree: true
            });
        }
    </script>
</body>
</html> 