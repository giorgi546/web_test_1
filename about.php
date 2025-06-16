<?php
require_once 'includes/config.php';

// Get some statistics for the about page
$stats = [];

// Total products
$stmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
$stmt->execute();
$stats['products'] = $stmt->fetch()['total'];

// Total customers
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
$stmt->execute();
$stats['customers'] = $stmt->fetch()['total'];

// Total orders
$stmt = $db->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$stats['orders'] = $stmt->fetch()['total'];

// Years in business (since the site was created)
$stats['years'] = date('Y') - 2020; // Assuming business started in 2020
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Learn about <?php echo SITE_NAME; ?> - your trusted partner for quality products and exceptional shopping experience.">
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* About page specific styles */
        .about-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 8rem 0 4rem;
            margin-top: 80px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .about-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(ellipse at center, rgba(255,255,255,0.1) 0%, transparent 70%);
        }
        
        .about-hero-content {
            position: relative;
            z-index: 2;
            animation: slideInUp 0.8s ease-out;
        }
        
        .about-hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .about-hero p {
            font-size: 1.3rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape-1 {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }
        
        .shape-3 {
            width: 60px;
            height: 60px;
            bottom: 30%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .about-section {
            padding: 5rem 0;
        }
        
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            margin-bottom: 4rem;
        }
        
        .about-content.reverse {
            direction: rtl;
        }
        
        .about-content.reverse > * {
            direction: ltr;
        }
        
        .about-text h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .about-text p {
            font-size: 1.1rem;
            color: #4B5563;
            line-height: 1.8;
            margin-bottom: 1.5rem;
        }
        
        .about-text .highlight {
            color: #3B82F6;
            font-weight: 600;
        }
        
        .about-image {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .about-image:hover {
            transform: translateY(-10px);
        }
        
        .about-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .about-image:hover img {
            transform: scale(1.05);
        }
        
        .stats-section {
            background: linear-gradient(135deg, #1F2937 0%, #111827 100%);
            color: white;
            padding: 5rem 0;
            position: relative;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
        }
        
        .stat-item {
            text-align: center;
            animation: countUp 2s ease-out;
        }
        
        .stat-number {
            font-size: 3.5rem;
            font-weight: 800;
            color: #10B981;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .stat-label {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }
        
        .values-section {
            padding: 5rem 0;
            background: #F8FAFC;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 1rem;
        }
        
        .section-subtitle {
            font-size: 1.1rem;
            color: #6B7280;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .value-card {
            background: white;
            padding: 3rem 2rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #E5E7EB;
        }
        
        .value-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            border-color: #3B82F6;
        }
        
        .value-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3B82F6 0%, #1D4ED8 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
            transition: transform 0.3s ease;
        }
        
        .value-card:hover .value-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .value-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 1rem;
        }
        
        .value-card p {
            color: #6B7280;
            line-height: 1.6;
        }
        
        .team-section {
            padding: 5rem 0;
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .team-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .team-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .team-image {
            height: 300px;
            background: linear-gradient(135deg, #E5E7EB 0%, #F3F4F6 100%);
            position: relative;
            overflow: hidden;
        }
        
        .team-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .team-card:hover .team-image img {
            transform: scale(1.05);
        }
        
        .team-info {
            padding: 2rem;
        }
        
        .team-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .team-role {
            color: #3B82F6;
            font-weight: 500;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }
        
        .team-bio {
            color: #6B7280;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .social-link {
            width: 40px;
            height: 40px;
            background: #F3F4F6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6B7280;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .social-link:hover {
            background: #3B82F6;
            color: white;
            transform: translateY(-2px);
        }
        
        .cta-section {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            padding: 5rem 0;
            text-align: center;
        }
        
        .cta-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .cta-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-cta {
            background: white;
            color: #059669;
            padding: 1rem 2rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-cta:hover {
            background: #F8FAFC;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline:hover {
            background: white;
            color: #059669;
        }
        
        /* Timeline Styles */
        .timeline {
            position: relative;
            margin: 3rem 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #E5E7EB;
            transform: translateX(-50%);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 3rem;
        }
        
        .timeline-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            width: 45%;
            position: relative;
        }
        
        .timeline-item:nth-child(odd) .timeline-content {
            margin-left: 55%;
        }
        
        .timeline-item:nth-child(even) .timeline-content {
            margin-right: 55%;
        }
        
        .timeline-year {
            background: #3B82F6;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            position: absolute;
            left: 50%;
            top: 1rem;
            transform: translateX(-50%);
            z-index: 2;
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .about-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .about-hero h1 {
                font-size: 2.5rem;
            }
            
            .timeline::before {
                left: 30px;
            }
            
            .timeline-content {
                width: calc(100% - 80px);
                margin-left: 80px !important;
                margin-right: 0 !important;
            }
            
            .timeline-year {
                left: 30px;
                transform: translateX(-50%);
            }
        }
        
        @media (max-width: 768px) {
            .about-hero {
                padding: 6rem 0 3rem;
            }
            
            .about-hero h1 {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 2rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .values-grid,
            .team-grid {
                grid-template-columns: 1fr;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        <div class="container">
            <div class="about-hero-content">
                <h1>About <?php echo SITE_NAME; ?></h1>
                <p>We're passionate about bringing you the best products with exceptional service and unbeatable value.</p>
            </div>
        </div>
    </section>

    <!-- Story Section -->
    <section class="about-section">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>Our Story</h2>
                    <p>Founded with a simple mission: to make online shopping <span class="highlight">easy, affordable, and enjoyable</span> for everyone. What started as a small idea has grown into a trusted platform serving thousands of customers worldwide.</p>
                    <p>We believe that great products shouldn't be complicated to find or expensive to buy. That's why we've carefully curated our selection to include only the <span class="highlight">highest quality items</span> at prices that won't break the bank.</p>
                    <p>Every day, we work hard to improve your shopping experience, from our user-friendly website to our lightning-fast shipping and responsive customer service.</p>
                </div>
                <div class="about-image">
                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Our team working">
                </div>
            </div>

            <div class="about-content reverse">
                <div class="about-text">
                    <h2>Our Mission</h2>
                    <p>To revolutionize online shopping by providing a <span class="highlight">seamless, secure, and satisfying</span> experience that exceeds customer expectations every single time.</p>
                    <p>We're committed to building lasting relationships with our customers through transparency, reliability, and genuine care for their needs.</p>
                    <p>Our goal isn't just to sell products – it's to become your <span class="highlight">trusted shopping partner</span> for life.</p>
                </div>
                <div class="about-image">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Customer service">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['products']); ?>+</span>
                    <span class="stat-label">Products</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['customers']); ?>+</span>
                    <span class="stat-label">Happy Customers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['orders']); ?>+</span>
                    <span class="stat-label">Orders Delivered</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $stats['years']; ?>+</span>
                    <span class="stat-label">Years Experience</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Our Values</h2>
                <p class="section-subtitle">The principles that guide everything we do and help us deliver exceptional experiences to our customers.</p>
            </div>
            
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Customer First</h3>
                    <p>Every decision we make starts with our customers. Your satisfaction, security, and success are our top priorities in everything we do.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Trust & Security</h3>
                    <p>We protect your personal information and ensure secure transactions with industry-leading encryption and privacy measures.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Quality Excellence</h3>
                    <p>We carefully curate every product in our catalog to ensure you receive only the highest quality items that exceed your expectations.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Innovation</h3>
                    <p>We continuously improve our platform, services, and processes to provide you with the most modern and efficient shopping experience.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Integrity</h3>
                    <p>Honest pricing, transparent policies, and reliable service. We believe in doing business the right way, every single time.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>Community</h3>
                    <p>We're committed to supporting our local communities and making a positive impact through responsible business practices.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Meet Our Team</h2>
                <p class="section-subtitle">The passionate people behind <?php echo SITE_NAME; ?> who work tirelessly to bring you the best shopping experience.</p>
            </div>
            
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-image">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80" alt="John Smith">
                    </div>
                    <div class="team-info">
                        <h3 class="team-name">John Smith</h3>
                        <div class="team-role">Founder & CEO</div>
                        <p class="team-bio">Passionate about e-commerce and customer experience. John founded <?php echo SITE_NAME; ?> with the vision of making online shopping simple and enjoyable for everyone.</p>
                        <div class="social-links">
                            <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-card">
                    <div class="team-image">
                        <img src="https://images.unsplash.com/photo-1494790108755-2616b612b606?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80" alt="Sarah Johnson">
                    </div>
                    <div class="team-info">
                        <h3 class="team-name">Sarah Johnson</h3>
                        <div class="team-role">Head of Operations</div>
                        <p class="team-bio">Ensures everything runs smoothly behind the scenes. Sarah manages our supply chain and logistics to get your orders delivered fast and safely.</p>
                        <div class="social-links">
                            <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-card">
                    <div class="team-image">
                        <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80" alt="Mike Chen">
                    </div>
                    <div class="team-info">
                        <h3 class="team-name">Mike Chen</h3>
                        <div class="team-role">Lead Developer</div>
                        <p class="team-bio">The tech wizard who keeps our website running perfectly. Mike constantly improves our platform to make your shopping experience better and more secure.</p>
                        <div class="social-links">
                            <a href="#" class="social-link"><i class="fab fa-github"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Shopping?</h2>
                <p>Join thousands of satisfied customers who trust <?php echo SITE_NAME; ?> for their shopping needs.</p>
                <div class="cta-buttons">
                    <a href="shop.php" class="btn-cta">
                        <i class="fas fa-shopping-bag"></i>
                        Browse Products
                    </a>
                    <a href="contact.php" class="btn-cta btn-outline">
                        <i class="fas fa-envelope"></i>
                        Contact Us
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Animate stats numbers when they come into view
            function animateStats() {
                $('.stat-number').each(function() {
                    const $this = $(this);
                    const target = parseInt($this.text().replace(/[^0-9]/g, ''));
                    const duration = 2000;
                    const step = target / (duration / 50);
                    let current = 0;
                    
                    const timer = setInterval(function() {
                        current += step;
                        if (current >= target) {
                            current = target;
                            clearInterval(timer);
                        }
                        $this.text(Math.floor(current).toLocaleString() + '+');
                    }, 50);
                });
            }
            
            // Trigger animation when stats section is in view
            const statsSection = $('.stats-section');
            let statsAnimated = false;
            
            $(window).scroll(function() {
                if (!statsAnimated && isInViewport(statsSection[0])) {
                    animateStats();
                    statsAnimated = true;
                }
            });
            
            // Check if element is in viewport
            function isInViewport(element) {
                const rect = element.getBoundingClientRect();
                return (
                    rect.top >= 0 &&
                    rect.left >= 0 &&
                    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
            }
            
            // Add scroll animations to value cards
            $(window).scroll(function() {
                $('.value-card').each(function() {
                    const elementTop = $(this).offset().top;
                    const elementBottom = elementTop + $(this).outerHeight();
                    const viewportTop = $(window).scrollTop();
                    const viewportBottom = viewportTop + $(window).height();
                    
                    if (elementBottom > viewportTop && elementTop < viewportBottom) {
                        $(this).addClass('animate-in');
                    }
                });
            });
            
            // Smooth scroll for anchor links
            $('a[href^="#"]').click(function(e) {
                e.preventDefault();
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 800);
                }
            });
            
            // Add parallax effect to hero section
            $(window).scroll(function() {
                const scrolled = $(this).scrollTop();
                const parallax = $('.about-hero');
                const speed = scrolled * 0.5;
                
                parallax.css('transform', 'translateY(' + speed + 'px)');
            });
            
            // Add hover effects to team cards
            $('.team-card').hover(
                function() {
                    $(this).find('.social-links').fadeIn(300);
                },
                function() {
                    $(this).find('.social-links').fadeOut(300);
                }
            );
            
            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut(300);
            }, 5000);
            
            // Add CSS for animated elements
            $('<style>')
                .prop('type', 'text/css')
                .html(`
                    .animate-in {
                        animation: slideInUp 0.6s ease-out;
                    }
                    
                    .social-links {
                        display: none;
                    }
                    
                    .team-card:hover .social-links {
                        display: flex !important;
                    }
                `)
                .appendTo('head');
        });
    </script>
</body>
</html>