<?php
/**
 * Luna Dine Order Controller
 * 
 * Handles order management functionality
 */

class OrderController {
    private $db;
    private $auth;
    
    public function __construct($db, $auth) {
        $this->db = $db;
        $this->auth = $auth;
    }
    
    /**
     * Display order management page
     */
    public function index() {
        try {
            // Check authentication
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_orders');
            
            // Get user data
            $user = $this->auth->getUser();
            
            // Get user branches
            $branches = $this->getUserBranches();
            
            // Get selected branch from session or request
            $selectedBranchId = $_SESSION['selected_branch_id'] ?? ($branches[0]['id'] ?? null);
            
            if (isset($_GET['branch_id'])) {
                $selectedBranchId = (int) $_GET['branch_id'];
                $_SESSION['selected_branch_id'] = $selectedBranchId;
            }
            
            // Get pagination parameters
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $limit = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $limit;
            
            // Get filters
            $status = $_GET['status'] ?? '';
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';
            
            // Get orders
            $orders = $this->getOrders($selectedBranchId, $status, $dateFrom, $dateTo, $limit, $offset);
            
            // Get total count for pagination
            $total = $this->getOrdersCount($selectedBranchId, $status, $dateFrom, $dateTo);
            
            // Get pagination
            $pagination = Utilities::createPagination($total, $limit, $page);
            
            // Include view
            include LUNA_DINE_CORE . '/views/orders/index.php';
            
        } catch (Exception $e) {
            $this->handleError('Order management page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
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
     * Get orders with filters
     */
    private function getOrders($branchId, $status = '', $dateFrom = '', $dateTo = '', $limit = ITEMS_PER_PAGE, $offset = 0) {
        try {
            $conditions = [];
            $params = [];
            
            if ($branchId) {
                $conditions[] = 'o.branch_id = :branch_id';
                $params[':branch_id'] = $branchId;
            }
            
            if (!empty($status)) {
                $conditions[] = 'o.status = :status';
                $params[':status'] = $status;
            }
            
            if (!empty($dateFrom)) {
                $conditions[] = 'DATE(o.created_at) >= :date_from';
                $params[':date_from'] = $dateFrom;
            }
            
            if (!empty($dateTo)) {
                $conditions[] = 'DATE(o.created_at) <= :date_to';
                $params[':date_to'] = $dateTo;
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            $sql = "SELECT o.*, b.name as branch_name, t.number as table_number, 
                           u.first_name, u.last_name, u.email as user_email
                    FROM luna_orders o 
                    LEFT JOIN luna_branches b ON o.branch_id = b.id 
                    LEFT JOIN luna_tables t ON o.table_id = t.id 
                    LEFT JOIN luna_users u ON o.user_id = u.id 
                    $whereClause 
                    ORDER BY o.created_at DESC 
                    LIMIT $limit OFFSET $offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            $this->handleError('Get orders failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get orders count with filters
     */
    private function getOrdersCount($branchId, $status = '', $dateFrom = '', $dateTo = '') {
        try {
            $conditions = [];
            $params = [];
            
            if ($branchId) {
                $conditions[] = 'branch_id = :branch_id';
                $params[':branch_id'] = $branchId;
            }
            
            if (!empty($status)) {
                $conditions[] = 'status = :status';
                $params[':status'] = $status;
            }
            
            if (!empty($dateFrom)) {
                $conditions[] = 'DATE(created_at) >= :date_from';
                $params[':date_from'] = $dateFrom;
            }
            
            if (!empty($dateTo)) {
                $conditions[] = 'DATE(created_at) <= :date_to';
                $params[':date_to'] = $dateTo;
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            $sql = "SELECT COUNT(*) as total FROM luna_orders $whereClause";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            return $result['total'];
            
        } catch (Exception $e) {
            $this->handleError('Get orders count failed: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * View order details
     */
    public function view() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_orders');
            
            $orderId = (int) ($_GET['id'] ?? 0);
            
            if (empty($orderId)) {
                include LUNA_DINE_CORE . '/views/errors/404.php';
                return;
            }
            
            // Get order details
            $order = $this->getOrderById($orderId);
            
            if (!$order) {
                include LUNA_DINE_CORE . '/views/errors/404.php';
                return;
            }
            
            // Check branch access
            if (!$this->auth->canAccessBranch($order['branch_id'])) {
                include LUNA_DINE_CORE . '/views/errors/403.php';
                return;
            }
            
            // Get order items
            $orderItems = $this->getOrderItems($orderId);
            
            // Get order status history
            $statusHistory = $this->getOrderStatusHistory($orderId);
            
            // Include view
            include LUNA_DINE_CORE . '/views/orders/view.php';
            
        } catch (Exception $e) {
            $this->handleError('View order failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Get order by ID
     */
    private function getOrderById($orderId) {
        try {
            $sql = "SELECT o.*, b.name as branch_name, b.address as branch_address, 
                           b.phone as branch_phone, t.number as table_number,
                           u.first_name, u.last_name, u.email as user_email
                    FROM luna_orders o 
                    LEFT JOIN luna_branches b ON o.branch_id = b.id 
                    LEFT JOIN luna_tables t ON o.table_id = t.id 
                    LEFT JOIN luna_users u ON o.user_id = u.id 
                    WHERE o.id = :id AND o.deleted_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            $this->handleError('Get order by ID failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get order items
     */
    private function getOrderItems($orderId) {
        try {
            $sql = "SELECT oi.*, mi.name as item_name, mi.image as item_image
                    FROM luna_order_items oi 
                    LEFT JOIN luna_menu_items mi ON oi.menu_item_id = mi.id 
                    WHERE oi.order_id = :order_id 
                    ORDER BY oi.created_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            $this->handleError('Get order items failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get order status history
     */
    private function getOrderStatusHistory($orderId) {
        try {
            $sql = "SELECT osh.*, u.first_name, u.last_name 
                    FROM luna_order_status_history osh 
                    LEFT JOIN luna_users u ON osh.user_id = u.id 
                    WHERE osh.order_id = :order_id 
                    ORDER BY osh.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            $this->handleError('Get order status history failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update order status
     */
    public function updateStatus() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_orders');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/orders');
            }
            
            $user = $this->auth->getUser();
            
            // Sanitize input
            $orderId = (int) ($_POST['order_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $notes = Utilities::sanitize($_POST['notes'] ?? '');
            
            // Validate input
            $errors = [];
            
            if (empty($orderId)) {
                $errors[] = 'Order ID is required';
            }
            
            if (empty($status)) {
                $errors[] = 'Status is required';
            }
            
            // Get order and check access
            $order = $this->getOrderById($orderId);
            if (!$order) {
                $errors[] = 'Order not found';
            } elseif (!$this->auth->canAccessBranch($order['branch_id'])) {
                $errors[] = 'You do not have access to this order';
            }
            
            if (!empty($errors)) {
                $_SESSION['order_errors'] = $errors;
                Utilities::redirect('/orders/view?id=' . $orderId);
            }
            
            // Begin transaction
            $this->db->beginTransaction();
            
            try {
                // Update order status
                $updated = $this->db->update('luna_orders', ['status' => $status], 'id = :id', [':id' => $orderId]);
                
                if ($updated) {
                    // Add to status history
                    $historyData = [
                        'order_id' => $orderId,
                        'status' => $status,
                        'notes' => $notes,
                        'user_id' => $user['id']
                    ];
                    
                    $this->db->insert('luna_order_status_history', $historyData);
                    
                    // Update actual delivery time if order is delivered
                    if ($status === 'delivered') {
                        $this->db->update('luna_orders', ['actual_delivery_time' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $orderId]);
                    }
                    
                    // Commit transaction
                    $this->db->commit();
                    
                    // Log activity
                    Utilities::logActivity($user['id'], 'order_status_updated', "Order #{$order['order_number']} status updated to $status");
                    
                    // Send notification if needed
                    $this->sendOrderStatusNotification($order, $status);
                    
                    $_SESSION['order_success'] = 'Order status updated successfully';
                } else {
                    // Rollback transaction
                    $this->db->rollback();
                    $_SESSION['order_error'] = 'Failed to update order status';
                }
                
            } catch (Exception $e) {
                // Rollback transaction
                $this->db->rollback();
                throw $e;
            }
            
            Utilities::redirect('/orders/view?id=' . $orderId);
            
        } catch (Exception $e) {
            $this->handleError('Update order status failed: ' . $e->getMessage());
            $_SESSION['order_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/orders/view?id=' . $_POST['order_id']);
        }
    }
    
    /**
     * Send order status notification
     */
    private function sendOrderStatusNotification($order, $status) {
        try {
            // Get settings
            $settings = $this->getSettings();
            
            // Send email notification if customer email is available
            if (!empty($order['customer_email'])) {
                $statusText = ucwords(str_replace('_', ' ', $status));
                
                $emailSubject = "Order Status Update - {$order['order_number']} - " . SITE_NAME;
                $emailMessage = "
                    <h2>Order Status Update</h2>
                    <p>Hello {$order['customer_name']},</p>
                    <p>Your order #{$order['order_number']} status has been updated to: <strong>$statusText</strong></p>
                    <p><strong>Order Details:</strong></p>
                    <ul>
                        <li>Order Number: {$order['order_number']}</li>
                        <li>Branch: {$order['branch_name']}</li>
                        <li>Order Type: " . ucwords(str_replace('_', ' ', $order['order_type'])) . "</li>
                        <li>Total Amount: " . Utilities::formatCurrency($order['total']) . "</li>
                    </ul>
                    <p>Thank you for choosing " . SITE_NAME . "!</p>
                ";
                
                Utilities::sendEmail($order['customer_email'], $emailSubject, $emailMessage);
            }
            
        } catch (Exception $e) {
            $this->handleError('Send order status notification failed: ' . $e->getMessage());
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
     * Display customer order page
     */
    public function customerOrder() {
        try {
            // Check if customer has table session
            if (!isset($_SESSION['current_table']) || !isset($_SESSION['current_branch'])) {
                Utilities::redirect('/');
            }
            
            $table = $_SESSION['current_table'];
            $branch = $_SESSION['current_branch'];
            
            // Get branch categories
            $categories = $this->getBranchCategories($branch['id']);
            
            // Get branch menu items
            $menuItems = $this->getBranchMenuItems($branch['id']);
            
            // Get cart from session
            $cart = $_SESSION['cart'] ?? [];
            
            // Include view
            include LUNA_DINE_CORE . '/views/orders/customer_order.php';
            
        } catch (Exception $e) {
            $this->handleError('Customer order page display failed: ' . $e->getMessage());
            include LUNA_DINE_CORE . '/views/errors/500.php';
        }
    }
    
    /**
     * Get branch categories
     */
    private function getBranchCategories($branchId) {
        try {
            return $this->db->select('luna_categories', '*', 'branch_id = :branch_id AND status = :status AND deleted_at IS NULL', 
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
                    WHERE mi.branch_id = :branch_id AND mi.is_available = :available AND mi.status = :status AND mi.deleted_at IS NULL 
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
     * Add item to cart
     */
    public function addToCart() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/order');
            }
            
            // Check if customer has table session
            if (!isset($_SESSION['current_table']) || !isset($_SESSION['current_branch'])) {
                Utilities::jsonResponse(['success' => false, 'message' => 'Session expired. Please scan QR code again.']);
            }
            
            // Sanitize input
            $menuItemId = (int) ($_POST['menu_item_id'] ?? 0);
            $quantity = (int) ($_POST['quantity'] ?? 1);
            $notes = Utilities::sanitize($_POST['notes'] ?? '');
            
            // Validate input
            if (empty($menuItemId) || $quantity <= 0) {
                Utilities::jsonResponse(['success' => false, 'message' => 'Invalid item or quantity.']);
            }
            
            // Get menu item
            $menuItem = $this->getMenuItemById($menuItemId);
            if (!$menuItem) {
                Utilities::jsonResponse(['success' => false, 'message' => 'Menu item not found.']);
            }
            
            // Check if item belongs to current branch
            if ($menuItem['branch_id'] != $_SESSION['current_branch']['id']) {
                Utilities::jsonResponse(['success' => false, 'message' => 'Invalid menu item.']);
            }
            
            // Initialize cart if not exists
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            // Add to cart
            $cartItem = [
                'menu_item_id' => $menuItemId,
                'name' => $menuItem['name'],
                'price' => $menuItem['discount_price'] ?? $menuItem['price'],
                'quantity' => $quantity,
                'notes' => $notes,
                'image' => $menuItem['image']
            ];
            
            $_SESSION['cart'][] = $cartItem;
            
            // Calculate cart total
            $cartTotal = $this->calculateCartTotal();
            
            Utilities::jsonResponse([
                'success' => true, 
                'message' => 'Item added to cart',
                'cart_count' => count($_SESSION['cart']),
                'cart_total' => $cartTotal
            ]);
            
        } catch (Exception $e) {
            $this->handleError('Add to cart failed: ' . $e->getMessage());
            Utilities::jsonResponse(['success' => false, 'message' => 'An error occurred. Please try again later.']);
        }
    }
    
    /**
     * Get menu item by ID
     */
    private function getMenuItemById($menuItemId) {
        try {
            $result = $this->db->select('luna_menu_items', '*', 'id = :id AND is_available = :available AND status = :status AND deleted_at IS NULL', 
                [':id' => $menuItemId, ':available' => 'yes', ':status' => 'active']);
            return !empty($result) ? $result[0] : null;
            
        } catch (Exception $e) {
            $this->handleError('Get menu item by ID failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calculate cart total
     */
    private function calculateCartTotal() {
        $total = 0;
        
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }
        }
        
        return $total;
    }
    
    /**
     * Remove item from cart
     */
    public function removeFromCart() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/order');
            }
            
            $index = (int) ($_POST['index'] ?? 0);
            
            if (isset($_SESSION['cart'][$index])) {
                unset($_SESSION['cart'][$index]);
                $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
                
                $cartTotal = $this->calculateCartTotal();
                
                Utilities::jsonResponse([
                    'success' => true, 
                    'message' => 'Item removed from cart',
                    'cart_count' => count($_SESSION['cart']),
                    'cart_total' => $cartTotal
                ]);
            } else {
                Utilities::jsonResponse(['success' => false, 'message' => 'Item not found in cart.']);
            }
            
        } catch (Exception $e) {
            $this->handleError('Remove from cart failed: ' . $e->getMessage());
            Utilities::jsonResponse(['success' => false, 'message' => 'An error occurred. Please try again later.']);
        }
    }
    
    /**
     * Clear cart
     */
    public function clearCart() {
        try {
            $_SESSION['cart'] = [];
            Utilities::jsonResponse(['success' => true, 'message' => 'Cart cleared']);
            
        } catch (Exception $e) {
            $this->handleError('Clear cart failed: ' . $e->getMessage());
            Utilities::jsonResponse(['success' => false, 'message' => 'An error occurred. Please try again later.']);
        }
    }
    
    /**
     * Place order
     */
    public function placeOrder() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/order');
            }
            
            // Check if customer has table session
            if (!isset($_SESSION['current_table']) || !isset($_SESSION['current_branch'])) {
                Utilities::jsonResponse(['success' => false, 'message' => 'Session expired. Please scan QR code again.']);
            }
            
            // Check if cart is empty
            if (empty($_SESSION['cart'])) {
                Utilities::jsonResponse(['success' => false, 'message' => 'Your cart is empty.']);
            }
            
            // Sanitize input
            $customerName = Utilities::sanitize($_POST['customer_name'] ?? '');
            $customerPhone = Utilities::sanitize($_POST['customer_phone'] ?? '');
            $customerEmail = Utilities::sanitize($_POST['customer_email'] ?? '');
            $notes = Utilities::sanitize($_POST['notes'] ?? '');
            
            // Validate input
            $errors = [];
            
            if (empty($customerName)) {
                $errors[] = 'Customer name is required';
            }
            
            if (empty($customerPhone)) {
                $errors[] = 'Customer phone is required';
            }
            
            if (!empty($customerEmail) && !Utilities::validateEmail($customerEmail)) {
                $errors[] = 'Valid email is required';
            }
            
            if (!empty($errors)) {
                Utilities::jsonResponse(['success' => false, 'message' => implode(', ', $errors)]);
            }
            
            // Begin transaction
            $this->db->beginTransaction();
            
            try {
                $table = $_SESSION['current_table'];
                $branch = $_SESSION['current_branch'];
                
                // Calculate order totals
                $subtotal = $this->calculateCartTotal();
                $tax = $subtotal * ($branch['tax_rate'] / 100);
                $deliveryFee = 0; // No delivery fee for dine-in
                $total = $subtotal + $tax + $deliveryFee;
                
                // Create order
                $orderData = [
                    'order_number' => Utilities::generateOrderNumber(),
                    'branch_id' => $branch['id'],
                    'table_id' => $table['id'],
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'customer_email' => $customerEmail,
                    'order_type' => 'dine_in',
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'payment_method' => 'cash',
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'discount' => 0,
                    'delivery_fee' => $deliveryFee,
                    'total' => $total,
                    'notes' => $notes
                ];
                
                $orderId = $this->db->insert('luna_orders', $orderData);
                
                if ($orderId) {
                    // Add order items
                    foreach ($_SESSION['cart'] as $item) {
                        $orderItemData = [
                            'order_id' => $orderId,
                            'menu_item_id' => $item['menu_item_id'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['price'],
                            'total_price' => $item['price'] * $item['quantity'],
                            'notes' => $item['notes'],
                            'status' => 'pending'
                        ];
                        
                        $this->db->insert('luna_order_items', $orderItemData);
                    }
                    
                    // Add order status history
                    $historyData = [
                        'order_id' => $orderId,
                        'status' => 'pending',
                        'notes' => 'Order placed by customer'
                    ];
                    
                    $this->db->insert('luna_order_status_history', $historyData);
                    
                    // Update table status
                    $this->db->update('luna_tables', ['status' => 'occupied'], 'id = :id', [':id' => $table['id']]);
                    
                    // Commit transaction
                    $this->db->commit();
                    
                    // Clear cart
                    $_SESSION['cart'] = [];
                    
                    // Clear table session
                    unset($_SESSION['current_table']);
                    unset($_SESSION['current_branch']);
                    
                    Utilities::jsonResponse([
                        'success' => true, 
                        'message' => 'Order placed successfully',
                        'order_id' => $orderId,
                        'order_number' => $orderData['order_number']
                    ]);
                } else {
                    // Rollback transaction
                    $this->db->rollback();
                    Utilities::jsonResponse(['success' => false, 'message' => 'Failed to place order.']);
                }
                
            } catch (Exception $e) {
                // Rollback transaction
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            $this->handleError('Place order failed: ' . $e->getMessage());
            Utilities::jsonResponse(['success' => false, 'message' => 'An error occurred. Please try again later.']);
        }
    }
    
    /**
     * Handle errors
     */
    private function handleError($message) {
        if (DEBUG_MODE) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Order Controller Error:</strong> $message<br>";
            echo "</div>";
        }
        
        Utilities::logError('Order Controller: ' . $message);
    }
}
?>