<?php
require_once 'includes/security.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        die("Security Token Verification Failed. Please go back and try again.");
    }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $exam = trim($_POST['target_exam'] ?? '');
    
    // Save to Database
    require_once 'config/db.php';
    $conn = getDB();
    $stmt = $conn->prepare("INSERT INTO inquiries (candidate_name, parent_phone, target_exam) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $phone, $exam);
    $stmt->execute();
    $inquiry_id = $conn->insert_id;
    
    // Log Guest Lead Inquiry Action
    log_activity('lead_inquiry', "Guest submitted admission inquiry for $name (exam: $exam)");

    // Dispatch Email Notification to Admin (sonusagarpoly@gmail.com)
    require_once __DIR__ . '/includes/mail_helper.php';
    $admin_notify_email = 'abssimamganj@gmail.com';
    
    $inquiry_html = get_base_template(
        "New Website Admission Inquiry",
        '<div class="greeting">New Lead Inquiry Received</div>
         <p>A new admission lead inquiry was submitted on the ABSS homepage.</p>
         <div class="info-card">
             <table role="presentation" width="100%">
                 <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Candidate Name</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($name) . '</td></tr>
                 <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Parent Phone</td><td style="padding:8px 0; font-weight:900; color:#059669; text-align:right;">' . htmlspecialchars($phone) . '</td></tr>
                 <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Target Exam</td><td style="padding:8px 0; font-weight:900; color:#2563eb; text-align:right;">' . htmlspecialchars($exam) . '</td></tr>
             </table>
         </div>
         <p><a href="http://localhost/abss/admin/inquiries.php" style="background:#2563eb; color:#fff; padding:12px 24px; border-radius:50px; text-decoration:none; font-weight:800; display:inline-block;">View Inquiries in Admin Panel →</a></p>'
    );

    send_smtp_email($admin_notify_email, "New Website Inquiry - " . $name . " (" . $exam . ")", $inquiry_html);

    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Success | ABSS</title>
        <link href='https://fonts.googleapis.com/css2?family=Outfit:wght@600;800;900&display=swap' rel='stylesheet'>
        <style>
            body { font-family: 'Outfit', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0d47a1 100%); color: #ffffff; }
            .card { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.18); padding: 45px 35px; border-radius: 30px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); text-align: center; max-width: 440px; }
            h1 { margin-bottom: 10px; font-size: 2rem; font-weight: 900; }
            p { opacity: 0.9; margin-bottom: 30px; color: #cbd5e1; line-height: 1.6; }
            .btn { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 14px 32px; border-radius: 50px; text-decoration: none; font-weight: 800; display: inline-block; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3); }
        </style>
    </head>
    <body>
        <div class='card'>
            <h1>Inquiry Sent!</h1>
            <p>Thank you, <strong>" . htmlspecialchars($name) . "</strong>. We have received your interest in <strong>" . htmlspecialchars($exam) . "</strong> preparation. Our team will contact you at <strong>" . htmlspecialchars($phone) . "</strong> shortly.</p>
            <a href='index.php' class='btn'>Back to Home</a>
        </div>
    </body>
    </html>";
} else {
    header("Location: index.php");
    exit();
}
?>
