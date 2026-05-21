<?php
require_once 'config.php';

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

try {
    // Get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Get mentor profile data
    $stmt = $conn->prepare("SELECT * FROM mentor_profiles WHERE mentor_id = ?");
    $stmt->execute([$user_id]);
    $mentor_profile = $stmt->fetch();
    
    // Get time period filter
    $period = isset($_GET['period']) ? sanitize_input($_GET['period']) : '30';
    
    // Get overall statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT s.session_id) as total_sessions,
            COUNT(DISTINCT CASE WHEN s.status = 'completed' THEN s.session_id END) as completed_sessions,
            COUNT(DISTINCT CASE WHEN s.status = 'cancelled' THEN s.session_id END) as cancelled_sessions,
            COUNT(DISTINCT s.mentee_id) as unique_mentees,
            AVG(r.rating) as average_rating,
            COUNT(DISTINCT r.review_id) as total_reviews,
            SUM(CASE WHEN s.status = 'completed' THEN s.hourly_rate * TIMESTAMPDIFF(HOUR, s.start_time, s.end_time) ELSE 0 END) as total_earnings,
            AVG(CASE WHEN s.status = 'completed' THEN s.hourly_rate * TIMESTAMPDIFF(HOUR, s.start_time, s.end_time) ELSE 0 END) as average_session_earnings
        FROM sessions s
        LEFT JOIN reviews r ON s.session_id = r.session_id
        WHERE s.mentor_id = ?
        AND s.start_time >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$user_id, $period]);
    $stats = $stmt->fetch();
    
    // Get monthly earnings for the past 12 months
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(start_time, '%Y-%m') as month,
            COUNT(DISTINCT session_id) as total_sessions,
            SUM(CASE WHEN status = 'completed' THEN hourly_rate * TIMESTAMPDIFF(HOUR, start_time, end_time) ELSE 0 END) as earnings
        FROM sessions
        WHERE mentor_id = ?
        AND start_time >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(start_time, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->execute([$user_id]);
    $monthly_stats = $stmt->fetchAll();
    
    // Get recent reviews with mentee details
    $stmt = $conn->prepare("
        SELECT r.*, 
               u.first_name as mentee_first_name,
               u.last_name as mentee_last_name,
               u.profile_picture as mentee_picture,
               s.start_time as session_date
        FROM reviews r
        JOIN sessions s ON r.session_id = s.session_id
        JOIN users u ON s.mentee_id = u.user_id
        WHERE s.mentor_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_reviews = $stmt->fetchAll();
    
    // Get session completion rate by day of week
    $stmt = $conn->prepare("
        SELECT 
            DAYNAME(start_time) as day_of_week,
            COUNT(*) as total_sessions,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_sessions
        FROM sessions
        WHERE mentor_id = ?
        AND start_time >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DAYNAME(start_time)
        ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
    ");
    $stmt->execute([$user_id, $period]);
    $daily_stats = $stmt->fetchAll();
    
    // Get expertise distribution
    $stmt = $conn->prepare("
        SELECT 
            u.skills as expertise,
            COUNT(DISTINCT s.session_id) as session_count
        FROM sessions s
        JOIN users u ON s.mentor_id = u.user_id
        WHERE s.mentor_id = ?
        AND s.start_time >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY u.skills
    ");
    $stmt->execute([$user_id, $period]);
    $expertise_stats = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Mentor Platform</title>
    <link rel="icon" type="image/png" href="assets/images/Favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        warning: '#ffc107'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-in': 'slideIn 0.5s ease-out',
                        'scale': 'scale 0.3s ease-in-out',
                        'bounce-in': 'bounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)'
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideIn: { '0%': { transform: 'translateY(20px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
                        scale: { '0%': { transform: 'scale(1)' }, '100%': { transform: 'scale(1.02)' } },
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
</head>
<body class="bg-background font-sans leading-relaxed">
    <div class="flex animate-fade-in">
        <div class="w-64 bg-white min-h-screen shadow-lg py-5 flex-shrink-0 border-r border-tertiary">
            <div class="text-center mb-4">
                <?php 
                    // Fix: Use URL-friendly path for profile picture
                    $profile_pic = $user['profile_picture'] ?? '';
                    $img_src = (!empty($profile_pic) && file_exists('uploads/' . $profile_pic)) ? 'uploads/' . $profile_pic : 'assets/images/default-avatar.jpg';
                ?>
                <img src="<?php echo $img_src; ?>" 
                     alt="Profile Picture" 
                     class="w-[100px] h-[100px] rounded-full object-cover mx-auto mb-4 border-[3px] border-secondary shadow-lg transition-all duration-300 hover:scale-110">
                <h5 class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                <p class="text-secondary">Mentor</p>
            </div>
            
            <nav class="space-y-1 px-3">
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all hover:bg-tertiary" href="dashboard.php"><i class="fas fa-home w-5 mr-2.5"></i> Dashboard</a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all hover:bg-tertiary" href="profile.php"><i class="fas fa-user w-5 mr-2.5"></i> Profile</a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all hover:bg-tertiary" href="messages.php"><i class="fas fa-comments w-5 mr-2.5"></i> Messages</a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all hover:bg-tertiary" href="sessions.php"><i class="fas fa-calendar w-5 mr-2.5"></i> Sessions</a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary bg-tertiary" href="analytics.php"><i class="fas fa-chart-line w-5 mr-2.5"></i> Analytics</a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-white bg-red-500 hover:bg-red-600" href="logout.php"><i class="fas fa-sign-out-alt w-5 mr-2.5"></i> Logout</a>
            </nav>
        </div>
        
        <div class="flex-1 p-8 animate-slide-in">
            <h4 class="text-xl font-semibold mb-6 text-primary">Analytics Dashboard</h4>
            
            <div class="bg-white rounded-xl shadow-lg p-4 mb-6 border border-tertiary/30">
                <form method="GET" action="" class="flex items-center">
                    <div class="w-1/3">
                        <label class="block text-sm font-medium text-primary mb-1">Time Period</label>
                        <select class="w-full px-3 py-2 border border-tertiary rounded-lg" name="period" onchange="this.form.submit()">
                            <option value="7" <?php echo $period == '7' ? 'selected' : ''; ?>>Last 7 days</option>
                            <option value="30" <?php echo $period == '30' ? 'selected' : ''; ?>>Last 30 days</option>
                            <option value="90" <?php echo $period == '90' ? 'selected' : ''; ?>>Last 90 days</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-primary to-secondary rounded-xl shadow-lg text-white p-5">
                    <div class="text-3xl font-bold mb-2"><?php echo $stats['total_sessions']; ?></div>
                    <div class="text-sm opacity-80">Total Sessions</div>
                </div>
                <div class="bg-gradient-to-br from-primary to-secondary rounded-xl shadow-lg text-white p-5">
                    <div class="text-3xl font-bold mb-2">₹<?php echo number_format($stats['total_earnings'] ?? 0, 2); ?></div>
                    <div class="text-sm opacity-80">Total Earnings</div>
                </div>
                <div class="bg-gradient-to-br from-primary to-secondary rounded-xl shadow-lg text-white p-5">
                    <div class="text-3xl font-bold mb-2"><?php echo $stats['unique_mentees']; ?></div>
                    <div class="text-sm opacity-80">Unique Mentees</div>
                </div>
                <div class="bg-gradient-to-br from-primary to-secondary rounded-xl shadow-lg text-white p-5">
                    <div class="text-3xl font-bold mb-2">
                        <?php echo $stats['total_sessions'] > 0 ? round(($stats['completed_sessions'] / $stats['total_sessions']) * 100) : 0; ?>%
                    </div>
                    <div class="text-sm opacity-80">Completion Rate</div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg border border-tertiary/30 p-6">
                <h5 class="text-lg font-semibold text-primary mb-6">Recent Reviews</h5>
                <?php foreach ($recent_reviews as $review): ?>
                    <div class="mb-6 pb-6 border-b border-tertiary/30 last:border-b-0">
                        <div class="flex items-center mb-2">
                            <?php 
                                $mentee_pic = $review['mentee_picture'] ?? '';
                                $m_img = (!empty($mentee_pic) && file_exists('uploads/' . $mentee_pic)) ? 'uploads/' . $mentee_pic : 'assets/images/default-avatar.jpg';
                            ?>
                            <img src="<?php echo $m_img; ?>" class="w-10 h-10 rounded-full object-cover mr-3">
                            <div>
                                <h6 class="font-semibold text-primary"><?php echo htmlspecialchars($review['mentee_first_name'] . ' ' . $review['mentee_last_name']); ?></h6>
                                <span class="text-sm text-secondary"><?php echo date('M d, Y', strtotime($review['session_date'])); ?></span>
                            </div>
                        </div>
                        <div class="text-warning mb-2">
                            <?php for ($i = 1; $i <= 5; $i++) echo $i <= $review['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                        </div>
                        <p class="text-secondary"><?php echo nl2br(htmlspecialchars($review['comment'] ?? '')); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>