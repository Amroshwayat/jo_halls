<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$pageTitle = 'Bookings Dashboard';
$currentPage = 'bookings';

$dbConn = $db->getConnection();

// Fetch bookings with hall and user info
$sql = "SELECT b.*, h.name AS hall_name, u.first_name, u.last_name FROM bookings b
        LEFT JOIN halls h ON b.hall_id = h.id
        LEFT JOIN users u ON b.user_id = u.id
        ORDER BY b.created_at DESC LIMIT 100";
$result = $dbConn->query($sql);

require_once '../includes/admin_header.php';
?>
<div class="container mt-4">
    <h1 class="mb-4"><i class="fas fa-calendar-alt"></i> Bookings Dashboard</h1>
    <!-- Filter/Search Form -->
    <form method="get" class="form-inline mb-3">
        <input type="text" name="user" class="form-control mr-2 mb-2" placeholder="User Name" value="<?= isset($_GET['user']) ? htmlspecialchars($_GET['user']) : '' ?>">
        <input type="text" name="hall" class="form-control mr-2 mb-2" placeholder="Hall Name" value="<?= isset($_GET['hall']) ? htmlspecialchars($_GET['hall']) : '' ?>">
        <select name="status" class="form-control mr-2 mb-2">
            <option value="">All Statuses</option>
            <option value="pending" <?= (isset($_GET['status']) && $_GET['status']==='pending')?'selected':'' ?>>Pending</option>
            <option value="confirmed" <?= (isset($_GET['status']) && $_GET['status']==='confirmed')?'selected':'' ?>>Confirmed</option>
            <option value="cancelled" <?= (isset($_GET['status']) && $_GET['status']==='cancelled')?'selected':'' ?>>Cancelled</option>
            <option value="completed" <?= (isset($_GET['status']) && $_GET['status']==='completed')?'selected':'' ?>>Completed</option>
        </select>
        <button type="submit" class="btn btn-primary mb-2">Search</button>
    </form>
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
                <?php
                // --- FILTER LOGIC ---
                $where = [];
                $params = [];
                if (!empty($_GET['user'])) {
                    $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ?)";
                    $params[] = "%".$_GET['user']."%";
                    $params[] = "%".$_GET['user']."%";
                }
                if (!empty($_GET['hall'])) {
                    $where[] = "h.name LIKE ?";
                    $params[] = "%".$_GET['hall']."%";
                }
                if (!empty($_GET['status'])) {
                    $where[] = "b.status = ?";
                    $params[] = $_GET['status'];
                }
                $sql = "SELECT b.*, h.name AS hall_name, u.first_name, u.last_name FROM bookings b
                        LEFT JOIN halls h ON b.hall_id = h.id
                        LEFT JOIN users u ON b.user_id = u.id";
                if ($where) {
                    $sql .= " WHERE ".implode(' AND ', $where);
                }
                $sql .= " ORDER BY b.created_at DESC LIMIT 100";
                $stmt = $dbConn->prepare($sql);
                if ($params) {
                    $types = str_repeat('s', count($params));
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                ?>
                <?php if ($result && $result->num_rows > 0): $i = 1; ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['hall_name']) ?></td>
                            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
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
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this booking?');"><i class="fas fa-trash"></i></a>
                                <?php if ($row['status'] !== 'confirmed'): ?>
                                    <a href="confirm.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success" title="Confirm"><i class="fas fa-check"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center">No bookings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once '../includes/admin_footer.php'; ?>
