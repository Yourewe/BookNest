<?php
require '../includes/db.php';
require '../includes/auth.php';

$id = $_GET['id'];
$conn->query("DELETE FROM books WHERE book_id = $id");
header("Location: manage_books.php");
?>
