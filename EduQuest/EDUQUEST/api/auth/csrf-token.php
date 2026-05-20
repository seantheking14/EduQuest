<?php
/**
 * CSRF Token Endpoint
 * GET /api/auth/csrf-token.php
 * Returns a CSRF token for form submission
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../utils/Security.php';

// Generate and return CSRF token
$token = generateCSRFToken();

http_response_code(200);
echo json_encode([
    'success' => true,
    'csrfToken' => $token,
]);
