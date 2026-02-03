<?php

namespace BookHive\Models;

use BookHive\Config\Database;

class User
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
        
        $sql = "SELECT * FROM users WHERE 1=1";

        if ($search) {
            $sql .= " AND (Name LIKE ? OR Membership_Number LIKE ? OR Contact LIKE ?)";
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
        $sql = "SELECT COUNT(*) FROM users WHERE 1=1";

        if ($search) {
            $sql .= " AND (Name LIKE ? OR Membership_Number LIKE ? OR Contact LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        return (int) $this->db->fetchColumn($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    }

    public function findByMembershipNumber(string $membershipNumber): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE Membership_Number = ?",
            [$membershipNumber]
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'Membership_Number' => $data['Membership_Number'] ?? null,
            'Name' => $data['Name'] ?? null,
            'Contact' => $data['Contact'] ?? null,
            'ID_Number' => $data['ID_Number'] ?? null,
        ];

        return $this->db->insert('users', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [
            'Membership_Number' => $data['Membership_Number'] ?? null,
            'Name' => $data['Name'] ?? null,
            'Contact' => $data['Contact'] ?? null,
            'ID_Number' => $data['ID_Number'] ?? null,
        ];

        return $this->db->update('users', $updateData, 'id = ?', [$id]) > 0;
    }

    public function delete(int $id): bool
    {
        // Check if user has active book issues
        $hasIssues = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM book_issue WHERE Member = ? AND Status = 'issued'",
            [$id]
        );

        if ($hasIssues > 0) {
            return false;
        }

        return $this->db->delete('users', 'id = ?', [$id]) > 0;
    }
}
