<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Models\BookIssue;
use BookHive\Models\Book;
use BookHive\Models\User;
use BookHive\Core\Validator;

$auth = new Auth();
$auth->requireAuth();
$auth->requirePermission('Book_Issue', 'view');

$issueModel = new BookIssue();
$bookModel = new Book();
$userModel = new User();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    if ($auth->hasPermission('Book_Issue', 'insert')) {
        $data = Validator::sanitizeArray($_POST);
        
        try {
            $issueModel->create($data);
            flash('success', 'Book issued successfully');
            redirect('issue.php');
        } catch (\Exception $e) {
            flash('error', 'Error issuing book: ' . $e->getMessage());
        }
    }
}

// Get filter parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$status = $_GET['status'] ?? 'issued';
$perPage = 10;

// Get issues
$issues = $issueModel->getAll($page, $perPage, $status);
$totalIssues = $issueModel->count($status);
$totalPages = ceil($totalIssues / $perPage);

// Get books and members for form
$books = $bookModel->getDb()->fetchAll("SELECT * FROM books WHERE Quantity > 0 ORDER BY Book_Title");
$members = $userModel->getDb()->fetchAll("SELECT * FROM users ORDER BY Name");

ob_start();
?>

<!-- Issue Form -->
<?php if ($auth->hasPermission('Book_Issue', 'insert')): ?>
<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title">Issue New Book</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?= csrf_field() ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-4);">
                <div class="form-group">
                    <label for="Member" class="form-label">Member *</label>
                    <select id="Member" name="Member" class="form-select" required>
                        <option value="">Select Member</option>
                        <?php foreach ($members as $member): ?>
                            <option value="<?= $member['id'] ?>">
                                <?= e($member['Name']) ?> (<?= e($member['Membership_Number']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="Book_Number" class="form-label">Book *</label>
                    <select id="Book_Number" name="Book_Number" class="form-select" required onchange="updateBookTitle(this)">
                        <option value="">Select Book</option>
                        <?php foreach ($books as $book): ?>
                            <option value="<?= $book['id'] ?>" data-title="<?= e($book['Book_Title']) ?>">
                                <?= e($book['Book_Title']) ?> (Qty: <?= $book['Quantity'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" id="Book_Title" name="Book_Title">
                </div>

                <div class="form-group">
                    <label for="Return_Date" class="form-label">Return Date *</label>
                    <input 
                        type="date" 
                        id="Return_Date" 
                        name="Return_Date" 
                        class="form-input"
                        value="<?= date('Y-m-d', strtotime('+14 days')) ?>"
                        required
                    >
                </div>
            </div>

            <button type="submit" class="btn btn-success">Issue Book</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="card mb-4">
    <div class="card-body" style="padding: var(--space-4);">
        <div style="display: flex; gap: var(--space-2);">
            <a href="?status=issued" class="btn <?= $status === 'issued' ? 'btn-primary' : 'btn-secondary' ?>">
                Issued Books
            </a>
            <a href="?status=returned" class="btn <?= $status === 'returned' ? 'btn-primary' : 'btn-secondary' ?>">
                Returned Books
            </a>
            <a href="?status=" class="btn <?= $status === '' ? 'btn-primary' : 'btn-secondary' ?>">
                All
            </a>
        </div>
    </div>
</div>

<!-- Issues Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Book Issues (<?= number_format($totalIssues) ?>)</h2>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <?php if (empty($issues)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-arrow-up-from-bracket"></i></div>
                <p>No book issues found</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Book</th>
                            <th>Issue Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($issues as $issue): ?>
                            <?php
                            $isOverdue = $issue['Status'] === 'issued' && strtotime($issue['Return_Date']) < time();
                            ?>
                            <tr>
                                <td>
                                    <div class="font-semibold"><?= e($issue['member_name']) ?></div>
                                    <div class="text-sm text-gray-500"><?= e($issue['Membership_Number']) ?></div>
                                </td>
                                <td>
                                    <div class="font-semibold"><?= e($issue['Book_Title']) ?></div>
                                    <div class="text-sm text-gray-500"><?= e($issue['ISBN_NO']) ?></div>
                                </td>
                                <td><?= date('M d, Y', strtotime($issue['Issue_Date'])) ?></td>
                                <td>
                                    <?= date('M d, Y', strtotime($issue['Return_Date'])) ?>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge badge-danger">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($issue['Status'] === 'issued'): ?>
                                        <span class="badge badge-warning">Issued</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Returned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($issue['Status'] === 'issued'): ?>
                                        <a href="return.php?issue_id=<?= $issue['id'] ?>" class="btn btn-primary btn-sm">
                                            Return
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="padding: var(--space-6);">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&status=<?= $status ?>" class="pagination-btn">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?>&status=<?= $status ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&status=<?= $status ?>" class="pagination-btn">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function updateBookTitle(select) {
    const selectedOption = select.options[select.selectedIndex];
    const bookTitle = selectedOption.getAttribute('data-title');
    document.getElementById('Book_Title').value = bookTitle || '';
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Issue Books';
$currentPage = 'issue';
require __DIR__ . '/../src/Views/layouts/app.php';
