<?php
// setup_security_questions.php — Set up 3 security questions (called after first login)
session_start();
require_once 'config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$uid = $_SESSION['user_id'];

// Check if already set
$already = $pdo->prepare("SELECT COUNT(*) FROM user_security_answers WHERE user_id = ?");
$already->execute([$uid]);
$alreadySet = $already->fetchColumn() >= 3;

$questions = $pdo->query("SELECT security_questions_id as id, question FROM security_questions ORDER BY security_questions_id")->fetchAll();

$msg = ''; $msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_questions'])) {
    $selected = $_POST['question_ids']  ?? [];
    $answers  = $_POST['answers']       ?? [];

    if (count(array_filter($answers)) < 3 || count(array_unique($selected)) < 3) {
        $msg = 'Please select 3 different questions and fill in all answers.';
        $msg_type = 'error';
    } else {
        try {
            $pdo->beginTransaction();
            // Delete old if re-setting
            $pdo->prepare("DELETE FROM user_security_answers WHERE user_id = ?")->execute([$uid]);
            $ins = $pdo->prepare("INSERT INTO user_security_answers (user_id, question_id, answer_hash) VALUES (?,?,?)");
            for ($i = 0; $i < 3; $i++) {
                $ans = strtolower(trim($answers[$i]));
                if (empty($ans)) { throw new Exception("All three answers are required."); }
                $hash = password_hash($ans, PASSWORD_BCRYPT);
                $ins->execute([$uid, $selected[$i], $hash]);
            }
            $pdo->commit();
            $msg = 'Security questions saved!'; $msg_type = 'success';
            $alreadySet = true;
            // Redirect faculty to dashboard, students to their dashboard
            $role = $_SESSION['role'] ?? '';
            $redir = str_starts_with($role, 'Student-') ? 'student_dashboard.php' : 'dashboard.php';
            echo "<script>setTimeout(()=>window.location.href='$redir',1500);</script>";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = 'Error: ' . $e->getMessage(); $msg_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Security Questions — IIEST</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Segoe UI',sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#240046,#3c096c); padding:24px; }
        .card { background:white; border-radius:16px; box-shadow:0 10px 40px rgba(0,0,0,0.25); width:100%; max-width:580px; overflow:hidden; }
        .card-header { background:#240046; color:white; padding:26px 30px; }
        .card-header h2 { margin:0; font-size:20px; }
        .card-header p { margin:8px 0 0; opacity:0.75; font-size:13px; }
        .card-body { padding:30px; }
        .q-block { border:1.5px solid #e2e8f0; border-radius:10px; padding:18px; margin-bottom:16px; }
        .q-num { font-size:11px; font-weight:700; color:#7c3aed; text-transform:uppercase; margin-bottom:10px; }
        label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:5px; }
        select, input { width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; margin-bottom:12px; }
        select:focus, input:focus { outline:none; border-color:#7c3aed; }
        input:last-child { margin-bottom:0; }
        .btn { width:100%; padding:13px; background:#240046; color:white; border:none; border-radius:8px; font-size:15px; font-weight:600; cursor:pointer; margin-top:8px; }
        .btn:hover { background:#3c096c; }
        .alert { padding:12px 14px; border-radius:8px; font-size:13px; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .alert-error   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
        .alert-success { background:#d1fae5; color:#065f46; border:1px solid #34d399; }
        .info-box { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:14px; font-size:13px; color:#1e40af; margin-bottom:20px; }
        .skip-link { display:block; text-align:center; margin-top:14px; font-size:13px; color:#94a3b8; text-decoration:none; }
        .skip-link:hover { color:#240046; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <h2><i class="fa fa-shield-halved"></i> &nbsp;Security Questions</h2>
        <p>These help you recover your account if you forget your password.</p>
    </div>
    <div class="card-body">

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?>">
            <i class="fa fa-<?= $msg_type==='success'?'check-circle':'circle-exclamation' ?>"></i>
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <?php if ($alreadySet && $msg_type !== 'success'): ?>
        <div class="info-box"><i class="fa fa-circle-check"></i> &nbsp;You have already set up security questions. You can update them below.</div>
        <?php elseif (!$alreadySet): ?>
        <div class="info-box"><i class="fa fa-circle-info"></i> &nbsp;Select 3 security questions and provide memorable answers. Answers are case-insensitive.</div>
        <?php endif; ?>

        <form method="POST">
            <?php for ($i = 0; $i < 3; $i++): ?>
            <div class="q-block">
                <div class="q-num">Question <?= $i+1 ?> of 3</div>
                <label>Select a question</label>
                <select name="question_ids[]" required>
                    <option value="">— Choose a question —</option>
                    <?php foreach ($questions as $q): ?>
                    <option value="<?= $q['id'] ?>"><?= htmlspecialchars($q['question']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Your answer</label>
                <input type="text" name="answers[]" placeholder="Type your answer" required autocomplete="off">
            </div>
            <?php endfor; ?>

            <button type="submit" name="save_questions" class="btn">
                <i class="fa fa-shield-halved"></i> Save Security Questions
            </button>
        </form>

        <?php
        $role = $_SESSION['role'] ?? '';
        $redir = str_starts_with($role, 'Student-') ? 'student_dashboard.php' : 'dashboard.php';        ?>
        <a href="<?= $redir ?>" class="skip-link">Skip for now →</a>
    </div>
</div>
</body>
</html>
