<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Models\User;
use BookHive\Core\Validator;

$auth = new Auth();
$auth->requireAuth();
$auth->requirePermission('Users', 'view');

$userModel = new User();

// Handle delete
if (isset($_GET['delete']) && $auth->hasPermission('Users', 'delete')) {
    if (verify_csrf()) {
        $id = (int)$_GET['delete'];
        if ($userModel->delete($id)) {
            flash('success', 'Member deleted successfully');
        } else {
            flash('error', 'Cannot delete member. They may have active book issues.');
        }
        redirect('members.php');
    }
}

// Get filter parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$search = $_GET['search'] ?? null;
$perPage = 10;

// Get members
$members = $userModel->getAll($page, $perPage, $search);
$totalMembers = $userModel->count($search);
$totalPages = ceil($totalMembers / $perPage);

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
                placeholder="Search by name, membership number, or contact..."
                value="<?= e($search ?? '') ?>"
            >
            
            <button type="submit" class="btn btn-primary">Search</button>
            
            <?php if ($search): ?>
                <a href="members.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Members Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Members (<?= number_format($totalMembers) ?>)</h2>
        <div style="display: flex; gap: var(--space-3);">
            <a href="?export=csv<?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-success btn-sm">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
            <?php if ($auth->hasPermission('Users', 'insert')): ?>
                <a href="member-form.php" class="btn btn-success">+ Add New Member</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <?php if (empty($members)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-users"></i></div>
                <p>No members found</p>
                <?php if ($auth->hasPermission('Users', 'insert')): ?>
                    <a href="member-form.php" class="btn btn-primary mt-4">Add Your First Member</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Membership #</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>ID Number</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-primary"><?= e($member['Membership_Number']) ?></span>
                                </td>
                                <td class="font-semibold"><?= e($member['Name']) ?></td>
                                <td><?= e($member['Contact']) ?></td>
                                <td><?= e($member['ID_Number']) ?></td>
                                <td>
                                    <div style="display: flex; gap: var(--space-2);">
                                        <?php if ($auth->hasPermission('Users', 'edit')): ?>
                                            <a href="member-form.php?id=<?= $member['id'] ?>" class="btn btn-primary btn-sm">
                                                Edit
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($auth->hasPermission('Users', 'delete')): ?>
                                            <form method="GET" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="delete" value="<?= $member['id'] ?>">
                                                <button 
                                                    type="submit" 
                                                    class="btn btn-danger btn-sm"
                                                    data-confirm="Are you sure you want to delete this member?"
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
$pageTitle = 'Members';
$currentPage = 'members';
require __DIR__ . '/../src/Views/layouts/app.php';
