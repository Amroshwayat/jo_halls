<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$currentPage = 'invitations';
$pageTitle = "Edit Invitation Template";

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$templateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$templateId) {
    setMessage('error', 'Invalid template ID');
    header('Location: index.php');
    exit();
}

$sql = "SELECT * FROM invitation_templates WHERE id = ?";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param('i', $templateId);
$stmt->execute();
$template = $stmt->get_result()->fetch_assoc();

if (!$template) {
    setMessage('error', 'Template not found');
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $html_content = trim($_POST['html_content']);
    $css_content = trim($_POST['css_content']);
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;
    $status = $_POST['status'];

    $errors = [];
    if (empty($name)) $errors[] = 'Template name is required';
    if (empty($category)) $errors[] = 'Category is required';
    if (empty($html_content)) $errors[] = 'HTML content is required';

    $thumbnail = $template['thumbnail'];
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        try {
            if ($thumbnail) {
                deleteImage($thumbnail);
            }
            $thumbnail = uploadImage($_FILES['thumbnail'], null, 'invitation_templates');
        } catch (Exception $e) {
            $errors[] = 'Error uploading thumbnail: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE invitation_templates 
                SET name = ?, category = ?, html_content = ?, css_content = ?, 
                    thumbnail = ?, is_premium = ?, status = ? 
                WHERE id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param('sssssiss', $name, $category, $html_content, $css_content, 
                         $thumbnail, $is_premium, $status, $templateId);

        if ($stmt->execute()) {
            setMessage('success', 'Template updated successfully');
            header('Location: index.php');
            exit();
        } else {
            $errors[] = 'Error updating template';
        }
    }
}

require_once '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Template</h1>
        <div>
            <a href="template_preview.php?id=<?php echo $templateId; ?>" 
               class="btn btn-info" target="_blank">
                <i class="fas fa-eye"></i> Preview
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Templates
            </a>
        </div>
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

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="name">Template Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($template['name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="category">Category</label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Wedding" <?php echo $template['category'] === 'Wedding' ? 'selected' : ''; ?>>
                                    Wedding
                                </option>
                                <option value="Engagement" <?php echo $template['category'] === 'Engagement' ? 'selected' : ''; ?>>
                                    Engagement
                                </option>
                                <option value="Anniversary" <?php echo $template['category'] === 'Anniversary' ? 'selected' : ''; ?>>
                                    Anniversary
                                </option>
                                <option value="Traditional" <?php echo $template['category'] === 'Traditional' ? 'selected' : ''; ?>>
                                    Traditional
                                </option>
                                <option value="Modern" <?php echo $template['category'] === 'Modern' ? 'selected' : ''; ?>>
                                    Modern
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="html_content">HTML Content</label>
                            <textarea class="form-control" id="html_content" name="html_content" 
                                    rows="15" required><?php echo htmlspecialchars($template['html_content']); ?></textarea>
                            <small class="form-text text-muted">
                                Available placeholders: {bride_name}, {groom_name}, {bride_father}, {groom_father}, 
                                {event_date}, {event_time}, {venue_name}, {venue_address}, {rsvp_date}
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="css_content">CSS Styles</label>
                            <textarea class="form-control" id="css_content" name="css_content" 
                                    rows="15"><?php echo htmlspecialchars($template['css_content']); ?></textarea>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Template Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Current Thumbnail</label>
                                    <?php if ($template['thumbnail']): ?>
                                    <img src="<?php echo getImageUrl($template['thumbnail']); ?>" 
                                         alt="Template Thumbnail" 
                                         class="img-fluid thumbnail-preview mb-2">
                                    <?php endif; ?>

                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="thumbnail" 
                                               name="thumbnail" accept="image/*">
                                        <label class="custom-file-label" for="thumbnail">
                                            Choose new thumbnail
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Recommended size: 800x600 pixels
                                    </small>
                                </div>

                                

                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="active" <?php echo $template['status'] === 'active' ? 'selected' : ''; ?>>
                                            Active
                                        </option>
                                        <option value="inactive" <?php echo $template['status'] === 'inactive' ? 'selected' : ''; ?>>
                                            Inactive
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                       
                    </div>
                </div>

                <hr>

                <div class="text-right">
                    <button type="button" class="btn btn-secondary" onclick="location.href='index.php'">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Update Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScripts = '
    bsCustomFileInput.init();

    function updatePreview() {
        var html = $("#html_content").val();
        var css = $("#css_content").val();
        var preview = `
            <style>${css}</style>
            <div>${html}</div>
        `;
        $("#templatePreview").html(preview);
    }

    $("#html_content, #css_content").on("input", updatePreview);

    updatePreview();
';

require_once '../includes/admin_footer.php';
?>
