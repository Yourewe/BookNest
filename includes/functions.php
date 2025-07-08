<?php
// BookNest Utility Functions

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate secure filename for uploads
 */
function generate_filename($original_filename) {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Handle file upload for book covers
 */
function upload_book_cover($file) {
    $upload_dir = dirname(__DIR__) . '/assets/images/books/';
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Check and create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            return ['success' => false, 'message' => 'Failed to create image upload directory.'];
        }
    }

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred'];
    }

    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large (max 5MB)'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    // Generate secure filename
    $filename = generate_filename($file['name']);
    $filepath = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Return web-accessible path
        return ['success' => true, 'filename' => 'assets/images/books/' . $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}

/**
 * Format price with currency
 */
function format_price($price) {
    return 'Rs. ' . number_format($price, 2);
}

/**
 * Get stock status badge
 */
function get_stock_status($quantity) {
    if ($quantity > 10) {
        return '<span class="badge badge-success">In Stock</span>';
    } elseif ($quantity > 0) {
        return '<span class="badge badge-warning">Low Stock</span>';
    } else {
        return '<span class="badge badge-danger">Out of Stock</span>';
    }
}

/**
 * Pagination helper
 */
function generate_pagination($current_page, $total_pages, $base_url) {
    $pagination = '<div class="pagination">';
    
    // Previous link
    if ($current_page > 1) {
        $pagination .= '<a href="' . $base_url . '?page=' . ($current_page - 1) . '">&laquo; Previous</a>';
    }
    
    // Page numbers
    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        if ($i == $current_page) {
            $pagination .= '<span class="current">' . $i . '</span>';
        } else {
            $pagination .= '<a href="' . $base_url . '?page=' . $i . '">' . $i . '</a>';
        }
    }
    
    // Next link
    if ($current_page < $total_pages) {
        $pagination .= '<a href="' . $base_url . '?page=' . ($current_page + 1) . '">Next &raquo;</a>';
    }
    
    $pagination .= '</div>';
    return $pagination;
}

/**
 * Show alert messages
 */
function show_alert($message, $type = 'success') {
    return '<div class="alert alert-' . $type . '">' . htmlspecialchars($message) . '</div>';
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect with message
 */
function redirect_with_message($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header('Location: ' . $url);
    exit;
}

/**
 * Display and clear session messages
 */
function display_session_message() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'success';
        $message = $_SESSION['message'];
        unset($_SESSION['message'], $_SESSION['message_type']);
        return show_alert($message, $type);
    }
    return '';
}

/**
 * Get unique genres from database
 */
function get_genres($conn) {
    $result = $conn->query("SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL AND genre != '' ORDER BY genre");
    $genres = [];
    while ($row = $result->fetch_assoc()) {
        $genres[] = $row['genre'];
    }
    return $genres;
}

/**
 * Search books with filters
 */
function search_books($conn, $search = '', $genre = '', $min_price = '', $max_price = '', $sort = 'title', $page = 1, $per_page = 12) {
    $where_conditions = ["1=1"];
    $params = [];
    $types = "";
    
    if (!empty($search)) {
        $where_conditions[] = "(title LIKE ? OR author LIKE ? OR description LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
        $types .= "sss";
    }
    
    if (!empty($genre)) {
        $where_conditions[] = "genre = ?";
        $params[] = $genre;
        $types .= "s";
    }
    
    if (!empty($min_price)) {
        $where_conditions[] = "price >= ?";
        $params[] = $min_price;
        $types .= "d";
    }
    
    if (!empty($max_price)) {
        $where_conditions[] = "price <= ?";
        $params[] = $max_price;
        $types .= "d";
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Valid sort options
    $valid_sorts = ['title', 'author', 'price', 'price_desc'];
    if (!in_array($sort, $valid_sorts)) {
        $sort = 'title';
    }
    
    switch ($sort) {
        case 'price':
            $order_clause = "ORDER BY price ASC";
            break;
        case 'price_desc':
            $order_clause = "ORDER BY price DESC";
            break;
        case 'author':
            $order_clause = "ORDER BY author ASC";
            break;
        default:
            $order_clause = "ORDER BY title ASC";
    }
    
    // Count total results
    $count_sql = "SELECT COUNT(*) as total FROM books WHERE $where_clause";
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total_results = $count_stmt->get_result()->fetch_assoc()['total'];
    
    // Calculate pagination
    $total_pages = ceil($total_results / $per_page);
    $offset = ($page - 1) * $per_page;
    
    // Get books
    $sql = "SELECT * FROM books WHERE $where_clause $order_clause LIMIT $per_page OFFSET $offset";
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $books = [];
    while ($book = $result->fetch_assoc()) {
        $books[] = $book;
    }
    
    return [
        'books' => $books,
        'total_results' => $total_results,
        'total_pages' => $total_pages,
        'current_page' => $page
    ];
}

/**
 * Get book by ID with error handling
 */
function get_book_by_id($conn, $book_id) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

/**
 * Validate book data
 */
function validate_book_data($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors[] = "Title is required";
    }
    
    if (empty($data['author'])) {
        $errors[] = "Author is required";
    }
    
    if (empty($data['price']) || $data['price'] <= 0) {
        $errors[] = "Valid price is required";
    }
    
    if (empty($data['stock_quantity']) || $data['stock_quantity'] < 0) {
        $errors[] = "Valid stock quantity is required";
    }
    
    return $errors;
}
?>