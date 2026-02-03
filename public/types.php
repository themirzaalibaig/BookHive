<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Models\Type;
use BookHive\Core\Validator;

$auth = new Auth();
$auth->requireAuth();
$auth->requirePermission('Types', 'view');

$typeModel = new Type();

// Handle delete
if (isset($_GET['delete']) && $auth->hasPermission('Types', 'delete')) {
    if (verify_csrf()) {
        $id = (int)$_GET['delete'];
        if ($typeModel->delete($id)) {
            flash('success', 'Category deleted successfully');
        } else {
            flash('error', 'Cannot delete category. It may be used by books.');
        }
        redirect('types.php');
    }
}

// Get filter parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$search = $_GET['search'] ?? null;
$perPage = 10;

// Get types
$types = $typeModel->getAll($page, $perPage, $search);
$totalTypes = $typeModel->count($search);
$totalPages = ceil($totalTypes / $perPage);

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
                placeholder="Search categories..."
                value="<?= e($search ?? '') ?>"
            >
            
            <button type="submit" class="btn btn-primary">Search</button>
            
            <?php if ($search): ?>
                <a href="types.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Types Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Book Categories (<?= number_format($totalTypes) ?>)</h2>
        <?php if ($auth->hasPermission('Types', 'insert')): ?>
            <a href="type-form.php" class="btn btn-success">+ Add New Category</a>
        <?php endif; ?>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <?php if (empty($types)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-tags"></i></div>
                <p>No categories found</p>
                <?php if ($auth->hasPermission('Types', 'insert')): ?>
                    <a href="type-form.php" class="btn btn-primary mt-4">Add Your First Category</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $type): ?>
                            <tr>
                                <td><?= $type['id'] ?></td>
                                <td class="font-semibold"><?= e($type['Name']) ?></td>
                                <td>
                                    <div style="display: flex; gap: var(--space-2);">
                                        <?php if ($auth->hasPermission('Types', 'edit')): ?>
                                            <a href="type-form.php?id=<?= $type['id'] ?>" class="btn btn-primary btn-sm">
                                                Edit
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($auth->hasPermission('Types', 'delete')): ?>
                                            <form method="GET" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="delete" value="<?= $type['id'] ?>">
                                                <button 
                                                    type="submit" 
                                                    class="btn btn-danger btn-sm"
                                                    data-confirm="Are you sure you want to delete this category?"
                                                >
                                                    Delete
                                                </button>
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
                        <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination-btn">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a 
                            href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                            class="pagination-btn <?= $i === $page ? 'active' : '' ?>"
                        >
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination-btn">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Categories';
$currentPage = 'types';
require __DIR__ . '/../src/Views/layouts/app.php';
