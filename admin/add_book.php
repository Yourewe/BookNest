<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input data
    $title = sanitize_input($_POST['title']);
    $author = sanitize_input($_POST['author']);
    $genre = sanitize_input($_POST['genre']);
    $price = floatval($_POST['price']);
    $description = sanitize_input($_POST['description']);
    $stock = intval($_POST['stock_quantity']);
    
    // Validate data
    $validation_errors = validate_book_data([
        'title' => $title,
        'author' => $author,
        'price' => $price,
        'stock_quantity' => $stock
    ]);
    
    $errors = array_merge($errors, $validation_errors);
    
    // Handle image upload
    $cover_image = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_result = upload_book_cover($_FILES['cover_image']);
        if ($upload_result['success']) {
            $cover_image = $upload_result['filename'];
        } else {
            $errors[] = $upload_result['message'];
        }
    }
    
    // If no errors, insert the book
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO books (title, author, genre, price, description, stock_quantity, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdsis", $title, $author, $genre, $price, $description, $stock, $cover_image);
            
            if ($stmt->execute()) {
                redirect_with_message('manage_books.php', 'Book added successfully!', 'success');
            } else {
                $errors[] = "Failed to add book. Please try again.";
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book - BookNest Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="container">
            <div class="admin-nav">
                <div class="logo">📚 BookNest Admin</div>
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_books.php">Manage Books</a>
                <a href="orders.php">Orders</a>
                <a href="../books.php">View Site</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="form-container">
            <h2>Add New Book</h2>
            
            <!-- Display errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Display session messages -->
            <?php echo display_session_message(); ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Book Title *</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="author">Author *</label>
                    <input type="text" id="author" name="author" required 
                           value="<?php echo isset($_POST['author']) ? htmlspecialchars($_POST['author']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="genre">Genre</label>
                    <input type="text" id="genre" name="genre" 
                           value="<?php echo isset($_POST['genre']) ? htmlspecialchars($_POST['genre']) : ''; ?>"
                           placeholder="e.g., Fiction, Non-fiction, Mystery, Romance">
                </div>

                <div class="form-group">
                    <label for="price">Price (Rs.) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required 
                           value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity *</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" min="0" required 
                           value="<?php echo isset($_POST['stock_quantity']) ? $_POST['stock_quantity'] : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" 
                              placeholder="Enter book description, summary, or key features..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="cover_image">Book Cover Image</label>
                    <div class="file-upload" id="fileUploadArea" style="cursor:pointer;">
                        <input type="file" id="cover_image" name="cover_image" accept="image/*" style="display:none;">
                        <div class="file-upload-text" id="fileUploadText">
                            📷 Click to upload book cover image<br>
                            <small>Supported formats: JPG, PNG, GIF (Max 5MB)</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-success">Add Book</button>
                    <a href="manage_books.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Make the file upload area clickable
        document.getElementById('fileUploadArea').addEventListener('click', function() {
            document.getElementById('cover_image').click();
        });

        // File upload preview
        document.getElementById('cover_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const uploadText = document.getElementById('fileUploadText');
            if (file) {
                uploadText.innerHTML = `📷 Selected: ${file.name}<br><small>Size: ${(file.size / 1024 / 1024).toFixed(2)} MB</small>`;
            } else {
                uploadText.innerHTML = '📷 Click to upload book cover image<br><small>Supported formats: JPG, PNG, GIF (Max 5MB)</small>';
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const price = document.getElementById('price').value;
            const stock = document.getElementById('stock_quantity').value;
            if (price < 0) {
                alert('Price cannot be negative');
                e.preventDefault();
                return;
            }
            if (stock < 0) {
                alert('Stock quantity cannot be negative');
                e.preventDefault();
                return;
            }
        });

        // Auto-refresh the page every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>
