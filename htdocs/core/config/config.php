<?php
/**
 * Luna Dine Configuration File
 * 
 * System configuration settings for Luna Dine
 */

// Database Configuration
define('DB_TYPE', 'sqlite');
define('DB_PATH', LUNA_DINE_DATABASE . '/luna_dine.db');
define('DB_PREFIX', 'luna_');

// Site Configuration
define('SITE_NAME', 'Luna Dine');
define('SITE_URL', 'http://localhost/moon-tech-project');
define('SITE_EMAIL', 'info@lunadine.com');
define('SITE_PHONE', '+880 1234-567890');

// Admin Configuration
define('ADMIN_EMAIL', 'admin@lunadine.com');
define('SUPER_ADMIN_EMAIL', 'developer@lunadine.com');

// Language Configuration
define('DEFAULT_LANGUAGE', 'en');
define('SUPPORTED_LANGUAGES', serialize(['en' => 'English', 'bn' => 'Bangla']));

// Theme Configuration
define('DEFAULT_THEME', 'default');
define('ACTIVE_THEME', 'default');

// Security Configuration
define('HASH_COST', 12);
define('SESSION_LIFETIME', 7200); // 2 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Upload Configuration
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', serialize(['jpg', 'jpeg', 'png', 'gif', 'webp']));
define('UPLOAD_PATH', LUNA_DINE_UPLOADS);

// QR Code Configuration
define('QR_SIZE', 300);
define('QR_ERROR_CORRECTION', 'H');
define('QR_MARGIN', 4);

// Pagination Configuration
define('ITEMS_PER_PAGE', 20);
define('MAX_ITEMS_PER_PAGE', 100);

// Cache Configuration
define('ENABLE_CACHE', true);
define('CACHE_LIFETIME', 3600); // 1 hour

// Debug Configuration
define('DEBUG_MODE', true);
define('LOG_ERRORS', true);
define('ERROR_LOG_PATH', LUNA_DINE_ROOT . '/logs/error.log');

// Email Configuration
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', SITE_EMAIL);
define('SMTP_FROM_NAME', SITE_NAME);

// Timezone Configuration
define('TIMEZONE', 'Asia/Dhaka');

// Currency Configuration
define('CURRENCY', 'BDT');
define('CURRENCY_SYMBOL', '৳');
define('CURRENCY_POSITION', 'before');

// Order Configuration
define('ORDER_PREFIX', 'LD');
define('ORDER_STATUS_PENDING', 'pending');
define('ORDER_STATUS_CONFIRMED', 'confirmed');
define('ORDER_STATUS_PREPARING', 'preparing');
define('ORDER_STATUS_READY', 'ready');
define('ORDER_STATUS_DELIVERED', 'delivered');
define('ORDER_STATUS_CANCELLED', 'cancelled');

// Payment Configuration
define('PAYMENT_METHODS', serialize(['cash', 'card', 'mobile_banking']));

// Table Configuration
define('TABLE_PREFIX', 'TBL');
define('DEFAULT_BRANCH_ID', 1);

// Addon Configuration
define('ENABLE_ADDONS', true);
define('ADDON_PATH', LUNA_DINE_ADDONS);

// Theme Configuration
define('ENABLE_THEMES', true);
define('THEME_PATH', LUNA_DINE_THEMES);

// API Configuration
define('API_ENABLED', true);
define('API_RATE_LIMIT', 100); // requests per minute
define('API_KEY_LIFETIME', 86400); // 24 hours

// Maintenance Configuration
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_MESSAGE', 'System is under maintenance. Please try again later.');

// Backup Configuration
define('AUTO_BACKUP', true);
define('BACKUP_INTERVAL', 86400); // 24 hours
define('BACKUP_PATH', LUNA_DINE_ROOT . '/backups');
define('MAX_BACKUPS', 7);

// Social Media Configuration
define('FACEBOOK_URL', 'https://facebook.com/lunadine');
define('TWITTER_URL', 'https://twitter.com/lunadine');
define('INSTAGRAM_URL', 'https://instagram.com/lunadine');
define('YOUTUBE_URL', 'https://youtube.com/lunadine');

// Google Configuration
define('GOOGLE_MAPS_API_KEY', '');
define('GOOGLE_ANALYTICS_ID', '');

// System Configuration
define('SYSTEM_VERSION', LUNA_DINE_VERSION);
define('SYSTEM_UPDATED', '2024-01-01');
define('SYSTEM_AUTHOR', 'Luna Dine Development Team');

// Error Handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (DEBUG_MODE) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<strong>Error:</strong> $errstr<br>";
        echo "<strong>File:</strong> $errfile<br>";
        echo "<strong>Line:</strong> $errline<br>";
        echo "</div>";
    }
    
    if (LOG_ERRORS) {
        error_log("[$errno] $errstr in $errfile on line $errline", 3, ERROR_LOG_PATH);
    }
    
    return true;
});

// Exception Handling
set_exception_handler(function($exception) {
    if (DEBUG_MODE) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
        echo "<strong>Exception:</strong> " . $exception->getMessage() . "<br>";
        echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
        echo "<strong>Trace:</strong><pre>" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
    }
    
    if (LOG_ERRORS) {
        error_log("Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine(), 3, ERROR_LOG_PATH);
    }
});
?>