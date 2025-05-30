<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'customer') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}
$pageTitle = 'Customer Dashboard';
require_once '../includes/header.php';
?>
<style>
/* Dashboard background and card styles */
.customer-dashboard {
    background: linear-gradient(135deg, var(--light-color) 0%, #f5f7fa 100%);
    min-height: 100vh;
    padding-bottom: 40px;
}

/* Enhanced Welcome Card */
.welcome-card {
    max-width: 700px;
    margin: 0 auto 2.5rem auto;
    background: #666;
    border-radius: 22px;
    box-shadow: 0 8px 32px rgba(255,75,125,0.13);
    padding: 2.2rem 2.5rem 2rem 2.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    position: relative;
    overflow: hidden;
    color: #fff;
    animation: fadeIn 0.7s;
}
.welcome-card .welcome-icon {
    font-size: 3.2rem;
    background: rgba(255,255,255,0.13);
    border-radius: 50%;
    padding: 1.1rem 1.2rem;
    box-shadow: 0 2px 12px rgba(255,75,125,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
}
.welcome-cute-img {
    width: 44px;
    height: 44px;
    object-fit: contain;
    margin-left: 0.7rem;
    margin-right: 0.2rem;
    margin-top: 0.1rem;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(255,75,125,0.08);
    background: #fff;
    display: inline-block;
    vertical-align: middle;
}
.welcome-card .welcome-text {
    flex: 1;
}
.welcome-card .welcome-title {
    font-size: 2.1rem;
    font-weight: 800;
    letter-spacing: -1px;
    margin-bottom: 0.3rem;
    color: #fff;
    text-shadow: 0 2px 12px rgba(255,75,125,0.13);
}
.welcome-card .welcome-desc {
    font-size: 1.15rem;
    color: #ffe5ef;
    opacity: 0.93;
    margin-bottom: 0;
    font-weight: 400;
}
@media (max-width: 800px) {
    .welcome-card {
        flex-direction: column;
        padding: 1.5rem 1.2rem;
        text-align: center;
        gap: 1rem;
    }
    .welcome-card .welcome-icon {
        margin-bottom: 0.5rem;
    }
    .welcome-cute-img {
        margin: 0 auto 0.5rem auto;
        display: block;
    }
}
.dashboard-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 2.2rem;
    justify-content: center;
    margin-top: 2.5rem;
}
.dashboard-card {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 6px 24px rgba(255,75,125,0.09);
    padding: 1.2rem 1rem 1rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    transition: box-shadow 0.2s, transform 0.2s;
    min-height: 185px;
    width: 220px;
    text-align: center;
    border: 1.5px solid #ffe5ef;
}
.dashboard-card:hover {
    box-shadow: 0 12px 32px rgba(255,31,90,0.13);
    transform: translateY(-6px) scale(1.06);
    border-color: #ffb3d6;
}
.dashboard-icon {
    font-size: 2.3rem !important;
    margin-bottom: 0.7rem;
    color: var(--primary-color);
    background: none !important;
    border-radius: 0;
    padding: 0;
    box-shadow: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.dashboard-title-row {
    display: flex;
    gap: 1.2rem;
    justify-content: center;
    margin-bottom: 1.2rem;
    flex-wrap: wrap;
}
.dashboard-title {
    font-size: 1.08rem;
    font-weight: bold;
    color: #fff;
    background: linear-gradient(90deg, var(--primary-color) 0%,rgb(139, 126, 255) 100%);
    padding: 0.35rem 1.1rem;
    border-radius: 22px;
    box-shadow: 0 2px 8px rgba(255,75,125,0.09);
    margin-bottom: 0.5rem;
    letter-spacing: 0.02em;
    transition: background 0.18s, color 0.18s, transform 0.12s;
    display: inline-block;
}
.dashboard-card:hover .dashboard-title {
    background: linear-gradient(90deg, #ff7eb3 0%, var(--primary-color) 100%);
    color: #fff;
    transform: scale(1.07);
}
.dashboard-text {
    color: #555;
    font-size: 0.93rem;
    margin-bottom: 1rem;
    flex: 1;
}
/* Button color tweaks for modern, soft look */
.dashboard-card .btn {
    margin-top: 0.2rem;
    font-size: 0.93rem;
    border-radius: 6px;
    padding: 0.45rem 1.1rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(255,75,125,0.07);
    letter-spacing: 0.02em;
    transition: background 0.15s, color 0.15s, transform 0.12s;
    border: none;
}
.dashboard-card .btn-primary {
    background: var(--primary-color);
    color: #fff;
}
.dashboard-card .btn-primary:hover {
    background: #ff1f5a;
    color: #fff;
}
.dashboard-card .btn-info {
    background: #38bdf8;
    color: #fff;
}
.dashboard-card .btn-info:hover {
    background: #0ea5e9;
    color: #fff;
}
.dashboard-card .btn-success {
    background: var(--success-color);
    color: #fff;
}
.dashboard-card .btn-success:hover {
    background: #218838;
    color: #fff;
}
.dashboard-card .btn-warning {
    background: #fbbf24;
    color: #444;
}
.dashboard-card .btn-warning:hover {
    background: #f59e42;
    color: #fff;
}
.dashboard-card .btn-secondary {
    background: var(--secondary-color);
    color: #fff;
}
.dashboard-card .btn-secondary:hover {
    background: #495057;
    color: #fff;
}
.dashboard-card .btn-dark {
    background: var(--dark-color);
    color: #fff;
}
.dashboard-card .btn-dark:hover {
    background: var(--primary-color);
    color: #fff;
}
@media (max-width: 1200px) {
    .dashboard-grid { grid-template-columns: repeat(2, 220px); }
}
@media (max-width: 900px) {
    .dashboard-grid { grid-template-columns: 1fr; gap: 1.2rem; }
    .dashboard-card { width: 100%; min-width: 0; }
}
</style>
<div class="customer-dashboard">
    <div class="container mt-4">
        <!-- Enhanced Welcome Section -->
        <div class="welcome-card">
            <!-- Removed the welcome-icon div -->
            <div class="welcome-text" style="margin: 0 auto;">
                <div class="welcome-title">
                    Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
                </div>
                <div class="welcome-desc">
                    We're glad to have you here. Manage your reservations, invitations, and account with ease.
                </div>
            </div>
        </div>
        <!-- End Enhanced Welcome Section -->
        <div class="dashboard-grid">
            
            <div class="dashboard-card">
                <div class="dashboard-icon"><i class="fas fa-list-check"></i></div>
                <div class="dashboard-title-row">
                    <div class="dashboard-title">My Reservations</div>
                </div>
                <p class="dashboard-text">View, cancel, or check your venue reservations.</p>
                <a href="my_reservations.php" class="btn btn-info">Manage</a>
            </div>
            <div class="dashboard-card">
                <div class="dashboard-icon"><i class="fas fa-envelope"></i></div>
                <div class="dashboard-title-row">
                    <div class="dashboard-title">Create Invitations</div>
                </div>
                <p class="dashboard-text">Send invitations to your guests for your upcoming events.</p>
                <a href="invitations.php" class="btn btn-success">Invite</a>
            </div>
         
            <div class="dashboard-card">
                <div class="dashboard-icon"><i class="far fa-clock"></i></div>
                <div class="dashboard-title-row">
                    <div class="dashboard-title">Venue Availability</div>
                </div>
                <p class="dashboard-text">See available times for all venues before booking.</p>
                <a href="availability.php" class="btn btn-dark">Check</a>
            </div>
        </div>
    </div>
</div>
<?php
require_once '../includes/footer.php';
?>
