<?php
session_start();
require 'config/db.php';
if (!isset($_SESSION['user_id'])) { exit(json_encode([])); }

$type = $_GET['type'] ?? '';   // 'institute' or 'outside'
$q    = $_GET['q']    ?? '';
$dept = $_GET['dept'] ?? '';

if ($type !== 'departments' && strlen($q) < 2 && empty($dept)) { exit(json_encode([])); }

header('Content-Type: application/json');

try {
    if ($type === 'institute') {
        $sql = "SELECT emp_id as id, CONCAT(first_name,' ',COALESCE(last_name,'')) as name,
                emp_designation as role, d.department_name as dept, email
                FROM employee_info e
                LEFT JOIN department d ON e.department_id = d.department_id
                WHERE e.emp_id != :self";
        $params = [':self' => $_SESSION['user_id']];
        if (!empty($q)) {
            $sql .= " AND (first_name LIKE :q OR last_name LIKE :q OR email LIKE :q OR CAST(emp_id AS CHAR) LIKE :q)";
            $params[':q'] = "%$q%";
        }
        if (!empty($dept)) {
            $sql .= " AND e.department_id = :dept";
            $params[':dept'] = $dept;
        }
        $sql .= " LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    } elseif ($type === 'outside') {
        $sql = "SELECT os_author_id as id, CONCAT(first_name,' ',COALESCE(last_name,'')) as name,
                institute_name as role, institute_name as dept, email
                FROM outside_author
                WHERE first_name LIKE :q OR last_name LIKE :q OR email LIKE :q OR institute_name LIKE :q
                LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':q' => "%$q%"]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    } elseif ($type === 'departments') {
        $stmt = $pdo->query("SELECT department_id as id, department_name as name FROM department ORDER BY department_name");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    } else {
        echo json_encode([]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}