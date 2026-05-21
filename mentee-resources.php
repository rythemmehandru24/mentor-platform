<?php
require_once 'config.php';

// Check if user is logged in and is a mentee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentee') {
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
    
    // Get all available resources from mentors
    $stmt = $conn->prepare("
        SELECT r.*, 
               u.first_name, u.last_name,
               CASE WHEN ra.resource_id IS NOT NULL THEN 1 ELSE 0 END as is_accessed
        FROM resources r
        JOIN users u ON r.mentor_id = u.user_id
        LEFT JOIN resource_access ra ON r.resource_id = ra.resource_id AND ra.mentee_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $resources = $stmt->fetchAll();
    
    // Debug log
    error_log("Resources fetched: " . print_r($resources, true));
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle resource access tracking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'access') {
    try {
        $resource_id = (int)$_POST['resource_id'];
        
        // Start transaction
        $conn->beginTransaction();
        
        // Record resource access in goal_resources table
        $stmt = $conn->prepare("
            INSERT INTO goal_resources (resource_id, mentee_id, viewed_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE viewed_at = NOW()
        ");
        $stmt->execute([$resource_id, $user_id]);
        
        // Record resource access in resource_access table
        $stmt = $conn->prepare("
            INSERT INTO resource_access (resource_id, mentee_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$resource_id, $user_id]);
        
        // Commit transaction
        $conn->commit();
        
        // Get the resource URL and redirect
        $stmt = $conn->prepare("SELECT file_name FROM resources WHERE resource_id = ?");
        $stmt->execute([$resource_id]);
        $resource = $stmt->fetch();
        
        if ($resource) {
            header("Location: " . UPLOAD_URL . "resources/" . $resource['file_name']);
            exit();
        }
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error = "Error accessing resource: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - Mentor Platform</title>
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
                        'dropdown': 'dropdown 0.2s ease-out'
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
                        dropdown: {
                            '0%': { transform: 'translateY(-10px)', opacity: '0' },
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
        .resource-icon {
            transition: transform 0.3s ease;
        }
        .resource-card:hover .resource-icon {
            transform: translateY(-5px);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-background font-['Segoe_UI',_Tahoma,_Geneva,_Verdana,_sans-serif]">
    <div class="min-h-screen flex animate-fade-in">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg py-5 flex-shrink-0 border-r border-tertiary">
            <div class="text-center mb-4">
                <img src="<?php echo $user['profile_picture'] ? UPLOAD_URL . $user['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                     alt="Profile Picture" 
                     class="w-[100px] h-[100px] rounded-full object-cover mx-auto mb-4 border-[3px] border-secondary shadow-lg transition-all duration-300 hover:scale-110 hover:border-primary">
                <h5 class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                <p class="text-secondary">Mentee</p>
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
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="goals.php">
                    <i class="fas fa-bullseye w-5 mr-2.5"></i> Goals
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary bg-tertiary" href="mentee-resources.php">
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
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-primary">Learning Resources</h1>
                <div class="relative">
                    <button type="button" 
                            class="bg-white border-2 border-primary text-primary px-4 py-2 rounded-lg hover:bg-tertiary/20 transition-all duration-300 flex items-center gap-2" 
                            onclick="document.getElementById('filterDropdown').classList.toggle('hidden')">
                        <i class="fas fa-filter"></i> Filter by Category
                    </button>
                    <div id="filterDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg z-10 border border-tertiary animate-dropdown">
                        <div class="py-1">
                            <a class="block px-4 py-2 text-sm text-primary hover:bg-tertiary/20 transition-colors" href="#" onclick="filterResources('all')">All Categories</a>
                            <a class="block px-4 py-2 text-sm text-primary hover:bg-tertiary/20 transition-colors" href="#" onclick="filterResources('Career Development')">Career Development</a>
                            <a class="block px-4 py-2 text-sm text-primary hover:bg-tertiary/20 transition-colors" href="#" onclick="filterResources('Education')">Education</a>
                            <a class="block px-4 py-2 text-sm text-primary hover:bg-tertiary/20 transition-colors" href="#" onclick="filterResources('Personal Growth')">Personal Growth</a>
                            <a class="block px-4 py-2 text-sm text-primary hover:bg-tertiary/20 transition-colors" href="#" onclick="filterResources('Professional Skills')">Professional Skills</a>
                            <a class="block px-4 py-2 text-sm text-primary hover:bg-tertiary/20 transition-colors" href="#" onclick="filterResources('Technical Skills')">Technical Skills</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="resourcesContainer">
                <?php foreach ($resources as $resource): ?>
                    <div class="resource-item" data-category="<?php echo htmlspecialchars($resource['category']); ?>">
                        <div class="resource-card bg-white rounded-xl shadow-lg hover-scale border border-tertiary/30 relative overflow-hidden">
                            <?php if ($resource['is_accessed']): ?>
                                <div class="absolute top-3 right-3 bg-tertiary/20 text-primary px-3 py-1 rounded-full text-xs flex items-center gap-1">
                                    <i class="fas fa-check"></i> Accessed
                                </div>
                            <?php endif; ?>
                            <div class="p-6">
                                <div class="text-center mb-4">
                                    <?php
                                    $icon_class = 'fas fa-file';
                                    switch ($resource['type']) {
                                        case 'document':
                                            $icon_class = 'fas fa-file-alt';
                                            break;
                                        case 'video':
                                            $icon_class = 'fas fa-video';
                                            break;
                                        case 'audio':
                                            $icon_class = 'fas fa-headphones';
                                            break;
                                        case 'image':
                                            $icon_class = 'fas fa-image';
                                            break;
                                    }
                                    ?>
                                    <i class="<?php echo $icon_class; ?> text-4xl text-primary mb-3 resource-icon"></i>
                                    <h5 class="text-lg font-semibold text-primary mb-2"><?php echo htmlspecialchars($resource['title']); ?></h5>
                                    <p class="text-secondary text-sm mb-4"><?php echo htmlspecialchars($resource['description']); ?></p>
                                </div>
                                
                                <div class="flex items-center justify-between text-sm text-secondary mb-4">
                                    <span class="flex items-center">
                                        <i class="fas fa-user mr-1"></i>
                                        <?php echo htmlspecialchars($resource['first_name'] . ' ' . $resource['last_name']); ?>
                                    </span>
                                    <span class="bg-tertiary/20 text-primary px-2 py-1 rounded-full">
                                        <?php echo htmlspecialchars($resource['category']); ?>
                                    </span>
                                </div>
                                
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="access">
                                    <input type="hidden" name="resource_id" value="<?php echo $resource['resource_id']; ?>">
                                    <button type="submit" class="w-full bg-primary hover:bg-secondary text-white px-4 py-2 rounded-lg transition-all duration-300 flex items-center justify-center gap-2">
                                        <i class="fas fa-download"></i> Access Resource
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Filter resources by category
        function filterResources(category) {
            const resources = document.querySelectorAll('.resource-item');
            resources.forEach(resource => {
                if (category === 'all' || resource.dataset.category === category) {
                    resource.style.display = 'block';
                } else {
                    resource.style.display = 'none';
                }
            });
            document.getElementById('filterDropdown').classList.add('hidden');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('filterDropdown');
            const button = event.target.closest('button');
            
            if (!button && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
</body>
</html> 