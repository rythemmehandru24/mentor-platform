<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success = '';
$error = '';

// Get user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Get all sessions
    $stmt = $conn->prepare("
        SELECT s.*, 
               m.first_name as mentor_first_name,
               m.last_name as mentor_last_name,
               m.profile_picture as mentor_picture,
               e.first_name as mentee_first_name,
               e.last_name as mentee_last_name,
               e.profile_picture as mentee_picture
        FROM sessions s
        JOIN users m ON s.mentor_id = m.user_id
        JOIN users e ON s.mentee_id = e.user_id
        WHERE s.mentor_id = ? OR s.mentee_id = ?
        ORDER BY s.start_time DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    $sessions = $stmt->fetchAll();
    
    // Get upcoming sessions
    $stmt = $conn->prepare("
        SELECT s.*, 
               m.first_name as mentor_first_name,
               m.last_name as mentor_last_name,
               m.profile_picture as mentor_picture,
               e.first_name as mentee_first_name,
               e.last_name as mentee_last_name,
               e.profile_picture as mentee_picture
        FROM sessions s
        JOIN users m ON s.mentor_id = m.user_id
        JOIN users e ON s.mentee_id = e.user_id
        WHERE (s.mentor_id = ? OR s.mentee_id = ?)
        AND s.status = 'scheduled'
        AND s.start_time >= NOW()
        ORDER BY s.start_time ASC
    ");
    $stmt->execute([$user_id, $user_id]);
    $upcoming_sessions = $stmt->fetchAll();
    
    // Get past sessions
    $stmt = $conn->prepare("
        SELECT s.*, 
               m.first_name as mentor_first_name,
               m.last_name as mentor_last_name,
               m.profile_picture as mentor_picture,
               e.first_name as mentee_first_name,
               e.last_name as mentee_last_name,
               e.profile_picture as mentee_picture
        FROM sessions s
        JOIN users m ON s.mentor_id = m.user_id
        JOIN users e ON s.mentee_id = e.user_id
        WHERE (s.mentor_id = ? OR s.mentee_id = ?)
        AND (s.status = 'completed' OR s.start_time < NOW())
        ORDER BY s.start_time DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    $past_sessions = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle session status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['session_id'])) {
    try {
        $session_id = (int)$_POST['session_id'];
        $action = $_POST['action'];
        
        // Verify user has permission to update session
        $stmt = $conn->prepare("
            SELECT * FROM sessions 
            WHERE session_id = ? AND (mentor_id = ? OR mentee_id = ?)
        ");
        $stmt->execute([$session_id, $user_id, $user_id]);
        $session = $stmt->fetch();
        
        if ($session) {
            if ($action == 'complete' && $role == 'mentor') {
                $stmt = $conn->prepare("UPDATE sessions SET status = 'completed' WHERE session_id = ?");
                $stmt->execute([$session_id]);
                $success = "Session marked as completed successfully!";
            } elseif ($action == 'cancel') {
                $stmt = $conn->prepare("UPDATE sessions SET status = 'cancelled' WHERE session_id = ?");
                $stmt->execute([$session_id]);
                $success = "Session cancelled successfully!";
            }
        } else {
            $error = "You don't have permission to update this session.";
        }
    } catch(PDOException $e) {
        $error = "An error occurred. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions - Mentor Platform</title>
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
                        'scale': 'scale 0.3s ease-in-out'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideIn: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' }
                        },
                        scale: {
                            '0%': { transform: 'scale(1)' },
                            '100%': { transform: 'scale(1.02)' }
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
    <div class="flex min-h-screen animate-fade-in">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg py-5 flex-shrink-0 border-r border-tertiary">
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
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="messages.php">
                    <i class="fas fa-comments w-5 mr-2.5"></i> Messages
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary bg-tertiary" href="sessions.php">
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
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Upcoming Sessions -->
            <div class="bg-white rounded-xl shadow-lg mb-6 hover-scale">
                <div class="p-5 border-b border-tertiary flex justify-between items-center">
                    <h5 class="text-xl font-semibold text-primary m-0">Upcoming Sessions</h5>
                    <?php if ($role == 'mentee'): ?>
                        <a href="schedule-session.php" class="bg-primary hover:bg-secondary text-white px-4 py-2 rounded-lg transition-all duration-300 flex items-center">
                            <i class="fas fa-plus mr-2"></i> Schedule New Session
                        </a>
                    <?php endif; ?>
                </div>
                <div class="p-5">
                    <?php if (empty($upcoming_sessions)): ?>
                        <p class="text-secondary">No upcoming sessions</p>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($upcoming_sessions as $session): ?>
                                <div class="bg-white rounded-xl shadow-lg transition-all duration-300 hover:-translate-y-1 border border-tertiary/30">
                                    <div class="p-4">
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <h6 class="text-lg font-semibold mb-1 text-primary">
                                                    <?php if ($role == 'mentor'): ?>
                                                        <?php echo htmlspecialchars($session['mentee_first_name'] . ' ' . $session['mentee_last_name']); ?>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($session['mentor_first_name'] . ' ' . $session['mentor_last_name']); ?>
                                                    <?php endif; ?>
                                                </h6>
                                                <span class="bg-tertiary/20 text-primary px-3 py-1 rounded-full text-sm">
                                                    <?php echo ucfirst($session['status']); ?>
                                                </span>
                                            </div>
                                            <span class="text-secondary text-sm">
                                                <?php echo date('M d, Y h:i A', strtotime($session['start_time'])); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="flex justify-between items-center">
                                            <a href="session-details.php?id=<?php echo $session['session_id']; ?>" 
                                               class="inline-block px-3 py-1 border border-primary text-primary hover:bg-primary hover:text-white rounded-lg text-sm transition-colors duration-300">
                                                View Details
                                            </a>
                                            <?php if ($role == 'mentor'): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
                                                    <input type="hidden" name="action" value="complete">
                                                    <button type="submit" class="px-3 py-1 bg-primary text-white hover:bg-secondary rounded-lg text-sm transition-colors duration-300">
                                                        Mark as Completed
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="px-3 py-1 border border-red-500 text-red-500 hover:bg-red-500 hover:text-white rounded-lg text-sm transition-colors duration-300">
                                                    Cancel
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Past Sessions -->
            <div class="bg-white rounded-xl shadow-lg hover-scale">
                <div class="p-5 border-b border-tertiary">
                    <h5 class="text-xl font-semibold text-primary m-0">Past Sessions</h5>
                </div>
                <div class="p-5">
                    <?php if (empty($past_sessions)): ?>
                        <p class="text-secondary">No past sessions</p>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($past_sessions as $session): ?>
                                <div class="bg-white rounded-xl shadow-lg transition-all duration-300 hover:-translate-y-1 border border-tertiary/30">
                                    <div class="p-4">
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <h6 class="text-lg font-semibold mb-1 text-primary">
                                                    <?php if ($role == 'mentor'): ?>
                                                        <?php echo htmlspecialchars($session['mentee_first_name'] . ' ' . $session['mentee_last_name']); ?>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($session['mentor_first_name'] . ' ' . $session['mentor_last_name']); ?>
                                                    <?php endif; ?>
                                                </h6>
                                                <span class="<?php 
                                                    echo $session['status'] == 'completed' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600';
                                                ?> px-3 py-1 rounded-full text-sm">
                                                    <?php echo ucfirst($session['status']); ?>
                                                </span>
                                            </div>
                                            <span class="text-secondary text-sm">
                                                <?php echo date('M d, Y h:i A', strtotime($session['start_time'])); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="flex justify-between items-center">
                                            <a href="session-details.php?id=<?php echo $session['session_id']; ?>" 
                                               class="inline-block px-3 py-1 border border-primary text-primary hover:bg-primary hover:text-white rounded-lg text-sm transition-colors duration-300">
                                                View Details
                                            </a>
                                            <?php if ($session['status'] == 'completed' && !isset($session['review_id'])): ?>
                                                <a href="review-session.php?id=<?php echo $session['session_id']; ?>" 
                                                   class="inline-block px-3 py-1 bg-primary text-white hover:bg-secondary rounded-lg text-sm transition-colors duration-300">
                                                    Leave Review
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 