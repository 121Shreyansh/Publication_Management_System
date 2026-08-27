<?php
declare(strict_types=1);

function marksheet_json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function marksheet_is_student_role(string $role): bool
{
    if (strcasecmp($role, 'student') === 0) {
        return true;
    }

    if (function_exists('str_starts_with')) {
        return str_starts_with($role, 'Student-');
    }

    return strpos($role, 'Student-') === 0;
}

function marksheet_registered_subjects(PDO $pdo, int $studentId, int $semester): array
{
    $stmt = $pdo->prepare(
        "SELECT se.subject_code, sp.subject_name, sp.subject_type, sp.credit
         FROM subject_enrollement se
         INNER JOIN subjects_pool sp ON sp.subject_code = se.subject_code
         WHERE se.student_id = :student_id
           AND sp.subject_semester = :semester
         ORDER BY sp.subject_type DESC, se.subject_code ASC"
    );
    $stmt->execute([
        ':student_id' => $studentId,
        ':semester' => $semester,
    ]);

    $subjects = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $code = strtoupper(trim((string)$row['subject_code']));
        if ($code === '') {
            continue;
        }
        $subjects[$code] = [
            'subject_code' => $code,
            'subject_name' => $row['subject_name'] ?? $code,
            'subject_type' => $row['subject_type'] ?? null,
            'credit' => $row['credit'] ?? null,
        ];
    }

    return $subjects;
}

function marksheet_allowed_grades(): array
{
    return ['O', 'A+', 'A', 'B+', 'B', 'C+', 'C', 'D', 'E', 'P', 'F', 'I', 'AB', 'DR'];
}

function marksheet_grade_points_from_letter(string $grade): ?float
{
    $scale = [
        'O' => 10.0,
        'A+' => 10.0,
        'A' => 9.0,
        'B+' => 8.0,
        'B' => 8.0,
        'C+' => 6.0,
        'C' => 7.0,
        'D' => 6.0,
        'E' => 0.0,
        'P' => 5.0,
        'F' => 0.0,
        'I' => 0.0,
        'AB' => 0.0,
        'DR' => 0.0,
    ];

    return $scale[strtoupper(trim($grade))] ?? null;
}

function marksheet_validate_grade(string $grade): bool
{
    return in_array(strtoupper(trim($grade)), marksheet_allowed_grades(), true);
}

function marksheet_validate_points($value): bool
{
    if (!is_numeric($value)) {
        return false;
    }
    $points = (float)$value;
    return is_finite($points) && $points >= 0 && $points <= 10;
}

function marksheet_find_binary(string $name): ?string
{
    $output = [];
    $exitCode = 1;
    exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null', $output, $exitCode);
    if ($exitCode === 0 && isset($output[0]) && is_executable($output[0])) {
        return $output[0];
    }

    foreach (['/opt/homebrew/bin', '/usr/local/bin', '/usr/bin', '/bin'] as $dir) {
        $path = $dir . '/' . $name;
        if (is_executable($path)) {
            return $path;
        }
    }

    return null;
}

function marksheet_make_temp_dir(): string
{
    $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
    $dir = $base . DIRECTORY_SEPARATOR . 'marksheet_ocr_' . bin2hex(random_bytes(8));
    if (!mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create OCR temporary directory.');
    }
    return $dir;
}

function marksheet_cleanup_temp_dir(?string $dir): void
{
    if ($dir === null || $dir === '' || !is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $path) {
        if ($path->isDir()) {
            @rmdir($path->getPathname());
        } else {
            @unlink($path->getPathname());
        }
    }
    @rmdir($dir);
}

function marksheet_ocr_pdf(string $pdfPath): array
{
    @ini_set('max_execution_time', '180');
    @ini_set('memory_limit', '512M');
    if (function_exists('set_time_limit')) {
        @set_time_limit(180);
    }

    $pdftoppm = marksheet_find_binary('pdftoppm');
    $tesseract = marksheet_find_binary('tesseract');
    $missing = [];
    if ($pdftoppm === null) {
        $missing[] = 'pdftoppm';
    }
    if ($tesseract === null) {
        $missing[] = 'tesseract';
    }
    if ($missing !== []) {
        throw new RuntimeException('Missing OCR dependencies: ' . implode(', ', $missing) . '. Install Tesseract and Poppler on the Unix server.');
    }

    $workDir = marksheet_make_temp_dir();
    try {
        $imagePrefix = $workDir . DIRECTORY_SEPARATOR . 'page';
        $renderOutput = [];
        $renderCode = 1;
        $renderCommand = escapeshellarg($pdftoppm)
            . ' -r 300 -png '
            . escapeshellarg($pdfPath)
            . ' '
            . escapeshellarg($imagePrefix)
            . ' 2>&1';
        exec($renderCommand, $renderOutput, $renderCode);
        if ($renderCode !== 0) {
            throw new RuntimeException('PDF rendering failed: ' . trim(implode(' ', $renderOutput)));
        }

        $images = glob($imagePrefix . '-*.png') ?: [];
        natsort($images);
        $images = array_values($images);
        if ($images === []) {
            throw new RuntimeException('PDF rendering did not produce any page images.');
        }

        $ocrText = '';
        foreach ($images as $index => $imagePath) {
            foreach ([4, 6] as $psm) {
                if (function_exists('set_time_limit')) {
                    @set_time_limit(180);
                }
                $outputBase = $workDir . DIRECTORY_SEPARATOR . 'ocr_' . $index . '_' . $psm;
                $ocrOutput = [];
                $ocrCode = 1;
                $ocrCommand = escapeshellarg($tesseract)
                    . ' '
                    . escapeshellarg($imagePath)
                    . ' '
                    . escapeshellarg($outputBase)
                    . ' -l eng --psm ' . $psm . ' -c preserve_interword_spaces=1 2>&1';
                exec($ocrCommand, $ocrOutput, $ocrCode);
                if ($ocrCode !== 0) {
                    throw new RuntimeException('Tesseract OCR failed: ' . trim(implode(' ', $ocrOutput)));
                }
                $textFile = $outputBase . '.txt';
                if (is_file($textFile)) {
                    $ocrText .= file_get_contents($textFile) . "\n";
                }
            }
        }

        return [
            'text' => $ocrText,
            'pages' => count($images),
            'characters' => strlen($ocrText),
        ];
    } finally {
        marksheet_cleanup_temp_dir($workDir);
    }
}

function marksheet_normalize_ocr_text(string $text): array
{
    $text = strtoupper($text);
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace('/([ABC])\s*\+/', '$1+', $text) ?? $text;

    $lines = [];
    foreach (explode("\n", $text) as $line) {
        $line = preg_replace('/[^\S\n]+/', ' ', trim($line)) ?? '';
        if ($line !== '') {
            $lines[] = $line;
        }
    }

    return $lines;
}

function marksheet_code_key(string $value): string
{
    $value = strtoupper($value);
    $value = preg_replace('/[^A-Z0-9]/', '', $value) ?? '';
    return strtr($value, [
        'O' => '0',
        'I' => '1',
        'L' => '1',
    ]);
}

function marksheet_line_matches_code(string $line, string $registeredCode): bool
{
    $lineKey = marksheet_code_key($line);
    $codeKey = marksheet_code_key($registeredCode);
    if ($codeKey !== '' && str_contains($lineKey, $codeKey)) {
        return true;
    }

    preg_match('/^[A-Z]+/', strtoupper($registeredCode), $registeredPrefixMatch);
    $registeredPrefix = $registeredPrefixMatch[0] ?? '';
    preg_match_all('/\b[A-Z0-9][A-Z0-9\- ]{2,14}\b/', strtoupper($line), $matches);
    foreach ($matches[0] ?? [] as $candidate) {
        $candidateCompact = preg_replace('/[^A-Z0-9]/', '', strtoupper($candidate)) ?? '';
        preg_match('/^[A-Z]+/', $candidateCompact, $candidatePrefixMatch);
        $candidatePrefix = $candidatePrefixMatch[0] ?? '';
        if ($registeredPrefix !== '' && $candidatePrefix !== '' && !str_starts_with($candidatePrefix, $registeredPrefix)) {
            continue;
        }

        $candidateKey = marksheet_code_key($candidate);
        $distance = levenshtein($candidateKey, $codeKey);
        if ($candidateKey !== '' && $distance <= (strlen($codeKey) >= 6 ? 2 : 1)) {
            return true;
        }
    }

    return false;
}

function marksheet_number_from_ocr(string $value): ?float
{
    $value = strtoupper(trim($value));
    $value = strtr($value, ['O' => '0', 'I' => '1', 'L' => '1']);
    $value = preg_replace('/[^0-9.]/', '', $value) ?? '';
    if ($value === '' || !is_numeric($value)) {
        return null;
    }

    return (float)$value;
}

function marksheet_normalize_grade_token(string $token, ?float $earnedPerCredit = null): ?string
{
    $token = strtoupper($token);
    $token = preg_replace('/\s+/', '', $token) ?? $token;
    $token = str_replace(['|', '[', ']', '{', '}', '(', ')', '“', '”', "'", '"', '.', ','], '', $token);
    $token = strtr($token, [
        'AT' => 'A+',
        'AY' => 'A+',
        'A¥' => 'A+',
        'BT' => 'B+',
        'BY' => 'B+',
        'CT' => 'C+',
        'CY' => 'C+',
    ]);

    if (in_array($token, marksheet_allowed_grades(), true)) {
        return $token;
    }

    if ($earnedPerCredit !== null) {
        if ($earnedPerCredit >= 9.75 && str_starts_with($token, 'A')) {
            return 'A+';
        }
        if ($earnedPerCredit >= 8.75 && str_starts_with($token, 'A')) {
            return 'A';
        }
        if ($earnedPerCredit >= 7.75 && str_starts_with($token, 'B')) {
            return str_contains($token, '+') ? 'B+' : 'B';
        }
        if ($earnedPerCredit >= 6.75 && str_starts_with($token, 'C')) {
            return str_contains($token, '+') ? 'C+' : 'C';
        }
    }

    if (preg_match('/^(O|A\+|B\+|C\+|AB|DR|A|B|C|D|E|F|P|I)/', $token, $match)) {
        return $match[1];
    }

    return null;
}

function marksheet_extract_grade_payload(string $line, ?float $registeredCredit = null): array
{
    $line = strtoupper($line);
    $line = preg_replace('/([ABC])\s*\+/', '$1+', $line) ?? $line;
    $gradePattern = '(O|A\+|B\+|C\+|AB|DR|A[\+\s]*[TY]?\+?|B[\+\s]*[TY]?\+?|C[\+\s]*[TY]?\+?|D|E|F|P|I)';
    $ocrNumberPattern = '[0-9OIL](?:[0-9OIL.]{0,4})';

    if (preg_match_all('/(?<![A-Z0-9])(' . $ocrNumberPattern . ')\s*\|?\s*' . $gradePattern . '\s*\|?\s*(' . $ocrNumberPattern . ')(?![A-Z0-9])/', $line, $matches, PREG_SET_ORDER)) {
        $best = null;
        foreach ($matches as $match) {
            $credit = marksheet_number_from_ocr($match[1]);
            $earned = marksheet_number_from_ocr($match[3]);
            if ($credit === null || $earned === null) {
                continue;
            }
            if ($registeredCredit !== null && abs($credit - $registeredCredit) > 0.01) {
                continue;
            }

            $points = $credit > 0 ? round($earned / $credit, 2) : 0.0;
            if (!marksheet_validate_points($points)) {
                continue;
            }

            $grade = marksheet_normalize_grade_token($match[2], $credit > 0 ? $points : null);
            if ($grade === null) {
                continue;
            }

            $best = [
                'letter_grade' => $grade,
                'grade_points' => $points,
                'confidence' => 0.96,
            ];
        }

        if ($best !== null) {
            return $best;
        }
    }

    $gradePattern = '(O|A\+|B\+|C\+|AB|DR|A|B|C|D|E|F|P|I)';
    $pointPattern = '(10(?:\.0{1,2})?|[0-9](?:\.[0-9]{1,2})?)';

    if (preg_match_all('/(?<![A-Z0-9])' . $gradePattern . '(?![A-Z0-9])\s+' . $pointPattern . '\b/', $line, $matches, PREG_SET_ORDER)) {
        $match = end($matches);
        return [
            'letter_grade' => $match[1],
            'grade_points' => (float)$match[2],
            'confidence' => 0.88,
        ];
    }

    if (preg_match_all('/\b' . $pointPattern . '\s+(?<![A-Z0-9])' . $gradePattern . '(?![A-Z0-9])/', $line, $matches, PREG_SET_ORDER)) {
        $match = end($matches);
        return [
            'letter_grade' => $match[2],
            'grade_points' => (float)$match[1],
            'confidence' => 0.78,
        ];
    }

    if (preg_match_all('/(?<![A-Z0-9])' . $gradePattern . '(?![A-Z0-9])/', $line, $matches)) {
        $grade = end($matches[1]);
        $points = marksheet_grade_points_from_letter($grade);
        if ($points !== null) {
            return [
                'letter_grade' => $grade,
                'grade_points' => $points,
                'confidence' => 0.55,
            ];
        }
    }

    return [
        'letter_grade' => null,
        'grade_points' => null,
        'confidence' => 0.0,
    ];
}

function marksheet_parse_ocr_text(string $text, array $registeredSubjects): array
{
    $lines = marksheet_normalize_ocr_text($text);
    $rows = [];
    $matchedCodes = [];

    foreach ($registeredSubjects as $code => $subject) {
        $bestPayload = null;
        foreach ($lines as $line) {
            if (marksheet_line_matches_code($line, $code)) {
                $credit = isset($subject['credit']) && is_numeric($subject['credit']) ? (float)$subject['credit'] : null;
                $payload = marksheet_extract_grade_payload($line, $credit);
                if ($bestPayload === null || $payload['confidence'] > $bestPayload['confidence']) {
                    $bestPayload = $payload;
                }
            }
        }

        $payload = $bestPayload ?? ['letter_grade' => null, 'grade_points' => null, 'confidence' => 0.0];

        $status = ($payload['letter_grade'] !== null && $payload['grade_points'] !== null)
            ? 'matched'
            : 'missing';

        if ($status === 'matched') {
            $matchedCodes[$code] = true;
        }

        $rows[] = [
            'subject_code' => $code,
            'subject_name' => $subject['subject_name'] ?? $code,
            'letter_grade' => $payload['letter_grade'],
            'grade_points' => $payload['grade_points'],
            'status' => $status,
            'confidence' => $payload['confidence'],
        ];
    }

    $rejected = marksheet_detect_unregistered_codes($lines, array_keys($registeredSubjects));

    return [
        'rows' => $rows,
        'rejected' => $rejected,
        'summary' => [
            'registered' => count($registeredSubjects),
            'matched' => count($matchedCodes),
            'missing' => count($registeredSubjects) - count($matchedCodes),
            'rejected' => count($rejected),
        ],
    ];
}

function marksheet_detect_unregistered_codes(array $lines, array $registeredCodes): array
{
    $registeredKeys = [];
    foreach ($registeredCodes as $code) {
        $registeredKeys[marksheet_code_key($code)] = true;
    }

    $seen = [];
    $rejected = [];
    $insideSubjectTable = false;
    foreach ($lines as $line) {
        if (str_contains($line, 'SUBJECT CODE')) {
            $insideSubjectTable = true;
            continue;
        }
        if (!$insideSubjectTable) {
            continue;
        }
        if (preg_match('/^TOTAL\s*:/', $line)) {
            break;
        }
        preg_match_all('/[A-Z]{2,5}\s*[-_]?\s*[0-9OIL]{3,4}[A-Z]?/', $line, $matches);
        foreach ($matches[0] ?? [] as $candidate) {
            $candidate = preg_replace('/[^A-Z0-9]/', '', strtoupper($candidate)) ?? strtoupper($candidate);
            if (!preg_match('/\d/', $candidate)) {
                continue;
            }
            $key = marksheet_code_key($candidate);
            if ($key === '' || isset($registeredKeys[$key]) || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $rejected[] = [
                'subject_code' => $candidate,
                'reason' => 'not_registered',
            ];
            if (count($rejected) >= 20) {
                return $rejected;
            }
        }
    }

    return $rejected;
}
