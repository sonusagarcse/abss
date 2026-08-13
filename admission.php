<?php
require_once 'config/db.php';
$conn = getDB();
$settings = getAllSettings();
$school_list = $conn->query("SELECT school_name FROM schools ORDER BY school_name ASC");
include 'includes/header.php';
?>

<!-- Admission Header -->
<section style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0d47a1 100%); padding: 65px 0 75px; color: #ffffff; text-align: center; position: relative; overflow: hidden;">
    <div style="position: absolute; top: -100px; right: -100px; width: 350px; height: 350px; background: rgba(56, 189, 248, 0.15); filter: blur(90px); border-radius: 50%;"></div>
    <div style="position: absolute; bottom: -100px; left: -100px; width: 350px; height: 350px; background: rgba(124, 58, 237, 0.15); filter: blur(90px); border-radius: 50%;"></div>

    <div class="container" style="position: relative; z-index: 2; max-width: 800px;">
        <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255, 255, 255, 0.12); padding: 6px 18px; border-radius: 50px; border: 1px solid rgba(255, 255, 255, 0.2); font-size: 0.82rem; font-weight: 800; margin-bottom: 16px; color: #38bdf8;">
            <i class="fas fa-bolt"></i> Fast Online Registration • Session 2026-27
        </div>
        <h1 style="font-size: 2.5rem; font-weight: 900; margin: 0 0 10px 0; letter-spacing: -0.02em;">
            Student Online <span style="background: linear-gradient(135deg, #38bdf8, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Admission</span>
        </h1>
        <p style="font-size: 1.02rem; color: #cbd5e1; margin: 0 auto; line-height: 1.6; font-weight: 500;">
            Complete registration in under 2 minutes. Fill in student & guardian details to reserve your entrance seat.
        </p>
    </div>
</section>

<!-- Admission Form Section: 2-Column Grid Layout -->
<section style="padding: 50px 0 80px; background: #f8fafc;">
    <div class="container">
        
        <div class="adm-grid-2col">
            
            <!-- LEFT COLUMN: HELPLINE & GUIDELINES CARD -->
            <div>
                <div style="background: #ffffff; border-radius: 24px; padding: 30px; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.02); position: sticky; top: 20px;">
                    
                    <div style="display:flex; align-items:center; gap:14px; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #f1f5f9;">
                        <div style="width:48px; height:48px; border-radius:14px; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:1.1rem; color:#0f172a; font-weight:800;">Admission Desk</h3>
                            <p style="margin:0; color:#64748b; font-size:0.82rem; font-weight:600;">ABSS Imamganj Center</p>
                        </div>
                    </div>

                    <div style="background:#f8fafc; border-radius:16px; padding:18px; border:1px solid #e2e8f0; margin-bottom:20px;">
                        <h4 style="margin:0 0 8px 0; color:#0f172a; font-size:0.9rem; font-weight:800;"><i class="fas fa-phone-alt" style="color:#2563eb;"></i> Need Registration Help?</h4>
                        <div style="font-size:1.1rem; font-weight:900; color:#2563eb; margin-bottom:4px;">+91 9523012888</div>
                        <small style="color:#64748b; font-weight:600;">Available 8:00 AM - 7:00 PM (Mon-Sat)</small>
                    </div>

                    <h4 style="margin:0 0 12px 0; color:#0f172a; font-size:0.88rem; font-weight:800; text-transform:uppercase; letter-spacing:0.04em;">Required Checklist</h4>
                    <ul style="list-style:none; padding:0; margin:0 0 25px 0;">
                        <li style="display:flex; align-items:center; gap:10px; margin-bottom:10px; font-size:0.88rem; font-weight:600; color:#475569;">
                            <i class="fas fa-check-circle" style="color:#16a34a;"></i> Student Passport Photo
                        </li>
                        <li style="display:flex; align-items:center; gap:10px; margin-bottom:10px; font-size:0.88rem; font-weight:600; color:#475569;">
                            <i class="fas fa-check-circle" style="color:#16a34a;"></i> Valid Parent Phone Number
                        </li>
                        <li style="display:flex; align-items:center; gap:10px; margin-bottom:10px; font-size:0.88rem; font-weight:600; color:#475569;">
                            <i class="fas fa-check-circle" style="color:#16a34a;"></i> Aadhaar / ID Proof (at counseling)
                        </li>
                    </ul>

                    <div style="background:linear-gradient(135deg, #0f172a, #1d4ed8); color:#ffffff; padding:20px; border-radius:18px;">
                        <div style="font-size:0.82rem; font-weight:800; color:#38bdf8; text-transform:uppercase; margin-bottom:4px;">Selection Guarantee</div>
                        <h4 style="margin:0 0 6px 0; font-size:1rem; font-weight:900;">500+ Top Entrance Selections</h4>
                        <p style="margin:0; font-size:0.82rem; color:#cbd5e1; line-height:1.5;">Specialized preparation for Netarhat, Sainik School, Navodaya & Simultala.</p>
                    </div>

                </div>
            </div>

            <!-- RIGHT COLUMN: ONLINE REGISTRATION FORM -->
            <div>
                <div style="background: #ffffff; border-radius: 28px; padding: 35px 30px; border: 1px solid #e2e8f0; box-shadow: 0 10px 35px rgba(0,0,0,0.03);" class="adm-card-resp">
                    
                    <div style="margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <h2 style="font-size: 1.4rem; color: #0f172a; font-weight: 900; margin: 0 0 4px 0;">Online Registration Form</h2>
                            <p style="margin: 0; color: #64748b; font-size: 0.85rem; font-weight: 600;">Fields marked with (<span style="color:#ef4444;">*</span>) are mandatory.</p>
                        </div>
                        <span style="background: #eff6ff; color: #2563eb; padding: 5px 12px; border-radius: 50px; font-weight: 800; font-size: 0.78rem;">
                            <i class="fas fa-lock"></i> SSL Secured
                        </span>
                    </div>

                    <form action="process_admission.php" method="POST" enctype="multipart/form-data" id="admissionForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                        <!-- 2-COLUMN INNER FORM GRID -->
                        <div class="form-inner-2col">
                            
                            <!-- INNER COLUMN 1: STUDENT INFORMATION -->
                            <div>
                                <div style="background: #f8fafc; border-radius: 14px; padding: 10px 14px; margin-bottom: 20px; border: 1px solid #e2e8f0; color: #0f172a; font-weight: 900; font-size: 0.88rem; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-user-graduate" style="color: #2563eb;"></i> 1. Student Details
                                </div>

                                <!-- Photo Upload Field -->
                                <div class="adm-field-group" style="margin-bottom: 18px;">
                                    <label class="adm-label">Student Passport Photo <small style="color:#64748b; font-weight:500;">(Optional)</small></label>
                                    <div style="display: flex; gap: 14px; align-items: center; background: #f8fafc; padding: 12px; border-radius: 14px; border: 1.5px dashed #cbd5e1;">
                                        <div id="pubPhotoCircle" style="width: 65px; height: 65px; border-radius: 14px; background: #ffffff; border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; position: relative;">
                                            <img id="pubPhotoPreview" src="" alt="" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px; display: none;">
                                            <i id="pubPhotoIcon" class="fas fa-camera" style="font-size: 1.4rem; color: #2563eb;"></i>
                                        </div>
                                        <div style="flex: 1; min-width: 0;">
                                            <input type="file" name="student_photo" accept="image/*" class="adm-input" style="padding: 6px 10px; font-size: 0.8rem; background: #ffffff;" onchange="pubPhotoPreview(this)" id="pub_student_photo">
                                            <small style="color:#64748b; display:block; margin-top:3px; font-weight:600; font-size:0.75rem;">JPG/PNG/WEBP (Max 5MB)</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="adm-field-group" style="margin-bottom: 15px;">
                                    <label class="adm-label">Student Full Name <span style="color:#ef4444;">*</span></label>
                                    <input type="text" name="student_name" class="adm-input" placeholder="Rahul Kumar" required>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                                    <div class="adm-field-group">
                                        <label class="adm-label">Date of Birth <span style="color:#ef4444;">*</span></label>
                                        <input type="date" name="dob" class="adm-input" required>
                                    </div>
                                    <div class="adm-field-group">
                                        <label class="adm-label">Gender <span style="color:#ef4444;">*</span></label>
                                        <select name="gender" class="adm-input" required>
                                            <option value="">Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="adm-field-group" style="margin-bottom: 15px;">
                                    <label class="adm-label">Target School Program <span style="color:#ef4444;">*</span></label>
                                    <select name="target_program" class="adm-input" required>
                                        <option value="">Select Target School</option>
                                        <?php if ($school_list && $school_list->num_rows > 0): ?>
                                            <?php while($s = $school_list->fetch_assoc()): ?>
                                                <option value="<?php echo htmlspecialchars($s['school_name']); ?>"><?php echo htmlspecialchars($s['school_name']); ?></option>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <option value="Netarhat Residential">Netarhat Residential Entrance</option>
                                            <option value="Sainik School (AISSEE)">Sainik School (AISSEE)</option>
                                            <option value="Navodaya Vidyalaya (JNVST)">Navodaya Vidyalaya (JNVST)</option>
                                            <option value="Simultala Residential">Simultala Residential</option>
                                            <option value="BHU CHS Entrance">BHU CHS Entrance</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="adm-field-group" style="margin-bottom: 20px;">
                                    <label class="adm-label">Scholar Mode <span style="color:#ef4444;">*</span></label>
                                    <select name="scholar_mode" class="adm-input" required>
                                        <option value="">Select Mode</option>
                                        <option value="Hostler">Hostler (Residential Boarding)</option>
                                        <option value="Day Scholar">Day Scholar (Full Day)</option>
                                        <option value="Tuition">Tuition (Subject Coaching)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- INNER COLUMN 2: GUARDIAN CONTACT & ADDRESS -->
                            <div>
                                <div style="background: #f8fafc; border-radius: 14px; padding: 10px 14px; margin-bottom: 20px; border: 1px solid #e2e8f0; color: #0f172a; font-weight: 900; font-size: 0.88rem; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-user-shield" style="color: #2563eb;"></i> 2. Guardian Contact
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                                    <div class="adm-field-group">
                                        <label class="adm-label">Parent Name <span style="color:#ef4444;">*</span></label>
                                        <input type="text" name="parent_name" class="adm-input" placeholder="Father/Mother name" required>
                                    </div>
                                    <div class="adm-field-group">
                                        <label class="adm-label">Relation <span style="color:#ef4444;">*</span></label>
                                        <select name="guardian_relationship" class="adm-input" required>
                                            <option value="Father">Father</option>
                                            <option value="Mother">Mother</option>
                                            <option value="Guardian">Guardian</option>
                                        </select>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                                    <div class="adm-field-group">
                                        <label class="adm-label">Mobile Number <span style="color:#ef4444;">*</span></label>
                                        <input type="tel" name="phone" class="adm-input" placeholder="10-digit mobile" pattern="[0-9]{10}" required>
                                    </div>
                                    <div class="adm-field-group">
                                        <label class="adm-label">Email <small style="color:#64748b; font-weight:500;">(Optional)</small></label>
                                        <input type="email" name="email" class="adm-input" placeholder="Parent email">
                                    </div>
                                </div>

                                <div class="adm-field-group" style="margin-bottom: 15px;">
                                    <label class="adm-label">Home Address <span style="color:#ef4444;">*</span></label>
                                    <input type="text" name="home_address" class="adm-input" placeholder="Village / Colony / Street" required>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 20px;">
                                    <div class="adm-field-group">
                                        <label class="adm-label">City <span style="color:#ef4444;">*</span></label>
                                        <input type="text" name="city" class="adm-input" placeholder="City" required>
                                    </div>
                                    <div class="adm-field-group">
                                        <label class="adm-label">State <span style="color:#ef4444;">*</span></label>
                                        <input type="text" name="state" class="adm-input" value="Bihar" required>
                                    </div>
                                    <div class="adm-field-group">
                                        <label class="adm-label">PIN</label>
                                        <input type="text" name="zip_code" class="adm-input" placeholder="PIN">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- OPTIONAL ADDITIONAL DETAILS (Accordion) -->
                        <div style="border: 1px dashed #cbd5e1; border-radius: 14px; padding: 14px 18px; margin-bottom: 25px; background: #fafafa;">
                            <div onclick="toggleOptionalDetails()" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
                                <span style="font-weight: 800; color: #475569; font-size: 0.88rem; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-plus-circle" id="optIcon" style="color: #2563eb;"></i> Add Optional Previous School & Emergency Info
                                </span>
                                <small style="color: #94a3b8; font-weight: 700;">Expand</small>
                            </div>

                            <div id="optionalBox" style="display: none; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                                <div class="adm-field-group" style="margin-bottom: 12px;">
                                    <label class="adm-label">Previous School Name</label>
                                    <input type="text" name="prev_school" class="adm-input" placeholder="Current or previous school name">
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div class="adm-field-group">
                                        <label class="adm-label">Emergency Contact Person</label>
                                        <input type="text" name="emergency_contact_name" class="adm-input" placeholder="Full name">
                                    </div>
                                    <div class="adm-field-group">
                                        <label class="adm-label">Emergency Phone</label>
                                        <input type="tel" name="emergency_phone" class="adm-input" placeholder="Emergency contact number">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <button type="submit" class="btn-submit-admission">
                                Submit Admission Application <i class="fas fa-paper-plane" style="margin-left: 8px;"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </div>
</section>

<style>
    .adm-grid-2col {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 30px;
        align-items: start;
    }

    .form-inner-2col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    .adm-field-group { display: flex; flex-direction: column; gap: 5px; }
    .adm-label { font-size: 0.8rem; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em; }
    .adm-input { 
        width: 100%; 
        padding: 11px 14px; 
        border-radius: 10px; 
        border: 2px solid #e2e8f0; 
        background: #ffffff; 
        font-family: inherit; 
        font-size: 0.92rem; 
        font-weight: 600; 
        color: #0f172a; 
        outline: none; 
        transition: all 0.25s ease;
    }
    .adm-input:focus { 
        border-color: #2563eb; 
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); 
    }

    .btn-submit-admission {
        width: 100%;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        border: none;
        padding: 15px;
        border-radius: 14px;
        font-weight: 900;
        font-size: 1.05rem;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-submit-admission:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(37, 99, 235, 0.4);
    }

    @media (max-width: 992px) {
        .adm-grid-2col { grid-template-columns: 1fr; gap: 20px; }
        .form-inner-2col { grid-template-columns: 1fr; gap: 15px; }
    }

    @media (max-width: 640px) {
        .adm-card-resp { padding: 22px 16px !important; border-radius: 20px !important; }
    }
</style>

<script>
function toggleOptionalDetails() {
    var box = document.getElementById('optionalBox');
    var icon = document.getElementById('optIcon');
    if (box.style.display === 'none' || box.style.display === '') {
        box.style.display = 'block';
        icon.className = 'fas fa-minus-circle';
    } else {
        box.style.display = 'none';
        icon.className = 'fas fa-plus-circle';
    }
}

function pubPhotoPreview(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = document.getElementById('pubPhotoPreview');
            var icon = document.getElementById('pubPhotoIcon');
            img.src = e.target.result;
            img.style.display = 'block';
            icon.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
