<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Set logout message
setMessage('success', 'You have been successfully logged out.');

// Redirect to login page
header('Location: ' . SITE_URL . '/users/login.php');
exit();
?>
