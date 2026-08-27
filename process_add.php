<?php
session_start();
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['pub_type'];
    $pdf_path = null;

    // Optional PDF Upload Logic
    if (!empty($_FILES['pdf_file']['name'])) {
        $path = "uploads/" . time() . "_" . basename($_FILES['pdf_file']['name']);
        if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $path)) {
            $pdf_path = $path;
        }
    }

    try {
        $pdo->beginTransaction();

        // 1. Base Entry with Verification Flag
        $stmt = $pdo->prepare("INSERT INTO publication_author (author_id, author_type, publication_type, publication_status, verification_flag) VALUES (?, 'Corresponding', ?, 'National', 0)");
        $stmt->execute([$_SESSION['user_id'], $type]);
        $pub_id = $pdo->lastInsertId();

        // 2. Type-Specific Logic based on your radio selection
        if ($type == 'Journal') {
            $stmt = $pdo->prepare("INSERT INTO journal_publication_details (journal_id, journal_name, title, publication_volume_no, publication_issue_no, doi, pub_date, pdf_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$pub_id, $_POST['title'], $_POST['j_name'], $_POST['volume'], $_POST['issue'], $_POST['doi'], $_POST['pub_date'], $pdf_path]);
        } 
        elseif ($type == 'Conference') {
            $stmt = $pdo->prepare("INSERT INTO conference_publication_details (conference_publication_id, conference_publication_title, conference_title, conference_city, conference_country, conference_start_date, conference_end_date, pdf_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$pub_id, $_POST['title'], $_POST['c_title'], $_POST['c_city'], $_POST['c_country'], $_POST['pub_date'], $_POST['end_date'], $pdf_path]);
        }

        $pdo->commit();
        // Fixed Redirect to avoid 404/500 errors
        header("Location: dashboard.php?page=add&status=success");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: dashboard.php?page=add&status=error&msg=" . urlencode($e->getMessage()));
        exit();
    }
}
?>