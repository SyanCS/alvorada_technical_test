<?php

namespace App\Contracts;

use PDO;

/**
 * Database Interface
 * Contract for database implementations
 */
interface DatabaseInterface
{
    /**
     * Get PDO connection
     */
    public function getConnection(): PDO;

    /**
     * Execute a prepared statement query
     */
    public function query(string $query, array $params = []): \PDOStatement;

    /**
     * Fetch all rows
     */
    public function fetchAll(string $query, array $params = []): array;

    /**
     * Fetch single row
     */
    public function fetchOne(string $query, array $params = []): array|false;

    /**
     * Insert record and return last insert ID
     */
    public function insert(string $query, array $params = []): int;

    /**
     * Update/Delete and return affected rows count
     */
    public function execute(string $query, array $params = []): int;

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool;

    /**
     * Commit transaction
     */
    public function commit(): bool;

    /**
     * Rollback transaction
     */
    public function rollback(): bool;
}

