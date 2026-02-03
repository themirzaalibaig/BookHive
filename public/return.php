<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Models\BookIssue;

$auth = new Auth();
$auth->requireAuth();
$auth->requirePermission('Book_Issue', 'edit');

$issueModel = new BookIssue();

// Handle return
if (isset($_GET['issue_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $issueId = (int)$_GET['issue_id'];
    
    try {
        $issueModel->returnBook($issueId);
        flash('success', 'Book returned successfully');
        redirect('issue.php');
    } catch (\Exception $e) {
        flash('error', 'Error returning book: ' . $e->getMessage());
    }
}

// Get issue details
$issueId = isset($_GET['issue_id']) ? (int)$_GET['issue_id'] : null;
$issue = $issueId ? $issueModel->find($issueId) : null;

if (!$issue) {
    flash('error', 'Issue not found');
    redirect('issue.php');
}

// Calculate fine if overdue
$fine = 0;
if ($issue['Status'] === 'issued' && strtotime($issue['Return_Date']) < time()) {
    $daysOverdue = floor((time() - strtotime($issue['Return_Date'])) / 86400);
    $fine = $daysOverdue * 10; // $10 per day
}

ob_start();
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Return Book</h2>
    </div>
    
    <div class="card-body">
        <div style="margin-bottom: var(--space-6);">
            <div style="margin-bottom: var(--space-4);">
                <div class="text-sm text-gray-500">Member</div>
                <div class="font-semibold"><?= e($issue['member_name']) ?></div>
                <div class="text-sm"><?= e($issue['Membership_Number']) ?></div>
            </div>

            <div style="margin-bottom: var(--space-4);">
                <div class="text-sm text-gray-500">Book</div>
                <div class="font-semibold"><?= e($issue['Book_Title']) ?></div>
                <div class="text-sm"><?= e($issue['ISBN_NO']) ?></div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4); margin-bottom: var(--space-4);">
                <div>
                    <div class="text-sm text-gray-500">Issue Date</div>
                    <div><?= date('M d, Y', strtotime($issue['Issue_Date'])) ?></div>
                </div>
                <div>
                    <div class="text-sm text-gray-500">Due Date</div>
                    <div><?= date('M d, Y', strtotime($issue['Return_Date'])) ?></div>
                </div>
            </div>

            <?php if ($fine > 0): ?>
                <div class="alert alert-danger">
                    <strong>Overdue Fine: $<?= number_format($fine, 2) ?></strong>
                    <div class="text-sm">
                        <?= floor((time() - strtotime($issue['Return_Date'])) / 86400) ?> days overdue @ $10/day
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <form method="POST" action="">
            <?= csrf_field() ?>
            
            <div style="display: flex; gap: var(--space-3);">
                <button type="submit" class="btn btn-success">
                    Confirm Return
                </button>
                <a href="issue.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Return Book';
$currentPage = 'return';
require __DIR__ . '/../src/Views/layouts/app.php';
