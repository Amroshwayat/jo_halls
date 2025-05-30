<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isHallOwner()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
global $db;

// Get stats
$totalHallsStmt = $db->getConnection()->prepare("SELECT COUNT(*) as cnt FROM halls WHERE owner_id = ?");
$totalHallsStmt->bind_param('i', $userId);
$totalHallsStmt->execute();
$totalHallsRes = $totalHallsStmt->get_result();
$totalHalls = ($row = $totalHallsRes->fetch_assoc()) ? $row['cnt'] : 0;

$newBookingsStmt = $db->getConnection()->prepare("SELECT COUNT(*) as cnt FROM bookings WHERE hall_id IN (SELECT id FROM halls WHERE owner_id = ?) AND status = 'pending'");
$newBookingsStmt->bind_param('i', $userId);
$newBookingsStmt->execute();
$newBookingsRes = $newBookingsStmt->get_result();
$newBookings = ($row = $newBookingsRes->fetch_assoc()) ? $row['cnt'] : 0;

$totalBookingsStmt = $db->getConnection()->prepare("SELECT COUNT(*) as cnt FROM bookings WHERE hall_id IN (SELECT id FROM halls WHERE owner_id = ?)");
$totalBookingsStmt->bind_param('i', $userId);
$totalBookingsStmt->execute();
$totalBookingsRes = $totalBookingsStmt->get_result();
$totalBookings = ($row = $totalBookingsRes->fetch_assoc()) ? $row['cnt'] : 0;

// Notifications (pending bookings)
$notifications = $newBookings;

require_once 'includes/owner_header.php';
?>
<style>
body {
    margin-top: 0 !important;
    padding-top: 0 !important;
}
.admin-content, .content-header {
    margin-top: 0 !important;
    padding-top: 0 !important;
}
.admin-wrapper {
    margin-top: 0 !important;
}
.content-header {
    margin-top: 0 !important;
    padding-top: 0 !important;
}
.content-header h1 {
    font-size: 2.1rem;
    font-weight: bold;
    margin-bottom: 1.7rem;
    display: flex;
    align-items: center;
    gap: 12px;
}
.quick-stats {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}
.stat-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    padding: 28px 38px;
    min-width: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 20px;
    transition: transform 0.13s;
}
.stat-card:hover {
    transform: translateY(-5px) scale(1.03);
    box-shadow: 0 6px 20px rgba(0,0,0,0.10);
}
.stat-card i {
    font-size: 2.2rem;
    margin-bottom: 10px;
    color: #00b894;
}
.stat-card h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
}
.stat-card p {
    font-size: 1.6rem;
    font-weight: bold;
    color: #222;
    margin: 0;
}
.stat-card .text-danger { color: #e17055; }
.stat-card .text-success { color: #098c50; }
.alert-info {
    background: #d0f0ff;
    color: #0984e3;
    border-radius: 7px;
    padding: 14px 22px;
    margin-top: 10px;
    font-size: 1.08rem;
    border-left: 5px solid #00b894;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
@media (max-width: 700px) {
    .quick-stats { flex-direction: column; gap: 18px; }
    .stat-card { min-width: 0; width: 100%; }
}
</style>
<div class="content-header">
    <h1><i class="fas fa-tachometer-alt"></i> Owner Dashboard</h1>
</div>
<div class="quick-stats">
    <div class="stat-card">
        <i class="fas fa-home"></i>
        <h3>Your Halls</h3>
        <p><?= $totalHalls ?></p>
    </div>
    <div class="stat-card">
        <i class="fas fa-calendar-alt"></i>
        <h3>New Reservation Requests</h3>
        <p class="text-danger"><?= $newBookings ?></p>
    </div>
    <div class="stat-card">
        <i class="fas fa-calendar-check"></i>
        <h3>Total Bookings</h3>
        <p class="text-success"><?= $totalBookings ?></p>
    </div>
</div>
<?php if ($notifications > 0): ?>
    <div class="alert alert-info">You have <?= $notifications ?> new reservation request(s) pending action!</div>
<?php endif; ?>
<?php require_once 'includes/owner_footer.php'; ?>
