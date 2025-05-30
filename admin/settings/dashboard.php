<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$pageTitle = 'Site Settings';
$currentPage = 'settings';

// Example settings (these should be fetched from DB or config in a real system)
$settings = [
    'site_name' => SITE_NAME,
    'site_url' => SITE_URL,
    'contact_email' => isset($_POST['contact_email']) ? $_POST['contact_email'] : 'info@johalls.com',
    'maintenance_mode' => isset($_POST['maintenance_mode']) ? true : false,
];

// Handle settings update (in real system, update DB/config file)
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Here you would update the settings in the DB or config file
    $success = true;
}

require_once '../includes/admin_header.php';
?>
<div class="container mt-4">
    <h1 class="mb-4"><i class="fas fa-cogs"></i> Site Settings</h1>
    <?php if ($success): ?>
        <div class="alert alert-success">Settings updated successfully.</div>
    <?php endif; ?>
    <form method="post" action="">
        <div class="form-group">
            <label>Site Name</label>
            <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name']) ?>" disabled>
        </div>
        <div class="form-group">
            <label>Site URL</label>
            <input type="text" name="site_url" class="form-control" value="<?= htmlspecialchars($settings['site_url']) ?>" disabled>
        </div>
        <div class="form-group">
            <label>Contact Email</label>
            <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($settings['contact_email']) ?>">
        </div>
        <div class="form-group">
            <label>Maintenance Mode</label>
            <input type="checkbox" name="maintenance_mode" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>
<?php require_once '../includes/admin_footer.php'; ?>
