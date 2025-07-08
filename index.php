<?php
session_start();
require 'includes/db.php';
require 'includes/functions.php';

// Get featured books (latest or most popular)
$featured_books = $conn->query("SELECT * FROM books WHERE stock_quantity > 0 ORDER BY book_id DESC LIMIT 6");

// Get quick stats for homepage
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_books,
        COUNT(DISTINCT author) as total_authors,
        COUNT(DISTINCT genre) as total_genres
    FROM books
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookNest - Your Online Bookstore</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 50px;
        }
        
        .hero-content h1 {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        
        .hero-stat {
            text-align: center;
        }
        
        .hero-stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        
        .hero-stat-label {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .section-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 30px;
            color: #333;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin: 50px 0;
        }
        
        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-stats {
                gap: 20px;
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1>Welcome to BookNest</h1>
                <p>Discover your next favorite book from our extensive collection</p>
                <a href="books.php" class="btn btn-success" style="font-size: 1.1rem; padding: 15px 30px;">
                    Explore Books
                </a>
                
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?php echo $stats['total_books']; ?>+</span>
                        <span class="hero-stat-label">Books Available</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?php echo $stats['total_authors']; ?>+</span>
                        <span class="hero-stat-label">Authors</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?php echo $stats['total_genres']; ?>+</span>
                        <span class="hero-stat-label">Genres</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Display session messages -->
        <?php echo display_session_message(); ?>

        <!-- Features Section -->
        <h2 class="section-title">Why Choose BookNest?</h2>
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">🚚</div>
                <h3>Fast Delivery</h3>
                <p>Get your books delivered quickly and safely to your doorstep</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>Best Prices</h3>
                <p>Competitive prices on all books with regular discounts and offers</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔍</div>
                <h3>Easy Search</h3>
                <p>Find your favorite books easily with our advanced search and filters</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⭐</div>
                <h3>Quality Assured</h3>
                <p>All books are carefully selected and quality checked before delivery</p>
            </div>
        </div>

        <!-- Featured Books -->
        <?php if ($featured_books->num_rows > 0): ?>
            <h2 class="section-title">Featured Books</h2>
            <div class="books-grid">
                <?php while ($book = $featured_books->fetch_assoc()): ?>
                    <div class="book-card">
                        <div class="book-image">
                            <?php if (!empty($book['cover_image']) && file_exists($book['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>">
                            <?php else: ?>
                                📚
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
                        
                        <div class="book-actions">
                            <a href="book_details.php?id=<?php echo $book['book_id']; ?>" 
                               class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="books.php" class="btn btn-secondary">View All Books</a>
            </div>
        <?php endif; ?>

        <!-- Call to Action -->
        <div style="background: #f8f9fa; padding: 40px; border-radius: 10px; text-align: center; margin: 50px 0;">
            <h2>Ready to Start Reading?</h2>
            <p style="font-size: 1.1rem; margin-bottom: 25px;">Join thousands of book lovers and discover your next great read</p>
            <?php if (!is_logged_in()): ?>
                <a href="register.php" class="btn btn-success" style="margin-right: 15px;">Get Started</a>
                <a href="login.php" class="btn btn-secondary">Sign In</a>
            <?php else: ?>
                <a href="books.php" class="btn btn-primary">Browse Books</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer style="background: #343a40; color: white; padding: 40px 0; margin-top: 60px;">
        <div class="container" style="text-align: center;">
            <div class="logo" style="margin-bottom: 20px;">📚 BookNest</div>
            <p style="margin-bottom: 20px;">Your trusted online bookstore for all your reading needs</p>
            <div style="display: flex; justify-content: center; gap: 30px; flex-wrap: wrap;">
                <a href="books.php" style="color: #ccc; text-decoration: none;">Books</a>
                <a href="#" style="color: #ccc; text-decoration: none;">About Us</a>
                <a href="#" style="color: #ccc; text-decoration: none;">Contact</a>
                <a href="#" style="color: #ccc; text-decoration: none;">Privacy Policy</a>
            </div>
            <hr style="margin: 30px 0; border-color: #495057;">
            <p style="margin: 0; opacity: 0.7;">&copy; <?php echo date('Y'); ?> BookNest. All rights reserved.</p>
        </div>
    </footer>

    <!-- Auto-refresh script -->
    <script>
        setTimeout(function() {
            location.reload();
        }, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>
