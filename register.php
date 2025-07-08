<?php
session_start();
require 'includes/db.php';
require 'includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (!validate_email($email)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email address is already registered";
        }
    }
    
    // If no errors, create account
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, 'customer')");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                redirect_with_message('login.php', 'Account created successfully! Please login to continue.', 'success');
            } else {
                $errors[] = "Failed to create account. Please try again.";
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
    <title>Register - BookNest</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container {
            max-width: 450px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .auth-header p {
            color: #666;
            margin: 0;
        }
        
        .auth-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }
        
        .auth-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .logo-link {
            display: block;
            text-align: center;
            margin-bottom: 30px;
            color: #667eea;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
        }
        
        .password-requirements {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .password-requirements ul {
            margin: 5px 0 0 20px;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <a href="index.php" class="back-home btn btn-secondary">← Back to Home</a>
    
    <div class="auth-container">
        <a href="index.php" class="logo-link">📚 BookNest</a>
        
        <div class="auth-header">
            <h2>Join BookNest</h2>
            <p>Create your account to start exploring books</p>
        </div>
        
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
        
        <form method="POST" id="registerForm">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                       placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Create a password" minlength="6">
                <div class="password-requirements">
                    <strong>Password Requirements:</strong>
                    <ul>
                        <li>At least 6 characters long</li>
                        <li>Mix of letters and numbers recommended</li>
                    </ul>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Confirm your password">
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; font-weight: normal;">
                    <input type="checkbox" id="terms" required style="margin-right: 10px; width: auto;">
                    I agree to the <a href="#" style="color: #667eea;">Terms of Service</a> and 
                    <a href="#" style="color: #667eea;">Privacy Policy</a>
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Create Account
                </button>
            </div>
        </form>
        
        <div class="auth-links">
            <p>Already have an account? <a href="login.php">Sign In</a></p>
            <p><a href="books.php">Browse Books as Guest</a></p>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            // Basic validation
            if (!name || !email || !password || !confirmPassword) {
                alert('Please fill in all fields');
                e.preventDefault();
                return;
            }
            
            if (name.length < 2) {
                alert('Name must be at least 2 characters long');
                e.preventDefault();
                return;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                e.preventDefault();
                return;
            }
            
            if (password !== confirmPassword) {
                alert('Passwords do not match');
                e.preventDefault();
                return;
            }
            
            if (!terms) {
                alert('Please accept the Terms of Service and Privacy Policy');
                e.preventDefault();
                return;
            }
        });
        
        // Real-time password confirmation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Focus on first field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('name').focus();
        });
        
        // Auto-refresh
        setTimeout(function() {
            location.reload();
        }, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>
