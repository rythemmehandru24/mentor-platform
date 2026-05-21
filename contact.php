<?php
require_once 'config.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message_content = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message_content)) {
        $message = 'Please fill in all fields.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        try {
            // Using PDO syntax with the $conn variable
$sql = "INSERT INTO contact_form_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)";            $stmt = $conn->prepare($sql);
            
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':subject' => $subject,
                ':message' => $message_content
            ]);

            $message = 'Thank you for your message. We will get back to you soon!';
            $messageType = 'success';
            
        } catch (PDOException $e) {
            // TEMPORARILY display the exact database error
            $message = 'DB Error: ' . $e->getMessage(); 
            $messageType = 'error';
        } catch (Exception $e) {
            // TEMPORARILY display the exact system error
            $message = 'System Error: ' . $e->getMessage(); 
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Mentor Platform</title>
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
                        background: '#F9F5ED'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'bg-slide': 'bgSlide 25s infinite linear',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        bgSlide: {
                            '0%': { opacity: '0', transform: 'scale(1)' },
                            '5%': { opacity: '0.2' },
                            '20%': { opacity: '0.2' },
                            '25%': { opacity: '0', transform: 'scale(1.1)' },
                            '100%': { opacity: '0' }
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="font-sans leading-relaxed">
    <nav class="bg-white shadow-md fixed w-full z-50 animate-fade-in">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center no-underline">
                    <img src="assets/images/Logo.jpg" alt="Logo" class="h-10 w-auto">
                    <span class="ml-2 text-primary text-xl font-bold tracking-tight">Mentor Platform</span>
                </a>
                
                <div class="hidden md:flex space-x-4 items-center">
                    <a class="text-gray-600 hover:text-primary transition-colors" href="index.php">Home</a>
                    <a class="text-gray-600 hover:text-primary transition-colors" href="index.php#features">Features</a>
                    <a class="text-gray-600 hover:text-primary transition-colors" href="index.php#how-it-works">How It Works</a>
                    <a class="text-gray-600 hover:text-primary transition-colors" href="about.php">About Us</a>
                    <a class="text-primary font-semibold border-b-2 border-primary" href="contact.php">Contact</a>
                    <a class="text-gray-600 hover:text-primary transition-colors" href="login.php">Login</a>
                    <a class="bg-primary text-white px-4 py-2 rounded-full hover:bg-opacity-90 transition-colors" href="register.php">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="relative min-h-[450px] flex items-center justify-center bg-primary text-white pt-24 pb-16 overflow-hidden">
        <div class="absolute inset-0 z-0">
            <div class="absolute inset-0 opacity-0 animate-bg-slide bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=1600');"></div>
            <div class="absolute inset-0 opacity-0 animate-bg-slide [animation-delay:5s] bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1600');"></div>
            <div class="absolute inset-0 opacity-0 animate-bg-slide [animation-delay:10s] bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?q=80&w=1600');"></div>
            <div class="absolute inset-0 opacity-0 animate-bg-slide [animation-delay:15s] bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1556761175-b413da4baf72?q=80&w=1600');"></div>
            <div class="absolute inset-0 opacity-0 animate-bg-slide [animation-delay:20s] bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1552664730-d307ca884978?q=80&w=1600');"></div>
            <div class="absolute inset-0 bg-primary/40 z-10"></div>
        </div>

        <div class="container relative z-20 mx-auto px-4 text-center animate-slide-up">
            <div class="flex flex-col items-center mb-6">
                <div class="bg-white p-4 rounded-full mb-4 shadow-lg flex items-center justify-center w-20 h-20">
                    <i class="fas fa-graduation-cap text-4xl text-primary"></i> 
                </div>
                <span class="text-tertiary font-bold tracking-[0.3em] uppercase text-sm">Mentor Program</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold mb-6">Contact Us</h1>
            <p class="text-xl mb-8">Have questions? We'd love to hear from you.</p>
        </div>
    </section>

    <section class="py-16 bg-background relative z-30">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto -mt-24"> <?php if ($message): ?>
                    <div class="mb-8 p-4 rounded-lg shadow-md <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> animate-fade-in">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-xl shadow-2xl p-8 animate-fade-in border border-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div class="text-center p-6 bg-background rounded-lg hover:shadow-md transition-shadow">
                            <div class="bg-secondary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-envelope text-3xl text-secondary"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-primary mb-2">Email Us</h3>
                            <p class="text-gray-600 text-sm">mentorplatform@gmail.com</p>
                        </div>
                        <div class="text-center p-6 bg-background rounded-lg hover:shadow-md transition-shadow">
                            <div class="bg-secondary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-phone text-3xl text-secondary"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-primary mb-2">Call Us</h3>
                            <p class="text-gray-600 text-sm">+91 6239845115</p>
                        </div>
                    </div>
                    
                    <form action="contact.php" method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input type="text" id="name" name="name" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                                    placeholder="Your name">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" id="email" name="email" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                                    placeholder="your@email.com">
                            </div>
                        </div>
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                            <input type="text" id="subject" name="subject" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                                placeholder="How can we help?">
                        </div>
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                            <textarea id="message" name="message" rows="5" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                                placeholder="Your message..."></textarea>
                        </div>
                        <button type="submit"
                            class="w-full bg-primary text-white py-3 px-6 rounded-lg hover:bg-opacity-90 transition-all font-bold shadow-lg transform hover:-translate-y-1">
                            Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-primary text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center md:text-left">
                <div>
                    <h5 class="text-xl font-semibold mb-4">Mentor Platform</h5>
                    <p class="text-gray-300">Connecting mentors and mentees for professional growth.</p>
                </div>
                <div>
                    <h5 class="text-xl font-semibold mb-4">Quick Links</h5>
                    <ul class="space-y-2">
                        <li><a href="about.php" class="text-gray-300 hover:text-tertiary transition-colors">About Us</a></li>
                        <li><a href="contact.php" class="text-gray-300 hover:text-tertiary transition-colors">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="text-xl font-semibold mb-4">Connect With Us</h5>
                    <div class="flex justify-center md:justify-start space-x-4">
                        <a href="https://x.com/RMehandru15984" target="_blank" class="text-gray-300 hover:text-tertiary transition-colors"><i class="fab fa-twitter text-xl"></i></a>
                        <a href="https://www.linkedin.com/in/rythem-mehandru-aa2989199/" target="_blank" class="text-gray-300 hover:text-tertiary transition-colors"><i class="fab fa-linkedin text-xl"></i></a>
                    </div>
                </div>
            </div>
            <hr class="border-secondary my-8">
            <div class="text-center text-gray-300">
                <p>&copy; 2025-26 Mentor Platform. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>