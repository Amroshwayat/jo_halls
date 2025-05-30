<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
if (!isLoggedIn() || !isHallOwner()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}
$userId = $_SESSION['user_id'];
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
global $db;

// جلب بيانات الحجز
$stmt = $db->getConnection()->prepare("SELECT b.*, h.name AS hall_name, u.first_name, u.last_name FROM bookings b LEFT JOIN halls h ON b.hall_id = h.id LEFT JOIN users u ON b.user_id = u.id WHERE b.id = ? AND h.owner_id = ?");
$stmt->bind_param('ii', $booking_id, $userId);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
if (!$booking) {
    echo '<div class="alert alert-danger">Booking not found or not allowed.</div>';
    exit();
}

// معالجة التعديل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $total_price = isset($_POST['total_price']) ? floatval($_POST['total_price']) : $booking['total_price'];
    $status = isset($_POST['status']) ? $_POST['status'] : $booking['status'];
    $stmt = $db->getConnection()->prepare("UPDATE bookings SET total_price = ?, status = ? WHERE id = ? AND hall_id IN (SELECT id FROM halls WHERE owner_id = ?)");
    $stmt->bind_param('dsii', $total_price, $status, $booking_id, $userId);
    $stmt->execute();
    header('Location: bookings.php');
    exit();
}
require_once 'includes/owner_header.php';
?>
<div class="container mt-4">
    <h2>Edit Booking #<?= $booking_id ?></h2>
    <form method="post">
        <div class="form-group">
            <label>Hall</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($booking['hall_name']) ?>" readonly>
        </div>
        <div class="form-group">
            <label>User</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?>" readonly>
        </div>
        <div class="form-group">
            <label>Event Date</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($booking['event_date']) ?>" readonly>
        </div>
        <div class="form-group">
            <label>Total Price</label>
            <input type="number" step="0.01" name="total_price" class="form-control" value="<?= htmlspecialchars($booking['total_price']) ?>">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="pending" <?= $booking['status']==='pending'?'selected':'' ?>>Pending</option>
                <option value="confirmed" <?= $booking['status']==='confirmed'?'selected':'' ?>>Confirmed</option>
                <option value="cancelled" <?= $booking['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
                <option value="completed" <?= $booking['status']==='completed'?'selected':'' ?>>Completed</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Save Changes</button>
        <a href="bookings.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
<?php require_once 'includes/owner_footer.php'; ?>
