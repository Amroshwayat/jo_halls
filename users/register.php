<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $phone = sanitizeInput($_POST['phone']);
    $role = sanitizeInput($_POST['role']);
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword) || 
        empty($firstName) || empty($lastName) || empty($phone)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (!validatePassword($password)) {
        $error = 'Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if email or username already exists
       global $db;

$stmt = $db->getConnection()->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
$stmt->execute([$email, $username]);

if ($stmt->fetch()) {
    $error = 'Email or username already exists';
} else {
    if (registerUser($username, $email, $password, $role, $firstName, $lastName, $phone)) {
        $success = 'Registration successful! You can now login.';
    } else {
        $error = 'Registration failed. Please try again.';
    }
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="auth-container">
        <div class="auth-box">
            <h2>Create Your Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="register-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required
                           placeholder="Choose a username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required
                               placeholder="Your first name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required
                               placeholder="Your last name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required
                           placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required
                           placeholder="Your phone number" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="role">I am a...</label>
                    <select id="role" name="role" required class="form-select">
                        <option value="">Select your role</option>
                        <option value="customer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'customer') ? 'selected' : ''; ?>>customer</option>
                        <option value="hall_owner" <?php echo (isset($_POST['role']) && $_POST['role'] === 'hall_owner') ? 'selected' : ''; ?>>Hall Owner</option>
                    </select>
                </div>

                <div class="form-row">
                <div class="form-group">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required placeholder="Create a password">
 
</div>

                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               placeholder="Confirm your password">
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>


            <div class="auth-links">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

 
</body>
</html>
