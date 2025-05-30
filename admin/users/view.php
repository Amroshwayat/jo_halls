<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$currentPage = 'users';
$pageTitle = "View User";

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$userId) {
    setMessage('error', 'Invalid user ID');
    header('Location: index.php');
    exit();
}

$viewUser = getUserById($userId);

if (!$viewUser) {
    setMessage('error', 'User not found');
    header('Location: index.php');
    exit();
}

require_once '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">View User Details</h1>
        <a href="index.php" class="btn btn-secondary">Back to Users</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php if ($viewUser['profile_image']): ?>
                        <img src="<?php echo getImageUrl($viewUser['profile_image']); ?>" 
                             alt="Profile Image" 
                             class="img-fluid rounded-circle mb-3" 
                             style="max-width: 200px;">
                    <?php else: ?>
                        <img src="<?php echo SITE_URL . '/' . DEFAULT_USER_IMAGE; ?>" 
                             alt="Default Profile" 
                             class="img-fluid rounded-circle mb-3" 
                             style="max-width: 200px;">
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Personal Information</h5>
                            <table class="table">
                                <tr>
                                    <th>First Name:</th>
                                    <td><?php echo htmlspecialchars($viewUser['first_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Last Name:</th>
                                    <td><?php echo htmlspecialchars($viewUser['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Username:</th>
                                    <td><?php echo htmlspecialchars($viewUser['username']); ?></td>
                                </tr>
                                <tr>
                                    <th>Role:</th>
                                    <td><span class="badge badge-info"><?php echo formatRole($viewUser['role']); ?></span></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'active' => 'success',
                                            'inactive' => 'warning',
                                            'suspended' => 'danger'
                                        ][$viewUser['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge badge-<?php echo $statusClass; ?>">
                                            <?php echo ucfirst($viewUser['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Contact Information</h5>
                            <table class="table">
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo htmlspecialchars($viewUser['email']); ?></td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td><?php echo htmlspecialchars($viewUser['phone'] ?? 'Not provided'); ?></td>
                                </tr>
                            </table>

                            <h5>Account Information</h5>
                            <table class="table">
                                <tr>
                                    <th>Created:</th>
                                    <td><?php echo date('M j, Y g:i A', strtotime($viewUser['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Last Login:</th>
                                    <td>
                                        <?php 
                                        echo $viewUser['last_login'] 
                                            ? date('M j, Y g:i A', strtotime($viewUser['last_login']))
                                            : 'Never';
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <a href="edit.php?id=<?php echo $viewUser['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit User
                </a>
               
            </div>
        </div>
    </div>
</div>



<?php
$extraScripts = '

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