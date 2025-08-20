<?php
/**
 * Luna Dine Theme System
 * 
 * Handles theme loading, management, and rendering
 */

class Themes {
    private $db;
    private $themes = [];
    private $active_theme = null;
    private $theme_vars = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadThemes();
        $this->setActiveTheme();
    }
    
    /**
     * Load all available themes
     */
    private function loadThemes() {
        if (!ENABLE_THEMES) {
            return;
        }
        
        $theme_dirs = glob(THEME_PATH . '/*', GLOB_ONLYDIR);
        
        foreach ($theme_dirs as $theme_dir) {
            $theme_name = basename($theme_dir);
            $config_file = $theme_dir . '/theme.json';
            
            if (file_exists($config_file)) {
                $config = json_decode(file_get_contents($config_file), true);
                
                if ($config && $this->validateThemeConfig($config)) {
                    $this->themes[$theme_name] = [
                        'name' => $config['name'],
                        'version' => $config['version'],
                        'description' => $config['description'],
                        'author' => $config['author'],
                        'settings' => $config['settings'] ?? [],
                        'path' => $theme_dir,
                        'url' => SITE_URL . '/themes/' . $theme_name,
                        'screenshots' => $this->getThemeScreenshots($theme_dir),
                        'config' => $config
                    ];
                }
            }
        }
    }
    
    /**
     * Validate theme configuration
     */
    private function validateThemeConfig($config) {
        $required_fields = ['name', 'version', 'description', 'author'];
        
        foreach ($required_fields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get theme screenshots
     */
    private function getThemeScreenshots($theme_dir) {
        $screenshots = [];
        $screenshot_files = glob($theme_dir . '/screenshot.{jpg,jpeg,png,gif}', GLOB_BRACE);
        
        foreach ($screenshot_files as $screenshot) {
            $screenshots[] = basename($screenshot);
        }
        
        return $screenshots;
    }
    
    /**
     * Set active theme
     */
    private function setActiveTheme() {
        $active_theme_name = $this->getActiveThemeName();
        
        if (isset($this->themes[$active_theme_name])) {
            $this->active_theme = $this->themes[$active_theme_name];
            $this->loadThemeVars($active_theme_name);
        } else {
            // Fallback to default theme
            $this->active_theme = $this->themes['default'] ?? null;
            if ($this->active_theme) {
                $this->loadThemeVars('default');
            }
        }
    }
    
    /**
     * Get active theme name
     */
    private function getActiveThemeName() {
        try {
            $stmt = $this->db->prepare("SELECT value FROM " . DB_PREFIX . "settings WHERE key = ?");
            $stmt->execute(['active_theme']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['value'] : DEFAULT_THEME;
        } catch (Exception $e) {
            return DEFAULT_THEME;
        }
    }
    
    /**
     * Load theme variables
     */
    private function loadThemeVars($theme_name) {
        $theme = $this->themes[$theme_name];
        
        // Default theme variables
        $this->theme_vars = [
            'primary_color' => '#667eea',
            'secondary_color' => '#764ba2',
            'accent_color' => '#f093fb',
            'background_color' => '#ffffff',
            'text_color' => '#333333',
            'border_color' => '#e2e8f0',
            'font_family' => 'Arial, sans-serif',
            'font_size' => '16px',
            'border_radius' => '8px',
            'shadow' => '0 4px 6px rgba(0, 0, 0, 0.1)',
            'transition' => 'all 0.3s ease'
        ];
        
        // Override with theme settings
        if (isset($theme['settings'])) {
            foreach ($theme['settings'] as $key => $value) {
                $this->theme_vars[$key] = $value;
            }
        }
        
        // Override with custom theme settings from database
        $custom_settings = $this->getThemeSettings($theme_name);
        foreach ($custom_settings as $key => $value) {
            $this->theme_vars[$key] = $value;
        }
    }
    
    /**
     * Get theme settings from database
     */
    private function getThemeSettings($theme_name) {
        $settings = [];
        
        try {
            $stmt = $this->db->prepare("SELECT key, value FROM " . DB_PREFIX . "settings WHERE key LIKE ?");
            $stmt->execute(['theme_' . $theme_name . '_%']);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $result) {
                $key = str_replace('theme_' . $theme_name . '_', '', $result['key']);
                $settings[$key] = $result['value'];
            }
        } catch (Exception $e) {
            error_log("Theme settings retrieval failed: " . $e->getMessage());
        }
        
        return $settings;
    }
    
    /**
     * Get all available themes
     */
    public function getAllThemes() {
        return $this->themes;
    }
    
    /**
     * Get active theme
     */
    public function getActiveTheme() {
        return $this->active_theme;
    }
    
    /**
     * Get theme by name
     */
    public function getTheme($name) {
        return isset($this->themes[$name]) ? $this->themes[$name] : null;
    }
    
    /**
     * Set active theme
     */
    public function setActiveTheme($theme_name) {
        if (!isset($this->themes[$theme_name])) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("INSERT OR REPLACE INTO " . DB_PREFIX . "settings (key, value, type, is_system) VALUES (?, ?, 'string', 'no')");
            $result = $stmt->execute(['active_theme', $theme_name]);
            
            if ($result) {
                $this->active_theme = $this->themes[$theme_name];
                $this->loadThemeVars($theme_name);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Active theme setting failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get theme variable
     */
    public function getVar($key, $default = null) {
        return isset($this->theme_vars[$key]) ? $this->theme_vars[$key] : $default;
    }
    
    /**
     * Get all theme variables
     */
    public function getVars() {
        return $this->theme_vars;
    }
    
    /**
     * Set theme variable
     */
    public function setVar($key, $value) {
        if (!$this->active_theme) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("INSERT OR REPLACE INTO " . DB_PREFIX . "settings (key, value, type, is_system) VALUES (?, ?, 'string', 'no')");
            $result = $stmt->execute(['theme_' . $this->active_theme['name'] . '_' . $key, $value]);
            
            if ($result) {
                $this->theme_vars[$key] = $value;
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Theme variable setting failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update theme settings
     */
    public function updateSettings($settings) {
        if (!$this->active_theme) {
            return false;
        }
        
        foreach ($settings as $key => $value) {
            $this->setVar($key, $value);
        }
        
        return true;
    }
    
    /**
     * Get theme CSS
     */
    public function getCSS() {
        $css = '';
        
        // Generate CSS from theme variables
        $css .= ":root {\n";
        foreach ($this->theme_vars as $key => $value) {
            $css .= "    --{$key}: {$value};\n";
        }
        $css .= "}\n\n";
        
        // Add custom theme CSS if exists
        if ($this->active_theme) {
            $css_file = $this->active_theme['path'] . '/css/style.css';
            if (file_exists($css_file)) {
                $css .= file_get_contents($css_file);
            }
        }
        
        return $css;
    }
    
    /**
     * Get theme JavaScript
     */
    public function getJavaScript() {
        $js = '';
        
        if ($this->active_theme) {
            $js_file = $this->active_theme['path'] . '/js/script.js';
            if (file_exists($js_file)) {
                $js .= file_get_contents($js_file);
            }
        }
        
        return $js;
    }
    
    /**
     * Render theme template
     */
    public function renderTemplate($template_name, $data = []) {
        if (!$this->active_theme) {
            return '';
        }
        
        $template_file = $this->active_theme['path'] . '/templates/' . $template_name . '.php';
        
        if (file_exists($template_file)) {
            // Extract data variables
            extract($data);
            
            // Start output buffering
            ob_start();
            
            // Include template file
            include $template_file;
            
            // Get buffered content
            $content = ob_get_clean();
            
            return $content;
        }
        
        return '';
    }
    
    /**
     * Get theme template
     */
    public function getTemplate($template_name) {
        if (!$this->active_theme) {
            return '';
        }
        
        $template_file = $this->active_theme['path'] . '/templates/' . $template_name . '.php';
        
        if (file_exists($template_file)) {
            return file_get_contents($template_file);
        }
        
        return '';
    }
    
    /**
     * Check if theme has template
     */
    public function hasTemplate($template_name) {
        if (!$this->active_theme) {
            return false;
        }
        
        $template_file = $this->active_theme['path'] . '/templates/' . $template_name . '.php';
        return file_exists($template_file);
    }
    
    /**
     * Get theme asset URL
     */
    public function getAssetURL($asset_path) {
        if (!$this->active_theme) {
            return SITE_URL . '/assets/' . $asset_path;
        }
        
        return $this->active_theme['url'] . '/' . $asset_path;
    }
    
    /**
     * Get theme template path
     */
    public function getTemplatePath($template_name) {
        if (!$this->active_theme) {
            return null;
        }
        
        $template_file = $this->active_theme['path'] . '/templates/' . $template_name . '.php';
        return file_exists($template_file) ? $template_file : null;
    }
    
    /**
     * Install theme
     */
    public function installTheme($theme_name) {
        if (!isset($this->themes[$theme_name])) {
            return false;
        }
        
        $theme = $this->themes[$theme_name];
        $install_file = $theme['path'] . '/install.php';
        
        if (file_exists($install_file)) {
            require_once $install_file;
            
            if (method_exists($theme_name, 'install')) {
                return $theme_name::install();
            }
        }
        
        return true;
    }
    
    /**
     * Uninstall theme
     */
    public function uninstallTheme($theme_name) {
        if (!isset($this->themes[$theme_name])) {
            return false;
        }
        
        // Don't allow uninstalling active theme
        if ($this->active_theme && $this->active_theme['name'] === $theme_name) {
            return false;
        }
        
        $theme = $this->themes[$theme_name];
        $uninstall_file = $theme['path'] . '/uninstall.php';
        
        if (file_exists($uninstall_file)) {
            require_once $uninstall_file;
            
            if (method_exists($theme_name, 'uninstall')) {
                return $theme_name::uninstall();
            }
        }
        
        // Remove theme settings
        try {
            $stmt = $this->db->prepare("DELETE FROM " . DB_PREFIX . "settings WHERE key LIKE ?");
            $stmt->execute(['theme_' . $theme_name . '_%']);
        } catch (Exception $e) {
            error_log("Theme settings deletion failed: " . $e->getMessage());
        }
        
        return true;
    }
    
    /**
     * Get theme info
     */
    public function getThemeInfo($theme_name) {
        return isset($this->themes[$theme_name]) ? $this->themes[$theme_name] : null;
    }
    
    /**
     * Get theme screenshot URL
     */
    public function getScreenshotURL($theme_name, $screenshot = 'screenshot.png') {
        if (!isset($this->themes[$theme_name])) {
            return '';
        }
        
        $theme = $this->themes[$theme_name];
        return $theme['url'] . '/' . $screenshot;
    }
    
    /**
     * Generate theme CSS variables
     */
    public function generateCSSVariables() {
        $css = ":root {\n";
        
        foreach ($this->theme_vars as $key => $value) {
            $css .= "    --{$key}: {$value};\n";
        }
        
        $css .= "}\n";
        
        return $css;
    }
    
    /**
     * Apply theme to HTML
     */
    public function applyTheme() {
        $css = $this->getCSS();
        $js = $this->getJavaScript();
        
        return [
            'css' => $css,
            'js' => $js,
            'vars' => $this->theme_vars
        ];
    }
}
?>