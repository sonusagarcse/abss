<?php
// process_contact.php - Secure Handler for Contact Page Inquiries & Document Attachments
require_once 'includes/security.php';
require_once 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: contact.php");
    exit();
}

// 1. CSRF Token Validation
if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $_SESSION['contact_error'] = "Security verification failed (invalid CSRF token). Please try submitting the form again.";
    header("Location: contact.php");
    exit();
}

$conn = getDB();

// 2. Sanitize and Extract Form Fields
$name         = trim($_POST['name'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$email        = trim($_POST['email'] ?? '');
$inquiry_type = trim($_POST['inquiry_type'] ?? 'General Query');
$subject      = trim($_POST['subject'] ?? '');
$message      = trim($_POST['message'] ?? '');

// 3. Field Validations
if (empty($name) || empty($phone) || empty($subject) || empty($message)) {
    $_SESSION['contact_error'] = "Please fill in all required fields (Name, Phone, Subject, and Message).";
    header("Location: contact.php");
    exit();
}

if (!preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) {
    $_SESSION['contact_error'] = "Please enter a valid phone or mobile number.";
    header("Location: contact.php");
    exit();
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['contact_error'] = "The email address provided is not valid.";
    header("Location: contact.php");
    exit();
}

// 4. Secure File Attachment Processing
$attachment_path = null;
if (isset($_FILES['query_document']) && !empty($_FILES['query_document']['name'])) {
    $upload_dir = __DIR__ . '/uploads/inquiries/';
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx'];
    $max_size_bytes = 5 * 1024 * 1024; // 5MB

    $upload_result = validate_and_save_upload(
        $_FILES['query_document'],
        $upload_dir,
        'uploads/inquiries/',
        $allowed_extensions,
        $max_size_bytes
    );

    if (!$upload_result['success']) {
        $_SESSION['contact_error'] = "File Upload Failed: " . $upload_result['error'];
        header("Location: contact.php");
        exit();
    }

    $attachment_path = $upload_result['file_path'];
}

// 5. Store Inquiry in Database
try {
    $target_exam = $inquiry_type; // Compatible with legacy target_exam field
    
    $stmt = $conn->prepare("INSERT INTO inquiries (candidate_name, parent_phone, email, target_exam, subject, message, attachment, inquiry_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param(
            "ssssssss",
            $name,
            $phone,
            $email,
            $target_exam,
            $subject,
            $message,
            $attachment_path,
            $inquiry_type
        );
        $stmt->execute();
        $inquiry_id = $conn->insert_id;
        $stmt->close();
    } else {
        throw new Exception("Database statement preparation failed.");
    }
} catch (Exception $e) {
    error_log("Error saving contact inquiry: " . $e->getMessage());
    $_SESSION['contact_error'] = "We could not save your inquiry due to a temporary database issue. Please call us directly at +91 9523012888.";
    header("Location: contact.php");
    exit();
}

// 6. Log Guest Activity
if (function_exists('log_activity')) {
    log_activity('contact_query', "Guest $name submitted inquiry: '$subject' ($inquiry_type)");
}

if (file_exists(__DIR__ . '/includes/mail_helper.php')) {
    require_once __DIR__ . '/includes/mail_helper.php';
    if (function_exists('send_smtp_email') && function_exists('get_base_template')) {
        $settings = getAllSettings();
        $admin_notify_email = $settings['contact_email'] ?? ($settings['admin_email'] ?? 'abssimamganj@gmail.com');
        
        $attachment_row = '';
        if (!empty($attachment_path)) {
            $attachment_row = '<tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Attached File</td><td style="padding:8px 0; font-weight:800; color:#2563eb; text-align:right;"><a href="http://localhost/abss/' . htmlspecialchars($attachment_path) . '" target="_blank">Download Attachment 📎</a></td></tr>';
        }

        $mail_content = '<div class="greeting">New Online Query Received</div>
            <p>A new inquiry was submitted via the ABSS Website Contact Page.</p>
            <div class="info-card" style="background:#f8fafc; border-radius:12px; padding:16px; margin:20px 0; border:1px solid #e2e8f0;">
                <table role="presentation" width="100%" style="border-collapse:collapse;">
                    <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Name</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($name) . '</td></tr>
                    <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Phone</td><td style="padding:8px 0; font-weight:900; color:#059669; text-align:right;">' . htmlspecialchars($phone) . '</td></tr>
                    <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Email</td><td style="padding:8px 0; font-weight:800; color:#2563eb; text-align:right;">' . htmlspecialchars($email ?: 'Not provided') . '</td></tr>
                    <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Category</td><td style="padding:8px 0; font-weight:800; color:#d97706; text-align:right;">' . htmlspecialchars($inquiry_type) . '</td></tr>
                    <tr><td style="padding:8px 0; font-weight:700; color:#64748b;">Subject</td><td style="padding:8px 0; font-weight:900; color:#0f172a; text-align:right;">' . htmlspecialchars($subject) . '</td></tr>
                    ' . $attachment_row . '
                </table>
            </div>
            <div style="background:#ffffff; border-left:4px solid #2563eb; padding:14px; margin-bottom:20px; font-size:0.95rem; color:#334155;">
                <strong>Message:</strong><br>' . nl2br(htmlspecialchars($message)) . '
            </div>
            <p><a href="http://localhost/abss/admin/inquiries.php" style="background:#2563eb; color:#fff; padding:12px 24px; border-radius:50px; text-decoration:none; font-weight:800; display:inline-block;">View All Inquiries in Admin Panel →</a></p>';

        $inquiry_html = get_base_template("New Contact Query - " . $subject, $mail_content);
        @send_smtp_email($admin_notify_email, "New Query: " . $subject . " - " . $name, $inquiry_html);
    }
}

// 8. Create In-Built Portal Notification for Admin Dashboard
if (function_exists('create_portal_notification')) {
    create_portal_notification(
        'general',
        "New Contact Query: " . $name,
        "Subject: $subject (Phone: $phone)",
        "inquiries.php",
        null,
        null,
        'fa-envelope',
        '#2563eb'
    );
}

// 9. Success Redirect
$_SESSION['contact_success'] = "Thank you, " . htmlspecialchars($name) . "! Your query regarding '" . htmlspecialchars($subject) . "' has been received. Our administration will contact you at " . htmlspecialchars($phone) . " shortly.";
header("Location: contact.php");
exit();
?>
