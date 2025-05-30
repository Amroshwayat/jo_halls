<?php
if (!isLoggedIn() || !isAdmin()) {
    header('Location: /login.php');
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<header class="admin-header">
    <div class="header-brand">
        <a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Jo Halls Admin</a>
    </div>
    
    <nav class="admin-nav">
        <ul>
            <li <?php echo strpos($currentPage, 'dashboard') !== false ? 'class="active"' : ''; ?>>
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li <?php echo strpos($currentPage, 'halls') !== false ? 'class="active"' : ''; ?>>
                <a href="<?php echo SITE_URL; ?>/admin/halls/dashboard.php">
                    <i class="fas fa-building"></i> Halls
                </a>
            </li>
            <li <?php echo strpos($currentPage, 'bookings') !== false ? 'class="active"' : ''; ?>>
                <a href="<?php echo SITE_URL; ?>/admin/bookings/dashboard.php">
                    <i class="fas fa-calendar-alt"></i> Bookings
                </a>
            </li>
            <li <?php echo strpos($currentPage, 'users') !== false ? 'class="active"' : ''; ?>>
                <a href="<?php echo SITE_URL; ?>/admin/users/dashboard.php">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <li <?php echo strpos($currentPage, 'reviews') !== false ? 'class="active"' : ''; ?>>
                <a href="<?php echo SITE_URL; ?>/admin/reviews/dashboard.php">
                    <i class="fas fa-star"></i> Reviews
                </a>
            </li>
            <li <?php echo strpos($currentPage, 'blog') !== false ? 'class="active"' : ''; ?>>
                <a href="<?php echo SITE_URL; ?>/admin/blog/dashboard.php">
                    <i class="fas fa-blog"></i> Blog
                </a>
            </li>
            <li <?php echo strpos($currentPage, 'invitations') !== false ? 'class="active"' : ''; ?>>
                <a href="<?php echo SITE_URL; ?>/admin/invitations/dashboard.php">
                    <i class="fas fa-envelope"></i> Invitations
                </a>
            </li>
            <li <?php echo strpos($currentPage, 'settings') !== false ? 'class="active"' : ''; ?>>
                <a href="<?php echo SITE_URL; ?>/admin/settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="user-menu">
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <small>Administrator</small>
        </div>
        <div class="dropdown">
            <button class="dropdown-toggle">
                <i class="fas fa-user-circle"></i>
            </button>
            <div class="dropdown-menu">
                <a href="<?php echo SITE_URL; ?>/admin/profile.php">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="<?php echo SITE_URL; ?>/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click handler for mobile menu toggle if needed
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    if (dropdownToggle && dropdownMenu) {
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.style.display = 'none';
            }
        });
    }
});
</script>
