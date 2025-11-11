<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MediFinder — Find the right medicine fast</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
    <link href="/medi/assets/css/style.css" rel="stylesheet" />
    <link rel="icon" href="/medi/assets/img/medifinder-logo.svg" />
    <style>
        :root {
            --primary: #0d6efd;
            --primary-dark: #0a58ca;
            --secondary: #6c757d;
            --success: #10b981;
            --info: #06b6d4;
            --warning: #f59e0b;
        }
        
        body {
            overflow-x: hidden;
        }
        
        /* Navbar Animations */
        .navbar {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95) !important;
            transition: all 0.3s ease;
            padding-top: 1px;
            padding-bottom: 1px;
            min-height: auto;
        }
        .navbar .container {
            padding-top: 0;
            padding-bottom: 0;
        }
        .navbar-brand {
            padding-top: 0;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        .navbar-nav {
            margin-bottom: 0 !important;
        }
        .navbar-collapse {
            padding-bottom: 0;
        }
                
        
        .nav-link {
            position: relative;
            transition: color 0.3s ease;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .nav-link:hover::after {
            width: 80%;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            min-height: 90vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.3;
        }
        
        .hero-content {
            animation: fadeInUp 0.8s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-section h1 {
            color: white;
            font-weight: 700;
            line-height: 1.2;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .hero-section p {
            color: rgba(255, 255, 255, 0.95);
            font-size: 1.2rem;
        }
        
        .hero-illustration {
            animation: float 3s ease-in-out infinite;
            filter: drop-shadow(0 10px 30px rgba(0, 0, 0, 0.2));
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .btn-hero {
            padding: 14px 32px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        /* Floating Pills Background */
        .floating-pills {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            pointer-events: none;
        }
        
        .pill {
            position: absolute;
            border-radius: 50px;
            opacity: 0.1;
            animation: floatPill 20s infinite;
        }
        
        .pill:nth-child(1) {
            width: 80px;
            height: 30px;
            background: white;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .pill:nth-child(2) {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            top: 70%;
            left: 80%;
            animation-delay: 3s;
        }
        
        .pill:nth-child(3) {
            width: 100px;
            height: 40px;
            background: white;
            top: 50%;
            left: 5%;
            animation-delay: 7s;
        }
        
        @keyframes floatPill {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
            }
            25% {
                transform: translate(30px, -50px) rotate(90deg);
            }
            50% {
                transform: translate(-20px, -100px) rotate(180deg);
            }
            75% {
                transform: translate(40px, -70px) rotate(270deg);
            }
        }
        
        /* Features Section */
        .features-section {
            padding: 100px 0;
        }
        
        .section-title {
            position: relative;
            display: inline-block;
            padding-bottom: 15px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--success));
            border-radius: 2px;
        }
        
        .feature-card {
            border: none;
            border-radius: 20px;
            transition: all 0.4s ease;
            overflow: hidden;
            background: white;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
        }
        
        .feature-card .icon-wrap {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .icon-wrap {
            transform: rotateY(360deg);
        }
        
        .feature-card h5 {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
        }
        
        /* Stats Counter */
        .stats-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            padding: 80px 0;
            color: white;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            display: block;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* CTA Section */
        .cta-section {
            padding: 100px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .cta-card {
            background: white;
            border-radius: 30px;
            padding: 60px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .cta-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        /* Footer */
        footer {
            background: #1e293b;
            color: white;
            padding: 40px 0;
        }
        
        footer a {
            color: rgba(255, 255, 255, 0.7);
            transition: color 0.3s ease;
        }
        
        footer a:hover {
            color: white;
        }
        
        /* Scroll Animation */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Medical Icons Animation */
        .medical-icon {
            animation: pulse-icon 2s ease-in-out infinite;
        }
        
        @keyframes pulse-icon {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="/medi/" style="gap: 0;">
                <img src="/medi/assets/img/m.png" alt="MediFinder" width="120" height="120" style="margin-right: -12px; display: block; vertical-align: middle;">
                <strong style="font-size: 1.3rem; margin-left: -12px;">MediFinder</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item"><a class="nav-link fw-semibold" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link fw-semibold" href="#how-it-works">How It Works</a></li>
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item"><a class="nav-link fw-semibold" href="/medi/reminders.php">Reminders</a></li>
                        <li class="nav-item"><a class="btn btn-outline-danger ms-lg-3 rounded-pill" href="/medi/auth/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><button type="button" class="btn btn-primary ms-lg-3 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section">
        <div class="floating-pills">
            <div class="pill"></div>
            <div class="pill"></div>
            <div class="pill"></div>
        </div>
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <div class="badge bg-white text-primary mb-3 px-3 py-2 rounded-pill">
                        <i class="fas fa-shield-heart me-2"></i>Trusted Healthcare Solution
                    </div>
                    <h1 class="display-4 mb-4">Find the Right Medicine Fast & Safely</h1>
                    <p class="mb-4">MediFinder uses advanced AI to analyze prescriptions, recommend safe alternatives, and locate nearby pharmacies with likely stock availability.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <button type="button" class="btn btn-light btn-hero" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fa-solid fa-camera me-2"></i>Get Started Free
                        </button>
                        <a href="#features" class="btn btn-outline-light btn-hero">
                            <i class="fa-solid fa-circle-info me-2"></i>Learn More
                        </a>
                    </div>
                    <div class="mt-4 d-flex gap-4 text-white">
                        <div>
                            <i class="fas fa-check-circle me-2"></i>100% Secure
                        </div>
                        <div>
                            <i class="fas fa-check-circle me-2"></i>AI-Powered
                        </div>
                        <div>
                            <i class="fas fa-check-circle me-2"></i>Real-Time Updates
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center mt-5 mt-lg-0">
                    <img src="/medi/assets/img/h.png?v=<?php echo time(); ?>" class="img-fluid hero-illustration" alt="Medicine Illustration" style="max-width: 600px;">
                </div>
            </div>
        </div>
    </header>

    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6 stat-item fade-in">
                    <i class="fas fa-users fa-2x mb-3 medical-icon"></i>
                    <span class="stat-number counter" data-target="10000">0</span>
                    <span class="stat-label">Happy Users</span>
                </div>
                <div class="col-md-3 col-6 stat-item fade-in">
                    <i class="fas fa-hospital fa-2x mb-3 medical-icon"></i>
                    <span class="stat-number counter" data-target="500">0</span>
                    <span class="stat-label">Partner Pharmacies</span>
                </div>
                <div class="col-md-3 col-6 stat-item fade-in">
                    <i class="fas fa-prescription fa-2x mb-3 medical-icon"></i>
                    <span class="stat-number counter" data-target="50000">0</span>
                    <span class="stat-label">Prescriptions Analyzed</span>
                </div>
                <div class="col-md-3 col-6 stat-item fade-in">
                    <i class="fas fa-clock fa-2x mb-3 medical-icon"></i>
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Available Support</span>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="features-section">
        <div class="container">
            <div class="text-center mb-5 fade-in">
                <h2 class="fw-bold section-title mb-3">How MediFinder Helps You</h2>
                <p class="text-secondary fs-5">From understanding prescriptions to getting the right medicine quickly</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3 fade-in">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="icon-wrap bg-primary-subtle text-primary mx-auto">
                                <i class="fa-solid fa-file-medical"></i>
                            </div>
                            <h5>Prescription OCR</h5>
                            <p class="text-secondary">Upload prescription images and extract text instantly using secure on-device OCR technology for complete privacy.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 fade-in">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="icon-wrap bg-success-subtle text-success mx-auto">
                                <i class="fa-solid fa-brain"></i>
                            </div>
                            <h5>AI Recommendations</h5>
                            <p class="text-secondary">Get intelligent medicine suggestions and safe alternatives based on your prescription and health profile.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 fade-in">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="icon-wrap bg-warning-subtle text-warning mx-auto">
                                <i class="fa-solid fa-keyboard"></i>
                            </div>
                            <h5>Manual Input</h5>
                            <p class="text-secondary">Type symptoms or medicine names to receive instant AI-powered suggestions and health recommendations.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 fade-in">
                    <div class="card feature-card shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="icon-wrap bg-info-subtle text-info mx-auto">
                                <i class="fa-solid fa-map-location-dot"></i>
                            </div>
                            <h5>Pharmacy Locator</h5>
                            <p class="text-secondary">Find nearby pharmacies with real-time stock availability and get directions instantly.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5 fade-in">
                <h2 class="fw-bold section-title mb-3">Simple 3-Step Process</h2>
                <p class="text-secondary fs-5">Get your medicine recommendations in minutes</p>
            </div>
            <div class="row g-5 align-items-center">
                <div class="col-lg-4 fade-in">
                    <div class="text-center">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">1</div>
                        <h4 class="fw-bold mb-3">Upload Prescription</h4>
                        <p class="text-secondary">Take a photo or upload your prescription. Our OCR technology extracts the information securely.</p>
                    </div>
                </div>
                <div class="col-lg-4 fade-in">
                    <div class="text-center">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">2</div>
                        <h4 class="fw-bold mb-3">Get AI Analysis</h4>
                        <p class="text-secondary">Receive intelligent recommendations and safe alternatives tailored to your needs.</p>
                    </div>
                </div>
                <div class="col-lg-4 fade-in">
                    <div class="text-center">
                        <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 2rem; font-weight: bold;">3</div>
                        <h4 class="fw-bold mb-3">Find Pharmacy</h4>
                        <p class="text-secondary">Locate nearby pharmacies with your medicine in stock and get directions.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <div class="cta-card fade-in">
                <div class="row align-items-center g-4">
                    <div class="col-lg-6">
                        <img src="/medi/assets/img/h.png" class="img-fluid rounded" alt="Prescription Upload" style="max-width: 400px;">
                    </div>
                    <div class="col-lg-6 position-relative">
                        <h3 class="fw-bold display-6 mb-4">Start Your Free Account Today</h3>
                        <p class="text-secondary fs-5 mb-4">Save your preferences, set medication reminders, and sync seamlessly across all your devices.</p>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Free forever account</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Secure cloud storage</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Smart reminders</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Multi-device sync</li>
                        </ul>
                        <?php if (!$isLoggedIn): ?>
                            <button type="button" class="btn btn-success btn-lg rounded-pill px-5" data-bs-toggle="modal" data-bs-target="#registerModal">
                                <i class="fa-solid fa-user-plus me-2"></i>Create Free Account
                            </button>
                        <?php else: ?>
                            <a href="/medi/prescription.php" class="btn btn-success btn-lg rounded-pill px-5">
                                <i class="fa-solid fa-arrow-right me-2"></i>Continue to Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="/medi/assets/img/medifinder-logo.svg" alt="MediFinder" width="32" height="32" class="me-2" style="filter: brightness(0) invert(1);">
                        <strong class="fs-5">MediFinder</strong>
                    </div>
                    <p class="text-white-50">Your trusted partner in finding the right medicine, fast and safely.</p>
                </div>
                <div class="col-md-2">
                    <h6 class="fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#features">Features</a></li>
                        <li class="mb-2"><a href="#how-it-works">How It Works</a></li>
                        <li class="mb-2"><a href="/medi/locator.php">Pharmacies</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <h6 class="fw-bold mb-3">Account</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a></li>
                        <li class="mb-2"><a href="#" data-bs-toggle="modal" data-bs-target="#registerModal">Register</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold mb-3">Stay Connected</h6>
                    <p class="text-white-50 mb-3">Get the latest updates and health tips.</p>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width: 40px; height: 40px;"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width: 40px; height: 40px;"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm rounded-circle" style="width: 40px; height: 40px;"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4 bg-white opacity-25">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <span class="text-white-50">© <?php echo date('Y'); ?> MediFinder. All rights reserved.</span>
                <div class="d-flex gap-3 small">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Contact Us</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Scroll Animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
        
        // Counter Animation
        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000;
            const increment = target / (duration / 16);
            let current = 0;
            
            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    counter.textContent = Math.floor(current).toLocaleString();
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target.toLocaleString() + '+';
                }
            };
            
            const counterObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCounter();
                        counterObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });
            
            counterObserver.observe(counter);
        });
        
        // Smooth Scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
        
        // Navbar Background on Scroll
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
            } else {
                navbar.style.boxShadow = '0 1px 3px rgba(0,0,0,0.05)';
            }
        });
    </script>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 30px; border: none; overflow: hidden;">
                <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); color: white; border: none; padding: 30px 40px;">
                    <div class="text-center w-100">
                        <div class="mb-3">
                            <img src="/medi/assets/img/medifinder-logo.svg" alt="MediFinder" width="60" height="60" style="background: white; padding: 10px; border-radius: 15px;">
                        </div>
                        <h3 class="modal-title fw-bold mb-2" id="loginModalLabel">Welcome back</h3>
                        <p class="mb-0" style="opacity: 0.95;">Sign in to continue to MediFinder</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 20px; right: 20px;"></button>
                </div>
                <div class="modal-body" style="padding: 40px;">
                    <div id="loginFormErrors" class="alert alert-danger d-none" role="alert"></div>
                    <form id="loginForm" method="post" action="/medi/auth/login.php">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Email Address</label>
                            <div class="position-relative">
                                <i class="fas fa-envelope" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; z-index: 10;"></i>
                                <input type="email" class="form-control form-control-lg" name="email" placeholder="your@email.com" required style="border-radius: 12px; border: 2px solid #e2e8f0; padding: 12px 16px 12px 45px;">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password</label>
                            <div class="position-relative">
                                <i class="fas fa-lock" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; z-index: 10;"></i>
                                <input type="password" class="form-control form-control-lg" name="password" id="loginPassword" placeholder="Enter your password" required style="border-radius: 12px; border: 2px solid #e2e8f0; padding: 12px 45px 12px 45px;">
                                <button type="button" class="btn btn-link p-0" onclick="toggleLoginPassword()" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; z-index: 10; text-decoration: none;">
                                    <i class="fas fa-eye" id="loginToggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-4 text-end">
                            <a href="/medi/auth/forgot_password.php" class="text-decoration-none" style="color: #0d6efd; font-size: 0.9rem;">Forgot password?</a>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 14px; padding: 15px; font-weight: 600;">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>
                    <div class="divider text-center my-4" style="position: relative;">
                        <span style="background: white; padding: 0 15px; position: relative; z-index: 1; color: #94a3b8;">OR</span>
                        <hr style="position: absolute; top: 50%; left: 0; right: 0; margin: 0; border-color: #e2e8f0;">
                    </div>
                    <div class="d-grid">
                        <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal" style="border-radius: 14px; padding: 14px; font-weight: 600;">
                            <i class="fas fa-user-plus me-2"></i>Create New Account
                        </button>
                    </div>
                    <div class="mt-4 pt-4 border-top">
                        <div class="row text-center">
                            <div class="col-4">
                                <i class="fas fa-shield-halved text-success mb-2"></i>
                                <div style="font-size: 0.85rem; color: #64748b;">Secure Login</div>
                            </div>
                            <div class="col-4">
                                <i class="fas fa-lock text-success mb-2"></i>
                                <div style="font-size: 0.85rem; color: #64748b;">Encrypted Data</div>
                            </div>
                            <div class="col-4">
                                <i class="fas fa-user-shield text-success mb-2"></i>
                                <div style="font-size: 0.85rem; color: #64748b;">Privacy Protected</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 30px; border: none; overflow: hidden;">
                <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); color: white; border: none; padding: 30px 40px;">
                    <div class="text-center w-100">
                        <div class="mb-3">
                            <img src="/medi/assets/img/medifinder-logo.svg" alt="MediFinder" width="60" height="60" style="background: white; padding: 10px; border-radius: 15px;">
                        </div>
                        <h3 class="modal-title fw-bold mb-2" id="registerModalLabel">Create Your MediFinder Account</h3>
                        <p class="mb-0" style="opacity: 0.95;">Manage reminders, save favorite pharmacies, and get personalized medicine alerts.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 20px; right: 20px;"></button>
                </div>
                <div class="modal-body" style="padding: 40px;">
                    <div id="registerFormErrors" class="alert alert-danger d-none" role="alert"></div>
                    <form id="registerForm" method="post" action="/medi/auth/register.php">
                        <div class="mb-4">
                            <label class="form-label fw-semibold"><i class="fas fa-user me-2 text-primary"></i>Full Name <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <i class="fas fa-user" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; z-index: 10;"></i>
                                <input type="text" class="form-control form-control-lg" name="name" placeholder="e.g., Jane Dela Cruz" required style="border-radius: 12px; border: 2px solid #e2e8f0; padding: 12px 16px 12px 45px;">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold"><i class="fas fa-envelope me-2 text-primary"></i>Email Address <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <i class="fas fa-envelope" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; z-index: 10;"></i>
                                <input type="email" class="form-control form-control-lg" name="email" placeholder="you@example.com" required style="border-radius: 12px; border: 2px solid #e2e8f0; padding: 12px 16px 12px 45px;">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold"><i class="fas fa-briefcase me-2 text-primary"></i>Account Type <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <i class="fas fa-briefcase" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; z-index: 10;"></i>
                                <select class="form-select form-select-lg" name="role" id="roleSelect" required style="border-radius: 12px; border: 2px solid #e2e8f0; padding: 12px 16px 12px 45px; appearance: none; background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2394a3b8\'><path d=\'M7 10l5 5 5-5z\'/></svg>'); background-repeat: no-repeat; background-position: right 16px center; background-size: 16px;">
                                    <option value="patient">Client — find medicines & set reminders</option>
                                    <option value="pharmacy_owner">Pharmacy Owner — list your pharmacy</option>
                                </select>
                            </div>
                            <small class="text-muted d-block mt-2"><i class="fas fa-info-circle me-1"></i>Need an admin account? Contact support.</small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold"><i class="fas fa-lock me-2 text-primary"></i>Password <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <i class="fas fa-lock" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; z-index: 10;"></i>
                                <input type="password" class="form-control form-control-lg" name="password" placeholder="Minimum 6 characters" required style="border-radius: 12px; border: 2px solid #e2e8f0; padding: 12px 45px 12px 45px;">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold"><i class="fas fa-lock me-2 text-primary"></i>Confirm Password <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <i class="fas fa-lock" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; z-index: 10;"></i>
                                <input type="password" class="form-control form-control-lg" name="confirm" placeholder="Re-type password" required style="border-radius: 12px; border: 2px solid #e2e8f0; padding: 12px 45px 12px 45px;">
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 14px; padding: 15px; font-weight: 600;">
                                <i class="fas fa-paper-plane me-2"></i>Create Account
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-lg" onclick="switchToLoginModal()" style="border-radius: 14px; padding: 14px; font-weight: 600;">
                                <i class="fas fa-sign-in-alt me-2"></i>Already registered? Sign in
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle registration form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);
            const errorsDiv = document.getElementById('registerFormErrors');
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            errorsDiv.classList.add('d-none');
            errorsDiv.innerHTML = '';
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';

            fetch('/medi/auth/register.php', {
                method: 'POST',
                body: formData,
                redirect: 'follow'
            })
            .then(response => {
                if (response.redirected) {
                    // Success - redirect to the new page
                    window.location.href = response.url;
                    return;
                }
                return response.text();
            })
            .then(html => {
                if (html) {
                    // Parse the response to check for errors
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const errorAlert = doc.querySelector('.alert-danger');
                    
                    if (errorAlert) {
                        // Show errors in modal
                        errorsDiv.innerHTML = errorAlert.innerHTML;
                        errorsDiv.classList.remove('d-none');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                        // Scroll to errors
                        errorsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    } else {
                        // No errors found, might be success - reload page
                        window.location.reload();
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorsDiv.innerHTML = '<strong>Error:</strong> Something went wrong. Please try again.';
                errorsDiv.classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });

        // Handle role selection change
        document.getElementById('roleSelect').addEventListener('change', function() {
            if (this.value === 'pharmacy_owner') {
                // Close modal and redirect to pharmacy registration
                const modal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
                if (modal) {
                    modal.hide();
                }
                window.location.href = '/medi/auth/register_pharmacy.php';
            }
        });

        // Switch from registration modal to login modal
        function switchToLoginModal() {
            const registerModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
            if (registerModal) {
                registerModal.hide();
            }
            setTimeout(() => {
                const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                loginModal.show();
            }, 300);
        }

        // Toggle password visibility for login
        function toggleLoginPassword() {
            const passwordInput = document.getElementById('loginPassword');
            const toggleIcon = document.getElementById('loginToggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Handle login form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);
            const errorsDiv = document.getElementById('loginFormErrors');
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            errorsDiv.classList.add('d-none');
            errorsDiv.innerHTML = '';
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';

            fetch('/medi/auth/login.php', {
                method: 'POST',
                body: formData,
                redirect: 'follow'
            })
            .then(response => {
                if (response.redirected) {
                    // Success - redirect to the new page
                    window.location.href = response.url;
                    return;
                }
                return response.text();
            })
            .then(html => {
                if (html) {
                    // Parse the response to check for errors
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const errorAlert = doc.querySelector('.alert-danger');
                    
                    if (errorAlert) {
                        // Show errors in modal
                        const errorText = errorAlert.textContent || errorAlert.innerText;
                        errorsDiv.innerHTML = '<div class="d-flex align-items-start"><i class="fas fa-exclamation-circle me-2 mt-1"></i><div>' + errorText + '</div></div>';
                        errorsDiv.classList.remove('d-none');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                        // Scroll to errors
                        errorsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    } else {
                        // No errors found, might be success - reload page
                        window.location.reload();
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorsDiv.innerHTML = '<div class="d-flex align-items-start"><i class="fas fa-exclamation-circle me-2 mt-1"></i><div>Something went wrong. Please try again.</div></div>';
                errorsDiv.classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });

        // Prevent page reload when clicking MediFinder logo/name
        document.addEventListener('DOMContentLoaded', function() {
            const navbarBrand = document.querySelector('.navbar-brand[href="/medi/"]');
            if (navbarBrand) {
                navbarBrand.addEventListener('click', function(e) {
                    const currentPath = window.location.pathname;
                    const linkHref = this.getAttribute('href');
                    
                    // If we're already on the target page, prevent reload
                    if (currentPath === linkHref || currentPath === linkHref + 'index.php') {
                        e.preventDefault();
                        // Smooth scroll to top instead
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }
                });
            }
        });

        const logoutNotice = <?php echo isset($_GET['logout']) ? 'true' : 'false'; ?>;
        if (logoutNotice) {
            Swal.fire({
                icon: 'success',
                title: 'Signed out',
                text: 'You have been logged out successfully.',
                confirmButtonColor: '#667eea'
            }).then(() => {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.delete('logout');
                window.history.replaceState({}, document.title, currentUrl.pathname + (currentUrl.search ? '?' + currentUrl.searchParams.toString() : '') + currentUrl.hash);
            });
        }
    </script>
</body>
</html>