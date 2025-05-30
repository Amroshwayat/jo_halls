<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isHallOwner()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$amenities = getAmenities();

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
        if (!$mainImage) {
            $errors[] = 'Failed to upload main image';
        }
    } else {
        $errors[] = 'Main image is required';
    }

    if (empty($errors)) {

        $hallId = createHall($_SESSION['user_id'], $name, $description, $address, $city, 
                           $latitude, $longitude, $capacityMin, $capacityMax, $pricePerHour, $mainImage);

        if ($hallId) {

            if (!empty($selectedAmenities)) {
                updateHallAmenities($hallId, $selectedAmenities);
            }

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

            setMessage('success', 'Hall added successfully! It will be visible after admin approval.');
            header('Location: manage.php');
            exit();
        } else {
            $errors[] = 'Failed to create hall. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Hall - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&libraries=places"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="content-wrapper">
        <div class="form-container">
            <h1>Add New Wedding Hall</h1>

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
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" required rows="5"><?php 
                            echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                        ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Location</h3>

                    <div class="form-group">
                        <label for="address">Address:</label>
                        <input type="text" id="address" name="address" required 
                               value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="city">City:</label>
                        <input type="text" id="city" name="city" required 
                               value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                    </div>

                    <input type="hidden" id="latitude" name="latitude" required>
                    <input type="hidden" id="longitude" name="longitude" required>

                    <div id="map" style="height: 300px; margin-bottom: 20px;"></div>
                </div>

                <div class="form-section">
                    <h3>Capacity & Pricing</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="capacity_min">Minimum Capacity:</label>
                            <input type="number" id="capacity_min" name="capacity_min" required min="1" 
                                   value="<?php echo isset($_POST['capacity_min']) ? (int)$_POST['capacity_min'] : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="capacity_max">Maximum Capacity:</label>
                            <input type="number" id="capacity_max" name="capacity_max" required min="1" 
                                   value="<?php echo isset($_POST['capacity_max']) ? (int)$_POST['capacity_max'] : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="price_per_hour">Price per Hour ($):</label>
                        <input type="number" id="price_per_hour" name="price_per_hour" required min="0" step="0.01" 
                               value="<?php echo isset($_POST['price_per_hour']) ? (float)$_POST['price_per_hour'] : ''; ?>">
                    </div>
                </div>

                <div class="form-section">
                    <h3>Amenities</h3>
                    <div class="amenities-grid">
                        <?php foreach ($amenities as $amenity): ?>
                            <div class="amenity-checkbox">
                                <input type="checkbox" id="amenity_<?php echo $amenity['id']; ?>" 
                                       name="amenities[]" value="<?php echo $amenity['id']; ?>"
                                       <?php echo isset($_POST['amenities']) && in_array($amenity['id'], $_POST['amenities']) ? 'checked' : ''; ?>>
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
                        <label for="main_image">Main Image:</label>
                        <input type="file" id="main_image" name="main_image" required accept="image/*" 
                               onchange="previewImage(this, 'main-image-preview')">
                        <img id="main-image-preview" class="image-preview" style="display: none;">
                    </div>

                    <div class="form-group">
                        <label for="additional_images">Additional Images:</label>
                        <input type="file" id="additional_images" name="additional_images[]" multiple accept="image/*" 
                               onchange="previewMultipleImages(this, 'additional-images-preview')">
                        <div id="additional-images-preview" class="images-preview-grid"></div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Hall</button>
                    <a href="manage.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        let map, marker;

        function initMap() {

            const defaultLocation = { lat: 31.9539, lng: 35.9106 };

            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 12,
                center: defaultLocation
            });

            marker = new google.maps.Marker({
                position: defaultLocation,
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