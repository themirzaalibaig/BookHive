<?php

namespace BookHive\Core;

class Report
{
    private \BookHive\Config\Database $db;

    public function __construct()
    {
        $this->db = \BookHive\Config\Database::getInstance();
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $stats = [];

        // Total books
        $stats['total_books'] = $this->db->fetchColumn("SELECT COUNT(*) FROM books");
        
        // Total members
        $stats['total_members'] = $this->db->fetchColumn("SELECT COUNT(*) FROM users");
        
        // Currently issued books
        $stats['issued_books'] = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM book_issue WHERE Status = 'issued'"
        );
        
        // Overdue books
        $stats['overdue_books'] = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM book_issue WHERE Status = 'issued' AND Return_Date < CURDATE()"
        );
        
        // Available books
        $stats['available_books'] = $this->db->fetchColumn(
            "SELECT SUM(Quantity) FROM books WHERE Quantity > 0"
        );
        
        // Total fines (calculated)
        $overdueIssues = $this->db->fetchAll(
            "SELECT DATEDIFF(CURDATE(), Return_Date) as days_overdue 
             FROM book_issue 
             WHERE Status = 'issued' AND Return_Date < CURDATE()"
        );
        
        $totalFines = 0;
        foreach ($overdueIssues as $issue) {
            $totalFines += $issue['days_overdue'] * 10; // $10 per day
        }
        $stats['total_fines'] = $totalFines;

        return $stats;
    }

    /**
     * Get monthly issue statistics for charts
     */
    public function getMonthlyIssueStats(int $months = 6): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(Issue_Date, '%Y-%m') as month,
                    COUNT(*) as total_issues
                FROM book_issue
                WHERE Issue_Date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(Issue_Date, '%Y-%m')
                ORDER BY month ASC";
        
        return $this->db->fetchAll($sql, [$months]);
    }

    /**
     * Get most popular books
     */
    public function getPopularBooks(int $limit = 10): array
    {
        $sql = "SELECT 
                    b.Book_Title,
                    b.Author_Name,
                    COUNT(bi.id) as issue_count
                FROM books b
                LEFT JOIN book_issue bi ON b.id = bi.Book_Number
                GROUP BY b.id
                ORDER BY issue_count DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Get most active members
     */
    public function getActiveMembers(int $limit = 10): array
    {
        $sql = "SELECT 
                    u.Name,
                    u.Membership_Number,
                    COUNT(bi.id) as total_issues
                FROM users u
                LEFT JOIN book_issue bi ON u.id = bi.Member
                GROUP BY u.id
                ORDER BY total_issues DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Get books by category distribution
     */
    public function getCategoryDistribution(): array
    {
        $sql = "SELECT 
                    t.Name as category,
                    COUNT(b.id) as book_count
                FROM types t
                LEFT JOIN books b ON t.id = b.Book_Type
                GROUP BY t.id
                ORDER BY book_count DESC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Get overdue report
     */
    public function getOverdueReport(): array
    {
        $sql = "SELECT 
                    bi.*,
                    u.Name as member_name,
                    u.Membership_Number,
                    u.Contact,
                    b.Book_Title,
                    b.ISBN_NO,
                    DATEDIFF(CURDATE(), bi.Return_Date) as days_overdue,
                    (DATEDIFF(CURDATE(), bi.Return_Date) * 10) as fine_amount
                FROM book_issue bi
                LEFT JOIN users u ON bi.Member = u.id
                LEFT JOIN books b ON bi.Book_Number = b.id
                WHERE bi.Status = 'issued' AND bi.Return_Date < CURDATE()
                ORDER BY days_overdue DESC";
        
        return $this->db->fetchAll($sql);
    }

    /**
     * Get issue/return statistics by date range
     */
    public function getIssueReturnStats(string $startDate, string $endDate): array
    {
        $stats = [];
        
        // Issues in date range
        $stats['total_issues'] = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM book_issue WHERE Issue_Date BETWEEN ? AND ?",
            [$startDate, $endDate]
        );
        
        // Returns in date range
        $stats['total_returns'] = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM book_issue WHERE Status = 'returned' AND Issue_Date BETWEEN ? AND ?",
            [$startDate, $endDate]
        );
        
        // Daily breakdown
        $stats['daily_breakdown'] = $this->db->fetchAll(
            "SELECT 
                DATE(Issue_Date) as date,
                COUNT(*) as issues
             FROM book_issue
             WHERE Issue_Date BETWEEN ? AND ?
             GROUP BY DATE(Issue_Date)
             ORDER BY date ASC",
            [$startDate, $endDate]
        );
        
        return $stats;
    }

    /**
     * Export data to CSV
     */
    public function exportToCSV(array $data, array $headers, string $filename): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}
