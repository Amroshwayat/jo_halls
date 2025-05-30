<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';

class FunctionsTest extends TestCase
{
    protected static $dbBackup;

    public static function setUpBeforeClass(): void
    {
        global $db;
        // Backup the global $db object
        self::$dbBackup = $db;
        // Use an in-memory SQLite DB for testing if possible
        $db = new class {
            private $conn;
            public function __construct() {
                $this->conn = new mysqli('localhost', 'root', '', 'test');
                // You may need to create a test database and users table for this to work
                $this->conn->query("CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(255) UNIQUE,
                    email VARCHAR(255) UNIQUE,
                    password VARCHAR(255),
                    role VARCHAR(50),
                    first_name VARCHAR(255),
                    last_name VARCHAR(255),
                    phone VARCHAR(50)
                )");
            }
            public function getConnection() {
                return $this->conn;
            }
        };
    }

    public static function tearDownAfterClass(): void
    {
        global $db;
        // Drop the test table and restore the original $db
        $db->getConnection()->query("DROP TABLE IF EXISTS users");
        $db = self::$dbBackup;
    }

    public function setUp(): void
    {
        global $db;
        // Clean up users table before each test
        $db->getConnection()->query("DELETE FROM users");
    }

    public function testRegisterUserSuccess()
    {
        $username = 'testuser';
        $email = 'testuser@example.com';
        $password = 'Test@1234';
        $role = 'customer';
        $firstName = 'Test';
        $lastName = 'User';
        $phone = '1234567890';

        $result = registerUser($username, $email, $password, $role, $firstName, $lastName, $phone);
        $this->assertTrue($result, 'registerUser should return true on success');
    }

    public function testRegisterUserDuplicate()
    {
        $username = 'testuser';
        $email = 'testuser@example.com';
        $password = 'Test@1234';
        $role = 'customer';
        $firstName = 'Test';
        $lastName = 'User';
        $phone = '1234567890';

        // First registration should succeed
        $result1 = registerUser($username, $email, $password, $role, $firstName, $lastName, $phone);
        $this->assertTrue($result1);

        // Second registration with same username/email should fail
        $result2 = registerUser($username, $email, $password, $role, $firstName, $lastName, $phone);
        $this->assertFalse($result2, 'registerUser should return false on duplicate username/email');
    }

    public function testDebugToConsoleWithString()
    {
        ob_start();
        debug_to_console('Hello');
        $output = ob_get_clean();
        $this->assertStringContainsString(
            "<script>console.log('Debug Objects: Hello' );</script>",
            $output
        );
    }

    public function testDebugToConsoleWithArray()
    {
        ob_start();
        debug_to_console(['foo', 'bar']);
        $output = ob_get_clean();
        $this->assertStringContainsString(
            "<script>console.log('Debug Objects: foo,bar' );</script>",
            $output
        );
    }

    public function testLoginUserSuccess()
    {
        // Register a user
        $username = 'loginuser';
        $email = 'loginuser@example.com';
        $password = 'Test@1234';
        $role = 'customer';
        $firstName = 'Login';
        $lastName = 'User';
        $phone = '1234567890';

        registerUser($username, $email, $password, $role, $firstName, $lastName, $phone);

        // Start session for loginUser to set $_SESSION
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $result = loginUser($email, $password);
        $this->assertTrue($result, 'loginUser should return true for correct credentials');
        $this->assertArrayHasKey('user_id', $_SESSION);
        $this->assertEquals($firstName, $_SESSION['user_name']);
    }

    public function testLoginUserWrongPassword()
    {
        $username = 'wrongpassuser';
        $email = 'wrongpass@example.com';
        $password = 'Test@1234';
        $role = 'customer';
        $firstName = 'Wrong';
        $lastName = 'Pass';
        $phone = '1234567890';

        registerUser($username, $email, $password, $role, $firstName, $lastName, $phone);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $result = loginUser($email, 'WrongPassword1!');
        $this->assertFalse($result, 'loginUser should return false for wrong password');
    }

    public function testLoginUserNonExistent()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $result = loginUser('nonexistent@example.com', 'AnyPassword1!');
        $this->assertFalse($result, 'loginUser should return false for non-existent user');
    }

    public function testGetCurrentUserReturnsNullWhenNotLoggedIn()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION['user_id']);
        $user = getCurrentUser();
        $this->assertNull($user, 'getCurrentUser should return null when not logged in');
    }

    public function testGetCurrentUserReturnsUserWhenLoggedIn()
    {
        global $db;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        // Register a user
        $username = 'currentuser';
        $email = 'currentuser@example.com';
        $password = 'Test@1234';
        $role = 'customer';
        $firstName = 'Current';
        $lastName = 'User';
        $phone = '1234567890';
        registerUser($username, $email, $password, $role, $firstName, $lastName, $phone);

        // Get the user id
        $result = $db->getConnection()->query("SELECT id FROM users WHERE email = '$email'");
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];

        $user = getCurrentUser();
        $this->assertIsArray($user, 'getCurrentUser should return user array when logged in');
        $this->assertEquals($email, $user['email']);
    }

    public function testIsLoggedInFalseWhenNotLoggedIn()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        unset($_SESSION['user_id']);
        $this->assertFalse(isLoggedIn(), 'isLoggedIn should return false when not logged in');
    }

    public function testIsLoggedInTrueWhenLoggedIn()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['user_id'] = 1;
        $this->assertTrue(isLoggedIn(), 'isLoggedIn should return true when user_id is set');
    }

    public function testIsAdmin()
    {
        global $db;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        // Register an admin user
        $username = 'adminuser';
        $email = 'adminuser@example.com';
        $password = 'Test@1234';
        $role = 'admin';
        $firstName = 'Admin';
        $lastName = 'User';
        $phone = '1234567890';
        registerUser($username, $email, $password, $role, $firstName, $lastName, $phone);

        // Get the user id
        $result = $db->getConnection()->query("SELECT id FROM users WHERE email = '$email'");
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];

        $this->assertTrue(isAdmin(), 'isAdmin should return true for admin user');
        $this->assertFalse(isHallOwner(), 'isHallOwner should return false for admin user');
        $this->assertFalse(isCustomer(), 'isCustomer should return false for admin user');
    }

    public function testIsHallOwner()
    {
        global $db;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        // Register a hall_owner user
        $username = 'owneruser';
        $email = 'owneruser@example.com';
        $password = 'Test@1234';
        $role = 'hall_owner';
        $firstName = 'Owner';
        $lastName = 'User';
        $phone = '1234567890';
        registerUser($username, $email, $password, $role, $firstName, $lastName, $phone);

        // Get the user id
        $result = $db->getConnection()->query("SELECT id FROM users WHERE email = '$email'");
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];

        $this->assertFalse(isAdmin(), 'isAdmin should return false for hall_owner user');
        $this->assertTrue(isHallOwner(), 'isHallOwner should return true for hall_owner user');
        $this->assertFalse(isCustomer(), 'isCustomer should return false for hall_owner user');
    }

    public function testIsCustomer()
    {
        global $db;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        // Register a customer user
        $username = 'customeruser';
        $email = 'customeruser@example.com';
        $password = 'Test@1234';
        $role = 'customer';
        $firstName = 'Customer';
        $lastName = 'User';
        $phone = '1234567890';
        registerUser($username, $email, $password, $role, $firstName, $lastName, $phone);

        // Get the user id
        $result = $db->getConnection()->query("SELECT id FROM users WHERE email = '$email'");
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];

        $this->assertFalse(isAdmin(), 'isAdmin should return false for customer user');
        $this->assertFalse(isHallOwner(), 'isHallOwner should return false for customer user');
        $this->assertTrue(isCustomer(), 'isCustomer should return true for customer user');
    }

    public function testGenerateRandomPasswordLengthAndComplexity()
    {
        $password = generateRandomPassword(16);
        $this->assertEquals(16, strlen($password), 'Password should be 16 characters long');
        $this->assertMatchesRegularExpression('/[A-Z]/', $password, 'Password should contain uppercase');
        $this->assertMatchesRegularExpression('/[a-z]/', $password, 'Password should contain lowercase');
        $this->assertMatchesRegularExpression('/[0-9]/', $password, 'Password should contain a digit');
        $this->assertMatchesRegularExpression('/[!@#$%^&*()_+]/', $password, 'Password should contain a special character');
    }

    public function testHashPasswordProducesValidHash()
    {
        $plain = 'Test@1234';
        $hash = hashPassword($plain);
        $this->assertTrue(password_verify($plain, $hash), 'Hashed password should verify with original');
        $this->assertNotEquals($plain, $hash, 'Hash should not equal plain password');
        $this->assertStringStartsWith('$2y$', $hash, 'Hash should use bcrypt');
    }

    public function testValidatePassword()
    {
        $this->assertTrue(validatePassword('Abcdef1!'), 'Valid password should return true');
        $this->assertFalse(validatePassword('abcdef1!'), 'Missing uppercase should return false');
        $this->assertFalse(validatePassword('ABCDEFG1!'), 'Missing lowercase should return false');
        $this->assertFalse(validatePassword('Abcdefgh!'), 'Missing digit should return false');
        $this->assertFalse(validatePassword('Abcdefg1'), 'Missing special char should return false');
        $this->assertFalse(validatePassword('Ab1!'), 'Too short should return false');
    }

    public function testValidateEmail()
    {
        $this->assertTrue(validateEmail('test@example.com'));
        $this->assertTrue(validateEmail('user.name+tag@domain.co.uk'));
        $this->assertFalse(validateEmail('invalid-email'));
        $this->assertFalse(validateEmail('user@.com'));
        $this->assertFalse(validateEmail('user@domain'));
    }

    public function testValidatePhone()
    {
        $this->assertTrue(validatePhone('+962799999999'));
        $this->assertTrue(validatePhone('0799999999'));
        $this->assertTrue(validatePhone('12345678'));
        $this->assertFalse(validatePhone('1234'));
        $this->assertFalse(validatePhone('phone123'));
        $this->assertFalse(validatePhone('++962799999999'));
    }

    public function testSanitizeInput()
    {
        $this->assertEquals('hello', sanitizeInput('  hello  '));
        $this->assertEquals('&lt;script&gt;', sanitizeInput('<script>'));
        $this->assertEquals('O&#039;Reilly', sanitizeInput("O'Reilly"));
        $this->assertEquals('&quot;quoted&quot;', sanitizeInput('"quoted"'));
    }

    public function testUploadImageSuccess()
    {
        // Create a temporary image file
        $tmpFile = tempnam(sys_get_temp_dir(), 'img');
        imagepng(imagecreatetruecolor(10, 10), $tmpFile);
        $file = [
            'name' => 'test.png',
            'type' => 'image/png',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpFile)
        ];

        // Use a test directory inside uploads/general
        $targetDir = 'uploads/general/test_uploads';
        if (!file_exists(SITE_ROOT . '/' . $targetDir)) {
            mkdir(SITE_ROOT . '/' . $targetDir, 0777, true);
        }

        $result = uploadImage($file, $targetDir, 'general');
        $this->assertIsString($result, 'uploadImage should return a string path on success');
        $this->assertFileExists(SITE_ROOT . '/' . $result, 'Uploaded file should exist');

        // Clean up
        if (file_exists(SITE_ROOT . '/' . $result)) {
            unlink(SITE_ROOT . '/' . $result);
        }
        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }

    public function testUploadImageRejectsNonImage()
    {
        // Create a fake non-image file
        $tmpFile = tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($tmpFile, 'not an image');
        $file = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpFile)
        ];

        $result = uploadImage($file, 'uploads/general/test_uploads', 'general');
        $this->assertFalse($result, 'uploadImage should return false for non-image files');

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }

    public function testUploadProfileImage()
    {
        // Create a temporary image file
        $tmpFile = tempnam(sys_get_temp_dir(), 'img');
        imagepng(imagecreatetruecolor(10, 10), $tmpFile);
        $file = [
            'name' => 'profile.png',
            'type' => 'image/png',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpFile)
        ];

        $result = uploadProfileImage($file);
        $this->assertIsString($result, 'uploadProfileImage should return a string path on success');
        $this->assertFileExists(SITE_ROOT . '/' . $result, 'Uploaded profile image should exist');

        // Clean up
        if (file_exists(SITE_ROOT . '/' . $result)) {
            unlink(SITE_ROOT . '/' . $result);
        }
        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }

    public function testGetImageUrl()
    {
        $path = 'uploads/profiles/test.png';
        $expected = SITE_URL . '/' . $path;
        $this->assertEquals($expected, getImageUrl($path), 'getImageUrl should return correct URL');

        $this->assertEquals(DEFAULT_IMAGE, getImageUrl(''), 'getImageUrl should return default image if path is empty');
    }

    public function testDeleteImage()
    {
        // Create a dummy file
        $path = 'uploads/general/delete_test.png';
        $fullPath = SITE_ROOT . '/' . $path;
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0777, true);
        }
        file_put_contents($fullPath, 'dummy');
        $this->assertFileExists($fullPath);

        $this->assertTrue(deleteImage($path), 'deleteImage should return true on success');
        $this->assertFileDoesNotExist($fullPath, 'File should be deleted');

        // Should return true if file does not exist
        $this->assertTrue(deleteImage($path), 'deleteImage should return true if file does not exist');
        $this->assertTrue(deleteImage(''), 'deleteImage should return true for empty path');
    }

    public function testCreateHall()
    {
        global $db;
        // Register a hall_owner user
        $username = 'hallowner';
        $email = 'hallowner@example.com';
        $password = 'Test@1234';
        $role = 'hall_owner';
        $firstName = 'Hall';
        $lastName = 'Owner';
        $phone = '1234567890';
        registerUser($username, $email, $password, $role, $firstName, $lastName, $phone);

        // Get the user id
        $result = $db->getConnection()->query("SELECT id FROM users WHERE email = '$email'");
        $row = $result->fetch_assoc();
        $userId = $row['id'];

        // Create halls table if not exists (for test DB)
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            created_at DATETIME
        )");

        $hallId = createHall(
            $userId,
            'Test Hall',
            'A nice hall',
            '123 Main St',
            'Amman',
            31.95,
            35.93,
            50,
            200,
            100.0,
            'uploads/venues/test.png'
        );
        $this->assertIsInt($hallId, 'createHall should return hall id on success');

        // Clean up
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
    }

    public function testUpdateHallAmenities()
    {
        global $db;
        // Create tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            created_at DATETIME
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS amenities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS hall_amenities (
            hall_id INT,
            amenity_id INT
        )");

        // Insert a hall and amenities
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES (1, 'Hall1', 'desc', 'addr', 'CityA', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $hallId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO amenities (name) VALUES ('Wifi'), ('Parking')");
        $amenityIds = [];
        $res = $db->getConnection()->query("SELECT id FROM amenities");
        while ($row = $res->fetch_assoc()) {
            $amenityIds[] = (int)$row['id'];
        }

        // Test adding amenities
        $result = updateHallAmenities($hallId, $amenityIds);
        $this->assertTrue($result, 'updateHallAmenities should return true');
        $res = $db->getConnection()->query("SELECT COUNT(*) as cnt FROM hall_amenities WHERE hall_id = $hallId");
        $row = $res->fetch_assoc();
        $this->assertEquals(count($amenityIds), (int)$row['cnt'], 'All amenities should be added');

        // Test removing all amenities
        $result = updateHallAmenities($hallId, []);
        $this->assertTrue($result, 'updateHallAmenities should return true when removing all');
        $res = $db->getConnection()->query("SELECT COUNT(*) as cnt FROM hall_amenities WHERE hall_id = $hallId");
        $row = $res->fetch_assoc();
        $this->assertEquals(0, (int)$row['cnt'], 'All amenities should be removed');

        // Clean up
        $db->getConnection()->query("DELETE FROM hall_amenities WHERE hall_id = $hallId");
        $db->getConnection()->query("DELETE FROM amenities");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
    }

    public function testAddVenueImage()
    {
        global $db;
        // Create tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            created_at DATETIME
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS hall_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hall_id INT,
            image_path VARCHAR(255),
            is_main TINYINT(1)
        )");

        // Insert a hall
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES (1, 'Hall2', 'desc', 'addr', 'CityB', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $hallId = $db->getConnection()->insert_id;

        // Add image as non-main
        $result = addVenueImage($hallId, 'uploads/venues/img1.jpg', false);
        $this->assertTrue($result, 'addVenueImage should return true for non-main image');
        $res = $db->getConnection()->query("SELECT * FROM hall_images WHERE hall_id = $hallId AND image_path = 'uploads/venues/img1.jpg'");
        $this->assertNotFalse($res->fetch_assoc(), 'Image should be inserted');

        // Add image as main, should unset previous mains
        $result = addVenueImage($hallId, 'uploads/venues/img2.jpg', true);
        $this->assertTrue($result, 'addVenueImage should return true for main image');
        $res = $db->getConnection()->query("SELECT * FROM hall_images WHERE hall_id = $hallId AND is_main = 1");
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $this->assertCount(1, $rows, 'Only one image should be main');

        // Clean up
        $db->getConnection()->query("DELETE FROM hall_images WHERE hall_id = $hallId");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
    }

    public function testGetHallById()
    {
        global $db;
        // Create tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(255),
            role VARCHAR(50),
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            created_at DATETIME
        )");

        // Insert user and hall
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('owner', 'owner@example.com', 'pass', 'hall_owner', 'Owner', 'User', '123')");
        $ownerId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES ($ownerId, 'Hall3', 'desc', 'addr', 'CityC', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $hallId = $db->getConnection()->insert_id;

        $hall = getHallById($hallId);
        $this->assertIsArray($hall, 'getHallById should return array');
        $this->assertEquals('Hall3', $hall['name']);
        $this->assertEquals('Owner', $hall['first_name']);
        $this->assertEquals('owner@example.com', $hall['email']);

        // Clean up
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
        $db->getConnection()->query("DELETE FROM users WHERE id = $ownerId");
    }

    public function testGetAllCities()
    {
        global $db;
        // Create table
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            created_at DATETIME
        )");

        // Insert halls with different cities and statuses
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES (1, 'HallA', 'desc', 'addr', 'CityX', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES (1, 'HallB', 'desc', 'addr', 'CityY', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES (1, 'HallC', 'desc', 'addr', 'CityX', 0, 0, 10, 100, 50, '', 'inactive', NOW())");

        $cities = getAllCities();
        $this->assertContains('CityX', $cities, 'CityX should be in active cities');
        $this->assertContains('CityY', $cities, 'CityY should be in active cities');
        $this->assertNotContains('CityC', $cities, 'Inactive city should not be included');
        $this->assertIsArray($cities);

        // Clean up
        $db->getConnection()->query("DELETE FROM halls WHERE city IN ('CityX','CityY')");
    }

    public function testSearchHalls()
    {
        global $db;
        // Insert a test hall
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, is_featured, created_at) VALUES (1, 'SearchTest', 'desc', 'addr', 'TestCity', 1, 1, 10, 100, 50, '', 'active', 1, NOW())");
        $hallId = $db->getConnection()->insert_id;

        // Test search by city
        $results = searchHalls('TestCity');
        $this->assertNotEmpty($results, 'searchHalls should return results for matching city');
        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $hallId) $found = true;
        }
        $this->assertTrue($found, 'Inserted hall should be found by city');

        // Test search by min capacity
        $results = searchHalls('TestCity', 10);
        $this->assertNotEmpty($results, 'searchHalls should return results for matching min capacity');

        // Test search by max price
        $results = searchHalls('TestCity', null, 100);
        $this->assertNotEmpty($results, 'searchHalls should return results for matching max price');

        // Test search by amenities (add amenity and link)
        $db->getConnection()->query("INSERT INTO amenities (name) VALUES ('TestAmenity')");
        $amenityId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO hall_amenities (hall_id, amenity_id) VALUES ($hallId, $amenityId)");
        $results = searchHalls('TestCity', null, null, [$amenityId]);
        $this->assertNotEmpty($results, 'searchHalls should return results for matching amenity');

        // Clean up
        $db->getConnection()->query("DELETE FROM hall_amenities WHERE hall_id = $hallId");
        $db->getConnection()->query("DELETE FROM amenities WHERE id = $amenityId");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
    }

    public function testGetFeaturedHalls()
    {
        global $db;
        // Insert a featured hall
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, is_featured, created_at) VALUES (1, 'FeaturedTest', 'desc', 'addr', 'CityF', 1, 1, 10, 100, 50, '', 'active', 1, NOW())");
        $hallId = $db->getConnection()->insert_id;

        $featured = getFeaturedHalls(1);
        $this->assertNotEmpty($featured, 'getFeaturedHalls should return at least one featured hall');
        $found = false;
        foreach ($featured as $row) {
            if ($row['id'] == $hallId) $found = true;
        }
        $this->assertTrue($found, 'Inserted featured hall should be in results');

        // Clean up
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
    }

    public function testGetHallImages()
    {
        global $db;
        // Insert a hall and image
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, is_featured, created_at) VALUES (1, 'ImageTest', 'desc', 'addr', 'CityI', 1, 1, 10, 100, 50, '', 'active', 0, NOW())");
        $hallId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO hall_images (hall_id, image_path, is_main) VALUES ($hallId, 'uploads/venues/testimg.jpg', 1)");
        $imageId = $db->getConnection()->insert_id;

        $images = getHallImages($hallId);
        $this->assertNotEmpty($images, 'getHallImages should return images for the hall');
        $this->assertEquals($images[0]['hall_id'], $hallId);

        // Clean up
        $db->getConnection()->query("DELETE FROM hall_images WHERE id = $imageId");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
    }

    public function testGetAmenities()
    {
        global $db;
        // Insert amenity
        $db->getConnection()->query("INSERT INTO amenities (name) VALUES ('AmenityTest')");
        $amenityId = $db->getConnection()->insert_id;

        $amenities = getAmenities();
        $this->assertNotEmpty($amenities, 'getAmenities should return at least one amenity');
        $found = false;
        foreach ($amenities as $a) {
            if ($a['id'] == $amenityId) $found = true;
        }
        $this->assertTrue($found, 'Inserted amenity should be in results');

        // Clean up
        $db->getConnection()->query("DELETE FROM amenities WHERE id = $amenityId");
    }

    public function testGetHallAmenities()
    {
        global $db;
        // Insert hall and amenity
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, is_featured, created_at) VALUES (1, 'AmenityHall', 'desc', 'addr', 'CityA', 1, 1, 10, 100, 50, '', 'active', 0, NOW())");
        $hallId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO amenities (name) VALUES ('AmenityForHall')");
        $amenityId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO hall_amenities (hall_id, amenity_id) VALUES ($hallId, $amenityId)");

        $hallAmenities = getHallAmenities($hallId);
        $this->assertNotEmpty($hallAmenities, 'getHallAmenities should return amenities for the hall');
        $found = false;
        foreach ($hallAmenities as $a) {
            if ($a['id'] == $amenityId) $found = true;
        }
        $this->assertTrue($found, 'Inserted amenity should be in hall amenities');

        // Clean up
        $db->getConnection()->query("DELETE FROM hall_amenities WHERE hall_id = $hallId");
        $db->getConnection()->query("DELETE FROM amenities WHERE id = $amenityId");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
    }

    public function testCreateBookingAndGetBookingsByHall()
    {
        global $db;
        // Create required tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(255),
            role VARCHAR(50),
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            created_at DATETIME
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hall_id INT,
            user_id INT,
            event_date DATE,
            start_time TIME,
            end_time TIME,
            total_guests INT,
            total_price DOUBLE,
            status VARCHAR(50) DEFAULT 'confirmed'
        )");

        // Insert user and hall
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('bookuser', 'bookuser@example.com', 'pass', 'customer', 'Book', 'User', '123')");
        $userId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES ($userId, 'BookHall', 'desc', 'addr', 'CityB', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $hallId = $db->getConnection()->insert_id;

        // Test createBooking
        $result = createBooking($hallId, $userId, '2024-01-01', '18:00:00', '22:00:00', 50, 200.0);
        $this->assertTrue($result, 'createBooking should return true on success');

        // Test getBookingsByHall
        $bookings = getBookingsByHall($hallId);
        $this->assertIsArray($bookings, 'getBookingsByHall should return array');
        $this->assertNotEmpty($bookings, 'getBookingsByHall should return at least one booking');
        $this->assertEquals('Book', $bookings[0]['first_name']);

        // Clean up
        $db->getConnection()->query("DELETE FROM bookings WHERE hall_id = $hallId");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
        $db->getConnection()->query("DELETE FROM users WHERE id = $userId");
    }

    public function testAddReview()
    {
        global $db;
        // Create required tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(255),
            role VARCHAR(50),
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            created_at DATETIME
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hall_id INT,
            user_id INT,
            rating INT,
            review_text TEXT,
            status VARCHAR(50) DEFAULT 'approved',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Insert user and hall
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('reviewuser', 'reviewuser@example.com', 'pass', 'customer', 'Review', 'User', '123')");
        $userId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES ($userId, 'ReviewHall', 'desc', 'addr', 'CityR', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $hallId = $db->getConnection()->insert_id;

        // Test addReview
        $result = addReview($hallId, $userId, 5, 'Great hall!');
        $this->assertTrue($result, 'addReview should return true on success');

        // Check review exists
        $res = $db->getConnection()->query("SELECT * FROM reviews WHERE hall_id = $hallId AND user_id = $userId");
        $row = $res->fetch_assoc();
        $this->assertNotFalse($row, 'Review should exist in database');
        $this->assertEquals(5, $row['rating']);
        $this->assertEquals('Great hall!', $row['review_text']);

        // Clean up
        $db->getConnection()->query("DELETE FROM reviews WHERE hall_id = $hallId");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
        $db->getConnection()->query("DELETE FROM users WHERE id = $userId");
    }

    public function testGetReviewCount()
    {
        global $db;
        // Create required tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(255),
            role VARCHAR(50),
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            created_at DATETIME
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hall_id INT,
            user_id INT,
            rating INT,
            review_text TEXT,
            status VARCHAR(50) DEFAULT 'approved',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Insert user and hall
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('reviewcountuser', 'reviewcount@example.com', 'pass', 'customer', 'Review', 'Count', '123')");
        $userId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES ($userId, 'ReviewCountHall', 'desc', 'addr', 'CityRC', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $hallId = $db->getConnection()->insert_id;

        // Add reviews
        $db->getConnection()->query("INSERT INTO reviews (hall_id, user_id, rating, review_text, status) VALUES ($hallId, $userId, 4, 'Nice', 'approved')");
        $db->getConnection()->query("INSERT INTO reviews (hall_id, user_id, rating, review_text, status) VALUES ($hallId, $userId, 5, 'Great', 'approved')");
        $db->getConnection()->query("INSERT INTO reviews (hall_id, user_id, rating, review_text, status) VALUES ($hallId, $userId, 2, 'Bad', 'pending')");

        $count = getReviewCount($hallId);
        $this->assertEquals(2, $count, 'getReviewCount should count only approved reviews');

        // Clean up
        $db->getConnection()->query("DELETE FROM reviews WHERE hall_id = $hallId");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
        $db->getConnection()->query("DELETE FROM users WHERE id = $userId");
    }

    public function testGetBookingCount()
    {
        global $db;
        // Create required tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            created_at DATETIME
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hall_id INT,
            user_id INT,
            event_date DATE,
            start_time TIME,
            end_time TIME,
            total_guests INT,
            total_price DOUBLE,
            status VARCHAR(50) DEFAULT 'confirmed'
        )");

        // Insert hall
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES (1, 'BookingCountHall', 'desc', 'addr', 'CityBC', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $hallId = $db->getConnection()->insert_id;

        // Add bookings
        $db->getConnection()->query("INSERT INTO bookings (hall_id, user_id, event_date, start_time, end_time, total_guests, total_price, status) VALUES ($hallId, 1, '2024-01-01', '10:00:00', '12:00:00', 10, 100, 'confirmed')");
        $db->getConnection()->query("INSERT INTO bookings (hall_id, user_id, event_date, start_time, end_time, total_guests, total_price, status) VALUES ($hallId, 1, '2024-01-02', '13:00:00', '15:00:00', 20, 200, 'completed')");
        $db->getConnection()->query("INSERT INTO bookings (hall_id, user_id, event_date, start_time, end_time, total_guests, total_price, status) VALUES ($hallId, 1, '2024-01-03', '16:00:00', '18:00:00', 30, 300, 'pending')");

        $count = getBookingCount($hallId);
        $this->assertEquals(2, $count, 'getBookingCount should count only confirmed and completed bookings');

        // Clean up
        $db->getConnection()->query("DELETE FROM bookings WHERE hall_id = $hallId");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
    }

    public function testGetHallReviews()
    {
        global $db;
        // Create required tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(255),
            role VARCHAR(50),
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            created_at DATETIME
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hall_id INT,
            user_id INT,
            rating INT,
            review_text TEXT,
            status VARCHAR(50) DEFAULT 'approved',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Insert user and hall
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('reviewlistuser', 'reviewlist@example.com', 'pass', 'customer', 'Review', 'List', '123')");
        $userId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES ($userId, 'ReviewListHall', 'desc', 'addr', 'CityRL', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $hallId = $db->getConnection()->insert_id;

        // Add reviews
        $db->getConnection()->query("INSERT INTO reviews (hall_id, user_id, rating, review_text, status) VALUES ($hallId, $userId, 4, 'Nice', 'approved')");
        $db->getConnection()->query("INSERT INTO reviews (hall_id, user_id, rating, review_text, status) VALUES ($hallId, $userId, 5, 'Great', 'approved')");
        $db->getConnection()->query("INSERT INTO reviews (hall_id, user_id, rating, review_text, status) VALUES ($hallId, $userId, 2, 'Bad', 'pending')");

        $reviews = getHallReviews($hallId, 10);
        $this->assertIsArray($reviews, 'getHallReviews should return array');
        $this->assertCount(2, $reviews, 'getHallReviews should return only approved reviews');
        $this->assertEquals('Review', $reviews[0]['first_name']);

        // Clean up
        $db->getConnection()->query("DELETE FROM reviews WHERE hall_id = $hallId");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
        $db->getConnection()->query("DELETE FROM users WHERE id = $userId");
    }

    public function testGetAIRecommendations()
    {
        global $db;
        // Create required tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(255),
            role VARCHAR(50),
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            is_featured TINYINT(1) DEFAULT 0,
            rating DOUBLE DEFAULT 0,
            created_at DATETIME
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS user_preferences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            preferred_city VARCHAR(255),
            guest_count INT,
            budget_per_hour DOUBLE
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hall_id INT,
            user_id INT,
            event_date DATE,
            start_time TIME,
            end_time TIME,
            total_guests INT,
            total_price DOUBLE,
            status VARCHAR(50) DEFAULT 'confirmed'
        )");
        // Insert user and hall
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('airecuser', 'airec@example.com', 'pass', 'customer', 'AI', 'Rec', '123')");
        $userId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, is_featured, rating, created_at) VALUES ($userId, 'AIRecHall', 'desc', 'addr', 'AICity', 0, 0, 10, 100, 50, '', 'active', 1, 4.5, NOW())");
        $hallId = $db->getConnection()->insert_id;
        // No preferences: should return featured halls
        $recs = getAIRecommendations($userId, 2);
        $this->assertIsArray($recs);
        $this->assertNotEmpty($recs, 'Should return featured halls if no preferences');
        // Add preferences
        $db->getConnection()->query("INSERT INTO user_preferences (user_id, preferred_city, guest_count, budget_per_hour) VALUES ($userId, 'AICity', 50, 100)");
        $recs2 = getAIRecommendations($userId, 2);
        $this->assertIsArray($recs2);
        $this->assertNotEmpty($recs2, 'Should return halls matching preferences');
        // Clean up
        $db->getConnection()->query("DELETE FROM user_preferences WHERE user_id = $userId");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
        $db->getConnection()->query("DELETE FROM users WHERE id = $userId");
    }

    public function testGetPopularLocations()
    {
        global $db;
        // Create table
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            created_at DATETIME
        )");
        // Insert halls in different cities
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES (1, 'PopHall1', 'desc', 'addr', 'PopCity', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES (1, 'PopHall2', 'desc', 'addr', 'PopCity', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES (1, 'PopHall3', 'desc', 'addr', 'OtherCity', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $locs = getPopularLocations();
        $this->assertIsArray($locs);
        $this->assertNotEmpty($locs);
        $found = false;
        foreach ($locs as $row) {
            if ($row['city'] === 'PopCity') $found = true;
        }
        $this->assertTrue($found, 'PopCity should be in popular locations');
        // Clean up
        $db->getConnection()->query("DELETE FROM halls WHERE city IN ('PopCity','OtherCity')");
    }

    public function testGetTestimonials()
    {
        global $db;
        // Create tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(255),
            role VARCHAR(50),
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            created_at DATETIME
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hall_id INT,
            user_id INT,
            rating INT,
            review_text TEXT,
            status VARCHAR(50) DEFAULT 'approved',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        // Insert user, hall, review
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('testiuser', 'testi@example.com', 'pass', 'customer', 'Testi', 'Monial', '123')");
        $userId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES ($userId, 'TestiHall', 'desc', 'addr', 'TestiCity', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $hallId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO reviews (hall_id, user_id, rating, review_text, status) VALUES ($hallId, $userId, 5, 'Excellent!', 'approved')");
        $testimonials = getTestimonials(2);
        $this->assertIsArray($testimonials);
        $this->assertNotEmpty($testimonials);
        $found = false;
        foreach ($testimonials as $row) {
            if ($row['review_text'] === 'Excellent!') $found = true;
        }
        $this->assertTrue($found, 'Testimonial should be present');
        // Clean up
        $db->getConnection()->query("DELETE FROM reviews WHERE hall_id = $hallId");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
        $db->getConnection()->query("DELETE FROM users WHERE id = $userId");
    }

    public function testGetSuccessfulBookingsCount()
    {
        global $db;
        // Create table
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hall_id INT,
            user_id INT,
            event_date DATE,
            start_time TIME,
            end_time TIME,
            total_guests INT,
            total_price DOUBLE,
            status VARCHAR(50) DEFAULT 'confirmed'
        )");
        // Insert bookings
        $db->getConnection()->query("INSERT INTO bookings (hall_id, user_id, event_date, start_time, end_time, total_guests, total_price, status) VALUES (1, 1, '2024-01-01', '10:00:00', '12:00:00', 10, 100, 'confirmed')");
        $db->getConnection()->query("INSERT INTO bookings (hall_id, user_id, event_date, start_time, end_time, total_guests, total_price, status) VALUES (1, 1, '2024-01-02', '13:00:00', '15:00:00', 20, 200, 'completed')");
        $db->getConnection()->query("INSERT INTO bookings (hall_id, user_id, event_date, start_time, end_time, total_guests, total_price, status) VALUES (1, 1, '2024-01-03', '16:00:00', '18:00:00', 30, 300, 'pending')");

        $count = getSuccessfulBookingsCount();
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(1, $count, 'Should count confirmed bookings');
        // Clean up
        $db->getConnection()->query("DELETE FROM bookings WHERE status IN ('confirmed','pending')");
    }

    public function testGetVenueCount()
    {
        global $db;
        // Create table
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            name VARCHAR(255),
            description TEXT,
            address VARCHAR(255),
            city VARCHAR(255),
            latitude DOUBLE,
            longitude DOUBLE,
            capacity_min INT,
            capacity_max INT,
            price_per_hour DOUBLE,
            main_image VARCHAR(255),
            status VARCHAR(50),
            created_at DATETIME
        )");
        // Insert halls
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES (1, 'VenueCount1', 'desc', 'addr', 'VenueCity', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $db->getConnection()->query("INSERT INTO halls (owner_id, name, description, address, city, latitude, longitude, capacity_min, capacity_max, price_per_hour, main_image, status, created_at) VALUES (1, 'VenueCount2', 'desc', 'addr', 'VenueCity', 0, 0, 10, 100, 50, '', 'active', NOW())");
        $count = getVenueCount();
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(2, $count, 'Should count all venues');
        // Clean up
        $db->getConnection()->query("DELETE FROM halls WHERE city = 'VenueCity'");
    }

    public function testGetAverageRatingOverall()
    {
        global $db;
        // Create table
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            hall_id INT,
            user_id INT,
            rating INT,
            review_text TEXT,
            status VARCHAR(50) DEFAULT 'approved',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        // Insert reviews
        $db->getConnection()->query("INSERT INTO reviews (hall_id, user_id, rating, review_text, status) VALUES (1, 1, 4, 'Nice', 'approved')");
        $db->getConnection()->query("INSERT INTO reviews (hall_id, user_id, rating, review_text, status) VALUES (1, 1, 5, 'Great', 'approved')");
        $avg = getAverageRatingOverall();
        $this->assertIsFloat($avg);
        $this->assertEquals(4.5, $avg, '', 0.1);
        // Clean up
        $db->getConnection()->query("DELETE FROM reviews WHERE hall_id = 1 AND user_id = 1");
    }

    public function testGetUserCount()
    {
        global $db;
        // Create table
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(255),
            role VARCHAR(50),
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50)
        )");
        // Insert users
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('usercount1', 'usercount1@example.com', 'pass', 'customer', 'User', 'Count', '123')");
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('usercount2', 'usercount2@example.com', 'pass', 'customer', 'User', 'Count', '123')");
        $count = getUserCount();
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(2, $count, 'Should count customer users');
        // Clean up
        $db->getConnection()->query("DELETE FROM users WHERE username IN ('usercount1','usercount2')");
    }

    public function testGetLatestBlogPosts()
    {
        global $db;
        // Create required tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(255),
            role VARCHAR(50),
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            author_id INT,
            title VARCHAR(255),
            slug VARCHAR(255),
            content TEXT,
            excerpt TEXT,
            status VARCHAR(50),
            created_at DATETIME,
            featured_image VARCHAR(255),
            thumbnail_image VARCHAR(255)
        )");
        // Insert user and blog posts
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('bloguser', 'bloguser@example.com', 'pass', 'admin', 'Blog', 'User', '123')");
        $authorId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES ($authorId, 'Post 1', 'post-1', 'Content 1', 'Excerpt 1', 'published', NOW())");
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES ($authorId, 'Post 2', 'post-2', 'Content 2', 'Excerpt 2', 'published', NOW())");
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES ($authorId, 'Post 3', 'post-3', 'Content 3', 'Excerpt 3', 'published', NOW())");
        $posts = getLatestBlogPosts(2);
        $this->assertIsArray($posts);
        $this->assertLessThanOrEqual(2, count($posts));
        $this->assertEquals('published', $posts[0]['status']);
        // Clean up
        $db->getConnection()->query("DELETE FROM blog_posts WHERE author_id = $authorId");
        $db->getConnection()->query("DELETE FROM users WHERE id = $authorId");
    }

    public function testGetBlogPost()
    {
        global $db;
        // Create required tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(255),
            role VARCHAR(50),
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            author_id INT,
            title VARCHAR(255),
            slug VARCHAR(255),
            content TEXT,
            excerpt TEXT,
            status VARCHAR(50),
            created_at DATETIME,
            featured_image VARCHAR(255),
            thumbnail_image VARCHAR(255)
        )");
        // Insert user and blog post
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('bloguser2', 'bloguser2@example.com', 'pass', 'admin', 'Blog', 'User2', '123')");
        $authorId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES ($authorId, 'Post X', 'post-x', 'Content X', 'Excerpt X', 'published', NOW())");
        $postId = $db->getConnection()->insert_id;
        $post = getBlogPost($postId);
        $this->assertIsArray($post);
        $this->assertEquals('Post X', $post['title']);
        $this->assertEquals('published', $post['status']);
        // Clean up
        $db->getConnection()->query("DELETE FROM blog_posts WHERE id = $postId");
        $db->getConnection()->query("DELETE FROM users WHERE id = $authorId");
    }

    public function testGetBlogPostBySlug()
    {
        global $db;
        // Create required tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(255),
            role VARCHAR(50),
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            author_id INT,
            title VARCHAR(255),
            slug VARCHAR(255),
            content TEXT,
            excerpt TEXT,
            status VARCHAR(50),
            created_at DATETIME,
            featured_image VARCHAR(255),
            thumbnail_image VARCHAR(255)
        )");
        // Insert user and blog post
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('bloguser3', 'bloguser3@example.com', 'pass', 'admin', 'Blog', 'User3', '123')");
        $authorId = $db->getConnection()->insert_id;
        $slug = 'unique-slug-123';
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES ($authorId, 'Post Y', '$slug', 'Content Y', 'Excerpt Y', 'published', NOW())");
        $post = getBlogPostBySlug($slug);
        $this->assertIsArray($post);
        $this->assertEquals('Post Y', $post['title']);
        $this->assertEquals($slug, $post['slug']);
        // Clean up
        $db->getConnection()->query("DELETE FROM blog_posts WHERE slug = '$slug'");
        $db->getConnection()->query("DELETE FROM users WHERE id = $authorId");
    }

    public function testGetBlogPostsCount()
    {
        global $db;
        // Create required tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            author_id INT,
            title VARCHAR(255),
            slug VARCHAR(255),
            content TEXT,
            excerpt TEXT,
            status VARCHAR(50),
            created_at DATETIME,
            featured_image VARCHAR(255),
            thumbnail_image VARCHAR(255)
        )");
        // Insert blog posts
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES (1, 'Count Post 1', 'count-post-1', 'Content', 'Excerpt', 'published', NOW())");
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES (1, 'Count Post 2', 'count-post-2', 'Content', 'Excerpt', 'draft', NOW())");
        $countPublished = getBlogPostsCount(['status' => 'published']);
        $countDraft = getBlogPostsCount(['status' => 'draft']);
        $this->assertGreaterThanOrEqual(1, $countPublished);
        $this->assertGreaterThanOrEqual(1, $countDraft);
        // Clean up
        $db->getConnection()->query("DELETE FROM blog_posts WHERE slug IN ('count-post-1','count-post-2')");
    }

    public function testGetBlogPosts()
    {
        global $db;
        // Create required tables
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255),
            email VARCHAR(255),
            password VARCHAR(255),
            role VARCHAR(50),
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            phone VARCHAR(50)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            author_id INT,
            title VARCHAR(255),
            slug VARCHAR(255),
            content TEXT,
            excerpt TEXT,
            status VARCHAR(50),
            created_at DATETIME,
            featured_image VARCHAR(255),
            thumbnail_image VARCHAR(255)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_post_categories (
            post_id INT,
            category_id INT
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255)
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_post_tags (
            post_id INT,
            tag_id INT
        )");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255)
        )");
        // Insert user, category, tag, and blog post
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('bloguser4', 'bloguser4@example.com', 'pass', 'admin', 'Blog', 'User4', '123')");
        $authorId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_categories (name) VALUES ('CatA')");
        $catId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_tags (name) VALUES ('TagA')");
        $tagId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES ($authorId, 'Post Z', 'post-z', 'Content Z', 'Excerpt Z', 'published', NOW())");
        $postId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_post_categories (post_id, category_id) VALUES ($postId, $catId)");
        $db->getConnection()->query("INSERT INTO blog_post_tags (post_id, tag_id) VALUES ($postId, $tagId)");
        $posts = getBlogPosts(1, 10, ['status' => 'published']);
        $this->assertIsArray($posts);
        $found = false;
        foreach ($posts as $p) {
            if ($p['title'] === 'Post Z') $found = true;
        }
        $this->assertTrue($found, 'Inserted post should be in results');
        // Clean up
        $db->getConnection()->query("DELETE FROM blog_post_tags WHERE post_id = $postId");
        $db->getConnection()->query("DELETE FROM blog_tags WHERE id = $tagId");
        $db->getConnection()->query("DELETE FROM blog_post_categories WHERE post_id = $postId");
        $db->getConnection()->query("DELETE FROM blog_categories WHERE id = $catId");
        $db->getConnection()->query("DELETE FROM blog_posts WHERE id = $postId");
        $db->getConnection()->query("DELETE FROM users WHERE id = $authorId");
    }

    public function testGetPostCategories()
    {
        global $db;
        // Setup: create category, post, and relation
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (id INT AUTO_INCREMENT PRIMARY KEY, author_id INT, title VARCHAR(255), slug VARCHAR(255), content TEXT, excerpt TEXT, status VARCHAR(50), created_at DATETIME)");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_post_categories (post_id INT, category_id INT)");
        $db->getConnection()->query("INSERT INTO blog_categories (name) VALUES ('CatTest')");
        $catId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES (1, 'Test Post', 'test-post', 'Content', 'Excerpt', 'published', NOW())");
        $postId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_post_categories (post_id, category_id) VALUES ($postId, $catId)");
        $categories = getPostCategories($postId);
        $this->assertIsArray($categories);
        $this->assertEquals('CatTest', $categories[0]['name']);
        // Clean up
        $db->getConnection()->query("DELETE FROM blog_post_categories WHERE post_id = $postId");
        $db->getConnection()->query("DELETE FROM blog_categories WHERE id = $catId");
        $db->getConnection()->query("DELETE FROM blog_posts WHERE id = $postId");
    }

    public function testGetPostTags()
    {
        global $db;
        // Setup: create tag, post, and relation
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_tags (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (id INT AUTO_INCREMENT PRIMARY KEY, author_id INT, title VARCHAR(255), slug VARCHAR(255), content TEXT, excerpt TEXT, status VARCHAR(50), created_at DATETIME)");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_post_tags (post_id INT, tag_id INT)");
        $db->getConnection()->query("INSERT INTO blog_tags (name) VALUES ('TagTest')");
        $tagId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES (1, 'Test Post', 'test-post', 'Content', 'Excerpt', 'published', NOW())");
        $postId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_post_tags (post_id, tag_id) VALUES ($postId, $tagId)");
        $tags = getPostTags($postId);
        $this->assertIsArray($tags);
        $this->assertEquals('TagTest', $tags[0]['name']);
        // Clean up
        $db->getConnection()->query("DELETE FROM blog_post_tags WHERE post_id = $postId");
        $db->getConnection()->query("DELETE FROM blog_tags WHERE id = $tagId");
        $db->getConnection()->query("DELETE FROM blog_posts WHERE id = $postId");
    }

    public function testGetRelatedPosts()
    {
        global $db;
        // Setup: create posts and category relation
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (id INT AUTO_INCREMENT PRIMARY KEY, author_id INT, title VARCHAR(255), slug VARCHAR(255), content TEXT, excerpt TEXT, status VARCHAR(50), created_at DATETIME)");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_post_categories (post_id INT, category_id INT)");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))");
        $db->getConnection()->query("INSERT INTO blog_categories (name) VALUES ('RelCat')");
        $catId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES (1, 'Main Post', 'main-post', 'Content', 'Excerpt', 'published', NOW())");
        $mainPostId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_post_categories (post_id, category_id) VALUES ($mainPostId, $catId)");
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES (1, 'Related Post', 'related-post', 'Content', 'Excerpt', 'published', NOW())");
        $relatedPostId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_post_categories (post_id, category_id) VALUES ($relatedPostId, $catId)");
        $related = getRelatedPosts($mainPostId, 2);
        $this->assertIsArray($related);
        $this->assertNotEmpty($related);
        // Clean up
        $db->getConnection()->query("DELETE FROM blog_post_categories WHERE post_id IN ($mainPostId, $relatedPostId)");
        $db->getConnection()->query("DELETE FROM blog_categories WHERE id = $catId");
        $db->getConnection()->query("DELETE FROM blog_posts WHERE id IN ($mainPostId, $relatedPostId)");
    }

    public function testGetNextAndPreviousPost()
    {
        global $db;
        // Setup: create two posts
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (id INT AUTO_INCREMENT PRIMARY KEY, author_id INT, title VARCHAR(255), slug VARCHAR(255), content TEXT, excerpt TEXT, status VARCHAR(50), created_at DATETIME)");
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES (1, 'First Post', 'first-post', 'Content', 'Excerpt', 'published', NOW() - INTERVAL 2 DAY)");
        $firstId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES (1, 'Second Post', 'second-post', 'Content', 'Excerpt', 'published', NOW())");
        $secondId = $db->getConnection()->insert_id;
        $next = getNextPost($firstId);
        $prev = getPreviousPost($secondId);
        $this->assertIsArray($next);
        $this->assertEquals('Second Post', $next['title']);
        $this->assertIsArray($prev);
        $this->assertEquals('First Post', $prev['title']);
        // Clean up
        $db->getConnection()->query("DELETE FROM blog_posts WHERE id IN ($firstId, $secondId)");
    }

    public function testIncrementPostViews()
    {
        global $db;
        // Setup: create post with views
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (id INT AUTO_INCREMENT PRIMARY KEY, author_id INT, title VARCHAR(255), slug VARCHAR(255), content TEXT, excerpt TEXT, status VARCHAR(50), created_at DATETIME, views INT DEFAULT 0)");
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at, views) VALUES (1, 'View Post', 'view-post', 'Content', 'Excerpt', 'published', NOW(), 0)");
        $postId = $db->getConnection()->insert_id;
        incrementPostViews($postId);
        $result = $db->getConnection()->query("SELECT views FROM blog_posts WHERE id = $postId");
        $row = $result->fetch_assoc();
        $this->assertEquals(1, (int)$row['views']);
        // Clean up
        $db->getConnection()->query("DELETE FROM blog_posts WHERE id = $postId");
    }

    public function testGetAuthorAvatar()
    {
        global $db;
        // Setup: create user with and without avatar
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(255), email VARCHAR(255), password VARCHAR(255), role VARCHAR(50), first_name VARCHAR(255), last_name VARCHAR(255), phone VARCHAR(50), profile_image VARCHAR(255))");
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone, profile_image) VALUES ('avataruser', 'avatar@example.com', 'pass', 'customer', 'Avatar', 'User', '123', 'uploads/profiles/avatar.jpg')");
        $userId = $db->getConnection()->insert_id;
        $avatar = getAuthorAvatar($userId);
        $this->assertStringContainsString('uploads/profiles/avatar.jpg', $avatar);
        // Clean up
        $db->getConnection()->query("DELETE FROM users WHERE id = $userId");
    }

    public function testFormatRole()
    {
        $this->assertEquals('Admin', formatRole('admin'));
        $this->assertEquals('Hall Owner', formatRole('hall_owner'));
        $this->assertEquals('Customer', formatRole('customer'));
        $this->assertEquals('Unknown', formatRole('other'));
    }

    public function testGetCurrentUrl()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/test/url';
        $url = getCurrentUrl();
        $this->assertEquals('https://localhost/test/url', $url);
    }

    public function testParseMarkdown()
    {
        $input = "Hello\nWorld <b>bold</b>";
        $expected = "Hello<br />\nWorld &lt;b&gt;bold&lt;/b&gt;";
        $this->assertEquals($expected, parseMarkdown($input));
    }

    public function testGetBlogCategories()
    {
        global $db;
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (id INT AUTO_INCREMENT PRIMARY KEY, author_id INT, title VARCHAR(255), slug VARCHAR(255), content TEXT, excerpt TEXT, status VARCHAR(50), created_at DATETIME)");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_post_categories (post_id INT, category_id INT)");
        $db->getConnection()->query("INSERT INTO blog_categories (name) VALUES ('Cat1'), ('Cat2')");
        $cat1 = $db->getConnection()->insert_id - 1;
        $cat2 = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES (1, 'PostA', 'posta', 'Content', 'Excerpt', 'published', NOW())");
        $postId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_post_categories (post_id, category_id) VALUES ($postId, $cat1)");
        $categories = getBlogCategories();
        $this->assertIsArray($categories);
        $this->assertNotEmpty($categories);
        $db->getConnection()->query("DELETE FROM blog_post_categories WHERE post_id = $postId");
        $db->getConnection()->query("DELETE FROM blog_categories WHERE id IN ($cat1, $cat2)");
        $db->getConnection()->query("DELETE FROM blog_posts WHERE id = $postId");
    }

    public function testGetBlogTags()
    {
        global $db;
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_tags (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (id INT AUTO_INCREMENT PRIMARY KEY, author_id INT, title VARCHAR(255), slug VARCHAR(255), content TEXT, excerpt TEXT, status VARCHAR(50), created_at DATETIME)");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_post_tags (post_id INT, tag_id INT)");
        $db->getConnection()->query("INSERT INTO blog_tags (name) VALUES ('Tag1'), ('Tag2')");
        $tag1 = $db->getConnection()->insert_id - 1;
        $tag2 = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES (1, 'PostB', 'postb', 'Content', 'Excerpt', 'published', NOW())");
        $postId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_post_tags (post_id, tag_id) VALUES ($postId, $tag1)");
        $tags = getBlogTags();
        $this->assertIsArray($tags);
        $this->assertNotEmpty($tags);
        $db->getConnection()->query("DELETE FROM blog_post_tags WHERE post_id = $postId");
        $db->getConnection()->query("DELETE FROM blog_tags WHERE id IN ($tag1, $tag2)");
        $db->getConnection()->query("DELETE FROM blog_posts WHERE id = $postId");
    }

    public function testGetBlogPostCategories()
    {
        global $db;
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (id INT AUTO_INCREMENT PRIMARY KEY, author_id INT, title VARCHAR(255), slug VARCHAR(255), content TEXT, excerpt TEXT, status VARCHAR(50), created_at DATETIME)");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_post_categories (post_id INT, category_id INT)");
        $db->getConnection()->query("INSERT INTO blog_categories (name) VALUES ('CatTest2')");
        $catId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES (1, 'Test Post2', 'test-post2', 'Content', 'Excerpt', 'published', NOW())");
        $postId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_post_categories (post_id, category_id) VALUES ($postId, $catId)");
        $categories = getBlogPostCategories($postId);
        $this->assertIsArray($categories);
        $this->assertEquals('CatTest2', $categories[0]['name']);
        $db->getConnection()->query("DELETE FROM blog_post_categories WHERE post_id = $postId");
        $db->getConnection()->query("DELETE FROM blog_categories WHERE id = $catId");
        $db->getConnection()->query("DELETE FROM blog_posts WHERE id = $postId");
    }

    public function testGetBlogPostTags()
    {
        global $db;
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_tags (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (id INT AUTO_INCREMENT PRIMARY KEY, author_id INT, title VARCHAR(255), slug VARCHAR(255), content TEXT, excerpt TEXT, status VARCHAR(50), created_at DATETIME)");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_post_tags (post_id INT, tag_id INT)");
        $db->getConnection()->query("INSERT INTO blog_tags (name) VALUES ('TagTest2')");
        $tagId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, slug, content, excerpt, status, created_at) VALUES (1, 'Test Post2', 'test-post2', 'Content', 'Excerpt', 'published', NOW())");
        $postId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_post_tags (post_id, tag_id) VALUES ($postId, $tagId)");
        $tags = getBlogPostTags($postId);
        $this->assertIsArray($tags);
        $this->assertEquals('TagTest2', $tags[0]['name']);
        $db->getConnection()->query("DELETE FROM blog_post_tags WHERE post_id = $postId");
        $db->getConnection()->query("DELETE FROM blog_tags WHERE id = $tagId");
        $db->getConnection()->query("DELETE FROM blog_posts WHERE id = $postId");
    }

    public function testSetAndGetMessage()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        setMessage('success', 'Test message');
        $msg = getMessage();
        $this->assertIsArray($msg);
        $this->assertEquals('success', $msg['type']);
        $this->assertEquals('Test message', $msg['text']);
        $this->assertNull(getMessage());
    }

    public function testSubscribeToNewsletter()
    {
        global $db;
        $db->getConnection()->query("DELETE FROM newsletter_subscribers WHERE email = 'testnewsletter@example.com'");
        $result1 = subscribeToNewsletter('testnewsletter@example.com');
        $this->assertIsArray($result1);
        $this->assertTrue($result1['success']);
        $result2 = subscribeToNewsletter('testnewsletter@example.com');
        $this->assertIsArray($result2);
        $db->getConnection()->query("DELETE FROM newsletter_subscribers WHERE email = 'testnewsletter@example.com'");
    }

    public function testGetHallAvailability()
    {
        global $db;
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (id INT AUTO_INCREMENT PRIMARY KEY, owner_id INT, name VARCHAR(255))");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS hall_availability (id INT AUTO_INCREMENT PRIMARY KEY, hall_id INT, day_of_week INT, start_time TIME, end_time TIME, is_available TINYINT(1))");
        $db->getConnection()->query("INSERT INTO halls (owner_id, name) VALUES (1, 'AvailHall')");
        $hallId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO hall_availability (hall_id, day_of_week, start_time, end_time, is_available) VALUES ($hallId, 1, '10:00:00', '12:00:00', 1)");
        $slots = getHallAvailability($hallId);
        $this->assertIsArray($slots);
        $this->assertNotEmpty($slots);
        $this->assertEquals('10:00:00', $slots[0]['start_time']);
        $slots2 = getHallAvailability($hallId + 1000);
        $this->assertIsArray($slots2);
        $this->assertEmpty($slots2);
        $db->getConnection()->query("DELETE FROM hall_availability WHERE hall_id = $hallId");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
    }

    public function testFormatTime()
    {
        $this->assertEquals('10:00 AM', formatTime('10:00:00'));
        $this->assertEquals('3:15 PM', formatTime('15:15:00'));
        $this->assertEquals('12:00 AM', formatTime('00:00:00'));
        $this->assertEquals('12:30 PM', formatTime('12:30:00'));
    }

    public function testGetUserById()
    {
        global $db;
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(255), email VARCHAR(255), password VARCHAR(255), role VARCHAR(50), first_name VARCHAR(255), last_name VARCHAR(255), phone VARCHAR(50))");
        $db->getConnection()->query("INSERT INTO users (username, email, password, role, first_name, last_name, phone) VALUES ('unituser', 'unituser@example.com', 'pass', 'customer', 'Unit', 'Test', '123')");
        $userId = $db->getConnection()->insert_id;
        $user = getUserById($userId);
        $this->assertIsArray($user);
        $this->assertEquals('unituser@example.com', $user['email']);
        $this->assertNull(getUserById($userId + 1000));
        $db->getConnection()->query("DELETE FROM users WHERE id = $userId");
    }

    public function testGetInvitationTemplateReturnsTemplate()
    {
        $template = getInvitationTemplate(1);
        $this->assertIsArray($template);
        $this->assertEquals(1, $template['id']);
        $this->assertEquals('active', $template['status']);
    }

    public function testGetInvitationTemplateReturnsNullForInvalidId()
    {
        $template = getInvitationTemplate(999999);
        $this->assertNull($template);
    }

    public function testGetInvitationTemplatesReturnsArray()
    {
        $templates = getInvitationTemplates();
        $this->assertIsArray($templates);
        if (!empty($templates)) {
            $this->assertArrayHasKey('id', $templates[0]);
        }
    }

    public function testGetInvitationTemplatesWithCategory()
    {
        $templates = getInvitationTemplates('wedding');
        $this->assertIsArray($templates);
        foreach ($templates as $tpl) {
            $this->assertEquals('wedding', $tpl['category']);
        }
    }

    public function testCountInvitationTemplatesReturnsInt()
    {
        $count = countInvitationTemplates();
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountInvitationTemplatesWithCategory()
    {
        $count = countInvitationTemplates('birthday');
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testSaveInvitationSuccess()
    {
        // Assume user_id 1 and template_id 1 exist in test DB
        $data = [
            'event_title' => 'Test Event',
            'color_theme' => 'blue',
            'custom_field' => 'value'
        ];
        $result = saveInvitation(1, 1, $data);
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('id', $result);
    }

    public function testSaveInvitationFailsWithInvalidData()
    {
        $data = [
            'event_title' => 'Test Event',
            'color_theme' => 'blue'
        ];
        $result = saveInvitation(1, 999999, $data);
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
    }

    public function testGetVenueMainImage()
    {
        global $db;
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (id INT AUTO_INCREMENT PRIMARY KEY, owner_id INT, name VARCHAR(255))");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS hall_images (id INT AUTO_INCREMENT PRIMARY KEY, hall_id INT, image_path VARCHAR(255), is_main TINYINT(1))");
        $db->getConnection()->query("INSERT INTO halls (owner_id, name) VALUES (1, 'VenueMain')");
        $hallId = $db->getConnection()->insert_id;
        $mainPath = 'uploads/venues/mainimg.jpg';
        $db->getConnection()->query("INSERT INTO hall_images (hall_id, image_path, is_main) VALUES ($hallId, '$mainPath', 1)");
        $otherPath = 'uploads/venues/otherimg.jpg';
        $db->getConnection()->query("INSERT INTO hall_images (hall_id, image_path, is_main) VALUES ($hallId, '$otherPath', 0)");
        $url = getVenueMainImage($hallId);
        $this->assertStringContainsString($mainPath, $url, 'Should return main image url');
        $db->getConnection()->query("DELETE FROM hall_images WHERE hall_id = $hallId AND is_main = 1");
        $url2 = getVenueMainImage($hallId);
        $this->assertStringContainsString($otherPath, $url2, 'Should fallback to first image');
        $db->getConnection()->query("DELETE FROM hall_images WHERE hall_id = $hallId");
        $url3 = getVenueMainImage($hallId);
        $this->assertStringContainsString('default-venue.jpg', $url3, 'Should fallback to default image');
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
    }

    public function testDeleteVenueImage()
    {
        global $db;
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS hall_images (id INT AUTO_INCREMENT PRIMARY KEY, hall_id INT, image_path VARCHAR(255), is_main TINYINT(1))");
        $imgPath = 'uploads/venues/deleteimg.jpg';
        $fullPath = SITE_ROOT . '/' . $imgPath;
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0777, true);
        }
        file_put_contents($fullPath, 'dummy');
        $db->getConnection()->query("INSERT INTO hall_images (hall_id, image_path, is_main) VALUES (1, '$imgPath', 0)");
        $imgId = $db->getConnection()->insert_id;
        $this->assertFileExists($fullPath);
        $result = deleteVenueImage($imgId);
        $this->assertTrue($result, 'deleteVenueImage should return true');
        $this->assertFileDoesNotExist($fullPath, 'File should be deleted');
        $this->assertFalse(deleteVenueImage(999999));
    }

    public function testSetMainVenueImage()
    {
        global $db;
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS halls (id INT AUTO_INCREMENT PRIMARY KEY, owner_id INT, name VARCHAR(255))");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS hall_images (id INT AUTO_INCREMENT PRIMARY KEY, hall_id INT, image_path VARCHAR(255), is_main TINYINT(1))");
        $db->getConnection()->query("INSERT INTO halls (owner_id, name) VALUES (1, 'SetMain')");
        $hallId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO hall_images (hall_id, image_path, is_main) VALUES ($hallId, 'uploads/venues/img1.jpg', 1)");
        $img1 = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO hall_images (hall_id, image_path, is_main) VALUES ($hallId, 'uploads/venues/img2.jpg', 0)");
        $img2 = $db->getConnection()->insert_id;
        $result = setMainVenueImage($hallId, $img2);
        $this->assertTrue($result, 'setMainVenueImage should return true');
        $res = $db->getConnection()->query("SELECT is_main FROM hall_images WHERE id = $img2");
        $row = $res->fetch_assoc();
        $this->assertEquals(1, (int)$row['is_main']);
        $res = $db->getConnection()->query("SELECT is_main FROM hall_images WHERE id = $img1");
        $row = $res->fetch_assoc();
        $this->assertEquals(0, (int)$row['is_main']);
        $db->getConnection()->query("DELETE FROM hall_images WHERE hall_id = $hallId");
        $db->getConnection()->query("DELETE FROM halls WHERE id = $hallId");
    }

    public function testDeleteBlogPost()
    {
        global $db;
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (id INT AUTO_INCREMENT PRIMARY KEY, author_id INT, title VARCHAR(255))");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_post_categories (post_id INT, category_id INT)");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_post_tags (post_id INT, tag_id INT)");
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title) VALUES (1, 'DelPost')");
        $postId = $db->getConnection()->insert_id;
        $db->getConnection()->query("INSERT INTO blog_post_categories (post_id, category_id) VALUES ($postId, 1)");
        $db->getConnection()->query("INSERT INTO blog_post_tags (post_id, tag_id) VALUES ($postId, 1)");
        $result = deleteBlogPost($postId);
        $this->assertTrue($result, 'deleteBlogPost should return true');
        $res = $db->getConnection()->query("SELECT * FROM blog_posts WHERE id = $postId");
        $this->assertEquals(0, $res->num_rows, 'Post should be deleted');
    }

    public function testUpdateBlogPostStatus()
    {
        global $db;
        // Create table
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (id INT AUTO_INCREMENT PRIMARY KEY, author_id INT, title VARCHAR(255), status VARCHAR(50), updated_at DATETIME)");
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title, status) VALUES (1, 'StatusPost', 'draft')");
        $postId = $db->getConnection()->insert_id;
        $result = updateBlogPostStatus($postId, 'published');
        $this->assertTrue($result, 'updateBlogPostStatus should return true');
        $res = $db->getConnection()->query("SELECT status FROM blog_posts WHERE id = $postId");
        $row = $res->fetch_assoc();
        $this->assertEquals('published', $row['status']);
        // Clean up
        $db->getConnection()->query("DELETE FROM blog_posts WHERE id = $postId");
    }

    public function testAddBlogImage()
    {
        global $db;
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_posts (id INT AUTO_INCREMENT PRIMARY KEY, author_id INT, title VARCHAR(255))");
        $db->getConnection()->query("CREATE TABLE IF NOT EXISTS blog_images (id INT AUTO_INCREMENT PRIMARY KEY, post_id INT, image_path VARCHAR(255), is_featured TINYINT(1), created_at DATETIME)");
        $db->getConnection()->query("INSERT INTO blog_posts (author_id, title) VALUES (1, 'ImgPost')");
        $postId = $db->getConnection()->insert_id;
        $result = addBlogImage($postId, 'uploads/blog/img1.jpg', false);
        $this->assertTrue($result, 'addBlogImage should return true');
        $res = $db->getConnection()->query("SELECT * FROM blog_images WHERE post_id = $postId AND image_path = 'uploads/blog/img1.jpg'");
        $this->assertNotFalse($res->fetch_assoc(), 'Image should be inserted');
        $result2 = addBlogImage($postId, 'uploads/blog/img2.jpg', true);
        $this->assertTrue($result2, 'addBlogImage should return true for featured');
        $res = $db->getConnection()->query("SELECT COUNT(*) as cnt FROM blog_images WHERE post_id = $postId AND is_featured = 1");
        $row = $res->fetch_assoc();
        $this->assertEquals(1, (int)$row['cnt'], 'Only one image should be featured');
        $db->getConnection()->query("DELETE FROM blog_images WHERE post_id = $postId");
        $db->getConnection()->query("DELETE FROM blog_posts WHERE id = $postId");
    }




    public function testGetBlogImagesReturnsArrayOfImages() {
    global $db;

    $expectedData = [
        ['id' => 1, 'post_id' => 5, 'image_url' => 'img1.jpg', 'created_at' => '2024-01-01 12:00:00'],
        ['id' => 2, 'post_id' => 5, 'image_url' => 'img2.jpg', 'created_at' => '2024-01-02 12:00:00'],
    ];

    $resultMock = $this->createMock(stdClass::class);
    $resultMock->method('fetch_all')->with(MYSQLI_ASSOC)->willReturn($expectedData);


    $stmtMock = $this->createMock(stdClass::class);
    $stmtMock->expects($this->once())->method('bind_param')->with("i", 5);
    $stmtMock->expects($this->once())->method('execute');
    $stmtMock->expects($this->once())->method('get_result')->willReturn($resultMock);

  
    $connectionMock = $this->createMock(stdClass::class);
    $connectionMock->method('prepare')->willReturn($stmtMock);

    
    $db = $this->createMock(stdClass::class);
    $db->method('getConnection')->willReturn($connectionMock);

    $actualData = getBlogImages(5);

    $this->assertEquals($expectedData, $actualData);
}


public function testDeleteBlogImageDeletesFileAndRecord()
{
    global $db;

    $imageId = 10;
    $imagePath = 'uploads/blog/image1.jpg';
    $fullFilePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $imagePath;

    $resultMock = $this->createMock(stdClass::class);
    $resultMock->method('fetch_assoc')->willReturn(['image_path' => $imagePath]);

    $selectStmtMock = $this->createMock(stdClass::class);
    $selectStmtMock->expects($this->once())->method('bind_param')->with("i", $imageId);
    $selectStmtMock->expects($this->once())->method('execute');
    $selectStmtMock->expects($this->once())->method('get_result')->willReturn($resultMock);

    $deleteStmtMock = $this->createMock(stdClass::class);
    $deleteStmtMock->expects($this->once())->method('bind_param')->with("i", $imageId);
    $deleteStmtMock->expects($this->once())->method('execute')->willReturn(true);

    $connectionMock = $this->createMock(stdClass::class);
    $connectionMock->method('prepare')->willReturnCallback(function($sql) use ($selectStmtMock, $deleteStmtMock) {
        if (stripos($sql, 'SELECT') !== false) {
            return $selectStmtMock;
        } else {
            return $deleteStmtMock;
        }
    });

    $db = $this->createMock(stdClass::class);
    $db->method('getConnection')->willReturn($connectionMock);

   
    if (!function_exists('TestNamespace\file_exists')) {
        function file_exists($filename) {
            return true;
        }
    }

    if (!function_exists('TestNamespace\unlink')) {
        function unlink($filename) {
            return true;
        }
    }

    $result = deleteBlogImage($imageId);

    $this->assertTrue($result);
}


public function testUploadBlogPostImageReturnsFalseIfUploadFails()
{
    global $db;

    if (!function_exists('uploadImage')) {
        function uploadImage($file, $dir, $prefix) {
            return false;
        }
    }

    $result = uploadBlogPostImage(1, ['tmp_name' => 'test.jpg']);
    $this->assertFalse($result);
}

public function testUploadBlogPostImageSuccess()
{
    global $db;

    if (!function_exists('uploadImage')) {
        function uploadImage($file, $dir, $prefix) {
            return 'uploads/blog/blog_image.jpg';
        }
    }

    if (!function_exists('addBlogImage')) {
        function addBlogImage($postId, $imagePath, $isFeatured) {
            return true;
        }
    }

    $result = uploadBlogPostImage(1, ['tmp_name' => 'test.jpg']);
    $this->assertTrue($result);
}

public function testGetBlogByIdReturnsBlogData()
{
    global $db;

    $expected = ['id' => 1, 'title' => 'Test Post', 'author_name' => 'John Doe'];

    $resultMock = $this->createMock(stdClass::class);
    $resultMock->method('fetch_assoc')->willReturn($expected);

    $stmtMock = $this->createMock(stdClass::class);
    $stmtMock->expects($this->once())->method('bind_param')->with('i', 1);
    $stmtMock->expects($this->once())->method('execute')->willReturn(true);
    $stmtMock->method('get_result')->willReturn($resultMock);

    $connectionMock = $this->createMock(stdClass::class);
    $connectionMock->method('prepare')->willReturn($stmtMock);

    $db = $this->createMock(stdClass::class);
    $db->method('getConnection')->willReturn($connectionMock);

    $result = getBlogById(1);
    $this->assertEquals($expected, $result);
}

public function testGetBlogByIdReturnsNullOnPrepareFail()
{
    global $db;

    $connectionMock = $this->createMock(stdClass::class);
    $connectionMock->method('prepare')->willReturn(false);

    $db = $this->createMock(stdClass::class);
    $db->method('getConnection')->willReturn($connectionMock);

    $this->assertNull(getBlogById(1));
}

public function testGetBlogBySlugReturnsBlogData()
{
    global $db;

    $expected = ['id' => 1, 'slug' => 'test-post'];

    $resultMock = $this->createMock(stdClass::class);
    $resultMock->method('fetch_assoc')->willReturn($expected);

    $stmtMock = $this->createMock(stdClass::class);
    $stmtMock->expects($this->once())->method('bind_param')->with('s', 'test-post');
    $stmtMock->expects($this->once())->method('execute');
    $stmtMock->method('get_result')->willReturn($resultMock);

    $connectionMock = $this->createMock(stdClass::class);
    $connectionMock->method('prepare')->willReturn($stmtMock);

    $db = $this->createMock(stdClass::class);
    $db->method('getConnection')->willReturn($connectionMock);

    $result = getBlogBySlug('test-post');
    $this->assertEquals($expected, $result);
}

public function testGetBlogBySlugReturnsNullOnPrepareFail()
{
    global $db;

    $connectionMock = $this->createMock(stdClass::class);
    $connectionMock->method('prepare')->willReturn(false);

    $db = $this->createMock(stdClass::class);
    $db->method('getConnection')->willReturn($connectionMock);

    $this->assertNull(getBlogBySlug('test-post'));
}

public function testUpdateBlogPostSuccess()
{
    global $db;

    $data = ['title' => 'New Title', 'content' => 'Updated content'];

    $stmtMock = $this->createMock(stdClass::class);
    $stmtMock->expects($this->once())->method('bind_param');
    $stmtMock->expects($this->once())->method('execute')->willReturn(true);
    $stmtMock->method('__get')->with('affected_rows')->willReturn(1);

    $connectionMock = $this->createMock(stdClass::class);
    $connectionMock->method('prepare')->willReturn($stmtMock);

    $db = $this->createMock(stdClass::class);
    $db->method('getConnection')->willReturn($connectionMock);

    $result = updateBlogPost(1, $data);
    $this->assertTrue($result);
}

public function testUpdateBlogPostFailsOnPrepare()
{
    global $db;

    $data = ['title' => 'New Title'];

    $connectionMock = $this->createMock(stdClass::class);
    $connectionMock->method('prepare')->willReturn(false);

    $db = $this->createMock(stdClass::class);
    $db->method('getConnection')->willReturn($connectionMock);

    $result = updateBlogPost(1, $data);
    $this->assertFalse($result);
}

public function testUpdateBlogPostFailsOnExecute()
{
    global $db;

    $data = ['title' => 'Fail'];

    $stmtMock = $this->createMock(stdClass::class);
    $stmtMock->expects($this->once())->method('bind_param');
    $stmtMock->expects($this->once())->method('execute')->willReturn(false);

    $connectionMock = $this->createMock(stdClass::class);
    $connectionMock->method('prepare')->willReturn($stmtMock);

    $db = $this->createMock(stdClass::class);
    $db->method('getConnection')->willReturn($connectionMock);

    $result = updateBlogPost(1, $data);
    $this->assertFalse($result);
}

public function testUpdateBlogPostCategoriesSuccess()
{
    global $db;

    $postId = 1;
    $categoryIds = [2, 3];

    $mockStmt = $this->createMock(stdClass::class);
    $mockStmt->expects($this->any())->method('bind_param');
    $mockStmt->expects($this->any())->method('execute')->willReturn(true);

    $mockConnection = $this->createMock(stdClass::class);
    $mockConnection->method('prepare')->willReturn($mockStmt);
    $mockConnection->method('begin_transaction');
    $mockConnection->method('commit');

    $db = $this->createMock(stdClass::class);
    $db->method('getConnection')->willReturn($mockConnection);

    $result = updateBlogPostCategories($postId, $categoryIds);
    $this->assertTrue($result);
}

public function testUpdateBlogPostTagsSuccess()
{
    global $db;

    $postId = 1;
    $tagIds = [4, 5];

    $mockStmt = $this->createMock(stdClass::class);
    $mockStmt->expects($this->any())->method('bind_param');
    $mockStmt->expects($this->any())->method('execute')->willReturn(true);

    $mockConnection = $this->createMock(stdClass::class);
    $mockConnection->method('prepare')->willReturn($mockStmt);
    $mockConnection->method('begin_transaction');
    $mockConnection->method('commit');

    $db = $this->createMock(stdClass::class);
    $db->method('getConnection')->willReturn($mockConnection);

    $result = updateBlogPostTags($postId, $tagIds);
    $this->assertTrue($result);
}

public function testGenerateSlug()
{
    $string = "Hello World! PHP is Great.";
    $expected = "hello-world-php-is-great";
    $this->assertEquals($expected, generateSlug($string));
}

public function testSetFeaturedImageSuccess()
{
    global $db;

    $postId = 1;
    $imageId = 10;
    $imagePath = 'uploads/blog/image.jpg';

    $resultMock = $this->createMock(stdClass::class);
    $resultMock->method('fetch_assoc')->willReturn(['image_path' => $imagePath]);

    $stmtMock = $this->createMock(stdClass::class);
    $stmtMock->method('bind_param');
    $stmtMock->method('execute')->willReturn(true);
    $stmtMock->method('get_result')->willReturn($resultMock);

    $connMock = $this->createMock(stdClass::class);
    $connMock->method('prepare')->willReturn($stmtMock);
    $connMock->method('begin_transaction');
    $connMock->method('commit');

    $db = $this->createMock(stdClass::class);
    $db->method('getConnection')->willReturn($connMock);

    $result = setFeaturedImage($postId, $imageId);
    $this->assertTrue($result);
}

public function testSetFeaturedImageFailsIfImageNotFound()
{
    global $db;

    $postId = 1;
    $imageId = 99;

    $resultMock = $this->createMock(stdClass::class);
    $resultMock->method('fetch_assoc')->willReturn(null);

    $stmtMock = $this->createMock(stdClass::class);
    $stmtMock->method('bind_param');
    $stmtMock->method('execute')->willReturn(true);
    $stmtMock->method('get_result')->willReturn($resultMock);

    $connMock = $this->createMock(stdClass::class);
    $connMock->method('prepare')->willReturn($stmtMock);
    $connMock->method('begin_transaction');
    $connMock->method('rollback');

    $db = $this->createMock(stdClass::class);
    $db->method('getConnection')->willReturn($connMock);

    $result = setFeaturedImage($postId, $imageId);
    $this->assertFalse($result);
}

public function testSetThumbnailImageSuccess()
{
    $mockDb = $this->createMock(Database::class);
    $mockConn = $this->createMock(mysqli::class);
    $mockStmt = $this->createMock(mysqli_stmt::class);
    $mockResult = $this->createMock(mysqli_result::class);

    $mockDb->method('getConnection')->willReturn($mockConn);

    $mockConn->expects($this->once())->method('begin_transaction');
    $mockConn->method('prepare')->willReturn($mockStmt);
    
    $mockStmt->expects($this->any())->method('bind_param');
    $mockStmt->expects($this->any())->method('execute')->willReturn(true);
    $mockStmt->expects($this->any())->method('get_result')->willReturn($mockResult);

    $mockResult->method('fetch_assoc')->willReturn(['image_path' => 'path/to/image.jpg']);

    $mockConn->expects($this->once())->method('commit');

    $GLOBALS['db'] = $mockDb;
    $this->assertTrue(setThumbnailImage(1, 2));
}

public function testSetThumbnailImageFailsIfImageNotFound()
{
    $mockDb = $this->createMock(Database::class);
    $mockConn = $this->createMock(mysqli::class);
    $mockStmt = $this->createMock(mysqli_stmt::class);
    $mockResult = $this->createMock(mysqli_result::class);

    $mockDb->method('getConnection')->willReturn($mockConn);
    $mockConn->method('begin_transaction');
    $mockConn->method('prepare')->willReturn($mockStmt);

    $mockStmt->method('bind_param');
    $mockStmt->method('execute')->willReturn(true);
    $mockStmt->method('get_result')->willReturn($mockResult);

    $mockResult->method('fetch_assoc')->willReturn(null);

    $mockConn->expects($this->once())->method('rollback');

    $GLOBALS['db'] = $mockDb;
    $this->assertFalse(setThumbnailImage(1, 2));
}

public function testRemoveFeaturedImageSuccess()
{
    $mockDb = $this->createMock(Database::class);
    $mockConn = $this->createMock(mysqli::class);
    $mockStmt = $this->createMock(mysqli_stmt::class);

    $mockDb->method('getConnection')->willReturn($mockConn);
    $mockConn->method('begin_transaction');
    $mockConn->method('prepare')->willReturn($mockStmt);
    $mockStmt->method('bind_param');
    $mockStmt->method('execute')->willReturn(true);

    $mockConn->expects($this->once())->method('commit');

    $GLOBALS['db'] = $mockDb;
    $this->assertTrue(removeFeaturedImage(1));
}

public function testRemoveFeaturedImageFailsWithException()
{
    $mockDb = $this->createMock(Database::class);
    $mockConn = $this->createMock(mysqli::class);

    $mockDb->method('getConnection')->willReturn($mockConn);
    $mockConn->method('begin_transaction')->willThrowException(new Exception("Test error"));
    $mockConn->expects($this->once())->method('rollback');

    $GLOBALS['db'] = $mockDb;
    $this->assertFalse(removeFeaturedImage(1));
}

public function testRemoveThumbnailImageSuccess()
{
    $mockDb = $this->createMock(Database::class);
    $mockConn = $this->createMock(mysqli::class);
    $mockStmt = $this->createMock(mysqli_stmt::class);

    $mockDb->method('getConnection')->willReturn($mockConn);
    $mockConn->method('prepare')->willReturn($mockStmt);
    $mockStmt->method('bind_param');
    $mockStmt->method('execute')->willReturn(true);
    $mockStmt->method('__get')->with('affected_rows')->willReturn(1);

    $GLOBALS['db'] = $mockDb;
    $this->assertTrue(removeThumbnailImage(1));
}

public function testRemoveThumbnailImageFailsOnExecute()
{
    $mockDb = $this->createMock(Database::class);
    $mockConn = $this->createMock(mysqli::class);
    $mockStmt = $this->createMock(mysqli_stmt::class);

    $mockDb->method('getConnection')->willReturn($mockConn);
    $mockConn->method('prepare')->willReturn($mockStmt);
    $mockStmt->method('bind_param');
    $mockStmt->method('execute')->willReturn(false);

    $GLOBALS['db'] = $mockDb;
    $this->assertFalse(removeThumbnailImage(1));
}

}
