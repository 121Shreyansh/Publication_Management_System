<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
if (!($_SESSION['change_password'] ?? 0)) { header("Location: change_password.php"); exit; }
if (!($_SESSION['profile_completed'] ?? 0)) { header("Location: complete_profile.php"); exit; }

require 'config/db.php';

// Fetch User Info
$stmt = $pdo->prepare("SELECT e.* FROM employee_info e WHERE e.emp_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$fullName   = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Faculty';
$designation = $user['emp_designation'] ?? 'Faculty';
$initials   = strtoupper(substr($user['first_name'] ?? 'F', 0, 1) . substr($user['last_name'] ?? '', 0, 1));

$page = $_GET['page'] ?? 'add';

// Pending co-author request count (for badge)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM co_author_requests WHERE requested_id = ? AND status = 'Pending'");
$stmt->execute([$_SESSION['user_id']]);
$pendingCount = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Publication Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --purple: #240046; --hover: #3c096c; --bg: #f8f9fa; --topbar-h: 62px; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); display: flex; flex-direction: column; height: 100vh; overflow: hidden; }

        /* ── TOP BAR ── */
        .topbar {
            height: var(--topbar-h);
            background: var(--purple);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            flex-shrink: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.25);
        }
        .topbar-brand { font-size: 16px; font-weight: 700; letter-spacing: 0.3px; display: flex; align-items: center; gap: 10px; }
        .topbar-brand span { opacity: 0.5; font-weight: 300; font-size: 13px; }

        .topbar-right { display: flex; align-items: center; gap: 18px; }

        /* Notification bell */
        .notif-btn { position: relative; background: none; border: none; color: white; font-size: 18px; cursor: pointer; padding: 6px; opacity: 0.85; transition: opacity 0.2s; }
        .notif-btn:hover { opacity: 1; }
        .notif-badge { position: absolute; top: 0; right: 0; background: #ef4444; color: white; font-size: 10px; font-weight: 700; border-radius: 50%; width: 17px; height: 17px; display: flex; align-items: center; justify-content: center; }

        /* Avatar + dropdown */
        .avatar-wrap { position: relative; }
        .avatar-btn { width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); color: white; font-weight: 700; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
        .avatar-btn:hover { background: rgba(255,255,255,0.3); }

        .dropdown { position: absolute; top: calc(100% + 10px); right: 0; background: white; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.15); min-width: 220px; overflow: hidden; display: none; z-index: 200; }
        .dropdown.open { display: block; }
        .dropdown-header { padding: 16px 18px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .dropdown-header .name { font-weight: 700; color: #1e293b; font-size: 14px; }
        .dropdown-header .role { font-size: 12px; color: #94a3b8; margin-top: 2px; }
        .dropdown a { display: flex; align-items: center; gap: 10px; padding: 13px 18px; color: #334155; text-decoration: none; font-size: 14px; transition: background 0.15s; }
        .dropdown a:hover { background: #f1f5f9; }
        .dropdown a.danger { color: #dc2626; }
        .dropdown a.danger:hover { background: #fef2f2; }
        .dropdown-divider { height: 1px; background: #e2e8f0; }

        /* ── BODY LAYOUT ── */
        .body-wrap { display: flex; flex: 1; overflow: hidden; }

        /* ── SIDEBAR ── */
        .sidebar { width: 260px; background: var(--purple); color: white; display: flex; flex-direction: column; flex-shrink: 0; overflow-y: auto; }
        .sidebar-section { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.35); padding: 8px 22px 4px; }
        .sidebar a { color: rgba(255,255,255,0.75); text-decoration: none; padding: 11px 22px; display: flex; align-items: center; gap: 12px; font-size: 14px; transition: all 0.15s; }
        .sidebar a:hover { background: rgba(255,255,255,0.08); color: white; }
        .sidebar a.active { background: rgba(255,255,255,0.15); color: white; border-left: 3px solid white; }
        .sidebar a i { width: 16px; text-align: center; }

        /* ── MAIN ── */
        .main { flex: 1; overflow-y: auto; padding: 35px 40px; }

        /* Autocomplete */
        .autocomplete-dropdown { position: absolute; background: white; border: 1px solid #ddd; width: 100%; z-index: 100; border-radius: 8px; display: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .autocomplete-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
        .autocomplete-item:hover { background: #f4f0fa; }

        /* Status messages */
        .alert-success { background:#d1fae5; color:#065f46; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #34d399; display:flex; align-items:center; gap:10px; }
        .alert-error { background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #f87171; }
    </style>
</head>
<body>

<!-- ── TOP BAR ── -->
<div class="topbar">
    <div class="topbar-brand">
        <i class="fa fa-book-open"></i>
        Publication Management System
        <span>| IIEST Dept of CST</span>
    </div>

    <div class="topbar-right">
        <!-- Notification Bell -->
        <a href="dashboard.php?page=profile" style="color:white; text-decoration:none;">
            <button class="notif-btn" title="Pending Assignments">
                <i class="fa fa-bell"></i>
                <?php if ($pendingCount > 0): ?>
                    <span class="notif-badge"><?= $pendingCount ?></span>
                <?php endif; ?>
            </button>
        </a>

        <!-- Avatar Dropdown -->
        <div class="avatar-wrap">
            <button class="avatar-btn" onclick="toggleDropdown()" title="My Account">
                <?= htmlspecialchars($initials) ?>
            </button>
            <div class="dropdown" id="avatarDropdown">
                <div class="dropdown-header">
                    <div class="name"><?= htmlspecialchars($fullName) ?></div>
                    <div class="role"><?= htmlspecialchars($designation) ?> &nbsp;·&nbsp; <?= htmlspecialchars($_SESSION['user_id']) ?></div>
                </div>
                <a href="dashboard.php?page=profile"><i class="fa fa-user"></i> My Profile</a>
                <a href="dashboard.php?page=profile#assignments"><i class="fa fa-list-check"></i> Pending Assignments
                    <?php if ($pendingCount > 0): ?>
                        <span style="margin-left:auto; background:#ef4444; color:white; font-size:11px; font-weight:700; padding:2px 7px; border-radius:10px;"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="danger"><i class="fa fa-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- ── BODY ── -->
<div class="body-wrap">

    <!-- ── SIDEBAR ── -->
    <nav class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-label">Publications</div>
            <a href="dashboard.php?page=add"    class="<?= $page=='add'    ?'active':'' ?>"><i class="fa fa-plus"></i> Add New</a>
            <a href="dashboard.php?page=view"   class="<?= $page=='view'   ?'active':'' ?>"><i class="fa fa-eye"></i> View Mine</a>
            <a href="dashboard.php?page=modify" class="<?= $page=='modify' ?'active':'' ?>"><i class="fa fa-pen"></i> Modify</a>
            <a href="dashboard.php?page=delete" class="<?= $page=='delete' ?'active':'' ?>"><i class="fa fa-trash"></i> Delete</a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-label">Tools</div>
            <a href="dashboard.php?page=search" class="<?= $page=='search' ?'active':'' ?>"><i class="fa fa-magnifying-glass"></i> Search All</a>
            <a href="dashboard.php?page=export" class="<?= $page=='export' ?'active':'' ?>"><i class="fa fa-file-export"></i> Bulk Export</a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-label">Account</div>
            <a href="dashboard.php?page=profile" class="<?= $page=='profile' ?'active':'' ?>">
                <i class="fa fa-user"></i> My Profile
                <?php if ($pendingCount > 0): ?>
                    <span style="margin-left:auto; background:#ef4444; color:white; font-size:10px; font-weight:700; padding:2px 7px; border-radius:10px;"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
            <a href="logout.php"><i class="fa fa-right-from-bracket"></i> Logout</a>
        </div>
    </nav>

    <!-- ── MAIN CONTENT ── -->
    <div class="main">
        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert-success" id="status-msg">
                <i class="fa fa-check-circle"></i> Publication added successfully!
            </div>
            <script>setTimeout(() => { document.getElementById('status-msg').style.display='none'; }, 4000);</script>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
            <div class="alert-error">
                <i class="fa fa-exclamation-triangle"></i> Error: <?= htmlspecialchars($_GET['msg'] ?? '') ?>
            </div>
        <?php endif; ?>

        <?php
        if ($page === 'profile') {
            include 'modules/pub_profile.php';
        } else {
            include "modules/pub_$page.php";
        }
        ?>
    </div>
</div>

<script>
function toggleDropdown() {
    document.getElementById('avatarDropdown').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    const wrap = document.querySelector('.avatar-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('avatarDropdown').classList.remove('open');
    }
});

// Autocomplete
document.addEventListener('input', function(e) {
    if (e.target && e.target.classList.contains('auto-search')) {
        const resultsBox = e.target.nextElementSibling;
        const val = e.target.value;
        if (val.length < 2) { resultsBox.style.display = 'none'; return; }
        fetch(`ajax_autocomplete.php?term=${encodeURIComponent(val)}`)
            .then(res => res.json())
            .then(data => {
                resultsBox.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(item => {
                        let div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerHTML = `<i class="fa fa-search" style="opacity:0.3;margin-right:8px;"></i>${item}`;
                        div.onclick = () => { e.target.value = item; resultsBox.style.display='none'; e.target.closest('form').submit(); };
                        resultsBox.appendChild(div);
                    });
                    resultsBox.style.display = 'block';
                } else { resultsBox.style.display = 'none'; }
            });
    }
});
document.addEventListener('click', function(e) {
    if (!e.target.classList.contains('auto-search'))
        document.querySelectorAll('.autocomplete-dropdown').forEach(d => d.style.display='none');
});
</script>
</body>
</html>