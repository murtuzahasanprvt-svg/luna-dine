<?php
/**
 * Admin Themes Management View
 */

// Get theme instance
global $theme;
$theme_vars = $theme->getVars();

// Page data
$page_title = 'Themes Management';
$page_description = 'Manage website themes and appearance';
$additional_head = '';

// Include theme header
include $theme->getTemplatePath('header');
?>

<div class="container">
    <div class="admin-header">
        <h1><i class="fas fa-palette"></i> Themes Management</h1>
        <p>Manage website themes and customize appearance</p>
    </div>
    
    <!-- Messages -->
    <?php if (isset($_SESSION['theme_success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['theme_success']; ?>
        </div>
        <?php unset($_SESSION['theme_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['theme_error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['theme_error']; ?>
        </div>
        <?php unset($_SESSION['theme_error']); ?>
    <?php endif; ?>
    
    <!-- Themes Overview -->
    <div class="admin-overview">
        <div class="overview-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-palette"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($allThemes); ?></h3>
                    <p>Total Themes</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-content">
                    <h3>1</h3>
                    <p>Active</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-moon"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($allThemes) - 1; ?></h3>
                    <p>Available</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Active Theme Preview -->
    <?php if ($activeTheme): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-star"></i> Active Theme</h2>
            </div>
            <div class="card-body">
                <div class="active-theme-preview">
                    <div class="theme-info">
                        <h3><?php echo htmlspecialchars($activeTheme['name']); ?></h3>
                        <p class="theme-version">Version <?php echo htmlspecialchars($activeTheme['version']); ?></p>
                        <p class="theme-author">By <?php echo htmlspecialchars($activeTheme['author']); ?></p>
                        <p class="theme-description"><?php echo htmlspecialchars($activeTheme['description']); ?></p>
                    </div>
                    
                    <div class="theme-screenshot">
                        <?php if (!empty($activeTheme['screenshots'])): ?>
                            <img src="<?php echo htmlspecialchars($activeTheme['url'] . '/' . $activeTheme['screenshots'][0]); ?>" 
                                 alt="<?php echo htmlspecialchars($activeTheme['name']); ?> Screenshot">
                        <?php else: ?>
                            <div class="no-screenshot">
                                <i class="fas fa-image"></i>
                                <p>No screenshot available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="theme-actions">
                    <button class="btn btn-primary" onclick="showThemeSettings('<?php echo htmlspecialchars($activeTheme['name']); ?>')">
                        <i class="fas fa-cog"></i> Theme Settings
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Available Themes -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-th-large"></i> Available Themes</h2>
        </div>
        <div class="card-body">
            <div class="themes-grid">
                <?php foreach ($allThemes as $themeName => $themeData): ?>
                    <?php if ($activeTheme && $activeTheme['name'] === $themeData['name']) continue; ?>
                    
                    <div class="theme-card">
                        <div class="theme-header">
                            <h3><?php echo htmlspecialchars($themeData['name']); ?></h3>
                            <span class="theme-version">v<?php echo htmlspecialchars($themeData['version']); ?></span>
                        </div>
                        
                        <div class="theme-screenshot">
                            <?php if (!empty($themeData['screenshots'])): ?>
                                <img src="<?php echo htmlspecialchars($themeData['url'] . '/' . $themeData['screenshots'][0]); ?>" 
                                     alt="<?php echo htmlspecialchars($themeData['name']); ?> Screenshot">
                            <?php else: ?>
                                <div class="no-screenshot">
                                    <i class="fas fa-image"></i>
                                    <p>No screenshot</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="theme-info">
                            <p class="theme-description"><?php echo htmlspecialchars($themeData['description']); ?></p>
                            <p class="theme-author">By <?php echo htmlspecialchars($themeData['author']); ?></p>
                        </div>
                        
                        <div class="theme-actions">
                            <form method="POST" action="/admin/set-active-theme" style="display: inline;">
                                <input type="hidden" name="theme_name" value="<?php echo htmlspecialchars($themeName); ?>">
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to activate this theme?');">
                                    <i class="fas fa-power-off"></i> Activate
                                </button>
                            </form>
                            
                            <button class="btn btn-secondary" onclick="previewTheme('<?php echo htmlspecialchars($themeName); ?>')">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                            
                            <form method="POST" action="/admin/install-theme" style="display: inline;">
                                <input type="hidden" name="theme_name" value="<?php echo htmlspecialchars($themeName); ?>">
                                <button type="submit" class="btn btn-accent">
                                    <i class="fas fa-download"></i> Install
                                </button>
                            </form>
                            
                            <form method="POST" action="/admin/uninstall-theme" style="display: inline;">
                                <input type="hidden" name="theme_name" value="<?php echo htmlspecialchars($themeName); ?>">
                                <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to uninstall this theme? This action cannot be undone.');">
                                    <i class="fas fa-trash"></i> Uninstall
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Theme Settings Modal -->
    <div id="themeSettingsModal" class="modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Theme Settings</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="themeSettingsForm" method="POST" action="/admin/update-theme-settings">
                    <div class="form-group">
                        <label class="form-label">Primary Color</label>
                        <input type="color" class="form-input" name="theme_primary_color" value="<?php echo $theme->getVar('primary_color'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Secondary Color</label>
                        <input type="color" class="form-input" name="theme_secondary_color" value="<?php echo $theme->getVar('secondary_color'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Accent Color</label>
                        <input type="color" class="form-input" name="theme_accent_color" value="<?php echo $theme->getVar('accent_color'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Background Color</label>
                        <input type="color" class="form-input" name="theme_background_color" value="<?php echo $theme->getVar('background_color'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Text Color</label>
                        <input type="color" class="form-input" name="theme_text_color" value="<?php echo $theme->getVar('text_color'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Font Family</label>
                        <select class="form-input form-select" name="theme_font_family">
                            <option value="Inter, system-ui, sans-serif" <?php echo $theme->getVar('font_family') === 'Inter, system-ui, sans-serif' ? 'selected' : ''; ?>>Inter</option>
                            <option value="Arial, sans-serif" <?php echo $theme->getVar('font_family') === 'Arial, sans-serif' ? 'selected' : ''; ?>>Arial</option>
                            <option value="Georgia, serif" <?php echo $theme->getVar('font_family') === 'Georgia, serif' ? 'selected' : ''; ?>>Georgia</option>
                            <option value="Times New Roman, serif" <?php echo $theme->getVar('font_family') === 'Times New Roman, serif' ? 'selected' : ''; ?>>Times New Roman</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Font Size</label>
                        <input type="number" class="form-input" name="theme_font_size" value="<?php echo $theme->getVar('font_size'); ?>" min="12" max="24">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Border Radius</label>
                        <input type="number" class="form-input" name="theme_border_radius" value="<?php echo $theme->getVar('border_radius'); ?>" min="0" max="20">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                        <button type="button" class="btn btn-secondary" onclick="closeThemeSettings()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add New Theme Section -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-plus"></i> Add New Theme</h2>
        </div>
        <div class="card-body">
            <p>To add a new theme, upload the theme files to the <code>/themes/</code> directory. The system will automatically detect and list the theme here.</p>
            
            <div class="theme-requirements">
                <h3>Theme Requirements:</h3>
                <ul>
                    <li>Create a directory in <code>/themes/</code> with the theme name</li>
                    <li>Create a <code>theme.json</code> configuration file</li>
                    <li>Create <code>css/style.css</code> for styles</li>
                    <li>Create <code>js/script.js</code> for JavaScript (optional)</li>
                    <li>Create <code>templates/</code> directory for template files (optional)</li>
                    <li>Follow the theme development guidelines</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
/* Admin Themes Management Styles */
.admin-header {
    margin-bottom: 30px;
    text-align: center;
}

.admin-header h1 {
    font-size: 2.5rem;
    margin-bottom: 10px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.overview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.stat-content h3 {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
    color: var(--text-color);
}

.stat-content p {
    margin: 0;
    color: #94a3b8;
    font-size: 14px;
}

.active-theme-preview {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 30px;
    align-items: start;
}

.theme-info h3 {
    margin: 0 0 5px 0;
    color: var(--text-color);
    font-size: 24px;
}

.theme-version {
    margin: 0 0 10px 0;
    color: #94a3b8;
    font-size: 14px;
}

.theme-author {
    margin: 0 0 15px 0;
    color: #94a3b8;
    font-size: 14px;
}

.theme-description {
    margin: 0;
    color: var(--text-color);
    line-height: 1.6;
}

.theme-screenshot {
    border-radius: var(--border-radius);
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.theme-screenshot img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.no-screenshot {
    width: 100%;
    height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(147, 51, 234, 0.1);
    color: #94a3b8;
}

.no-screenshot i {
    font-size: 48px;
    margin-bottom: 10px;
}

.themes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.theme-card {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: var(--transition);
}

.theme-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
    border-color: var(--primary-color);
}

.theme-header {
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.theme-header h3 {
    margin: 0;
    color: var(--text-color);
    font-size: 18px;
}

.theme-version {
    color: #94a3b8;
    font-size: 12px;
    font-weight: 600;
}

.theme-card .theme-screenshot {
    height: 150px;
}

.theme-card .theme-info {
    padding: 15px 20px;
}

.theme-card .theme-description {
    font-size: 14px;
    margin-bottom: 8px;
}

.theme-card .theme-author {
    font-size: 12px;
    margin: 0;
}

.theme-actions {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.theme-actions .btn {
    font-size: 12px;
    padding: 6px 12px;
}

.theme-requirements {
    background: rgba(147, 51, 234, 0.1);
    padding: 20px;
    border-radius: var(--border-radius);
    margin-top: 20px;
}

.theme-requirements h3 {
    margin-top: 0;
    color: var(--primary-color);
}

.theme-requirements ul {
    margin-bottom: 0;
    padding-left: 20px;
}

.theme-requirements li {
    margin-bottom: 8px;
    color: var(--text-color);
}

.theme-requirements code {
    background: rgba(0, 0, 0, 0.2);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 14px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.modal-content {
    position: relative;
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    z-index: 1001;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: var(--text-color);
}

.modal-close {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: var(--transition);
}

.modal-close:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.modal-body {
    padding: 20px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .active-theme-preview {
        grid-template-columns: 1fr;
    }
    
    .themes-grid {
        grid-template-columns: 1fr;
    }
    
    .theme-actions {
        flex-direction: column;
    }
    
    .theme-actions .btn {
        width: 100%;
    }
    
    .modal-content {
        width: 95%;
        margin: 20px;
    }
}
</style>

<script>
function showThemeSettings(themeName) {
    document.getElementById('themeSettingsModal').classList.add('active');
}

function closeThemeSettings() {
    document.getElementById('themeSettingsModal').classList.remove('active');
}

function previewTheme(themeName) {
    // In a real implementation, this would open a preview
    alert('Preview functionality would be implemented here for theme: ' + themeName);
}

// Close modal when clicking outside
document.getElementById('themeSettingsModal').addEventListener('click', function(e) {
    if (e.target === this || e.target.classList.contains('modal-overlay')) {
        closeThemeSettings();
    }
});
</script>

<?php
// Include theme footer
include $theme->getTemplatePath('footer');
?>