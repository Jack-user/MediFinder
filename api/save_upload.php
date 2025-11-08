<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

$userId = (int)$_SESSION['user_id'];
$filename = isset($body['filename']) ? trim($body['filename']) : null;
$extractedText = isset($body['extracted_text']) ? trim($body['extracted_text']) : '';

if (!$filename || !$extractedText) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$stmt = $db->prepare('INSERT INTO uploads (user_id, original_name, extracted_text) VALUES (?, ?, ?)');
$stmt->bind_param('iss', $userId, $filename, $extractedText);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Save failed']);
}

$stmt->close();

