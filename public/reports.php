<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Core\Report;

$auth = new Auth();
$auth->requireAuth();

$report = new Report();
$reportType = $_GET['type'] ?? 'overview';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    switch ($reportType) {
        case 'overdue':
            $data = $report->getOverdueReport();
            $headers = ['Member', 'Membership #', 'Contact', 'Book Title', 'ISBN', 'Issue Date', 'Due Date', 'Days Overdue', 'Fine Amount'];
            $rows = array_map(function($row) {
                return [
                    $row['member_name'],
                    $row['Membership_Number'],
                    $row['Contact'],
                    $row['Book_Title'],
                    $row['ISBN_NO'],
                    $row['Issue_Date'],
                    $row['Return_Date'],
                    $row['days_overdue'],
                    '$' . number_format($row['fine_amount'], 2)
                ];
            }, $data);
            $report->exportToCSV($rows, $headers, 'overdue_books_' . date('Y-m-d') . '.csv');
            break;
            
        case 'popular':
            $data = $report->getPopularBooks(50);
            $headers = ['Book Title', 'Author', 'Total Issues'];
            $rows = array_map(function($row) {
                return [$row['Book_Title'], $row['Author_Name'], $row['issue_count']];
            }, $data);
            $report->exportToCSV($rows, $headers, 'popular_books_' . date('Y-m-d') . '.csv');
            break;
            
        case 'active_members':
            $data = $report->getActiveMembers(50);
            $headers = ['Member Name', 'Membership Number', 'Total Issues'];
            $rows = array_map(function($row) {
                return [$row['Name'], $row['Membership_Number'], $row['total_issues']];
            }, $data);
            $report->exportToCSV($rows, $headers, 'active_members_' . date('Y-m-d') . '.csv');
            break;
    }
}

// Get report data based on type
$reportData = [];
$reportTitle = '';

switch ($reportType) {
    case 'overdue':
        $reportData = $report->getOverdueReport();
        $reportTitle = 'Overdue Books Report';
        break;
    case 'popular':
        $reportData = $report->getPopularBooks(20);
        $reportTitle = 'Most Popular Books';
        break;
    case 'active_members':
        $reportData = $report->getActiveMembers(20);
        $reportTitle = 'Most Active Members';
        break;
    case 'category':
        $reportData = $report->getCategoryDistribution();
        $reportTitle = 'Books by Category';
        break;
    default:
        $stats = $report->getDashboardStats();
        $reportTitle = 'Library Overview';
}

ob_start();
?>

<div class="mb-4">
    <div class="card">
        <div class="card-body">
            <div style="display: flex; gap: var(--space-3); flex-wrap: wrap;">
                <a href="reports.php?type=overview" class="btn <?= $reportType === 'overview' ? 'btn-primary' : 'btn-secondary' ?>">
                    <i class="fas fa-chart-bar"></i> Overview
                </a>
                <a href="reports.php?type=overdue" class="btn <?= $reportType === 'overdue' ? 'btn-primary' : 'btn-secondary' ?>">
                    <i class="fas fa-exclamation-triangle"></i> Overdue Books
                </a>
                <a href="reports.php?type=popular" class="btn <?= $reportType === 'popular' ? 'btn-primary' : 'btn-secondary' ?>">
                    <i class="fas fa-star"></i> Popular Books
                </a>
                <a href="reports.php?type=active_members" class="btn <?= $reportType === 'active_members' ? 'btn-primary' : 'btn-secondary' ?>">
                    <i class="fas fa-users"></i> Active Members
                </a>
                <a href="reports.php?type=category" class="btn <?= $reportType === 'category' ? 'btn-primary' : 'btn-secondary' ?>">
                    <i class="fas fa-tags"></i> By Category
                </a>
            </div>
        </div>
    </div>
</div>

<?php if ($reportType === 'overview'): ?>
    <!-- Overview Statistics -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-6); margin-bottom: var(--space-8);">
        <div class="card">
            <div class="card-body">
                <div style="font-size: var(--text-sm); color: var(--color-gray-500); margin-bottom: var(--space-2);">Total Books</div>
                <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--color-primary-600);">
                    <?= number_format($stats['total_books']) ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div style="font-size: var(--text-sm); color: var(--color-gray-500); margin-bottom: var(--space-2);">Total Members</div>
                <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--color-success-600);">
                    <?= number_format($stats['total_members']) ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div style="font-size: var(--text-sm); color: var(--color-gray-500); margin-bottom: var(--space-2);">Issued Books</div>
                <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--color-warning-600);">
                    <?= number_format($stats['issued_books']) ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div style="font-size: var(--text-sm); color: var(--color-gray-500); margin-bottom: var(--space-2);">Overdue Books</div>
                <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--color-danger-600);">
                    <?= number_format($stats['overdue_books']) ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div style="font-size: var(--text-sm); color: var(--color-gray-500); margin-bottom: var(--space-2);">Total Fines</div>
                <div style="font-size: var(--text-3xl); font-weight: 700; color: var(--color-danger-600);">
                    $<?= number_format($stats['total_fines'], 2) ?>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($reportType === 'overdue'): ?>
    <!-- Overdue Books Report -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= $reportTitle ?></h2>
            <div style="display: flex; gap: var(--space-3);">
                <a href="?type=overdue&export=csv" class="btn btn-success btn-sm">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
                <button onclick="window.print()" class="btn btn-secondary btn-sm">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($reportData)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-check-circle"></i></div>
                    <p>No overdue books! Great job!</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Contact</th>
                                <th>Book</th>
                                <th>Due Date</th>
                                <th>Days Overdue</th>
                                <th>Fine</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $row): ?>
                                <tr>
                                    <td>
                                        <div class="font-semibold"><?= e($row['member_name']) ?></div>
                                        <div class="text-sm text-gray-500"><?= e($row['Membership_Number']) ?></div>
                                    </td>
                                    <td><?= e($row['Contact']) ?></td>
                                    <td><?= e($row['Book_Title']) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['Return_Date'])) ?></td>
                                    <td><span class="badge badge-danger"><?= $row['days_overdue'] ?> days</span></td>
                                    <td class="font-semibold" style="color: var(--color-danger-600);">
                                        $<?= number_format($row['fine_amount'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($reportType === 'popular'): ?>
    <!-- Popular Books Report -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= $reportTitle ?></h2>
            <div style="display: flex; gap: var(--space-3);">
                <a href="?type=popular&export=csv" class="btn btn-success btn-sm">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
                <button onclick="window.print()" class="btn btn-secondary btn-sm">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Total Issues</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $index => $row): ?>
                            <tr>
                                <td><span class="badge badge-primary">#<?= $index + 1 ?></span></td>
                                <td class="font-semibold"><?= e($row['Book_Title']) ?></td>
                                <td><?= e($row['Author_Name']) ?></td>
                                <td><span class="badge badge-success"><?= $row['issue_count'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($reportType === 'active_members'): ?>
    <!-- Active Members Report -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= $reportTitle ?></h2>
            <div style="display: flex; gap: var(--space-3);">
                <a href="?type=active_members&export=csv" class="btn btn-success btn-sm">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
                <button onclick="window.print()" class="btn btn-secondary btn-sm">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Member Name</th>
                            <th>Membership Number</th>
                            <th>Total Issues</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $index => $row): ?>
                            <tr>
                                <td><span class="badge badge-primary">#<?= $index + 1 ?></span></td>
                                <td class="font-semibold"><?= e($row['Name']) ?></td>
                                <td><?= e($row['Membership_Number']) ?></td>
                                <td><span class="badge badge-success"><?= $row['total_issues'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($reportType === 'category'): ?>
    <!-- Category Distribution Report -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= $reportTitle ?></h2>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Number of Books</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = array_sum(array_column($reportData, 'book_count'));
                        foreach ($reportData as $row): 
                            $percentage = $total > 0 ? ($row['book_count'] / $total) * 100 : 0;
                        ?>
                            <tr>
                                <td class="font-semibold"><?= e($row['category']) ?></td>
                                <td><?= $row['book_count'] ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: var(--space-3);">
                                        <div style="flex: 1; background: var(--color-gray-200); height: 8px; border-radius: 4px; overflow: hidden;">
                                            <div style="width: <?= $percentage ?>%; background: var(--color-primary-600); height: 100%;"></div>
                                        </div>
                                        <span><?= number_format($percentage, 1) ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
@media print {
    .sidebar, .header, .btn, .card-header a, .card-header button { display: none !important; }
    .main-content { margin-left: 0 !important; }
    .card { border: 1px solid #000; page-break-inside: avoid; }
}
</style>

<?php
$content = ob_get_clean();
$pageTitle = 'Reports';
$currentPage = 'reports';
require __DIR__ . '/../src/Views/layouts/app.php';
