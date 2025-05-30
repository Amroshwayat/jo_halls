<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$currentPage = 'users';
$pageTitle = "Edit User";

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/users/login.php');
    exit();
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$userId) {
    setMessage('error', 'Invalid user ID');
    header('Location: index.php');
    exit();
}

$editUser = getUserById($userId);

if (!$editUser) {
    setMessage('error', 'User not found');
    header('Location: index.php');
    exit();
}

error_log("Editing user ID: " . $userId);
error_log("User data: " . print_r($editUser, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $status = $_POST['status'];

    $errors = [];
    if (empty($firstName)) $errors[] = 'First name is required';
    if (empty($lastName)) $errors[] = 'Last name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($username)) $errors[] = 'Username is required';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param('si', $username, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = 'Username already exists';
    }

    $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param('si', $email, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = 'Email already exists';
    }

    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
    }

    $profileImage = $editUser['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        try {
            $uploadedImage = uploadImage($_FILES['profile_image'], null, 'user');
            if ($uploadedImage) {

                if ($profileImage) {
                    deleteImage($profileImage);
                }
                $profileImage = $uploadedImage;
            }
        } catch (Exception $e) {
            $errors[] = 'Error uploading image: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {

        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    phone = ?, 
                    username = ?, 
                    password = ?,
                    role = ?,
                    status = ?,
                    profile_image = ?
                    WHERE id = ?";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bind_param('sssssssssi', 
                $firstName, $lastName, $email, $phone, $username, 
                $hashedPassword, $role, $status, $profileImage, $userId);
        } else {
            $sql = "UPDATE users SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    phone = ?, 
                    username = ?, 
                    role = ?,
                    status = ?,
                    profile_image = ?
                    WHERE id = ?";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bind_param('ssssssssi', 
                $firstName, $lastName, $email, $phone, $username, 
                $role, $status, $profileImage, $userId);
        }

        if ($stmt->execute()) {
            setMessage('success', 'User updated successfully');
            header('Location: index.php');
            exit();
        } else {
            $errors[] = 'Error updating user';
        }
    }
}

require_once '../includes/admin_header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-user-edit"></i> Edit User</h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Users
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form action="" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($editUser['first_name']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($editUser['last_name']); ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($editUser['email']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($editUser['phone']); ?>">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($editUser['username']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="hall_owner" <?php echo $editUser['role'] === 'hall_owner' ? 'selected' : ''; ?>>
                                Hall Owner
                            </option>
                            <option value="customer" <?php echo $editUser['role'] === 'customer' ? 'selected' : ''; ?>>
                                Customer
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="active" <?php echo $editUser['status'] === 'active' ? 'selected' : ''; ?>>
                                Active
                            </option>
                            <option value="inactive" <?php echo $editUser['status'] === 'inactive' ? 'selected' : ''; ?>>
                                Inactive
                            </option>
                            <option value="suspended" <?php echo $editUser['status'] === 'suspended' ? 'selected' : ''; ?>>
                                Suspended
                            </option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="profile_image">Profile Image</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="profile_image" name="profile_image" 
                                   accept="image/jpeg,image/png,image/webp">
                            <label class="custom-file-label" for="profile_image">Choose file</label>
                        </div>
                        <?php if ($editUser['profile_image']): ?>
                            <div class="mt-2">
                                <img src="<?php echo getImageUrl($editUser['profile_image']); ?>" 
                                     alt="Current Profile Image" class="img-thumbnail" style="max-width: 100px;">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               minlength="8" autocomplete="new-password">
                        <small class="form-text text-muted">Leave blank to keep current password</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               minlength="8">
                    </div>
                </div>
            </div>

            <div class="form-group mb-0">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$extraScripts = '

    $(".custom-file-input").on("change", function() {
        var fileName = $(this).val().split("\\\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });

    $("#password, #confirm_password").on("keyup", function() {
        var password = $("#password").val();
        var confirm = $("#confirm_password").val();

        if (password || confirm) {
            if (password === confirm) {
                $("#confirm_password")[0].setCustomValidity("");
            } else {
                $("#confirm_password")[0].setCustomValidity("Passwords do not match");
            }
        } else {
            $("#confirm_password")[0].setCustomValidity("");
        }
    });

    (function() {
        "use strict";
        window.addEventListener("load", function() {
            var forms = document.getElementsByClassName("needs-validation");
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener("submit", function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add("was-validated");
                }, false);
            });
        }, false);
    })();
';

require_once '../includes/admin_footer.php';
?>