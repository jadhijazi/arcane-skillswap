<?php
/**
 * ============================================================================
 * Database Connection & PDO Wrapper
 * ============================================================================
 * 
 * Provides secure PDO database access with prepared statements,
 * connection pooling, and error handling.
 * 
 * Author: Muhammad Ibrahim Khan (Database & Security Lead)
 * SCSM2223 Cross-Platform Application Development
 */

class Database {
    private static ?Database $instance = null;
    private PDO $connection;
    private string $host;
    private string $db;
    private string $user;
    private string $password;
    
    /**
     * Constructor - Initialize database credentials
     * All credentials should come from environment variables for security
     */
    private function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db = $_ENV['DB_NAME'] ?? 'skillswap';
        $this->user = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
    }
    
    /**
     * Singleton pattern - Get or create database instance
     * Ensures only one connection is maintained
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->connect();
        }
        return self::$instance;
    }
    
    /**
     * Establish PDO connection with error handling
     */
    private function connect(): void {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4";
            
            $this->connection = new PDO(
                $dsn,
                $this->user,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_FOUND_ROWS => true,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get the PDO connection object
     */
    public function getConnection(): PDO {
        return $this->connection;
    }
    
    /**
     * Execute a prepared statement with parameters
     * 
     * @param string $sql SQL query with ? placeholders
     * @param array $params Array of parameters to bind
     * @return PDOStatement
     */
    public function execute(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new RuntimeException("Query execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch a single row (associative array)
     */
    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Fetch all rows (array of associative arrays)
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetch a single column value
     */
    public function fetchColumn(string $sql, array $params = []): mixed {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Insert a record and return the last inserted ID
     */
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, array_values($data));
        
        return (int) $this->connection->lastInsertId();
    }
    
    /**
     * Update records and return number of affected rows
     */
    public function update(string $table, array $data, array $conditions): int {
        $setClause = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $whereClause = implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($conditions)));
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
        $params = array_merge(array_values($data), array_values($conditions));
        
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Delete records and return number of affected rows
     */
    public function delete(string $table, array $conditions): int {
        $whereClause = implode(' AND ', array_map(fn($k) => "{$k} = ?", array_keys($conditions)));
        $sql = "DELETE FROM {$table} WHERE {$whereClause}";
        
        $stmt = $this->execute($sql, array_values($conditions));
        return $stmt->rowCount();
    }
    
    /**
     * Start a transaction
     */
    public function beginTransaction(): void {
        $this->connection->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit(): void {
        $this->connection->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollBack(): void {
        $this->connection->rollBack();
    }
    
    /**
     * Close the database connection
     */
    public function close(): void {
        $this->connection = null;
        self::$instance = null;
    }
}