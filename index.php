<?php
require_once 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Platform - Connect with Expert Mentors</title>
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
                    fontFamily: {
                        sans: ['Segoe UI', 'Tahoma', 'Geneva', 'Verdana', 'sans-serif'],
                        display: ['Poppins', 'Segoe UI', 'Tahoma', 'sans-serif'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'bounce-in': 'bounceIn 0.5s cubic-bezier(0.36, 0, 0.66, -0.56)',
                        'bg-slide': 'bgSlide 25s infinite linear',
                    },
                    keyframes: {
                        fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        bounceIn: {
                            '0%': { transform: 'scale(0.3)', opacity: '0' },
                            '50%': { transform: 'scale(1.05)' },
                            '70%': { transform: 'scale(0.9)' },
                            '100%': { transform: 'scale(1)', opacity: '1' },
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600&display=swap" rel="stylesheet">
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
                    <a class="text-gray-600 hover:text-primary transition-colors" href="#features">Features</a>
                    <a class="text-gray-600 hover:text-primary transition-colors" href="#how-it-works">How It Works</a>
                    <a class="text-gray-600 hover:text-primary transition-colors" href="about.php">About Us</a>
                    <a class="text-gray-600 hover:text-primary transition-colors" href="contact.php">Contact</a>
                    <a class="text-gray-600 hover:text-primary transition-colors" href="login.php">Login</a>
                    <a class="bg-primary text-white px-4 py-2 rounded-full hover:bg-opacity-90 transition-colors" href="register.php">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <section id="top" class="relative min-h-screen flex items-center justify-center overflow-hidden bg-primary text-white">
        <div class="absolute inset-0 z-0">
            <div class="absolute inset-0 opacity-0 animate-bg-slide bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=1600');"></div>
            <div class="absolute inset-0 opacity-0 animate-bg-slide [animation-delay:5s] bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1515187029135-18ee286d815b?q=80&w=1600');"></div>
            <div class="absolute inset-0 opacity-0 animate-bg-slide [animation-delay:10s] bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?q=80&w=1600');"></div>
            <div class="absolute inset-0 opacity-0 animate-bg-slide [animation-delay:15s] bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=1600');"></div>
            <div class="absolute inset-0 opacity-0 animate-bg-slide [animation-delay:20s] bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1552664730-d307ca884978?q=80&w=1600');"></div>
            
            <div class="absolute inset-0 bg-primary/60 z-10"></div>
        </div>

        <div class="container relative z-20 mx-auto px-4 text-center">
            <div class="max-w-4xl mx-auto animate-slide-up">
                <div class="flex flex-col items-center mb-8">
                    <div class="bg-white p-5 rounded-full mb-4 shadow-2xl flex items-center justify-center w-24 h-24 transform hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-graduation-cap text-5xl text-primary"></i> 
                    </div>
                    <span class="text-tertiary font-bold tracking-[0.4em] uppercase text-sm">Mentor Program</span>
                </div>

                <h1 class="text-5xl md:text-7xl font-bold mb-8 leading-tight">
                    Unlock Your Potential with Expert Mentorship
                </h1>
                <p class="text-xl md:text-2xl mb-12 font-light text-gray-100">
                    Connect with experienced mentors who can guide you towards your personal and professional goals regarding Computer Languages.
                </p>
                
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="register.php" class="bg-secondary text-white px-10 py-4 rounded-full font-bold uppercase hover:bg-opacity-90 transition-all duration-300 transform hover:-translate-y-1 hover:shadow-2xl shadow-lg">
                        Get Started
                    </a>
                    <a href="#features" class="bg-white/10 backdrop-blur-md border border-white/20 text-white px-10 py-4 rounded-full font-bold uppercase hover:bg-white/20 transition-all duration-300">
                        Explore Features
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="py-20 bg-background">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-4 text-primary animate-slide-up">Why Choose Our Platform?</h2>
            <p class="text-gray-600 text-center mb-12 max-w-2xl mx-auto">We provide a comprehensive mentorship experience that helps you achieve your goals.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-xl shadow-lg p-8 text-center hover:-translate-y-2 transition-all duration-300 group">
                    <div class="bg-secondary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-secondary/20 transition-colors">
                        <i class="fas fa-user-graduate text-4xl text-secondary"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-4 text-primary">Expert Mentors</h3>
                    <p class="text-gray-600">Connect with industry leaders and verified professionals.</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center hover:-translate-y-2 transition-all duration-300 group">
                    <div class="bg-secondary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-secondary/20 transition-colors">
                        <i class="fas fa-calendar-check text-4xl text-secondary"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-4 text-primary">Flexible Scheduling</h3>
                    <p class="text-gray-600">Book sessions anytime with our 24/7 easy scheduling system.</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-8 text-center hover:-translate-y-2 transition-all duration-300 group">
                    <div class="bg-secondary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:bg-secondary/20 transition-colors">
                        <i class="fas fa-chart-line text-4xl text-secondary"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-4 text-primary">Track Progress</h3>
                    <p class="text-gray-600">Monitor your development with goal tracking and analytics.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-center mb-16 text-primary">How It Works</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="text-center group">
                    <div class="relative mb-6">
                        <div class="bg-secondary/10 w-24 h-24 rounded-full flex items-center justify-center mx-auto group-hover:bg-secondary/20 transition-all">
                            <i class="fas fa-user-plus text-4xl text-secondary"></i>
                        </div>
                        <div class="absolute top-0 right-1/4 w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold">1</div>
                    </div>
                    <h4 class="text-xl font-bold mb-2 text-primary">Join Us</h4>
                    <p class="text-gray-600">Create your profile</p>
                </div>
                <div class="text-center group">
                    <div class="relative mb-6">
                        <div class="bg-secondary/10 w-24 h-24 rounded-full flex items-center justify-center mx-auto group-hover:bg-secondary/20 transition-all">
                            <i class="fas fa-search text-4xl text-secondary"></i>
                        </div>
                        <div class="absolute top-0 right-1/4 w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold">2</div>
                    </div>
                    <h4 class="text-xl font-bold mb-2 text-primary">Find Mentor</h4>
                    <p class="text-gray-600">Match with experts</p>
                </div>
                <div class="text-center group">
                    <div class="relative mb-6">
                        <div class="bg-secondary/10 w-24 h-24 rounded-full flex items-center justify-center mx-auto group-hover:bg-secondary/20 transition-all">
                            <i class="fas fa-calendar-alt text-4xl text-secondary"></i>
                        </div>
                        <div class="absolute top-0 right-1/4 w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold">3</div>
                    </div>
                    <h4 class="text-xl font-bold mb-2 text-primary">Schedule</h4>
                    <p class="text-gray-600">Book your sessions</p>
                </div>
                <div class="text-center group">
                    <div class="relative mb-6">
                        <div class="bg-secondary/10 w-24 h-24 rounded-full flex items-center justify-center mx-auto group-hover:bg-secondary/20 transition-all">
                            <i class="fas fa-rocket text-4xl text-secondary"></i>
                        </div>
                        <div class="absolute top-0 right-1/4 w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold">4</div>
                    </div>
                    <h4 class="text-xl font-bold mb-2 text-primary">Elevate</h4>
                    <p class="text-gray-600">Start your journey</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-primary text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
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
                    <div class="flex space-x-4">
                         <a href="https://x.com/RMehandru15984" target="_blank" class="text-gray-300 hover:text-tertiary"><i class="fab fa-twitter text-xl"></i></a>
                        <a href="https://www.linkedin.com/in/rythem-mehandru-aa2989199/" target="_blank" class="text-gray-300 hover:text-tertiary"><i class="fab fa-linkedin text-xl"></i></a>
                    </div>
                </div>
            </div>
            <hr class="border-secondary my-8">
            <div class="text-center text-gray-300">
                <p>&copy; 2025-26 Mentor Platform. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function updateActiveTab() {
                const sections = [
                    { id: 'top', element: document.getElementById('top') },
                    { id: 'features', element: document.getElementById('features') },
                    { id: 'how-it-works', element: document.getElementById('how-it-works') }
                ];
                
                const navLinks = {
                    'top': document.querySelector('a[href="index.php"]'),
                    'features': document.querySelector('a[href="#features"]'),
                    'how-it-works': document.querySelector('a[href="#how-it-works"]')
                };

                const scrollPosition = window.scrollY + 100;

                sections.forEach(section => {
                    if (section.element) {
                        const top = section.element.offsetTop - 100;
                        const bottom = top + section.element.offsetHeight;
                        if (scrollPosition >= top && scrollPosition <= bottom) {
                            Object.values(navLinks).forEach(link => link?.classList.remove('text-primary', 'font-semibold', 'border-b-2', 'border-primary'));
                            navLinks[section.id]?.classList.add('text-primary', 'font-semibold', 'border-b-2', 'border-primary');
                        }
                    }
                });
            }

            window.addEventListener('scroll', updateActiveTab);
            updateActiveTab();

            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.getElementById(this.getAttribute('href').slice(1))?.scrollIntoView({ behavior: 'smooth' });
                });
            });
        });
    </script>
</body>
</html>