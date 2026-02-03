<?php

namespace BookHive\Models;

use BookHive\Config\Database;

class Fine
{
    private Database $db;
    private float $finePerDay = 10.00; // Default $10 per day

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Calculate fine for an issue
     */
    public function calculateFine(int $issueId): float
    {
        $issue = $this->db->fetchOne(
            "SELECT * FROM book_issue WHERE id = ?",
            [$issueId]
        );

        if (!$issue || $issue['Status'] !== 'issued') {
            return 0.00;
        }

        $dueDate = strtotime($issue['Return_Date']);
        $today = strtotime(date('Y-m-d'));

        if ($today <= $dueDate) {
            return 0.00;
        }

        $daysOverdue = floor(($today - $dueDate) / 86400);
        return $daysOverdue * $this->finePerDay;
    }

    /**
     * Get all fines (paid and unpaid)
     */
    public function getAll(int $page = 1, int $perPage = 10, ?string $status = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        
        $sql = "SELECT 
                    f.*,
                    u.Name as member_name,
                    u.Membership_Number,
                    b.Book_Title,
                    bi.Issue_Date,
                    bi.Return_Date
                FROM fines f
                LEFT JOIN book_issue bi ON f.issue_id = bi.id
                LEFT JOIN users u ON bi.Member = u.id
                LEFT JOIN books b ON bi.Book_Number = b.id
                WHERE 1=1";

        if ($status) {
            $sql .= " AND f.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY f.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Count fines
     */
    public function count(?string $status = null): int
    {
        $params = [];
        $sql = "SELECT COUNT(*) FROM fines WHERE 1=1";

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        return (int) $this->db->fetchColumn($sql, $params);
    }

    /**
     * Record a fine
     */
    public function create(int $issueId, float $amount, string $reason = 'Overdue'): int
    {
        return $this->db->insert('fines', [
            'issue_id' => $issueId,
            'amount' => $amount,
            'reason' => $reason,
            'status' => 'unpaid',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Mark fine as paid
     */
    public function markAsPaid(int $fineId, string $paymentMethod = 'cash', ?string $transactionId = null): bool
    {
        return $this->db->update('fines', [
            'status' => 'paid',
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
            'paid_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$fineId]) > 0;
    }

    /**
     * Waive a fine
     */
    public function waive(int $fineId, string $reason): bool
    {
        return $this->db->update('fines', [
            'status' => 'waived',
            'waiver_reason' => $reason,
            'waived_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$fineId]) > 0;
    }

    /**
     * Get total unpaid fines for a member
     */
    public function getMemberUnpaidFines(int $memberId): float
    {
        $sql = "SELECT COALESCE(SUM(f.amount), 0) as total
                FROM fines f
                LEFT JOIN book_issue bi ON f.issue_id = bi.id
                WHERE bi.Member = ? AND f.status = 'unpaid'";
        
        $result = $this->db->fetchOne($sql, [$memberId]);
        return (float) ($result['total'] ?? 0);
    }

    /**
     * Get fine statistics
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        $stats['total_fines'] = $this->db->fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM fines");
        $stats['paid_fines'] = $this->db->fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM fines WHERE status = 'paid'");
        $stats['unpaid_fines'] = $this->db->fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM fines WHERE status = 'unpaid'");
        $stats['waived_fines'] = $this->db->fetchColumn("SELECT COALESCE(SUM(amount), 0) FROM fines WHERE status = 'waived'");
        
        return $stats;
    }
}
