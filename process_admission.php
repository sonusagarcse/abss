<?php
require_once 'includes/security.php';
require_once 'config/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die("Security Token Verification Failed. Please go back and try again.");
    }

    $conn = getDB();

    // Student info
    $student_name   = trim($_POST['student_name'] ?? '');
    $dob            = trim($_POST['dob'] ?? '');
    $gender         = trim($_POST['gender'] ?? '');
    $home_address   = trim($_POST['home_address'] ?? '');
    $city           = trim($_POST['city'] ?? '');
    $state          = trim($_POST['state'] ?? 'Bihar');
    $zip_code       = trim($_POST['zip_code'] ?? '');
    $prev_school    = trim($_POST['prev_school'] ?? '');
    $target_program = trim($_POST['target_program'] ?? '');
    $scholar_mode   = trim($_POST['scholar_mode'] ?? '');

    // Combine full address string
    $full_address = $home_address;
    if (!empty($city)) $full_address .= ", $city";
    if (!empty($state)) $full_address .= ", $state";
    if (!empty($zip_code)) $full_address .= " - $zip_code";

    // Guardian info
    $parent_name           = trim($_POST['parent_name'] ?? '');
    $phone                 = trim($_POST['phone'] ?? '');
    $email                 = trim($_POST['email'] ?? '');
    $address               = trim($_POST['address'] ?? '');
    if (empty($address)) {
        $address = $full_address;
    }
    $guardian_relationship = trim($_POST['guardian_relationship'] ?? '');

    // Emergency contact
    $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_relationship = trim($_POST['emergency_relationship'] ?? '');
    $emergency_phone        = trim($_POST['emergency_phone'] ?? '');

    // Medical (optional)
    $has_allergies            = isset($_POST['has_allergies']) ? 1 : 0;
    $allergies_detail         = trim($_POST['allergies_detail'] ?? '');
    $has_medical_condition    = isset($_POST['has_medical_condition']) ? 1 : 0;
    $medical_condition_detail = trim($_POST['medical_condition_detail'] ?? '');
    $physician_name           = trim($_POST['physician_name'] ?? '');
    $physician_phone          = trim($_POST['physician_phone'] ?? '');
    $insurance_provider       = trim($_POST['insurance_provider'] ?? '');
    $insurance_policy         = trim($_POST['insurance_policy'] ?? '');

    // Handle student photo upload
    $student_photo = '';
    $upload_dir = __DIR__ . '/uploads/students/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    if (!empty($_FILES['student_photo']['name'])) {
        $sp_ext = strtolower(pathinfo($_FILES['student_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($sp_ext, ['jpg','jpeg','png','gif','webp']) && $_FILES['student_photo']['size'] < 5 * 1024 * 1024) {
            $sp_name = 'adm_pic_' . time() . '_' . rand(1000,9999) . '.' . $sp_ext;
            if (move_uploaded_file($_FILES['student_photo']['tmp_name'], $upload_dir . $sp_name)) {
                $student_photo = 'uploads/students/' . $sp_name;
            }
        }
    }

    $success      = false;
    $admission_id = 0;
    $error_msg    = '';

    try {
        // Query matching admissions table schema (address, city, state, zip_code)
        $sql = "INSERT INTO admissions (
                    student_name, dob, gender, address, city, state, zip_code, prev_school,
                    parent_name, phone, email, guardian_relationship,
                    emergency_contact_name, emergency_relationship, emergency_phone,
                    has_allergies, allergies_detail, has_medical_condition, medical_condition_detail,
                    physician_name, physician_phone, insurance_provider, insurance_policy,
                    scholar_mode, target_program, student_photo
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?
                )";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param(
                "ssssssssssssssisssssssssss",
                $student_name, $dob, $gender, $address, $city, $state, $zip_code, $prev_school,
                $parent_name, $phone, $email, $guardian_relationship,
                $emergency_contact_name, $emergency_relationship, $emergency_phone,
                $has_allergies, $allergies_detail, $has_medical_condition, $medical_condition_detail,
                $physician_name, $physician_phone, $insurance_provider, $insurance_policy,
                $scholar_mode, $target_program, $student_photo
            );
            if ($stmt->execute()) {
                $success      = true;
                $admission_id = $stmt->insert_id;
                log_activity('admission_application', "Guest submitted online admission for student $student_name (#$admission_id)");

                // Dispatch Email Notification to Admin & Parent
                require_once __DIR__ . '/includes/mail_helper.php';
                $admin_notify_email = 'abssimamganj@gmail.com';
                
                $admin_html = get_base_template(
                    "New Online Admission Application",
                    '<div class="greeting">New Online Admission Application #' . str_pad($admission_id, 5, '0', STR_PAD_LEFT) . '</div>
                     <p>A new student admission application has been registered online.</p>
                     <div class="info-card">
                         <table role="presentation" width="100%">
                             <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Student Name</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($student_name) . '</td></tr>
                             <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Date of Birth</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($dob) . '</td></tr>
                             <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Gender</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($gender) . '</td></tr>
                             <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Target Program</td><td style="padding:8px 0; font-weight:900; color:#2563eb; text-align:right;">' . htmlspecialchars($target_program) . '</td></tr>
                             <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Scholar Mode</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($scholar_mode) . '</td></tr>
                             <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Guardian Name</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($parent_name) . ' (' . htmlspecialchars($guardian_relationship) . ')</td></tr>
                             <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Phone Number</td><td style="padding:8px 0; font-weight:900; color:#059669; text-align:right;">' . htmlspecialchars($phone) . '</td></tr>
                             <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Email Address</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($email ?: '—') . '</td></tr>
                             <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Full Address</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($address) . '</td></tr>
                         </table>
                     </div>'
                );

                send_smtp_email($admin_notify_email, "New Online Admission #" . str_pad($admission_id, 5, '0', STR_PAD_LEFT) . " - " . $student_name, $admin_html);

                if (!empty($email)) {
                    $parent_html = get_base_template(
                        "Admission Application Received",
                        '<div class="greeting">Application Received</div>
                         <p>Dear <strong>' . htmlspecialchars($parent_name) . '</strong>,</p>
                         <p>Thank you for registering <strong>' . htmlspecialchars($student_name) . '</strong> for session 2026-27 at Awasiya Bal Shikshan Sansthan.</p>
                         <div class="info-card">
                             <div style="font-size:1.1rem; font-weight:900; color:#2563eb; margin-bottom:6px;">Application #' . str_pad($admission_id, 5, '0', STR_PAD_LEFT) . '</div>
                             <div><strong>Program:</strong> ' . htmlspecialchars($target_program) . ' (' . htmlspecialchars($scholar_mode) . ')</div>
                             <div><strong>Status:</strong> Under Review</div>
                         </div>
                         <p>Our admission counselor will contact you at <strong>' . htmlspecialchars($phone) . '</strong> shortly.</p>'
                    );
                    send_smtp_email($email, "Application Received - " . $student_name . " - ABSS", $parent_html);
                }

            } else {
                $error_msg = "Database Execution Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_msg = "Database Error: Unable to prepare statement (" . $conn->error . ")";
        }
    } catch (mysqli_sql_exception $e) {
        $error_msg = "Database Error: " . $e->getMessage();
    } catch (Exception $e) {
        $error_msg = "System Error: " . $e->getMessage();
    }
    ?>
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Application Submitted | ABSS</title>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { font-family: 'Outfit', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0d47a1 100%); color: #fff; padding: 20px; }
            .card { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.18); padding: 45px 35px; border-radius: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); text-align: center; max-width: 520px; width: 100%; }
            .icon-wrapper { width: 80px; height: 80px; background: #22c55e; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 38px; margin: 0 auto 20px; box-shadow: 0 10px 25px rgba(34, 197, 94, 0.4); }
            h1 { margin: 0 0 12px 0; font-size: 2rem; color: #fff; font-weight: 900; }
            p { opacity: 0.9; margin-bottom: 25px; line-height: 1.6; font-size: 1rem; color: #cbd5e1; }
            .btn { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; padding: 14px 32px; border-radius: 50px; text-decoration: none; font-weight: 800; display: inline-block; transition: 0.3s; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3); }
            .btn:hover { transform: translateY(-2px); box-shadow: 0 14px 28px rgba(37, 99, 235, 0.4); }
            .details-box { background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.1); padding: 18px 20px; border-radius: 18px; margin-bottom: 25px; text-align: left; font-size: 0.9rem; line-height: 1.9; }
            .details-box strong { color: #38bdf8; }
            .reg-no-badge { display: inline-block; background: rgba(56, 189, 248, 0.15); border: 2px solid #38bdf8; color: #38bdf8; font-size: 1.4rem; font-weight: 900; padding: 8px 25px; border-radius: 50px; margin: 10px 0 20px; letter-spacing: 0.05em; }
        </style>
    </head>
    <body>
        <div class='card'>
            <?php if($success): ?>
                <div class="icon-wrapper"><i class="fas fa-check"></i></div>
                <h1>Application Submitted!</h1>
                <p>Thank you, <strong><?php echo htmlspecialchars($student_name); ?></strong>! Your online admission request has been recorded.</p>
                <div class="reg-no-badge">Application #<?php echo str_pad($admission_id, 5, '0', STR_PAD_LEFT); ?></div>
                <div class="details-box">
                    <div><strong>Application ID:</strong> #<?php echo str_pad($admission_id, 5, '0', STR_PAD_LEFT); ?></div>
                    <div><strong>Student Name:</strong> <?php echo htmlspecialchars($student_name); ?></div>
                    <div><strong>Date of Birth:</strong> <?php echo $dob ? date('d M Y', strtotime($dob)) : '—'; ?></div>
                    <div><strong>Program:</strong> <?php echo htmlspecialchars($target_program); ?></div>
                    <div><strong>Mode:</strong> <?php echo htmlspecialchars($scholar_mode); ?></div>
                    <div><strong>Guardian:</strong> <?php echo htmlspecialchars($parent_name); ?></div>
                    <div><strong>Status:</strong> <span style="color: #4ade80; font-weight: 800;">Under Review</span></div>
                </div>
                <p style="font-size: 0.88rem; opacity: 0.8;">Our admission desk will review your application and contact you at <strong><?php echo htmlspecialchars($phone); ?></strong> with next steps.</p>
                <a href="index.php" class="btn"><i class="fas fa-home"></i> Return to Homepage</a>
            <?php else: ?>
                <div class="icon-wrapper" style="background:#ef4444; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.4);"><i class="fas fa-exclamation-triangle"></i></div>
                <h1>Submission Error</h1>
                <p style="color:#fca5a5;"><?php echo htmlspecialchars($error_msg); ?></p>
                <a href="admission.php" class="btn" style="background:#ef4444;"><i class="fas fa-arrow-left"></i> Back to Admission Form</a>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
?>
