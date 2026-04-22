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
        <h1 class="fade-in" style="animation-delay: 0.1s; font-weight: 900;">आवासीय बाल शिक्षण <span class="text-accent">संस्थान</span>
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
                <div class="f-icon"><i class="fas fa-palette"></i></div>
                <h3>Holistic Arts</h3>
                <p>Developing creativity through specialized music, painting, and competitive debate clubs.</p>
            </div>
        </div>
    </div>
</section>

<!-- Hall of Excellence: Premium Achievers Section -->
<section id="achievers" class="achievers-section bg-pattern">
    <div class="container">
        <div class="section-header fade-in" style="text-align: center; margin-bottom: 60px;">
            <span class="section-badge">Our Pride</span>
            <h2 class="section-title">Hall of Excellence</h2>
            <p style="max-width: 600px; margin: 0 auto; color: var(--text-muted);">Celebrating the brilliance and hard work of our top-performing students who made it to elite institutions.</p>
        </div>
        <div class="achievers-flex-container">
            <?php
            $achievers_query = $conn->query("SELECT * FROM achievers ORDER BY created_at DESC");
            if ($achievers_query->num_rows > 0):
                while ($achiever = $achievers_query->fetch_assoc()): ?>
                    <div class="achiever-card-premium glass-card fade-in">
                        <a href="<?php echo $achiever["image_path"]; ?>" class="glightbox" data-gallery="achievers" data-title="<?php echo htmlspecialchars($achiever["name"]); ?> - <?php echo htmlspecialchars($achiever["target_school"]); ?>">
                            <div class="achiever-img-box">
                                <img src="<?php echo $achiever["image_path"]; ?>" alt="<?php echo $achiever["name"]; ?>">
                            </div>
                        </a>
                        <div class="achiever-info">
                            <h3><?php echo $achiever["name"]; ?></h3>
                            <div class="achiever-tag"><?php echo $achiever["target_school"]; ?></div>
                            <div class="achiever-batch">Batch of <?php echo $achiever["batch_year"]; ?></div>
                        </div>
                    </div>
                <?php endwhile;
            else: ?>
                <p class="text-center" style="width: 100%; opacity: 0.6;">Our achievers list is being updated. Stay tuned!</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Gallery Preview -->
<section id="gallery" class="gallery-section">
    <div class="container">
        <div class="section-header fade-in" style="text-align: center; margin-bottom: 50px;">
            <span class="section-badge">Captured Moments</span>
            <h2 class="section-title">Campus Life in Action</h2>
        </div>
        <div class="gallery-flex-container">
            <?php
            $gallery_query = $conn->query("SELECT * FROM gallery ORDER BY created_at DESC LIMIT 6");
            if ($gallery_query->num_rows > 0):
                $delay = 0;
                while ($photo = $gallery_query->fetch_assoc()): ?>
                    <div class="gallery-item-premium glass-card fade-in" style="animation-delay: <?php echo $delay; ?>s;">
                        <a href="<?php echo $photo["image_path"]; ?>" class="glightbox" data-gallery="campus-life" data-title="<?php echo htmlspecialchars($photo["caption"]); ?>">
                            <div class="gallery-img-box">
                                <img src="<?php echo $photo["image_path"]; ?>" alt="<?php echo $photo["caption"]; ?>">
                            </div>
                            <div class="gallery-caption-overlay"><?php echo $photo["caption"]; ?></div>
                        </a>
                    </div>
                    <?php $delay += 0.1; endwhile;
            else: ?>
                <div class="gallery-item-premium glass-card fade-in">
                    <div class="gallery-img-box">
                        <img src="https://images.unsplash.com/photo-1546410531-bb4caa6b424d?auto=format&fit=crop&w=600&q=80" alt="Student Studying">
                    </div>
                </div>
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

            <!-- Online Admission CTA -->
            <div class="form-container-p glass-card fade-in" style="animation-delay: 0.2s; margin-top: 50px; text-align: center; padding: 50px 30px;">
                <h3 class="card-subtitle" style="font-size: 2rem; margin-bottom: 15px;">Ready to shape your child's future?</h3>
                <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 30px; max-width: 600px; margin-left: auto; margin-right: auto;">
                    Our comprehensive Online Admission System is now open for the 2026-27 academic year. 
                </p>
                <a href="admission.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 18px 40px; border-radius: 50px; display: inline-flex; align-items: center; gap: 15px;">
                    Apply for Admission Now <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>



<?php include 'includes/footer.php'; ?>