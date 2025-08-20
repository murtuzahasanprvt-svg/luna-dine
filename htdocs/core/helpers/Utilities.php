<?php
/**
 * Luna Dine Utilities Helper
 * 
 * General utility functions for Luna Dine
 */

class Utilities {
    
    /**
     * Generate random string
     */
    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }
    
    /**
     * Generate unique ID
     */
    public static function generateUniqueId($prefix = '') {
        return $prefix . uniqid() . '_' . self::generateRandomString(8);
    }
    
    /**
     * Generate order number
     */
    public static function generateOrderNumber() {
        return ORDER_PREFIX . date('YmdHis') . rand(100, 999);
    }
    
    /**
     * Generate table number
     */
    public static function generateTableNumber($branchId) {
        return TABLE_PREFIX . $branchId . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Format currency
     */
    public static function formatCurrency($amount) {
        $formatted = number_format($amount, 2, '.', ',');
        
        if (CURRENCY_POSITION === 'before') {
            return CURRENCY_SYMBOL . $formatted;
        } else {
            return $formatted . CURRENCY_SYMBOL;
        }
    }
    
    /**
     * Format date
     */
    public static function formatDate($date, $format = 'Y-m-d H:i:s') {
        return date($format, strtotime($date));
    }
    
    /**
     * Get time ago
     */
    public static function timeAgo($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' minutes ago';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' hours ago';
        } elseif ($diff < 604800) {
            return floor($diff / 86400) . ' days ago';
        } elseif ($diff < 2592000) {
            return floor($diff / 604800) . ' weeks ago';
        } elseif ($diff < 31536000) {
            return floor($diff / 2592000) . ' months ago';
        } else {
            return floor($diff / 31536000) . ' years ago';
        }
    }
    
    /**
     * Sanitize input
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::sanitize($value);
            }
        } else {
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            $input = trim($input);
            $input = stripslashes($input);
        }
        
        return $input;
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * Validate phone number (Bangladesh format)
     */
    public static function validatePhone($phone) {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it's a valid Bangladesh phone number
        return (preg_match('/^(01[3-9]\d{8})$/', $phone) || 
                preg_match('/^(8801[3-9]\d{8})$/', $phone));
    }
    
    /**
     * Validate URL
     */
    public static function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
    
    /**
     * Create slug from string
     */
    public static function createSlug($string) {
        $slug = strtolower($string);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', ' ', $slug);
        $slug = preg_replace('/\s/', '-', $slug);
        return $slug;
    }
    
    /**
     * Truncate text
     */
    public static function truncate($text, $length = 100, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . $suffix;
    }
    
    /**
     * Get file extension
     */
    public static function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Check if file is image
     */
    public static function isImage($filename) {
        $allowedTypes = unserialize(ALLOWED_IMAGE_TYPES);
        $extension = self::getFileExtension($filename);
        return in_array($extension, $allowedTypes);
    }
    
    /**
     * Format file size
     */
    public static function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    /**
     * Upload file
     */
    public static function uploadFile($file, $destination, $allowedTypes = []) {
        try {
            // Check if file was uploaded
            if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
                return ['success' => false, 'message' => 'No file uploaded.'];
            }
            
            // Check file size
            if ($file['size'] > MAX_FILE_SIZE) {
                return ['success' => false, 'message' => 'File size exceeds limit.'];
            }
            
            // Check file type
            $extension = self::getFileExtension($file['name']);
            if (!empty($allowedTypes) && !in_array($extension, $allowedTypes)) {
                return ['success' => false, 'message' => 'File type not allowed.'];
            }
            
            // Create destination directory if it doesn't exist
            $dir = dirname($destination);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Generate unique filename
            $filename = self::generateUniqueId() . '.' . $extension;
            $destination = $dir . '/' . $filename;
            
            // Move file
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                return ['success' => true, 'message' => 'File uploaded successfully.', 'filename' => $filename];
            } else {
                return ['success' => false, 'message' => 'File upload failed.'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Upload error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete file
     */
    public static function deleteFile($filepath) {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return true;
    }
    
    /**
     * Send email
     */
    public static function sendEmail($to, $subject, $message, $headers = '') {
        try {
            if (empty($headers)) {
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">" . "\r\n";
            }
            
            return mail($to, $subject, $message, $headers);
            
        } catch (Exception $e) {
            self::logError('Email send failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log error
     */
    public static function logError($message) {
        if (LOG_ERRORS) {
            $logMessage = date('Y-m-d H:i:s') . ' - ' . $message . "\n";
            error_log($logMessage, 3, ERROR_LOG_PATH);
        }
    }
    
    /**
     * Log activity
     */
    public static function logActivity($userId, $action, $description = '') {
        try {
            global $db;
            
            $sql = "INSERT INTO " . DB_PREFIX . "activity_logs (user_id, action, description, ip_address, user_agent) 
                    VALUES (:user_id, :action, :description, :ip_address, :user_agent)";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':action', $action);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
            $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            self::logError('Activity log failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get client IP
     */
    public static function getClientIp() {
        $ipaddress = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }
        
        return $ipaddress;
    }
    
    /**
     * Get browser info
     */
    public static function getBrowserInfo() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $browser = "Unknown Browser";
        $browser_version = "";
        
        // Browser detection
        if (preg_match('/MSIE/i', $user_agent) && !preg_match('/Opera/i', $user_agent)) {
            $browser = "Internet Explorer";
            $browser_version = "MSIE";
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            $browser = "Firefox";
        } elseif (preg_match('/Chrome/i', $user_agent)) {
            $browser = "Chrome";
        } elseif (preg_match('/Safari/i', $user_agent)) {
            $browser = "Safari";
        } elseif (preg_match('/Opera/i', $user_agent)) {
            $browser = "Opera";
        } elseif (preg_match('/Netscape/i', $user_agent)) {
            $browser = "Netscape";
        }
        
        return [
            'browser' => $browser,
            'user_agent' => $user_agent
        ];
    }
    
    /**
     * Get operating system
     */
    public static function getOperatingSystem() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        $os_platform = "Unknown OS Platform";
        
        $os_array = [
            '/windows nt 10/i' => 'Windows 10',
            '/windows nt 6.3/i' => 'Windows 8.1',
            '/windows nt 6.2/i' => 'Windows 8',
            '/windows nt 6.1/i' => 'Windows 7',
            '/windows nt 6.0/i' => 'Windows Vista',
            '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
            '/windows nt 5.1/i' => 'Windows XP',
            '/windows xp/i' => 'Windows XP',
            '/windows nt 5.0/i' => 'Windows 2000',
            '/windows me/i' => 'Windows ME',
            '/win98/i' => 'Windows 98',
            '/win95/i' => 'Windows 95',
            '/win16/i' => 'Windows 3.11',
            '/macintosh|mac os x/i' => 'Mac OS X',
            '/mac_powerpc/i' => 'Mac OS 9',
            '/linux/i' => 'Linux',
            '/ubuntu/i' => 'Ubuntu',
            '/iphone/i' => 'iPhone',
            '/ipod/i' => 'iPod',
            '/ipad/i' => 'iPad',
            '/android/i' => 'Android',
            '/blackberry/i' => 'BlackBerry',
            '/webos/i' => 'Mobile'
        ];
        
        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os_platform = $value;
                break;
            }
        }
        
        return $os_platform;
    }
    
    /**
     * Generate QR code
     */
    public static function generateQRCode($data, $filename = null) {
        try {
            if (!$filename) {
                $filename = self::generateUniqueId('qr_') . '.png';
            }
            
            $filepath = UPLOAD_PATH . '/qrcodes/' . $filename;
            
            // Create directory if it doesn't exist
            $dir = dirname($filepath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Using Google Charts API for QR code generation
            $url = "https://chart.googleapis.com/chart?chs=" . QR_SIZE . "x" . QR_SIZE . "&cht=qr&chl=" . urlencode($data) . "&choe=UTF-8&chld=" . QR_ERROR_CORRECTION . "|" . QR_MARGIN;
            
            // Download QR code image
            $imageData = file_get_contents($url);
            if ($imageData) {
                file_put_contents($filepath, $imageData);
                return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
            }
            
            return ['success' => false, 'message' => 'Failed to generate QR code.'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'QR code generation failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create pagination
     */
    public static function createPagination($totalItems, $itemsPerPage = ITEMS_PER_PAGE, $currentPage = 1) {
        $totalPages = ceil($totalItems / $itemsPerPage);
        $currentPage = max(1, min($currentPage, $totalPages));
        
        $pagination = [
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'offset' => ($currentPage - 1) * $itemsPerPage,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'previous_page' => $currentPage - 1,
            'next_page' => $currentPage + 1,
            'pages' => []
        ];
        
        // Generate page numbers
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++) {
            $pagination['pages'][] = [
                'number' => $i,
                'is_current' => $i == $currentPage
            ];
        }
        
        return $pagination;
    }
    
    /**
     * Clean array
     */
    public static function cleanArray($array) {
        return array_filter($array, function($value) {
            return $value !== null && $value !== '' && $value !== false;
        });
    }
    
    /**
     * Array to CSV
     */
    public static function arrayToCsv($data, $filename = 'export.csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }
        
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * JSON response
     */
    public static function jsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
    
    /**
     * Redirect
     */
    public static function redirect($url, $statusCode = 302) {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }
    
    /**
     * Get current URL
     */
    public static function getCurrentUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        
        return $protocol . '://' . $host . $uri;
    }
    
    /**
     * Is AJAX request
     */
    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Get language from browser
     */
    public static function getBrowserLanguage() {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            return substr($languages[0], 0, 2);
        }
        return DEFAULT_LANGUAGE;
    }
    
    /**
     * Translate text
     */
    public static function translate($key, $lang = null) {
        if (!$lang) {
            $lang = isset($_SESSION['language']) ? $_SESSION['language'] : DEFAULT_LANGUAGE;
        }
        
        $translations = self::getTranslations($lang);
        
        return isset($translations[$key]) ? $translations[$key] : $key;
    }
    
    /**
     * Get translations
     */
    private static function getTranslations($lang) {
        $translationFile = LUNA_DINE_ROOT . '/core/lang/' . $lang . '.php';
        
        if (file_exists($translationFile)) {
            return require $translationFile;
        }
        
        return [];
    }
}
?>