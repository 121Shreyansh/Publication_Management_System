<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/marksheet_ocr_lib.php';

@ini_set('max_execution_time', '180');
@ini_set('memory_limit', '512M');
if (function_exists('set_time_limit')) {
    @set_time_limit(180);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    marksheet_json_response(405, ['ok' => false, 'message' => 'Method not allowed. Use POST.']);
}

$studentId = (int)($_SESSION['user_id'] ?? 0);
$role = trim((string)($_SESSION['role'] ?? ''));
if ($studentId <= 0 || !marksheet_is_student_role($role)) {
    marksheet_json_response(401, ['ok' => false, 'message' => 'Unauthorized student session.']);
}

$semester = trim((string)($_POST['semester'] ?? ''));
if (!preg_match('/^[1-8]$/', $semester)) {
    marksheet_json_response(400, ['ok' => false, 'message' => 'Invalid semester. Allowed values: 1..8.']);
}

if (!isset($_FILES['marksheet_pdf']) || !is_array($_FILES['marksheet_pdf'])) {
    marksheet_json_response(400, ['ok' => false, 'message' => 'Upload a PDF marksheet first.']);
}

$file = $_FILES['marksheet_pdf'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    marksheet_json_response(400, ['ok' => false, 'message' => 'PDF upload failed. Error code: ' . (int)$file['error']]);
}

$originalName = (string)($file['name'] ?? '');
if (!preg_match('/\.pdf$/i', $originalName)) {
    marksheet_json_response(400, ['ok' => false, 'message' => 'Only PDF files are allowed.']);
}

if ((int)($file['size'] ?? 0) <= 0 || (int)$file['size'] > 12 * 1024 * 1024) {
    marksheet_json_response(400, ['ok' => false, 'message' => 'PDF must be between 1 byte and 12 MB.']);
}

$tempDir = null;
try {
    require __DIR__ . '/../config/db.php';
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection is not initialized.');
    }

    $registeredSubjects = marksheet_registered_subjects($pdo, $studentId, (int)$semester);
    if ($registeredSubjects === []) {
        marksheet_json_response(409, [
            'ok' => false,
            'message' => 'No registered subjects found for this semester. Complete semester registration first.',
            'reason' => 'registration_missing',
        ]);
    }

    $tempDir = marksheet_make_temp_dir();
    $pdfPath = $tempDir . DIRECTORY_SEPARATOR . 'marksheet.pdf';
    if (!move_uploaded_file((string)$file['tmp_name'], $pdfPath)) {
        throw new RuntimeException('Could not move uploaded PDF into temporary OCR storage.');
    }

    $ocr = marksheet_ocr_pdf($pdfPath);
    $parsed = marksheet_parse_ocr_text($ocr['text'], $registeredSubjects);

    $token = bin2hex(random_bytes(16));
    $_SESSION['marksheet_ocr_reviews'][$token] = [
        'student_id' => $studentId,
        'semester' => (int)$semester,
        'registered_codes' => array_keys($registeredSubjects),
        'rows' => $parsed['rows'],
        'created_at' => time(),
        'expires_at' => time() + 1800,
    ];

    marksheet_json_response(200, [
        'ok' => true,
        'token' => $token,
        'rows' => $parsed['rows'],
        'rejected' => $parsed['rejected'],
        'summary' => array_merge($parsed['summary'], [
            'pages' => $ocr['pages'],
            'ocr_characters' => $ocr['characters'],
        ]),
    ]);
} catch (Throwable $exception) {
    marksheet_json_response(500, [
        'ok' => false,
        'message' => $exception->getMessage(),
    ]);
} finally {
    marksheet_cleanup_temp_dir($tempDir);
}
