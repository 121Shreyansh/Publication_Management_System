<?php

/**
 * STUDENT REGISTRATION MODULE
 * - Reads semester from semester_registration table
 * - Populates Theory and Sessional slots from subjects_pool
 * - Submits to modules/generate_pdf.php which also persists into subject_enrollement
 */

// ─── FETCH STUDENT PROFILE (with department name via JOIN) ──────────────────────
$stmt = $pdo->prepare("
    SELECT s.*, d.department_name
    FROM student_info s
    LEFT JOIN department d ON s.department_id = d.department_id
    WHERE s.student_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user_profile = $stmt->fetch();

// ─── FETCH ACTIVE SEMESTER ─────────────────────────────────────────────────────
$sem_stmt = $pdo->prepare("
    SELECT semester FROM semester_registration
    WHERE student_id = ?
    ORDER BY semester DESC
    LIMIT 1
");
$sem_stmt->execute([$_SESSION['user_id']]);
$reg_data = $sem_stmt->fetch();

if (!$reg_data) {
    echo "<div style='padding:20px; background:#fff1f2; color:#be123c; border-radius:8px;'>
            <h3><i class='fa fa-exclamation-triangle'></i> No Semester Found</h3>
            <p>You have not been assigned to a semester yet. Please contact the department office.</p>
          </div>";
    return;
}

$current_sem = $reg_data['semester'];

// ─── CHECK FOR ALREADY-ENROLLED SUBJECTS (prevent re-submission) ───────────────
$enrolled_stmt = $pdo->prepare("
    SELECT subject_code FROM subject_enrollement WHERE student_id = ?
");
$enrolled_stmt->execute([$_SESSION['user_id']]);
$enrolled_codes = array_column($enrolled_stmt->fetchAll(), 'subject_code');
$already_enrolled = !empty($enrolled_codes);

// ─── FETCH SUBJECTS FOR THIS SEMESTER ──────────────────────────────────────────
$subject_target = $user_profile['department_id'] ?? ($user_profile['student_type'] ?? 'UG');
$sub_stmt = $pdo->prepare("
    SELECT * FROM subjects_pool
    WHERE subject_semester = ? AND taught_in = ?
    ORDER BY subject_type DESC, subject_code ASC
");
$sub_stmt->execute([$current_sem, $subject_target]);
$all_subjects = $sub_stmt->fetchAll();

$theory_pool    = [];
$sessional_pool = [];
foreach ($all_subjects as $sub) {
    if ($sub['subject_type'] === 'Theory') {
        $theory_pool[] = $sub;
    } else {
        // Sessional, Project, Other all go to sessional slots
        $sessional_pool[] = $sub;
    }
}

$theory_slots = max(5, count($theory_pool));
$sessional_slots = max(3, count($sessional_pool));
?>

<div style="background: white; padding: 35px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">

    <div style="border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 25px;">
        <h2 style="color: var(--purple); margin: 0;">
            <i class="fa fa-file-signature"></i> Semester Registration
        </h2>
        <p style="color: #64748b; margin-top: 5px;">
            Department: <strong><?= htmlspecialchars($user_profile['department_name'] ?? $user_profile['department_id'] ?? 'N/A') ?></strong>
            &nbsp;|&nbsp; Registering for: <strong>Semester <?= htmlspecialchars($current_sem) ?></strong>
            &nbsp;|&nbsp; Programme: <strong><?= htmlspecialchars($user_profile['student_type'] ?? 'UG') ?></strong>
        </p>
    </div>

    <?php if ($already_enrolled): ?>
    <div style="background:#eff6ff; color:#1e40af; padding:16px; border-radius:8px; border:1px solid #bfdbfe; margin-bottom:25px;">
        <i class="fa fa-info-circle"></i>
        <strong>You have already submitted your subject enrolment.</strong>
        You can still generate the PDF again below, but your previously selected subjects will be used.
        Contact the department if you need to change your selections.
    </div>
    <?php endif; ?>

    <form action="modules/generate_pdf.php" method="POST" target="_blank" id="reg-form">

        <!-- FEE DETAILS -->
        <h4 style="color: #475569; margin-bottom: 15px;">1. Institute Fee Payment Details</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;
                    background: #f8fafc; padding: 25px; border-radius: 10px;
                    border: 1px solid #e2e8f0; margin-bottom: 30px;">
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">Amount Paid (Rs.)</label>
                <input type="number" name="fee_amount" required
                       style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">Payment Date</label>
                <input type="date" name="fee_date" required
                       style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">Transaction ID</label>
                <input type="text" name="txn_id" required
                       style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box;">
            </div>
        </div>

        <!-- SUBJECT SELECTION -->
        <h4 style="color: #475569; margin-bottom: 15px;">2. Subject Selection</h4>

        <?php if (empty($theory_pool) && empty($sessional_pool)): ?>
            <div style="background:#fef9c3; color:#854d0e; padding:16px; border-radius:8px; border:1px solid #fde047;">
                <i class="fa fa-exclamation-triangle"></i>
                No subjects found in the pool for Semester <?= $current_sem ?>.
                Please contact the department to have subjects added.
            </div>
        <?php else: ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">

            <!-- THEORY -->
            <div>
                <h5 style="color: var(--purple); border-bottom: 2px solid #e2e8f0;
                            padding-bottom: 10px; margin-bottom: 15px;">
                    <i class="fa fa-book"></i> Theory Subjects
                    <span style="font-size:12px; font-weight:normal; color:#94a3b8;">(Select exactly <?= $theory_slots ?>)</span>
                </h5>
                <?php for ($i = 1; $i <= $theory_slots; $i++): ?>
                <div style="margin-bottom: 15px;">
                    <label style="font-size:12px; font-weight:bold; color:#64748b;">
                        Theory Slot <?= $i ?>
                    </label>
                    <select name="selected_subjects[]"
                            style="width:100%; padding:10px; border:1px solid #cbd5e1;
                                   border-radius:6px; background:#fff;"
                            <?= $already_enrolled ? 'disabled' : '' ?>>
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($theory_pool as $t):
                            $sel = in_array($t['subject_code'], $enrolled_codes) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($t['subject_code']) ?>" <?= $sel ?>>
                                <?= htmlspecialchars($t['subject_code']) ?> — <?= htmlspecialchars($t['subject_name']) ?>
                                (<?= $t['credit'] ?> cr)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endfor; ?>
            </div>

            <!-- SESSIONAL -->
            <div>
                <h5 style="color: #be123c; border-bottom: 2px solid #e2e8f0;
                            padding-bottom: 10px; margin-bottom: 15px;">
                    <i class="fa fa-flask"></i> Sessional / Practical
                    <span style="font-size:12px; font-weight:normal; color:#94a3b8;">(Select exactly <?= $sessional_slots ?>)</span>
                </h5>
                <?php for ($i = 1; $i <= $sessional_slots; $i++): ?>
                <div style="margin-bottom: 15px;">
                    <label style="font-size:12px; font-weight:bold; color:#64748b;">
                        Sessional Slot <?= $i ?>
                    </label>
                    <?php if (empty($sessional_pool)): ?>
                        <input type="text" disabled value="(No practical subjects found)"
                               style="width:100%; padding:10px; border:1px solid #cbd5e1;
                                      border-radius:6px; color:#94a3b8; box-sizing:border-box;">
                    <?php else: ?>
                        <select name="selected_subjects[]"
                                style="width:100%; padding:10px; border:1px solid #cbd5e1;
                                       border-radius:6px; background:#fff;"
                                <?= $already_enrolled ? 'disabled' : '' ?>>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($sessional_pool as $s):
                                $sel = in_array($s['subject_code'], $enrolled_codes) ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars($s['subject_code']) ?>" <?= $sel ?>>
                                    <?= htmlspecialchars($s['subject_code']) ?> — <?= htmlspecialchars($s['subject_name']) ?>
                                    (<?= $s['credit'] ?> cr)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>

        </div><!-- /grid -->
        <?php endif; ?>

        <!-- SUBMIT -->
        <div style="margin-top: 30px; text-align: right;">
            <button type="submit"
                    style="background: var(--purple); color: white; border: none;
                           padding: 14px 40px; border-radius: 8px; font-weight: bold;
                           font-size: 16px; cursor: pointer;
                           box-shadow: 0 4px 12px rgba(36,0,70,0.2);">
                <i class="fa fa-print"></i>
                <?= $already_enrolled ? 'Re-generate PDF' : 'Submit & Generate Official PDF' ?>
            </button>
        </div>

    </form>
</div>

<script>
// Client-side guard: warn if dropdowns are left blank before submit
document.getElementById('reg-form')?.addEventListener('submit', function(e) {
    const selects = this.querySelectorAll('select:not([disabled])');
    let empty = 0;
    selects.forEach(s => { if (!s.value) empty++; });
    if (empty > 0) {
        if (!confirm(empty + ' subject slot(s) are still empty. Continue anyway?')) {
            e.preventDefault();
        }
    }
});
</script>
