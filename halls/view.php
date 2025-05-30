<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$hallId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$hall = getHallById($hallId);
if (!$hall) {
    header('Location: ' . SITE_URL . '/halls/index.php');
    exit();
}

$phoneNumber = $hall['phone']; 


$whatsappLink = "https://wa.me/$phoneNumber";


$images = getHallImages($hallId);

$amenities = getHallAmenities($hallId);

$reviews = getHallReviews($hallId);

$availability = getHallAvailability($hallId);

$averageRating = getAverageRating($hallId);
$reviewCount = getReviewCount($hallId);
$bookingCount = getBookingCount($hallId);

include_once '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hall['name']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <!-- Hero Section with Image Gallery -->
    <div class="hall-hero">
        <div class="swiper hall-gallery">
            <div class="swiper-wrapper">
           

                <?php foreach ($images as $image): ?>
                <div class="swiper-slide">
                    <img src="<?php echo SITE_URL . '/' . htmlspecialchars($image['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($hall['name']); ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </div>

    <div class="content-wrapper">
        <!-- Quick Info Bar -->
        <div class="quick-info-bar">
            <div class="info-item">
                <i class="fas fa-star"></i>
                <span><?php echo number_format($averageRating, 1); ?> (<?php echo $reviewCount; ?> reviews)</span>
            </div>
            <div class="info-item">
                <i class="fas fa-users"></i>
                <span><?php echo $hall['capacity_min']; ?>-<?php echo $hall['capacity_max']; ?> guests</span>
            </div>
            <div class="info-item">
                <i class="fas fa-calendar-check"></i>
                <span><?php echo $bookingCount; ?> successful events</span>
            </div>
            <div class="info-item">
                <i class="fas fa-dollar-sign"></i>
                <span><?php echo number_format($hall['price_per_hour'], 2); ?>/hour</span>
            </div>
        </div>

        <div class="hall-content">
            <!-- Left Column -->
            <div class="hall-main">
                <section class="hall-description">
                    <h1><?php echo htmlspecialchars($hall['name']); ?></h1>
                    <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hall['address']); ?>, <?php echo htmlspecialchars($hall['city']); ?></p>
                    <div class="description-content">
                        <?php echo nl2br(htmlspecialchars($hall['description'])); ?>
                    </div>
                </section>

                <section class="hall-amenities">
                    <h2>Amenities & Features</h2>
                    <div class="amenities-grid">
                        <?php foreach ($amenities as $amenity): ?>
                        <div class="amenity-item">
                            <i class="fas <?php echo htmlspecialchars($amenity['icon']); ?>"></i>
                            <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="hall-availability">
                    <h2>Available Times</h2>
                    <br>
                    <div class="availability-grid">
                        <?php foreach ($availability as $time): ?>
                        <div class="availability-item">
                            <div class="day"><?php echo getDayName($time['day_of_week']); ?></div>
                            <div class="time">
                                <?php echo formatTime($time['start_time']); ?> - 
                                <?php echo formatTime($time['end_time']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="hall-reviews">
                    <h2>Customer Reviews</h2>
                    <div class="reviews-summary">
                        <div class="rating-big">
                            <span class="rating-number"><?php echo number_format($averageRating, 1); ?></span>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $averageRating ? 'filled' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-count"><?php echo $reviewCount; ?> reviews</span>
                        </div>
                    </div>
                    <div class="reviews-grid">
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <h4><?php echo htmlspecialchars($review['couple_names']); ?></h4>
                                    <div class="review-date">
                                        <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="review-content">
                                <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn-book-now">
                    <a style="text-decoration: none; color: white"; href="<?= SITE_URL ?>/customer/feedback.php?id=<?php echo $hall['id']; ?>">Submit Feedback</a></button> 
                </section>

                <section class="hall-location">
                    <h2>Location & Directions</h2>
                    <div class="location-info">
                        <div class="location-details">
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hall['address']); ?></p>
                            <p><i class="fas fa-city"></i> <?php echo htmlspecialchars($hall['city']); ?></p>
                            <div class="location-actions">
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($hall['latitude'] . ',' . $hall['longitude']); ?>" 
                                   class="btn-directions" target="_blank">
                                    <i class="fas fa-directions"></i> Get Directions
                                </a>
                                <button class="btn-copy-address" onclick="copyAddress('<?php echo addslashes($hall['address']); ?>')">
                                    <i class="fas fa-copy"></i> Copy Address
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="map"></div>
                </section>
            </div>
 <!-- <form method="POST" action="jo_halls\customer\reserve.php" class="booking-form">
                    jo_halls\customer\reserve.php -->
                    <!-- </form> -->
            <!-- Right Column -->
            <div class="hall-sidebar">
                <div class="booking-card">
                    <h3>Book This Venue</h3>
                    <?php if (isLoggedIn()): ?>
                   
                        <a href="<?= SITE_URL ?>/customer/reserve.php?id=<?php echo $hall['id']; ?>" class="btn-book-now" style="text-decoration: none; color: white; padding: 10px 20px; background-color: #ff4b7d; border-radius: 5px; display: inline-block; text-align: center;">
    Book Now
</a>
   
                    
                    <?php else: ?>
                    <div class="login-prompt">
                        <p>Please log in to book this venue</p>
                        <a href="<?php echo SITE_URL; ?>/users/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" 
                           class="btn-login">Log In to Book</a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="contact-owner">
                    <h3>Contact Hall Owner</h3>
                    <div class="owner-info">


                        <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($hall['first_name'] . ' ' . $hall['last_name']); ?></p>
                        <p><i class="fas fa-phone"></i><a style="text-decoration: none;color:#444450 "; href="tel:$phoneNumber" target="_blank"><?php echo htmlspecialchars($phoneNumber); ?></a></p>
                     <p><i class="fab fa-whatsapp"></i><a style="text-decoration: none;color:#444450 "; href="<?= $whatsappLink ?>" target="_blank">Chat on WhatsApp</a></p>   
                     <a  style="text-decoration: none;" href="mailto:<?php echo htmlspecialchars($hall['email']); ?>"> <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($hall['email']); ?></p></a>  
          

            </div>
        </div>
    </div>

   

    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <script>

        const swiper = new Swiper('.hall-gallery', {
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev'
            }
        });

        function initMap() {
            const location = {
                lat: <?php echo $hall['latitude']; ?>,
                lng: <?php echo $hall['longitude']; ?>
            };

            const mapOptions = {
                zoom: 15,
                center: location,
                mapTypeControl: true,
                fullscreenControl: true,
                streetViewControl: true,
                styles: [
                    {
                        featureType: 'poi',
                        elementType: 'labels',
                        stylers: [{ visibility: 'on' }]
                    }
                ]
            };

            const map = new google.maps.Map(document.getElementById('map'), mapOptions);

            const marker = new google.maps.Marker({
                position: location,
                map: map,
                title: '<?php echo addslashes($hall['name']); ?>',
                animation: google.maps.Animation.DROP
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div class="map-info-window">
                        <h3><?php echo addslashes($hall['name']); ?></h3>
                        <p><?php echo addslashes($hall['address']); ?></p>
                        <p><strong>Capacity:</strong> <?php echo $hall['capacity_min']; ?>-<?php echo $hall['capacity_max']; ?> guests</p>
                    </div>
                `
            });

            marker.addListener('click', () => {
                infoWindow.open(map, marker);
            });
        }

        function copyAddress(address) {
            navigator.clipboard.writeText(address).then(() => {
                const btn = document.querySelector('.btn-copy-address');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            });
        }

        window.addEventListener('load', initMap);
    </script>
</body>
</html>