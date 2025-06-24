<?php
require 'includes/db.php';
$result = $conn->query("SELECT * FROM books");
while ($book = $result->fetch_assoc()) {
    echo "<div>
            <h3>{$book['title']}</h3>
            <p>by {$book['author']}</p>
            <p>Price: Rs. {$book['price']}</p>
            <a href='book_details.php?id={$book['book_id']}'>View</a>
          </div><hr>";
}
?>
