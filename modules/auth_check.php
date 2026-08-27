<?php
// modules/auth_check.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Basic Login Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. Gate 1: Mandatory Password Change
if ($_SESSION['change_password'] == 0) {
    if (basename($_SERVER['PHP_SELF']) != 'change_password.php') {
        header("Location: change_password.php?status=first_login");
        exit();
    }
} 
// 3. Gate 2: Mandatory Profile Completion
elseif ($_SESSION['profile_completed'] == 0) {
    $allowed = ['complete_profile.php', 'change_password.php', 'logout.php'];
    if (!in_array(basename($_SERVER['PHP_SELF']), $allowed)) {
        header("Location: complete_profile.php?status=incomplete");
        exit();
    }
}
?>