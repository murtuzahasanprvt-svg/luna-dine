<?php
/**
 * Luna Dine Settings System
 * 
 * Handles comprehensive system settings management
 */

class Settings {
    private $db;
    private $settings = [];
    private $settingsGroups = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadSettings();
        $this->defineSettingsGroups();
    }
    
    /**
     * Load all settings from database
     */
    private function loadSettings() {
        try {
            $result = $this->db->select('luna_settings', '*', '1=1', [], 'key ASC');
            
            foreach ($result as $row) {
                $this->settings[$row['key']] = [
                    'value' => $row['value'],
                    'description' => $row['description'],
                    'type' => $row['type'],
                    'is_system' => $row['is_system']
                ];
            }
            
        } catch (Exception $e) {
            error_log("Load settings failed: " . $e->getMessage());
        }
    }
    
    /**
     * Define settings groups
     */
    private function defineSettingsGroups() {
        $this->settingsGroups = [
            'general' => [
                'title' => 'General Settings',
                'description' => 'Basic system configuration',
                'icon' => 'fas fa-cog',
                'settings' => [
                    'site_name' => [
                        'label' => 'Site Name',
                        'type' => 'text',
                        'description' => 'The name of your website',
                        'default' => 'Luna Dine'
                    ],
                    'site_email' => [
                        'label' => 'Site Email',
                        'type' => 'email',
                        'description' => 'Main contact email address',
                        'default' => 'info@lunadine.com'
                    ],
                    'site_phone' => [
                        'label' => 'Site Phone',
                        'type' => 'tel',
                        'description' => 'Main contact phone number',
                        'default' => '+880 1234-567890'
                    ],
                    'site_description' => [
                        'label' => 'Site Description',
                        'type' => 'textarea',
                        'description' => 'Brief description of your website',
                        'default' => 'Advanced QR menu system for restaurants'
                    ],
                    'site_keywords' => [
                        'label' => 'Site Keywords',
                        'type' => 'text',
                        'description' => 'SEO keywords (comma separated)',
                        'default' => 'restaurant, qr menu, food ordering, dining'
                    ]
                ]
            ],
            'appearance' => [
                'title' => 'Appearance',
                'description' => 'Website appearance and theme settings',
                'icon' => 'fas fa-palette',
                'settings' => [
                    'active_theme' => [
                        'label' => 'Active Theme',
                        'type' => 'select',
                        'description' => 'Select the active theme',
                        'options' => ['default' => 'Default Theme', 'dark' => 'Dark Theme'],
                        'default' => 'default'
                    ],
                    'logo_path' => [
                        'label' => 'Logo Path',
                        'type' => 'file',
                        'description' => 'Website logo image',
                        'default' => '/assets/img/logo.png'
                    ],
                    'favicon_path' => [
                        'label' => 'Favicon Path',
                        'type' => 'file',
                        'description' => 'Website favicon',
                        'default' => '/assets/img/favicon.ico'
                    ],
                    'custom_css' => [
                        'label' => 'Custom CSS',
                        'type' => 'textarea',
                        'description' => 'Additional CSS styles',
                        'default' => ''
                    ],
                    'custom_js' => [
                        'label' => 'Custom JavaScript',
                        'type' => 'textarea',
                        'description' => 'Additional JavaScript code',
                        'default' => ''
                    ]
                ]
            ],
            'localization' => [
                'title' => 'Localization',
                'description' => 'Language and regional settings',
                'icon' => 'fas fa-globe',
                'settings' => [
                    'default_language' => [
                        'label' => 'Default Language',
                        'type' => 'select',
                        'description' => 'Default website language',
                        'options' => ['en' => 'English', 'bn' => 'Bangla'],
                        'default' => 'en'
                    ],
                    'timezone' => [
                        'label' => 'Timezone',
                        'type' => 'select',
                        'description' => 'Server timezone',
                        'options' => [
                            'Asia/Dhaka' => 'Asia/Dhaka',
                            'UTC' => 'UTC',
                            'Asia/Kolkata' => 'Asia/Kolkata'
                        ],
                        'default' => 'Asia/Dhaka'
                    ],
                    'date_format' => [
                        'label' => 'Date Format',
                        'type' => 'select',
                        'description' => 'Default date display format',
                        'options' => [
                            'Y-m-d' => 'YYYY-MM-DD',
                            'd/m/Y' => 'DD/MM/YYYY',
                            'm/d/Y' => 'MM/DD/YYYY'
                        ],
                        'default' => 'Y-m-d'
                    ],
                    'time_format' => [
                        'label' => 'Time Format',
                        'type' => 'select',
                        'description' => 'Default time display format',
                        'options' => [
                            'H:i' => '24-hour format',
                            'h:i A' => '12-hour format'
                        ],
                        'default' => 'H:i'
                    ]
                ]
            ],
            'currency' => [
                'title' => 'Currency',
                'description' => 'Currency and pricing settings',
                'icon' => 'fas fa-money-bill',
                'settings' => [
                    'currency' => [
                        'label' => 'Currency Code',
                        'type' => 'text',
                        'description' => 'ISO currency code (e.g., BDT, USD, EUR)',
                        'default' => 'BDT'
                    ],
                    'currency_symbol' => [
                        'label' => 'Currency Symbol',
                        'type' => 'text',
                        'description' => 'Currency symbol (e.g., ৳, $, €)',
                        'default' => '৳'
                    ],
                    'currency_position' => [
                        'label' => 'Currency Position',
                        'type' => 'select',
                        'description' => 'Where to place the currency symbol',
                        'options' => [
                            'before' => 'Before amount (৳100)',
                            'after' => 'After amount (100৳)'
                        ],
                        'default' => 'before'
                    ],
                    'decimal_separator' => [
                        'label' => 'Decimal Separator',
                        'type' => 'text',
                        'description' => 'Character for decimal separation',
                        'default' => '.'
                    ],
                    'thousand_separator' => [
                        'label' => 'Thousand Separator',
                        'type' => 'text',
                        'description' => 'Character for thousand separation',
                        'default' => ','
                    ]
                ]
            ],
            'restaurant' => [
                'title' => 'Restaurant Settings',
                'description' => 'Restaurant-specific configuration',
                'icon' => 'fas fa-utensils',
                'settings' => [
                    'restaurant_name' => [
                        'label' => 'Restaurant Name',
                        'type' => 'text',
                        'description' => 'Your restaurant name',
                        'default' => 'Luna Dine Restaurant'
                    ],
                    'restaurant_address' => [
                        'label' => 'Restaurant Address',
                        'type' => 'textarea',
                        'description' => 'Full restaurant address',
                        'default' => 'Dhaka, Bangladesh'
                    ],
                    'restaurant_phone' => [
                        'label' => 'Restaurant Phone',
                        'type' => 'tel',
                        'description' => 'Restaurant contact number',
                        'default' => '+880 1234-567890'
                    ],
                    'opening_hours' => [
                        'label' => 'Opening Hours',
                        'type' => 'textarea',
                        'description' => 'Restaurant opening hours',
                        'default' => "Monday-Sunday: 10:00 AM - 10:00 PM"
                    ],
                    'tax_rate' => [
                        'label' => 'Tax Rate (%)',
                        'type' => 'number',
                        'description' => 'Default tax rate for orders',
                        'default' => '0',
                        'min' => 0,
                        'max' => 100,
                        'step' => 0.1
                    ]
                ]
            ],
            'orders' => [
                'title' => 'Order Settings',
                'description' => 'Order processing and management',
                'icon' => 'fas fa-receipt',
                'settings' => [
                    'order_prefix' => [
                        'label' => 'Order Number Prefix',
                        'type' => 'text',
                        'description' => 'Prefix for order numbers',
                        'default' => 'LD'
                    ],
                    'min_order_amount' => [
                        'label' => 'Minimum Order Amount',
                        'type' => 'number',
                        'description' => 'Minimum amount for orders',
                        'default' => '0',
                        'min' => 0,
                        'step' => 0.01
                    ],
                    'delivery_fee' => [
                        'label' => 'Delivery Fee',
                        'type' => 'number',
                        'description' => 'Default delivery fee',
                        'default' => '0',
                        'min' => 0,
                        'step' => 0.01
                    ],
                    'free_delivery_threshold' => [
                        'label' => 'Free Delivery Threshold',
                        'type' => 'number',
                        'description' => 'Order amount for free delivery',
                        'default' => '0',
                        'min' => 0,
                        'step' => 0.01
                    ],
                    'auto_confirm_orders' => [
                        'label' => 'Auto Confirm Orders',
                        'type' => 'boolean',
                        'description' => 'Automatically confirm new orders',
                        'default' => 'no'
                    ]
                ]
            ],
            'payments' => [
                'title' => 'Payment Settings',
                'description' => 'Payment gateway configuration',
                'icon' => 'fas fa-credit-card',
                'settings' => [
                    'payment_methods' => [
                        'label' => 'Available Payment Methods',
                        'type' => 'multiselect',
                        'description' => 'Select available payment methods',
                        'options' => [
                            'cash' => 'Cash',
                            'card' => 'Credit/Debit Card',
                            'mobile_banking' => 'Mobile Banking'
                        ],
                        'default' => ['cash']
                    ],
                    'ssl_commerz_store_id' => [
                        'label' => 'SSLCommerz Store ID',
                        'type' => 'text',
                        'description' => 'SSLCommerz payment gateway store ID',
                        'default' => ''
                    ],
                    'ssl_commerz_store_password' => [
                        'label' => 'SSLCommerz Store Password',
                        'type' => 'password',
                        'description' => 'SSLCommerz payment gateway password',
                        'default' => ''
                    ],
                    'bkash_merchant_number' => [
                        'label' => 'bKash Merchant Number',
                        'type' => 'text',
                        'description' => 'bKash merchant number for mobile payments',
                        'default' => ''
                    ]
                ]
            ],
            'notifications' => [
                'title' => 'Notifications',
                'description' => 'Email and notification settings',
                'icon' => 'fas fa-bell',
                'settings' => [
                    'email_notifications' => [
                        'label' => 'Email Notifications',
                        'type' => 'boolean',
                        'description' => 'Enable email notifications',
                        'default' => 'yes'
                    ],
                    'smtp_host' => [
                        'label' => 'SMTP Host',
                        'type' => 'text',
                        'description' => 'SMTP server hostname',
                        'default' => 'localhost'
                    ],
                    'smtp_port' => [
                        'label' => 'SMTP Port',
                        'type' => 'number',
                        'description' => 'SMTP server port',
                        'default' => '587',
                        'min' => 1,
                        'max' => 65535
                    ],
                    'smtp_username' => [
                        'label' => 'SMTP Username',
                        'type' => 'text',
                        'description' => 'SMTP username',
                        'default' => ''
                    ],
                    'smtp_password' => [
                        'label' => 'SMTP Password',
                        'type' => 'password',
                        'description' => 'SMTP password',
                        'default' => ''
                    ]
                ]
            ],
            'security' => [
                'title' => 'Security',
                'description' => 'Security and access control',
                'icon' => 'fas fa-shield-alt',
                'settings' => [
                    'session_lifetime' => [
                        'label' => 'Session Lifetime (minutes)',
                        'type' => 'number',
                        'description' => 'How long sessions remain active',
                        'default' => '120',
                        'min' => 15,
                        'max' => 1440
                    ],
                    'max_login_attempts' => [
                        'label' => 'Max Login Attempts',
                        'type' => 'number',
                        'description' => 'Maximum login attempts before lockout',
                        'default' => '5',
                        'min' => 1,
                        'max' => 10
                    ],
                    'login_lockout_time' => [
                        'label' => 'Login Lockout Time (minutes)',
                        'type' => 'number',
                        'description' => 'How long to lock out after failed attempts',
                        'default' => '15',
                        'min' => 1,
                        'max' => 1440
                    ],
                    'force_https' => [
                        'label' => 'Force HTTPS',
                        'type' => 'boolean',
                        'description' => 'Redirect all requests to HTTPS',
                        'default' => 'no'
                    ]
                ]
            ],
            'backup' => [
                'title' => 'Backup',
                'description' => 'Backup and recovery settings',
                'icon' => 'fas fa-database',
                'settings' => [
                    'auto_backup' => [
                        'label' => 'Automatic Backups',
                        'type' => 'boolean',
                        'description' => 'Enable automatic scheduled backups',
                        'default' => 'yes'
                    ],
                    'backup_interval' => [
                        'label' => 'Backup Interval (hours)',
                        'type' => 'number',
                        'description' => 'How often to create automatic backups',
                        'default' => '24',
                        'min' => 1,
                        'max' => 168
                    ],
                    'max_backups' => [
                        'label' => 'Maximum Backups',
                        'type' => 'number',
                        'description' => 'Maximum number of backups to keep',
                        'default' => '7',
                        'min' => 1,
                        'max' => 30
                    ],
                    'backup_notification' => [
                        'label' => 'Backup Notifications',
                        'type' => 'boolean',
                        'description' => 'Send email notifications for backup status',
                        'default' => 'yes'
                    ]
                ]
            ],
            'api' => [
                'title' => 'API',
                'description' => 'API and integration settings',
                'icon' => 'fas fa-code',
                'settings' => [
                    'api_enabled' => [
                        'label' => 'Enable API',
                        'type' => 'boolean',
                        'description' => 'Enable REST API access',
                        'default' => 'yes'
                    ],
                    'api_rate_limit' => [
                        'label' => 'API Rate Limit',
                        'type' => 'number',
                        'description' => 'Requests per minute per IP',
                        'default' => '100',
                        'min' => 10,
                        'max' => 1000
                    ],
                    'api_key_lifetime' => [
                        'label' => 'API Key Lifetime (hours)',
                        'type' => 'number',
                        'description' => 'How long API keys remain valid',
                        'default' => '24',
                        'min' => 1,
                        'max' => 720
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Get setting value
     */
    public function get($key, $default = null) {
        if (isset($this->settings[$key])) {
            $value = $this->settings[$key]['value'];
            
            // Convert value based on type
            switch ($this->settings[$key]['type']) {
                case 'boolean':
                    return $value === 'true' || $value === '1' || $value === 1;
                    
                case 'integer':
                    return (int) $value;
                    
                case 'float':
                    return (float) $value;
                    
                case 'json':
                    return json_decode($value, true) ?: [];
                    
                default:
                    return $value;
            }
        }
        
        // Try to get default from settings groups
        foreach ($this->settingsGroups as $group) {
            if (isset($group['settings'][$key])) {
                $setting = $group['settings'][$key];
                return $setting['default'] ?? $default;
            }
        }
        
        return $default;
    }
    
    /**
     * Set setting value
     */
    public function set($key, $value, $description = '', $type = 'string') {
        try {
            // Convert value to string for storage
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_array($value)) {
                $value = json_encode($value);
                $type = 'json';
            }
            
            $data = [
                'key' => $key,
                'value' => $value,
                'description' => $description,
                'type' => $type,
                'is_system' => 'no'
            ];
            
            // Check if setting exists
            if (isset($this->settings[$key])) {
                $updated = $this->db->update('luna_settings', $data, 'key = :key', [':key' => $key]);
            } else {
                $updated = $this->db->insert('luna_settings', $data);
            }
            
            if ($updated) {
                // Update cached settings
                $this->settings[$key] = [
                    'value' => $value,
                    'description' => $description,
                    'type' => $type,
                    'is_system' => 'no'
                ];
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Set setting failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all settings
     */
    public function getAll() {
        return $this->settings;
    }
    
    /**
     * Get settings groups
     */
    public function getGroups() {
        return $this->settingsGroups;
    }
    
    /**
     * Get settings by group
     */
    public function getGroup($groupName) {
        return isset($this->settingsGroups[$groupName]) ? $this->settingsGroups[$groupName] : null;
    }
    
    /**
     * Update multiple settings
     */
    public function updateMultiple($settings) {
        try {
            $success = true;
            
            foreach ($settings as $key => $value) {
                if (!$this->set($key, $value)) {
                    $success = false;
                }
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("Update multiple settings failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete setting
     */
    public function delete($key) {
        try {
            // Don't delete system settings
            if (isset($this->settings[$key]) && $this->settings[$key]['is_system'] === 'yes') {
                return false;
            }
            
            $deleted = $this->db->delete('luna_settings', 'key = :key', [':key' => $key]);
            
            if ($deleted) {
                unset($this->settings[$key]);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Delete setting failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reset setting to default
     */
    public function reset($key) {
        try {
            // Find default value
            $defaultValue = null;
            
            foreach ($this->settingsGroups as $group) {
                if (isset($group['settings'][$key])) {
                    $defaultValue = $group['settings'][$key]['default'];
                    break;
                }
            }
            
            if ($defaultValue !== null) {
                return $this->set($key, $defaultValue);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Reset setting failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Export settings
     */
    public function export() {
        try {
            $exportData = [];
            
            foreach ($this->settings as $key => $setting) {
                // Skip system settings and sensitive data
                if ($setting['is_system'] === 'yes' || strpos($key, 'password') !== false) {
                    continue;
                }
                
                $exportData[$key] = [
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                    'type' => $setting['type']
                ];
            }
            
            return json_encode($exportData, JSON_PRETTY_PRINT);
            
        } catch (Exception $e) {
            error_log("Export settings failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Import settings
     */
    public function import($jsonData) {
        try {
            $data = json_decode($jsonData, true);
            
            if (!$data || !is_array($data)) {
                return false;
            }
            
            $success = true;
            
            foreach ($data as $key => $setting) {
                if (!isset($setting['value'])) {
                    continue;
                }
                
                $description = $setting['description'] ?? '';
                $type = $setting['type'] ?? 'string';
                
                if (!$this->set($key, $setting['value'], $description, $type)) {
                    $success = false;
                }
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("Import settings failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Format currency
     */
    public function formatCurrency($amount) {
        $currency = $this->get('currency', 'BDT');
        $symbol = $this->get('currency_symbol', '৳');
        $position = $this->get('currency_position', 'before');
        $decimalSep = $this->get('decimal_separator', '.');
        $thousandSep = $this->get('thousand_separator', ',');
        
        $formatted = number_format($amount, 2, $decimalSep, $thousandSep);
        
        if ($position === 'before') {
            return $symbol . $formatted;
        } else {
            return $formatted . $symbol;
        }
    }
    
    /**
     * Format date
     */
    public function formatDate($date, $format = null) {
        if (!$format) {
            $format = $this->get('date_format', 'Y-m-d');
        }
        
        return date($format, strtotime($date));
    }
    
    /**
     * Format time
     */
    public function formatTime($time, $format = null) {
        if (!$format) {
            $format = $this->get('time_format', 'H:i');
        }
        
        return date($format, strtotime($time));
    }
    
    /**
     * Format datetime
     */
    public function formatDateTime($datetime, $dateFormat = null, $timeFormat = null) {
        if (!$dateFormat) {
            $dateFormat = $this->get('date_format', 'Y-m-d');
        }
        
        if (!$timeFormat) {
            $timeFormat = $this->get('time_format', 'H:i');
        }
        
        return date($dateFormat . ' ' . $timeFormat, strtotime($datetime));
    }
    
    /**
     * Get available payment methods
     */
    public function getPaymentMethods() {
        $methods = $this->get('payment_methods', '["cash"]');
        return is_array($methods) ? $methods : ['cash'];
    }
    
    /**
     * Check if payment method is available
     */
    public function isPaymentMethodAvailable($method) {
        $availableMethods = $this->getPaymentMethods();
        return in_array($method, $availableMethods);
    }
    
    /**
     * Get restaurant info
     */
    public function getRestaurantInfo() {
        return [
            'name' => $this->get('restaurant_name', 'Luna Dine Restaurant'),
            'address' => $this->get('restaurant_address', 'Dhaka, Bangladesh'),
            'phone' => $this->get('restaurant_phone', '+880 1234-567890'),
            'email' => $this->get('site_email', 'info@lunadine.com'),
            'opening_hours' => $this->get('opening_hours', "Monday-Sunday: 10:00 AM - 10:00 PM")
        ];
    }
    
    /**
     * Validate setting value
     */
    public function validate($key, $value, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
                
            case 'number':
                return is_numeric($value);
                
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false;
                
            case 'boolean':
                return in_array($value, [true, false, 'true', 'false', '1', '0', 1, 0]);
                
            case 'json':
                json_decode($value);
                return json_last_error() === JSON_ERROR_NONE;
                
            default:
                return true;
        }
    }
    
    /**
     * Sanitize setting value
     */
    public function sanitize($value, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($value, FILTER_SANITIZE_EMAIL);
                
            case 'url':
                return filter_var($value, FILTER_SANITIZE_URL);
                
            case 'number':
            case 'integer':
                return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT);
                
            case 'boolean':
                return in_array($value, [true, 'true', '1', 1]) ? 'true' : 'false';
                
            default:
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    }
}
?>