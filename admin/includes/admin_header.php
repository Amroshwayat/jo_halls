<?php
if (!isset($currentPage)) {
    $currentPage = '';
}

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-crown"></i> Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="<?php echo $currentPage === 'venues' ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/admin/venues/">
                            <i class="fas fa-building"></i> Venues
                        </a>
                    </li>
                    <li class="<?php echo $currentPage === 'bookings' ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/admin/bookings/dashboard.php">
                            <i class="fas fa-calendar-alt"></i> Bookings
                        </a>
                    </li>
                    <li class="<?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/admin/users/">
                            <i class="fas fa-users"></i> Users
                        </a>
                    </li>
                 
                 
                    <li <?php echo strpos($currentPage, 'reviews') !== false ? 'class="active"' : ''; ?>>
                        <a href="<?php echo SITE_URL; ?>/admin/reviews/dashboard.php">
                            <i class="fas fa-star"></i> Reviews
                        </a>
                    </li>
                    <li <?php echo strpos($currentPage, 'invitations') !== false ? 'class="active"' : ''; ?>>
                <a href="<?php echo SITE_URL; ?>/admin/invitations/index.php">
                    <i class="fas fa-envelope"></i> Invitations
                </a>
            </li>
                    
                    <li class="<?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/admin/settings/dashboard.php">
                            <i class="fas fa-cogs"></i> Settings
                        </a>
                    </li>
                    <li class="<?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/index.php">
                            <i class="fas fa-arrow-left"></i> Back to main page
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="admin-user-menu">
                    <span class="user-name">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </span>
                    <a href="<?php echo SITE_URL; ?>/users/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </header>

            <div class="admin-content">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                        ?>
                    </div>
                <?php endif; ?>
