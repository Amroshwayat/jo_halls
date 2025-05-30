<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Find Your Perfect Wedding Venue</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_MAPS_API_KEY; ?>&libraries=places"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h1>Find Your Perfect Wedding Venue</h1>
                
            </div>

            <div class="scroll-indicator">
                <span>Scroll to explore</span>
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>

        <section class="featured-halls">
            <div class="section-header">
                <h2>Featured Wedding Venues</h2>
                <p>Discover our handpicked selection of stunning venues</p>
            </div>

            <div class="swiper featured-swiper">
                <div class="swiper-wrapper">
                    <?php
                    $featuredHalls = getFeaturedHalls(6);
                    foreach ($featuredHalls as $hall):
                        $rating = getAverageRating($hall['id']);
                        $amenities = getHallAmenities($hall['id']);
                    ?>
                    <div class="swiper-slide">
                        <div class="hall-card" data-hall-id="<?php echo $hall['id']; ?>">
                            <div class="hall-image">
                                <img src="<?php echo htmlspecialchars($hall['main_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($hall['name']); ?>">
                                <div class="hall-price">
                                    From $<?php echo number_format($hall['price_per_hour']); ?>/hour
                                </div>
                            </div>
                            <div class="hall-info">
                                <h3><?php echo htmlspecialchars($hall['name']); ?></h3>
                                <div class="hall-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $rating ? ' active' : ''; ?>"></i>
                                    <?php endfor; ?>
                                    <span>(<?php echo getReviewCount($hall['id']); ?> reviews)</span>
                                </div>
                                <div class="hall-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($hall['city']); ?>
                                </div>
                                <div class="hall-capacity">
                                    <i class="fas fa-users"></i>
                                    <?php echo $hall['capacity_min']; ?> - <?php echo $hall['capacity_max']; ?> guests
                                </div>
                                <div class="hall-amenities">
                                    <?php foreach(array_slice($amenities, 0, 4) as $amenity): ?>
                                        <span class="amenity-tag">
                                            <i class="<?php echo $amenity['icon']; ?>"></i>
                                            <?php echo htmlspecialchars($amenity['name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if(count($amenities) > 4): ?>
                                        <span class="amenity-tag more">+<?php echo count($amenities) - 4; ?> more</span>
                                    <?php endif; ?>
                                </div>
                                <a href="halls/view.php?id=<?php echo $hall['id']; ?>" class="btn-view">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </section>

        <?php if(isset($_SESSION['user_id'])): ?>
        <section class="ai-recommendations">
            <div class="section-header">
                <h2>Recommended for You</h2>
                <p>Personalized suggestions based on your preferences</p>
            </div>
            
        </section>
        <?php endif; ?>

     

        <section class="popular-locations">
            <div class="section-header">
                <h2>Popular Locations</h2>
                <p>Explore venues in top wedding destinations</p>
            </div>
            <div class="locations-grid">
                <?php
                $popularLocations = getPopularLocations();
                foreach($popularLocations as $location):
                ?>
                <a href="halls/search.php?city=<?php echo urlencode($location['city']); ?>" 
                   class="location-card">
                    <img src="<?php echo SITE_URL . '/' . $location['image']; ?>" 
                         alt="<?php echo htmlspecialchars($location['city']); ?> Wedding Venues"
                         onerror="this.src='<?php echo SITE_URL; ?>/assets/images/cities/default.jpg'">
                    <div class="location-info">
                        <h3><?php echo htmlspecialchars($location['city']); ?></h3>
                        <p><?php echo $location['venue_count']; ?> venue<?php echo $location['venue_count'] != 1 ? 's' : ''; ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="testimonials">
            <div class="section-header">
                <h2>What Couples Say</h2>
                <p>Real experiences from happy couples</p>
            </div>
            <div class="swiper testimonials-swiper">
                <div class="swiper-wrapper">
                    <?php
                    $testimonials = getTestimonials(5);
                    foreach($testimonials as $testimonial):
                    ?>
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <div class="testimonial-content">
                               
                                <div class="testimonial-author">
                                    <h4><?php echo htmlspecialchars($testimonial['couple_names']); ?></h4>
                                    <p><?php echo htmlspecialchars($testimonial['venue_name'] ?? ''); ?></p>
 <p><?php echo htmlspecialchars($testimonial['review_text']); ?></p>
                                    <div class="rating">
                                        <?php for($i = 0; $i < $testimonial['rating']; $i++): ?>
                                            <i class="fas fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </section>

        <section class="trust-indicators">
            <div class="trust-grid">
                <div class="trust-item">
                    <i class="fas fa-heart"></i>
                    <h3><?php echo getSuccessfulBookingsCount(); ?>+</h3>
                    <p>Happy Couples</p>
                </div>
                <div class="trust-item">
                    <i class="fas fa-building"></i>
                    <h3><?php echo getVenueCount(); ?>+</h3>
                    <p>Wedding Venues</p>
                </div>
                <div class="trust-item">
                    <i class="fas fa-star"></i>
                    <h3><?php echo getAverageRatingOverall(); ?></h3>
                    <p>Average Rating</p>
                </div>
                <div class="trust-item">
                    <i class="fas fa-users"></i>
                    <h3><?php echo getUserCount(); ?>+</h3>
                    <p>Active Users</p>
                </div>
            </div>
        </section>

        

      
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
    <script>
        const featuredSwiper = new Swiper('.featured-swiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                640: {
                    slidesPerView: 2,
                },
                1024: {
                    slidesPerView: 3,
                },
            },
            autoplay: {
                delay: 5000,
            },
        });

        const testimonialsSwiper = new Swiper('.testimonials-swiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            autoplay: {
                delay: 6000,
            },
        });

        const locationInput = document.getElementById('location-search');
        const autocomplete = new google.maps.places.Autocomplete(locationInput, {
            types: ['(cities)'],
            componentRestrictions: { country: 'JO' }
        });

        document.querySelector('.scroll-indicator').addEventListener('click', () => {
            document.querySelector('.featured-halls').scrollIntoView({ 
                behavior: 'smooth' 
            });
        });

       

      
    </script>
</body>
</html>