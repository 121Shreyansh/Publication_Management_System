<?php
// student_publication.php — Student Publication Upload
if (!isset($pdo)) { require_once __DIR__ . '/../config/db.php'; }

$msg = '';
$msg_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_pub'])) {
    $title       = trim($_POST['title'] ?? '');
    $pub_type    = $_POST['pub_type'] ?? 'Journal';
    $venue       = trim($_POST['venue'] ?? '');
    $pub_date    = $_POST['pub_date'] ?? null;
    $doi         = trim($_POST['doi'] ?? '');
    $pdf_path    = null;

    if (empty($title)) {
        $msg = 'Publication title is required.';
        $msg_type = 'error';
    } else {
        // Handle optional PDF
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
                VALUES (?, 'Student Author', ?, 'National', 0)");
            $stmt->execute([$_SESSION['user_id'], $pub_type]);
            $pub_id = $pdo->lastInsertId();

            if ($pub_type === 'Conference') {
                $stmt = $pdo->prepare("INSERT INTO conference_publication_details
                    (conference_publication_id, conference_publication_title, conference_title,
                     conference_start_date, publication_doi, pdf_path)
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$pub_id, $title, $venue ?: null, $pub_date ?: null, $doi ?: null, $pdf_path]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO journal_publication_details
                    (journal_id, title, journal_name, pub_date, publication_doi, pdf_path)
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$pub_id, $title, $venue ?: null, $pub_date ?: null, $doi ?: null, $pdf_path]);
            }

            $pdo->commit();
            $msg = 'Publication submitted successfully! It will be reviewed by the department.';
            $msg_type = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = 'Error: ' . $e->getMessage();
            $msg_type = 'error';
        }
    }
}

// Fetch existing student publications
$stmt = $pdo->prepare("
    SELECT pa.publication_id, pa.publication_type, pa.verification_flag,
           COALESCE(j.journal_publication_title, c.conference_publication_title) as title,
           COALESCE(j.journal_publication_date, c.conference_start_date) as pub_date,
           COALESCE(j.journal_title, c.conference_title) as venue
    FROM publication_author pa
    LEFT JOIN journal_publication_details    j ON pa.publication_id = j.journal_publication_id
    LEFT JOIN conference_publication_details c ON pa.publication_id = c.conference_publication_id
    WHERE pa.author_id = ?
    ORDER BY pa.publication_id DESC
");
$stmt->execute([$_SESSION['user_id']]);
$myPubs = $stmt->fetchAll();
?>

<div style="display:flex; flex-direction:column; gap:24px;">

    <!-- Upload Form -->
    <div style="background:white; padding:35px; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.08); border-top:6px solid #7c3aed;">
        <h2 style="margin:0 0 20px; color:#7c3aed; font-size:20px;">
            <i class="fa fa-file-export"></i> Upload a Publication
        </h2>

        <?php if ($msg): ?>
        <div style="padding:13px 16px; border-radius:8px; margin-bottom:20px; font-size:14px; display:flex; align-items:center; gap:10px;
            background:<?= $msg_type==='success' ? '#d1fae5' : '#fee2e2' ?>;
            color:<?= $msg_type==='success' ? '#065f46' : '#991b1b' ?>;
            border:1px solid <?= $msg_type==='success' ? '#34d399' : '#fca5a5' ?>;">
            <i class="fa fa-<?= $msg_type==='success' ? 'check-circle' : 'circle-exclamation' ?>"></i>
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:18px;">
                <div style="grid-column:span 2;">
                    <label style="display:block; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:5px;">Publication Title <span style="color:#dc2626">*</span></label>
                    <input type="text" name="title" required
                           style="width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box;"
                           placeholder="Enter full title of the paper / article">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:5px;">Type</label>
                    <select name="pub_type" style="width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box; background:white;">
                        <option value="Journal">Journal</option>
                        <option value="Conference">Conference</option>
                        <option value="News Letter">News Letter</option>
                        <option value="Book Chapter">Book Chapter</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:5px;">Publication Date</label>
                    <input type="date" name="pub_date"
                           style="width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:5px;">Journal / Conference Name</label>
                    <input type="text" name="venue"
                           style="width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box;"
                           placeholder="Where was it published?">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:5px;">DOI (if available)</label>
                    <input type="text" name="doi"
                           style="width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box;"
                           placeholder="e.g. 10.1000/xyz123">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:5px;">Upload PDF (optional)</label>
                    <input type="file" name="pdf_file" accept=".pdf"
                           style="width:100%; padding:9px 13px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; box-sizing:border-box; background:white;">
                </div>
            </div>
            <div style="text-align:right;">
                <button type="submit" name="submit_pub"
                        style="background:#7c3aed; color:white; border:none; padding:12px 32px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:8px;">
                    <i class="fa fa-paper-plane"></i> Submit Publication
                </button>
            </div>
        </form>
    </div>

    <!-- My Submissions -->
    <div style="background:white; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.08); border-top:6px solid #240046; overflow:hidden;">
        <div style="padding:20px 28px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
            <h2 style="margin:0; color:#240046; font-size:17px;"><i class="fa fa-book-open"></i> My Submissions</h2>
            <span style="font-size:13px; color:#94a3b8;"><?= count($myPubs) ?> record<?= count($myPubs)!=1?'s':'' ?></span>
        </div>
        <?php if (empty($myPubs)): ?>
        <div style="text-align:center; padding:50px 20px; color:#94a3b8;">
            <i class="fa fa-folder-open" style="font-size:32px; display:block; margin-bottom:10px; opacity:0.35;"></i>
            <p style="font-size:14px;">No publications submitted yet.</p>
        </div>
        <?php else: ?>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:11px 18px; text-align:left; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; border-bottom:2px solid #f1f5f9;">Title</th>
                    <th style="padding:11px 18px; text-align:left; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; border-bottom:2px solid #f1f5f9;">Type</th>
                    <th style="padding:11px 18px; text-align:left; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; border-bottom:2px solid #f1f5f9;">Date</th>
                    <th style="padding:11px 18px; text-align:left; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; border-bottom:2px solid #f1f5f9;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($myPubs as $i => $pub): ?>
            <tr style="border-bottom:1px solid #f8f9fa; background:<?= $i%2===1 ? '#fafafa' : 'white' ?>;">
                <td style="padding:13px 18px; font-size:14px; font-weight:500; color:#1e293b; max-width:360px;">
                    <?= htmlspecialchars($pub['title'] ?? '—') ?>
                    <?php if ($pub['venue']): ?>
                        <span style="font-size:12px; color:#94a3b8; display:block; margin-top:2px;"><?= htmlspecialchars($pub['venue']) ?></span>
                    <?php endif; ?>
                </td>
                <td style="padding:13px 18px;">
                    <span style="font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px;
                        background:<?= $pub['publication_type']==='Journal'?'#ede9fe':($pub['publication_type']==='Conference'?'#dbeafe':'#fef9c3') ?>;
                        color:<?= $pub['publication_type']==='Journal'?'#5b21b6':($pub['publication_type']==='Conference'?'#1d4ed8':'#854d0e') ?>;">
                        <?= htmlspecialchars($pub['publication_type']) ?>
                    </span>
                </td>
                <td style="padding:13px 18px; font-size:13px; color:#64748b; white-space:nowrap;">
                    <?= $pub['pub_date'] ? date('d M Y', strtotime($pub['pub_date'])) : '—' ?>
                </td>
                <td style="padding:13px 18px;">
                    <span style="font-size:11px; font-weight:700; padding:3px 8px; border-radius:20px;
                        background:<?= $pub['verification_flag'] ? '#dcfce7' : '#fff7ed' ?>;
                        color:<?= $pub['verification_flag'] ? '#166534' : '#9a3412' ?>;">
                        <?= $pub['verification_flag'] ? 'Verified' : 'Pending Review' ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>
