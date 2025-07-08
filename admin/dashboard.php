<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Fetch comprehensive stats
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM books) as total_books,
        (SELECT COUNT(*) FROM orders) as total_orders,
        (SELECT COUNT(*) FROM users WHERE user_type = 'customer') as total_customers,
        (SELECT SUM(stock_quantity) FROM books) as total_stock,
        (SELECT COUNT(*) FROM books WHERE stock_quantity = 0) as out_of_stock,
        (SELECT COUNT(*) FROM books WHERE stock_quantity <= 5 AND stock_quantity > 0) as low_stock,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders) as total_revenue,
        (SELECT COUNT(*) FROM orders WHERE status = 'Pending') as pending_orders
";

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get recent orders
$recent_orders = $conn->query("
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    ORDER BY o.order_date DESC 
    LIMIT 5
");

// Get low stock books
$low_stock_books = $conn->query("
    SELECT * FROM books 
    WHERE stock_quantity <= 5 
    ORDER BY stock_quantity ASC 
    LIMIT 5
");

// Get top selling books (mock data for now, you can implement proper analytics later)
$top_books = $conn->query("
    SELECT b.title, b.author, COUNT(od.book_id) as sales_count
    FROM books b
    LEFT JOIN order_details od ON b.book_id = od.book_id
    GROUP BY b.book_id
    ORDER BY sales_count DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BookNest</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .dashboard-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .dashboard-section h3 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 5px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .action-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.3s;
        }
        
        .action-card:hover {
            transform: translateY(-3px);
            color: white;
        }
        
        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .list-item:last-child {
            border-bottom: none;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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

        <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>!</h2>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_books']; ?></div>
                <div class="stat-label">Total Books</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_customers']; ?></div>
                <div class="stat-label">Customers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ffc107;"><?php echo $stats['pending_orders']; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo format_price($stats['total_revenue']); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_stock']; ?></div>
                <div class="stat-label">Items in Stock</div>
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

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="add_book.php" class="action-card">
                <div style="font-size: 2rem; margin-bottom: 10px;">📖</div>
                <div>Add New Book</div>
            </a>
            <a href="manage_books.php" class="action-card">
                <div style="font-size: 2rem; margin-bottom: 10px;">📚</div>
                <div>Manage Books</div>
            </a>
            <a href="orders.php" class="action-card">
                <div style="font-size: 2rem; margin-bottom: 10px;">📦</div>
                <div>View Orders</div>
            </a>
            <a href="../books.php" class="action-card">
                <div style="font-size: 2rem; margin-bottom: 10px;">🌐</div>
                <div>View Site</div>
            </a>
        </div>

        <!-- Dashboard Content Grid -->
        <div class="dashboard-grid">
            <!-- Recent Orders -->
            <div class="dashboard-section">
                <h3>Recent Orders</h3>
                <?php if ($recent_orders->num_rows > 0): ?>
                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                        <div class="list-item">
                            <div>
                                <strong>#<?php echo $order['order_id']; ?></strong><br>
                                <small><?php echo htmlspecialchars($order['customer_name']); ?></small>
                            </div>
                            <div style="text-align: right;">
                                <strong><?php echo format_price($order['total_amount']); ?></strong><br>
                                <small><?php echo date('M j, Y', strtotime($order['order_date'])); ?></small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="orders.php" class="btn btn-primary">View All Orders</a>
                    </div>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 20px;">No orders yet</p>
                <?php endif; ?>
            </div>

            <!-- Low Stock Alert -->
            <div class="dashboard-section">
                <h3>Low Stock Alert</h3>
                <?php if ($low_stock_books->num_rows > 0): ?>
                    <?php while ($book = $low_stock_books->fetch_assoc()): ?>
                        <div class="list-item">
                            <div>
                                <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                <small><?php echo htmlspecialchars($book['author']); ?></small>
                            </div>
                            <div style="text-align: right;">
                                <span style="color: <?php echo $book['stock_quantity'] == 0 ? '#dc3545' : '#ffc107'; ?>; font-weight: bold;">
                                    <?php echo $book['stock_quantity']; ?> left
                                </span><br>
                                <a href="edit_book.php?id=<?php echo $book['book_id']; ?>" style="font-size: 12px;">Update</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="manage_books.php" class="btn btn-warning">Manage Stock</a>
                    </div>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 20px;">All books are well stocked! 👍</p>
                <?php endif; ?>
            </div>

            <!-- Top Selling Books -->
            <div class="dashboard-section">
                <h3>Popular Books</h3>
                <?php if ($top_books->num_rows > 0): ?>
                    <?php while ($book = $top_books->fetch_assoc()): ?>
                        <div class="list-item">
                            <div>
                                <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                <small><?php echo htmlspecialchars($book['author']); ?></small>
                            </div>
                            <div style="text-align: right;">
                                <span style="color: #28a745; font-weight: bold;">
                                    <?php echo $book['sales_count']; ?> sales
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 20px;">No sales data yet</p>
                <?php endif; ?>
            </div>

            <!-- System Info -->
            <div class="dashboard-section">
                <h3>System Information</h3>
                <div class="list-item">
                    <span>BookNest Version</span>
                    <span>1.0.0</span>
                </div>
                <div class="list-item">
                    <span>Last Login</span>
                    <span><?php echo date('M j, Y g:i A'); ?></span>
                </div>
                <div class="list-item">
                    <span>Server Status</span>
                    <span style="color: #28a745;">● Online</span>
                </div>
                <div class="list-item">
                    <span>Database</span>
                    <span style="color: #28a745;">● Connected</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh stats every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);

        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            console.log('BookNest Admin Dashboard loaded successfully');
        });
    </script>
</body>
</html>
