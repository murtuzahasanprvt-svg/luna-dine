<?php
/**
 * Default Theme Footer Template
 */

// Get theme instance
global $theme;
?>

    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About <?php echo SITE_NAME; ?></h3>
                    <p><?php echo SITE_NAME; ?> is an advanced QR menu system designed for modern restaurants. Experience seamless ordering with our innovative technology.</p>
                    <div class="social-links">
                        <a href="<?php echo FACEBOOK_URL; ?>" target="_blank"><i class="fab fa-facebook"></i></a>
                        <a href="<?php echo TWITTER_URL; ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <a href="<?php echo INSTAGRAM_URL; ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="<?php echo YOUTUBE_URL; ?>" target="_blank"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>/menu">Menu</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/order">Order Online</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/about">About Us</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact">Contact</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/privacy">Privacy Policy</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/terms">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Services</h3>
                    <ul>
                        <li><a href="#">QR Code Ordering</a></li>
                        <li><a href="#">Online Delivery</a></li>
                        <li><a href="#">Table Reservation</a></li>
                        <li><a href="#">Catering Services</a></li>
                        <li><a href="#">Private Events</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <ul>
                        <li><i class="fas fa-phone"></i> <?php echo SITE_PHONE; ?></li>
                        <li><i class="fas fa-envelope"></i> <?php echo SITE_EMAIL; ?></li>
                        <li><i class="fas fa-map-marker-alt"></i> Dhaka, Bangladesh</li>
                        <li><i class="fas fa-clock"></i> Daily: 10:00 AM - 10:00 PM</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved. | Powered by Luna Dine System</p>
            </div>
        </div>
    </footer>
    
    <!-- Theme JavaScript -->
    <script src="<?php echo $theme->getAssetURL('js/script.js'); ?>"></script>
    
    <!-- Additional scripts -->
    <?php if (isset($additional_scripts)) echo $additional_scripts; ?>
    
    <!-- Execute theme hooks -->
    <?php
    if (isset($addons)) {
        echo $addons->executeHook('frontend_footer');
    }
    ?>
</body>
</html>