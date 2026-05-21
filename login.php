<?php
require_once 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        try {
            $stmt = $conn->prepare("SELECT user_id, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } catch(PDOException $e) {
            $error = "An error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mentor Platform</title>
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
        .login-card {
            transition: all 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(54, 44, 122, 0.1);
        }
        .social-icon {
            transition: all 0.3s ease;
        }
        .social-icon:hover {
            transform: translateY(-3px) scale(1.1);
        }
        .input-field {
            transition: all 0.3s ease;
        }
        .input-field:focus {
            transform: translateY(-2px);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-background min-h-screen flex items-center font-['Segoe_UI',_Tahoma,_Geneva,_Verdana,_sans-serif]">
    <div class="max-w-md w-full mx-auto p-5 animate-fade-in">
        <div class="login-card bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-primary text-white text-center p-5">
                <h3 class="text-xl font-semibold mb-0">Welcome Back</h3>
                <p class="mb-0">Login to your account</p>
            </div>
            <div class="p-6 animate-slide-in">
                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-4 animate-bounce-in"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="email" class="block text-primary text-sm font-bold mb-2">Email address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-tertiary"></i>
                            </div>
                            <input type="email" id="email" name="email" required 
                                   class="input-field w-full pl-10 pr-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="block text-primary text-sm font-bold mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-tertiary"></i>
                            </div>
                            <input type="password" id="password" name="password" required 
                                   class="input-field w-full pl-10 pr-3 py-2 border border-tertiary rounded-lg focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-primary hover:bg-secondary text-white font-bold py-2 px-4 rounded-lg transition-all duration-300 mb-3">
                        Login
                    </button>
                </form>
                
                <div class="mt-5 text-center">
                    <p class="text-secondary text-sm">Or login with</p>
                    <div class="mt-2 space-x-4">
                        <a href="#" class="social-icon text-primary hover:text-secondary text-2xl inline-block"><i class="fab fa-google"></i></a>
                        <a href="#" class="social-icon text-primary hover:text-secondary text-2xl inline-block"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-icon text-primary hover:text-secondary text-2xl inline-block"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-sm text-secondary">Don't have an account? <a href="register.php" class="text-primary hover:text-secondary transition-colors duration-300">Sign up</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 