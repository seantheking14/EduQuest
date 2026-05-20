<?php
/**
 * Current User Profile Endpoint
 * GET /api/auth/me.php
 * Returns the currently authenticated user's information
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../middleware/auth.php';

try {
    // Get the authenticated user
    $user = requireAuth();

    // For teachers, get additional teacher profile information
    if ($user['role'] === 'teacher') {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare('
                SELECT t.id, t.user_id, t.school_id, t.grade_level, t.subject, t.bio, t.avatar_url, 
                       u.first_name, u.last_name, u.email, u.role
                FROM teachers t
                LEFT JOIN users u ON u.id = t.user_id
                WHERE t.user_id = :user_id
                LIMIT 1
            ');
            $stmt->execute([':user_id' => $user['id']]);
            $teacher = $stmt->fetch();

            if ($teacher) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => (int) $teacher['id'],
                        'user_id' => (int) $teacher['user_id'],
                        'first_name' => $teacher['first_name'],
                        'last_name' => $teacher['last_name'],
                        'email' => $teacher['email'],
                        'role' => $teacher['role'],
                        'school_id' => $teacher['school_id'],
                        'grade_level' => $teacher['grade_level'],
                        'subject' => $teacher['subject'],
                        'bio' => $teacher['bio'],
                        'avatar_url' => $teacher['avatar_url']
                    ]
                ]);
                exit;
            }
        } catch (Exception $e) {
            // Fall through to return basic user info
        }
    }

    // If we get here, just return the authenticated user info
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $user['id'],
            'email' => $user['email'] ?? '',
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'role' => $user['role']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: ' . $e->getMessage()
    ]);
}
