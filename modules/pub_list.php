<?php
// pub_list.php — View Mine with co-author notifications
if (!isset($pdo)) { require_once __DIR__ . '/../config/db.php'; }
$user_id = $_SESSION['user_id'];

// Pending co-author requests for ME
$stmt = $pdo->prepare("
    SELECT car.request_id, car.publication_id, car.requester_id, car.requested_at,
           e.first_name, e.last_name, e.emp_designation,
           COALESCE(j.journal_publication_title, c.conference_publication_title) as pub_title,
           pa.publication_type
    FROM co_author_requests car
    JOIN employee_info e ON car.requester_id = e.emp_id
    JOIN publication_author pa ON car.publication_id = pa.publication_id
    LEFT JOIN journal_publication_details j ON pa.publication_id = j.journal_publication_id
    LEFT JOIN conference_publication_details c ON pa.publication_id = c.conference_publication_id
    WHERE car.requested_id = ? AND car.status = 'Pending'
    ORDER BY car.requested_at DESC
");
$stmt->execute([$user_id]);
$pendingRequests = $stmt->fetchAll();

// My own publications (as main author)
$stmt = $pdo->prepare("
    SELECT pa.publication_id, pa.publication_type, pa.verification_flag, pa.author_type,
           COALESCE(j.journal_publication_title, c.conference_publication_title) as pub_title,
           COALESCE(j.journal_publication_date, c.conference_start_date) as pub_date
    FROM publication_author pa
    LEFT JOIN journal_publication_details j ON pa.publication_id = j.journal_publication_id
    LEFT JOIN conference_publication_details c ON pa.publication_id = c.conference_publication_id
    WHERE pa.author_id = ?
    ORDER BY pub_date DESC
");
$stmt->execute([$user_id]);
$myPubs = $stmt->fetchAll();

// Publications I accepted as co-author
$stmt = $pdo->prepare("
    SELECT car.publication_id, pa.publication_type, pa.verification_flag,
           'Co-Author' as author_type,
           COALESCE(j.journal_publication_title, c.conference_publication_title) as pub_title,
           COALESCE(j.journal_publication_date, c.conference_start_date) as pub_date,
           e.first_name as main_fn, e.last_name as main_ln
    FROM co_author_requests car
    JOIN publication_author pa ON car.publication_id = pa.publication_id
    JOIN employee_info e ON pa.author_id = e.emp_id
    LEFT JOIN journal_publication_details j ON pa.publication_id = j.journal_publication_id
    LEFT JOIN conference_publication_details c ON pa.publication_id = c.conference_publication_id
    WHERE car.requested_id = ? AND car.status = 'Accepted'
    ORDER BY pub_date DESC
");
$stmt->execute([$user_id]);
$coAuthoredPubs = $stmt->fetchAll();
?>
<style>
/* Notification cards */
.notif-banner { background:white; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.08); border-left:5px solid #f59e0b; margin-bottom:24px; overflow:hidden; }
.notif-banner-header { background:#fffbeb; padding:14px 24px; display:flex; align-items:center; gap:10px; border-bottom:1px solid #fde68a; }
.notif-banner-header h3 { margin:0; font-size:15px; color:#92400e; }
.notif-banner-header .badge { background:#f59e0b; color:white; font-size:11px; font-weight:700; padding:2px 8px; border-radius:20px; }
.notif-item { display:flex; align-items:center; gap:16px; padding:16px 24px; border-bottom:1px solid #fef9c3; }
.notif-item:last-child { border-bottom:none; }
.notif-avatar { width:42px; height:42px; border-radius:50%; background:#fde68a; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px; color:#92400e; flex-shrink:0; }
.notif-info { flex:1; }
.notif-info .from { font-size:14px; font-weight:600; color:#1e293b; }
.notif-info .pub  { font-size:13px; color:#64748b; margin-top:2px; }
.notif-info .time { font-size:11px; color:#94a3b8; margin-top:2px; }
.notif-actions { display:flex; gap:8px; }
.btn-accept { background:#16a34a; color:white; border:none; padding:8px 18px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px; }
.btn-accept:hover { background:#15803d; }
.btn-reject { background:white; color:#dc2626; border:1.5px solid #fca5a5; padding:8px 18px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }
.btn-reject:hover { background:#fef2f2; }

/* Publications table */
.list-card { background:white; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.08); border-top:6px solid #240046; overflow:hidden; margin-bottom:24px; }
.list-card-header { padding:22px 28px 16px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #f1f5f9; }
.list-card-header h2 { margin:0; font-size:18px; color:#240046; display:flex; align-items:center; gap:8px; }
.list-table { width:100%; border-collapse:collapse; }
.list-table thead tr { background:#f8fafc; }
.list-table thead th { padding:11px 18px; text-align:left; font-size:12px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.4px; border-bottom:2px solid #f1f5f9; }
.list-table tbody tr { border-bottom:1px solid #f8f9fa; transition:background 0.15s; }
.list-table tbody tr:hover { background:#faf5ff; }
.list-table tbody td { padding:13px 18px; font-size:14px; color:#334155; vertical-align:middle; }

.type-pill { font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; }
.tp-Journal    { background:#ede9fe; color:#5b21b6; }
.tp-Conference { background:#dbeafe; color:#1d4ed8; }
.tp-Newspaper  { background:#fef9c3; color:#854d0e; }
.tp-Book       { background:#dcfce7; color:#166534; }
.role-pill { font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; background:#e0f2fe; color:#0369a1; margin-left:6px; }
.role-coauthor { background:#fce7f3; color:#9d174d; }

.badge-verified { background:#dcfce7; color:#166534; font-size:11px; padding:3px 8px; border-radius:20px; font-weight:600; }
.badge-pending  { background:#fff7ed; color:#9a3412; font-size:11px; padding:3px 8px; border-radius:20px; font-weight:600; }

.list-empty { text-align:center; padding:50px 20px; color:#94a3b8; }
.list-empty i { font-size:32px; display:block; margin-bottom:10px; opacity:0.35; }

.btn-list-export { background:#240046; color:white; border:none; padding:9px 20px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:8px; text-decoration:none; }
</style>

<!-- ── PENDING NOTIFICATIONS ── -->
<?php if (count($pendingRequests) > 0): ?>
<div class="notif-banner" id="notif-banner">
    <div class="notif-banner-header">
        <i class="fa fa-bell" style="color:#f59e0b; font-size:18px;"></i>
        <h3>Co-Author Requests</h3>
        <span class="badge"><?= count($pendingRequests) ?></span>
    </div>
    <?php foreach ($pendingRequests as $req): ?>
    <?php
        $initials = strtoupper(substr($req['first_name'],0,1).substr($req['last_name']??'',0,1));
        $name = trim($req['first_name'].' '.($req['last_name']??''));
        $time = date('d M Y', strtotime($req['requested_at']));
    ?>
    <div class="notif-item" id="notif-<?= $req['request_id'] ?>">
        <div class="notif-avatar"><?= $initials ?></div>
        <div class="notif-info">
            <div class="from"><?= htmlspecialchars($name) ?>
                <span style="font-weight:400;color:#64748b;font-size:13px;">&nbsp;(<?= htmlspecialchars($req['emp_designation']??'Faculty') ?>)</span>
            </div>
            <div class="pub">
                has listed you as co-author on: <strong>"<?= htmlspecialchars($req['pub_title']) ?>"</strong>
                <span class="type-pill tp-<?= $req['publication_type'] ?>"><?= $req['publication_type'] ?></span>
            </div>
            <div class="time"><i class="fa fa-clock"></i> Requested on <?= $time ?></div>
        </div>
        <div class="notif-actions">
            <button class="btn-accept" onclick="respondRequest(<?= $req['request_id'] ?>, 'accept')">
                <i class="fa fa-check"></i> Accept
            </button>
            <button class="btn-reject" onclick="respondRequest(<?= $req['request_id'] ?>, 'reject')">
                Decline
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── MY PUBLICATIONS ── -->
<div class="list-card">
    <div class="list-card-header">
        <h2><i class="fa fa-book-open"></i> My Publications</h2>
        <a href="dashboard.php?page=export" class="btn-list-export"><i class="fa fa-file-export"></i> Export</a>
    </div>
    <table class="list-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Type</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($myPubs as $pub): ?>
        <tr>
            <td style="font-weight:500; max-width:360px;">
                <?= htmlspecialchars($pub['pub_title'] ?? '—') ?>
                <span class="role-pill"><?= htmlspecialchars($pub['author_type']) ?></span>
            </td>
            <td><span class="type-pill tp-<?= $pub['publication_type'] ?>"><?= $pub['publication_type'] ?></span></td>
            <td style="color:#64748b; white-space:nowrap;"><?= $pub['pub_date'] ? date('d M Y', strtotime($pub['pub_date'])) : '—' ?></td>
            <td><span class="<?= $pub['verification_flag'] ? 'badge-verified' : 'badge-pending' ?>"><?= $pub['verification_flag'] ? 'Verified' : 'Pending' ?></span></td>
            <td>
                <a href="dashboard.php?page=modify&action=edit&id=<?= $pub['publication_id'] ?>" style="color:#240046;margin-right:12px;" title="Edit"><i class="fa fa-pen-to-square"></i></a>
                <a href="process_bulk_delete.php?single=<?= $pub['publication_id'] ?>" style="color:#dc2626;" onclick="return confirm('Delete this publication?')" title="Delete"><i class="fa fa-trash"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($myPubs)): ?>
        <tr><td colspan="5" class="list-empty"><i class="fa fa-folder-open"></i><br>No publications yet. Use <strong>Add New</strong> to get started.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ── CO-AUTHORED PUBLICATIONS ── -->
<?php if (count($coAuthoredPubs) > 0): ?>
<div class="list-card" style="border-top-color:#7c3aed;">
    <div class="list-card-header">
        <h2 style="color:#7c3aed;"><i class="fa fa-users"></i> Co-Authored Publications</h2>
        <span style="font-size:13px;color:#94a3b8;">Publications you accepted as co-author</span>
    </div>
    <table class="list-table">
        <thead>
            <tr><th>Title</th><th>Type</th><th>Main Author</th><th>Date</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($coAuthoredPubs as $pub): ?>
        <tr>
            <td style="font-weight:500; max-width:320px;">
                <?= htmlspecialchars($pub['pub_title'] ?? '—') ?>
                <span class="role-pill role-coauthor">Co-Author</span>
            </td>
            <td><span class="type-pill tp-<?= $pub['publication_type'] ?>"><?= $pub['publication_type'] ?></span></td>
            <td style="color:#64748b;"><?= htmlspecialchars(trim($pub['main_fn'].' '.$pub['main_ln'])) ?></td>
            <td style="color:#64748b; white-space:nowrap;"><?= $pub['pub_date'] ? date('d M Y', strtotime($pub['pub_date'])) : '—' ?></td>
            <td><span class="<?= $pub['verification_flag'] ? 'badge-verified' : 'badge-pending' ?>"><?= $pub['verification_flag'] ? 'Verified' : 'Pending' ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<script>
function respondRequest(reqId, action) {
    const item = document.getElementById('notif-' + reqId);
    const btns = item.querySelectorAll('button');
    btns.forEach(b => { b.disabled = true; b.style.opacity = '0.5'; });

    fetch('ajax_coauthor_respond.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=${action}&request_id=${reqId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            if (action === 'accept') {
                item.style.background = '#f0fdf4';
                item.querySelector('.notif-actions').innerHTML = '<span style="color:#16a34a;font-size:13px;font-weight:600;"><i class="fa fa-check-circle"></i> Accepted — publication added to your profile</span>';
                setTimeout(() => location.reload(), 2000);
            } else {
                item.style.background = '#fef2f2';
                item.querySelector('.notif-actions').innerHTML = '<span style="color:#dc2626;font-size:13px;font-weight:600;"><i class="fa fa-xmark-circle"></i> Declined</span>';
                setTimeout(() => item.remove(), 2000);
            }
        }
    })
    .catch(() => { btns.forEach(b => { b.disabled=false; b.style.opacity='1'; }); });
}
</script>