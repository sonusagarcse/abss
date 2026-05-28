<?php
// includes/mail_helper.php - Custom SMTP Email Client & Styled Responsive HTML Templates

require_once __DIR__ . '/../config/db.php';

/**
 * Custom SMTP Client that sends emails using direct TCP socket stream connections.
 * Falls back to PHP's built-in mail() function if SMTP settings are not provided.
 *
 * @param string $to Recipient Email Address
 * @param string $subject Email Subject Line
 * @param string $message HTML Email Body
 * @return bool True if successfully dispatched, False otherwise.
 */
function send_smtp_email($to, $subject, $message) {
    $settings = getAllSettings();
    
    $host = isset($settings['smtp_host']) ? trim($settings['smtp_host']) : '';
    $port = isset($settings['smtp_port']) ? (int)trim($settings['smtp_port']) : 587;
    $user = isset($settings['smtp_username']) ? trim($settings['smtp_username']) : '';
    $pass = isset($settings['smtp_password']) ? trim($settings['smtp_password']) : '';
    $encryption = isset($settings['smtp_encryption']) ? trim($settings['smtp_encryption']) : 'tls';
    $from_email = isset($settings['email']) ? trim($settings['email']) : 'abssimamganj@gmail.com';
    $from_name = isset($settings['school_name']) ? trim($settings['school_name']) : 'ABSS Portal';

    // If SMTP credentials are not filled, fall back to native PHP mail() function
    if (empty($host) || empty($user) || empty($pass)) {
        error_log("ABSS SMTP System: Mail Server credentials not configured. Falling back to native PHP mail() with suppressed warnings.");
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <$from_email>\r\n";
        $headers .= "Reply-To: <$from_email>\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        return @mail($to, $subject, $message, $headers);
    }
    
    // Direct SMTP Socket implementation
    $socket_host = ($encryption === 'ssl') ? "ssl://$host" : $host;
    
    $socket = @fsockopen($socket_host, $port, $errno, $errstr, 15);
    if (!$socket) {
        error_log("SMTP Connection Failure: $errstr ($errno) - Host: $socket_host, Port: $port");
        return false;
    }
    
    // Helper closure to read SMTP responses
    $readResponse = function() use ($socket) {
        $data = '';
        while (($str = fgets($socket, 515)) !== false) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        return $data;
    };
    
    // Helper closure to write commands
    $writeCommand = function($cmd) use ($socket) {
        fwrite($socket, $cmd . "\r\n");
    };
    
    // Read Greeting (220)
    $readResponse();
    
    // Say EHLO
    $writeCommand("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $readResponse();
    
    // If TLS, issue STARTTLS and upgrade crypto
    if ($encryption === 'tls') {
        $writeCommand("STARTTLS");
        $res = $readResponse();
        if (strpos($res, '220') === false) {
            error_log("SMTP STARTTLS Handshake Failed: $res");
            fclose($socket);
            return false;
        }
        
        // Upgrade socket to secure TLS stream
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log("SMTP Stream Crypto Upgrade Failed.");
            fclose($socket);
            return false;
        }
        
        // Re-greet server after TLS session established
        $writeCommand("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $readResponse();
    }
    
    // Perform AUTH LOGIN
    $writeCommand("AUTH LOGIN");
    $readResponse();
    
    $writeCommand(base64_encode($user));
    $readResponse();
    
    $writeCommand(base64_encode($pass));
    $auth_res = $readResponse();
    if (strpos($auth_res, '235') === false) {
        error_log("SMTP Authentication Failure for User $user: $auth_res");
        fclose($socket);
        return false;
    }
    
    // Set envelopes
    $writeCommand("MAIL FROM: <$user>");
    $readResponse();
    
    $writeCommand("RCPT TO: <$to>");
    $readResponse();
    
    // Write Data
    $writeCommand("DATA");
    $readResponse();
    
    // Base64 encode email headers to prevent encoding/spam issues
    $encoded_subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $encoded_from = "=?UTF-8?B?" . base64_encode($from_name) . "?= <$user>";
    
    $headers = [
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "From: $encoded_from",
        "To: <$to>",
        "Subject: $encoded_subject",
        "Date: " . date('r'),
        "Message-ID: <" . time() . "-" . uniqid() . "@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ">",
        "X-Mailer: ABSS-SMTP-Helper/PHP"
    ];
    
    // Payload stream transmission
    $payload = implode("\r\n", $headers) . "\r\n\r\n" . $message . "\r\n.\r\n";
    $writeCommand($payload);
    $data_res = $readResponse();
    
    $writeCommand("QUIT");
    fclose($socket);
    
    return strpos($data_res, '250') !== false;
}

/**
 * Returns a gorgeous, responsive, branding-compliant HTML template.
 */
function get_base_template($title, $content) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title) . '</title>
        <style>
            body { font-family: \'Outfit\', \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7fa; color: #333333; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
            table { border-collapse: collapse; width: 100%; }
            .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 24px; box-shadow: 0 10px 40px rgba(13, 71, 161, 0.04); overflow: hidden; border: 1px solid #f0f4f8; }
            .header { background: linear-gradient(135deg, #0d47a1 0%, #002171 100%); padding: 40px 30px; text-align: center; }
            .header h1 { color: #ffffff; margin: 10px 0 0; font-size: 24px; font-weight: 800; }
            .logo { height: 60px; }
            .body-content { padding: 40px 30px; line-height: 1.6; }
            .greeting { font-size: 18px; font-weight: 700; color: #0d47a1; margin-bottom: 20px; }
            .info-card { background-color: #f8faff; border: 2px solid #eef2ff; border-radius: 16px; padding: 25px; margin: 25px 0; }
            .info-row { display: flex; justify-content: space-between; border-bottom: 1px solid #eef2ff; padding: 12px 0; }
            .info-row:last-child { border-bottom: none; }
            .info-label { font-weight: 700; color: #5c6bc0; font-size: 14px; text-transform: uppercase; }
            .info-val { font-weight: 800; color: #0d47a1; font-size: 15px; }
            .btn { display: inline-block; background-color: #0d47a1; color: #ffffff !important; text-decoration: none; padding: 16px 32px; border-radius: 16px; font-weight: 800; font-size: 15px; text-align: center; margin-top: 20px; box-shadow: 0 8px 15px rgba(13, 71, 161, 0.15); transition: 0.3s; }
            .footer { background-color: #f8faff; border-top: 1px solid #f0f4f8; padding: 30px; text-align: center; font-size: 12px; color: #9aa5ce; font-weight: 600; }
        </style>
    </head>
    <body>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td align="center" style="padding: 20px 0;">
                    <div class="container">
                        <div class="header">
                            <img src="https://abss.lkvmbihar.in/assets/logo.png" alt="ABSS Logo" class="logo" style="display:inline-block; max-height: 60px;">
                            <h1>Awasiya Bal Shikshan Sansthan</h1>
                        </div>
                        <div class="body-content">
                            ' . $content . '
                        </div>
                        <div class="footer">
                            This is an automated notification from the ABSS Digital Portal.<br>
                            Lok Kala Bhavan, Gewalganj, Imamganj, Gaya, Bihar 824206.<br>
                            © 2026 ABSS secure portal.
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </body>
    </html>';
}

/**
 * Generates Fee payment confirmation HTML email.
 */
function get_fee_paid_template($student_name, $amount, $month, $payment_date, $receipt_url) {
    $content = '
    <div class="greeting">Dear Parents / Guardians,</div>
    <p>We are pleased to inform you that your fee payment for <b>' . htmlspecialchars($student_name) . '</b> has been recorded successfully. Please find the transaction summary below:</p>
    
    <div class="info-card">
        <table role="presentation" width="100%">
            <tr>
                <td style="padding: 10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase;">Student Name</td>
                <td style="padding: 10px 0; font-weight:800; color:#0d47a1; text-align:right;">' . htmlspecialchars($student_name) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase; border-top:1px solid #eef2ff;">Amount Paid</td>
                <td style="padding: 10px 0; font-weight:800; color:#1b5e20; text-align:right; font-size:18px; border-top:1px solid #eef2ff;">₹ ' . number_format($amount, 2) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase; border-top:1px solid #eef2ff;">For Month</td>
                <td style="padding: 10px 0; font-weight:800; color:#0d47a1; text-align:right; border-top:1px solid #eef2ff;">' . htmlspecialchars($month) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase; border-top:1px solid #eef2ff;">Payment Date</td>
                <td style="padding: 10px 0; font-weight:800; color:#0d47a1; text-align:right; border-top:1px solid #eef2ff;">' . date('d F, Y', strtotime($payment_date)) . '</td>
            </tr>
        </table>
    </div>

    <p style="margin-bottom: 25px;">You can view and download the official, printable cash receipt directly in your secure Parent Portal account.</p>
    <div style="text-align: center;">
        <a href="' . htmlspecialchars($receipt_url) . '" class="btn" target="_blank">Download Cash Receipt</a>
    </div>';
    
    return get_base_template("Receipt Confirmation - $student_name", $content);
}

/**
 * Generates Academic Result publication HTML email.
 */
function get_result_published_template($student_name, $exam_name, $score, $total_marks, $rank, $dashboard_url) {
    $percentage = round(($score / $total_marks) * 100, 2);
    $rank_display = $rank ? $rank : 'N/A';
    
    $content = '
    <div class="greeting">Dear Parents / Guardians,</div>
    <p>Academic performance assessment results for <b>' . htmlspecialchars($student_name) . '</b> have been published. Here is a summary of the mock/test results:</p>
    
    <div class="info-card">
        <table role="presentation" width="100%">
            <tr>
                <td style="padding: 10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase;">Assessment Module</td>
                <td style="padding: 10px 0; font-weight:800; color:#0d47a1; text-align:right;">' . htmlspecialchars($exam_name) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase; border-top:1px solid #eef2ff;">Score Obtained</td>
                <td style="padding: 10px 0; font-weight:800; color:#0d47a1; text-align:right; border-top:1px solid #eef2ff;">' . $score . ' / ' . $total_marks . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase; border-top:1px solid #eef2ff;">Percentage</td>
                <td style="padding: 10px 0; font-weight:800; color:#1b5e20; text-align:right; font-size:16px; border-top:1px solid #eef2ff;">' . $percentage . '%</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase; border-top:1px solid #eef2ff;">Class Rank</td>
                <td style="padding: 10px 0; font-weight:800; color:#b71c1c; text-align:right; font-size:16px; border-top:1px solid #eef2ff;">' . $rank_display . '</td>
            </tr>
        </table>
    </div>

    <p style="margin-bottom: 25px;">Please log in to your Parent Portal account to view detailed analytics, comparative marks charts, and other class reports.</p>
    <div style="text-align: center;">
        <a href="' . htmlspecialchars($dashboard_url) . '" class="btn" target="_blank">Access Parent Portal</a>
    </div>';
    
    return get_base_template("Grade Assessment Published - $student_name", $content);
}

/**
 * Generates Essential Announcement / Notices broadcast HTML email.
 */
function get_essential_update_template($notice_title, $notice_content, $notice_type, $notice_date) {
    $badge_color = '#0d47a1';
    if ($notice_type === 'important') $badge_color = '#b71c1c';
    if ($notice_type === 'event') $badge_color = '#1b5e20';
    
    $content = '
    <div class="greeting">Important School Announcement</div>
    <p>A new announcement has been published on the ABSS Digital Notice Board on <b>' . date('d F, Y', strtotime($notice_date)) . '</b>.</p>
    
    <div style="border-left: 5px solid ' . $badge_color . '; background-color: #f8faff; padding: 25px; border-radius: 0 16px 16px 0; margin: 25px 0;">
        <div style="display:inline-block; font-size:11px; font-weight:800; color:#ffffff; background-color:' . $badge_color . '; padding:4px 12px; border-radius:100px; text-transform:uppercase; margin-bottom:12px;">' . htmlspecialchars($notice_type) . '</div>
        <h3 style="color:#0d47a1; margin:0 0 15px 0; font-size:18px; font-weight:800;">' . htmlspecialchars($notice_title) . '</h3>
        <p style="margin:0; font-size:14px; line-height:1.7; color:#3f51b5; font-weight:500;">' . nl2br(htmlspecialchars($notice_content)) . '</p>
    </div>

    <p style="margin-bottom: 25px;">You can view the full announcement list and history in your secure Parent Portal account.</p>
    <div style="text-align: center;">
        <a href="http://localhost/abss/admin/login.php?role=parent" class="btn" target="_blank">Go to Portal Notice Board</a>
    </div>';
    
    return get_base_template("Announcement: $notice_title", $content);
}

/**
 * Generates Support Ticket raised confirmation HTML email.
 */
function get_ticket_raised_template($parent_name, $subject, $message, $status, $ticket_id) {
    $content = '
    <div class="greeting">Support Ticket Raised</div>
    <p>Dear ' . htmlspecialchars($parent_name) . ',</p>
    <p>Your support ticket has been recorded successfully. Our administrative desk is reviewing your query, and we will get back to you shortly.</p>
    
    <div class="info-card">
        <table role="presentation" width="100%">
            <tr>
                <td style="padding: 10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase;">Ticket ID</td>
                <td style="padding: 10px 0; font-weight:800; color:#0d47a1; text-align:right;">#ABSS-TKT-' . str_pad($ticket_id, 4, '0', STR_PAD_LEFT) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase; border-top:1px solid #eef2ff;">Subject</td>
                <td style="padding: 10px 0; font-weight:800; color:#0d47a1; text-align:right; border-top:1px solid #eef2ff;">' . htmlspecialchars($subject) . '</td>
            </tr>
            <tr>
                <td style="padding: 10px 0; font-weight:700; color:#5c6bc0; font-size:13px; text-transform:uppercase; border-top:1px solid #eef2ff;">Status</td>
                <td style="padding: 10px 0; font-weight:800; color:#ef6c00; text-align:right; text-transform:uppercase; border-top:1px solid #eef2ff;">' . htmlspecialchars($status) . '</td>
            </tr>
        </table>
    </div>

    <div style="background-color:#f8faff; border-left:4px solid #0d47a1; padding:15px; border-radius:4px; margin-top:20px; font-size:14px; color:#333; line-height:1.6;">
        <strong>Your Message:</strong><br>
        ' . nl2br(htmlspecialchars($message)) . '
    </div>';
    
    return get_base_template("Support Ticket Recorded", $content);
}

/**
 * Generates Support Ticket resolved HTML email.
 */
function get_ticket_resolved_template($parent_name, $subject, $ticket_id) {
    $content = '
    <div class="greeting">Support Ticket Resolved</div>
    <p>Dear ' . htmlspecialchars($parent_name) . ',</p>
    <p>We are pleased to inform you that your support ticket **#ABSS-TKT-' . str_pad($ticket_id, 4, '0', STR_PAD_LEFT) . '** regarding **' . htmlspecialchars($subject) . '** has been resolved by our school administration.</p>
    
    <p>If you have any further questions, feel free to reply directly to this email or raise another ticket from your Parent Portal.</p>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="http://localhost/abss/admin/login.php?role=parent" class="btn" target="_blank">Access Parent Portal</a>
    </div>';
    
    return get_base_template("Support Ticket Resolved", $content);
}
?>
