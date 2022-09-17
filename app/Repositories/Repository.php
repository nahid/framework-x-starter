<?php

namespace App\Repositories;

use React\MySQL\ConnectionInterface;
use function React\Async\await;

abstract class Repository
{
    public function __construct(
        protected ConnectionInterface $db,
    ) {
    }

    public abstract function getTableName(): string;

    public function getConnection(): ConnectionInterface
    {
        return $this->db;
    }

    public function all(): array
    {
        $result = await($this->db->query('SELECT * FROM ' . $this->getTableName()));

        assert($result instanceof \React\MySQL\QueryResult);

        return $result->resultRows;
    }

    public function find(int $id): ?array
    {
        $result = await($this->db->query('SELECT * FROM ' . $this->getTableName() . ' WHERE id = ? LIMIT 1', [$id]));

        assert($result instanceof \React\MySQL\QueryResult);

        return $result->resultRows[0] ?? null;
    }

}