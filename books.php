<?php
session_start();
require 'includes/db.php';
require 'includes/functions.php';

// Get search parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$genre = isset($_GET['genre']) ? sanitize_input($_GET['genre']) : '';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : '';
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'title';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Search books
$search_results = search_books($conn, $search, $genre, $min_price, $max_price, $sort, $page);
$books = $search_results['books'];
$total_results = $search_results['total_results'];
$total_pages = $search_results['total_pages'];
$current_page = $search_results['current_page'];

// Get all genres for filter dropdown
$genres = get_genres($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books - BookNest</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">📚 BookNest</div>
            <nav class="nav">
                <a href="index.php">Home</a>
                <a href="books.php">Books</a>
                <?php if (is_logged_in()): ?>
                    <a href="cart.php">Cart</a>
                    <?php if (is_admin()): ?>
                        <a href="admin/dashboard.php">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Display session messages -->
        <?php echo display_session_message(); ?>

        <!-- Search and Filter Section -->
        <div class="search-container">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search books, authors..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="genre">
                    <option value="">All Genres</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?php echo htmlspecialchars($g); ?>" 
                                <?php echo $genre === $g ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="number" name="min_price" placeholder="Min Price" step="0.01" 
                       value="<?php echo $min_price; ?>">
                
                <input type="number" name="max_price" placeholder="Max Price" step="0.01" 
                       value="<?php echo $max_price; ?>">
                
                <select name="sort">
                    <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title A-Z</option>
                    <option value="author" <?php echo $sort === 'author' ? 'selected' : ''; ?>>Author A-Z</option>
                    <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Price Low-High</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price High-Low</option>
                </select>
                
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="books.php" class="btn btn-secondary">Clear</a>
            </form>
        </div>

        <!-- Results Summary -->
        <div class="main-content">
            <p><strong><?php echo $total_results; ?></strong> books found</p>
            
            <?php if (empty($books)): ?>
                <div class="alert alert-warning">
                    <h3>No books found</h3>
                    <p>Try adjusting your search criteria or browse all books.</p>
                </div>
            <?php else: ?>
                <!-- Books Grid -->
                <div class="books-grid">
                    <?php foreach ($books as $book): ?>
                        <div class="book-card">
                            <div class="book-image">
                                <?php if (!empty($book['cover_image']) && file_exists($book['cover_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($book['title']); ?>">
                                <?php else: ?>
                                    📚 No Image
                                <?php endif; ?>
                            </div>
                            
                            <div class="book-title">
                                <?php echo htmlspecialchars($book['title']); ?>
                            </div>
                            
                            <div class="book-author">
                                by <?php echo htmlspecialchars($book['author']); ?>
                            </div>
                            
                            <?php if (!empty($book['genre'])): ?>
                                <div class="book-genre">
                                    <?php echo htmlspecialchars($book['genre']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="book-price">
                                <?php echo format_price($book['price']); ?>
                            </div>
                            
                            <div class="book-stock">
                                Stock: <?php echo $book['stock_quantity']; ?> 
                                <?php echo get_stock_status($book['stock_quantity']); ?>
                            </div>
                            
                            <div class="book-actions">
                                <a href="book_details.php?id=<?php echo $book['book_id']; ?>" 
                                   class="btn btn-primary">View Details</a>
                                
                                <?php if (is_logged_in() && $book['stock_quantity'] > 0): ?>
                                    <form method="POST" action="cart.php" style="display: inline;">
                                        <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" name="add_to_cart" class="btn btn-success">
                                            Add to Cart
                                        </button>
                                    </form>
                                <?php elseif (!is_logged_in()): ?>
                                    <a href="login.php" class="btn btn-secondary">Login to Buy</a>
                                <?php else: ?>
                                    <span class="btn btn-secondary" style="opacity: 0.6;">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <?php
                    $base_url = 'books.php';
                    $query_params = [];
                    if (!empty($search)) $query_params[] = 'search=' . urlencode($search);
                    if (!empty($genre)) $query_params[] = 'genre=' . urlencode($genre);
                    if (!empty($min_price)) $query_params[] = 'min_price=' . $min_price;
                    if (!empty($max_price)) $query_params[] = 'max_price=' . $max_price;
                    if (!empty($sort)) $query_params[] = 'sort=' . $sort;
                    
                    if (!empty($query_params)) {
                        $base_url .= '?' . implode('&', $query_params);
                    }
                    
                    echo generate_pagination($current_page, $total_pages, $base_url);
                    ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-submit form on filter change (optional)
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.search-form');
            const selects = form.querySelectorAll('select');
            
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    // Uncomment the line below to auto-submit on filter change
                    // form.submit();
                });
            });
        });

        // Auto-refresh functionality
        setTimeout(function() {
            location.reload();
        }, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>
