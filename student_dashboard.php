<?php
session_start();
if (!isset($_SESSION['user_id']) || !str_starts_with($_SESSION['role'] ?? '', 'Student-')) {
    header("Location: index.php"); exit;
}
require_once __DIR__ . '/config/db.php';

$stmt = $pdo->prepare("
    SELECT s.*, d.department_name
    FROM student_info s
    LEFT JOIN department d ON s.department_id = d.department_id
    WHERE s.student_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    die("<div style='padding:30px;font-family:sans-serif;color:#991b1b;background:#fee2e2;border-radius:8px;margin:40px auto;max-width:500px;'>
        <b>Setup Error:</b> No student_info record for user ID <code>" . htmlspecialchars($_SESSION['user_id']) . "</code>. Contact the department office.
    </div>");
}

$fullName   = strtoupper(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?: 'Student';
$initials   = strtoupper(substr($user['first_name'] ?? 'S', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
$deptName   = $user['department_name'] ?? ($user['department_id'] ?? 'N/A');
$regNo      = $user['registration_no'] ?? '';
$isVerified = (bool)($user['verification_flag'] ?? 0);
$page       = $_GET['page'] ?? 'profile';

$validPages = ['profile', 'registration', 'marksheet', 'publication'];
if (!in_array($page, $validPages)) $page = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Portal — IIEST</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root { --purple:#240046; --purple2:#3c096c; --bg:#f8f9fa; --topbar-h:62px; }
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'Segoe UI',sans-serif; background:var(--bg); display:flex; flex-direction:column; height:100vh; overflow:hidden; }

/* Topbar */
.topbar { height:var(--topbar-h); background:var(--purple); color:white; display:flex; align-items:center; justify-content:space-between; padding:0 28px; flex-shrink:0; box-shadow:0 2px 10px rgba(0,0,0,.25); }
.topbar-brand { font-size:16px; font-weight:700; display:flex; align-items:center; gap:10px; }
.topbar-brand span { opacity:.5; font-weight:300; font-size:13px; }

/* Avatar */
.avatar-wrap { position:relative; }
.avatar-btn { width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,.2); border:2px solid rgba(255,255,255,.4); color:white; font-weight:700; font-size:14px; cursor:pointer; display:flex; align-items:center; justify-content:center; }
.dropdown { position:absolute; top:calc(100% + 10px); right:0; background:white; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,.15); min-width:230px; overflow:hidden; display:none; z-index:200; }
.dropdown.open { display:block; }
.dropdown-header { padding:16px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
.dropdown-header .name { font-weight:700; color:#1e293b; font-size:14px; }
.dropdown-header .sub { font-size:12px; color:#94a3b8; margin-top:3px; }
.dropdown a { display:flex; align-items:center; gap:10px; padding:13px 18px; color:#334155; text-decoration:none; font-size:14px; }
.dropdown a:hover { background:#f1f5f9; }
.dropdown a.danger { color:#dc2626; }
.dropdown-divider { height:1px; background:#e2e8f0; }

/* Layout */
.body-wrap { display:flex; flex:1; overflow:hidden; }
.sidebar { width:260px; background:var(--purple); color:white; display:flex; flex-direction:column; flex-shrink:0; overflow-y:auto; }
.sidebar-section { padding:10px 0; border-bottom:1px solid rgba(255,255,255,.08); }
.sidebar-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:rgba(255,255,255,.35); padding:8px 22px 4px; }
.sidebar a { color:rgba(255,255,255,.75); text-decoration:none; padding:11px 22px; display:flex; align-items:center; gap:12px; font-size:14px; transition:all .15s; }
.sidebar a:hover { background:rgba(255,255,255,.08); color:white; }
.sidebar a.active { background:rgba(255,255,255,.15); color:white; border-left:3px solid white; padding-left:19px; }
.sidebar a i { width:16px; text-align:center; }
.sidebar a.locked { opacity:.4; pointer-events:none; cursor:not-allowed; }
.lock-icon { margin-left:auto; font-size:11px; opacity:.6; }
.badge { margin-left:auto; background:#f59e0b; color:white; font-size:10px; padding:2px 7px; border-radius:10px; font-weight:700; }

/* Main content */
.main { flex:1; overflow-y:auto; padding:32px 38px; }
.alert { padding:14px 18px; border-radius:8px; margin-bottom:20px; font-size:14px; display:flex; align-items:center; gap:10px; }
.alert-success { background:#d1fae5; color:#065f46; border:1px solid #34d399; }
.alert-warn { background:#fef9c3; color:#854d0e; border:1px solid #fde047; }
</style>
</head>
<body>

<div class="topbar">
    <div class="topbar-brand">
        <i class="fa fa-graduation-cap"></i> Student Portal
        <span>| IIEST Shibpur</span>
    </div>
    <div class="avatar-wrap">
        <button class="avatar-btn" onclick="this.closest('.avatar-wrap').querySelector('.dropdown').classList.toggle('open')">
            <?= htmlspecialchars($initials) ?>
        </button>
        <div class="dropdown">
            <div class="dropdown-header">
                <div class="name"><?= htmlspecialchars($fullName) ?></div>
                <div class="sub">
                    <?= htmlspecialchars($deptName) ?>
                    <?php if ($regNo): ?>&nbsp;·&nbsp;<code style="font-size:11px;"><?= htmlspecialchars($regNo) ?></code><?php endif; ?>
                </div>
                <div class="sub" style="margin-top:3px;">
                    <?= $isVerified
                        ? '<span style="color:#10b981;font-weight:700;"><i class="fa fa-circle-check"></i> Verified</span>'
                        : '<span style="color:#f59e0b;font-weight:700;"><i class="fa fa-clock"></i> Pending Verification</span>'
                    ?>
                </div>
            </div>
            <a href="student_dashboard.php?page=profile"><i class="fa fa-user"></i> My Profile</a>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="danger"><i class="fa fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>
</div>

<div class="body-wrap">
    <nav class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-label">Account</div>
            <a href="student_dashboard.php?page=profile" class="<?= $page === 'profile' ? 'active' : '' ?>">
                <i class="fa fa-id-card"></i> My Profile
                <?php if (!$isVerified): ?><span class="badge">Pending</span><?php endif; ?>
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-label">Academics</div>
            <a href="<?= $isVerified ? 'student_dashboard.php?page=registration' : '#' ?>"
               class="<?= $page === 'registration' ? 'active' : (!$isVerified ? 'locked' : '') ?>">
                <i class="fa fa-file-signature"></i> Semester Registration
                <?php if (!$isVerified): ?><i class="fa fa-lock lock-icon"></i><?php endif; ?>
            </a>
            <a href="<?= $isVerified ? 'student_dashboard.php?page=marksheet' : '#' ?>"
               class="<?= $page === 'marksheet' ? 'active' : (!$isVerified ? 'locked' : '') ?>">
                <i class="fa fa-table-list"></i> Semester Marksheet
                <?php if (!$isVerified): ?><i class="fa fa-lock lock-icon"></i><?php endif; ?>
            </a>
            <a href="<?= $isVerified ? 'student_dashboard.php?page=publication' : '#' ?>"
               class="<?= $page === 'publication' ? 'active' : (!$isVerified ? 'locked' : '') ?>">
                <i class="fa fa-file-export"></i> Upload Publication
                <?php if (!$isVerified): ?><i class="fa fa-lock lock-icon"></i><?php endif; ?>
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-label">Session</div>
            <a href="logout.php"><i class="fa fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>

    <div class="main">
        <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            <div class="alert alert-success" id="status-msg">
                <i class="fa fa-check-circle"></i> Profile saved and submitted for verification!
            </div>
            <script>setTimeout(() => { let m = document.getElementById('status-msg'); if (m) m.style.display='none'; }, 5000);</script>
        <?php endif; ?>

        <?php if (in_array($page, ['registration', 'marksheet', 'publication']) && !$isVerified): ?>
            <div class="alert alert-warn">
                <i class="fa fa-lock"></i>&nbsp;
                <strong>Profile not yet verified.</strong> Academic features unlock after department verification.
            </div>

        <?php elseif ($page === 'registration'): ?>
            <?php include __DIR__ . '/modules/student_registration.php'; ?>

        <?php elseif ($page === 'marksheet'): ?>
            <?php include __DIR__ . '/modules/student_marksheet.php'; ?>

        <?php elseif ($page === 'publication'): ?>
            <?php include __DIR__ . '/modules/student_publication.php'; ?>

        <?php else: ?>
            <?php include __DIR__ . '/modules/student_profile.php'; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('click', function(e) {
    if (!e.target.closest('.avatar-wrap'))
        document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
});
</script>
</body>
</html>