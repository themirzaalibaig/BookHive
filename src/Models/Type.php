<?php

namespace BookHive\Models;

use BookHive\Config\Database;

class Type
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getDb(): Database
    {
        return $this->db;
    }

    public function getAll(int $page = 1, int $perPage = 10, ?string $search = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        
        $sql = "SELECT * FROM types WHERE 1=1";

        if ($search) {
            $sql .= " AND Name LIKE ?";
            $params[] = "%{$search}%";
        }

        $sql .= " ORDER BY Name ASC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function count(?string $search = null): int
    {
        $params = [];
        $sql = "SELECT COUNT(*) FROM types WHERE 1=1";

        if ($search) {
            $sql .= " AND Name LIKE ?";
            $params[] = "%{$search}%";
        }

        return (int) $this->db->fetchColumn($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM types WHERE id = ?", [$id]);
    }

    public function create(array $data): int
    {
        return $this->db->insert('types', ['Name' => $data['Name'] ?? null]);
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->update('types', ['Name' => $data['Name'] ?? null], 'id = ?', [$id]) > 0;
    }

    public function delete(int $id): bool
    {
        // Check if type is used by any books
        $hasBooks = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM books WHERE Book_Type = ?",
            [$id]
        );

        if ($hasBooks > 0) {
            return false;
        }

        return $this->db->delete('types', 'id = ?', [$id]) > 0;
    }
}
