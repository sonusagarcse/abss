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

    $student_name = trim($_POST['student_name'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $parent_name = trim($_POST['parent_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $target_program = trim($_POST['target_program'] ?? '');
    $scholar_mode = trim($_POST['scholar_mode'] ?? '');
    $prev_school = trim($_POST['prev_school'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $success = false;
    $admission_id = 0;
    $error_msg = '';

    try {
        $sql = "INSERT INTO admissions (student_name, dob, gender, parent_name, phone, email, scholar_mode, target_program, prev_school, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ssssssssss", $student_name, $dob, $gender, $parent_name, $phone, $email, $scholar_mode, $target_program, $prev_school, $address);
            if ($stmt->execute()) {
                $success = true;
                $admission_id = $stmt->insert_id;
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
            .card { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.2); padding: 50px 40px; border-radius: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.2); text-align: center; max-width: 500px; width: 90%; }
            .icon-wrapper { width: 80px; height: 80px; background: #4caf50; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 40px; margin: 0 auto 20px; box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3); }
            h1 { margin-bottom: 15px; font-size: 2rem; color: #fff; }
            p { opacity: 0.9; margin-bottom: 35px; line-height: 1.6; font-size: 1.05rem; }
            .btn { background: #ffd600; color: #010c1f; padding: 15px 35px; border-radius: 50px; text-decoration: none; font-weight: 700; display: inline-block; transition: 0.3s; box-shadow: 0 10px 20px rgba(255, 214, 0, 0.2); }
            .btn:hover { background: #fff; transform: translateY(-3px); box-shadow: 0 15px 25px rgba(255, 255, 255, 0.2); }
            .details-box { background: rgba(0,0,0,0.2); padding: 15px; border-radius: 15px; margin-bottom: 30px; text-align: left; font-size: 0.9rem; }
            .details-box strong { color: #ffd600; }
        </style>
    </head>
    <body>
        <div class='card'>
            <?php if($success): ?>
                <div class="icon-wrapper"><i class="fas fa-check"></i></div>
                <h1>Application Submitted!</h1>
                <p>Thank you! We have successfully received the admission application for <strong><?php echo htmlspecialchars($student_name); ?></strong>.</p>
                <div class="details-box">
                    <div><strong>Admission ID:</strong> <span style="color: #ffd600; font-size: 1.2rem;">#<?php echo $admission_id; ?></span></div>
                    <div style="margin-top: 10px;"><strong>Student:</strong> <?php echo htmlspecialchars($student_name); ?></div>
                    <div style="margin-top: 8px;"><strong>Program:</strong> <?php echo htmlspecialchars($target_program); ?></div>
                    <div style="margin-top: 8px;"><strong>Mode:</strong> <?php echo htmlspecialchars($scholar_mode); ?></div>
                    <div style="margin-top: 8px;"><strong>Status:</strong> <span style="color: #4caf50; font-weight: bold;">Under Review</span></div>
                </div>
                <p style="font-size: 0.9rem; opacity: 0.7;">Our admission counselor will review the details and contact you at <strong><?php echo htmlspecialchars($phone); ?></strong> shortly with the next steps.</p>
            <?php else: ?>
                <div class="icon-wrapper" style="background: #f44336;"><i class="fas fa-times"></i></div>
                <h1>Submission Failed</h1>
                <p>Sorry, there was an error processing your application.</p>
                <?php if(!empty($error_msg)): ?>
                    <div class="details-box" style="background: rgba(244, 67, 54, 0.2);">
                        <strong style="color: #ff9999;">Database Error:</strong> <span style="color: #fff;"><?php echo htmlspecialchars($error_msg); ?></span>
                    </div>
                <?php endif; ?>
                <p>Please try again or contact support.</p>
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
