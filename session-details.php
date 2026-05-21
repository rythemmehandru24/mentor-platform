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
    
    if (!$user) {
        header("Location: logout.php");
        exit();
    }
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Get session ID from URL
$session_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$session_id) {
    header("Location: sessions.php");
    exit();
}

try {
    // Get session details
    $stmt = $conn->prepare("
        SELECT s.*, 
               m.first_name as mentor_first_name,
               m.last_name as mentor_last_name,
               m.profile_picture as mentor_picture,
               m.email as mentor_email,
               e.first_name as mentee_first_name,
               e.last_name as mentee_last_name,
               e.profile_picture as mentee_picture,
               e.email as mentee_email,
               mp.hourly_rate
        FROM sessions s
        JOIN users m ON s.mentor_id = m.user_id
        JOIN users e ON s.mentee_id = e.user_id
        JOIN mentor_profiles mp ON s.mentor_id = mp.mentor_id
        WHERE s.session_id = ? AND (s.mentor_id = ? OR s.mentee_id = ?)
    ");
    $stmt->execute([$session_id, $user_id, $user_id]);
    $session = $stmt->fetch();
    
    if (!$session) {
        header("Location: sessions.php");
        exit();
    }
    
    // Debug logging
    error_log("Session Data: " . print_r($session, true));
    
    // Get session review if exists
    $stmt = $conn->prepare("
        SELECT r.*, 
               u.first_name, u.last_name, u.profile_picture
        FROM reviews r
        JOIN users u ON r.mentee_id = u.user_id
        WHERE r.session_id = ?
    ");
    $stmt->execute([$session_id]);
    $review = $stmt->fetch();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle session status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        $action = $_POST['action'];
        
        if ($action == 'complete' && $role == 'mentor') {
            $stmt = $conn->prepare("UPDATE sessions SET status = 'completed' WHERE session_id = ?");
            $stmt->execute([$session_id]);
            $success = "Session marked as completed successfully!";
        } elseif ($action == 'cancel') {
            $stmt = $conn->prepare("UPDATE sessions SET status = 'cancelled' WHERE session_id = ?");
            $stmt->execute([$session_id]);
            $success = "Session cancelled successfully!";
        }
        
        // Refresh session data
        $stmt = $conn->prepare("
            SELECT s.*, 
                   m.first_name as mentor_first_name,
                   m.last_name as mentor_last_name,
                   m.profile_picture as mentor_picture,
                   m.email as mentor_email,
                   e.first_name as mentee_first_name,
                   e.last_name as mentee_last_name,
                   e.profile_picture as mentee_picture,
                   e.email as mentee_email,
                   mp.hourly_rate
            FROM sessions s
            JOIN users m ON s.mentor_id = m.user_id
            JOIN users e ON s.mentee_id = e.user_id
            JOIN mentor_profiles mp ON s.mentor_id = mp.mentor_id
            WHERE s.session_id = ? AND (s.mentor_id = ? OR s.mentee_id = ?)
        ");
        $stmt->execute([$session_id, $user_id, $user_id]);
        $session = $stmt->fetch();
        
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
    <title>Session Details - Mentor Platform</title>
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
                        'scale': 'scale 0.3s ease-in-out',
                        'bounce-in': 'bounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)'
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
                        },
                        bounceIn: {
                            '0%': { transform: 'scale(0.3)', opacity: '0' },
                            '50%': { transform: 'scale(1.05)', opacity: '0.8' },
                            '70%': { transform: 'scale(0.9)', opacity: '0.9' },
                            '100%': { transform: 'scale(1)', opacity: '1' }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .profile-card {
            transition: all 0.3s ease;
        }
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(54, 44, 122, 0.1);
        }
        .profile-image {
            transition: all 0.3s ease;
        }
        .profile-card:hover .profile-image {
            transform: scale(1.05);
            border-color: #735CC6;
        }
        .action-button {
            transition: all 0.3s ease;
        }
        .action-button:hover {
            transform: translateY(-2px);
        }
        .review-card {
            transition: all 0.3s ease;
        }
        .review-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(54, 44, 122, 0.1);
        }
        .star-rating {
            transition: all 0.3s ease;
        }
        .review-card:hover .star-rating {
            transform: scale(1.05);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-background font-['Segoe_UI',_Tahoma,_Geneva,_Verdana,_sans-serif]">
    <div class="flex animate-fade-in">
        <!-- Sidebar -->
        <div class="w-64 bg-white min-h-screen shadow-lg py-5 flex-shrink-0 border-r border-tertiary">
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
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 animate-bounce-in"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 animate-bounce-in"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="bg-white rounded-xl shadow-lg border border-tertiary/30 mb-6">
                <div class="p-5 border-b border-tertiary/30 flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-primary m-0">Session Details</h1>
                    <span class="<?php 
                        echo $session['status'] == 'scheduled' ? 'bg-tertiary/20 text-primary' : 
                            ($session['status'] == 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
                    ?> px-3 py-1 rounded-full text-sm font-medium">
                        <?php echo ucfirst($session['status']); ?>
                    </span>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Mentor Information -->
                        <div class="profile-card bg-white rounded-xl shadow-lg border border-tertiary/30">
                            <div class="p-5 text-center">
                                <?php
                                    $mentor_picture_path = $session['mentor_picture'] ? UPLOAD_URL . $session['mentor_picture'] : 'assets/images/default-avatar.jpg';
                                    ?>
                                <img src="<?php echo $mentor_picture_path; ?>" 
                                     alt="Mentor Picture" 
                                     class="profile-image w-[100px] h-[100px] rounded-full object-cover mx-auto mb-4 border-[3px] border-secondary shadow-lg transition-all duration-300 hover:scale-110 hover:border-primary"
                                     onerror="this.src='assets/images/default-avatar.jpg'; console.log('Error loading mentor picture');">
                                <h5 class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($session['mentor_first_name'] . ' ' . $session['mentor_last_name']); ?></h5>
                                <p class="text-secondary">Mentor</p>
                                <p class="text-secondary"><i class="fas fa-envelope mr-2"></i> <?php echo htmlspecialchars($session['mentor_email']); ?></p>
                            </div>
                        </div>
                        
                        <!-- Mentee Information -->
                        <div class="profile-card bg-white rounded-xl shadow-lg border border-tertiary/30">
                            <div class="p-5 text-center">
                                <img src="<?php echo $session['mentee_picture'] ? UPLOAD_URL . $session['mentee_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                                     alt="Mentee Picture" 
                                     class="profile-image w-[100px] h-[100px] rounded-full object-cover mx-auto mb-4 border-[3px] border-secondary shadow-lg transition-all duration-300 hover:scale-110 hover:border-primary">
                                <h5 class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($session['mentee_first_name'] . ' ' . $session['mentee_last_name']); ?></h5>
                                <p class="text-secondary">Mentee</p>
                                <p class="text-secondary"><i class="fas fa-envelope mr-2"></i> <?php echo htmlspecialchars($session['mentee_email']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-6 border-tertiary/30">
                    
                    <!-- Session Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h6 class="text-lg font-semibold text-primary mb-4">Session Information</h6>
                            <p class="mb-2 text-secondary"><span class="font-semibold text-primary">Date:</span> <?php echo date('M d, Y', strtotime($session['start_time'])); ?></p>
                            <p class="mb-2 text-secondary"><span class="font-semibold text-primary">Time:</span> <?php echo date('h:i A', strtotime($session['start_time'])); ?> - <?php echo date('h:i A', strtotime($session['end_time'])); ?></p>
                            <p class="mb-2 text-secondary"><span class="font-semibold text-primary">Duration:</span> <?php echo round((strtotime($session['end_time']) - strtotime($session['start_time'])) / 3600); ?> hours</p>
                            <p class="mb-2 text-secondary"><span class="font-semibold text-primary">Total Cost:</span> $<?php echo number_format($session['hourly_rate'] * round((strtotime($session['end_time']) - strtotime($session['start_time'])) / 3600), 2); ?></p>
                        </div>
                        
                        <div>
                            <h6 class="text-lg font-semibold text-primary mb-4">Session Notes</h6>
                            <p class="whitespace-pre-line text-secondary"><?php echo nl2br(htmlspecialchars($session['notes'] ?? 'No notes provided.')); ?></p>
                        </div>
                    </div>
                    
                    <!-- Session Actions -->
                    <?php if ($session['status'] == 'scheduled'): ?>
                        <div class="mt-6 flex justify-between items-center">
                            <a href="messages.php?user=<?php echo $role == 'mentor' ? $session['mentee_id'] : $session['mentor_id']; ?>" 
                               class="action-button inline-block px-4 py-2 border border-primary text-primary hover:bg-primary hover:text-white rounded-lg transition-all duration-300">
                                <i class="fas fa-comments mr-2"></i> Send Message
                            </a>
                            
                            <div class="space-x-2">
                                <?php if ($role == 'mentor'): ?>
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="action" value="complete">
                                        <button type="submit" class="action-button px-4 py-2 bg-green-500 text-white hover:bg-green-600 rounded-lg transition-all duration-300">
                                            <i class="fas fa-check mr-2"></i> Mark Complete
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" action="" class="inline">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="action-button px-4 py-2 bg-red-500 text-white hover:bg-red-600 rounded-lg transition-all duration-300">
                                        <i class="fas fa-times mr-2"></i> Cancel Session
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Session Review -->
                    <?php if ($session['status'] == 'completed'): ?>
                        <?php if ($review): ?>
                            <div class="review-card mt-6 bg-white rounded-xl shadow-lg border border-tertiary/30 p-5">
                                <div class="flex items-center mb-4">
                                    <img src="<?php echo $review['profile_picture'] ? UPLOAD_URL . $review['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                                         alt="Reviewer Picture" 
                                         class="w-10 h-10 rounded-full object-cover mr-3 border-2 border-secondary">
                                    <div>
                                        <h6 class="font-semibold text-primary">
                                            <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                        </h6>
                                        <div class="star-rating text-yellow-400">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                                <p class="whitespace-pre-line text-secondary"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                        <?php elseif ($role == 'mentee'): ?>
                            <div class="mt-6 text-center">
                                <a href="review-session.php?id=<?php echo $session_id; ?>" 
                                   class="action-button inline-block px-4 py-2 bg-primary hover:bg-secondary text-white rounded-lg transition-all duration-300">
                                    <i class="fas fa-star mr-2"></i> Leave Review
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 