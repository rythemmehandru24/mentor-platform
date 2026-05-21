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
    
    // Get mentee profile data
    $stmt = $conn->prepare("SELECT * FROM mentee_profiles WHERE mentee_id = ?");
    $stmt->execute([$user_id]);
    $mentee_profile = $stmt->fetch();
    
    // Get search parameters
    $search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
    $category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
    $min_rate = isset($_GET['min_rate']) ? (float)$_GET['min_rate'] : 0;
    $max_rate = isset($_GET['max_rate']) ? (float)$_GET['max_rate'] : 699;
    $availability = isset($_GET['availability']) ? sanitize_input($_GET['availability']) : '';
    $sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'rating';
    
    // Build query for mentor search
    $query = "
        SELECT u.*, 
               mp.*,
               AVG(r.rating) as average_rating,
               COUNT(DISTINCT r.review_id) as total_reviews,
               COUNT(DISTINCT s.session_id) as total_sessions
        FROM users u
        JOIN mentor_profiles mp ON u.user_id = mp.mentor_id
        LEFT JOIN reviews r ON u.user_id = r.mentor_id
        LEFT JOIN sessions s ON u.user_id = s.mentor_id
        WHERE u.role = 'mentor'
    ";
    
    $params = [];
    
    if ($search) {
        // Added CONCAT to match against the full "First Last" string
        $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.skills LIKE ?)";
        $search_param = "%$search%";
        // Added a 4th parameter for the CONCAT check
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    if ($category) {
        $query .= " AND u.skills LIKE ?";
        $params[] = "%$category%";
    }
    
    if ($min_rate > 0) {
        $query .= " AND mp.hourly_rate >= ?";
        $params[] = $min_rate;
    }
    
    if ($max_rate < 1000) {
        $query .= " AND mp.hourly_rate <= ?";
        $params[] = $max_rate;
    }
    
    if ($availability) {
        $query .= " AND mp.availability LIKE ?";
        $params[] = "%$availability%";
    }
    
    $query .= " GROUP BY u.user_id";
    
    // Add sorting
    switch ($sort) {
        case 'rating':
            $query .= " ORDER BY average_rating DESC";
            break;
        case 'price_low':
            $query .= " ORDER BY mp.hourly_rate ASC";
            break;
        case 'price_high':
            $query .= " ORDER BY mp.hourly_rate DESC";
            break;
        case 'sessions':
            $query .= " ORDER BY total_sessions DESC";
            break;
        default:
            $query .= " ORDER BY average_rating DESC";
    }
    
    // Get mentors
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $mentors = $stmt->fetchAll();
    
    // Get unique categories for filter
    $stmt = $conn->prepare("
        SELECT DISTINCT skills as category
        FROM users
        WHERE role = 'mentor' AND skills IS NOT NULL
        ORDER BY skills
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Mentor - Mentor Platform</title>
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
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideIn: { '0%': { transform: 'translateY(20px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
                        scale: { '0%': { transform: 'scale(1)' }, '100%': { transform: 'scale(1.02)' } },
                        dropdown: { '0%': { transform: 'translateY(-10px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } }
                    }
                }
            }
        }
    </script>
    <style>
        .hover-scale { transition: transform 0.3s ease; }
        .hover-scale:hover { transform: scale(1.02); }
        .mentor-card { transition: all 0.3s ease; }
        .mentor-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(54, 44, 122, 0.1); }
        .skill-tag { transition: all 0.3s ease; }
        .mentor-card:hover .skill-tag { transform: translateY(-2px); }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-background font-['Segoe_UI',_Tahoma,_Geneva,_Verdana,_sans-serif]">
    <div class="min-h-screen flex animate-fade-in">
        <div class="w-64 bg-white shadow-lg py-5 flex-shrink-0 border-r border-tertiary">
            <div class="text-center mb-4">
                <?php 
                    $user_pic = $user['profile_picture'] ?? '';
                    $user_pic_url = (!empty($user_pic) && file_exists('uploads/' . $user_pic)) ? UPLOAD_URL . $user_pic : 'assets/images/default-avatar.jpg';
                ?>
                <img src="<?php echo $user_pic_url; ?>" 
                     alt="Profile Picture" 
                     class="w-[100px] h-[100px] rounded-full object-cover mx-auto mb-4 border-[3px] border-secondary shadow-lg transition-all duration-300 hover:scale-110 hover:border-primary">
                <h5 class="text-lg font-semibold text-primary"><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></h5>
                <p class="text-secondary"><?php echo ucfirst($role); ?></p>
            </div>
            
            <nav class="space-y-1 px-3">
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="dashboard.php"><i class="fas fa-home w-5 mr-2.5"></i> Dashboard</a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="profile.php"><i class="fas fa-user w-5 mr-2.5"></i> Profile</a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="messages.php"><i class="fas fa-comments w-5 mr-2.5"></i> Messages</a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="sessions.php"><i class="fas fa-calendar w-5 mr-2.5"></i> Sessions</a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="goals.php"><i class="fas fa-bullseye w-5 mr-2.5"></i> Goals</a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="mentee-resources.php"><i class="fas fa-book w-5 mr-2.5"></i> Resources</a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary bg-tertiary" href="find-mentor.php"><i class="fas fa-search w-5 mr-2.5"></i> Find Mentor</a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-white bg-red-500 transition-all duration-300 hover:bg-red-600" href="logout.php"><i class="fas fa-sign-out-alt w-5 mr-2.5"></i> Logout</a>
            </nav>
        </div>
        
        <div class="flex-1 p-8 animate-slide-in">
            <?php if ($success): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4"><?php echo $error; ?></div><?php endif; ?>
            
            <h1 class="text-2xl font-bold text-primary mb-6">Find Your Perfect Mentor</h1>
            
            <div class="bg-white p-6 rounded-xl shadow-lg mb-6 border border-tertiary/30">
                <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-4">
                        <label class="block text-sm font-medium text-primary mb-1">Search</label>
                        <input type="text" class="w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition-all duration-300" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, skills, or expertise">
                    </div>
                    
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-primary mb-1">Category</label>
                        <select class="w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition-all duration-300" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category == $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-primary mb-1">Price Range (₹)</label>
                        <div class="flex">
                            <input type="number" class="w-1/2 px-3 py-2 border border-tertiary rounded-l-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition-all duration-300" name="min_rate" value="<?php echo $min_rate; ?>" placeholder="Min">
                            <span class="flex items-center px-2 bg-tertiary/20 border-t border-b border-tertiary text-primary">-</span>
                            <input type="number" class="w-1/2 px-3 py-2 border border-tertiary rounded-r-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition-all duration-300" name="max_rate" value="<?php echo $max_rate; ?>" placeholder="Max">
                        </div>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-primary mb-1">Sort By</label>
                        <select class="w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent transition-all duration-300" name="sort">
                            <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="sessions" <?php echo $sort == 'sessions' ? 'selected' : ''; ?>>Most Sessions</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-12">
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-all duration-300 flex items-center gap-2"><i class="fas fa-search"></i> Search</button>
                    </div>
                </form>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($mentors as $mentor): ?>
                    <div class="mentor-card bg-white rounded-xl shadow-lg border border-tertiary/30 overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start mb-4">
                                <?php 
                                    $mentor_pic = $mentor['profile_picture'] ?? '';
                                    $mentor_pic_url = (!empty($mentor_pic) && file_exists('uploads/' . $mentor_pic)) ? UPLOAD_URL . $mentor_pic : 'assets/images/default-avatar.jpg';
                                ?>
                                <img src="<?php echo $mentor_pic_url; ?>" 
                                     alt="Mentor Picture" 
                                     class="w-20 h-20 rounded-full object-cover mr-4 border-2 border-secondary shadow-sm transition-all duration-300 hover:scale-110 hover:border-primary flex-shrink-0">
                                <div class="flex-1 min-w-0">
                                    <h5 class="text-xl font-semibold mb-2 truncate text-primary"><?php echo htmlspecialchars(($mentor['first_name'] ?? '') . ' ' . ($mentor['last_name'] ?? '')); ?></h5>
                                    <div class="flex flex-col space-y-2">
                                        <div class="flex items-center text-yellow-500">
                                            <i class="fas fa-star mr-1"></i>
                                            <span class="mr-1"><?php echo $mentor['average_rating'] !== null ? number_format($mentor['average_rating'], 1) : '0.0'; ?></span>
                                            <span class="text-secondary text-sm">(<?php echo $mentor['total_reviews']; ?> reviews)</span>
                                        </div>
                                        <div class="flex items-center text-primary"><i class="fas fa-calendar-check mr-1"></i><span><?php echo $mentor['total_sessions']; ?> sessions</span></div>
                                        <div class="text-primary font-bold">₹<?php echo number_format($mentor['hourly_rate'], 0); ?>/session</div>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="text-secondary mb-4 line-clamp-3"><?php echo nl2br(htmlspecialchars($mentor['bio'] ?? '')); ?></p>
                            
                            <div class="mb-4">
                                <strong class="text-primary block mb-2">Expertise:</strong>
                                <div class="flex flex-wrap gap-2">
                                    <?php
                                    $skills_list = $mentor['skills'] ?? '';
                                    if (!empty(trim($skills_list))) {
                                        $expertise = explode(',', $skills_list);
                                        foreach ($expertise as $skill) {
                                            echo '<span class="skill-tag inline-block px-3 py-1 bg-tertiary/20 text-primary rounded-full text-xs">' . htmlspecialchars(trim($skill)) . '</span>';
                                        }
                                    } else {
                                        echo '<span class="text-gray-400 text-xs italic">No skills listed</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-2 pt-2 border-t border-tertiary/30">
                                <a href="messages.php?user=<?php echo $mentor['user_id']; ?>" class="flex-1 border border-primary text-primary px-3 py-2 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 flex items-center justify-center gap-2 text-sm font-semibold">
                                    <i class="fas fa-comment-alt"></i> Message
                                </a>
                                <a href="schedule-session.php?mentor_id=<?php echo $mentor['user_id']; ?>" class="flex-1 bg-primary text-white px-3 py-2 rounded-lg hover:bg-secondary transition-all duration-300 flex items-center justify-center gap-2 text-sm font-semibold">
                                    <i class="fas fa-calendar-plus"></i> Schedule
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($mentors)): ?>
                    <div class="col-span-full"><div class="bg-tertiary/20 border border-tertiary text-primary px-4 py-3 rounded-lg">No mentors found matching your criteria. Try adjusting your search filters.</div></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>