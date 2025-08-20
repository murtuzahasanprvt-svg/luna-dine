-- Luna Dine Database Schema
-- SQLite Database for Multi-branch Restaurant Management System

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- Users table
CREATE TABLE IF NOT EXISTS luna_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    branch_id INTEGER,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    phone TEXT,
    password TEXT NOT NULL,
    avatar TEXT,
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'suspended')),
    last_login DATETIME,
    last_activity DATETIME,
    login_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME,
    FOREIGN KEY (role_id) REFERENCES luna_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES luna_branches(id) ON DELETE SET NULL
);

-- Roles table
CREATE TABLE IF NOT EXISTS luna_roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    display_name TEXT NOT NULL,
    description TEXT,
    permissions TEXT, -- JSON format
    is_system TEXT DEFAULT 'no' CHECK(is_system IN ('yes', 'no')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Branches table
CREATE TABLE IF NOT EXISTS luna_branches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    code TEXT NOT NULL UNIQUE,
    address TEXT NOT NULL,
    phone TEXT NOT NULL,
    email TEXT,
    latitude REAL,
    longitude REAL,
    opening_time TEXT,
    closing_time TEXT,
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'maintenance')),
    tax_rate REAL DEFAULT 0.0,
    delivery_fee REAL DEFAULT 0.0,
    min_order_amount REAL DEFAULT 0.0,
    max_delivery_distance REAL DEFAULT 0.0,
    description TEXT,
    image TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME
);

-- User Branches table (for users who can access multiple branches)
CREATE TABLE IF NOT EXISTS luna_user_branches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    branch_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES luna_users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES luna_branches(id) ON DELETE CASCADE,
    UNIQUE(user_id, branch_id)
);

-- Categories table
CREATE TABLE IF NOT EXISTS luna_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    image TEXT,
    sort_order INTEGER DEFAULT 0,
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME,
    FOREIGN KEY (branch_id) REFERENCES luna_branches(id) ON DELETE CASCADE
);

-- Menu Items table
CREATE TABLE IF NOT EXISTS luna_menu_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    price REAL NOT NULL,
    discount_price REAL,
    image TEXT,
    preparation_time INTEGER DEFAULT 0, -- in minutes
    calories INTEGER,
    is_vegetarian TEXT DEFAULT 'no' CHECK(is_vegetarian IN ('yes', 'no')),
    is_vegan TEXT DEFAULT 'no' CHECK(is_vegan IN ('yes', 'no')),
    is_gluten_free TEXT DEFAULT 'no' CHECK(is_gluten_free IN ('yes', 'no')),
    is_spicy TEXT DEFAULT 'no' CHECK(is_spicy IN ('yes', 'no')),
    is_available TEXT DEFAULT 'yes' CHECK(is_available IN ('yes', 'no')),
    is_featured TEXT DEFAULT 'no' CHECK(is_featured IN ('yes', 'no')),
    sort_order INTEGER DEFAULT 0,
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME,
    FOREIGN KEY (branch_id) REFERENCES luna_branches(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES luna_categories(id) ON DELETE CASCADE
);

-- Menu Item Variations table
CREATE TABLE IF NOT EXISTS luna_menu_item_variations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    menu_item_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    price REAL NOT NULL,
    description TEXT,
    sort_order INTEGER DEFAULT 0,
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_item_id) REFERENCES luna_menu_items(id) ON DELETE CASCADE
);

-- Add-ons table
CREATE TABLE IF NOT EXISTS luna_addons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    price REAL NOT NULL,
    sort_order INTEGER DEFAULT 0,
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME,
    FOREIGN KEY (branch_id) REFERENCES luna_branches(id) ON DELETE CASCADE
);

-- Menu Item Add-ons table
CREATE TABLE IF NOT EXISTS luna_menu_item_addons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    menu_item_id INTEGER NOT NULL,
    addon_id INTEGER NOT NULL,
    is_required TEXT DEFAULT 'no' CHECK(is_required IN ('yes', 'no')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_item_id) REFERENCES luna_menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (addon_id) REFERENCES luna_addons(id) ON DELETE CASCADE,
    UNIQUE(menu_item_id, addon_id)
);

-- Tables table
CREATE TABLE IF NOT EXISTS luna_tables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    branch_id INTEGER NOT NULL,
    number TEXT NOT NULL,
    qr_code TEXT NOT NULL UNIQUE,
    capacity INTEGER DEFAULT 4,
    status TEXT DEFAULT 'available' CHECK(status IN ('available', 'occupied', 'reserved', 'maintenance')),
    location TEXT,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME,
    FOREIGN KEY (branch_id) REFERENCES luna_branches(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE IF NOT EXISTS luna_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_number TEXT NOT NULL UNIQUE,
    branch_id INTEGER NOT NULL,
    table_id INTEGER,
    user_id INTEGER,
    customer_name TEXT,
    customer_phone TEXT,
    customer_email TEXT,
    customer_address TEXT,
    order_type TEXT DEFAULT 'dine_in' CHECK(order_type IN ('dine_in', 'takeaway', 'delivery')),
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled')),
    payment_status TEXT DEFAULT 'pending' CHECK(payment_status IN ('pending', 'paid', 'failed', 'refunded')),
    payment_method TEXT DEFAULT 'cash' CHECK(payment_method IN ('cash', 'card', 'mobile_banking')),
    subtotal REAL NOT NULL,
    tax REAL DEFAULT 0.0,
    discount REAL DEFAULT 0.0,
    delivery_fee REAL DEFAULT 0.0,
    total REAL NOT NULL,
    notes TEXT,
    estimated_delivery_time DATETIME,
    actual_delivery_time DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME,
    FOREIGN KEY (branch_id) REFERENCES luna_branches(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES luna_tables(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES luna_users(id) ON DELETE SET NULL
);

-- Order Items table
CREATE TABLE IF NOT EXISTS luna_order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    menu_item_id INTEGER NOT NULL,
    variation_id INTEGER,
    quantity INTEGER NOT NULL,
    unit_price REAL NOT NULL,
    total_price REAL NOT NULL,
    notes TEXT,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'preparing', 'ready', 'cancelled')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES luna_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES luna_menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (variation_id) REFERENCES luna_menu_item_variations(id) ON DELETE SET NULL
);

-- Order Item Add-ons table
CREATE TABLE IF NOT EXISTS luna_order_item_addons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_item_id INTEGER NOT NULL,
    addon_id INTEGER NOT NULL,
    price REAL NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_item_id) REFERENCES luna_order_items(id) ON DELETE CASCADE,
    FOREIGN KEY (addon_id) REFERENCES luna_addons(id) ON DELETE CASCADE
);

-- Order Status History table
CREATE TABLE IF NOT EXISTS luna_order_status_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    status TEXT NOT NULL,
    notes TEXT,
    user_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES luna_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES luna_users(id) ON DELETE SET NULL
);

-- Settings table
CREATE TABLE IF NOT EXISTS luna_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key TEXT NOT NULL UNIQUE,
    value TEXT,
    description TEXT,
    type TEXT DEFAULT 'string' CHECK(type IN ('string', 'integer', 'float', 'boolean', 'json')),
    is_system TEXT DEFAULT 'no' CHECK(is_system IN ('yes', 'no')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Activity Logs table
CREATE TABLE IF NOT EXISTS luna_activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    description TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES luna_users(id) ON DELETE SET NULL
);

-- Login Attempts table
CREATE TABLE IF NOT EXISTS luna_login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    success TEXT DEFAULT 'no' CHECK(success IN ('yes', 'no')),
    ip_address TEXT,
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- User Tokens table (for remember me and API tokens)
CREATE TABLE IF NOT EXISTS luna_user_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    type TEXT DEFAULT 'remember_me' CHECK(type IN ('remember_me', 'api', 'reset_password')),
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    used_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES luna_users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE IF NOT EXISTS luna_notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    type TEXT NOT NULL,
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    data TEXT, -- JSON format
    is_read TEXT DEFAULT 'no' CHECK(is_read IN ('yes', 'no')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES luna_users(id) ON DELETE SET NULL
);

-- Reviews table
CREATE TABLE IF NOT EXISTS luna_reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    branch_id INTEGER NOT NULL,
    user_id INTEGER,
    customer_name TEXT,
    rating INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
    review TEXT,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES luna_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES luna_branches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES luna_users(id) ON DELETE SET NULL
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_email ON luna_users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON luna_users(role_id);
CREATE INDEX IF NOT EXISTS idx_users_branch ON luna_users(branch_id);
CREATE INDEX IF NOT EXISTS idx_users_status ON luna_users(status);
CREATE INDEX IF NOT EXISTS idx_branches_code ON luna_branches(code);
CREATE INDEX IF NOT EXISTS idx_branches_status ON luna_branches(status);
CREATE INDEX IF NOT EXISTS idx_categories_branch ON luna_categories(branch_id);
CREATE INDEX IF NOT EXISTS idx_menu_items_branch ON luna_menu_items(branch_id);
CREATE INDEX IF NOT EXISTS idx_menu_items_category ON luna_menu_items(category_id);
CREATE INDEX IF NOT EXISTS idx_menu_items_status ON luna_menu_items(status);
CREATE INDEX IF NOT EXISTS idx_orders_number ON luna_orders(order_number);
CREATE INDEX IF NOT EXISTS idx_orders_branch ON luna_orders(branch_id);
CREATE INDEX IF NOT EXISTS idx_orders_status ON luna_orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_created ON luna_orders(created_at);
CREATE INDEX IF NOT EXISTS idx_order_items_order ON luna_order_items(order_id);
CREATE INDEX IF NOT EXISTS idx_tables_branch ON luna_tables(branch_id);
CREATE INDEX IF NOT EXISTS idx_tables_qr ON luna_tables(qr_code);
CREATE INDEX IF NOT EXISTS idx_activity_logs_user ON luna_activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_action ON luna_activity_logs(action);
CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON luna_login_attempts(email);
CREATE INDEX IF NOT EXISTS idx_login_attempts_created ON luna_login_attempts(created_at);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON luna_notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_reviews_order ON luna_reviews(order_id);
CREATE INDEX IF NOT EXISTS idx_reviews_branch ON luna_reviews(branch_id);

-- Insert default roles
INSERT OR IGNORE INTO luna_roles (name, display_name, description, permissions, is_system) VALUES 
('super_admin', 'Super Admin', 'System administrator with full access', '{"access_admin": true, "manage_users": true, "manage_branches": true, "manage_menu": true, "manage_orders": true, "manage_settings": true, "manage_addons": true, "manage_themes": true, "view_reports": true, "system_access": true}', 'yes'),
('owner', 'Owner', 'Restaurant owner with multi-branch access', '{"access_admin": true, "manage_users": true, "manage_branches": true, "manage_menu": true, "manage_orders": true, "manage_settings": true, "view_reports": true}', 'yes'),
('branch_manager', 'Branch Manager', 'Branch manager with single-branch access', '{"access_admin": true, "manage_staff": true, "manage_menu": true, "manage_orders": true, "view_reports": true}', 'yes'),
('chef', 'Chef', 'Kitchen staff for food preparation', '{"access_admin": true, "manage_orders": true, "view_kitchen": true}', 'yes'),
('waiter', 'Waiter', 'Restaurant staff for customer service', '{"access_admin": true, "manage_orders": true, "view_tables": true}', 'yes'),
('staff', 'Staff', 'General restaurant staff', '{"access_admin": true, "view_orders": true}', 'yes');

-- Insert default settings
INSERT OR IGNORE INTO luna_settings (key, value, description, type, is_system) VALUES 
('site_name', 'Luna Dine', 'Website name', 'string', 'yes'),
('site_email', 'info@lunadine.com', 'Website email', 'string', 'yes'),
('site_phone', '+880 1234-567890', 'Website phone', 'string', 'yes'),
('currency', 'BDT', 'Currency code', 'string', 'yes'),
('currency_symbol', '৳', 'Currency symbol', 'string', 'yes'),
('currency_position', 'before', 'Currency position', 'string', 'yes'),
('tax_rate', '0.00', 'Default tax rate', 'float', 'yes'),
('delivery_fee', '0.00', 'Default delivery fee', 'float', 'yes'),
('min_order_amount', '0.00', 'Minimum order amount', 'float', 'yes'),
('max_delivery_distance', '0.00', 'Maximum delivery distance in km', 'float', 'yes'),
('order_prefix', 'LD', 'Order number prefix', 'string', 'yes'),
('table_prefix', 'TBL', 'Table number prefix', 'string', 'yes'),
('qr_size', '300', 'QR code size', 'integer', 'yes'),
('qr_error_correction', 'H', 'QR code error correction level', 'string', 'yes'),
('qr_margin', '4', 'QR code margin', 'integer', 'yes'),
('items_per_page', '20', 'Items per page for pagination', 'integer', 'yes'),
('max_items_per_page', '100', 'Maximum items per page', 'integer', 'yes'),
('enable_cache', 'true', 'Enable caching', 'boolean', 'yes'),
('cache_lifetime', '3600', 'Cache lifetime in seconds', 'integer', 'yes'),
('enable_addons', 'true', 'Enable addon system', 'boolean', 'yes'),
('enable_themes', 'true', 'Enable theme system', 'boolean', 'yes'),
('api_enabled', 'true', 'Enable API', 'boolean', 'yes'),
('api_rate_limit', '100', 'API rate limit per minute', 'integer', 'yes'),
('api_key_lifetime', '86400', 'API key lifetime in seconds', 'integer', 'yes'),
('maintenance_mode', 'false', 'Maintenance mode', 'boolean', 'yes'),
('auto_backup', 'true', 'Enable automatic backup', 'boolean', 'yes'),
('backup_interval', '86400', 'Backup interval in seconds', 'integer', 'yes'),
('max_backups', '7', 'Maximum number of backups', 'integer', 'yes'),
('default_language', 'en', 'Default language', 'string', 'yes'),
('facebook_url', 'https://facebook.com/lunadine', 'Facebook URL', 'string', 'yes'),
('twitter_url', 'https://twitter.com/lunadine', 'Twitter URL', 'string', 'yes'),
('instagram_url', 'https://instagram.com/lunadine', 'Instagram URL', 'string', 'yes'),
('youtube_url', 'https://youtube.com/lunadine', 'YouTube URL', 'string', 'yes');

-- Create triggers for updating timestamps
CREATE TRIGGER IF NOT EXISTS update_users_timestamp 
AFTER UPDATE ON luna_users
BEGIN
    UPDATE luna_users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_branches_timestamp 
AFTER UPDATE ON luna_branches
BEGIN
    UPDATE luna_branches SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_categories_timestamp 
AFTER UPDATE ON luna_categories
BEGIN
    UPDATE luna_categories SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_menu_items_timestamp 
AFTER UPDATE ON luna_menu_items
BEGIN
    UPDATE luna_menu_items SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_menu_item_variations_timestamp 
AFTER UPDATE ON luna_menu_item_variations
BEGIN
    UPDATE luna_menu_item_variations SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_addons_timestamp 
AFTER UPDATE ON luna_addons
BEGIN
    UPDATE luna_addons SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_tables_timestamp 
AFTER UPDATE ON luna_tables
BEGIN
    UPDATE luna_tables SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_orders_timestamp 
AFTER UPDATE ON luna_orders
BEGIN
    UPDATE luna_orders SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_order_items_timestamp 
AFTER UPDATE ON luna_order_items
BEGIN
    UPDATE luna_order_items SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_settings_timestamp 
AFTER UPDATE ON luna_settings
BEGIN
    UPDATE luna_settings SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_reviews_timestamp 
AFTER UPDATE ON luna_reviews
BEGIN
    UPDATE luna_reviews SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;