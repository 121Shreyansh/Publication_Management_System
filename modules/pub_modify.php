<?php
// pub_modify.php
if (!isset($pdo)) { require_once __DIR__ . '/../config/db.php'; }
$user_id = $_SESSION['user_id'];
$msg = "";

// UPDATE LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_pub'])) {
    $pub_id = $_POST['pub_id'];
    $type   = $_POST['pub_type'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE publication_author SET author_type=?, publication_status=? WHERE publication_id=? AND author_id=?");
        $stmt->execute([$_POST['author_type'] ?: 'Corresponding', $_POST['status'], $pub_id, $user_id]);

        if ($type == 'Journal') {
            $stmt = $pdo->prepare("UPDATE journal_publication_details SET journal_publication_title=?,journal_title=?,journal_publication_date=?,journal_publisher=?,publication_volume_no=?,publication_issue_no=?,published_city=?,published_country=?,publication_doi=?,first_page_no=?,last_page_no=? WHERE journal_publication_id=?");
            $stmt->execute([$_POST['title'],$_POST['venue'],$_POST['pub_date'],$_POST['publisher'],$_POST['volume'],$_POST['issue'],$_POST['city'],$_POST['country'],$_POST['doi'],$_POST['f_page']?:null,$_POST['l_page']?:null,$pub_id]);
        } elseif ($type == 'Conference') {
            $stmt = $pdo->prepare("UPDATE conference_publication_details SET conference_publication_title=?,conference_title=?,conference_start_date=?,conference_end_date=?,conference_city=?,conference_country=?,conference_publisher=?,publication_doi=?,conference_location=? WHERE conference_publication_id=?");
            $stmt->execute([$_POST['title'],$_POST['venue'],$_POST['pub_date'],$_POST['end_date'],$_POST['city'],$_POST['country'],$_POST['publisher'],$_POST['doi'],$_POST['location'],$pub_id]);
        }
        $pdo->commit();
        header("Location: dashboard.php?page=modify&updated=1");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "<div style='background:#fee2e2;color:#991b1b;padding:14px;border-radius:8px;border:1px solid #fca5a5;margin-bottom:20px;'><i class='fa fa-circle-exclamation'></i> " . $e->getMessage() . "</div>";
    }
}

// FILTER & LIST
$type_f   = $_GET['type']   ?? 'All';
$search_f = $_GET['search'] ?? '';

$query = "SELECT pa.publication_id, pa.publication_type, pa.verification_flag,
          j.journal_publication_title as j_t, j.journal_publication_date as j_date, j.publication_volume_no as j_vol, j.publication_issue_no as j_iss,
          c.conference_publication_title as c_t, c.conference_start_date as c_date
          FROM publication_author pa
          LEFT JOIN journal_publication_details j ON pa.publication_id = j.journal_publication_id
          LEFT JOIN conference_publication_details c ON pa.publication_id = c.conference_publication_id
          WHERE pa.author_id = :auth_id";
$params = [':auth_id' => $user_id];
if ($type_f !== 'All') { $query .= " AND pa.publication_type = :p_type"; $params[':p_type'] = $type_f; }
if (!empty($search_f)) { $query .= " AND (j.journal_publication_title LIKE :search OR c.conference_publication_title LIKE :search)"; $params[':search'] = "%$search_f%"; }
$stmt = $pdo->prepare($query . " ORDER BY pa.publication_id DESC");
$stmt->execute($params);
$results = $stmt->fetchAll();
?>
<style>
    .mod-card { background:white; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.08); border-top:6px solid #7c3aed; font-family:'Segoe UI',sans-serif; overflow:hidden; }
    .mod-header { padding:28px 35px 20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; }
    .mod-header h2 { margin:0; color:#7c3aed; font-size:22px; }
    .mod-header p  { margin:5px 0 0; color:#64748b; font-size:13px; }

    .mod-filter { background:#faf5ff; padding:22px 35px; border-bottom:1px solid #ede9fe; }
    .mod-filter-grid { display:grid; grid-template-columns:2fr 1fr auto; gap:14px; align-items:flex-end; }
    .mod-filter label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:5px; }
    .mod-filter input, .mod-filter select { width:100%; padding:10px 13px; border:1.5px solid #ddd6fe; border-radius:8px; font-size:14px; box-sizing:border-box; }
    .mod-filter input:focus, .mod-filter select:focus { outline:none; border-color:#7c3aed; }
    .btn-mod-filter { background:#7c3aed; color:white; border:none; padding:10px 22px; border-radius:8px; font-weight:600; cursor:pointer; height:42px; font-size:14px; white-space:nowrap; }
    .btn-mod-filter:hover { background:#6d28d9; }

    .mod-table { width:100%; border-collapse:collapse; }
    .mod-table thead tr { background:#7c3aed; color:white; }
    .mod-table thead th { padding:13px 20px; text-align:left; font-size:13px; font-weight:600; }
    .mod-table tbody tr { border-bottom:1px solid #f1f5f9; transition:background 0.15s; }
    .mod-table tbody tr:hover { background:#faf5ff; }
    .mod-table tbody td { padding:14px 20px; font-size:14px; color:#334155; vertical-align:middle; }

    .type-pill { font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; }
    .type-Journal    { background:#ede9fe; color:#5b21b6; }
    .type-Conference { background:#dbeafe; color:#1d4ed8; }
    .type-Newspaper  { background:#fef9c3; color:#854d0e; }
    .type-Book       { background:#dcfce7; color:#166534; }

    .badge-verified { background:#dcfce7; color:#166534; font-size:11px; padding:3px 8px; border-radius:20px; font-weight:600; }
    .badge-pending  { background:#fff7ed; color:#9a3412; font-size:11px; padding:3px 8px; border-radius:20px; font-weight:600; }

    .btn-mod-edit { color:#7c3aed; font-weight:700; text-decoration:none; border:1.5px solid #ddd6fe; padding:6px 14px; border-radius:6px; font-size:13px; transition:all 0.2s; display:inline-flex; align-items:center; gap:6px; }
    .btn-mod-edit:hover { background:#7c3aed; color:white; border-color:#7c3aed; }

    .mod-empty { text-align:center; padding:60px 20px; color:#94a3b8; }
    .mod-empty i { font-size:36px; display:block; margin-bottom:10px; opacity:0.4; }

    /* Edit form */
    .edit-card { background:white; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.08); border-top:6px solid #7c3aed; overflow:hidden; }
    .edit-header { padding:24px 35px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:14px; }
    .edit-header h2 { margin:0; color:#7c3aed; font-size:20px; }
    .edit-body { padding:28px 35px; }
    .edit-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
    .edit-col-full { grid-column:span 4; }
    .edit-col-half { grid-column:span 2; }
    .edit-col-3    { grid-column:span 3; }
    .edit-label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:5px; }
    .edit-body input, .edit-body select { width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; color:#1e293b; box-sizing:border-box; transition:border 0.2s; }
    .edit-body input:focus, .edit-body select:focus { outline:none; border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,0.07); }
    .edit-footer { padding:20px 35px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
    .btn-update { background:#7c3aed; color:white; border:none; padding:12px 32px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:8px; }
    .btn-update:hover { background:#6d28d9; }
    .btn-cancel-edit { background:white; color:#64748b; border:1.5px solid #e2e8f0; padding:12px 24px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .btn-cancel-edit:hover { border-color:#94a3b8; }
</style>

<?php if (isset($_GET['updated'])): ?>
<div style="background:#d1fae5;color:#065f46;padding:14px 18px;border-radius:8px;border:1px solid #34d399;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:13px;" id="upd-msg">
    <i class="fa fa-check-circle"></i> Publication updated successfully!
</div>
<script>setTimeout(()=>{ let m=document.getElementById('upd-msg'); if(m) m.style.display='none'; },4000);</script>
<?php endif; ?>

<?php if (!isset($_GET['action']) || $_GET['action'] !== 'edit'): ?>
<!-- ── LIST VIEW ── -->
<div class="mod-card">
    <div class="mod-header">
        <div>
            <h2><i class="fa fa-pen" style="margin-right:8px;"></i>Modify Publications</h2>
            <p>Search and select a publication to edit its details.</p>
        </div>
        <span style="font-size:13px;color:#94a3b8;"><?= count($results) ?> record<?= count($results)!=1?'s':'' ?> found</span>
    </div>

    <div class="mod-filter">
        <form method="GET" id="modFilterForm">
            <input type="hidden" name="page" value="modify">
            <div class="mod-filter-grid">
                <div>
                    <label>Search Title</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search_f) ?>" placeholder="Type to search...">
                </div>
                <div>
                    <label>Publication Type</label>
                    <select name="type" onchange="this.form.submit()">
                        <option value="All">All Types</option>
                        <option value="Journal"    <?= $type_f=='Journal'   ?'selected':'' ?>>Journal</option>
                        <option value="Conference" <?= $type_f=='Conference'?'selected':'' ?>>Conference</option>
                        <option value="Newspaper"  <?= $type_f=='News Letter' ?'selected':'' ?>>Newspaper</option>
                    </select>
                </div>
                <button type="submit" class="btn-mod-filter"><i class="fa fa-magnifying-glass"></i> Search</button>
            </div>
        </form>
    </div>

    <?= $msg ?>

    <?php if (count($results) > 0): ?>
    <table class="mod-table">
        <thead>
            <tr>
                <th style="width:50%">Title</th>
                <th>Type</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
            <?php $title = htmlspecialchars($row['j_t'] ?? $row['c_t'] ?? '—'); ?>
            <?php $date  = $row['j_date'] ?? $row['c_date'] ?? null; ?>
            <tr>
                <td style="font-weight:500;"><?= $title ?></td>
                <td><span class="type-pill type-<?= $row['publication_type'] ?>"><?= $row['publication_type'] ?></span></td>
                <td style="color:#64748b;"><?= $date ? date('d M Y', strtotime($date)) : '—' ?></td>
                <td><span class="<?= $row['verification_flag'] ? 'badge-verified' : 'badge-pending' ?>"><?= $row['verification_flag'] ? 'Verified' : 'Pending' ?></span></td>
                <td><a href="?page=modify&action=edit&id=<?= $row['publication_id'] ?>" class="btn-mod-edit"><i class="fa fa-pen-to-square"></i> Edit</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="mod-empty">
        <i class="fa fa-folder-open"></i>
        <p>No publications found<?= !empty($search_f) ? " matching \"".htmlspecialchars($search_f)."\"" : "" ?>.</p>
    </div>
    <?php endif; ?>
</div>

<?php else:
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT pa.*, j.*, c.* FROM publication_author pa
        LEFT JOIN journal_publication_details j ON pa.publication_id = j.journal_publication_id
        LEFT JOIN conference_publication_details c ON pa.publication_id = c.conference_publication_id
        WHERE pa.publication_id = ? AND pa.author_id = ?");
    $stmt->execute([$id, $user_id]);
    $d = $stmt->fetch();
    if (!$d) { echo "<div class='mod-empty'><i class='fa fa-ban'></i><p>Record not found or access denied.</p></div>"; return; }
    $pubType = $d['publication_type'];
?>
<!-- ── EDIT FORM ── -->
<div class="edit-card">
    <div class="edit-header">
        <a href="?page=modify" class="btn-cancel-edit" style="padding:8px 14px;"><i class="fa fa-arrow-left"></i></a>
        <div>
            <h2>Edit <?= htmlspecialchars($pubType) ?> Publication</h2>
            <p style="margin:3px 0 0;color:#64748b;font-size:13px;">ID #<?= $id ?> &nbsp;·&nbsp; Changes are saved immediately.</p>
        </div>
    </div>

    <?= $msg ?>

    <form method="POST">
        <input type="hidden" name="pub_id"   value="<?= $id ?>">
        <input type="hidden" name="pub_type" value="<?= htmlspecialchars($pubType) ?>">

        <div class="edit-body">
            <div class="edit-grid">
                <div class="edit-col-3">
                    <label class="edit-label">Publication Title <span style="color:#dc2626">*</span></label>
                    <input type="text" name="title" value="<?= htmlspecialchars($d['title'] ?? $d['conference_publication_title'] ?? '') ?>" required>
                </div>
                <div>
                    <label class="edit-label">Author Role</label>
                    <select name="author_type">
                        <option value="Corresponding" <?= ($d['author_type']??'')==='Corresponding'?'selected':'' ?>>Corresponding</option>
                        <option value="Supervisor"    <?= ($d['author_type']??'')==='Supervisor'   ?'selected':'' ?>>Supervisor</option>
                        <option value="Other"         <?= ($d['author_type']??'')==='Other'        ?'selected':'' ?>>Other</option>
                    </select>
                </div>
                <div class="edit-col-half">
                    <label class="edit-label">Venue / <?= $pubType==='Journal'?'Journal':'Conference' ?> Name</label>
                    <input type="text" name="venue" value="<?= htmlspecialchars($d['journal_title'] ?? $d['conference_title'] ?? '') ?>">
                </div>
                <div>
                    <label class="edit-label">Start Date</label>
                    <input type="date" name="pub_date" value="<?= $d['journal_publication_date'] ?? $d['conference_start_date'] ?? '' ?>">
                </div>
                <div>
                    <label class="edit-label">Geographic Scope</label>
                    <select name="status">
                        <option value="National"      <?= ($d['publication_status']??'')==='National'     ?'selected':'' ?>>National</option>
                        <option value="International" <?= ($d['publication_status']??'')==='International'?'selected':'' ?>>International</option>
                    </select>
                </div>
                <div class="edit-col-half">
                    <label class="edit-label">Publisher</label>
                    <input type="text" name="publisher" value="<?= htmlspecialchars($d['journal_publisher'] ?? $d['conference_publisher'] ?? '') ?>">
                </div>
                <div class="edit-col-half">
                    <label class="edit-label">DOI</label>
                    <input type="text" name="doi" value="<?= htmlspecialchars($d['publication_doi'] ?? '') ?>">
                </div>

                <?php if ($pubType === 'Journal'): ?>
                <div>
                    <label class="edit-label">Volume</label>
                    <input type="text" name="volume" value="<?= htmlspecialchars($d['publication_volume_no'] ?? '') ?>">
                </div>
                <div>
                    <label class="edit-label">Issue</label>
                    <input type="text" name="issue" value="<?= htmlspecialchars($d['publication_issue_no'] ?? '') ?>">
                </div>
                <div>
                    <label class="edit-label">First Page</label>
                    <input type="number" name="f_page" value="<?= $d['first_page_no'] ?? '' ?>">
                </div>
                <div>
                    <label class="edit-label">Last Page</label>
                    <input type="number" name="l_page" value="<?= $d['last_page_no'] ?? '' ?>">
                </div>
                <div class="edit-col-half">
                    <label class="edit-label">City</label>
                    <input type="text" name="city" value="<?= htmlspecialchars($d['published_city'] ?? '') ?>">
                </div>
                <div class="edit-col-half">
                    <label class="edit-label">Country</label>
                    <input type="text" name="country" value="<?= htmlspecialchars($d['published_country'] ?? '') ?>">
                </div>

                <?php elseif ($pubType === 'Conference'): ?>
                <div>
                    <label class="edit-label">End Date</label>
                    <input type="date" name="end_date" value="<?= $d['conference_end_date'] ?? '' ?>">
                </div>
                <div>
                    <label class="edit-label">City</label>
                    <input type="text" name="city" value="<?= htmlspecialchars($d['conference_city'] ?? '') ?>">
                </div>
                <div>
                    <label class="edit-label">Country</label>
                    <input type="text" name="country" value="<?= htmlspecialchars($d['conference_country'] ?? '') ?>">
                </div>
                <div>
                    <label class="edit-label">Location / Venue</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($d['conference_location'] ?? '') ?>">
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="edit-footer">
            <a href="?page=modify" class="btn-cancel-edit"><i class="fa fa-xmark"></i> Cancel</a>
            <button type="submit" name="update_pub" class="btn-update"><i class="fa fa-floppy-disk"></i> Save Changes</button>
        </div>
    </form>
</div>
<?php endif; ?>