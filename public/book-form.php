<?php
require_once __DIR__ . '/bootstrap.php';

use BookHive\Core\Auth;
use BookHive\Models\Book;
use BookHive\Core\Validator;

$auth = new Auth();
$auth->requireAuth();

$bookModel = new Book();
$isEdit = isset($_GET['id']);
$book = null;

if ($isEdit) {
    $auth->requirePermission('books', 'edit');
    $bookId = (int)$_GET['id'];
    $book = $bookModel->find($bookId);
    
    if (!$book) {
        flash('error', 'Book not found');
        redirect('books.php');
    }
} else {
    $auth->requirePermission('books', 'insert');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $data = Validator::sanitizeArray($_POST);
    
    $validator = new Validator($data);
    $rules = [
        'Book_Title' => 'required|max:200',
        'ISBN_NO' => 'max:100',
        'Author_Name' => 'max:100',
        'Quantity' => 'numeric',
        'Price' => 'numeric',
        'Pages' => 'numeric',
    ];
    
    if ($validator->validate($rules)) {
        try {
            if ($isEdit) {
                $bookModel->update($bookId, $data);
                flash('success', 'Book updated successfully');
            } else {
                $bookModel->create($data);
                flash('success', 'Book added successfully');
            }
            redirect('books.php');
        } catch (\Exception $e) {
            flash('error', 'Error saving book: ' . $e->getMessage());
        }
    } else {
        $_SESSION['errors'] = $validator->errors();
        $_SESSION['old'] = $data;
    }
}

$types = $bookModel->getTypes();

ob_start();
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title"><?= $isEdit ? 'Edit Book' : 'Add New Book' ?></h2>
    </div>
    
    <div class="card-body">
        <form method="POST" action="">
            <?= csrf_field() ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6);">
                <div class="form-group">
                    <label for="ISBN_NO" class="form-label">ISBN Number</label>
                    <input 
                        type="text" 
                        id="ISBN_NO" 
                        name="ISBN_NO" 
                        class="form-input"
                        value="<?= e($book['ISBN_NO'] ?? old('ISBN_NO')) ?>"
                    >
                    <?php if (error('ISBN_NO')): ?>
                        <div class="form-error"><?= e(error('ISBN_NO')) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="Book_Title" class="form-label">Book Title *</label>
                    <input 
                        type="text" 
                        id="Book_Title" 
                        name="Book_Title" 
                        class="form-input"
                        value="<?= e($book['Book_Title'] ?? old('Book_Title')) ?>"
                        required
                    >
                    <?php if (error('Book_Title')): ?>
                        <div class="form-error"><?= e(error('Book_Title')) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="Author_Name" class="form-label">Author Name</label>
                    <input 
                        type="text" 
                        id="Author_Name" 
                        name="Author_Name" 
                        class="form-input"
                        value="<?= e($book['Author_Name'] ?? old('Author_Name')) ?>"
                    >
                    <?php if (error('Author_Name')): ?>
                        <div class="form-error"><?= e(error('Author_Name')) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="Book_Type" class="form-label">Category</label>
                    <select id="Book_Type" name="Book_Type" class="form-select">
                        <option value="">Select Category</option>
                        <?php foreach ($types as $type): ?>
                            <option 
                                value="<?= $type['id'] ?>"
                                <?= ($book['Book_Type'] ?? old('Book_Type')) == $type['id'] ? 'selected' : '' ?>
                            >
                                <?= e($type['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="Quantity" class="form-label">Quantity</label>
                    <input 
                        type="number" 
                        id="Quantity" 
                        name="Quantity" 
                        class="form-input"
                        value="<?= e($book['Quantity'] ?? old('Quantity', 0)) ?>"
                        min="0"
                    >
                    <?php if (error('Quantity')): ?>
                        <div class="form-error"><?= e(error('Quantity')) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="Price" class="form-label">Price ($)</label>
                    <input 
                        type="number" 
                        id="Price" 
                        name="Price" 
                        class="form-input"
                        value="<?= e($book['Price'] ?? old('Price', '0.00')) ?>"
                        step="0.01"
                        min="0"
                    >
                    <?php if (error('Price')): ?>
                        <div class="form-error"><?= e(error('Price')) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="Purchase_Date" class="form-label">Purchase Date</label>
                    <input 
                        type="date" 
                        id="Purchase_Date" 
                        name="Purchase_Date" 
                        class="form-input"
                        value="<?= e($book['Purchase_Date'] ?? old('Purchase_Date')) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="Edition" class="form-label">Edition</label>
                    <input 
                        type="text" 
                        id="Edition" 
                        name="Edition" 
                        class="form-input"
                        value="<?= e($book['Edition'] ?? old('Edition')) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="Pages" class="form-label">Pages</label>
                    <input 
                        type="number" 
                        id="Pages" 
                        name="Pages" 
                        class="form-input"
                        value="<?= e($book['Pages'] ?? old('Pages')) ?>"
                        min="0"
                    >
                    <?php if (error('Pages')): ?>
                        <div class="form-error"><?= e(error('Pages')) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="Publisher" class="form-label">Publisher</label>
                    <input 
                        type="text" 
                        id="Publisher" 
                        name="Publisher" 
                        class="form-input"
                        value="<?= e($book['Publisher'] ?? old('Publisher')) ?>"
                    >
                </div>
            </div>

            <div style="display: flex; gap: var(--space-3); margin-top: var(--space-6);">
                <button type="submit" class="btn btn-success">
                    <?= $isEdit ? 'Update Book' : 'Add Book' ?>
                </button>
                <a href="books.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
// Clear old input and errors
unset($_SESSION['old'], $_SESSION['errors']);

$content = ob_get_clean();
$pageTitle = $isEdit ? 'Edit Book' : 'Add New Book';
$currentPage = 'books';
require __DIR__ . '/../src/Views/layouts/app.php';
