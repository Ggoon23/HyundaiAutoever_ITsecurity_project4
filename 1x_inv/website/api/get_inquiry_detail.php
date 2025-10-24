<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Database connection
$host = getenv('DB_HOST') ?: 'db';
$dbname = getenv('DB_NAME') ?: 'ota_db';
$username = getenv('DB_USER') ?: 'admin';
$password = getenv('DB_PASS') ?: 'password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $inputPassword = isset($_GET['password']) ? $_GET['password'] : null;

    if ($id <=0) {
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 문의 번호입니다.'
        ]);
        exit;
    }

    // Fetch inquiry
    $stmt = $pdo->prepare("
        SELECT *
        FROM inquiries
        WHERE id = :id
    ");

    $stmt->execute(['id' => $id]);
    $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inquiry) {
        echo json_encode([
            'success' => false,
            'message' => '문의를 찾을 수 없습니다.'
        ]);
        exit;
    }

    // Check if locked
    if ($inquiry['is_locked']) {
        if (!$inputPassword) {
            echo json_encode([
                'success' => false,
                'message' => '비밀번호가 필요합니다.'
            ]);
            exit;
        }

        // WARNING: Plain text password comparison for vulnerability testing
        // In production, use password_verify()
        if ($inquiry['password'] !== $inputPassword) {
            echo json_encode([
                'success' => false,
                'message' => '비밀번호가 일치하지 않습니다.'
            ]);
            exit;
        }
    }

    // Remove password from response
    unset($inquiry['password']);
    $inquiry['is_locked'] = (bool)$inquiry['is_locked'];

    echo json_encode([
        'success' => true,
        'inquiry' => $inquiry
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 오류: ' . $e->getMessage()
    ]);
}
?>
