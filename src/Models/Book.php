<?php

namespace BookHive\Models;

use BookHive\Config\Database;

class Book
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

    public function getAll(int $page = 1, int $perPage = 10, ?string $search = null, ?int $typeFilter = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        
        $sql = "SELECT b.*, t.Name as type_name 
                FROM books b 
                LEFT JOIN types t ON b.Book_Type = t.id 
                WHERE 1=1";

        if ($search) {
            $sql .= " AND (b.Book_Title LIKE ? OR b.Author_Name LIKE ? OR b.ISBN_NO LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if ($typeFilter) {
            $sql .= " AND b.Book_Type = ?";
            $params[] = $typeFilter;
        }

        $sql .= " ORDER BY b.id DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function count(?string $search = null, ?int $typeFilter = null): int
    {
        $params = [];
        $sql = "SELECT COUNT(*) FROM books WHERE 1=1";

        if ($search) {
            $sql .= " AND (Book_Title LIKE ? OR Author_Name LIKE ? OR ISBN_NO LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if ($typeFilter) {
            $sql .= " AND Book_Type = ?";
            $params[] = $typeFilter;
        }

        return (int) $this->db->fetchColumn($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT b.*, t.Name as type_name 
             FROM books b 
             LEFT JOIN types t ON b.Book_Type = t.id 
             WHERE b.id = ?",
            [$id]
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'ISBN_NO' => $data['ISBN_NO'] ?? null,
            'Book_Title' => $data['Book_Title'] ?? null,
            'Book_Type' => $data['Book_Type'] ?? null,
            'Author_Name' => $data['Author_Name'] ?? null,
            'Quantity' => $data['Quantity'] ?? 0,
            'Purchase_Date' => $data['Purchase_Date'] ?? null,
            'Edition' => $data['Edition'] ?? null,
            'Price' => $data['Price'] ?? 0.00,
            'Pages' => $data['Pages'] ?? null,
            'Publisher' => $data['Publisher'] ?? null,
        ];

        return $this->db->insert('books', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [
            'ISBN_NO' => $data['ISBN_NO'] ?? null,
            'Book_Title' => $data['Book_Title'] ?? null,
            'Book_Type' => $data['Book_Type'] ?? null,
            'Author_Name' => $data['Author_Name'] ?? null,
            'Quantity' => $data['Quantity'] ?? 0,
            'Purchase_Date' => $data['Purchase_Date'] ?? null,
            'Edition' => $data['Edition'] ?? null,
            'Price' => $data['Price'] ?? 0.00,
            'Pages' => $data['Pages'] ?? null,
            'Publisher' => $data['Publisher'] ?? null,
        ];

        return $this->db->update('books', $updateData, 'id = ?', [$id]) > 0;
    }

    public function delete(int $id): bool
    {
        // Check if book has active issues
        $hasIssues = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM book_issue WHERE Book_Number = ? AND Status = 'issued'",
            [$id]
        );

        if ($hasIssues > 0) {
            return false;
        }

        return $this->db->delete('books', 'id = ?', [$id]) > 0;
    }

    public function getTypes(): array
    {
        return $this->db->fetchAll("SELECT * FROM types ORDER BY Name");
    }
}
