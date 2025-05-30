<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get unique halls by their ID
$compareHallsRaw = isset($_SESSION['compare_halls']) ? $_SESSION['compare_halls'] : [];
$uniqueHalls = [];
$seenIds = [];

foreach ($compareHallsRaw as $hall) {
    if (!in_array($hall['id'], $seenIds)) {
        $uniqueHalls[] = $hall;
        $seenIds[] = $hall['id'];
    }
    if (count($uniqueHalls) === 2) {
        break;
    }
}

// If less than two unique halls, redirect
if (count($uniqueHalls) < 2) {
    header('Location: search.php');
    exit;
}

// Fetch additional data for each hall
foreach ($uniqueHalls as &$hall) {
    $hall['rating'] = getAverageRating($hall['id']);
    $hall['reviews'] = getReviewCount($hall['id']);
    $hall['bookings'] = getBookingCount($hall['id']);
    $hall['amenities'] = getHallAmenities($hall['id']);
    $hall['availability'] = getHallAvailability($hall['id']);
}
unset($hall); // break reference

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compare Halls - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <main class="compare-page">
        <div class="container">
            <div class="compare-header">
                <h1>Compare Wedding Venues</h1>
                <a href="search.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Search
                </a>
            </div>

            <div class="compare-table">
                <table>
                    <!-- Basic Information -->
                    <tr class="section-header">
                        <th></th>
                        <?php foreach ($uniqueHalls as $hall): ?>
                        <th>
                            <div class="hall-header">
                                <img src="<?php echo SITE_URL . '/' . $hall['main_image']; ?>" 
                                     alt="<?php echo htmlspecialchars($hall['name']); ?>">
                                <h2><?php echo htmlspecialchars($hall['name']); ?></h2>
                            </div>
                        </th>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Location -->
                    <tr class="section-header">
                        <td colspan="3">Location</td>
                    </tr>
                    <tr>
                        <td>City</td>
                        <?php foreach ($uniqueHalls as $hall): ?>
                        <td><?php echo htmlspecialchars($hall['city']); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Address</td>
                        <?php foreach ($uniqueHalls as $hall): ?>
                        <td><?php echo htmlspecialchars($hall['address']); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Capacity -->
                    <tr class="section-header">
                        <td colspan="3">Capacity</td>
                    </tr>
                    <tr>
                        <td>Minimum Guests</td>
                        <?php foreach ($uniqueHalls as $hall): ?>
                        <td><?php echo $hall['capacity_min']; ?> guests</td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Maximum Guests</td>
                        <?php foreach ($uniqueHalls as $hall): ?>
                        <td><?php echo $hall['capacity_max']; ?> guests</td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Pricing -->
                    <tr class="section-header">
                        <td colspan="3">Pricing</td>
                    </tr>
                    <tr>
                        <td>Price per Hour</td>
                        <?php foreach ($uniqueHalls as $hall): ?>
                        <td>$<?php echo number_format($hall['price_per_hour'], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Ratings & Reviews -->
                    <tr class="section-header">
                        <td colspan="3">Ratings & Reviews</td>
                    </tr>
                    <tr>
                        <td>Rating</td>
                        <?php foreach ($uniqueHalls as $hall): ?>
                        <td class="rating-cell">
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $hall['rating'] ? 'filled' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span>(<?php echo $hall['rating']; ?>/5)</span>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Total Reviews</td>
                        <?php foreach ($uniqueHalls as $hall): ?>
                        <td><?php echo $hall['reviews']; ?> reviews</td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Successful Events</td>
                        <?php foreach ($uniqueHalls as $hall): ?>
                        <td><?php echo $hall['bookings']; ?> events</td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Amenities -->
                    <tr class="section-header">
                        <td colspan="3">Amenities</td>
                    </tr>
                    <tr>
                        <td>Available Amenities</td>
                        <?php foreach ($uniqueHalls as $hall): ?>
                        <td>
                            <div class="amenities-grid">
                                <?php foreach ($hall['amenities'] as $amenity): ?>
                                <div class="amenity-item">
                                    <i class="fas <?php echo $amenity['icon']; ?>" 
                                       title="<?php echo htmlspecialchars($amenity['name']); ?>"></i>
                                    <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Availability -->
                    <tr class="section-header">
                        <td colspan="3">Availability</td>
                    </tr>
                    <tr>
                        <td>Operating Hours</td>
                        <?php foreach ($uniqueHalls as $hall): ?>
                        <td>
                            <div class="availability-grid">
                                <?php foreach ($hall['availability'] as $time): ?>
                                <div class="time-slot">
                                    <strong><?php echo getDayName($time['day_of_week']); ?></strong>
                                    <span>
                                        <?php echo formatTime($time['start_time']); ?> - 
                                        <?php echo formatTime($time['end_time']); ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Actions -->
                    <tr class="section-header">
                        <td colspan="3">Actions</td>
                    </tr>
                    <tr>
                        <td></td>
                        <?php foreach ($uniqueHalls as $hall): ?>
                        <td>
                            <div class="action-buttons">
                                <a href="view.php?id=<?php echo $hall['id']; ?>" class="btn-view">
                                    View Details
                                </a>
                                <?php if (isLoggedIn()): ?>
                                <a href="/customer/reserve.php?id=<?php echo $hall['id']; ?>" class="btn-book">
                                    Book Now
                                </a>
                                <?php else: ?>
                                <a href="../users/login.php?redirect=halls/booking.php?id=<?php echo $hall['id']; ?>" 
                                   class="btn-book">
                                    Login to Book
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </table>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>