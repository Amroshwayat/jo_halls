<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
if (!isLoggedIn() || $_SESSION['user_role'] !== 'customer') {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}
$pageTitle = 'Create Invitations';
require_once '../includes/header.php';
?>
<!-- Custom styles for invitation creation page -->
<style>
.invitation-create-container {
    max-width: 700px;
    margin: 40px auto 0 auto;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 6px 32px rgba(0,0,0,0.07);
    padding: 2.5rem 2rem 2rem 2rem;
}
.invitation-create-title {
    text-align: center;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    color: #ff4b7d;
    font-weight: bold;
    letter-spacing: -1px;
}
.invitation-form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1.1rem;
    margin-bottom: 1.1rem;
}
.invitation-form-group {
    flex: 1 1 160px;
    min-width: 140px;
    margin-bottom: 0;
}
.invitation-label {
    font-weight: 500;
    color: #333;
    margin-bottom: 0.3rem;
    display: block;
}
.invitation-form-control {
    width: 100%;
    border-radius: 7px;
    border: 1.5px solid #e1e1e1;
    padding: 0.55rem 0.85rem;
    font-size: 1rem;
    margin-bottom: 0.2rem;
    transition: border 0.18s;
}
.invitation-btn-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.7rem 1.1rem;
    align-items: center;
    margin-bottom: 1.1rem;
    justify-content: flex-start;
}
.invitation-btn {
    border-radius: 7px;
    font-weight: 600;
    font-size: 1rem;
    padding: 0.55rem 1.3rem;
    min-width: 160px;
    box-shadow: 0 2px 8px rgba(255,31,90,0.07);
    transition: background 0.14s, color 0.14s, transform 0.13s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border: none;
    outline: none;
    cursor: pointer;
    text-decoration: none;
}
.invitation-btn-secondary {
    background: #6c757d;
    color: #fff;
}
.invitation-btn-secondary:hover {
    background: #495057;
}
.invitation-btn-info {
    background: #17a2b8;
    color: #fff;
}
.invitation-btn-info:hover {
    background: #138496;
}
.invitation-btn-success {
    background: #28a745;
    color: #fff;
}
.invitation-btn-success:hover {
    background: #218838;
}
.invitation-btn-preview {
    background: #5bc0de;
    color: #fff;
}
.invitation-btn-preview:hover {
    background: #31b0d5;
}
.invitation-template-preview {
    min-height: 110px;
    background: #f8f9fa;
    border-radius: 7px;
    padding: 1.1rem 1.2rem;
    margin-bottom: 1.1rem;
    border: 1.5px solid #e1e1e1;
    box-shadow: 0 2px 8px rgba(80,80,120,0.04);
}
@media (max-width: 600px) {
    .invitation-form-row, .invitation-btn-row {
        flex-direction: column;
        gap: 0.7rem;
    }
    .invitation-btn {
        min-width: 0;
        width: 100%;
    }
}
.share-dropdown {
    position: relative;
    display: inline-block;
}
.share-btn:focus + .share-menu,
.share-dropdown:hover .share-menu {
    display: flex;
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
}
.share-menu {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    position: absolute;
    left: 0;
    top: 110%;
    min-width: 180px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 6px 24px rgba(80,80,120,0.13);
    padding: 0.5rem 0;
    z-index: 10;
    opacity: 0;
    pointer-events: none;
    transform: translateY(10px);
    transition: opacity 0.18s, transform 0.18s;
}
.share-menu button {
    background: none;
    border: none;
    color: #333;
    text-align: left;
    padding: 0.7rem 1.2rem;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.7rem;
    width: 100%;
    cursor: pointer;
    border-radius: 5px;
    transition: background 0.13s, color 0.13s;
}
.share-menu button:hover {
    background: #f8f9fa;
    color: #ff6b6b;
}
@media (max-width: 600px) {
    .share-menu {
        min-width: 140px;
        right: 0;
        left: auto;
    }
}
</style>
<?php
// جلب القوالب المتوفرة من قاعدة البيانات
$templates = [];
$sql = "SELECT * FROM invitation_templates WHERE status = 'active' ORDER BY name ASC";
$result = $db->getConnection()->query($sql);
if ($result) {
    $templates = $result->fetch_all(MYSQLI_ASSOC);
}

// جلب الحجوزات المؤكدة للعميل
$approvedBookings = [];
$sqlBookings = "SELECT b.id, h.name as hall_name, b.event_date, b.start_time, b.end_time FROM bookings b JOIN halls h ON b.hall_id = h.id WHERE b.user_id = ? AND b.status = 'confirmed' ORDER BY b.event_date DESC";
$stmtBookings = $db->getConnection()->prepare($sqlBookings);
$stmtBookings->bind_param('i', $_SESSION['user_id']);
$stmtBookings->execute();
$resultBookings = $stmtBookings->get_result();
if ($resultBookings) {
    $approvedBookings = $resultBookings->fetch_all(MYSQLI_ASSOC);
}
$stmtBookings->close();

// معالجة إرسال الدعوة
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['template_id'], $_POST['event_title'], $_POST['guests'], $_POST['booking_id'])) {
    $template_id = (int)$_POST['template_id'];
    $event_title = trim($_POST['event_title']);
    $guests = trim($_POST['guests']); // قائمة إيميلات مفصولة بفواصل
    $user_id = $_SESSION['user_id'];
    $booking_id = (int)$_POST['booking_id'];

    // تحقق أن الحجز المختار فعلاً للعميل ومؤكد
    $stmt_check = $db->getConnection()->prepare("SELECT COUNT(*) FROM bookings WHERE id = ? AND user_id = ? AND status = 'confirmed'");
    $stmt_check->bind_param('ii', $booking_id, $user_id);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count > 0) {
        $stmt = $db->getConnection()->prepare("INSERT INTO invitations (user_id, booking_id, template_id, title, status) VALUES (?, ?, ?, ?, 'pending')");
        if ($stmt === false) {
            $error = 'Database error: ' . $db->getConnection()->error;
        } else {
            $stmt->bind_param('iiis', $user_id, $booking_id, $template_id, $event_title);
            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = 'Error saving invitation. Please try again.';
            }
        }
    } else {
        $error = 'Invalid booking selected.';
    }
}
?>
<div class="invitation-create-container">
    <h2 class="invitation-create-title">Create Invitation</h2>
    <?php if ($success): ?>
        <div class="alert alert-success" style="font-size: 1rem; border-radius: 7px; margin-bottom: 1.2rem; text-align: center;">Invitation created successfully and pending approval.</div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger" style="font-size: 1rem; border-radius: 7px; margin-bottom: 1.2rem; text-align: center;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (empty($approvedBookings)): ?>
        <div class="alert alert-info" style="font-size: 1rem; border-radius: 7px; margin-bottom: 1.2rem; text-align: center;">You don't have any confirmed bookings to create an invitation.</div>
    <?php else: ?>
        <form method="post" class="mb-4">
            <div class="invitation-form-row">
                <div class="form-group invitation-form-group">
                    <label class="invitation-label">Invitation Template</label>
                    <select name="template_id" id="template_id" class="invitation-form-control" required>
                        <option value="">Select Template</option>
                        <?php foreach ($templates as $tpl): ?>
                            <option value="<?= isset($tpl['id']) ? htmlspecialchars($tpl['id']) : '' ?>" data-html="<?= isset($tpl['html_content']) ? htmlspecialchars($tpl['html_content']) : '' ?>" data-css="<?= isset($tpl['css_content']) ? htmlspecialchars($tpl['css_content']) : '' ?>" data-fields='<?= isset($tpl['html_content']) ? json_encode(getTemplateFields($tpl['html_content'])) : "[]" ?>'><?= isset($tpl['name']) ? htmlspecialchars($tpl['name']) : 'Unnamed Template' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group invitation-form-group">
                    <label class="invitation-label">Reservation (Booking)</label>
                    <select name="booking_id" id="booking_id" class="invitation-form-control" required>
                        <option value="">Select Booking</option>
                        <?php foreach ($approvedBookings as $booking): ?>
                            <option value="<?= $booking['id'] ?>">
                                <?= htmlspecialchars($booking['hall_name']) ?> | <?= htmlspecialchars($booking['event_date']) ?> <?= htmlspecialchars($booking['start_time']) ?>-<?= htmlspecialchars($booking['end_time']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group invitation-form-group">
                    <label class="invitation-label">Event Title</label>
                    <input type="text" name="event_title" class="invitation-form-control" required>
                </div>
                
              
            </div>
            <div id="dynamicFields"></div>
            <div class="form-group">
                <button type="button" class="invitation-btn invitation-btn-preview mb-2" onclick="showTemplatePreview()">
                    <i class="fas fa-eye"></i> Preview Invitation
                </button>
            </div>
            <div class="form-group">
                <label class="invitation-label">Template Preview</label>
                <div id="templatePreview" class="invitation-template-preview"></div>
            </div>
            <!-- Buttons Row -->
            <div class="form-group invitation-btn-row">
                <a href="index.php" class="invitation-btn invitation-btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <button type="button" id="saveAsImageBtn" class="invitation-btn invitation-btn-info">
                    <i class="fas fa-download"></i> Save as Image
                </button>
               
               
            </div>
        </form>
    <?php endif; ?>
</div>
<!-- html2canvas CDN -->
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<!-- FontAwesome for download icon (if not already included) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script>
function getTemplateFieldsFromOption(option) {
    try {
        var fields = JSON.parse(option.getAttribute('data-fields'));
        return Array.isArray(fields) ? fields : [];
    } catch (e) {
        return [];
    }
}

function showDynamicFields() {
    var select = document.getElementById('template_id');
    var selected = select.options[select.selectedIndex];
    var fields = getTemplateFieldsFromOption(selected);
    var dynamicFields = document.getElementById('dynamicFields');
    dynamicFields.innerHTML = '';
    if(fields.length === 0) return;
    // 2 columns responsive grid
    dynamicFields.innerHTML = '<div id="fields-grid" style="display: flex; flex-wrap: wrap; gap: 2.3rem 3.5rem; margin-bottom: 1.2rem;">' +
        fields.map(function(field, idx) {
            return '<div style="flex: 1 1 320px; min-width: 260px; max-width: 400px; margin-bottom: 0.8rem;">' +
                '<label style="display:block; font-weight:500; color:#222; margin-bottom:0.35rem; font-size:1.07rem;">' +
                    field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) +
                '</label>' +
                '<input type="text" class="form-control" id="field_' + field + '" placeholder="' + field + '" style="width:100%; border-radius:7px; border:1.5px solid #e1e1e1; padding:0.53rem 0.9rem; font-size:1rem; transition:border 0.18s;">' +
            '</div>';
        }).join('') + '</div>';
}

function showTemplatePreview() {
    var select = document.getElementById('template_id');
    var preview = document.getElementById('templatePreview');
    var selected = select.options[select.selectedIndex];
    var html = selected.getAttribute('data-html') || '';
    var css = selected.getAttribute('data-css') || '';
    var fields = getTemplateFieldsFromOption(selected);
    // Get user field values
    var fieldValues = {};
    fields.forEach(function(field) {
        var input = document.getElementById('field_' + field);
        fieldValues[field] = input ? input.value : '{' + field + '}';
    });
    // Replace placeholders
    var htmlPreview = html;
    Object.keys(fieldValues).forEach(function(key) {
        var regex = new RegExp('{' + key + '}', 'g');
        htmlPreview = htmlPreview.replace(regex, fieldValues[key]);
    });
    if (!html) {
        preview.innerHTML = '<span class="text-muted">No template selected or template is empty.</span>';
    } else {
        preview.innerHTML = htmlPreview + '<style>' + css + '</style>';
    }
}

document.getElementById('template_id').addEventListener('change', function() {
    showDynamicFields();
    document.getElementById('templatePreview').innerHTML = '<span class="text-muted">Fill the fields and click Preview Invitation.</span>';
});

window.onload = function() {
    showDynamicFields();
    document.getElementById('templatePreview').innerHTML = '<span class="text-muted">Fill the fields and click Preview Invitation.</span>';
}

// Save as Image functionality
document.getElementById('saveAsImageBtn').addEventListener('click', function() {
    var preview = document.getElementById('templatePreview');
    if (
        !preview ||
        !preview.innerHTML.trim() ||
        preview.innerText.trim() === 'Fill the fields and click Preview Invitation.' ||
        preview.innerText.trim() === 'No template selected or template is empty.'
    ) {
        alert('The invitation image is not yet available. Please preview the invitation first.');
        return;
    }
    html2canvas(preview, {backgroundColor: null, useCORS: true, scale: 2}).then(function(canvas) {
        var link = document.createElement('a');
        link.download = 'invitation-template.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });
});

function getShareText() {
    var eventTitle = document.querySelector('input[name="event_title"]')?.value || 'Invitation';
    var url = window.location.href;
    return {
        text: eventTitle + ' - Check out this invitation!',
        url: url
    };
}

function shareOnWhatsApp() {
    var share = getShareText();
    var whatsappUrl = "https://wa.me/?text=" + encodeURIComponent(share.text + " " + share.url);
    window.open(whatsappUrl, '_blank');
}

function shareOnFacebook() {
    var share = getShareText();
    var facebookUrl = "https://www.facebook.com/sharer/sharer.php?u=" + encodeURIComponent(share.url);
    window.open(facebookUrl, '_blank');
}

function shareOnTwitter() {
    var share = getShareText();
    var twitterUrl = "https://twitter.com/intent/tweet?url=" + encodeURIComponent(share.url) + "&text=" + encodeURIComponent(share.text);
    window.open(twitterUrl, '_blank');
}

function shareOnLinkedIn() {
    var share = getShareText();
    var linkedinUrl = "https://www.linkedin.com/shareArticle?mini=true&url=" + encodeURIComponent(share.url) + "&title=" + encodeURIComponent(share.text);
    window.open(linkedinUrl, '_blank');
}

// Optionally close dropdown on click outside (for accessibility)
document.addEventListener('click', function(e) {
    var dropdown = document.querySelector('.share-dropdown');
    var menu = document.querySelector('.share-menu');
    if (!dropdown.contains(e.target)) {
        menu.style.opacity = 0;
        menu.style.pointerEvents = 'none';
        menu.style.transform = 'translateY(10px)';
    }
});
document.querySelector('.share-btn').addEventListener('click', function(e) {
    var menu = document.querySelector('.share-menu');
    if (menu.style.opacity === "1") {
        menu.style.opacity = 0;
        menu.style.pointerEvents = 'none';
        menu.style.transform = 'translateY(10px)';
    } else {
        menu.style.opacity = 1;
        menu.style.pointerEvents = 'auto';
        menu.style.transform = 'translateY(0)';
    }
});
</script>
<?php
// Helper function to extract placeholders from template html
function getTemplateFields($html) {
    preg_match_all('/{([a-zA-Z0-9_]+)}/', $html, $matches);
    return array_unique($matches[1]);
}
?>
