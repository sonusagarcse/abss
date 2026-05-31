<?php
require_once 'config/db.php';
$conn = getDB();
$settings = getAllSettings();
$school_list = $conn->query("SELECT school_name FROM schools ORDER BY school_name ASC");
include 'includes/header.php';
?>

<!-- Admission Header -->
<section class="hero-premium bg-gradient-primary" style="min-height: 40vh; padding: 120px 0 60px;">
    <div class="hero-glow"></div>
    <div class="container hero-content text-center">
        <div class="hero-badge fade-in">Join the Excellence</div>
        <h1 class="fade-in" style="animation-delay: 0.1s;">Online <span class="text-accent">Admission</span> 2026-27</h1>
        <p class="hero-subtitle fade-in" style="animation-delay: 0.2s; max-width: 800px; margin: 0 auto;">
            Take the first step towards a bright future. Fill out the application form below to apply for our residential or day scholar programs.
        </p>
    </div>
</section>

<style>
    .adm-section-title {
        background: linear-gradient(135deg, #0d47a1, #1565c0);
        color: #fff;
        padding: 10px 22px;
        border-radius: 12px;
        font-weight: 800;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin: 35px 0 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .adm-section-title:first-of-type { margin-top: 0; }
    .adm-yn-row { display: flex; gap: 30px; align-items: center; margin: 8px 0 12px; }
    .adm-yn-row label { display: flex; align-items: center; gap: 8px; font-weight: 700; color: #5a6a8a; cursor: pointer; font-size: 0.95rem; }
    .adm-yn-row input[type="checkbox"] { width: 18px; height: 18px; accent-color: #0d47a1; }
    .adm-detail-row { display: none; margin-top: 8px; }
    .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    @media(max-width: 700px) {
        .three-col { grid-template-columns: 1fr; }
    }
</style>

<!-- Admission Form Section -->
<section style="padding: 60px 0; background: #f4f7fb;">
    <div class="container">
        <div class="glass-card fade-in" style="max-width: 950px; margin: 0 auto; padding: 45px; border-radius: 30px;">
            <div class="section-header fade-in" style="margin-bottom: 30px;">
                <h2 class="section-title" style="font-size: 2rem;">Application Form</h2>
                <p style="color: var(--text-muted);">Please provide accurate details. Our team will contact you for the next steps.</p>
            </div>

            <form action="process_admission.php" method="POST" enctype="multipart/form-data" class="premium-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <!-- 1. STUDENT INFORMATION -->
                <div class="adm-section-title"><i class="fas fa-user-graduate"></i> 1. Student Information</div>

                <!-- Passport Photo Upload -->
                <div style="display:flex; gap:30px; align-items:flex-start; margin-bottom:30px; flex-wrap:wrap;">
                    <div style="text-align:center; flex-shrink:0;">
                        <div id="pubPhotoCircle" style="width:110px; height:110px; border-radius:50%; background:#eef2ff; border:3px dashed #c7d2fe; display:flex; align-items:center; justify-content:center; overflow:hidden; margin:0 auto 10px; cursor:pointer; position:relative;">
                            <img id="pubPhotoPreview" src="" alt="" style="width:100%; height:100%; object-fit:cover; border-radius:50%; display:none;">
                            <i id="pubPhotoIcon" class="fas fa-camera" style="font-size:2rem; color:#a5b4fc;"></i>
                            <input type="file" name="student_photo" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;" onchange="pubPhotoPreview(this)" id="pub_student_photo">
                        </div>
                        <div style="font-size:0.82rem; color:#5a6a8a; font-weight:600;">Passport Photo<br><small style="opacity:0.7;">JPG/PNG, max 3MB</small></div>
                    </div>
                    <div style="flex:1; min-width:220px;">
                        <div class="input-group">
                            <label>Student Full Name <span style="color:red">*</span></label>
                            <input type="text" name="student_name" placeholder="Enter student's full name" required>
                        </div>
                        <div class="input-group" style="margin-top:15px;">
                            <label>Date of Birth <span style="color:red">*</span></label>
                            <input type="date" name="dob" required>
                        </div>
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label>Gender <span style="color:red">*</span></label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">☐ Male</option>
                            <option value="Female">☐ Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Student ID (Auto-assigned after review)</label>
                        <input type="text" placeholder="Will be assigned after review" disabled style="background:#f0f4fb; color:#aaa;">
                    </div>
                </div>

                <div class="input-group">
                    <label>Home Address <span style="color:red">*</span></label>
                    <input type="text" name="home_address" placeholder="Street / Village / Colony" required>
                </div>

                <div class="three-col">
                    <div class="input-group">
                        <label>City <span style="color:red">*</span></label>
                        <input type="text" name="city" placeholder="City" required>
                    </div>
                    <div class="input-group">
                        <label>State <span style="color:red">*</span></label>
                        <input type="text" name="state" placeholder="State" required>
                    </div>
                    <div class="input-group">
                        <label>ZIP / PIN Code</label>
                        <input type="text" name="zip_code" placeholder="PIN Code">
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label>Previous / Current School (if any)</label>
                        <input type="text" name="prev_school" placeholder="Name of current school">
                    </div>
                    <div class="input-group">
                        <label>Target School <span style="color:red">*</span></label>
                        <select name="target_program" required>
                            <option value="">Select School</option>
                            <?php while($s = $school_list->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($s['school_name']); ?>"><?php echo htmlspecialchars($s['school_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <label>Scholar Mode <span style="color:red">*</span></label>
                    <select name="scholar_mode" required>
                        <option value="">Select Mode</option>
                        <option value="Day Scholar">Day Scholar</option>
                        <option value="Hostler">Hostler</option>
                    </select>
                </div>

                <!-- 2. GUARDIAN INFORMATION -->
                <div class="adm-section-title"><i class="fas fa-user-shield"></i> 2. Guardian Information</div>

                <div class="input-row">
                    <div class="input-group">
                        <label>Guardian Name <span style="color:red">*</span></label>
                        <input type="text" name="parent_name" placeholder="Full name of parent/guardian" required>
                    </div>
                    <div class="input-group">
                        <label>Relationship to Student</label>
                        <select name="guardian_relationship">
                            <option value="">Select Relationship</option>
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Guardian">Guardian</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label>Phone Number <span style="color:red">*</span></label>
                        <input type="tel" name="phone" placeholder="+91 XXXXX XXXXX" required>
                    </div>
                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="Email for communication">
                    </div>
                </div>

                <div class="input-group">
                    <label>Guardian Home Address (if different from student) <span style="color:red">*</span></label>
                    <textarea name="address" rows="3" placeholder="Enter complete address with PIN code — or write 'Same as student'" required style="width: 100%; padding: 15px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.1); font-family: inherit; resize: vertical;"></textarea>
                </div>

                <!-- 3. EMERGENCY CONTACT -->
                <div class="adm-section-title"><i class="fas fa-phone-alt"></i> 3. Emergency Contact Information</div>

                <div class="input-row">
                    <div class="input-group">
                        <label>Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" placeholder="Full name">
                    </div>
                    <div class="input-group">
                        <label>Relationship to Student</label>
                        <input type="text" name="emergency_relationship" placeholder="e.g. Uncle, Aunt, Grandparent">
                    </div>
                </div>

                <div class="input-group">
                    <label>Emergency Phone Number</label>
                    <input type="tel" name="emergency_phone" placeholder="+91 XXXXX XXXXX">
                </div>

                <!-- 4. MEDICAL INFORMATION (OPTIONAL) -->
                <div class="adm-section-title"><i class="fas fa-heartbeat"></i> 4. Medical Information <small style="font-weight:400; opacity:0.75; font-size:0.75rem; text-transform:none; letter-spacing:0;">(Optional)</small></div>

                <div class="input-group">
                    <label>Does the student have any allergies?</label>
                    <div class="adm-yn-row">
                        <label><input type="checkbox" name="has_allergies" id="pub_has_allergies" onchange="pubToggle('pub_has_allergies','pub_allergies_detail')"> Yes</label>
                        <label style="color:#aaa;"><input type="checkbox" id="pub_no_allergies" disabled> No</label>
                    </div>
                    <div class="adm-detail-row" id="pub_allergies_detail">
                        <input type="text" name="allergies_detail" placeholder="If yes, please list the allergies">
                    </div>
                </div>

                <div class="input-group">
                    <label>Does the student have any medical conditions we should be aware of?</label>
                    <div class="adm-yn-row">
                        <label><input type="checkbox" name="has_medical_condition" id="pub_has_medical" onchange="pubToggle('pub_has_medical','pub_medical_detail')"> Yes</label>
                        <label style="color:#aaa;"><input type="checkbox" id="pub_no_medical" disabled> No</label>
                    </div>
                    <div class="adm-detail-row" id="pub_medical_detail">
                        <input type="text" name="medical_condition_detail" placeholder="If yes, please specify">
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label>Primary Physician Name</label>
                        <input type="text" name="physician_name" placeholder="Doctor's name">
                    </div>
                    <div class="input-group">
                        <label>Physician Phone Number</label>
                        <input type="tel" name="physician_phone" placeholder="Doctor's contact number">
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label>Health Insurance Provider</label>
                        <input type="text" name="insurance_provider" placeholder="Insurance company name">
                    </div>
                    <div class="input-group">
                        <label>Policy Number</label>
                        <input type="text" name="insurance_policy" placeholder="Policy / Card number">
                    </div>
                </div>

                <div style="margin-top: 40px;">
                    <button type="submit" class="btn btn-primary w-100" style="padding: 18px; font-size: 1.1rem; border-radius: 15px;">Submit Application <i class="fas fa-paper-plane" style="margin-left: 10px;"></i></button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
function pubToggle(checkboxId, rowId) {
    var cb = document.getElementById(checkboxId);
    document.getElementById(rowId).style.display = cb.checked ? 'block' : 'none';
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
