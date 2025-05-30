<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$city = isset($_GET['city']) ? sanitizeInput($_GET['city']) : '';
$minCapacity = isset($_GET['min_capacity']) ? (int)$_GET['min_capacity'] : '';
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : '';
$amenities = isset($_GET['amenities']) && is_array($_GET['amenities']) ? array_map('intval', $_GET['amenities']) : [];
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'rating';

$cities = getAllCities();

$allAmenities = getAmenities();

$halls = searchHalls($city, $minCapacity, $maxPrice, $amenities, $sortBy);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$compareHalls = isset($_SESSION['compare_halls']) ? $_SESSION['compare_halls'] : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Wedding Halls - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="search-page">
        <!-- Search Filters -->
        <aside class="search-filters">
            <div class="filters-header">
                <h2>Filters</h2>
                <button onclick="resetFilters()" class="btn-reset">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>

            <form id="filterForm" method="GET" action="search.php">
                <!-- Location Filter -->
                <div class="filter-group">
                    <h3>Location</h3>
                    <select name="city" class="filter-select">
                        <option value="">All Cities</option>
                        <?php foreach ($cities as $cityOption): ?>
                        <option value="<?php echo htmlspecialchars($cityOption); ?>" 
                                <?php echo $city === $cityOption ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cityOption); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Capacity Filter -->
                <div class="filter-group">
                    <h3>Guest Capacity</h3>
                    <div class="capacity-slider">
                        <input type="range" id="capacityRange" name="min_capacity" 
                               min="0" max="1000" step="50" 
                               value="<?php echo $minCapacity ?: '0'; ?>"
                               oninput="updateCapacityValue(this.value)">
                        <div class="range-values">
                            <span>0</span>
                            <span id="capacityValue"><?php echo $minCapacity ?: '0'; ?></span>
                            <span>1000</span>
                        </div>
                    </div>
                </div>

                <!-- Price Filter -->
                <div class="filter-group">
                    <h3>Maximum Price per Hour</h3>
                    <div class="price-slider">
                        <input type="range" id="priceRange" name="max_price" 
                               min="0" max="500" step="10" 
                               value="<?php echo $maxPrice ?: '500'; ?>"
                               oninput="updatePriceValue(this.value)">
                        <div class="range-values">
                            <span>$0</span>
                            <span id="priceValue">$<?php echo $maxPrice ?: '500'; ?></span>
                            <span>$500</span>
                        </div>
                    </div>
                </div>

                <!-- Amenities Filter -->
                <div class="filter-group">
                    <h3>Amenities</h3>
                    <div class="amenities-grid">
                        <?php foreach ($allAmenities as $amenity): ?>
                        <label class="amenity-checkbox">
                            <input type="checkbox" name="amenities[]" 
                                   value="<?php echo $amenity['id']; ?>"
                                   <?php echo in_array($amenity['id'], $amenities) ? 'checked' : ''; ?>>
                            <i class="fas <?php echo $amenity['icon']; ?>"></i>
                            <?php echo htmlspecialchars($amenity['name']); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sort Options -->
                <div class="filter-group">
                    <h3>Sort By</h3>
                    <select name="sort" class="filter-select">
                        <option value="rating" <?php echo $sortBy === 'rating' ? 'selected' : ''; ?>>
                            Best Rating
                        </option>
                        <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>
                            Price: Low to High
                        </option>
                        <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>
                            Price: High to Low
                        </option>
                        <option value="capacity" <?php echo $sortBy === 'capacity' ? 'selected' : ''; ?>>
                            Capacity
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn-apply">Apply Filters</button>
            </form>
        </aside>

        <!-- Search Results -->
        <div class="search-results">
            <div class="results-header">
                <h1>Wedding Venues</h1>
                <div class="results-count">
                    <?php echo count($halls); ?> venues found
                </div>
            </div>

            <!-- Comparison Bar -->
            <div id="compareBar" class="compare-bar <?php echo !empty($compareHalls) ? 'active' : ''; ?>">
                <div class="compare-halls">
                    <?php foreach ($compareHalls as $compareHall): ?>
                    <div class="compare-item">
                        <img src="<?php echo SITE_URL . '/' . $compareHall['main_image']; ?>" 
                             alt="<?php echo htmlspecialchars($compareHall['name']); ?>">
                        <span><?php echo htmlspecialchars($compareHall['name']); ?></span>
                        <button onclick="removeFromCompare(<?php echo $compareHall['id']; ?>)" 
                                class="btn-remove">×</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($compareHalls) >= 2): ?>
                <a href="compare.php" class="btn-compare">Compare Venues</a>
                <?php endif; ?>
            </div>

            <!-- Halls Grid -->
            <div class="halls-grid">
                <?php if (empty($halls)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h2>No Venues Found</h2>
                    <p>Try adjusting your filters to find more venues</p>
                </div>
                <?php else: ?>
                    <?php foreach ($halls as $hall): ?>
                    <div class="hall-card">
                        <div class="hall-image">
                            <img src="<?php echo SITE_URL . '/' . $hall['main_image']; ?>" 
                                 alt="<?php echo htmlspecialchars($hall['name']); ?>">
                            <button onclick="toggleCompare(<?php echo $hall['id']; ?>)" 
                                    class="btn-add-compare <?php echo in_array($hall['id'], array_column($compareHalls, 'id')) ? 'active' : ''; ?>">
                                <i class="fas fa-balance-scale"></i>
                                Compare
                            </button>
                        </div>
                        <div class="hall-info">
                            <h3><?php echo htmlspecialchars($hall['name']); ?></h3>
                            <p class="hall-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($hall['city']); ?>
                            </p>
                            <div class="hall-meta">
                                <div class="meta-item">
                                    <i class="fas fa-users"></i>
                                    <?php echo $hall['capacity_min']; ?>-<?php echo $hall['capacity_max']; ?> guests
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-dollar-sign"></i>
                                    $<?php echo number_format($hall['price_per_hour'], 2); ?>/hour
                                </div>
                            </div>
                            <div class="hall-rating">
                                <?php $rating = getAverageRating($hall['id']); ?>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $rating ? 'filled' : ''; ?>"></i>
                                <?php endfor; ?>
                                <span>(<?php echo getReviewCount($hall['id']); ?>)</span>
                            </div>
                            <div class="hall-amenities">
                                <?php $hallAmenities = getHallAmenities($hall['id']); ?>
                                <?php foreach (array_slice($hallAmenities, 0, 4) as $amenity): ?>
                                <i class="fas <?php echo $amenity['icon']; ?>" 
                                   title="<?php echo htmlspecialchars($amenity['name']); ?>"></i>
                                <?php endforeach; ?>
                                <?php if (count($hallAmenities) > 4): ?>
                                <span>+<?php echo count($hallAmenities) - 4; ?> more</span>
                                <?php endif; ?>
                            </div>
                            <a href="view.php?id=<?php echo $hall['id']; ?>" class="btn-view">
                                View Details
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>

        function updateCapacityValue(value) {
            document.getElementById('capacityValue').textContent = value;
        }

        function updatePriceValue(value) {
            document.getElementById('priceValue').textContent = '$' + value;
        }

        function resetFilters() {
            document.getElementById('filterForm').reset();
            document.getElementById('capacityValue').textContent = '0';
            document.getElementById('priceValue').textContent = '$500';
        }

        function toggleCompare(hallId) {
            fetch(`../api/compare.php?hall_id=${hallId}`, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        function removeFromCompare(hallId) {
            fetch(`../api/compare.php?hall_id=${hallId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>