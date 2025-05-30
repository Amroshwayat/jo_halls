<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
if (!isLoggedIn() || !isHallOwner()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}
$userId = $_SESSION['user_id'];
global $db;
  $duplicate = false;


// Fetch halls for this owner
$stmt = $db->getConnection()->prepare("SELECT id, name FROM halls WHERE owner_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$halls = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all availability slots for this owner's halls
$availability = [];
$sql = "SELECT a.*, h.name as hall_name FROM hall_availability a JOIN halls h ON a.hall_id = h.id WHERE h.owner_id = ? ORDER BY a.hall_id, a.day_of_week, a.start_time";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $availability = $result->fetch_all(MYSQLI_ASSOC);
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    $stmt = $db->getConnection()->prepare("DELETE FROM hall_availability WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    header("Location: availability.php");
    exit();
}

// Handle edit (fetch slot for editing)
$editSlot = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $editId = (int)$_POST['edit_id'];
    $stmt = $db->getConnection()->prepare("SELECT * FROM hall_availability WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editSlot = $result->fetch_assoc();
}

// Handle update (edit save)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edit'])) {
    $editId = (int)$_POST['edit_id'];
    $hallId = (int)$_POST['hall_id'];
    $day = (int)$_POST['day'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $stmt = $db->getConnection()->prepare("UPDATE hall_availability SET hall_id=?, day_of_week=?, start_time=?, end_time=? WHERE id=?");
    $stmt->bind_param("iissi", $hallId, $day, $start, $end, $editId);
    $stmt->execute();
    header("Location: availability.php");
    exit();
}

// Handle update
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hall_id'], $_POST['day'], $_POST['start_time'], $_POST['end_time'])) {
    $hallId = (int)$_POST['hall_id'];
    $day = (int)$_POST['day'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    // Upsert (insert or update)
  // Check if this slot already exists

$stmt = $db->getConnection()->prepare("SELECT id FROM hall_availability WHERE hall_id=? AND day_of_week=? AND start_time=? AND end_time=?");
$stmt->bind_param("iiss", $hallId, $day, $start, $end);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Insert new availability
    $stmt = $db->getConnection()->prepare("INSERT INTO hall_availability (hall_id, day_of_week, start_time, end_time, is_available) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("iiss", $hallId, $day, $start, $end);
    $stmt->execute();
    $success = true;
} else {
    $duplicate = true;
}

}

require_once 'includes/owner_header.php';
?>
<div class="container mt-4">
    <h1 class="mb-4"><i class="fas fa-calendar"></i> Update Hall Availability</h1>
   
    <?php if ($success): ?>
      <div class="alert alert-success">Availability updated successfully.</div>
<?php elseif ($duplicate): ?>
    <div class="mb-4"><i class="fas fa-calendar"></i> This time is already available for this room!</div>
<?php endif; ?>

    <form method="post" class="mb-4">
        <div class="form-row">
            <div class="col-md-3">
                <label>Hall</label>
                <select name="hall_id" class="form-control" required>
                    <?php foreach ($halls as $hall): ?>
                        <option value="<?= $hall['id'] ?>"<?= isset($editSlot) && $editSlot['hall_id'] == $hall['id'] ? ' selected' : '' ?>><?= htmlspecialchars($hall['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>Day of Week</label>
                <select name="day" class="form-control" required>
                    <?php foreach ([0=>'Sunday',1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday'] as $k=>$v): ?>
                        <option value="<?= $k ?>"<?= isset($editSlot) && $editSlot['day_of_week'] == $k ? ' selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>Start Time</label>
                <input type="time" name="start_time" class="form-control" required value="<?= isset($editSlot) ? htmlspecialchars($editSlot['start_time']) : '' ?>">
            </div>
            <div class="col-md-2">
                <label>End Time</label>
                <input type="time" name="end_time" class="form-control" required value="<?= isset($editSlot) ? htmlspecialchars($editSlot['end_time']) : '' ?>">
            </div>
            <div class="col-md-2 align-self-end">
                <?php if (isset($editSlot)): ?>
                    <input type="hidden" name="edit_id" value="<?= $editSlot['id'] ?>">
                    <button type="submit" name="save_edit" class="btn btn-success">Save Edit</button>
                    <a href="availability.php" class="btn btn-secondary">Cancel</a>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary">Save</button>
                <?php endif; ?>
            </div>
        </div>
    </form>
    <?php if (!empty($availability)): ?>
        <div class="card mt-4">
            <div class="card-header">Current Availability</div>
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Hall</th>
                            <th>Day</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($availability as $slot): ?>
                        <tr>
                            <td><?= htmlspecialchars($slot['hall_name']) ?></td>
                            <td><?= ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$slot['day_of_week']] ?></td>
                            <td><?= htmlspecialchars($slot['start_time']) ?></td>
                            <td><?= htmlspecialchars($slot['end_time']) ?></td>
                            <td>
                                <form method="post" style="display:inline-block">
                                    <input type="hidden" name="delete_id" value="<?= $slot['id'] ?>">
                                    <button type="submit" name="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete this slot?')">Delete</button>
                                </form>
                                <form method="post" style="display:inline-block">
                                    <input type="hidden" name="edit_id" value="<?= $slot['id'] ?>">
                                    <button type="submit" name="edit" class="btn btn-sm btn-warning">Edit</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once 'includes/owner_footer.php'; ?>
