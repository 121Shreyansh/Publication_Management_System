<?php

/**
 * STUDENT PROFILE MODULE
 * Handles: Personal, Contact, and Guardian data management.
 * Logic: Updates student_info and flips verification_flag to lock the record.
 */

// 1. BACKEND LOGIC: HANDLE FORM SUBMISSION
if (isset($_POST['update_profile'])) {
    try {
        $v_status = isset($_POST['request_verification']) ? 1 : 0;
        $v_enum   = $v_status ? 'Pending' : 'Pending'; // HOD sets it to 'Verified'

        // BRUH FIX: Convert empty strings to NULL for integer columns to avoid 1366 errors
        $mobile = !empty($_POST['mobile_no']) ? $_POST['mobile_no'] : null;
        $g_mobile = !empty($_POST['g_mobile']) ? $_POST['g_mobile'] : null;
        $pin = !empty($_POST['pin']) ? $_POST['pin'] : null;

        $update_sql = "UPDATE student_info SET 
            first_name = ?, middle_name = ?, last_name = ?,
            email = ?, mobile_no = ?, 
            address = ?, city = ?, pin = ?,
            gender = ?, dob = ?, blood_group = ?,
            guardian_first_name = ?, guardian_last_name = ?, 
            guardain_mobile_no = ?, guardian_email = ?,
            verification_flag = ?, verification_status = ?,
            verification_date = " . ($v_status ? "CURRENT_DATE" : "NULL") . "
            WHERE student_id = ?";

        $params = [
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $_POST['email'],
            $mobile,
            $_POST['address'],
            $_POST['city'],
            $pin,
            $_POST['gender'],
            $_POST['dob'],
            $_POST['blood_group'],
            $_POST['g_first_name'],
            $_POST['g_last_name'],
            $g_mobile,
            $_POST['g_email'],
            $v_status,
            $v_enum,
            $_SESSION['user_id']
        ];

        $update = $pdo->prepare($update_sql);
        if ($update->execute($params)) {
            header("Location: student_dashboard.php?page=profile&status=success");
            exit;
        }
    } catch (PDOException $e) {
        // Detailed error reporting to catch SQL mismatches
        die("<div style='color:red; padding:20px; background:#fff; border:2px solid red; border-radius:10px;'>
                <b>Backend Connection Error:</b> " . $e->getMessage() . "
            </div>");
    }
}

// 2. DATA RETRIEVAL: FETCH CURRENT STATE
$stmt = $pdo->prepare("SELECT * FROM student_info WHERE student_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Determine if fields should be locked (disabled) based on verification_flag
$v_status_enum = $student['verification_status'] ?? 'Pending';
$disabled = ($student['verification_flag'] == 1) ? 'disabled' : '';
$badge_bg    = ['Pending'=>'#fff7ed','Verified'=>'#d1fae5','Rejected'=>'#fee2e2'][$v_status_enum] ?? '#fff7ed';
$badge_color = ['Pending'=>'#d97706','Verified'=>'#065f46','Rejected'=>'#991b1b'][$v_status_enum] ?? '#d97706';
$badge_border= ['Pending'=>'#fbbf24','Verified'=>'#34d399','Rejected'=>'#fca5a5'][$v_status_enum] ?? '#fbbf24';
$badge_icon  = ['Pending'=>'fa-user-clock','Verified'=>'fa-user-check','Rejected'=>'fa-user-xmark'][$v_status_enum] ?? 'fa-user-clock';
?>

<div class="profile-card" style="background: white; padding: 35px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h2 style="margin:0; color: var(--purple); font-size: 24px;">
            <i class="fa fa-id-card" style="margin-right: 10px;"></i> Student Profile
        </h2>
        <span style="padding: 8px 18px; border-radius: 50px; font-size: 13px; font-weight: bold; 
            background: <?= $badge_bg ?>; 
            color: <?= $badge_color ?>; 
            border: 1px solid <?= $badge_border ?>;">
            <i class="fa <?= $badge_icon ?>"></i>
            <?= strtoupper($v_status_enum) ?>
        </span>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div style="background: #ecfdf5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #10b981;">
            <i class="fa fa-check-circle"></i> Profile information has been saved and locked for verification.
        </div>
    <?php endif; ?>

    <form method="POST">
        <h4 style="color: #475569; margin-bottom: 12px; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">
            <i class="fa fa-graduation-cap"></i> Academic Summary
        </h4>
        <div style="background: #f8fafc; padding: 20px; border-radius: 10px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 35px; border-left: 5px solid var(--purple);">
            <div><label style="display:block; font-size:11px; color:#94a3b8; font-weight:bold;">STUDENT ID</label><strong style="color:#1e293b;"><?= htmlspecialchars($student['student_id']) ?></strong></div>
            <div><label style="display:block; font-size:11px; color:#94a3b8; font-weight:bold;">DEPT ID</label><strong style="color:#1e293b;"><?= htmlspecialchars($student['department_id'] ?? 'N/A') ?></strong></div>
            <div><label style="display:block; font-size:11px; color:#94a3b8; font-weight:bold;">ENROLMENT</label><strong style="color:#1e293b;"><?= htmlspecialchars($student['enrolment_date'] ?? 'N/A') ?></strong></div>
            <div><label style="display:block; font-size:11px; color:#94a3b8; font-weight:bold;">TYPE</label><strong style="color:#1e293b;"><?= htmlspecialchars($student['student_type'] ?? 'UG') ?></strong></div>
        </div>

        <h4 style="border-left: 4px solid var(--purple); padding-left: 12px; color: #1e293b; margin-bottom: 20px;">Personal & Contact Details</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 35px;">
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($student['first_name'] ?? '') ?>" <?= $disabled ?> required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing: border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">Middle Name</label>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($student['middle_name'] ?? '') ?>" <?= $disabled ?> style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing: border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($student['last_name'] ?? '') ?>" <?= $disabled ?> style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing: border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($student['email'] ?? '') ?>" <?= $disabled ?> required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing: border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">Mobile (No symbols)</label>
                <input type="text" name="mobile_no" value="<?= htmlspecialchars($student['mobile_no'] ?? '') ?>" <?= $disabled ?> required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing: border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">Blood Group</label>
                <input type="text" name="blood_group" value="<?= htmlspecialchars($student['blood_group'] ?? '') ?>" <?= $disabled ?> placeholder="e.g. O+" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing: border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">Gender</label>
                <select name="gender" <?= $disabled ?> style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing: border-box; background: white;">
                    <option value="<?= $student['gender'] ?>"><?= $student['gender'] ?: 'Select' ?></option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">Date of Birth</label>
                <input type="date" name="dob" value="<?= $student['dob'] ?>" <?= $disabled ?> style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing: border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">City</label>
                <input type="text" name="city" value="<?= htmlspecialchars($student['city'] ?? '') ?>" <?= $disabled ?> style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing: border-box;">
            </div>
            <div style="grid-column: span 2;">
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">Full Residential Address</label>
                <textarea name="address" <?= $disabled ?> style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; height: 43px; box-sizing: border-box;"><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:5px;">PIN Code</label>
                <input type="text" name="pin" value="<?= htmlspecialchars($student['pin'] ?? '') ?>" <?= $disabled ?> style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing: border-box;">
            </div>
        </div>

        <h4 style="border-left: 4px solid #f59e0b; padding-left: 12px; color: #1e293b; margin-bottom: 20px;">Guardian & Emergency Information</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; background: #fffcf0; padding: 25px; border-radius: 12px; border: 1px solid #fef3c7;">
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#92400e; margin-bottom:5px;">Guardian First Name</label>
                <input type="text" name="g_first_name" value="<?= htmlspecialchars($student['guardian_first_name'] ?? '') ?>" <?= $disabled ?> required style="width:100%; padding:10px; border:1px solid #fcd34d; border-radius:6px; box-sizing: border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#92400e; margin-bottom:5px;">Guardian Last Name</label>
                <input type="text" name="g_last_name" value="<?= htmlspecialchars($student['guardian_last_name'] ?? '') ?>" <?= $disabled ?> style="width:100%; padding:10px; border:1px solid #fcd34d; border-radius:6px; box-sizing: border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#92400e; margin-bottom:5px;">Guardian Mobile</label>
                <input type="text" name="g_mobile" value="<?= htmlspecialchars($student['guardain_mobile_no'] ?? '') ?>" <?= $disabled ?> required style="width:100%; padding:10px; border:1px solid #fcd34d; border-radius:6px; box-sizing: border-box;">
            </div>
            <div>
                <label style="display:block; font-size:13px; font-weight:600; color:#92400e; margin-bottom:5px;">Guardian Email</label>
                <input type="email" name="g_email" value="<?= htmlspecialchars($student['guardian_email'] ?? '') ?>" <?= $disabled ?> style="width:100%; padding:10px; border:1px solid #fcd34d; border-radius:6px; box-sizing: border-box;">
            </div>
        </div>

        <div style="margin-top: 40px; padding: 25px; background: #f1f5f9; border-radius: 12px; display: flex; flex-direction: column; gap: 15px;">
            <?php if (!$student['verification_flag']): ?>
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <input type="checkbox" name="request_verification" id="v_check" required style="width: 20px; height: 20px; cursor: pointer; margin-top: 3px;">
                    <label for="v_check" style="font-size: 14px; color: #475569; cursor: pointer; line-height: 1.5;">
                        <strong>Declaration:</strong> I confirm that all the information provided above is correct. I understand that my profile will be <b>locked for editing</b> once I submit for official verification.
                    </label>
                </div>
                <button type="submit" name="update_profile" style="background: var(--purple); color: white; border: none; padding: 14px 35px; border-radius: 8px; font-weight: bold; cursor: pointer; width: fit-content; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <i class="fa fa-paper-plane" style="margin-right: 8px;"></i> Submit Profile for Official Review
                </button>
            <?php else: ?>
                <div style="color: #065f46; background: #d1fae5; padding: 15px; border-radius: 8px; border: 1px solid #34d399; display: flex; align-items: center; gap: 12px;">
                    <i class="fa fa-lock" style="font-size: 20px;"></i>
                    <span style="font-weight: 600;">Your profile is verified. Access to Semester Registration and Subject Allotment is now active.</span>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>
