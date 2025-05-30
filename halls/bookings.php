<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
if (!isLoggedIn() || !isHallOwner()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}
$userId = $_SESSION['user_id'];
global $db;

// Fetch bookings for all halls owned by this owner
$sql = "SELECT b.*, h.name as hall_name, u.first_name, u.last_name FROM bookings b
        JOIN halls h ON b.hall_id = h.id
        JOIN users u ON b.user_id = u.id
        WHERE h.owner_id = ?
        ORDER BY b.created_at DESC";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);

// Handle accept/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['action'])) {
    $bookingId = (int)$_POST['booking_id'];
    $action = $_POST['action'];
    if (in_array($action, ['accept', 'reject'])) {
        $newStatus = $action === 'accept' ? 'confirmed' : 'cancelled';
        $update = $db->getConnection()->prepare("UPDATE bookings SET status = ? WHERE id = ? AND hall_id IN (SELECT id FROM halls WHERE owner_id = ?)");
        $update->bind_param("sii", $newStatus, $bookingId, $userId);
        $update->execute();
        setMessage('success', 'Booking '.($action === 'accept' ? 'accepted' : 'rejected').' successfully.');
        header('Location: bookings.php');
        exit();
    }
}

require_once 'includes/owner_header.php';
?>
<div class="container mt-4">
    <h1 class="mb-4"><i class="fas fa-calendar-alt"></i> Your Hall Bookings</h1>
    <?php $message = getMessage(); ?>
    <?php if ($message): ?>
        <div class="alert alert-<?= $message['type'] ?>"> <?= $message['text'] ?> </div>
    <?php endif; ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>#</th>
                    <th>Hall</th>
                    <th>User</th>
                    <th>Event Date</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Total Price</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($bookings as $row): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['hall_name']) ?></td>
                        <td><?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?></td>
                        <td><?= htmlspecialchars($row['event_date']) ?></td>
                        <td><span class="badge badge-<?php
                            switch($row['status']) {
                                case 'confirmed': echo 'success'; break;
                                case 'pending': echo 'warning'; break;
                                case 'cancelled': echo 'danger'; break;
                                case 'completed': echo 'primary'; break;
                                default: echo 'secondary';
                            }
                        ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></td>
                        <td><span class="badge badge-<?php
                            switch($row['payment_status']) {
                                case 'paid': echo 'success'; break;
                                case 'pending': echo 'warning'; break;
                                case 'refunded': echo 'info'; break;
                                default: echo 'secondary';
                            }
                        ?>"><?= htmlspecialchars(ucfirst($row['payment_status'])) ?></span></td>
                        <td><?= number_format($row['total_price'], 2) ?> JOD</td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td>
                            <?php if ($row['status'] === 'pending'): ?>
                                <form method="post" style="display:inline-block">
                                    <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="action" value="accept" class="btn btn-sm btn-success" title="Accept"><i class="fas fa-check"></i></button>
                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" title="Reject"><i class="fas fa-times"></i></button>
                                </form>
                            <?php endif; ?>
                            <a href="edit_booking.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info" title="Edit"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once 'includes/owner_footer.php'; ?>
