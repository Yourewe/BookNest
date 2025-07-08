<?php
session_start();
require 'includes/db.php';
require 'includes/functions.php';

// Get book ID and validate
$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id <= 0) {
    redirect_with_message('books.php', 'Invalid book ID', 'error');
}

// Get book details
$book = get_book_by_id($conn, $book_id);

if (!$book) {
    redirect_with_message('books.php', 'Book not found', 'error');
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!is_logged_in()) {
        redirect_with_message('login.php', 'Please login to add items to cart', 'warning');
    }
    
    $quantity = max(1, intval($_POST['quantity']));
    
    if ($quantity > $book['stock_quantity']) {
        redirect_with_message('book_details.php?id=' . $book_id, 'Not enough stock available', 'error');
    }
    
    // Add to cart (you'll need to implement cart functionality)
    redirect_with_message('cart.php', 'Book added to cart successfully!', 'success');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - BookNest</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .book-details {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
            margin: 20px 0;
        }
        
        .book-image-large {
            width: 100%;
            max-width: 400px;
            height: 500px;
            background: #f8f9fa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #6c757d;
        }
        
        .book-image-large img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .book-info h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #333;
        }
        
        .book-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 20px 0;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        
        .meta-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
        }
        
        .meta-value {
            font-size: 1.1rem;
        }
        
        .book-description {
            margin: 30px 0;
            line-height: 1.8;
            color: #555;
        }
        
        .purchase-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        
        .quantity-selector input {
            width: 80px;
            padding: 8px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .book-details {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .book-info h1 {
                font-size: 2rem;
            }
        }
    </style>
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

        <!-- Back to books link -->
        <p><a href="books.php" class="btn btn-secondary">← Back to Books</a></p>

        <!-- Book Details -->
        <div class="book-details">
            <!-- Book Image -->
            <div class="book-image-large">
                <?php if (!empty($book['cover_image']) && file_exists($book['cover_image'])): ?>
                    <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                         alt="<?php echo htmlspecialchars($book['title']); ?>">
                <?php else: ?>
                    📚
                <?php endif; ?>
            </div>

            <!-- Book Information -->
            <div class="book-info">
                <h1><?php echo htmlspecialchars($book['title']); ?></h1>
                
                <div class="book-meta">
                    <div class="meta-item">
                        <div class="meta-label">Author</div>
                        <div class="meta-value"><?php echo htmlspecialchars($book['author']); ?></div>
                    </div>
                    
                    <?php if (!empty($book['genre'])): ?>
                    <div class="meta-item">
                        <div class="meta-label">Genre</div>
                        <div class="meta-value">
                            <span class="book-genre"><?php echo htmlspecialchars($book['genre']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="meta-item">
                        <div class="meta-label">Price</div>
                        <div class="meta-value book-price"><?php echo format_price($book['price']); ?></div>
                    </div>
                    
                    <div class="meta-item">
                        <div class="meta-label">Availability</div>
                        <div class="meta-value">
                            <?php echo get_stock_status($book['stock_quantity']); ?>
                            (<?php echo $book['stock_quantity']; ?> in stock)
                        </div>
                    </div>
                </div>

                <?php if (!empty($book['description'])): ?>
                <div class="book-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Purchase Section -->
                <div class="purchase-section">
                    <?php if ($book['stock_quantity'] > 0): ?>
                        <?php if (is_logged_in()): ?>
                            <form method="POST">
                                <div class="quantity-selector">
                                    <label for="quantity"><strong>Quantity:</strong></label>
                                    <input type="number" name="quantity" id="quantity" value="1" 
                                           min="1" max="<?php echo $book['stock_quantity']; ?>" required>
                                    <span>of <?php echo $book['stock_quantity']; ?> available</span>
                                </div>
                                
                                <button type="submit" name="add_to_cart" class="btn btn-success">
                                    🛒 Add to Cart
                                </button>
                            </form>
                        <?php else: ?>
                            <p><strong>Please <a href="login.php">login</a> to purchase this book.</strong></p>
                            <a href="login.php" class="btn btn-primary">Login</a>
                            <a href="register.php" class="btn btn-secondary">Register</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><strong>This book is currently out of stock.</strong></p>
                        <button class="btn btn-secondary" disabled>Out of Stock</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Related Books (Optional enhancement) -->
        <?php
        // Get related books by same author or genre
        $related_sql = "SELECT * FROM books WHERE (author = ? OR genre = ?) AND book_id != ? LIMIT 4";
        $related_stmt = $conn->prepare($related_sql);
        $related_stmt->bind_param("ssi", $book['author'], $book['genre'], $book_id);
        $related_stmt->execute();
        $related_result = $related_stmt->get_result();
        
        if ($related_result->num_rows > 0):
        ?>
        <div style="margin-top: 50px;">
            <h3>Related Books</h3>
            <div class="books-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
                <?php while ($related = $related_result->fetch_assoc()): ?>
                    <div class="book-card">
                        <div class="book-image" style="height: 150px;">
                            <?php if (!empty($related['cover_image']) && file_exists($related['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($related['cover_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['title']); ?>">
                            <?php else: ?>
                                📚
                            <?php endif; ?>
                        </div>
                        
                        <div class="book-title" style="font-size: 1rem;">
                            <?php echo htmlspecialchars($related['title']); ?>
                        </div>
                        
                        <div class="book-author">
                            <?php echo htmlspecialchars($related['author']); ?>
                        </div>
                        
                        <div class="book-price">
                            <?php echo format_price($related['price']); ?>
                        </div>
                        
                        <div class="book-actions">
                            <a href="book_details.php?id=<?php echo $related['book_id']; ?>" 
                               class="btn btn-primary">View</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Update total price when quantity changes
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInput = document.getElementById('quantity');
            if (quantityInput) {
                quantityInput.addEventListener('change', function() {
                    // You can add price calculation logic here if needed
                    console.log('Quantity changed to:', this.value);
                });
            }
        });

        // Auto-refresh the page every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>
