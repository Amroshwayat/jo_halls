<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isHallOwner()) {
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit;
}

$hallId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$hall = getHallById($hallId);

if (!$hall) {
    header('Location: ' . SITE_URL . '/admin/venues/');
    exit;
}

$success_message = '';
$error_message = '';

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload':
                if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                    $uploadedFiles = reArrayFiles($_FILES['images']);
                    $successCount = 0;
                    
                    foreach ($uploadedFiles as $file) {
                        $imagePath = uploadImage($file, null, 'hall');
                        if ($imagePath) {
                            $isMain = isset($_POST['is_main']) && $_POST['is_main'] === 'true';
                            if (addVenueImage($hallId, $imagePath, $isMain)) {
                                $successCount++;
                            } else {
                                $error_message = "Failed to save image to database";
                                // Delete the uploaded file if database insert fails
                                deleteImage($imagePath);
                            }
                        } else {
                            $error_message = "Failed to upload image file";
                        }
                    }
                    
                    if ($successCount > 0) {
                        $success_message = "Successfully uploaded $successCount image(s)";
                    }
                }
                break;
                
            case 'delete':
                if (isset($_POST['image_id'])) {
                    $imageId = (int)$_POST['image_id'];
                    if (deleteVenueImage($imageId)) {
                        $success_message = "Image deleted successfully";
                    } else {
                        $error_message = "Failed to delete image";
                    }
                }
                break;
                
            case 'set_main':
                if (isset($_POST['image_id'])) {
                    $imageId = (int)$_POST['image_id'];
                    if (setMainVenueImage($hallId, $imageId)) {
                        $success_message = "Main image updated successfully";
                    } else {
                        $error_message = "Failed to update main image";
                    }
                }
                break;
        }
    }
}

// Get all images for this venue
$images = getHallImages($hallId);

// Helper function to re-arrange $_FILES array
function reArrayFiles($filePost) {
    $fileArray = array();
    $fileCount = count($filePost['name']);
    $fileKeys = array_keys($filePost);
    
    for ($i = 0; $i < $fileCount; $i++) {
        foreach ($fileKeys as $key) {
            $fileArray[$i][$key] = $filePost[$key][$i];
        }
    }
    
    return $fileArray;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Venue Images - <?php echo htmlspecialchars($hall['name']); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1rem;
        }
        .image-card {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0.5rem;
        }
        .image-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
        }
        .image-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 0.5rem;
            gap: 0.5rem;
        }
        .main-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: #4CAF50;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        .upload-form {
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f5f5f5;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include '../includes/owner_header.php'; ?>
    
    <div class="container">
        <h1>Manage Images - <?php echo htmlspecialchars($hall['name']); ?></h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="upload-form">
            <h3>Upload New Images</h3>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <div class="form-group">
                    <label for="images">Select Images:</label>
                    <input type="file" name="images[]" id="images" multiple accept="image/*" required>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_main" value="true">
                        Set as main image
                    </label>
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
                    
                    <img src="<?php echo SITE_URL . '/' . $image['image_path']; ?>" 
                         alt="Venue image">
                    
                    <div class="image-actions">
                        <?php if (!$image['is_main']): ?>
                            <form action="" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="set_main">
                                <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-success" 
                                        onclick="return confirm('Set this as the main image?')">
                                    Set as Main
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <form action="" method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Are you sure you want to delete this image?')">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">Back to Venues</a>
        </div>
    </div>
    
    <?php include '../includes/owner_footer.php'; ?>
</body>
</html>
