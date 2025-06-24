<?php
require '../includes/db.php';
require '../includes/auth.php';

$res = $conn->query("SELECT o.order_id, o.order_date, o.total_amount, u.name 
                     FROM orders o 
                     JOIN users u ON o.user_id = u.user_id 
                     ORDER BY o.order_date DESC");

echo "<h3>All Orders</h3>";
while ($order = $res->fetch_assoc()) {
    echo "<div>
            Order ID: {$order['order_id']}<br>
            Customer: {$order['name']}<br>
            Date: {$order['order_date']}<br>
            Total: Rs. {$order['total_amount']}<br><hr>
          </div>";
}
echo "<a href='dashboard.php'>Back to Dashboard</a>";
?>
