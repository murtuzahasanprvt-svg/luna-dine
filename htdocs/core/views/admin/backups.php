<?php
/**
 * Admin Backup Management View
 */

// Get theme instance
global $theme;
$theme_vars = $theme->getVars();

// Page data
$page_title = 'Backup Management';
$page_description = 'Manage system backups and restore data';
$additional_head = '';

// Include theme header
include $theme->getTemplatePath('header');
?>

<div class="container">
    <div class="admin-header">
        <h1><i class="fas fa-database"></i> Backup Management</h1>
        <p>Create, manage, and restore system backups</p>
    </div>
    
    <!-- Messages -->
    <?php if (isset($_SESSION['backup_success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['backup_success']; ?>
        </div>
        <?php unset($_SESSION['backup_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['backup_error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['backup_error']; ?>
        </div>
        <?php unset($_SESSION['backup_error']); ?>
    <?php endif; ?>
    
    <!-- Backup Overview -->
    <div class="admin-overview">
        <div class="overview-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($backups); ?></h3>
                    <p>Total Backups</p>
                </div>
            </div>
            
            <?php 
            $totalSize = 0;
            foreach ($backups as $backup) {
                $totalSize += $backup['size'];
            }
            ?>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $this->formatFileSize($totalSize); ?></h3>
                    <p>Total Size</p>
                </div>
            </div>
            
            <?php
            $databaseBackups = array_filter($backups, function($b) { return $b['type'] === 'database'; });
            ?>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                    <i class="fas fa-table"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($databaseBackups); ?></h3>
                    <p>Database Backups</p>
                </div>
            </div>
            
            <?php
            $completeBackups = array_filter($backups, function($b) { return $b['type'] === 'complete'; });
            ?>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                    <i class="fas fa-archive"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($completeBackups); ?></h3>
                    <p>Complete Backups</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Backup Section -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-plus"></i> Create New Backup</h2>
        </div>
        <div class="card-body">
            <div class="backup-actions">
                <form method="POST" action="/admin/create-database-backup" class="backup-form">
                    <div class="backup-type">
                        <div class="backup-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="backup-info">
                            <h3>Database Backup</h3>
                            <p>Backup all database tables and data</p>
                        </div>
                        <div class="backup-controls">
                            <input type="text" name="description" placeholder="Backup description (optional)" class="form-input">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-download"></i> Create Database Backup
                            </button>
                        </div>
                    </div>
                </form>
                
                <form method="POST" action="/admin/create-files-backup" class="backup-form">
                    <div class="backup-type">
                        <div class="backup-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fas fa-folder"></i>
                        </div>
                        <div class="backup-info">
                            <h3>Files Backup</h3>
                            <p>Backup all system files and assets</p>
                        </div>
                        <div class="backup-controls">
                            <input type="text" name="description" placeholder="Backup description (optional)" class="form-input">
                            <button type="submit" class="btn btn-accent">
                                <i class="fas fa-download"></i> Create Files Backup
                            </button>
                        </div>
                    </div>
                </form>
                
                <form method="POST" action="/admin/create-complete-backup" class="backup-form">
                    <div class="backup-type">
                        <div class="backup-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <i class="fas fa-archive"></i>
                        </div>
                        <div class="backup-info">
                            <h3>Complete Backup</h3>
                            <p>Backup database and all files (recommended)</p>
                        </div>
                        <div class="backup-controls">
                            <input type="text" name="description" placeholder="Backup description (optional)" class="form-input">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-download"></i> Create Complete Backup
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Existing Backups -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-list"></i> Existing Backups</h2>
        </div>
        <div class="card-body">
            <?php if (empty($backups)): ?>
                <div class="no-backups">
                    <i class="fas fa-database"></i>
                    <p>No backups found. Create your first backup above.</p>
                </div>
            <?php else: ?>
                <div class="backups-table">
                    <div class="table-header">
                        <div class="table-col">Filename</div>
                        <div class="table-col">Type</div>
                        <div class="table-col">Size</div>
                        <div class="table-col">Created</div>
                        <div class="table-col">Actions</div>
                    </div>
                    
                    <?php foreach ($backups as $backup): ?>
                        <div class="table-row">
                            <div class="table-col">
                                <div class="backup-filename">
                                    <i class="fas fa-file-archive"></i>
                                    <?php echo htmlspecialchars($backup['filename']); ?>
                                </div>
                            </div>
                            <div class="table-col">
                                <span class="backup-type-badge <?php echo $backup['type']; ?>">
                                    <?php echo ucfirst($backup['type']); ?>
                                </span>
                            </div>
                            <div class="table-col">
                                <?php echo $this->formatFileSize($backup['size']); ?>
                            </div>
                            <div class="table-col">
                                <?php echo $backup['created_formatted']; ?>
                            </div>
                            <div class="table-col">
                                <div class="backup-actions">
                                    <a href="/admin/download-backup?file=<?php echo urlencode($backup['path']); ?>" 
                                       class="btn btn-sm btn-primary" title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    
                                    <button type="button" class="btn btn-sm btn-accent" 
                                            onclick="showRestoreModal('<?php echo htmlspecialchars($backup['path']); ?>', '<?php echo $backup['type']; ?>')"
                                            title="Restore">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    
                                    <form method="POST" action="/admin/delete-backup" style="display: inline;">
                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($backup['path']); ?>">
                                        <button type="submit" class="btn btn-sm btn-secondary" 
                                                onclick="return confirm('Are you sure you want to delete this backup? This action cannot be undone.');"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Backup Settings -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-cog"></i> Backup Settings</h2>
        </div>
        <div class="card-body">
            <div class="backup-settings">
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Automatic Backups</h3>
                        <p>System will automatically create complete backups every 24 hours</p>
                    </div>
                    <div class="setting-value">
                        <span class="status-badge <?php echo AUTO_BACKUP ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo AUTO_BACKUP ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Backup Retention</h3>
                        <p>Keep last <?php echo MAX_BACKUPS; ?> backups only</p>
                    </div>
                    <div class="setting-value">
                        <span class="status-badge status-active"><?php echo MAX_BACKUPS; ?> backups</span>
                    </div>
                </div>
                
                <div class="setting-item">
                    <div class="setting-info">
                        <h3>Backup Directory</h3>
                        <p>All backups are stored in: <?php echo BACKUP_PATH; ?></p>
                    </div>
                    <div class="setting-value">
                        <span class="status-badge status-active"><?php echo is_writable(BACKUP_PATH) ? 'Writable' : 'Not Writable'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Restore Backup Modal -->
<div id="restoreModal" class="modal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Restore Backup</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="restore-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <p><strong>Warning:</strong> Restoring a backup will overwrite existing data. This action cannot be undone.</p>
                <p>Make sure you have a current backup before proceeding.</p>
            </div>
            
            <form id="restoreForm" method="POST" action="/admin/restore-backup">
                <input type="hidden" name="backup_file" id="restoreBackupFile">
                <input type="hidden" name="backup_type" id="restoreBackupType">
                
                <div class="form-group">
                    <label class="form-label">Confirmation</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="confirmRestore" required>
                        <label for="confirmRestore">I understand that this will overwrite existing data and cannot be undone.</label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="restoreButton">
                        <i class="fas fa-undo"></i> Restore Backup
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeRestoreModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Admin Backup Management Styles */
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

.backup-actions {
    display: grid;
    gap: 20px;
}

.backup-form {
    display: block;
}

.backup-type {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 20px;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 20px;
    align-items: center;
    transition: var(--transition);
}

.backup-type:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.backup-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.backup-info h3 {
    margin: 0 0 5px 0;
    color: var(--text-color);
    font-size: 18px;
}

.backup-info p {
    margin: 0;
    color: #94a3b8;
    font-size: 14px;
}

.backup-controls {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: flex-end;
}

.backup-controls .form-input {
    width: 250px;
}

.no-backups {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
}

.no-backups i {
    font-size: 64px;
    margin-bottom: 20px;
    color: #64748b;
}

.backups-table {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.table-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.5fr 1fr;
    gap: 20px;
    padding: 20px;
    background: rgba(147, 51, 234, 0.1);
    border-bottom: 1px solid var(--border-color);
    font-weight: 600;
    color: var(--text-color);
}

.table-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.5fr 1fr;
    gap: 20px;
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
    align-items: center;
}

.table-row:last-child {
    border-bottom: none;
}

.backup-filename {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-color);
}

.backup-filename i {
    color: #94a3b8;
}

.backup-type-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.backup-type-badge.database {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
}

.backup-type-badge.files {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
}

.backup-type-badge.complete {
    background: rgba(139, 92, 246, 0.2);
    color: #8b5cf6;
}

.backup-type-badge.unknown {
    background: rgba(107, 114, 128, 0.2);
    color: #6b7280;
}

.backup-actions {
    display: flex;
    gap: 5px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.backup-settings {
    display: grid;
    gap: 20px;
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: rgba(147, 51, 234, 0.1);
    border-radius: var(--border-radius);
}

.setting-info h3 {
    margin: 0 0 5px 0;
    color: var(--text-color);
    font-size: 16px;
}

.setting-info p {
    margin: 0;
    color: #94a3b8;
    font-size: 14px;
}

.restore-warning {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid #ef4444;
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 20px;
    color: #ef4444;
}

.restore-warning i {
    font-size: 24px;
    margin-bottom: 10px;
    display: block;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.checkbox-group input[type="checkbox"] {
    width: 20px;
    height: 20px;
}

@media (max-width: 768px) {
    .overview-stats {
        grid-template-columns: 1fr;
    }
    
    .backup-type {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .backup-controls {
        align-items: center;
    }
    
    .backup-controls .form-input {
        width: 100%;
    }
    
    .table-header,
    .table-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .table-header {
        display: none;
    }
    
    .table-row {
        flex-direction: column;
        text-align: center;
    }
    
    .setting-item {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
}
</style>

<script>
function showRestoreModal(backupFile, backupType) {
    document.getElementById('restoreBackupFile').value = backupFile;
    document.getElementById('restoreBackupType').value = backupType;
    document.getElementById('restoreModal').classList.add('active');
}

function closeRestoreModal() {
    document.getElementById('restoreModal').classList.remove('active');
    document.getElementById('restoreForm').reset();
}

// Close modal when clicking outside
document.getElementById('restoreModal').addEventListener('click', function(e) {
    if (e.target === this || e.target.classList.contains('modal-overlay')) {
        closeRestoreModal();
    }
});

// Format file size helper function
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>

<?php
// Include theme footer
include $theme->getTemplatePath('footer');
?>