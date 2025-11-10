<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Image file is required']);
    exit;
}

$file = $_FILES['image'];

if (($file['size'] ?? 0) <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Uploaded file is empty']);
    exit;
}

// Limit to 8 MB to avoid excessive processing
if ($file['size'] > 8 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File must be smaller than 8 MB']);
    exit;
}

$allowedMime = ['image/jpeg', 'image/png', 'image/jpg', 'image/bmp', 'image/tiff', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

if (!in_array($mime, $allowedMime, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unsupported file type']);
    exit;
}

$tmpDir = sys_get_temp_dir();
$tmpPath = tempnam($tmpDir, 'ocr_');

if ($tmpPath === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to allocate temp file']);
    exit;
}

$cleanup = static function () use (&$tmpPath): void {
    if ($tmpPath && is_file($tmpPath)) {
        @unlink($tmpPath);
    }
};

try {
    if (!move_uploaded_file($file['tmp_name'], $tmpPath)) {
        throw new RuntimeException('Failed to move uploaded file');
    }

    $pythonBinary = getenv('PYTHON_BINARY') ?: 'python';
    $scriptPath = realpath(__DIR__ . '/../python/ocr_enhance.py');

    if ($scriptPath === false) {
        throw new RuntimeException('OCR script not found');
    }

    $cmd = sprintf(
        '%s %s --input %s --no-debug-image',
        escapeshellcmd($pythonBinary),
        escapeshellarg($scriptPath),
        escapeshellarg($tmpPath)
    );

    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($cmd, $descriptorSpec, $pipes, __DIR__);

    if (!is_resource($process)) {
        throw new RuntimeException('Failed to start OCR process');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]) ?: '';
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        $message = $stderr ?: $stdout ?: 'OCR process failed';
        throw new RuntimeException(trim($message));
    }

    $data = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);

    if (!isset($data['success']) || $data['success'] !== true) {
        $errorMessage = $data['error'] ?? 'OCR failed';
        throw new RuntimeException($errorMessage);
    }

    echo json_encode([
        'success' => true,
        'text' => $data['text'] ?? '',
        'confidence' => $data['confidence'] ?? null,
        'warnings' => $data['warnings'] ?? [],
        'steps' => $data['steps'] ?? [],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
} finally {
    $cleanup();
}

