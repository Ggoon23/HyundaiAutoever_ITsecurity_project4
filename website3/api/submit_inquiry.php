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

// File-based storage configuration
$inquiries_dir = '../inquiries/';
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

    // Create inquiries directory if it doesn't exist
    if (!is_dir($inquiries_dir)) {
        mkdir($inquiries_dir, 0777, true);
    }

    // Handle is_locked and password
    $is_locked = isset($data['is_locked']) && $data['is_locked'] === 'on' ? true : false;
    $password = null;

    if ($is_locked) {
        if (empty($data['password'])) {
            throw new Exception('Password is required for locked inquiries');
        }
        // WARNING: Plain text password storage for vulnerability testing
        // In production, use password_hash()
        $password = $data['password'];
    }

    // Extract file extension from subject
    $subject = trim($data['subject']);
    $subject_info = pathinfo($subject);
    $file_extension = isset($subject_info['extension']) ? strtolower($subject_info['extension']) : 'json';
    $subject_without_ext = isset($subject_info['extension']) ? $subject_info['filename'] : $subject;

    // Generate unique inquiry ID based on timestamp
    $inquiry_id = time() . '_' . uniqid();
    $inquiry_filename = $inquiry_id . '.' . $file_extension;
    $inquiry_path = $inquiries_dir . $inquiry_filename;

    // Prepare inquiry data
    $inquiry_data = [
        'id' => $inquiry_id,
        'name' => trim($data['name']),
        'company' => isset($data['company']) ? trim($data['company']) : '',
        'email' => trim($data['email']),
        'phone' => trim($data['phone']),
        'category' => $data['category'],
        'subject' => $subject_without_ext,
        'message' => trim($data['message']),
        'image_path' => $image_path,
        'is_locked' => $is_locked,
        'password' => $password,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Save inquiry based on file extension
    if ($file_extension === 'yaml' || $file_extension === 'yml') {
        // Save as YAML (vulnerable to PyYAML deserialization)
        $yaml_content = "id: " . $inquiry_data['id'] . "\n";
        $yaml_content .= "name: " . $inquiry_data['name'] . "\n";
        $yaml_content .= "company: " . $inquiry_data['company'] . "\n";
        $yaml_content .= "email: " . $inquiry_data['email'] . "\n";
        $yaml_content .= "phone: " . $inquiry_data['phone'] . "\n";
        $yaml_content .= "category: " . $inquiry_data['category'] . "\n";
        $yaml_content .= "subject: " . $inquiry_data['subject'] . "\n";
        $yaml_content .= "message: |\n  " . str_replace("\n", "\n  ", $inquiry_data['message']) . "\n";
        $yaml_content .= "image_path: " . ($inquiry_data['image_path'] ?: 'null') . "\n";
        $yaml_content .= "is_locked: " . ($inquiry_data['is_locked'] ? 'true' : 'false') . "\n";
        $yaml_content .= "password: " . ($inquiry_data['password'] ?: 'null') . "\n";
        $yaml_content .= "status: " . $inquiry_data['status'] . "\n";
        $yaml_content .= "created_at: " . $inquiry_data['created_at'] . "\n";

        if (file_put_contents($inquiry_path, $yaml_content) === false) {
            throw new Exception('Failed to save inquiry file');
        }
    } else {
        // Save as JSON (default)
        if (file_put_contents($inquiry_path, json_encode($inquiry_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            throw new Exception('Failed to save inquiry file');
        }
    }

    // Success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => '문의가 성공적으로 접수되었습니다.',
        'inquiry_id' => $inquiry_id,
        'image_path' => $image_path,
        'file_type' => $file_extension
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
