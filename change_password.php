<?php
// change_password.php — Mandatory first-login password change
session_start();
require_once 'config/db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
// If already changed, skip ahead to correct next step
if ($_SESSION['change_password'] == 1) {
    if (str_starts_with($_SESSION['role'] ?? '', 'Student-')) {
        header("Location: student_complete_profile.php");
    } else {
        header("Location: complete_profile.php");
    }
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new  = $_POST['new_password'];
    $conf = $_POST['confirm_password'];

    if (strlen($new) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($new !== $conf) {
        $error = "Passwords do not match.";
    } elseif (password_verify($new, '') || $new === $user_id) {
        // Prevent using employee ID as new password
        $error = "You cannot use your Employee ID as your password.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE `index` SET password = ?, change_password = 1,
            last_changed_password_date = CURDATE(),
            last_changed_password_time = CURTIME()
            WHERE user_id = ?");
        $stmt->execute([$hashed, $user_id]);
        $_SESSION['change_password'] = 1;
        if (str_starts_with($_SESSION['role'] ?? '', 'Student-')) {
            header("Location: student_complete_profile.php");
        } else {
            header("Location: complete_profile.php");
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
               background: linear-gradient(135deg, #240046, #3c096c); font-family:'Segoe UI',sans-serif; }
        .card { background:white; border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,0.25); width:420px; overflow:hidden; }
        .card-header { background:#240046; color:white; padding:28px 30px; text-align:center; }
        .card-header h2 { margin:0; font-size:20px; }
        .card-header p { margin:8px 0 0; opacity:0.75; font-size:13px; }
        .card-body { padding:30px; }
        .step-info { background:#f0f4ff; border:1px solid #c7d2fe; border-radius:10px; padding:14px; margin-bottom:22px; font-size:13px; color:#3730a3; }
        .step-info span { font-weight:700; }
        label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:5px; }
        .input-wrap { position:relative; margin-bottom:16px; }
        input[type=password] { width:100%; padding:11px 42px 11px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box; }
        input[type=password]:focus { outline:none; border-color:#240046; }
        .eye-btn { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#94a3b8; font-size:15px; }
        .btn { width:100%; padding:13px; background:#240046; color:white; border:none; border-radius:8px; font-size:15px; font-weight:600; cursor:pointer; margin-top:5px; }
        .btn:hover { background:#3c096c; }
        .error { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; border-radius:8px; padding:12px; font-size:13px; margin-bottom:15px; }
        .rules { font-size:12px; color:#94a3b8; margin-top:15px; line-height:1.8; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <h2><i class="fa fa-lock"></i> &nbsp;Secure Your Account</h2>
        <p>Step 1 of 2 — Change your default password</p>
    </div>
    <div class="card-body">
        <div class="step-info">
            <i class="fa fa-circle-info"></i>
            &nbsp;You are logged in as <span><?= htmlspecialchars($user_id) ?></span>.
            Your default password was your Employee ID. Please set a new secure password to continue.
        </div>

        <?php if ($error): ?>
        <div class="error"><i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>New Password</label>
            <div class="input-wrap">
                <input type="password" name="new_password" id="p1" required placeholder="Min. 8 characters">
                <button type="button" class="eye-btn" onclick="toggle('p1',this)"><i class="fa fa-eye"></i></button>
            </div>

            <label>Confirm New Password</label>
            <div class="input-wrap">
                <input type="password" name="confirm_password" id="p2" required placeholder="Re-enter password">
                <button type="button" class="eye-btn" onclick="toggle('p2',this)"><i class="fa fa-eye"></i></button>
            </div>

            <button type="submit" class="btn"><i class="fa fa-arrow-right"></i> &nbsp;Set Password & Continue</button>
        </form>

        <div class="rules">
            <i class="fa fa-shield"></i> Password rules:<br>
            • At least 8 characters<br>
            • Cannot be your Employee ID<br>
            • Both fields must match
        </div>
    </div>
</div>

<script>
function toggle(id, btn) {
    const inp = document.getElementById(id);
    const isPass = inp.type === 'password';
    inp.type = isPass ? 'text' : 'password';
    btn.innerHTML = isPass ? '<i class="fa fa-eye-slash"></i>' : '<i class="fa fa-eye"></i>';
}
</script>
</body>
</html>