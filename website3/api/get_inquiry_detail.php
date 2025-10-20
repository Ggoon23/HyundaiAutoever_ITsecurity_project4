<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// File-based storage configuration
$inquiries_dir = '../inquiries/';

try {
    $id = isset($_GET['id']) ? $_GET['id'] : '';
    $inputPassword = isset($_GET['password']) ? $_GET['password'] : null;

    if (empty($id)) {
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 문의 번호입니다.'
        ]);
        exit;
    }

    // Find the inquiry file
    $inquiry = null;
    $files = glob($inquiries_dir . $id . '.*');

    if (empty($files)) {
        echo json_encode([
            'success' => false,
            'message' => '문의를 찾을 수 없습니다.'
        ]);
        exit;
    }

    $file = $files[0];
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    if ($extension === 'json') {
        // Read JSON file
        $content = file_get_contents($file);
        if ($content === false) {
            throw new Exception('파일을 읽을 수 없습니다.');
        }

        $inquiry = json_decode($content, true);
        if (!$inquiry) {
            throw new Exception('JSON 파싱 오류');
        }
    } elseif ($extension === 'yaml' || $extension === 'yml') {
        // Use Python to parse YAML file (VULNERABLE to PyYAML deserialization)
        $python_script = __DIR__ . '/../parse_yaml.py';
        $file_path = realpath($file);

        // Execute Python script with yaml.load() vulnerability
        $command = "python3 " . escapeshellarg($python_script) . " " . escapeshellarg($file_path);
        $output = shell_exec($command . " 2>&1");

        if ($output === null) {
            throw new Exception('Python 스크립트 실행 실패');
        }

        $inquiry = json_decode($output, true);
        if (!$inquiry) {
            throw new Exception('YAML 파싱 오류: ' . $output);
        }
    } else {
        throw new Exception('지원하지 않는 파일 형식입니다.');
    }

    // Check if locked
    if (isset($inquiry['is_locked']) && $inquiry['is_locked']) {
        if (!$inputPassword) {
            echo json_encode([
                'success' => false,
                'message' => '비밀번호가 필요합니다.'
            ]);
            exit;
        }

        // WARNING: Plain text password comparison for vulnerability testing
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

    echo json_encode([
        'success' => true,
        'inquiry' => $inquiry
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '오류: ' . $e->getMessage()
    ]);
}
?>
