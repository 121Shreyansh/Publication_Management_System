<?php
session_start();
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM `index` WHERE user_name = ?");
    $stmt->execute([trim($_POST['user_name'])]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id']           = $user['user_id'];
        $_SESSION['role']              = $user['role'];
        $_SESSION['change_password']   = $user['change_password'];
        $_SESSION['profile_completed'] = $user['profile_completed'];

        // HOD — straight to HOD dashboard (no gates for now)
        if ($user['role'] === 'HOD') {
            header("Location: hod_dashboard.php");
            exit;
        }

        // Students and Faculty share the same gate chain
        if ($user['change_password'] == 0) {
            header("Location: change_password.php");
            exit;
        }

        if ($user['profile_completed'] == 0) {
            // Route profile completion by role
            if (str_starts_with($user['role'], 'Student-')) {
                header("Location: student_complete_profile.php");
            } else {
                header("Location: complete_profile.php");
            }
            exit;
        }

        // Fully set up — go to correct dashboard
        if (str_starts_with($user['role'], 'Student-')) {
            header("Location: student_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;

    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login | IIEST Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin:0; height:100vh; display:flex; align-items:center; justify-content:center;
               background:linear-gradient(135deg,#240046 0%,#3c096c 100%); font-family:sans-serif; }
        .login-card { background:white; padding:40px; border-radius:15px; box-shadow:0 10px 25px rgba(0,0,0,0.3); width:350px; }
        h2 { text-align:center; color:#240046; margin-bottom:25px; }
        label { font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px; text-transform:uppercase; }
        input { width:100%; padding:12px; margin-bottom:15px; border:1.5px solid #ddd; border-radius:8px; box-sizing:border-box; font-size:14px; }
        input:focus { outline:none; border-color:#240046; }
        button { width:100%; padding:12px; background:#240046; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:15px; margin-top:5px; }
        button:hover { background:#3c096c; }
        .error { color:#dc2626; font-size:13px; margin-top:10px; text-align:center; }
        .forgot { text-align:center; margin-top:14px; font-size:13px; }
        .forgot a { color:#240046; text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>IIEST — Portal</h2>
        <form method="POST">
            <label>Username</label>
            <input type="text" name="user_name" placeholder="Enter your username" required autofocus>
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter your password" required>
            <button type="submit">Login</button>
            <?php if (isset($error)): ?>
                <p class="error"><i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        </form>
        <div class="forgot">
            <a href="forgot_password.php"><i class="fa fa-key" style="font-size:11px;"></i> Forgot your password?</a>
        </div>
    </div>
</body>
</html>
