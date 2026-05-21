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
    
    // Get role-specific profile data
    if ($role == 'mentor') {
        $stmt = $conn->prepare("SELECT * FROM mentor_profiles WHERE mentor_id = ?");
    } else {
        $stmt = $conn->prepare("SELECT * FROM mentee_profiles WHERE mentee_id = ?");
    }
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            error_log("Profile picture upload attempt started");
            error_log("File data: " . print_r($_FILES['profile_picture'], true));
            
            $file = $_FILES['profile_picture'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_error = $file['error'];
            
            // Get file extension
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            error_log("File extension: " . $file_ext);
            
            // Allowed file types
            $allowed = array('jpg', 'jpeg', 'png');
            
            if (in_array($file_ext, $allowed)) {
                if ($file_error === 0) {
                    if ($file_size <= MAX_FILE_SIZE) {
                        // Generate unique filename
                        $file_new_name = uniqid('profile_') . '.' . $file_ext;
                        $file_destination = UPLOAD_PATH . $file_new_name;
                        error_log("Attempting to move file to: " . $file_destination);
                        
                        if (move_uploaded_file($file_tmp, $file_destination)) {
                            error_log("File moved successfully");
                            // Update user profile picture in database
                            $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                            $stmt->execute([$file_new_name, $user_id]);
                            error_log("Database updated with new profile picture: " . $file_new_name);
                            $success = "Profile picture updated successfully!";
                        } else {
                            error_log("Failed to move uploaded file");
                            $error = "Error uploading file";
                        }
                    } else {
                        error_log("File size too large: " . $file_size);
                        $error = "File size too large";
                    }
                } else {
                    error_log("File upload error: " . $file_error);
                    $error = "Error uploading file";
                }
            } else {
                error_log("Invalid file type: " . $file_ext);
                $error = "Invalid file type";
            }
        }
        
        // Update user information
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $qualification = sanitize_input($_POST['qualification']);
        $skills = sanitize_input($_POST['skills']);
        $interests = sanitize_input($_POST['interests']);
        $location = sanitize_input($_POST['location']);
        
        $stmt = $conn->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, qualification = ?, skills = ?, interests = ?, location = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$first_name, $last_name, $qualification, $skills, $interests, $location, $user_id]);
        
        // Update role-specific information
        if ($role == 'mentor') {
            $hourly_rate = sanitize_input($_POST['hourly_rate']);
            $availability = sanitize_input($_POST['availability']);
            
            $stmt = $conn->prepare("
                UPDATE mentor_profiles 
                SET hourly_rate = ?, availability = ?
                WHERE mentor_id = ?
            ");
            $stmt->execute([$hourly_rate, $availability, $user_id]);
        } else {
            $goals = sanitize_input($_POST['goals']);
            $preferred_topics = sanitize_input($_POST['preferred_topics']);
            
            $stmt = $conn->prepare("
                UPDATE mentee_profiles 
                SET goals = ?, preferred_mentoring_topics = ?
                WHERE mentee_id = ?
            ");
            $stmt->execute([$goals, $preferred_topics, $user_id]);
        }
        
        $success = "Profile updated successfully!";
        
        // Refresh user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($role == 'mentor') {
            $stmt = $conn->prepare("SELECT * FROM mentor_profiles WHERE mentor_id = ?");
        } else {
            $stmt = $conn->prepare("SELECT * FROM mentee_profiles WHERE mentee_id = ?");
        }
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
        
    } catch(PDOException $e) {
        $error = "An error occurred. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Mentor Platform</title>
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
                <?php
                error_log("User data: " . print_r($user, true));
                error_log("Profile picture path: " . ($user['profile_picture'] ? UPLOAD_URL . $user['profile_picture'] : 'assets/images/default-avatar.jpg'));
                ?>
                <img src="<?php echo $user['profile_picture'] ? UPLOAD_URL . $user['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                     alt="Profile Picture" 
                     class="w-[100px] h-[100px] rounded-full object-cover mx-auto mb-4 border-[3px] border-secondary shadow-lg transition-all duration-300 hover:scale-110 hover:border-primary profile-picture">
                <h5 class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                <p class="text-secondary"><?php echo ucfirst($role); ?></p>
            </div>
            
            <nav class="space-y-1 px-3">
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="dashboard.php">
                    <i class="fas fa-home w-5 mr-2.5"></i> Dashboard
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary bg-tertiary" href="profile.php">
                    <i class="fas fa-user w-5 mr-2.5"></i> Profile
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="messages.php">
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
        <div class="flex-1 p-8 animate-slide-in">
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 transition-all duration-300 hover:shadow-md">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 transition-all duration-300 hover:shadow-md">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-xl shadow-lg transition-all duration-300 hover:shadow-xl hover-scale">
                <div class="p-6">
                    <h2 class="text-2xl font-bold mb-6 text-primary">Edit Profile</h2>
                    
                    <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div class="text-center mb-8">
                            <div class="relative inline-block">
                                <img src="<?php echo $user['profile_picture'] ? UPLOAD_URL . $user['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                                     alt="Profile Picture" 
                                     class="w-[150px] h-[150px] rounded-full object-cover mx-auto border-[3px] border-secondary shadow-lg transition-all duration-300 hover:scale-110 hover:border-primary profile-picture">
                                <label for="profile_picture" class="absolute bottom-0 right-0 w-10 h-10 bg-primary hover:bg-secondary text-white rounded-full flex items-center justify-center cursor-pointer transition-all duration-300 hover:scale-110">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" 
                                       class="hidden" 
                                       id="profile_picture" 
                                       name="profile_picture" 
                                       accept="image/*">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-secondary mb-2">First Name</label>
                                <input type="text" 
                                       class="w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" 
                                       required>
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-secondary mb-2">Last Name</label>
                                <input type="text" 
                                       class="w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" 
                                       required>
                            </div>
                        </div>
                        
                    <div class="mb-6">
                        <label for="qualification" class="block text-sm font-medium text-secondary mb-2">Qualification</label>
                        <textarea class="w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent" 
                                id="qualification" 
                                name="qualification" 
                                rows="2"
                                placeholder="Digilocker links of Degree and Qualifications"><?php echo htmlspecialchars($user['Qualification'] ?? ''); ?></textarea>
                   </div>
                        
                        <div class="mb-6">
                            <label for="skills" class="block text-sm font-medium text-secondary mb-2">Skills</label>
                            <input type="text" 
                                   class="w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent" 
                                   id="skills" 
                                   name="skills" 
                                   value="<?php echo htmlspecialchars($user['skills'] ?? ''); ?>"
                                   placeholder="Enter skills separated by commas">
                        </div>
                        
                        <div class="mb-6">
                            <label for="interests" class="block text-sm font-medium text-secondary mb-2">Interests</label>
                            <input type="text" 
                                   class="w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent" 
                                   id="interests" 
                                   name="interests" 
                                   value="<?php echo htmlspecialchars($user['interests'] ?? ''); ?>"
                                   placeholder="Enter interests separated by commas">
                        </div>
                        
                        <div class="mb-6">
                            <label for="location" class="block text-sm font-medium text-secondary mb-2">Location</label>
                            <input type="text" 
                                   class="w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent" 
                                   id="location" 
                                   name="location" 
                                   value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                        </div>
                        
                        <?php if ($role == 'mentor'): ?>
                            <div class="mb-6">
                                <label for="hourly_rate" class="block text-sm font-medium text-secondary mb-2">Hourly Rate (₹)</label>
                                <input type="number" 
                                       class="w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent" 
                                       id="hourly_rate" 
                                       name="hourly_rate" 
                                       value="<?php echo htmlspecialchars($profile['hourly_rate'] ?? ''); ?>"
                                       min="99" 
                                       step="100">
                            </div>
                            
                            <div class="mb-6">
                                <label for="availability" class="block text-sm font-medium text-secondary mb-2">Availability</label>
                                <textarea class="w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent" 
                                          id="availability" 
                                          name="availability" 
                                          rows="3"><?php echo htmlspecialchars($profile['availability'] ?? ''); ?></textarea>
                            </div>
                        <?php else: ?>
                            <div class="mb-6">
                                <label for="goals" class="block text-sm font-medium text-secondary mb-2">Goals</label>
                                <textarea class="w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent" 
                                          id="goals" 
                                          name="goals" 
                                          rows="3"><?php echo htmlspecialchars($profile['goals'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-6">
                                <label for="preferred_topics" class="block text-sm font-medium text-secondary mb-2">Preferred Mentoring Topics</label>
                                <textarea class="w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent" 
                                          id="preferred_topics" 
                                          name="preferred_topics" 
                                          rows="3"><?php echo htmlspecialchars($profile['preferred_mentoring_topics'] ?? ''); ?></textarea>
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="bg-primary hover:bg-secondary text-white font-bold py-2 px-6 rounded-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-lg">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Preview profile picture before upload
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-picture').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 