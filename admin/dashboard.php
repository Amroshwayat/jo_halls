<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$currentPage = 'dashboard';
$pageTitle = "Admin Dashboard";

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$stats = [
    'total_venues' => 0,
    'total_users' => 0,
    'pending_bookings' => 0,
    'approved_bookings' => 0,
    'rejected_bookings' => 0,
    'recent_reviews' => []
];

$sql = "SELECT COUNT(*) as count FROM halls";
$stmt = $db->getConnection()->prepare($sql);
if (!$stmt->execute()) {
    die("Error: " . $stmt->error);
}
$result = $stmt->get_result();
$stats['total_venues'] = $result->fetch_assoc()['count'];

$sql = "SELECT COUNT(*) as count FROM users WHERE role != 'admin'";
$stmt = $db->getConnection()->prepare($sql);
if (!$stmt->execute()) {
    die("Error: " . $stmt->error);
}
$result = $stmt->get_result();
$stats['total_users'] = $result->fetch_assoc()['count'];

$sql = "SELECT status, COUNT(*) as count FROM bookings GROUP BY status";
$stmt = $db->getConnection()->prepare($sql);
if (!$stmt->execute()) {
    die("Error: " . $stmt->error);
}
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'pending') {
        $stats['pending_bookings'] = $row['count'];
    } else if ($row['status'] === 'approved') {
        $stats['approved_bookings'] = $row['count'];
    } else if ($row['status'] === 'rejected') {
        $stats['rejected_bookings'] = $row['count'];
    }
}

$sql = "SELECT r.*, h.name as hall_name, u.first_name, u.last_name, r.rating, r.review_text 
        FROM reviews r 
        JOIN halls h ON r.hall_id = h.id 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC LIMIT 5";
$stmt = $db->getConnection()->prepare($sql);
if (!$stmt->execute()) {
    die("Error: " . $stmt->error);
}
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $stats['recent_reviews'][] = $row;
}

$sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
        FROM bookings 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY month 
        ORDER BY month";
$stmt = $db->getConnection()->prepare($sql);
if (!$stmt->execute()) {
    die("Error: " . $stmt->error);
}
$result = $stmt->get_result();
$monthlyBookings = [];
while ($row = $result->fetch_assoc()) {
    $monthlyBookings[] = $row;
}

require_once 'includes/admin_header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>
</div>

<div class="quick-stats">
    <div class="stat-card">
        <i class="fas fa-building"></i>
        <h3>Total Venues</h3>
        <p><?php echo number_format($stats['total_venues']); ?></p>
    </div>

    <div class="stat-card">
        <i class="fas fa-users"></i>
        <h3>Total Users</h3>
        <p><?php echo number_format($stats['total_users']); ?></p>
    </div>

    <div class="stat-card">
        <i class="fas fa-clock"></i>
        <h3>Pending Bookings</h3>
        <p><?php echo number_format($stats['pending_bookings']); ?></p>
    </div>

    <div class="stat-card">
        <i class="fas fa-calendar-check"></i>
        <h3>Approved Bookings</h3>
        <p><?php echo number_format($stats['approved_bookings']); ?></p>
    </div>
</div>

<div class="charts-section">
    <div class="chart-container">
        <h2>Booking Statistics</h2>
        <canvas id="bookingChart"></canvas>
    </div>

    <div class="chart-container">
        <h2>Booking Status Distribution</h2>
        <canvas id="statusChart"></canvas>
    </div>
</div>

<div class="recent-reviews">
    <h2><i class="fas fa-star"></i> Recent Reviews</h2>
    <div class="reviews-list">
        <?php foreach ($stats['recent_reviews'] as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <h4><?php echo htmlspecialchars($review['hall_name']); ?></h4>
                    <div class="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <p class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></p>
                <p class="review-meta">
                    By <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                    on <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

    const monthlyData = ' . json_encode($monthlyBookings) . ';
    const labels = monthlyData.map(item => {
        const date = new Date(item.month + "-01");
        return date.toLocaleDateString("en-US", { month: "short", year: "numeric" });
    });
    const values = monthlyData.map(item => item.count);

    new Chart(document.getElementById("bookingChart"), {
        type: "line",
        data: {
            labels: labels,
            datasets: [{
                label: "Monthly Bookings",
                data: values,
                borderColor: "#4CAF50",
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: "Booking Trends (Last 6 Months)"
                }
            }
        }
    });

    new Chart(document.getElementById("statusChart"), {
        type: "pie",
        data: {
            labels: ["Pending", "Approved", "Rejected"],
            datasets: [{
                data: [
                    ' . $stats['pending_bookings'] . ',
                    ' . $stats['approved_bookings'] . ',
                    ' . $stats['rejected_bookings'] . '
                ],
                backgroundColor: ["#FFC107", "#4CAF50", "#F44336"]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: "Booking Status Distribution"
                }
            }
        }
    });
</script>';

require_once 'includes/admin_footer.php';
?>