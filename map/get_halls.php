<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// استخدام mysqli لجلب بيانات القاعات
$query = "SELECT * FROM halls";
$result = $db->getConnection()->query($query);

// تحقق من وجود نتائج
if ($result->num_rows > 0) {
    $halls = [];
    
    // جلب النتائج
    while ($row = $result->fetch_assoc()) {
        $halls[] = $row;
    }
    
    // إرجاع البيانات بصيغة JSON
    echo json_encode($halls, JSON_UNESCAPED_UNICODE);
} else {
    // في حالة عدم وجود نتائج
    echo json_encode(['error' => 'No halls found']);
}
?>