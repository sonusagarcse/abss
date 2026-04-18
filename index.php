<?php
require_once 'config/db.php';
$conn = getDB();
$settings = getAllSettings();
include 'includes/header.php';
?>

<!-- Hero Section: Premium Redesign -->
<section id="home" class="hero-premium bg-gradient-primary">
    <div class="hero-glow"></div>
    <div class="container hero-content">
        <div class="hero-badge fade-in">Nurturing Minds, Creating Future Leaders</div>
        <h1 class="fade-in" style="animation-delay: 0.1s;">आवासीय बाल शिक्षण <span class="text-accent">संस्थान</span>
        </h1>
        <p class="hero-subtitle fade-in" style="animation-delay: 0.2s;">
            The Premier Competitive Education & Research Center for elite residential school preparation.
            <span class="highlight">Netarhat | Sainik | Navodaya | Simultala</span>
        </p>
        <div class="hero-btns fade-in" style="animation-delay: 0.3s;">
            <a href="#admission" class="btn btn-secondary">Admission 2026-27 <i class="fas fa-arrow-right"></i></a>
            <a href="assets/Prospectus ABSS.pdf" class="btn btn-glass" download><i class="fas fa-file-download"></i> Get
                Prospectus</a>
        </div>
        <div class="hero-floating-elements">
            <div class="floating-shape shape-1 animate-float"></div>
            <div class="floating-shape shape-2 animate-float" style="animation-delay: -2s;"></div>
        </div>
    </div>
</section>

<!-- Competitive Exams: Visual Tags -->
<section class="exam-section bg-pattern">
    <div class="container">
        <div class="section-header fade-in">
            <span class="section-badge">Competitive Excellence</span>
            <h2 class="section-title">Preparation Excellence For</h2>
        </div>
        <div class="exam-tags-container">
            <?php
            $exams = ["Netarhat Residential", "Sainik School", "Navodaya Vidyalaya", "BHU Entrance", "Military School", "Simultala Residential", "Indira Gandhi Balika", "Vanasthali Vidyapith"];
            foreach ($exams as $index => $exam): ?>
                <div class="exam-tag-premium fade-in" style="animation-delay: <?php echo ($index * 0.05); ?>s">
                    <i class="fas fa-graduation-cap"></i> <?php echo $exam; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Notice Board Section: Dynamic -->
<section id="notices" class="notices-reel bg-white" style="padding: 20px 0; border-bottom: 1px solid #f0f4f8;">
    <div class="container" style="display: flex; align-items: center; gap: 20px;">
        <div class="notice-label"><i class="fas fa-bullhorn"></i> Important Updates</div>
        <div class="notice-marquee">
            <?php
            $notices_query = $conn->query("SELECT * FROM notices WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5");
            if ($notices_query->num_rows > 0):
                while ($notice = $notices_query->fetch_assoc()): ?>
                    <span class="notice-item type-<?php echo $notice['type']; ?>">
                        <strong>[<?php echo strtoupper($notice['type']); ?>]</strong>
                        <?php echo $notice['title']; ?>: <?php echo $notice['content']; ?>
                    </span>
                <?php endwhile;
            else: ?>
                <span class="notice-item">Welcome to ABSS - Admission for session 2026-27 is now open. Admissions are based
                    on entrance tests.</span>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    /* Notice Marquee Styles */
    .notice-label {
        background: var(--primary);
        color: #fff;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 800;
        font-size: 0.85rem;
        white-space: nowrap;
        box-shadow: 0 4px 10px rgba(13, 71, 161, 0.2);
    }

    .notice-marquee {
        overflow: hidden;
        white-space: nowrap;
        flex: 1;
        font-size: 1rem;
        color: var(--primary-dark);
        font-weight: 600;
    }

    .notice-item {
        display: inline-block;
        margin-right: 100px;
        animation: marquee 30s linear infinite;
    }

    .notice-item.type-important {
        color: #d32f2f;
    }

    .notice-item.type-event {
        color: #f97316;
    }

    @keyframes marquee {
        0% {
            transform: translateX(100%);
        }

        100% {
            transform: translateX(-100%);
        }
    }
</style>

<!-- Secretary Vision: Side-by-Side Premium -->
<section id="about" class="vision-section">
    <div class="container">
        <div class="vision-flex">
            <div class="vision-image-wrapper fade-in">
                <div class="image-frame">
                    <img src="assets/Secratery.png" alt="Suman Kumar" class="vision-img">
                </div>
                <div class="info-card glass-card">
                    <h4>Suman Kumar</h4>
                    <p>Secretary, Lok Kala Vikas Manch</p>
                    <div class="social-mini">
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="vision-content fade-in" style="animation-delay: 0.2s;">
                <span class="section-badge">Director's Message</span>
                <h2 class="section-title">Our Vision for Your Child</h2>
                <p class="lead-text italic serif-font">"Success is not just about marks; it's about the courage to
                    compete and the character to win."</p>
                <p>Welcome to <b>Awasiya Bal Shikshan Sansthan</b>. We bridging the gap between standard education and
                    competitive brilliance. Our specialized curriculum and residential environment are designed to
                    foster intellectual curiosity and disciplined growth.</p>
                <div class="vision-points">
                    <div class="v-point glass-card"><i class="fas fa-shield-alt"></i> <span>100% Safety &
                            Security</span></div>
                    <div class="v-point glass-card"><i class="fas fa-microscope"></i> <span>Research-Based
                            Pedagogy</span></div>
                    <div class="v-point glass-card"><i class="fas fa-brain"></i> <span>Mental Ability Training</span>
                    </div>
                    <div class="v-point glass-card"><i class="fas fa-medal"></i> <span>Excellence Guaranteed</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features: Glassmorphism Grid -->
<section class="features-premium bg-pattern">
    <div class="container">
        <div class="section-header fade-in">
            <span class="section-badge">World Class Facilities</span>
            <h2 class="section-title">Everything Your Child Needs</h2>
        </div>
        <div class="features-grid-premium">
            <div class="feature-card-p glass-card fade-in">
                <div class="f-icon"><i class="fas fa-hotel"></i></div>
                <h3>Residential Life</h3>
                <p>Separate, secure, and comfortable hostel facilities for both boys and girls with 24/7 care.</p>
            </div>
            <div class="feature-card-p glass-card fade-in" style="animation-delay: 0.1s;">
                <div class="f-icon"><i class="fas fa-utensils"></i></div>
                <h3>Nutrition First</h3>
                <p>High-quality, balanced organic meals and pure RO drinking water for physical wellbeing.</p>
            </div>
            <div class="feature-card-p glass-card fade-in" style="animation-delay: 0.2s;">
                <div class="f-icon"><i class="fas fa-swatches"></i></div>
                <h3>Holistic Arts</h3>
                <p>Developing creativity through specialized music, painting, and competitive debate clubs.</p>
            </div>
        </div>
    </div>
</section>

<!-- Gallery Preview -->
<section id="gallery" class="gallery-section">
    <div class="container">
        <div class="section-header fade-in">
            <span class="section-badge">Captured Moments</span>
            <h2 class="section-title">Campus Life in Action</h2>
        </div>
        <div class="gallery-grid">
            <?php
            $gallery_query = $conn->query("SELECT * FROM gallery ORDER BY created_at DESC LIMIT 6");
            if ($gallery_query->num_rows > 0):
                $delay = 0;
                while ($photo = $gallery_query->fetch_assoc()): ?>
                    <div class="gallery-item glass-card fade-in" style="animation-delay: <?php echo $delay; ?>s;">
                        <img src="<?php echo $photo['image_path']; ?>" alt="<?php echo $photo['caption']; ?>">
                        <div class="gallery-caption"><?php echo $photo['caption']; ?></div>
                    </div>
                    <?php $delay += 0.1; endwhile;
            else: ?>
                <!-- Fallback to static if empty -->
                <div class="gallery-item glass-card fade-in"><img
                        src="https://images.unsplash.com/photo-1546410531-bb4caa6b424d?auto=format&fit=crop&w=600&q=80"
                        alt="Student Studying"></div>
                <div class="gallery-item glass-card fade-in" style="animation-delay: 0.1s;"><img
                        src="https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=600&q=80"
                        alt="Classroom"></div>
                <div class="gallery-item glass-card fade-in" style="animation-delay: 0.2s;"><img
                        src="https://media.istockphoto.com/id/1125899420/photo/preschool-in-class.jpg?s=612x612&w=0&k=20&c=jyZUZjEUyxobipUYS9w8235VotEUvBwlSwpIC2EY_F0="
                        alt="Students Playing"></div>
                <div class="gallery-item glass-card fade-in" style="animation-delay: 0.3s;"><img
                        src="https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=600&q=80"
                        alt="Library"></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Admission & Fee: Modern Duo -->
<section id="admission" class="admission-section">
    <div class="container">
        <div class="text-center" style="margin-bottom: 50px; text-align: center;">
            <span class="section-badge">Admission 2026-27</span>
            <h2 class="section-title">Investment in Excellence</h2>
        </div>

        <div class="admission-grid">
            <div class="fee-cards-container" style="display: flex; gap: 30px; flex-wrap: wrap;">
                <!-- Residential Plan -->
                <div class="pricing-card glass-card fade-in" style="flex: 1; min-width: 300px;">
                    <h3 class="card-subtitle">Residential Scholar</h3>
                    <div class="price-main">₹ <?php echo number_format($settings['res_fee']); ?><span>/month</span>
                    </div>
                    <ul class="pricing-list">
                        <li><i class="fas fa-check"></i> Registration Fee: ₹
                            <?php echo $settings['registration_fee']; ?>/-</li>
                        <li><i class="fas fa-check"></i> Admission Fee: ₹ <?php echo $settings['admission_fee']; ?>/-
                        </li>
                        <li><i class="fas fa-check"></i> Annual Development: ₹
                            <?php echo $settings['development_fee']; ?>/-</li>
                        <li><i class="fas fa-check"></i> Hostel & Quality Meals included</li>
                    </ul>
                    <div class="total-tag">Initial Payment: ₹
                        <?php echo number_format($settings['res_fee'] + $settings['registration_fee'] + $settings['admission_fee'] + $settings['development_fee']); ?>/-
                    </div>
                </div>

                <!-- Day Scholar Plan -->
                <div class="pricing-card glass-card fade-in" style="flex: 1; min-width: 300px; animation-delay: 0.1s;">
                    <h3 class="card-subtitle">Day Scholar</h3>
                    <div class="price-main">₹ <?php echo number_format($settings['day_fee']); ?><span>/month</span>
                    </div>
                    <ul class="pricing-list">
                        <li><i class="fas fa-check"></i> Registration Fee: ₹
                            <?php echo $settings['registration_fee']; ?>/-</li>
                        <li><i class="fas fa-check"></i> Admission Fee: ₹ <?php echo $settings['admission_fee']; ?>/-
                        </li>
                        <li><i class="fas fa-check"></i> Annual Development: ₹
                            <?php echo $settings['development_fee']; ?>/-</li>
                        <li><i class="fas fa-check"></i> Intensive Classroom Training</li>
                    </ul>
                    <div class="total-tag">Initial Payment: ₹
                        <?php echo number_format($settings['day_fee'] + $settings['registration_fee'] + $settings['admission_fee'] + $settings['development_fee']); ?>/-
                    </div>
                </div>
            </div>

            <!-- Inquiry Form restored after the section (merged into grid for the requested layout) -->
            <div class="form-container-p glass-card fade-in" style="animation-delay: 0.2s; margin-top: 50px;">
                <div class="text-center" style="margin-bottom: 30px;">
                    <h3 class="card-subtitle">Global Admission Inquiry</h3>
                    <p style="font-size: 0.9rem; color: var(--text-muted);">Fill the form below and our counselor will
                        contact you within 24 hours.</p>
                </div>
                <form action="process.php" method="POST" class="premium-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="input-row">
                        <div class="input-group">
                            <label>Candidate Name</label>
                            <input type="text" name="name" placeholder="Full Name" required>
                        </div>
                        <div class="input-group">
                            <label>Parent Mobile</label>
                            <input type="tel" name="phone" placeholder="+91 9XXXX XXXXX" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Target School / Program</label>
                        <select name="target_exam">
                            <option>Netarhat Residential</option>
                            <option>Sainik School</option>
                            <option>Navodaya Vidyalaya</option>
                            <option>Day Scholar Program</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100">Submit Admission Inquiry</button>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
    /* --- Admission Grid --- */
    .admission-grid {
        max-width: 1000px;
        margin: 0 auto;
    }

    .price-main {
        font-size: 3rem;
        font-weight: 800;
        color: var(--primary);
        margin: 20px 0;
    }

    .price-main span {
        font-size: 1rem;
        color: var(--text-muted);
        font-weight: 400;
    }

    .pricing-list {
        list-style: none;
        margin-bottom: 30px;
    }

    .pricing-list li {
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }

    .pricing-list i {
        color: #4caf50;
    }

    .total-tag {
        background: var(--secondary);
        color: var(--primary-dark);
        padding: 15px;
        border-radius: 12px;
        text-align: center;
        font-weight: 700;
        width: 100%;
    }

    .card-subtitle {
        font-size: 1.4rem;
        color: var(--primary-dark);
        margin-bottom: 10px;
        font-weight: 700;
    }

    /* --- Inquiry Section Styling --- */
    /* --- General Section Spacing --- */
    section {
        padding: 100px 0;
    }

    /* --- Hero Premium CSS --- */
    .hero-premium {
        min-height: 90vh;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        color: var(--white);
        padding: 120px 0;
        /* Extra for hero */
    }

    .hero-glow {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(255, 214, 0, 0.2) 0%, transparent 70%);
        transform: translate(-50%, -50%);
        pointer-events: none;
    }

    .hero-content {
        position: relative;
        z-index: 10;
        text-align: center;
    }

    .hero-badge {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(5px);
        padding: 8px 24px;
        border-radius: 50px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        display: inline-block;
        margin-bottom: 30px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .hero-premium h1 {
        font-size: clamp(2.5rem, 6vw, 4.5rem);
        margin-bottom: 25px;
        line-height: 1.1;
    }

    .hero-subtitle {
        font-size: 1.3rem;
        margin-bottom: 40px;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
        opacity: 0.9;
    }

    .text-accent {
        color: var(--secondary);
    }

    .highlight {
        border-bottom: 2px solid var(--secondary);
        display: inline-block;
        padding-bottom: 4px;
    }

    .btn-glass {
        background: rgba(255, 255, 255, 0.1);
        color: var(--white);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .btn-glass:hover {
        background: var(--white);
        color: var(--primary);
    }

    /* --- Floating Elements --- */
    .floating-shape {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
    }

    .shape-1 {
        width: 300px;
        height: 300px;
        top: -100px;
        right: -50px;
    }

    .shape-2 {
        width: 400px;
        height: 400px;
        bottom: -150px;
        left: -100px;
    }

    /* --- Exam Tags --- */
    .exam-tags-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
    }

    .exam-tag-premium {
        background: var(--white);
        padding: 15px 30px;
        border-radius: 100px;
        box-shadow: var(--shadow-sm);
        color: var(--primary-dark);
        font-weight: 700;
        font-size: 0.95rem;
        transition: var(--ease-in-out);
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #eef2ff;
    }

    .exam-tag-premium i {
        color: var(--secondary-dark);
    }

    .exam-tag-premium:hover {
        transform: scale(1.05);
        box-shadow: var(--shadow-md);
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
    }

    /* --- Vision Section --- */
    .vision-flex {
        display: flex;
        align-items: center;
        gap: 80px;
        padding: 60px 0;
    }

    .vision-image-wrapper {
        flex: 0.8;
        position: relative;
        max-width: 450px;
    }

    .image-frame {
        border-radius: 40px;
        overflow: hidden;
        background: var(--white);
        padding: 15px;
        box-shadow: var(--shadow-lg);
        height: 500px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .vision-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 25px;
    }

    .info-card {
        position: absolute;
        bottom: -20px;
        right: -20px;
        padding: 20px;
        width: 260px;
        height: auto;
        min-height: fit-content;
    }

    .info-card h4 {
        color: var(--primary-dark);
        margin-bottom: 5px;
        font-size: 1.1rem;
    }

    .info-card p {
        font-size: 0.85rem;
        line-height: 1.4;
        margin-bottom: 15px;
    }

    .social-mini {
        margin-top: 10px;
        display: flex;
        gap: 15px;
        font-size: 1.2rem;
    }

    .vision-content {
        flex: 1.2;
    }

    .lead-text {
        font-size: 1.4rem;
        color: var(--text-muted);
        margin-bottom: 25px;
        border-left: 4px solid var(--secondary);
        padding-left: 20px;
    }

    .vision-points {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
        /* Increased gap */
        margin-top: 40px;
    }

    .v-point.glass-card {
        padding: 15px 20px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(255, 255, 255, 0.4);
    }

    .v-point i {
        color: var(--secondary-dark);
        font-size: 1.1rem;
    }

    /* --- Features Premium --- */
    .features-premium {
        padding: 100px 0;
    }

    .features-grid-premium {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 40px;
    }

    .feature-card-p {
        text-align: center;
    }

    .f-icon {
        width: 80px;
        height: 80px;
        background: var(--primary);
        color: var(--white);
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 25px;
        box-shadow: 0 10px 20px rgba(13, 71, 161, 0.2);
    }

    /* --- Inquiry Section Styling --- */
    .premium-form .input-row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .input-group {
        flex: 1;
        margin-bottom: 20px;
    }

    .input-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--primary-dark);
    }

    .input-group input,
    .input-group select {
        width: 100%;
        padding: 15px;
        border-radius: 12px;
        border: 2px solid #eef2ff;
        outline: none;
        transition: var(--ease-in-out);
    }

    .input-group input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(13, 71, 161, 0.1);
    }

    /* --- Utilities --- */
    .italic {
        font-style: italic;
    }

    .w-100 {
        width: 100%;
    }

    @media (max-width: 992px) {

        .vision-flex,
        .admission-grid {
            flex-direction: column;
        }

        .features-grid-premium {
            grid-template-columns: 1fr;
        }

        .vision-image-wrapper {
            width: 100%;
            max-width: 400px;
            margin-bottom: 50px;
        }

        .premium-form .input-row {
            flex-direction: column;
            gap: 0;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>