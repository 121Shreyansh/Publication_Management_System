<?php
// student_complete_profile.php — First-time profile setup for students
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']))                              { header("Location: index.php"); exit; }
if ($_SESSION['change_password'] == 0)                        { header("Location: change_password.php"); exit; }
if ($_SESSION['profile_completed'] == 1)                      { header("Location: student_dashboard.php"); exit; }
if (!str_starts_with($_SESSION['role'] ?? '', 'Student-'))    { header("Location: index.php"); exit; }

$user_id = $_SESSION['user_id'];
$msg = ''; $msg_type = '';

// Fetch any pre-filled data (created by HOD)
$stmt = $pdo->prepare("SELECT s.*, d.department_name FROM student_info s LEFT JOIN department d ON s.department_id = d.department_id WHERE s.student_id = ?");
$stmt->execute([$user_id]);
$p = $stmt->fetch();

$departments = $pdo->query("SELECT department_id, department_name FROM department ORDER BY department_name")->fetchAll();

// ── Handle submission ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_final = isset($_POST['final_submit']);
    $errors   = [];

    if ($is_final) {
        if (empty($_POST['first_name']))           $errors[] = "First Name is required.";
        if (empty($_POST['gender']))               $errors[] = "Gender is required.";
        if (empty($_POST['email']))                $errors[] = "Email is required.";
        if (empty($_POST['guardian_first_name']))  $errors[] = "Guardian First Name is required.";
        if (empty($_POST['guardain_mobile_no']))   $errors[] = "Guardian Mobile is required.";
    }

    if (empty($errors)) {
        try {
            $mobile   = !empty($_POST['mobile_no'])          ? $_POST['mobile_no']          : null;
            $g_mobile = !empty($_POST['guardain_mobile_no']) ? $_POST['guardain_mobile_no'] : null;
            $pin      = !empty($_POST['pin'])                ? $_POST['pin']                : null;

            $sql = "UPDATE student_info SET
                first_name            = ?,
                middle_name           = ?,
                last_name             = ?,
                gender                = ?,
                dob                   = ?,
                mobile_no             = ?,
                email                 = ?,
                address               = ?,
                city                  = ?,
                state                 = ?,
                pin                   = ?,
                country               = ?,
                blood_group           = ?,
                guardian_first_name   = ?,
                guardian_middle_name  = ?,
                guardian_last_name    = ?,
                guardain_mobile_no    = ?,
                guardian_email        = ?,
                registration_no       = ?,
                enrolment_date        = ?,
                draft_saved           = 1
            WHERE student_id = ?";

            $pdo->prepare($sql)->execute([
                $_POST['first_name'],
                $_POST['middle_name']          ?: null,
                $_POST['last_name']            ?: null,
                $_POST['gender'],
                $_POST['dob']                  ?: null,
                $mobile,
                $_POST['email'],
                $_POST['address']              ?: null,
                $_POST['city']                 ?: null,
                $_POST['state']                ?: null,
                $pin,
                $_POST['country']              ?: null,
                $_POST['blood_group']          ?: null,
                $_POST['guardian_first_name'],
                $_POST['guardian_middle_name'] ?: null,
                $_POST['guardian_last_name']   ?: null,
                $g_mobile,
                $_POST['guardian_email']       ?: null,
                $_POST['registration_no']      ?: null,
                $_POST['enrolment_date']       ?: null,
                $user_id
            ]);

            if ($is_final) {
                $pdo->prepare("UPDATE `index` SET profile_completed = 1 WHERE user_id = ?")
                    ->execute([$user_id]);
                $_SESSION['profile_completed'] = 1;
                header("Location: setup_security_questions.php");
                exit;
            } else {
                $stmt = $pdo->prepare("SELECT s.*, d.department_name FROM student_info s LEFT JOIN department d ON s.department_id = d.department_id WHERE s.student_id = ?");
                $stmt->execute([$user_id]);
                $p = $stmt->fetch();
                $msg = "Draft saved! Fill in the rest and submit when ready.";
                $msg_type = "success";
            }
        } catch (Exception $e) {
            $msg = "Error: " . $e->getMessage();
            $msg_type = "error";
        }
    } else {
        $msg = implode("<br>• ", array_merge(["Please fix the following:"], $errors));
        $msg_type = "error";
    }
}

function sv($p, $k) { return htmlspecialchars($p[$k] ?? ''); }
function so($p, $k, $v) { return ($p[$k] ?? '') == $v ? 'selected' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete Your Profile — IIEST</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --purple:#240046; --bg:#f1f5f9; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; background:var(--bg); min-height:100vh; }

        /* ── TOP BAR ── */
        .topbar { background:var(--purple); color:white; height:62px; display:flex; align-items:center; justify-content:space-between; padding:0 32px; box-shadow:0 2px 10px rgba(0,0,0,0.25); position:sticky; top:0; z-index:100; }
        .topbar-brand { font-size:16px; font-weight:700; display:flex; align-items:center; gap:10px; }
        .topbar-brand span { opacity:0.5; font-weight:300; font-size:13px; }
        .topbar-right { font-size:13px; opacity:0.7; }

        /* ── STEP INDICATOR ── */
        .steps-bar { background:white; border-bottom:1px solid #e2e8f0; padding:18px 40px; display:flex; align-items:center; gap:0; }
        .step { display:flex; align-items:center; gap:10px; flex:1; }
        .step-num { width:30px; height:30px; border-radius:50%; background:#e2e8f0; color:#94a3b8; font-weight:700; font-size:13px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .step.active .step-num { background:var(--purple); color:white; }
        .step.done   .step-num { background:#16a34a; color:white; }
        .step-label { font-size:13px; font-weight:600; color:#94a3b8; }
        .step.active .step-label { color:var(--purple); }
        .step.done   .step-label { color:#16a34a; }
        .step-line { flex:1; height:2px; background:#e2e8f0; margin:0 10px; }

        /* ── MAIN ── */
        .container { max-width:960px; margin:32px auto; padding:0 24px 60px; }
        .card { background:white; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,0.07); overflow:hidden; margin-bottom:20px; }
        .card-head { padding:20px 28px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:10px; }
        .card-head h3 { margin:0; font-size:15px; color:var(--purple); }
        .card-body { padding:24px 28px; }
        .grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:18px; }
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
        .span-2 { grid-column:span 2; }
        .span-3 { grid-column:span 3; }

        .fl { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:5px; }
        .fl .opt { font-weight:400; text-transform:none; color:#94a3b8; }
        input, select, textarea {
            width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:8px;
            font-size:14px; color:#1e293b; background:white; transition:border 0.2s; font-family:inherit;
        }
        input:focus, select:focus, textarea:focus { outline:none; border-color:var(--purple); box-shadow:0 0 0 3px rgba(36,0,70,0.07); }
        input[readonly] { background:#f8fafc; color:#94a3b8; cursor:not-allowed; }
        textarea { resize:vertical; min-height:70px; }

        .locked-info { background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:14px 18px; font-size:13px; color:#166534; margin-bottom:20px; display:flex; align-items:center; gap:8px; }

        .alert { padding:14px 18px; border-radius:8px; font-size:13px; margin-bottom:20px; display:flex; align-items:flex-start; gap:10px; }
        .alert-success { background:#d1fae5; color:#065f46; border:1px solid #34d399; }
        .alert-error   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }

        .btn-row { display:flex; gap:12px; justify-content:flex-end; padding:20px 28px; background:#f8fafc; border-top:1px solid #e2e8f0; }
        .btn { padding:11px 28px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; border:none; display:inline-flex; align-items:center; gap:8px; }
        .btn-primary { background:var(--purple); color:white; }
        .btn-primary:hover { background:#3c096c; }
        .btn-outline { background:white; color:#64748b; border:1.5px solid #e2e8f0; }
        .btn-outline:hover { border-color:#94a3b8; }

        .guardian-card { background:#fffcf0; border:1px solid #fef3c7; border-radius:10px; padding:20px; }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-brand">
        <i class="fa fa-graduation-cap"></i>
        Student Portal
        <span>| IIEST Shibpur — Profile Setup</span>
    </div>
    <div class="topbar-right">
        Logged in as <strong><?= htmlspecialchars($user_id) ?></strong>
        &nbsp;·&nbsp; <a href="logout.php" style="color:rgba(255,255,255,0.7);text-decoration:none;">Logout</a>
    </div>
</div>

<!-- Step indicator -->
<div class="steps-bar">
    <div class="step done">
        <div class="step-num"><i class="fa fa-check" style="font-size:11px;"></i></div>
        <div class="step-label">Change Password</div>
    </div>
    <div class="step-line"></div>
    <div class="step active">
        <div class="step-num">2</div>
        <div class="step-label">Complete Profile</div>
    </div>
    <div class="step-line"></div>
    <div class="step">
        <div class="step-num">3</div>
        <div class="step-label">Security Questions</div>
    </div>
</div>

<div class="container">

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?>">
        <i class="fa fa-<?= $msg_type==='success'?'check-circle':'circle-exclamation' ?>"></i>
        <div><?= $msg ?></div>
    </div>
    <?php endif; ?>

    <!-- Locked academic info -->
    <div class="locked-info">
        <i class="fa fa-lock"></i>
        <div>Some academic details below are pre-filled by the department and cannot be changed. Fill in your personal and guardian information.</div>
    </div>

    <form method="POST">

        <!-- Academic Summary (readonly) -->
        <div class="card">
            <div class="card-head">
                <i class="fa fa-graduation-cap" style="color:var(--purple);"></i>
                <h3>Academic Details</h3>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div>
                        <label class="fl">Student ID</label>
                        <input type="text" value="<?= htmlspecialchars($user_id) ?>" readonly>
                    </div>
                    <div>
                        <label class="fl">Programme</label>
                        <input type="text" value="<?= sv($p,'student_type') ?>" readonly>
                    </div>
                    <div>
                        <label class="fl">Department</label>
                        <input type="text" value="<?= sv($p,'department_name') ?: sv($p,'department_id') ?>" readonly>
                    </div>
                    <div>
                        <label class="fl">Registration No. <span class="opt">(optional)</span></label>
                        <input type="text" name="registration_no" value="<?= sv($p,'registration_no') ?>" placeholder="University reg. number">
                    </div>
                    <div>
                        <label class="fl">Enrolment Date <span class="opt">(optional)</span></label>
                        <input type="date" name="enrolment_date" value="<?= sv($p,'enrolment_date') ?>">
                    </div>
                    <?php if (($p['student_type']??'') === 'PhD'): ?>
                    <div>
                        <label class="fl">Category of PhD</label>
                        <input type="text" name="category_of_phd" value="<?= sv($p,'category_of_phd') ?>" placeholder="e.g. Full-time, Part-time">
                    </div>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Personal Details -->
        <div class="card">
            <div class="card-head">
                <i class="fa fa-user" style="color:var(--purple);"></i>
                <h3>Personal Details</h3>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div>
                        <label class="fl">First Name <span style="color:#dc2626">*</span></label>
                        <input type="text" name="first_name" value="<?= sv($p,'first_name') ?>" required>
                    </div>
                    <div>
                        <label class="fl">Middle Name <span class="opt">(optional)</span></label>
                        <input type="text" name="middle_name" value="<?= sv($p,'middle_name') ?>">
                    </div>
                    <div>
                        <label class="fl">Last Name <span class="opt">(optional)</span></label>
                        <input type="text" name="last_name" value="<?= sv($p,'last_name') ?>">
                    </div>
                    <div>
                        <label class="fl">Gender <span style="color:#dc2626">*</span></label>
                        <select name="gender" required>
                            <option value="">— Select —</option>
                            <option value="Male"   <?= so($p,'gender','Male') ?>>Male</option>
                            <option value="Female" <?= so($p,'gender','Female') ?>>Female</option>
                            <option value="Other"  <?= so($p,'gender','Other') ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="fl">Date of Birth</label>
                        <input type="date" name="dob" value="<?= sv($p,'dob') ?>">
                    </div>
                    <div>
                        <label class="fl">Blood Group</label>
                        <input type="text" name="blood_group" value="<?= sv($p,'blood_group') ?>" placeholder="e.g. O+">
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Details -->
        <div class="card">
            <div class="card-head">
                <i class="fa fa-address-card" style="color:var(--purple);"></i>
                <h3>Contact Details</h3>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div>
                        <label class="fl">Email <span style="color:#dc2626">*</span></label>
                        <input type="email" name="email" value="<?= sv($p,'email') ?>" required>
                    </div>
                    <div>
                        <label class="fl">Mobile No.</label>
                        <input type="text" name="mobile_no" value="<?= sv($p,'mobile_no') ?>" placeholder="10-digit number">
                    </div>
                    <div>
                        <label class="fl">City</label>
                        <input type="text" name="city" value="<?= sv($p,'city') ?>">
                    </div>
                    <div class="span-2">
                        <label class="fl">Full Address</label>
                        <textarea name="address"><?= sv($p,'address') ?></textarea>
                    </div>
                    <div>
                        <label class="fl">State</label>
                        <input type="text" name="state" value="<?= sv($p,'state') ?>">
                    </div>
                    <div>
                        <label class="fl">PIN Code</label>
                        <input type="text" name="pin" value="<?= sv($p,'pin') ?>">
                    </div>
                    <div>
                        <label class="fl">Country</label>
                        <input type="text" name="country" value="<?= sv($p,'country') ?>" placeholder="India">
                    </div>
                </div>
            </div>
        </div>

        <!-- Guardian Details -->
        <div class="card">
            <div class="card-head">
                <i class="fa fa-people-roof" style="color:#d97706;"></i>
                <h3>Guardian / Emergency Contact</h3>
            </div>
            <div class="card-body">
                <div class="guardian-card">
                    <div class="grid-2">
                        <div>
                            <label class="fl">Guardian First Name <span style="color:#dc2626">*</span></label>
                            <input type="text" name="guardian_first_name" value="<?= sv($p,'guardian_first_name') ?>" required>
                        </div>
                        <div>
                            <label class="fl">Guardian Last Name</label>
                            <input type="text" name="guardian_last_name" value="<?= sv($p,'guardian_last_name') ?>">
                        </div>
                        <div>
                            <label class="fl">Guardian Middle Name <span class="opt">(optional)</span></label>
                            <input type="text" name="guardian_middle_name" value="<?= sv($p,'guardian_middle_name') ?>">
                        </div>
                        <div>
                            <label class="fl">Guardian Mobile <span style="color:#dc2626">*</span></label>
                            <input type="text" name="guardain_mobile_no" value="<?= sv($p,'guardain_mobile_no') ?>" required>
                        </div>
                        <div class="span-2">
                            <label class="fl">Guardian Email <span class="opt">(optional)</span></label>
                            <input type="email" name="guardian_email" value="<?= sv($p,'guardian_email') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Declaration + Buttons -->
        <div class="card">
            <div class="card-body">
                <div style="display:flex; align-items:flex-start; gap:12px; background:#f8fafc; border-radius:8px; padding:16px;">
                    <input type="checkbox" name="declaration" id="decl" required style="width:18px;height:18px;margin-top:2px;flex-shrink:0;">
                    <label for="decl" style="font-size:14px;color:#475569;cursor:pointer;line-height:1.5;">
                        <strong>Declaration:</strong> I confirm that all information provided is accurate. I understand this will be reviewed by the department for verification.
                    </label>
                </div>
            </div>
            <div class="btn-row">
                <button type="submit" name="save_draft" class="btn btn-outline">
                    <i class="fa fa-floppy-disk"></i> Save as Draft
                </button>
                <button type="submit" name="final_submit" class="btn btn-primary">
                    <i class="fa fa-paper-plane"></i> Submit Profile
                </button>
            </div>
        </div>

    </form>
</div>

</body>
</html>
