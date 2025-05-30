<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$currentPage = 'invitations';
$pageTitle = "Add Invitation Template";

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/users/login.php');
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

    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        try {
            $thumbnail = uploadImage($_FILES['thumbnail'], null, 'invitation_templates');
        } catch (Exception $e) {
            $errors[] = 'Error uploading thumbnail: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        $sql = "INSERT INTO invitation_templates (name, category, html_content, css_content, thumbnail, is_premium, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param('sssssss', $name, $category, $html_content, $css_content, $thumbnail, $is_premium, $status);

        if ($stmt->execute()) {
            setMessage('success', 'Template added successfully');
            header('Location: index.php');
            exit();
        } else {
            $errors[] = 'Error adding template';
        }
    }
}

require_once '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add New Template</h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Templates
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

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="name">Template Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="category">Category</label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Wedding">Wedding</option>
                                <option value="Engagement">Engagement</option>
                                <option value="Anniversary">Anniversary</option>
                                <option value="Traditional">Traditional</option>
                                <option value="Modern">Modern</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="html_content">HTML Content</label>
                            <textarea class="form-control" id="html_content" name="html_content" rows="10" required></textarea>
                            <small class="form-text text-muted">
                                Use placeholders like {bride_name}, {groom_name}, {event_date}, etc.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="css_content">CSS Styles</label>
                            <textarea class="form-control" id="css_content" name="css_content" rows="10"></textarea>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Template Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="thumbnail">Thumbnail Image</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="thumbnail" name="thumbnail" accept="image/*">
                                        <label class="custom-file-label" for="thumbnail">Choose file</label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Recommended size: 800x600 pixels
                                    </small>
                                    <!-- Thumbnail preview image (hidden by default) -->
                                    <div id="thumbnailPreviewContainer" style="margin-top:10px; display:none;">
                                        <img id="thumbnailPreviewImg" src="" alt="Thumbnail Preview" style="max-width:100%; border-radius:8px; box-shadow:0 2px 8px #eee;">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_premium" name="is_premium">
                                        <label class="custom-control-label" for="is_premium">Premium Template</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Preview</h6>
                            </div>
                            <div class="card-body">
                                <div id="templatePreview" class="border p-3" style="min-height: 300px;">
                                    Preview will appear here...
                                </div>
                                <!-- Show thumbnail preview in preview section -->
                                <div id="templateThumbnailPreview" style="margin-top:1rem; display:none;">
                                    <label style="font-weight:600;">Thumbnail Preview:</label>
                                    <img id="templateThumbnailImg" src="" alt="Thumbnail Preview" style="max-width:100%; border-radius:8px; box-shadow:0 2px 8px #eee;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="text-right">
                    <button type="button" class="btn btn-secondary" onclick="location.href='index.php'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Template</button>
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
            ${html}
        `;
        $("#templatePreview").html(preview);

        // If thumbnail is selected, show it in the preview section
        var fileInput = document.getElementById("thumbnail");
        var previewDiv = document.getElementById("templateThumbnailPreview");
        var previewImg = document.getElementById("templateThumbnailImg");
        if (fileInput && fileInput.files && fileInput.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewDiv.style.display = "block";
            };
            reader.readAsDataURL(fileInput.files[0]);
        } else {
            previewDiv.style.display = "none";
            previewImg.src = "";
        }
    }

    $("#html_content, #css_content").on("input", updatePreview);

    // Show selected file name and image preview for thumbnail
    $("#thumbnail").on("change", function() {
        var fileName = this.files && this.files.length > 0 ? this.files[0].name : "Choose file";
        $(this).next(".custom-file-label").html(fileName);

        // Show image preview below the file input
        var previewContainer = document.getElementById("thumbnailPreviewContainer");
        var previewImg = document.getElementById("thumbnailPreviewImg");
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.style.display = "block";
            };
            reader.readAsDataURL(this.files[0]);
        } else {
            previewContainer.style.display = "none";
            previewImg.src = "";
        }

        // Also update the preview section
        updatePreview();
    });

    if (typeof CodeMirror !== "undefined") {
        CodeMirror.fromTextArea(document.getElementById("html_content"), {
            mode: "xml",
            htmlMode: true,
            lineNumbers: true,
            theme: "monokai"
        });

        CodeMirror.fromTextArea(document.getElementById("css_content"), {
            mode: "css",
            lineNumbers: true,
            theme: "monokai"
        });
    }
';

require_once '../includes/admin_footer.php';
?>