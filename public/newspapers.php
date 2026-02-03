<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Models\Newspaper;

$auth = new Auth();
$auth->requireAuth();
$auth->requirePermission('NewsPapers', 'view');

$newspaperModel = new Newspaper();

// Handle delete
if (isset($_GET['delete']) && $auth->hasPermission('NewsPapers', 'delete')) {
    if (verify_csrf()) {
        $id = (int)$_GET['delete'];
        if ($newspaperModel->delete($id)) {
            flash('success', 'Newspaper deleted successfully');
        } else {
            flash('error', 'Error deleting newspaper');
        }
        redirect('newspapers.php');
    }
}

// Get filter parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$search = $_GET['search'] ?? null;
$perPage = 10;

// Get newspapers
$newspapers = $newspaperModel->getAll($page, $perPage, $search);
$totalNewspapers = $newspaperModel->count($search);
$totalPages = ceil($totalNewspapers / $perPage);

ob_start();
?>

<!-- Search Bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="search-bar">
            <input 
                type="text" 
                name="search" 
                class="form-input search-input" 
                placeholder="Search newspapers..."
                value="<?= e($search ?? '') ?>"
            >
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search): ?>
                <a href="newspapers.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Newspapers Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Newspapers (<?= number_format($totalNewspapers) ?>)</h2>
        <?php if ($auth->hasPermission('NewsPapers', 'insert')): ?>
            <a href="newspaper-form.php" class="btn btn-success">+ Add New Newspaper</a>
        <?php endif; ?>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <?php if (empty($newspapers)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="far fa-newspaper"></i></div>
                <p>No newspapers found</p>
                <?php if ($auth->hasPermission('NewsPapers', 'insert')): ?>
                    <a href="newspaper-form.php" class="btn btn-primary mt-4">Add Your First Newspaper</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Language</th>
                            <th>Type</th>
                            <th>Date Published</th>
                            <th>Pages</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($newspapers as $newspaper): ?>
                            <tr>
                                <td class="font-semibold"><?= e($newspaper['Name']) ?></td>
                                <td><?= e($newspaper['Language']) ?></td>
                                <td><?= e($newspaper['Type']) ?></td>
                                <td><?= $newspaper['Date_Published'] ? date('M d, Y', strtotime($newspaper['Date_Published'])) : '-' ?></td>
                                <td><?= e($newspaper['Pages']) ?></td>
                                <td>$<?= number_format($newspaper['Price'], 2) ?></td>
                                <td>
                                    <div style="display: flex; gap: var(--space-2);">
                                        <?php if ($auth->hasPermission('NewsPapers', 'edit')): ?>
                                            <a href="newspaper-form.php?id=<?= $newspaper['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                        <?php endif; ?>
                                        <?php if ($auth->hasPermission('NewsPapers', 'delete')): ?>
                                            <form method="GET" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="delete" value="<?= $newspaper['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" data-confirm="Are you sure?">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
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
                        <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination-btn">Previous</a>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination-btn">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Newspapers';
$currentPage = 'newspapers';
require __DIR__ . '/../src/Views/layouts/app.php';
