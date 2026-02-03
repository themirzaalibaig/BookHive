<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'BookHive Library' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="<?= url('dashboard.php') ?>" class="sidebar-logo">
                    <i class="fas fa-book"></i> BookHive
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <a href="<?= url('dashboard.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line nav-icon"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="<?= url('books.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'books' ? 'active' : '' ?>">
                    <i class="fas fa-book-open nav-icon"></i>
                    <span>Books</span>
                </a>
                
                <a href="<?= url('members.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'members' ? 'active' : '' ?>">
                    <i class="fas fa-users nav-icon"></i>
                    <span>Members</span>
                </a>
                
                <a href="<?= url('issue.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'issue' ? 'active' : '' ?>">
                    <i class="fas fa-arrow-up-from-bracket nav-icon"></i>
                    <span>Issue Books</span>
                </a>
                
                <a href="<?= url('return.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'return' ? 'active' : '' ?>">
                    <i class="fas fa-arrow-down-to-bracket nav-icon"></i>
                    <span>Return Books</span>
                </a>
                
                <a href="<?= url('types.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'types' ? 'active' : '' ?>">
                    <i class="fas fa-tags nav-icon"></i>
                    <span>Categories</span>
                </a>
                
                <a href="<?= url('magazines.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'magazines' ? 'active' : '' ?>">
                    <i class="fas fa-newspaper nav-icon"></i>
                    <span>Magazines</span>
                </a>
                
                <a href="<?= url('newspapers.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'newspapers' ? 'active' : '' ?>">
                    <i class="far fa-newspaper nav-icon"></i>
                    <span>Newspapers</span>
                </a>
                
                <a href="<?= url('reports.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'reports' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar nav-icon"></i>
                    <span>Reports</span>
                </a>
                
                <a href="<?= url('settings.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog nav-icon"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <h1 class="header-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
                
                <div class="header-actions">
                    <span class="text-sm text-gray-500">
                        <?php
                        $auth = new \BookHive\Core\Auth();
                        $user = $auth->user();
                        echo e($user['id'] ?? 'Guest');
                        ?>
                    </span>
                    <a href="<?= url('logout.php') ?>" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </header>

            <!-- Content Area -->
            <main class="content">
                <?php if (flash('success')): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?= e(flash('success')) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (flash('error')): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= e(flash('error')) ?></span>
                    </div>
                <?php endif; ?>

                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
