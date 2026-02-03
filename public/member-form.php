<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Models\User;
use BookHive\Core\Validator;

$auth = new Auth();
$auth->requireAuth();

$userModel = new User();
$isEdit = isset($_GET['id']);
$member = null;

if ($isEdit) {
    $auth->requirePermission('Users', 'edit');
    $memberId = (int)$_GET['id'];
    $member = $userModel->find($memberId);
    
    if (!$member) {
        flash('error', 'Member not found');
        redirect('members.php');
    }
} else {
    $auth->requirePermission('Users', 'insert');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $data = Validator::sanitizeArray($_POST);
    
    $validator = new Validator($data);
    $rules = [
        'Name' => 'required|max:140',
        'Membership_Number' => 'required|max:40',
        'Contact' => 'max:40',
        'ID_Number' => 'numeric',
    ];
    
    if ($validator->validate($rules)) {
        try {
            if ($isEdit) {
                $userModel->update($memberId, $data);
                flash('success', 'Member updated successfully');
            } else {
                $userModel->create($data);
                flash('success', 'Member added successfully');
            }
            redirect('members.php');
        } catch (\Exception $e) {
            flash('error', 'Error saving member: ' . $e->getMessage());
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
        <h2 class="card-title"><?= $isEdit ? 'Edit Member' : 'Add New Member' ?></h2>
    </div>
    
    <div class="card-body">
        <form method="POST" action="">
            <?= csrf_field() ?>
            
            <div class="form-group">
                <label for="Membership_Number" class="form-label">Membership Number *</label>
                <input 
                    type="text" 
                    id="Membership_Number" 
                    name="Membership_Number" 
                    class="form-input"
                    value="<?= e($member['Membership_Number'] ?? old('Membership_Number')) ?>"
                    required
                >
                <?php if (error('Membership_Number')): ?>
                    <div class="form-error"><?= e(error('Membership_Number')) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="Name" class="form-label">Full Name *</label>
                <input 
                    type="text" 
                    id="Name" 
                    name="Name" 
                    class="form-input"
                    value="<?= e($member['Name'] ?? old('Name')) ?>"
                    required
                >
                <?php if (error('Name')): ?>
                    <div class="form-error"><?= e(error('Name')) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="Contact" class="form-label">Contact Number</label>
                <input 
                    type="text" 
                    id="Contact" 
                    name="Contact" 
                    class="form-input"
                    value="<?= e($member['Contact'] ?? old('Contact')) ?>"
                >
                <?php if (error('Contact')): ?>
                    <div class="form-error"><?= e(error('Contact')) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="ID_Number" class="form-label">ID Number</label>
                <input 
                    type="number" 
                    id="ID_Number" 
                    name="ID_Number" 
                    class="form-input"
                    value="<?= e($member['ID_Number'] ?? old('ID_Number')) ?>"
                >
                <?php if (error('ID_Number')): ?>
                    <div class="form-error"><?= e(error('ID_Number')) ?></div>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: var(--space-3); margin-top: var(--space-6);">
                <button type="submit" class="btn btn-success">
                    <?= $isEdit ? 'Update Member' : 'Add Member' ?>
                </button>
                <a href="members.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
// Clear old input and errors
unset($_SESSION['old'], $_SESSION['errors']);

$content = ob_get_clean();
$pageTitle = $isEdit ? 'Edit Member' : 'Add New Member';
$currentPage = 'members';
require __DIR__ . '/../src/Views/layouts/app.php';
