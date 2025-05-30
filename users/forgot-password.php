<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
$pageTitle = "Forgot Password";

$error = '';
$success = '';
$step = 1;
$email = '';
$showResetForm = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == 1) {
        // Step 1: Check email
        $email = trim($_POST['email']);
        if (!$email) {
            $error = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            global $db;
            $stmt = $db->getConnection()->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                $error = 'Email address not found.';
            } else {
                $showResetForm = true;
                $step = 2;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['step']) && $_POST['step'] == 2) {
        // Step 2: Reset password
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        if (!$password || !$confirm) {
            $error = 'Please enter and confirm your new password.';
            $showResetForm = true;
            $step = 2;
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
            $showResetForm = true;
            $step = 2;
        } elseif (!validatePassword($password)) {
            $error = 'Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.';
            $showResetForm = true;
            $step = 2;
        } else {
            global $db;
            $stmt = $db->getConnection()->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                $error = 'Email address not found.';
                $stmt->close();
            } else {
                $stmt->close();
                $hashed = hashPassword($password);
                // Create a new statement for the update
                $stmt2 = $db->getConnection()->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt2->bind_param("ss", $hashed, $email);
                if ($stmt2->execute()) {
                    $success = 'Your password has been reset. You can now <a href="login.php">login</a>.';
                    $step = 3;
                } else {
                    $error = 'Could not reset password. Please try again.';
                    $showResetForm = true;
                    $step = 2;
                }
                $stmt2->close();
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
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="auth-container">
        <div class="auth-box">
            <h2>Forgot Password</h2>
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 1.5rem;">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 1 && !$success): ?>
                <form method="post" class="forgot-form" autocomplete="off">
                    <input type="hidden" name="step" value="1">
                    <div class="form-group">
                        <label for="email">Enter your email address</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Continue
                    </button>
                </form>
            <?php elseif ($step === 2 && !$success): ?>
                <form method="post" class="reset-form" autocomplete="off">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" required autocomplete="new-password">
                        <small class="form-text text-muted">
                            Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Reset Password
                    </button>
                </form>
            <?php endif; ?>

            <a href="login.php" class="btn-submit" style="display:inline-block;text-align:center;">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
