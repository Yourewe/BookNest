<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    echo "Your cart is empty.";
    exit;
}

$total = 0;
foreach ($_SESSION['cart'] as $book_id => $quantity) {
    $res = $conn->query("SELECT * FROM books WHERE book_id = $book_id");
    $book = $res->fetch_assoc();
    $total += $book['price'] * $quantity;
}

$user_id = $_SESSION['user_id'];
$conn->query("INSERT INTO orders (user_id, total_amount) VALUES ($user_id, $total)");
$order_id = $conn->insert_id;

foreach ($_SESSION['cart'] as $book_id => $quantity) {
    $res = $conn->query("SELECT * FROM books WHERE book_id = $book_id");
    $book = $res->fetch_assoc();
    $subtotal = $book['price'] * $quantity;
    
    $conn->query("INSERT INTO order_details (order_id, book_id, quantity, subtotal) 
                  VALUES ($order_id, $book_id, $quantity, $subtotal)");
    
    // Reduce stock
    $conn->query("UPDATE books SET stock_quantity = stock_quantity - $quantity WHERE book_id = $book_id");
}

unset($_SESSION['cart']);
echo "Order placed successfully! <a href='books.php'>Continue Shopping</a>";
?>
