<?php
/**
 * GET /api/learning/student-modules.php
 * Returns all enrolled courses with their modules and materials for the authenticated student.
 *
 * Query params:
 *   courseId (optional) — filter to a single course
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

$user = requireAuth();
$db   = getDBConnection();

// Resolve student_id from user
$stmt = $db->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $user['role'] === 'student' ? $user['id'] : 0]);
$studentRow = $stmt->fetch();

if (!$studentRow) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Student profile not found.']);
    exit;
}

$studentId = (int) $studentRow['id'];
$courseId   = isset($_GET['courseId']) ? (int) $_GET['courseId'] : null;

try {
    // Get enrolled courses
    $sql = "
        SELECT c.id, c.title, c.description, c.subject, c.grade_level,
               c.school_year, c.cover_color, c.created_at,
               t.id AS teacher_id,
               CONCAT(u.first_name, ' ', u.last_name) AS teacher_name
        FROM course_enrollments ce
        JOIN courses c ON c.id = ce.course_id AND c.is_active = 1
        JOIN teachers t ON t.id = c.teacher_id
        JOIN users u ON u.id = t.user_id
        WHERE ce.student_id = :sid
    ";
    $params = [':sid' => $studentId];

    if ($courseId) {
        $sql .= ' AND c.id = :cid';
        $params[':cid'] = $courseId;
    }

    $sql .= ' ORDER BY c.title ASC';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $courses = $stmt->fetchAll();

    $result = [];

    foreach ($courses as $course) {
        $cid = (int) $course['id'];

        // Get modules (visible only)
        $modStmt = $db->prepare('
            SELECT id, title, description, position
            FROM course_modules
            WHERE course_id = :cid AND is_visible = 1
            ORDER BY position ASC, id ASC
        ');
        $modStmt->execute([':cid' => $cid]);
        $modules = $modStmt->fetchAll();

        $moduleList = [];
        foreach ($modules as $mod) {
            // Get materials (visible only)
            $matStmt = $db->prepare('
                SELECT id, title, description, material_type, content,
                       original_filename, file_size, mime_type, position, due_date, created_at
                FROM course_materials
                WHERE module_id = :mid AND is_visible = 1
                ORDER BY position ASC, id ASC
            ');
            $matStmt->execute([':mid' => (int) $mod['id']]);
            $materials = $matStmt->fetchAll();

            $matList = [];
            foreach ($materials as $mat) {
                $matItem = [
                    'id'            => (int) $mat['id'],
                    'title'         => $mat['title'],
                    'description'   => $mat['description'],
                    'materialType'  => $mat['material_type'],
                    'dueDate'       => $mat['due_date'],
                    'createdAt'     => $mat['created_at'],
                ];

                if ($mat['material_type'] === 'link') {
                    $matItem['url'] = $mat['content'];
                } elseif ($mat['material_type'] === 'text') {
                    $matItem['content'] = $mat['content'];
                } elseif ($mat['material_type'] === 'file') {
                    $matItem['fileName']  = $mat['original_filename'];
                    $matItem['fileSize']  = (int) $mat['file_size'];
                    $matItem['mimeType']  = $mat['mime_type'];
                    $matItem['fileType']  = getFileCategory($mat['mime_type'], $mat['original_filename']);
                } elseif ($mat['material_type'] === 'assignment') {
                    $matItem['content'] = $mat['content'];

                    // Include submission status
                    $subStmt = $db->prepare('
                        SELECT id, original_filename, file_size, status, grade, feedback, submitted_at, graded_at
                        FROM assignment_submissions
                        WHERE material_id = ? AND student_id = ?
                        LIMIT 1
                    ');
                    $subStmt->execute([(int) $mat['id'], $studentId]);
                    $sub = $subStmt->fetch(PDO::FETCH_ASSOC);
                    if ($sub) {
                        $matItem['submission'] = [
                            'id'              => (int) $sub['id'],
                            'originalFilename' => $sub['original_filename'],
                            'fileSize'        => (int) $sub['file_size'],
                            'status'          => $sub['status'],
                            'grade'           => $sub['grade'],
                            'feedback'        => $sub['feedback'],
                            'submittedAt'     => $sub['submitted_at'],
                            'gradedAt'        => $sub['graded_at'],
                        ];
                    }
                }

                $matList[] = $matItem;
            }

            $moduleList[] = [
                'id'          => (int) $mod['id'],
                'title'       => $mod['title'],
                'description' => $mod['description'],
                'materials'   => $matList,
            ];
        }

        // Get announcements for this course (pinned first, newest first)
        $annStmt = $db->prepare('
            SELECT id, title, content, is_pinned, created_at, updated_at
            FROM course_announcements
            WHERE course_id = :cid
            ORDER BY is_pinned DESC, created_at DESC, id DESC
            LIMIT 10
        ');
        $annStmt->execute([':cid' => $cid]);
        $annRows = $annStmt->fetchAll(PDO::FETCH_ASSOC);
        $announcements = array_map(static function(array $row): array {
            return [
                'id'        => (int)$row['id'],
                'title'     => $row['title'],
                'content'   => $row['content'],
                'isPinned'  => ((int)$row['is_pinned']) === 1,
                'createdAt' => $row['created_at'],
                'updatedAt' => $row['updated_at'],
            ];
        }, $annRows);

        $result[] = [
            'id'          => $cid,
            'title'       => $course['title'],
            'description' => $course['description'],
            'subject'     => $course['subject'],
            'gradeLevel'  => $course['grade_level'],
            'schoolYear'  => $course['school_year'],
            'coverColor'  => $course['cover_color'],
            'teacherName' => $course['teacher_name'],
            'announcements' => $announcements,
            'modules'     => $moduleList,
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => $result,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load course modules.']);
}

/**
 * Determine a user-friendly file category from MIME type and filename.
 */
function getFileCategory(?string $mime, ?string $filename): string {
    $ext = strtolower(pathinfo($filename ?? '', PATHINFO_EXTENSION));

    // PowerPoint
    if (in_array($ext, ['ppt', 'pptx'], true) ||
        in_array($mime, ['application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'], true)) {
        return 'ppt';
    }
    // Word
    if (in_array($ext, ['doc', 'docx'], true) ||
        in_array($mime, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true)) {
        return 'word';
    }
    // Excel / CSV
    if (in_array($ext, ['xls', 'xlsx', 'csv'], true) ||
        in_array($mime, ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'], true)) {
        return 'spreadsheet';
    }
    // PDF
    if ($ext === 'pdf' || $mime === 'application/pdf') {
        return 'pdf';
    }
    // Images
    if (str_starts_with($mime ?? '', 'image/')) {
        return 'image';
    }
    // Video
    if (str_starts_with($mime ?? '', 'video/')) {
        return 'video';
    }
    // Audio
    if (str_starts_with($mime ?? '', 'audio/')) {
        return 'audio';
    }

    return 'file';
}
