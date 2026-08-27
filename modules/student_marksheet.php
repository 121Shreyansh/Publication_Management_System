<?php
/**
 * Student marksheet page.
 *
 * Registered subjects are the only source of allowed marksheet rows. PDF OCR is
 * handled server-side and always returns a review payload before anything is
 * saved to the marksheet table.
 */
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/db.php';
}

$uid = (int)$_SESSION['user_id'];
$enrollmentNo = (string)$uid;

$metaStmt = $pdo->prepare("
    SELECT s.department_id, s.registration_no, s.student_type,
           d.department_name,
           sr.semester AS current_semester
    FROM student_info s
    LEFT JOIN department d ON d.department_id = s.department_id
    LEFT JOIN semester_registration sr ON sr.student_id = s.student_id
    WHERE s.student_id = ?
");
$metaStmt->execute([$uid]);
$meta = $metaStmt->fetch() ?: [];

$currentSem = (int)($meta['current_semester'] ?? 1);
$maxSem = ($meta['student_type'] ?? 'UG') === 'MCA' ? 4 : 8;

$enrollStmt = $pdo->prepare("
    SELECT se.subject_code, sp.subject_name, sp.subject_type, sp.credit, sp.subject_semester
    FROM subject_enrollement se
    INNER JOIN subjects_pool sp ON sp.subject_code = se.subject_code
    WHERE se.student_id = ?
    ORDER BY CAST(sp.subject_semester AS UNSIGNED) ASC, sp.subject_type DESC, se.subject_code ASC
");
$enrollStmt->execute([$uid]);
$enrolled = $enrollStmt->fetchAll();

$markStmt = $pdo->prepare("
    SELECT m.semester, m.subject_code, m.letter_grade, m.grade_points
    FROM marksheet m
    WHERE m.enrollment_no = ?
");
$markStmt->execute([$enrollmentNo]);
$allMarks = $markStmt->fetchAll();

$marksBySemesterAndCode = [];
foreach ($allMarks as $mark) {
    $semester = (string)($mark['semester'] ?? '');
    $subjectCode = strtoupper(trim((string)($mark['subject_code'] ?? '')));
    if ($semester !== '' && $subjectCode !== '') {
        $marksBySemesterAndCode[$semester][$subjectCode] = $mark;
    }
}

$bySem = [];
foreach ($enrolled as $subject) {
    $semester = (string)($subject['subject_semester'] ?? '');
    $subjectCode = strtoupper(trim((string)($subject['subject_code'] ?? '')));
    if ($semester === '' || $subjectCode === '') {
        continue;
    }

    $saved = $marksBySemesterAndCode[$semester][$subjectCode] ?? null;
    $bySem[$semester][] = [
        'semester' => $semester,
        'subject_code' => $subjectCode,
        'subject_name' => $subject['subject_name'] ?? null,
        'subject_type' => $subject['subject_type'] ?? null,
        'credit' => $subject['credit'] ?? null,
        'letter_grade' => $saved['letter_grade'] ?? null,
        'grade_points' => $saved['grade_points'] ?? null,
    ];
}

$uploadedSems = [];
$totalPts = 0.0;
$totalCr = 0.0;
foreach ($bySem as $semester => $rows) {
    foreach ($rows as $row) {
        if ($row['letter_grade'] !== null && $row['grade_points'] !== null) {
            $uploadedSems[$semester] = true;
        }
        if ($row['credit'] !== null && $row['grade_points'] !== null) {
            $totalPts += (float)$row['grade_points'] * (float)$row['credit'];
            $totalCr += (float)$row['credit'];
        }
    }
}
$cgpa = $totalCr > 0 ? round($totalPts / $totalCr, 2) : null;
?>

<style>
.ms-page { background:white; padding:32px; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,.08); border-top:6px solid #0f766e; }
.ms-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:22px; padding-bottom:16px; border-bottom:1px solid #f1f5f9; flex-wrap:wrap; gap:12px; }
.ms-title { margin:0; color:#0f766e; font-size:21px; }
.cgpa-box { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:10px 24px; text-align:center; }
.cgpa-box .lbl { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:2px; }
.cgpa-box .val { font-size:30px; font-weight:800; color:#166534; }
.sem-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:22px; }
.sem-tab { padding:7px 18px; border-radius:20px; font-size:13px; font-weight:600; cursor:pointer; border:1.5px solid #e2e8f0; background:white; color:#64748b; }
.sem-tab.active { background:#0f766e; color:white; border-color:#0f766e; }
.sem-tab.uploaded { border-color:#22c55e; color:#166534; background:#f0fdf4; }
.sem-tab.uploaded.active { background:#16a34a; color:white; border-color:#16a34a; }
.sem-tab.current { border-color:#f59e0b; color:#92400e; background:#fffbeb; }
.sem-tab.current.active { background:#d97706; color:white; border-color:#d97706; }
.sem-panel { display:none; }
.sem-panel.active { display:block; }
.upload-card, .saved-card { background:white; border-radius:14px; padding:26px; border:1px solid #e2e8f0; box-shadow:0 2px 12px rgba(0,0,0,.06); margin-bottom:22px; }
.upload-card h3, .saved-card h3 { margin:0 0 16px; color:#240046; font-size:16px; display:flex; align-items:center; gap:8px; }
.upload-controls { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px; }
.upload-controls input[type=file] { border:1.5px solid #e2e8f0; border-radius:8px; padding:8px 12px; font-size:13px; max-width:320px; }
.btn-ms { border:none; border-radius:8px; padding:9px 18px; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:7px; }
.btn-ms-primary { background:#240046; color:white; }
.btn-ms-secondary { background:#334155; color:white; }
.btn-ms-green { background:#16a34a; color:white; }
.btn-ms-green:disabled, .btn-ms-primary:disabled { opacity:.5; cursor:not-allowed; }
.notice { padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:10px; }
.notice-info { background:#f0f9ff; border:1px solid #bae6fd; color:#075985; }
.notice-warn { background:#fef9c3; border:1px solid #fde047; color:#854d0e; }
.notice-success { background:#f0fdf4; border:1px solid #86efac; color:#166534; }
.notice-error { background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; }
.review-table-wrap { border:1px solid #e2e8f0; border-radius:10px; overflow:auto; max-height:430px; margin-top:12px; display:none; }
.review-table, .ms-table { width:100%; border-collapse:collapse; background:white; }
.review-table thead tr { background:#eef2ff; position:sticky; top:0; }
.ms-table thead tr { background:#f0fdf4; }
.review-table th, .ms-table th { padding:9px 12px; text-align:left; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; border-bottom:1px solid #e2e8f0; }
.review-table td, .ms-table td { padding:10px 12px; font-size:13px; border-top:1px solid #f1f5f9; vertical-align:top; }
.review-table input[type=text] { width:100%; border:1px solid #e2e8f0; border-radius:6px; padding:6px 8px; font-size:13px; font-family:monospace; }
.review-table input.invalid { border-color:#dc2626; background:#fee2e2; }
.review-table tr.needs-manual { background:#fff7ed; }
.review-table tr.needs-manual input { border-color:#fed7aa; background:#fffaf0; }
.manual-hint { display:block; margin-top:4px; color:#9a3412; font-size:11px; font-weight:700; }
.code-cell { font-family:monospace; font-weight:700; color:#5b21b6; white-space:nowrap; }
.grade-pill, .status-pill, .type-pill { display:inline-block; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:700; }
.grade-good { background:#dcfce7; color:#166534; }
.grade-avg { background:#fef9c3; color:#854d0e; }
.grade-fail { background:#fee2e2; color:#991b1b; }
.grade-pending, .status-missing { background:#e2e8f0; color:#475569; }
.status-matched { background:#dcfce7; color:#166534; }
.type-T { background:#dbeafe; color:#1d4ed8; }
.type-S { background:#fce7f3; color:#9d174d; }
.rejected-box { display:none; margin-top:12px; padding:10px 14px; border-radius:8px; background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; font-size:13px; }
.sgpa-bar { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#f0fdf4; border-radius:8px; margin-top:14px; font-size:13px; color:#475569; gap:12px; flex-wrap:wrap; }
.sgpa-val { font-weight:800; color:#166534; font-size:16px; }
.empty-card { text-align:center; padding:30px 20px; color:#94a3b8; background:white; border-radius:14px; border:1px solid #e2e8f0; }
</style>

<div class="ms-page">
    <div class="ms-header">
        <div>
            <h2 class="ms-title"><i class="fa fa-table-list"></i> Semester Marksheet</h2>
            <p style="margin:4px 0 0;font-size:13px;color:#64748b;">
                <?= htmlspecialchars($meta['department_name'] ?? '') ?>
                <?php if ($meta['registration_no'] ?? ''): ?>
                    &nbsp;|&nbsp; Reg No: <strong><?= htmlspecialchars($meta['registration_no']) ?></strong>
                <?php endif; ?>
            </p>
        </div>
        <?php if ($cgpa !== null): ?>
        <div class="cgpa-box">
            <div class="lbl">CGPA</div>
            <div class="val"><?= htmlspecialchars((string)$cgpa) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="sem-tabs" id="semTabs">
        <?php for ($s = 1; $s <= $maxSem; $s++):
            $uploaded = isset($uploadedSems[(string)$s]);
            $isCurrent = ($s === $currentSem);
            $cls = $uploaded ? 'uploaded' : ($isCurrent ? 'current' : '');
            $cls .= ($s === 1) ? ' active' : '';
        ?>
        <button class="sem-tab <?= $cls ?>" onclick="showSem(<?= $s ?>)" id="tab<?= $s ?>">
            Sem <?= $s ?><?= $uploaded ? ' *' : ($isCurrent ? ' .' : '') ?>
        </button>
        <?php endfor; ?>
    </div>

    <?php for ($s = 1; $s <= $maxSem; $s++):
        $rows = $bySem[(string)$s] ?? [];
        $hasRows = $rows !== [];
        $hasMarks = isset($uploadedSems[(string)$s]);
        $sPts = 0.0;
        $sCr = 0.0;
        foreach ($rows as $row) {
            if ($row['credit'] !== null && $row['grade_points'] !== null) {
                $sPts += (float)$row['grade_points'] * (float)$row['credit'];
                $sCr += (float)$row['credit'];
            }
        }
        $sgpa = $sCr > 0 ? round($sPts / $sCr, 2) : null;
    ?>
    <div class="sem-panel <?= $s === 1 ? 'active' : '' ?>" id="panel<?= $s ?>">
        <div class="upload-card">
            <h3><i class="fa fa-cloud-arrow-up" style="color:#240046;"></i> Upload Marksheet - Semester <?= $s ?></h3>
            <div class="notice notice-info">
                <i class="fa fa-circle-info"></i>
                Upload a PDF marksheet. The server will OCR it, match only your registered subjects, and ask you to review before saving.
            </div>
            <div id="parseNotice<?= $s ?>" style="display:none;"></div>
            <div class="upload-controls">
                <input type="file" accept=".pdf" id="pdfFile<?= $s ?>">
                <button class="btn-ms btn-ms-primary" id="scanBtn<?= $s ?>" onclick="scanMarksheet(<?= $s ?>)">
                    <i class="fa fa-magnifying-glass"></i> Scan PDF
                </button>
                <button class="btn-ms btn-ms-green" id="saveBtn<?= $s ?>" disabled onclick="confirmMarksheet(<?= $s ?>)">
                    <i class="fa fa-floppy-disk"></i> Save Reviewed Grades
                </button>
            </div>
            <div class="review-table-wrap" id="reviewWrap<?= $s ?>">
                <table class="review-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Subject</th>
                            <th>OCR Status</th>
                            <th>Letter Grade</th>
                            <th>Grade Points</th>
                        </tr>
                    </thead>
                    <tbody id="reviewBody<?= $s ?>"></tbody>
                </table>
            </div>
            <div class="rejected-box" id="rejectedBox<?= $s ?>"></div>
        </div>

        <?php if ($hasRows): ?>
        <div class="saved-card">
            <h3><i class="fa fa-circle-check"></i> <?= $hasMarks ? 'Saved Grades' : 'Registered Subjects' ?> - Semester <?= $s ?></h3>
            <table class="ms-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Credits</th>
                        <th>Grade</th>
                        <th>Grade Pts</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row):
                    $grade = $row['letter_grade'];
                    $gradeClass = $grade === null || $grade === ''
                        ? 'grade-pending'
                        : (in_array($grade, ['O', 'A+', 'A'], true) ? 'grade-good' : (in_array($grade, ['F', 'E', 'I', 'AB', 'DR'], true) ? 'grade-fail' : 'grade-avg'));
                    $type = (string)($row['subject_type'] ?? '');
                    $typeClass = $type === 'Theory' ? 'type-T' : 'type-S';
                ?>
                    <tr>
                        <td class="code-cell"><?= htmlspecialchars($row['subject_code']) ?></td>
                        <td><?= htmlspecialchars($row['subject_name'] ?? $row['subject_code']) ?></td>
                        <td><span class="type-pill <?= $typeClass ?>"><?= htmlspecialchars($type ?: '-') ?></span></td>
                        <td><?= htmlspecialchars((string)($row['credit'] ?? '-')) ?></td>
                        <td><span class="grade-pill <?= $gradeClass ?>"><?= htmlspecialchars($grade ?: 'Pending') ?></span></td>
                        <td><?= $row['grade_points'] !== null ? htmlspecialchars((string)$row['grade_points']) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($sgpa !== null): ?>
            <div class="sgpa-bar">
                <span>Subjects: <strong><?= count($rows) ?></strong></span>
                <span>Credits: <strong><?= htmlspecialchars((string)array_sum(array_column($rows, 'credit'))) ?></strong></span>
                <span>SGPA: <span class="sgpa-val"><?= htmlspecialchars((string)$sgpa) ?> / 10</span></span>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="empty-card">
            <i class="fa fa-folder-open" style="font-size:32px;display:block;margin-bottom:10px;opacity:.3;"></i>
            <p style="font-size:14px;">No registered subjects found for Semester <?= $s ?>. Complete semester registration first.</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endfor; ?>
</div>

<script>
const ocrReviewTokens = {};
const gradePattern = /^(O|A\+|B\+|C\+|AB|DR|A|B|C|D|E|F|P|I)$/;

function showSem(n) {
    document.querySelectorAll('.sem-panel').forEach(panel => panel.classList.remove('active'));
    document.querySelectorAll('.sem-tab').forEach(tab => tab.classList.remove('active'));
    document.getElementById('panel' + n).classList.add('active');
    document.getElementById('tab' + n).classList.add('active');
}

function scanMarksheet(sem) {
    const fileInput = document.getElementById('pdfFile' + sem);
    const file = fileInput.files[0];
    if (!file) {
        setNotice(sem, 'warn', 'Choose a PDF marksheet first.');
        return;
    }

    const btn = document.getElementById('scanBtn' + sem);
    const saveBtn = document.getElementById('saveBtn' + sem);
    btn.disabled = true;
    saveBtn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Scanning...';
    setNotice(sem, 'info', 'Uploading PDF and running OCR on the server...');

    const body = new FormData();
    body.append('semester', String(sem));
    body.append('marksheet_pdf', file);

    fetch('modules/marksheet_ocr_upload.php', {
        method: 'POST',
        credentials: 'same-origin',
        body
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-magnifying-glass"></i> Scan PDF';
        if (!data.ok) {
            clearReview(sem);
            setNotice(sem, 'error', escHtml(data.message || 'OCR failed.'));
            return;
        }
        renderReview(sem, data);
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-magnifying-glass"></i> Scan PDF';
        clearReview(sem);
        setNotice(sem, 'error', 'Network error while scanning: ' + escHtml(error.message));
    });
}

function renderReview(sem, data) {
    ocrReviewTokens[sem] = data.token;
    const tbody = document.getElementById('reviewBody' + sem);
    tbody.innerHTML = '';

    (data.rows || []).forEach(row => {
        const isMatched = row.status === 'matched';
        const statusClass = isMatched ? 'status-matched' : 'status-missing';
        const statusLabel = isMatched ? 'OCR matched' : 'Manual entry needed';
        const rowClass = isMatched ? '' : ' class="needs-manual"';
        const hint = isMatched ? '' : '<span class="manual-hint">OCR could not confidently read this subject. Enter it from the PDF.</span>';
        tbody.insertAdjacentHTML('beforeend', `
            <tr data-code="${escAttr(row.subject_code)}"${rowClass}>
                <td class="code-cell">${escHtml(row.subject_code)}</td>
                <td>${escHtml(row.subject_name || row.subject_code)}</td>
                <td><span class="status-pill ${statusClass}">${statusLabel}</span></td>
                <td><input type="text" class="grade-inp" value="${escAttr(row.letter_grade || '')}" placeholder="A+" oninput="validateReview(${sem})" maxlength="4">${hint}</td>
                <td><input type="text" class="pts-inp" value="${escAttr(row.grade_points ?? '')}" placeholder="0-10" oninput="validateReview(${sem})"></td>
            </tr>
        `);
    });

    const rejectedBox = document.getElementById('rejectedBox' + sem);
    const rejected = data.rejected || [];
    if (rejected.length > 0) {
        rejectedBox.style.display = 'block';
        rejectedBox.innerHTML = '<strong>Rejected unregistered OCR rows:</strong> ' + rejected
            .map(item => escHtml(item.subject_code || 'Unknown'))
            .join(', ');
    } else {
        rejectedBox.style.display = 'none';
        rejectedBox.innerHTML = '';
    }

    document.getElementById('reviewWrap' + sem).style.display = 'block';
    const missing = data.summary?.missing ?? 0;
    const matched = data.summary?.matched ?? 0;
    const rejectedCount = data.summary?.rejected ?? 0;
    const reviewHint = missing === 0
        ? 'Review the rows, then click Save Reviewed Grades.'
        : 'Fill the missing grade fields in the review table before saving.';
    setNotice(sem, missing === 0 ? 'success' : 'warn', `OCR review ready. Matched ${matched} registered subject(s), missing ${missing}, rejected ${rejectedCount} unregistered row(s). ${reviewHint}`);
    validateReview(sem);
    document.getElementById('reviewWrap' + sem).scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function validateReview(sem) {
    let valid = true;
    document.querySelectorAll('#reviewBody' + sem + ' tr').forEach(row => {
        const gradeInput = row.querySelector('.grade-inp');
        const pointsInput = row.querySelector('.pts-inp');
        const grade = gradeInput.value.trim().toUpperCase();
        const points = pointsInput.value.trim();
        const numericPoints = Number(points);

        gradeInput.value = grade;
        gradeInput.classList.toggle('invalid', !gradePattern.test(grade));
        pointsInput.classList.toggle('invalid', points === '' || Number.isNaN(numericPoints) || numericPoints < 0 || numericPoints > 10);

        if (!gradePattern.test(grade) || points === '' || Number.isNaN(numericPoints) || numericPoints < 0 || numericPoints > 10) {
            valid = false;
        }
    });
    const saveBtn = document.getElementById('saveBtn' + sem);
    saveBtn.disabled = !valid || !ocrReviewTokens[sem];
    saveBtn.title = saveBtn.disabled
        ? 'Complete every reviewed grade and grade point before saving.'
        : 'Save the reviewed OCR grades to the database.';
}

function confirmMarksheet(sem) {
    validateReview(sem);
    const btn = document.getElementById('saveBtn' + sem);
    if (btn.disabled) {
        setNotice(sem, 'error', 'Every registered subject needs a valid letter grade and grade point from 0 to 10.');
        return;
    }

    const rows = Array.from(document.querySelectorAll('#reviewBody' + sem + ' tr')).map(row => ({
        subject_code: row.dataset.code,
        letter_grade: row.querySelector('.grade-inp').value.trim().toUpperCase(),
        grade_points: Number(row.querySelector('.pts-inp').value.trim())
    }));

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
    setNotice(sem, 'info', 'Saving reviewed grades...');

    fetch('modules/marksheet_ocr_confirm.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: ocrReviewTokens[sem], semester: String(sem), rows })
    })
    .then(response => response.json())
    .then(data => {
        btn.innerHTML = '<i class="fa fa-floppy-disk"></i> Save Reviewed Grades';
        if (!data.ok) {
            btn.disabled = false;
            setNotice(sem, 'error', escHtml(data.message || 'Could not save reviewed grades.'));
            validateReview(sem);
            return;
        }
        setNotice(sem, 'success', `Saved ${data.summary?.saved ?? rows.length} registered subject grade(s). <a href="student_dashboard.php?page=marksheet" style="font-weight:700;color:#065f46;">Refresh to see grades.</a>`);
        document.getElementById('tab' + sem).classList.add('uploaded');
        document.getElementById('tab' + sem).textContent = 'Sem ' + sem + ' *';
    })
    .catch(error => {
        btn.innerHTML = '<i class="fa fa-floppy-disk"></i> Save Reviewed Grades';
        btn.disabled = false;
        setNotice(sem, 'error', 'Network error while saving: ' + escHtml(error.message));
        validateReview(sem);
    });
}

function clearReview(sem) {
    ocrReviewTokens[sem] = null;
    document.getElementById('reviewBody' + sem).innerHTML = '';
    document.getElementById('reviewWrap' + sem).style.display = 'none';
    document.getElementById('rejectedBox' + sem).style.display = 'none';
    document.getElementById('saveBtn' + sem).disabled = true;
}

function setNotice(sem, type, html) {
    const el = document.getElementById('parseNotice' + sem);
    el.className = 'notice notice-' + type;
    el.innerHTML = html;
    el.style.display = '';
}

function escHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function escAttr(value) {
    return escHtml(value).replace(/'/g, '&#039;');
}
</script>
