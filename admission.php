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
        <p class="hero-subtitle fade-in" style="animation-delay: 0.2s;" style="max-width: 800px; margin: 0 auto;">
            Take the first step towards a bright future. Fill out the application form below to apply for our residential or day scholar programs.
        </p>
    </div>
</section>

<!-- Admission Form Section -->
<section style="padding: 60px 0; background: #f4f7fb;">
    <div class="container">
        <div class="glass-card fade-in" style="max-width: 900px; margin: 0 auto; padding: 40px; border-radius: 30px;">
            <div class="section-header fade-in" style="margin-bottom: 30px;">
                <h2 class="section-title" style="font-size: 2rem;">Application Form</h2>
                <p style="color: var(--text-muted);">Please provide accurate details. Our team will contact you for the next steps.</p>
            </div>
            
            <form action="process_admission.php" method="POST" class="premium-form">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <h3 style="color: var(--primary); margin-bottom: 20px; font-size: 1.2rem; border-bottom: 2px solid #eee; padding-bottom: 10px;">1. Student Information</h3>
                <div class="input-row">
                    <div class="input-group">
                        <label>Student Full Name <span style="color:red">*</span></label>
                        <input type="text" name="student_name" placeholder="Enter student's name" required>
                    </div>
                    <div class="input-group">
                        <label>Date of Birth <span style="color:red">*</span></label>
                        <input type="date" name="dob" required>
                    </div>
                </div>
                
                <div class="input-row">
                    <div class="input-group">
                        <label>Gender <span style="color:red">*</span></label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
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

                <div class="input-row">
                    <div class="input-group">
                        <label>Scholar Mode <span style="color:red">*</span></label>
                        <select name="scholar_mode" required>
                            <option value="">Select Mode</option>
                            <option value="Day Scholar">Day Scholar</option>
                            <option value="Hostler">Hostler</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Previous/Current School</label>
                        <input type="text" name="prev_school" placeholder="Name of current school (if any)">
                    </div>
                </div>

                <h3 style="color: var(--primary); margin-top: 40px; margin-bottom: 20px; font-size: 1.2rem; border-bottom: 2px solid #eee; padding-bottom: 10px;">2. Parent / Guardian Information</h3>
                <div class="input-row">
                    <div class="input-group">
                        <label>Parent/Guardian Name <span style="color:red">*</span></label>
                        <input type="text" name="parent_name" placeholder="Enter parent's name" required>
                    </div>
                    <div class="input-group">
                        <label>Mobile Number <span style="color:red">*</span></label>
                        <input type="tel" name="phone" placeholder="+91 XXXXX XXXXX" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Email for communication">
                </div>

                <div class="input-group">
                    <label>Full Residential Address <span style="color:red">*</span></label>
                    <textarea name="address" rows="3" placeholder="Enter complete address with PIN code" required style="width: 100%; padding: 15px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.1); font-family: inherit; resize: vertical;"></textarea>
                </div>

                <div style="margin-top: 40px;">
                    <button type="submit" class="btn btn-primary w-100" style="padding: 18px; font-size: 1.1rem; border-radius: 15px;">Submit Application <i class="fas fa-paper-plane" style="margin-left: 10px;"></i></button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
