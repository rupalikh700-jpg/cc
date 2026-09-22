<?php
// ============================================
// api/auth.php — API authentication middleware
// ============================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

function jsonResponse(array $data, int $statusCode = 200): never {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function authenticateApi(): ?int {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!str_starts_with($authHeader, 'Bearer ')) {
        jsonResponse(['success' => false, 'error' => 'Missing or invalid Authorization header'], 401);
    }

    $token = substr($authHeader, 7);
    if (empty($token)) {
        jsonResponse(['success' => false, 'error' => 'Empty token'], 401);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT user_id FROM api_tokens WHERE token = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $db->close();

    if (!$row) {
        jsonResponse(['success' => false, 'error' => 'Invalid or expired token'], 401);
    }

    return (int)$row['user_id'];
}
