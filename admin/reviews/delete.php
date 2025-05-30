<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['review_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Review ID is required']);
    exit;
}

$reviewId = (int)$_POST['review_id'];

$sql = "DELETE FROM reviews WHERE id = ?";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("i", $reviewId);

header('Content-Type: application/json');
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete review']);
}
?>