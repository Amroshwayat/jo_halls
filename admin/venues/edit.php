<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../../users/login.php');
    exit();
}

$currentPage = 'venues';
$pageTitle = 'Edit Venue';

// Get venue ID
$venueId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$venueId) {
    header('Location: index.php');
    exit();
}

// Get venue details
$sql = "SELECT * FROM halls WHERE id = ?";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("i", $venueId);
$stmt->execute();
$result = $stmt->get_result();
$venue = $result->fetch_assoc();

if (!$venue) {
    header('Location: index.php');
    exit();
}

// Get venue's current amenities
$sql = "SELECT amenity_id FROM hall_amenities WHERE hall_id = ?";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("i", $venueId);
$stmt->execute();
$result = $stmt->get_result();
$currentAmenities = array_column($result->fetch_all(MYSQLI_ASSOC), 'amenity_id');

// حذف الصورة الرئيسية
if (isset($_GET['delete_main_image']) && $venue['main_image']) {
    $imagePath = $venue['main_image'];
    $stmt = $db->getConnection()->prepare("UPDATE halls SET main_image = NULL WHERE id = ?");
    $stmt->bind_param("i", $venueId);
    if ($stmt->execute() && file_exists($imagePath)) {
        unlink($imagePath);
    }
    header("Location: edit.php?id=$venueId");
    exit();
}

// حذف صورة إضافية
if (isset($_GET['delete_image_id'])) {
    $imageId = (int)$_GET['delete_image_id'];
    $stmt = $db->getConnection()->prepare("SELECT image_path FROM hall_images WHERE id = ? AND hall_id = ?");
    $stmt->bind_param("ii", $imageId, $venueId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $imagePath = $row['image_path'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        $stmt = $db->getConnection()->prepare("DELETE FROM hall_images WHERE id = ?");
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
    }
    header("Location: edit.php?id=$venueId");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $city = $_POST['city'] ?? '';
    $address = $_POST['address'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $capacity_min = (int)($_POST['capacity_min'] ?? 0);
    $capacity_max = (int)($_POST['capacity_max'] ?? 0);
    $price_per_hour = (float)($_POST['price_per_hour'] ?? 0);
    $amenities = $_POST['amenities'] ?? [];
    
    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($latitude) || empty($longitude)) $errors[] = "Location coordinates are required";
    if ($capacity_min <= 0) $errors[] = "Minimum capacity must be greater than 0";
    if ($capacity_max <= $capacity_min) $errors[] = "Maximum capacity must be greater than minimum capacity";
    if ($price_per_hour <= 0) $errors[] = "Price per hour must be greater than 0";
    
    if (empty($errors)) {
        // Handle main image upload if new one is provided
        $mainImage = $venue['main_image'];
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $newMainImage = uploadImage($_FILES['main_image'], 'uploads/venues');
            if ($newMainImage) {
                // Delete old main image
                if ($mainImage && file_exists($mainImage)) {
                    unlink($mainImage);
                }
                $mainImage = $newMainImage;
            } else {
                $errors[] = "Failed to upload main image";
            }
        }
        
        if (empty($errors)) {
            // Update venue
            $sql = "UPDATE halls SET 
                    name = ?, description = ?, address = ?, city = ?,
                    latitude = ?, longitude = ?, capacity_min = ?, 
                    capacity_max = ?, price_per_hour = ?, main_image = ?
                    WHERE id = ?";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bind_param("ssssddiidsi", $name, $description, $address, $city,
                            $latitude, $longitude, $capacity_min, $capacity_max,
                            $price_per_hour, $mainImage, $venueId);
            
            if ($stmt->execute()) {
                // Update amenities
                updateHallAmenities($venueId, $amenities);
                
                // Handle additional images
                if (isset($_FILES['additional_images'])) {
                    foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['additional_images']['name'][$key],
                                'type' => $_FILES['additional_images']['type'][$key],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['additional_images']['error'][$key],
                                'size' => $_FILES['additional_images']['size'][$key]
                            ];
                            $imagePath = uploadImage($file, 'uploads/venues');
                            if ($imagePath) {
                                addVenueImage($venueId, $imagePath);
                            }
                        }
                    }
                }
                
                header('Location: index.php?success=1');
                exit();
            } else {
                $errors[] = "Failed to update venue";
            }
        }
    }
} else {
    // Pre-fill form with venue data
    $_POST = $venue;
}

// Get all amenities
$sql = "SELECT * FROM amenities ORDER BY name";
$result = $db->getConnection()->query($sql);
$amenities = $result->fetch_all(MYSQLI_ASSOC);

require_once '../includes/admin_header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-edit"></i> Edit Venue</h1>
    <div class="actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Venues
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="content-body">
    <form method="POST" enctype="multipart/form-data" class="venue-form">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="name">Venue Name *</label>
                <input type="text" class="form-control" id="name" name="name" required
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
            <div class="form-group col-md-6">
                <label for="city">City *</label>
                <input type="text" class="form-control" id="city" name="city" required
                       value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description *</label>
            <textarea class="form-control" id="description" name="description" rows="4" required><?php 
                echo htmlspecialchars($_POST['description'] ?? ''); 
            ?></textarea>
        </div>

        <div class="form-group">
            <label for="address">Address *</label>
            <input type="text" class="form-control" id="address" name="address" required
                   value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="latitude">Latitude *</label>
                <input type="text" class="form-control" id="latitude" name="latitude" required
                       value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>">
            </div>
            <div class="form-group col-md-6">
                <label for="longitude">Longitude *</label>
                <input type="text" class="form-control" id="longitude" name="longitude" required
                       value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>">
            </div>
        </div>

        <div id="map" style="height: 400px; margin-bottom: 20px;"></div>

        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="capacity_min">Minimum Capacity *</label>
                <input type="number" class="form-control" id="capacity_min" name="capacity_min" required
                       value="<?php echo htmlspecialchars($_POST['capacity_min'] ?? '50'); ?>">
            </div>
            <div class="form-group col-md-4">
                <label for="capacity_max">Maximum Capacity *</label>
                <input type="number" class="form-control" id="capacity_max" name="capacity_max" required
                       value="<?php echo htmlspecialchars($_POST['capacity_max'] ?? '200'); ?>">
            </div>
            <div class="form-group col-md-4">
                <label for="price_per_hour">Price per Hour ($) *</label>
                <input type="number" class="form-control" id="price_per_hour" name="price_per_hour" 
                       step="0.01" required
                       value="<?php echo htmlspecialchars($_POST['price_per_hour'] ?? '100'); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Amenities</label>
            <div class="amenities-grid">
                <?php foreach ($amenities as $amenity): ?>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" 
                               id="amenity_<?php echo $amenity['id']; ?>"
                               name="amenities[]" 
                               value="<?php echo $amenity['id']; ?>"
                               <?php 
                               if (in_array($amenity['id'], $currentAmenities)) echo 'checked'; 
                               ?>>
                        <label class="custom-control-label" 
                               for="amenity_<?php echo $amenity['id']; ?>">
                            <?php echo htmlspecialchars($amenity['name']); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="main_image">Main Image</label>
            <?php if ($venue['main_image']): ?>
                <div class="current-image mb-2">
                    <img src="<?php echo SITE_URL . '/' . $venue['main_image']; ?>" 
                         alt="Current main image" style="max-width: 200px;">
                </div>
                <div class="mb-2">
        <a href="edit.php?id=<?php echo $venueId; ?>&delete_main_image=1"
           class="btn btn-sm btn-danger"
           onclick="return confirm('هل أنت متأكد من حذف الصورة الرئيسية؟');">
            <i class="fas fa-trash-alt"></i> delete
        </a>
    </div>
            <?php endif; ?>
            
            <div class="custom-file">
                <input type="file" class="custom-file-input" id="main_image" 
                       name="main_image" accept="image/*">
                <label class="custom-file-label" for="main_image">Choose new image</label>
            </div>
            <small class="form-text text-muted">Leave empty to keep current image</small>
        </div>

        <div class="form-group">
            <label for="additional_images">Additional Images</label>
            <div class="custom-file">
                <input type="file" class="custom-file-input" id="additional_images" 
                       name="additional_images[]" accept="image/*" multiple>
                <label class="custom-file-label" for="additional_images">Choose new images</label>
            </div>
            <small class="form-text text-muted">You can select multiple images</small>
        </div>

        <div class="form-group">
        
    <div class="row">
        <?php
        $stmt = $db->getConnection()->prepare("SELECT * FROM hall_images WHERE hall_id = ?");
        $stmt->bind_param("i", $venueId);
        $stmt->execute();
        $images = $stmt->get_result();
        while ($image = $images->fetch_assoc()):
        ?>
            <div class="col-md-3 mb-3">
                <div class="card">
                    <img src="<?php echo SITE_URL . '/' . $image['image_path']; ?>" 
                         class="card-img-top" style="max-height: 150px; object-fit: cover;">
                    <div class="card-body p-2 text-center">
                        <a href="edit.php?id=<?php echo $venueId; ?>&delete_image_id=<?php echo $image['id']; ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('هل أنت متأكد من حذف هذه الصورة؟');">
                            <i class="fas fa-trash-alt"></i> delete
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>


