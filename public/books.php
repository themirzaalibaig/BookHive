<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Models\Book;
use BookHive\Core\Validator;
use BookHive\Core\Report;

$auth = new Auth();
$auth->requireAuth();
$auth->requirePermission('books', 'view');

$bookModel = new Book();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $search = $_GET['search'] ?? null;
    $typeFilter = isset($_GET['type']) ? (int)$_GET['type'] : null;
    
    $allBooks = $bookModel->getAll(1, 10000, $search, $typeFilter);
    
    $headers = ['ID', 'ISBN', 'Title', 'Author', 'Category', 'Publisher', 'Year', 'Pages', 'Quantity', 'Price'];
    $rows = array_map(function($book) {
        return [
            $book['id'],
            $book['ISBN_NO'],
            $book['Book_Title'],
            $book['Author_Name'],
            $book['type_name'] ?? '',
            $book['Publisher'],
            $book['Year_Of_Publication'],
            $book['Pages'],
            $book['Quantity'],
            '$' . number_format($book['Price'], 2)
        ];
    }, $allBooks);
    
    $report = new Report();
    $report->exportToCSV($rows, $headers, 'books_export_' . date('Y-m-d') . '.csv');
}

// Handle delete
if (isset($_GET['delete']) && $auth->hasPermission('books', 'delete')) {
    if (verify_csrf()) {
        $id = (int)$_GET['delete'];
        if ($bookModel->delete($id)) {
            flash('success', 'Book deleted successfully');
        } else {
            flash('error', 'Cannot delete book. It may have active issues.');
        }
        redirect('books.php');
    }
}

// Get filter parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$search = $_GET['search'] ?? null;
$typeFilter = isset($_GET['type']) ? (int)$_GET['type'] : null;
$perPage = 10;

// Get books
$books = $bookModel->getAll($page, $perPage, $search, $typeFilter);
$totalBooks = $bookModel->count($search, $typeFilter);
$totalPages = ceil($totalBooks / $perPage);

// Get types for filter
$types = $bookModel->getTypes();

ob_start();
?>

<!-- Search and Filter Bar -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="search-bar">
            <input 
                type="text" 
                name="search" 
                class="form-input search-input" 
                placeholder="Search by title, author, or ISBN..."
                value="<?= e($search ?? '') ?>"
            >
            
            <select name="type" class="form-select" style="max-width: 200px;">
                <option value="">All Categories</option>
                <?php foreach ($types as $type): ?>
                    <option value="<?= $type['id'] ?>" <?= $typeFilter == $type['id'] ? 'selected' : '' ?>>
                        <?= e($type['Name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-primary">Search</button>
            
            <?php if ($search || $typeFilter): ?>
                <a href="books.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Books Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Books (<?= number_format($totalBooks) ?>)</h2>
        <div style="display: flex; gap: var(--space-3);">
            <a href="?export=csv<?= $search ? '&search=' . urlencode($search) : '' ?><?= $category ? '&category=' . $category : '' ?>" class="btn btn-success btn-sm">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
            <?php if ($auth->hasPermission('books', 'insert')): ?>
                <a href="book-form.php" class="btn btn-success">+ Add New Book</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <?php if (empty($books)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-book-open"></i></div>
                <p>No books found</p>
                <?php if ($auth->hasPermission('books', 'insert')): ?>
                    <a href="book-form.php" class="btn btn-primary mt-4">Add Your First Book</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ISBN</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><?= e($book['ISBN_NO']) ?></td>
                                <td class="font-semibold"><?= e($book['Book_Title']) ?></td>
                                <td><?= e($book['Author_Name']) ?></td>
                                <td>
                                    <?php if ($book['type_name']): ?>
                                        <span class="badge badge-primary"><?= e($book['type_name']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($book['Quantity'] > 0): ?>
                                        <span class="badge badge-success"><?= $book['Quantity'] ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format($book['Price'], 2) ?></td>
                                <td>
                                    <div style="display: flex; gap: var(--space-2);">
                                        <?php if ($auth->hasPermission('books', 'edit')): ?>
                                            <a href="book-form.php?id=<?= $book['id'] ?>" class="btn btn-primary btn-sm">
                                                Edit
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($auth->hasPermission('books', 'delete')): ?>
                                            <form method="GET" action="" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="delete" value="<?= $book['id'] ?>">
                                                <button 
                                                    type="submit" 
                                                    class="btn btn-danger btn-sm"
                                                    data-confirm="Are you sure you want to delete this book?"
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
                        <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $typeFilter ? '&type=' . $typeFilter : '' ?>" class="pagination-btn">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a 
                            href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $typeFilter ? '&type=' . $typeFilter : '' ?>" 
                            class="pagination-btn <?= $i === $page ? 'active' : '' ?>"
                        >
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $typeFilter ? '&type=' . $typeFilter : '' ?>" class="pagination-btn">
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
$pageTitle = 'Books';
$currentPage = 'books';
require __DIR__ . '/../src/Views/layouts/app.php';
