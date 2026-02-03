<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Models\Magazine;

$auth = new Auth();
$auth->requireAuth();
$auth->requirePermission('Magazines', 'view');

$magazineModel = new Magazine();

// Handle delete
if (isset($_GET['delete']) && $auth->hasPermission('Magazines', 'delete')) {
    if (verify_csrf()) {
        $id = (int)$_GET['delete'];
        if ($magazineModel->delete($id)) {
            flash('success', 'Magazine deleted successfully');
        } else {
            flash('error', 'Error deleting magazine');
        }
        redirect('magazines.php');
    }
}

// Get filter parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$search = $_GET['search'] ?? null;
$perPage = 10;

// Get magazines
$magazines = $magazineModel->getAll($page, $perPage, $search);
$totalMagazines = $magazineModel->count($search);
$totalPages = ceil($totalMagazines / $perPage);

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
                placeholder="Search magazines..."
                value="<?= e($search ?? '') ?>"
            >
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search): ?>
                <a href="magazines.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Magazines Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Magazines (<?= number_format($totalMagazines) ?>)</h2>
        <?php if ($auth->hasPermission('Magazines', 'insert')): ?>
            <a href="magazine-form.php" class="btn btn-success">+ Add New Magazine</a>
        <?php endif; ?>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <?php if (empty($magazines)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-newspaper"></i></div>
                <p>No magazines found</p>
                <?php if ($auth->hasPermission('Magazines', 'insert')): ?>
                    <a href="magazine-form.php" class="btn btn-primary mt-4">Add Your First Magazine</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Date Published</th>
                            <th>Pages</th>
                            <th>Price</th>
                            <th>Publisher</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($magazines as $magazine): ?>
                            <tr>
                                <td class="font-semibold"><?= e($magazine['Name']) ?></td>
                                <td><?= e($magazine['Type']) ?></td>
                                <td><?= $magazine['Date_Published'] ? date('M d, Y', strtotime($magazine['Date_Published'])) : '-' ?></td>
                                <td><?= e($magazine['Pages']) ?></td>
                                <td>$<?= number_format($magazine['Price'], 2) ?></td>
                                <td><?= e($magazine['Publisher']) ?></td>
                                <td>
                                    <div style="display: flex; gap: var(--space-2);">
                                        <?php if ($auth->hasPermission('Magazines', 'edit')): ?>
                                            <a href="magazine-form.php?id=<?= $magazine['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                        <?php endif; ?>
                                        <?php if ($auth->hasPermission('Magazines', 'delete')): ?>
                                            <form method="GET" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="delete" value="<?= $magazine['id'] ?>">
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
$pageTitle = 'Magazines';
$currentPage = 'magazines';
require __DIR__ . '/../src/Views/layouts/app.php';
