<?php
require_once 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $role = sanitize_input($_POST['role']);
    
    // Validate input
    if (empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name)) {
        $error = "Please fill in all fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "Email already registered";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$email, $hashed_password, $first_name, $last_name, $role]);
                
                // Create corresponding profile
                $user_id = $conn->lastInsertId();
                if ($role == 'mentor') {
                    $stmt = $conn->prepare("INSERT INTO mentor_profiles (mentor_id) VALUES (?)");
                    $stmt->execute([$user_id]);
                } else {
                    $stmt = $conn->prepare("INSERT INTO mentee_profiles (mentee_id) VALUES (?)");
                    $stmt->execute([$user_id]);
                }
                
                $success = "Registration successful! Please login.";
            }
        } catch(PDOException $e) {
            $error = "An error occurred: " . $e->getMessage();
            error_log("Registration Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Mentor Platform</title>
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
        .role-option {
            transition: all 0.3s ease;
        }
        .role-option:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(54, 44, 122, 0.1);
        }
        .role-option.selected {
            background-color: #362C7A;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(54, 44, 122, 0.2);
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
<body class="bg-background min-h-screen flex items-center py-5 font-['Segoe_UI',_Tahoma,_Geneva,_Verdana,_sans-serif]">
    <div class="max-w-[500px] w-full mx-auto px-5 animate-fade-in">
        <div class="bg-white rounded-xl shadow-lg border border-tertiary/30">
            <div class="bg-primary text-white text-center rounded-t-xl p-5">
                <h3 class="text-xl font-semibold mb-1">Create Account</h3>
                <p class="m-0">Join our mentor platform</p>
            </div>
            <div class="p-6">
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 animate-bounce-in"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 animate-bounce-in"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm" class="animate-slide-in">
                    <div class="flex gap-5 mb-5">
                        <div class="flex-1 p-4 border-2 border-primary rounded-xl text-center cursor-pointer role-option" data-role="mentor">
                            <i class="fas fa-user-graduate text-2xl mb-2 text-primary"></i>
                            <h5 class="text-lg font-semibold text-primary">Mentor</h5>
                            <p class="m-0 text-secondary">Share your expertise</p>
                        </div>
                        <div class="flex-1 p-4 border-2 border-primary rounded-xl text-center cursor-pointer role-option" data-role="mentee">
                            <i class="fas fa-user text-2xl mb-2 text-primary"></i>
                            <h5 class="text-lg font-semibold text-primary">Mentee</h5>
                            <p class="m-0 text-secondary">Learn and grow</p>
                        </div>
                    </div>
                    
                    <input type="hidden" name="role" id="role" required>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-primary mb-1">First Name</label>
                            <input type="text" 
                                   class="form-input w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-tertiary" 
                                   id="first_name" 
                                   name="first_name" 
                                   required>
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-primary mb-1">Last Name</label>
                            <input type="text" 
                                   class="form-input w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-tertiary" 
                                   id="last_name" 
                                   name="last_name" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-primary mb-1">Email address</label>
                        <input type="email" 
                               class="form-input w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-tertiary" 
                               id="email" 
                               name="email" 
                               required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-primary mb-1">Password</label>
                        <input type="password" 
                               class="form-input w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-tertiary" 
                               id="password" 
                               name="password" 
                               required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="block text-sm font-medium text-primary mb-1">Confirm Password</label>
                        <input type="password" 
                               class="form-input w-full px-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-tertiary" 
                               id="confirm_password" 
                               name="confirm_password" 
                               required>
                    </div>
                    
                    <button type="submit" class="submit-button w-full bg-primary hover:bg-secondary text-white py-2 px-4 rounded-lg transition-all duration-300 mb-4">
                        Create Account
                    </button>
                    
                    <div class="text-center">
                        <p class="m-0 text-secondary">Already have an account? <a href="login.php" class="text-primary hover:text-secondary transition-colors duration-300">Login</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleOptions = document.querySelectorAll('.role-option');
            const roleInput = document.getElementById('role');
            
            roleOptions.forEach(option => {
                option.addEventListener('click', function() {
                    roleOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    roleInput.value = this.dataset.role;
                });
            });
            
            // Form validation
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                if (!roleInput.value) {
                    e.preventDefault();
                    alert('Please select a role');
                }
            });
        });
    </script>
</body>
</html> 