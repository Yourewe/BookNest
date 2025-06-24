<?php
require '../includes/db.php';
require '../includes/auth.php';

$result = $conn->query("SELECT * FROM books");

echo "<h3>Manage Books</h3>";
while ($book = $result->fetch_assoc()) {
    echo "<div>
            <strong>{$book['title']}</strong> by {$book['author']} (Stock: {$book['stock_quantity']})<br>
            <a href='edit_book.php?id={$book['book_id']}'>Edit</a> | 
            <a href='delete_book.php?id={$book['book_id']}'>Delete</a>
          </div><hr>";
}
echo "<a href='dashboard.php'>Back to Dashboard</a>";
?>
