<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    // Get user profile data
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Get role-specific data
    if ($role == 'mentor') {
        $stmt = $conn->prepare("
            SELECT mp.*, 
                   COUNT(DISTINCT s.session_id) as total_sessions,
                   COUNT(DISTINCT r.review_id) as total_reviews,
                   AVG(r.rating) as average_rating
            FROM mentor_profiles mp
            LEFT JOIN sessions s ON mp.mentor_id = s.mentor_id
            LEFT JOIN reviews r ON mp.mentor_id = r.mentor_id
            WHERE mp.mentor_id = ?
            GROUP BY mp.mentor_id
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT mp.*, 
                   COUNT(DISTINCT s.session_id) as total_sessions,
                   COUNT(DISTINCT g.goal_id) as total_goals,
                   COUNT(DISTINCT ra.resource_id) as total_resources
            FROM mentee_profiles mp
            LEFT JOIN sessions s ON mp.mentee_id = s.mentee_id
            LEFT JOIN goals g ON mp.mentee_id = g.mentee_id
            LEFT JOIN resource_access ra ON mp.mentee_id = ra.mentee_id
            WHERE mp.mentee_id = ?
            GROUP BY mp.mentee_id
        ");
    }
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
    // Get upcoming sessions
    $stmt = $conn->prepare("
        SELECT s.*, 
               u.first_name, u.last_name, u.email
        FROM sessions s
        JOIN users u ON (s.mentor_id = u.user_id AND ? = 'mentee') 
                      OR (s.mentee_id = u.user_id AND ? = 'mentor')
        WHERE (s.mentor_id = ? OR s.mentee_id = ?)
        AND s.status = 'scheduled'
        AND s.start_time >= NOW()
        ORDER BY s.start_time ASC
        LIMIT 5
    ");
    $stmt->execute([$role, $role, $user_id, $user_id]);
    $upcoming_sessions = $stmt->fetchAll();
    
    // Get recent messages
    $stmt = $conn->prepare("
        SELECT m.*, 
               u.first_name, u.last_name, u.email
        FROM messages m
        JOIN users u ON (m.sender_id = u.user_id AND m.receiver_id = ?)
                      OR (m.receiver_id = u.user_id AND m.sender_id = ?)
        WHERE m.sender_id = ? OR m.receiver_id = ?
        ORDER BY m.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
    $recent_messages = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mentor Platform</title>
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
                        white: '#FFFFFF'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-in': 'slideIn 0.5s ease-out',
                        'pulse-slow': 'pulse 3s infinite'
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
<body class="bg-background font-['Segoe_UI',_Tahoma,_Geneva,_Verdana,_sans-serif]">
    <div class="flex animate-fade-in">
        <!-- Sidebar -->
        <div class="w-64 bg-white min-h-screen shadow-lg py-5 flex-shrink-0">
            <div class="text-center mb-4">
                <img src="<?php echo $user['profile_picture'] ? UPLOAD_URL . $user['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                     alt="Profile Picture" 
                     class="w-[100px] h-[100px] rounded-full object-cover mx-auto mb-4 border-[3px] border-secondary shadow-lg transition-all duration-300 hover:scale-110 hover:border-primary">
                <h5 class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                <p class="text-secondary"><?php echo ucfirst($role); ?></p>
            </div>
            
            <nav class="space-y-1 px-3">
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary bg-tertiary" href="dashboard.php">
                    <i class="fas fa-home w-5 mr-2.5"></i> Dashboard
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="profile.php">
                    <i class="fas fa-user w-5 mr-2.5"></i> Profile
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="messages.php">
                    <i class="fas fa-envelope w-5 mr-2.5"></i> Messages
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
        <div class="flex-1 p-8 animate-slide-in">
            <!-- Profile Header -->
            <div class="bg-white rounded-xl shadow-lg p-5 mb-5 transition-all duration-300 hover:shadow-xl hover-scale">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold mb-2 text-primary">Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>
                        <p class="text-secondary">Here's what's happening with your account today.</p>
                    </div>
                    <div>
                        <a href="profile.php" class="inline-block bg-primary hover:bg-secondary text-white font-bold py-2 px-4 rounded-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                            Edit Profile
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <?php if ($role == 'mentor'): ?>
                    <div class="bg-primary text-white rounded-xl shadow-lg p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover-scale">
                        <div class="text-center">
                            <i class="fas fa-calendar-check text-4xl mb-2.5 transition-transform duration-300 animate-pulse-slow"></i>
                            <h3 class="text-xl font-bold"><?php echo $profile['total_sessions']; ?></h3>
                            <p class="text-sm opacity-90">Total Sessions</p>
                        </div>
                    </div>
                    <div class="bg-primary text-white rounded-xl shadow-lg p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover-scale">
                        <div class="text-center">
                            <i class="fas fa-star text-4xl mb-2.5 transition-transform duration-300 animate-pulse-slow"></i>
                            <h3 class="text-xl font-bold"><?php echo number_format($profile['average_rating'] ?? 0, 1); ?></h3>
                            <p class="text-sm opacity-90">Average Rating</p>
                        </div>
                    </div>
                    <div class="bg-primary text-white rounded-xl shadow-lg p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover-scale">
                        <div class="text-center">
                            <i class="fas fa-comment text-4xl mb-2.5 transition-transform duration-300 animate-pulse-slow"></i>
                            <h3 class="text-xl font-bold"><?php echo $profile['total_reviews']; ?></h3>
                            <p class="text-sm opacity-90">Total Reviews</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-primary text-white rounded-xl shadow-lg p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover-scale">
                        <div class="text-center">
                            <i class="fas fa-calendar-check text-4xl mb-2.5 transition-transform duration-300 animate-pulse-slow"></i>
                            <h3 class="text-xl font-bold"><?php echo $profile['total_sessions']; ?></h3>
                            <p class="text-sm opacity-90">Total Sessions</p>
                        </div>
                    </div>
                    <div class="bg-primary text-white rounded-xl shadow-lg p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover-scale">
                        <div class="text-center">
                            <i class="fas fa-bullseye text-4xl mb-2.5 transition-transform duration-300 animate-pulse-slow"></i>
                            <h3 class="text-xl font-bold"><?php echo $profile['total_goals']; ?></h3>
                            <p class="text-sm opacity-90">Total Goals</p>
                        </div>
                    </div>
                    <div class="bg-primary text-white rounded-xl shadow-lg p-5 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover-scale">
                        <div class="text-center">
                            <i class="fas fa-book text-4xl mb-2.5 transition-transform duration-300 animate-pulse-slow"></i>
                            <h3 class="text-xl font-bold"><?php echo $profile['total_resources']; ?></h3>
                            <p class="text-sm opacity-90">Resources Accessed</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Upcoming Sessions and Recent Messages -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Upcoming Sessions -->
                <div class="bg-white rounded-xl shadow-lg transition-all duration-300 hover:shadow-xl hover-scale">
                    <div class="p-4 border-b border-tertiary">
                        <h3 class="text-lg font-semibold text-primary">Upcoming Sessions</h3>
                    </div>
                    <div class="p-4">
                        <?php if (empty($upcoming_sessions)): ?>
                            <p class="text-secondary">No upcoming sessions scheduled.</p>
                        <?php else: ?>
                            <?php foreach ($upcoming_sessions as $session): ?>
                                <div class="mb-4 pb-4 border-b border-tertiary last:border-b-0 transition-all duration-300 hover:bg-background rounded-lg p-2">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <h4 class="font-semibold text-primary"><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></h4>
                                            <p class="text-sm text-secondary"><?php echo date('M j, Y g:i A', strtotime($session['start_time'])); ?></p>
                                        </div>
                                        <a href="session-details.php?id=<?php echo $session['session_id']; ?>" class="text-secondary hover:text-primary transition-colors duration-300">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Messages -->
                <div class="bg-white rounded-xl shadow-lg transition-all duration-300 hover:shadow-xl hover-scale">
                    <div class="p-4 border-b border-tertiary">
                        <h3 class="text-lg font-semibold text-primary">Recent Messages</h3>
                    </div>
                    <div class="p-4">
                        <?php if (empty($recent_messages)): ?>
                            <p class="text-secondary">No recent messages.</p>
                        <?php else: ?>
                            <?php foreach ($recent_messages as $message): ?>
                                <div class="mb-4 pb-4 border-b border-tertiary last:border-b-0 transition-all duration-300 hover:bg-background rounded-lg p-2">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <h4 class="font-semibold text-primary"><?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?></h4>
                                            <p class="text-sm text-secondary"><?php 
                                                $messageContent = $message['message'] ?? $message['content'] ?? '';
                                                echo htmlspecialchars(strlen($messageContent) > 50 ? substr($messageContent, 0, 50) . '...' : $messageContent); 
                                            ?></p>
                                            <small class="text-tertiary"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></small>
                                        </div>
                                        <a href="messages.php" class="text-secondary hover:text-primary transition-colors duration-300">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 