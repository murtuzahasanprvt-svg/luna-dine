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
            
            // Get settings
            $settings = $this->getAllSettings();
            
            // Include view
            include LUNA_DINE_CORE . '/views/admin/settings.php';
            
        } catch (Exception $e) {
            $this->handleError('Settings page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Get all settings
     */
    private function getAllSettings() {
        try {
            $settings = [];
            $result = $this->db->select('luna_settings', '*', '1=1', [], 'key ASC');
            
            foreach ($result as $row) {
                $settings[$row['key']] = [
                    'value' => $row['value'],
                    'description' => $row['description'],
                    'type' => $row['type']
                ];
            }
            
            return $settings;
            
        } catch (Exception $e) {
            $this->handleError('Get all settings failed: ' . $e->getMessage());
            return [];
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
            
            // Get all settings
            $allSettings = $this->getAllSettings();
            
            $updated = 0;
            
            foreach ($allSettings as $key => $setting) {
                if (isset($_POST[$key])) {
                    $value = $_POST[$key];
                    
                    // Convert value based on type
                    switch ($setting['type']) {
                        case 'integer':
                            $value = (int) $value;
                            break;
                        case 'float':
                            $value = (float) $value;
                            break;
                        case 'boolean':
                            $value = $value === 'yes' || $value === 'true' || $value === '1' ? 'true' : 'false';
                            break;
                        default:
                            $value = Utilities::sanitize($value);
                    }
                    
                    // Update setting
                    $result = $this->db->update('luna_settings', ['value' => $value], 'key = :key', [':key' => $key]);
                    
                    if ($result) {
                        $updated++;
                    }
                }
            }
            
            // Log activity
            Utilities::logActivity($user['id'], 'settings_updated', "Updated $updated settings");
            
            $_SESSION['settings_success'] = "$updated settings updated successfully";
            Utilities::redirect('/admin/settings');
            
        } catch (Exception $e) {
            $this->handleError('Settings update failed: ' . $e->getMessage());
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
}
?>