<?php
// contact.php - Official Contact Page for ABSS Imamganj
require_once 'includes/security.php';
require_once 'config/db.php';
$conn = getDB();
$settings = getAllSettings();

$contact_phone = $settings['contact_phone'] ?? '+91 9523012888';
$contact_whatsapp = $settings['contact_whatsapp'] ?? '919523012888';
$contact_email = $settings['contact_email'] ?? 'abssimamganj@gmail.com';
$contact_address = $settings['contact_address'] ?? 'Lok Kala Bhavan, Main Road, Imamganj, Gaya, Bihar - 824206';
$contact_hours_weekday = $settings['contact_hours_weekday'] ?? 'Mon - Sat: 8:00 AM - 7:00 PM';
$contact_hours_sunday = $settings['contact_hours_sunday'] ?? 'Sunday: 9:00 AM - 1:00 PM';
$contact_map_query = $settings['contact_map_query'] ?? 'Imamganj, Gaya, Bihar 824206';
$contact_map_iframe = trim($settings['contact_map_iframe'] ?? '');
$contact_map_link = $settings['contact_map_link'] ?? 'https://maps.google.com/?q=Lok+Kala+Bhavan+Imamganj+Gaya+Bihar';

$page_title = "Contact Us | Awasiya Bal Shikshan Sansthan (ABSS) Imamganj";
include 'includes/header.php';
?>

<!-- Page Breadcrumb / Hero Header -->
<section style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e40af 100%); padding: 70px 0 50px 0; color: #ffffff; position: relative; overflow: hidden;">
    <div style="position: absolute; top: -50%; right: -20%; width: 600px; height: 600px; background: radial-gradient(circle, rgba(56,189,248,0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none;"></div>
    
    <div class="container" style="position: relative; z-index: 2; text-align: center;">
        <span style="display: inline-flex; align-items: center; gap: 8px; background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.35); color: #38bdf8; padding: 6px 18px; border-radius: 50px; font-size: 0.82rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 16px;">
            <i class="fas fa-headset"></i> 24/7 Student & Parent Helpdesk
        </span>
        <h1 style="font-size: clamp(2rem, 3.5vw, 3rem); font-weight: 900; margin: 0 0 14px 0; line-height: 1.15; color: #ffffff;">
            Get in Touch With Us
        </h1>
        <p style="color: #cbd5e1; font-size: 1.05rem; max-width: 680px; margin: 0 auto; font-weight: 500; line-height: 1.6;">
            Have queries regarding Admissions 2026-27, competitive entrance programs, residential hostel facilities, or campus tours? Our dedicated team is here to assist you.
        </p>
    </div>
</section>

<!-- Main Contact Section -->
<section style="padding: 70px 0; background: #f8fafc;">
    <div class="container">

        <!-- Notification / Status Banner -->
        <?php if (isset($_SESSION['contact_success'])): ?>
            <div style="background: #f0fdf4; border: 1px solid #86efac; color: #166534; padding: 18px 24px; border-radius: 16px; margin-bottom: 35px; display: flex; align-items: center; gap: 14px; box-shadow: 0 4px 15px rgba(22, 101, 52, 0.08);">
                <i class="fas fa-check-circle" style="font-size: 1.5rem; color: #22c55e;"></i>
                <div>
                    <strong style="display: block; font-size: 1rem; margin-bottom: 2px;">Query Submitted Successfully!</strong>
                    <span><?php echo htmlspecialchars($_SESSION['contact_success']); unset($_SESSION['contact_success']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['contact_error'])): ?>
            <div style="background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 18px 24px; border-radius: 16px; margin-bottom: 35px; display: flex; align-items: center; gap: 14px; box-shadow: 0 4px 15px rgba(153, 27, 27, 0.08);">
                <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem; color: #ef4444;"></i>
                <div>
                    <strong style="display: block; font-size: 1rem; margin-bottom: 2px;">Submission Error</strong>
                    <span><?php echo htmlspecialchars($_SESSION['contact_error']); unset($_SESSION['contact_error']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Contact Information Cards Grid (4 Column) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 50px;">
            
            <!-- Card 1: Phone / Hotline -->
            <div class="contact-info-card">
                <div class="icon-circle" style="background: #eff6ff; color: #2563eb;">
                    <i class="fas fa-phone-volume"></i>
                </div>
                <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0 0 8px 0;">Helpline & Phone</h3>
                <p style="color: #64748b; font-size: 0.88rem; margin: 0 0 12px 0;">Direct institutional helpline for immediate consultation:</p>
                <div style="margin-top: auto;">
                    <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $contact_phone)) ?>" style="display: block; font-size: 1.05rem; font-weight: 900; color: #2563eb; text-decoration: none; margin-bottom: 6px;">
                        <?= htmlspecialchars($contact_phone) ?>
                    </a>
                    <a href="https://wa.me/<?= htmlspecialchars(preg_replace('/[^0-9]/', '', $contact_whatsapp)) ?>?text=Hello%20ABSS%20Imamganj%2C%20I%20have%20an%20inquiry%20regarding%20admission." target="_blank" style="display: inline-flex; align-items: center; gap: 6px; color: #059669; font-weight: 700; font-size: 0.85rem; text-decoration: none;">
                        <i class="fab fa-whatsapp"></i> Chat on WhatsApp
                    </a>
                </div>
            </div>

            <!-- Card 2: Email -->
            <div class="contact-info-card">
                <div class="icon-circle" style="background: #fdf2f8; color: #db2777;">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0 0 8px 0;">Email Support</h3>
                <p style="color: #64748b; font-size: 0.88rem; margin: 0 0 12px 0;">Send us your detailed admission queries and paperwork:</p>
                <div style="margin-top: auto;">
                    <a href="mailto:<?= htmlspecialchars($contact_email) ?>" style="display: block; font-size: 0.95rem; font-weight: 800; color: #db2777; text-decoration: none; word-break: break-all; margin-bottom: 6px;">
                        <?= htmlspecialchars($contact_email) ?>
                    </a>
                    <span style="color: #64748b; font-size: 0.8rem; font-weight: 600;">Average response time: 2-4 hours</span>
                </div>
            </div>

            <!-- Card 3: Location / Address -->
            <div class="contact-info-card">
                <div class="icon-circle" style="background: #f0fdf4; color: #16a34a;">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0 0 8px 0;">Campus Location</h3>
                <p style="color: #64748b; font-size: 0.88rem; margin: 0 0 12px 0;">
                    <?= htmlspecialchars($contact_address) ?>
                </p>
                <div style="margin-top: auto;">
                    <a href="#campus-map" style="display: inline-flex; align-items: center; gap: 6px; color: #16a34a; font-weight: 700; font-size: 0.85rem; text-decoration: none;">
                        <i class="fas fa-directions"></i> View Google Map Below
                    </a>
                </div>
            </div>

            <!-- Card 4: Office Hours -->
            <div class="contact-info-card">
                <div class="icon-circle" style="background: #fffbeb; color: #d97706;">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0 0 8px 0;">Office Hours</h3>
                <p style="color: #64748b; font-size: 0.88rem; margin: 0 0 12px 0;">Open for campus visits, counseling, and form submission:</p>
                <div style="margin-top: auto; font-size: 0.84rem; color: #334155; font-weight: 700; line-height: 1.5;">
                    <div><?= htmlspecialchars($contact_hours_weekday) ?></div>
                    <div style="color: #d97706;"><?= htmlspecialchars($contact_hours_sunday) ?></div>
                </div>
            </div>

        </div>

        <!-- 2-COLUMN SECTION: MAP & SEND A QUERY FORM -->
        <div style="display: grid; grid-template-columns: 1fr 1.15fr; gap: 35px; align-items: flex-start;" class="contact-two-col">
            
            <!-- LEFT COLUMN: INTERACTIVE MAP & DIRECTIONS -->
            <div id="campus-map" style="background: #ffffff; border-radius: 28px; padding: 30px; border: 1px solid #e2e8f0; box-shadow: 0 4px 25px rgba(0,0,0,0.03);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <span style="color: #2563eb; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em;">Interactive Navigation</span>
                        <h2 style="font-size: 1.5rem; color: #0f172a; font-weight: 900; margin: 4px 0 0 0;">Campus Map & Location</h2>
                    </div>
                    <a href="<?= htmlspecialchars($contact_map_link) ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; background: #eff6ff; color: #2563eb; padding: 8px 16px; border-radius: 50px; font-weight: 800; font-size: 0.82rem; text-decoration: none; border: 1px solid #bfdbfe;">
                        <i class="fas fa-external-link-alt"></i> Open in Google Maps
                    </a>
                </div>

                <!-- Google Maps Embedded iFrame -->
                <div style="border-radius: 20px; overflow: hidden; height: 360px; border: 1px solid #cbd5e1; box-shadow: inset 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 24px; position: relative;">
                    <?php if (!empty($contact_map_iframe)): ?>
                        <?php if (strpos($contact_map_iframe, '<iframe') !== false): ?>
                            <?= preg_replace('/width="[^"]*"/', 'width="100%"', preg_replace('/height="[^"]*"/', 'height="100%"', $contact_map_iframe)) ?>
                        <?php else: ?>
                            <iframe 
                                title="ABSS Campus Location Map"
                                src="<?= htmlspecialchars($contact_map_iframe) ?>" 
                                width="100%" 
                                height="100%" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        <?php endif; ?>
                    <?php else: ?>
                        <iframe 
                            title="ABSS Campus Location Map"
                            src="https://maps.google.com/maps?q=<?= urlencode($contact_map_query) ?>&t=&z=14&ie=UTF8&iwloc=&output=embed" 
                            width="100%" 
                            height="100%" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    <?php endif; ?>
                </div>

                <!-- Travel & Route Guidance -->
                <div style="background: #f8fafc; border-radius: 18px; padding: 20px; border: 1px solid #e2e8f0;">
                    <h4 style="font-size: 0.95rem; color: #0f172a; font-weight: 800; margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-route" style="color: #2563eb;"></i> How to Reach ABSS Imamganj
                    </h4>
                    <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.85rem; color: #475569; line-height: 1.7; font-weight: 600;">
                        <li style="margin-bottom: 6px;">
                            <strong style="color: #0f172a;"><i class="fas fa-bus-alt" style="color:#059669; width:18px;"></i> By Bus / Road:</strong> Imamganj Bus Stand is just 300 meters away from Lok Kala Bhavan on Main Road.
                        </li>
                        <li style="margin-bottom: 6px;">
                            <strong style="color: #0f172a;"><i class="fas fa-train" style="color:#2563eb; width:18px;"></i> Nearest Railway Station:</strong> Gaya Junction (65 km) & Guraru Station (50 km). Frequent direct cabs and buses operate daily.
                        </li>
                        <li>
                            <strong style="color: #0f172a;"><i class="fas fa-landmark" style="color:#d97706; width:18px;"></i> Key Landmark:</strong> Lok Kala Bhavan, Main Market, Imamganj, Gaya.
                        </li>
                    </ul>
                </div>
            </div>

            <!-- RIGHT COLUMN: SEND A QUERY / INQUIRY FORM -->
            <div style="background: #ffffff; border-radius: 28px; padding: 35px; border: 1px solid #e2e8f0; box-shadow: 0 10px 35px rgba(0,0,0,0.04);">
                <div style="margin-bottom: 25px;">
                    <span style="color: #2563eb; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em;">Send An Online Query</span>
                    <h2 style="font-size: 1.6rem; color: #0f172a; font-weight: 900; margin: 4px 0 6px 0;">Have a Question? Write to Us</h2>
                    <p style="color: #64748b; font-size: 0.88rem; margin: 0; font-weight: 500;">
                        Fill out the query form below. You may also attach relevant documents or student records (PDF, JPG, PNG, DOCX up to 5MB).
                    </p>
                </div>

                <form action="process_contact.php" method="POST" enctype="multipart/form-data" id="contactForm">
                    <!-- CSRF Protection Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generate_csrf_token()); ?>">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;" class="form-row-resp">
                        <div>
                            <label for="contact_name" style="display: block; font-size: 0.85rem; font-weight: 800; color: #1e293b; margin-bottom: 6px;">
                                Full Name <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" id="contact_name" name="name" required placeholder="e.g. Ramesh Kumar" class="form-input-field">
                        </div>

                        <div>
                            <label for="contact_phone" style="display: block; font-size: 0.85rem; font-weight: 800; color: #1e293b; margin-bottom: 6px;">
                                Phone / Mobile Number <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="tel" id="contact_phone" name="phone" required pattern="[0-9]{10}" placeholder="10-digit mobile number" class="form-input-field">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;" class="form-row-resp">
                        <div>
                            <label for="contact_email" style="display: block; font-size: 0.85rem; font-weight: 800; color: #1e293b; margin-bottom: 6px;">
                                Email Address <small style="color: #94a3b8; font-weight: 600;">(Optional)</small>
                            </label>
                            <input type="email" id="contact_email" name="email" placeholder="e.g. parent@gmail.com" class="form-input-field">
                        </div>

                        <div>
                            <label for="query_type" style="display: block; font-size: 0.85rem; font-weight: 800; color: #1e293b; margin-bottom: 6px;">
                                Inquiry Category <span style="color: #ef4444;">*</span>
                            </label>
                            <select id="query_type" name="inquiry_type" required class="form-input-field" style="background-color: #fff;">
                                <option value="Admission 2026-27">Admission Inquiry 2026-27</option>
                                <option value="Netarhat Entrance Coaching">Netarhat Entrance Coaching</option>
                                <option value="Sainik School (AISSEE)">Sainik School (AISSEE)</option>
                                <option value="Navodaya / Simultala">Navodaya / Simultala Vidyalaya</option>
                                <option value="Residential Hostel Facility">Residential Hostel Facility</option>
                                <option value="Fee Structure & Billing">Fee Structure & Billing</option>
                                <option value="General Query">General Institutional Query</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label for="contact_subject" style="display: block; font-size: 0.85rem; font-weight: 800; color: #1e293b; margin-bottom: 6px;">
                            Subject / Purpose <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="contact_subject" name="subject" required placeholder="e.g. Inquiry regarding Class 5 Hostler batch admission" class="form-input-field">
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label for="contact_message" style="display: block; font-size: 0.85rem; font-weight: 800; color: #1e293b; margin-bottom: 6px;">
                            Detailed Message / Query <span style="color: #ef4444;">*</span>
                        </label>
                        <textarea id="contact_message" name="message" rows="4" required placeholder="Please describe your query in detail..." class="form-input-field" style="resize: vertical;"></textarea>
                    </div>

                    <!-- Secure File Attachment Upload Field -->
                    <div style="margin-bottom: 24px;">
                        <label for="query_document" style="display: block; font-size: 0.85rem; font-weight: 800; color: #1e293b; margin-bottom: 6px;">
                            Attach Document / Student Record <small style="color: #94a3b8; font-weight: 600;">(Optional)</small>
                        </label>
                        <div class="file-upload-box">
                            <input type="file" id="query_document" name="query_document" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx" style="font-size: 0.85rem; color: #475569; width: 100%;">
                            <div style="margin-top: 6px; font-size: 0.76rem; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-shield-alt" style="color: #16a34a;"></i> Allowed formats: PDF, JPG, PNG, WEBP, DOC, DOCX (Max 5MB). Script uploads strictly prohibited.
                            </div>
                        </div>
                    </div>

                    <button type="submit" id="btnSubmitQuery" style="width: 100%; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; border: none; padding: 15px 24px; border-radius: 14px; font-size: 1rem; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3); transition: all 0.3s ease;">
                        <i class="fas fa-paper-plane"></i> Send Query to ABSS Administration
                    </button>
                </form>
            </div>

        </div>

    </div>
</section>

<!-- Additional Inline Styles for Contact Page UI -->
<style>
    .contact-info-card {
        background: #ffffff;
        border-radius: 24px;
        padding: 28px 22px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .contact-info-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.07);
    }
    .icon-circle {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin-bottom: 18px;
    }
    .form-input-field {
        width: 100%;
        padding: 12px 16px;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.9rem;
        color: #0f172a;
        font-family: inherit;
        font-weight: 600;
        box-sizing: border-box;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .form-input-field:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    .file-upload-box {
        background: #f8fafc;
        border: 1.5px dashed #cbd5e1;
        border-radius: 12px;
        padding: 14px 16px;
        transition: border-color 0.2s ease;
    }
    .file-upload-box:hover {
        border-color: #2563eb;
    }

    @media (max-width: 900px) {
        .contact-two-col {
            grid-template-columns: 1fr !important;
        }
        .form-row-resp {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<script>
    document.getElementById('contactForm')?.addEventListener('submit', function() {
        var btn = document.getElementById('btnSubmitQuery');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Your Query...';
            btn.style.opacity = '0.8';
            btn.style.pointerEvents = 'none';
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
