<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
$hallId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$hall = getHallById($hallId);
if (!isLoggedIn() || $_SESSION['user_role'] !== 'customer') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}
$pageTitle = 'Feedback';
require_once '../includes/header.php';

$user_id = $_SESSION['user_id'];

// جلب القاعات التي حجزها المستخدم فقط
$halls = [];
$stmt = $db->getConnection()->prepare("SELECT DISTINCT h.id, h.name FROM halls h JOIN bookings b ON h.id = b.hall_id WHERE b.user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $halls[] = $row;
}
$stmt->close();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_text'], $_POST['hall_id'], $_POST['rating'], $_POST['couple_names'])) {
    $hall_id = (int)$_POST['hall_id'];
    $review_text = trim($_POST['review_text']);
    $rating = (int)$_POST['rating'];
    $couple_names = trim($_POST['couple_names']);
    $venue_name = isset($_POST['venue_name']) ? trim($_POST['venue_name']) : '';

    if (strlen($review_text) < 5) {
        $error = 'Feedback must be at least 5 characters.';
    } elseif (!$hall_id) {
        $error = 'Please select a hall.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Please provide a rating between 1 and 5.';
    } else {
       
        $stmt = $db->getConnection()->prepare(
            "INSERT INTO reviews (hall_id, user_id, rating, review_text, status, couple_names, venue_name, created_at) 
             VALUES (?, ?, ?, ?, 'pending', ?, ?, NOW())"
        );
        $stmt->bind_param('iiisss', $hall_id, $user_id, $rating, $review_text, $couple_names, $venue_name);
        
        if ($stmt->execute()) {
            $success = true;
        } else {
            $error = 'Error saving feedback. Please try again.';
        }
        $stmt->close();
    }
}
?>
<div class="container mt-4" style="max-width: 600px; margin: 40px auto 0 auto; background: #fff; border-radius: 14px; box-shadow: 0 6px 32px rgba(0,0,0,0.07); padding: 2.3rem 2rem 2rem 2rem;">
    <h2 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.4rem; color: #ff4b7d; font-weight: bold;">Feedback</h2>
    <?php if ($success): ?>
        <div class="alert alert-success" style="font-size: 1rem; border-radius: 7px; margin-bottom: 1.2rem; text-align: center;">Thank you for your feedback!</div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger" style="font-size: 1rem; border-radius: 7px; margin-bottom: 1.2rem; text-align: center;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <div class="form-group" style="margin-bottom: 1.1rem;">
            <label for="hall_id" style="font-weight: 500; color: #333; margin-bottom: 0.35rem;">Hall</label>
          
            <h3>   <input type="hidden" name="hall_id" id="hall_id" value="<?= htmlspecialchars($hallId) ?>">
<p style="margin-top: 0.5rem; font-size: 1.05rem; color: #333;">
    <?= htmlspecialchars($hall['name']) ?> 
</p></h3> 
        </div>
        <div class="form-group" style="margin-bottom: 1.1rem;">
            <label for="couple_names" style="font-weight: 500; color: #333; margin-bottom: 0.35rem;">Couple Names</label>
            <input type="text" name="couple_names" id="couple_names" class="form-control" maxlength="100" required style="width: 100%; border-radius: 7px; border: 1.5px solid #e1e1e1; padding: 0.55rem 0.85rem; font-size: 1rem; margin-bottom: 0.2rem; transition: border 0.18s;">
        </div>
        <div class="form-group" style="margin-bottom: 1.1rem;">
            <label for="rating" style="font-weight: 500; color: #333; margin-bottom: 0.35rem;">Rating</label>
            <div id="star-rating" style="font-size:2.1rem; color: #FFD700; display: flex; gap: 0.25rem; align-items: center;">
                <input type="hidden" name="rating" id="rating" required>
                <span class="star" data-value="1" style="cursor:pointer; transition: transform 0.13s;"></span>
                <span class="star" data-value="2" style="cursor:pointer; transition: transform 0.13s;"></span>
                <span class="star" data-value="3" style="cursor:pointer; transition: transform 0.13s;"></span>
                <span class="star" data-value="4" style="cursor:pointer; transition: transform 0.13s;"></span>
                <span class="star" data-value="5" style="cursor:pointer; transition: transform 0.13s;"></span>
            </div>
        </div>
        <div class="form-group" style="margin-bottom: 1.1rem;">
            <label for="review_text" style="font-weight: 500; color: #333; margin-bottom: 0.35rem;">Your Feedback</label>
            <textarea name="review_text" id="review_text" class="form-control" rows="5" required minlength="5" style="width: 100%; border-radius: 7px; border: 1.5px solid #e1e1e1; padding: 0.55rem 0.85rem; font-size: 1rem; resize: vertical; transition: border 0.18s;"></textarea>
        </div>
        <input type="hidden" name="venue_name" value="<?= htmlspecialchars($hall['name']) ?>">

        <button type="submit" class="btn btn-primary" style="border-radius: 7px; font-weight: 600; font-size: 1rem; padding: 0.55rem 1.2rem; margin-top: 0.7rem; background: #ff4b7d; color: #fff; border: none; box-shadow: 0 2px 8px rgba(255,31,90,0.07); transition: background 0.14s, color 0.14s, transform 0.13s;">Submit</button>
    </form>
    <script>
        // Star rating logic
        const stars = document.querySelectorAll('#star-rating .star');
        const ratingInput = document.getElementById('rating');
        stars.forEach(star => {
            star.innerHTML = '&#9733;';
            star.addEventListener('click', function() {
                let value = this.getAttribute('data-value');
                ratingInput.value = value;
                stars.forEach(s => {
                    if (s.getAttribute('data-value') <= value) {
                        s.style.color = '#FFD700';
                        s.style.transform = 'scale(1.18)';
                    } else {
                        s.style.color = '#ccc';
                        s.style.transform = 'scale(1)';
                    }
                });
            });
        });
    </script>
</div>
