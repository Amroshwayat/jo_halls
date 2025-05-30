<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'customer') {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}
$pageTitle = 'Venue Availability';
require_once '../includes/header.php';

// جلب كل القاعات
$venues = [];
$res = $db->getConnection()->query("SELECT id, name FROM halls ORDER BY name");
while ($row = $res->fetch_assoc()) {
    $venues[] = $row;
}

$selected_venue = isset($_GET['venue_id']) ? (int)$_GET['venue_id'] : 0;
$availability = [];
if ($selected_venue) {
    $stmt = $db->getConnection()->prepare("SELECT day_of_week, start_time, end_time, is_available FROM hall_availability WHERE hall_id = ? ORDER BY day_of_week, start_time");
    $stmt->bind_param('i', $selected_venue);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $availability[] = $row;
    }
    $stmt->close();
}
?>
<div class="container mt-4" style="max-width: 650px; margin: 40px auto 0 auto; background: #fff; border-radius: 14px; box-shadow: 0 6px 32px rgba(0,0,0,0.07); padding: 2.4rem 2.2rem 2rem 2.2rem;">
    <h2 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.35rem; color: #ff4b7d; font-weight: bold;">Check Venue Availability</h2>
    <form method="get" class="form-inline mb-4" style="display: flex; flex-wrap: wrap; align-items: center; gap: 1.1rem; justify-content: center; margin-bottom: 1.7rem;">
        <label for="venue_id" class="mr-2" style="font-weight: 500; color: #333; font-size: 1.07rem;">Select Venue:</label>
        <select name="venue_id" id="venue_id" class="form-control mr-2" required onchange="this.form.submit()" style="width: 220px; border-radius: 7px; border: 1.5px solid #e1e1e1; padding: 0.55rem 0.85rem; font-size: 1rem; transition: border 0.18s;">
            <option value="">Choose...</option>
            <?php foreach ($venues as $venue): ?>
                <option value="<?= $venue['id'] ?>" <?= $selected_venue == $venue['id'] ? 'selected' : '' ?>><?= htmlspecialchars($venue['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button type="submit" class="btn btn-primary" style="border-radius: 7px; font-weight: 600; font-size: 1rem; padding: 0.45rem 1.1rem; background: #ff4b7d; color: #fff; border: none;">Show Availability</button></noscript>
    </form>
    <?php if ($selected_venue): ?>
        <h4 style="color: #222; font-size: 1.1rem; font-weight: 600; margin-bottom: 1.2rem; text-align: center;">Availability for: <span style="color: #5bc0de; font-weight: 700;"><?= htmlspecialchars($venues[array_search($selected_venue, array_column($venues, 'id'))]['name']) ?></span></h4>
        <?php if ($availability): ?>
            <table class="table table-bordered table-striped mt-3" style="background: #f8f9fa; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(31,31,90,0.03);">
                <thead style="background: #f3f3f3;">
                    <tr>
                        <th style="padding: 0.7rem; font-weight: 600; color: #333;">Day</th>
                        <th style="padding: 0.7rem; font-weight: 600; color: #333;">Start Time</th>
                        <th style="padding: 0.7rem; font-weight: 600; color: #333;">End Time</th>
                        <th style="padding: 0.7rem; font-weight: 600; color: #333;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($availability as $slot): ?>
                        <tr>
                            <td style="padding: 0.65rem;"><?= jddayofweek($slot['day_of_week'], 1) ?></td>
                            <td style="padding: 0.65rem;"><?= htmlspecialchars(substr($slot['start_time'], 0, 5)) ?></td>
                            <td style="padding: 0.65rem;"><?= htmlspecialchars(substr($slot['end_time'], 0, 5)) ?></td>
                            <td style="padding: 0.65rem;">
                                <?php if ($slot['is_available']): ?>
                                    <span class="badge badge-success" style="background: #28a745; color: #fff; border-radius: 6px; padding: 0.4em 0.9em; font-size: 1em;">Available</span>
                                <?php else: ?>
                                    <span class="badge badge-danger" style="background: #dc3545; color: #fff; border-radius: 6px; padding: 0.4em 0.9em; font-size: 1em;">Unavailable</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning" style="font-size: 1rem; border-radius: 7px; margin: 1.3rem auto; text-align: center; background: #fff3cd; color: #856404; border: 1.5px solid #ffeeba; max-width: 420px;">No availability data for this venue.</div>
        <?php endif; ?>
    <?php endif; ?>
</div>
