<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$templateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT * FROM invitation_templates WHERE id = ?";
$stmt = $db->getConnection()->prepare($sql);
$stmt->bind_param('i', $templateId);
$stmt->execute();
$template = $stmt->get_result()->fetch_assoc();

if (!$template) {
    die('Template not found');
}

$sampleData = [
    'bride_name' => 'Sarah',
    'groom_name' => 'Michael',
    'bride_father' => 'Mr. Johnson',
    'groom_father' => 'Mr. Smith',
    'event_date' => 'Saturday, 15 June 2024',
    'event_time' => '5:00 PM',
    'venue_name' => 'Grand Palace Hall',
    'venue_address' => '123 Wedding Street, City',
    'rsvp_date' => '15 May 2024'
];

$rawHtml = $template['html_content'];
$html = $template['html_content'];
foreach ($sampleData as $key => $value) {
    $html = str_replace('{' . $key . '}', $value, $html);
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Invitation Preview</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f5f5f5;
            font-family: 'Noto Kufi Arabic', 'Playfair Display', serif;
        }
        .preview-header {
            background: #fff;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .preview-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .preview-title {
            font-size: 1.2rem;
            margin: 0;
        }
        .preview-actions {
            display: flex;
            gap: 1rem;
        }
        .preview-button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        .preview-button.primary {
            background: #4CAF50;
            color: white;
        }
        .preview-button.secondary {
            background: #f0f0f0;
            color: #333;
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
            margin: 0 auto;
            border-radius: 8px;
        }
        .edit-panel {
            position: fixed;
            right: 0;
            top: 0;
            width: 300px;
            height: 100vh;
            background: #fff;
            box-shadow: -2px 0 5px rgba(0,0,0,0.1);
            padding: 1rem;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        .edit-panel.active {
            transform: translateX(0);
        }
        .edit-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .form-group label {
            font-weight: bold;
            font-size: 0.9rem;
        }
        .form-group input {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        @media print {
            .preview-header, .edit-panel {
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
<script type="text/template" id="rawTemplate"><?php echo htmlspecialchars($rawHtml); ?></script>

    <div class="preview-header">
        <div class="preview-controls">
            <h1 class="preview-title"><?php echo htmlspecialchars($template['name']); ?></h1>
            <div class="preview-actions">
                <button class="preview-button secondary" onclick="toggleEditPanel()">
                    <i class="fas fa-edit"></i> Edit Text
                </button>
               
                <button class="preview-button secondary" onclick="window.close()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <div class="preview-container">
        <div class="preview-frame" id="invitationContent">
            <?php echo $html; ?>
        </div>
    </div>

    <div class="edit-panel" id="editPanel">
        <h3>Edit Invitation Details</h3>
        <div class="edit-form">
            <div class="form-group">
                <label for="brideName">Bride's Name</label>
                <input type="text" id="brideName" value="<?php echo $sampleData['bride_name']; ?>" 
                       onchange="updatePreview('bride_name', this.value)">
            </div>
            <div class="form-group">
                <label for="groomName">Groom's Name</label>
                <input type="text" id="groomName" value="<?php echo $sampleData['groom_name']; ?>"
                       onchange="updatePreview('groom_name', this.value)">
            </div>
            <div class="form-group">
                <label for="brideFather">Bride's Father</label>
                <input type="text" id="brideFather" value="<?php echo $sampleData['bride_father']; ?>"
                       onchange="updatePreview('bride_father', this.value)">
            </div>
            <div class="form-group">
                <label for="groomFather">Groom's Father</label>
                <input type="text" id="groomFather" value="<?php echo $sampleData['groom_father']; ?>"
                       onchange="updatePreview('groom_father', this.value)">
            </div>
            <div class="form-group">
                <label for="eventDate">Event Date</label>
                <input type="text" id="eventDate" value="<?php echo $sampleData['event_date']; ?>"
                       onchange="updatePreview('event_date', this.value)">
            </div>
            <div class="form-group">
                <label for="eventTime">Event Time</label>
                <input type="text" id="eventTime" value="<?php echo $sampleData['event_time']; ?>"
                       onchange="updatePreview('event_time', this.value)">
            </div>
            <div class="form-group">
                <label for="venueName">Venue Name</label>
                <input type="text" id="venueName" value="<?php echo $sampleData['venue_name']; ?>"
                       onchange="updatePreview('venue_name', this.value)">
            </div>
           
        </div>
    </div>

    <script>
  let originalHtml = `<?php echo addslashes($rawHtml); ?>`;

    let formData = {
        bride_name: '<?php echo $sampleData['bride_name']; ?>',
        groom_name: '<?php echo $sampleData['groom_name']; ?>',
        bride_father: '<?php echo $sampleData['bride_father']; ?>',
        groom_father: '<?php echo $sampleData['groom_father']; ?>',
        event_date: '<?php echo $sampleData['event_date']; ?>',
        event_time: '<?php echo $sampleData['event_time']; ?>',
        venue_name: '<?php echo $sampleData['venue_name']; ?>',
        venue_address: '<?php echo $sampleData['venue_address']; ?>',
    };

    function toggleEditPanel() {
        document.getElementById('editPanel').classList.toggle('active');
    }

    function updatePreview(field, value) {
        formData[field] = value;

        let updatedHtml = originalHtml;
        for (let key in formData) {
            const regex = new RegExp(`{${key}}`, 'g');
            updatedHtml = updatedHtml.replace(regex, formData[key]);
        }

        document.getElementById('invitationContent').innerHTML = updatedHtml;
    }
</script>

</body>
</html>