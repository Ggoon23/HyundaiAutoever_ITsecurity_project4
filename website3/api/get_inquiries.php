<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// File-based storage configuration
$inquiries_dir = '../inquiries/';

try {
    // Create inquiries directory if it doesn't exist
    if (!is_dir($inquiries_dir)) {
        mkdir($inquiries_dir, 0777, true);
    }

    // Read all inquiry files
    $inquiries = [];
    $files = glob($inquiries_dir . '*.*');

    foreach ($files as $file) {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if ($extension === 'json') {
            // Read JSON file
            $content = file_get_contents($file);
            if ($content !== false) {
                $data = json_decode($content, true);
                if ($data) {
                    $inquiries[] = [
                        'id' => $data['id'],
                        'subject' => $data['subject'],
                        'is_locked' => $data['is_locked'],
                        'status' => $data['status'],
                        'created_at' => $data['created_at']
                    ];
                }
            }
        } elseif ($extension === 'yaml' || $extension === 'yml') {
            // For YAML files, we need to parse them using Python (vulnerable)
            // For listing purposes, extract basic info without full parsing
            $filename = basename($file, '.' . $extension);
            $parts = explode('_', $filename);
            $timestamp = isset($parts[0]) ? $parts[0] : time();

            $content = file_get_contents($file);
            if ($content !== false) {
                // Simple regex to extract fields (not full YAML parsing)
                preg_match('/subject:\s*(.+)$/m', $content, $subject_match);
                preg_match('/is_locked:\s*(.+)$/m', $content, $locked_match);
                preg_match('/status:\s*(.+)$/m', $content, $status_match);
                preg_match('/created_at:\s*(.+)$/m', $content, $created_match);

                $inquiries[] = [
                    'id' => $filename,
                    'subject' => isset($subject_match[1]) ? trim($subject_match[1]) : 'Unknown',
                    'is_locked' => isset($locked_match[1]) ? (trim($locked_match[1]) === 'true') : false,
                    'status' => isset($status_match[1]) ? trim($status_match[1]) : 'pending',
                    'created_at' => isset($created_match[1]) ? trim($created_match[1]) : date('Y-m-d H:i:s', $timestamp)
                ];
            }
        }
    }

    // Sort by created_at DESC
    usort($inquiries, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    echo json_encode([
        'success' => true,
        'inquiries' => $inquiries
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '파일 읽기 실패: ' . $e->getMessage()
    ]);
}
?>
