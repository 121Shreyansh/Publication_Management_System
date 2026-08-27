<?php
require 'config/db.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    exit(json_encode([]));
}

$term = $_GET['term'] ?? '';
if (strlen($term) < 2) {
    exit(json_encode([]));
}

$likeTerm = "%$term%";

try {
    // Search both tables for titles belonging to this user
    // Using COALESCE and UNION to handle different column names across tables
    $stmt = $pdo->prepare("
        SELECT title FROM (
            SELECT title as title, author_id FROM journal_publication_details j 
            JOIN publication_author pa ON j.journal_publication_id = pa.publication_id
            UNION
            SELECT conference_publication_title as title, author_id FROM conference_publication_details c 
            JOIN publication_author pa ON c.conference_publication_id = pa.publication_id
        ) as combined 
        WHERE title LIKE ? AND author_id = ? 
        LIMIT 8
    ");

    $stmt->execute([$likeTerm, $_SESSION['user_id']]);
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

    header('Content-Type: application/json');
    echo json_encode($results);
} catch (Exception $e) {
    exit(json_encode([]));
}