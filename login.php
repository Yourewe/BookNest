<?php
session_start();
require 'includes/db.php';
require 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];

    if (!validate_email($email)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows == 1) {
            $user = $res->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['name'] = $user['name'];
                
                // Redirect based on user type
                if ($user['user_type'] === 'admin') {
                    redirect_with_message('admin/dashboard.php', 'Welcome back, Admin!', 'success');
                } else {
                    redirect_with_message('index.php', 'Welcome back!', 'success');
                }
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BookNest</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container {
            max-width: 400px;
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
    </style>
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <a href="index.php" class="back-home btn btn-secondary">← Back to Home</a>
    
    <div class="auth-container">
        <a href="index.php" class="logo-link">📚 BookNest</a>
        
        <div class="auth-header">
            <h2>Welcome Back!</h2>
            <p>Sign in to your account to continue</p>
        </div>
        
        <!-- Display errors -->
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Display session messages -->
        <?php echo display_session_message(); ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password">
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Sign In
                </button>
            </div>
        </form>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="register.php">Create Account</a></p>
            <p><a href="books.php">Browse Books as Guest</a></p>
        </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                alert('Please fill in all fields');
                e.preventDefault();
                return;
            }
            
            if (password.length < 6) {
                alert('Password must be at least 6 characters long');
                e.preventDefault();
                return;
            }
        });
        
        // Focus on first empty field
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            
            if (!emailField.value) {
                emailField.focus();
            } else {
                passwordField.focus();
            }
        });
        
        // Auto-refresh the page every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>
