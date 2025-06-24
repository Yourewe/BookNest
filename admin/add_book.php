<?php
require '../includes/db.php';
require '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $genre = $_POST['genre'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $stock = $_POST['stock_quantity'];

    $stmt = $conn->prepare("INSERT INTO books (title, author, genre, price, description, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $title, $author, $genre, $price, $description, $stock);
    $stmt->execute();

    echo "Book added successfully. <a href='dashboard.php'>Back to Dashboard</a>";
}
?>

<h3>Add New Book</h3>
<form method="POST">
    Title: <input type="text" name="title" required><br>
    Author: <input type="text" name="author" required><br>
    Genre: <input type="text" name="genre" required><br>
    Price: <input type="number" step="0.01" name="price" required><br>
    Description:<br>
    <textarea name="description" required></textarea><br>
    Stock Quantity: <input type="number" name="stock_quantity" required><br>
    <button type="submit">Add Book</button>
</form>
