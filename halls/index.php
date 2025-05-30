<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$city = isset($_GET['city']) ? sanitizeInput($_GET['city']) : '';
$minCapacity = isset($_GET['min_capacity']) ? (int)$_GET['min_capacity'] : null;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;

$halls = searchHalls($query, $city, $minCapacity, $maxPrice);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Halls - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&libraries=places"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="content-wrapper">
        <div class="search-filters">
            <form method="GET" action="" class="search-form">
                <div class="form-group">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" 
                           placeholder="Search halls..." id="search-input">
                </div>

                <div class="form-group">
                    <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>" 
                           placeholder="City" id="city-input">
                </div>

                <div class="form-group">
                    <input type="number" name="min_capacity" value="<?php echo $minCapacity; ?>" 
                           placeholder="Minimum Capacity">
                </div>

                <div class="form-group">
                    <input type="number" name="max_price" value="<?php echo $maxPrice; ?>" 
                           placeholder="Maximum Price per Hour">
                </div>

                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="view-toggle">
            <button onclick="toggleView('grid')" class="active">Grid View</button>
            <button onclick="toggleView('map')">Map View</button>
        </div>

        <div id="grid-view" class="halls-grid">
            <?php if (empty($halls)): ?>
                <div class="no-results">
                    <p>No halls found matching your criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($halls as $hall): ?>
                    <div class="hall-card">
                        <div class="hall-image">
                            <img src="../<?php echo htmlspecialchars($hall['main_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($hall['name']); ?>">
                            <?php if ($hall['is_featured']): ?>
                                <span class="featured-badge">Featured</span>
                            <?php endif; ?>
                        </div>

                        <div class="hall-info">
                            <h3><?php echo htmlspecialchars($hall['name']); ?></h3>
                            <p class="location">
                                <i class="fa fa-map-marker"></i>
                                <?php echo htmlspecialchars($hall['city']); ?>
                            </p>
                            <p class="capacity">
                                <i class="fa fa-users"></i>
                                <?php echo $hall['capacity_min']; ?> - <?php echo $hall['capacity_max']; ?> guests
                            </p>
                            <p class="price">
                                <i class="fa fa-tag"></i>
                                $<?php echo number_format($hall['price_per_hour'], 2); ?> per hour
                            </p>

                            <div class="hall-actions">
                                <a href="view.php?id=<?php echo $hall['id']; ?>" class="btn btn-primary">
                                    View Details
                                </a>
                                <?php if (isLoggedIn()): ?>
                                    <button onclick="toggleFavorite(<?php echo $hall['id']; ?>)" 
                                            class="btn btn-outline favorite-btn">
                                        <i class="fa fa-heart"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="map-view" class="map-container" style="display: none;">
            <div id="map" style="height: 600px;"></div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>

        function initMap() {
            const map = new google.maps.Map(document.getElementById('map'), {
                zoom: 12,
                center: { lat: 31.9539, lng: 35.9106 } 
            });

            const halls = <?php echo json_encode($halls); ?>;
            const bounds = new google.maps.LatLngBounds();
            const infoWindow = new google.maps.InfoWindow();

            halls.forEach(hall => {
                const position = {
                    lat: parseFloat(hall.latitude),
                    lng: parseFloat(hall.longitude)
                };

                const marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: hall.name
                });

                bounds.extend(position);

                const content = `
                    <div class="map-info-window">
                        <h3>${hall.name}</h3>
                        <p>${hall.address}</p>
                        <p>Capacity: ${hall.capacity_min} - ${hall.capacity_max} guests</p>
                        <p>Price: $${hall.price_per_hour} per hour</p>
                        <a href="view.php?id=${hall.id}" class="btn btn-primary">View Details</a>
                    </div>
                `;

                marker.addListener('click', () => {
                    infoWindow.setContent(content);
                    infoWindow.open(map, marker);
                });
            });

            if (halls.length > 0) {
                map.fitBounds(bounds);
            }
        }

        function toggleView(view) {
            const gridView = document.getElementById('grid-view');
            const mapView = document.getElementById('map-view');
            const buttons = document.querySelectorAll('.view-toggle button');

            if (view === 'grid') {
                gridView.style.display = 'grid';
                mapView.style.display = 'none';
            } else {
                gridView.style.display = 'none';
                mapView.style.display = 'block';
                initMap();
            }

            buttons.forEach(button => {
                button.classList.toggle('active', 
                    button.textContent.toLowerCase().includes(view));
            });
        }

        document.addEventListener('DOMContentLoaded', function() {

            const cityInput = document.getElementById('city-input');
            new google.maps.places.Autocomplete(cityInput, {
                types: ['(cities)'],
                componentRestrictions: { country: 'JO' }
            });
        });
    </script>
</body>
</html>