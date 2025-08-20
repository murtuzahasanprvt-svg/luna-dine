<?php
/**
 * Luna Dine Installation Script
 * 
 * This script initializes the Luna Dine system by:
 * 1. Creating necessary directories
 * 2. Setting up the database
 * 3. Creating default admin user
 */

// Prevent direct access
if (!defined('LUNA_DINE_ROOT')) {
    define('LUNA_DINE_ROOT', __DIR__ . '/');
    require_once LUNA_DINE_ROOT . '/index.php';
}

class Installer {
    private $db;
    private $errors = [];
    private $success = [];
    
    public function __construct() {
        // Load configuration
        require_once LUNA_DINE_CORE . '/config/config.php';
        
        // Initialize database
        require_once LUNA_DINE_CORE . '/helpers/Database.php';
        $this->db = new Database();
    }
    
    /**
     * Run installation
     */
    public function install() {
        try {
            // Step 1: Create directories
            $this->createDirectories();
            
            // Step 2: Initialize database
            $this->initializeDatabase();
            
            // Step 3: Create default admin user
            $this->createDefaultAdmin();
            
            // Step 4: Create default branch
            $this->createDefaultBranch();
            
            return [
                'success' => true,
                'message' => 'Installation completed successfully!',
                'details' => $this->success
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
                'errors' => $this->errors
            ];
        }
    }
    
    /**
     * Create necessary directories
     */
    private function createDirectories() {
        $directories = [
            LUNA_DINE_ROOT . '/logs',
            LUNA_DINE_ROOT . '/backups',
            LUNA_DINE_ROOT . '/uploads/qrcodes',
            LUNA_DINE_ROOT . '/uploads/avatars',
            LUNA_DINE_ROOT . '/uploads/menu_items',
            LUNA_DINE_ROOT . '/uploads/branches',
            LUNA_DINE_ROOT . '/core/lang',
            LUNA_DINE_ROOT . '/themes/default',
            LUNA_DINE_ROOT . '/addons'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if (mkdir($dir, 0755, true)) {
                    $this->success[] = "Created directory: $dir";
                } else {
                    $this->errors[] = "Failed to create directory: $dir";
                }
            }
        }
        
        // Create .htaccess files for security
        $htaccessFiles = [
            LUNA_DINE_ROOT . '/uploads/.htaccess' => 'Deny from all',
            LUNA_DINE_ROOT . '/database/.htaccess' => 'Deny from all',
            LUNA_DINE_ROOT . '/logs/.htaccess' => 'Deny from all',
            LUNA_DINE_ROOT . '/backups/.htaccess' => 'Deny from all'
        ];
        
        foreach ($htaccessFiles as $file => $content) {
            if (!file_exists($file)) {
                if (file_put_contents($file, $content)) {
                    $this->success[] = "Created .htaccess file: $file";
                } else {
                    $this->errors[] = "Failed to create .htaccess file: $file";
                }
            }
        }
    }
    
    /**
     * Initialize database
     */
    private function initializeDatabase() {
        try {
            // Check if database is already initialized
            if ($this->db->tableExists('luna_users')) {
                $this->success[] = "Database already initialized";
                return;
            }
            
            // Initialize tables from schema
            $this->db->initializeTables();
            $this->success[] = "Database tables created successfully";
            
        } catch (Exception $e) {
            $this->errors[] = "Database initialization failed: " . $e->getMessage();
            throw $e;
        }
    }
    
    /**
     * Create default admin user
     */
    private function createDefaultAdmin() {
        try {
            // Check if admin user already exists
            $existingAdmin = $this->db->select('luna_users', '*', 'email = :email', [':email' => ADMIN_EMAIL]);
            
            if (!empty($existingAdmin)) {
                $this->success[] = "Default admin user already exists";
                return;
            }
            
            // Get super admin role ID
            $role = $this->db->select('luna_roles', '*', 'name = :name', [':name' => 'super_admin']);
            
            if (empty($role)) {
                $this->errors[] = "Super admin role not found";
                return;
            }
            
            $roleId = $role[0]['id'];
            
            // Create admin user
            $adminData = [
                'role_id' => $roleId,
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => ADMIN_EMAIL,
                'phone' => '+880 1234-567890',
                'password' => password_hash('admin123', PASSWORD_DEFAULT, ['cost' => HASH_COST]),
                'status' => 'active'
            ];
            
            $adminId = $this->db->insert('luna_users', $adminData);
            
            if ($adminId) {
                $this->success[] = "Default admin user created successfully";
                $this->success[] = "Email: " . ADMIN_EMAIL;
                $this->success[] = "Password: admin123";
                $this->success[] = "Please change the password after first login!";
            } else {
                $this->errors[] = "Failed to create default admin user";
            }
            
        } catch (Exception $e) {
            $this->errors[] = "Create admin user failed: " . $e->getMessage();
            throw $e;
        }
    }
    
    /**
     * Create default branch
     */
    private function createDefaultBranch() {
        try {
            // Check if branch already exists
            $existingBranch = $this->db->select('luna_branches', '*', 'code = :code', [':code' => 'MAIN']);
            
            if (!empty($existingBranch)) {
                $this->success[] = "Default branch already exists";
                return;
            }
            
            // Create default branch
            $branchData = [
                'name' => 'Main Branch',
                'code' => 'MAIN',
                'address' => '123 Main Street, Dhaka, Bangladesh',
                'phone' => '+880 1234-567890',
                'email' => 'main@lunadine.com',
                'opening_time' => '10:00',
                'closing_time' => '23:00',
                'status' => 'active',
                'tax_rate' => 0.00,
                'delivery_fee' => 0.00,
                'min_order_amount' => 0.00,
                'max_delivery_distance' => 10.00,
                'description' => 'Main branch of Luna Dine restaurant'
            ];
            
            $branchId = $this->db->insert('luna_branches', $branchData);
            
            if ($branchId) {
                $this->success[] = "Default branch created successfully";
                
                // Create default category
                $categoryData = [
                    'branch_id' => $branchId,
                    'name' => 'Main Course',
                    'description' => 'Main course items',
                    'sort_order' => 1,
                    'status' => 'active'
                ];
                
                $categoryId = $this->db->insert('luna_categories', $categoryData);
                
                if ($categoryId) {
                    $this->success[] = "Default category created successfully";
                }
                
                // Create default tables
                for ($i = 1; $i <= 10; $i++) {
                    $tableNumber = TABLE_PREFIX . '001' . $i;
                    $qrCode = md5($tableNumber . time() . rand());
                    
                    $tableData = [
                        'branch_id' => $branchId,
                        'number' => $tableNumber,
                        'qr_code' => $qrCode,
                        'capacity' => 4,
                        'status' => 'available',
                        'location' => 'Ground Floor'
                    ];
                    
                    $this->db->insert('luna_tables', $tableData);
                }
                
                $this->success[] = "Default tables created successfully";
                
            } else {
                $this->errors[] = "Failed to create default branch";
            }
            
        } catch (Exception $e) {
            $this->errors[] = "Create default branch failed: " . $e->getMessage();
            throw $e;
        }
    }
    
    /**
     * Check if system is already installed
     */
    public function isInstalled() {
        return $this->db->tableExists('luna_users') && 
               $this->db->tableExists('luna_settings');
    }
}

// Handle installation request
if (isset($_GET['install']) && $_GET['install'] === 'true') {
    $installer = new Installer();
    
    if ($installer->isInstalled()) {
        echo "<h1>Already Installed</h1>";
        echo "<p>Luna Dine is already installed. Please delete or rename the install.php file.</p>";
        exit;
    }
    
    $result = $installer->install();
    
    if ($result['success']) {
        echo "<h1>Installation Successful!</h1>";
        echo "<p>" . $result['message'] . "</p>";
        echo "<h2>Details:</h2>";
        echo "<ul>";
        foreach ($result['details'] as $detail) {
            echo "<li>$detail</li>";
        }
        echo "</ul>";
        echo "<p><a href='/'>Go to Homepage</a></p>";
        echo "<p><a href='/admin'>Go to Admin Panel</a></p>";
    } else {
        echo "<h1>Installation Failed!</h1>";
        echo "<p>" . $result['message'] . "</p>";
        if (!empty($result['errors'])) {
            echo "<h2>Errors:</h2>";
            echo "<ul>";
            foreach ($result['errors'] as $error) {
                echo "<li>$error</li>";
            }
            echo "</ul>";
        }
    }
    exit;
}

// Show installation form
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luna Dine - Installation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .requirements {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .requirements h3 {
            margin-top: 0;
            color: #2e7d32;
        }
        .requirements ul {
            margin-bottom: 0;
        }
        .install-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: #4CAF50;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        .install-btn:hover {
            background: #45a049;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌙 Luna Dine Installation</h1>
        
        <div class="warning">
            <strong>Important:</strong> This installation will create the necessary database tables and default data for Luna Dine. Make sure you have proper file permissions.
        </div>
        
        <div class="requirements">
            <h3>System Requirements</h3>
            <ul>
                <li>PHP 7.0 or higher</li>
                <li>SQLite3 extension enabled</li>
                <li>Write permissions in project directory</li>
                <li>Web server (Apache, Nginx, etc.)</li>
            </ul>
        </div>
        
        <p>Click the button below to start the installation process:</p>
        
        <a href="?install=true" class="install-btn">Install Luna Dine</a>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666;">
            <p>Luna Dine v<?php echo LUNA_DINE_VERSION; ?> - Advanced QR Menu System</p>
        </div>
    </div>
</body>
</html>