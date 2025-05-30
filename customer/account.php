<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'customer') {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$pageTitle = 'My Account';

// جلب بيانات المستخدم
$stmt = $db->getConnection()->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($first_name, $last_name, $email, $phone);
$stmt->fetch();
$stmt->close();

// تعديل البيانات
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    if (!$first_name || !$last_name || !$email) {
        $error = 'First name, last name, and email are required.';
    } else {
        $stmt = $db->getConnection()->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param('ssssi', $first_name, $last_name, $email, $phone, $user_id);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = 'Could not update account. Please try again.';
        }
        $stmt->close();
    }
}
require_once '../includes/header.php';
?>
<div class="container mt-4" style="max-width: 520px; margin: 40px auto 0 auto; background: #fff; border-radius: 14px; box-shadow: 0 6px 32px rgba(0,0,0,0.07); padding: 2.2rem 2rem 2rem 2rem;">
    <h2 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.35rem; color: #ff4b7d; font-weight: bold;">My Account</h2>
    <?php if ($success): ?>
        <div class="alert alert-success" style="font-size: 1rem; border-radius: 7px; margin-bottom: 1.2rem; text-align: center;">Account updated successfully.</div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger" style="font-size: 1rem; border-radius: 7px; margin-bottom: 1.2rem; text-align: center;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group" style="margin-bottom: 1.1rem;">
            <label for="first_name" style="font-weight: 500; color: #333; margin-bottom: 0.35rem;">First Name</label>
            <input type="text" name="first_name" id="first_name" class="form-control" value="<?= htmlspecialchars($first_name) ?>" required style="width: 100%; border-radius: 7px; border: 1.5px solid #e1e1e1; padding: 0.55rem 0.85rem; font-size: 1rem; margin-bottom: 0.2rem; transition: border 0.18s;">
        </div>
        <div class="form-group" style="margin-bottom: 1.1rem;">
            <label for="last_name" style="font-weight: 500; color: #333; margin-bottom: 0.35rem;">Last Name</label>
            <input type="text" name="last_name" id="last_name" class="form-control" value="<?= htmlspecialchars($last_name) ?>" required style="width: 100%; border-radius: 7px; border: 1.5px solid #e1e1e1; padding: 0.55rem 0.85rem; font-size: 1rem; margin-bottom: 0.2rem; transition: border 0.18s;">
        </div>
        <div class="form-group" style="margin-bottom: 1.1rem;">
            <label for="email" style="font-weight: 500; color: #333; margin-bottom: 0.35rem;">Email</label>
            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required style="width: 100%; border-radius: 7px; border: 1.5px solid #e1e1e1; padding: 0.55rem 0.85rem; font-size: 1rem; margin-bottom: 0.2rem; transition: border 0.18s;">
        </div>
        <div class="form-group" style="margin-bottom: 1.1rem;">
            <label for="phone" style="font-weight: 500; color: #333; margin-bottom: 0.35rem;">Phone</label>
            <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>" style="width: 100%; border-radius: 7px; border: 1.5px solid #e1e1e1; padding: 0.55rem 0.85rem; font-size: 1rem; margin-bottom: 0.2rem; transition: border 0.18s;">
        </div>
        <button type="submit" class="btn btn-primary" style="border-radius: 7px; font-weight: 600; font-size: 1rem; padding: 0.55rem 1.2rem; margin-top: 0.7rem; background: #ff4b7d; color: #fff; border: none; box-shadow: 0 2px 8px rgba(255,31,90,0.07); transition: background 0.14s, color 0.14s, transform 0.13s;">Save Changes</button>
    </form>
</div>
