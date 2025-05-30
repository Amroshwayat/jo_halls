<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isHallOwner()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$hallId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'];

// تحقق أن القاعة تعود للمالك الحالي
$stmt = $db->getConnection()->prepare("SELECT * FROM halls WHERE id = ? AND owner_id = ?");
$stmt->execute([$hallId, $userId]);
$hall = $stmt->get_result()->fetch_assoc();
if (!$hall) {
    header('Location: manage.php');
    exit();
}

$success_message = $error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'upload') {
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['images']['name'][$key],
                        'type' => $_FILES['images']['type'][$key],
                        'tmp_name' => $tmp_name,
                        'error' => $_FILES['images']['error'][$key],
                        'size' => $_FILES['images']['size'][$key]
                    ];
                    $imagePath = uploadImage($file, 'halls');
                    if ($imagePath) {
                        addHallImage($hallId, $imagePath);
                        $success_message = 'Image uploaded successfully!';
                    } else {
                        $error_message = 'Failed to upload image.';
                    }
                }
            }
        }
    } elseif ($action === 'delete') {
        $imageId = (int)$_POST['image_id'];
        if (deleteHallImage($imageId)) {
            $success_message = 'Image deleted successfully!';
        } else {
            $error_message = 'Failed to delete image.';
        }
    } elseif ($action === 'set_main') {
        $imageId = (int)$_POST['image_id'];
        if (setMainHallImage($hallId, $imageId)) {
            $success_message = 'Main image updated successfully!';
        } else {
            $error_message = 'Failed to update main image.';
        }
    }
}

$images = getHallImages($hallId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Images - <?php echo htmlspecialchars($hall['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/css/edit_hall.css">
    <style>
        .image-grid { display: flex; flex-wrap: wrap; gap: 18px; margin-top: 25px; }
        .image-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 12px 16px; text-align: center; }
        .image-card img { width: 160px; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
        .main-badge { display: block; color: #fff; background: #ff5eaa; border-radius: 8px; padding: 2px 10px; font-size: 0.93rem; margin-bottom: 7px; }
        .image-actions { margin-top: 7px; }
        .image-actions form { display: inline; }
        .image-actions button { margin: 0 2px; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="content-wrapper">
        <div class="form-container">
            <h1>Manage Images - <?php echo htmlspecialchars($hall['name']); ?></h1>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <div class="upload-form">
                <h3>Upload New Images</h3>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    <div class="form-group">
                        <label for="images">Select Images:</label>
                        <input type="file" name="images[]" id="images" multiple accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload Images</button>
                </form>
            </div>
            <div class="image-grid">
                <?php foreach ($images as $image): ?>
                    <div class="image-card">
                        <?php if ($image['is_main']): ?>
                            <span class="main-badge">Main Image</span>
                        <?php endif; ?>
                        <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" alt="Hall image">
                        <div class="image-actions">
                            <?php if (!$image['is_main']): ?>
                                <form action="" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="set_main">
                                    <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Set this as the main image?')">Set as Main</button>
                                </form>
                            <?php endif; ?>
                            <form action="" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this image?')">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
