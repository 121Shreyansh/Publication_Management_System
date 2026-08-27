<?php
session_start();

// Determine where to redirect based on role before destroying session
$role = $_SESSION['role'] ?? '';

if (str_starts_with($role, 'Student-')) {
    $redirect = 'index.php';
    $portal   = 'Student Portal';
} elseif ($role === 'HOD') {
    $redirect = 'index.php';
    $portal   = 'HOD Dashboard';
} else {
    $redirect = 'index.php';
    $portal   = 'Faculty Dashboard';
}

// If confirmed, destroy session and redirect
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logout — IIEST</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #240046 0%, #3c096c 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 50px 45px;
            width: 420px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            animation: popIn 0.3s ease;
        }

        @keyframes popIn {
            from { transform: scale(0.92); opacity: 0; }
            to   { transform: scale(1);    opacity: 1; }
        }

        .icon-wrap {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #fef3c7;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }

        .icon-wrap i {
            font-size: 34px;
            color: #d97706;
        }

        h2 {
            color: #1e293b;
            font-size: 22px;
            margin-bottom: 10px;
        }

        p {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        p strong {
            color: #240046;
        }

        .btn-row {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .btn {
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-logout {
            background: #240046;
            color: white;
        }

        .btn-logout:hover {
            background: #3c096c;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(36,0,70,0.3);
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
        }

        .portal-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #475569;
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }

        .footer-note {
            margin-top: 28px;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="icon-wrap">
        <i class="fa fa-right-from-bracket"></i>
    </div>

    <div class="portal-badge">
        <i class="fa fa-shield-halved"></i>
        &nbsp;<?= htmlspecialchars($portal) ?>
    </div>

    <h2>Confirm Logout</h2>
    <p>
        You are signed in as <strong><?= htmlspecialchars($_SESSION['user_id'] ?? 'Unknown') ?></strong>.<br>
        Are you sure you want to log out?
    </p>

    <div class="btn-row">
        <a href="javascript:history.back()" class="btn btn-cancel">
            <i class="fa fa-arrow-left"></i> Stay
        </a>
        <a href="logout.php?confirm=yes" class="btn btn-logout">
            <i class="fa fa-right-from-bracket"></i> Yes, Logout
        </a>
    </div>

    <div class="footer-note">
        <i class="fa fa-lock"></i> &nbsp;Your session will be securely terminated.
    </div>
</div>

</body>
</html>