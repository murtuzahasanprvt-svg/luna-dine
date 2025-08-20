<?php
/**
 * Luna Dine Authentication Helper
 * 
 * User authentication and role-based access control
 */

class Auth {
    private $db;
    private $user = null;
    private $isLoggedIn = false;
    
    /**
     * Constructor - Initialize authentication
     */
    public function __construct($db) {
        $this->db = $db;
        $this->checkSession();
    }
    
    /**
     * Check user session
     */
    private function checkSession() {
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $user = $this->getUserById($userId);
            
            if ($user && $user['status'] === 'active') {
                $this->user = $user;
                $this->isLoggedIn = true;
                
                // Update last activity
                $this->updateLastActivity($userId);
            } else {
                $this->logout();
            }
        }
    }
    
    /**
     * Get user by ID
     */
    private function getUserById($userId) {
        try {
            $sql = "SELECT u.*, r.name as role_name, r.permissions as role_permissions 
                    FROM " . DB_PREFIX . "users u 
                    LEFT JOIN " . DB_PREFIX . "roles r ON u.role_id = r.id 
                    WHERE u.id = :id AND u.deleted_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (Exception $e) {
            $this->handleError('Get user failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user last activity
     */
    private function updateLastActivity($userId) {
        try {
            $sql = "UPDATE " . DB_PREFIX . "users 
                    SET last_activity = CURRENT_TIMESTAMP 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            $this->handleError('Update last activity failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Login user
     */
    public function login($email, $password, $remember = false) {
        try {
            // Check login attempts
            if (!$this->checkLoginAttempts($email)) {
                return ['success' => false, 'message' => 'Too many login attempts. Please try again later.'];
            }
            
            // Get user by email
            $sql = "SELECT u.*, r.name as role_name, r.permissions as role_permissions 
                    FROM " . DB_PREFIX . "users u 
                    LEFT JOIN " . DB_PREFIX . "roles r ON u.role_id = r.id 
                    WHERE u.email = :email AND u.deleted_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->recordLoginAttempt($email, false);
                return ['success' => false, 'message' => 'Invalid email or password.'];
            }
            
            // Check user status
            if ($user['status'] !== 'active') {
                return ['success' => false, 'message' => 'Account is not active.'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                $this->recordLoginAttempt($email, false);
                return ['success' => false, 'message' => 'Invalid email or password.'];
            }
            
            // Check if password needs rehash
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT, ['cost' => HASH_COST])) {
                $newPasswordHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
                $this->updatePasswordHash($user['id'], $newPasswordHash);
            }
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role_name'];
            $_SESSION['login_time'] = time();
            
            // Update user info
            $this->user = $user;
            $this->isLoggedIn = true;
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Clear login attempts
            $this->clearLoginAttempts($email);
            
            // Set remember me cookie
            if ($remember) {
                $this->setRememberMeCookie($user['id']);
            }
            
            return ['success' => true, 'message' => 'Login successful.', 'user' => $user];
            
        } catch (Exception $e) {
            $this->handleError('Login failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Check login attempts
     */
    private function checkLoginAttempts($email) {
        try {
            $sql = "SELECT COUNT(*) as attempt_count 
                    FROM " . DB_PREFIX . "login_attempts 
                    WHERE email = :email 
                    AND created_at > datetime('now', '-' . LOGIN_LOCKOUT_TIME . ' seconds')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result['attempt_count'] < MAX_LOGIN_ATTEMPTS;
            
        } catch (Exception $e) {
            $this->handleError('Check login attempts failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Record login attempt
     */
    private function recordLoginAttempt($email, $success) {
        try {
            $sql = "INSERT INTO " . DB_PREFIX . "login_attempts (email, success, ip_address, user_agent) 
                    VALUES (:email, :success, :ip_address, :user_agent)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':success', $success, PDO::PARAM_BOOL);
            $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
            $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
            
            return $stmt->execute();
        } catch (Exception $e) {
            $this->handleError('Record login attempt failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear login attempts
     */
    private function clearLoginAttempts($email) {
        try {
            $sql = "DELETE FROM " . DB_PREFIX . "login_attempts WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            return $stmt->execute();
        } catch (Exception $e) {
            $this->handleError('Clear login attempts failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update password hash
     */
    private function updatePasswordHash($userId, $passwordHash) {
        try {
            $sql = "UPDATE " . DB_PREFIX . "users SET password = :password WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':password', $passwordHash);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            $this->handleError('Update password hash failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update last login
     */
    private function updateLastLogin($userId) {
        try {
            $sql = "UPDATE " . DB_PREFIX . "users 
                    SET last_login = CURRENT_TIMESTAMP, login_count = login_count + 1 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            $this->handleError('Update last login failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set remember me cookie
     */
    private function setRememberMeCookie($userId) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        
        try {
            $sql = "INSERT INTO " . DB_PREFIX . "user_tokens (user_id, token, type, expires_at) 
                    VALUES (:user_id, :token, 'remember_me', datetime('now', '+30 days'))";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            setcookie('remember_me', $token, $expiry, '/', '', true, true);
            
        } catch (Exception $e) {
            $this->handleError('Set remember me cookie failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Clear session
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_me'])) {
            $this->clearRememberMeCookie($_COOKIE['remember_me']);
            setcookie('remember_me', '', time() - 3600, '/');
        }
        
        $this->user = null;
        $this->isLoggedIn = false;
        
        return true;
    }
    
    /**
     * Clear remember me cookie
     */
    private function clearRememberMeCookie($token) {
        try {
            $sql = "DELETE FROM " . DB_PREFIX . "user_tokens WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':token', $token);
            return $stmt->execute();
        } catch (Exception $e) {
            $this->handleError('Clear remember me cookie failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return $this->isLoggedIn;
    }
    
    /**
     * Get current user
     */
    public function getUser() {
        return $this->user;
    }
    
    /**
     * Get user ID
     */
    public function getUserId() {
        return $this->isLoggedIn ? $this->user['id'] : null;
    }
    
    /**
     * Get user role
     */
    public function getUserRole() {
        return $this->isLoggedIn ? $this->user['role_name'] : null;
    }
    
    /**
     * Check if user has specific role
     */
    public function hasRole($role) {
        return $this->isLoggedIn && $this->user['role_name'] === $role;
    }
    
    /**
     * Check if user has permission
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn) {
            return false;
        }
        
        // Super admin has all permissions
        if ($this->user['role_name'] === 'super_admin') {
            return true;
        }
        
        // Check role permissions
        $permissions = json_decode($this->user['role_permissions'], true);
        return is_array($permissions) && in_array($permission, $permissions);
    }
    
    /**
     * Check if user can access branch
     */
    public function canAccessBranch($branchId) {
        if (!$this->isLoggedIn) {
            return false;
        }
        
        // Super admin and owner can access all branches
        if (in_array($this->user['role_name'], ['super_admin', 'owner'])) {
            return true;
        }
        
        // Branch manager can only access their branch
        if ($this->user['role_name'] === 'branch_manager') {
            return $this->user['branch_id'] == $branchId;
        }
        
        // Other roles need explicit branch assignment
        try {
            $sql = "SELECT COUNT(*) as count FROM " . DB_PREFIX . "user_branches 
                    WHERE user_id = :user_id AND branch_id = :branch_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $this->user['id'], PDO::PARAM_INT);
            $stmt->bindParam(':branch_id', $branchId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result['count'] > 0;
            
        } catch (Exception $e) {
            $this->handleError('Check branch access failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            header('HTTP/1.0 403 Forbidden');
            echo 'Access denied. Insufficient permissions.';
            exit;
        }
    }
    
    /**
     * Require specific permission
     */
    public function requirePermission($permission) {
        $this->requireAuth();
        
        if (!$this->hasPermission($permission)) {
            header('HTTP/1.0 403 Forbidden');
            echo 'Access denied. Insufficient permissions.';
            exit;
        }
    }
    
    /**
     * Handle authentication errors
     */
    private function handleError($message) {
        if (DEBUG_MODE) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Auth Error:</strong> $message<br>";
            echo "</div>";
        }
        
        if (LOG_ERRORS) {
            error_log("Auth Error: $message", 3, ERROR_LOG_PATH);
        }
    }
}
?>