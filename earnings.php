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
    
    // Get earnings summary
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_sessions,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_sessions,
            SUM(CASE WHEN status = 'completed' THEN hourly_rate * TIMESTAMPDIFF(HOUR, start_time, end_time) ELSE 0 END) as total_earnings,
            AVG(CASE WHEN status = 'completed' THEN hourly_rate * TIMESTAMPDIFF(HOUR, start_time, end_time) ELSE 0 END) as average_earnings_per_session
        FROM sessions
        WHERE mentor_id = ?
    ");
    $stmt->execute([$user_id]);
    $earnings_summary = $stmt->fetch();
    
    // Get recent sessions with earnings
    $stmt = $conn->prepare("
        SELECT s.*, 
               u.first_name as mentee_first_name,
               u.last_name as mentee_last_name,
               u.profile_picture as mentee_picture,
               (s.hourly_rate * TIMESTAMPDIFF(HOUR, s.start_time, s.end_time)) as session_earnings
        FROM sessions s
        JOIN users u ON s.mentee_id = u.user_id
        WHERE s.mentor_id = ?
        ORDER BY s.start_time DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recent_sessions = $stmt->fetchAll();
    
    // Get monthly earnings for the past 6 months
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(start_time, '%Y-%m') as month,
            SUM(CASE WHEN status = 'completed' THEN hourly_rate * TIMESTAMPDIFF(HOUR, start_time, end_time) ELSE 0 END) as monthly_earnings
        FROM sessions
        WHERE mentor_id = ?
        AND start_time >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(start_time, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->execute([$user_id]);
    $monthly_earnings = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - Mentor Platform</title>
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
        .stats-card {
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(54, 44, 122, 0.1);
        }
        .chart-container {
            transition: all 0.3s ease;
        }
        .chart-container:hover {
            transform: scale(1.01);
        }
        .session-row {
            transition: all 0.3s ease;
        }
        .session-row:hover {
            transform: translateX(5px);
            background-color: rgba(198, 182, 247, 0.1);
        }
        .status-badge {
            transition: all 0.3s ease;
        }
        .session-row:hover .status-badge {
            transform: scale(1.05);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-background font-['Segoe_UI',_Tahoma,_Geneva,_Verdana,_sans-serif]">
    <div class="flex animate-fade-in">
        <div class="w-64 bg-white min-h-screen shadow-lg py-5 flex-shrink-0 border-r border-tertiary">
            <div class="text-center mb-4">
                <img src="<?php echo $user['profile_picture'] ? UPLOAD_URL . $user['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                     alt="Profile Picture" 
                     class="w-[100px] h-[100px] rounded-full object-cover mx-auto mb-4 border-[3px] border-secondary shadow-lg transition-all duration-300 hover:scale-110 hover:border-primary">
                <h5 class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                <p class="text-secondary">Mentor</p>
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
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="sessions.php">
                    <i class="fas fa-calendar w-5 mr-2.5"></i> Sessions
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="resources.php">
                    <i class="fas fa-book w-5 mr-2.5"></i> Resources
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary bg-tertiary" href="earnings.php">
                    <i class="fas fa-dollar-sign w-5 mr-2.5"></i> Earnings
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="analytics.php">
                    <i class="fas fa-chart-line w-5 mr-2.5"></i> Analytics
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-white bg-red-500 transition-all duration-300 hover:bg-red-600" href="logout.php">
                    <i class="fas fa-sign-out-alt w-5 mr-2.5"></i> Logout
                </a>
            </nav>
        </div>
        
        <div class="flex-1 p-8 animate-slide-in">
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 animate-bounce-in"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 animate-bounce-in"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <h1 class="text-2xl font-bold text-primary mb-6">Earnings Overview</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="stats-card bg-gradient-to-br from-primary to-secondary rounded-xl shadow-lg text-white p-5">
                    <div class="text-3xl font-bold mb-2">₹<?php echo number_format($earnings_summary['total_earnings'], 2); ?></div>
                    <div class="text-sm opacity-80">Total Earnings</div>
                </div>
                <div class="stats-card bg-gradient-to-br from-primary to-secondary rounded-xl shadow-lg text-white p-5">
                    <div class="text-3xl font-bold mb-2">₹<?php echo number_format($earnings_summary['average_earnings_per_session'], 2); ?></div>
                    <div class="text-sm opacity-80">Average per Session</div>
                </div>
                <div class="stats-card bg-gradient-to-br from-primary to-secondary rounded-xl shadow-lg text-white p-5">
                    <div class="text-3xl font-bold mb-2"><?php echo $earnings_summary['completed_sessions']; ?></div>
                    <div class="text-sm opacity-80">Completed Sessions</div>
                </div>
                <div class="stats-card bg-gradient-to-br from-primary to-secondary rounded-xl shadow-lg text-white p-5">
                    <div class="text-3xl font-bold mb-2">₹<?php echo number_format($mentor_profile['hourly_rate'], 2); ?></div>
                    <div class="text-sm opacity-80">Hourly Rate</div>
                </div>
            </div>
            
            <div class="chart-container bg-white rounded-xl shadow-lg mb-6 border border-tertiary/30">
                <div class="px-6 py-4 border-b border-tertiary/30">
                    <h5 class="text-lg font-semibold text-primary">Monthly Earnings</h5>
                </div>
                <div class="p-6">
                    <canvas id="earningsChart"></canvas>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg border border-tertiary/30">
                <div class="px-6 py-4 border-b border-tertiary/30">
                    <h5 class="text-lg font-semibold text-primary">Recent Sessions</h5>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-tertiary/10">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Mentee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Duration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Earnings</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-tertiary/20">
                                <?php foreach ($recent_sessions as $session): ?>
                                    <tr class="session-row">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <img src="<?php echo $session['mentee_picture'] ? UPLOAD_URL . $session['mentee_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                                                     alt="Mentee Picture" 
                                                     class="w-10 h-10 rounded-full object-cover border-2 border-secondary shadow-sm transition-all duration-300 hover:scale-110 hover:border-primary mr-3">
                                                <div class="text-sm font-medium text-primary">
                                                    <?php echo htmlspecialchars($session['mentee_first_name'] . ' ' . $session['mentee_last_name']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-secondary">
                                            <?php echo date('M d, Y', strtotime($session['start_time'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-secondary">
                                            <?php echo round((strtotime($session['end_time']) - strtotime($session['start_time'])) / 3600); ?> hours
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="status-badge inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                                echo $session['status'] == 'completed' ? 'bg-green-100 text-green-800' : 
                                                    ($session['status'] == 'scheduled' ? 'bg-tertiary/20 text-primary' : 'bg-red-100 text-red-800'); 
                                            ?>">
                                                <?php echo ucfirst($session['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-secondary">
                                            <?php if ($session['status'] == 'completed'): ?>
                                                ₹<?php echo number_format($session['session_earnings'], 2); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Earnings Chart
       const ctx = document.getElementById('earningsChart').getContext('2d');
const monthlyData = <?php echo json_encode(array_reverse($monthly_earnings)); ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
            label: 'Monthly Earnings',
            data: monthlyData.map(item => item.monthly_earnings),
            borderColor: '#362C7A',
            backgroundColor: 'rgba(198, 182, 247, 0.2)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                // Force the Y-axis to go up to 700 so 699 is visible
                min: 0,
                max: 700, 
                ticks: {
                    // Force a label every 100 units
                    stepSize: 100,
                    callback: function(value) {
                        // If value is 0, show 0
                        // For 100, show 99; for 200, show 199, etc.
                        if (value === 0) return '₹0';
                        return '₹' + (value - 1);
                    }
                },
                grid: {
                    drawTicks: true,
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            }
        }
    }
});
    </script>
</body>
</html>