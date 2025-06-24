<?php
session_start();
require 'includes/db.php';

$book_id = $_GET['id'];
$result = $conn->query("SELECT * FROM books WHERE book_id = $book_id");
$book = $result->fetch_assoc();
?>

<h2><?php echo $book['title']; ?></h2>
<p>Author: <?php echo $book['author']; ?></p>
<p>Price: Rs. <?php echo $book['price']; ?></p>

<form method="POST" action="cart.php">
    <input type="hidden" name="book_id" value="<?php echo $book['book_id']; ?>">
    Quantity: <input type="number" name="quantity" value="1" min="1" max="<?php echo $book['stock_quantity']; ?>">
    <button type="submit" name="add_to_cart">Add to Cart</button>
</form>
