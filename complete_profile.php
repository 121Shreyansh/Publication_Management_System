<?php
// complete_profile.php — Faculty Profile Setup (First Login)
session_start();
require_once 'config/db.php';

// Auth: must be logged in and password must already be changed
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
if ($_SESSION['change_password'] == 0) { header("Location: change_password.php"); exit; }
if ($_SESSION['profile_completed'] == 1) { header("Location: dashboard.php"); exit; }

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

// Fetch existing data (pre-filled by HOD or previous draft)
$stmt = $pdo->prepare("SELECT * FROM employee_info WHERE emp_id = ?");
$stmt->execute([$user_id]);
$p = $stmt->fetch(); // $p = profile

// Fetch departments
$departments = $pdo->query("SELECT department_id, department_name FROM department")->fetchAll();

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $is_final = isset($_POST['final_submit']);

    // Validate mandatory fields only on final submit
    $errors = [];
    if ($is_final) {
        if (empty($_POST['first_name']))        $errors[] = "First Name is required.";
        if (empty($_POST['gender']))            $errors[] = "Gender is required.";
        if (empty($_POST['mobile_no_1']))       $errors[] = "Mobile number is required.";
        if (empty($_POST['email']))             $errors[] = "Email is required.";
        if (empty($_POST['emp_designation']))   $errors[] = "Designation is required.";
        if (empty($_POST['highest_degree']))    $errors[] = "Highest Degree is required.";
        if (empty($_POST['date_join']))         $errors[] = "Date of Joining is required.";
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE employee_info SET
                first_name              = ?,
                middle_name             = ?,
                last_name               = ?,
                gender                  = ?,
                dob                     = ?,
                mobile_no_1             = ?,
                mobile_no_2             = ?,
                email                   = ?,
                address                 = ?,
                city                    = ?,
                state                   = ?,
                pin                     = ?,
                country                 = ?,
                emp_designation         = ?,
                department_id           = ?,
                highest_degree          = ?,
                highest_degree_univ     = ?,
                highest_degree_date     = ?,
                area_specialization     = ?,
                pan                     = ?,
                date_join               = ?,
                draft_saved             = 1
            WHERE emp_id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['first_name'],
                $_POST['middle_name']           ?: null,
                $_POST['last_name']             ?: null,
                $_POST['gender'],
                $_POST['dob']                   ?: null,
                $_POST['mobile_no_1'],
                $_POST['mobile_no_2']           ?: null,
                $_POST['email'],
                $_POST['address']               ?: null,
                $_POST['city']                  ?: null,
                $_POST['state']                 ?: null,
                $_POST['pin']                   ?: null,
                $_POST['country']               ?: null,
                $_POST['emp_designation'],
                $_POST['department_id']         ?: null,
                $_POST['highest_degree'],
                $_POST['highest_degree_univ']   ?: null,
                $_POST['highest_degree_date']   ?: null,
                $_POST['area_specialization']   ?: null,
                $_POST['pan']                   ?: null,
                $_POST['date_join'],
                $user_id
            ]);

            if ($is_final) {
                // Mark profile as complete
                $pdo->prepare("UPDATE `index` SET profile_completed = 1 WHERE user_id = ?")
                    ->execute([$user_id]);
                $_SESSION['profile_completed'] = 1;

                // Update session full name for dashboard
                $_SESSION['full_name'] = trim($_POST['first_name'] . ' ' . $_POST['last_name']);

                header("Location: setup_security_questions.php");
                exit;
            } else {
                // Reload page with fresh data
                $stmt = $pdo->prepare("SELECT * FROM employee_info WHERE emp_id = ?");
                $stmt->execute([$user_id]);
                $p = $stmt->fetch();
                $msg = "Draft saved successfully! You can continue filling in the rest and submit when ready.";
                $msg_type = "success";
            }

        } catch (Exception $e) {
            $msg = "Error saving: " . $e->getMessage();
            $msg_type = "error";
        }
    } else {
        $msg = implode("<br>• ", array_merge(["Please fix the following:"], $errors));
        $msg_type = "error";
    }
}

// Helper to safely get old value
function val($p, $key) {
    return htmlspecialchars($p[$key] ?? $_POST[$key] ?? '');
}
function sel($p, $key, $value) {
    return ($p[$key] ?? '') == $value ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete Your Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --purple: #240046; --hover: #3c096c; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #240046, #3c096c); min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 40px 20px; }

        .page-header { color: white; text-align: center; margin-bottom: 30px; }
        .page-header h1 { font-size: 24px; font-weight: 700; }
        .page-header p { opacity: 0.75; margin-top: 6px; font-size: 14px; }

        .card { background: white; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.25); width: 100%; max-width: 860px; overflow: hidden; }

        /* Progress Steps */
        .progress-bar { display: flex; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .step { flex: 1; padding: 16px; text-align: center; font-size: 12px; font-weight: 600; color: #94a3b8; border-bottom: 3px solid transparent; cursor: pointer; transition: all 0.2s; }
        .step.active { color: var(--purple); border-bottom-color: var(--purple); }
        .step.done { color: #16a34a; border-bottom-color: #16a34a; }
        .step i { display: block; font-size: 18px; margin-bottom: 4px; }

        /* Sections */
        .section { display: none; padding: 30px; }
        .section.active { display: block; }
        .section-title { font-size: 16px; font-weight: 700; color: var(--purple); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; }

        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .col-full { grid-column: span 3; }
        .col-2 { grid-column: span 2; }

        label { display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.4px; }
        .req { color: #dc2626; }
        input, select, textarea { width: 100%; padding: 10px 13px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; color: #1e293b; transition: border 0.2s; font-family: inherit; }
        input:focus, select:focus { outline: none; border-color: var(--purple); }
        input[readonly] { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }

        /* Alerts */
        .alert { padding: 14px 18px; border-radius: 10px; margin: 20px 30px 0; font-size: 13px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #34d399; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        /* Navigation Buttons */
        .nav-buttons { display: flex; justify-content: space-between; align-items: center; padding: 20px 30px; background: #f8fafc; border-top: 1px solid #e2e8f0; }
        .btn { padding: 11px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-outline { background: white; color: #64748b; border: 1.5px solid #e2e8f0; }
        .btn-outline:hover { border-color: #94a3b8; }
        .btn-primary { background: var(--purple); color: white; }
        .btn-primary:hover { background: var(--hover); }
        .btn-save { background: #0f766e; color: white; }
        .btn-save:hover { background: #0d5c57; }
        .btn-success { background: #16a34a; color: white; }
        .btn-success:hover { background: #15803d; }

        .draft-note { font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px; }
    </style>
</head>
<body>

<div class="page-header">
    <h1><i class="fa fa-id-card"></i> &nbsp;Complete Your Faculty Profile</h1>
    <p>Fill in your details below. You can save a draft anytime and return later.</p>
</div>

<?php if ($msg): ?>
<div style="width:100%; max-width:860px;">
    <div class="alert alert-<?= $msg_type ?>">
        <i class="fa fa-<?= $msg_type == 'success' ? 'check-circle' : 'circle-exclamation' ?>"></i>
        <?= $msg ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <!-- Progress Steps -->
    <div class="progress-bar">
        <div class="step active" id="tab-1" onclick="goTo(1)">
            <i class="fa fa-user"></i> Personal Info
        </div>
        <div class="step" id="tab-2" onclick="goTo(2)">
            <i class="fa fa-phone"></i> Contact Details
        </div>
        <div class="step" id="tab-3" onclick="goTo(3)">
            <i class="fa fa-graduation-cap"></i> Academic Info
        </div>
        <div class="step" id="tab-4" onclick="goTo(4)">
            <i class="fa fa-check-circle"></i> Review & Submit
        </div>
    </div>

    <form method="POST" id="profileForm">

        <!-- SECTION 1: Personal Info -->
        <div class="section active" id="sec-1">
            <div class="section-title"><i class="fa fa-user"></i> &nbsp;Personal Information</div>
            <div class="form-grid">
                <div>
                    <label>First Name <span class="req">*</span></label>
                    <input type="text" name="first_name" value="<?= val($p,'first_name') ?>" required>
                </div>
                <div>
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?= val($p,'middle_name') ?>">
                </div>
                <div>
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= val($p,'last_name') ?>">
                </div>
                <div>
                    <label>Gender <span class="req">*</span></label>
                    <select name="gender" required>
                        <option value="">-- Select --</option>
                        <option value="Male" <?= sel($p,'gender','Male') ?>>Male</option>
                        <option value="Female" <?= sel($p,'gender','Female') ?>>Female</option>
                        <option value="Other" <?= sel($p,'gender','Other') ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label>Date of Birth</label>
                    <input type="date" name="dob" value="<?= val($p,'dob') ?>">
                </div>
                <div>
                    <label>Employee ID</label>
                    <input type="text" value="<?= htmlspecialchars($user_id) ?>" readonly>
                </div>
            </div>
        </div>

        <!-- SECTION 2: Contact Details -->
        <div class="section" id="sec-2">
            <div class="section-title"><i class="fa fa-phone"></i> &nbsp;Contact Details</div>
            <div class="form-grid">
                <div>
                    <label>Mobile No. (Primary) <span class="req">*</span></label>
                    <input type="tel" name="mobile_no_1" value="<?= val($p,'mobile_no_1') ?>">
                </div>
                <div>
                    <label>Mobile No. (Secondary)</label>
                    <input type="tel" name="mobile_no_2" value="<?= val($p,'mobile_no_2') ?>">
                </div>
                <div>
                    <label>Email Address <span class="req">*</span></label>
                    <input type="email" name="email" value="<?= val($p,'email') ?>">
                </div>
                <div class="col-full">
                    <label>Address</label>
                    <input type="text" name="address" value="<?= val($p,'address') ?>">
                </div>
                <div>
                    <label>City</label>
                    <input type="text" name="city" value="<?= val($p,'city') ?>">
                </div>
                <div>
                    <label>State</label>
                    <input type="text" name="state" value="<?= val($p,'state') ?>">
                </div>
                <div>
                    <label>PIN Code</label>
                    <input type="number" name="pin" value="<?= val($p,'pin') ?>">
                </div>
                <div>
                    <label>Country</label>
                    <input type="text" name="country" value="<?= val($p,'country') ?>">
                </div>
            </div>
        </div>

        <!-- SECTION 3: Academic Info -->
        <div class="section" id="sec-3">
            <div class="section-title"><i class="fa fa-graduation-cap"></i> &nbsp;Academic Information</div>
            <div class="form-grid">
                <div>
                    <label>Designation <span class="req">*</span></label>
                    <input type="text" name="emp_designation" value="<?= val($p,'emp_designation') ?>" placeholder="e.g. Assistant Professor">
                </div>
                <div>
                    <label>Department</label>
                    <select name="department_id">
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['department_id'] ?>" <?= sel($p,'department_id',$dept['department_id']) ?>>
                            <?= htmlspecialchars($dept['department_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Date of Joining <span class="req">*</span></label>
                    <input type="date" name="date_join" value="<?= val($p,'date_join') ?>">
                </div>
                <div>
                    <label>Highest Degree <span class="req">*</span></label>
                    <input type="text" name="highest_degree" value="<?= val($p,'highest_degree') ?>" placeholder="e.g. Ph.D, M.Tech">
                </div>
                <div>
                    <label>Degree University</label>
                    <input type="text" name="highest_degree_univ" value="<?= val($p,'highest_degree_univ') ?>">
                </div>
                <div>
                    <label>Degree Year</label>
                    <input type="date" name="highest_degree_date" value="<?= val($p,'highest_degree_date') ?>">
                </div>
                <div class="col-full">
                    <label>Area of Specialization</label>
                    <input type="text" name="area_specialization" value="<?= val($p,'area_specialization') ?>" placeholder="e.g. Machine Learning, VLSI Design">
                </div>
                <div>
                    <label>PAN Number</label>
                    <input type="text" name="pan" value="<?= val($p,'pan') ?>" placeholder="Optional">
                </div>
            </div>
        </div>

        <!-- SECTION 4: Review -->
        <div class="section" id="sec-4">
            <div class="section-title"><i class="fa fa-check-circle"></i> &nbsp;Review & Submit</div>
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:20px; margin-bottom:20px; font-size:14px; color:#166534;">
                <i class="fa fa-info-circle"></i>
                &nbsp;Please review all your information before final submission. Once submitted, your profile will be locked and you will be taken to your dashboard.
                You can still <strong>Save as Draft</strong> if you need to come back.
            </div>
            <div style="background:#fefce8; border:1px solid #fde047; border-radius:10px; padding:16px; font-size:13px; color:#713f12;">
                <i class="fa fa-triangle-exclamation"></i>
                &nbsp;Mandatory fields: <strong>First Name, Gender, Mobile, Email, Designation, Highest Degree, Date of Joining.</strong>
                These must be filled before final submission.
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="nav-buttons">
            <div>
                <button type="button" class="btn btn-outline" id="prevBtn" onclick="navigate(-1)" style="display:none;">
                    <i class="fa fa-arrow-left"></i> Previous
                </button>
            </div>
            <div class="draft-note" id="draftNote">
                <?php if ($p && $p['draft_saved']): ?>
                    <i class="fa fa-floppy-disk" style="color:#0f766e;"></i> Draft previously saved
                <?php endif; ?>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" name="save_draft" class="btn btn-save">
                    <i class="fa fa-floppy-disk"></i> Save Draft
                </button>
                <button type="button" class="btn btn-primary" id="nextBtn" onclick="navigate(1)">
                    Next <i class="fa fa-arrow-right"></i>
                </button>
                <button type="submit" name="final_submit" class="btn btn-success" id="submitBtn" style="display:none;">
                    <i class="fa fa-check"></i> Submit & Enter Dashboard
                </button>
            </div>
        </div>

    </form>
</div>

<script>
let current = 1;
const total = 4;

function goTo(n) {
    document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    document.getElementById('sec-' + n).classList.add('active');
    document.getElementById('tab-' + n).classList.add('active');
    current = n;
    updateButtons();
}

function navigate(dir) {
    const next = current + dir;
    if (next < 1 || next > total) return;
    goTo(next);
}

function updateButtons() {
    document.getElementById('prevBtn').style.display = current > 1 ? 'inline-flex' : 'none';
    document.getElementById('nextBtn').style.display = current < total ? 'inline-flex' : 'none';
    document.getElementById('submitBtn').style.display = current === total ? 'inline-flex' : 'none';
}

updateButtons();
</script>

</body>
</html>