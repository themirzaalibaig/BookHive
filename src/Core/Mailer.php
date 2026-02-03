<?php

namespace BookHive\Core;

class Mailer
{
    private string $fromEmail;
    private string $fromName;

    public function __construct()
    {
        $this->fromEmail = env('MAIL_FROM_ADDRESS', 'library@bookhive.com');
        $this->fromName = env('MAIL_FROM_NAME', 'BookHive Library');
    }

    /**
     * Send email
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $headers = [];
        $headers[] = 'From: ' . $this->fromName . ' <' . $this->fromEmail . '>';
        $headers[] = 'Reply-To: ' . $this->fromEmail;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        if ($isHtml) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
        }

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * Send due date reminder
     */
    public function sendDueDateReminder(array $member, array $book, string $dueDate): bool
    {
        $subject = 'Book Due Date Reminder - BookHive Library';
        
        $body = $this->getEmailTemplate([
            'title' => 'Book Due Date Reminder',
            'content' => "
                <p>Dear {$member['Name']},</p>
                <p>This is a friendly reminder that the following book is due soon:</p>
                <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <strong>Book:</strong> {$book['Book_Title']}<br>
                    <strong>Author:</strong> {$book['Author_Name']}<br>
                    <strong>Due Date:</strong> " . date('F d, Y', strtotime($dueDate)) . "
                </div>
                <p>Please return the book by the due date to avoid late fees.</p>
                <p>Thank you for using BookHive Library!</p>
            "
        ]);

        return $this->send($member['Contact'], $subject, $body);
    }

    /**
     * Send overdue notice
     */
    public function sendOverdueNotice(array $member, array $book, int $daysOverdue, float $fine): bool
    {
        $subject = 'Overdue Book Notice - BookHive Library';
        
        $body = $this->getEmailTemplate([
            'title' => 'Overdue Book Notice',
            'content' => "
                <p>Dear {$member['Name']},</p>
                <p style='color: #dc2626;'><strong>The following book is overdue:</strong></p>
                <div style='background: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0;'>
                    <strong>Book:</strong> {$book['Book_Title']}<br>
                    <strong>Author:</strong> {$book['Author_Name']}<br>
                    <strong>Days Overdue:</strong> {$daysOverdue} days<br>
                    <strong>Fine Amount:</strong> $" . number_format($fine, 2) . "
                </div>
                <p>Please return the book as soon as possible to avoid additional charges.</p>
                <p>Thank you for your cooperation.</p>
            "
        ]);

        return $this->send($member['Contact'], $subject, $body);
    }

    /**
     * Send welcome email to new member
     */
    public function sendWelcomeEmail(array $member): bool
    {
        $subject = 'Welcome to BookHive Library!';
        
        $body = $this->getEmailTemplate([
            'title' => 'Welcome to BookHive!',
            'content' => "
                <p>Dear {$member['Name']},</p>
                <p>Welcome to BookHive Library! We're excited to have you as a member.</p>
                <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <strong>Your Membership Details:</strong><br>
                    <strong>Membership Number:</strong> {$member['Membership_Number']}<br>
                    <strong>Name:</strong> {$member['Name']}
                </div>
                <p>You can now start borrowing books from our extensive collection.</p>
                <p><strong>Library Hours:</strong> Monday - Saturday, 9:00 AM - 6:00 PM</p>
                <p>If you have any questions, feel free to contact us.</p>
                <p>Happy Reading!</p>
            "
        ]);

        return $this->send($member['Contact'], $subject, $body);
    }

    /**
     * Send book reservation notification
     */
    public function sendReservationNotification(array $member, array $book): bool
    {
        $subject = 'Book Available - BookHive Library';
        
        $body = $this->getEmailTemplate([
            'title' => 'Your Reserved Book is Available!',
            'content' => "
                <p>Dear {$member['Name']},</p>
                <p>Good news! The book you reserved is now available:</p>
                <div style='background: #f0fdf4; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <strong>Book:</strong> {$book['Book_Title']}<br>
                    <strong>Author:</strong> {$book['Author_Name']}
                </div>
                <p>Please visit the library within 3 days to collect your book.</p>
                <p>Thank you!</p>
            "
        ]);

        return $this->send($member['Contact'], $subject, $body);
    }

    /**
     * Get email template
     */
    private function getEmailTemplate(array $data): string
    {
        $title = $data['title'] ?? 'BookHive Library';
        $content = $data['content'] ?? '';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$title}</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                <h1 style='margin: 0; font-size: 24px;'>📚 BookHive Library</h1>
            </div>
            <div style='background: white; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;'>
                {$content}
            </div>
            <div style='text-align: center; margin-top: 20px; color: #6b7280; font-size: 12px;'>
                <p>This is an automated message from BookHive Library System</p>
                <p>&copy; " . date('Y') . " BookHive Library. All rights reserved.</p>
            </div>
        </body>
        </html>
        ";
    }
}
