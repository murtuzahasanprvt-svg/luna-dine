<?php
/**
 * Luna Dine Addon System
 * 
 * Handles addon loading, management, and execution
 */

class Addons {
    private $db;
    private $addons = [];
    private $hooks = [];
    private $loaded_addons = [];
    
    public function __construct($db) {
        $this->db = $db;
        $this->loadAddons();
    }
    
    /**
     * Load all available addons
     */
    private function loadAddons() {
        if (!ENABLE_ADDONS) {
            return;
        }
        
        $addon_dirs = glob(ADDON_PATH . '/*', GLOB_ONLYDIR);
        
        foreach ($addon_dirs as $addon_dir) {
            $addon_name = basename($addon_dir);
            $config_file = $addon_dir . '/addon.json';
            
            if (file_exists($config_file)) {
                $config = json_decode(file_get_contents($config_file), true);
                
                if ($config && $this->validateAddonConfig($config)) {
                    $this->addons[$addon_name] = [
                        'name' => $config['name'],
                        'version' => $config['version'],
                        'description' => $config['description'],
                        'author' => $config['author'],
                        'dependencies' => $config['dependencies'] ?? [],
                        'hooks' => $config['hooks'] ?? [],
                        'path' => $addon_dir,
                        'enabled' => $this->isAddonEnabled($addon_name),
                        'config' => $config
                    ];
                    
                    if ($this->addons[$addon_name]['enabled']) {
                        $this->loadAddon($addon_name);
                    }
                }
            }
        }
    }
    
    /**
     * Validate addon configuration
     */
    private function validateAddonConfig($config) {
        $required_fields = ['name', 'version', 'description', 'author'];
        
        foreach ($required_fields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if addon is enabled
     */
    private function isAddonEnabled($addon_name) {
        try {
            $stmt = $this->db->prepare("SELECT value FROM " . DB_PREFIX . "settings WHERE key = ?");
            $stmt->execute(['addon_' . $addon_name . '_enabled']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['value'] === 'true';
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Load a specific addon
     */
    private function loadAddon($addon_name) {
        $addon = $this->addons[$addon_name];
        $main_file = $addon['path'] . '/' . $addon_name . '.php';
        
        if (file_exists($main_file)) {
            require_once $main_file;
            
            // Register hooks
            if (isset($addon['hooks'])) {
                foreach ($addon['hooks'] as $hook => $callback) {
                    $this->registerHook($hook, $callback);
                }
            }
            
            $this->loaded_addons[] = $addon_name;
            
            // Call addon init method if exists
            if (method_exists($addon_name, 'init')) {
                $addon_name::init();
            }
        }
    }
    
    /**
     * Register a hook
     */
    private function registerHook($hook, $callback) {
        if (!isset($this->hooks[$hook])) {
            $this->hooks[$hook] = [];
        }
        
        $this->hooks[$hook][] = $callback;
    }
    
    /**
     * Execute hooks for a specific event
     */
    public function executeHook($hook, $params = []) {
        if (!isset($this->hooks[$hook])) {
            return null;
        }
        
        $results = [];
        
        foreach ($this->hooks[$hook] as $callback) {
            if (is_callable($callback)) {
                $results[] = call_user_func_array($callback, $params);
            } elseif (strpos($callback, '::') !== false) {
                list($class, $method) = explode('::', $callback);
                if (method_exists($class, $method)) {
                    $results[] = call_user_func_array([$class, $method], $params);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Get all available addons
     */
    public function getAllAddons() {
        return $this->addons;
    }
    
    /**
     * Get enabled addons
     */
    public function getEnabledAddons() {
        return array_filter($this->addons, function($addon) {
            return $addon['enabled'];
        });
    }
    
    /**
     * Get disabled addons
     */
    public function getDisabledAddons() {
        return array_filter($this->addons, function($addon) {
            return !$addon['enabled'];
        });
    }
    
    /**
     * Enable an addon
     */
    public function enableAddon($addon_name) {
        if (!isset($this->addons[$addon_name])) {
            return false;
        }
        
        // Check dependencies
        $addon = $this->addons[$addon_name];
        foreach ($addon['dependencies'] as $dependency) {
            if (!isset($this->addons[$dependency]) || !$this->addons[$dependency]['enabled']) {
                return false;
            }
        }
        
        // Enable addon in database
        $this->setAddonSetting($addon_name, 'enabled', 'true');
        
        // Load addon
        if (!$addon['enabled']) {
            $this->loadAddon($addon_name);
            $this->addons[$addon_name]['enabled'] = true;
        }
        
        return true;
    }
    
    /**
     * Disable an addon
     */
    public function disableAddon($addon_name) {
        if (!isset($this->addons[$addon_name])) {
            return false;
        }
        
        // Check if other addons depend on this one
        foreach ($this->addons as $name => $addon) {
            if ($addon['enabled'] && in_array($addon_name, $addon['dependencies'])) {
                return false;
            }
        }
        
        // Disable addon in database
        $this->setAddonSetting($addon_name, 'enabled', 'false');
        
        // Unload addon
        if ($this->addons[$addon_name]['enabled']) {
            $this->unloadAddon($addon_name);
            $this->addons[$addon_name]['enabled'] = false;
        }
        
        return true;
    }
    
    /**
     * Unload an addon
     */
    private function unloadAddon($addon_name) {
        $addon = $this->addons[$addon_name];
        
        // Remove hooks
        if (isset($addon['hooks'])) {
            foreach ($addon['hooks'] as $hook => $callback) {
                $this->unregisterHook($hook, $callback);
            }
        }
        
        // Remove from loaded addons
        $key = array_search($addon_name, $this->loaded_addons);
        if ($key !== false) {
            unset($this->loaded_addons[$key]);
        }
        
        // Call addon destroy method if exists
        if (method_exists($addon_name, 'destroy')) {
            $addon_name::destroy();
        }
    }
    
    /**
     * Unregister a hook
     */
    private function unregisterHook($hook, $callback) {
        if (isset($this->hooks[$hook])) {
            $key = array_search($callback, $this->hooks[$hook]);
            if ($key !== false) {
                unset($this->hooks[$hook][$key]);
            }
        }
    }
    
    /**
     * Set addon setting
     */
    private function setAddonSetting($addon_name, $setting, $value) {
        $key = 'addon_' . $addon_name . '_' . $setting;
        
        try {
            $stmt = $this->db->prepare("INSERT OR REPLACE INTO " . DB_PREFIX . "settings (key, value, type, is_system) VALUES (?, ?, 'boolean', 'no')");
            return $stmt->execute([$key, $value]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get addon setting
     */
    public function getAddonSetting($addon_name, $setting, $default = null) {
        $key = 'addon_' . $addon_name . '_' . $setting;
        
        try {
            $stmt = $this->db->prepare("SELECT value FROM " . DB_PREFIX . "settings WHERE key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    /**
     * Install an addon
     */
    public function installAddon($addon_name) {
        if (!isset($this->addons[$addon_name])) {
            return false;
        }
        
        $addon = $this->addons[$addon_name];
        $install_file = $addon['path'] . '/install.php';
        
        if (file_exists($install_file)) {
            require_once $install_file;
            
            if (method_exists($addon_name, 'install')) {
                return $addon_name::install();
            }
        }
        
        return true;
    }
    
    /**
     * Uninstall an addon
     */
    public function uninstallAddon($addon_name) {
        if (!isset($this->addons[$addon_name])) {
            return false;
        }
        
        // Disable addon first
        $this->disableAddon($addon_name);
        
        $addon = $this->addons[$addon_name];
        $uninstall_file = $addon['path'] . '/uninstall.php';
        
        if (file_exists($uninstall_file)) {
            require_once $uninstall_file;
            
            if (method_exists($addon_name, 'uninstall')) {
                return $addon_name::uninstall();
            }
        }
        
        // Remove addon settings
        try {
            $stmt = $this->db->prepare("DELETE FROM " . DB_PREFIX . "settings WHERE key LIKE ?");
            $stmt->execute(['addon_' . $addon_name . '_%']);
        } catch (Exception $e) {
            // Continue even if settings deletion fails
        }
        
        return true;
    }
    
    /**
     * Get addon info
     */
    public function getAddonInfo($addon_name) {
        return isset($this->addons[$addon_name]) ? $this->addons[$addon_name] : null;
    }
    
    /**
     * Check if addon is loaded
     */
    public function isAddonLoaded($addon_name) {
        return in_array($addon_name, $this->loaded_addons);
    }
    
    /**
     * Get loaded addons
     */
    public function getLoadedAddons() {
        return $this->loaded_addons;
    }
    
    /**
     * Get hooks
     */
    public function getHooks() {
        return $this->hooks;
    }
    
    /**
     * Get hooks for a specific event
     */
    public function getHooksForEvent($event) {
        return isset($this->hooks[$event]) ? $this->hooks[$event] : [];
    }
}
?>