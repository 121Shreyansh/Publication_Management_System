<?php
// pub_delete.php - Bulk Deletion
if (!isset($pdo)) { require_once __DIR__ . '/../config/db.php'; }
$user_id = $_SESSION['user_id'];

// Fetch actual name from DB
$nameStmt = $pdo->prepare("SELECT CONCAT(first_name,' ',COALESCE(last_name,'')) as full_name FROM employee_info WHERE emp_id = ?");
$nameStmt->execute([$user_id]);
$nameRow  = $nameStmt->fetch();
$fullName = trim($nameRow['full_name'] ?? '');

// --- 1. DYNAMIC SEARCH & FILTER LOGIC ---
$type_f = $_GET['type'] ?? 'All';
$q = $_GET['q'] ?? '';

$sql = "SELECT pa.publication_id, pa.publication_type, 
        j.journal_publication_title as j_t, j.journal_publication_date as j_date, j.publication_volume_no as j_vol, j.publication_issue_no as j_iss,
        c.conference_publication_title as c_t, c.conference_start_date as c_date
        FROM publication_author pa
        LEFT JOIN journal_publication_details j ON pa.publication_id = j.journal_publication_id
        LEFT JOIN conference_publication_details c ON pa.publication_id = c.conference_publication_id
        WHERE pa.author_id = :auth_id";

$params = [':auth_id' => $user_id];

if ($type_f !== 'All') {
    $sql .= " AND pa.publication_type = :type";
    $params[':type'] = $type_f;
}

if (!empty($q)) { 
    $sql .= " AND (j.journal_publication_title LIKE :q OR c.conference_publication_title LIKE :q)"; 
    $params[':q'] = "%$q%";
}

$stmt = $pdo->prepare($sql . " ORDER BY pa.publication_id DESC");
$stmt->execute($params);
$results = $stmt->fetchAll();
?>

<style>
    /* Main Layout */
    .delete-card { background: white; padding: 35px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-top: 6px solid #e63946; font-family: 'Segoe UI', sans-serif; }
    .page-title { color: #e63946; font-size: 24px; font-weight: bold; margin-bottom: 25px; margin-top: 0; }

    /* Search Section Fixes */
    .filter-section { background: #fdf2f2; padding: 20px; border-radius: 12px; border: 1px solid #fee2e2; margin-bottom: 30px; }
    .grid-layout { display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: flex-end; }
    input, select { padding: 10px; border: 1px solid #ddd; border-radius: 8px; width: 100%; box-sizing: border-box; }
    label { font-weight: 600; font-size: 13px; color: #444; margin-bottom: 5px; display: block; }
    
    .btn-search { background: #2d3748; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; height: 42px; transition: 0.3s; }
    .btn-search:hover { background: #1a202c; }

    /* Bulk Action Bar */
    .bulk-bar { display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 15px 20px; border-radius: 10px; border: 1px solid #fed7d7; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
    .select-all-label { color: #e63946; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 10px; font-size: 15px; }
    .btn-delete { background: #e63946; color: white; border: none; padding: 10px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 8px; }
    .btn-delete:hover { background: #c53030; }

    /* Publication Row Fixes */
    .pub-row { display: flex; align-items: center; gap: 15px; padding: 15px; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
    .pub-row:hover { background: #fff5f5; }
    .pub-checkbox { width: 18px; height: 18px; cursor: pointer; margin: 0; }
    .citation-text { font-size: 14px; color: #334155; line-height: 1.5; }
    .pub-title { font-weight: bold; color: #1e293b; }
    .type-tag { font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-left: 5px; }
</style>

<div class="delete-card">
    <h1 class="page-title">Bulk Delete Publications</h1>

    <div class="filter-section">
        <form method="GET" class="grid-layout">
            <input type="hidden" name="page" value="delete">
            <div>
                <label>Search Keywords</label>
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Find records for removal...">
            </div>
            <div>
                <label>Type</label>
                <select name="type">
                    <option value="All">All Types</option>
                    <option value="Journal" <?= $type_f=='Journal'?'selected':'' ?>>Journal</option>
                    <option value="Conference" <?= $type_f=='Conference'?'selected':'' ?>>Conference</option>
                </select>
            </div>
            <button type="submit" class="btn-search">Apply Filter</button>
        </form>
    </div>

    <form action="process_bulk_delete.php" method="POST" id="bulkDeleteForm">
        <div class="bulk-bar">
            <label class="select-all-label">
                <input type="checkbox" id="selectAll" onclick="toggleAll(this)" class="pub-checkbox"> Select All
            </label>
            <button type="submit" name="bulk_delete" class="btn-delete" onclick="return confirmDeletion()">
                <i class="fa fa-trash-alt"></i> Delete Selected
            </button>
        </div>

        <div class="publication-list">
            <?php if (count($results) > 0): ?>
                <?php foreach($results as $index => $row): ?>
                <div class="pub-row">
                    <input type="checkbox" name="pub_ids[]" value="<?= $row['publication_id'] ?>" class="pub-checkbox">
                    <div class="citation-text">
                        <span style="color:#94a3b8; font-weight:bold;"><?= $index + 1 ?>.</span>
                        <span><?= htmlspecialchars($fullName) ?>, </span> 
                        <span class="pub-title">"<?= htmlspecialchars($row['j_t'] ?? $row['c_t']) ?>"</span>, 
                        <?php if ($row['publication_type'] == 'Journal'): ?>
                            Vol. <?= $row['j_vol'] ?>(<?= $row['j_iss'] ?>), 
                            <?= date('Y', strtotime($row['j_date'])) ?>
                        <?php else: ?>
                            <?= date('Y', strtotime($row['c_date'])) ?>
                        <?php endif; ?>
                        <span class="type-tag">(<?= $row['publication_type'] ?>)</span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align:center; padding: 50px; color: #94a3b8;">
                    <i class="fa fa-folder-open" style="font-size: 30px; display: block; margin-bottom: 10px;"></i>
                    No publications found matching your search.
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.pub-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
}

function confirmDeletion() {
    const selectedCount = document.querySelectorAll('.pub-checkbox:checked:not(#selectAll)').length;
    if (selectedCount === 0) {
        alert("Please select at least one publication to delete.");
        return false;
    }
    return confirm(`Warning: You have selected ${selectedCount} record(s) for permanent removal. This action cannot be undone. Proceed?`);
}
</script>