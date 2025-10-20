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

        // Validate file size
        if ($file['size'] > $max_file_size) {
            throw new Exception('File size exceeds 5MB limit');
        }

        // ========================================
        // 🛡️ SECURITY: Image-only upload validation
        // ========================================

        // 1. 확장자 검증 (화이트리스트)
        $original_name = basename($file['name']);
        $file_info = pathinfo($original_name);
        $filename_base = $file_info['filename'];
        $file_ext = isset($file_info['extension']) ? strtolower($file_info['extension']) : '';

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_extensions)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF allowed');
        }

        // 2. MIME 타입 검증
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_mime_types)) {
            throw new Exception('Invalid file content. File is not a valid image');
        }

        // 3. 매직 바이트 검증 (파일 시그니처)
        $file_handle = fopen($file['tmp_name'], 'rb');
        $file_header = fread($file_handle, 8);
        fclose($file_handle);

        $valid_signature = false;
        // JPEG: FF D8 FF
        if (substr($file_header, 0, 3) === "\xFF\xD8\xFF") {
            $valid_signature = true;
        }
        // PNG: 89 50 4E 47 0D 0A 1A 0A
        elseif (substr($file_header, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
            $valid_signature = true;
        }
        // GIF: 47 49 46 38 (GIF8)
        elseif (substr($file_header, 0, 4) === "\x47\x49\x46\x38") {
            $valid_signature = true;
        }

        if (!$valid_signature) {
            throw new Exception('Invalid file signature. File is corrupted or not an image');
        }

        // 4. PHP 코드 패턴 검사 (추가 보안)
        $file_content = file_get_contents($file['tmp_name']);
        $dangerous_patterns = [
            '/<\?php/i',
            '/<\?=/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/system\s*\(/i',
            '/exec\s*\(/i',
            '/passthru\s*\(/i',
            '/shell_exec\s*\(/i',
            '/base64_decode\s*\(/i'
        ];

        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $file_content)) {
                throw new Exception('Malicious code detected in file');
            }
        }

        // 5. 이미지 재처리 (메타데이터 및 숨겨진 코드 제거)
        $clean_image = null;
        switch ($mime_type) {
            case 'image/jpeg':
                $clean_image = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $clean_image = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $clean_image = imagecreatefromgif($file['tmp_name']);
                break;
        }

        if (!$clean_image) {
            throw new Exception('Failed to process image');
        }

        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Handle duplicate filenames by adding (1), (2), etc.
        $new_filename = $filename_base . '.' . $file_ext;
        $target_path = $upload_dir . $new_filename;
        $counter = 1;

        while (file_exists($target_path)) {
            $new_filename = $filename_base . '(' . $counter . ').' . $file_ext;
            $target_path = $upload_dir . $new_filename;
            $counter++;
        }

        // 재처리된 이미지를 저장 (원본 파일 대신)
        $save_success = false;
        switch ($mime_type) {
            case 'image/jpeg':
                $save_success = imagejpeg($clean_image, $target_path, 90);
                break;
            case 'image/png':
                $save_success = imagepng($clean_image, $target_path, 9);
                break;
            case 'image/gif':
                $save_success = imagegif($clean_image, $target_path);
                break;
        }

        // 메모리 해제
        imagedestroy($clean_image);

        if (!$save_success) {
            throw new Exception('Failed to save cleaned image');
        }

        // 파일 권한 설정
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
