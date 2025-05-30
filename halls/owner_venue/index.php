<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isHallOwner()) {
    header('Location: ../../users/login.php');
    exit();
}

$currentPage = 'venues';
$pageTitle = 'Manage Venues';

// Handle venue actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $venueId = isset($_POST['venue_id']) ? (int)$_POST['venue_id'] : 0;
        
        switch ($_POST['action']) {
            case 'toggle_status':
                $status = $_POST['status'];
                $sql = "UPDATE halls SET status = ? WHERE id = ?";
                $stmt = $db->getConnection()->prepare($sql);
                $stmt->bind_param("si", $status, $venueId);
                if (!$stmt->execute()) {
                    die("Error executing query: " . $db->getConnection()->error);
                }
                break;
                
            case 'delete':
                // First delete related records
                $tables = ['hall_images', 'hall_amenities', 'bookings', 'reviews'];
                foreach ($tables as $table) {
                    $sql = "DELETE FROM $table WHERE hall_id = ?";
                    $stmt = $db->getConnection()->prepare($sql);
                    $stmt->bind_param("i", $venueId);
                    $stmt->execute();
                }
                
                // Then delete the hall
                $sql = "DELETE FROM halls WHERE id = ?";
                $stmt = $db->getConnection()->prepare($sql);
                $stmt->bind_param("i", $venueId);
                $stmt->execute();
                break;
        }
        
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
        exit();
    }
}

// Get all venues with their details
$owner = getCurrentUser();
$owner_id = $owner ? $owner['id'] : 0;
$sql = "SELECT h.*, 
        (SELECT GROUP_CONCAT(a.name) 
         FROM hall_amenities ha 
         JOIN amenities a ON ha.amenity_id = a.id 
         WHERE ha.hall_id = h.id) as amenities,
        (SELECT COUNT(*) FROM bookings b WHERE b.hall_id = h.id) as total_bookings,
        (SELECT COUNT(*) FROM reviews r WHERE r.hall_id = h.id) as total_reviews,
        (SELECT AVG(rating) FROM reviews r WHERE r.hall_id = h.id) as average_rating,
        (SELECT COUNT(*) FROM hall_images hi WHERE hi.hall_id = h.id) as total_images
        FROM halls h
        WHERE h.owner_id = ?
        ORDER BY h.created_at DESC";

$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
    die("Error executing query: " . $db->getConnection()->error);
}
$venues = $result->fetch_all(MYSQLI_ASSOC);

// Get all amenities for the filter
$sql = "SELECT * FROM amenities ORDER BY name";
$result = $db->getConnection()->query($sql);
if (!$result) {
    die("Error executing query: " . $db->getConnection()->error);
}
$amenities = $result->fetch_all(MYSQLI_ASSOC);

require_once '../includes/owner_header.php';
?>





<div class="content-header">
    <h1><i class="fas fa-building"></i> Manage Venues</h1>
    <div class="actions">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Venue
        </a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> Changes saved successfully!
    </div>
<?php endif; ?>

<div class="filter-section">
    <div class="search-box">
        <input type="text" id="venueSearch" placeholder="Search venues..." class="form-control">
    </div>
    <div class="filter-options">
        <select id="amenityFilter" class="form-control">
            <option value="">All Amenities</option>
            <?php foreach ($amenities as $amenity): ?>
                <option value="<?php echo htmlspecialchars($amenity['name']); ?>">
                    <?php echo htmlspecialchars($amenity['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select id="statusFilter" class="form-control">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
</div>

<div class="table-responsive">
    <table class="table venues-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Amenities</th>
                <th>Location</th>
                <th>Price/Hour</th>
                <th>Status</th>
               <!-- <th>Featured</th>-->
                <th>Stats</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($venues as $venue): ?>
                <tr data-amenities="<?php echo htmlspecialchars($venue['amenities'] ?? ''); ?>" 
                    data-status="<?php echo htmlspecialchars($venue['status']); ?>">
                    <td class="venue-image">
                        <img src="<?php echo getVenueMainImage($venue['id']); ?>" 
                             alt="<?php echo htmlspecialchars($venue['name']); ?>">
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($venue['name']); ?></strong>
                        <small class="d-block text-muted">
                            <?php echo substr(htmlspecialchars($venue['description']), 0, 100); ?>...
                        </small>
                    </td>
                    <td>
                        <div class="amenities-list">
                            <?php 
                            $amenityList = explode(',', $venue['amenities'] ?? '');
                            foreach (array_slice($amenityList, 0, 3) as $amenity): ?>
                                <span class="badge badge-info"><?php echo htmlspecialchars($amenity); ?></span>
                            <?php endforeach; ?>
                            <?php if (count($amenityList) > 3): ?>
                                <span class="badge badge-secondary">+<?php echo count($amenityList) - 3; ?> more</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($venue['city']); ?></td>
                    <td>$<?php echo number_format($venue['price_per_hour']); ?></td>
                    <td>
                        <form method="POST" class="status-form">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="venue_id" value="<?php echo $venue['id']; ?>">
                            <select name="status" class="form-control status-select" 
                                    data-status="<?php echo htmlspecialchars($venue['status']); ?>"
                                    onchange="this.form.submit()">
                                <option value="active" <?php echo $venue['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="pending" <?php echo $venue['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="inactive" <?php echo $venue['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </form>
                    </td>
                   <!-- <td>
                        <form method="POST" class="featured-form">
                            <input type="hidden" name="action" value="toggle_featured">
                            <input type="hidden" name="venue_id" value="<?php echo $venue['id']; ?>">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" 
                                       id="featured_<?php echo $venue['id']; ?>"
                                       name="featured" 
                                       <?php echo $venue['is_featured'] ? 'checked' : ''; ?>
                                       onchange="this.form.submit()">
                                <label class="custom-control-label" 
                                       for="featured_<?php echo $venue['id']; ?>"></label>
                            </div>
                        </form>
                    </td>-->
                    <td>
                        <div class="venue-stats">
                            <span title="Bookings">
                                <i class="fas fa-calendar"></i> <?php echo $venue['total_bookings']; ?>
                            </span>
                            <span title="Reviews">
                                <i class="fas fa-star"></i>
                                <?php echo number_format($venue['average_rating'] ?? 0, 1); ?>
                                (<?php echo $venue['total_reviews']; ?>)
                            </span>
                            <span title="Images">
                                <i class="fas fa-images"></i> <?php echo $venue['total_images']; ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="edit.php?id=<?php echo $venue['id']; ?>" 
                               class="btn btn-sm btn-primary" 
                               title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="images.php?id=<?php echo $venue['id']; ?>" 
                               class="btn btn-sm btn-info" 
                               title="Manage Images">
                                <i class="fas fa-images"></i>
                            </a>
                            <button type="button" 
                                    class="btn btn-sm btn-danger delete-venue" 
                                    data-venue-id="<?php echo $venue['id']; ?>"
                                    data-venue-name="<?php echo htmlspecialchars($venue['name']); ?>"
                                    title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Venue</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteVenueName"></strong>?</p>
                <p class="text-danger">This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="venue_id" id="deleteVenueId">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    function filterVenues() {
        var searchValue = $("#venueSearch").val().toLowerCase();
        var amenity = $("#amenityFilter").val().toLowerCase();
        var status = $("#statusFilter").val();

        $(".venues-table tbody tr").each(function() {
            var rowText = $(this).text().toLowerCase();
            var amenities = $(this).data("amenities") ? $(this).data("amenities").toLowerCase() : "";
            var rowStatus = $(this).data("status");

            var matchesSearch = rowText.indexOf(searchValue) > -1;
            var matchesAmenity = (amenity === "" || amenities.indexOf(amenity) > -1);
            var matchesStatus = (status === "" || rowStatus === status);

            $(this).toggle(matchesSearch && matchesAmenity && matchesStatus);
        });
    }

    $("#venueSearch, #amenityFilter, #statusFilter").on("input change", filterVenues);

    // Delete confirmation
    $(".delete-venue").click(function() {
        var id = $(this).data("venue-id");
        var name = $(this).data("venue-name");
        $("#deleteVenueId").val(id);
        $("#deleteVenueName").text(name);
        $("#deleteModal").modal("show");
    });

    // Style status select based on value
    $(".status-select").each(function() {
        var status = $(this).val();
        $(this).addClass("status-" + status);
    });
});
</script>

<?php require_once '../includes/owner_footer.php'; ?>
