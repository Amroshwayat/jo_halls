<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
$hallId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$hall = getHallById($hallId);
if (!isLoggedIn() ) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}
$pageTitle = 'Reserve Venue';
require_once '../includes/header.php';
// AJAX: Get available slots for a hall and date
if (isset($_GET['ajax']) && $_GET['ajax'] === 'slots' && isset($_GET['hall_id'], $_GET['date'])) {
    ob_end_clean(); // Ensure NO whitespace, newlines, or output before the AJAX JSON header or echo
    header('Content-Type: application/json');
    $hall_id = (int)$_GET['hall_id'];
    $date = $_GET['date'];
    $dayOfWeek = (date('w', strtotime($date)) + 6) % 7;
    $slots = [];
    $stmt = $db->getConnection()->prepare("SELECT start_time, end_time FROM hall_availability WHERE hall_id = ? AND day_of_week = ? AND is_available = 1");
    $stmt->bind_param('ii', $hall_id, $dayOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $slots[] = $row;
    }
    $stmt->close();
    echo json_encode($slots);
    exit();
}

// Get all available halls
global $db;
$sql = "SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) as owner_name FROM halls h JOIN users u ON h.owner_id = u.id WHERE h.status = 'active'";
$result = $db->getConnection()->query($sql);
$halls = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Handle reservation form submission
$reservation_success = false;
$reservation_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hall_id'], $_POST['date'], $_POST['start_time'], $_POST['end_time'], $_POST['guests'])) {
    $hall_id = (int)$_POST['hall_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $guests = (int)$_POST['guests'];
    $user_id = $_SESSION['user_id'];
    // Check for conflicting bookings
    $stmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM bookings WHERE hall_id = ? AND event_date = ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))");
    $stmt->bind_param("isssssss", $hall_id, $date, $end_time, $start_time, $start_time, $end_time, $start_time, $end_time);
    $stmt->execute();
    $stmt->bind_result($conflict_count);
    $stmt->fetch();
    $stmt->close();
    if ($conflict_count > 0) {
        $reservation_error = 'This venue is already booked for the selected time.';
    } else {
        // حساب السعر
        $price_per_hour = 0;
        $min_guests = 1;
        $max_guests = 1000; // قيمة افتراضية، عدل حسب الحاجة
        
        foreach ($halls as $hallItem) {
            if ($hallItem['id'] == $hall_id) {
                $price_per_hour = $hallItem['price_per_hour'];
                $min_guests = $hallItem['capacity_min']; // يجب أن تكون الأعمدة موجودة في جدول halls
                $max_guests = $hallItem['capacity_max'];
                break;
            }
        }
        
        if ($guests < $min_guests || $guests > $max_guests) {
            $reservation_error = "The number of guests must be between $min_guests and $max_guests.";
        }
        
        $start_minutes = intval(substr($start_time,0,2))*60 + intval(substr($start_time,3,2));
        $end_minutes = intval(substr($end_time,0,2))*60 + intval(substr($end_time,3,2));
        $duration = ($end_minutes - $start_minutes) / 60;
        $total_price = $duration * $price_per_hour;
        // Insert booking
        $stmt = $db->getConnection()->prepare("INSERT INTO bookings (user_id, hall_id, event_date, start_time, end_time, guests, total_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iisssid", $user_id, $hall_id, $date, $start_time, $end_time, $guests, $total_price);
        if ($stmt->execute()) {
            $reservation_success = true;
        } else {
            $reservation_error = 'Error saving booking. Please try again.';
        }
        $stmt->close();
    }
}
?>
<div class="container mt-4" style="max-width: 820px; margin: 40px auto 0 auto; background: #fff; border-radius: 14px; box-shadow: 0 6px 32px rgba(0,0,0,0.07); padding: 2.5rem 2.2rem 2rem 2.2rem;">
    <h2 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.35rem; color: #ff4b7d; font-weight: bold;">Reserve a Venue</h2>
    <?php if ($reservation_success): ?>
        <div class="alert alert-success" style="font-size: 1rem; border-radius: 7px; margin-bottom: 1.2rem; text-align: center;">Your reservation has been submitted and is pending approval.</div>
    <?php elseif ($reservation_error): ?>
        <div class="alert alert-danger" style="font-size: 1rem; border-radius: 7px; margin-bottom: 1.2rem; text-align: center;"><?= htmlspecialchars($reservation_error) ?></div>
    <?php endif; ?>
    <form method="post" class="mb-4" id="reserveForm" onsubmit="return validateGuestCount();">

        <div class="form-row" style="display: flex; flex-wrap: wrap; gap: 1.1rem 2.2rem; margin-bottom: 1.1rem; justify-content: space-between;">
            <div class="form-group col-md-4" style="flex: 1 1 220px; min-width: 180px; margin-bottom: 0;">
                <label style="font-weight: 500; color: #333; margin-bottom: 0.3rem;">Venue</label>
                
            <h3>   <input type="hidden" name="hall_id" id="hall_id" value="<?= htmlspecialchars($hallId) ?>">
<p style="margin-top: 0.5rem; font-size: 1.05rem; color: #333;">
    <?= htmlspecialchars($hall['name']) ?> 
</p></h3> 

            
            </div>
            <div class="form-group col-md-4" style="flex: 1 1 180px; min-width: 140px; margin-bottom: 0;">
                <label style="font-weight: 500; color: #333; margin-bottom: 0.3rem;">Date</label>
                <input type="date" name="date" class="form-control" required id="date" style="width: 100%; border-radius: 7px; border: 1.5px solid #e1e1e1; padding: 0.55rem 0.85rem; font-size: 1rem; margin-bottom: 0.2rem; transition: border 0.18s;">
            </div>
            <div class="form-group col-md-4" style="flex: 1 1 140px; min-width: 120px; margin-bottom: 0;">
                <label style="font-weight: 500; color: #333; margin-bottom: 0.3rem;">Start Time</label>
                <select name="start_time" class="form-control" required id="start_time" style="width: 100%; border-radius: 7px; border: 1.5px solid #e1e1e1; padding: 0.55rem 0.85rem; font-size: 1rem; margin-bottom: 0.2rem; transition: border 0.18s;">
                    <option value="">Select</option>
                </select>
            </div>
            <div class="form-group col-md-4" style="flex: 1 1 140px; min-width: 120px; margin-bottom: 0;">
                <label style="font-weight: 500; color: #333; margin-bottom: 0.3rem;">End Time</label>
                <select name="end_time" class="form-control" required id="end_time" style="width: 100%; border-radius: 7px; border: 1.5px solid #e1e1e1; padding: 0.55rem 0.85rem; font-size: 1rem; margin-bottom: 0.2rem; transition: border 0.18s;">
                    <option value="">Select</option>
                </select>
            </div>
            <div class="form-group col-md-4" style="flex: 1 1 120px; min-width: 100px; margin-bottom: 0;">
                <label style="font-weight: 500; color: #333; margin-bottom: 0.3rem;">Guests</label>
                <input type="number" name="guests" class="form-control" min="1" required id="guests" style="width: 100%; border-radius: 7px; border: 1.5px solid #e1e1e1; padding: 0.55rem 0.85rem; font-size: 1rem; margin-bottom: 0.2rem; transition: border 0.18s;">
            </div>
            <div class="form-group col-md-2 align-self-end" style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary" style="border-radius: 7px; font-weight: 600; font-size: 1rem; padding: 0.55rem 1.2rem; background: #ff4b7d; color: #fff; border: none; box-shadow: 0 2px 8px rgba(255,31,90,0.07); transition: background 0.14s, color 0.14s, transform 0.13s;">Book</button>
            </div>
        </div>
        <div class="form-row" style="margin-top: 1.1rem;">
            <div class="form-group col-md-3" style="min-width: 180px;">
                <label style="font-weight: 500; color: #333; margin-bottom: 0.3rem;">Estimated Price</label>
                <input type="text" id="price_display" class="form-control" readonly style="width: 100%; border-radius: 7px; border: 1.5px solid #e1e1e1; padding: 0.55rem 0.85rem; font-size: 1rem; background: #f8f9fa;">
            </div>
        </div>
    </form>
    
 <a  href="<?php echo $base_url; ?>/halls/view.php?id=<?php echo $hallId ?>" <?php echo $current_page == 'search.php' ?> class="btn btn-secondary" style="border-radius: 7px; font-weight: 600; font-size: 1rem; padding: 0.55rem 1.3rem; margin-top: 0.7rem; background: #6c757d; color: #fff; margin-left: 0.5rem; box-shadow: 0 2px 8px rgba(255,31,90,0.07); transition: background 0.14s, color 0.14s, transform 0.13s;">Back</a>
</div>
<script>
      const PRICE_PER_HOUR = <?= isset($hall['price_per_hour']) ? (float)$hall['price_per_hour'] : 0 ?>;
function add30min(time) {
    var parts = time.split(":");
    var hours = parseInt(parts[0]);
    var mins = parseInt(parts[1]);
    mins += 30;
    if (mins >= 60) {
        hours += 1;
        mins -= 60;
    }
    return hours.toString().padStart(2, '0') + ':' + mins.toString().padStart(2, '0');
}
// Fetch available slots for selected hall and date
function fetchSlots() {
  
    var hallId = document.getElementById('hall_id').value;
    var date = document.getElementById('date').value;
    var startSel = document.getElementById('start_time');
    var endSel = document.getElementById('end_time');
    startSel.innerHTML = '<option value="">Select</option>';
    endSel.innerHTML = '<option value="">Select</option>';
    if (!hallId || !date) return;
    fetch('?ajax=slots&hall_id=' + hallId + '&date=' + date)
        .then(res => res.json())
        .then(data => {
            if (data.length === 0) {
                startSel.innerHTML = '<option value="">No available times</option>';
                endSel.innerHTML = '<option value="">No available times</option>';
                return;
            }
            data.forEach(slot => {
                var from = slot.start_time;
                var to = slot.end_time;
                var t = from;
                while (t < to) {
                    startSel.innerHTML += '<option value="' + t + '">' + t + '</option>';
                    t = add30min(t);
                }
                t = add30min(from);
                while (t <= to) {
                    endSel.innerHTML += '<option value="' + t + '">' + t + '</option>';
                    t = add30min(t);
                }
            });
        });
        
}

  

function updatePrice() {
    
    var hallSel = document.getElementById('hall_id');
    var pricePerHour = PRICE_PER_HOUR;
    var start = document.getElementById('start_time').value;
    var end = document.getElementById('end_time').value;
    var guests = document.getElementById('guests').value;
    if (!pricePerHour || !start || !end || !guests) {
        document.getElementById('price_display').value = '';
        return;
    }
    var s = start.split(":");
    var e = end.split(":");
    var startMins = parseInt(s[0])*60 + parseInt(s[1]);
    var endMins = parseInt(e[0])*60 + parseInt(e[1]);
    var duration = (endMins - startMins) / 60;
    if (duration > 0) {
        document.getElementById('price_display').value = (duration * pricePerHour).toFixed(2) + ' $';
    } else {
        document.getElementById('price_display').value = '';
    }
}
document.getElementById('hall_id').addEventListener('change', function() {
    fetchSlots();
    updatePrice();
});
document.getElementById('date').addEventListener('change', fetchSlots);
document.getElementById('start_time').addEventListener('change', updatePrice);
document.getElementById('end_time').addEventListener('change', updatePrice);
document.getElementById('guests').addEventListener('input', updatePrice);
function validateGuestCount() {
    const guests = parseInt(document.getElementById('guests').value);
    const minGuests = <?= (int)$hall['capacity_min'] ?>;
    const maxGuests = <?= (int)$hall['capacity_max'] ?>;

    if (isNaN(guests)) {
        alert("Please enter the number of guests.");
        return false;
    }

    if (guests < minGuests || guests > maxGuests) {
        alert("Number of guests must be between " + minGuests + " and " + maxGuests + ".");
        return false;
    }

    return true;
}

// باقي الشيفرة الخاصة بك هنا...

document.addEventListener('DOMContentLoaded', function() {
    // احصل على التاريخ الحالي في تنسيق yyyy-mm-dd
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0'); // الشهر يبدأ من 0
    var yyyy = today.getFullYear();
    today = yyyy + '-' + mm + '-' + dd;

    // ضبط الحد الأدنى للتاريخ في حقل الإدخال
    document.getElementById('date').setAttribute('min', today);
});



</script>
