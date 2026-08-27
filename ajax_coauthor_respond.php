<?php
session_start();
require 'config/db.php';
if (!isset($_SESSION['user_id'])) { exit(json_encode(['ok'=>false])); }

$action     = $_POST['action']     ?? '';
$request_id = (int)($_POST['request_id'] ?? 0);
$user_id    = $_SESSION['user_id'];

try {
    // Verify the request is actually for this user
    $stmt = $pdo->prepare("SELECT * FROM co_author_requests WHERE request_id=? AND requested_id=? AND status='Pending'");
    $stmt->execute([$request_id, $user_id]);
    $req = $stmt->fetch();
    if (!$req) { echo json_encode(['ok'=>false,'msg'=>'Request not found']); exit; }

    if ($action === 'accept') {
        // Insert as co-author in publication_author
        $stmt = $pdo->prepare("SELECT publication_type FROM publication_author WHERE publication_id=?");
        $stmt->execute([$req['publication_id']]);
        $pub = $stmt->fetch();

        $stmt = $pdo->prepare("INSERT IGNORE INTO publication_author
            (author_id, author_type, publication_type, publication_status, verification_flag)
            SELECT ?, 'Co-Author', publication_type, publication_status, 1
            FROM publication_author WHERE publication_id=? LIMIT 1");
        // Note: publication_author has UNIQUE on publication_id, so co-authors need a different table
        // We update the request status and rely on co_author_requests for display
        $stmt2 = $pdo->prepare("UPDATE co_author_requests SET status='Accepted', responded_at=NOW() WHERE request_id=?");
        $stmt2->execute([$request_id]);
        echo json_encode(['ok'=>true,'action'=>'accepted']);

    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE co_author_requests SET status='Rejected', responded_at=NOW() WHERE request_id=?");
        $stmt->execute([$request_id]);
        echo json_encode(['ok'=>true,'action'=>'rejected']);
    }
} catch (Exception $e) {
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}