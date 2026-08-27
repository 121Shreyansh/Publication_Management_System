<?php
// ─── ERROR REPORTING ───────────────────────────────────────────────────────────
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ─── FPDF PATH (lives inside modules/fpdf/) ────────────────────────────────────
$fpdf_path = __DIR__ . '/fpdf/fpdf.php';
if (!file_exists($fpdf_path)) {
    die("Error: Could not find fpdf/fpdf.php. Expected at: " . $fpdf_path);
}
require($fpdf_path);

// ─── SESSION ───────────────────────────────────────────────────────────────────
session_start();

// ─── DATABASE (use shared config if available, else fallback) ──────────────────
$config_path = __DIR__ . '/../config/db.php';
if (file_exists($config_path)) {
    require($config_path); // expects $pdo to be set
} else {
    // Fallback hardcoded connection
    $host    = 'localhost';
    $db      = 'pub';
    $user    = 'root';
    $pass    = 'YES';
    $charset = 'utf8mb4';
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (\PDOException $e) {
        die("Database Connection Failed: " . $e->getMessage());
    }
}

// ─── AUTH CHECK ────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    die("Error: You are not logged in.");
}

// ─── FETCH STUDENT + DEPARTMENT (JOIN so we get department_name for PDF) ───────
$stmt = $pdo->prepare("
    SELECT s.*, d.department_name, d.department_hod
    FROM student_info s
    LEFT JOIN department d ON s.department_id = d.department_id
    WHERE s.student_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    die("Error: Student record not found for ID: " . htmlspecialchars($_SESSION['user_id']));
}

// ─── FETCH CURRENT SEMESTER FROM semester_registration ─────────────────────────
$sem_stmt = $pdo->prepare("SELECT semester FROM semester_registration WHERE student_id = ?");
$sem_stmt->execute([$_SESSION['user_id']]);
$sem_row = $sem_stmt->fetch();
$current_sem = $sem_row ? $sem_row['semester'] : ($student['current_semester'] ?? 1);

// ─── COLLECT SELECTED SUBJECT CODES FROM POST ─────────────────────────────────
$selected_ids = array_filter($_POST['selected_subjects'] ?? []);

// ─── FETCH SUBJECT DETAILS ─────────────────────────────────────────────────────
$theory_subjects    = [];
$practical_subjects = [];

if (!empty($selected_ids)) {
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
    $sub_stmt = $pdo->prepare("SELECT * FROM subjects_pool WHERE subject_code IN ($placeholders)");
    $sub_stmt->execute(array_values($selected_ids));
    foreach ($sub_stmt->fetchAll() as $s) {
        if ($s['subject_type'] === 'Theory') {
            $theory_subjects[] = $s;
        } else {
            $practical_subjects[] = $s;
        }
    }
}

// ─── SAVE ENROLMENTS INTO subject_enrollement TABLE ───────────────────────────
// Determine current academic year (e.g. if month >= July, year = this year else last year)
$academic_year = (int) date('m') >= 7 ? (int) date('Y') : (int) date('Y') - 1;
$session_label = (int) date('m') >= 7 ? 'ODD' : 'EVEN';

foreach ($selected_ids as $code) {
    if (empty($code)) continue;
    // INSERT IGNORE prevents duplicate on re-submit
    $ins = $pdo->prepare("
        INSERT IGNORE INTO subject_enrollement
            (student_id, subject_code, academic_year, session, verification_flag)
        VALUES (?, ?, ?, ?, 0)
    ");
    $ins->execute([$_SESSION['user_id'], $code, $academic_year, $session_label]);
}

// ─── PDF CLASS ────────────────────────────────────────────────────────────────
class IIEST_Registration_Form extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 5, 'Office of the Dean Academic', 0, 1, 'C');
        $this->Cell(0, 5, 'Indian Institute of Engineering Science and Technology, Shibpur', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 8, 'Semester Registration Form for Undergraduate / Postgraduate Courses', 0, 1, 'C');
        $this->Ln(2);
    }
}

// ─── GENERATE PDF ──────────────────────────────────────────────────────────────
$pdf = new IIEST_Registration_Form();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// SECTION 1 — PERSONAL DETAILS
$fullname   = strtoupper(trim(
    ($student['first_name']   ?? '') . ' ' .
    ($student['middle_name']  ?? '') . ' ' .
    ($student['last_name']    ?? '')
));
$dept_label = $student['department_name']
    ? ($student['department_id'] . ' — ' . $student['department_name'])
    : ($student['department_id'] ?? 'N/A');

$pdf->Cell(95, 7, '1. Student Name (English): ' . $fullname, 0, 0);
$pdf->Cell(95, 7, '   (Hindi): _______________________', 0, 1);

$pdf->Cell(95, 7, '2. Department: ' . $dept_label, 0, 0);
$pdf->Cell(95, 7, '3. Programme: ' . ($student['student_type'] ?? 'UG'), 0, 1);

$pdf->Cell(95, 7, '4. Registration for: Semester ' . $current_sem, 0, 0);
$pdf->Cell(95, 7, '5. Registration No: ' . ($student['student_id'] ?? 'N/A'), 0, 1);

$pdf->Cell(95, 7, '6. G-suite ID: ' . ($student['email'] ?? 'N/A'), 0, 0);
$pdf->Cell(95, 7, '   Mobile No.: ' . ($student['mobile_no'] ?? 'N/A'), 0, 1);

// SECTION 7 — FEE DETAILS
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, '7. Details of Institute fee payment:', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(63, 6, 'Amount paid: Rs. ' . htmlspecialchars($_POST['fee_amount'] ?? ''), 0, 0);
$pdf->Cell(63, 6, 'Date: ' . htmlspecialchars($_POST['fee_date'] ?? ''), 0, 0);
$pdf->Cell(64, 6, 'Transaction ID: ' . htmlspecialchars($_POST['txn_id'] ?? ''), 0, 1);
$pdf->Ln(3);
$pdf->SetFont('Arial', 'I', 9);
$pdf->Cell(0, 5, '(Attach self-attested copy of Payment receipt)', 0, 1);

// SECTION 8 — THEORY TABLE
$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, '8. Subject details (Including Practical / Laboratory Subjects):', 0, 1);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(10,  7, 'Sl.',           1, 0, 'C');
$pdf->Cell(25,  7, 'Subject Code',  1, 0, 'C');
$pdf->Cell(80,  7, 'Name of Subject (Theory)', 1, 0, 'C');
$pdf->Cell(25,  7, 'Core/Elective', 1, 0, 'C');
$pdf->Cell(20,  7, 'Credit',        1, 0, 'C');
$pdf->Cell(30,  7, 'Remarks',       1, 1, 'C');

$pdf->SetFont('Arial', '', 8);
$count = 1;
foreach ($theory_subjects as $sub) {
    $pdf->Cell(10, 7, $count++ . '.', 1, 0, 'C');
    $pdf->Cell(25, 7, $sub['subject_code'], 1);
    $pdf->Cell(80, 7, ' ' . mb_substr($sub['subject_name'], 0, 45), 1);
    $pdf->Cell(25, 7, ' Core', 1, 0, 'C');
    $pdf->Cell(20, 7, $sub['credit'], 1, 0, 'C');
    $pdf->Cell(30, 7, '', 1, 1);
}
for ($k = $count; $k <= 5; $k++) {
    $pdf->Cell(10, 7, $k . '.', 1, 0, 'C');
    $pdf->Cell(25, 7, '', 1); $pdf->Cell(80, 7, '', 1);
    $pdf->Cell(25, 7, '', 1); $pdf->Cell(20, 7, '', 1); $pdf->Cell(30, 7, '', 1, 1);
}

// PRACTICAL TABLE
$pdf->Ln(4);
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(10,  7, 'Sl.',           1, 0, 'C');
$pdf->Cell(25,  7, 'Subject Code',  1, 0, 'C');
$pdf->Cell(105, 7, 'Name of Subject (Practical/Lab/Sessional)', 1, 0, 'C');
$pdf->Cell(20,  7, 'Credit',        1, 0, 'C');
$pdf->Cell(30,  7, 'Remarks',       1, 1, 'C');

$pdf->SetFont('Arial', '', 8);
$p_count = 1;
foreach ($practical_subjects as $sub) {
    $pdf->Cell(10,  7, $p_count++ . '.', 1, 0, 'C');
    $pdf->Cell(25,  7, $sub['subject_code'], 1);
    $pdf->Cell(105, 7, ' ' . mb_substr($sub['subject_name'], 0, 55), 1);
    $pdf->Cell(20,  7, $sub['credit'], 1, 0, 'C');
    $pdf->Cell(30,  7, '', 1, 1);
}
for ($k = $p_count; $k <= 3; $k++) {
    $pdf->Cell(10, 7, $k . '.', 1, 0, 'C');
    $pdf->Cell(25, 7, '', 1); $pdf->Cell(105, 7, '', 1);
    $pdf->Cell(20, 7, '', 1); $pdf->Cell(30, 7, '', 1, 1);
}

// SIGNATURES
$pdf->Ln(8);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(20,  5, 'Date:', 0, 0);
$pdf->Cell(40,  5, date('d/m/Y'), 0, 0);
$pdf->Cell(70,  5, '', 0, 0);
$pdf->Cell(60,  5, '________________________', 0, 1, 'C');
$pdf->Cell(130, 5, '', 0, 0);
$pdf->Cell(60,  5, 'Signature of the Student', 0, 1, 'C');

$pdf->Ln(8);
$pdf->Cell(95, 5, 'Checked by the Department/School/', 0, 0);
$pdf->Cell(95, 5, 'Recommended / Not recommended', 0, 1, 'R');
$pdf->Cell(95, 5, 'Center/any other authority', 0, 0);
$pdf->Cell(95, 5, '(Signature)', 0, 1, 'R');

$pdf->Ln(8);
$pdf->Cell(95, 5, 'Scrutinized', 0, 0);
$pdf->Cell(95, 5, 'Head of the Department/School/Center', 0, 1, 'R');

// OFFICE USE
$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, 'Office use only', 'T', 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(95, 6, 'Recommended / Not recommended', 0, 0);
$pdf->Cell(95, 6, 'Approved / Not approved', 0, 1, 'R');

$pdf->Ln(12);
$pdf->Cell(63, 5, 'PIC (Examination) / AR (Academic)', 0, 0, 'C');
$pdf->Cell(63, 5, 'JR (Academic)', 0, 0, 'C');
$pdf->Cell(63, 5, 'Associate Dean (AC) / Dean (AC)', 0, 1, 'C');

// ─── OUTPUT ────────────────────────────────────────────────────────────────────
$pdf->Output('I', 'Semester_Registration_Form.pdf');
?>
