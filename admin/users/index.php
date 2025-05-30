<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$currentPage = 'users';
$pageTitle = "Manage Users";

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    if ($_POST['action'] === 'update_status') {
        $status = $_POST['status'];
        $sql = "UPDATE users SET status = ? WHERE id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param('si', $status, $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit;
        }
    }

    if ($_POST['action'] === 'update_role') {
        $role = $_POST['role'];
        $sql = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param('si', $role, $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit;
        }
    }

    if ($_POST['action'] === 'delete') {

        $sql = "SELECT COUNT(*) as count FROM halls WHERE owner_id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $hallCount = $result->fetch_assoc()['count'];

        if ($hallCount > 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot delete user. They own ' . $hallCount . ' venue(s).'
            ]);
            exit;
        }

        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param('i', $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit;
        }
    }

    echo json_encode(['success' => false]);
    exit;
}

$sql = "SELECT u.*,
        COUNT(DISTINCT h.id) as total_venues,
        COUNT(DISTINCT b.id) as total_bookings,
        COUNT(DISTINCT r.id) as total_reviews,
        MAX(u.last_login) as last_login_date
        FROM users u
        LEFT JOIN halls h ON u.id = h.owner_id
        LEFT JOIN bookings b ON u.id = b.user_id
        LEFT JOIN reviews r ON u.id = r.user_id
        WHERE u.role != 'admin'
        GROUP BY u.id
        ORDER BY u.created_at DESC";
$stmt = $db->getConnection()->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

error_log("Number of users found: " . count($users));

require_once '../includes/admin_header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-users"></i> Manage Users</h1>
    <a href="add.php" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Add New User
    </a>
</div>

<div class="filter-section">
    <div class="search-box">
        <input type="text" id="userSearch" class="form-control" placeholder="Search users...">
    </div>
    <div class="filter-options">
        <select id="roleFilter" class="form-control">
            <option value="">All Roles</option>
            <option value="hall_owner">Hall Owner</option>
            <option value="customer">Customer</option>
        </select>
        <select id="statusFilter" class="form-control">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
        </select>
    </div>
</div>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>User</th>
                <th>Contact</th>
                <th>Role</th>
                <th>Stats</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr data-role="<?php echo htmlspecialchars($user['role']); ?>" 
                    data-status="<?php echo htmlspecialchars($user['status']); ?>">
                    <td>
                        <div class="user-info">
                            <?php if ($user['profile_image']): ?>
                                <img src="<?php echo getImageUrl($user['profile_image']); ?>" 
                                     alt="Profile" class="user-avatar" style="width:100px; height:100;">
                            <?php else: ?>
                                <div class="user-avatar-placeholder">
                                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <strong>
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </strong>
                                <small class="d-block text-muted">
                                    @<?php echo htmlspecialchars($user['username']); ?>
                                </small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="user-contact">
                            <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>" class="text-muted">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                            </a>
                            <div class="text-muted">
                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <select class="role-select form-control" data-user-id="<?php echo $user['id']; ?>">
                            <option value="hall_owner" <?php echo $user['role'] === 'hall_owner' ? 'selected' : ''; ?>>
                                Hall Owner
                            </option>
                            <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>
                                Customer
                            </option>
                        </select>
                    </td>
                    <td>
                        <div class="user-stats">
                            <?php if ($user['role'] === 'hall_owner'): ?>
                                <span title="Venues" class="badge badge-info">
                                    <i class="fas fa-building"></i> <?php echo $user['total_venues']; ?>
                                </span>
                            <?php endif; ?>
                            <span title="Bookings" class="badge badge-success">
                                <i class="fas fa-calendar-check"></i> <?php echo $user['total_bookings']; ?>
                            </span>
                            <span title="Reviews" class="badge badge-warning">
                                <i class="fas fa-star"></i> <?php echo $user['total_reviews']; ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <select class="status-select form-control <?php echo 'status-' . $user['status']; ?>"
                                data-user-id="<?php echo $user['id']; ?>">
                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>
                                Active
                            </option>
                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>
                                Inactive
                            </option>
                            <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>
                                Suspended
                            </option>
                        </select>
                    </td>
                    <td>
                        <?php if ($user['last_login_date']): ?>
                            <span title="<?php echo date('Y-m-d H:i:s', strtotime($user['last_login_date'])); ?>">
                                <?php echo date('M j, Y', strtotime($user['last_login_date'])); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">Never</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="edit.php?id=<?php echo $user['id']; ?>" 
                               class="btn btn-info btn-sm" 
                               title="Edit User">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="view.php?id=<?php echo $user['id']; ?>" 
                               class="btn btn-success btn-sm"
                               title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                          
                            <button type="button" 
                                    class="btn btn-danger btn-sm delete-user"
                                    data-user-id="<?php echo $user['id']; ?>"
                                    data-user-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                                    title="Delete User">
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
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete User</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->

<?php
$extraScripts = '

    $("#userSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $(".table tbody tr").filter(function() {
            return $(this).text().toLowerCase().indexOf(value) > -1;
        }).show().end().not(":visible").hide();
    });

    $("#roleFilter").on("change", function() {
        var role = $(this).val();
        if (!role) {
            $(".table tbody tr").show();
        } else {
            $(".table tbody tr").each(function() {
                $(this).toggle($(this).data("role") === role);
            });
        }
    });

    $("#statusFilter").on("change", function() {
        var status = $(this).val();
        if (!status) {
            $(".table tbody tr").show();
        } else {
            $(".table tbody tr").each(function() {
                $(this).toggle($(this).data("status") === status);
            });
        }
    });

    $(".role-select").on("change", function() {
        var userId = $(this).data("user-id");
        var role = $(this).val();
        var select = $(this);

        $.ajax({
            url: "index.php",
            method: "POST",
            data: {
                action: "update_role",
                user_id: userId,
                role: role
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    select.closest("tr").attr("data-role", role);
                } else {
                    alert(data.message || "Error updating role");
                }
            },
            error: function(xhr, status, error) {
                alert("Error updating role: " + error);
            }
        });
    });

    $(".status-select").on("change", function() {
        var userId = $(this).data("user-id");
        var status = $(this).val();
        var select = $(this);

        $.ajax({
            url: "index.php",
            method: "POST",
            data: {
                action: "update_status",
                user_id: userId,
                status: status
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    select.removeClass("status-active status-inactive status-suspended")
                          .addClass("status-" + status)
                          .closest("tr")
                          .attr("data-status", status);
                } else {
                    alert(data.message || "Error updating status");
                }
            },
            error: function(xhr, status, error) {
                alert("Error updating status: " + error);
            }
        });
    });

    $(".delete-user").on("click", function() {
        var userId = $(this).data("user-id");
        var userName = $(this).data("user-name");
        $("#deleteUserName").text(userName);
        $("#confirmDelete").data("user-id", userId);
        $("#deleteModal").modal("show");
    });

    $("#confirmDelete").on("click", function() {
        var userId = $(this).data("user-id");

        $.ajax({
            url: "index.php",
            method: "POST",
            data: {
                action: "delete",
                user_id: userId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    $("#deleteModal").modal("hide");
                    location.reload();
                } else {
                    alert(data.message || "Error deleting user");
                }
            },
            error: function(xhr, status, error) {
                alert("Error deleting user: " + error);
            }
        });
    });

    var resetUserId = null;

    $(".reset-password-btn").on("click", function(e) {
        e.preventDefault();
        resetUserId = $(this).data("user-id");
        $("#newPasswordBox").addClass("d-none");
        $("#newPassword").text("");
        $("#resetPasswordModal").modal("show");
    });

    $("#confirmReset").on("click", function() {
        if (!resetUserId) return;

        $.ajax({
            url: "reset_password.php",
            method: "POST",
            data: { user_id: resetUserId },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#newPassword").text(response.password);
                    $("#newPasswordBox").removeClass("d-none");
                    $("#confirmReset").addClass("d-none");
                } else {
                    alert(response.message || "Error resetting password");
                }
            },
            error: function() {
                alert("Error resetting password");
            }
        });
    });

    $("#resetPasswordModal").on("hidden.bs.modal", function() {
        resetUserId = null;
        $("#newPasswordBox").addClass("d-none");
        $("#newPassword").text("");
        $("#confirmReset").removeClass("d-none");
    });
';

require_once '../includes/admin_footer.php';
?>