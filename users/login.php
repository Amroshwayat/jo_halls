<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        if (loginUser($email, $password)) {
            $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : SITE_URL . '/index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit();
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="auth-container">
        <div class="auth-box">
            <h2>Welcome Back!</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Enter your password">
                </div>
                
                <div class="form-group" style="text-align: right;">
                    <a href="forgot-password.php" class="auth-links">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="auth-links">
                Don't have an account? <a href="register.php">Sign up now</a>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
