<?php
// 1. Fetch existing record based on the ID passed in URL
$pub_id = $_GET['id'] ?? null;
if (!$pub_id) { echo "No publication selected."; return; }

$stmt = $pdo->prepare("
    SELECT pa.*, j.*, c.* FROM publication_author pa
    LEFT JOIN journal_publication_details j ON pa.publication_id = j.journal_publication_id
    LEFT JOIN conference_publication_details c ON pa.publication_id = c.conference_publication_id
    WHERE pa.publication_id = ? AND pa.author_id = ?
");
$stmt->execute([$pub_id, $_SESSION['user_id']]);
$data = $stmt->fetch();

if (!$data) { echo "<div class='alert'>Access denied or record not found.</div>"; return; }

$type = $data['publication_type'];

// 2. Handle Update Logic (Integrated within the same file)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_pub'])) {
    try {
        $pdo->beginTransaction();

        if ($type == 'Journal') {
            $update = $pdo->prepare("
                UPDATE journal_publication_details 
                SET journal_publication_title = ?, journal_title = ?, publication_volume_no = ?, publication_issue_no = ?, publication_doi = ?, journal_publication_date = ? 
                WHERE journal_publication_id = ?
                WHERE journal_publication_id = ?
            ");
            $update->execute([$_POST['title'], $_POST['venue'], $_POST['volume'], $_POST['issue'], $_POST['doi'], $_POST['pub_date'], $pub_id]);
        } else {
            $update = $pdo->prepare("
                UPDATE conference_publication_details 
                SET conference_publication_title = ?, conference_title = ?, conference_start_date = ?, conference_end_date = ?, conference_city = ?, conference_location = ? 
                WHERE conference_publication_id = ?
            ");
            $update->execute([$_POST['title'], $_POST['venue'], $_POST['pub_date'], $_POST['end_date'], $_POST['c_city'], $_POST['c_location'], $pub_id]);
        }

        $pdo->commit();
        echo "<script>window.location.href='dashboard.php?page=modify&status=success';</script>";
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>

<style>
    .form-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .header-badge { display: inline-block; background: #f4f0fa; color: #240046; padding: 5px 15px; border-radius: 20px; font-weight: bold; margin-bottom: 20px; border: 1px solid #dcd0ff; }
    .form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
    .col-full { grid-column: span 4; }
    .col-half { grid-column: span 2; }
    label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 13px; color: #555; }
    input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
    input:focus { border-color: #240046; outline: none; box-shadow: 0 0 5px rgba(36, 0, 70, 0.1); }
    .submit-btn { background: #240046; color: white; padding: 12px; border: none; border-radius: 6px; width: 100%; cursor: pointer; font-weight: bold; transition: 0.3s; }
    .submit-btn:hover { background: #3c096c; }
    .cancel-btn { background: #6c757d; color: white; padding: 12px; border: none; border-radius: 6px; text-decoration: none; text-align: center; font-weight: bold; }
</style>

<div class="form-card">
    <div class="header-badge">Editing <?= $type ?></div>
    <h2 style="color:#240046; margin-top:0;">Modify Publication Details</h2>

    <form method="POST">
        <div class="form-grid">
            <div class="col-full">
                <label>Publication Title *</label>
                <input type="text" name="title" value="<?= htmlspecialchars($data['journal_publication_title'] ?? $data['conference_publication_title'] ?? '') ?>" required>
            </div>

            <div class="col-half">
                <label>Department</label>
                <select name="dept">
                    <option value="CSE" selected>Computer Science & Engineering</option>
                </select>
            </div>

            <div class="col-half">
                <label><?= ($type == 'Journal') ? 'Date of Publishing' : 'Start Date' ?> *</label>
                <input type="date" name="pub_date" value="<?= $data['journal_publication_date'] ?? $data['conference_start_date'] ?>" required>
            </div>

            <?php if ($type == 'Journal'): ?>
                <div class="col-half">
                    <label>Journal Name (Venue)</label>
                    <input type="text" name="venue" value="<?= htmlspecialchars($data['journal_title']) ?>">
                </div>
                <div>
                    <label>Volume</label>
                    <input type="text" name="volume" value="<?= htmlspecialchars($data['publication_volume_no']) ?>">
                </div>
                <div>
                    <label>Issue No.</label>
                    <input type="text" name="issue" value="<?= htmlspecialchars($data['publication_issue_no']) ?>">
                </div>
                <div class="col-half">
                    <label>DOI</label>
                    <input type="text" name="doi" value="<?= htmlspecialchars($data['doi'] ?? '') ?>">
                </div>

            <?php else: ?>
                <div class="col-half">
                    <label>Conference Name (Venue)</label>
                    <input type="text" name="venue" value="<?= htmlspecialchars($data['conference_title']) ?>">
                </div>
                <div>
                    <label>Date of Ending *</label>
                    <input type="date" name="end_date" value="<?= $data['conference_end_date'] ?>" required>
                </div>
                <div>
                    <label>City</label>
                    <input type="text" name="c_city" value="<?= htmlspecialchars($data['conference_city']) ?>">
                </div>
                <div class="col-half">
                    <label>Conference Location</label>
                    <input type="text" name="c_location" value="<?= htmlspecialchars($data['conference_location']) ?>">
                </div>
            <?php endif; ?>

            <div class="col-half">
                <label>Co-Author ID (Optional)</label>
                <input type="text" name="co_author" placeholder="Optional ID">
            </div>
            <div class="col-half">
                <label>Current PDF</label>
                <input type="text" value="<?= $data['pdf_path'] ? basename($data['pdf_path']) : 'No PDF attached' ?>" disabled style="background:#f8f9fa;">
            </div>
        </div>

        <div style="margin-top: 30px; display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
            <button type="submit" name="update_pub" class="submit-btn">Save Changes</button>
            <a href="dashboard.php?page=modify" class="cancel-btn">Cancel</a>
        </div>
    </form>
</div>