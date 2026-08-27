<?php
// pub_export.php
if (!isset($pdo)) { require_once __DIR__ . '/../config/db.php'; }
$user_id  = $_SESSION['user_id'];

// Fetch full name
$stmt = $pdo->prepare("SELECT CONCAT(first_name,' ',COALESCE(last_name,'')) as name FROM employee_info WHERE emp_id=?");
$stmt->execute([$user_id]);
$meRow    = $stmt->fetch();
$fullName = trim($meRow['name'] ?? $_SESSION['user_id']);

// Filters
$type_f   = $_GET['type']   ?? 'All';
$search_f = $_GET['search'] ?? '';

$sql = "SELECT pa.publication_id, pa.publication_type, pa.verification_flag,
        j.journal_publication_title as j_t,   j.journal_publication_date as j_date,
        j.journal_title as j_v,   j.publication_volume_no as j_vol,
        j.publication_issue_no as j_iss, j.journal_publisher as j_pub,
        j.published_city as j_city, j.published_country    as j_country,
        j.publication_doi as j_doi, j.first_page_no as j_fp, j.last_page_no as j_lp,
        c.conference_publication_title as c_t,
        c.conference_start_date        as c_date,
        c.conference_title             as c_v,
        c.conference_publisher         as c_pub,
        c.conference_city              as c_city,
        c.conference_country           as c_country,
        c.publication_doi              as c_doi
        FROM publication_author pa
        LEFT JOIN journal_publication_details    j ON pa.publication_id = j.journal_publication_id
        LEFT JOIN conference_publication_details c ON pa.publication_id = c.conference_publication_id
        WHERE pa.author_id = :uid";
$params = [':uid' => $user_id];
if ($type_f !== 'All') { $sql .= " AND pa.publication_type = :t"; $params[':t'] = $type_f; }
if (!empty($search_f)) { $sql .= " AND (j.journal_publication_title LIKE :s OR c.conference_publication_title LIKE :s)"; $params[':s'] = "%$search_f%"; }
$stmt = $pdo->prepare($sql . " ORDER BY pa.publication_id DESC");
$stmt->execute($params);
$results = $stmt->fetchAll();

// All available fields with labels
$allFields = [
    'title'       => 'Title',
    'authors'     => 'Authors',
    'venue'       => 'Journal / Conference',
    'pub_date'    => 'Publication Date',
    'pub_type'    => 'Publication Type',
    'doi'         => 'DOI',
    'publisher'   => 'Publisher',
    'vol_issue'   => 'Volume / Issue',
    'pages'       => 'Page Numbers',
    'location'    => 'City / Country',
    'status'      => 'Verification Status',
];
?>
<style>
.exp-card { background:white; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.08); border-top:6px solid #0f766e; overflow:hidden; font-family:'Segoe UI',sans-serif; }
.exp-header { padding:26px 32px 18px; border-bottom:1px solid #f1f5f9; }
.exp-header h2 { margin:0; color:#0f766e; font-size:21px; display:flex; align-items:center; gap:10px; }
.exp-header p  { margin:5px 0 0; color:#64748b; font-size:13px; }

/* Steps */
.exp-steps { display:flex; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
.exp-step { flex:1; padding:14px 20px; display:flex; align-items:center; gap:10px; font-size:13px; color:#94a3b8; border-bottom:3px solid transparent; }
.exp-step .step-num { width:26px; height:26px; border-radius:50%; background:#e2e8f0; color:#64748b; font-weight:700; font-size:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.exp-step.done .step-num  { background:#0f766e; color:white; }
.exp-step.done            { color:#0f766e; border-bottom-color:#0f766e; }

/* Filter bar */
.exp-filter { padding:18px 32px; background:#f0fdfa; border-bottom:1px solid #ccfbf1; display:grid; grid-template-columns:2fr 1fr auto; gap:14px; align-items:flex-end; }
.exp-filter label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:5px; }
.exp-filter input, .exp-filter select { width:100%; padding:9px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box; }
.exp-filter input:focus, .exp-filter select:focus { outline:none; border-color:#0f766e; }
.btn-filter { background:#475569; color:white; border:none; padding:9px 20px; border-radius:8px; font-weight:600; cursor:pointer; height:40px; font-size:13px; }

.exp-body { padding:24px 32px; }

/* Step 1: Fields */
.step-block { margin-bottom:28px; }
.step-title { font-size:13px; font-weight:700; color:#0f766e; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.step-title .snum { background:#0f766e; color:white; width:22px; height:22px; border-radius:50%; font-size:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }

.fields-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
.field-chk { display:none; }
.field-lbl { display:flex; align-items:center; gap:8px; padding:9px 14px; border:1.5px solid #e2e8f0; border-radius:8px; cursor:pointer; font-size:13px; font-weight:500; color:#475569; transition:all 0.15s; user-select:none; }
.field-lbl:hover { border-color:#0f766e; color:#0f766e; }
.field-chk:checked + .field-lbl { background:#f0fdfa; border-color:#0f766e; color:#0f766e; font-weight:600; }
.field-chk:checked + .field-lbl i { color:#0f766e; }
.field-lbl i { font-size:12px; color:#94a3b8; }
.field-select-all { font-size:12px; color:#0f766e; font-weight:600; cursor:pointer; text-decoration:underline; margin-left:auto; }

/* Step 2: Formats */
.formats-grid { display:flex; gap:12px; flex-wrap:wrap; }
.fmt-chk { display:none; }
.fmt-lbl { display:flex; flex-direction:column; align-items:center; gap:6px; padding:14px 22px; border:2px solid #e2e8f0; border-radius:10px; cursor:pointer; font-size:13px; font-weight:600; color:#475569; transition:all 0.2s; min-width:90px; text-align:center; user-select:none; }
.fmt-lbl i { font-size:22px; }
.fmt-lbl:hover { border-color:#0f766e; }
.fmt-chk:checked + .fmt-lbl { border-color:#0f766e; background:#f0fdfa; color:#0f766e; }
.fmt-xl  i { color:#217346; }
.fmt-csv i { color:#0284c7; }
.fmt-pdf i { color:#dc2626; }
.fmt-doc i { color:#2563eb; }
.fmt-chk:checked + .fmt-xl  .fmt-lbl { border-color:#217346; }

/* Step 3: Publication list */
.pub-list-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
.pub-list-header .sel-all-wrap { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:#475569; cursor:pointer; }
.pub-list-header .sel-count { font-size:13px; color:#94a3b8; }

.pub-exp-row { display:flex; align-items:center; gap:14px; padding:12px 16px; border:1.5px solid #e2e8f0; border-radius:8px; margin-bottom:8px; cursor:pointer; transition:all 0.15s; }
.pub-exp-row:hover { border-color:#0f766e; background:#f0fdfa; }
.pub-exp-row.selected { border-color:#0f766e; background:#f0fdfa; }
.pub-exp-row input[type=checkbox] { width:16px; height:16px; accent-color:#0f766e; flex-shrink:0; cursor:pointer; }
.pub-exp-info { flex:1; }
.pub-exp-title { font-size:14px; font-weight:600; color:#1e293b; }
.pub-exp-meta  { font-size:12px; color:#94a3b8; margin-top:3px; display:flex; gap:12px; }
.type-pill-exp { font-size:11px; font-weight:700; padding:2px 8px; border-radius:20px; margin-left:8px; }
.tp-e-Journal    { background:#ede9fe; color:#5b21b6; }
.tp-e-Conference { background:#dbeafe; color:#1d4ed8; }
.tp-e-Newspaper  { background:#fef9c3; color:#854d0e; }
.tp-e-Book       { background:#dcfce7; color:#166534; }

.exp-empty { text-align:center; padding:40px; color:#94a3b8; }
.exp-empty i { font-size:30px; display:block; margin-bottom:8px; opacity:0.35; }

/* Footer */
.exp-footer { padding:20px 32px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
.btn-exp-dl { background:#0f766e; color:white; border:none; padding:13px 32px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:9px; transition:background 0.2s; }
.btn-exp-dl:hover { background:#0d5c57; }
.btn-exp-dl:disabled { background:#94a3b8; cursor:not-allowed; }
.exp-summary { font-size:13px; color:#64748b; }
.exp-summary strong { color:#0f766e; }
</style>

<div class="exp-card">
    <div class="exp-header">
        <h2><i class="fa fa-file-export"></i> Bulk Export Publications</h2>
        <p>Select publications, choose fields and output formats — then download everything at once.</p>
    </div>

    <!-- Steps indicator -->
    <div class="exp-steps">
        <div class="exp-step done"><div class="step-num">1</div> Choose Fields</div>
        <div class="exp-step done"><div class="step-num">2</div> Choose Formats</div>
        <div class="exp-step done"><div class="step-num">3</div> Select Publications</div>
        <div class="exp-step done"><div class="step-num">4</div> Download</div>
    </div>

    <!-- Filter bar -->
    <form method="GET" id="filterForm">
        <input type="hidden" name="page" value="export">
        <div class="exp-filter">
            <div>
                <label>Search Title</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search_f) ?>" placeholder="Filter publications...">
            </div>
            <div>
                <label>Type</label>
                <select name="type" onchange="this.closest('form').submit()">
                    <option value="All">All Types</option>
                    <option value="Journal"    <?= $type_f=='Journal'   ?'selected':'' ?>>Journal</option>
                    <option value="Conference" <?= $type_f=='Conference'?'selected':'' ?>>Conference</option>
                    <option value="Newspaper"  <?= $type_f=='News Letter' ?'selected':'' ?>>Newspaper</option>
                </select>
            </div>
            <button type="submit" class="btn-filter"><i class="fa fa-magnifying-glass"></i> Filter</button>
        </div>
    </form>

    <form action="modules/process_export.php" method="POST" id="exportForm">
        <div class="exp-body">

            <!-- STEP 1: Fields -->
            <div class="step-block">
                <div class="step-title">
                    <span class="snum">1</span> Select Fields to Export
                    <span class="field-select-all" onclick="toggleAllFields()"  id="fieldToggleLink">Select All</span>
                </div>
                <div class="fields-grid">
                    <?php foreach ($allFields as $val => $label): ?>
                    <div>
                        <input type="checkbox" class="field-chk" name="fields[]" value="<?= $val ?>" id="f-<?= $val ?>"
                            <?= in_array($val, ['title','authors','venue','pub_date','pub_type']) ? 'checked' : '' ?>>
                        <label class="field-lbl" for="f-<?= $val ?>">
                            <i class="fa fa-<?= [
                                'title'=>'heading','authors'=>'users','venue'=>'building',
                                'pub_date'=>'calendar','pub_type'=>'tag','doi'=>'link',
                                'publisher'=>'building-columns','vol_issue'=>'layer-group',
                                'pages'=>'file','location'=>'location-dot','status'=>'circle-check'
                            ][$val] ?? 'circle' ?>"></i>
                            <?= $label ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- STEP 2: Formats (multi-select) -->
            <div class="step-block">
                <div class="step-title"><span class="snum">2</span> Select Export Format(s) <span style="font-size:11px;color:#94a3b8;font-weight:400;text-transform:none;">(choose one or more)</span></div>
                <div class="formats-grid">
                    <div>
                        <input type="checkbox" class="fmt-chk" name="formats[]" value="excel" id="fmt-xl" checked>
                        <label class="fmt-lbl fmt-xl" for="fmt-xl"><i class="fa fa-file-excel"></i> Excel<br><span style="font-size:10px;font-weight:400;">.xlsx</span></label>
                    </div>
                    <div>
                        <input type="checkbox" class="fmt-chk" name="formats[]" value="csv" id="fmt-csv">
                        <label class="fmt-lbl fmt-csv" for="fmt-csv"><i class="fa fa-file-csv"></i> CSV<br><span style="font-size:10px;font-weight:400;">.csv</span></label>
                    </div>
                    <div>
                        <input type="checkbox" class="fmt-chk" name="formats[]" value="pdf" id="fmt-pdf">
                        <label class="fmt-lbl fmt-pdf" for="fmt-pdf"><i class="fa fa-file-pdf"></i> PDF<br><span style="font-size:10px;font-weight:400;">.pdf</span></label>
                    </div>
                    <div>
                        <input type="checkbox" class="fmt-chk" name="formats[]" value="word" id="fmt-doc">
                        <label class="fmt-lbl fmt-doc" for="fmt-doc"><i class="fa fa-file-word"></i> Word<br><span style="font-size:10px;font-weight:400;">.docx</span></label>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Publications -->
            <div class="step-block">
                <div class="step-title"><span class="snum">3</span> Select Publications</div>
                <div class="pub-list-header">
                    <label class="sel-all-wrap">
                        <input type="checkbox" id="selAllPubs" onchange="toggleAllPubs(this)"> Select All (<?= count($results) ?>)
                    </label>
                    <span class="sel-count"><span id="selCount">0</span> selected</span>
                </div>

                <?php if (empty($results)): ?>
                <div class="exp-empty"><i class="fa fa-folder-open"></i><p>No publications found.</p></div>
                <?php else: ?>
                <?php foreach ($results as $row): ?>
                <?php
                    $title   = htmlspecialchars($row['j_t'] ?? $row['c_t'] ?? '—');
                    $venue   = htmlspecialchars($row['j_v'] ?? $row['c_v'] ?? '—');
                    $date    = $row['j_date'] ?? $row['c_date'] ?? null;
                    $dateStr = $date ? date('d M Y', strtotime($date)) : '—';
                    $ptype   = $row['publication_type'];
                ?>
                <div class="pub-exp-row" onclick="toggleRow(this)">
                    <input type="checkbox" name="pub_ids[]" value="<?= $row['publication_id'] ?>" class="pub-chk" onclick="event.stopPropagation(); updateCount();">
                    <div class="pub-exp-info">
                        <div class="pub-exp-title">
                            <?= $title ?>
                            <span class="type-pill-exp tp-e-<?= $ptype ?>"><?= $ptype ?></span>
                        </div>
                        <div class="pub-exp-meta">
                            <span><i class="fa fa-building" style="font-size:10px;"></i> <?= $venue ?></span>
                            <span><i class="fa fa-calendar" style="font-size:10px;"></i> <?= $dateStr ?></span>
                            <?php if ($row['verification_flag']): ?>
                            <span style="color:#16a34a;"><i class="fa fa-check-circle" style="font-size:10px;"></i> Verified</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div><!-- /exp-body -->

        <div class="exp-footer">
            <div class="exp-summary">
                <strong id="summaryCount">0</strong> publication(s) selected &nbsp;·&nbsp;
                <strong id="summaryFormats">Excel</strong>
                &nbsp;·&nbsp;
                <strong id="summaryFields">5</strong> field(s)
            </div>
            <button type="submit" name="execute_export" class="btn-exp-dl" id="btnExport" disabled>
                <i class="fa fa-download"></i> Download Export
            </button>
        </div>

    </form>
</div>

<script>
// Row click toggle
function toggleRow(row) {
    const cb = row.querySelector('.pub-chk');
    cb.checked = !cb.checked;
    row.classList.toggle('selected', cb.checked);
    updateCount();
}

// Select all pubs
function toggleAllPubs(master) {
    document.querySelectorAll('.pub-chk').forEach(cb => {
        cb.checked = master.checked;
        cb.closest('.pub-exp-row').classList.toggle('selected', master.checked);
    });
    updateCount();
}

// Count & summary update
function updateCount() {
    const checked = document.querySelectorAll('.pub-chk:checked').length;
    document.getElementById('selCount').textContent    = checked;
    document.getElementById('summaryCount').textContent = checked;

    const fmts = [...document.querySelectorAll('.fmt-chk:checked')].map(c => c.value.toUpperCase());
    document.getElementById('summaryFormats').textContent = fmts.length ? fmts.join(', ') : 'None';

    const fields = document.querySelectorAll('.field-chk:checked').length;
    document.getElementById('summaryFields').textContent = fields;

    const fmtSelected = document.querySelectorAll('.fmt-chk:checked').length > 0;
    document.getElementById('btnExport').disabled = checked === 0 || !fmtSelected || fields === 0;
}

// Toggle all fields
let allFieldsSelected = false;
function toggleAllFields() {
    allFieldsSelected = !allFieldsSelected;
    document.querySelectorAll('.field-chk').forEach(cb => cb.checked = allFieldsSelected);
    document.getElementById('fieldToggleLink').textContent = allFieldsSelected ? 'Deselect All' : 'Select All';
    updateCount();
}

// Live update on format/field changes
document.querySelectorAll('.fmt-chk, .field-chk').forEach(cb => cb.addEventListener('change', updateCount));

// Init
updateCount();
</script>