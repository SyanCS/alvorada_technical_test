<?php
/**
 * Database Connection Helper
 * This file provides a simple way to get a database connection
 * for use in API endpoints.
 * 
 * Note: The actual database connection is managed by src/Config/Database.php
 * This file is provided for compatibility with the requirements structure.
 */

// Load autoloader
require_once __DIR__ . '/../src/Config/Autoloader.php';

use App\Config\Database;

/**
 * Get database connection
 * @return PDO
 */
function getDbConnection(): PDO
{
    $db = Database::getInstance();
    return $db->getConnection();
}

/**
 * Execute a query and return results
 * @param string $query
 * @param array $params
 * @return array
 */
function dbQuery(string $query, array $params = []): array
{
    $db = Database::getInstance();
    return $db->fetchAll($query, $params);
}

/**
 * Execute an insert query and return the last insert ID
 * @param string $query
 * @param array $params
 * @return int
 */
function dbInsert(string $query, array $params = []): int
{
    $db = Database::getInstance();
    return $db->insert($query, $params);
}

/**
 * Execute a query (for UPDATE/DELETE)
 * @param string $query
 * @param array $params
 * @return bool
 */
function dbExecute(string $query, array $params = []): bool
{
    $db = Database::getInstance();
    $db->query($query, $params);
    return true;
}

