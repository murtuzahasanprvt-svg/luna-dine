<?php
/**
 * Luna Dine Admin Controller
 * 
 * Handles admin panel functionality
 */

class AdminController {
    private $db;
    private $auth;
    
    public function __construct($db, $auth) {
        $this->db = $db;
        $this->auth = $auth;
    }
    
    /**
     * Display admin dashboard
     */
    public function index() {
        try {
            // Check authentication
            $this->auth->requireAuth();
            $this->auth->requirePermission('access_admin');
            
            // Get user data
            $user = $this->auth->getUser();
            
            // Get dashboard statistics
            $stats = $this->getDashboardStats();
            
            // Get recent orders
            $recentOrders = $this->getRecentOrders();
            
            // Get low stock items
            $lowStockItems = $this->getLowStockItems();
            
            // Include view
            include LUNA_DINE_CORE . '/views/admin/dashboard.php';
            
        } catch (Exception $e) {
            $this->handleError('Admin dashboard display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Get dashboard statistics
     */
    private function getDashboardStats() {
        try {
            $stats = [];
            
            // Get accessible branches for user
            $branches = $this->getUserBranches();
            $branchIds = array_column($branches, 'id');
            $branchCondition = empty($branchIds) ? '' : 'AND branch_id IN (' . implode(',', $branchIds) . ')';
            
            // Total orders today
            $today = date('Y-m-d');
            $sql = "SELECT COUNT(*) as total FROM luna_orders 
                    WHERE DATE(created_at) = :today AND deleted_at IS NULL $branchCondition";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $result = $stmt->fetch();
            $stats['orders_today'] = $result['total'];
            
            // Total revenue today
            $sql = "SELECT SUM(total) as total FROM luna_orders 
                    WHERE DATE(created_at) = :today AND status != 'cancelled' AND deleted_at IS NULL $branchCondition";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $result = $stmt->fetch();
            $stats['revenue_today'] = $result['total'] ?? 0;
            
            // Total customers
            $sql = "SELECT COUNT(DISTINCT customer_phone) as total FROM luna_orders 
                    WHERE deleted_at IS NULL $branchCondition";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            $stats['total_customers'] = $result['total'];
            
            // Active branches
            if ($this->auth->hasRole('super_admin') || $this->auth->hasRole('owner')) {
                $sql = "SELECT COUNT(*) as total FROM luna_branches WHERE status = 'active' AND deleted_at IS NULL";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $result = $stmt->fetch();
                $stats['active_branches'] = $result['total'];
            } else {
                $stats['active_branches'] = count($branches);
            }
            
            // Pending orders
            $sql = "SELECT COUNT(*) as total FROM luna_orders 
                    WHERE status = 'pending' AND deleted_at IS NULL $branchCondition";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            $stats['pending_orders'] = $result['total'];
            
            // Popular items
            $sql = "SELECT mi.name, COUNT(oi.id) as order_count 
                    FROM luna_order_items oi 
                    JOIN luna_menu_items mi ON oi.menu_item_id = mi.id 
                    WHERE oi.created_at >= datetime('now', '-7 days') $branchCondition
                    GROUP BY mi.id 
                    ORDER BY order_count DESC 
                    LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stats['popular_items'] = $stmt->fetchAll();
            
            return $stats;
            
        } catch (Exception $e) {
            $this->handleError('Get dashboard stats failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user branches
     */
    private function getUserBranches() {
        try {
            $user = $this->auth->getUser();
            
            // Super admin and owner can see all branches
            if ($user['role_name'] === 'super_admin' || $user['role_name'] === 'owner') {
                return $this->db->select('luna_branches', '*', 'status = :status AND deleted_at IS NULL', 
                    [':status' => 'active']);
            }
            
            // Branch manager can see only their branch
            if ($user['role_name'] === 'branch_manager') {
                return $this->db->select('luna_branches', '*', 'id = :id AND status = :status AND deleted_at IS NULL', 
                    [':id' => $user['branch_id'], ':status' => 'active']);
            }
            
            // Other roles need explicit branch assignment
            $sql = "SELECT b.* FROM luna_branches b 
                    JOIN luna_user_branches ub ON b.id = ub.branch_id 
                    WHERE ub.user_id = :user_id AND b.status = :status AND b.deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
            $stmt->bindParam(':status', 'active');
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            $this->handleError('Get user branches failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent orders
     */
    private function getRecentOrders() {
        try {
            $branches = $this->getUserBranches();
            $branchIds = array_column($branches, 'id');
            $branchCondition = empty($branchIds) ? '' : 'AND o.branch_id IN (' . implode(',', $branchIds) . ')';
            
            $sql = "SELECT o.*, b.name as branch_name, u.first_name, u.last_name 
                    FROM luna_orders o 
                    LEFT JOIN luna_branches b ON o.branch_id = b.id 
                    LEFT JOIN luna_users u ON o.user_id = u.id 
                    WHERE o.deleted_at IS NULL $branchCondition 
                    ORDER BY o.created_at DESC 
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            $this->handleError('Get recent orders failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get low stock items (placeholder for inventory system)
     */
    private function getLowStockItems() {
        try {
            // This would typically connect to an inventory system
            // For now, return empty array
            return [];
            
        } catch (Exception $e) {
            $this->handleError('Get low stock items failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Display profile page
     */
    public function profile() {
        try {
            $this->auth->requireAuth();
            
            $user = $this->auth->getUser();
            
            // Include view
            include LUNA_DINE_CORE . '/views/admin/profile.php';
            
        } catch (Exception $e) {
            $this->handleError('Profile page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Update profile
     */
    public function updateProfile() {
        try {
            $this->auth->requireAuth();
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/profile');
            }
            
            $user = $this->auth->getUser();
            $userId = $user['id'];
            
            // Sanitize input
            $firstName = Utilities::sanitize($_POST['first_name'] ?? '');
            $lastName = Utilities::sanitize($_POST['last_name'] ?? '');
            $phone = Utilities::sanitize($_POST['phone'] ?? '');
            $email = Utilities::sanitize($_POST['email'] ?? '');
            
            // Validate input
            $errors = [];
            
            if (empty($firstName)) {
                $errors[] = 'First name is required';
            }
            
            if (empty($lastName)) {
                $errors[] = 'Last name is required';
            }
            
            if (empty($email) || !Utilities::validateEmail($email)) {
                $errors[] = 'Valid email is required';
            }
            
            // Check if email is already used by another user
            $existingUser = $this->db->select('luna_users', 'id', 'email = :email AND id != :id', 
                [':email' => $email, ':id' => $userId]);
            
            if (!empty($existingUser)) {
                $errors[] = 'Email is already in use';
            }
            
            if (!empty($errors)) {
                $_SESSION['profile_errors'] = $errors;
                $_SESSION['profile_data'] = $_POST;
                Utilities::redirect('/admin/profile');
            }
            
            // Update user
            $updateData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'email' => $email
            ];
            
            $updated = $this->db->update('luna_users', $updateData, 'id = :id', [':id' => $userId]);
            
            if ($updated) {
                // Log activity
                Utilities::logActivity($userId, 'profile_updated', 'Profile updated successfully');
                
                $_SESSION['profile_success'] = 'Profile updated successfully';
                
                // Update session data
                $_SESSION['user_email'] = $email;
                
            } else {
                $_SESSION['profile_error'] = 'Failed to update profile';
            }
            
            Utilities::redirect('/admin/profile');
            
        } catch (Exception $e) {
            $this->handleError('Profile update failed: ' . $e->getMessage());
            $_SESSION['profile_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/profile');
        }
    }
    
    /**
     * Change password
     */
    public function changePassword() {
        try {
            $this->auth->requireAuth();
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/profile');
            }
            
            $user = $this->auth->getUser();
            $userId = $user['id'];
            
            // Sanitize input
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate input
            $errors = [];
            
            if (empty($currentPassword)) {
                $errors[] = 'Current password is required';
            }
            
            if (empty($newPassword)) {
                $errors[] = 'New password is required';
            }
            
            if (strlen($newPassword) < 6) {
                $errors[] = 'New password must be at least 6 characters';
            }
            
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'Passwords do not match';
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                $errors[] = 'Current password is incorrect';
            }
            
            if (!empty($errors)) {
                $_SESSION['password_errors'] = $errors;
                Utilities::redirect('/admin/profile');
            }
            
            // Update password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
            $updated = $this->db->update('luna_users', ['password' => $newPasswordHash], 'id = :id', [':id' => $userId]);
            
            if ($updated) {
                // Log activity
                Utilities::logActivity($userId, 'password_changed', 'Password changed successfully');
                
                $_SESSION['password_success'] = 'Password changed successfully';
                
                // Clear all sessions except current
                $this->clearUserSessions($userId);
                
            } else {
                $_SESSION['password_error'] = 'Failed to change password';
            }
            
            Utilities::redirect('/admin/profile');
            
        } catch (Exception $e) {
            $this->handleError('Password change failed: ' . $e->getMessage());
            $_SESSION['password_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/profile');
        }
    }
    
    /**
     * Clear user sessions (except current)
     */
    private function clearUserSessions($userId) {
        try {
            // Delete all remember me tokens except current session
            $this->db->delete('luna_user_tokens', 'user_id = :user_id AND type = :type', 
                [':user_id' => $userId, ':type' => 'remember_me']);
                
        } catch (Exception $e) {
            $this->handleError('Clear user sessions failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Display settings page
     */
    public function settings() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_settings');
            
            // Initialize settings system
            require_once LUNA_DINE_CORE . '/helpers/Settings.php';
            $settings = new Settings($this->db);
            
            // Get active tab
            $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
            
            // Get settings groups
            $settingsGroups = $settings->getGroups();
            
            // Include view
            include LUNA_DINE_CORE . '/views/admin/settings.php';
            
        } catch (Exception $e) {
            $this->handleError('Settings page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Update settings
     */
    public function updateSettings() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_settings');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/settings');
            }
            
            $user = $this->auth->getUser();
            
            // Initialize settings system
            require_once LUNA_DINE_CORE . '/helpers/Settings.php';
            $settings = new Settings($this->db);
            
            // Get settings group
            $group = isset($_POST['settings_group']) ? $_POST['settings_group'] : 'general';
            $settingsGroup = $settings->getGroup($group);
            
            if (!$settingsGroup) {
                $_SESSION['settings_error'] = 'Invalid settings group';
                Utilities::redirect('/admin/settings');
            }
            
            // Process settings
            $updates = [];
            $errors = [];
            
            foreach ($settingsGroup['settings'] as $key => $setting) {
                if (isset($_POST[$key])) {
                    $value = $_POST[$key];
                    
                    // Sanitize value based on type
                    $sanitizedValue = $settings->sanitize($value, $setting['type']);
                    
                    // Validate value
                    if (!$settings->validate($sanitizedValue, $setting['type'])) {
                        $errors[] = "Invalid value for {$setting['label']}";
                        continue;
                    }
                    
                    // Handle file uploads
                    if ($setting['type'] === 'file' && isset($_FILES[$key])) {
                        $file = $_FILES[$key];
                        if ($file['error'] === UPLOAD_ERR_OK) {
                            $uploadPath = LUNA_DINE_UPLOADS . '/' . basename($file['name']);
                            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                                $sanitizedValue = '/uploads/' . basename($file['name']);
                            } else {
                                $errors[] = "Failed to upload file for {$setting['label']}";
                                continue;
                            }
                        }
                    }
                    
                    $updates[$key] = $sanitizedValue;
                }
            }
            
            if (!empty($errors)) {
                $_SESSION['settings_errors'] = $errors;
                $_SESSION['settings_data'] = $_POST;
                Utilities::redirect('/admin/settings?tab=' . $group);
            }
            
            // Update settings
            if ($settings->updateMultiple($updates)) {
                $_SESSION['settings_success'] = 'Settings updated successfully';
                Utilities::logActivity($user['id'], 'settings_updated', "Settings updated for group: $group");
            } else {
                $_SESSION['settings_error'] = 'Failed to update settings';
            }
            
            Utilities::redirect('/admin/settings?tab=' . $group);
            
        } catch (Exception $e) {
            $this->handleError('Update settings failed: ' . $e->getMessage());
            $_SESSION['settings_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/settings');
        }
    }
    
    /**
     * Export settings
     */
    public function exportSettings() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_settings');
            
            // Initialize settings system
            require_once LUNA_DINE_CORE . '/helpers/Settings.php';
            $settings = new Settings($this->db);
            
            $exportData = $settings->export();
            
            if ($exportData) {
                // Set headers for download
                header('Content-Description: File Transfer');
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="luna_dine_settings_' . date('Y-m-d') . '.json"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . strlen($exportData));
                
                echo $exportData;
                exit;
            } else {
                $_SESSION['settings_error'] = 'Failed to export settings';
                Utilities::redirect('/admin/settings');
            }
            
        } catch (Exception $e) {
            $this->handleError('Export settings failed: ' . $e->getMessage());
            $_SESSION['settings_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/settings');
        }
    }
    
    /**
     * Import settings
     */
    public function importSettings() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_settings');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/settings');
            }
            
            if (!isset($_FILES['settings_file']) || $_FILES['settings_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['settings_error'] = 'Please select a valid settings file';
                Utilities::redirect('/admin/settings');
            }
            
            $file = $_FILES['settings_file'];
            $jsonData = file_get_contents($file['tmp_name']);
            
            // Initialize settings system
            require_once LUNA_DINE_CORE . '/helpers/Settings.php';
            $settings = new Settings($this->db);
            
            if ($settings->import($jsonData)) {
                $_SESSION['settings_success'] = 'Settings imported successfully';
                Utilities::logActivity($_SESSION['user_id'], 'settings_imported', 'Settings imported from file');
            } else {
                $_SESSION['settings_error'] = 'Failed to import settings. Please check the file format.';
            }
            
            Utilities::redirect('/admin/settings');
            
        } catch (Exception $e) {
            $this->handleError('Import settings failed: ' . $e->getMessage());
            $_SESSION['settings_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/settings');
        }
    }
    
    /**
     * Reset settings
     */
    public function resetSettings() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_settings');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/settings');
            }
            
            $settingKey = Utilities::sanitize($_POST['setting_key'] ?? '');
            $group = Utilities::sanitize($_POST['settings_group'] ?? 'general');
            
            if (empty($settingKey)) {
                $_SESSION['settings_error'] = 'Setting key is required';
                Utilities::redirect('/admin/settings?tab=' . $group);
            }
            
            // Initialize settings system
            require_once LUNA_DINE_CORE . '/helpers/Settings.php';
            $settings = new Settings($this->db);
            
            if ($settings->reset($settingKey)) {
                $_SESSION['settings_success'] = 'Setting reset to default successfully';
                Utilities::logActivity($_SESSION['user_id'], 'setting_reset', "Setting reset: $settingKey");
            } else {
                $_SESSION['settings_error'] = 'Failed to reset setting';
            }
            
            Utilities::redirect('/admin/settings?tab=' . $group);
            
        } catch (Exception $e) {
            $this->handleError('Reset settings failed: ' . $e->getMessage());
            $_SESSION['settings_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/settings');
        }
    }
    
    /**
     * Display activity logs
     */
    public function activityLogs() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('view_reports');
            
            $user = $this->auth->getUser();
            
            // Get pagination parameters
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $limit = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $limit;
            
            // Get user branches for filtering
            $branches = $this->getUserBranches();
            $branchIds = array_column($branches, 'id');
            
            // Build query conditions
            $conditions = [];
            $params = [];
            
            // Filter by user branches if not super admin
            if (!($user['role_name'] === 'super_admin' || $user['role_name'] === 'owner')) {
                if (!empty($branchIds)) {
                    $conditions[] = 'al.user_id IN (SELECT user_id FROM luna_user_branches WHERE branch_id IN (' . implode(',', $branchIds) . '))';
                }
            }
            
            // Add search filters
            if (isset($_GET['action']) && !empty($_GET['action'])) {
                $conditions[] = 'al.action LIKE :action';
                $params[':action'] = '%' . $_GET['action'] . '%';
            }
            
            if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
                $conditions[] = 'al.user_id = :user_id';
                $params[':user_id'] = (int) $_GET['user_id'];
            }
            
            if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
                $conditions[] = 'DATE(al.created_at) >= :date_from';
                $params[':date_from'] = $_GET['date_from'];
            }
            
            if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
                $conditions[] = 'DATE(al.created_at) <= :date_to';
                $params[':date_to'] = $_GET['date_to'];
            }
            
            // Build WHERE clause
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get total count
            $sql = "SELECT COUNT(*) as total FROM luna_activity_logs al $whereClause";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            $total = $result['total'];
            
            // Get logs
            $sql = "SELECT al.*, u.first_name, u.last_name, u.email 
                    FROM luna_activity_logs al 
                    LEFT JOIN luna_users u ON al.user_id = u.id 
                    $whereClause 
                    ORDER BY al.created_at DESC 
                    LIMIT $limit OFFSET $offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll();
            
            // Get pagination
            $pagination = Utilities::createPagination($total, $limit, $page);
            
            // Include view
            include LUNA_DINE_CORE . '/views/admin/activity_logs.php';
            
        } catch (Exception $e) {
            $this->handleError('Activity logs display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Handle errors
     */
    private function handleError($message) {
        if (DEBUG_MODE) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Admin Controller Error:</strong> $message<br>";
            echo "</div>";
        }
        
        Utilities::logError('Admin Controller: ' . $message);
    }
    
    /**
     * Display addons management page
     */
    public function addons() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_addons');
            
            // Initialize addons system
            require_once LUNA_DINE_CORE . '/helpers/Addons.php';
            $addons = new Addons($this->db);
            
            // Get all addons
            $allAddons = $addons->getAllAddons();
            $enabledAddons = $addons->getEnabledAddons();
            $disabledAddons = $addons->getDisabledAddons();
            
            // Include view
            include LUNA_DINE_CORE . '/views/admin/addons.php';
            
        } catch (Exception $e) {
            $this->handleError('Addons management page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Enable addon
     */
    public function enableAddon() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_addons');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/addons');
            }
            
            $addonName = Utilities::sanitize($_POST['addon_name'] ?? '');
            
            if (empty($addonName)) {
                $_SESSION['addon_error'] = 'Addon name is required';
                Utilities::redirect('/admin/addons');
            }
            
            // Initialize addons system
            require_once LUNA_DINE_CORE . '/helpers/Addons.php';
            $addons = new Addons($this->db);
            
            if ($addons->enableAddon($addonName)) {
                $_SESSION['addon_success'] = 'Addon enabled successfully';
                Utilities::logActivity($_SESSION['user_id'], 'addon_enabled', "Addon '$addonName' enabled");
            } else {
                $_SESSION['addon_error'] = 'Failed to enable addon. Please check dependencies.';
            }
            
            Utilities::redirect('/admin/addons');
            
        } catch (Exception $e) {
            $this->handleError('Enable addon failed: ' . $e->getMessage());
            $_SESSION['addon_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/addons');
        }
    }
    
    /**
     * Disable addon
     */
    public function disableAddon() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_addons');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/addons');
            }
            
            $addonName = Utilities::sanitize($_POST['addon_name'] ?? '');
            
            if (empty($addonName)) {
                $_SESSION['addon_error'] = 'Addon name is required';
                Utilities::redirect('/admin/addons');
            }
            
            // Initialize addons system
            require_once LUNA_DINE_CORE . '/helpers/Addons.php';
            $addons = new Addons($this->db);
            
            if ($addons->disableAddon($addonName)) {
                $_SESSION['addon_success'] = 'Addon disabled successfully';
                Utilities::logActivity($_SESSION['user_id'], 'addon_disabled', "Addon '$addonName' disabled");
            } else {
                $_SESSION['addon_error'] = 'Failed to disable addon. Other addons may depend on it.';
            }
            
            Utilities::redirect('/admin/addons');
            
        } catch (Exception $e) {
            $this->handleError('Disable addon failed: ' . $e->getMessage());
            $_SESSION['addon_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/addons');
        }
    }
    
    /**
     * Install addon
     */
    public function installAddon() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_addons');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/addons');
            }
            
            $addonName = Utilities::sanitize($_POST['addon_name'] ?? '');
            
            if (empty($addonName)) {
                $_SESSION['addon_error'] = 'Addon name is required';
                Utilities::redirect('/admin/addons');
            }
            
            // Initialize addons system
            require_once LUNA_DINE_CORE . '/helpers/Addons.php';
            $addons = new Addons($this->db);
            
            if ($addons->installAddon($addonName)) {
                $_SESSION['addon_success'] = 'Addon installed successfully';
                Utilities::logActivity($_SESSION['user_id'], 'addon_installed', "Addon '$addonName' installed");
            } else {
                $_SESSION['addon_error'] = 'Failed to install addon';
            }
            
            Utilities::redirect('/admin/addons');
            
        } catch (Exception $e) {
            $this->handleError('Install addon failed: ' . $e->getMessage());
            $_SESSION['addon_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/addons');
        }
    }
    
    /**
     * Uninstall addon
     */
    public function uninstallAddon() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_addons');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/addons');
            }
            
            $addonName = Utilities::sanitize($_POST['addon_name'] ?? '');
            
            if (empty($addonName)) {
                $_SESSION['addon_error'] = 'Addon name is required';
                Utilities::redirect('/admin/addons');
            }
            
            // Initialize addons system
            require_once LUNA_DINE_CORE . '/helpers/Addons.php';
            $addons = new Addons($this->db);
            
            if ($addons->uninstallAddon($addonName)) {
                $_SESSION['addon_success'] = 'Addon uninstalled successfully';
                Utilities::logActivity($_SESSION['user_id'], 'addon_uninstalled', "Addon '$addonName' uninstalled");
            } else {
                $_SESSION['addon_error'] = 'Failed to uninstall addon';
            }
            
            Utilities::redirect('/admin/addons');
            
        } catch (Exception $e) {
            $this->handleError('Uninstall addon failed: ' . $e->getMessage());
            $_SESSION['addon_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/addons');
        }
    }
    
    /**
     * Display themes management page
     */
    public function themes() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_themes');
            
            // Initialize themes system
            require_once LUNA_DINE_CORE . '/helpers/Themes.php';
            $themes = new Themes($this->db);
            
            // Get all themes
            $allThemes = $themes->getAllThemes();
            $activeTheme = $themes->getActiveTheme();
            
            // Include view
            include LUNA_DINE_CORE . '/views/admin/themes.php';
            
        } catch (Exception $e) {
            $this->handleError('Themes management page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Set active theme
     */
    public function setActiveTheme() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_themes');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/themes');
            }
            
            $themeName = Utilities::sanitize($_POST['theme_name'] ?? '');
            
            if (empty($themeName)) {
                $_SESSION['theme_error'] = 'Theme name is required';
                Utilities::redirect('/admin/themes');
            }
            
            // Initialize themes system
            require_once LUNA_DINE_CORE . '/helpers/Themes.php';
            $themes = new Themes($this->db);
            
            if ($themes->setActiveTheme($themeName)) {
                $_SESSION['theme_success'] = 'Theme activated successfully';
                Utilities::logActivity($_SESSION['user_id'], 'theme_activated', "Theme '$themeName' activated");
            } else {
                $_SESSION['theme_error'] = 'Failed to activate theme';
            }
            
            Utilities::redirect('/admin/themes');
            
        } catch (Exception $e) {
            $this->handleError('Set active theme failed: ' . $e->getMessage());
            $_SESSION['theme_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/themes');
        }
    }
    
    /**
     * Update theme settings
     */
    public function updateThemeSettings() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_themes');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/themes');
            }
            
            // Initialize themes system
            require_once LUNA_DINE_CORE . '/helpers/Themes.php';
            $themes = new Themes($this->db);
            
            // Get theme settings from POST
            $settings = [];
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'theme_') === 0) {
                    $settingName = str_replace('theme_', '', $key);
                    $settings[$settingName] = Utilities::sanitize($value);
                }
            }
            
            if ($themes->updateSettings($settings)) {
                $_SESSION['theme_success'] = 'Theme settings updated successfully';
                Utilities::logActivity($_SESSION['user_id'], 'theme_settings_updated', 'Theme settings updated');
            } else {
                $_SESSION['theme_error'] = 'Failed to update theme settings';
            }
            
            Utilities::redirect('/admin/themes');
            
        } catch (Exception $e) {
            $this->handleError('Update theme settings failed: ' . $e->getMessage());
            $_SESSION['theme_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/themes');
        }
    }
    
    /**
     * Install theme
     */
    public function installTheme() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_themes');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/themes');
            }
            
            $themeName = Utilities::sanitize($_POST['theme_name'] ?? '');
            
            if (empty($themeName)) {
                $_SESSION['theme_error'] = 'Theme name is required';
                Utilities::redirect('/admin/themes');
            }
            
            // Initialize themes system
            require_once LUNA_DINE_CORE . '/helpers/Themes.php';
            $themes = new Themes($this->db);
            
            if ($themes->installTheme($themeName)) {
                $_SESSION['theme_success'] = 'Theme installed successfully';
                Utilities::logActivity($_SESSION['user_id'], 'theme_installed', "Theme '$themeName' installed");
            } else {
                $_SESSION['theme_error'] = 'Failed to install theme';
            }
            
            Utilities::redirect('/admin/themes');
            
        } catch (Exception $e) {
            $this->handleError('Install theme failed: ' . $e->getMessage());
            $_SESSION['theme_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/themes');
        }
    }
    
    /**
     * Uninstall theme
     */
    public function uninstallTheme() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_themes');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/themes');
            }
            
            $themeName = Utilities::sanitize($_POST['theme_name'] ?? '');
            
            if (empty($themeName)) {
                $_SESSION['theme_error'] = 'Theme name is required';
                Utilities::redirect('/admin/themes');
            }
            
            // Initialize themes system
            require_once LUNA_DINE_CORE . '/helpers/Themes.php';
            $themes = new Themes($this->db);
            
            if ($themes->uninstallTheme($themeName)) {
                $_SESSION['theme_success'] = 'Theme uninstalled successfully';
                Utilities::logActivity($_SESSION['user_id'], 'theme_uninstalled', "Theme '$themeName' uninstalled");
            } else {
                $_SESSION['theme_error'] = 'Failed to uninstall theme. It may be currently active.';
            }
            
            Utilities::redirect('/admin/themes');
            
        } catch (Exception $e) {
            $this->handleError('Uninstall theme failed: ' . $e->getMessage());
            $_SESSION['theme_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/themes');
        }
    }
    
    /**
     * Display backup management page
     */
    public function backups() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_settings');
            
            // Initialize backup system
            require_once LUNA_DINE_CORE . '/helpers/Backup.php';
            $backup = new Backup($this->db);
            
            // Get backup list
            $backups = $backup->getBackupList();
            
            // Include view
            include LUNA_DINE_CORE . '/views/admin/backups.php';
            
        } catch (Exception $e) {
            $this->handleError('Backup management page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Create database backup
     */
    public function createDatabaseBackup() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_settings');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/backups');
            }
            
            $description = Utilities::sanitize($_POST['description'] ?? '');
            
            // Initialize backup system
            require_once LUNA_DINE_CORE . '/helpers/Backup.php';
            $backup = new Backup($this->db);
            
            $result = $backup->createDatabaseBackup($description);
            
            if ($result['success']) {
                $_SESSION['backup_success'] = 'Database backup created successfully';
                Utilities::logActivity($_SESSION['user_id'], 'database_backup_created', 'Database backup created: ' . basename($result['file']));
            } else {
                $_SESSION['backup_error'] = 'Failed to create database backup: ' . $result['error'];
            }
            
            Utilities::redirect('/admin/backups');
            
        } catch (Exception $e) {
            $this->handleError('Create database backup failed: ' . $e->getMessage());
            $_SESSION['backup_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/backups');
        }
    }
    
    /**
     * Create files backup
     */
    public function createFilesBackup() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_settings');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/backups');
            }
            
            $description = Utilities::sanitize($_POST['description'] ?? '');
            
            // Initialize backup system
            require_once LUNA_DINE_CORE . '/helpers/Backup.php';
            $backup = new Backup($this->db);
            
            $result = $backup->createFilesBackup($description);
            
            if ($result['success']) {
                $_SESSION['backup_success'] = 'Files backup created successfully';
                Utilities::logActivity($_SESSION['user_id'], 'files_backup_created', 'Files backup created: ' . basename($result['file']));
            } else {
                $_SESSION['backup_error'] = 'Failed to create files backup: ' . $result['error'];
            }
            
            Utilities::redirect('/admin/backups');
            
        } catch (Exception $e) {
            $this->handleError('Create files backup failed: ' . $e->getMessage());
            $_SESSION['backup_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/backups');
        }
    }
    
    /**
     * Create complete backup
     */
    public function createCompleteBackup() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_settings');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/backups');
            }
            
            $description = Utilities::sanitize($_POST['description'] ?? '');
            
            // Initialize backup system
            require_once LUNA_DINE_CORE . '/helpers/Backup.php';
            $backup = new Backup($this->db);
            
            $result = $backup->createCompleteBackup($description);
            
            if ($result['success']) {
                $_SESSION['backup_success'] = 'Complete backup created successfully';
                Utilities::logActivity($_SESSION['user_id'], 'complete_backup_created', 'Complete backup created: ' . basename($result['file']));
            } else {
                $_SESSION['backup_error'] = 'Failed to create complete backup: ' . $result['error'];
            }
            
            Utilities::redirect('/admin/backups');
            
        } catch (Exception $e) {
            $this->handleError('Create complete backup failed: ' . $e->getMessage());
            $_SESSION['backup_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/backups');
        }
    }
    
    /**
     * Restore backup
     */
    public function restoreBackup() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_settings');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/backups');
            }
            
            $backupFile = Utilities::sanitize($_POST['backup_file'] ?? '');
            $backupType = Utilities::sanitize($_POST['backup_type'] ?? '');
            
            if (empty($backupFile) || empty($backupType)) {
                $_SESSION['backup_error'] = 'Backup file and type are required';
                Utilities::redirect('/admin/backups');
            }
            
            // Security check - ensure file is in backup directory
            $backupPath = BACKUP_PATH;
            if (strpos($backupFile, $backupPath) !== 0) {
                $_SESSION['backup_error'] = 'Invalid backup file path';
                Utilities::redirect('/admin/backups');
            }
            
            // Initialize backup system
            require_once LUNA_DINE_CORE . '/helpers/Backup.php';
            $backup = new Backup($this->db);
            
            $result = $backup->restoreBackup($backupFile, $backupType);
            
            if ($result['success']) {
                $_SESSION['backup_success'] = 'Backup restored successfully';
                Utilities::logActivity($_SESSION['user_id'], 'backup_restored', "Backup restored: $backupFile (Type: $backupType)");
            } else {
                $_SESSION['backup_error'] = 'Failed to restore backup: ' . $result['error'];
            }
            
            Utilities::redirect('/admin/backups');
            
        } catch (Exception $e) {
            $this->handleError('Restore backup failed: ' . $e->getMessage());
            $_SESSION['backup_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/backups');
        }
    }
    
    /**
     * Delete backup
     */
    public function deleteBackup() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_settings');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/admin/backups');
            }
            
            $backupFile = Utilities::sanitize($_POST['backup_file'] ?? '');
            
            if (empty($backupFile)) {
                $_SESSION['backup_error'] = 'Backup file is required';
                Utilities::redirect('/admin/backups');
            }
            
            // Security check - ensure file is in backup directory
            $backupPath = BACKUP_PATH;
            if (strpos($backupFile, $backupPath) !== 0) {
                $_SESSION['backup_error'] = 'Invalid backup file path';
                Utilities::redirect('/admin/backups');
            }
            
            // Initialize backup system
            require_once LUNA_DINE_CORE . '/helpers/Backup.php';
            $backup = new Backup($this->db);
            
            $result = $backup->deleteBackup($backupFile);
            
            if ($result['success']) {
                $_SESSION['backup_success'] = 'Backup deleted successfully';
                Utilities::logActivity($_SESSION['user_id'], 'backup_deleted', "Backup deleted: $backupFile");
            } else {
                $_SESSION['backup_error'] = 'Failed to delete backup: ' . $result['error'];
            }
            
            Utilities::redirect('/admin/backups');
            
        } catch (Exception $e) {
            $this->handleError('Delete backup failed: ' . $e->getMessage());
            $_SESSION['backup_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/backups');
        }
    }
    
    /**
     * Download backup
     */
    public function downloadBackup() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_settings');
            
            $backupFile = Utilities::sanitize($_GET['file'] ?? '');
            
            if (empty($backupFile)) {
                $_SESSION['backup_error'] = 'Backup file is required';
                Utilities::redirect('/admin/backups');
            }
            
            // Security check - ensure file is in backup directory
            $backupPath = BACKUP_PATH;
            if (strpos($backupFile, $backupPath) !== 0 || !file_exists($backupFile)) {
                $_SESSION['backup_error'] = 'Invalid backup file';
                Utilities::redirect('/admin/backups');
            }
            
            // Set headers for download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($backupFile) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($backupFile));
            
            // Output file
            readfile($backupFile);
            exit;
            
        } catch (Exception $e) {
            $this->handleError('Download backup failed: ' . $e->getMessage());
            $_SESSION['backup_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/admin/backups');
        }
    }
}
?>