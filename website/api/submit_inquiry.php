<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Database configuration
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'ota_db';
$db_user = getenv('DB_USER') ?: 'admin';
$db_pass = getenv('DB_PASS') ?: 'password';

// File upload configuration
$upload_dir = '../uploads/';
$max_file_size = 5 * 1024 * 1024; // 5MB
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

try {
    // Get form data
    $data = $_POST;

    // Validate required fields
    $required_fields = ['name', 'email', 'phone', 'category', 'subject', 'message'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate category
    $valid_categories = ['product', 'technical', 'sales', 'partnership', 'other'];
    if (!in_array($data['category'], $valid_categories)) {
        throw new Exception('Invalid category');
    }

    // Handle file upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }

        // Validate file size (only size check for vulnerability testing)
        if ($file['size'] > $max_file_size) {
            throw new Exception('File size exceeds 5MB limit');
        }

        // WARNING: Weak validation for security testing purposes only
        // Get original filename
        $original_name = basename($file['name']);
        $file_info = pathinfo($original_name);
        $filename_base = $file_info['filename'];
        $file_ext = isset($file_info['extension']) ? $file_info['extension'] : '';

        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Handle duplicate filenames by adding (1), (2), etc.
        $new_filename = $original_name;
        $target_path = $upload_dir . $new_filename;
        $counter = 1;

        while (file_exists($target_path)) {
            if ($file_ext) {
                $new_filename = $filename_base . '(' . $counter . ').' . $file_ext;
            } else {
                $new_filename = $filename_base . '(' . $counter . ')';
            }
            $target_path = $upload_dir . $new_filename;
            $counter++;
        }

        // Move uploaded file with original name (or modified for duplicates)
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            throw new Exception('Failed to save uploaded file');
        }

        // Make file readable/writable for testing
        chmod($target_path, 0644);

        $image_path = 'uploads/' . $new_filename;
    }

    // Connect to database
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

    // Handle is_locked and password
    $is_locked = isset($data['is_locked']) && $data['is_locked'] === 'on' ? 1 : 0;
    $password = null;

    if ($is_locked) {
        if (empty($data['password'])) {
            throw new Exception('Password is required for locked inquiries');
        }
        // WARNING: Plain text password storage for vulnerability testing
        // In production, use password_hash()
        $password = $data['password'];
    }

    // Prepare SQL statement
    $sql = "INSERT INTO inquiries (name, company, email, phone, category, subject, message, image_path, is_locked, password, status)
            VALUES (:name, :company, :email, :phone, :category, :subject, :message, :image_path, :is_locked, :password, 'pending')";

    $stmt = $pdo->prepare($sql);

    // Execute with parameters
    $stmt->execute([
        ':name' => trim($data['name']),
        ':company' => isset($data['company']) ? trim($data['company']) : null,
        ':email' => trim($data['email']),
        ':phone' => trim($data['phone']),
        ':category' => $data['category'],
        ':subject' => trim($data['subject']),
        ':message' => trim($data['message']),
        ':image_path' => $image_path,
        ':is_locked' => $is_locked,
        ':password' => $password
    ]);

    $inquiry_id = $pdo->lastInsertId();

    // Success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => '문의가 성공적으로 접수되었습니다.',
        'inquiry_id' => $inquiry_id,
        'image_path' => $image_path
    ]);

} catch (PDOException $e) {
    // Delete uploaded file if database insert fails
    if (isset($image_path) && file_exists('../' . $image_path)) {
        unlink('../' . $image_path);
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Delete uploaded file if validation fails
    if (isset($image_path) && file_exists('../' . $image_path)) {
        unlink('../' . $image_path);
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
