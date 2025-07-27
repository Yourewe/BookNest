<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id <= 0) {
    redirect_with_message('manage_books.php', 'Invalid book ID', 'error');
}

// Get book details first
$book = get_book_by_id($conn, $book_id);

if (!$book) {
    redirect_with_message('manage_books.php', 'Book not found', 'error');
}

try {
    // Start transaction (PDO method)
    $conn->beginTransaction();
    
    // Check if book has any orders (PDO version)
    $order_check = $conn->prepare("SELECT COUNT(*) as order_count FROM order_details WHERE book_id = ?");
    $order_check->execute([$book_id]);
    $order_result = $order_check->fetch();
    $order_count = $order_result['order_count'];
    
    if ($order_count > 0) {
        // Rollback and redirect with warning
        $conn->rollback();
        redirect_with_message('manage_books.php', 'Cannot delete book with existing orders. Consider updating stock to 0 instead.', 'warning');
    }
    
    // Delete the book
    $delete_stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
    $delete_stmt->bind_param("i", $book_id);
    
    if ($delete_stmt->execute()) {
        // Commit transaction
        $conn->commit();
        redirect_with_message('manage_books.php', 'Book "' . $book['title'] . '" deleted successfully', 'success');
    } else {
        throw new Exception("Failed to delete book from database");
    }
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    redirect_with_message('manage_books.php', 'Error deleting book: ' . $e->getMessage(), 'error');
}
?>

<script>
    setTimeout(function() {
        location.reload();
    }, 30000); // Refresh every 30 seconds
</script>
