<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$currentPage = 'invitations';
$pageTitle = "Manage Invitation Templates";

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$sql = "SELECT * FROM invitation_templates ORDER BY created_at DESC";
$result = $db->getConnection()->query($sql);
$templates = $result->fetch_all(MYSQLI_ASSOC);

require_once '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Invitation Templates</h1>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Template
        </a>
    </div>

    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">All Templates</h6>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-filter="all">All</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-filter="active">Active</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-filter="inactive">Inactive</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($templates as $template): ?>
                        <div class="col-md-4 mb-4 template-card" data-status="<?php echo $template['status']; ?>">
                            <div class="card h-100">
                                <div class="position-relative">
                                    <?php if ($template['thumbnail']): ?>
                                    <img src="<?php echo getImageUrl($template['thumbnail']); ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($template['name']); ?>"
                                         style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                         style="height: 200px;">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                    
                                </div>

                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($template['name']); ?></h5>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            Category: <?php echo htmlspecialchars($template['category']); ?>
                                        </small>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge badge-<?php echo $template['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($template['status']); ?>
                                        </span>
                                        <div class="btn-group">
                                            <a href="preview.php?id=<?php echo $template['id']; ?>" 
                                               class="btn btn-sm btn-info" 
                                               title="Preview"
                                               target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $template['id']; ?>" 
                                               class="btn btn-sm btn-primary" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger delete-template" 
                                                    data-id="<?php echo $template['id']; ?>"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Template</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this template?</p>
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Warning: This will also delete all invitations using this template.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = '

    $("[data-filter]").on("click", function() {
        var filter = $(this).data("filter");
        $("[data-filter]").removeClass("active");
        $(this).addClass("active");

        if (filter === "all") {
            $(".template-card").show();
        } else {
            $(".template-card").hide();
            $(".template-card[data-status=" + filter + "]").show();
        }
    });

    var deleteTemplateId = null;

    $(".delete-template").on("click", function() {
        deleteTemplateId = $(this).data("id");
        $("#deleteModal").modal("show");
    });

    $("#confirmDelete").on("click", function() {
        if (!deleteTemplateId) return;

        $.ajax({
            url: "delete.php",
            method: "POST",
            data: { id: deleteTemplateId },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || "Error deleting template");
                }
            },
            error: function() {
                alert("Error deleting template");
            }
        });
    });

    $("#deleteModal").on("hidden.bs.modal", function() {
        deleteTemplateId = null;
    });
';

require_once '../includes/admin_footer.php';
?>