<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isHallOwner()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$hallId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

global $db;
$stmt = $db->getConnection()->prepare("
    SELECT h.*, GROUP_CONCAT(ha.amenity_id) as amenity_ids
    FROM halls h
    LEFT JOIN hall_amenities ha ON h.id = ha.hall_id
    WHERE h.id = ? AND h.owner_id = ?
    GROUP BY h.id
");
$stmt->execute([$hallId, $_SESSION['user_id']]);
$hall = null;
$result = $stmt->get_result();
if ($result) {
    $hall = $result->fetch_assoc();
}

if (!$hall) {
    header('Location: manage.php');
    exit();
}

$amenities = getAmenities();

$images = getHallImages($hallId);

$currentAmenities = $hall['amenity_ids'] ? explode(',', $hall['amenity_ids']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $address = sanitizeInput($_POST['address']);
    $city = sanitizeInput($_POST['city']);
    $latitude = (float)$_POST['latitude'];
    $longitude = (float)$_POST['longitude'];
    $capacityMin = (int)$_POST['capacity_min'];
    $capacityMax = (int)$_POST['capacity_max'];
    $pricePerHour = (float)$_POST['price_per_hour'];
    $selectedAmenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];

    $errors = [];

    if (empty($name)) $errors[] = 'Hall name is required';
    if (empty($description)) $errors[] = 'Description is required';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($city)) $errors[] = 'City is required';
    if ($capacityMin < 1) $errors[] = 'Minimum capacity must be at least 1';
    if ($capacityMax < $capacityMin) $errors[] = 'Maximum capacity must be greater than minimum capacity';
    if ($pricePerHour <= 0) $errors[] = 'Price per hour must be greater than 0';

    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === 0) {
        $mainImage = uploadImage($_FILES['main_image'], 'halls');
        if ($mainImage) {

            $stmt = $db->getConnection()->prepare("UPDATE halls SET main_image = ? WHERE id = ?");
            $stmt->execute([$mainImage, $hallId]);
        } else {
            $errors[] = 'Failed to upload main image';
        }
    }

    if (empty($errors)) {

        if (updateHall($hallId, $name, $description, $address, $city, $latitude, $longitude,
                      $capacityMin, $capacityMax, $pricePerHour)) {

            updateHallAmenities($hallId, $selectedAmenities);

            if (isset($_FILES['additional_images'])) {
                foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['additional_images']['error'][$key] === 0) {
                        $file = [
                            'name' => $_FILES['additional_images']['name'][$key],
                            'type' => $_FILES['additional_images']['type'][$key],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['additional_images']['error'][$key],
                            'size' => $_FILES['additional_images']['size'][$key]
                        ];

                        $imagePath = uploadImage($file, 'halls');
                        if ($imagePath) {
                            addHallImage($hallId, $imagePath);
                        }
                    }
                }
            }

            if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $imageId) {
                    $stmt = $db->getConnection()->prepare("
                        DELETE FROM hall_images WHERE id = ? AND hall_id = ?
                    ");
                    $stmt->execute([(int)$imageId, $hallId]);
                }
            }

            setMessage('success', 'Hall updated successfully!');
            header('Location: manage.php');
            exit();
        } else {
            $errors[] = 'Failed to update hall. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hall - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/edit_hall.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&libraries=places"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="content-wrapper">
        <div class="form-container">
            <h1>Edit Wedding Hall</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" class="hall-form" 
                  onsubmit="return validateForm()">
                <div class="form-section">
                    <h3>Basic Information</h3>

                    <div class="form-group">
                        <label for="name">Hall Name:</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo htmlspecialchars($hall['name']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" required rows="5"><?php 
                            echo htmlspecialchars($hall['description']); 
                        ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Location</h3>

                    <div class="form-group">
                        <label for="address">Address:</label>
                        <input type="text" id="address" name="address" required 
                               value="<?php echo htmlspecialchars($hall['address']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="city">City:</label>
                        <input type="text" id="city" name="city" required 
                               value="<?php echo htmlspecialchars($hall['city']); ?>">
                    </div>

                    <input type="hidden" id="latitude" name="latitude" 
                           value="<?php echo $hall['latitude']; ?>" required>
                    <input type="hidden" id="longitude" name="longitude" 
                           value="<?php echo $hall['longitude']; ?>" required>

                    <div id="map" style="height: 300px; margin-bottom: 20px;"></div>
                </div>

                <div class="form-section">
                    <h3>Capacity & Pricing</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="capacity_min">Minimum Capacity:</label>
                            <input type="number" id="capacity_min" name="capacity_min" required min="1" 
                                   value="<?php echo $hall['capacity_min']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="capacity_max">Maximum Capacity:</label>
                            <input type="number" id="capacity_max" name="capacity_max" required min="1" 
                                   value="<?php echo $hall['capacity_max']; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="price_per_hour">Price per Hour ($):</label>
                        <input type="number" id="price_per_hour" name="price_per_hour" required min="0" step="0.01" 
                               value="<?php echo $hall['price_per_hour']; ?>">
                    </div>
                </div>

                <div class="form-section">
                    <h3>Amenities</h3>
                    <div class="amenities-grid">
                        <?php foreach ($amenities as $amenity): ?>
                            <div class="amenity-checkbox">
                                <input type="checkbox" id="amenity_<?php echo $amenity['id']; ?>" 
                                       name="amenities[]" value="<?php echo $amenity['id']; ?>"
                                       <?php echo in_array($amenity['id'], $currentAmenities) ? 'checked' : ''; ?>>
                                <label for="amenity_<?php echo $amenity['id']; ?>">
                                    <i class="fa <?php echo $amenity['icon']; ?>"></i>
                                    <?php echo htmlspecialchars($amenity['name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Images</h3>

                    <div class="form-group">
                        <label>Current Main Image:</label>
                        <img src="../<?php echo htmlspecialchars($hall['main_image']); ?>" 
                             alt="Main Image" class="current-image">

                        <label for="main_image">Update Main Image:</label>
                        <input type="file" id="main_image" name="main_image" accept="image/*" 
                               onchange="previewImage(this, 'main-image-preview')">
                        <img id="main-image-preview" class="image-preview" style="display: none;">
                    </div>

                    <div class="form-group">
                        <label>Current Additional Images:</label>
                        <div class="current-images-grid">
                            <?php foreach ($images as $image): ?>
                                <div class="image-container">
                                    <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" 
                                         alt="Hall Image">
                                    <label>
                                        <input type="checkbox" name="delete_images[]" 
                                               value="<?php echo $image['id']; ?>">
                                        Delete
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <label for="additional_images">Add More Images:</label>
                        <input type="file" id="additional_images" name="additional_images[]" multiple accept="image/*" 
                               onchange="previewMultipleImages(this, 'additional-images-preview')">
                        <div id="additional-images-preview" class="images-preview-grid"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Hall</button>
                    <a href="manage.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        let map, marker;

        function initMap() {
            const hallLocation = {
                lat: <?php echo $hall['latitude']; ?>,
                lng: <?php echo $hall['longitude']; ?>
            };

            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 15,
                center: hallLocation
            });

            marker = new google.maps.Marker({
                position: hallLocation,
                map: map,
                draggable: true
            });

            marker.addListener('dragend', function() {
                const position = marker.getPosition();
                document.getElementById('latitude').value = position.lat();
                document.getElementById('longitude').value = position.lng();
            });

            const addressInput = document.getElementById('address');
            const autocomplete = new google.maps.places.Autocomplete(addressInput, {
                componentRestrictions: { country: 'JO' }
            });

            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                if (place.geometry) {
                    map.setCenter(place.geometry.location);
                    marker.setPosition(place.geometry.location);

                    document.getElementById('latitude').value = place.geometry.location.lat();
                    document.getElementById('longitude').value = place.geometry.location.lng();

                    for (const component of place.address_components) {
                        if (component.types.includes('locality')) {
                            document.getElementById('city').value = component.long_name;
                            break;
                        }
                    }
                }
            });
        }

        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function previewMultipleImages(input, previewId) {
            const preview = document.getElementById(previewId);
            preview.innerHTML = '';

            if (input.files) {
                for (let i = 0; i < input.files.length; i++) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'image-preview';
                        preview.appendChild(img);
                    };
                    reader.readAsDataURL(input.files[i]);
                }
            }
        }

        function validateForm() {
            const capacityMin = parseInt(document.getElementById('capacity_min').value);
            const capacityMax = parseInt(document.getElementById('capacity_max').value);
            const pricePerHour = parseFloat(document.getElementById('price_per_hour').value);
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;

            if (capacityMax < capacityMin) {
                alert('Maximum capacity must be greater than minimum capacity');
                return false;
            }

            if (pricePerHour <= 0) {
                alert('Price per hour must be greater than 0');
                return false;
            }

            if (!latitude || !longitude) {
                alert('Please select a location on the map');
                return false;
            }

            return true;
        }

        document.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>