<?php
session_start();
require 'includes/db.php';
require 'includes/functions.php';

if (!is_logged_in()) {
    redirect_with_message('login.php', 'Please login to access your cart', 'warning');
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add to cart
if (isset($_POST['add_to_cart'])) {
    $book_id = intval($_POST['book_id']);
    $quantity = max(1, intval($_POST['quantity']));

    // Check if book exists and has enough stock
    $book = get_book_by_id($conn, $book_id);
    if ($book && $book['stock_quantity'] >= $quantity) {
        if (isset($_SESSION['cart'][$book_id])) {
            $new_quantity = $_SESSION['cart'][$book_id] + $quantity;
            if ($new_quantity <= $book['stock_quantity']) {
                $_SESSION['cart'][$book_id] = $new_quantity;
                redirect_with_message('cart.php', 'Item quantity updated in cart!', 'success');
            } else {
                redirect_with_message('cart.php', 'Not enough stock available', 'error');
            }
        } else {
            $_SESSION['cart'][$book_id] = $quantity;
            redirect_with_message('cart.php', 'Item added to cart!', 'success');
        }
    } else {
        redirect_with_message('cart.php', 'Book not available or insufficient stock', 'error');
    }
}

// Update quantity
if (isset($_POST['update_quantity'])) {
    $book_id = intval($_POST['book_id']);
    $quantity = max(1, intval($_POST['quantity']));
    
    $book = get_book_by_id($conn, $book_id);
    if ($book && $quantity <= $book['stock_quantity']) {
        $_SESSION['cart'][$book_id] = $quantity;
        redirect_with_message('cart.php', 'Cart updated!', 'success');
    } else {
        redirect_with_message('cart.php', 'Invalid quantity or insufficient stock', 'error');
    }
}

// Remove item
if (isset($_GET['remove'])) {
    $book_id = intval($_GET['remove']);
    if (isset($_SESSION['cart'][$book_id])) {
        unset($_SESSION['cart'][$book_id]);
        redirect_with_message('cart.php', 'Item removed from cart', 'success');
    }
}

// Clear cart
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    redirect_with_message('cart.php', 'Cart cleared', 'success');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - BookNest</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .cart-item {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 20px;
            align-items: center;
        }
        
        .cart-item-image {
            width: 80px;
            height: 120px;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .cart-item-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .cart-item-details h3 {
            margin-bottom: 5px;
            color: #333;
        }
        
        .cart-item-details p {
            margin: 5px 0;
            color: #666;
        }
        
        .cart-item-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
        }
        
        .quantity-controls input {
            width: 60px;
            text-align: center;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        .cart-summary {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
            position: sticky;
            top: 20px;
        }
        
        .cart-total {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .empty-cart-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .cart-item-actions {
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">📚 BookNest</div>
            <nav class="nav">
                <a href="index.php">Home</a>
                <a href="books.php">Books</a>
                <a href="cart.php">Cart</a>
                <?php if (is_admin()): ?>
                    <a href="admin/dashboard.php">Admin</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Display session messages -->
        <?php echo display_session_message(); ?>

        <h2>🛒 Your Shopping Cart</h2>

        <?php
        $total = 0;
        $cart_count = 0;
        
        if (!empty($_SESSION['cart'])) {
            $cart_items = [];
            foreach ($_SESSION['cart'] as $book_id => $quantity) {
                $book = get_book_by_id($conn, $book_id);
                if ($book) {
                    $cart_items[] = [
                        'book' => $book,
                        'quantity' => $quantity,
                        'subtotal' => $book['price'] * $quantity
                    ];
                    $total += $book['price'] * $quantity;
                    $cart_count += $quantity;
                }
            }

            if (!empty($cart_items)) {
            ?>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 20px;">
                    <!-- Cart Items -->
                    <div>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <div class="cart-item-image">
                                    <?php if (!empty($item['book']['cover_image']) && file_exists($item['book']['cover_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['book']['cover_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['book']['title']); ?>">
                                    <?php else: ?>
                                        📚
                                    <?php endif; ?>
                                </div>
                                
                                <div class="cart-item-details">
                                    <h3><?php echo htmlspecialchars($item['book']['title']); ?></h3>
                                    <p><strong>Author:</strong> <?php echo htmlspecialchars($item['book']['author']); ?></p>
                                    <p><strong>Price:</strong> <?php echo format_price($item['book']['price']); ?></p>
                                    <p><strong>Stock Available:</strong> <?php echo $item['book']['stock_quantity']; ?></p>
                                    
                                    <form method="POST" class="quantity-controls">
                                        <input type="hidden" name="book_id" value="<?php echo $item['book']['book_id']; ?>">
                                        <label>Quantity:</label>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['book']['stock_quantity']; ?>">
                                        <button type="submit" name="update_quantity" class="btn btn-secondary btn-sm">Update</button>
                                    </form>
                                </div>
                                
                                <div class="cart-item-actions">
                                    <div class="cart-total" style="font-size: 1.2rem; margin-bottom: 10px;">
                                        <?php echo format_price($item['subtotal']); ?>
                                    </div>
                                    <a href="cart.php?remove=<?php echo $item['book']['book_id']; ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Remove this item from cart?');">
                                        Remove
                                    </a>
                                    <a href="book_details.php?id=<?php echo $item['book']['book_id']; ?>" 
                                       class="btn btn-secondary btn-sm">
                                        View Book
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="margin-top: 20px; text-align: center;">
                            <a href="cart.php?clear=1" class="btn btn-warning"
                               onclick="return confirm('Clear entire cart?');">
                                Clear Cart
                            </a>
                        </div>
                    </div>
                    
                    <!-- Cart Summary -->
                    <div class="cart-summary">
                        <h3>Order Summary</h3>
                        
                        <div style="margin: 20px 0;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Items (<?php echo $cart_count; ?>):</span>
                                <span><?php echo format_price($total); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Shipping:</span>
                                <span>Free</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span>Tax:</span>
                                <span>Included</span>
                            </div>
                        </div>
                        
                        <div class="cart-total">
                            Total: <?php echo format_price($total); ?>
                        </div>
                        
                        <a href="checkout.php" class="btn btn-success" style="width: 100%; margin-bottom: 10px;">
                            Proceed to Checkout
                        </a>
                        <a href="books.php" class="btn btn-secondary" style="width: 100%;">
                            Continue Shopping
                        </a>
                    </div>
                </div>
            <?php
            } else {
            ?>
                <div class="empty-cart">
                    <div class="empty-cart-icon">🛒</div>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added any books to your cart yet.</p>
                    <a href="books.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php
            }
        } else {
        ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">🛒</div>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added any books to your cart yet.</p>
                <a href="books.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php
        }
        ?>
    </div>

<script>
    // Auto-submit quantity forms on change (optional)
    document.querySelectorAll('.quantity-controls input[name="quantity"]').forEach(function(input) {
        input.addEventListener('change', function() {
            // Uncomment the line below to auto-submit on quantity change
            // this.closest('form').submit();
        });
    });

    // Prevent quantity from being 0
    document.querySelectorAll('input[type="number"]').forEach(function(input) {
        input.addEventListener('input', function() {
            if (parseInt(this.value) < 1 || isNaN(parseInt(this.value))) {
                this.value = 1;
            }
        });
    });

    // Auto-refresh cart page every 30 seconds
    setTimeout(function() {
        location.reload();
    }, 30000); // Refresh every 30 seconds
</script>
</body>
</html>
