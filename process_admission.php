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
    $state          = trim($_POST['state'] ?? '');
    $zip_code       = trim($_POST['zip_code'] ?? '');
    $prev_school    = trim($_POST['prev_school'] ?? '');
    $target_program = trim($_POST['target_program'] ?? '');
    $scholar_mode   = trim($_POST['scholar_mode'] ?? '');

    // Guardian info
    $parent_name           = trim($_POST['parent_name'] ?? '');
    $phone                 = trim($_POST['phone'] ?? '');
    $email                 = trim($_POST['email'] ?? '');
    $address               = trim($_POST['address'] ?? '');
    $guardian_relationship = trim($_POST['guardian_relationship'] ?? '');

    // Emergency contact
    $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_relationship = trim($_POST['emergency_relationship'] ?? '');
    $emergency_phone        = trim($_POST['emergency_phone'] ?? '');

    // Medical (all optional)
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
        if (in_array($sp_ext, ['jpg','jpeg','png','gif','webp']) && $_FILES['student_photo']['size'] < 3 * 1024 * 1024) {
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
        $sql = "INSERT INTO admissions (
                    student_name, dob, gender, home_address, city, state, zip_code, prev_school,
                    parent_name, phone, email, address, guardian_relationship,
                    emergency_contact_name, emergency_relationship, emergency_phone,
                    has_allergies, allergies_detail, has_medical_condition, medical_condition_detail,
                    physician_name, physician_phone, insurance_provider, insurance_policy,
                    scholar_mode, target_program, student_photo
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?
                )";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param(
                "ssssssssssssssssissssssssss",
                $student_name, $dob, $gender, $home_address, $city, $state, $zip_code, $prev_school,
                $parent_name, $phone, $email, $address, $guardian_relationship,
                $emergency_contact_name, $emergency_relationship, $emergency_phone,
                $has_allergies, $allergies_detail, $has_medical_condition, $medical_condition_detail,
                $physician_name, $physician_phone, $insurance_provider, $insurance_policy,
                $scholar_mode, $target_program, $student_photo
            );
            if ($stmt->execute()) {
                $success      = true;
                $admission_id = $stmt->insert_id;
                // Log admission application
                log_activity('admission_application', "Guest submitted admission request for student $student_name (#$admission_id)");
            }
            $stmt->close();
        } else {
            $error_msg = "Database Error: Unable to prepare statement.";
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
        <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap' rel='stylesheet'>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body { font-family: 'Poppins', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: linear-gradient(135deg, #010c1f 0%, #0d47a1 100%); color: #fff; }
            .card { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.2); padding: 50px 40px; border-radius: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.2); text-align: center; max-width: 560px; width: 90%; }
            .icon-wrapper { width: 80px; height: 80px; background: #4caf50; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px; box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3); }
            h1 { margin-bottom: 15px; font-size: 2rem; color: #fff; }
            p { opacity: 0.9; margin-bottom: 25px; line-height: 1.6; font-size: 1.05rem; }
            .btn { background: #ffd600; color: #010c1f; padding: 15px 35px; border-radius: 50px; text-decoration: none; font-weight: 700; display: inline-block; transition: 0.3s; box-shadow: 0 10px 20px rgba(255, 214, 0, 0.2); }
            .btn:hover { background: #fff; transform: translateY(-3px); }
            .details-box { background: rgba(0,0,0,0.2); padding: 18px 20px; border-radius: 15px; margin-bottom: 25px; text-align: left; font-size: 0.9rem; line-height: 1.9; }
            .details-box strong { color: #ffd600; }
            .reg-no-badge { display: inline-block; background: rgba(255,214,0,0.15); border: 2px solid #ffd600; color: #ffd600; font-size: 1.4rem; font-weight: 800; padding: 8px 25px; border-radius: 50px; margin: 10px 0 20px; letter-spacing: 0.05em; }
        </style>
    </head>
    <body>
        <div class='card'>
            <?php if($success): ?>
                <div class="icon-wrapper"><i class="fas fa-check"></i></div>
                <h1>Application Submitted!</h1>
                <p>Thank you, <strong><?php echo htmlspecialchars($student_name); ?></strong>! Your admission application has been received successfully.</p>
                <div class="reg-no-badge">Application #<?php echo str_pad($admission_id, 5, '0', STR_PAD_LEFT); ?></div>
                <div class="details-box">
                    <div><strong>Application ID:</strong> #<?php echo str_pad($admission_id, 5, '0', STR_PAD_LEFT); ?></div>
                    <div><strong>Student:</strong> <?php echo htmlspecialchars($student_name); ?></div>
                    <div><strong>D.O.B:</strong> <?php echo $dob ? date('d M Y', strtotime($dob)) : '—'; ?></div>
                    <div><strong>Program:</strong> <?php echo htmlspecialchars($target_program); ?></div>
                    <div><strong>Mode:</strong> <?php echo htmlspecialchars($scholar_mode); ?></div>
                    <div><strong>Guardian:</strong> <?php echo htmlspecialchars($parent_name); ?></div>
                    <div><strong>Status:</strong> <span style="color: #4caf50; font-weight: bold;">Under Review</span></div>
                </div>
                <p style="font-size: 0.9rem; opacity: 0.7;">Our admission counselor will review your application and contact you at <strong><?php echo htmlspecialchars($phone); ?></strong> with next steps.</p>
            <?php else: ?>
                <div class="icon-wrapper" style="background: #f44336;"><i class="fas fa-times"></i></div>
                <h1>Submission Failed</h1>
                <p>Sorry, there was an error processing your application.</p>
                <?php if(!empty($error_msg)): ?>
                    <div class="details-box" style="background: rgba(244, 67, 54, 0.2);">
                        <strong style="color: #ff9999;">Error:</strong> <span style="color: #fff;"><?php echo htmlspecialchars($error_msg); ?></span>
                    </div>
                <?php endif; ?>
                <p>Please try again or contact us for support.</p>
            <?php endif; ?>
            <a href='index.php' class='btn'>Return to Homepage</a>
        </div>
    </body>
    </html>
    <?php
} else {
    header("Location: index.php");
    exit();
}
?>
