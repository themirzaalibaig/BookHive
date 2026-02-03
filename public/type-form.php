<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Models\Type;
use BookHive\Core\Validator;

$auth = new Auth();
$auth->requireAuth();

$typeModel = new Type();
$isEdit = isset($_GET['id']);
$type = null;

if ($isEdit) {
    $auth->requirePermission('Types', 'edit');
    $typeId = (int)$_GET['id'];
    $type = $typeModel->find($typeId);
    
    if (!$type) {
        flash('error', 'Category not found');
        redirect('types.php');
    }
} else {
    $auth->requirePermission('Types', 'insert');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $data = Validator::sanitizeArray($_POST);
    
    $validator = new Validator($data);
    $rules = ['Name' => 'required|max:40'];
    
    if ($validator->validate($rules)) {
        try {
            if ($isEdit) {
                $typeModel->update($typeId, $data);
                flash('success', 'Category updated successfully');
            } else {
                $typeModel->create($data);
                flash('success', 'Category added successfully');
            }
            redirect('types.php');
        } catch (\Exception $e) {
            flash('error', 'Error saving category: ' . $e->getMessage());
        }
    } else {
        $_SESSION['errors'] = $validator->errors();
        $_SESSION['old'] = $data;
    }
}

ob_start();
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title"><?= $isEdit ? 'Edit Category' : 'Add New Category' ?></h2>
    </div>
    
    <div class="card-body">
        <form method="POST" action="">
            <?= csrf_field() ?>
            
            <div class="form-group">
                <label for="Name" class="form-label">Category Name *</label>
                <input 
                    type="text" 
                    id="Name" 
                    name="Name" 
                    class="form-input"
                    value="<?= e($type['Name'] ?? old('Name')) ?>"
                    required
                    autofocus
                    placeholder="e.g., Fiction, Science, History"
                >
                <?php if (error('Name')): ?>
                    <div class="form-error"><?= e(error('Name')) ?></div>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: var(--space-3); margin-top: var(--space-6);">
                <button type="submit" class="btn btn-success">
                    <?= $isEdit ? 'Update Category' : 'Add Category' ?>
                </button>
                <a href="types.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
// Clear old input and errors
unset($_SESSION['old'], $_SESSION['errors']);

$content = ob_get_clean();
$pageTitle = $isEdit ? 'Edit Category' : 'Add New Category';
$currentPage = 'types';
require __DIR__ . '/../src/Views/layouts/app.php';
