<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Models\Magazine;
use BookHive\Core\Validator;

$auth = new Auth();
$auth->requireAuth();

$magazineModel = new Magazine();
$isEdit = isset($_GET['id']);
$magazine = null;

if ($isEdit) {
    $auth->requirePermission('Magazines', 'edit');
    $magazineId = (int)$_GET['id'];
    $magazine = $magazineModel->find($magazineId);
    if (!$magazine) {
        flash('error', 'Magazine not found');
        redirect('magazines.php');
    }
} else {
    $auth->requirePermission('Magazines', 'insert');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $data = Validator::sanitizeArray($_POST);
    
    $validator = new Validator($data);
    $rules = [
        'Name' => 'required|max:100',
        'Type' => 'max:40',
        'Pages' => 'numeric',
        'Price' => 'numeric',
    ];
    
    if ($validator->validate($rules)) {
        try {
            if ($isEdit) {
                $magazineModel->update($magazineId, $data);
                flash('success', 'Magazine updated successfully');
            } else {
                $magazineModel->create($data);
                flash('success', 'Magazine added successfully');
            }
            redirect('magazines.php');
        } catch (\Exception $e) {
            flash('error', 'Error saving magazine: ' . $e->getMessage());
        }
    } else {
        $_SESSION['errors'] = $validator->errors();
        $_SESSION['old'] = $data;
    }
}

ob_start();
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title"><?= $isEdit ? 'Edit Magazine' : 'Add New Magazine' ?></h2>
    </div>
    
    <div class="card-body">
        <form method="POST" action="">
            <?= csrf_field() ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6);">
                <div class="form-group">
                    <label for="Name" class="form-label">Magazine Name *</label>
                    <input type="text" id="Name" name="Name" class="form-input" value="<?= e($magazine['Name'] ?? old('Name')) ?>" required>
                    <?php if (error('Name')): ?><div class="form-error"><?= e(error('Name')) ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="Type" class="form-label">Type</label>
                    <input type="text" id="Type" name="Type" class="form-input" value="<?= e($magazine['Type'] ?? old('Type')) ?>">
                </div>

                <div class="form-group">
                    <label for="Date_Of_Receipt" class="form-label">Date of Receipt</label>
                    <input type="date" id="Date_Of_Receipt" name="Date_Of_Receipt" class="form-input" value="<?= e($magazine['Date_Of_Receipt'] ?? old('Date_Of_Receipt')) ?>">
                </div>

                <div class="form-group">
                    <label for="Date_Published" class="form-label">Date Published</label>
                    <input type="date" id="Date_Published" name="Date_Published" class="form-input" value="<?= e($magazine['Date_Published'] ?? old('Date_Published')) ?>">
                </div>

                <div class="form-group">
                    <label for="Pages" class="form-label">Pages</label>
                    <input type="number" id="Pages" name="Pages" class="form-input" value="<?= e($magazine['Pages'] ?? old('Pages')) ?>" min="0">
                </div>

                <div class="form-group">
                    <label for="Price" class="form-label">Price ($)</label>
                    <input type="number" id="Price" name="Price" class="form-input" value="<?= e($magazine['Price'] ?? old('Price', '0.00')) ?>" step="0.01" min="0">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="Publisher" class="form-label">Publisher</label>
                    <input type="text" id="Publisher" name="Publisher" class="form-input" value="<?= e($magazine['Publisher'] ?? old('Publisher')) ?>">
                </div>
            </div>

            <div style="display: flex; gap: var(--space-3); margin-top: var(--space-6);">
                <button type="submit" class="btn btn-success"><?= $isEdit ? 'Update Magazine' : 'Add Magazine' ?></button>
                <a href="magazines.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
unset($_SESSION['old'], $_SESSION['errors']);
$content = ob_get_clean();
$pageTitle = $isEdit ? 'Edit Magazine' : 'Add New Magazine';
$currentPage = 'magazines';
require __DIR__ . '/../src/Views/layouts/app.php';
