<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function subject_pool_json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function subject_pool_is_student_role(string $role): bool
{
    $normalized = strtolower(trim($role));

    if (strcasecmp($role, 'student') === 0) {
        return true;
    }

    return strpos($normalized, 'student-') === 0;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    subject_pool_json_response(405, [
        'ok' => false,
        'message' => 'Method not allowed. Use GET.',
    ]);
}

$studentId = strtoupper(trim((string)($_SESSION['user_id'] ?? '')));
$role = trim((string)($_SESSION['role'] ?? ''));

if ($studentId === '' || !subject_pool_is_student_role($role)) {
    subject_pool_json_response(401, [
        'ok' => false,
        'message' => 'Unauthorized student session.',
    ]);
}

$semester = trim((string)($_GET['semester'] ?? ''));
if (!preg_match('/^[1-8]$/', $semester)) {
    subject_pool_json_response(400, [
        'ok' => false,
        'message' => 'Invalid semester. Allowed values: 1..8.',
    ]);
}

try {
    require __DIR__ . '/../config/db.php';
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection is not initialized.');
    }

    // Get student's department to filter subjects correctly
    $deptStmt = $pdo->prepare("SELECT department_id FROM student_info WHERE student_id = :sid");
    $deptStmt->execute([':sid' => $studentId]);
    $deptRow = $deptStmt->fetch(PDO::FETCH_ASSOC);
    $deptId = $deptRow['department_id'] ?? null;

    $stmt = $pdo->prepare(
        "SELECT subject_code, subject_name, subject_type, credit
         FROM subjects_pool
         WHERE subject_semester = :semester
           AND (:dept IS NULL OR taught_in = :dept)
         ORDER BY subject_type DESC, subject_code ASC"
    );
    $stmt->execute([':semester' => (int)$semester, ':dept' => $deptId]);

    $theory = [];
    $sessional = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $entry = [
            'subject_code' => strtoupper(trim((string)($row['subject_code'] ?? ''))),
            'subject_name' => trim((string)($row['subject_name'] ?? '')),
            'credit' => isset($row['credit']) ? (string)$row['credit'] : '',
        ];

        if ($entry['subject_code'] === '') {
            continue;
        }

        if (strcasecmp((string)($row['subject_type'] ?? ''), 'Theory') === 0) {
            $theory[] = $entry;
        } else {
            $sessional[] = $entry;
        }
    }

    subject_pool_json_response(200, [
        'ok' => true,
        'semester' => (int)$semester,
        'theory' => $theory,
        'sessional' => $sessional,
    ]);
} catch (Throwable $exception) {
    subject_pool_json_response(500, [
        'ok' => false,
        'message' => 'Failed to fetch subject pool.',
        'details' => $exception->getMessage(),
    ]);
}