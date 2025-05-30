<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'customer') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}
$pageTitle = 'My Reservations';
require_once '../includes/header.php';

$user_id = $_SESSION['user_id'];
// Handle cancellation
if (isset($_POST['cancel_id'])) {
    $cancel_id = (int)$_POST['cancel_id'];
    $stmt = $db->getConnection()->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cancel_id, $user_id);
    $stmt->execute();
}
// Fetch user's reservations
$sql = "SELECT b.*, h.name as hall_name FROM bookings b JOIN halls h ON b.hall_id = h.id WHERE b.user_id = ? ORDER BY b.event_date DESC, b.start_time DESC";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$reservations = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<div class="container mt-4">
    <div class="reservations-container">
        <h2>My Reservations</h2>
        <?php if (empty($reservations)): ?>
            <div class="alert alert-info">You have no reservations yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Venue</th>
                            <th>Date</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Guests</th>
                            <th>Estimated Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reservations as $res): ?>
                        <tr>
                            <td><?= htmlspecialchars($res['hall_name']) ?></td>
                            <td><?= htmlspecialchars($res['event_date']) ?></td>
                            <td><?= htmlspecialchars($res['start_time']) ?></td>
                            <td><?= htmlspecialchars($res['end_time']) ?></td>
                            <td><?= htmlspecialchars($res['guests']) ?></td>
                            <td><?= number_format($res['total_price'], 2) ?> $</td>
                            <td><?= htmlspecialchars(ucfirst($res['status'])) ?></td>
                            <td>
                                <?php if ($res['status'] === 'pending' || $res['status'] === 'confirmed'): ?>
                                    <form method="post" style="display:inline-block">
                                        <input type="hidden" name="cancel_id" value="<?= $res['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this reservation?')">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <a href="index.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</div>
