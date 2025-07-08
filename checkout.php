<?php
session_start();
require 'includes/db.php';
require 'includes/functions.php';

if (!is_logged_in()) {
    redirect_with_message('login.php', 'Please login to checkout', 'warning');
}

if (empty($_SESSION['cart'])) {
    redirect_with_message('cart.php', 'Your cart is empty', 'warning');
}

$errors = [];
$order_placed = false;
$order_id = null;

// Calculate total and validate cart
$total = 0;
$cart_items = [];
foreach ($_SESSION['cart'] as $book_id => $quantity) {
    $book = get_book_by_id($conn, $book_id);
    if ($book) {
        if ($book['stock_quantity'] >= $quantity) {
            $cart_items[] = [
                'book' => $book,
                'quantity' => $quantity,
                'subtotal' => $book['price'] * $quantity
            ];
            $total += $book['price'] * $quantity;
        } else {
            $errors[] = "Insufficient stock for: " . $book['title'];
        }
    } else {
        $errors[] = "Some items in your cart are no longer available";
    }
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($errors)) {
    $shipping_address = sanitize_input($_POST['shipping_address']);
    $phone = sanitize_input($_POST['phone']);
    $payment_method = sanitize_input($_POST['payment_method']);
    
    // Validation
    if (empty($shipping_address)) {
        $errors[] = "Shipping address is required";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    if (empty($payment_method)) {
        $errors[] = "Payment method is required";
    }
    
    // If no errors, process the order
    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            
            $user_id = $_SESSION['user_id'];
            
            // Insert order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, phone, payment_method) VALUES (?, ?, 'Pending', ?, ?, ?)");
            $stmt->bind_param("idsss", $user_id, $total, $shipping_address, $phone, $payment_method);
            $stmt->execute();
            $order_id = $conn->insert_id;
            
            // Insert order details and update stock
            foreach ($cart_items as $item) {
                $book = $item['book'];
                $quantity = $item['quantity'];
                $subtotal = $item['subtotal'];
                
                // Insert order detail
                $stmt = $conn->prepare("INSERT INTO order_details (order_id, book_id, quantity, subtotal) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $order_id, $book['book_id'], $quantity, $subtotal);
                $stmt->execute();
                
                // Update stock
                $stmt = $conn->prepare("UPDATE books SET stock_quantity = stock_quantity - ? WHERE book_id = ?");
                $stmt->bind_param("ii", $quantity, $book['book_id']);
                $stmt->execute();
            }
            
            $conn->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            $order_placed = true;
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to process order: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $order_placed ? 'Order Confirmed' : 'Checkout'; ?> - BookNest</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-top: 20px;
        }
        
        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .order-summary {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }
        
        .payment-option {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-option:hover {
            border-color: #667eea;
        }
        
        .payment-option.selected {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .payment-option input[type="radio"] {
            display: none;
        }
        
        .success-animation {
            text-align: center;
            padding: 60px 20px;
        }
        
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
            animation: bounce 1s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            
            .payment-methods {
                grid-template-columns: 1fr;
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
        <?php if ($order_placed): ?>
            <!-- Order Success -->
            <div class="form-container">
                <div class="success-animation">
                    <div class="success-icon">✅</div>
                    <h2>Order Confirmed!</h2>
                    <p>Your order #<?php echo $order_id; ?> has been placed successfully.</p>
                    <p>Thank you for shopping with BookNest!</p>
                    
                    <div style="margin: 30px 0;">
                        <h3>What's next?</h3>
                        <ul style="text-align: left; max-width: 400px; margin: 0 auto;">
                            <li>You will receive an email confirmation shortly</li>
                            <li>We'll process your order within 1-2 business days</li>
                            <li>Your books will be shipped to the provided address</li>
                            <li>Track your order status in your account</li>
                        </ul>
                    </div>
                    
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <a href="books.php" class="btn btn-primary">Continue Shopping</a>
                        <a href="index.php" class="btn btn-secondary">Back to Home</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Checkout Form -->
            <?php echo display_session_message(); ?>
            
            <h2>🛒 Checkout</h2>
            
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
            
            <div class="checkout-container">
                <!-- Checkout Form -->
                <div class="checkout-form">
                    <h3>Shipping Information</h3>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="shipping_address">Shipping Address *</label>
                            <textarea id="shipping_address" name="shipping_address" rows="4" required 
                                      placeholder="Enter your complete shipping address"><?php echo isset($_POST['shipping_address']) ? htmlspecialchars($_POST['shipping_address']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                   placeholder="Enter your phone number">
                        </div>
                        
                        <h3 style="margin-top: 30px;">Payment Method</h3>
                        
                        <div class="payment-methods">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="cod" required>
                                <div>
                                    <div style="font-size: 1.5rem; margin-bottom: 5px;">💵</div>
                                    <strong>Cash on Delivery</strong>
                                    <br><small>Pay when you receive</small>
                                </div>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="online" required>
                                <div>
                                    <div style="font-size: 1.5rem; margin-bottom: 5px;">💳</div>
                                    <strong>Online Payment</strong>
                                    <br><small>Pay now (Coming Soon)</small>
                                </div>
                            </label>
                        </div>
                        
                        <div class="form-group" style="margin-top: 30px;">
                            <button type="submit" class="btn btn-success" style="width: 100%; padding: 15px;">
                                Place Order (<?php echo format_price($total); ?>)
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    
                    <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <div>
                                <strong><?php echo htmlspecialchars($item['book']['title']); ?></strong>
                                <br>
                                <small>Qty: <?php echo $item['quantity']; ?> × <?php echo format_price($item['book']['price']); ?></small>
                            </div>
                            <div>
                                <strong><?php echo format_price($item['subtotal']); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="margin: 20px 0; padding-top: 20px; border-top: 2px solid #eee;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Subtotal:</span>
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
                        <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold; padding-top: 10px; border-top: 1px solid #ddd;">
                            <span>Total:</span>
                            <span><?php echo format_price($total); ?></span>
                        </div>
                    </div>
                    
                    <a href="cart.php" class="btn btn-secondary" style="width: 100%;">
                        ← Back to Cart
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Payment method selection
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Check the radio button
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
        
        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const address = document.getElementById('shipping_address').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            
            if (!address || !phone || !paymentMethod) {
                alert('Please fill in all required fields');
                e.preventDefault();
                return;
            }
            
            if (address.length < 10) {
                alert('Please provide a complete shipping address');
                e.preventDefault();
                return;
            }
        });
        
        // Auto-refresh
        setTimeout(function() {
            location.reload();
        }, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>
