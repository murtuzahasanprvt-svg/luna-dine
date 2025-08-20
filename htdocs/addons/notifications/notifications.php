<?php
/**
 * Notifications Addon
 * 
 * Real-time notifications for orders, customers, and staff
 */

class Notifications {
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
            'enable_email_notifications' => 'true',
            'enable_push_notifications' => 'true',
            'enable_sms_notifications' => 'false',
            'email_order_created' => 'true',
            'email_order_ready' => 'true',
            'push_order_status' => 'true',
            'sound_enabled' => 'true'
        ];
        
        foreach ($default_settings as $key => $value) {
            self::$settings[$key] = get_option('notifications_' . $key, $value);
        }
    }
    
    /**
     * Create addon tables
     */
    private static function createTables() {
        try {
            // Notification Templates table
            self::$db->exec("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "notification_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                type TEXT NOT NULL CHECK(type IN ('email', 'push', 'sms')),
                event TEXT NOT NULL,
                subject TEXT,
                message TEXT NOT NULL,
                is_active TEXT DEFAULT 'yes',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Notification Queue table
            self::$db->exec("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "notification_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                type TEXT NOT NULL CHECK(type IN ('email', 'push', 'sms')),
                recipient TEXT NOT NULL,
                subject TEXT,
                message TEXT NOT NULL,
                status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'sent', 'failed')),
                attempts INTEGER DEFAULT 0,
                sent_at DATETIME,
                error_message TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            
            // User Notification Preferences table
            self::$db->exec("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "user_notification_preferences (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                email_notifications TEXT DEFAULT 'yes',
                push_notifications TEXT DEFAULT 'yes',
                sms_notifications TEXT DEFAULT 'no',
                order_notifications TEXT DEFAULT 'yes',
                system_notifications TEXT DEFAULT 'yes',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES " . DB_PREFIX . "users(id) ON DELETE CASCADE
            )");
            
        } catch (Exception $e) {
            error_log("Notifications addon table creation failed: " . $e->getMessage());
        }
    }
    
    /**
     * Add admin menu items
     */
    public static function adminMenu() {
        return [
            'notifications' => [
                'title' => 'Notifications',
                'icon' => 'fa-bell',
                'submenu' => [
                    'templates' => ['title' => 'Templates', 'url' => '/admin/notifications/templates'],
                    'queue' => ['title' => 'Queue', 'url' => '/admin/notifications/queue'],
                    'settings' => ['title' => 'Settings', 'url' => '/admin/notifications/settings']
                ]
            ]
        ];
    }
    
    /**
     * Add frontend header content
     */
    public static function frontendHeader() {
        if (self::$settings['enable_push_notifications'] === 'true') {
            return '
            <script src="' . SITE_URL . '/addons/notifications/assets/push-notifications.js"></script>
            <link rel="manifest" href="' . SITE_URL . '/addons/notifications/manifest.json">
            ';
        }
        return '';
    }
    
    /**
     * Handle order created event
     */
    public static function onOrderCreated($order_id) {
        $order = self::getOrder($order_id);
        if (!$order) return;
        
        // Send email notification to customer
        if (self::$settings['email_order_created'] === 'true' && $order['customer_email']) {
            self::sendEmailNotification($order['customer_email'], 'order_created', $order);
        }
        
        // Send push notification to staff
        if (self::$settings['push_order_status'] === 'true') {
            self::sendPushNotification('staff', 'new_order', $order);
        }
        
        // Send SMS notification if enabled
        if (self::$settings['enable_sms_notifications'] === 'true' && $order['customer_phone']) {
            self::sendSMSNotification($order['customer_phone'], 'order_created', $order);
        }
    }
    
    /**
     * Handle order updated event
     */
    public static function onOrderUpdated($order_id, $old_status, $new_status) {
        $order = self::getOrder($order_id);
        if (!$order) return;
        
        // Send email notification for order ready
        if ($new_status === 'ready' && self::$settings['email_order_ready'] === 'true' && $order['customer_email']) {
            self::sendEmailNotification($order['customer_email'], 'order_ready', $order);
        }
        
        // Send push notification for status change
        if (self::$settings['push_order_status'] === 'true') {
            self::sendPushNotification('customer', 'order_status_changed', [
                'order_id' => $order_id,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'order_number' => $order['order_number']
            ]);
        }
    }
    
    /**
     * Handle user login event
     */
    public static function onUserLogin($user_id) {
        // Send welcome back notification
        $user = self::getUser($user_id);
        if (!$user) return;
        
        self::sendPushNotification('user', 'welcome_back', [
            'user_id' => $user_id,
            'user_name' => $user['first_name'] . ' ' . $user['last_name']
        ]);
    }
    
    /**
     * Send email notification
     */
    private static function sendEmailNotification($recipient, $event, $data) {
        if (self::$settings['enable_email_notifications'] !== 'true') {
            return false;
        }
        
        $template = self::getTemplate('email', $event);
        if (!$template) {
            return false;
        }
        
        $subject = self::parseTemplate($template['subject'], $data);
        $message = self::parseTemplate($template['message'], $data);
        
        // Add to queue
        return self::addToQueue('email', $recipient, $subject, $message);
    }
    
    /**
     * Send push notification
     */
    private static function sendPushNotification($audience, $event, $data) {
        if (self::$settings['enable_push_notifications'] !== 'true') {
            return false;
        }
        
        $template = self::getTemplate('push', $event);
        if (!$template) {
            return false;
        }
        
        $title = self::parseTemplate($template['subject'] ?? '', $data);
        $message = self::parseTemplate($template['message'], $data);
        
        // Add to queue (in real implementation, this would use Web Push API)
        return self::addToQueue('push', $audience, $title, $message);
    }
    
    /**
     * Send SMS notification
     */
    private static function sendSMSNotification($recipient, $event, $data) {
        if (self::$settings['enable_sms_notifications'] !== 'true') {
            return false;
        }
        
        $template = self::getTemplate('sms', $event);
        if (!$template) {
            return false;
        }
        
        $message = self::parseTemplate($template['message'], $data);
        
        // Add to queue
        return self::addToQueue('sms', $recipient, '', $message);
    }
    
    /**
     * Get notification template
     */
    private static function getTemplate($type, $event) {
        try {
            $stmt = self::$db->prepare("SELECT * FROM " . DB_PREFIX . "notification_templates WHERE type = ? AND event = ? AND is_active = 'yes'");
            $stmt->execute([$type, $event]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Template retrieval failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Parse template with data
     */
    private static function parseTemplate($template, $data) {
        if (empty($template)) {
            return '';
        }
        
        $replacements = [
            '{order_number}' => $data['order_number'] ?? '',
            '{customer_name}' => $data['customer_name'] ?? '',
            '{total_amount}' => $data['total'] ?? '',
            '{status}' => $data['status'] ?? '',
            '{restaurant_name}' => SITE_NAME,
            '{restaurant_phone}' => SITE_PHONE,
            '{restaurant_email}' => SITE_EMAIL
        ];
        
        foreach ($replacements as $key => $value) {
            $template = str_replace($key, $value, $template);
        }
        
        return $template;
    }
    
    /**
     * Add notification to queue
     */
    private static function addToQueue($type, $recipient, $subject, $message) {
        try {
            $stmt = self::$db->prepare("INSERT INTO " . DB_PREFIX . "notification_queue (type, recipient, subject, message, status) VALUES (?, ?, ?, ?, 'pending')");
            return $stmt->execute([$type, $recipient, $subject, $message]);
        } catch (Exception $e) {
            error_log("Queue insertion failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get order details
     */
    private static function getOrder($order_id) {
        try {
            $stmt = self::$db->prepare("SELECT * FROM " . DB_PREFIX . "orders WHERE id = ?");
            $stmt->execute([$order_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Order retrieval failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user details
     */
    private static function getUser($user_id) {
        try {
            $stmt = self::$db->prepare("SELECT * FROM " . DB_PREFIX . "users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("User retrieval failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Process notification queue
     */
    public static function processQueue() {
        try {
            $stmt = self::$db->prepare("SELECT * FROM " . DB_PREFIX . "notification_queue WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT 10");
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($notifications as $notification) {
                $sent = false;
                
                switch ($notification['type']) {
                    case 'email':
                        $sent = self::sendEmail($notification);
                        break;
                    case 'push':
                        $sent = self::sendPush($notification);
                        break;
                    case 'sms':
                        $sent = self::sendSMS($notification);
                        break;
                }
                
                if ($sent) {
                    $update_stmt = self::$db->prepare("UPDATE " . DB_PREFIX . "notification_queue SET status = 'sent', sent_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $update_stmt->execute([$notification['id']]);
                } else {
                    $update_stmt = self::$db->prepare("UPDATE " . DB_PREFIX . "notification_queue SET attempts = attempts + 1, error_message = ? WHERE id = ?");
                    $update_stmt->execute(['Failed to send', $notification['id']]);
                }
            }
        } catch (Exception $e) {
            error_log("Queue processing failed: " . $e->getMessage());
        }
    }
    
    /**
     * Send email (placeholder implementation)
     */
    private static function sendEmail($notification) {
        // In real implementation, this would use PHPMailer or similar
        // For now, just simulate success
        return true;
    }
    
    /**
     * Send push notification (placeholder implementation)
     */
    private static function sendPush($notification) {
        // In real implementation, this would use Web Push API
        // For now, just simulate success
        return true;
    }
    
    /**
     * Send SMS (placeholder implementation)
     */
    private static function sendSMS($notification) {
        // In real implementation, this would use Twilio or similar
        // For now, just simulate success
        return true;
    }
    
    /**
     * Get notification queue
     */
    public static function getQueue($status = null) {
        try {
            $sql = "SELECT * FROM " . DB_PREFIX . "notification_queue";
            $params = [];
            
            if ($status) {
                $sql .= " WHERE status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = self::$db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Queue retrieval failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get notification templates
     */
    public static function getTemplates($type = null) {
        try {
            $sql = "SELECT * FROM " . DB_PREFIX . "notification_templates";
            $params = [];
            
            if ($type) {
                $sql .= " WHERE type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY type, event";
            
            $stmt = self::$db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Templates retrieval failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Save notification template
     */
    public static function saveTemplate($data) {
        try {
            $stmt = self::$db->prepare("INSERT OR REPLACE INTO " . DB_PREFIX . "notification_templates (id, name, type, event, subject, message, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            return $stmt->execute([
                $data['id'] ?? null,
                $data['name'],
                $data['type'],
                $data['event'],
                $data['subject'],
                $data['message'],
                $data['is_active']
            ]);
        } catch (Exception $e) {
            error_log("Template save failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update settings
     */
    public static function updateSettings($settings) {
        foreach ($settings as $key => $value) {
            update_option('notifications_' . $key, $value);
        }
        self::loadSettings();
        return true;
    }
    
    /**
     * Install addon
     */
    public static function install() {
        self::createTables();
        
        // Insert default templates
        $default_templates = [
            [
                'name' => 'Order Created Email',
                'type' => 'email',
                'event' => 'order_created',
                'subject' => 'Order Confirmation - {order_number}',
                'message' => 'Dear {customer_name},\n\nThank you for your order! Your order #{order_number} has been received and is being processed.\n\nOrder Details:\n- Total Amount: {total_amount}\n- Status: {status}\n\nWe will notify you when your order is ready for pickup/delivery.\n\nBest regards,\n{restaurant_name}\n\nContact us: {restaurant_phone} | {restaurant_email}'
            ],
            [
                'name' => 'Order Ready Email',
                'type' => 'email',
                'event' => 'order_ready',
                'subject' => 'Your Order is Ready - {order_number}',
                'message' => 'Dear {customer_name},\n\nGreat news! Your order #{order_number} is now ready for pickup/delivery.\n\nPlease collect your order at your earliest convenience.\n\nThank you for choosing {restaurant_name}!\n\nBest regards,\n{restaurant_name}\n\nContact us: {restaurant_phone} | {restaurant_email}'
            ],
            [
                'name' => 'New Order Push',
                'type' => 'push',
                'event' => 'new_order',
                'subject' => 'New Order Received',
                'message' => 'Order #{order_number} has been placed. Total: {total_amount}'
            ]
        ];
        
        foreach ($default_templates as $template) {
            try {
                $stmt = self::$db->prepare("INSERT INTO " . DB_PREFIX . "notification_templates (name, type, event, subject, message) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $template['name'],
                    $template['type'],
                    $template['event'],
                    $template['subject'],
                    $template['message']
                ]);
            } catch (Exception $e) {
                error_log("Default template creation failed: " . $e->getMessage());
            }
        }
        
        return true;
    }
    
    /**
     * Uninstall addon
     */
    public static function uninstall() {
        try {
            // Drop addon tables
            self::$db->exec("DROP TABLE IF EXISTS " . DB_PREFIX . "notification_templates");
            self::$db->exec("DROP TABLE IF EXISTS " . DB_PREFIX . "notification_queue");
            self::$db->exec("DROP TABLE IF EXISTS " . DB_PREFIX . "user_notification_preferences");
            
            // Remove settings
            self::$db->exec("DELETE FROM " . DB_PREFIX . "settings WHERE key LIKE 'notifications_%'");
            
        } catch (Exception $e) {
            error_log("Notifications uninstall failed: " . $e->getMessage());
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