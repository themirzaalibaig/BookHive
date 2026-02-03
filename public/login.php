<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;

$auth = new Auth();

// If already logged in, redirect to dashboard
if ($auth->check()) {
    redirect('dashboard.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($auth->login($username, $password, $remember)) {
        flash('success', 'Welcome back!');
        redirect('dashboard.php');
    } else {
        flash('error', 'Invalid username or password');
    }
}

// Render login view
ob_start();
?>

<form method="POST" action="">
    <?= csrf_field() ?>
    
    <?php if (flash('error')): ?>
        <div class="alert alert-danger">
            <?= e(flash('error')) ?>
        </div>
    <?php endif; ?>

    <div class="form-group">
        <label for="username" class="form-label">Username</label>
        <input 
            type="text" 
            id="username" 
            name="username" 
            class="form-input" 
            required 
            autofocus
            placeholder="Enter your username"
        >
    </div>

    <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input 
            type="password" 
            id="password" 
            name="password" 
            class="form-input" 
            required
            placeholder="Enter your password"
        >
    </div>

    <div class="form-group">
        <label style="display: flex; align-items: center; gap: 0.5rem;">
            <input type="checkbox" name="remember" value="1">
            <span class="text-sm">Remember me</span>
        </label>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%;">
        Sign In
    </button>

    <div style="margin-top: 1rem; text-align: center; font-size: var(--text-sm); color: var(--color-gray-500);">
        <p>Default credentials: admin / admin</p>
    </div>
</form>

<?php
$content = ob_get_clean();
$title = 'Login - BookHive';
require __DIR__ . '/../src/Views/layouts/auth.php';
