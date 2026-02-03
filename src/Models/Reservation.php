<?php

namespace BookHive\Models;

use BookHive\Config\Database;

class Reservation
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a reservation
     */
    public function create(int $bookId, int $memberId): int
    {
        // Check if book is available
        $book = $this->db->fetchOne("SELECT Quantity FROM books WHERE id = ?", [$bookId]);
        if ($book && $book['Quantity'] > 0) {
            throw new \Exception('Book is currently available. Please issue it directly.');
        }

        // Check if member already has a reservation for this book
        $existing = $this->db->fetchOne(
            "SELECT id FROM book_reservations WHERE book_id = ? AND member_id = ? AND status = 'active'",
            [$bookId, $memberId]
        );

        if ($existing) {
            throw new \Exception('You already have an active reservation for this book.');
        }

        $expiresAt = date('Y-m-d H:i:s', strtotime('+3 days'));

        return $this->db->insert('book_reservations', [
            'book_id' => $bookId,
            'member_id' => $memberId,
            'reservation_date' => date('Y-m-d'),
            'status' => 'active',
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get all reservations
     */
    public function getAll(int $page = 1, int $perPage = 10, ?string $status = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        
        $sql = "SELECT 
                    r.*,
                    b.Book_Title,
                    b.Author_Name,
                    b.ISBN_NO,
                    u.Name as member_name,
                    u.Membership_Number,
                    u.Contact
                FROM book_reservations r
                LEFT JOIN books b ON r.book_id = b.id
                LEFT JOIN users u ON r.member_id = u.id
                WHERE 1=1";

        if ($status) {
            $sql .= " AND r.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Count reservations
     */
    public function count(?string $status = null): int
    {
        $params = [];
        $sql = "SELECT COUNT(*) FROM book_reservations WHERE 1=1";

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        return (int) $this->db->fetchColumn($sql, $params);
    }

    /**
     * Get reservations for a specific book
     */
    public function getByBook(int $bookId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.Name as member_name, u.Membership_Number, u.Contact
             FROM book_reservations r
             LEFT JOIN users u ON r.member_id = u.id
             WHERE r.book_id = ? AND r.status = 'active'
             ORDER BY r.created_at ASC",
            [$bookId]
        );
    }

    /**
     * Get reservations for a specific member
     */
    public function getByMember(int $memberId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, b.Book_Title, b.Author_Name
             FROM book_reservations r
             LEFT JOIN books b ON r.book_id = b.id
             WHERE r.member_id = ? AND r.status = 'active'
             ORDER BY r.created_at DESC",
            [$memberId]
        );
    }

    /**
     * Fulfill a reservation (book is now available)
     */
    public function fulfill(int $reservationId): bool
    {
        return $this->db->update('book_reservations', [
            'status' => 'fulfilled',
            'fulfilled_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$reservationId]) > 0;
    }

    /**
     * Cancel a reservation
     */
    public function cancel(int $reservationId): bool
    {
        return $this->db->update('book_reservations', [
            'status' => 'cancelled'
        ], 'id = ?', [$reservationId]) > 0;
    }

    /**
     * Expire old reservations
     */
    public function expireOld(): int
    {
        return $this->db->update('book_reservations', [
            'status' => 'expired'
        ], "status = 'active' AND expires_at < NOW()", []);
    }

    /**
     * Notify next person in queue when book is returned
     */
    public function notifyNext(int $bookId): ?array
    {
        $reservations = $this->getByBook($bookId);
        
        if (empty($reservations)) {
            return null;
        }

        $nextReservation = $reservations[0];
        
        // Mark as notified
        $this->db->update('book_reservations', [
            'notified_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$nextReservation['id']]);

        return $nextReservation;
    }
}
