<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// تأكد أن المستخدم مسجل دخول ومش أدمن؟ امنعه
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

// تحقق إذا تم إرسال المعرف (ID) في الرابط
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // المعرف غير موجود => ارجع لصفحة الحجوزات
    header('Location: dashboard.php?error=missing_id');
    exit();
}

$bookingId = intval($_GET['id']);
$dbConn = $db->getConnection();

// تحقق إذا الحجز موجود أولًا
$stmtCheck = $dbConn->prepare("SELECT id FROM bookings WHERE id = ?");
$stmtCheck->bind_param("i", $bookingId);
$stmtCheck->execute();
$result = $stmtCheck->get_result();

if ($result->num_rows === 0) {
    // الحجز غير موجود
    header('Location: dashboard.php?error=not_found');
    exit();
}

// احذف الحجز
$stmtDelete = $dbConn->prepare("DELETE FROM bookings WHERE id = ?");
$stmtDelete->bind_param("i", $bookingId);

if ($stmtDelete->execute()) {
    header('Location: dashboard.php?deleted=1');
    exit();
} else {
    header('Location: dashboard.php?error=delete_failed');
    exit();
}
?>
