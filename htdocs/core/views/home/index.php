<?php
/**
 * Luna Dine Homepage View
 */

// Prevent direct access
if (!defined('LUNA_DINE_ROOT')) {
    exit;
}

// Get system settings
$settings = [];
try {
    $settings_result = $db->select('luna_settings', '*', 'is_system = :is_system', [':is_system' => 'yes']);
    foreach ($settings_result as $row) {
        $settings[$row['key']] = $row['value'];
    }
} catch (Exception $e) {
    // Use defaults
    $settings = [
        'site_name' => 'Luna Dine',
        'site_email' => 'info@lunadine.com',
        'site_phone' => '+880 1234-567890',
        'currency_symbol' => '৳',
        'facebook_url' => 'https://facebook.com/lunadine',
        'twitter_url' => 'https://twitter.com/lunadine',
        'instagram_url' => 'https://instagram.com/lunadine'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name']); ?> - Advanced QR Menu System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: none;
            color: white;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }
        
        .nav-links a:hover {
            opacity: 0.8;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 120px 0 80px;
            text-align: center;
            margin-top: 70px;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: fadeInUp 1s ease;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease 0.2s both;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease 0.4s both;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .btn-primary {
            background: white;
            color: #667eea;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: transparent;
            color: white;
            border-color: white;
        }
        
        .btn-secondary:hover {
            background: white;
            color: #667eea;
        }
        
        /* Features Section */
        .features {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #333;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
        
        /* Branches Section */
        .branches {
            padding: 80px 0;
        }
        
        .branches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .branch-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .branch-card:hover {
            transform: translateY(-5px);
        }
        
        .branch-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .branch-info {
            padding: 1.5rem;
        }
        
        .branch-info h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .branch-info p {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .branch-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 0.9rem;
        }
        
        /* Featured Items */
        .featured-items {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .item-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .item-card:hover {
            transform: translateY(-5px);
        }
        
        .item-image {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }
        
        .item-info {
            padding: 1.5rem;
        }
        
        .item-info h4 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .item-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .item-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        /* Footer */
        footer {
            background: #333;
            color: white;
            padding: 40px 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h3 {
            margin-bottom: 1rem;
            color: #667eea;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 0.5rem;
        }
        
        .footer-section a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #555;
            color: #ccc;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: #667eea;
            color: white;
            text-align: center;
            line-height: 40px;
            border-radius: 50%;
            transition: background 0.3s ease;
        }
        
        .social-links a:hover {
            background: #764ba2;
        }
        
        /* Animations */
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
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .features-grid,
            .branches-grid,
            .items-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="container">
            <a href="/" class="logo">🌙 <?php echo htmlspecialchars($settings['site_name']); ?></a>
            <ul class="nav-links">
                <li><a href="/">Home</a></li>
                <li><a href="/menu">Menu</a></li>
                <li><a href="/branches">Branches</a></li>
                <li><a href="/about">About</a></li>
                <li><a href="/contact">Contact</a></li>
                <li><a href="/login">Staff Login</a></li>
            </ul>
        </nav>
    </header>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Welcome to <?php echo htmlspecialchars($settings['site_name']); ?></h1>
            <p>Experience the future of restaurant dining with our advanced QR menu system</p>
            <div class="cta-buttons">
                <a href="/menu" class="btn btn-primary">View Menu</a>
                <a href="/branches" class="btn btn-secondary">Find Branch</a>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Why Choose Us?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3>QR Code Ordering</h3>
                    <p>Scan QR codes at your table to browse menu and place orders instantly</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🍽️</div>
                    <h3>Digital Menu</h3>
                    <p>Interactive digital menu with images, descriptions, and real-time availability</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">⚡</div>
                    <h3>Fast Service</h3>
                    <p>Quick order processing and real-time status updates for better dining experience</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🏪</div>
                    <h3>Multi-branch</h3>
                    <p>Access menus and order from any of our branches across the city</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💳</div>
                    <h3>Easy Payment</h3>
                    <p>Multiple payment options including cash, card, and mobile banking</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🌐</div>
                    <h3>Bilingual Support</h3>
                    <p>Available in both English and Bangla for your convenience</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Branches Section -->
    <section class="branches">
        <div class="container">
            <h2 class="section-title">Our Branches</h2>
            <div class="branches-grid">
                <?php if (!empty($branches)): ?>
                    <?php foreach ($branches as $branch): ?>
                        <div class="branch-card">
                            <div class="branch-image">
                                🏪
                            </div>
                            <div class="branch-info">
                                <h3><?php echo htmlspecialchars($branch['name']); ?></h3>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($branch['address']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($branch['phone']); ?></p>
                                <p><strong>Hours:</strong> <?php echo htmlspecialchars($branch['opening_time']); ?> - <?php echo htmlspecialchars($branch['closing_time']); ?></p>
                                <div class="branch-actions">
                                    <a href="/branch/<?php echo htmlspecialchars($branch['code']); ?>" class="btn btn-primary btn-small">View Menu</a>
                                    <a href="/contact" class="btn btn-secondary btn-small">Contact</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No branches available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Featured Items Section -->
    <section class="featured-items">
        <div class="container">
            <h2 class="section-title">Featured Items</h2>
            <div class="items-grid">
                <?php if (!empty($featuredItems)): ?>
                    <?php foreach ($featuredItems as $item): ?>
                        <div class="item-card">
                            <div class="item-image">
                                🍽️
                            </div>
                            <div class="item-info">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p><?php echo htmlspecialchars(substr($item['description'], 0, 100)) . '...'; ?></p>
                                <div class="item-price"><?php echo htmlspecialchars($settings['currency_symbol']); ?><?php echo number_format($item['price'], 2); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No featured items available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About <?php echo htmlspecialchars($settings['site_name']); ?></h3>
                    <p>Advanced QR menu system for multi-branch restaurants, providing seamless dining experience with digital ordering and management.</p>
                    <div class="social-links">
                        <a href="<?php echo htmlspecialchars($settings['facebook_url']); ?>">📘</a>
                        <a href="<?php echo htmlspecialchars($settings['twitter_url']); ?>">🐦</a>
                        <a href="<?php echo htmlspecialchars($settings['instagram_url']); ?>">📷</a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="/">Home</a></li>
                        <li><a href="/menu">Menu</a></li>
                        <li><a href="/branches">Branches</a></li>
                        <li><a href="/about">About</a></li>
                        <li><a href="/contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Services</h3>
                    <ul>
                        <li><a href="#">QR Code Ordering</a></li>
                        <li><a href="#">Digital Menu</a></li>
                        <li><a href="#">Online Reservations</a></li>
                        <li><a href="#">Delivery Services</a></li>
                        <li><a href="#">Catering</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <ul>
                        <li>📞 <?php echo htmlspecialchars($settings['site_phone']); ?></li>
                        <li>✉️ <?php echo htmlspecialchars($settings['site_email']); ?></li>
                        <li>📍 Multiple locations across Bangladesh</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name']); ?>. All rights reserved. | v<?php echo LUNA_DINE_VERSION; ?></p>
            </div>
        </div>
    </footer>
    
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Add scroll effect to header
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(102, 126, 234, 0.95)';
            } else {
                header.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            }
        });
    </script>
</body>
</html>