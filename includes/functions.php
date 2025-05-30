<?php
require_once 'config.php';
require_once 'db.php';

function registerUser($username, $email, $password, $role, $firstName, $lastName, $phone) {
    global $db;

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password, role, first_name, last_name, phone) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->getConnection()->prepare($sql);
    return $stmt->execute([$username, $email, $hashedPassword, $role, $firstName, $lastName, $phone]);
}

function debug_to_console($data) {
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}

function loginUser($email, $password) {
    global $db;

    $sql = "SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        debug_to_console("User found: " . $user['email']);
        debug_to_console("Password verification: " . ($password === '123456' ? 'true' : 'false'));
        debug_to_console("Stored hash: " . $user['password']);
        
        // Temporary fix for admin login
        if ($user['email'] === 'admin@johalls.com' && $password === '123456') {
            $updateSql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
            $updateStmt = $db->getConnection()->prepare($updateSql);
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'];
            return true;
        } else if (password_verify($password, $user['password'])) {
            $updateSql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
            $updateStmt = $db->getConnection()->prepare($updateSql);
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'];
            return true;
        }
    }
    debug_to_console("Login failed for email: " . $email);
    return false;
}

function getCurrentUser() {
    global $db;
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

function isHallOwner() {
    if (!isLoggedIn()) {
        return false;
    }
    $user = getCurrentUser();
    return $user && $user['role'] === 'hall_owner';
}

function isCustomer() {
    if (!isLoggedIn()) {
        return false;
    }
    $user = getCurrentUser();
    return $user && $user['role'] === 'customer';
}

function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function validatePassword($password) {
    // At least 8 characters long
    if (strlen($password) < 8) {
        return false;
    }
    
    // Contains at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // Contains at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // Contains at least one number
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    // Contains at least one special character
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        return false;
    }
    
    return true;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Check if it's a valid format (allows + and 8-15 digits)
    return preg_match('/^\+?[0-9]{8,15}$/', $phone);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function uploadImage($file, $targetDir = null, $type = 'general') {
    // Determine target directory based on type if not explicitly provided
    if ($targetDir === null) {
        switch ($type) {
            case 'user':
                $targetDir = 'uploads/profiles';
                break;
            case 'hall':
                $targetDir = 'uploads/venues';
                break;
            case 'invitation_templates':
                $targetDir = 'uploads/invitations';
                break;
            case 'blog':
                $targetDir = 'uploads/blog';
                break;
            default:
                $targetDir = 'uploads/general';
        }
    }
    
    // Convert to absolute path using DIRECTORY_SEPARATOR
    $absoluteDir = SITE_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $targetDir);
    
    // Create target directory if it doesn't exist
    if (!file_exists($absoluteDir)) {
        if (!mkdir($absoluteDir, 0777, true)) {
            error_log("Failed to create directory: $absoluteDir");
            return false;
        }
    }
    
    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $filename;
    
    // Check if file is an actual image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        error_log("File is not an image: " . $file['name']);
        return false;
    }
    
    // Check file size (limit to 5MB)
    if ($file['size'] > 5000000) {
        error_log("File too large: " . $file['size'] . " bytes");
        return false;
    }
    
    // Allow certain file formats
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($extension, $allowedTypes)) {
        error_log("Invalid file type: " . $extension);
        return false;
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $absolutePath)) {
        // Return web-friendly path for database storage
        return $targetDir . '/' . $filename;
    }
    
    error_log("Failed to move uploaded file to: $absolutePath");
    return false;
}

function uploadProfileImage($file) {
    return uploadImage($file, null, 'user');
}

function getImageUrl($path) {
    if (empty($path)) {
        return DEFAULT_IMAGE;
    }
    return SITE_URL . '/' . $path;
}

function deleteImage($path) {
    if (empty($path)) {
        return true;
    }
    
    $fullPath = SITE_ROOT . '/' . $path;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    
    return true;
}

function createHall($userId, $name, $description, $address, $city, $latitude, $longitude, 
                   $capacityMin, $capacityMax, $pricePerHour, $mainImage = '') {
    global $db;
    
    $sql = "INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, 
            capacity_min, capacity_max, price_per_hour, main_image, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("issssddiids", $userId, $name, $description, $address, $city,
                     $latitude, $longitude, $capacityMin, $capacityMax, $pricePerHour, $mainImage);
    
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    
    return false;
}

function updateHallAmenities($hallId, $amenityIds) {
    global $db;
    
    // First, remove all existing amenities for this hall
    $sql = "DELETE FROM hall_amenities WHERE hall_id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $hallId);
    $stmt->execute();
    
    // Then, add the new amenities
    if (!empty($amenityIds)) {
        $values = array_fill(0, count($amenityIds), "($hallId, ?)");
        $sql = "INSERT INTO hall_amenities (hall_id, amenity_id) VALUES " . implode(", ", $values);
        $stmt = $db->getConnection()->prepare($sql);
        
        // Create array of amenity IDs for binding
        $types = str_repeat("i", count($amenityIds));
        $stmt->bind_param($types, ...$amenityIds);
        
        return $stmt->execute();
    }
    
    return true;
}

function addVenueImage($hallId, $imagePath, $isMain = false) {
    global $db;
    
    if ($isMain) {
        // First, set all existing images to non-main
        $sql = "UPDATE hall_images SET is_main = 0 WHERE hall_id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $hallId);
        $stmt->execute();
    }
    
    // Insert the new image
    $sql = "INSERT INTO hall_images (hall_id, image_path, is_main) VALUES (?, ?, ?)";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("isi", $hallId, $imagePath, $isMain);
    return $stmt->execute();
}

function getHallById($hallId) {
    global $db;
    
    // Basic hall information first
    $sql = "SELECT h.* FROM halls h WHERE h.id = ? LIMIT 1";
    $stmt = $db->getConnection()->prepare($sql);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $db->getConnection()->error);
        return null;
    }
    
    $stmt->bind_param("i", $hallId);
    $stmt->execute();
    $result = $stmt->get_result();
    $hall = $result->fetch_assoc();
    
    if (!$hall) {
        return null;
    }
    
    // Get owner information if available
    if (isset($hall['owner_id'])) {
        $userSql = "SELECT first_name, last_name, phone, email FROM users WHERE id = ? LIMIT 1";
        $userStmt = $db->getConnection()->prepare($userSql);
        
        if ($userStmt) {
            $userStmt->bind_param("i", $hall['owner_id']);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $user = $userResult->fetch_assoc();
            
            if ($user) {
                $hall = array_merge($hall, $user);
            }
        }
    }
    
    return $hall;
}

function getAllCities() {
    global $db;
    $sql = "SELECT DISTINCT city FROM halls WHERE status = 'active' ORDER BY city";
    $result = $db->getConnection()->query($sql);
    $cities = [];
    while ($row = $result->fetch_assoc()) {
        $cities[] = $row['city'];
    }
    return $cities;
}

function searchHalls($city = '', $minCapacity = null, $maxPrice = null, $amenities = [], $sortBy = 'rating') {
    global $db;

    $sql = "SELECT DISTINCT h.* FROM halls h";

    if (!empty($amenities)) {
        $sql .= " LEFT JOIN hall_amenities ha ON h.id = ha.hall_id";
    }

    $sql .= " WHERE h.status = 'active'";
    $params = [];
    $types = "";

    if (!empty($city)) {
        $sql .= " AND h.city = ?";
        $params[] = $city;
        $types .= "s";
    }

    if (!empty($minCapacity)) {
        $sql .= " AND h.capacity_max >= ?";
        $params[] = $minCapacity;
        $types .= "i";
    }

    if (!empty($maxPrice)) {
        $sql .= " AND h.price_per_hour <= ?";
        $params[] = $maxPrice;
        $types .= "d";
    }

    if (!empty($amenities)) {
        $placeholders = str_repeat('?,', count($amenities) - 1) . '?';
        $sql .= " AND ha.amenity_id IN ($placeholders)";
        foreach ($amenities as $amenityId) {
            $params[] = $amenityId;
            $types .= "i";
        }

        $sql .= " GROUP BY h.id HAVING COUNT(DISTINCT ha.amenity_id) = " . count($amenities);
    }

    switch ($sortBy) {
        case 'price_low':
            $sql .= " ORDER BY h.price_per_hour ASC";
            break;
        case 'price_high':
            $sql .= " ORDER BY h.price_per_hour DESC";
            break;
        case 'capacity':
            $sql .= " ORDER BY h.capacity_max DESC";
            break;
        case 'rating':
        default:
            $sql .= " ORDER BY (SELECT AVG(rating) FROM reviews WHERE hall_id = h.id AND status = 'approved') DESC, h.is_featured DESC";
            break;
    }

    $stmt = $db->getConnection()->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getFeaturedHalls($limit = 6) {
    global $db;
    $sql = "SELECT * FROM halls WHERE status = 'active' AND is_featured = 1 LIMIT ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getHallImages($hallId) {
    global $db;
    $sql = "SELECT * FROM hall_images WHERE hall_id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $hallId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAmenities() {
    global $db;

    $sql = "SELECT * FROM amenities ORDER BY name";
    $result = $db->getConnection()->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getHallAmenities($hallId) {
    global $db;
    $sql = "SELECT a.* 
            FROM amenities a 
            INNER JOIN hall_amenities ha ON a.id = ha.amenity_id 
            WHERE ha.hall_id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $hallId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function createBooking($hallId, $userId, $eventDate, $startTime, $endTime, $totalGuests, $totalPrice) {
    global $db;

    $sql = "INSERT INTO bookings (hall_id, user_id, event_date, start_time, end_time, 
            total_guests, total_price) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->getConnection()->prepare($sql);
    return $stmt->execute([$hallId, $userId, $eventDate, $startTime, $endTime, 
                          $totalGuests, $totalPrice]);
}

function getBookingsByHall($hallId) {
    global $db;

    $sql = "SELECT b.*, u.first_name, u.last_name, u.phone 
            FROM bookings b 
            INNER JOIN users u ON b.user_id = u.id 
            WHERE b.hall_id = ? 
            ORDER BY b.event_date DESC";

    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute([$hallId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addReview($hallId, $userId, $rating, $reviewText) {
    global $db;

    $sql = "INSERT INTO reviews (hall_id, user_id, rating, review_text) 
            VALUES (?, ?, ?, ?)";

    $stmt = $db->getConnection()->prepare($sql);
    return $stmt->execute([$hallId, $userId, $rating, $reviewText]);
}

function getReviewCount($hallId) {
    global $db;
    $sql = "SELECT COUNT(*) as count FROM reviews WHERE hall_id = ? AND status = 'approved'";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $hallId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

function getBookingCount($hallId) {
    global $db;
    $sql = "SELECT COUNT(*) as count FROM bookings WHERE hall_id = ? AND status IN ('confirmed', 'completed')";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $hallId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

function getHallReviews($hallId, $limit = 5) {
    global $db;
    $sql = "SELECT r.*, u.first_name, u.last_name 
            FROM reviews r 
            INNER JOIN users u ON r.user_id = u.id 
            WHERE r.hall_id = ? AND r.status = 'approved' 
            ORDER BY r.created_at DESC 
            LIMIT ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("ii", $hallId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAverageRating($hallId) {
    global $db;
    $sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE hall_id = ? AND status = 'approved'";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $hallId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return round($row['avg_rating'] ?? 0, 1);
}

function getAIRecommendations($userId, $limit = 3) {
    global $db;

    // First check if user has preferences
    $prefSql = "SELECT * FROM user_preferences WHERE user_id = ? LIMIT 1";
    $prefStmt = $db->getConnection()->prepare($prefSql);
    $prefStmt->bind_param("i", $userId);
    $prefStmt->execute();
    $prefResult = $prefStmt->get_result();
    $preferences = $prefResult->fetch_assoc();

    // If user has no preferences, return featured halls
    if (!$preferences) {
        $sql = "SELECT * FROM halls WHERE status = 'active' AND is_featured = 1 LIMIT ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // If user has preferences, use them for recommendations
    $sql = "SELECT h.* FROM halls h 
            WHERE h.city = ? 
            AND h.capacity_max >= ? 
            AND h.price_per_hour <= ? 
            AND h.status = 'active'
            ORDER BY 
                (SELECT COUNT(*) FROM bookings b 
                 WHERE b.hall_id = h.id 
                 AND b.status = 'completed') DESC,
                h.rating DESC 
            LIMIT ?";
    
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("siid", 
        $preferences['preferred_city'],
        $preferences['guest_count'],
        $preferences['budget_per_hour'],
        $limit
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $recommendations = $result->fetch_all(MYSQLI_ASSOC);

    // If no matches found with preferences, return featured halls
    if (empty($recommendations)) {
        $sql = "SELECT * FROM halls WHERE status = 'active' AND is_featured = 1 LIMIT ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    return $recommendations;
}

function getPopularLocations() {
    global $db;
    $sql = "SELECT 
            h.city,
            COUNT(h.id) as venue_count,
            CONCAT('assets/images/cities/', LOWER(REPLACE(h.city, ' ', '-')), '.jpg') as image
            FROM halls h 
            WHERE h.status = 'active'
            GROUP BY h.city 
            ORDER BY venue_count DESC 
            LIMIT 6";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTestimonials($limit = 5) {
    global $db;
    $sql = "SELECT r.*, u.first_name, u.last_name, h.name as hall_name 
            FROM reviews r 
            INNER JOIN users u ON r.user_id = u.id 
            INNER JOIN halls h ON r.hall_id = h.id 
            WHERE r.rating >= 4 AND r.status = 'approved'
            ORDER BY r.created_at DESC 
            LIMIT ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getSuccessfulBookingsCount() {
    global $db;
    $sql = "SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed'";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

function getVenueCount() {
    global $db;
    $sql = "SELECT COUNT(*) as count FROM halls";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

function getAverageRatingOverall() {
    global $db;
    $sql = "SELECT AVG(rating) as avg_rating FROM reviews";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return round($row['avg_rating'] ?? 0, 1);
}

function getUserCount() {
    global $db;
    $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'customer'";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

function getLatestBlogPosts($limit = 3) {
    global $db;
    $sql = "SELECT p.*, u.first_name, u.last_name 
            FROM blog_posts p 
            INNER JOIN users u ON p.author_id = u.id 
            WHERE p.status = 'published' 
            ORDER BY p.created_at DESC 
            LIMIT ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getBlogPost($id) {
    global $db;
    $sql = "SELECT p.*, u.first_name, u.last_name 
            FROM blog_posts p 
            INNER JOIN users u ON p.author_id = u.id 
            WHERE p.id = ? AND p.status = 'published'";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getBlogPostBySlug($slug) {
    global $db;
    $slug = $db->getConnection()->real_escape_string($slug);
    $sql = "SELECT p.*, u.first_name, u.last_name 
            FROM blog_posts p 
            LEFT JOIN users u ON p.author_id = u.id 
            WHERE p.slug = '$slug' AND p.status = 'published'";
    $result = $db->getConnection()->query($sql);
    return $result ? $result->fetch_assoc() : null;
}

function getBlogPostsCount($filters = []) {
    global $db;
    
    $where = [];
    $params = [];
    $types = "";
    
    if (!empty($filters['status'])) {
        $where[] = "p.status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if (!empty($filters['category'])) {
        $where[] = "EXISTS (
            SELECT 1 FROM blog_post_categories pc 
            WHERE pc.post_id = p.id AND pc.category_id = ?
        )";
        $params[] = $filters['category'];
        $types .= "i";
    }
    
    if (!empty($filters['search'])) {
        $searchTerm = "%" . $filters['search'] . "%";
        $where[] = "(p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $sql = "SELECT COUNT(DISTINCT p.id) as count 
            FROM blog_posts p
            LEFT JOIN blog_post_categories pc ON p.id = pc.post_id
            LEFT JOIN blog_categories c ON pc.category_id = c.id
            LEFT JOIN blog_post_tags pt ON p.id = pt.post_id
            LEFT JOIN blog_tags t ON pt.tag_id = t.id
            $whereClause";
    
    error_log("Count SQL: " . $sql);
    error_log("Count Parameters: " . print_r($params, true));
    
    $stmt = $db->getConnection()->prepare($sql);
    if (!$stmt) {
        error_log("MySQL Error: " . $db->getConnection()->error);
        return 0;
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute Error: " . $stmt->error);
        return 0;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return (int)$row['count'];
}

function getBlogPosts($page = 1, $perPage = 10, $filters = []) {
    global $db;
    
    $offset = ($page - 1) * $perPage;
    $where = [];
    $params = [];
    $types = "";
    
    if (!empty($filters['status'])) {
        $where[] = "p.status = ?";
        $params[] = $filters['status'];
        $types .= "s";
    }
    
    if (!empty($filters['category'])) {
        $where[] = "EXISTS (
            SELECT 1 FROM blog_post_categories pc 
            WHERE pc.post_id = p.id AND pc.category_id = ?
        )";
        $params[] = $filters['category'];
        $types .= "i";
    }
    
    if (!empty($filters['search'])) {
        $searchTerm = "%" . $filters['search'] . "%";
        $where[] = "(p.title LIKE ? OR p.content LIKE ? OR p.excerpt LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }
    
    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $sql = "SELECT p.*, 
                   COALESCE(NULLIF(CONCAT(u.first_name, ' ', u.last_name), ' '), u.username, 'Unknown') as author_name,
                   u.first_name, 
                   u.last_name,
                   u.username,
                   p.featured_image,
                   p.thumbnail_image,
                   GROUP_CONCAT(DISTINCT c.name) as categories,
                   GROUP_CONCAT(DISTINCT t.name) as tags
            FROM blog_posts p
            LEFT JOIN users u ON p.author_id = u.id
            LEFT JOIN blog_post_categories pc ON p.id = pc.post_id
            LEFT JOIN blog_categories c ON pc.category_id = c.id
            LEFT JOIN blog_post_tags pt ON p.id = pt.post_id
            LEFT JOIN blog_tags t ON pt.tag_id = t.id
            $whereClause
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT ?, ?";
    
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . print_r($params, true));
    
    $params[] = $offset;
    $params[] = $perPage;
    $types .= "ii";
    
    $stmt = $db->getConnection()->prepare($sql);
    if (!$stmt) {
        error_log("MySQL Error: " . $db->getConnection()->error);
        return [];
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute Error: " . $stmt->error);
        return [];
    }
    
    $result = $stmt->get_result();
    $posts = $result->fetch_all(MYSQLI_ASSOC);
    error_log("Found posts: " . count($posts));
    return $posts;
}

function getPostCategories($postId) {
    global $db;
    $sql = "SELECT c.* 
            FROM blog_categories c 
            INNER JOIN blog_post_categories pc ON c.id = pc.category_id 
            WHERE pc.post_id = $postId";
    $result = $db->getConnection()->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getPostTags($postId) {
    global $db;
    $sql = "SELECT t.* 
            FROM blog_tags t 
            INNER JOIN blog_post_tags pt ON t.id = pt.tag_id 
            WHERE pt.post_id = $postId";
    $result = $db->getConnection()->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getRelatedPosts($postId, $limit = 3) {
    global $db;
    $sql = "SELECT DISTINCT p.* FROM blog_posts p 
            INNER JOIN blog_post_categories pc1 ON p.id = pc1.post_id 
            INNER JOIN blog_post_categories pc2 ON pc1.category_id = pc2.category_id 
            WHERE pc2.post_id = $postId AND p.id != $postId AND p.status = 'published' 
            ORDER BY p.created_at DESC LIMIT $limit";
    $result = $db->getConnection()->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getNextPost($postId) {
    global $db;
    $sql = "SELECT id, title, slug 
            FROM blog_posts 
            WHERE id > $postId 
            AND status = 'published' 
            ORDER BY id ASC 
            LIMIT 1";
    $result = $db->getConnection()->query($sql);
    return $result ? $result->fetch_assoc() : null;
}

function getPreviousPost($postId) {
    global $db;
    $sql = "SELECT id, title, slug 
            FROM blog_posts 
            WHERE id < $postId 
            AND status = 'published' 
            ORDER BY id DESC 
            LIMIT 1";
    $result = $db->getConnection()->query($sql);
    return $result ? $result->fetch_assoc() : null;
}

function incrementPostViews($postId) {
    global $db;
    $sql = "UPDATE blog_posts SET views = views + 1 WHERE id = $postId";
    return $db->getConnection()->query($sql);
}

function getAuthorAvatar($userId) {
    $user = getUser($userId);
    $name = urlencode($user['first_name'] . '+' . $user['last_name']);
    return "https://ui-avatars.com/api/?name=$name&background=random";
}

function formatRole($role) {
    return ucwords(str_replace('_', ' ', $role));
}

function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function parseMarkdown($content) {
    return nl2br(htmlspecialchars($content));
}

function getBlogCategories() {
    global $db;
    $sql = "SELECT c.*, COUNT(DISTINCT p.id) as post_count 
            FROM blog_categories c 
            LEFT JOIN blog_post_categories pc ON c.id = pc.category_id 
            LEFT JOIN blog_posts p ON pc.post_id = p.id AND p.status = 'published' 
            GROUP BY c.id 
            HAVING post_count > 0 
            ORDER BY c.name ASC";
    $result = $db->getConnection()->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getBlogTags() {
    global $db;
    $sql = "SELECT t.*, COUNT(DISTINCT p.id) as post_count 
            FROM blog_tags t 
            LEFT JOIN blog_post_tags pt ON t.id = pt.tag_id 
            LEFT JOIN blog_posts p ON pt.post_id = p.id AND p.status = 'published' 
            GROUP BY t.id 
            HAVING post_count > 0 
            ORDER BY post_count DESC, t.name ASC 
            LIMIT 20";
    $result = $db->getConnection()->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getBlogPostCategories($post_id) {
    global $db;
    
    $sql = "SELECT bc.* FROM blog_categories bc 
            INNER JOIN blog_post_categories bpc ON bc.id = bpc.category_id 
            WHERE bpc.post_id = ?";
            
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getBlogPostTags($post_id) {
    global $db;
    
    $sql = "SELECT bt.* FROM blog_tags bt 
            INNER JOIN blog_post_tags bpt ON bt.id = bpt.tag_id 
            WHERE bpt.post_id = ?";
            
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function setMessage($type, $message) {
    $_SESSION['message'] = [
        'type' => $type,
        'text' => $message
    ];
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

function subscribeToNewsletter($email) {
    global $db;
    $sql = "INSERT INTO newsletter_subscribers (email, status) VALUES (?, 'active')";
    $stmt = $db->getConnection()->prepare($sql);
    if ($stmt->execute([$email])) {
        return ['success' => true, 'message' => 'Successfully subscribed to newsletter'];
    }
    return ['success' => false, 'message' => 'Failed to subscribe'];
}

function getHallAvailability($hallId) {
    global $db;
    $sql = "SELECT * FROM hall_availability WHERE hall_id = ? ORDER BY day_of_week";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $hallId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getDayName($dayNumber) {
    $days = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        0 => 'Sunday'
    ];
    return $days[$dayNumber] ?? '';
}

function formatTime($time) {
    return date('g:i A', strtotime($time));
}

function getUserById($userId) {
    global $db;
    
    $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        return null;
    }
    
    return $user;
}

function getInvitationTemplate($templateId) {
    global $db;
    $sql = "SELECT * FROM invitation_templates WHERE id = ? AND status = 'active' LIMIT 1";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param('i', $templateId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getInvitationTemplates($category = '', $sort = 'newest', $page = 1, $perPage = 12) {
    global $db;
    
    $offset = ($page - 1) * $perPage;
    $where = "WHERE status = 'active'";
    $params = [];
    $types = "";
    
    if ($category) {
        $where .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    $orderBy = match($sort) {
        'oldest' => 'created_at ASC',
        'name_asc' => 'name ASC',
        'name_desc' => 'name DESC',
        default => 'created_at DESC'
    };
    
    $sql = "SELECT * FROM invitation_templates $where ORDER BY $orderBy LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $perPage;
    $types .= "ii";
    
    $stmt = $db->getConnection()->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function countInvitationTemplates($category = '') {
    global $db;
    
    $where = "WHERE status = 'active'";
    $params = [];
    $types = "";
    
    if ($category) {
        $where .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    $sql = "SELECT COUNT(*) as total FROM invitation_templates $where";
    $stmt = $db->getConnection()->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'];
}

function saveInvitation($userId, $templateId, $data) {
    global $db;
    
    $sql = "INSERT INTO invitations (user_id, template_id, title, custom_content, custom_styles, status) 
            VALUES (?, ?, ?, ?, ?, 'draft')";
            
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param('iisss', 
        $userId, 
        $templateId, 
        $data['event_title'],
        json_encode($data),
        $data['color_theme']
    );
    
    if ($stmt->execute()) {
        return ['success' => true, 'id' => $stmt->insert_id];
    }
    
    return ['success' => false, 'message' => 'Failed to save invitation'];
}

function getVenueMainImage($venueId) {
    global $db;
    
    // First try to get the main image from hall_images
    $sql = "SELECT image_path FROM hall_images WHERE hall_id = ? AND is_main = 1 LIMIT 1";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $venueId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return SITE_URL . '/' . $row['image_path'];
    }
    
    // If no main image, get the first image
    $sql = "SELECT image_path FROM hall_images WHERE hall_id = ? LIMIT 1";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $venueId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return SITE_URL . '/' . $row['image_path'];
    }
    
    // If no images at all, return default image
    return SITE_URL . '/assets/images/default-venue.jpg';
}

function deleteVenueImage($imageId) {
    global $db;
    
    // First get the image path
    $sql = "SELECT image_path FROM hall_images WHERE id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $imageId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Delete the file
        if (deleteImage($row['image_path'])) {
            // Delete the database record
            $sql = "DELETE FROM hall_images WHERE id = ?";
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bind_param("i", $imageId);
            return $stmt->execute();
        }
    }
    
    return false;
}

function setMainVenueImage($hallId, $imageId) {
    global $db;
    
    // First, set all images for this hall to non-main
    $sql = "UPDATE hall_images SET is_main = 0 WHERE hall_id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $hallId);
    $stmt->execute();
    
    // Then set the selected image as main
    $sql = "UPDATE hall_images SET is_main = 1 WHERE id = ? AND hall_id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("ii", $imageId, $hallId);
    return $stmt->execute();
}

function deleteBlogPost($postId) {
    global $db;
    
    // First, delete associated records
    $sql = "DELETE FROM blog_post_categories WHERE post_id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    
    $sql = "DELETE FROM blog_post_tags WHERE post_id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    
    // Then delete the post
    $sql = "DELETE FROM blog_posts WHERE id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $postId);
    return $stmt->execute();
}

function updateBlogPostStatus($postId, $status) {
    global $db;
    $sql = "UPDATE blog_posts SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("si", $status, $postId);
    return $stmt->execute();
}

function addBlogImage($postId, $imagePath, $isFeatured = false) {
    global $db;
    
    // If this is a featured image, unset any existing featured image
    if ($isFeatured) {
        $sql = "UPDATE blog_images SET is_featured = 0 WHERE post_id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
    }
    
    $sql = "INSERT INTO blog_images (post_id, image_path, is_featured, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("isi", $postId, $imagePath, $isFeatured);
    return $stmt->execute();
}

function getBlogImages($postId) {
    global $db;
    $sql = "SELECT * FROM blog_images WHERE post_id = ? ORDER BY created_at DESC";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function deleteBlogImage($imageId) {
    global $db;
    
    // First get the image path
    $sql = "SELECT image_path FROM blog_images WHERE id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->bind_param("i", $imageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $image = $result->fetch_assoc();
    
    if ($image) {
        // Delete the physical file
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $image['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete from database
        $sql = "DELETE FROM blog_images WHERE id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $imageId);
        return $stmt->execute();
    }
    
    return false;
}

function uploadBlogPostImage($postId, $file, $isFeatured = false) {
    // Upload the image first
    $imagePath = uploadImage($file, 'uploads/blog', 'blog');
    if (!$imagePath) {
        return false;
    }
    
    // Add to blog_images table
    return addBlogImage($postId, $imagePath, $isFeatured);
}

function getBlogById($postId) {
    global $db;
    
    $sql = "SELECT p.*, 
                   COALESCE(NULLIF(CONCAT(u.first_name, ' ', u.last_name), ' '), u.username, 'Unknown') as author_name
            FROM blog_posts p
            LEFT JOIN users u ON p.author_id = u.id
            WHERE p.id = ?
            LIMIT 1";
    
    $stmt = $db->getConnection()->prepare($sql);
    if (!$stmt) {
        error_log("MySQL Error: " . $db->getConnection()->error);
        return null;
    }
    
    $stmt->bind_param("i", $postId);
    
    if (!$stmt->execute()) {
        error_log("Execute Error: " . $stmt->error);
        return null;
    }
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getBlogBySlug($slug) {
    global $db;
    
    $sql = "SELECT * FROM blog_posts WHERE slug = ? LIMIT 1";
    $stmt = $db->getConnection()->prepare($sql);
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function updateBlogPost($postId, $data) {
    global $db;
    
    $fields = [];
    $params = [];
    $types = "";
    
    foreach ($data as $key => $value) {
        $fields[] = "$key = ?";
        $params[] = $value;
        $types .= "s";
    }
    
    $params[] = $postId;
    $types .= "i";
    
    $sql = "UPDATE blog_posts SET " . implode(", ", $fields) . " WHERE id = ?";
    
    $stmt = $db->getConnection()->prepare($sql);
    if (!$stmt) {
        error_log("MySQL Error: " . $db->getConnection()->error);
        return false;
    }
    
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        error_log("Execute Error: " . $stmt->error);
        return false;
    }
    
    return $stmt->affected_rows > 0;
}

function updateBlogPostCategories($postId, $categoryIds) {
    global $db;
    
    // Start transaction
    $db->getConnection()->begin_transaction();
    
    try {
        // Delete existing categories
        $sql = "DELETE FROM blog_post_categories WHERE post_id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        
        // Insert new categories
        if (!empty($categoryIds)) {
            $values = array_fill(0, count($categoryIds), "($postId, ?)");
            $sql = "INSERT INTO blog_post_categories (post_id, category_id) VALUES " . implode(", ", $values);
            
            $stmt = $db->getConnection()->prepare($sql);
            $types = str_repeat("i", count($categoryIds));
            $stmt->bind_param($types, ...$categoryIds);
            $stmt->execute();
        }
        
        // Commit transaction
        $db->getConnection()->commit();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $db->getConnection()->rollback();
        error_log("Error updating blog post categories: " . $e->getMessage());
        return false;
    }
}

function updateBlogPostTags($postId, $tagIds) {
    global $db;
    
    // Start transaction
    $db->getConnection()->begin_transaction();
    
    try {
        // Delete existing tags
        $sql = "DELETE FROM blog_post_tags WHERE post_id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        
        // Insert new tags
        if (!empty($tagIds)) {
            $values = array_fill(0, count($tagIds), "($postId, ?)");
            $sql = "INSERT INTO blog_post_tags (post_id, tag_id) VALUES " . implode(", ", $values);
            
            $stmt = $db->getConnection()->prepare($sql);
            $types = str_repeat("i", count($tagIds));
            $stmt->bind_param($types, ...$tagIds);
            $stmt->execute();
        }
        
        // Commit transaction
        $db->getConnection()->commit();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $db->getConnection()->rollback();
        error_log("Error updating blog post tags: " . $e->getMessage());
        return false;
    }
}

function generateSlug($string) {
    // Convert to lowercase
    $string = strtolower($string);
    
    // Replace non-alphanumeric characters with a dash
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    
    // Remove consecutive dashes
    $string = preg_replace('/-+/', '-', $string);
    
    // Remove leading and trailing dashes
    $string = trim($string, '-');
    
    return $string;
}

function setFeaturedImage($postId, $imageId) {
    global $db;
    
    // Start transaction
    $db->getConnection()->begin_transaction();
    
    try {
        // Get the image path
        $sql = "SELECT image_path FROM blog_images WHERE id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
        $result = $stmt->get_result();
        $image = $result->fetch_assoc();
        
        if (!$image) {
            throw new Exception("Image not found");
        }
        
        // Update the post's featured image
        $sql = "UPDATE blog_posts SET featured_image = ? WHERE id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("si", $image['image_path'], $postId);
        $stmt->execute();
        
        // Update is_featured flag in blog_images
        $sql = "UPDATE blog_images SET is_featured = CASE WHEN id = ? THEN 1 ELSE 0 END WHERE post_id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("ii", $imageId, $postId);
        $stmt->execute();
        
        // Commit transaction
        $db->getConnection()->commit();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $db->getConnection()->rollback();
        error_log("Error setting featured image: " . $e->getMessage());
        return false;
    }
}

function setThumbnailImage($postId, $imageId) {
    global $db;
    
    // Start transaction
    $db->getConnection()->begin_transaction();
    
    try {
        // Get the image path
        $sql = "SELECT image_path FROM blog_images WHERE id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
        $result = $stmt->get_result();
        $image = $result->fetch_assoc();
        
        if (!$image) {
            throw new Exception("Image not found");
        }
        
        // Update the post's thumbnail image
        $sql = "UPDATE blog_posts SET thumbnail_image = ? WHERE id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("si", $image['image_path'], $postId);
        $stmt->execute();
        
        // Commit transaction
        $db->getConnection()->commit();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $db->getConnection()->rollback();
        error_log("Error setting thumbnail image: " . $e->getMessage());
        return false;
    }
}

function removeFeaturedImage($postId) {
    global $db;
    
    // Start transaction
    $db->getConnection()->begin_transaction();
    
    try {
        // Update the post's featured image
        $sql = "UPDATE blog_posts SET featured_image = NULL WHERE id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        
        // Update is_featured flag in blog_images
        $sql = "UPDATE blog_images SET is_featured = 0 WHERE post_id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        
        // Commit transaction
        $db->getConnection()->commit();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $db->getConnection()->rollback();
        error_log("Error removing featured image: " . $e->getMessage());
        return false;
    }
}

function removeThumbnailImage($postId) {
    global $db;
    $sql = "UPDATE blog_posts SET thumbnail_image = NULL WHERE id = ?";
    $stmt = $db->getConnection()->prepare($sql);
    
    if (!$stmt) {
        error_log("MySQL Error: " . $db->getConnection()->error);
        return false;
    }
    
    $stmt->bind_param("i", $postId);
    
    if (!$stmt->execute()) {
        error_log("Execute Error: " . $stmt->error);
        return false;
    }
    
    return $stmt->affected_rows > 0;
}

?>
