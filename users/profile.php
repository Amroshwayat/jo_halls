<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['profile_image'])) {
    $firstName = isset($_POST['first_name']) ? sanitizeInput($_POST['first_name']) : '';
    $lastName = isset($_POST['last_name']) ? sanitizeInput($_POST['last_name']) : '';
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
    $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($firstName) || empty($lastName) || empty($phone)) {
        $error = 'Please fill in all required fields';
    } else {
        $updateFields = [];
        $updateTypes = "";
        $updateParams = [];

        // Basic info update
        $updateFields[] = "first_name = ?";
        $updateFields[] = "last_name = ?";
        $updateFields[] = "phone = ?";
        $updateTypes .= "sss";
        $updateParams[] = $firstName;
        $updateParams[] = $lastName;
        $updateParams[] = $phone;

        // Password update if provided
        if (!empty($currentPassword) && !empty($newPassword)) {
            if ($newPassword !== $confirmPassword) {
                $error = 'New passwords do not match';
            } else if (!password_verify($currentPassword, $user['password'])) {
                $error = 'Current password is incorrect';
            } else {
                $updateFields[] = "password = ?";
                $updateTypes .= "s";
                $updateParams[] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
        }

        if (empty($error)) {
            $updateParams[] = $userId;
            $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bind_param($updateTypes . "i", ...$updateParams);

            if ($stmt->execute()) {
                $success = 'Profile updated successfully';
                // Refresh user data
                $stmt = $db->getConnection()->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error = 'Failed to update profile';
            }
        }
    }
}
// Handle profile image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    // Delete image file from server
    if (!empty($user['profile_image']) && file_exists('../' . $user['profile_image'])) {
        unlink('../' . $user['profile_image']);
    }

    // Update database to remove image
    $sql = "UPDATE users SET profile_image = NULL WHERE id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    // Redirect to avoid resubmission and refresh data
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Handle profile image upload
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($_FILES['profile_image']['type'], $allowedTypes)) {
        $error = 'Invalid file type. Please upload a JPEG, PNG, or GIF image.';
    } else if ($_FILES['profile_image']['size'] > $maxSize) {
        $error = 'File is too large. Maximum size is 5MB.';
    } else {
        $uploadDir = '../assets/images/users/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid('profile_') . '_' . basename($_FILES['profile_image']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
            $relativePath = 'assets/images/users/' . $fileName;
            
            $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bind_param("si", $relativePath, $userId);
            
            if ($stmt->execute()) {
                $success = 'Profile image updated successfully';
                $user['profile_image'] = $relativePath;
            } else {
                $error = 'Failed to update profile image in database';
            }
        } else {
            $error = 'Failed to upload profile image';
        }
    }
}

$pageTitle = "My Profile";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/profile.css">
</head>
<body>
    <?php include_once '../includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="profile-section">
                <h1>My Profile</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
               

                <div class="profile-content">
                    <div class="profile-image">
                    <div class="profile-image-frame">
                            <img src="<?php echo !empty($user['profile_image']) ? 
                                SITE_URL . '/' . $user['profile_image'] : 
                                SITE_URL . '/assets/images/default-user.png'; ?>" 
                                alt="" id="preview-image">
                            <?php if (empty($user['profile_image'])): ?>
                                <div class="profile-image-label-top">Profile Image</div>
                            <?php endif; ?>
                        </div>
                        
                        <form action="" method="POST" enctype="multipart/form-data" class="image-upload-form">
                            <input type="file" name="profile_image" id="profile_image" accept="image/*" style="display: none;">
                            <button type="button" class="btn change-photo-btn" onclick="document.getElementById('profile_image').click();">
                                <i class="fas fa-camera"></i> Change Photo
                            </button>
                        </form>
                        

                         <!-- زر حذف الصورة -->
                        <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete the photo?');" style="margin-top: 10px;">
                            <input type="hidden" name="delete_image" value="1">
                            <button type="submit" class="btn btn-remove-photo">
                                <i class="fas fa-trash-alt"></i> Delete Photo
                            </button>
                        </form>
                    </div>

                    <form action="" method="POST" class="profile-form">
                        <div class="form-group">
                            <label for="first_name">
                                <i class="fas fa-user"></i> First Name
                            </label>
                            <input type="text" id="first_name" name="first_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="last_name">
                                <i class="fas fa-user"></i> Last Name
                            </label>
                            <input type="text" id="last_name" name="last_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email" id="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Phone
                            </label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>

                        <div class="password-section">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="form-control">
                            </div>

                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control">
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include_once '../includes/footer.php'; ?>

    <script>
        document.getElementById('profile_image').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Show image preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-image').src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
                
                // Submit form
                this.closest('form').submit();
            }
        });

        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password && confirmPassword.value && password !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            if (password && this.value && password !== this.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>

    <style>
    :root {
        --primary-color: #ff4b7d;
        --primary-dark: #ff1f5a;
        --danger-color: #dc3545;
        --danger-dark: #b71c1c;
        --border-radius: 8px;
        --transition: all 0.3s ease;
    }
    /* Profile Page Title Styling */
    .profile-page-title {
        text-align: center;
        margin: 40px 0 20px 0;
    }
    .profile-page-title h1 {
        font-size: 2.1rem;
        font-weight: 800;
        letter-spacing: -1px;
        margin-bottom: 0.3rem;
        color: #fff;
        text-shadow: 0 2px 12px rgba(255,75,125,0.13);
        /* Use brand gradient for background */
        background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        border-radius: 22px;
        padding: 18px 40px 18px 32px;
        box-shadow: 0 8px 32px rgba(255,75,125,0.13);
        border: none;
        position: relative;
        display: inline-block;
        line-height: 1.1;
    }
    .profile-page-title h1 i {
        color: #ff4b7d;
        margin-right: 16px;
        font-size: 1.5em;
        vertical-align: middle;
        background: linear-gradient(135deg, #ff4b7d 60%, #ff1f5a 100%);
        border-radius: 50%;
        padding: 10px;
        box-shadow: 0 2px 8px #ff4b7d22;
    }
    /* Remove old .profile-title-group and related styles */
    .profile-title-group,
    .profile-title-main,
    .profile-title-sub {
        display: none !important;
    }
    .profile-section {
        max-width: 1000px;
        margin: 40px auto;
        padding: 30px;
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        animation: fadeIn 0.5s ease-out;
    }
    .profile-content {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 40px;
        align-items: flex-start;
    }
    .profile-image {
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.2rem;
        position: relative;
    }
    .profile-image-frame {
        position: relative;
        display: inline-block;
    }
    .profile-image-frame img {
        width: 160px;
        height: 160px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid var(--primary-color);
        background: #fff;
        box-shadow: 0 2px 10px rgba(255,75,125,0.10);
        transition: transform 0.3s;
        z-index: 1;
        display: block;
    }
    .profile-image-label-top {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        font-weight: 700;
        color: #ff4b7d;
        font-size: 1.08rem;
        background: rgba(255,255,255,0.92);
        padding: 8px 24px;
        border-radius: 20px;
        box-shadow: 0 2px 8px rgba(255,75,125,0.07);
        border: 1.5px solid #ffe0ec;
        letter-spacing: 0.5px;
        text-align: center;
        pointer-events: none;
        z-index: 2;
    }
    .image-upload-form {
        width: 100%;
        margin-top: 0.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .photo-btn-group {
        display: flex;
        gap: 0.7rem;
        justify-content: center;
        margin-top: 0.7rem;
        width: 100%;
    }
    .change-photo-btn {
        background: var(--primary-color);
        border: none;
        color: #fff;
        font-weight: 600;
        border-radius: var(--border-radius);
        padding: 12px 22px;
        box-shadow: 0 2px 12px rgba(255,75,125,0.13);
        display: flex;
        align-items: center;
        gap: 0.7rem;
        font-size: 1.08rem;
        cursor: pointer;
        transition: var(--transition);
        outline: none;
    }
    .change-photo-btn:hover {
        background: var(--primary-dark);
        color: #fff;
        transform: translateY(-2px) scale(1.04);
        box-shadow: 0 4px 18px rgba(255,75,125,0.18);
    }
    .change-photo-btn i {
        font-size: 1.35em;
        color: #fff;
        background: var(--primary-dark);
        border-radius: 50%;
        padding: 7px;
        box-shadow: 0 2px 8px rgba(255,75,125,0.13);
        border: 2px solid #fff;
        margin-right: 2px;
        transition: var(--transition);
    }
    .btn-remove-photo {
        background: var(--danger-color);
        color: #fff;
        border: none;
        border-radius: var(--border-radius);
        padding: 12px 22px;
        font-size: 1.08rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.7rem;
        cursor: pointer;
        box-shadow: 0 2px 12px rgba(220,53,69,0.13);
        transition: var(--transition);
        outline: none;
    }
    .btn-remove-photo:hover {
        background: var(--danger-dark);
        color: #fff;
        transform: translateY(-2px) scale(1.04);
        box-shadow: 0 4px 18px rgba(220,53,69,0.18);
    }
    .btn-remove-photo i {
        font-size: 1.25em;
        color: #fff;
        background: var(--danger-dark);
        border-radius: 50%;
        padding: 7px;
        box-shadow: 0 2px 8px rgba(220,53,69,0.13);
        border: 2px solid #fff;
        margin-right: 2px;
        transition: var(--transition);
    }
    .profile-form {
        background: #f8f9fa;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(255,75,125,0.04);
    }
    .form-group {
        margin-bottom: 25px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #495057;
        font-weight: 500;
        font-size: 0.95em;
    }
    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        font-size: 1em;
        transition: border-color 0.3s;
        background: #fff;
    }
    .form-control:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(255,75,125,0.13);
    }
    .form-control:disabled {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
    .password-section {
        margin-top: 30px;
        padding-top: 30px;
        border-top: 2px solid #dee2e6;
    }
    .password-section h3 {
        color: #495057;
        font-size: 1.3em;
        margin-bottom: 20px;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .form-actions {
        margin-top: 30px;
        text-align: right;
    }
    .btn-primary {
        padding: 12px 30px;
        font-size: 1em;
        font-weight: 600;
        border-radius: var(--border-radius);
        background: var(--primary-color);
        color: #fff;
        border: none;
        box-shadow: 0 2px 8px rgba(255,75,125,0.13);
        transition: var(--transition);
    }
    .btn-primary:hover {
        background: var(--primary-dark);
        color: #fff;
        transform: translateY(-2px) scale(1.04);
        box-shadow: 0 4px 18px rgba(255,75,125,0.18);
    }
    .alert {
        padding: 15px 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 1rem;
        animation: fadeIn 0.3s;
    }
    .alert-danger {
        background-color: #fff3f3;
        color: #dc3545;
        border: 2px solid #ffcdd2;
    }
    .alert-success {
        background-color: #f1f9f1;
        color: #28a745;
        border: 2px solid #c3e6cb;
    }
    @media (max-width: 900px) {
        .profile-content {
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        .profile-section {
            padding: 20px;
            margin: 20px;
        }
        .profile-image-frame img {
            width: 120px;
            height: 120px;
        }
        .profile-image-label-top {
            font-size: 0.98rem;
            padding: 5px 14px;
        }
    }
    @media (max-width: 500px) {
        .profile-image-frame img {
            width: 90px;
            height: 90px;
        }
        .profile-image-label-top {
            font-size: 0.92rem;
            padding: 4px 10px;
        }
        .photo-btn-group {
            flex-direction: column;
            gap: 0.5rem;
        }
        .change-photo-btn, .btn-remove-photo {
            width: 100%;
            padding: 10px 0;
            font-size: 1rem;
        }
        .profile-form {
            padding: 15px;
        }
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px);}
        to { opacity: 1; transform: translateY(0);}
    }
    </style>
</body>
</html>
