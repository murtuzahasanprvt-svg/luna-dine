<?php
/**
 * Luna Dine Backup System
 * 
 * Handles database and file backup functionality
 */

class Backup {
    private $db;
    private $backupPath;
    private $maxBackups;
    
    public function __construct($db) {
        $this->db = $db;
        $this->backupPath = BACKUP_PATH;
        $this->maxBackups = MAX_BACKUPS;
        
        // Ensure backup directory exists
        $this->ensureBackupDirectory();
    }
    
    /**
     * Ensure backup directory exists
     */
    private function ensureBackupDirectory() {
        if (!file_exists($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }
    
    /**
     * Create database backup
     */
    public function createDatabaseBackup($description = '') {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->backupPath . '/database_' . $timestamp . '.sql';
            
            // Get all tables
            $tables = $this->getTables();
            
            // Create backup file
            $backupContent = $this->generateBackupContent($tables);
            
            // Write backup to file
            file_put_contents($backupFile, $backupContent);
            
            // Compress backup file
            $compressedFile = $this->compressFile($backupFile);
            
            // Remove uncompressed file
            if ($compressedFile && file_exists($backupFile)) {
                unlink($backupFile);
            }
            
            // Log backup creation
            $this->logBackup('database', $compressedFile ?: $backupFile, $description);
            
            // Clean old backups
            $this->cleanOldBackups();
            
            return [
                'success' => true,
                'file' => $compressedFile ?: $backupFile,
                'size' => filesize($compressedFile ?: $backupFile),
                'timestamp' => $timestamp
            ];
            
        } catch (Exception $e) {
            error_log("Database backup failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all database tables
     */
    private function getTables() {
        $tables = [];
        
        try {
            $result = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            
            while ($row = $result->fetch()) {
                $tables[] = $row['name'];
            }
            
        } catch (Exception $e) {
            error_log("Get tables failed: " . $e->getMessage());
        }
        
        return $tables;
    }
    
    /**
     * Generate backup content
     */
    private function generateBackupContent($tables) {
        $content = "-- Luna Dine Database Backup\n";
        $content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $content .= "-- Database: SQLite\n\n";
        
        foreach ($tables as $table) {
            $content .= $this->getTableDump($table);
        }
        
        return $content;
    }
    
    /**
     * Get table dump
     */
    private function getTableDump($table) {
        $dump = "";
        
        try {
            // Get CREATE TABLE statement
            $result = $this->db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'");
            $row = $result->fetch();
            
            if ($row && isset($row['sql'])) {
                $dump .= "-- Table structure for `$table`\n";
                $dump .= "DROP TABLE IF EXISTS `$table`;\n";
                $dump .= $row['sql'] . ";\n\n";
                
                // Get table data
                $result = $this->db->query("SELECT * FROM `$table`");
                $columns = $result->columnCount();
                
                if ($columns > 0) {
                    $dump .= "-- Data for table `$table`\n";
                    
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        $values = array_map([$this, 'escapeValue'], array_values($row));
                        $columns = array_map([$this, 'escapeColumn'], array_keys($row));
                        
                        $dump .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES ('" . implode("', '", $values) . "');\n";
                    }
                    
                    $dump .= "\n";
                }
            }
            
        } catch (Exception $e) {
            error_log("Table dump failed for $table: " . $e->getMessage());
        }
        
        return $dump;
    }
    
    /**
     * Escape column name
     */
    private function escapeColumn($value) {
        return str_replace('`', '``', $value);
    }
    
    /**
     * Escape value for SQL
     */
    private function escapeValue($value) {
        if ($value === null) {
            return 'NULL';
        }
        
        return str_replace("'", "''", $value);
    }
    
    /**
     * Compress file
     */
    private function compressFile($file) {
        if (!file_exists($file)) {
            return false;
        }
        
        $compressedFile = $file . '.gz';
        
        if (file_put_contents($compressedFile, gzencode(file_get_contents($file), 9))) {
            return $compressedFile;
        }
        
        return false;
    }
    
    /**
     * Create files backup
     */
    public function createFilesBackup($description = '') {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->backupPath . '/files_' . $timestamp . '.tar.gz';
            
            // Create tar archive
            $phar = new PharData($backupFile);
            
            // Add files to backup (exclude certain directories)
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(LUNA_DINE_ROOT, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($files as $file) {
                $relativePath = substr($file->getPathname(), strlen(LUNA_DINE_ROOT) + 1);
                
                // Skip backup directory and other excluded paths
                if (strpos($relativePath, 'backups/') === 0 ||
                    strpos($relativePath, 'node_modules/') === 0 ||
                    strpos($relativePath, '.git/') === 0 ||
                    strpos($relativePath, '.env') !== false ||
                    $file->getFilename() === '.DS_Store') {
                    continue;
                }
                
                if ($file->isDir()) {
                    $phar->addEmptyDir($relativePath);
                } else {
                    $phar->addFile($file->getPathname(), $relativePath);
                }
            }
            
            // Compress the archive
            $phar->compress(Phar::GZ);
            
            // Remove uncompressed archive
            unset($phar);
            if (file_exists($backupFile)) {
                unlink($backupFile);
            }
            
            $compressedFile = $backupFile . '.gz';
            
            // Log backup creation
            $this->logBackup('files', $compressedFile, $description);
            
            // Clean old backups
            $this->cleanOldBackups();
            
            return [
                'success' => true,
                'file' => $compressedFile,
                'size' => filesize($compressedFile),
                'timestamp' => $timestamp
            ];
            
        } catch (Exception $e) {
            error_log("Files backup failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create complete backup (database + files)
     */
    public function createCompleteBackup($description = '') {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupDir = $this->backupPath . '/complete_' . $timestamp;
            
            // Create backup directory
            mkdir($backupDir, 0755, true);
            
            // Create database backup
            $dbBackup = $this->createDatabaseBackup($description);
            if (!$dbBackup['success']) {
                throw new Exception("Database backup failed: " . $dbBackup['error']);
            }
            
            // Copy database backup to complete backup directory
            $dbBackupFile = $backupDir . '/database.sql.gz';
            copy($dbBackup['file'], $dbBackupFile);
            
            // Create files backup
            $filesBackup = $this->createFilesBackup($description);
            if (!$filesBackup['success']) {
                throw new Exception("Files backup failed: " . $filesBackup['error']);
            }
            
            // Copy files backup to complete backup directory
            $filesBackupFile = $backupDir . '/files.tar.gz';
            copy($filesBackup['file'], $filesBackupFile);
            
            // Create backup info file
            $infoFile = $backupDir . '/backup_info.json';
            $info = [
                'timestamp' => $timestamp,
                'description' => $description,
                'database_backup' => 'database.sql.gz',
                'files_backup' => 'files.tar.gz',
                'database_size' => filesize($dbBackupFile),
                'files_size' => filesize($filesBackupFile),
                'total_size' => filesize($dbBackupFile) + filesize($filesBackupFile),
                'created_by' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'system'
            ];
            
            file_put_contents($infoFile, json_encode($info, JSON_PRETTY_PRINT));
            
            // Create final archive
            $finalArchive = $backupDir . '.tar.gz';
            $phar = new PharData($finalArchive);
            $phar->buildFromDirectory($backupDir);
            $phar->compress(Phar::GZ);
            unset($phar);
            
            // Remove temporary directory
            $this->removeDirectory($backupDir);
            if (file_exists($finalArchive)) {
                unlink($finalArchive);
            }
            
            $compressedFile = $finalArchive . '.gz';
            
            // Log backup creation
            $this->logBackup('complete', $compressedFile, $description);
            
            // Clean old backups
            $this->cleanOldBackups();
            
            return [
                'success' => true,
                'file' => $compressedFile,
                'size' => filesize($compressedFile),
                'timestamp' => $timestamp,
                'info' => $info
            ];
            
        } catch (Exception $e) {
            error_log("Complete backup failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Remove directory recursively
     */
    private function removeDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!$this->removeDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Restore backup
     */
    public function restoreBackup($backupFile, $type = 'database') {
        try {
            if (!file_exists($backupFile)) {
                throw new Exception("Backup file not found: $backupFile");
            }
            
            switch ($type) {
                case 'database':
                    return $this->restoreDatabase($backupFile);
                    
                case 'files':
                    return $this->restoreFiles($backupFile);
                    
                case 'complete':
                    return $this->restoreComplete($backupFile);
                    
                default:
                    throw new Exception("Invalid backup type: $type");
            }
            
        } catch (Exception $e) {
            error_log("Restore backup failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Restore database from backup
     */
    private function restoreDatabase($backupFile) {
        try {
            // Extract backup if compressed
            if (pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz') {
                $content = gzdecode(file_get_contents($backupFile));
            } else {
                $content = file_get_contents($backupFile);
            }
            
            // Drop all existing tables
            $tables = $this->getTables();
            foreach ($tables as $table) {
                $this->db->exec("DROP TABLE IF EXISTS `$table`");
            }
            
            // Execute backup SQL
            $this->db->exec($content);
            
            // Log restore
            $this->logRestore('database', $backupFile);
            
            return [
                'success' => true,
                'message' => 'Database restored successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Database restore failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Restore files from backup
     */
    private function restoreFiles($backupFile) {
        try {
            // Extract archive
            $extractPath = LUNA_DINE_ROOT . '/restore_temp_' . time();
            mkdir($extractPath, 0755, true);
            
            if (pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz') {
                $phar = new PharData($backupFile);
                $phar->extractTo($extractPath);
            } else {
                copy($backupFile, $extractPath . '/files.tar');
                $phar = new PharData($extractPath . '/files.tar');
                $phar->extractTo($extractPath);
            }
            
            // Restore files (this is a simplified version)
            // In production, you'd want more sophisticated file handling
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($files as $file) {
                $relativePath = substr($file->getPathname(), strlen($extractPath) + 1);
                $targetPath = LUNA_DINE_ROOT . '/' . $relativePath;
                
                if ($file->isDir()) {
                    if (!file_exists($targetPath)) {
                        mkdir($targetPath, 0755, true);
                    }
                } else {
                    copy($file->getPathname(), $targetPath);
                }
            }
            
            // Clean up
            $this->removeDirectory($extractPath);
            
            // Log restore
            $this->logRestore('files', $backupFile);
            
            return [
                'success' => true,
                'message' => 'Files restored successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Files restore failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Restore complete backup
     */
    private function restoreComplete($backupFile) {
        try {
            // Extract complete backup
            $extractPath = LUNA_DINE_ROOT . '/restore_temp_' . time();
            mkdir($extractPath, 0755, true);
            
            if (pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz') {
                $phar = new PharData($backupFile);
                $phar->extractTo($extractPath);
            }
            
            // Restore database
            $dbBackupFile = $extractPath . '/database.sql.gz';
            if (file_exists($dbBackupFile)) {
                $this->restoreDatabase($dbBackupFile);
            }
            
            // Restore files
            $filesBackupFile = $extractPath . '/files.tar.gz';
            if (file_exists($filesBackupFile)) {
                $this->restoreFiles($filesBackupFile);
            }
            
            // Clean up
            $this->removeDirectory($extractPath);
            
            // Log restore
            $this->logRestore('complete', $backupFile);
            
            return [
                'success' => true,
                'message' => 'Complete backup restored successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Complete restore failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get backup list
     */
    public function getBackupList() {
        $backups = [];
        
        try {
            $files = glob($this->backupPath . '/*');
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $filename = basename($file);
                    $type = $this->getBackupType($filename);
                    
                    $backups[] = [
                        'filename' => $filename,
                        'path' => $file,
                        'type' => $type,
                        'size' => filesize($file),
                        'created' => filemtime($file),
                        'created_formatted' => date('Y-m-d H:i:s', filemtime($file))
                    ];
                }
            }
            
            // Sort by creation date (newest first)
            usort($backups, function($a, $b) {
                return $b['created'] - $a['created'];
            });
            
        } catch (Exception $e) {
            error_log("Get backup list failed: " . $e->getMessage());
        }
        
        return $backups;
    }
    
    /**
     * Get backup type from filename
     */
    private function getBackupType($filename) {
        if (strpos($filename, 'database_') === 0) {
            return 'database';
        } elseif (strpos($filename, 'files_') === 0) {
            return 'files';
        } elseif (strpos($filename, 'complete_') === 0) {
            return 'complete';
        }
        
        return 'unknown';
    }
    
    /**
     * Delete backup
     */
    public function deleteBackup($backupFile) {
        try {
            if (!file_exists($backupFile)) {
                return [
                    'success' => false,
                    'error' => 'Backup file not found'
                ];
            }
            
            if (unlink($backupFile)) {
                return [
                    'success' => true,
                    'message' => 'Backup deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to delete backup file'
                ];
            }
            
        } catch (Exception $e) {
            error_log("Delete backup failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Clean old backups
     */
    private function cleanOldBackups() {
        try {
            $backups = $this->getBackupList();
            
            if (count($backups) > $this->maxBackups) {
                // Keep only the newest backups
                $backupsToDelete = array_slice($backups, $this->maxBackups);
                
                foreach ($backupsToDelete as $backup) {
                    $this->deleteBackup($backup['path']);
                }
            }
            
        } catch (Exception $e) {
            error_log("Clean old backups failed: " . $e->getMessage());
        }
    }
    
    /**
     * Log backup creation
     */
    private function logBackup($type, $file, $description) {
        try {
            $logMessage = sprintf(
                "Backup created - Type: %s, File: %s, Size: %s, Description: %s",
                $type,
                basename($file),
                $this->formatFileSize(filesize($file)),
                $description
            );
            
            error_log($logMessage);
            
            // Log to database if available
            if ($this->db) {
                $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
                Utilities::logActivity($userId, 'backup_created', $logMessage);
            }
            
        } catch (Exception $e) {
            error_log("Log backup failed: " . $e->getMessage());
        }
    }
    
    /**
     * Log restore operation
     */
    private function logRestore($type, $file) {
        try {
            $logMessage = sprintf(
                "Backup restored - Type: %s, File: %s, Size: %s",
                $type,
                basename($file),
                $this->formatFileSize(filesize($file))
            );
            
            error_log($logMessage);
            
            // Log to database if available
            if ($this->db) {
                $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
                Utilities::logActivity($userId, 'backup_restored', $logMessage);
            }
            
        } catch (Exception $e) {
            error_log("Log restore failed: " . $e->getMessage());
        }
    }
    
    /**
     * Format file size
     */
    private function formatFileSize($bytes) {
        if ($bytes === 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Schedule automatic backup
     */
    public function scheduleAutoBackup() {
        if (!AUTO_BACKUP) {
            return;
        }
        
        try {
            // Check if backup is needed
            $lastBackupFile = $this->getLastBackupFile();
            $backupNeeded = true;
            
            if ($lastBackupFile) {
                $lastBackupTime = filemtime($lastBackupFile);
                $nextBackupTime = $lastBackupTime + BACKUP_INTERVAL;
                
                if (time() < $nextBackupTime) {
                    $backupNeeded = false;
                }
            }
            
            if ($backupNeeded) {
                $this->createCompleteBackup('Automatic scheduled backup');
            }
            
        } catch (Exception $e) {
            error_log("Auto backup failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get last backup file
     */
    private function getLastBackupFile() {
        $files = glob($this->backupPath . '/complete_*.tar.gz');
        
        if (empty($files)) {
            return null;
        }
        
        // Return the most recent file
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        return $files[0];
    }
}
?>