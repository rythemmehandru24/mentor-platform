<?php
require_once 'config.php';

// Check if user is logged in and is a mentee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentee') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$success = '';
$error = '';

try {
    // Get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Get all goals for the mentee
    $stmt = $conn->prepare("
        SELECT DISTINCT g.goal_id, g.title, g.description, g.target_date, g.status, g.created_at
        FROM goals g
        WHERE g.mentee_id = ?
        ORDER BY g.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Enhanced debugging
    error_log("User ID: " . $user_id);
    error_log("SQL Query: " . $stmt->queryString);
    error_log("Number of goals found: " . count($goals));
    error_log("Goals data (detailed):");
    foreach ($goals as $index => $goal) {
        error_log("Index: " . $index . 
                 ", Goal ID: " . $goal['goal_id'] . 
                 ", Title: " . $goal['title'] . 
                 ", Description: " . $goal['description']);
    }
    
    // Verify each goal is unique
    $seen_goals = array();
    foreach ($goals as $key => $goal) {
        if (isset($seen_goals[$goal['goal_id']])) {
            error_log("Duplicate goal found with ID: " . $goal['goal_id']);
            // Remove duplicate
            unset($goals[$key]);
        } else {
            $seen_goals[$goal['goal_id']] = true;
        }
    }
    
    // Reset array keys
    $goals = array_values($goals);
    
    // Get goal progress for each goal
    foreach ($goals as &$goal) {
        $stmt = $conn->prepare("
            SELECT * FROM goal_progress 
            WHERE goal_id = ? 
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$goal['goal_id']]);
        $latest_progress = $stmt->fetch();
        
        // Set overall progress
        $goal['overall_progress'] = $latest_progress ? $latest_progress['progress_percentage'] : 0;
    }
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle goal creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] == 'create') {
            $title = sanitize_input($_POST['title']);
            $description = sanitize_input($_POST['description']);
            $target_date = sanitize_input($_POST['target_date']);
            
            if (empty($title) || empty($description) || empty($target_date)) {
                $error = "Please fill in all required fields";
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO goals (mentee_id, title, description, target_date)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $title, $description, $target_date]);
                
                $success = "Goal created successfully!";
                $_SESSION['success_message'] = $success;
                
                // Redirect immediately after creating the goal
                header("Location: goals.php");
                exit();
            }
        }
        
        // Handle goal progress update
        elseif ($_POST['action'] == 'update_progress') {
            $goal_id = (int)$_POST['goal_id'];
            $progress_value = (int)$_POST['progress_value'];
            $notes = sanitize_input($_POST['notes']);
            
            if ($progress_value < 0 || $progress_value > 100) {
                $error = "Progress value must be between 0 and 100";
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO goal_progress (goal_id, progress_percentage, update_text)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$goal_id, $progress_value, $notes]);
                
                $success = "Progress updated successfully!";
                
                // Refresh goals list after updating progress
                $stmt = $conn->prepare("
                    SELECT g.goal_id, g.title, g.description, g.target_date, g.status, g.created_at
                    FROM goals g
                    WHERE g.mentee_id = ?
                    ORDER BY g.created_at DESC
                ");
                $stmt->execute([$user_id]);
                $goals = $stmt->fetchAll();
                
                // Refresh progress data for each goal
                foreach ($goals as &$goal) {
                    $stmt = $conn->prepare("
                        SELECT * FROM goal_progress 
                        WHERE goal_id = ? 
                        ORDER BY created_at DESC
                        LIMIT 1
                    ");
                    $stmt->execute([$goal['goal_id']]);
                    $latest_progress = $stmt->fetch();
                    
                    // Set overall progress
                    $goal['overall_progress'] = $latest_progress ? $latest_progress['progress_percentage'] : 0;
                }
            }
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
    <title>My Goals - Mentor System</title>
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
                        'modal-in': 'modalIn 0.3s ease-out'
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
                        modalIn: {
                            '0%': { transform: 'translateY(-50px)', opacity: '0' },
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
        .progress-ring {
            transition: stroke-dashoffset 0.3s ease;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-background font-['Segoe_UI',_Tahoma,_Geneva,_Verdana,_sans-serif]">
    <?php
    require_once "config.php";
    
    // ... existing code ...
    ?>

    <div class="min-h-screen flex animate-fade-in">
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
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="sessions.php">
                    <i class="fas fa-calendar w-5 mr-2.5"></i> Sessions
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary bg-tertiary" href="goals.php">
                    <i class="fas fa-bullseye w-5 mr-2.5"></i> Goals
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="mentee-resources.php">
                    <i class="fas fa-book w-5 mr-2.5"></i> Resources
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="find-mentor.php">
                    <i class="fas fa-search w-5 mr-2.5"></i> Find Mentor
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-white bg-red-500 transition-all duration-300 hover:bg-red-600" href="logout.php">
                    <i class="fas fa-sign-out-alt w-5 mr-2.5"></i> Logout
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 p-8 animate-slide-in">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Goals Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-primary">My Goals</h1>
                <button onclick="document.getElementById('createGoalModal').classList.remove('hidden')" 
                        class="bg-primary hover:bg-secondary text-white px-4 py-2 rounded-lg transition-all duration-300 flex items-center">
                    <i class="fas fa-plus mr-2"></i> Create New Goal
                </button>
            </div>
            
            <!-- Goals Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($goals as $goal): ?>
                    <div class="bg-white rounded-xl shadow-lg hover-scale border border-tertiary/30">
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h5 class="text-lg font-semibold text-primary mb-1"><?php echo htmlspecialchars($goal['title']); ?></h5>
                                    <p class="text-secondary text-sm mb-2"><?php echo htmlspecialchars($goal['description']); ?></p>
                                </div>
                                <div class="relative w-12 h-12">
                                    <svg class="transform -rotate-90 w-12 h-12">
                                        <circle cx="24" cy="24" r="20" 
                                                stroke-width="4" 
                                                fill="none"
                                                class="stroke-tertiary/30" />
                                        <circle cx="24" cy="24" r="20"
                                                stroke-width="4"
                                                fill="none"
                                                stroke-dasharray="125.6"
                                                stroke-dashoffset="<?php echo 125.6 - ($goal['overall_progress'] / 100 * 125.6); ?>"
                                                class="progress-ring stroke-primary" />
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center text-sm font-semibold text-primary">
                                        <?php echo $goal['overall_progress']; ?>%
                                    </span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between text-sm text-secondary mb-4">
                                <span>
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    Target: <?php echo date('M d, Y', strtotime($goal['target_date'])); ?>
                                </span>
                                <span class="<?php 
                                    if ($goal['status'] == 'completed') {
                                        echo 'bg-tertiary/20 text-primary border border-tertiary';
                                    } elseif ($goal['status'] == 'in_progress') {
                                        echo 'bg-secondary/20 text-primary border border-secondary';
                                    } else {
                                        echo 'bg-gray-100 text-primary border border-tertiary/30';
                                    }
                                ?> px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    <?php echo $goal['status'] == 'in_progress' ? 'In Progress' : ucfirst($goal['status']); ?>
                                </span>
                            </div>
                            
                            <button onclick="document.getElementById('updateProgress<?php echo $goal['goal_id']; ?>').classList.remove('hidden')"
                                    class="w-full bg-tertiary/20 hover:bg-secondary/20 text-primary hover:text-secondary px-4 py-2 rounded-lg transition-all duration-300 border border-tertiary/30 hover:border-secondary">
                                Update Progress
                            </button>
                        </div>
                    </div>
                    
                    <!-- Update Progress Modal -->
                    <div id="updateProgress<?php echo $goal['goal_id']; ?>" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
                        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white animate-modal-in">
                            <div class="flex justify-between items-center mb-4">
                                <h5 class="text-xl font-semibold text-primary">Update Progress</h5>
                                <button type="button" class="text-secondary hover:text-primary transition-colors" 
                                        onclick="this.closest('.fixed').classList.add('hidden')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_progress">
                                <input type="hidden" name="goal_id" value="<?php echo $goal['goal_id']; ?>">
                                
                                <div class="mb-4">
                                    <label class="block text-primary text-sm font-semibold mb-2">Progress Value (%)</label>
                                    <input type="number" 
                                           class="shadow appearance-none border border-tertiary rounded-lg w-full py-2 px-3 text-primary leading-tight focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           name="progress_value" 
                                           min="0" 
                                           max="100" 
                                           required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-primary text-sm font-semibold mb-2">Notes</label>
                                    <textarea class="shadow appearance-none border border-tertiary rounded-lg w-full py-2 px-3 text-primary leading-tight focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                              name="notes" 
                                              rows="3" 
                                              placeholder="Add any notes about your progress..."></textarea>
                                </div>
                                
                                <div class="flex justify-end gap-2">
                                    <button type="button" 
                                            class="bg-tertiary/20 text-primary px-4 py-2 rounded-lg hover:bg-tertiary/30 transition-colors"
                                            onclick="this.closest('.fixed').classList.add('hidden')">
                                        Cancel
                                    </button>
                                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
                                        Update Progress
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Create Goal Modal -->
    <div id="createGoalModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white animate-modal-in">
            <div class="flex justify-between items-center mb-4">
                <h5 class="text-xl font-semibold text-primary">Create New Goal</h5>
                <button type="button" class="text-secondary hover:text-primary transition-colors" 
                        onclick="this.closest('.fixed').classList.add('hidden')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                
                <div class="mb-4">
                    <label class="block text-primary text-sm font-semibold mb-2">Title</label>
                    <input type="text" 
                           class="shadow appearance-none border border-tertiary rounded-lg w-full py-2 px-3 text-primary leading-tight focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                           name="title" 
                           required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-primary text-sm font-semibold mb-2">Description</label>
                    <textarea class="shadow appearance-none border border-tertiary rounded-lg w-full py-2 px-3 text-primary leading-tight focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                              name="description" 
                              rows="3" 
                              required></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-primary text-sm font-semibold mb-2">Target Date</label>
                    <input type="date" 
                           class="shadow appearance-none border border-tertiary rounded-lg w-full py-2 px-3 text-primary leading-tight focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                           name="target_date" 
                           required>
                </div>
                
                <div class="flex justify-end gap-2">
                    <button type="button" 
                            class="bg-tertiary/20 text-primary px-4 py-2 rounded-lg hover:bg-tertiary/30 transition-colors"
                            onclick="this.closest('.fixed').classList.add('hidden')">
                        Cancel
                    </button>
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
                        Create Goal
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 