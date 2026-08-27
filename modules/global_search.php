<?php
// Capture Filters from the UI
$q = $_GET['q'] ?? '';
$type = $_GET['type'] ?? '';
$dept = $_GET['dept'] ?? '';
$author_name = $_GET['author_name'] ?? '';

$params = [];
// Using LEFT JOIN so publications show up even if the author link is broken
$sql = "SELECT pa.publication_id, pa.publication_type, 
        e.first_name, e.last_name, e.emp_designation,
        COALESCE(j.title, c.conference_publication_title) as title,
        COALESCE(j.pub_date, c.conference_start_date) as date,
        COALESCE(j.journal_name, c.conference_title) as venue,
        COALESCE(j.publication_volume_no, '') as vol, 
        COALESCE(j.publication_issue_no, '') as issue
        FROM publication_author pa
        LEFT JOIN employee_info e ON pa.author_id = e.emp_id
        LEFT JOIN journal_publication_details j ON pa.publication_id = j.journal_id
        LEFT JOIN conference_publication_details c ON pa.publication_id = c.conference_publication_id
        WHERE 1=1";

// Apply Global Filters
if (!empty($q)) { 
    $sql .= " AND (j.title LIKE ? OR c.conference_publication_title LIKE ?)"; 
    $params[] = "%$q%"; $params[] = "%$q%"; 
}
if (!empty($type)) { $sql .= " AND pa.publication_type = ?"; $params[] = $type; }
if (!empty($dept)) { $sql .= " AND e.emp_designation LIKE ?"; $params[] = "%$dept%"; }
if (!empty($author_name)) { 
    $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ?)"; 
    $params[] = "%$author_name%"; $params[] = "%$author_name%"; 
}

$sql .= " ORDER BY date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();
?>

<div class="form-card" style="background:white; padding:30px; border-radius:15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <h2 style="color:var(--purple); margin-top:0;">Global Publication Search</h2>

    <form method="GET">
        <input type="hidden" name="page" value="search">
        <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:10px; margin-bottom:20px;">
            <input type="text" name="q" class="auto-search" placeholder="Search title..." value="<?= htmlspecialchars($q) ?>" style="padding:10px; border-radius:8px; border:1px solid #ddd;">
            <input type="text" name="author_name" placeholder="Author Name" value="<?= htmlspecialchars($author_name) ?>" style="padding:10px; border-radius:8px; border:1px solid #ddd;">
            <button type="submit" class="submit-btn" style="margin:0;">Search All</button>
        </div>
    </form>

    <div class="publication-list">
        <?php if(count($results) > 0): ?>
            <?php foreach($results as $index => $row): ?>
            <div style="margin-bottom:20px; padding:15px; border-left: 4px solid var(--purple); background:#fcfcfc;">
                <div style="font-size:12px; font-weight:bold; color:#666; margin-bottom:5px;">
                    <i class="fa fa-user"></i> 
                    <?= $row['first_name'] ? htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) : "Unknown Author" ?> 
                    | <?= htmlspecialchars($row['publication_type']) ?>
                </div>
                <div style="line-height:1.6;">
                    <span style="font-style:italic; font-weight:bold; color: #2d6a4f;">"<?= htmlspecialchars($row['title']) ?>"</span>, 
                    <?= htmlspecialchars($row['venue']) ?>, 
                    <strong><?= !empty($row['date']) ? date('Y', strtotime($row['date'])) : 'N/A' ?></strong>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center; padding:40px; color:#999;">
                <i class="fa fa-search" style="font-size:40px; margin-bottom:10px;"></i>
                <p>No publications found in the database yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>