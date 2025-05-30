<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isHallOwner()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
global $db;
$stmt = $db->getConnection()->prepare("
    SELECT h.*, 
           COUNT(DISTINCT b.id) as total_bookings,
           COUNT(DISTINCT r.id) as total_reviews,
           AVG(r.rating) as average_rating
    FROM halls h
    LEFT JOIN bookings b ON h.id = b.hall_id
    LEFT JOIN reviews r ON h.id = r.hall_id AND r.status = 'approved'
    WHERE h.owner_id = ?
    GROUP BY h.id
    ORDER BY h.created_at DESC
");
$stmt->execute([$userId]);
$halls = [];
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $halls[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_hall'])) {
    $hallId = (int)$_POST['hall_id'];

    $stmt = $db->getConnection()->prepare("SELECT id FROM halls WHERE id = ? AND owner_id = ?");
    $stmt->execute([$hallId, $userId]);

    if ($stmt->rowCount() > 0) {

        $stmt = $db->getConnection()->prepare("DELETE FROM hall_images WHERE hall_id = ?");
        $stmt->execute([$hallId]);

        $stmt = $db->getConnection()->prepare("DELETE FROM halls WHERE id = ?");
        $stmt->execute([$hallId]);

        setMessage('success', 'Hall deleted successfully');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }
}
?>
<?php require_once 'includes/owner_header.php'; ?>

    <div class="content-wrapper">
        <div class="dashboard-header">
            <h1>Manage Your Wedding Halls</h1>
            <a href="add.php" class="btn btn-primary">Add New Hall</a>
        </div>

        <?php $message = getMessage(); ?>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?>">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($halls)): ?>
            <div class="no-halls">
                <p>You haven't added any halls yet.</p>
                <a href="add.php" class="btn btn-primary">Add Your First Hall</a>
            </div>
        <?php else: ?>
            <div class="halls-grid dashboard">
                <?php foreach ($halls as $hall): ?>
                    <div class="hall-card dashboard">
                        <div class="hall-image">
                            <img src="../<?php echo htmlspecialchars($hall['main_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($hall['name']); ?>">
                            <span class="status-badge <?php echo $hall['status']; ?>">
                                <?php echo ucfirst($hall['status']); ?>
                            </span>
                        </div>

                        <div class="hall-info">
                            <h3><?php echo htmlspecialchars($hall['name']); ?></h3>

                            <div class="stats">
                                <div class="stat">
                                    <span class="label">Bookings</span>
                                    <span class="value"><?php echo $hall['total_bookings']; ?></span>
                                </div>
                                <div class="stat">
                                    <span class="label">Reviews</span>
                                    <span class="value"><?php echo $hall['total_reviews']; ?></span>
                                </div>
                                <div class="stat">
                                    <span class="label">Rating</span>
                                    <span class="value">
                                        <?php echo $hall['average_rating'] ? 
                                              number_format($hall['average_rating'], 1) : 'N/A'; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="hall-actions">
                                <a href="edit.php?id=<?php echo $hall['id']; ?>" 
                                   class="btn btn-secondary">Edit</a>
                                <a href="images.php?id=<?php echo $hall['id']; ?>" class="btn btn-secondary btn-sm ml-1">Manage Images</a>
                                <a href="bookings.php?hall_id=<?php echo $hall['id']; ?>" 
                                   class="btn btn-secondary">View Bookings</a>
                                <form method="POST" action="" class="delete-form" 
                                      onsubmit="return confirm('Are you sure you want to delete this hall?');">
                                    <input type="hidden" name="hall_id" value="<?php echo $hall['id']; ?>">
                                    <button type="submit" name="delete_hall" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php require_once 'includes/owner_header.php'; ?>
<link rel="stylesheet" href="assets/css/manage_halls.css">
<?php require_once 'includes/owner_footer.php'; ?>