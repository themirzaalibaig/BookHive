-- Add fines table
CREATE TABLE IF NOT EXISTS `fines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT 'Overdue',
  `status` enum('unpaid','paid','waived') NOT NULL DEFAULT 'unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `waiver_reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `waived_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `issue_id` (`issue_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add book reservations table
CREATE TABLE IF NOT EXISTS `book_reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `status` enum('active','fulfilled','cancelled','expired') NOT NULL DEFAULT 'active',
  `notified_at` datetime DEFAULT NULL,
  `fulfilled_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `book_id` (`book_id`),
  KEY `member_id` (`member_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add email notifications log table
CREATE TABLE IF NOT EXISTS `email_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` enum('sent','failed','pending') NOT NULL DEFAULT 'pending',
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add activity logs table
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add settings table for configurable options
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL UNIQUE,
  `value` text DEFAULT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `settings` (`key`, `value`, `type`, `description`) VALUES
('fine_per_day', '10.00', 'decimal', 'Fine amount per day for overdue books'),
('max_books_per_member', '5', 'integer', 'Maximum books a member can borrow at once'),
('loan_period_days', '14', 'integer', 'Default loan period in days'),
('reservation_hold_days', '3', 'integer', 'Days to hold a reserved book'),
('send_due_reminders', '1', 'boolean', 'Send due date reminder emails'),
('reminder_days_before', '2', 'integer', 'Days before due date to send reminder'),
('library_email', 'library@bookhive.com', 'string', 'Library email address'),
('library_phone', '', 'string', 'Library phone number'),
('library_address', '', 'text', 'Library physical address')
ON DUPLICATE KEY UPDATE `key` = `key`;
