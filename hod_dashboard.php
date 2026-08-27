<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'HOD') {
    header("Location: index.php"); exit;
}

$msg = ''; $msg_type = '';
$user_id = $_SESSION['user_id'];
$page = $_GET['page'] ?? 'faculty';

// ── Create Faculty ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_faculty'])) {
    $un = trim($_POST['user_name']);
    $dp = password_hash($un, PASSWORD_DEFAULT);
    try {
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO `index` (user_name,password,role,change_password,profile_completed) VALUES (?,?,'Employee',0,0)")->execute([$un,$dp]);
        $nid = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO employee_info (emp_id,emp_type,department_id,gender,first_name,last_name,emp_designation,email,mobile_no_1,date_join,draft_saved) VALUES (?,'Employee',?,?,?,?,?,?,?,?,0)")->execute([
            $nid,$_POST['department_id'],$_POST['gender'],$_POST['first_name'],$_POST['last_name'],$_POST['designation'],$_POST['email'],$_POST['mobile'],$_POST['date_join']
        ]);
        $pdo->commit();
        $msg = "Faculty <strong>{$_POST['first_name']} {$_POST['last_name']}</strong> created. ID: <strong>$nid</strong> &nbsp;·&nbsp; Username: <strong>$un</strong> &nbsp;·&nbsp; Default password: <strong>$un</strong>";
        $msg_type = 'success';
    } catch(Exception $e) { $pdo->rollBack(); $msg = "Error: ".$e->getMessage(); $msg_type = 'error'; }
}

// ── Create Student ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_student'])) {
    $un = trim($_POST['s_user_name']);
    $dp = password_hash($un, PASSWORD_DEFAULT);
    $stype = $_POST['s_type'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO `index` (user_name,password,role,change_password,profile_completed) VALUES (?,?,'Student-$stype',0,0)")->execute([$un,$dp]);
        $nid = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO student_info (student_id,student_type,department_id,gender,first_name,last_name,guardian_first_name,email,status,verification_flag) VALUES (?,?,?,?,?,?,?,'','Ongoing',0)")->execute([
            $nid,$stype,$_POST['s_department_id'],$_POST['s_gender'],$_POST['s_first_name'],$_POST['s_last_name'],$_POST['s_guardian']
        ]);
        $pdo->prepare("INSERT INTO semester_registration (student_id,semester,sem_reg_date) VALUES (?,?,CURDATE())")->execute([$nid,(int)$_POST['s_semester']]);
        $pdo->commit();
        $msg = "Student <strong>{$_POST['s_first_name']} {$_POST['s_last_name']}</strong> created. ID: <strong>$nid</strong> &nbsp;·&nbsp; Username: <strong>$un</strong> &nbsp;·&nbsp; Default password: <strong>$un</strong>";
        $msg_type = 'success';
    } catch(Exception $e) { $pdo->rollBack(); $msg = "Error: ".$e->getMessage(); $msg_type = 'error'; }
}

// ── Verify Student ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_student'])) {
    $sid = (int)$_POST['student_id'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE student_info SET verification_flag=1,verification_date=CURDATE(),verified_by=? WHERE student_id=?")->execute([$user_id,$sid]);
        try {
            $pdo->prepare("INSERT INTO verification (student_id,emp_id,verification_type,verification_flag,verification_date,verification_time) VALUES (?,?,'Profile',1,CURDATE(),CURTIME())")->execute([$sid,$user_id]);
        } catch(Exception $e2) { /* verification table may not exist yet */ }
        $pdo->commit();
        $msg = "Student #$sid verified successfully."; $msg_type = 'success';
    } catch(Exception $e) { $pdo->rollBack(); $msg = "Error: ".$e->getMessage(); $msg_type = 'error'; }
}

// ── Verify Publication ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_pub'])) {
    $pid = (int)$_POST['pub_id'];
    $pdo->prepare("UPDATE publication_author SET verification_flag=1 WHERE publication_id=?")->execute([$pid]);
    header("Location: hod_dashboard.php?page=pub-verify&msg=verified"); exit;
}

// ── Assign Job ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_job'])) {
    try {
        $jt   = (int)$_POST['job_type_id'];
        $to   = (int)$_POST['assigned_to'];
        $sem  = $_POST['semester'] ? (int)$_POST['semester'] : null;
        $yr   = $_POST['year']     ? (int)$_POST['year']     : null;
        $sd   = $_POST['start_date'] ?: null;
        $dd   = $_POST['due_date']   ?: null;

        // assigned_by: HOD may not be in employee_info, so use null if FK would fail
        try {
            $chk = $pdo->prepare("SELECT emp_id FROM employee_info WHERE emp_id=?");
            $chk->execute([$user_id]);
            $assigned_by = $chk->fetchColumn() ? $user_id : null;
        } catch(Exception $e) { $assigned_by = null; }

        // Collect student IDs from all three selection methods
        $student_ids = [];

        // Method 1: individual multi-select
        if (!empty($_POST['assigned_for_multi'])) {
            foreach ($_POST['assigned_for_multi'] as $sid) {
                $v = (int)$sid; if ($v > 0) $student_ids[] = $v;
            }
        }

        // Method 2: registration number range  e.g. 2024-CSB-001 to 2024-CSB-030
        if (!empty($_POST['reg_from']) && !empty($_POST['reg_to'])) {
            // Parse prefix and numbers from patterns like 2024-CSB-001
            $from_raw = strtoupper(trim($_POST['reg_from']));
            $to_raw   = strtoupper(trim($_POST['reg_to']));
            // Extract trailing numeric part
            if (preg_match('/^(.+?)(\d+)$/', $from_raw, $mf) && preg_match('/^(.+?)(\d+)$/', $to_raw, $mt)) {
                $prefix  = $mf[1];
                $num_from = (int)$mf[2];
                $num_to   = (int)$mt[2];
                $pad_len  = strlen($mf[2]);
                if ($prefix === $mt[1] && $num_to >= $num_from) {
                    $placeholders_list = [];
                    for ($n = $num_from; $n <= $num_to; $n++) {
                        $placeholders_list[] = $prefix . str_pad($n, $pad_len, '0', STR_PAD_LEFT);
                    }
                    if (!empty($placeholders_list)) {
                        $ph = implode(',', array_fill(0, count($placeholders_list), '?'));
                        $rs = $pdo->prepare("SELECT student_id FROM student_info WHERE registration_no IN ($ph)");
                        $rs->execute($placeholders_list);
                        foreach ($rs->fetchAll(PDO::FETCH_COLUMN) as $sid) $student_ids[] = (int)$sid;
                    }
                }
            }
        }

        // Method 3: semester/year pool (assign to ALL students in that semester+year if no specific students chosen)
        if (empty($student_ids) && $sem) {
            $pool_sql = "SELECT sr.student_id FROM semester_registration sr WHERE sr.semester=?";
            $pool_params = [$sem];
            if ($yr) { $pool_sql .= " AND YEAR(sr.sem_reg_date)=?"; $pool_params[] = $yr; }
            $pool_stmt = $pdo->prepare($pool_sql);
            $pool_stmt->execute($pool_params);
            foreach ($pool_stmt->fetchAll(PDO::FETCH_COLUMN) as $sid) $student_ids[] = (int)$sid;
        }

        $student_ids = array_unique($student_ids);

        $ins = $pdo->prepare("INSERT INTO job_assignment (job_assign_type_id,assigned_by,assigned_to,assigned_for,semester,year,job_assignment_start_date,job_assignment_due_date,job_assignment_status) VALUES (?,?,?,?,?,?,?,?,'Pending')");

        if (empty($student_ids)) {
            // No specific students — create a single assignment with assigned_for = null
            $ins->execute([$jt, $assigned_by, $to, null, $sem, $yr, $sd, $dd]);
            $count = 1;
        } else {
            $pdo->beginTransaction();
            foreach ($student_ids as $sid) {
                $ins->execute([$jt, $assigned_by, $to, $sid, $sem, $yr, $sd, $dd]);
            }
            $pdo->commit();
            $count = count($student_ids);
        }

        $msg = "Job assigned to $count student(s) successfully."; $msg_type = 'success';
    } catch(Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $msg = "Error: ".$e->getMessage(); $msg_type = 'error';
    }
}

// ── Update Job Status ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_job_status'])) {
    $aid = (int)$_POST['assignment_id'];
    $status = $_POST['new_status'];
    $pdo->prepare("UPDATE job_assignment SET job_assignment_status=? WHERE assignment_id=?")->execute([$status,$aid]);
    if ($status === 'Completed') {
        try { $pdo->prepare("INSERT INTO job_completion_log (assignment_id,done_by,job_completion_status,job_completion_date,job_completion_time) VALUES (?,?,'Verified',CURDATE(),CURTIME())")->execute([$aid,$user_id]); } catch(Exception $e2){}
    }
    $msg = "Status updated."; $msg_type = 'success';
}

// ── Fetch all data ─────────────────────────────────────────────
try { $faculty = $pdo->query("SELECT i.user_id,i.user_name,i.change_password,i.profile_completed,e.first_name,e.last_name,e.emp_designation,e.department_id,e.email FROM `index` i LEFT JOIN employee_info e ON i.user_id=e.emp_id WHERE i.role='Employee' ORDER BY i.user_id DESC")->fetchAll(); }
catch(Exception $e) { try { $faculty = $pdo->query("SELECT i.user_id,i.user_name,i.password_changed as change_password,i.profile_completed,e.first_name,e.last_name,e.emp_designation,e.department_id,e.email FROM `index` i LEFT JOIN employee_info e ON i.user_id=e.emp_id WHERE i.role='Employee' ORDER BY i.user_id DESC")->fetchAll(); } catch(Exception $e2){ $faculty=[]; } }

try { $students = $pdo->query("SELECT i.user_id,i.user_name,i.role,i.change_password,s.first_name,s.last_name,s.department_id,s.student_type,s.verification_flag,s.email FROM `index` i LEFT JOIN student_info s ON i.user_id=s.student_id WHERE i.role LIKE 'Student-%' ORDER BY i.user_id DESC")->fetchAll(); }
catch(Exception $e) { $students=[]; }

try { $departments = $pdo->query("SELECT department_id,department_name FROM department ORDER BY department_name")->fetchAll(); }
catch(Exception $e) { $departments=[]; }

try { $job_types = $pdo->query("SELECT job_type_id,job_name FROM job_details ORDER BY job_type_id")->fetchAll(); }
catch(Exception $e) { $job_types=[]; }

try { $all_emp = $pdo->query("SELECT e.emp_id,CONCAT(e.first_name,' ',COALESCE(e.last_name,'')) as name FROM employee_info e ORDER BY e.first_name")->fetchAll(); }
catch(Exception $e) { $all_emp=[]; }

try { $all_stu = $pdo->query("SELECT s.student_id,CONCAT(s.first_name,' ',COALESCE(s.last_name,'')) as name FROM student_info s ORDER BY s.first_name")->fetchAll(); }
catch(Exception $e) { $all_stu=[]; }

try { $jobs = $pdo->query("SELECT ja.*,jd.job_name,CONCAT(ea.first_name,' ',COALESCE(ea.last_name,'')) as assigned_to_name,CONCAT(ef.first_name,' ',COALESCE(ef.last_name,'')) as assigned_for_name FROM job_assignment ja LEFT JOIN job_details jd ON ja.job_assign_type_id=jd.job_type_id LEFT JOIN employee_info ea ON ja.assigned_to=ea.emp_id LEFT JOIN student_info ef ON ja.assigned_for=ef.student_id ORDER BY ja.assignment_id DESC LIMIT 50")->fetchAll(); }
catch(Exception $e) { $jobs=[]; }

// Publications search
$pub_q = trim($_GET['pub_q'] ?? '');
$pub_type = $_GET['pub_type'] ?? '';
$pubs = [];
if (!empty($pub_q) || !empty($pub_type)) {
    try {
        $psql = "SELECT pa.publication_id,pa.publication_type,pa.verification_flag,COALESCE(j.journal_publication_title,c.conference_publication_title) as title,COALESCE(j.journal_publication_date,c.conference_start_date) as pub_date,COALESCE(j.journal_title,c.conference_title) as venue,e.first_name,e.last_name FROM publication_author pa LEFT JOIN journal_publication_details j ON pa.publication_id=j.journal_publication_id LEFT JOIN conference_publication_details c ON pa.publication_id=c.conference_publication_id LEFT JOIN employee_info e ON pa.author_id=e.emp_id WHERE 1=1";
        $pp = [];
        if (!empty($pub_q)) { $psql .= " AND (j.journal_publication_title LIKE ? OR c.conference_publication_title LIKE ? OR j.journal_title LIKE ? OR c.conference_title LIKE ?)"; $lq="%$pub_q%"; $pp=[$lq,$lq,$lq,$lq]; }
        if (!empty($pub_type)) { $psql .= " AND pa.publication_type=?"; $pp[]=$pub_type; }
        $psql .= " ORDER BY pub_date DESC LIMIT 100";
        $s = $pdo->prepare($psql); $s->execute($pp); $pubs = $s->fetchAll();
    } catch(Exception $e) { $pubs=[]; }
}

try { $pending_pubs = $pdo->query("SELECT pa.publication_id,pa.publication_type,pa.verification_flag,COALESCE(j.journal_publication_title,c.conference_publication_title) as title,COALESCE(j.journal_publication_date,c.conference_start_date) as pub_date,e.first_name,e.last_name FROM publication_author pa LEFT JOIN journal_publication_details j ON pa.publication_id=j.journal_publication_id LEFT JOIN conference_publication_details c ON pa.publication_id=c.conference_publication_id LEFT JOIN employee_info e ON pa.author_id=e.emp_id WHERE pa.verification_flag=0 ORDER BY pub_date DESC LIMIT 100")->fetchAll(); }
catch(Exception $e) { $pending_pubs=[]; }

// ── Helper functions ──────────────────────────────────────────
function pill($text, $cls) { return "<span class='pill $cls'>$text</span>"; }
function typePill($type) {
    $map = ['Journal'=>'tp-Journal','Conference'=>'tp-Conference','Book Chapter'=>'tp-Book','News Letter'=>'tp-News','Technical Report'=>'tp-News'];
    $cls = $map[$type] ?? 'tp-Journal';
    return "<span class='tp $cls'>".htmlspecialchars($type)."</span>";
}
function idBadge($id) { return "<span class='id-badge'>#$id</span>"; }

// ── Build page content ────────────────────────────────────────
ob_start();

if ($page === 'faculty') {
    echo "<div class='card'>";
    echo "<div class='card-header'><h2><i class='fa fa-users'></i> Faculty Members</h2><span class='cnt'>".count($faculty)." records</span></div>";
    echo "<table class='data-table'><thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Designation</th><th>Dept</th><th>Email</th><th>Password</th><th>Profile</th></tr></thead><tbody>";
    foreach ($faculty as $f) {
        $name = htmlspecialchars(trim(($f['first_name']??'').' '.($f['last_name']??''))) ?: '—';
        $pwpill = $f['change_password'] ? pill('Changed','s-complete') : pill('Default','s-incomplete');
        $prpill = $f['profile_completed'] ? pill('Complete','s-verified') : pill('Incomplete','s-pending');
        echo "<tr><td>".idBadge($f['user_id'])."</td><td>".htmlspecialchars($f['user_name'])."</td><td>$name</td><td>".htmlspecialchars($f['emp_designation']??'—')."</td><td>".htmlspecialchars($f['department_id']??'—')."</td><td>".htmlspecialchars($f['email']??'—')."</td><td>$pwpill</td><td>$prpill</td></tr>";
    }
    if (empty($faculty)) echo "<tr><td colspan='8' class='empty-state'><i class='fa fa-users'></i><p>No faculty yet.</p></td></tr>";
    echo "</tbody></table></div>";

} elseif ($page === 'students') {
    echo "<div class='card'>";
    echo "<div class='card-header'><h2><i class='fa fa-graduation-cap'></i> Students</h2><span class='cnt'>".count($students)." records</span></div>";
    echo "<table class='data-table'><thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Type</th><th>Dept</th><th>1st Login</th><th>Verified</th><th>Action</th></tr></thead><tbody>";
    foreach ($students as $s) {
        $name = htmlspecialchars(trim(($s['first_name']??'').' '.($s['last_name']??''))) ?: '—';
        $pwpill = $s['change_password'] ? pill('Done','s-complete') : pill('Pending','s-incomplete');
        $vflag = $s['verification_flag'] ?? 0;
        $vpill = $vflag ? pill('Verified','s-verified') : pill('Pending','s-pending');
        $action = !$vflag
            ? "<form method='POST' style='display:inline'><input type='hidden' name='student_id' value='{$s['user_id']}'><button class='btn-sm btn-verify' name='verify_student' onclick=\"return confirm('Verify this student?')\"><i class='fa fa-check'></i> Verify</button></form>"
            : "<span style='color:#94a3b8;font-size:12px;'><i class='fa fa-lock'></i> Done</span>";
        echo "<tr><td>".idBadge($s['user_id'])."</td><td>".htmlspecialchars($s['user_name'])."</td><td>$name</td><td>".htmlspecialchars($s['student_type']??'—')."</td><td>".htmlspecialchars($s['department_id']??'—')."</td><td>$pwpill</td><td>$vpill</td><td>$action</td></tr>";
    }
    if (empty($students)) echo "<tr><td colspan='8' class='empty-state'><i class='fa fa-graduation-cap'></i><p>No students yet.</p></td></tr>";
    echo "</tbody></table></div>";

} elseif ($page === 'add-faculty') {
    $dept_opts = "<option value=''>— Select —</option>";
    foreach ($departments as $d) $dept_opts .= "<option value='".htmlspecialchars($d['department_id'])."'>".htmlspecialchars($d['department_name'])."</option>";
    echo "
    <div class='card'>
        <div class='card-header'><h2><i class='fa fa-user-plus'></i> Create Faculty Account</h2><span class='cnt'>User ID auto-assigned &nbsp;·&nbsp; Default password = username</span></div>
        <form method='POST'>
        <div class='form-grid three'>
            <div><label class='fl'>Username *</label><input type='text' name='user_name' required placeholder='e.g. john.doe'></div>
            <div><label class='fl'>First Name *</label><input type='text' name='first_name' required></div>
            <div><label class='fl'>Last Name</label><input type='text' name='last_name'></div>
            <div><label class='fl'>Email *</label><input type='email' name='email' required></div>
            <div><label class='fl'>Mobile</label><input type='text' name='mobile'></div>
            <div><label class='fl'>Designation</label><input type='text' name='designation' placeholder='e.g. Assistant Professor'></div>
            <div><label class='fl'>Department</label><select name='department_id'>$dept_opts</select></div>
            <div><label class='fl'>Gender</label><select name='gender'><option value='Male'>Male</option><option value='Female'>Female</option><option value='Other'>Other</option></select></div>
            <div><label class='fl'>Date of Joining *</label><input type='date' name='date_join' required></div>
        </div>
        <div class='form-footer'><button class='btn btn-purple' name='create_faculty'><i class='fa fa-user-plus'></i> Create Account</button></div>
        </form>
    </div>";

} elseif ($page === 'add-student') {
    $dept_opts = "<option value=''>— Select —</option>";
    foreach ($departments as $d) $dept_opts .= "<option value='".htmlspecialchars($d['department_id'])."'>".htmlspecialchars($d['department_name'])."</option>";
    $sem_opts = "";
    for ($i=1;$i<=8;$i++) $sem_opts .= "<option value='$i'>Semester $i</option>";
    echo "
    <div class='card'>
        <div class='card-header'><h2><i class='fa fa-user-graduate'></i> Create Student Account</h2><span class='cnt'>User ID auto-assigned &nbsp;·&nbsp; Default password = username</span></div>
        <form method='POST'>
        <div class='form-grid three'>
            <div><label class='fl'>Username *</label><input type='text' name='s_user_name' required></div>
            <div><label class='fl'>First Name *</label><input type='text' name='s_first_name' required></div>
            <div><label class='fl'>Last Name</label><input type='text' name='s_last_name'></div>
            <div><label class='fl'>Guardian Name *</label><input type='text' name='s_guardian' required></div>
            <div><label class='fl'>Programme *</label><select name='s_type' required><option value='UG'>UG</option><option value='PG'>PG</option><option value='PhD'>PhD</option></select></div>
            <div><label class='fl'>Department</label><select name='s_department_id'>$dept_opts</select></div>
            <div><label class='fl'>Gender</label><select name='s_gender'><option value='Male'>Male</option><option value='Female'>Female</option><option value='Other'>Other</option></select></div>
            <div><label class='fl'>Semester *</label><select name='s_semester' required>$sem_opts</select></div>
        </div>
        <div class='form-footer'><button class='btn btn-purple' name='create_student'><i class='fa fa-user-graduate'></i> Create Account</button></div>
        </form>
    </div>";

} elseif ($page === 'pub-search') {
    $type_opts = "<option value=''>All Types</option>";
    foreach (['Journal','Conference','Book Chapter','News Letter','Technical Report'] as $t)
        $type_opts .= "<option value='$t'".($pub_type===$t?' selected':'').">$t</option>";
    echo "
    <div class='card'>
        <div class='card-header'><h2><i class='fa fa-magnifying-glass'></i> Search All Publications</h2></div>
        <form method='GET' class='srch-bar'>
            <input type='hidden' name='page' value='pub-search'>
            <input type='text' name='pub_q' value='".htmlspecialchars($pub_q)."' placeholder='Search title, journal, conference...'>
            <select name='pub_type'>$type_opts</select>
            <button type='submit' class='btn-srch'><i class='fa fa-magnifying-glass'></i> Search</button>
        </form>";
    if (!empty($pub_q) || !empty($pub_type)) {
        echo "<table class='data-table'><thead><tr><th>#</th><th>Title</th><th>Type</th><th>Venue</th><th>Date</th><th>Author</th><th>Status</th></tr></thead><tbody>";
        if (empty($pubs)) {
            echo "<tr><td colspan='7' class='empty-state'><i class='fa fa-folder-open'></i><p>No results.</p></td></tr>";
        } else {
            foreach ($pubs as $pub) {
                $vp = $pub['verification_flag'] ? pill('Verified','s-verified') : pill('Pending','s-pending');
                $dt = $pub['pub_date'] ? date('d M Y', strtotime($pub['pub_date'])) : '—';
                echo "<tr><td>".idBadge($pub['publication_id'])."</td><td style='max-width:260px;font-weight:500;'>".htmlspecialchars($pub['title']??'—')."</td><td>".typePill($pub['publication_type'])."</td><td style='color:#64748b;font-size:12px;'>".htmlspecialchars($pub['venue']??'—')."</td><td style='color:#64748b;white-space:nowrap;'>$dt</td><td>".htmlspecialchars(trim(($pub['first_name']??'').' '.($pub['last_name']??'')))."</td><td>$vp</td></tr>";
            }
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='empty-state' style='padding:50px'><i class='fa fa-magnifying-glass'></i><p>Enter a search term or select a type above.</p></div>";
    }
    echo "</div>";

} elseif ($page === 'pub-verify') {
    if (isset($_GET['msg']) && $_GET['msg'] === 'verified') {
        $msg = 'Publication verified successfully.'; $msg_type = 'success';
    }
    echo "
    <div class='card'>
        <div class='card-header'><h2><i class='fa fa-circle-check'></i> Pending Verification</h2><span class='cnt'>".count($pending_pubs)." unverified</span></div>
        <table class='data-table'><thead><tr><th>#</th><th>Title</th><th>Type</th><th>Date</th><th>Author</th><th>Action</th></tr></thead><tbody>";
    if (empty($pending_pubs)) {
        echo "<tr><td colspan='6' class='empty-state'><i class='fa fa-circle-check'></i><p>All publications verified.</p></td></tr>";
    } else {
        foreach ($pending_pubs as $pub) {
            $dt = $pub['pub_date'] ? date('d M Y', strtotime($pub['pub_date'])) : '—';
            echo "<tr><td>".idBadge($pub['publication_id'])."</td><td style='max-width:260px;font-weight:500;'>".htmlspecialchars($pub['title']??'—')."</td><td>".typePill($pub['publication_type'])."</td><td style='color:#64748b;white-space:nowrap;'>$dt</td><td>".htmlspecialchars(trim(($pub['first_name']??'').' '.($pub['last_name']??'')))."</td>
            <td><form method='POST' style='display:inline'><input type='hidden' name='pub_id' value='{$pub['publication_id']}'><button class='btn-sm btn-green' name='verify_pub' onclick=\"return confirm('Mark as verified?')\"><i class='fa fa-check'></i> Verify</button></form></td></tr>";
        }
    }
    echo "</tbody></table></div>";

} elseif ($page === 'jobs') {
    echo "
    <div class='card'>
        <div class='card-header'>
            <h2><i class='fa fa-list-check'></i> All Job Assignments</h2>
            <a href='hod_dashboard.php?page=assign-job' class='btn btn-purple' style='font-size:13px;padding:8px 16px;'><i class='fa fa-plus'></i> New</a>
        </div>
        <table class='data-table'><thead><tr><th>#</th><th>Job</th><th>Assigned To</th><th>For Student</th><th>Due Date</th><th>Status</th><th>Update</th></tr></thead><tbody>";
    if (empty($jobs)) {
        echo "<tr><td colspan='7' class='empty-state'><i class='fa fa-list-check'></i><p>No assignments yet.</p></td></tr>";
    } else {
        foreach ($jobs as $j) {
            $scls = ['Pending'=>'s-pending','In Progress'=>'s-inprogress','Completed'=>'s-verified'][$j['job_assignment_status']] ?? 's-pending';
            $dt = $j['job_assignment_due_date'] ? date('d M Y', strtotime($j['job_assignment_due_date'])) : '—';
            $status = htmlspecialchars($j['job_assignment_status']);
            $opts = "";
            foreach (['Pending','In Progress','Completed'] as $opt)
                $opts .= "<option".($j['job_assignment_status']===$opt?' selected':'').">$opt</option>";
            echo "<tr><td>".idBadge($j['assignment_id'])."</td><td style='font-weight:500;'>".htmlspecialchars($j['job_name']??'—')."</td><td>".htmlspecialchars($j['assigned_to_name']??'—')."</td><td>".htmlspecialchars($j['assigned_for_name']??'—')."</td><td style='color:#64748b;white-space:nowrap;'>$dt</td><td>".pill($status,$scls)."</td>
            <td><form method='POST' style='display:flex;gap:6px;align-items:center;'><input type='hidden' name='assignment_id' value='{$j['assignment_id']}'><select name='new_status' style='padding:5px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;'>$opts</select><button class='btn-sm btn-purple' name='update_job_status'><i class='fa fa-check'></i></button></form></td></tr>";
        }
    }
    echo "</tbody></table></div>";

} elseif ($page === 'assign-job') {
    $jt_opts = "<option value=''>— Select Job —</option>";
    foreach ($job_types as $jt) $jt_opts .= "<option value='{$jt['job_type_id']}'>".htmlspecialchars($jt['job_name'])."</option>";
    $emp_opts = "<option value=''>— Select Faculty —</option>";
    foreach ($all_emp as $e) $emp_opts .= "<option value='{$e['emp_id']}'>".htmlspecialchars($e['name'])."</option>";
    $sem_opts = "<option value=''>— Any —</option>";
    for ($i=1;$i<=8;$i++) $sem_opts .= "<option value='$i'>Semester $i</option>";

    // Build student JSON for JS filtering by semester + year
    $stu_json = [];
    try {
        $stu_full = $pdo->query("SELECT s.student_id, CONCAT(s.first_name,' ',COALESCE(s.last_name,'')) as name, s.registration_no, sr.semester, YEAR(sr.sem_reg_date) as reg_year FROM student_info s LEFT JOIN semester_registration sr ON s.student_id=sr.student_id ORDER BY sr.semester, s.registration_no")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($stu_full as $s) $stu_json[] = $s;
    } catch(Exception $e) { $stu_json = []; }
    $stu_json_enc = json_encode($stu_json);

    // Dept codes for reg-no hint
    $dept_codes = [];
    foreach ($departments as $d) $dept_codes[] = strtoupper(substr($d['department_id'],0,3)).'B';

    echo "
    <div class='card'>
        <div class='card-header'><h2><i class='fa fa-plus-circle'></i> New Job Assignment</h2></div>
        <form method='POST' id='assignJobForm'>

        <div class='form-grid'>
            <div>
                <label class='fl'>Job Type *</label>
                <select name='job_type_id' required>$jt_opts</select>
            </div>
            <div>
                <label class='fl'>Assign To (Faculty) *</label>
                <select name='assigned_to' required>$emp_opts</select>
            </div>
        </div>

        <hr style='margin:20px 0;border:none;border-top:1.5px solid #f1f5f9;'>
        <p style='font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:0 0 14px;'>Student Pool</p>

        <div class='form-grid'>
            <div>
                <label class='fl'>Semester</label>
                <select name='semester' id='semFilter' onchange='filterStudents()'>$sem_opts</select>
                <span style='font-size:11px;color:#94a3b8;'>Selecting a semester filters the student list below</span>
            </div>
            <div>
                <label class='fl'>Academic Year</label>
                <input type='number' name='year' id='yearFilter' placeholder='e.g. 2024' min='2000' max='2100' oninput='filterStudents()'>
                <span style='font-size:11px;color:#94a3b8;'>Year when student enrolled in that semester</span>
            </div>
        </div>

        <hr style='margin:20px 0;border:none;border-top:1.5px solid #f1f5f9;'>
        <p style='font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin:0 0 14px;'>Select Students
            <span style='font-weight:400;text-transform:none;font-size:12px;color:#94a3b8;'> — use any combination of the methods below</span>
        </p>

        <!-- Tab switcher -->
        <div style='display:flex;gap:0;margin-bottom:18px;border:1.5px solid #e2e8f0;border-radius:8px;overflow:hidden;width:fit-content;'>
            <button type='button' onclick='switchTab(\"pick\")' id='tab-pick'
                style='padding:8px 18px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:#240046;color:white;'>
                <i class='fa fa-list-check'></i> Pick Students
            </button>
            <button type='button' onclick='switchTab(\"range\")' id='tab-range'
                style='padding:8px 18px;font-size:13px;font-weight:600;border:none;cursor:pointer;background:white;color:#64748b;'>
                <i class='fa fa-hashtag'></i> Reg No Range
            </button>
        </div>

        <!-- Method 1: Multi-select -->
        <div id='tab-pick-content'>
            <label class='fl'>Select Students (hold Ctrl / Cmd to pick multiple)</label>
            <select name='assigned_for_multi[]' id='stuMultiSelect' multiple
                style='width:100%;min-height:160px;padding:8px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;'>
            </select>
            <div style='display:flex;gap:10px;margin-top:6px;'>
                <button type='button' onclick='selectAll()' style='font-size:11px;padding:4px 10px;border:1px solid #e2e8f0;border-radius:5px;cursor:pointer;background:white;color:#374151;'><i class='fa fa-check-double'></i> Select All</button>
                <button type='button' onclick='deselectAll()' style='font-size:11px;padding:4px 10px;border:1px solid #e2e8f0;border-radius:5px;cursor:pointer;background:white;color:#374151;'><i class='fa fa-xmark'></i> Deselect All</button>
                <span id='selCount' style='font-size:12px;color:#94a3b8;line-height:26px;'></span>
            </div>
        </div>

        <!-- Method 2: Reg No Range -->
        <div id='tab-range-content' style='display:none;'>
            <div class='form-grid'>
                <div>
                    <label class='fl'>From Registration No</label>
                    <input type='text' name='reg_from' id='reg_from' placeholder='e.g. 2024-CSB-001' style='text-transform:uppercase;'>
                    <span style='font-size:11px;color:#94a3b8;'>Format: YEAR-BRANCHCODE-NUMBER (e.g. 2024-CSB-001, 2024-AMB-001)</span>
                </div>
                <div>
                    <label class='fl'>To Registration No</label>
                    <input type='text' name='reg_to' id='reg_to' placeholder='e.g. 2024-CSB-060' style='text-transform:uppercase;'>
                    <span style='font-size:11px;color:#94a3b8;'>Must have same prefix as From (same year &amp; branch)</span>
                </div>
            </div>
        </div>

        <hr style='margin:20px 0;border:none;border-top:1.5px solid #f1f5f9;'>
        <div class='form-grid'>
            <div><label class='fl'>Start Date</label><input type='date' name='start_date'></div>
            <div><label class='fl'>Due Date</label><input type='date' name='due_date'></div>
        </div>

        <div class='form-footer'>
            <span id='assignSummary' style='font-size:13px;color:#64748b;margin-right:16px;'></span>
            <button class='btn btn-purple' name='assign_job'><i class='fa fa-paper-plane'></i> Assign Job</button>
        </div>
        </form>
    </div>

    <script>
    const ALL_STUDENTS = $stu_json_enc;

    function filterStudents() {
        const sem  = document.getElementById('semFilter').value;
        const yr   = document.getElementById('yearFilter').value;
        const sel  = document.getElementById('stuMultiSelect');
        const prev = Array.from(sel.selectedOptions).map(o => o.value);
        sel.innerHTML = '';
        let filtered = ALL_STUDENTS;
        if (sem)  filtered = filtered.filter(s => String(s.semester) === sem);
        if (yr)   filtered = filtered.filter(s => String(s.reg_year) === yr);
        if (!sem && !yr) filtered = ALL_STUDENTS;
        filtered.forEach(s => {
            const o = document.createElement('option');
            o.value = s.student_id;
            const reg = s.registration_no ? ' [' + s.registration_no + ']' : '';
            const semLabel = s.semester ? ' · Sem ' + s.semester : '';
            o.textContent = s.name + reg + semLabel;
            if (prev.includes(String(s.student_id))) o.selected = true;
            sel.appendChild(o);
        });
        updateCount();
        updateSummary();
    }

    function selectAll()   { Array.from(document.getElementById('stuMultiSelect').options).forEach(o => o.selected = true); updateCount(); updateSummary(); }
    function deselectAll() { Array.from(document.getElementById('stuMultiSelect').options).forEach(o => o.selected = false); updateCount(); updateSummary(); }

    function updateCount() {
        const n = document.getElementById('stuMultiSelect').selectedOptions.length;
        document.getElementById('selCount').textContent = n > 0 ? n + ' selected' : '';
    }

    function switchTab(tab) {
        document.getElementById('tab-pick-content').style.display  = tab === 'pick'  ? '' : 'none';
        document.getElementById('tab-range-content').style.display = tab === 'range' ? '' : 'none';
        document.getElementById('tab-pick').style.background  = tab === 'pick'  ? '#240046' : 'white';
        document.getElementById('tab-pick').style.color       = tab === 'pick'  ? 'white'   : '#64748b';
        document.getElementById('tab-range').style.background = tab === 'range' ? '#240046' : 'white';
        document.getElementById('tab-range').style.color      = tab === 'range' ? 'white'   : '#64748b';
        updateSummary();
    }

    function updateSummary() {
        const pickTab  = document.getElementById('tab-pick-content').style.display !== 'none';
        const rangeTab = !pickTab;
        let summary = '';
        if (pickTab) {
            const n = document.getElementById('stuMultiSelect').selectedOptions.length;
            if (n > 0) summary = n + ' student(s) selected manually';
            else {
                const sem = document.getElementById('semFilter').value;
                if (sem) summary = 'Will assign to all students in selected semester';
                else summary = 'No students selected — will create 1 unassigned job';
            }
        } else {
            const f = document.getElementById('reg_from').value;
            const t = document.getElementById('reg_to').value;
            if (f && t) summary = 'Will assign by registration range: ' + f.toUpperCase() + ' → ' + t.toUpperCase();
            else summary = 'Enter a registration number range';
        }
        document.getElementById('assignSummary').textContent = summary;
    }

    document.getElementById('stuMultiSelect').addEventListener('change', () => { updateCount(); updateSummary(); });
    document.getElementById('reg_from').addEventListener('input', updateSummary);
    document.getElementById('reg_to').addEventListener('input', updateSummary);

    // Init
    filterStudents();
    </script>";
}

$pageContent = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HOD Dashboard — IIEST</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --purple:#240046; --hover:#3c096c; --bg:#f8f9fa; --topbar-h:62px; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; background:var(--bg); display:flex; flex-direction:column; height:100vh; overflow:hidden; }
        .topbar { height:var(--topbar-h); background:var(--purple); color:white; display:flex; align-items:center; justify-content:space-between; padding:0 28px; flex-shrink:0; z-index:100; box-shadow:0 2px 10px rgba(0,0,0,0.25); }
        .topbar-brand { font-size:16px; font-weight:700; display:flex; align-items:center; gap:10px; }
        .topbar-brand span { opacity:0.5; font-weight:300; font-size:13px; }
        .avatar-wrap { position:relative; }
        .avatar-btn { width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,0.2); border:2px solid rgba(255,255,255,0.4); color:white; font-weight:700; font-size:14px; cursor:pointer; display:flex; align-items:center; justify-content:center; }
        .dropdown { position:absolute; top:calc(100% + 10px); right:0; background:white; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.15); min-width:180px; overflow:hidden; display:none; z-index:200; }
        .dropdown.open { display:block; }
        .dropdown-header { padding:14px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
        .dropdown-header .name { font-weight:700; color:#1e293b; font-size:13px; }
        .dropdown-header .role { font-size:11px; color:#94a3b8; }
        .dropdown a { display:flex; align-items:center; gap:10px; padding:12px 18px; color:#334155; text-decoration:none; font-size:13px; }
        .dropdown a:hover { background:#f1f5f9; }
        .dropdown a.danger { color:#dc2626; }
        .body-wrap { display:flex; flex:1; overflow:hidden; }
        .sidebar { width:260px; background:var(--purple); color:white; display:flex; flex-direction:column; flex-shrink:0; overflow-y:auto; }
        .sidebar-section { padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.08); }
        .sidebar-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:rgba(255,255,255,0.35); padding:8px 22px 4px; }
        .sidebar a { color:rgba(255,255,255,0.75); text-decoration:none; padding:11px 22px; display:flex; align-items:center; gap:12px; font-size:14px; transition:all 0.15s; }
        .sidebar a:hover { background:rgba(255,255,255,0.08); color:white; }
        .sidebar a.active { background:rgba(255,255,255,0.15); color:white; border-left:3px solid white; }
        .sidebar a i { width:16px; text-align:center; }
        .main { flex:1; overflow-y:auto; padding:32px 36px; }
        .alert { padding:14px 18px; border-radius:8px; margin-bottom:20px; font-size:14px; display:flex; align-items:center; gap:10px; }
        .alert-success { background:#d1fae5; color:#065f46; border:1px solid #34d399; }
        .alert-error   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
        .card { background:white; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,0.07); overflow:hidden; margin-bottom:22px; }
        .card-header { padding:18px 26px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; }
        .card-header h2 { margin:0; font-size:16px; color:var(--purple); display:flex; align-items:center; gap:8px; }
        .card-header .cnt { font-size:13px; color:#94a3b8; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; padding:22px 26px; }
        .form-grid.three { grid-template-columns:1fr 1fr 1fr; }
        .fl { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:5px; }
        .form-grid input,.form-grid select { width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; color:#1e293b; }
        .form-grid input:focus,.form-grid select:focus { outline:none; border-color:var(--purple); }
        .form-footer { padding:14px 26px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; }
        .btn { padding:10px 24px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; border:none; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
        .btn-purple { background:var(--purple); color:white; }
        .btn-purple:hover { background:var(--hover); }
        .btn-sm { padding:5px 12px; font-size:12px; border-radius:6px; border:none; cursor:pointer; font-weight:600; }
        .btn-verify,.btn-green { background:#16a34a; color:white; }
        .data-table { width:100%; border-collapse:collapse; }
        .data-table thead tr { background:#f8fafc; }
        .data-table thead th { padding:10px 18px; text-align:left; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; border-bottom:2px solid #f1f5f9; }
        .data-table tbody tr { border-bottom:1px solid #f8f9fa; transition:background 0.15s; }
        .data-table tbody tr:hover { background:#faf5ff; }
        .data-table tbody td { padding:12px 18px; font-size:13px; color:#334155; vertical-align:middle; }
        .id-badge { background:#ede9fe; color:#5b21b6; font-weight:700; font-size:11px; padding:3px 9px; border-radius:6px; font-family:monospace; }
        .pill { font-size:11px; font-weight:700; padding:3px 9px; border-radius:20px; }
        .s-verified   { background:#dcfce7; color:#166534; }
        .s-pending    { background:#fff7ed; color:#9a3412; }
        .s-complete   { background:#e0f2fe; color:#0369a1; }
        .s-incomplete { background:#fef9c3; color:#854d0e; }
        .s-inprogress { background:#dbeafe; color:#1d4ed8; }
        .tp { font-size:11px; font-weight:700; padding:3px 9px; border-radius:20px; }
        .tp-Journal    { background:#ede9fe; color:#5b21b6; }
        .tp-Conference { background:#dbeafe; color:#1d4ed8; }
        .tp-Book       { background:#dcfce7; color:#166534; }
        .tp-News       { background:#fef9c3; color:#854d0e; }
        .srch-bar { padding:16px 26px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; }
        .srch-bar input,.srch-bar select { padding:9px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; }
        .srch-bar input { flex:1; min-width:220px; }
        .btn-srch { background:var(--purple); color:white; border:none; padding:9px 20px; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; }
        .empty-state { text-align:center; padding:50px 20px; color:#94a3b8; }
        .empty-state i { font-size:32px; display:block; margin-bottom:10px; opacity:0.35; }
    </style>
</head>
<body>
<div class="topbar">
    <div class="topbar-brand">
        <i class="fa fa-shield-halved"></i>
        HOD Dashboard
        <span>| IIEST Shibpur</span>
    </div>
    <div class="avatar-wrap">
        <button class="avatar-btn" onclick="document.getElementById('hodDrop').classList.toggle('open')">HOD</button>
        <div class="dropdown" id="hodDrop">
            <div class="dropdown-header">
                <div class="name">HOD Admin</div>
                <div class="role">ID #<?= htmlspecialchars($user_id) ?></div>
            </div>
            <a href="logout.php" class="danger"><i class="fa fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>
</div>
<div class="body-wrap">
<nav class="sidebar">
    <div class="sidebar-section">
        <div class="sidebar-label">User Management</div>
        <a href="hod_dashboard.php?page=faculty"     class="<?= $page==='faculty'    ?'active':'' ?>"><i class="fa fa-chalkboard-user"></i> Faculty</a>
        <a href="hod_dashboard.php?page=students"    class="<?= $page==='students'   ?'active':'' ?>"><i class="fa fa-graduation-cap"></i> Students</a>
        <a href="hod_dashboard.php?page=add-faculty" class="<?= $page==='add-faculty'?'active':'' ?>"><i class="fa fa-user-plus"></i> Add Faculty</a>
        <a href="hod_dashboard.php?page=add-student" class="<?= $page==='add-student'?'active':'' ?>"><i class="fa fa-user-graduate"></i> Add Student</a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-label">Publications</div>
        <a href="hod_dashboard.php?page=pub-search"  class="<?= $page==='pub-search' ?'active':'' ?>"><i class="fa fa-magnifying-glass"></i> Search All</a>
        <a href="hod_dashboard.php?page=pub-verify"  class="<?= $page==='pub-verify' ?'active':'' ?>"><i class="fa fa-circle-check"></i> Pending Verification
            <?php if (!empty($pending_pubs)): ?>
            <span style="margin-left:auto;background:#ef4444;color:white;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;"><?= count($pending_pubs) ?></span>
            <?php endif; ?>
        </a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-label">Job Assignment</div>
        <a href="hod_dashboard.php?page=jobs"        class="<?= $page==='jobs'       ?'active':'' ?>"><i class="fa fa-list-check"></i> All Assignments</a>
        <a href="hod_dashboard.php?page=assign-job"  class="<?= $page==='assign-job' ?'active':'' ?>"><i class="fa fa-plus-circle"></i> New Assignment</a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-label">Session</div>
        <a href="logout.php"><i class="fa fa-right-from-bracket"></i> Logout</a>
    </div>
</nav>
<div class="main">
    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?>">
        <i class="fa fa-<?= $msg_type==='success'?'check-circle':'circle-exclamation' ?>"></i>
        <?= $msg ?>
    </div>
    <?php endif; ?>
    <?= $pageContent ?>
</div>
</div>
<script>
document.addEventListener('click', function(e) {
    if (!e.target.closest('.avatar-wrap')) {
        var d = document.getElementById('hodDrop');
        if (d) d.classList.remove('open');
    }
});
</script>
</body>
</html>