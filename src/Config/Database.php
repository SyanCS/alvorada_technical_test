<?php

namespace App\Config;

use App\Contracts\DatabaseInterface;
use PDO;
use PDOException;

/**
 * Database Connection Class
 * Singleton pattern to ensure only one database connection exists
 * Provides PDO connection with prepared statement support
 * Implements DatabaseInterface for loose coupling
 */
class Database implements DatabaseInterface
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    
    private string $host;
    private string $dbName;
    private string $username;
    private string $password;
    private string $charset;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $config = AppConfig::getInstance();
        
        $this->host = $config->get('database.host');
        $this->dbName = $config->get('database.name');
        $this->username = $config->get('database.user');
        $this->password = $config->get('database.password');
        $this->charset = $config->get('database.charset');
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     * Creates connection if it doesn't exist
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Establish database connection
     */
    private function connect(): void
    {
        try {
            // PostgreSQL connection string
            $dsn = "pgsql:host={$this->host};dbname={$this->dbName};options='--client_encoding=UTF8'";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
            // Enable PostGIS extension if not already enabled
            $this->connection->exec("CREATE EXTENSION IF NOT EXISTS postgis");
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new PDOException("Database connection failed. Please check your configuration.");
        }
    }

    /**
     * Execute a prepared statement query
     * 
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind
     * @return \PDOStatement
     */
    public function query(string $query, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch all rows
     */
    public function fetchAll(string $query, array $params = []): array
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Fetch single row
     */
    public function fetchOne(string $query, array $params = []): array|false
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetch();
    }

    /**
     * Insert record and return last insert ID
     */
    public function insert(string $query, array $params = []): int
    {
        $this->query($query, $params);
        return (int) $this->getConnection()->lastInsertId();
    }

    /**
     * Update/Delete and return affected rows count
     */
    public function execute(string $query, array $params = []): int
    {
        $stmt = $this->query($query, $params);
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->getConnection()->rollBack();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}


