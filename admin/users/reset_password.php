<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }

    $newPassword = generateRandomPassword();
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param('si', $hashedPassword, $userId);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Password reset successfully',
            'password' => $newPassword
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error resetting password']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit;
?>