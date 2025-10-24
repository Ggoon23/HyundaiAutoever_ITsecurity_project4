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

    // Fetch all inquiries ordered by created_at DESC
    $stmt = $pdo->prepare("
        SELECT
            id,
            subject,
            is_locked,
            status,
            created_at
        FROM inquiries
        ORDER BY created_at DESC
    ");

    $stmt->execute();
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert is_locked to boolean
    foreach ($inquiries as &$inquiry) {
        $inquiry['is_locked'] = (bool)$inquiry['is_locked'];
    }

    echo json_encode([
        'success' => true,
        'inquiries' => $inquiries
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '데이터베이스 연결 실패: ' . $e->getMessage()
    ]);
}
?>
