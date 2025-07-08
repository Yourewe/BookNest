<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = sanitize_input($_POST['status']);
    
    $valid_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        if ($stmt->execute()) {
            redirect_with_message('orders.php', 'Order status updated successfully!', 'success');
        } else {
            redirect_with_message('orders.php', 'Failed to update order status', 'error');
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get search parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Build query
$where_conditions = ["1=1"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR o.order_id = ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search]);
    $types .= "sss";
}

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Count total orders
$count_sql = "SELECT COUNT(*) as total FROM orders o JOIN users u ON o.user_id = u.user_id WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];

// Calculate pagination
$total_pages = ceil($total_orders / $per_page);

// Get orders
$sql = "SELECT o.*, u.name as customer_name, u.email as customer_email 
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        WHERE $where_clause 
        ORDER BY o.order_date DESC 
        LIMIT $per_page OFFSET $offset";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders_result = $stmt->get_result();

// Get quick stats
$stats_result = $conn->query("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'Processing' THEN 1 END) as processing_orders,
        COUNT(CASE WHEN status = 'Shipped' THEN 1 END) as shipped_orders,
        COUNT(CASE WHEN status = 'Delivered' THEN 1 END) as delivered_orders,
        COALESCE(SUM(total_amount), 0) as total_revenue
    FROM orders
");
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - BookNest Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-shipped { background: #d4edda; color: #155724; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .order-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .status-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .status-filter {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 15px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .status-filter.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .status-filter:hover {
            border-color: #667eea;
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

        <h2>📦 Orders Management</h2>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ffc107;"><?php echo $stats['pending_orders']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #17a2b8;"><?php echo $stats['processing_orders']; ?></div>
                <div class="stat-label">Processing</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #28a745;"><?php echo $stats['delivered_orders']; ?></div>
                <div class="stat-label">Delivered</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo format_price($stats['total_revenue']); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 30px 0;">
            <form method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <input type="text" name="search" placeholder="Search by customer name, email, or order ID..." 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       style="flex: 1; min-width: 200px; padding: 8px; border: 2px solid #ddd; border-radius: 5px;">
                
                <select name="status" style="padding: 8px; border: 2px solid #ddd; border-radius: 5px;">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Processing" <?php echo $status_filter === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="Shipped" <?php echo $status_filter === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="Delivered" <?php echo $status_filter === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                
                <button type="submit" class="btn btn-primary">Search</button>
                
                <?php if (!empty($search) || !empty($status_filter)): ?>
                    <a href="orders.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="table">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders_result->num_rows === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                <?php if (!empty($search) || !empty($status_filter)): ?>
                                    No orders found matching your criteria
                                <?php else: ?>
                                    No orders found
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($order = $orders_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo $order['order_id']; ?></strong>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                    <br><small style="color: #666;"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($order['order_date'])); ?>
                                    <br><small style="color: #666;"><?php echo date('g:i A', strtotime($order['order_date'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo format_price($order['total_amount']); ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <!-- Status Update Form -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <select name="status" style="padding: 3px; font-size: 12px; border: 1px solid #ddd; border-radius: 3px;">
                                                <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Processing" <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="Shipped" <?php echo $order['status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="Delivered" <?php echo $order['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="Cancelled" <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-success" 
                                                    style="padding: 3px 8px; font-size: 12px;">Update</button>
                                        </form>
                                    </div>
                                    
                                    <!-- Order Details (expandable) -->
                                    <div class="order-details" id="details-<?php echo $order['order_id']; ?>" style="display: none;">
                                        <?php
                                        // Get order items
                                        $items_sql = "SELECT od.*, b.title, b.author 
                                                     FROM order_details od 
                                                     JOIN books b ON od.book_id = b.book_id 
                                                     WHERE od.order_id = ?";
                                        $items_stmt = $conn->prepare($items_sql);
                                        $items_stmt->bind_param("i", $order['order_id']);
                                        $items_stmt->execute();
                                        $items_result = $items_stmt->get_result();
                                        ?>
                                        
                                        <strong>Order Items:</strong>
                                        <ul style="margin: 5px 0; padding-left: 20px;">
                                            <?php while ($item = $items_result->fetch_assoc()): ?>
                                                <li>
                                                    <?php echo htmlspecialchars($item['title']); ?> 
                                                    by <?php echo htmlspecialchars($item['author']); ?>
                                                    (Qty: <?php echo $item['quantity']; ?> - <?php echo format_price($item['subtotal']); ?>)
                                                </li>
                                            <?php endwhile; ?>
                                        </ul>
                                        
                                        <?php if (!empty($order['shipping_address'])): ?>
                                            <strong>Shipping Address:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?><br>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($order['phone'])): ?>
                                            <strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?><br>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($order['payment_method'])): ?>
                                            <strong>Payment:</strong> <?php echo ucfirst($order['payment_method']); ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button onclick="toggleDetails(<?php echo $order['order_id']; ?>)" 
                                            class="btn btn-secondary" style="padding: 3px 8px; font-size: 12px; margin-top: 5px;">
                                        View Details
                                    </button>
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
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
            
            <p style="text-align: center; color: #666; margin-top: 10px;">
                Showing <?php echo (($page - 1) * $per_page) + 1; ?> to 
                <?php echo min($page * $per_page, $total_orders); ?> of 
                <?php echo $total_orders; ?> orders
            </p>
        <?php endif; ?>
    </div>

    <script>
        function toggleDetails(orderId) {
            const details = document.getElementById('details-' + orderId);
            const button = event.target;
            
            if (details.style.display === 'none') {
                details.style.display = 'block';
                button.textContent = 'Hide Details';
            } else {
                details.style.display = 'none';
                button.textContent = 'View Details';
            }
        }
        
        // Auto-submit status updates
        document.querySelectorAll('select[name="status"]').forEach(select => {
            select.addEventListener('change', function() {
                // You can uncomment the line below to auto-submit status changes
                // this.closest('form').submit();
            });
        });
        
        // Auto-refresh functionality
        setTimeout(function() {
            location.reload();
        }, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>
