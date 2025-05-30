<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit;
}

$currentPage = 'reviews';
$pageTitle = 'Reviews Dashboard';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$hallId = isset($_GET['hall_id']) ? (int)$_GET['hall_id'] : 0;

$sql = "SELECT r.*, 
        h.name as hall_name,
        COALESCE(NULLIF(CONCAT(u.first_name, ' ', u.last_name), ' '), u.username) as reviewer_name
        FROM reviews r
        LEFT JOIN halls h ON r.hall_id = h.id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE 1=1";

$countSql = "SELECT COUNT(*) as total FROM reviews r WHERE 1=1";
$params = [];
$types = "";

if ($status !== 'all') {
    $sql .= " AND r.status = ?";
    $countSql .= " AND r.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($hallId > 0) {
    $sql .= " AND r.hall_id = ?";
    $countSql .= " AND r.hall_id = ?";
    $params[] = $hallId;
    $types .= "i";
}

$stmt = $db->getConnection()->prepare($countSql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalResult = $stmt->get_result()->fetch_assoc();
$total = $totalResult['total'];
$totalPages = ceil($total / $perPage);

$sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $db->getConnection()->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$halls = [];
$hallsSql = "SELECT id, name FROM halls ORDER BY name";
$hallsResult = $db->getConnection()->query($hallsSql);
while ($hall = $hallsResult->fetch_assoc()) {
    $halls[] = $hall;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'update_status') {
        $reviewId = (int)$_POST['review_id'];
        $newStatus = $_POST['status'];

        $updateSql = "UPDATE reviews SET status = ? WHERE id = ?";
        $stmt = $db->getConnection()->prepare($updateSql);
        $stmt->bind_param("si", $newStatus, $reviewId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update status']);
        }
        exit;
    }
}

require_once '../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="dashboard-header">
        <h1><?php echo $pageTitle; ?></h1>

        <!-- Filters -->
        <div class="filters">
            <form method="get" class="filter-form">
                <select name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>

                <select name="hall_id" onchange="this.form.submit()">
                    <option value="0">All Halls</option>
                    <?php foreach ($halls as $hall): ?>
                        <option value="<?php echo $hall['id']; ?>" <?php echo $hallId === $hall['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($hall['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Reviews Table -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Hall</th>
                    <th>Reviewer</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($review['hall_name']); ?></td>
                        <td><?php echo htmlspecialchars($review['reviewer_name']); ?></td>
                        <td>
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?php echo $i <= $review['rating'] ? ' active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($review['review_text']); ?></td>
                        <td>
                            <select class="status-select" data-review-id="<?php echo $review['id']; ?>">
                                <option value="pending" <?php echo $review['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $review['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $review['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-danger btn-sm delete-review" data-review-id="<?php echo $review['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reviews)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No reviews found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo ($page - 1); ?>&status=<?php echo $status; ?>&hall_id=<?php echo $hallId; ?>" class="btn btn-sm">&laquo; Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="btn btn-sm active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&hall_id=<?php echo $hallId; ?>" class="btn btn-sm"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo ($page + 1); ?>&status=<?php echo $status; ?>&hall_id=<?php echo $hallId; ?>" class="btn btn-sm">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php

$extraScripts = <<<JS

    $('.status-select').on('change', function() {
        const reviewId = $(this).data('review-id');
        const newStatus = $(this).val();
        const select = $(this);

        $.ajax({
            url: 'dashboard.php',
            method: 'POST',
            data: {
                action: 'update_status',
                review_id: reviewId,
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Review status updated successfully');
                } else {
                    showAlert('danger', 'Failed to update review status');
                    select.val(select.data('original-value'));
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while updating the review status');
                select.val(select.data('original-value'));
            }
        });
    }).each(function() {
        $(this).data('original-value', $(this).val());
    });

    $('.delete-review').on('click', function() {
        if (confirm('Are you sure you want to delete this review?')) {
            const reviewId = $(this).data('review-id');
            const row = $(this).closest('tr');

            $.ajax({
                url: 'delete.php',
                method: 'POST',
                data: { review_id: reviewId },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(400, function() {
                            $(this).remove();
                            showAlert('success', 'Review deleted successfully');
                        });
                    } else {
                        showAlert('danger', 'Failed to delete review');
                    }
                },
                error: function() {
                    showAlert('danger', 'An error occurred while deleting the review');
                }
            });
        }
    });

    function showAlert(type, message) {
        const alert = $('<div>')
            .addClass('alert alert-' + type)
            .html('<i class="fas fa-' + (type === 'success' ? 'check' : 'exclamation') + '-circle"></i> ' + message);

        $('.admin-content').prepend(alert);
        alert.delay(5000).fadeOut(500, function() {
            $(this).remove();
        });
    }
JS;

require_once '../includes/admin_footer.php';
?>