<?php
require '../includes/db.php';
require '../includes/auth.php';

$id = $_GET['id'];
$res = $conn->query("SELECT * FROM books WHERE book_id = $id");
$book = $res->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $genre = $_POST['genre'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $stock = $_POST['stock_quantity'];

    $conn->query("UPDATE books SET title='$title', author='$author', genre='$genre', price='$price', description='$description', stock_quantity='$stock' WHERE book_id=$id");
    header("Location: manage_books.php");
}
?>

<h3>Edit Book</h3>
<form method="POST">
    Title: <input type="text" name="title" value="<?php echo $book['title']; ?>" required><br>
    Author: <input type="text" name="author" value="<?php echo $book['author']; ?>" required><br>
    Genre: <input type="text" name="genre" value="<?php echo $book['genre']; ?>" required><br>
    Price: <input type="number" step="0.01" name="price" value="<?php echo $book['price']; ?>" required><br>
    Description:<br>
    <textarea name="description" required><?php echo $book['description']; ?></textarea><br>
    Stock Quantity: <input type="number" name="stock_quantity" value="<?php echo $book['stock_quantity']; ?>" required><br>
    <button type="submit">Update Book</button>
</form>
