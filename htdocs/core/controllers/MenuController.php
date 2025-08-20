<?php
/**
 * Luna Dine Menu Controller
 * 
 * Handles menu management functionality
 */

class MenuController {
    private $db;
    private $auth;
    
    public function __construct($db, $auth) {
        $this->db = $db;
        $this->auth = $auth;
    }
    
    /**
     * Display menu management page
     */
    public function index() {
        try {
            // Check authentication
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_menu');
            
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
            
            // Get categories for selected branch
            $categories = $this->getBranchCategories($selectedBranchId);
            
            // Get menu items for selected branch
            $menuItems = $this->getBranchMenuItems($selectedBranchId);
            
            // Include view
            include LUNA_DINE_CORE . '/views/menu/index.php';
            
        } catch (Exception $e) {
            $this->handleError('Menu management page display failed: ' . $e->getMessage());
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
     * Get branch categories
     */
    private function getBranchCategories($branchId) {
        try {
            if (!$branchId) {
                return [];
            }
            
            return $this->db->select('luna_categories', '*', 'branch_id = :branch_id AND deleted_at IS NULL', 
                [':branch_id' => $branchId], 'sort_order ASC, name ASC');
                
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
            if (!$branchId) {
                return [];
            }
            
            $sql = "SELECT mi.*, c.name as category_name 
                    FROM luna_menu_items mi 
                    LEFT JOIN luna_categories c ON mi.category_id = c.id 
                    WHERE mi.branch_id = :branch_id AND mi.deleted_at IS NULL 
                    ORDER BY mi.sort_order ASC, mi.name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':branch_id', $branchId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            $this->handleError('Get branch menu items failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add new category
     */
    public function addCategory() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_menu');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/menu');
            }
            
            $user = $this->auth->getUser();
            
            // Sanitize input
            $branchId = (int) ($_POST['branch_id'] ?? 0);
            $name = Utilities::sanitize($_POST['name'] ?? '');
            $description = Utilities::sanitize($_POST['description'] ?? '');
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            
            // Validate input
            $errors = [];
            
            if (empty($branchId)) {
                $errors[] = 'Branch is required';
            }
            
            if (empty($name)) {
                $errors[] = 'Category name is required';
            }
            
            // Check branch access
            if (!$this->auth->canAccessBranch($branchId)) {
                $errors[] = 'You do not have access to this branch';
            }
            
            if (!empty($errors)) {
                $_SESSION['category_errors'] = $errors;
                $_SESSION['category_data'] = $_POST;
                Utilities::redirect('/menu');
            }
            
            // Handle image upload
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = Utilities::uploadFile($_FILES['image'], UPLOAD_PATH . '/categories/', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                if ($uploadResult['success']) {
                    $image = $uploadResult['filename'];
                } else {
                    $errors[] = $uploadResult['message'];
                }
            }
            
            // Create category
            $categoryData = [
                'branch_id' => $branchId,
                'name' => $name,
                'description' => $description,
                'image' => $image,
                'sort_order' => $sortOrder,
                'status' => 'active'
            ];
            
            $categoryId = $this->db->insert('luna_categories', $categoryData);
            
            if ($categoryId) {
                // Log activity
                Utilities::logActivity($user['id'], 'category_added', "Category '$name' added");
                
                $_SESSION['category_success'] = 'Category added successfully';
            } else {
                $_SESSION['category_error'] = 'Failed to add category';
            }
            
            Utilities::redirect('/menu');
            
        } catch (Exception $e) {
            $this->handleError('Add category failed: ' . $e->getMessage());
            $_SESSION['category_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/menu');
        }
    }
    
    /**
     * Update category
     */
    public function updateCategory() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_menu');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/menu');
            }
            
            $user = $this->auth->getUser();
            
            // Sanitize input
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $name = Utilities::sanitize($_POST['name'] ?? '');
            $description = Utilities::sanitize($_POST['description'] ?? '');
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            $status = $_POST['status'] ?? 'active';
            
            // Validate input
            $errors = [];
            
            if (empty($categoryId)) {
                $errors[] = 'Category ID is required';
            }
            
            if (empty($name)) {
                $errors[] = 'Category name is required';
            }
            
            // Get category and check access
            $category = $this->getCategoryById($categoryId);
            if (!$category) {
                $errors[] = 'Category not found';
            } elseif (!$this->auth->canAccessBranch($category['branch_id'])) {
                $errors[] = 'You do not have access to this category';
            }
            
            if (!empty($errors)) {
                $_SESSION['category_errors'] = $errors;
                $_SESSION['category_data'] = $_POST;
                Utilities::redirect('/menu');
            }
            
            // Handle image upload
            $image = $category['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Delete old image
                if (!empty($image)) {
                    Utilities::deleteFile(UPLOAD_PATH . '/categories/' . $image);
                }
                
                $uploadResult = Utilities::uploadFile($_FILES['image'], UPLOAD_PATH . '/categories/', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                if ($uploadResult['success']) {
                    $image = $uploadResult['filename'];
                } else {
                    $errors[] = $uploadResult['message'];
                }
            }
            
            // Update category
            $updateData = [
                'name' => $name,
                'description' => $description,
                'image' => $image,
                'sort_order' => $sortOrder,
                'status' => $status
            ];
            
            $updated = $this->db->update('luna_categories', $updateData, 'id = :id', [':id' => $categoryId]);
            
            if ($updated) {
                // Log activity
                Utilities::logActivity($user['id'], 'category_updated', "Category '$name' updated");
                
                $_SESSION['category_success'] = 'Category updated successfully';
            } else {
                $_SESSION['category_error'] = 'Failed to update category';
            }
            
            Utilities::redirect('/menu');
            
        } catch (Exception $e) {
            $this->handleError('Update category failed: ' . $e->getMessage());
            $_SESSION['category_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/menu');
        }
    }
    
    /**
     * Delete category
     */
    public function deleteCategory() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_menu');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/menu');
            }
            
            $user = $this->auth->getUser();
            
            // Sanitize input
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            
            // Validate input
            $errors = [];
            
            if (empty($categoryId)) {
                $errors[] = 'Category ID is required';
            }
            
            // Get category and check access
            $category = $this->getCategoryById($categoryId);
            if (!$category) {
                $errors[] = 'Category not found';
            } elseif (!$this->auth->canAccessBranch($category['branch_id'])) {
                $errors[] = 'You do not have access to this category';
            }
            
            // Check if category has menu items
            $menuItems = $this->db->select('luna_menu_items', 'COUNT(*) as count', 
                'category_id = :category_id AND deleted_at IS NULL', [':category_id' => $categoryId]);
            
            if (!empty($menuItems) && $menuItems[0]['count'] > 0) {
                $errors[] = 'Cannot delete category with existing menu items';
            }
            
            if (!empty($errors)) {
                $_SESSION['category_errors'] = $errors;
                Utilities::redirect('/menu');
            }
            
            // Soft delete category
            $deleted = $this->db->update('luna_categories', ['deleted_at' => date('Y-m-d H:i:s')], 
                'id = :id', [':id' => $categoryId]);
            
            if ($deleted) {
                // Log activity
                Utilities::logActivity($user['id'], 'category_deleted', "Category '{$category['name']}' deleted");
                
                $_SESSION['category_success'] = 'Category deleted successfully';
            } else {
                $_SESSION['category_error'] = 'Failed to delete category';
            }
            
            Utilities::redirect('/menu');
            
        } catch (Exception $e) {
            $this->handleError('Delete category failed: ' . $e->getMessage());
            $_SESSION['category_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/menu');
        }
    }
    
    /**
     * Add new menu item
     */
    public function addMenuItem() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_menu');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/menu');
            }
            
            $user = $this->auth->getUser();
            
            // Sanitize input
            $branchId = (int) ($_POST['branch_id'] ?? 0);
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $name = Utilities::sanitize($_POST['name'] ?? '');
            $description = Utilities::sanitize($_POST['description'] ?? '');
            $price = (float) ($_POST['price'] ?? 0);
            $discountPrice = (float) ($_POST['discount_price'] ?? 0);
            $preparationTime = (int) ($_POST['preparation_time'] ?? 0);
            $calories = (int) ($_POST['calories'] ?? 0);
            $isVegetarian = $_POST['is_vegetarian'] ?? 'no';
            $isVegan = $_POST['is_vegan'] ?? 'no';
            $isGlutenFree = $_POST['is_gluten_free'] ?? 'no';
            $isSpicy = $_POST['is_spicy'] ?? 'no';
            $isAvailable = $_POST['is_available'] ?? 'yes';
            $isFeatured = $_POST['is_featured'] ?? 'no';
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            $status = $_POST['status'] ?? 'active';
            
            // Validate input
            $errors = [];
            
            if (empty($branchId)) {
                $errors[] = 'Branch is required';
            }
            
            if (empty($categoryId)) {
                $errors[] = 'Category is required';
            }
            
            if (empty($name)) {
                $errors[] = 'Menu item name is required';
            }
            
            if ($price <= 0) {
                $errors[] = 'Price must be greater than 0';
            }
            
            // Check branch access
            if (!$this->auth->canAccessBranch($branchId)) {
                $errors[] = 'You do not have access to this branch';
            }
            
            if (!empty($errors)) {
                $_SESSION['menu_item_errors'] = $errors;
                $_SESSION['menu_item_data'] = $_POST;
                Utilities::redirect('/menu');
            }
            
            // Handle image upload
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = Utilities::uploadFile($_FILES['image'], UPLOAD_PATH . '/menu_items/', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                if ($uploadResult['success']) {
                    $image = $uploadResult['filename'];
                } else {
                    $errors[] = $uploadResult['message'];
                }
            }
            
            // Create menu item
            $menuItemData = [
                'branch_id' => $branchId,
                'category_id' => $categoryId,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'discount_price' => $discountPrice > 0 ? $discountPrice : null,
                'image' => $image,
                'preparation_time' => $preparationTime,
                'calories' => $calories > 0 ? $calories : null,
                'is_vegetarian' => $isVegetarian,
                'is_vegan' => $isVegan,
                'is_gluten_free' => $isGlutenFree,
                'is_spicy' => $isSpicy,
                'is_available' => $isAvailable,
                'is_featured' => $isFeatured,
                'sort_order' => $sortOrder,
                'status' => $status
            ];
            
            $menuItemId = $this->db->insert('luna_menu_items', $menuItemData);
            
            if ($menuItemId) {
                // Log activity
                Utilities::logActivity($user['id'], 'menu_item_added', "Menu item '$name' added");
                
                $_SESSION['menu_item_success'] = 'Menu item added successfully';
            } else {
                $_SESSION['menu_item_error'] = 'Failed to add menu item';
            }
            
            Utilities::redirect('/menu');
            
        } catch (Exception $e) {
            $this->handleError('Add menu item failed: ' . $e->getMessage());
            $_SESSION['menu_item_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/menu');
        }
    }
    
    /**
     * Update menu item
     */
    public function updateMenuItem() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_menu');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/menu');
            }
            
            $user = $this->auth->getUser();
            
            // Sanitize input
            $menuItemId = (int) ($_POST['menu_item_id'] ?? 0);
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $name = Utilities::sanitize($_POST['name'] ?? '');
            $description = Utilities::sanitize($_POST['description'] ?? '');
            $price = (float) ($_POST['price'] ?? 0);
            $discountPrice = (float) ($_POST['discount_price'] ?? 0);
            $preparationTime = (int) ($_POST['preparation_time'] ?? 0);
            $calories = (int) ($_POST['calories'] ?? 0);
            $isVegetarian = $_POST['is_vegetarian'] ?? 'no';
            $isVegan = $_POST['is_vegan'] ?? 'no';
            $isGlutenFree = $_POST['is_gluten_free'] ?? 'no';
            $isSpicy = $_POST['is_spicy'] ?? 'no';
            $isAvailable = $_POST['is_available'] ?? 'yes';
            $isFeatured = $_POST['is_featured'] ?? 'no';
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            $status = $_POST['status'] ?? 'active';
            
            // Validate input
            $errors = [];
            
            if (empty($menuItemId)) {
                $errors[] = 'Menu item ID is required';
            }
            
            if (empty($categoryId)) {
                $errors[] = 'Category is required';
            }
            
            if (empty($name)) {
                $errors[] = 'Menu item name is required';
            }
            
            if ($price <= 0) {
                $errors[] = 'Price must be greater than 0';
            }
            
            // Get menu item and check access
            $menuItem = $this->getMenuItemById($menuItemId);
            if (!$menuItem) {
                $errors[] = 'Menu item not found';
            } elseif (!$this->auth->canAccessBranch($menuItem['branch_id'])) {
                $errors[] = 'You do not have access to this menu item';
            }
            
            if (!empty($errors)) {
                $_SESSION['menu_item_errors'] = $errors;
                $_SESSION['menu_item_data'] = $_POST;
                Utilities::redirect('/menu');
            }
            
            // Handle image upload
            $image = $menuItem['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Delete old image
                if (!empty($image)) {
                    Utilities::deleteFile(UPLOAD_PATH . '/menu_items/' . $image);
                }
                
                $uploadResult = Utilities::uploadFile($_FILES['image'], UPLOAD_PATH . '/menu_items/', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                if ($uploadResult['success']) {
                    $image = $uploadResult['filename'];
                } else {
                    $errors[] = $uploadResult['message'];
                }
            }
            
            // Update menu item
            $updateData = [
                'category_id' => $categoryId,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'discount_price' => $discountPrice > 0 ? $discountPrice : null,
                'image' => $image,
                'preparation_time' => $preparationTime,
                'calories' => $calories > 0 ? $calories : null,
                'is_vegetarian' => $isVegetarian,
                'is_vegan' => $isVegan,
                'is_gluten_free' => $isGlutenFree,
                'is_spicy' => $isSpicy,
                'is_available' => $isAvailable,
                'is_featured' => $isFeatured,
                'sort_order' => $sortOrder,
                'status' => $status
            ];
            
            $updated = $this->db->update('luna_menu_items', $updateData, 'id = :id', [':id' => $menuItemId]);
            
            if ($updated) {
                // Log activity
                Utilities::logActivity($user['id'], 'menu_item_updated', "Menu item '$name' updated");
                
                $_SESSION['menu_item_success'] = 'Menu item updated successfully';
            } else {
                $_SESSION['menu_item_error'] = 'Failed to update menu item';
            }
            
            Utilities::redirect('/menu');
            
        } catch (Exception $e) {
            $this->handleError('Update menu item failed: ' . $e->getMessage());
            $_SESSION['menu_item_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/menu');
        }
    }
    
    /**
     * Delete menu item
     */
    public function deleteMenuItem() {
        try {
            $this->auth->requireAuth();
            $this->auth->requirePermission('manage_menu');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Utilities::redirect('/menu');
            }
            
            $user = $this->auth->getUser();
            
            // Sanitize input
            $menuItemId = (int) ($_POST['menu_item_id'] ?? 0);
            
            // Validate input
            $errors = [];
            
            if (empty($menuItemId)) {
                $errors[] = 'Menu item ID is required';
            }
            
            // Get menu item and check access
            $menuItem = $this->getMenuItemById($menuItemId);
            if (!$menuItem) {
                $errors[] = 'Menu item not found';
            } elseif (!$this->auth->canAccessBranch($menuItem['branch_id'])) {
                $errors[] = 'You do not have access to this menu item';
            }
            
            if (!empty($errors)) {
                $_SESSION['menu_item_errors'] = $errors;
                Utilities::redirect('/menu');
            }
            
            // Soft delete menu item
            $deleted = $this->db->update('luna_menu_items', ['deleted_at' => date('Y-m-d H:i:s')], 
                'id = :id', [':id' => $menuItemId]);
            
            if ($deleted) {
                // Delete image
                if (!empty($menuItem['image'])) {
                    Utilities::deleteFile(UPLOAD_PATH . '/menu_items/' . $menuItem['image']);
                }
                
                // Log activity
                Utilities::logActivity($user['id'], 'menu_item_deleted', "Menu item '{$menuItem['name']}' deleted");
                
                $_SESSION['menu_item_success'] = 'Menu item deleted successfully';
            } else {
                $_SESSION['menu_item_error'] = 'Failed to delete menu item';
            }
            
            Utilities::redirect('/menu');
            
        } catch (Exception $e) {
            $this->handleError('Delete menu item failed: ' . $e->getMessage());
            $_SESSION['menu_item_error'] = 'An error occurred. Please try again later.';
            Utilities::redirect('/menu');
        }
    }
    
    /**
     * Get category by ID
     */
    private function getCategoryById($categoryId) {
        try {
            $result = $this->db->select('luna_categories', '*', 'id = :id AND deleted_at IS NULL', [':id' => $categoryId]);
            return !empty($result) ? $result[0] : null;
            
        } catch (Exception $e) {
            $this->handleError('Get category by ID failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get menu item by ID
     */
    private function getMenuItemById($menuItemId) {
        try {
            $result = $this->db->select('luna_menu_items', '*', 'id = :id AND deleted_at IS NULL', [':id' => $menuItemId]);
            return !empty($result) ? $result[0] : null;
            
        } catch (Exception $e) {
            $this->handleError('Get menu item by ID failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Handle errors
     */
    private function handleError($message) {
        if (DEBUG_MODE) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Menu Controller Error:</strong> $message<br>";
            echo "</div>";
        }
        
        Utilities::logError('Menu Controller: ' . $message);
    }
}
?>