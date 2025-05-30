<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['compare_halls'])) {
    $_SESSION['compare_halls'] = [];
}

$hallId = isset($_GET['hall_id']) ? (int)$_GET['hall_id'] : 0;

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':

        if ($hallId > 0) {
            $hall = getHallById($hallId);
            if ($hall) {

                if (!in_array($hallId, array_column($_SESSION['compare_halls'], 'id'))) {

                    if (count($_SESSION['compare_halls']) < 3) {
                        $_SESSION['compare_halls'][] = $hall;
                        echo json_encode(['success' => true, 'message' => 'Hall added to comparison']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Maximum 3 halls can be compared']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Hall already in comparison']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Hall not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid hall ID']);
        }
        break;

    case 'DELETE':

        if ($hallId > 0) {
            foreach ($_SESSION['compare_halls'] as $key => $hall) {
                if ($hall['id'] === $hallId) {
                    unset($_SESSION['compare_halls'][$key]);
                    $_SESSION['compare_halls'] = array_values($_SESSION['compare_halls']); 
                    echo json_encode(['success' => true, 'message' => 'Hall removed from comparison']);
                    exit;
                }
            }
            echo json_encode(['success' => false, 'message' => 'Hall not found in comparison']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid hall ID']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        break;
}
?>