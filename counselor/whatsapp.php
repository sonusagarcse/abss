<?php
// counselor/whatsapp.php - Quick WhatsApp Outreach Hub
require_once 'includes/auth.php';
require_counselor_module('whatsapp');

$pre_phone = $_GET['phone'] ?? '';
$pre_name = $_GET['name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Hub | Counselor Portal</title>
    <?php include 'includes/head_css.php'; ?>
    <style>
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 700; color: #475569; margin-bottom: 6px; text-transform: uppercase; }
        .form-control { width: 100%; padding: 11px 14px; border-radius: 12px; border: 1.5px solid #cbd5e1; font-size: 0.92rem; font-family: inherit; box-sizing: border-box; }
        .form-control:focus { outline: none; border-color: #22c55e; }
        .templates-list { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; }
        .tpl-card { background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 14px; cursor: pointer; transition: all 0.2s; }
        .tpl-card:hover { border-color: #22c55e; background: #f0fdf4; }
        .tpl-title { font-weight: 800; font-size: 0.88rem; color: #0f172a; margin-bottom: 4px; }
        .tpl-preview { font-size: 0.8rem; color: #64748b; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content" style="padding: 28px;">
        <div style="max-width: 1000px; margin: 0 auto;">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h1 style="font-size: 1.55rem; font-weight: 800; color: #0f172a; margin: 0;">
                        <i class="fab fa-whatsapp" style="color: #22c55e;"></i> WhatsApp Counseling Outreach Hub
                    </h1>
                    <p style="color: #64748b; font-size: 0.86rem; margin: 3px 0 0 0;">
                        Direct 1-click WhatsApp messaging bridge for candidate consultations and follow-ups.
                    </p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <!-- Compose Form -->
                <div class="portal-card">
                    <h2 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0 0 16px 0;">Compose Outreach Message</h2>
                    
                    <div class="form-group">
                        <label>Candidate / Parent Name</label>
                        <input type="text" id="target_name" class="form-control" value="<?php echo htmlspecialchars($pre_name); ?>" placeholder="e.g. Rahul Kumar">
                    </div>

                    <div class="form-group">
                        <label>Parent Mobile Number *</label>
                        <input type="tel" id="target_phone" class="form-control" value="<?php echo htmlspecialchars($pre_phone); ?>" placeholder="e.g. 9876543210 (10 digits)">
                    </div>

                    <div class="form-group">
                        <label>Message Content *</label>
                        <textarea id="target_message" rows="6" class="form-control" placeholder="Type your personalized message or select a counseling template from the right..."></textarea>
                    </div>

                    <button type="button" onclick="sendWhatsApp()" class="btn-portal" style="width: 100%; background: #22c55e; color: #fff; font-size: 0.95rem; padding: 12px; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.3);">
                        <i class="fab fa-whatsapp" style="font-size: 1.2rem;"></i> Open in WhatsApp Web / App
                    </button>
                </div>

                <!-- Template Library -->
                <div class="portal-card">
                    <h2 style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin: 0 0 6px 0;">Counseling Message Templates</h2>
                    <p style="color: #64748b; font-size: 0.8rem; margin: 0 0 14px 0;">Click any template to populate the message box.</p>

                    <div class="templates-list">
                        <div class="tpl-card" onclick="applyTemplate(1)">
                            <div class="tpl-title"><i class="fas fa-hand-holding-heart" style="color: #22c55e;"></i> Admission Inquiry Follow-up</div>
                            <div class="tpl-preview">Namaste! Thank you for inquiring about admission at Amar Bharat Siksha Sansthan...</div>
                        </div>

                        <div class="tpl-card" onclick="applyTemplate(2)">
                            <div class="tpl-title"><i class="fas fa-calendar-check" style="color: #2563eb;"></i> Campus Counseling Session Invite</div>
                            <div class="tpl-preview">Dear Parent, We cordially invite you and your child for a one-on-one career counseling...</div>
                        </div>

                        <div class="tpl-card" onclick="applyTemplate(3)">
                            <div class="tpl-title"><i class="fas fa-file-alt" style="color: #ea580c;"></i> Entrance Exam Registration Reminder</div>
                            <div class="tpl-preview">Important Reminder: Registration for the upcoming entrance coaching batch is now closing...</div>
                        </div>

                        <div class="tpl-card" onclick="applyTemplate(4)">
                            <div class="tpl-title"><i class="fas fa-folder-open" style="color: #7c3aed;"></i> Required Documents Submission</div>
                            <div class="tpl-preview">Dear Parent, Please bring the following documents for admission verification: 1. Aadhaar Card...</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        function applyTemplate(id) {
            const name = document.getElementById('target_name').value.trim() || 'Parent';
            let msg = '';
            if (id === 1) {
                msg = `Namaste! Thank you for inquiring about admissions at Amar Bharat Siksha Sansthan (ABSS) for ${name}.\n\nOur specialized coaching batches for Sainik School, Military School (RMS), Navodaya, and BHU CHS offer rigorous academic preparation with top faculty.\n\nWould you like to schedule a quick call or visit our campus for a personalized counseling session?\n\nRegards,\nAdmission Counselor\nAmar Bharat Siksha Sansthan`;
            } else if (id === 2) {
                msg = `Dear ${name},\n\nWe cordially invite you and your child for a one-on-one counseling session at Amar Bharat Siksha Sansthan campus.\n\nOur counselors will evaluate your child's aptitude, outline the entrance exam pattern, and recommend the best preparation roadmap.\n\nPlease let us know your preferred date and time to reserve your slot.\n\nWarm regards,\nABSS Counseling Desk`;
            } else if (id === 3) {
                msg = `Important Admission Update for ${name}:\n\nAdmissions for the upcoming academic session at Amar Bharat Siksha Sansthan are now active with limited seats per batch.\n\nKindly complete the enrollment formalities soon to secure your seat.\n\nFor any questions, feel free to reply to this message.\n\nABSS Admissions Office`;
            } else if (id === 4) {
                msg = `Dear ${name},\n\nKindly note the checklist of documents required for admission completion:\n1. Student Aadhaar Card & 4 Passport Photos\n2. Previous School Marksheet / TC\n3. Parent ID Proof\n\nPlease visit our admission desk to finalize registration.\n\nABSS Desk`;
            }
            document.getElementById('target_message').value = msg;
        }

        function sendWhatsApp() {
            let phone = document.getElementById('target_phone').value.trim().replace(/[^0-9]/g, '');
            const msg = document.getElementById('target_message').value.trim();

            if (!phone) {
                alert('Please enter a parent mobile number.');
                return;
            }
            if (phone.length === 10) {
                phone = '91' + phone;
            }
            if (!msg) {
                alert('Please enter or select a message to send.');
                return;
            }

            const url = `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
            window.open(url, '_blank');
        }
    </script>
</body>
</html>

