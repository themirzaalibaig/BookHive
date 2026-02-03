<?php

namespace BookHive\Models;

use BookHive\Config\Database;

class BookIssue
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(int $page = 1, int $perPage = 10, ?string $status = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        
        $sql = "SELECT bi.*, 
                       u.Name as member_name, 
                       u.Membership_Number,
                       b.Book_Title,
                       b.ISBN_NO
                FROM book_issue bi
                LEFT JOIN users u ON bi.Member = u.id
                LEFT JOIN books b ON bi.Book_Number = b.id
                WHERE 1=1";

        if ($status) {
            $sql .= " AND bi.Status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY bi.id DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function count(?string $status = null): int
    {
        $params = [];
        $sql = "SELECT COUNT(*) FROM book_issue WHERE 1=1";

        if ($status) {
            $sql .= " AND Status = ?";
            $params[] = $status;
        }

        return (int) $this->db->fetchColumn($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT bi.*, 
                    u.Name as member_name, 
                    u.Membership_Number,
                    b.Book_Title,
                    b.ISBN_NO
             FROM book_issue bi
             LEFT JOIN users u ON bi.Member = u.id
             LEFT JOIN books b ON bi.Book_Number = b.id
             WHERE bi.id = ?",
            [$id]
        );
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();

        try {
            // Decrease book quantity
            $this->db->query(
                "UPDATE books SET Quantity = Quantity - 1 WHERE id = ? AND Quantity > 0",
                [$data['Book_Number']]
            );

            // Create issue record
            $insertData = [
                'Member' => $data['Member'],
                'Number' => $data['Number'] ?? null,
                'Book_Number' => $data['Book_Number'],
                'Book_Title' => $data['Book_Title'],
                'Issue_Date' => $data['Issue_Date'] ?? date('Y-m-d'),
                'Return_Date' => $data['Return_Date'],
                'Status' => 'issued',
                'issue_id' => $data['issue_id'] ?? null,
            ];

            $id = $this->db->insert('book_issue', $insertData);
            
            $this->db->commit();
            return $id;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function returnBook(int $issueId): bool
    {
        $this->db->beginTransaction();

        try {
            $issue = $this->find($issueId);
            if (!$issue) {
                throw new \Exception("Issue not found");
            }

            // Update issue status
            $this->db->update(
                'book_issue',
                ['Status' => 'returned'],
                'id = ?',
                [$issueId]
            );

            // Increase book quantity
            $this->db->query(
                "UPDATE books SET Quantity = Quantity + 1 WHERE id = ?",
                [$issue['Book_Number']]
            );

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getOverdueIssues(): array
    {
        return $this->db->fetchAll(
            "SELECT bi.*, 
                    u.Name as member_name, 
                    u.Membership_Number,
                    b.Book_Title,
                    DATEDIFF(CURDATE(), bi.Return_Date) as days_overdue
             FROM book_issue bi
             LEFT JOIN users u ON bi.Member = u.id
             LEFT JOIN books b ON bi.Book_Number = b.id
             WHERE bi.Status = 'issued' AND bi.Return_Date < CURDATE()
             ORDER BY bi.Return_Date ASC"
        );
    }
}
