<?php
// pub_view.php - Professional Citation View with Advanced Filters
require_once 'config/db.php';
$user_id = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Arjun Das'; // Matches your profile

// --- 1. DYNAMIC SEARCH & FILTER LOGIC ---
$type_f = $_GET['type'] ?? 'All';
$search_f = $_GET['search'] ?? '';

// Build Query with Author Grouping for Citation View
$query = "SELECT pa.publication_id, pa.publication_type, pa.verification_flag,
          j.journal_publication_title as j_t, j.journal_publication_date as j_date, j.journal_title as j_v, 
          j.publication_volume_no as j_vol, j.publication_issue_no as j_iss,
          c.conference_publication_title as c_t, c.conference_start_date as c_date, 
          c.conference_title as c_v
          FROM publication_author pa
          LEFT JOIN journal_publication_details j ON pa.publication_id = j.journal_publication_id
          LEFT JOIN conference_publication_details c ON pa.publication_id = c.conference_publication_id
          WHERE pa.author_id = :auth_id";

$params = [':auth_id' => $user_id];

// Dynamic SQL Builder for selective filters
if ($type_f == 'Journal') {
    $query .= " AND pa.publication_type = 'Journal'";
    if (!empty($_GET['j_pub']))  { $query .= " AND j.journal_publisher LIKE :j_pub"; $params[':j_pub'] = "%".$_GET['j_pub']."%"; }
    if (!empty($_GET['j_doi']))  { $query .= " AND j.publication_doi = :j_doi"; $params[':j_doi'] = $_GET['j_doi']; }
    if (!empty($_GET['j_city'])) { $query .= " AND j.published_city LIKE :j_city"; $params[':j_city'] = "%".$_GET['j_city']."%"; }
} elseif ($type_f == 'Conference') {
    $query .= " AND pa.publication_type = 'Conference'";
    if (!empty($_GET['c_start'])) { $query .= " AND c.conference_start_date >= :c_start"; $params[':c_start'] = $_GET['c_start']; }
    if (!empty($_GET['c_end']))   { $query .= " AND c.conference_end_date <= :c_end"; $params[':c_end'] = $_GET['c_end']; }
}

if (!empty($search_f)) {
    $query .= " AND (j.journal_publication_title LIKE :search OR c.conference_publication_title LIKE :search)";
    $params[':search'] = "%$search_f%";
}

$stmt = $pdo->prepare($query . " ORDER BY pa.publication_id DESC");
$stmt->execute($params);
$results = $stmt->fetchAll();

// Pre-load titles for Autocomplete
$autocomplete_titles = array_filter(array_map(function($r) { return $r['j_t'] ?? $r['c_t']; }, $results));
?>

<style>
    .view-card { background: white; padding: 35px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-top: 6px solid #4b2c20; font-family: 'Segoe UI', sans-serif; }
    .filter-section { background: #f8f6ff; padding: 25px; border-radius: 12px; border: 1px solid #e0d7ff; margin-bottom: 30px; position: relative; }
    .grid-layout { display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: flex-end; }
    input, select { padding: 10px; border: 1px solid #ddd; border-radius: 8px; width: 100%; box-sizing: border-box; }
    label { font-weight: 600; font-size: 13px; color: #555; margin-bottom: 5px; display: block; }
    
    .btn-main { background: #4b2c20; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; }
    .btn-toggle { background: white; color: #4b2c20; border: 1px solid #4b2c20; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold; }
    
    /* Autocomplete Styling */
    .autocomplete-list { position: absolute; background: white; border: 1px solid #ddd; width: 45%; z-index: 1000; max-height: 150px; overflow-y: auto; display: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .autocomplete-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; font-size: 13px; }
    .autocomplete-item:hover { background: #f8f6ff; }

    /* Citation Layout */
    .pub-row { display: flex; align-items: flex-start; gap: 15px; padding: 20px; border-bottom: 1px solid #f1f5f9; }
    .citation-text { font-size: 15px; color: #334155; line-height: 1.6; }
    .pub-title { font-weight: bold; color: #1e293b; }
    .type-tag { font-size: 11px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-left: 8px; }
    .extra-filters { display: none; grid-column: span 3; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-top: 15px; padding-top: 15px; border-top: 1px dashed #d1c4e9; }
</style>

<div class="view-card">
    <h2 style="color:#4b2c20; margin-top:0;">My Published Works</h2>

    <div class="filter-section">
        <form method="GET" id="searchForm" autocomplete="off">
            <input type="hidden" name="page" value="view">
            <div class="grid-layout">
                <div style="position: relative;">
                    <label>Search Titles</label>
                    <input type="text" name="search" id="autoInput" value="<?= htmlspecialchars($search_f) ?>" placeholder="Type to search your publications...">
                    <div id="suggestionBox" class="autocomplete-list"></div>
                </div>
                <div>
                    <label>Type</label>
                    <select name="type" onchange="this.form.submit()">
                        <option value="All">All Categories</option>
                        <option value="Journal" <?= $type_f=='Journal'?'selected':'' ?>>Journal</option>
                        <option value="Conference" <?= $type_f=='Conference'?'selected':'' ?>>Conference</option>
                    </select>
                </div>
                <div>
                    <?php if($type_f != 'All'): ?>
                        <button type="button" class="btn-toggle" onclick="toggleExtra()">More Filters</button>
                    <?php endif; ?>
                    <button type="submit" class="btn-main">Filter</button>
                </div>

                <div id="extraSection" class="extra-filters">
                    <?php if($type_f == 'Journal'): ?>
                        <div><label>Publisher</label><input type="text" name="j_pub" value="<?= htmlspecialchars($_GET['j_pub']??'') ?>"></div>
                        <div><label>DOI</label><input type="text" name="j_doi" value="<?= htmlspecialchars($_GET['j_doi']??'') ?>"></div>
                        <div><label>City</label><input type="text" name="j_city" value="<?= htmlspecialchars($_GET['j_city']??'') ?>"></div>
                    <?php elseif($type_f == 'Conference'): ?>
                        <div><label>Date From</label><input type="date" name="c_start" value="<?= $_GET['c_start']??'' ?>"></div>
                        <div><label>Date To</label><input type="date" name="c_end" value="<?= $_GET['c_end']??'' ?>"></div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div class="publication-list">
        <?php if (count($results) > 0): ?>
            <?php foreach($results as $index => $row): ?>
            <div class="pub-row">
                <div class="citation-text">
                    <span style="color:#94a3b8; font-weight:bold; margin-right:10px;"><?= $index + 1 ?>.</span>
                    <span><?= htmlspecialchars($fullName) ?>, </span> 
                    <span class="pub-title">"<?= htmlspecialchars($row['j_t'] ?? $row['c_t']) ?>"</span>, 
                    <?php if ($row['publication_type'] == 'Journal'): ?>
                        Vol. <?= $row['j_vol'] ?>(<?= $row['j_iss'] ?>), 
                        <?= date('Y', strtotime($row['j_date'])) ?>
                    <?php else: ?>
                        <?= htmlspecialchars($row['c_v']) ?>,
                        <?= date('Y', strtotime($row['c_date'])) ?>
                    <?php endif; ?>
                    <span class="type-tag">(<?= $row['publication_type'] ?>)</span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center; padding: 60px; color: #94a3b8;">
                No publications found matching your search criteria.
            </div>
        <?php endif; ?>
    </div>
</div>



<script>
// Autocomplete Logic
const titles = <?= json_encode(array_values($autocomplete_titles)) ?>;
const input = document.getElementById("autoInput");
const box = document.getElementById("suggestionBox");

input.addEventListener("input", function() {
    const val = this.value.toLowerCase();
    box.innerHTML = "";
    if (!val) { box.style.display = "none"; return; }
    const matches = titles.filter(t => t.toLowerCase().includes(val)).slice(0, 5);
    if (matches.length > 0) {
        box.style.display = "block";
        matches.forEach(m => {
            const div = document.createElement("div");
            div.className = "autocomplete-item"; div.innerText = m;
            div.onclick = () => { input.value = m; box.style.display = "none"; document.getElementById("searchForm").submit(); };
            box.appendChild(div);
        });
    } else { box.style.display = "none"; }
});

function toggleExtra() {
    var s = document.getElementById("extraSection");
    s.style.display = (s.style.display === "grid") ? "none" : "grid";
}
</script>