<?php
/**
 * Luna Dine Authentication Controller
 * 
 * Handles user authentication (login, logout, registration, etc.)
 */

class AuthController {
    private $db;
    private $auth;
    
    public function __construct($db, $auth) {
        $this->db = $db;
        $this->auth = $auth;
    }
    
    /**
     * Display login page
     */
    public function login() {
        try {
            // Redirect if already logged in
            if ($this->auth->isLoggedIn()) {
                Utilities::redirect('/admin');
            }
            
            // Include login view
            include LUNA_DINE_CORE . '/views/auth/login.php';
            
        } catch (Exception $e) {
            $this->handleError('Login page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Handle login submission
     */
    public function doLogin() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/login');
            }
            
            // Sanitize input
            $email = Utilities::sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']) && $_POST['remember'] === 'yes';
            
            // Validate input
            $errors = [];
            
            if (empty($email)) {
                $errors[] = 'Email is required';
            }
            
            if (empty($password)) {
                $errors[] = 'Password is required';
            }
            
            if (!empty($errors)) {
                $_SESSION['login_errors'] = $errors;
                $_SESSION['login_email'] = $email;
                Utilities::redirect('/login');
            }
            
            // Attempt login
            $result = $this->auth->login($email, $password, $remember);
            
            if ($result['success']) {
                // Log activity
                Utilities::logActivity($result['user']['id'], 'login', 'User logged in successfully');
                
                // Redirect to intended page or admin dashboard
                $redirect = $_SESSION['intended_url'] ?? '/admin';
                unset($_SESSION['intended_url']);
                
                Utilities::redirect($redirect);
            } else {
                $_SESSION['login_error'] = $result['message'];
                $_SESSION['login_email'] = $email;
                Utilities::redirect('/login');
            }
            
        } catch (Exception $e) {
            $this->handleError('Login failed: ' . $e->getMessage());
            $_SESSION['login_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/login');
        }
    }
    
    /**
     * Handle logout
     */
    public function logout() {
        try {
            if ($this->auth->isLoggedIn()) {
                $user = $this->auth->getUser();
                
                // Log activity
                Utilities::logActivity($user['id'], 'logout', 'User logged out');
                
                // Logout
                $this->auth->logout();
            }
            
            Utilities::redirect('/login');
            
        } catch (Exception $e) {
            $this->handleError('Logout failed: ' . $e->getMessage());
            Utilities::redirect('/');
        }
    }
    
    /**
     * Display forgot password page
     */
    public function forgotPassword() {
        try {
            // Redirect if already logged in
            if ($this->auth->isLoggedIn()) {
                Utilities::redirect('/admin');
            }
            
            // Include forgot password view
            include LUNA_DINE_CORE . '/views/auth/forgot_password.php';
            
        } catch (Exception $e) {
            $this->handleError('Forgot password page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Handle forgot password submission
     */
    public function doForgotPassword() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/forgot-password');
            }
            
            // Sanitize input
            $email = Utilities::sanitize($_POST['email'] ?? '');
            
            // Validate input
            $errors = [];
            
            if (empty($email)) {
                $errors[] = 'Email is required';
            }
            
            if (!Utilities::validateEmail($email)) {
                $errors[] = 'Valid email is required';
            }
            
            if (!empty($errors)) {
                $_SESSION['forgot_password_errors'] = $errors;
                $_SESSION['forgot_password_email'] = $email;
                Utilities::redirect('/forgot-password');
            }
            
            // Check if user exists
            $user = $this->getUserByEmail($email);
            
            if (!$user) {
                // Don't reveal that user doesn't exist
                $_SESSION['forgot_password_success'] = 'If your email is registered, you will receive a password reset link.';
                Utilities::redirect('/forgot-password');
            }
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token
            $tokenData = [
                'user_id' => $user['id'],
                'token' => $token,
                'type' => 'reset_password',
                'expires_at' => $expiry
            ];
            
            $tokenId = $this->db->insert('luna_user_tokens', $tokenData);
            
            if ($tokenId) {
                // Send reset email
                $resetLink = SITE_URL . '/reset-password?token=' . $token;
                
                $emailSubject = 'Password Reset Request - ' . SITE_NAME;
                $emailMessage = "
                    <h2>Password Reset Request</h2>
                    <p>Hello {$user['first_name']} {$user['last_name']},</p>
                    <p>We received a request to reset your password. Click the link below to reset your password:</p>
                    <p><a href='$resetLink' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                    <p>If you didn't request this, please ignore this email.</p>
                    <p>This link will expire in 1 hour.</p>
                    <p>Best regards,<br>" . SITE_NAME . " Team</p>
                ";
                
                $sent = Utilities::sendEmail($email, $emailSubject, $emailMessage);
                
                if ($sent) {
                    $_SESSION['forgot_password_success'] = 'Password reset link sent to your email.';
                } else {
                    $_SESSION['forgot_password_error'] = 'Failed to send reset email. Please try again later.';
                }
            } else {
                $_SESSION['forgot_password_error'] = 'Failed to generate reset token. Please try again later.';
            }
            
            Utilities::redirect('/forgot-password');
            
        } catch (Exception $e) {
            $this->handleError('Forgot password failed: ' . $e->getMessage());
            $_SESSION['forgot_password_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/forgot-password');
        }
    }
    
    /**
     * Display reset password page
     */
    public function resetPassword() {
        try {
            // Redirect if already logged in
            if ($this->auth->isLoggedIn()) {
                Utilities::redirect('/admin');
            }
            
            // Get token from URL
            $token = $_GET['token'] ?? '';
            
            if (empty($token)) {
                $_SESSION['reset_password_error'] = 'Invalid reset token.';
                Utilities::redirect('/forgot-password');
            }
            
            // Validate token
            $tokenData = $this->getValidToken($token, 'reset_password');
            
            if (!$tokenData) {
                $_SESSION['reset_password_error'] = 'Invalid or expired reset token.';
                Utilities::redirect('/forgot-password');
            }
            
            // Include reset password view
            include LUNA_DINE_CORE . '/views/auth/reset_password.php';
            
        } catch (Exception $e) {
            $this->handleError('Reset password page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Handle reset password submission
     */
    public function doResetPassword() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/forgot-password');
            }
            
            // Sanitize input
            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validate input
            $errors = [];
            
            if (empty($token)) {
                $errors[] = 'Invalid reset token.';
            }
            
            if (empty($password)) {
                $errors[] = 'Password is required';
            }
            
            if (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            }
            
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match';
            }
            
            if (!empty($errors)) {
                $_SESSION['reset_password_errors'] = $errors;
                $_SESSION['reset_password_token'] = $token;
                Utilities::redirect('/reset-password?token=' . $token);
            }
            
            // Validate token
            $tokenData = $this->getValidToken($token, 'reset_password');
            
            if (!$tokenData) {
                $_SESSION['reset_password_error'] = 'Invalid or expired reset token.';
                Utilities::redirect('/forgot-password');
            }
            
            // Update password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]);
            $updated = $this->db->update('luna_users', ['password' => $passwordHash], 'id = :id', 
                [':id' => $tokenData['user_id']]);
            
            if ($updated) {
                // Mark token as used
                $this->db->update('luna_user_tokens', ['used_at' => date('Y-m-d H:i:s')], 'id = :id', 
                    [':id' => $tokenData['id']]);
                
                // Log activity
                Utilities::logActivity($tokenData['user_id'], 'password_reset', 'Password reset successfully');
                
                $_SESSION['reset_password_success'] = 'Password reset successfully. Please login with your new password.';
                Utilities::redirect('/login');
            } else {
                $_SESSION['reset_password_error'] = 'Failed to reset password. Please try again later.';
                Utilities::redirect('/reset-password?token=' . $token);
            }
            
        } catch (Exception $e) {
            $this->handleError('Reset password failed: ' . $e->getMessage());
            $_SESSION['reset_password_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/forgot-password');
        }
    }
    
    /**
     * Display registration page (if enabled)
     */
    public function register() {
        try {
            // Check if registration is enabled
            $settings = $this->getSettings();
            if (!isset($settings['enable_registration']) || $settings['enable_registration']['value'] !== 'true') {
                include LUNA_DINE_CORE . '/views/errors/403.php';
                return;
            }
            
            // Redirect if already logged in
            if ($this->auth->isLoggedIn()) {
                Utilities::redirect('/admin');
            }
            
            // Get roles for registration
            $roles = $this->getRegistrationRoles();
            
            // Include registration view
            include LUNA_DINE_CORE . '/views/auth/register.php';
            
        } catch (Exception $e) {
            $this->handleError('Registration page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Handle registration submission
     */
    public function doRegister() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/register');
            }
            
            // Check if registration is enabled
            $settings = $this->getSettings();
            if (!isset($settings['enable_registration']) || $settings['enable_registration']['value'] !== 'true') {
                include LUNA_DINE_CORE . '/views/errors/403.php';
                return;
            }
            
            // Sanitize input
            $firstName = Utilities::sanitize($_POST['first_name'] ?? '');
            $lastName = Utilities::sanitize($_POST['last_name'] ?? '');
            $email = Utilities::sanitize($_POST['email'] ?? '');
            $phone = Utilities::sanitize($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $roleId = (int) ($_POST['role_id'] ?? 0);
            
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
            
            if (empty($password)) {
                $errors[] = 'Password is required';
            }
            
            if (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters';
            }
            
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match';
            }
            
            // Check if email already exists
            $existingUser = $this->getUserByEmail($email);
            if ($existingUser) {
                $errors[] = 'Email already registered';
            }
            
            // Validate role
            $validRoles = $this->getRegistrationRoles();
            $validRoleIds = array_column($validRoles, 'id');
            if (!in_array($roleId, $validRoleIds)) {
                $errors[] = 'Invalid role selected';
            }
            
            if (!empty($errors)) {
                $_SESSION['register_errors'] = $errors;
                $_SESSION['register_data'] = $_POST;
                Utilities::redirect('/register');
            }
            
            // Create user
            $userData = [
                'role_id' => $roleId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'password' => password_hash($password, PASSWORD_DEFAULT, ['cost' => HASH_COST]),
                'status' => 'pending' // Pending approval
            ];
            
            $userId = $this->db->insert('luna_users', $userData);
            
            if ($userId) {
                // Log activity
                Utilities::logActivity($userId, 'user_registered', 'New user registered');
                
                // Send notification to admins
                $this->notifyAdminsAboutNewUser($userId);
                
                $_SESSION['register_success'] = 'Registration successful. Your account is pending approval.';
                Utilities::redirect('/login');
            } else {
                $_SESSION['register_error'] = 'Failed to register. Please try again later.';
                $_SESSION['register_data'] = $_POST;
                Utilities::redirect('/register');
            }
            
        } catch (Exception $e) {
            $this->handleError('Registration failed: ' . $e->getMessage());
            $_SESSION['register_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/register');
        }
    }
    
    /**
     * Get user by email
     */
    private function getUserByEmail($email) {
        try {
            $result = $this->db->select('luna_users', '*', 'email = :email AND deleted_at IS NULL', [':email' => $email]);
            return !empty($result) ? $result[0] : null;
            
        } catch (Exception $e) {
            $this->handleError('Get user by email failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get valid token
     */
    private function getValidToken($token, $type) {
        try {
            $sql = "SELECT * FROM luna_user_tokens 
                    WHERE token = :token AND type = :type AND expires_at > CURRENT_TIMESTAMP AND used_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':type', $type);
            $stmt->execute();
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            $this->handleError('Get valid token failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get settings
     */
    private function getSettings() {
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
            $this->handleError('Get settings failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get roles available for registration
     */
    private function getRegistrationRoles() {
        try {
            // Only allow certain roles for registration
            $allowedRoles = ['staff', 'waiter', 'chef'];
            
            $placeholders = implode(',', array_fill(0, count($allowedRoles), '?'));
            $sql = "SELECT * FROM luna_roles WHERE name IN ($placeholders) AND status = 'active'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($allowedRoles);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            $this->handleError('Get registration roles failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Notify admins about new user registration
     */
    private function notifyAdminsAboutNewUser($userId) {
        try {
            // Get user details
            $user = $this->getUserById($userId);
            if (!$user) {
                return;
            }
            
            // Get admin users
            $adminRoles = ['super_admin', 'owner'];
            $placeholders = implode(',', array_fill(0, count($adminRoles), '?'));
            $sql = "SELECT * FROM luna_users WHERE role_id IN (SELECT id FROM luna_roles WHERE name IN ($placeholders)) AND status = 'active'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($adminRoles);
            $admins = $stmt->fetchAll();
            
            // Send notification to each admin
            foreach ($admins as $admin) {
                $emailSubject = 'New User Registration - ' . SITE_NAME;
                $emailMessage = "
                    <h2>New User Registration</h2>
                    <p>A new user has registered and is pending approval:</p>
                    <p><strong>Name:</strong> {$user['first_name']} {$user['last_name']}</p>
                    <p><strong>Email:</strong> {$user['email']}</p>
                    <p><strong>Phone:</strong> {$user['phone']}</p>
                    <p><strong>Role:</strong> {$user['role_name']}</p>
                    <p><strong>Registered:</strong> " . Utilities::formatDate($user['created_at']) . "</p>
                    <p>Please review and approve this user account.</p>
                    <p><a href='" . SITE_URL . "/admin/users'>View Users</a></p>
                ";
                
                Utilities::sendEmail($admin['email'], $emailSubject, $emailMessage);
                
                // Create notification
                $notificationData = [
                    'user_id' => $admin['id'],
                    'type' => 'new_user_registration',
                    'title' => 'New User Registration',
                    'message' => "New user {$user['first_name']} {$user['last_name']} registered and needs approval.",
                    'data' => json_encode(['user_id' => $userId])
                ];
                
                $this->db->insert('luna_notifications', $notificationData);
            }
            
        } catch (Exception $e) {
            $this->handleError('Notify admins about new user failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get user by ID
     */
    private function getUserById($userId) {
        try {
            $sql = "SELECT u.*, r.name as role_name 
                    FROM luna_users u 
                    LEFT JOIN luna_roles r ON u.role_id = r.id 
                    WHERE u.id = :id AND u.deleted_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            $this->handleError('Get user by ID failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Handle errors
     */
    private function handleError($message) {
        if (DEBUG_MODE) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Auth Controller Error:</strong> $message<br>";
            echo "</div>";
        }
        
        Utilities::logError('Auth Controller: ' . $message);
    }
}
?>