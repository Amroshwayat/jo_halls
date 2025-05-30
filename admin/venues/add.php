<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../../users/login.php');
    exit();
}

$currentPage = 'venues';
$pageTitle = 'Add New Venue';

// Get all hall owners
$owners = [];
$sqlOwners = "SELECT id, first_name, last_name, email FROM users WHERE role = 'hall_owner' AND status = 'active' ORDER BY first_name, last_name";
$resOwners = $db->getConnection()->query($sqlOwners);
if ($resOwners) {
    $owners = $resOwners->fetch_all(MYSQLI_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_id = isset($_POST['owner_id']) ? (int)$_POST['owner_id'] : 0;
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
        // Handle main image upload
        $mainImage = '';
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $mainImage = uploadImage($_FILES['main_image'], 'uploads/venues');
            if (!$mainImage) {
                $errors[] = "Failed to upload main image";
            }
        }
        
        if (empty($errors)) {
            // Create venue
            $venueId = createHall($owner_id, $name, $description, $address, $city, 
                                $latitude, $longitude, $capacity_min, $capacity_max, $price_per_hour, $mainImage);
            
            if ($venueId) {
                // Add amenities
                if (!empty($amenities)) {
                    updateHallAmenities($venueId, $amenities);
                }
                
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
                $errors[] = "Failed to create venue";
            }
        }
    }
}

// Get all amenities
$sql = "SELECT * FROM amenities ORDER BY name";
$result = $db->getConnection()->query($sql);
$amenities = $result->fetch_all(MYSQLI_ASSOC);

require_once '../includes/admin_header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-plus-circle"></i> Add New Venue</h1>
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

<style>
.amenities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px 24px;
    margin-top: 8px;
    margin-bottom: 10px;
}
.custom-control.custom-checkbox {
    padding-left: 1.7em;
    font-size: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    padding: 8px 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    display: flex;
    align-items: center;
}
.custom-control-label {
    cursor: pointer;
    font-weight: 500;
    color: #333;
}
.form-row {
    margin-bottom: 2px !important;
}
#map {
    margin-bottom: 2px !important;
    height: 120px !important;
    min-height: 60px !important;
}
</style>

<div class="content-body">
    <form method="POST" enctype="multipart/form-data" class="venue-form">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="owner_id">Venue Owner *</label>
                <select class="form-control" id="owner_id" name="owner_id" required>
                    <option value="">-- Select Owner --</option>
                    <?php foreach ($owners as $owner): ?>
                        <option value="<?= $owner['id'] ?>" <?= (isset($_POST['owner_id']) && $_POST['owner_id'] == $owner['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($owner['first_name'] . ' ' . $owner['last_name'] . ' (' . $owner['email'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="city">City *</label>
                <input type="text" class="form-control" id="city" name="city" required
                       value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="name">Venue Name *</label>
                <input type="text" class="form-control" id="name" name="name" required
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
            <div class="form-group col-md-6">
                <label for="address">Address *</label>
                <input type="text" class="form-control" id="address" name="address" required
                       value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description *</label>
            <textarea class="form-control" id="description" name="description" rows="4" required><?php 
                echo htmlspecialchars($_POST['description'] ?? ''); 
            ?></textarea>
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
                               if (isset($_POST['amenities']) && 
                                   in_array($amenity['id'], $_POST['amenities'])) echo 'checked'; 
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
            <label for="main_image">Main Image *</label>
            <div class="custom-file">
                <input type="file" class="custom-file-input" id="main_image" 
                       name="main_image" accept="image/*" required>
                <label class="custom-file-label" for="main_image">Choose file</label>
            </div>
        </div>

        <div class="form-group">
            <label for="additional_images">Additional Images</label>
            <div class="custom-file">
                <input type="file" class="custom-file-input" id="additional_images" 
                       name="additional_images[]" accept="image/*" multiple>
                <label class="custom-file-label" for="additional_images">Choose files</label>
            </div>
            <small class="form-text text-muted">You can select multiple images</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Venue
            </button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php 
$extraScripts = '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=' . GOOGLE_MAPS_API_KEY . '&libraries=places"></script>
<script>
$(document).ready(function() {
    // Initialize map
    const map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: 31.9539, lng: 35.9106 }, // Amman, Jordan
        zoom: 8
    });
    
    let marker = null;
    
    // Add click listener to map
    map.addListener("click", (event) => {
        const lat = event.latLng.lat();
        const lng = event.latLng.lng();
        
        // Update form fields
        $("#latitude").val(lat);
        $("#longitude").val(lng);
        
        // Update or create marker
        if (marker) {
            marker.setPosition(event.latLng);
        } else {
            marker = new google.maps.Marker({
                position: event.latLng,
                map: map,
                draggable: true
            });
            
            // Add drag end listener to marker
            marker.addListener("dragend", (event) => {
                const lat = event.latLng.lat();
                const lng = event.latLng.lng();
                $("#latitude").val(lat);
                $("#longitude").val(lng);
            });
        }
    });
    
    // Initialize Places Autocomplete
    const addressInput = document.getElementById("address");
    const autocomplete = new google.maps.places.Autocomplete(addressInput, {
        componentRestrictions: { country: "JO" }
    });
    
    autocomplete.addListener("place_changed", () => {
        const place = autocomplete.getPlace();
        if (place.geometry) {
            const lat = place.geometry.location.lat();
            const lng = place.geometry.location.lng();
            
            // Update form fields
            $("#latitude").val(lat);
            $("#longitude").val(lng);
            $("#city").val(place.vicinity);
            
            // Update map
            map.setCenter(place.geometry.location);
            map.setZoom(15);
            
            // Update or create marker
            if (marker) {
                marker.setPosition(place.geometry.location);
            } else {
                marker = new google.maps.Marker({
                    position: place.geometry.location,
                    map: map,
                    draggable: true
                });
                
                marker.addListener("dragend", (event) => {
                    const lat = event.latLng.lat();
                    const lng = event.latLng.lng();
                    $("#latitude").val(lat);
                    $("#longitude").val(lng);
                });
            }
        }
    });
    
    // Handle file inputs
    $(".custom-file-input").on("change", function() {
        let fileName = $(this).val().split("\\").pop();
        if (this.files.length > 1) {
            fileName = this.files.length + " files selected";
        }
        $(this).next(".custom-file-label").html(fileName);
    });
});
</script>';

require_once '../includes/admin_footer.php';
?>
