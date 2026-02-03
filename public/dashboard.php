<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Core\Report;
use BookHive\Config\Database;

$auth = new Auth();
$auth->requireAuth();

$db = Database::getInstance();
$report = new Report();

// Get comprehensive statistics
$stats = $report->getDashboardStats();

// Get chart data
$monthlyStats = $report->getMonthlyIssueStats(6);
$popularBooks = $report->getPopularBooks(5);
$categoryDist = $report->getCategoryDistribution();

// Recent issues
$recentIssues = $db->fetchAll(
    "SELECT bi.*, u.Name as member_name, u.Membership_Number, b.Book_Title, b.ISBN_NO
     FROM book_issue bi
     LEFT JOIN users u ON bi.Member = u.id
     LEFT JOIN books b ON bi.Book_Number = b.id
     ORDER BY bi.id DESC
     LIMIT 5"
);

ob_start();
?>

<!-- Statistics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-6); margin-bottom: var(--space-8);">
    <div class="card">
        <div class="card-body">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: var(--text-sm); color: var(--color-gray-500); margin-bottom: var(--space-2);">
                        Total Books
                    </div>
                    <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--color-primary-600);">
                        <?= number_format($stats['total_books']) ?>
                    </div>
                </div>
                <div style="font-size: 2.5rem; color: var(--color-primary-200);">
                    <i class="fas fa-book"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: var(--text-sm); color: var(--color-gray-500); margin-bottom: var(--space-2);">
                        Total Members
                    </div>
                    <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--color-success-600);">
                        <?= number_format($stats['total_members']) ?>
                    </div>
                </div>
                <div style="font-size: 2.5rem; color: var(--color-success-200);">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: var(--text-sm); color: var(--color-gray-500); margin-bottom: var(--space-2);">
                        Issued Books
                    </div>
                    <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--color-warning-600);">
                        <?= number_format($stats['issued_books']) ?>
                    </div>
                </div>
                <div style="font-size: 2.5rem; color: var(--color-warning-200);">
                    <i class="fas fa-arrow-up-from-bracket"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: var(--text-sm); color: var(--color-gray-500); margin-bottom: var(--space-2);">
                        Overdue Books
                    </div>
                    <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--color-danger-600);">
                        <?= number_format($stats['overdue_books']) ?>
                    </div>
                </div>
                <div style="font-size: 2.5rem; color: var(--color-danger-200);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: var(--text-sm); color: var(--color-gray-500); margin-bottom: var(--space-2);">
                        Available Books
                    </div>
                    <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--color-primary-600);">
                        <?= number_format($stats['available_books']) ?>
                    </div>
                </div>
                <div style="font-size: 2.5rem; color: var(--color-primary-200);">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: var(--text-sm); color: var(--color-gray-500); margin-bottom: var(--space-2);">
                        Total Fines
                    </div>
                    <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--color-danger-600);">
                        $<?= number_format($stats['total_fines'], 2) ?>
                    </div>
                </div>
                <div style="font-size: 2.5rem; color: var(--color-danger-200);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6); margin-bottom: var(--space-8);">
    <!-- Monthly Issues Chart -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Monthly Book Issues (Last 6 Months)</h2>
        </div>
        <div class="card-body">
            <canvas id="monthlyIssuesChart" height="80"></canvas>
        </div>
    </div>

    <!-- Category Distribution Chart -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Books by Category</h2>
        </div>
        <div class="card-body">
            <canvas id="categoryChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Popular Books & Recent Issues -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-8);">
    <!-- Popular Books -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Most Popular Books</h2>
            <a href="reports.php?type=popular" class="btn btn-primary btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($popularBooks)): ?>
                <div class="empty-state">
                    <p>No data available</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Issues</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($popularBooks as $book): ?>
                            <tr>
                                <td class="font-semibold"><?= e($book['Book_Title']) ?></td>
                                <td><?= e($book['Author_Name']) ?></td>
                                <td><span class="badge badge-primary"><?= $book['issue_count'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Issues -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Book Issues</h2>
            <a href="issue.php" class="btn btn-primary btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($recentIssues)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-book-open"></i></div>
                    <p>No book issues yet</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Book</th>
                            <th>Issue Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentIssues as $issue): ?>
                            <tr>
                                <td><?= e($issue['member_name']) ?></td>
                                <td><?= e($issue['Book_Title']) ?></td>
                                <td><?= date('M d, Y', strtotime($issue['Issue_Date'])) ?></td>
                                <td>
                                    <?php if ($issue['Status'] === 'issued'): ?>
                                        <span class="badge badge-warning">Issued</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Returned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Monthly Issues Chart
const monthlyData = <?= json_encode($monthlyStats) ?>;
const monthlyLabels = monthlyData.map(d => d.month);
const monthlyValues = monthlyData.map(d => parseInt(d.total_issues));

new Chart(document.getElementById('monthlyIssuesChart'), {
    type: 'line',
    data: {
        labels: monthlyLabels,
        datasets: [{
            label: 'Book Issues',
            data: monthlyValues,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Category Distribution Chart
const categoryData = <?= json_encode($categoryDist) ?>;
const categoryLabels = categoryData.map(d => d.category);
const categoryValues = categoryData.map(d => parseInt(d.book_count));

new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: categoryLabels,
        datasets: [{
            data: categoryValues,
            backgroundColor: [
                '#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
require __DIR__ . '/../src/Views/layouts/app.php';
