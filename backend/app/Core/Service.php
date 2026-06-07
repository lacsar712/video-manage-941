<?php

namespace App\Core;

use PDO;

abstract class Service
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    protected function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    protected function commit(): void
    {
        $this->db->commit();
    }

    protected function rollBack(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
