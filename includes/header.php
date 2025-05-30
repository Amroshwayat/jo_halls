<?php
// تأكد من تضمين functions.php لتعريف الدوال مثل isLoggedIn()
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/functions.php';
}
$current_page = basename($_SERVER['PHP_SELF']);
$base_url = SITE_URL;
$currentPath = $_SERVER['PHP_SELF'] ?? '';
$isHomePage = strpos($currentPath, '/index.php') !== false || $currentPath === '/';
?>
<head>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<header class="main-header">
    <div class="header-content">
        <div class="logo">
            <a href="<?php echo $base_url; ?>/index.php"><?php echo SITE_NAME; ?></a>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="<?php echo $base_url; ?>/index.php" <?php echo $current_page == 'index.php' ? 'class="active"' : ''; ?>>Home</a></li>
                <li><a href="<?php echo $base_url; ?>/halls/search.php" <?php echo $current_page == 'search.php' ? 'class="active"' : ''; ?>>Search Venues</a></li>
                <li><a href="<?php echo $base_url; ?>/map/map.php">Map</a></li>
                <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                    <?php if (function_exists('isAdmin') && isAdmin()): ?>
                        <li><a href="<?php echo $base_url; ?>/admin/dashboard.php">Admin Dashboard</a></li>
                    <?php endif; ?>
                    <?php if (function_exists('isHallOwner') && isHallOwner()): ?>
                        <li><a href="<?= SITE_URL ?>/halls/dashboard.php"><i class="fas fa-crown"></i> Owner Dashboard</a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer'): ?>
                        <li><a href="<?= SITE_URL ?>/customer/index.php"><i class="fas fa-user "></i> Customer Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $base_url; ?>/users/profile.php">My Profile</a></li>
                    
                    <li><a href="<?php echo $base_url; ?>/users/logout.php">Logout</a></li>
                <?php else: ?>
                    
                    <li><a href="<?php echo $base_url; ?>/users/login.php">Login</a></li>
                    <li><a href="<?php echo $base_url; ?>/users/register.php" class="btn-primary">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

