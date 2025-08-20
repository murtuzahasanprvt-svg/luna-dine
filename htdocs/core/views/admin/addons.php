<?php
/**
 * Admin Addons Management View
 */

// Get theme instance
global $theme;
$theme_vars = $theme->getVars();

// Page data
$page_title = 'Addons Management';
$page_description = 'Manage system addons and extensions';
$additional_head = '';

// Include theme header
include $theme->getTemplatePath('header');
?>

<div class="container">
    <div class="admin-header">
        <h1><i class="fas fa-puzzle-piece"></i> Addons Management</h1>
        <p>Manage system addons and extensions to extend functionality</p>
    </div>
    
    <!-- Messages -->
    <?php if (isset($_SESSION['addon_success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['addon_success']; ?>
        </div>
        <?php unset($_SESSION['addon_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['addon_error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['addon_error']; ?>
        </div>
        <?php unset($_SESSION['addon_error']); ?>
    <?php endif; ?>
    
    <!-- Addons Overview -->
    <div class="admin-overview">
        <div class="overview-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-puzzle-piece"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($allAddons); ?></h3>
                    <p>Total Addons</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                    <i class="fas fa-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($enabledAddons); ?></h3>
                    <p>Enabled</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <i class="fas fa-times"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($disabledAddons); ?></h3>
                    <p>Disabled</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Addons Tabs -->
    <div class="tabs">
        <div class="tab-buttons">
            <button class="tab-button active" data-tab="all">All Addons</button>
            <button class="tab-button" data-tab="enabled">Enabled</button>
            <button class="tab-button" data-tab="disabled">Disabled</button>
        </div>
        
        <div class="tab-contents">
            <!-- All Addons Tab -->
            <div class="tab-content active" data-tab="all">
                <div class="addons-grid">
                    <?php foreach ($allAddons as $addonName => $addon): ?>
                        <div class="addon-card">
                            <div class="addon-header">
                                <div class="addon-info">
                                    <h3><?php echo htmlspecialchars($addon['name']); ?></h3>
                                    <p class="addon-version">Version <?php echo htmlspecialchars($addon['version']); ?></p>
                                </div>
                                <div class="addon-status">
                                    <span class="status-badge <?php echo $addon['enabled'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $addon['enabled'] ? 'Enabled' : 'Disabled'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="addon-body">
                                <p class="addon-description"><?php echo htmlspecialchars($addon['description']); ?></p>
                                <p class="addon-author">By <?php echo htmlspecialchars($addon['author']); ?></p>
                                
                                <?php if (!empty($addon['dependencies'])): ?>
                                    <div class="addon-dependencies">
                                        <strong>Dependencies:</strong>
                                        <?php echo implode(', ', array_map('htmlspecialchars', $addon['dependencies'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="addon-actions">
                                <?php if ($addon['enabled']): ?>
                                    <form method="POST" action="/admin/disable-addon" style="display: inline;">
                                        <input type="hidden" name="addon_name" value="<?php echo htmlspecialchars($addonName); ?>">
                                        <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to disable this addon?');">
                                            <i class="fas fa-power-off"></i> Disable
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="/admin/enable-addon" style="display: inline;">
                                        <input type="hidden" name="addon_name" value="<?php echo htmlspecialchars($addonName); ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-power-off"></i> Enable
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" action="/admin/install-addon" style="display: inline;">
                                    <input type="hidden" name="addon_name" value="<?php echo htmlspecialchars($addonName); ?>">
                                    <button type="submit" class="btn btn-accent">
                                        <i class="fas fa-download"></i> Install
                                    </button>
                                </form>
                                
                                <form method="POST" action="/admin/uninstall-addon" style="display: inline;">
                                    <input type="hidden" name="addon_name" value="<?php echo htmlspecialchars($addonName); ?>">
                                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to uninstall this addon? This action cannot be undone.');">
                                        <i class="fas fa-trash"></i> Uninstall
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Enabled Addons Tab -->
            <div class="tab-content" data-tab="enabled">
                <div class="addons-grid">
                    <?php foreach ($enabledAddons as $addonName => $addon): ?>
                        <div class="addon-card">
                            <div class="addon-header">
                                <div class="addon-info">
                                    <h3><?php echo htmlspecialchars($addon['name']); ?></h3>
                                    <p class="addon-version">Version <?php echo htmlspecialchars($addon['version']); ?></p>
                                </div>
                                <div class="addon-status">
                                    <span class="status-badge status-active">Enabled</span>
                                </div>
                            </div>
                            
                            <div class="addon-body">
                                <p class="addon-description"><?php echo htmlspecialchars($addon['description']); ?></p>
                                <p class="addon-author">By <?php echo htmlspecialchars($addon['author']); ?></p>
                                
                                <?php if (!empty($addon['dependencies'])): ?>
                                    <div class="addon-dependencies">
                                        <strong>Dependencies:</strong>
                                        <?php echo implode(', ', array_map('htmlspecialchars', $addon['dependencies'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="addon-actions">
                                <form method="POST" action="/admin/disable-addon" style="display: inline;">
                                    <input type="hidden" name="addon_name" value="<?php echo htmlspecialchars($addonName); ?>">
                                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to disable this addon?');">
                                        <i class="fas fa-power-off"></i> Disable
                                    </button>
                                </form>
                                
                                <form method="POST" action="/admin/uninstall-addon" style="display: inline;">
                                    <input type="hidden" name="addon_name" value="<?php echo htmlspecialchars($addonName); ?>">
                                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to uninstall this addon? This action cannot be undone.');">
                                        <i class="fas fa-trash"></i> Uninstall
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Disabled Addons Tab -->
            <div class="tab-content" data-tab="disabled">
                <div class="addons-grid">
                    <?php foreach ($disabledAddons as $addonName => $addon): ?>
                        <div class="addon-card">
                            <div class="addon-header">
                                <div class="addon-info">
                                    <h3><?php echo htmlspecialchars($addon['name']); ?></h3>
                                    <p class="addon-version">Version <?php echo htmlspecialchars($addon['version']); ?></p>
                                </div>
                                <div class="addon-status">
                                    <span class="status-badge status-inactive">Disabled</span>
                                </div>
                            </div>
                            
                            <div class="addon-body">
                                <p class="addon-description"><?php echo htmlspecialchars($addon['description']); ?></p>
                                <p class="addon-author">By <?php echo htmlspecialchars($addon['author']); ?></p>
                                
                                <?php if (!empty($addon['dependencies'])): ?>
                                    <div class="addon-dependencies">
                                        <strong>Dependencies:</strong>
                                        <?php echo implode(', ', array_map('htmlspecialchars', $addon['dependencies'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="addon-actions">
                                <form method="POST" action="/admin/enable-addon" style="display: inline;">
                                    <input type="hidden" name="addon_name" value="<?php echo htmlspecialchars($addonName); ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-power-off"></i> Enable
                                    </button>
                                </form>
                                
                                <form method="POST" action="/admin/install-addon" style="display: inline;">
                                    <input type="hidden" name="addon_name" value="<?php echo htmlspecialchars($addonName); ?>">
                                    <button type="submit" class="btn btn-accent">
                                        <i class="fas fa-download"></i> Install
                                    </button>
                                </form>
                                
                                <form method="POST" action="/admin/uninstall-addon" style="display: inline;">
                                    <input type="hidden" name="addon_name" value="<?php echo htmlspecialchars($addonName); ?>">
                                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to uninstall this addon? This action cannot be undone.');">
                                        <i class="fas fa-trash"></i> Uninstall
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add New Addon Section -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-plus"></i> Add New Addon</h2>
        </div>
        <div class="card-body">
            <p>To add a new addon, upload the addon files to the <code>/addons/</code> directory. The system will automatically detect and list the addon here.</p>
            
            <div class="addon-requirements">
                <h3>Addon Requirements:</h3>
                <ul>
                    <li>Create a directory in <code>/addons/</code> with the addon name</li>
                    <li>Create an <code>addon.json</code> configuration file</li>
                    <li>Create a main PHP file with the same name as the addon directory</li>
                    <li>Follow the addon development guidelines</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
/* Admin Addons Management Styles */
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

.admin-overview {
    margin-bottom: 30px;
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

.addons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.addon-card {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: var(--transition);
}

.addon-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
    border-color: var(--primary-color);
}

.addon-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.addon-info h3 {
    margin: 0 0 5px 0;
    color: var(--text-color);
    font-size: 18px;
}

.addon-version {
    margin: 0;
    color: #94a3b8;
    font-size: 12px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: rgba(34, 197, 94, 0.2);
    color: #22c55e;
}

.status-inactive {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.addon-body {
    padding: 20px;
}

.addon-description {
    margin: 0 0 10px 0;
    color: var(--text-color);
    line-height: 1.5;
}

.addon-author {
    margin: 0 0 15px 0;
    color: #94a3b8;
    font-size: 14px;
}

.addon-dependencies {
    background: rgba(147, 51, 234, 0.1);
    padding: 10px;
    border-radius: var(--border-radius);
    font-size: 14px;
}

.addon-dependencies strong {
    color: var(--primary-color);
}

.addon-actions {
    padding: 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.addon-actions .btn {
    font-size: 14px;
    padding: 8px 16px;
}

.addon-requirements {
    background: rgba(147, 51, 234, 0.1);
    padding: 20px;
    border-radius: var(--border-radius);
    margin-top: 20px;
}

.addon-requirements h3 {
    margin-top: 0;
    color: var(--primary-color);
}

.addon-requirements ul {
    margin-bottom: 0;
    padding-left: 20px;
}

.addon-requirements li {
    margin-bottom: 8px;
    color: var(--text-color);
}

.addon-requirements code {
    background: rgba(0, 0, 0, 0.2);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 14px;
}

@media (max-width: 768px) {
    .addons-grid {
        grid-template-columns: 1fr;
    }
    
    .addon-actions {
        flex-direction: column;
    }
    
    .addon-actions .btn {
        width: 100%;
    }
}
</style>

<?php
// Include theme footer
include $theme->getTemplatePath('footer');
?>