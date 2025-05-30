<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

$currentPage = 'users';
$pageTitle = "Add New User";

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = sanitizeInput($_POST['first_name']);
    $lastName = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $username = sanitizeInput($_POST['username']);
    $role = sanitizeInput($_POST['role']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($username)) $errors[] = "Username is required";
    if (empty($password)) $errors[] = "Password is required";

    if (!empty($email) && !validateEmail($email)) {
        $errors[] = "Invalid email format";
    }

    if (!empty($phone) && !validatePhone($phone)) {
        $errors[] = "Invalid phone format";
    }

    if (!empty($email)) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email already exists";
        }
    }

    if (!empty($username)) {
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Username already exists";
        }
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    } elseif (!validatePassword($password)) {
        $errors[] = "Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character";
    }

    $profileImage = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        try {
            $profileImage = uploadImage($_FILES['profile_image'], SITE_ROOT . '/uploads/profiles');
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $hashedPassword = hashPassword($password);

        $sql = "INSERT INTO users (username, email, password, role, first_name, last_name, phone, profile_image, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";

        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param('ssssssss', $username, $email, $hashedPassword, $role, $firstName, $lastName, $phone, $profileImage);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "User created successfully";
            header('Location: index.php');
            exit();
        } else {
            $errors[] = "Error creating user: " . $db->getConnection()->error;
        }
    }
}

require_once '../includes/admin_header.php';
?>

<div class="content-header">
    <h1><i class="fas fa-user-plus"></i> Add New User</h1>
    <a href="index.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Users
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
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
                        <label for="first_name">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                               required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                               required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                               placeholder="+1234567890">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="hall_owner" <?php echo (isset($_POST['role']) && $_POST['role'] === 'hall_owner') ? 'selected' : ''; ?>>
                                Hall Owner
                            </option>
                            <option value="customer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'customer') ? 'selected' : ''; ?>>
                                Customer
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="form-text text-muted">
                            Password must be at least 8 characters long and contain uppercase, lowercase, number, and special character.
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="profile_image">Profile Image</label>
                <input type="file" class="form-control-file" id="profile_image" name="profile_image" 
                       accept="image/jpeg,image/png,image/webp">
                <small class="form-text text-muted">
                    Maximum file size: 5MB. Allowed formats: JPG, PNG, WebP
                </small>
            </div>

            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create User
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
<script>
$(document).ready(function() {

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

    $("#password").on("input", function() {
        var password = $(this).val();
        var strength = 0;

        if (password.length >= 8) strength++;

        if (password.match(/[A-Z]/)) strength++;

        if (password.match(/[a-z]/)) strength++;

        if (password.match(/[0-9]/)) strength++;

        if (password.match(/[!@#$%^&*(),.?":{}|<>]/)) strength++;

        var strengthClass = "";
        var strengthText = "";

        switch(strength) {
            case 0:
            case 1:
                strengthClass = "text-danger";
                strengthText = "Very Weak";
                break;
            case 2:
                strengthClass = "text-warning";
                strengthText = "Weak";
                break;
            case 3:
                strengthClass = "text-info";
                strengthText = "Medium";
                break;
            case 4:
                strengthClass = "text-primary";
                strengthText = "Strong";
                break;
            case 5:
                strengthClass = "text-success";
                strengthText = "Very Strong";
                break;
        }

        $(this).next(".form-text")
            .removeClass("text-danger text-warning text-info text-primary text-success")
            .addClass(strengthClass)
            .html("Password strength: " + strengthText);
    });

    $("#confirm_password").on("input", function() {
        var password = $("#password").val();
        var confirmPassword = $(this).val();

        if (password === confirmPassword) {
            $(this).removeClass("is-invalid").addClass("is-valid");
        } else {
            $(this).removeClass("is-valid").addClass("is-invalid");
        }
    });
});
</script>';

require_once '../includes/admin_footer.php';
?>