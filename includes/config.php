<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jo_halls');

define('GOOGLE_MAPS_API_KEY', 'AIzaSyBdOshwkZLDwLibwjvpcnalp1Sh1kOeiiE');

define('SITE_URL', 'http://localhost:8000');
define('SITE_NAME', 'Jo Halls');

// Root paths
define('SITE_ROOT', dirname(__DIR__));
define('UPLOADS_PATH', SITE_ROOT . '/uploads');


// Image upload directories
define('HALLS_IMAGES_PATH', UPLOADS_PATH . '/halls');
define('BLOG_IMAGES_PATH', UPLOADS_PATH . '/blog');
define('TEMPLATE_IMAGES_PATH', UPLOADS_PATH . '/templates');
define('USER_IMAGES_PATH', UPLOADS_PATH . '/users');


// Default images (relative to SITE_URL)
define('DEFAULT_IMAGE', 'assets/images/default.jpg');
define('DEFAULT_HALL_IMAGE', 'assets/images/default-hall.jpg');
define('DEFAULT_USER_IMAGE', 'assets/images/default-user.jpg');
define('DEFAULT_BLOG_IMAGE', 'assets/images/default-blog.jpg');
define('DEFAULT_TEMPLATE_IMAGE', 'assets/images/default-template.jpg');


// Maximum upload sizes
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB


error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
?>