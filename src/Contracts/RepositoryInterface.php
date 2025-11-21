<?php

namespace App\Contracts;

/**
 * Repository Interface
 * Base contract for all repositories
 */
interface RepositoryInterface
{
    /**
     * Find entity by ID
     * 
     * @param int $id
     * @return mixed|null
     */
    public function findById(int $id): mixed;

    /**
     * Find all entities
     * 
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findAll(int $limit = 100, int $offset = 0): array;

    /**
     * Delete entity by ID
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}

