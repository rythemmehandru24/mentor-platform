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

// Get session ID from URL
$session_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$session_id) {
    header("Location: sessions.php");
    exit();
}

try {
    // Get user data
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Get session details
    $stmt = $conn->prepare("
        SELECT s.*, 
               m.first_name as mentor_first_name,
               m.last_name as mentor_last_name,
               m.profile_picture as mentor_picture
        FROM sessions s
        JOIN users m ON s.mentor_id = m.user_id
        WHERE s.session_id = ? AND s.mentee_id = ? AND s.status = 'completed'
    ");
    $stmt->execute([$session_id, $user_id]);
    $session = $stmt->fetch();
    
    if (!$session) {
        header("Location: sessions.php");
        exit();
    }
    
    // Check if review already exists
    $stmt = $conn->prepare("SELECT * FROM reviews WHERE session_id = ?");
    $stmt->execute([$session_id]);
    if ($stmt->fetch()) {
        header("Location: session-details.php?id=" . $session_id);
        exit();
    }
    
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $rating = (int)$_POST['rating'];
        $comment = sanitize_input($_POST['comment']);
        
        // Validate input
        if ($rating < 1 || $rating > 5) {
            $error = "Please select a valid rating";
        } elseif (empty($comment)) {
            $error = "Please provide a comment";
        } else {
            // Insert review
            $stmt = $conn->prepare("
                INSERT INTO reviews (session_id, mentor_id, mentee_id, rating, comment)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$session_id, $session['mentor_id'], $user_id, $rating, $comment]);
            
            // Update mentor's average rating
            $stmt = $conn->prepare("
                UPDATE mentor_profiles 
                SET rating = (
                    SELECT AVG(rating) 
                    FROM reviews 
                    WHERE mentor_id = ?
                )
                WHERE mentor_id = ?
            ");
            $stmt->execute([$session['mentor_id'], $session['mentor_id']]);
            
            $success = "Review submitted successfully!";
            
            // Redirect to session details page after 2 seconds
            header("refresh:2;url=session-details.php?id=" . $session_id);
        }
    } catch(PDOException $e) {
        error_log("Review submission error: " . $e->getMessage());
        $error = "An error occurred. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Session - Mentor Platform</title>
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
        .rating-stars i {
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .rating-stars i:hover,
        .rating-stars i.hover,
        .rating-stars i.active {
            color: #735CC6;
            transform: scale(1.1);
        }
        
        .rating-stars .fas {
            -webkit-text-stroke: 1px #735CC6;
        }
        
        .mentor-card {
            transition: all 0.3s ease;
        }
        .mentor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(54, 44, 122, 0.1);
        }
        .mentor-image {
            transition: all 0.3s ease;
        }
        .mentor-card:hover .mentor-image {
            transform: scale(1.05);
            border-color: #735CC6;
        }
        .form-input {
            transition: all 0.3s ease;
        }
        .form-input:focus {
            border-color: #735CC6;
            box-shadow: 0 0 0 3px rgba(115, 92, 198, 0.1);
        }
        .submit-button {
            transition: all 0.3s ease;
        }
        .submit-button:hover {
            transform: translateY(-2px);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-background font-['Segoe_UI',_Tahoma,_Geneva,_Verdana,_sans-serif]">
    <div class="flex animate-fade-in">
        <!-- Sidebar -->
        <div class="w-64 bg-white min-h-screen shadow-lg py-5 flex-shrink-0 border-r border-tertiary">
            <div class="text-center mb-4">
                <img src="<?php echo $user['profile_picture'] ? UPLOAD_PATH . $user['profile_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
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
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary bg-tertiary" href="sessions.php">
                    <i class="fas fa-calendar w-5 mr-2.5"></i> Sessions
                </a>
                <a class="flex items-center px-5 py-2.5 rounded-md text-primary transition-all duration-300 hover:bg-tertiary hover:text-primary" href="goals.php">
                    <i class="fas fa-bullseye w-5 mr-2.5"></i> Goals
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
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 animate-bounce-in"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 animate-bounce-in"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="bg-white rounded-xl shadow-lg border border-tertiary/30 mb-6">
                <div class="p-5 border-b border-tertiary/30">
                    <h1 class="text-2xl font-bold text-primary m-0">Review Session</h1>
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="mentor-card text-center">
                            <img src="<?php echo $session['mentor_picture'] ? UPLOAD_PATH . $session['mentor_picture'] : 'assets/images/default-avatar.jpg'; ?>" 
                                 alt="Mentor Picture" 
                                 class="mentor-image w-[100px] h-[100px] rounded-full object-cover mx-auto mb-4 border-[3px] border-secondary shadow-lg transition-all duration-300 hover:scale-110 hover:border-primary">
                            <h5 class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($session['mentor_first_name'] . ' ' . $session['mentor_last_name']); ?></h5>
                            <p class="text-secondary">Mentor</p>
                        </div>
                        <div>
                            <h6 class="text-lg font-semibold text-primary mb-3">Session Information</h6>
                            <p class="mb-2 text-secondary"><span class="font-semibold text-primary">Date:</span> <?php echo date('M d, Y', strtotime($session['start_time'])); ?></p>
                            <p class="mb-2 text-secondary"><span class="font-semibold text-primary">Time:</span> <?php echo date('h:i A', strtotime($session['start_time'])); ?> - <?php echo date('h:i A', strtotime($session['end_time'])); ?></p>
                            <p class="mb-2 text-secondary"><span class="font-semibold text-primary">Duration:</span> <?php echo round((strtotime($session['end_time']) - strtotime($session['start_time'])) / 3600); ?> hours</p>
                        </div>
                    </div>
                    
                    <form method="POST" action="" id="reviewForm">
                        <input type="hidden" id="rating" name="rating" value="">
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-primary mb-2">Your Rating</label>
                            <div class="rating-stars text-3xl mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star" data-rating="<?php echo $i; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p id="ratingLabel" class="text-secondary text-sm"></p>
                        </div>
                        
                        <div class="mb-6">
                            <label for="comment" class="block text-sm font-medium text-primary mb-2">Your Review</label>
                            <textarea id="comment" 
                                      name="comment" 
                                      rows="4" 
                                      class="form-input w-full px-4 py-2 rounded-lg border border-tertiary focus:outline-none focus:ring-2 focus:ring-tertiary"
                                      placeholder="Share your experience with this mentoring session"></textarea>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="submit-button px-6 py-2 bg-primary hover:bg-secondary text-white rounded-lg transition-all duration-300">
                                Submit Review
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const stars = document.querySelectorAll('.rating-stars i');
        const ratingInput = document.getElementById('rating');
        const ratingLabel = document.getElementById('ratingLabel');
        let selectedRating = 0;
        
        const ratingText = {
            0: '',
            1: 'Poor - Not satisfied with the mentoring',
            2: 'Fair - Some room for improvement',
            3: 'Good - Met expectations',
            4: 'Very Good - Above expectations',
            5: 'Excellent - Outstanding mentoring'
        };
        
        function updateStars(rating, isHover = false) {
            stars.forEach(star => {
                const starRating = parseInt(star.dataset.rating);
                if (starRating <= rating) {
                    star.classList.add(isHover ? 'hover' : 'active');
                } else {
                    star.classList.remove(isHover ? 'hover' : 'active');
                }
            });
            
            if (!isHover) {
                selectedRating = rating;
                ratingInput.value = rating;
            }
            ratingLabel.textContent = ratingText[rating];
        }
        
        stars.forEach(star => {
            // Hover effect
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                updateStars(rating, true);
            });
            
            // Click event
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                updateStars(rating);
            });
        });
        
        // Mouse leave rating container
        document.querySelector('.rating-stars').addEventListener('mouseleave', function() {
            updateStars(selectedRating);
        });
        
        // Form validation
        document.getElementById('reviewForm').addEventListener('submit', function(e) {
            if (!ratingInput.value) {
                e.preventDefault();
                alert('Please select a rating');
                return false;
            }
            
            const comment = document.getElementById('comment').value.trim();
            if (!comment) {
                e.preventDefault();
                alert('Please provide a review comment');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html> 