<?php
// Owner Dashboard Header - similar to admin_header.php but for hall owners
if (!isset($currentPage)) $currentPage = '';
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';
if (!isLoggedIn() || !isHallOwner()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}
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
    <style>
        body { background: #f5f6fa; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 220px; background: #222; color: #fff; min-height: 100vh; padding-top: 30px;
        }
        .sidebar-brand { font-size: 1.3rem; text-align: center; margin-bottom: 2rem; font-weight: bold; }
        .sidebar-menu { list-style: none; padding: 0; }
        .sidebar-menu li { margin: 0; }
        .sidebar-menu li a {
            display: block; padding: 12px 24px; color: #fff; text-decoration: none;
            border-left: 4px solid transparent; transition: background 0.2s, border 0.2s;
        }
        .sidebar-menu li.active a, .sidebar-menu li a:hover {
            background: #444; border-left: 4px solid #00b894;
        }
        .admin-content {
            flex: 1; padding: 40px 40px 20px 40px;
        }
        .content-header h1 { font-size: 2rem; margin-bottom: 1.5rem; }
        .actions { margin-bottom: 1.5rem; }
        .btn { display: inline-block; padding: 8px 18px; border-radius: 4px; background: #00b894; color: #fff; text-decoration: none; font-weight: bold; transition: background 0.2s; }
        .btn-secondary { background: #636e72; }
        .btn:hover { background: #019875; }
        @media (max-width: 800px) {
            .admin-wrapper { flex-direction: column; }
            .admin-sidebar { width: 100%; min-height: auto; }
            .admin-content { padding: 20px; }
        }
    </style>
    <style>
.admin-header {
    background: #2c3e50;
    color: #fff;
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header-brand a {
    color: #fff;
    font-size: 1.5rem;
    text-decoration: none;
    font-weight: bold;
}

.admin-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    gap: 1rem;
}

.admin-nav a {
    color: #ecf0f1;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background-color 0.3s;
}

.admin-nav a:hover {
    background: #34495e;
}

.admin-nav li.active a {
    background: #3498db;
}

.user-menu {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-info {
    text-align: right;
}

.user-info span {
    display: block;
    font-weight: bold;
}

.user-info small {
    color: #bdc3c7;
}

.dropdown {
    position: relative;
}

.dropdown-toggle {
    background: none;
    border: none;
    color: #fff;
    cursor: pointer;
    font-size: 1.5rem;
}

.dropdown-menu {
    position: absolute;
    right: 0;
    top: 100%;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: none;
    min-width: 200px;
    z-index: 1000;
}

.dropdown:hover .dropdown-menu {
    display: block;
}

.dropdown-menu a {
    color: #2c3e50;
    padding: 0.75rem 1rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dropdown-menu a:hover {
    background: #f8f9fa;
}

@media (max-width: 768px) {
    .admin-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .admin-nav ul {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .user-menu {
        width: 100%;
        justify-content: center;
    }
}
</style>
</head>
<body>
<div class="admin-wrapper">
    <?php 
    $currentPath = $_SERVER['PHP_SELF'] ?? '';
    $isProfilePage = strpos($currentPath, '/users/profile.php') !== false || strpos($currentPath, 'profile.php') !== false;
    ?>
    <?php if (!$isProfilePage): ?>
    <nav class="admin-sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-crown"></i> Owner Panel
        </div>
        <ul class="sidebar-menu">
            <li class="<?= $currentPage == 'dashboard' ? 'active' : '' ?>">
                <a href="<?= SITE_URL ?>/halls/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </li>
            <li class="<?= $currentPage == 'manage' ? 'active' : '' ?>">
                <a href="<?= SITE_URL ?>/halls/owner_venue/index.php"><i class="fas fa-home"></i> Manage Halls</a>
            </li>
            <li class="<?= $currentPage == 'bookings' ? 'active' : '' ?>">
                <a href="<?= SITE_URL ?>/halls/bookings.php"><i class="fas fa-calendar-alt"></i> Reservations</a>
            </li>
            <li class="<?= $currentPage == 'availability' ? 'active' : '' ?>">
                <a href="<?= SITE_URL ?>/halls/availability.php"><i class="fas fa-calendar"></i> Availability</a>
                
            </li>
            <li class="<?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                        <a href="<?php echo SITE_URL; ?>/index.php">
                            <i class="fas fa-arrow-left"></i> Back to main page
                        </a>
                    </li>
            <li>
                <a href="<?= SITE_URL ?>/users/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
    <div class="admin-content">
