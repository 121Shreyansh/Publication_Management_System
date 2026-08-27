<?php
// login_process.php logic snippet
if ($login_valid) {
    $_SESSION['user_id'] = $row['user_id'];
    $_SESSION['change_password'] = $row['change_password'];
    $_SESSION['profile_completed'] = $row['profile_completed'];

    if ($_SESSION['change_password'] == 0) {
        header("Location: change_password.php");
    } elseif ($_SESSION['profile_completed'] == 0) {
        header("Location: complete_profile.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}