<?php
// pub_profile.php — Faculty Profile View & Edit (included inside dashboard.php)
if (!isset($pdo)) { require_once __DIR__ . '/../config/db.php'; }
$user_id = $_SESSION['user_id'];

// Fetch departments
$departments = $pdo->query("SELECT department_id, department_name FROM department")->fetchAll();

// Handle updates
$prof_msg = ''; $prof_msg_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $section = $_POST['section'];
    try {
        if ($section === 'personal') {
            $stmt = $pdo->prepare("UPDATE employee_info SET
                gender=?, dob=?, mobile_no_1=?, mobile_no_2=?, email=?,
                address=?, city=?, state=?, pin=?, country=?
                WHERE emp_id=?");
            $stmt->execute([
                $_POST['gender'], $_POST['dob']?:null, $_POST['mobile_no_1'],
                $_POST['mobile_no_2']?:null, $_POST['email'],
                $_POST['address']?:null, $_POST['city']?:null,
                $_POST['state']?:null, $_POST['pin']?:null,
                $_POST['country']?:null, $user_id
            ]);
        } elseif ($section === 'professional') {
            $stmt = $pdo->prepare("UPDATE employee_info SET
                emp_designation=?, area_specialization=?,
                highest_degree=?, highest_degree_univ=?, highest_degree_date=?
                WHERE emp_id=?");
            $stmt->execute([
                $_POST['emp_designation'], $_POST['area_specialization']?:null,
                $_POST['highest_degree'], $_POST['highest_degree_univ']?:null,
                $_POST['highest_degree_date']?:null, $user_id
            ]);
        }
        $prof_msg = 'Profile updated successfully.';
        $prof_msg_type = 'success';
    } catch (Exception $e) {
        $prof_msg = 'Error: ' . $e->getMessage();
        $prof_msg_type = 'error';
    }
}

// Fetch fresh data
$stmt = $pdo->prepare("SELECT e.*, d.department_name FROM employee_info e LEFT JOIN department d ON e.department_id = d.department_id WHERE e.emp_id = ?");
$stmt->execute([$user_id]);
$p = $stmt->fetch();

function pv($p, $k) { return htmlspecialchars($p[$k] ?? ''); }
function ps($p, $k, $v) { return ($p[$k] ?? '') == $v ? 'selected' : ''; }
?>
<style>
    .prof-wrap { display:grid; grid-template-columns:300px 1fr; gap:24px; align-items:start; }

    /* Left card — identity */
    .prof-id-card { background:white; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.08); overflow:hidden; }
    .prof-id-banner { background:linear-gradient(135deg,#240046,#7c3aed); padding:30px 24px 20px; text-align:center; }
    .prof-avatar { width:80px; height:80px; border-radius:50%; background:rgba(255,255,255,0.2); border:3px solid rgba(255,255,255,0.5); display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:700; color:white; margin:0 auto 12px; }
    .prof-id-banner h3 { color:white; margin:0 0 4px; font-size:18px; }
    .prof-id-banner p  { color:rgba(255,255,255,0.7); margin:0; font-size:13px; }
    .prof-id-body { padding:20px 24px; }
    .prof-id-row { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid #f1f5f9; font-size:13px; color:#475569; }
    .prof-id-row:last-child { border-bottom:none; }
    .prof-id-row i { width:16px; color:#94a3b8; text-align:center; flex-shrink:0; }
    .prof-id-row strong { color:#1e293b; }
    .locked-badge { display:inline-flex; align-items:center; gap:5px; background:#f1f5f9; color:#64748b; font-size:11px; padding:4px 10px; border-radius:20px; margin-top:16px; border:1px solid #e2e8f0; }

    /* Right — tab panels */
    .prof-panels { background:white; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.08); overflow:hidden; }
    .prof-tabs { display:flex; border-bottom:2px solid #f1f5f9; }
    .prof-tab { flex:1; padding:16px 20px; text-align:center; font-size:13px; font-weight:600; color:#94a3b8; cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; transition:all 0.2s; display:flex; align-items:center; justify-content:center; gap:8px; }
    .prof-tab.active { color:#240046; border-bottom-color:#240046; }
    .prof-tab:hover { color:#240046; background:#faf5ff; }

    .prof-panel { display:none; padding:28px 30px; }
    .prof-panel.active { display:block; }
    .prof-panel-title { font-size:15px; font-weight:700; color:#240046; margin:0 0 20px; display:flex; align-items:center; gap:8px; }

    .prof-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
    .prof-col-full { grid-column:span 3; }
    .prof-col-2    { grid-column:span 2; }
    .prof-label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:5px; }
    .prof-input { width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; color:#1e293b; box-sizing:border-box; transition:border 0.2s; }
    .prof-input:focus { outline:none; border-color:#240046; box-shadow:0 0 0 3px rgba(36,0,70,0.07); }
    .prof-input[readonly], .prof-input[disabled] { background:#f8fafc; color:#94a3b8; cursor:not-allowed; }

    .prof-locked-note { display:inline-flex; align-items:center; gap:6px; font-size:11px; color:#94a3b8; margin-top:4px; }

    .prof-footer { padding:20px 30px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:12px; align-items:center; }
    .btn-prof-save { background:#240046; color:white; border:none; padding:11px 28px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:8px; }
    .btn-prof-save:hover { background:#3c096c; }

    .prof-alert-success { background:#d1fae5; color:#065f46; padding:13px 18px; border-radius:8px; border:1px solid #34d399; font-size:13px; display:flex; align-items:center; gap:8px; margin-bottom:18px; }
    .prof-alert-error   { background:#fee2e2; color:#991b1b; padding:13px 18px; border-radius:8px; border:1px solid #fca5a5; font-size:13px; display:flex; align-items:center; gap:8px; margin-bottom:18px; }
</style>

<?php
$fullName   = trim(pv($p,'first_name').' '.pv($p,'last_name')) ?: 'Faculty';
$initials   = strtoupper(substr($p['first_name']??'F',0,1).substr($p['last_name']??'',0,1));
$deptName   = pv($p,'department_name') ?: pv($p,'department_id') ?: 'N/A';
$designation = pv($p,'emp_designation') ?: 'Faculty';
$activeTab  = $_GET['prof_tab'] ?? 'personal';
?>

<div class="prof-wrap">

    <!-- ── LEFT: Identity Card ── -->
    <div class="prof-id-card">
        <div class="prof-id-banner">
            <div class="prof-avatar"><?= $initials ?></div>
            <h3><?= $fullName ?></h3>
            <p><?= $designation ?></p>
        </div>
        <div class="prof-id-body">
            <div class="prof-id-row"><i class="fa fa-id-badge"></i><span>Employee ID &nbsp;<strong><?= pv($p,'emp_id') ?: $user_id ?></strong></span></div>
            <div class="prof-id-row"><i class="fa fa-building"></i><span><?= $deptName ?></span></div>
            <div class="prof-id-row"><i class="fa fa-calendar-plus"></i><span>Joined &nbsp;<strong><?= pv($p,'date_join') ? date('d M Y', strtotime($p['date_join'])) : '—' ?></strong></span></div>
            <div class="prof-id-row"><i class="fa fa-graduation-cap"></i><span><?= pv($p,'highest_degree') ?: '—' ?></span></div>
            <div class="prof-id-row"><i class="fa fa-envelope"></i><span style="word-break:break-all;"><?= pv($p,'email') ?: '—' ?></span></div>
            <div class="prof-id-row"><i class="fa fa-phone"></i><span><?= pv($p,'mobile_no_1') ?: '—' ?></span></div>
            <div style="text-align:center; margin-top:4px;">
                <span class="locked-badge"><i class="fa fa-lock"></i> Some fields are locked</span>
            </div>
        </div>
    </div>

    <!-- ── RIGHT: Edit Panels ── -->
    <div class="prof-panels">
        <div class="prof-tabs">
            <div class="prof-tab <?= $activeTab==='personal'?'active':'' ?>" onclick="switchTab('personal')">
                <i class="fa fa-user"></i> Personal Details
            </div>
            <div class="prof-tab <?= $activeTab==='professional'?'active':'' ?>" onclick="switchTab('professional')">
                <i class="fa fa-graduation-cap"></i> Professional Details
            </div>
        </div>

        <?php if ($prof_msg): ?>
        <div style="padding:18px 30px 0;">
            <div class="prof-alert-<?= $prof_msg_type ?>">
                <i class="fa fa-<?= $prof_msg_type==='success'?'check-circle':'circle-exclamation' ?>"></i>
                <?= htmlspecialchars($prof_msg) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── PERSONAL TAB ── -->
        <form method="POST" id="form-personal">
            <input type="hidden" name="update_profile" value="1">
            <input type="hidden" name="section" value="personal">
            <div class="prof-panel <?= $activeTab==='personal'?'active':'' ?>" id="panel-personal">
                <div class="prof-panel-title"><i class="fa fa-user"></i> Personal Information</div>
                <div class="prof-grid">
                    <!-- LOCKED: Name fields -->
                    <div>
                        <label class="prof-label">First Name <i class="fa fa-lock" style="font-size:9px;color:#cbd5e1;"></i></label>
                        <input class="prof-input" value="<?= pv($p,'first_name') ?>" readonly>
                        <span class="prof-locked-note"><i class="fa fa-lock"></i> Contact HOD to change</span>
                    </div>
                    <div>
                        <label class="prof-label">Middle Name <i class="fa fa-lock" style="font-size:9px;color:#cbd5e1;"></i></label>
                        <input class="prof-input" value="<?= pv($p,'middle_name') ?>" readonly>
                    </div>
                    <div>
                        <label class="prof-label">Last Name <i class="fa fa-lock" style="font-size:9px;color:#cbd5e1;"></i></label>
                        <input class="prof-input" value="<?= pv($p,'last_name') ?>" readonly>
                    </div>

                    <!-- EDITABLE: Gender, DOB -->
                    <div>
                        <label class="prof-label">Gender</label>
                        <select class="prof-input" name="gender">
                            <option value="">-- Select --</option>
                            <option value="Male"   <?= ps($p,'gender','Male')   ?>>Male</option>
                            <option value="Female" <?= ps($p,'gender','Female') ?>>Female</option>
                            <option value="Other"  <?= ps($p,'gender','Other')  ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="prof-label">Date of Birth</label>
                        <input type="date" class="prof-input" name="dob" value="<?= pv($p,'dob') ?>">
                    </div>
                    <div>
                        <!-- LOCKED: Employee ID -->
                        <label class="prof-label">Employee ID <i class="fa fa-lock" style="font-size:9px;color:#cbd5e1;"></i></label>
                        <input class="prof-input" value="<?= $user_id ?>" readonly>
                    </div>

                    <!-- Contact -->
                    <div>
                        <label class="prof-label">Mobile (Primary)</label>
                        <input type="tel" class="prof-input" name="mobile_no_1" value="<?= pv($p,'mobile_no_1') ?>" placeholder="10-digit number">
                    </div>
                    <div>
                        <label class="prof-label">Mobile (Secondary)</label>
                        <input type="tel" class="prof-input" name="mobile_no_2" value="<?= pv($p,'mobile_no_2') ?>">
                    </div>
                    <div>
                        <label class="prof-label">Email Address</label>
                        <input type="email" class="prof-input" name="email" value="<?= pv($p,'email') ?>">
                    </div>

                    <!-- Address -->
                    <div class="prof-col-full">
                        <label class="prof-label">Address</label>
                        <input type="text" class="prof-input" name="address" value="<?= pv($p,'address') ?>">
                    </div>
                    <div>
                        <label class="prof-label">City</label>
                        <input type="text" class="prof-input" name="city" value="<?= pv($p,'city') ?>">
                    </div>
                    <div>
                        <label class="prof-label">State</label>
                        <input type="text" class="prof-input" name="state" value="<?= pv($p,'state') ?>">
                    </div>
                    <div>
                        <label class="prof-label">PIN Code</label>
                        <input type="text" class="prof-input" name="pin" value="<?= pv($p,'pin') ?>">
                    </div>
                    <div>
                        <label class="prof-label">Country</label>
                        <input type="text" class="prof-input" name="country" value="<?= pv($p,'country') ?>">
                    </div>
                </div>
            </div>
            <div class="prof-footer" id="footer-personal" style="<?= $activeTab==='personal'?'':'display:none;' ?>">
                <span style="font-size:12px;color:#94a3b8;"><i class="fa fa-info-circle"></i> Locked fields require HOD approval to change.</span>
                <button type="submit" class="btn-prof-save"><i class="fa fa-floppy-disk"></i> Save Personal Details</button>
            </div>
        </form>

        <!-- ── PROFESSIONAL TAB ── -->
        <form method="POST" id="form-professional">
            <input type="hidden" name="update_profile" value="1">
            <input type="hidden" name="section" value="professional">
            <div class="prof-panel <?= $activeTab==='professional'?'active':'' ?>" id="panel-professional">
                <div class="prof-panel-title"><i class="fa fa-graduation-cap"></i> Professional Information</div>
                <div class="prof-grid">
                    <!-- EDITABLE -->
                    <div>
                        <label class="prof-label">Designation</label>
                        <input type="text" class="prof-input" name="emp_designation" value="<?= pv($p,'emp_designation') ?>" placeholder="e.g. Assistant Professor">
                    </div>
                    <div class="prof-col-2">
                        <label class="prof-label">Area of Specialization</label>
                        <input type="text" class="prof-input" name="area_specialization" value="<?= pv($p,'area_specialization') ?>" placeholder="e.g. Machine Learning, VLSI">
                    </div>
                    <div>
                        <label class="prof-label">Highest Degree</label>
                        <input type="text" class="prof-input" name="highest_degree" value="<?= pv($p,'highest_degree') ?>" placeholder="e.g. Ph.D, M.Tech">
                    </div>
                    <div>
                        <label class="prof-label">Degree University</label>
                        <input type="text" class="prof-input" name="highest_degree_univ" value="<?= pv($p,'highest_degree_univ') ?>">
                    </div>
                    <div>
                        <label class="prof-label">Degree Year</label>
                        <input type="date" class="prof-input" name="highest_degree_date" value="<?= pv($p,'highest_degree_date') ?>">
                    </div>

                    <!-- LOCKED: Department, Date of Joining, PAN -->
                    <div>
                        <label class="prof-label">Department <i class="fa fa-lock" style="font-size:9px;color:#cbd5e1;"></i></label>
                        <input class="prof-input" value="<?= $deptName ?>" readonly>
                        <span class="prof-locked-note"><i class="fa fa-lock"></i> Contact HOD to change</span>
                    </div>
                    <div>
                        <label class="prof-label">Date of Joining <i class="fa fa-lock" style="font-size:9px;color:#cbd5e1;"></i></label>
                        <input class="prof-input" value="<?= pv($p,'date_join') ? date('d M Y', strtotime($p['date_join'])) : '' ?>" readonly>
                    </div>
                    <div>
                        <label class="prof-label">PAN Number <i class="fa fa-lock" style="font-size:9px;color:#cbd5e1;"></i></label>
                        <input class="prof-input" value="<?= pv($p,'pan') ?>" readonly>
                        <span class="prof-locked-note"><i class="fa fa-lock"></i> Contact HOD to change</span>
                    </div>
                </div>
            </div>
            <div class="prof-footer" id="footer-professional" style="<?= $activeTab==='professional'?'':'display:none;' ?>">
                <span style="font-size:12px;color:#94a3b8;"><i class="fa fa-info-circle"></i> Locked fields require HOD approval to change.</span>
                <button type="submit" class="btn-prof-save"><i class="fa fa-floppy-disk"></i> Save Professional Details</button>
            </div>
        </form>

    </div><!-- /prof-panels -->
</div><!-- /prof-wrap -->

<script>
function switchTab(name) {
    document.querySelectorAll('.prof-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.prof-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('[id^="footer-"]').forEach(f => f.style.display = 'none');
    document.querySelector('.prof-tab:nth-child(' + (name==='personal'?1:2) + ')').classList.add('active');
    document.getElementById('panel-' + name).classList.add('active');
    document.getElementById('footer-' + name).style.display = 'flex';
}
</script>