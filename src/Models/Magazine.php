<?php

namespace BookHive\Models;

use BookHive\Config\Database;

class Magazine
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
        
        $sql = "SELECT * FROM magazines WHERE 1=1";

        if ($search) {
            $sql .= " AND (Name LIKE ? OR Type LIKE ? OR Publisher LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function count(?string $search = null): int
    {
        $params = [];
        $sql = "SELECT COUNT(*) FROM magazines WHERE 1=1";

        if ($search) {
            $sql .= " AND (Name LIKE ? OR Type LIKE ? OR Publisher LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        return (int) $this->db->fetchColumn($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM magazines WHERE id = ?", [$id]);
    }

    public function create(array $data): int
    {
        $insertData = [
            'Type' => $data['Type'] ?? null,
            'Name' => $data['Name'] ?? null,
            'Date_Of_Receipt' => $data['Date_Of_Receipt'] ?? null,
            'Date_Published' => $data['Date_Published'] ?? null,
            'Pages' => $data['Pages'] ?? null,
            'Price' => $data['Price'] ?? 0.00,
            'Publisher' => $data['Publisher'] ?? null,
        ];

        return $this->db->insert('magazines', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [
            'Type' => $data['Type'] ?? null,
            'Name' => $data['Name'] ?? null,
            'Date_Of_Receipt' => $data['Date_Of_Receipt'] ?? null,
            'Date_Published' => $data['Date_Published'] ?? null,
            'Pages' => $data['Pages'] ?? null,
            'Price' => $data['Price'] ?? 0.00,
            'Publisher' => $data['Publisher'] ?? null,
        ];

        return $this->db->update('magazines', $updateData, 'id = ?', [$id]) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->delete('magazines', 'id = ?', [$id]) > 0;
    }
}
