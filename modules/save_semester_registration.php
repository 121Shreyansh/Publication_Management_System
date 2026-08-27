<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function registration_json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function registration_is_student_role(string $role): bool
{
    if (strcasecmp($role, 'student') === 0) {
        return true;
    }

    if (function_exists('str_starts_with')) {
        return str_starts_with($role, 'Student-');
    }

    return strpos($role, 'Student-') === 0;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    registration_json_response(405, [
        'ok' => false,
        'message' => 'Method not allowed. Use POST.',
    ]);
}

$studentId = strtoupper(trim((string)($_SESSION['user_id'] ?? '')));
$role = trim((string)($_SESSION['role'] ?? ''));

if ($studentId === '' || !registration_is_student_role($role)) {
    registration_json_response(401, [
        'ok' => false,
        'message' => 'Unauthorized student session.',
    ]);
}

$rawInput = file_get_contents('php://input');
if ($rawInput === false || trim($rawInput) === '') {
    registration_json_response(400, [
        'ok' => false,
        'message' => 'Empty request body.',
    ]);
}

$payload = json_decode($rawInput, true);
if (!is_array($payload)) {
    registration_json_response(400, [
        'ok' => false,
        'message' => 'Invalid JSON payload.',
    ]);
}

$semester = trim((string)($payload['semester'] ?? ''));
if (!preg_match('/^[1-8]$/', $semester)) {
    registration_json_response(400, [
        'ok' => false,
        'message' => 'Invalid semester. Allowed values: 1..8.',
    ]);
}

$selectedSubjects = $payload['selected_subjects'] ?? null;
if (!is_array($selectedSubjects) || $selectedSubjects === []) {
    registration_json_response(400, [
        'ok' => false,
        'message' => 'selected_subjects must be a non-empty array.',
    ]);
}

$subjectRegex = '/^[A-Z0-9-]{3,32}$/';
$normalized = [];
foreach ($selectedSubjects as $code) {
    $subjectCode = strtoupper(trim((string)$code));
    if ($subjectCode === '') {
        continue;
    }
    if (!preg_match($subjectRegex, $subjectCode)) {
        registration_json_response(400, [
            'ok' => false,
            'message' => "Invalid subject code: {$subjectCode}",
        ]);
    }
    $normalized[$subjectCode] = true;
}

$subjectCodes = array_keys($normalized);
if ($subjectCodes === []) {
    registration_json_response(400, [
        'ok' => false,
        'message' => 'No valid subject codes found in selected_subjects.',
    ]);
}

$feeAmount = trim((string)($payload['fee_amount'] ?? ''));
$feeDate = trim((string)($payload['fee_date'] ?? ''));
$txnId = trim((string)($payload['txn_id'] ?? ''));
$theory = is_array($payload['theory'] ?? null) ? $payload['theory'] : [];
$sessional = is_array($payload['sessional'] ?? null) ? $payload['sessional'] : [];

try {
    require __DIR__ . '/../config/db.php';
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection is not initialized.');
    }

    $placeholders = implode(',', array_fill(0, count($subjectCodes), '?'));
    $validStmt = $pdo->prepare(
        "SELECT DISTINCT UPPER(TRIM(subject_code)) AS subject_code
         FROM subjects_pool
         WHERE subject_semester = ?
           AND subject_code IN ({$placeholders})"
    );
    $validStmt->execute(array_merge([(int)$semester], $subjectCodes));
    $validCodes = $validStmt->fetchAll(PDO::FETCH_COLUMN);

    $validSet = [];
    foreach ($validCodes as $code) {
        $clean = strtoupper(trim((string)$code));
        if ($clean !== '') {
            $validSet[$clean] = true;
        }
    }

    $missing = [];
    foreach ($subjectCodes as $code) {
        if (!isset($validSet[$code])) {
            $missing[] = $code;
        }
    }
    if ($missing !== []) {
        registration_json_response(400, [
            'ok' => false,
            'message' => 'Some subject codes are not present in subjects_pool for this semester.',
            'invalid_subject_codes' => array_values($missing),
        ]);
    }

    $academicYear = ((int)date('m') >= 7) ? (int)date('Y') : ((int)date('Y') - 1);
    $sessionLabel = ((int)date('m') >= 7) ? 'ODD' : 'EVEN';

    $pdo->beginTransaction();

    $semStmt = $pdo->prepare(
        "INSERT INTO semester_registration
            (student_id, semester, sem_reg_date, verification_flag)
         VALUES
            (:student_id, :semester, CURDATE(), 0)
         ON DUPLICATE KEY UPDATE
            semester = VALUES(semester),
            sem_reg_date = VALUES(sem_reg_date)"
    );
    $semStmt->execute([
        ':student_id' => $studentId,
        ':semester' => (int)$semester,
    ]);

    $deleteStmt = $pdo->prepare(
        "DELETE se
         FROM subject_enrollement se
         INNER JOIN subjects_pool sp ON sp.subject_code = se.subject_code
         WHERE se.student_id = :student_id
           AND sp.subject_semester = :semester"
    );
    $deleteStmt->execute([
        ':student_id' => $studentId,
        ':semester' => (int)$semester,
    ]);

    $insertStmt = $pdo->prepare(
        "INSERT IGNORE INTO subject_enrollement
            (student_id, subject_code, academic_year, session, verification_flag)
         VALUES
            (:student_id, :subject_code, :academic_year, :session, 0)"
    );
    foreach ($subjectCodes as $code) {
        $insertStmt->execute([
            ':student_id' => $studentId,
            ':subject_code' => $code,
            ':academic_year' => $academicYear,
            ':session' => $sessionLabel,
        ]);
    }

    // Optional parity snapshot for downstream modules that read marksheet_registration.
    try {
        $registrationSnapshot = json_encode([
            'student_id' => $studentId,
            'semester' => (int)$semester,
            'fee_amount' => $feeAmount,
            'fee_date' => $feeDate,
            'txn_id' => $txnId,
            'selected_subjects' => $subjectCodes,
            'theory' => $theory,
            'sessional' => $sessional,
        ], JSON_UNESCAPED_UNICODE);

        $marksheetRegStmt = $pdo->prepare(
            "INSERT INTO marksheet_registration
                (enrollment_no, semester, fee_amount, fee_date, txn_id, registration_json)
             VALUES
                (:enrollment_no, :semester, :fee_amount, :fee_date, :txn_id, :registration_json)
             ON DUPLICATE KEY UPDATE
                fee_amount = VALUES(fee_amount),
                fee_date = VALUES(fee_date),
                txn_id = VALUES(txn_id),
                registration_json = VALUES(registration_json)"
        );
        $marksheetRegStmt->execute([
            ':enrollment_no' => $studentId,
            ':semester' => $semester,
            ':fee_amount' => ($feeAmount === '' ? null : $feeAmount),
            ':fee_date' => ($feeDate === '' ? null : $feeDate),
            ':txn_id' => ($txnId === '' ? null : $txnId),
            ':registration_json' => ($registrationSnapshot === false ? '{}' : $registrationSnapshot),
        ]);
    } catch (Throwable $snapshotError) {
        // Keep enrolment persistence successful even if marksheet_registration table is unavailable.
    }

    $pdo->commit();

    registration_json_response(200, [
        'ok' => true,
        'message' => 'Semester registration saved.',
        'summary' => [
            'semester' => (int)$semester,
            'subjects_saved' => count($subjectCodes),
        ],
    ]);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    registration_json_response(500, [
        'ok' => false,
        'message' => 'Failed to save semester registration.',
        'details' => $exception->getMessage(),
    ]);
}