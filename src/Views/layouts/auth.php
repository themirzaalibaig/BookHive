<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Login - BookHive' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-gray-100);
        }
        .auth-card {
            width: 100%;
            max-width: 400px;
            padding: var(--space-8);
        }
        .auth-logo {
            text-align: center;
            font-size: var(--text-3xl);
            font-weight: 700;
            color: var(--color-primary-600);
            margin-bottom: var(--space-8);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="card auth-card">
            <div class="auth-logo">
                <i class="fas fa-book"></i> BookHive
            </div>
            <?= $content ?? '' ?>
        </div>
    </div>
</body>
</html>
