<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Mentor Platform</title>
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
                    <a class="text-primary font-semibold border-b-2 border-primary" href="about.php">About Us</a>
                    <a class="text-gray-600 hover:text-primary transition-colors" href="contact.php">Contact</a>
                    <a class="text-gray-600 hover:text-primary transition-colors" href="login.php">Login</a>
                    <a class="bg-primary text-white px-4 py-2 rounded-full hover:bg-opacity-90 transition-colors" href="register.php">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>
    <section class="relative min-h-[500px] flex items-center justify-center bg-primary text-white pt-24 pb-16 overflow-hidden">
        <div class="absolute inset-0 z-0">
            <div class="absolute inset-0 opacity-0 animate-bg-slide bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=1600');"></div>
            <div class="absolute inset-0 opacity-0 animate-bg-slide [animation-delay:5s] bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1515187029135-18ee286d815b?q=80&w=1600');"></div>
            <div class="absolute inset-0 opacity-0 animate-bg-slide [animation-delay:10s] bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?q=80&w=1600');"></div>
            <div class="absolute inset-0 opacity-0 animate-bg-slide [animation-delay:15s] bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=1600');"></div>
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
            <h1 class="text-4xl md:text-6xl font-bold mb-6">Unlock Your Potential</h1>
            <p class="text-xl mb-8">Empowering growth through meaningful mentorship connections.</p>
        </div>
    </section>

    <section class="py-16 bg-background">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg p-8 animate-fade-in mb-8">
                    <h2 class="text-3xl font-bold mb-6 text-primary">Our Story</h2>
                    <div class="prose max-w-none text-gray-600">
                        <p class="mb-6">Founded in 2025-26, Mentor Platform was built on a clear vision to make high-quality mentorship accessible to everyone in the field of computer science and information technology. Our founders, having personally experienced the transformative impact of mentorship in their tech careers, identified a significant gap in the industry: while skilled developers and IT professionals were eager to share their knowledge, many aspiring learners struggled to find the right mentors to guide them.</p>
                        <p class="mb-6">What began as a simple idea has evolved into a specialized platform that connects mentors and mentees across programming languages, software development, data science, cybersecurity, and other IT domains. We have carefully designed our platform to address common challenges in mentorship, such as scheduling conflicts, communication barriers, and effective progress tracking in a rapidly evolving tech environment.</p>
                        <p class="mb-6">Today, our platform serves thousands of users worldwide, helping individuals learn coding, build real-world projects, and advance their careers in the IT industry. We are proud to have empowered countless learners to achieve their professional goals and strengthen their technical skills through our structured, practical, and industry-focused mentorship programs.</p>
                </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-8 animate-fade-in">
                    <h2 class="text-3xl font-bold mb-6 text-primary">Our Values</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-background rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <div class="bg-secondary/10 w-12 h-12 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-handshake text-2xl text-secondary"></i>
                                </div>
                                <h3 class="text-xl font-semibold text-primary">Trust & Integrity</h3>
                            </div>
                            <p class="text-gray-600">We believe in building strong, trustworthy relationships between mentors and mentees, ensuring a safe and reliable environment for professional growth.</p>
                        </div>
                        <div class="bg-background rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <div class="bg-secondary/10 w-12 h-12 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-users text-2xl text-secondary"></i>
                                </div>
                                <h3 class="text-xl font-semibold text-primary">Inclusivity</h3>
                            </div>
                            <p class="text-gray-600">We're committed to creating an inclusive platform that welcomes diverse perspectives, backgrounds, and experiences.</p>
                        </div>
                        <div class="bg-background rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <div class="bg-secondary/10 w-12 h-12 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-star text-2xl text-secondary"></i>
                                </div>
                                <h3 class="text-xl font-semibold text-primary">Excellence</h3>
                            </div>
                            <p class="text-gray-600">We strive for excellence in every aspect of our platform, from user experience to the quality of mentorship connections we facilitate.</p>
                        </div>
                        <div class="bg-background rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <div class="bg-secondary/10 w-12 h-12 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-seedling text-2xl text-secondary"></i>
                                </div>
                                <h3 class="text-xl font-semibold text-primary">Growth Mindset</h3>
                            </div>
                            <p class="text-gray-600">We believe in continuous learning and improvement, both for our platform and the professionals we serve.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-3xl font-bold text-center mb-12 text-primary animate-slide-up">Our Mission & Vision</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-background rounded-xl p-8 animate-fade-in">
                        <div class="bg-secondary/10 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                            <i class="fas fa-lightbulb text-3xl text-secondary"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-4 text-primary">Vision</h3>
                        <p class="text-gray-600 mb-4">To create a world where quality mentorship is accessible to everyone, fostering personal and professional growth across IT industry.</p>
                        <ul class="text-gray-600 space-y-2">
                            <li class="flex items-start"><i class="fas fa-check text-secondary mr-2 mt-1"></i><span>Breaking down barriers to professional development</span></li>
                            <li class="flex items-start"><i class="fas fa-check text-secondary mr-2 mt-1"></i><span>Creating global mentorship opportunities</span></li>
                            <li class="flex items-start"><i class="fas fa-check text-secondary mr-2 mt-1"></i><span>Empowering the next generation of leaders</span></li>
                        </ul>
                    </div>
                    <div class="bg-background rounded-xl p-8 animate-fade-in" style="animation-delay: 0.2s">
                        <div class="bg-secondary/10 w-16 h-16 rounded-full flex items-center justify-center mb-6">
                            <i class="fas fa-bullseye text-3xl text-secondary"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-4 text-primary">Goals</h3>
                        <p class="text-gray-600 mb-4">To facilitate meaningful connections, provide valuable resources, and create an environment where both mentors and mentees can thrive.</p>
                        <ul class="text-gray-600 space-y-2">
                            <li class="flex items-start"><i class="fas fa-check text-secondary mr-2 mt-1"></i><span>Building a community of passionate professionals</span></li>
                            <li class="flex items-start"><i class="fas fa-check text-secondary mr-2 mt-1"></i><span>Facilitating knowledge transfer across generations</span></li>
                            <li class="flex items-start"><i class="fas fa-check text-secondary mr-2 mt-1"></i><span>Measuring and celebrating mentorship success</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-background">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-4 text-primary animate-slide-up">Our Leadership Team</h2>
            <p class="text-gray-600 text-center mb-12 max-w-2xl mx-auto">Meet the passionate individuals driving our mission to make quality mentorship accessible to everyone.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg p-6 text-center animate-fade-in group hover:-translate-y-2 transition-transform duration-300">
                    <div class="w-24 h-24 rounded-full bg-secondary/10 flex items-center justify-center mx-auto mb-4 group-hover:bg-secondary/20 transition-colors">
                        <i class="fas fa-user text-4xl text-secondary"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-primary mb-2">Rythem Mehandru</h3>
                    <p class="text-gray-600 mb-2">Founder & CEO</p>
                    <p class="text-sm text-gray-500 mb-4">15+ years experience in EdTech</p>
                    <div class="flex justify-center space-x-3">
                        <a href="#" class="text-gray-400 hover:text-primary transition-colors"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-gray-400 hover:text-primary transition-colors"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center animate-fade-in group hover:-translate-y-2 transition-transform duration-300" style="animation-delay: 0.2s">
                    <div class="w-24 h-24 rounded-full bg-secondary/10 flex items-center justify-center mx-auto mb-4 group-hover:bg-secondary/20 transition-colors">
                        <i class="fas fa-user text-4xl text-secondary"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-primary mb-2">Prince Kumar Singh</h3>
                    <p class="text-gray-600 mb-2">Head of Technology</p>
                    <p class="text-sm text-gray-500 mb-4">12+ years in Software Development</p>
                    <div class="flex justify-center space-x-3">
                        <a href="#" class="text-gray-400 hover:text-primary transition-colors"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-gray-400 hover:text-primary transition-colors"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center animate-fade-in group hover:-translate-y-2 transition-transform duration-300" style="animation-delay: 0.4s">
                    <div class="w-24 h-24 rounded-full bg-secondary/10 flex items-center justify-center mx-auto mb-4 group-hover:bg-secondary/20 transition-colors">
                        <i class="fas fa-user text-4xl text-secondary"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-primary mb-2">Anaya</h3>
                    <p class="text-gray-600 mb-2">Community Manager</p>
                    <p class="text-sm text-gray-500 mb-4">8+ years in Community Building</p>
                    <div class="flex justify-center space-x-3">
                        <a href="#" class="text-gray-400 hover:text-primary transition-colors"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-gray-400 hover:text-primary transition-colors"><i class="fab fa-github"></i></a>
                    </div>
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
                        <li><a href="about.php" class="text-gray-300 hover:text-tertiary">About Us</a></li>
                        <li><a href="contact.php" class="text-gray-300 hover:text-tertiary">Contact</a></li>
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
</body>
</html>