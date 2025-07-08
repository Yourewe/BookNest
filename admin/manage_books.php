<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle search and pagination
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;

// Build search query
$where_clause = "1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $where_clause .= " AND (title LIKE ? OR author LIKE ? OR genre LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
    $types = "sss";
}

// Count total books
$count_sql = "SELECT COUNT(*) as total FROM books WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_books = $count_stmt->get_result()->fetch_assoc()['total'];

// Calculate pagination
$total_pages = ceil($total_books / $per_page);
$offset = ($page - 1) * $per_page;

// Get books
$sql = "SELECT * FROM books WHERE $where_clause ORDER BY title ASC LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get quick stats
$stats_result = $conn->query("
    SELECT 
        COUNT(*) as total_books,
        SUM(stock_quantity) as total_stock,
        COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock,
        COUNT(CASE WHEN stock_quantity <= 5 AND stock_quantity > 0 THEN 1 END) as low_stock
    FROM books
");
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - BookNest Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="container">
            <div class="admin-nav">
                <div class="logo">📚 BookNest Admin</div>
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_books.php">Manage Books</a>
                <a href="orders.php">Orders</a>
                <a href="../books.php">View Site</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Display session messages -->
        <?php echo display_session_message(); ?>

        <h2>Manage Books</h2>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_books']; ?></div>
                <div class="stat-label">Total Books</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_stock']; ?></div>
                <div class="stat-label">Total Stock</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #dc3545;"><?php echo $stats['out_of_stock']; ?></div>
                <div class="stat-label">Out of Stock</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ffc107;"><?php echo $stats['low_stock']; ?></div>
                <div class="stat-label">Low Stock</div>
            </div>
        </div>

        <!-- Actions and Search -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin: 30px 0 20px 0; flex-wrap: wrap; gap: 15px;">
            <a href="add_book.php" class="btn btn-success">+ Add New Book</a>
            
            <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                <input type="text" name="search" placeholder="Search books..." 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       style="padding: 8px; border: 2px solid #ddd; border-radius: 5px;">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="manage_books.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Books Table -->
        <div class="table">
            <table>
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Genre</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows === 0): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                                <?php if (!empty($search)): ?>
                                    No books found matching "<?php echo htmlspecialchars($search); ?>"
                                <?php else: ?>
                                    No books found. <a href="add_book.php">Add your first book</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($book = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="width: 50px; height: 70px; background: #f8f9fa; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                        <?php if (!empty($book['cover_image']) && file_exists('../' . $book['cover_image'])): ?>
                                            <img src="../<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                                 alt="Cover" style="max-width: 100%; max-height: 100%; object-fit: cover; border-radius: 3px;">
                                        <?php else: ?>
                                            📚
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                    <br><small style="color: #666;">ID: <?php echo $book['book_id']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td>
                                    <?php if (!empty($book['genre'])): ?>
                                        <span class="book-genre"><?php echo htmlspecialchars($book['genre']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo format_price($book['price']); ?></strong></td>
                                <td>
                                    <span style="font-weight: bold; color: <?php echo $book['stock_quantity'] <= 5 ? ($book['stock_quantity'] == 0 ? '#dc3545' : '#ffc107') : '#28a745'; ?>">
                                        <?php echo $book['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo get_stock_status($book['stock_quantity']); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <a href="../book_details.php?id=<?php echo $book['book_id']; ?>" 
                                           class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;" 
                                           target="_blank" title="View Book">👁</a>
                                        <a href="edit_book.php?id=<?php echo $book['book_id']; ?>" 
                                           class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;" 
                                           title="Edit Book">✏️</a>
                                        <a href="delete_books.php?id=<?php echo $book['book_id']; ?>" 
                                           class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" 
                                           onclick="return confirm('Are you sure you want to delete \"<?php echo htmlspecialchars($book['title']); ?>\"? This action cannot be undone.');" 
                                           title="Delete Book">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
            
            <p style="text-align: center; color: #666; margin-top: 10px;">
                Showing <?php echo (($page - 1) * $per_page) + 1; ?> to 
                <?php echo min($page * $per_page, $total_books); ?> of 
                <?php echo $total_books; ?> books
            </p>
        <?php endif; ?>
    </div>

    <script>
        // Bulk actions (future enhancement)
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('input[name="book_ids[]"]');
            for (let checkbox of checkboxes) {
                checkbox.checked = source.checked;
            }
        }

        // Quick stock update (future enhancement)
        function quickStockUpdate(bookId, currentStock) {
            const newStock = prompt(`Update stock for this book (current: ${currentStock}):`);
            if (newStock !== null && !isNaN(newStock) && newStock >= 0) {
                // You can implement AJAX call here
                alert('Stock update feature coming soon!');
            }
        }

        // Auto-refresh functionality
        setTimeout(function() {
            location.reload();
        }, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>
