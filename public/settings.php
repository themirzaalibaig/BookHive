<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Models\Setting;
use BookHive\Core\Validator;

$auth = new Auth();
$auth->requireAuth();
$auth->requirePermission('admin', 'edit'); // Only admins can change settings

$settingModel = new Setting();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $data = Validator::sanitizeArray($_POST);
    
    if ($settingModel->updateMultiple($data)) {
        flash('success', 'Settings updated successfully');
    } else {
        flash('error', 'Error updating settings');
    }
    redirect('settings.php');
}

// Get all settings
$settings = $settingModel->getAll();
$settingsGrouped = [];

foreach ($settings as $setting) {
    $parts = explode('_', $setting['key'], 2);
    $category = $parts[0];
    $settingsGrouped[$category][] = $setting;
}

ob_start();
?>

<form method="POST" action="">
    <?= csrf_field() ?>
    
    <!-- Library Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-building"></i> Library Information</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6);">
                <div class="form-group">
                    <label for="library_email" class="form-label">Library Email</label>
                    <input type="email" id="library_email" name="library_email" class="form-input" 
                           value="<?= e($settingModel->get('library_email', '')) ?>">
                </div>

                <div class="form-group">
                    <label for="library_phone" class="form-label">Library Phone</label>
                    <input type="text" id="library_phone" name="library_phone" class="form-input" 
                           value="<?= e($settingModel->get('library_phone', '')) ?>">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="library_address" class="form-label">Library Address</label>
                    <textarea id="library_address" name="library_address" class="form-input" rows="3"><?= e($settingModel->get('library_address', '')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Loan Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-book"></i> Loan Settings</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6);">
                <div class="form-group">
                    <label for="loan_period_days" class="form-label">Default Loan Period (Days)</label>
                    <input type="number" id="loan_period_days" name="loan_period_days" class="form-input" 
                           value="<?= e($settingModel->get('loan_period_days', 14)) ?>" min="1" max="90">
                    <small class="text-sm text-gray-500">Number of days a book can be borrowed</small>
                </div>

                <div class="form-group">
                    <label for="max_books_per_member" class="form-label">Max Books Per Member</label>
                    <input type="number" id="max_books_per_member" name="max_books_per_member" class="form-input" 
                           value="<?= e($settingModel->get('max_books_per_member', 5)) ?>" min="1" max="20">
                    <small class="text-sm text-gray-500">Maximum books a member can borrow at once</small>
                </div>

                <div class="form-group">
                    <label for="allow_renewals" class="form-label">Allow Renewals</label>
                    <select id="allow_renewals" name="allow_renewals" class="form-input">
                        <option value="1" <?= $settingModel->get('allow_renewals', 1) ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= !$settingModel->get('allow_renewals', 1) ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="max_renewals" class="form-label">Max Renewals Per Book</label>
                    <input type="number" id="max_renewals" name="max_renewals" class="form-input" 
                           value="<?= e($settingModel->get('max_renewals', 2)) ?>" min="0" max="10">
                </div>
            </div>
        </div>
    </div>

    <!-- Fine Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-dollar-sign"></i> Fine Settings</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6);">
                <div class="form-group">
                    <label for="fine_per_day" class="form-label">Fine Per Day ($)</label>
                    <input type="number" id="fine_per_day" name="fine_per_day" class="form-input" 
                           value="<?= e($settingModel->get('fine_per_day', 10.00)) ?>" step="0.01" min="0">
                    <small class="text-sm text-gray-500">Fine amount charged per day for overdue books</small>
                </div>

                <div class="form-group">
                    <label for="max_fine_amount" class="form-label">Maximum Fine Amount ($)</label>
                    <input type="number" id="max_fine_amount" name="max_fine_amount" class="form-input" 
                           value="<?= e($settingModel->get('max_fine_amount', 100.00)) ?>" step="0.01" min="0">
                    <small class="text-sm text-gray-500">Maximum fine that can be charged (0 for unlimited)</small>
                </div>

                <div class="form-group">
                    <label for="grace_period_days" class="form-label">Grace Period (Days)</label>
                    <input type="number" id="grace_period_days" name="grace_period_days" class="form-input" 
                           value="<?= e($settingModel->get('grace_period_days', 0)) ?>" min="0" max="7">
                    <small class="text-sm text-gray-500">Days after due date before fines start</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservation Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-bookmark"></i> Reservation Settings</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6);">
                <div class="form-group">
                    <label for="allow_reservations" class="form-label">Allow Reservations</label>
                    <select id="allow_reservations" name="allow_reservations" class="form-input">
                        <option value="1" <?= $settingModel->get('allow_reservations', 1) ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= !$settingModel->get('allow_reservations', 1) ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reservation_hold_days" class="form-label">Reservation Hold Period (Days)</label>
                    <input type="number" id="reservation_hold_days" name="reservation_hold_days" class="form-input" 
                           value="<?= e($settingModel->get('reservation_hold_days', 3)) ?>" min="1" max="14">
                    <small class="text-sm text-gray-500">Days to hold a reserved book before expiring</small>
                </div>

                <div class="form-group">
                    <label for="max_reservations_per_member" class="form-label">Max Reservations Per Member</label>
                    <input type="number" id="max_reservations_per_member" name="max_reservations_per_member" class="form-input" 
                           value="<?= e($settingModel->get('max_reservations_per_member', 3)) ?>" min="1" max="10">
                </div>
            </div>
        </div>
    </div>

    <!-- Email Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-envelope"></i> Email Notification Settings</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6);">
                <div class="form-group">
                    <label for="send_due_reminders" class="form-label">Send Due Date Reminders</label>
                    <select id="send_due_reminders" name="send_due_reminders" class="form-input">
                        <option value="1" <?= $settingModel->get('send_due_reminders', 1) ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= !$settingModel->get('send_due_reminders', 1) ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reminder_days_before" class="form-label">Send Reminder (Days Before Due)</label>
                    <input type="number" id="reminder_days_before" name="reminder_days_before" class="form-input" 
                           value="<?= e($settingModel->get('reminder_days_before', 2)) ?>" min="1" max="7">
                </div>

                <div class="form-group">
                    <label for="send_overdue_notices" class="form-label">Send Overdue Notices</label>
                    <select id="send_overdue_notices" name="send_overdue_notices" class="form-input">
                        <option value="1" <?= $settingModel->get('send_overdue_notices', 1) ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= !$settingModel->get('send_overdue_notices', 1) ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="send_welcome_emails" class="form-label">Send Welcome Emails</label>
                    <select id="send_welcome_emails" name="send_welcome_emails" class="form-input">
                        <option value="1" <?= $settingModel->get('send_welcome_emails', 1) ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?= !$settingModel->get('send_welcome_emails', 1) ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div style="display: flex; justify-content: flex-end;">
        <button type="submit" class="btn btn-success">
            <i class="fas fa-save"></i> Save Settings
        </button>
    </div>
</form>

<?php
$content = ob_get_clean();
$pageTitle = 'Settings';
$currentPage = 'settings';
require __DIR__ . '/../src/Views/layouts/app.php';
