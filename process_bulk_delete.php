<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) { exit("Unauthorized access."); }

if (isset($_POST['bulk_delete']) && !empty($_POST['pub_ids'])) {
    $ids = $_POST['pub_ids']; 
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    try {
        $pdo->beginTransaction();
        
        // Delete only records belonging to the logged-in user
        $sql = "DELETE FROM publication_author WHERE author_id = ? AND publication_id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute(array_merge([$_SESSION['user_id']], $ids));
        
        $pdo->commit();
        header("Location: dashboard.php?page=delete&status=success&msg=" . count($ids) . "+Publications+Successfully+Removed");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: dashboard.php?page=delete&status=error&msg=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: dashboard.php?page=delete&status=error&msg=Please+select+at+least+one+item+to+delete");
    exit();
}