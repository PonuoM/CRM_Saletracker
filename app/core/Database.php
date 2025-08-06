<?php
/**
 * Database Connection Class
 * จัดการการเชื่อมต่อฐานข้อมูลและ query operations
 */

// Load configuration
require_once __DIR__ . '/../../config/config.php';

class Database {
    private $pdo;
    private static $instance = null;
    
    public function __construct() {
        $this->connect();
    }
    
    /**
     * Singleton pattern
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Connect to database
     */
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get PDO instance
     */
    public function getPdo() {
        return $this->pdo;
    }
    
    /**
     * Get database connection (alias for getPdo)
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Execute query
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert data
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Execute INSERT query and return success status
     */
    public function executeInsert($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Insert failed: " . $e->getMessage());
        }
    }
    
    /**
     * Update data
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        // Handle mixed parameter types by converting positional to named
        $finalParams = $data;
        
        // If WHERE clause uses positional parameters (?), convert them to named
        if (strpos($where, '?') !== false) {
            $whereParts = explode('?', $where);
            $newWhere = '';
            $paramIndex = 0;
            
            foreach ($whereParts as $i => $part) {
                $newWhere .= $part;
                if ($i < count($whereParts) - 1) {
                    $paramName = 'where_param_' . $paramIndex;
                    $newWhere .= ':' . $paramName;
                    $finalParams[$paramName] = $whereParams[$paramIndex];
                    $paramIndex++;
                }
            }
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$newWhere}";
        } else {
            // If WHERE clause already uses named parameters, merge directly
            $finalParams = array_merge($data, $whereParams);
        }
        
        $stmt = $this->query($sql, $finalParams);
        return $stmt->rowCount();
    }
    
    /**
     * Delete data
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * Check if table exists
     */
    public function tableExists($tableName) {
        try {
            // ใช้ information_schema แทน SHOW TABLES LIKE เพื่อความปลอดภัย
            $sql = "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$tableName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['count'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get table structure
     */
    public function getTableStructure($tableName) {
        $sql = "DESCRIBE {$tableName}";
        return $this->fetchAll($sql);
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}
?> 