<?php
// pub_add.php — Publication Add (new schema)
if (!isset($pdo)) { require_once __DIR__ . '/../config/db.php'; }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pub'])) {
    $type     = $_POST['pub_type'];
    $pdf_path = null;

    if (!empty($_FILES['pdf_file']['name'])) {
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $pdf_path = 'uploads/' . time() . '_' . basename($_FILES['pdf_file']['name']);
        move_uploaded_file($_FILES['pdf_file']['tmp_name'], __DIR__ . '/../' . $pdf_path);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO publication_author
            (author_id, author_type, publication_type, publication_status, verification_flag)
            VALUES (?, 'Main', ?, 'National', 0)");
        $stmt->execute([$_SESSION['user_id'], $type]);
        $pub_id = $pdo->lastInsertId();

        if ($type === 'Journal' || $type === 'News Letter') {
            $stmt = $pdo->prepare("INSERT INTO journal_publication_details
                (journal_publication_id, journal_publication_title, journal_title,
                 journal_publication_date, journal_publisher,
                 publication_volume_no, publication_issue_no,
                 published_city, published_country, publication_doi,
                 first_page_no, last_page_no, pdf_path)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $pub_id,
                $_POST['title'],
                $_POST['venue']     ?: null,
                $_POST['pub_date']  ?: null,
                $_POST['publisher'] ?: null,
                $_POST['volume']    ?: null,
                $_POST['issue']     ?: null,
                $_POST['j_city']    ?: null,
                $_POST['j_country'] ?: null,
                $_POST['doi']       ?: null,
                $_POST['f_page']    ?: null,
                $_POST['l_page']    ?: null,
                $pdf_path
            ]);

        } elseif ($type === 'Conference') {
            $stmt = $pdo->prepare("INSERT INTO conference_publication_details
                (conference_publication_id, conference_publication_title, conference_title,
                 conference_start_date, conference_end_date,
                 conference_city, conference_country, conference_publisher,
                 publication_doi, conference_location,
                 first_page_no, last_page_no, pdf_path)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $pub_id,
                $_POST['title'],
                $_POST['venue']     ?: null,
                $_POST['pub_date']  ?: null,
                $_POST['end_date']  ?: null,
                $_POST['c_city']    ?: null,
                $_POST['c_country'] ?: null,
                $_POST['publisher'] ?: null,
                $_POST['doi']       ?: null,
                $_POST['location']  ?: null,
                $_POST['f_page']    ?: null,
                $_POST['l_page']    ?: null,
                $pdf_path
            ]);

        } elseif ($type === 'Book Chapter') {
            $stmt = $pdo->prepare("INSERT INTO book_chapter_publication_details
                (book_chapter_publication_id, book_chapter_publication_title, book_chapter_title,
                 book_chapter_publication_date, book_chapter_publisher,
                 publication_volume_no, publication_issue_no,
                 published_city, published_country, publication_doi,
                 first_page_no, last_page_no, pdf_path)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $pub_id,
                $_POST['title'],
                $_POST['venue']      ?: null,
                $_POST['pub_date']   ?: null,
                $_POST['publisher']  ?: null,
                $_POST['volume']     ?: null,
                $_POST['issue']      ?: null,
                $_POST['j_city']     ?: null,
                $_POST['j_country']  ?: null,
                $_POST['doi']        ?: null,
                $_POST['f_page']     ?: null,
                $_POST['l_page']     ?: null,
                $pdf_path
            ]);

        } elseif ($type === 'Technical Report') {
            $stmt = $pdo->prepare("INSERT INTO journal_publication_details
                (journal_publication_id, journal_publication_title, journal_title,
                 journal_publication_date, journal_publisher, pdf_path)
                VALUES (?,?,?,?,?,?)");
            $stmt->execute([
                $pub_id,
                $_POST['title'],
                $_POST['venue']     ?: null,
                $_POST['pub_date']  ?: null,
                $_POST['publisher'] ?: null,
                $pdf_path
            ]);
        }

        // Co-authors
        $coAuthors = json_decode($_POST['co_authors_json'] ?? '[]', true);
        foreach ($coAuthors as $ca) {
            if ($ca['source'] === 'institute') {
                $stmt = $pdo->prepare("INSERT INTO co_author_requests
                    (requester_id, requested_id, publication_id, status, requested_at)
                    VALUES (?, ?, ?, 'Pending', NOW())");
                $stmt->execute([$_SESSION['user_id'], $ca['id'], $pub_id]);
            } elseif ($ca['source'] === 'outside') {
                $stmt = $pdo->prepare("INSERT IGNORE INTO co_author_requests
                    (requester_id, requested_id, publication_id, status, requested_at)
                    VALUES (?, ?, ?, 'Accepted', NOW())");
                $stmt->execute([$_SESSION['user_id'], $ca['id'], $pub_id]);
            } elseif ($ca['source'] === 'outside_new') {
                $os_id = 'OS_' . time() . '_' . rand(100, 999);
                $stmt = $pdo->prepare("INSERT INTO outside_author
                    (os_author_id, gender, first_name, last_name, email, institute_name, city, country)
                    VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([
                    $os_id,
                    $ca['gender']    ?? 'Other',
                    $ca['first_name'] ?? '',
                    $ca['last_name']  ?? '',
                    $ca['email']      ?? '',
                    $ca['institute']  ?? '',
                    $ca['city']       ?? '',
                    $ca['country']    ?? ''
                ]);
                $stmt = $pdo->prepare("INSERT IGNORE INTO co_author_requests
                    (requester_id, requested_id, publication_id, status, requested_at)
                    VALUES (?, ?, ?, 'Accepted', NOW())");
                $stmt->execute([$_SESSION['user_id'], $os_id, $pub_id]);
            }
        }

        $pdo->commit();
        $instCount = count(array_filter($coAuthors, fn($c) => $c['source'] === 'institute'));
        $msg = $instCount > 0 ? 'pub_saved_with_notif' : 'pub_saved';
        echo "<script>window.location.href='dashboard.php?page=view&status=$msg';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        $addError = $e->getMessage();
    }
}
?>
<style>
.add-card { background:white; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.08); border-top:6px solid #240046; overflow:hidden; font-family:'Segoe UI',sans-serif; }
.add-header { padding:26px 35px 18px; border-bottom:1px solid #f1f5f9; }
.add-header h2 { margin:0; color:#240046; font-size:21px; display:flex; align-items:center; gap:10px; }
.add-header p  { margin:5px 0 0; color:#64748b; font-size:13px; }
.type-selector { display:flex; gap:12px; padding:20px 35px; background:#f8f7ff; border-bottom:1px solid #ede9fe; flex-wrap:wrap; }
.type-selector input[type="radio"] { display:none; }
.type-pill-lbl { display:flex; align-items:center; gap:8px; padding:10px 20px; border:2px solid #ddd6fe; border-radius:30px; cursor:pointer; font-size:13px; font-weight:600; color:#6d28d9; background:white; transition:all 0.2s; user-select:none; }
.type-selector input[type="radio"]:checked + .type-pill-lbl { background:#240046; border-color:#240046; color:white; box-shadow:0 3px 10px rgba(36,0,70,0.25); }
.type-pill-lbl:hover { border-color:#240046; }
.add-body { padding:26px 35px 10px; }
.pub-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
.pub-c1 { grid-column:span 1; } .pub-c2 { grid-column:span 2; } .pub-c3 { grid-column:span 3; } .pub-c4 { grid-column:span 4; }
.fl { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; margin-bottom:5px; }
.fl .opt { color:#94a3b8; font-weight:400; text-transform:none; font-size:10px; }
.add-body input,.add-body select { width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; color:#1e293b; background:white; transition:border 0.2s; box-sizing:border-box; }
.add-body input:focus,.add-body select:focus { outline:none; border-color:#240046; box-shadow:0 0 0 3px rgba(36,0,70,0.07); }
.dyn-sec { display:none; }
.sec-tag { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#94a3b8; margin:4px 0 -6px; grid-column:span 4; }
.sec-hr  { border:none; border-top:1px dashed #e2e8f0; margin:8px 0 4px; grid-column:span 4; }
.coauth-section { margin:0 35px 20px; border:2px solid #e2e8f0; border-radius:12px; overflow:hidden; transition:border-color 0.2s; }
.coauth-section.active { border-color:#7c3aed; }
.coauth-toggle { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:#f8fafc; cursor:pointer; user-select:none; }
.coauth-toggle-left { display:flex; align-items:center; gap:12px; }
.coauth-toggle-left h4 { margin:0; font-size:14px; color:#334155; font-weight:600; }
.coauth-toggle-left p  { margin:2px 0 0; font-size:12px; color:#94a3b8; }
.coauth-toggle-arrow { color:#94a3b8; transition:transform 0.25s; font-size:14px; }
.coauth-toggle-arrow.open { transform:rotate(180deg); }
.coauth-body { display:none; padding:20px; border-top:1px solid #e2e8f0; }
.coauth-body.open { display:block; }
.coauth-count-row { display:flex; align-items:center; gap:14px; margin-bottom:18px; }
.coauth-count-row label { font-size:13px; font-weight:600; color:#475569; white-space:nowrap; }
.coauth-count-row input[type=number] { width:80px; padding:8px 12px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; }
.source-tabs { display:flex; gap:8px; margin-bottom:16px; }
.src-tab { padding:8px 16px; border:2px solid #e2e8f0; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; color:#64748b; background:white; transition:all 0.2s; display:flex; align-items:center; gap:7px; }
.src-tab.active { border-color:#7c3aed; color:#7c3aed; background:#faf5ff; }
.coauth-search-wrap { position:relative; margin-bottom:12px; }
.coauth-search-wrap input { width:100%; padding:10px 14px 10px 38px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box; }
.coauth-search-wrap input:focus { outline:none; border-color:#7c3aed; }
.coauth-search-wrap .search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:14px; pointer-events:none; }
.coauth-dept-filter { margin-bottom:10px; }
.coauth-dept-filter select { width:100%; padding:9px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; }
.coauth-results { border:1px solid #e2e8f0; border-radius:8px; max-height:220px; overflow-y:auto; display:none; background:white; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
.coauth-result-item { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-bottom:1px solid #f1f5f9; cursor:pointer; transition:background 0.15s; }
.coauth-result-item:last-child { border-bottom:none; }
.coauth-result-item:hover { background:#faf5ff; }
.coauth-result-info .name { font-size:14px; font-weight:600; color:#1e293b; }
.coauth-result-info .sub  { font-size:12px; color:#94a3b8; margin-top:1px; }
.btn-add-coauthor { background:#7c3aed; color:white; border:none; padding:6px 14px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; }
.coauth-no-results { padding:20px; text-align:center; color:#94a3b8; font-size:13px; display:none; }
.coauth-loading { padding:14px; text-align:center; color:#94a3b8; font-size:13px; display:none; }
.new-outside-form { background:#faf5ff; border:1.5px dashed #ddd6fe; border-radius:10px; padding:16px; margin-top:12px; display:none; }
.new-outside-form h5 { margin:0 0 14px; font-size:13px; color:#6d28d9; }
.nof-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
.nof-c2 { grid-column:span 2; } .nof-c3 { grid-column:span 3; }
.nof-label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:4px; }
.new-outside-form input,.new-outside-form select { width:100%; padding:9px 12px; border:1.5px solid #ddd6fe; border-radius:7px; font-size:13px; box-sizing:border-box; }
.btn-add-new-outside { background:#7c3aed; color:white; border:none; padding:9px 20px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; margin-top:12px; display:flex; align-items:center; gap:7px; }
.coauth-selected { margin-top:14px; }
.coauth-selected-title { font-size:12px; font-weight:700; text-transform:uppercase; color:#64748b; letter-spacing:0.5px; margin-bottom:8px; }
.coauth-chip { display:inline-flex; align-items:center; gap:8px; background:#ede9fe; color:#5b21b6; padding:6px 12px; border-radius:20px; font-size:13px; font-weight:600; margin:4px; }
.coauth-chip.outside { background:#dbeafe; color:#1d4ed8; }
.coauth-chip .remove-chip { cursor:pointer; font-size:14px; margin-left:2px; line-height:1; }
.coauth-chip .remove-chip:hover { color:#dc2626; }
.coauth-notif-note { margin-top:12px; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:12px 14px; font-size:12px; color:#92400e; display:none; }
.coauth-notif-note.show { display:flex; align-items:flex-start; gap:8px; }
.add-footer { padding:20px 35px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
.btn-add-submit { background:#240046; color:white; border:none; padding:12px 32px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:8px; }
.btn-add-submit:hover { background:#3c096c; }
.btn-add-reset { background:white; color:#64748b; border:1.5px solid #e2e8f0; padding:12px 24px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; }
.add-hint { text-align:center; padding:50px 20px; color:#94a3b8; }
.add-hint i { font-size:40px; display:block; margin-bottom:12px; opacity:0.4; }
</style>

<div class="add-card">
    <div class="add-header">
        <h2><i class="fa fa-plus-circle"></i> Add New Publication</h2>
        <p>Select a type below. You are automatically listed as the Main Author.</p>
    </div>

    <?php if (!empty($addError)): ?>
    <div style="background:#fee2e2;color:#991b1b;padding:14px 18px;border-radius:8px;margin:16px 35px 0;border:1px solid #fca5a5;font-size:13px;">
        <i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($addError) ?>
    </div>
    <?php endif; ?>

    <div class="type-selector">
        <input type="radio" name="pts" id="t-j" onchange="toggleType('Journal')">
        <label for="t-j" class="type-pill-lbl"><i class="fa fa-newspaper"></i> Journal</label>
        <input type="radio" name="pts" id="t-c" onchange="toggleType('Conference')">
        <label for="t-c" class="type-pill-lbl"><i class="fa fa-users"></i> Conference</label>
        <input type="radio" name="pts" id="t-b" onchange="toggleType('Book Chapter')">
        <label for="t-b" class="type-pill-lbl"><i class="fa fa-book"></i> Book Chapter</label>
        <input type="radio" name="pts" id="t-n" onchange="toggleType('News Letter')">
        <label for="t-n" class="type-pill-lbl"><i class="fa fa-file-lines"></i> News Letter</label>
        <input type="radio" name="pts" id="t-r" onchange="toggleType('Technical Report')">
        <label for="t-r" class="type-pill-lbl"><i class="fa fa-file-alt"></i> Technical Report</label>
    </div>

    <div id="add-hint" class="add-hint">
        <i class="fa fa-hand-pointer"></i>
        <p>Select a publication type above to begin.</p>
    </div>

    <form method="POST" enctype="multipart/form-data" id="mainPubForm" style="display:none;">
        <input type="hidden" name="pub_type"        id="hidden_type">
        <input type="hidden" name="co_authors_json" id="co_authors_json" value="[]">

        <div class="add-body">
            <div class="pub-grid">
                <div class="pub-c3">
                    <label class="fl">Publication Title <span style="color:#dc2626">*</span></label>
                    <input type="text" name="title" required placeholder="Enter full publication title">
                </div>
                <div class="pub-c1">
                    <label class="fl">Your Role</label>
                    <input type="text" value="Main Author" readonly style="background:#f8fafc;color:#94a3b8;cursor:not-allowed;">
                </div>
                <div class="pub-c2">
                    <label class="fl">Publication Date <span style="color:#dc2626">*</span></label>
                    <input type="date" name="pub_date" required>
                </div>
                <div class="pub-c1">
                    <label class="fl">Scope</label>
                    <select name="pub_status">
                        <option value="National">National</option>
                        <option value="International">International</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="pub-c1">
                    <label class="fl">DOI <span class="opt">(optional)</span></label>
                    <input type="text" name="doi" placeholder="10.1109/...">
                </div>

                <!-- JOURNAL / NEWS LETTER -->
                <div id="Journal_fields" class="dyn-sec pub-c4">
                    <div class="pub-grid">
                        <div class="sec-tag">Journal Details</div>
                        <div class="pub-c2"><label class="fl">Journal Name</label><input type="text" name="venue" placeholder="e.g. IEEE Transactions..."></div>
                        <div class="pub-c2"><label class="fl">Publisher</label><input type="text" name="publisher"></div>
                        <div class="pub-c1"><label class="fl">Volume</label><input type="text" name="volume"></div>
                        <div class="pub-c1"><label class="fl">Issue</label><input type="text" name="issue"></div>
                        <div class="pub-c1"><label class="fl">First Page</label><input type="number" name="f_page"></div>
                        <div class="pub-c1"><label class="fl">Last Page</label><input type="number" name="l_page"></div>
                        <div class="pub-c2"><label class="fl">Published City</label><input type="text" name="j_city"></div>
                        <div class="pub-c2"><label class="fl">Published Country</label><input type="text" name="j_country"></div>
                    </div>
                </div>

                <!-- CONFERENCE -->
                <div id="Conference_fields" class="dyn-sec pub-c4">
                    <div class="pub-grid">
                        <div class="sec-tag">Conference Details</div>
                        <div class="pub-c2"><label class="fl">Conference Name</label><input type="text" name="venue"></div>
                        <div class="pub-c1"><label class="fl">End Date</label><input type="date" name="end_date"></div>
                        <div class="pub-c1"><label class="fl">Publisher</label><input type="text" name="publisher"></div>
                        <div class="pub-c1"><label class="fl">City</label><input type="text" name="c_city"></div>
                        <div class="pub-c1"><label class="fl">Country</label><input type="text" name="c_country"></div>
                        <div class="pub-c2"><label class="fl">Venue / Location</label><input type="text" name="location"></div>
                        <div class="pub-c1"><label class="fl">First Page</label><input type="number" name="f_page"></div>
                        <div class="pub-c1"><label class="fl">Last Page</label><input type="number" name="l_page"></div>
                    </div>
                </div>

                <!-- BOOK CHAPTER -->
                <div id="Book Chapter_fields" class="dyn-sec pub-c4">
                    <div class="pub-grid">
                        <div class="sec-tag">Book Chapter Details</div>
                        <div class="pub-c2"><label class="fl">Book Title</label><input type="text" name="venue"></div>
                        <div class="pub-c2"><label class="fl">Publisher</label><input type="text" name="publisher"></div>
                        <div class="pub-c1"><label class="fl">Volume</label><input type="text" name="volume"></div>
                        <div class="pub-c1"><label class="fl">Issue</label><input type="text" name="issue"></div>
                        <div class="pub-c1"><label class="fl">First Page</label><input type="number" name="f_page"></div>
                        <div class="pub-c1"><label class="fl">Last Page</label><input type="number" name="l_page"></div>
                        <div class="pub-c2"><label class="fl">City</label><input type="text" name="j_city"></div>
                        <div class="pub-c2"><label class="fl">Country</label><input type="text" name="j_country"></div>
                    </div>
                </div>

                <!-- NEWS LETTER (same layout as Journal) -->
                <div id="News Letter_fields" class="dyn-sec pub-c4">
                    <div class="pub-grid">
                        <div class="sec-tag">News Letter Details</div>
                        <div class="pub-c2"><label class="fl">Newsletter / Publication Name</label><input type="text" name="venue"></div>
                        <div class="pub-c2"><label class="fl">Publisher</label><input type="text" name="publisher"></div>
                        <div class="pub-c1"><label class="fl">Volume</label><input type="text" name="volume"></div>
                        <div class="pub-c1"><label class="fl">Issue</label><input type="text" name="issue"></div>
                    </div>
                </div>

                <!-- TECHNICAL REPORT -->
                <div id="Technical Report_fields" class="dyn-sec pub-c4">
                    <div class="pub-grid">
                        <div class="sec-tag">Technical Report Details</div>
                        <div class="pub-c2"><label class="fl">Report Series / Journal</label><input type="text" name="venue"></div>
                        <div class="pub-c2"><label class="fl">Publisher / Institution</label><input type="text" name="publisher"></div>
                    </div>
                </div>

                <hr class="sec-hr">
                <div class="pub-c2">
                    <label class="fl">Upload PDF <span class="opt">(optional)</span></label>
                    <input type="file" name="pdf_file" accept=".pdf">
                </div>
            </div>
        </div>

        <!-- CO-AUTHOR SECTION -->
        <div class="coauth-section" id="coauthSection">
            <div class="coauth-toggle" onclick="toggleCoauth()">
                <div class="coauth-toggle-left">
                    <div style="width:38px;height:38px;border-radius:10px;background:#ede9fe;display:flex;align-items:center;justify-content:center;color:#7c3aed;font-size:16px;">
                        <i class="fa fa-user-plus"></i>
                    </div>
                    <div>
                        <h4>Add Co-Authors</h4>
                        <p id="coauth-toggle-sub">Click to add co-authors from your institute or outside</p>
                    </div>
                </div>
                <i class="fa fa-chevron-down coauth-toggle-arrow" id="coauthArrow"></i>
            </div>
            <div class="coauth-body" id="coauthBody">
                <div class="coauth-count-row">
                    <label>Number of co-authors:</label>
                    <input type="number" id="coauth_count" min="1" max="20" placeholder="e.g. 2" oninput="onCountChange(this.value)">
                    <span style="font-size:13px;color:#94a3b8;" id="coauth-count-hint">Enter a number to continue</span>
                </div>
                <div id="coauth-source-area" style="display:none;">
                    <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;">Source:</div>
                    <div class="source-tabs">
                        <div class="src-tab active" id="src-tab-inst" onclick="switchSource('institute')"><i class="fa fa-building-columns"></i> Institute / Faculty</div>
                        <div class="src-tab" id="src-tab-out" onclick="switchSource('outside')"><i class="fa fa-globe"></i> Outside / External</div>
                    </div>
                    <div id="src-institute">
                        <div class="coauth-dept-filter">
                            <select id="dept-filter" onchange="deptFilter(this.value)">
                                <option value="">— Filter by Department (optional) —</option>
                            </select>
                        </div>
                        <div class="coauth-search-wrap">
                            <i class="fa fa-magnifying-glass search-ico"></i>
                            <input type="text" id="inst-search" placeholder="Search by name, email or ID..." oninput="searchInstitute(this.value)">
                        </div>
                        <div class="coauth-loading" id="inst-loading"><i class="fa fa-spinner fa-spin"></i> Searching...</div>
                        <div class="coauth-results" id="inst-results"></div>
                        <div class="coauth-no-results" id="inst-no-results">No faculty found.</div>
                    </div>
                    <div id="src-outside" style="display:none;">
                        <div class="coauth-search-wrap">
                            <i class="fa fa-magnifying-glass search-ico"></i>
                            <input type="text" id="out-search" placeholder="Search existing external authors..." oninput="searchOutside(this.value)">
                        </div>
                        <div class="coauth-loading" id="out-loading"><i class="fa fa-spinner fa-spin"></i> Searching...</div>
                        <div class="coauth-results" id="out-results"></div>
                        <div class="coauth-no-results" id="out-no-results">Not found.</div>
                        <div style="margin-top:10px;">
                            <button type="button" onclick="toggleNewOutside()" style="background:white;color:#7c3aed;border:1.5px dashed #ddd6fe;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;width:100%;display:flex;align-items:center;justify-content:center;gap:8px;">
                                <i class="fa fa-plus"></i> Add New External Author
                            </button>
                        </div>
                        <div class="new-outside-form" id="new-outside-form">
                            <h5><i class="fa fa-user-pen"></i> &nbsp;New External Author</h5>
                            <div class="nof-grid">
                                <div><label class="nof-label">First Name *</label><input type="text" id="nof-fn"></div>
                                <div><label class="nof-label">Last Name</label><input type="text" id="nof-ln"></div>
                                <div><label class="nof-label">Gender *</label><select id="nof-gender"><option value="Other">Other</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
                                <div class="nof-c2"><label class="nof-label">Email *</label><input type="email" id="nof-email"></div>
                                <div><label class="nof-label">Institute *</label><input type="text" id="nof-inst"></div>
                                <div><label class="nof-label">City</label><input type="text" id="nof-city"></div>
                                <div><label class="nof-label">Country</label><input type="text" id="nof-country"></div>
                            </div>
                            <button type="button" class="btn-add-new-outside" onclick="addNewOutside()"><i class="fa fa-user-plus"></i> Add This Author</button>
                        </div>
                    </div>
                    <div class="coauth-notif-note" id="notif-note">
                        <i class="fa fa-bell" style="flex-shrink:0;margin-top:1px;"></i>
                        <div><strong>Notification will be sent.</strong> Institute co-authors will receive a request to verify this publication.</div>
                    </div>
                    <div class="coauth-selected" id="coauth-selected-wrap" style="display:none;">
                        <div class="coauth-selected-title">Selected Co-Authors (<span id="coauth-chip-count">0</span> / <span id="coauth-max">0</span>)</div>
                        <div id="coauth-chips"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="add-footer">
            <button type="button" class="btn-add-reset" onclick="resetForm()"><i class="fa fa-rotate-left"></i> Reset</button>
            <button type="submit" name="submit_pub" class="btn-add-submit"><i class="fa fa-floppy-disk"></i> Save Publication</button>
        </div>
    </form>
</div>

<script>
const selectedCoAuthors = [];
let coauthOpen=false, currentSource='institute', maxCoAuthors=0, deptList=[], instSearchTimer, outSearchTimer;

function toggleType(type) {
    document.getElementById('add-hint').style.display='none';
    document.getElementById('mainPubForm').style.display='block';
    document.getElementById('hidden_type').value=type;
    document.querySelectorAll('.dyn-sec').forEach(s=>s.style.display='none');
    // Journal and News Letter share the same fields section
    const key = (type==='News Letter') ? 'News Letter' : type;
    const t=document.getElementById(key+'_fields');
    if(t) t.style.display='block';
}
function resetForm() {
    document.getElementById('mainPubForm').reset();
    document.querySelectorAll('input[name="pts"]').forEach(r=>r.checked=false);
    document.querySelectorAll('.dyn-sec').forEach(s=>s.style.display='none');
    document.getElementById('mainPubForm').style.display='none';
    document.getElementById('add-hint').style.display='block';
    selectedCoAuthors.length=0;
    document.getElementById('co_authors_json').value='[]';
    renderChips();
    if(coauthOpen) toggleCoauth();
}
function toggleCoauth() {
    coauthOpen=!coauthOpen;
    document.getElementById('coauthBody').classList.toggle('open',coauthOpen);
    document.getElementById('coauthArrow').classList.toggle('open',coauthOpen);
    document.getElementById('coauthSection').classList.toggle('active',coauthOpen);
    if(coauthOpen && deptList.length===0) loadDepts();
}
function onCountChange(val) {
    const n=parseInt(val);
    const area=document.getElementById('coauth-source-area');
    const hint=document.getElementById('coauth-count-hint');
    if(!n||n<1){area.style.display='none';hint.textContent='Enter a number to continue';return;}
    maxCoAuthors=n; document.getElementById('coauth-max').textContent=n;
    hint.textContent=`Add up to ${n} co-author${n>1?'s':''}`;
    area.style.display='block';
}
function switchSource(src) {
    currentSource=src;
    document.getElementById('src-tab-inst').classList.toggle('active',src==='institute');
    document.getElementById('src-tab-out').classList.toggle('active',src==='outside');
    document.getElementById('src-institute').style.display=src==='institute'?'block':'none';
    document.getElementById('src-outside').style.display=src==='outside'?'block':'none';
}
function loadDepts() {
    fetch('ajax_coauthor_search.php?type=departments').then(r=>r.json()).then(data=>{
        deptList=data; const sel=document.getElementById('dept-filter');
        data.forEach(d=>{const o=document.createElement('option');o.value=d.id;o.textContent=d.name;sel.appendChild(o);});
    }).catch(()=>{});
}
function deptFilter(deptId){clearTimeout(instSearchTimer);doInstSearch(document.getElementById('inst-search').value,deptId);}
function searchInstitute(q){clearTimeout(instSearchTimer);if(q.length<2&&!document.getElementById('dept-filter').value){hideResults('inst');return;}instSearchTimer=setTimeout(()=>doInstSearch(q,document.getElementById('dept-filter').value),300);}
function doInstSearch(q,dept){showLoading('inst');let url=`ajax_coauthor_search.php?type=institute&q=${encodeURIComponent(q)}`;if(dept)url+=`&dept=${encodeURIComponent(dept)}`;fetch(url).then(r=>r.json()).then(data=>{hideLoading('inst');renderResults('inst',data,'institute');}).catch(()=>hideLoading('inst'));}
function searchOutside(q){clearTimeout(outSearchTimer);if(q.length<2){hideResults('out');return;}outSearchTimer=setTimeout(()=>{showLoading('out');fetch(`ajax_coauthor_search.php?type=outside&q=${encodeURIComponent(q)}`).then(r=>r.json()).then(data=>{hideLoading('out');renderResults('out',data,'outside');}).catch(()=>hideLoading('out'));},300);}
function renderResults(prefix,data,source){const box=document.getElementById(prefix+'-results');const noRes=document.getElementById(prefix+'-no-results');box.innerHTML='';const already=selectedCoAuthors.map(a=>a.id);const filtered=data.filter(d=>!already.includes(d.id));if(filtered.length===0){box.style.display='none';noRes.style.display='block';return;}noRes.style.display='none';box.style.display='block';filtered.forEach(p=>{const div=document.createElement('div');div.className='coauth-result-item';div.innerHTML=`<div class="coauth-result-info"><div class="name">${escHtml(p.name)}</div><div class="sub">${escHtml(p.role||'')} &nbsp;·&nbsp; ${escHtml(p.dept||'')} &nbsp;·&nbsp; ${escHtml(p.email||'')}</div></div><button type="button" class="btn-add-coauthor" onclick='addCoAuthor(${JSON.stringify(p)},"${source}")'><i class="fa fa-plus"></i> Add</button>`;box.appendChild(div);});}
function addCoAuthor(person,source){if(selectedCoAuthors.length>=maxCoAuthors){alert(`You can only add ${maxCoAuthors} co-author(s).`);return;}if(selectedCoAuthors.find(a=>a.id===person.id))return;selectedCoAuthors.push({...person,source});updateJson();renderChips();document.getElementById(source==='institute'?'inst-search':'out-search').value='';hideResults(source==='institute'?'inst':'out');updateNotifNote();updateToggleSub();}
function removeCoAuthor(id){const idx=selectedCoAuthors.findIndex(a=>a.id===id);if(idx>-1)selectedCoAuthors.splice(idx,1);updateJson();renderChips();updateNotifNote();updateToggleSub();}
function renderChips(){const wrap=document.getElementById('coauth-selected-wrap');const box=document.getElementById('coauth-chips');document.getElementById('coauth-chip-count').textContent=selectedCoAuthors.length;if(selectedCoAuthors.length===0){wrap.style.display='none';return;}wrap.style.display='block';box.innerHTML=selectedCoAuthors.map(a=>`<span class="coauth-chip ${a.source==='outside'||a.source==='outside_new'?'outside':''}"><i class="fa fa-user"></i> ${escHtml(a.name)} <span style="opacity:0.7;font-size:11px;">${escHtml(a.dept||a.institute||'')}</span> <span style="font-size:10px;background:rgba(0,0,0,0.1);padding:2px 6px;border-radius:10px;">${a.source==='institute'?'Institute':'External'}</span> <span class="remove-chip" onclick="removeCoAuthor('${a.id}')">✕</span></span>`).join('');}
function toggleNewOutside(){const f=document.getElementById('new-outside-form');f.style.display=f.style.display==='none'?'block':'none';}
function addNewOutside(){const fn=document.getElementById('nof-fn').value.trim();const ln=document.getElementById('nof-ln').value.trim();const em=document.getElementById('nof-email').value.trim();const inst=document.getElementById('nof-inst').value.trim();if(!fn||!em||!inst){alert('First name, email and institute are required.');return;}if(selectedCoAuthors.length>=maxCoAuthors){alert(`You can only add ${maxCoAuthors} co-author(s).`);return;}const fakeId='NEW_'+Date.now();selectedCoAuthors.push({id:fakeId,name:fn+(ln?' '+ln:''),source:'outside_new',first_name:fn,last_name:ln,email:em,institute:inst,gender:document.getElementById('nof-gender').value,city:document.getElementById('nof-city').value,country:document.getElementById('nof-country').value,dept:inst,role:inst});updateJson();renderChips();updateNotifNote();updateToggleSub();document.getElementById('new-outside-form').style.display='none';['nof-fn','nof-ln','nof-email','nof-inst','nof-city','nof-country'].forEach(id=>document.getElementById(id).value='');}
function updateJson(){document.getElementById('co_authors_json').value=JSON.stringify(selectedCoAuthors);}
function updateNotifNote(){document.getElementById('notif-note').classList.toggle('show',selectedCoAuthors.some(a=>a.source==='institute'));}
function updateToggleSub(){document.getElementById('coauth-toggle-sub').textContent=selectedCoAuthors.length===0?'Click to add co-authors from your institute or outside':`${selectedCoAuthors.length} co-author${selectedCoAuthors.length>1?'s':''} added`;}
function showLoading(p){document.getElementById(p+'-loading').style.display='block';document.getElementById(p+'-results').style.display='none';document.getElementById(p+'-no-results').style.display='none';}
function hideLoading(p){document.getElementById(p+'-loading').style.display='none';}
function hideResults(p){document.getElementById(p+'-results').style.display='none';document.getElementById(p+'-no-results').style.display='none';}
function escHtml(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
</script>
