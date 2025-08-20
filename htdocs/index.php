<?php
/**
 * Luna Dine - Advanced QR Menu Website for Multibranch Restaurant
 * 
 * Project Name: Luna Dine
 * Main Entry Point
 * 
 * @author Luna Dine Development Team
 * @version 1.0.0
 */

// Define constants
define('LUNA_DINE_VERSION', '1.0.0');
define('LUNA_DINE_ROOT', __DIR__);
define('LUNA_DINE_CORE', LUNA_DINE_ROOT . '/core');
define('LUNA_DINE_ADDONS', LUNA_DINE_ROOT . '/addons');
define('LUNA_DINE_THEMES', LUNA_DINE_ROOT . '/themes');
define('LUNA_DINE_ASSETS', LUNA_DINE_ROOT . '/assets');
define('LUNA_DINE_DATABASE', LUNA_DINE_ROOT . '/database');
define('LUNA_DINE_UPLOADS', LUNA_DINE_ROOT . '/uploads');

// Error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Set default timezone
date_default_timezone_set('Asia/Dhaka');

// Start session
session_start();

// Load configuration
require_once LUNA_DINE_CORE . '/config/config.php';

// Load core libraries
require_once LUNA_DINE_CORE . '/helpers/Database.php';
require_once LUNA_DINE_CORE . '/helpers/Auth.php';
require_once LUNA_DINE_CORE . '/helpers/Utilities.php';

// Initialize database connection
$db = new Database();

// Initialize authentication
$auth = new Auth($db);

// Basic routing
$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Remove query string from request
$request = strtok($request, '?');

// Simple router
switch ($request) {
    case '/':
    case '/home':
        require_once LUNA_DINE_CORE . '/controllers/HomeController.php';
        $controller = new HomeController($db, $auth);
        $controller->index();
        break;
        
    case '/admin':
        require_once LUNA_DINE_CORE . '/controllers/AdminController.php';
        $controller = new AdminController($db, $auth);
        $controller->index();
        break;
        
    case '/login':
        require_once LUNA_DINE_CORE . '/controllers/AuthController.php';
        $controller = new AuthController($db, $auth);
        $controller->login();
        break;
        
    case '/logout':
        require_once LUNA_DINE_CORE . '/controllers/AuthController.php';
        $controller = new AuthController($db, $auth);
        $controller->logout();
        break;
        
    case '/menu':
        require_once LUNA_DINE_CORE . '/controllers/MenuController.php';
        $controller = new MenuController($db, $auth);
        $controller->index();
        break;
        
    case '/order':
        require_once LUNA_DINE_CORE . '/controllers/OrderController.php';
        $controller = new OrderController($db, $auth);
        $controller->index();
        break;
        
    default:
        // 404 Not Found
        http_response_code(404);
        require_once LUNA_DINE_CORE . '/views/errors/404.php';
        break;
}
?>