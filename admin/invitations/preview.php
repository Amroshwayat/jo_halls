<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . SITE_URL . '/users/login.php');
    exit();
}

$templateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$templateId) {
    die('Invalid template ID');
}

$sql = "SELECT * FROM invitation_templates WHERE id = ?";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param('i', $templateId);
$stmt->execute();
$result = $stmt->get_result();
$template = $result->fetch_assoc();

if (!$template) {
    die('Template not found');
}

$sampleData = [
    'bride_name' => 'Sarah Johnson',
    'groom_name' => 'Michael Smith',
    'event_date' => 'Saturday, June 15th, 2024',
    'event_time' => '5:00 PM',
    'venue_name' => 'Grand Palace Hall',
    'venue_address' => '123 Wedding Street, City',
    'rsvp_date' => 'May 15th, 2024'
];

$html = $template['html_content'];
foreach ($sampleData as $key => $value) {
    $html = str_replace('{' . $key . '}', $value, $html);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?php echo htmlspecialchars($template['name']); ?></title>
    <link href="<?php echo SITE_URL; ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .preview-header {
            background: #fff;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .preview-container {
            flex: 1;
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .preview-frame {
            background: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 800px;
            width: 100%;
        }
        @media print {
            .preview-header {
                display: none;
            }
            .preview-container {
                padding: 0;
            }
            .preview-frame {
                box-shadow: none;
            }
        }
        <?php echo $template['css_content']; ?>
    </style>
</head>
<body>
    <div class="preview-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Preview: <?php echo htmlspecialchars($template['name']); ?></h5>
                <div class="btn-group">
                   
                    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="preview-container">
        <div class="preview-frame">
            <?php echo $html; ?>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/jquery.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-kit-id.js"></script>
</body>
</html>