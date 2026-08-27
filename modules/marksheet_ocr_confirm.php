<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/marksheet_ocr_lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    marksheet_json_response(405, ['ok' => false, 'message' => 'Method not allowed. Use POST.']);
}

$studentId = (int)($_SESSION['user_id'] ?? 0);
$role = trim((string)($_SESSION['role'] ?? ''));
if ($studentId <= 0 || !marksheet_is_student_role($role)) {
    marksheet_json_response(401, ['ok' => false, 'message' => 'Unauthorized student session.']);
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput === false ? '' : $rawInput, true);
if (!is_array($payload)) {
    marksheet_json_response(400, ['ok' => false, 'message' => 'Invalid JSON payload.']);
}

$token = trim((string)($payload['token'] ?? ''));
$semester = trim((string)($payload['semester'] ?? ''));
$rows = $payload['rows'] ?? null;
if ($token === '' || !preg_match('/^[1-8]$/', $semester) || !is_array($rows)) {
    marksheet_json_response(400, ['ok' => false, 'message' => 'Missing token, semester, or rows.']);
}

$review = $_SESSION['marksheet_ocr_reviews'][$token] ?? null;
if (!is_array($review)) {
    marksheet_json_response(409, ['ok' => false, 'message' => 'OCR review expired. Scan the PDF again.']);
}
if ((int)$review['student_id'] !== $studentId || (int)$review['semester'] !== (int)$semester) {
    marksheet_json_response(403, ['ok' => false, 'message' => 'OCR review does not belong to this student and semester.']);
}
if ((int)($review['expires_at'] ?? 0) < time()) {
    unset($_SESSION['marksheet_ocr_reviews'][$token]);
    marksheet_json_response(409, ['ok' => false, 'message' => 'OCR review expired. Scan the PDF again.']);
}

try {
    require __DIR__ . '/../config/db.php';
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection is not initialized.');
    }

    $registeredSubjects = marksheet_registered_subjects($pdo, $studentId, (int)$semester);
    $registeredCodes = array_keys($registeredSubjects);
    sort($registeredCodes);

    $submitted = [];
    foreach ($rows as $index => $row) {
        if (!is_array($row)) {
            marksheet_json_response(400, ['ok' => false, 'message' => 'Invalid row at index ' . (int)$index . '.']);
        }
        $code = strtoupper(trim((string)($row['subject_code'] ?? '')));
        $grade = strtoupper(trim((string)($row['letter_grade'] ?? '')));
        $points = $row['grade_points'] ?? null;

        if (!isset($registeredSubjects[$code])) {
            marksheet_json_response(400, ['ok' => false, 'message' => 'Unregistered subject cannot be saved: ' . $code]);
        }
        if (!marksheet_validate_grade($grade)) {
            marksheet_json_response(400, ['ok' => false, 'message' => 'Invalid letter grade for ' . $code . '.']);
        }
        if (!marksheet_validate_points($points)) {
            marksheet_json_response(400, ['ok' => false, 'message' => 'Grade points for ' . $code . ' must be from 0 to 10.']);
        }

        $submitted[$code] = [
            'subject_code' => $code,
            'letter_grade' => $grade,
            'grade_points' => round((float)$points, 2),
        ];
    }

    $submittedCodes = array_keys($submitted);
    sort($submittedCodes);
    if ($submittedCodes !== $registeredCodes) {
        marksheet_json_response(400, [
            'ok' => false,
            'message' => 'Reviewed rows must exactly match the registered subjects for this semester.',
            'registered_subjects' => $registeredCodes,
            'submitted_subjects' => $submittedCodes,
        ]);
    }

    $pdo->beginTransaction();
    $deleteStmt = $pdo->prepare(
        "DELETE FROM marksheet
         WHERE enrollment_no = :enrollment_no
           AND semester = :semester"
    );
    $deleteStmt->execute([
        ':enrollment_no' => (string)$studentId,
        ':semester' => $semester,
    ]);

    $insertStmt = $pdo->prepare(
        "INSERT INTO marksheet
            (enrollment_no, subject_code, semester, letter_grade, grade_points)
         VALUES
            (:enrollment_no, :subject_code, :semester, :letter_grade, :grade_points)
         ON DUPLICATE KEY UPDATE
            semester = VALUES(semester),
            letter_grade = VALUES(letter_grade),
            grade_points = VALUES(grade_points)"
    );

    foreach ($registeredCodes as $code) {
        $row = $submitted[$code];
        $insertStmt->execute([
            ':enrollment_no' => (string)$studentId,
            ':subject_code' => $row['subject_code'],
            ':semester' => $semester,
            ':letter_grade' => $row['letter_grade'],
            ':grade_points' => $row['grade_points'],
        ]);
    }

    $pdo->commit();
    unset($_SESSION['marksheet_ocr_reviews'][$token]);

    marksheet_json_response(200, [
        'ok' => true,
        'message' => 'Reviewed grades saved.',
        'summary' => [
            'saved' => count($registeredCodes),
            'semester' => (int)$semester,
        ],
    ]);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    marksheet_json_response(500, [
        'ok' => false,
        'message' => 'Failed to save reviewed grades: ' . $exception->getMessage(),
    ]);
}
