<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// التحقق من صلاحية المستخدم
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// التحقق من أن الـ ID موجود في الطلب
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid template ID']);
    exit();
}

$templateId = (int)$_POST['id'];

// حذف القالب من قاعدة البيانات
$dbConn = $db->getConnection();

// أولاً تحقق إذا القالب موجود
$stmtCheck = $dbConn->prepare("SELECT * FROM invitation_templates WHERE id = ?");
$stmtCheck->bind_param("i", $templateId);
$stmtCheck->execute();
$result = $stmtCheck->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Template not found']);
    exit();
}

// حذف القالب
$stmtDelete = $dbConn->prepare("DELETE FROM invitation_templates WHERE id = ?");
$stmtDelete->bind_param("i", $templateId);

if ($stmtDelete->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete template']);
}
