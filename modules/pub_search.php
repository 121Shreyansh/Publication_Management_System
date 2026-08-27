<?php
// pub_search.php — Global search across all authors (institute + outside)
if (!isset($pdo)) { require_once __DIR__ . '/../config/db.php'; }

$q          = trim($_GET['q']          ?? '');
$type_f     = $_GET['type']            ?? '';
$status_f   = $_GET['status']          ?? '';
$date_from  = $_GET['date_from']       ?? '';
$date_to    = $_GET['date_to']         ?? '';
$author_f   = trim($_GET['author']     ?? '');
$dept_f     = $_GET['dept']            ?? '';
$verified_f = $_GET['verified']        ?? '';

// Fetch departments for filter dropdown
$departments = $pdo->query("SELECT department_id, department_name FROM department ORDER BY department_name")->fetchAll();

$results = [];
$searched = false;

if (!empty($q) || !empty($type_f) || !empty($author_f) || !empty($dept_f) || !empty($date_from) || !empty($verified_f)) {
    $searched = true;

    $sql = "SELECT DISTINCT
                pa.publication_id, pa.publication_type, pa.verification_flag, pa.publication_status,
                COALESCE(j.journal_publication_title, c.conference_publication_title) as title,
                COALESCE(j.journal_publication_date, c.conference_start_date)     as pub_date,
                COALESCE(j.journal_title, c.conference_title)      as venue,
                COALESCE(j.journal_publisher, c.conference_publisher) as publisher,
                COALESCE(j.publication_doi, c.publication_doi)    as doi,
                j.publication_volume_no as vol, j.publication_issue_no as iss,
                e_main.first_name as main_fn, e_main.last_name as main_ln,
                d.department_name
            FROM publication_author pa
            LEFT JOIN journal_publication_details    j      ON pa.publication_id = j.journal_publication_id
            LEFT JOIN conference_publication_details c      ON pa.publication_id = c.conference_publication_id
            LEFT JOIN employee_info                  e_main ON pa.author_id      = e_main.emp_id
            LEFT JOIN department                     d      ON e_main.department_id = d.department_id
            WHERE 1=1";

    $params = [];

    if (!empty($q)) {
        $sql .= " AND (j.journal_publication_title LIKE ? OR c.conference_publication_title LIKE ?
                       OR j.journal_title LIKE ? OR c.conference_title LIKE ?
                       OR j.publication_doi LIKE ? OR c.publication_doi LIKE ?)";
        $lq = "%$q%";
        $params = array_merge($params, [$lq,$lq,$lq,$lq,$lq,$lq]);
    }
    if (!empty($type_f))    { $sql .= " AND pa.publication_type = ?";    $params[] = $type_f; }
    if (!empty($status_f))  { $sql .= " AND pa.publication_status = ?";  $params[] = $status_f; }
    if (!empty($verified_f)){ $sql .= " AND pa.verification_flag = ?";   $params[] = $verified_f; }
    if (!empty($date_from)) { $sql .= " AND COALESCE(j.journal_publication_date, c.conference_start_date) >= ?"; $params[] = $date_from; }
    if (!empty($date_to))   { $sql .= " AND COALESCE(j.journal_publication_date, c.conference_start_date) <= ?"; $params[] = $date_to; }
    if (!empty($dept_f))    { $sql .= " AND e_main.department_id = ?";   $params[] = $dept_f; }

    if (!empty($author_f)) {
        // Search by author name — could be main or co-author (institute or outside)
        $sql .= " AND (
            pa.publication_id IN (
                SELECT pa2.publication_id FROM publication_author pa2
                JOIN employee_info e2 ON pa2.author_id = e2.emp_id
                WHERE CONCAT(e2.first_name,' ',COALESCE(e2.last_name,'')) LIKE ?
            )
            OR pa.publication_id IN (
                SELECT car.publication_id FROM co_author_requests car
                JOIN employee_info e3 ON car.requested_id = e3.emp_id
                WHERE CONCAT(e3.first_name,' ',COALESCE(e3.last_name,'')) LIKE ?
                  AND car.status = 'Accepted'
            )
            OR pa.publication_id IN (
                SELECT car2.publication_id FROM co_author_requests car2
                JOIN outside_author oa ON car2.requested_id = oa.os_author_id
                WHERE CONCAT(oa.first_name,' ',COALESCE(oa.last_name,'')) LIKE ?
            )
        )";
        $la = "%$author_f%";
        $params = array_merge($params, [$la, $la, $la]);
    }

    $sql .= " ORDER BY pub_date DESC LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Fetch all authors per publication
    $pubIds = array_column($results, 'publication_id');
    $authorMap = [];
    if (!empty($pubIds)) {
        $ph = implode(',', array_fill(0, count($pubIds), '?'));

        // Main + accepted co-authors (institute)
        $s = $pdo->prepare("
            SELECT pa.publication_id,
                   CONCAT(e.first_name,' ',COALESCE(e.last_name,'')) as name,
                   pa.author_type, d.department_name as dept
            FROM publication_author pa
            JOIN employee_info e ON pa.author_id = e.emp_id
            LEFT JOIN department d ON e.department_id = d.department_id
            WHERE pa.publication_id IN ($ph)
            UNION
            SELECT car.publication_id,
                   CONCAT(e2.first_name,' ',COALESCE(e2.last_name,'')) as name,
                   'Co-Author' as author_type, d2.department_name as dept
            FROM co_author_requests car
            JOIN employee_info e2 ON car.requested_id = e2.emp_id
            LEFT JOIN department d2 ON e2.department_id = d2.department_id
            WHERE car.publication_id IN ($ph) AND car.status='Accepted'
            UNION
            SELECT car2.publication_id,
                   CONCAT(oa.first_name,' ',COALESCE(oa.last_name,'')) as name,
                   'External Co-Author' as author_type, oa.institute_name as dept
            FROM co_author_requests car2
            JOIN outside_author oa ON car2.requested_id = oa.os_author_id
            WHERE car2.publication_id IN ($ph)
        ");
        $s->execute([...$pubIds, ...$pubIds, ...$pubIds]);
        foreach ($s->fetchAll() as $a) {
            $authorMap[$a['publication_id']][] = $a;
        }
    }
}
?>
<style>
.srch-card { background:white; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.08); border-top:6px solid #1d4ed8; overflow:hidden; font-family:'Segoe UI',sans-serif; }
.srch-header { padding:24px 32px 16px; border-bottom:1px solid #f1f5f9; }
.srch-header h2 { margin:0; color:#1d4ed8; font-size:21px; display:flex; align-items:center; gap:10px; }
.srch-header p  { margin:5px 0 0; color:#64748b; font-size:13px; }

/* Search bar */
.srch-main-bar { padding:20px 32px; background:#eff6ff; border-bottom:1px solid #bfdbfe; }
.srch-main-wrap { position:relative; }
.srch-main-wrap input { width:100%; padding:13px 20px 13px 46px; border:2px solid #bfdbfe; border-radius:10px; font-size:15px; box-sizing:border-box; transition:border 0.2s; }
.srch-main-wrap input:focus { outline:none; border-color:#1d4ed8; box-shadow:0 0 0 3px rgba(29,78,216,0.1); }
.srch-main-wrap .sico { position:absolute; left:15px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:16px; pointer-events:none; }
.srch-main-wrap .btn-go { position:absolute; right:8px; top:50%; transform:translateY(-50%); background:#1d4ed8; color:white; border:none; padding:8px 20px; border-radius:7px; font-weight:600; font-size:13px; cursor:pointer; }
.srch-main-wrap .btn-go:hover { background:#1e40af; }

/* Filters */
.srch-filters { padding:16px 32px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:grid; grid-template-columns:repeat(4,1fr) auto; gap:12px; align-items:flex-end; }
.sf-label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:5px; }
.srch-filters input, .srch-filters select { width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; box-sizing:border-box; }
.srch-filters input:focus, .srch-filters select:focus { outline:none; border-color:#1d4ed8; }
.btn-srch-clear { background:white; color:#64748b; border:1.5px solid #e2e8f0; padding:9px 18px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap; }
.btn-srch-clear:hover { border-color:#94a3b8; }

/* Results */
.srch-results { padding:24px 32px; }
.srch-results-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
.srch-results-header h3 { margin:0; font-size:15px; color:#1e293b; }
.srch-count { background:#dbeafe; color:#1d4ed8; font-size:12px; font-weight:700; padding:3px 10px; border-radius:20px; }

.srch-result-item { border:1.5px solid #e2e8f0; border-radius:10px; padding:18px 20px; margin-bottom:12px; transition:all 0.15s; }
.srch-result-item:hover { border-color:#1d4ed8; box-shadow:0 2px 8px rgba(29,78,216,0.08); }
.sri-top { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; }
.sri-title { font-size:15px; font-weight:600; color:#1e293b; flex:1; }
.sri-badges { display:flex; gap:6px; flex-shrink:0; }
.type-pill-s { font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; }
.tps-Journal    { background:#ede9fe; color:#5b21b6; }
.tps-Conference { background:#dbeafe; color:#1d4ed8; }
.tps-Newspaper  { background:#fef9c3; color:#854d0e; }
.tps-Book       { background:#dcfce7; color:#166534; }
.vbadge-v { background:#dcfce7; color:#166534; font-size:11px; padding:3px 8px; border-radius:20px; font-weight:600; }
.vbadge-p { background:#fff7ed; color:#9a3412; font-size:11px; padding:3px 8px; border-radius:20px; font-weight:600; }

.sri-meta { display:flex; gap:18px; margin:8px 0; flex-wrap:wrap; }
.sri-meta span { font-size:12px; color:#64748b; display:flex; align-items:center; gap:5px; }
.sri-meta i { font-size:10px; }

.sri-authors { margin-top:10px; border-top:1px dashed #e2e8f0; padding-top:10px; }
.sri-authors-label { font-size:11px; font-weight:700; text-transform:uppercase; color:#94a3b8; letter-spacing:0.4px; margin-bottom:6px; }
.author-tag { display:inline-flex; align-items:center; gap:5px; background:#f1f5f9; color:#475569; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:500; margin:2px; }
.author-tag.main    { background:#ede9fe; color:#5b21b6; }
.author-tag.coauth  { background:#e0f2fe; color:#0369a1; }
.author-tag.ext     { background:#fef9c3; color:#854d0e; }

.srch-empty { text-align:center; padding:60px 20px; color:#94a3b8; }
.srch-empty i { font-size:36px; display:block; margin-bottom:10px; opacity:0.35; }
.srch-hint  { text-align:center; padding:60px 20px; color:#94a3b8; }
.srch-hint  i { font-size:40px; display:block; margin-bottom:12px; opacity:0.3; }
.srch-hint p { font-size:14px; max-width:300px; margin:0 auto; line-height:1.6; }
</style>

<div class="srch-card">
    <div class="srch-header">
        <h2><i class="fa fa-magnifying-glass"></i> Global Publication Search</h2>
        <p>Search across all publications from all authors — institute faculty, co-authors, and external collaborators.</p>
    </div>

    <form method="GET" id="searchForm">
        <input type="hidden" name="page" value="search">

        <!-- Main search bar -->
        <div class="srch-main-bar">
            <div class="srch-main-wrap">
                <i class="fa fa-magnifying-glass sico"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by title, journal name, conference, DOI..." autofocus>
                <button type="submit" class="btn-go">Search</button>
            </div>
        </div>

        <!-- Advanced filters -->
        <div class="srch-filters">
            <div>
                <label class="sf-label">Author Name</label>
                <input type="text" name="author" value="<?= htmlspecialchars($author_f) ?>" placeholder="e.g. Arjun Das">
            </div>
            <div>
                <label class="sf-label">Department</label>
                <select name="dept">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['department_id'] ?>" <?= $dept_f===$d['department_id']?'selected':'' ?>><?= htmlspecialchars($d['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="sf-label">Type</label>
                <select name="type">
                    <option value="">All Types</option>
                    <option value="Journal"    <?= $type_f==='Journal'   ?'selected':'' ?>>Journal</option>
                    <option value="Conference" <?= $type_f==='Conference'?'selected':'' ?>>Conference</option>
                    <option value="Newspaper"  <?= $type_f==='News Letter' ?'selected':'' ?>>Newspaper</option>
                    <option value="Book Chapter" <?= $type_f==='Book Chapter'?'selected':'' ?>>Book Chapter</option>
                </select>
            </div>
            <div>
                <label class="sf-label">Date Range</label>
                <div style="display:flex;gap:6px;">
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" placeholder="From">
                    <input type="date" name="date_to"   value="<?= htmlspecialchars($date_to) ?>"   placeholder="To">
                </div>
            </div>
            <div>
                <label class="sf-label">Status</label>
                <select name="verified">
                    <option value="">Any</option>
                    <option value="1" <?= $verified_f==='1'?'selected':'' ?>>Verified</option>
                    <option value="0" <?= $verified_f==='0'?'selected':'' ?>>Pending</option>
                </select>
            </div>
            <a href="dashboard.php?page=search" class="btn-srch-clear"><i class="fa fa-rotate-left"></i> Clear</a>
        </div>
    </form>

    <div class="srch-results">
        <?php if (!$searched): ?>
        <div class="srch-hint">
            <i class="fa fa-globe"></i>
            <p>Use the search bar and filters above to find publications from any author across the entire system.</p>
        </div>

        <?php elseif (empty($results)): ?>
        <div class="srch-empty">
            <i class="fa fa-folder-open"></i>
            <p>No publications matched your search. Try different keywords or remove some filters.</p>
        </div>

        <?php else: ?>
        <div class="srch-results-header">
            <h3>Search Results</h3>
            <span class="srch-count"><?= count($results) ?> result<?= count($results)!=1?'s':'' ?></span>
        </div>

        <?php foreach ($results as $row): ?>
        <?php
            $title   = htmlspecialchars($row['title'] ?? '—');
            $venue   = htmlspecialchars($row['venue'] ?? '');
            $doi     = htmlspecialchars($row['doi']   ?? '');
            $date    = $row['pub_date'] ? date('d M Y', strtotime($row['pub_date'])) : '—';
            $ptype   = $row['publication_type'];
            $authors = $authorMap[$row['publication_id']] ?? [];
        ?>
        <div class="srch-result-item">
            <div class="sri-top">
                <div class="sri-title"><?= $title ?></div>
                <div class="sri-badges">
                    <span class="type-pill-s tps-<?= $ptype ?>"><?= $ptype ?></span>
                    <span class="<?= $row['verification_flag'] ? 'vbadge-v' : 'vbadge-p' ?>"><?= $row['verification_flag'] ? 'Verified' : 'Pending' ?></span>
                </div>
            </div>
            <div class="sri-meta">
                <?php if ($venue): ?><span><i class="fa fa-building"></i> <?= $venue ?></span><?php endif; ?>
                <span><i class="fa fa-calendar"></i> <?= $date ?></span>
                <?php if ($doi): ?><span><i class="fa fa-link"></i> <?= $doi ?></span><?php endif; ?>
                <?php if ($row['department_name']): ?><span><i class="fa fa-sitemap"></i> <?= htmlspecialchars($row['department_name']) ?></span><?php endif; ?>
            </div>
            <?php if (!empty($authors)): ?>
            <div class="sri-authors">
                <div class="sri-authors-label">Authors</div>
                <?php foreach ($authors as $a): ?>
                <?php
                    $cls = 'coauth';
                    if (str_contains($a['author_type'], 'Main')) $cls = 'main';
                    elseif (str_contains($a['author_type'], 'External')) $cls = 'ext';
                ?>
                <span class="author-tag <?= $cls ?>">
                    <i class="fa fa-user" style="font-size:9px;"></i>
                    <?= htmlspecialchars(trim($a['name'])) ?>
                    <?php if ($a['dept']): ?><span style="opacity:0.6;font-size:10px;">(<?= htmlspecialchars($a['dept']) ?>)</span><?php endif; ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>