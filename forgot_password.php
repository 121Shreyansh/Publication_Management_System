<?php
// forgot_password.php — Reset password using security questions
session_start();
require_once 'config/db.php';

$step    = $_GET['step']  ?? 1;
$msg     = '';
$msg_type= '';

// ── STEP 1: Enter username ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['find_user'])) {
    $uname = trim($_POST['user_name']);
    $stmt  = $pdo->prepare("SELECT user_id FROM `index` WHERE user_name = ?");
    $stmt->execute([$uname]);
    $found = $stmt->fetch();
    if (!$found) {
        $msg = 'No account found for that username.';
        $msg_type = 'error';
    } else {
        // Check if user has security questions set
        $sq = $pdo->prepare("SELECT COUNT(*) FROM user_security_answers WHERE user_id = ?");
        $sq->execute([$found['user_id']]);
        if ($sq->fetchColumn() < 1) {
            $msg = 'No security questions set for this account. Please contact the administrator.';
            $msg_type = 'error';
        } else {
            $_SESSION['reset_user_id'] = $found['user_id'];
            header('Location: forgot_password.php?step=2');
            exit;
        }
    }
}

// ── STEP 2: Answer security question ────────────────────────
if ($step == 2) {
    if (!isset($_SESSION['reset_user_id'])) { header('Location: forgot_password.php'); exit; }
    $uid = $_SESSION['reset_user_id'];
    // Pick one random question the user has answered
    $stmt = $pdo->prepare("
        SELECT usa.security_ans_id, usa.answer_hash, sq.question
        FROM user_security_answers usa
        JOIN security_questions sq ON usa.question_id = sq.security_questions_id
        WHERE usa.user_id = ?
        ORDER BY RAND() LIMIT 1
    ");
    $stmt->execute([$uid]);
    $sq = $stmt->fetch();
    $_SESSION['reset_ans_id']   = $sq['security_ans_id'];
    $_SESSION['reset_ans_hash'] = $sq['answer_hash'];
    $challenge_question = $sq['question'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_answer'])) {
    if (!isset($_SESSION['reset_user_id'], $_SESSION['reset_ans_hash'])) {
        header('Location: forgot_password.php'); exit;
    }
    $answer = strtolower(trim($_POST['security_answer']));
    if (password_verify($answer, $_SESSION['reset_ans_hash'])) {
        $_SESSION['reset_verified'] = true;
        header('Location: forgot_password.php?step=3'); exit;
    } else {
        $msg = 'Incorrect answer. Please try again.';
        $msg_type = 'error';
        $step = 2;
        // Re-fetch question
        $stmt = $pdo->prepare("
            SELECT usa.answer_hash, sq.question FROM user_security_answers usa
            JOIN security_questions sq ON usa.question_id = sq.security_questions_id
            WHERE usa.security_ans_id = ?");
        $stmt->execute([$_SESSION['reset_ans_id']]);
        $sq = $stmt->fetch();
        $challenge_question = $sq['question'];
        $_SESSION['reset_ans_hash'] = $sq['answer_hash'];
    }
}

// ── STEP 3: Set new password ─────────────────────────────────
if ($step == 3) {
    if (!isset($_SESSION['reset_user_id'], $_SESSION['reset_verified'])) {
        header('Location: forgot_password.php'); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_new_password'])) {
    if (!isset($_SESSION['reset_user_id'], $_SESSION['reset_verified'])) {
        header('Location: forgot_password.php'); exit;
    }
    $new  = $_POST['new_password'];
    $conf = $_POST['confirm_password'];
    if (strlen($new) < 8) {
        $msg = 'Password must be at least 8 characters.'; $msg_type = 'error'; $step = 3;
    } elseif ($new !== $conf) {
        $msg = 'Passwords do not match.'; $msg_type = 'error'; $step = 3;
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE `index` SET password=?, change_password=1,
            last_changed_password_date=CURDATE(), last_changed_password_time=CURTIME()
            WHERE user_id=?")
            ->execute([$hashed, $_SESSION['reset_user_id']]);
        unset($_SESSION['reset_user_id'], $_SESSION['reset_verified'], $_SESSION['reset_ans_id'], $_SESSION['reset_ans_hash']);
        $msg = 'Password reset successfully! You can now log in.';
        $msg_type = 'success';
        $step = 'done';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password — IIEST</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#240046,#3c096c); }
        .card { background:white; border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,0.25); width:440px; overflow:hidden; }
        .card-header { background:#240046; color:white; padding:28px 30px; text-align:center; }
        .card-header h2 { margin:0; font-size:20px; }
        .card-header p { margin:8px 0 0; opacity:0.75; font-size:13px; }
        .card-body { padding:30px; }
        .steps { display:flex; gap:0; margin-bottom:28px; }
        .step { flex:1; text-align:center; font-size:12px; font-weight:700; color:#94a3b8; position:relative; }
        .step::after { content:''; position:absolute; top:12px; left:50%; width:100%; height:2px; background:#e2e8f0; z-index:0; }
        .step:last-child::after { display:none; }
        .step-num { width:26px; height:26px; border-radius:50%; background:#e2e8f0; color:#94a3b8; font-size:12px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; position:relative; z-index:1; margin-bottom:4px; }
        .step.active .step-num { background:#240046; color:white; }
        .step.active { color:#240046; }
        .step.done .step-num { background:#16a34a; color:white; }
        label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:5px; }
        input { width:100%; padding:11px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; margin-bottom:16px; }
        input:focus { outline:none; border-color:#240046; }
        .btn { width:100%; padding:13px; background:#240046; color:white; border:none; border-radius:8px; font-size:15px; font-weight:600; cursor:pointer; }
        .btn:hover { background:#3c096c; }
        .alert { padding:12px 14px; border-radius:8px; font-size:13px; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .alert-error   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
        .alert-success { background:#d1fae5; color:#065f46; border:1px solid #34d399; }
        .back-link { display:block; text-align:center; margin-top:16px; font-size:13px; color:#64748b; text-decoration:none; }
        .back-link:hover { color:#240046; }
        .q-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px 16px; margin-bottom:16px; font-size:14px; color:#1e293b; font-style:italic; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <h2><i class="fa fa-key"></i> &nbsp;Reset Password</h2>
        <p>IIEST Shibpur Portal</p>
    </div>
    <div class="card-body">

        <?php if ($step !== 'done'): ?>
        <div class="steps">
            <div class="step <?= $step>=1?'active':'' ?> <?= $step>1?'done':'' ?>"><div class="step-num"><?= $step>1?'✓':'1' ?></div><div>Find Account</div></div>
            <div class="step <?= $step>=2?'active':'' ?> <?= $step>2?'done':'' ?>"><div class="step-num"><?= $step>2?'✓':'2' ?></div><div>Verify</div></div>
            <div class="step <?= $step>=3?'active':'' ?>"><div class="step-num">3</div><div>New Password</div></div>
        </div>
        <?php endif; ?>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?>">
            <i class="fa fa-<?= $msg_type==='success'?'check-circle':'circle-exclamation' ?>"></i>
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <?php if ($step == 1 || $step == 'done' && $msg_type === 'error'): ?>
        <form method="POST">
            <label>Username</label>
            <input type="text" name="user_name" placeholder="Enter your username" required autofocus>
            <button type="submit" name="find_user" class="btn">Find My Account</button>
        </form>

        <?php elseif ($step == 2): ?>
        <p style="font-size:13px;color:#64748b;margin-bottom:16px;">Answer your security question to continue.</p>
        <div class="q-box"><i class="fa fa-question-circle" style="color:#240046;margin-right:6px;"></i><?= htmlspecialchars($challenge_question) ?></div>
        <form method="POST">
            <label>Your Answer</label>
            <input type="text" name="security_answer" placeholder="Type your answer" required autofocus autocomplete="off">
            <button type="submit" name="verify_answer" class="btn">Verify Answer</button>
        </form>

        <?php elseif ($step == 3): ?>
        <form method="POST">
            <label>New Password</label>
            <input type="password" name="new_password" placeholder="At least 8 characters" required>
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Re-enter password" required>
            <button type="submit" name="set_new_password" class="btn">Set New Password</button>
        </form>

        <?php elseif ($step == 'done'): ?>
        <div style="text-align:center;padding:20px 0;">
            <i class="fa fa-check-circle" style="font-size:48px;color:#16a34a;display:block;margin-bottom:16px;"></i>
            <p style="font-size:15px;color:#1e293b;font-weight:600;">Password reset successfully!</p>
            <p style="font-size:13px;color:#64748b;margin-top:8px;">You can now log in with your new password.</p>
        </div>
        <?php endif; ?>

        <a href="index.php" class="back-link"><i class="fa fa-arrow-left"></i> Back to Login</a>
    </div>
</div>
</body>
</html>
