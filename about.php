<?php
require_once 'includes/config.php';
$pageTitle = 'About Us';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(120deg, #f8fafc 0%, #f5f6fa 100%);
        }
        .about-hero {
            background: linear-gradient(90deg, #ff4b7d 0%, #ffb3d6 100%);
            color: #fff;
            border-radius: 18px;
            box-shadow: 0 6px 32px rgba(255,75,125,0.10);
            padding: 2.5rem 2rem 2rem 2rem;
            text-align: center;
            margin: 40px auto 32px auto;
            max-width: 900px;
            position: relative;
            overflow: hidden;
        }
        .about-hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }
        .about-hero p {
            font-size: 1.25rem;
            opacity: 0.95;
            margin-bottom: 0.5rem;
        }
        .about-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(80,80,120,0.07);
            padding: 2.2rem 2rem 2rem 2rem;
            max-width: 900px;
            margin: 0 auto 40px auto;
        }
        .about-section {
            margin-bottom: 2.2rem;
        }
        .about-section:last-child {
            margin-bottom: 0;
        }
        .about-section h4 {
            color: #ff4b7d;
            font-weight: 700;
            margin-bottom: 0.7rem;
            font-size: 1.18rem;
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .about-section ul {
            padding-left: 1.2rem;
            margin-bottom: 0;
        }
        .about-section ul li {
            margin-bottom: 0.5rem;
            font-size: 1.08rem;
        }
        .about-team {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-top: 1.2rem;
        }
        .about-team-img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 2px 8px rgba(255,75,125,0.10);
            border: 3px solid #ffb3d6;
        }
        .about-contact {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.2rem 1rem;
            margin-top: 1.2rem;
            font-size: 1.08rem;
        }
        .about-contact b {
            color: #ff4b7d;
        }
        .about-img-wrap {
            text-align: center;
            margin-top: 2.5rem;
        }
        .about-img-wrap img {
            max-width: 320px;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(255,75,125,0.10);
        }
        @media (max-width: 700px) {
            .about-hero, .about-card { padding: 1.2rem 0.7rem 1.2rem 0.7rem; }
            .about-img-wrap img { max-width: 98vw; }
        }
    </style>
</head>
<body>
<?php require_once 'includes/header.php'; ?>

<div class="about-hero">
    <h1><i class="fas fa-heart"></i> About Jo Halls</h1>
    <p>Jo Halls is your trusted partner for finding and booking the perfect wedding and event venues in Jordan.</p>
    <p style="font-size:1.08rem;opacity:0.85;">Making your special occasions easier, more joyful, and truly memorable.</p>
</div>

<div class="about-card">
    <div class="about-section">
        <h4><i class="fas fa-bullseye"></i> Our Mission</h4>
        <p>
            We aim to make event planning easy and joyful by connecting customers with the best halls, providing real availability, transparent pricing, and genuine reviews. Whether you are planning a wedding, engagement, or any special occasion, Jo Halls is here to help you every step of the way.
        </p>
    </div>
    <div class="about-section">
        <h4><i class="fas fa-star"></i> Why Choose Us?</h4>
        <ul>
            <li><i class="fas fa-search"></i> Easy and fast venue search and booking</li>
            <li><i class="fas fa-check-circle"></i> Verified reviews from real customers</li>
            <li><i class="fas fa-comments"></i> Direct communication with hall owners</li>
            <li><i class="fas fa-shield-alt"></i> Secure and transparent process</li>
            <li><i class="fas fa-headset"></i> Local support and up-to-date information</li>
        </ul>
    </div>
    <div class="about-section">
        <h4><i class="fas fa-users"></i> Meet Our Team</h4>
        <div class="about-team">
            <img src="images\t2.jpg" alt="Team Member" class="about-team-img" loading="lazy">
            <div>
                <b>Our team</b> consists of passionate professionals with experience in event management, hospitality, and technology.<br>
                We are dedicated to making your event unforgettable!
            </div>
        </div>
    </div>
    <div class="about-section">
        <h4><i class="fas fa-envelope"></i> Contact Us</h4>
        <div class="about-contact">
            Have questions or need help? <br>
            <b>Email:</b> <a href="mailto:JoHalls@gmail.com">JoHalls@gmail.com</a><br>
            <b>Phone:</b> <a href="tel:0788207386">0788207386</a><br>
            Or leave us your feedback <a href="/customer/feedback.php">here</a>.
        </div>
    </div>
    
</div>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
