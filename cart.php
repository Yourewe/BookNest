<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_POST['add_to_cart'])) {
    $book_id = $_POST['book_id'];
    $quantity = $_POST['quantity'];

    if (isset($_SESSION['cart'][$book_id])) {
        $_SESSION['cart'][$book_id] += $quantity;
    } else {
        $_SESSION['cart'][$book_id] = $quantity;
    }
    header("Location: cart.php");
    exit;
}

// Remove item
if (isset($_GET['remove'])) {
    $book_id = $_GET['remove'];
    unset($_SESSION['cart'][$book_id]);
    header("Location: cart.php");
    exit;
}
?>

<h2>Your Cart</h2>

<?php
$total = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $book_id => $quantity) {
        $res = $conn->query("SELECT * FROM books WHERE book_id = $book_id");
        $book = $res->fetch_assoc();
        $subtotal = $book['price'] * $quantity;
        $total += $subtotal;
        echo "<div>
                <h3>{$book['title']}</h3>
                <p>Quantity: $quantity</p>
                <p>Subtotal: Rs. $subtotal</p>
                <a href='cart.php?remove=$book_id'>Remove</a>
              </div><hr>";
    }
    echo "<h3>Total: Rs. $total</h3>";
    echo "<a href='checkout.php'>Proceed to Checkout</a>";
} else {
    echo "Your cart is empty.";
}
?>
