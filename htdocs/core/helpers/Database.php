<?php
/**
 * Luna Dine Database Helper
 * 
 * SQLite Database wrapper for Luna Dine
 */

class Database {
    private $connection;
    private $statement;
    private $dbPath;
    
    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->dbPath = DB_PATH;
        $this->connect();
    }
    
    /**
     * Connect to SQLite database
     */
    private function connect() {
        try {
            // Create database file if it doesn't exist
            if (!file_exists($this->dbPath)) {
                $this->createDatabase();
            }
            
            // Create SQLite connection
            $this->connection = new PDO('sqlite:' . $this->dbPath);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Enable foreign keys
            $this->connection->exec('PRAGMA foreign_keys = ON');
            
            // Set busy timeout
            $this->connection->exec('PRAGMA busy_timeout = 5000');
            
        } catch (PDOException $e) {
            $this->handleError('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Create database file and tables
     */
    private function createDatabase() {
        try {
            // Create database directory if it doesn't exist
            $dbDir = dirname($this->dbPath);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }
            
            // Create empty database file
            touch($this->dbPath);
            
            // Set proper permissions
            chmod($this->dbPath, 0644);
            
        } catch (Exception $e) {
            $this->handleError('Database creation failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Initialize database tables
     */
    public function initializeTables() {
        try {
            $sql = file_get_contents(LUNA_DINE_DATABASE . '/schema.sql');
            if ($sql) {
                $this->connection->exec($sql);
            }
        } catch (PDOException $e) {
            $this->handleError('Table initialization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Prepare SQL statement
     */
    public function prepare($sql) {
        try {
            $this->statement = $this->connection->prepare($sql);
            return $this;
        } catch (PDOException $e) {
            $this->handleError('Statement preparation failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Bind parameters to statement
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        try {
            $this->statement->bindValue($param, $value, $type);
            return $this;
        } catch (PDOException $e) {
            $this->handleError('Parameter binding failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute prepared statement
     */
    public function execute($params = []) {
        try {
            if (!empty($params)) {
                return $this->statement->execute($params);
            } else {
                return $this->statement->execute();
            }
        } catch (PDOException $e) {
            $this->handleError('Statement execution failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get single row
     */
    public function fetch() {
        try {
            return $this->statement->fetch();
        } catch (PDOException $e) {
            $this->handleError('Fetch failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all rows
     */
    public function fetchAll() {
        try {
            return $this->statement->fetchAll();
        } catch (PDOException $e) {
            $this->handleError('FetchAll failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get single column
     */
    public function fetchColumn($column = 0) {
        try {
            return $this->statement->fetchColumn($column);
        } catch (PDOException $e) {
            $this->handleError('FetchColumn failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get last inserted ID
     */
    public function lastInsertId() {
        try {
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            $this->handleError('LastInsertId failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get number of affected rows
     */
    public function rowCount() {
        try {
            return $this->statement->rowCount();
        } catch (PDOException $e) {
            $this->handleError('RowCount failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        try {
            return $this->connection->beginTransaction();
        } catch (PDOException $e) {
            $this->handleError('Transaction begin failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        try {
            return $this->connection->commit();
        } catch (PDOException $e) {
            $this->handleError('Transaction commit failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        try {
            return $this->connection->rollBack();
        } catch (PDOException $e) {
            $this->handleError('Transaction rollback failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get table columns
     */
    public function getTableColumns($table) {
        try {
            $sql = "PRAGMA table_info($table)";
            $stmt = $this->connection->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError('Get table columns failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Table exists check
     */
    public function tableExists($table) {
        try {
            $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=:table";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([':table' => $table]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            $this->handleError('Table exists check failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all tables
     */
    public function getTables() {
        try {
            $sql = "SELECT name FROM sqlite_master WHERE type='table'";
            $stmt = $this->connection->query($sql);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->handleError('Get tables failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute raw SQL query
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->handleError('Query execution failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Insert data into table
     */
    public function insert($table, $data) {
        try {
            $columns = array_keys($data);
            $values = array_values($data);
            $placeholders = array_fill(0, count($columns), '?');
            
            $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($values);
            
            return $this->lastInsertId();
        } catch (PDOException $e) {
            $this->handleError('Insert failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update data in table
     */
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $set = [];
            $params = [];
            
            foreach ($data as $column => $value) {
                $set[] = "$column = ?";
                $params[] = $value;
            }
            
            $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $where";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute(array_merge($params, $whereParams));
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->handleError('Update failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete data from table
     */
    public function delete($table, $where, $params = []) {
        try {
            $sql = "DELETE FROM $table WHERE $where";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->handleError('Delete failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Select data from table
     */
    public function select($table, $columns = '*', $where = '', $params = [], $orderBy = '', $limit = '', $offset = '') {
        try {
            $sql = "SELECT $columns FROM $table";
            
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            
            if (!empty($orderBy)) {
                $sql .= " ORDER BY $orderBy";
            }
            
            if (!empty($limit)) {
                $sql .= " LIMIT $limit";
            }
            
            if (!empty($offset)) {
                $sql .= " OFFSET $offset";
            }
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError('Select failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get database error info
     */
    public function errorInfo() {
        return $this->connection->errorInfo();
    }
    
    /**
     * Handle database errors
     */
    private function handleError($message) {
        if (DEBUG_MODE) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Database Error:</strong> $message<br>";
            echo "</div>";
        }
        
        if (LOG_ERRORS) {
            error_log("Database Error: $message", 3, ERROR_LOG_PATH);
        }
    }
    
    /**
     * Close database connection
     */
    public function close() {
        $this->connection = null;
    }
    
    /**
     * Destructor - Close database connection
     */
    public function __destruct() {
        $this->close();
    }
}
?>