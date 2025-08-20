<?php
/**
 * QR Generator Addon
 * 
 * Advanced QR code generation with custom designs and analytics
 */

class QRGenerator {
    private static $db;
    private static $settings = [];
    
    /**
     * Initialize the addon
     */
    public static function init() {
        global $db;
        self::$db = $db;
        self::loadSettings();
        
        // Create addon tables if they don't exist
        self::createTables();
    }
    
    /**
     * Load addon settings
     */
    private static function loadSettings() {
        $default_settings = [
            'qr_size' => '300',
            'qr_error_correction' => 'H',
            'qr_margin' => '4',
            'qr_bg_color' => '#FFFFFF',
            'qr_fg_color' => '#000000',
            'enable_analytics' => 'true'
        ];
        
        foreach ($default_settings as $key => $value) {
            self::$settings[$key] = get_option('qr_generator_' . $key, $value);
        }
    }
    
    /**
     * Create addon tables
     */
    private static function createTables() {
        try {
            // QR Analytics table
            self::$db->exec("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "qr_analytics (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                qr_code TEXT NOT NULL,
                branch_id INTEGER,
                table_id INTEGER,
                scan_count INTEGER DEFAULT 0,
                last_scan DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (branch_id) REFERENCES " . DB_PREFIX . "branches(id) ON DELETE CASCADE,
                FOREIGN KEY (table_id) REFERENCES " . DB_PREFIX . "tables(id) ON DELETE CASCADE
            )");
            
            // QR Designs table
            self::$db->exec("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "qr_designs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                bg_color TEXT DEFAULT '#FFFFFF',
                fg_color TEXT DEFAULT '#000000',
                logo_path TEXT,
                eye_color TEXT DEFAULT '#000000',
                pattern_type TEXT DEFAULT 'square',
                is_default TEXT DEFAULT 'no',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            
        } catch (Exception $e) {
            error_log("QR Generator addon table creation failed: " . $e->getMessage());
        }
    }
    
    /**
     * Add admin menu items
     */
    public static function adminMenu() {
        return [
            'qr_generator' => [
                'title' => 'QR Generator',
                'icon' => 'fa-qrcode',
                'submenu' => [
                    'designs' => ['title' => 'QR Designs', 'url' => '/admin/qr-generator/designs'],
                    'analytics' => ['title' => 'QR Analytics', 'url' => '/admin/qr-generator/analytics'],
                    'settings' => ['title' => 'Settings', 'url' => '/admin/qr-generator/settings']
                ]
            ]
        ];
    }
    
    /**
     * Add frontend header content
     */
    public static function frontendHeader() {
        if (self::$settings['enable_analytics'] === 'true') {
            return '<script src="' . SITE_URL . '/addons/qr_generator/assets/tracker.js"></script>';
        }
        return '';
    }
    
    /**
     * Handle order created event
     */
    public static function onOrderCreated($order_id) {
        // Track order source if it came from QR scan
        if (isset($_SESSION['qr_scan_id'])) {
            try {
                $stmt = self::$db->prepare("UPDATE " . DB_PREFIX . "qr_analytics SET order_count = order_count + 1 WHERE id = ?");
                $stmt->execute([$_SESSION['qr_scan_id']]);
            } catch (Exception $e) {
                error_log("QR Generator analytics update failed: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Generate QR code with custom design
     */
    public static function generateQR($data, $options = []) {
        $size = $options['size'] ?? self::$settings['qr_size'];
        $error_correction = $options['error_correction'] ?? self::$settings['qr_error_correction'];
        $margin = $options['margin'] ?? self::$settings['qr_margin'];
        $bg_color = $options['bg_color'] ?? self::$settings['qr_bg_color'];
        $fg_color = $options['fg_color'] ?? self::$settings['qr_fg_color'];
        
        // Generate QR code URL using Google Charts API
        $qr_url = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($data);
        $qr_url .= "&choe=UTF-8&chld={$error_correction}|{$margin}";
        $qr_url .= "&chco=" . str_replace('#', '', $fg_color) . "|" . str_replace('#', '', $bg_color);
        
        return $qr_url;
    }
    
    /**
     * Track QR scan
     */
    public static function trackScan($qr_code, $branch_id = null, $table_id = null) {
        if (self::$settings['enable_analytics'] !== 'true') {
            return;
        }
        
        try {
            // Check if QR code exists in analytics
            $stmt = self::$db->prepare("SELECT id FROM " . DB_PREFIX . "qr_analytics WHERE qr_code = ?");
            $stmt->execute([$qr_code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Update existing record
                $stmt = self::$db->prepare("UPDATE " . DB_PREFIX . "qr_analytics SET scan_count = scan_count + 1, last_scan = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$result['id']]);
                $_SESSION['qr_scan_id'] = $result['id'];
            } else {
                // Create new record
                $stmt = self::$db->prepare("INSERT INTO " . DB_PREFIX . "qr_analytics (qr_code, branch_id, table_id, scan_count, last_scan) VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)");
                $stmt->execute([$qr_code, $branch_id, $table_id]);
                $_SESSION['qr_scan_id'] = self::$db->lastInsertId();
            }
        } catch (Exception $e) {
            error_log("QR Generator tracking failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get QR analytics data
     */
    public static function getAnalytics($branch_id = null, $start_date = null, $end_date = null) {
        try {
            $sql = "SELECT * FROM " . DB_PREFIX . "qr_analytics WHERE 1=1";
            $params = [];
            
            if ($branch_id) {
                $sql .= " AND branch_id = ?";
                $params[] = $branch_id;
            }
            
            if ($start_date) {
                $sql .= " AND created_at >= ?";
                $params[] = $start_date;
            }
            
            if ($end_date) {
                $sql .= " AND created_at <= ?";
                $params[] = $end_date;
            }
            
            $sql .= " ORDER BY scan_count DESC";
            
            $stmt = self::$db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("QR Generator analytics retrieval failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get QR designs
     */
    public static function getDesigns() {
        try {
            $stmt = self::$db->prepare("SELECT * FROM " . DB_PREFIX . "qr_designs ORDER BY is_default DESC, name");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("QR Generator designs retrieval failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Save QR design
     */
    public static function saveDesign($data) {
        try {
            $stmt = self::$db->prepare("INSERT INTO " . DB_PREFIX . "qr_designs (name, description, bg_color, fg_color, logo_path, eye_color, pattern_type, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            return $stmt->execute([
                $data['name'],
                $data['description'],
                $data['bg_color'],
                $data['fg_color'],
                $data['logo_path'],
                $data['eye_color'],
                $data['pattern_type'],
                $data['is_default']
            ]);
        } catch (Exception $e) {
            error_log("QR Generator design save failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update settings
     */
    public static function updateSettings($settings) {
        foreach ($settings as $key => $value) {
            update_option('qr_generator_' . $key, $value);
        }
        self::loadSettings();
        return true;
    }
    
    /**
     * Install addon
     */
    public static function install() {
        self::createTables();
        
        // Insert default design
        try {
            $stmt = self::$db->prepare("INSERT INTO " . DB_PREFIX . "qr_designs (name, description, bg_color, fg_color, is_default) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['Default', 'Default QR design', '#FFFFFF', '#000000', 'yes']);
        } catch (Exception $e) {
            error_log("QR Generator default design creation failed: " . $e->getMessage());
        }
        
        return true;
    }
    
    /**
     * Uninstall addon
     */
    public static function uninstall() {
        try {
            // Drop addon tables
            self::$db->exec("DROP TABLE IF EXISTS " . DB_PREFIX . "qr_analytics");
            self::$db->exec("DROP TABLE IF EXISTS " . DB_PREFIX . "qr_designs");
            
            // Remove settings
            self::$db->exec("DELETE FROM " . DB_PREFIX . "settings WHERE key LIKE 'qr_generator_%'");
            
        } catch (Exception $e) {
            error_log("QR Generator uninstall failed: " . $e->getMessage());
        }
        
        return true;
    }
    
    /**
     * Destroy addon
     */
    public static function destroy() {
        // Cleanup when addon is disabled
    }
}
?>