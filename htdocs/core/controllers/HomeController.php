<?php
/**
 * Luna Dine Home Controller
 * 
 * Handles homepage and customer-facing functionality
 */

class HomeController {
    private $db;
    private $auth;
    
    public function __construct($db, $auth) {
        $this->db = $db;
        $this->auth = $auth;
    }
    
    /**
     * Display homepage
     */
    public function index() {
        try {
            // Get system settings
            $settings = $this->getSettings();
            
            // Get branches
            $branches = $this->getBranches();
            
            // Get featured menu items
            $featuredItems = $this->getFeaturedMenuItems();
            
            // Include view
            include LUNA_DINE_CORE . '/views/home/index.php';
            
        } catch (Exception $e) {
            $this->handleError('Homepage display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Get system settings
     */
    private function getSettings() {
        try {
            $settings = [];
            $result = $this->db->select('luna_settings', '*', 'is_system = :is_system', [':is_system' => 'yes']);
            
            foreach ($result as $row) {
                $settings[$row['key']] = $row['value'];
            }
            
            return $settings;
            
        } catch (Exception $e) {
            $this->handleError('Get settings failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get active branches
     */
    private function getBranches() {
        try {
            return $this->db->select('luna_branches', '*', 'status = :status', [':status' => 'active'], 'name ASC');
            
        } catch (Exception $e) {
            $this->handleError('Get branches failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get featured menu items
     */
    private function getFeaturedMenuItems() {
        try {
            $sql = "SELECT mi.*, c.name as category_name, b.name as branch_name 
                    FROM luna_menu_items mi 
                    LEFT JOIN luna_categories c ON mi.category_id = c.id 
                    LEFT JOIN luna_branches b ON mi.branch_id = b.id 
                    WHERE mi.is_featured = :featured AND mi.is_available = :available AND mi.status = :status 
                    ORDER BY mi.sort_order ASC, mi.created_at DESC 
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':featured', 'yes');
            $stmt->bindParam(':available', 'yes');
            $stmt->bindParam(':status', 'active');
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            $this->handleError('Get featured items failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Display branch page
     */
    public function branch($branchCode) {
        try {
            // Get branch details
            $branch = $this->getBranchByCode($branchCode);
            
            if (!$branch) {
                include LUNA_DINE_CORE . '/views/errors/404.php';
                return;
            }
            
            // Get branch categories
            $categories = $this->getBranchCategories($branch['id']);
            
            // Get branch menu items
            $menuItems = $this->getBranchMenuItems($branch['id']);
            
            // Include view
            include LUNA_DINE_CORE . '/views/home/branch.php';
            
        } catch (Exception $e) {
            $this->handleError('Branch page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Get branch by code
     */
    private function getBranchByCode($branchCode) {
        try {
            $result = $this->db->select('luna_branches', '*', 'code = :code AND status = :status', 
                [':code' => $branchCode, ':status' => 'active']);
            
            return !empty($result) ? $result[0] : null;
            
        } catch (Exception $e) {
            $this->handleError('Get branch by code failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get branch categories
     */
    private function getBranchCategories($branchId) {
        try {
            return $this->db->select('luna_categories', '*', 'branch_id = :branch_id AND status = :status', 
                [':branch_id' => $branchId, ':status' => 'active'], 'sort_order ASC, name ASC');
            
        } catch (Exception $e) {
            $this->handleError('Get branch categories failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get branch menu items
     */
    private function getBranchMenuItems($branchId) {
        try {
            $sql = "SELECT mi.*, c.name as category_name 
                    FROM luna_menu_items mi 
                    LEFT JOIN luna_categories c ON mi.category_id = c.id 
                    WHERE mi.branch_id = :branch_id AND mi.is_available = :available AND mi.status = :status 
                    ORDER BY mi.sort_order ASC, mi.name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':branch_id', $branchId, PDO::PARAM_INT);
            $stmt->bindParam(':available', 'yes');
            $stmt->bindParam(':status', 'active');
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            $this->handleError('Get branch menu items failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Display menu page
     */
    public function menu($branchCode = null) {
        try {
            $branch = null;
            $categories = [];
            $menuItems = [];
            
            if ($branchCode) {
                // Get specific branch menu
                $branch = $this->getBranchByCode($branchCode);
                if (!$branch) {
                    include LUNA_DINE_CORE . '/views/errors/404.php';
                    return;
                }
                
                $categories = $this->getBranchCategories($branch['id']);
                $menuItems = $this->getBranchMenuItems($branch['id']);
            } else {
                // Get all branches menu
                $branches = $this->getBranches();
                $allCategories = [];
                $allMenuItems = [];
                
                foreach ($branches as $branch) {
                    $branchCategories = $this->getBranchCategories($branch['id']);
                    $branchMenuItems = $this->getBranchMenuItems($branch['id']);
                    
                    $allCategories = array_merge($allCategories, $branchCategories);
                    $allMenuItems = array_merge($allMenuItems, $branchMenuItems);
                }
                
                $categories = $allCategories;
                $menuItems = $allMenuItems;
            }
            
            // Include view
            include LUNA_DINE_CORE . '/views/home/menu.php';
            
        } catch (Exception $e) {
            $this->handleError('Menu page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Display table page (from QR code scan)
     */
    public function table($qrCode) {
        try {
            // Get table by QR code
            $table = $this->getTableByQRCode($qrCode);
            
            if (!$table) {
                include LUNA_DINE_CORE . '/views/errors/404.php';
                return;
            }
            
            // Get branch details
            $branch = $this->getBranchById($table['branch_id']);
            
            if (!$branch || $branch['status'] !== 'active') {
                include LUNA_DINE_CORE . '/views/errors/404.php';
                return;
            }
            
            // Get branch categories
            $categories = $this->getBranchCategories($branch['id']);
            
            // Get branch menu items
            $menuItems = $this->getBranchMenuItems($branch['id']);
            
            // Start session for cart
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Set current table in session
            $_SESSION['current_table'] = $table;
            $_SESSION['current_branch'] = $branch;
            
            // Include view
            include LUNA_DINE_CORE . '/views/home/table.php';
            
        } catch (Exception $e) {
            $this->handleError('Table page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Get table by QR code
     */
    private function getTableByQRCode($qrCode) {
        try {
            $result = $this->db->select('luna_tables', '*', 'qr_code = :qr_code AND status = :status', 
                [':qr_code' => $qrCode, ':status' => 'available']);
            
            return !empty($result) ? $result[0] : null;
            
        } catch (Exception $e) {
            $this->handleError('Get table by QR code failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get branch by ID
     */
    private function getBranchById($branchId) {
        try {
            $result = $this->db->select('luna_branches', '*', 'id = :id', [':id' => $branchId]);
            return !empty($result) ? $result[0] : null;
            
        } catch (Exception $e) {
            $this->handleError('Get branch by ID failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Display contact page
     */
    public function contact() {
        try {
            // Get system settings
            $settings = $this->getSettings();
            
            // Include view
            include LUNA_DINE_CORE . '/views/home/contact.php';
            
        } catch (Exception $e) {
            $this->handleError('Contact page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Display about page
     */
    public function about() {
        try {
            // Get system settings
            $settings = $this->getSettings();
            
            // Include view
            include LUNA_DINE_CORE . '/views/home/about.php';
            
        } catch (Exception $e) {
            $this->handleError('About page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Handle contact form submission
     */
    public function submitContact() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/contact');
            }
            
            // Sanitize input
            $name = Utilities::sanitize($_POST['name'] ?? '');
            $email = Utilities::sanitize($_POST['email'] ?? '');
            $phone = Utilities::sanitize($_POST['phone'] ?? '');
            $subject = Utilities::sanitize($_POST['subject'] ?? '');
            $message = Utilities::sanitize($_POST['message'] ?? '');
            
            // Validate input
            $errors = [];
            
            if (empty($name)) {
                $errors[] = 'Name is required';
            }
            
            if (empty($email) || !Utilities::validateEmail($email)) {
                $errors[] = 'Valid email is required';
            }
            
            if (empty($message)) {
                $errors[] = 'Message is required';
            }
            
            if (!empty($errors)) {
                $_SESSION['contact_errors'] = $errors;
                $_SESSION['contact_data'] = $_POST;
                Utilities::redirect('/contact');
            }
            
            // Get system settings
            $settings = $this->getSettings();
            
            // Send email
            $emailSubject = "Contact Form: $subject";
            $emailMessage = "
                <h2>Contact Form Submission</h2>
                <p><strong>Name:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Phone:</strong> $phone</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong></p>
                <p>" . nl2br($message) . "</p>
            ";
            
            $sent = Utilities::sendEmail($settings['site_email'], $emailSubject, $emailMessage);
            
            if ($sent) {
                $_SESSION['contact_success'] = 'Thank you for your message. We will get back to you soon!';
            } else {
                $_SESSION['contact_error'] = 'Failed to send message. Please try again later.';
            }
            
            Utilities::redirect('/contact');
            
        } catch (Exception $e) {
            $this->handleError('Contact form submission failed: ' . $e->getMessage());
            $_SESSION['contact_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/contact');
        }
    }
    
    /**
     * Handle errors
     */
    private function handleError($message) {
        if (DEBUG_MODE) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Home Controller Error:</strong> $message<br>";
            echo "</div>";
        }
        
        Utilities::logError('Home Controller: ' . $message);
    }
}
?>