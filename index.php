<?php
require_once 'config/db.php';
$conn = getDB();
$settings = getAllSettings();
include 'includes/header.php';
?>

<!-- Hero Section: Premium Institution Redesign -->
<section id="home" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0d47a1 100%); padding: 80px 0 90px; color: #ffffff; position: relative; overflow: hidden;">
    <div style="position: absolute; top: -100px; right: -100px; width: 400px; height: 400px; background: rgba(56, 189, 248, 0.15); filter: blur(90px); border-radius: 50%;"></div>
    <div style="position: absolute; bottom: -100px; left: -100px; width: 400px; height: 400px; background: rgba(124, 58, 237, 0.15); filter: blur(90px); border-radius: 50%;"></div>

    <div class="container" style="position: relative; z-index: 2;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;" class="hero-grid-resp">
            
            <!-- Left Hero Content -->
            <div>
                <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255, 255, 255, 0.12); backdrop-filter: blur(10px); padding: 8px 18px; border-radius: 50px; border: 1px solid rgba(255, 255, 255, 0.2); font-size: 0.85rem; font-weight: 800; margin-bottom: 25px; color: #38bdf8;">
                    <i class="fas fa-award"></i> Premier Residential Competitive Education Center
                </div>
                
                <h1 style="font-size: 3rem; font-weight: 900; line-height: 1.15; margin-bottom: 20px; letter-spacing: -0.02em;">
                    आवासीय बाल शिक्षण <span style="background: linear-gradient(135deg, #38bdf8, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">संस्थान</span>
                </h1>

                <p style="font-size: 1.15rem; color: #cbd5e1; line-height: 1.6; margin-bottom: 30px; font-weight: 500;">
                    Specialized Coaching & Residential Research Pedagogy for elite entrance exams:
                    <strong style="color: #ffffff; display: block; margin-top: 8px; font-weight: 800; font-size: 1.25rem;">
                        Netarhat • Sainik School • Navodaya Vidyalaya • Simultala
                    </strong>
                </p>

                <!-- 3 Action Buttons in 1st Section -->
                <div class="hero-action-buttons">
                    <a href="admission.php" class="hero-cta-btn btn-admission">
                        <i class="fas fa-user-plus"></i> Admission 2026-27 <i class="fas fa-arrow-right" style="font-size:0.85em;"></i>
                    </a>
                    
                    <a href="assets/Prospectus ABSS.pdf" download class="hero-cta-btn btn-prospectus-big">
                        <i class="fas fa-file-pdf"></i> Download Prospectus
                    </a>

                    <a href="app/index" class="hero-cta-btn btn-app-download">
                        <i class="fab fa-android"></i> Download App
                    </a>
                </div>

                <!-- Live Key Metrics Floating Bar -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; background: rgba(255, 255, 255, 0.06); backdrop-filter: blur(16px); padding: 20px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div>
                        <div style="font-size: 1.6rem; font-weight: 900; color: #38bdf8;">500+</div>
                        <div style="font-size: 0.78rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Selections</div>
                    </div>
                    <div>
                        <div style="font-size: 1.6rem; font-weight: 900; color: #4ade80;">100%</div>
                        <div style="font-size: 0.78rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Safety & Care</div>
                    </div>
                    <div>
                        <div style="font-size: 1.6rem; font-weight: 900; color: #c084fc;">15+ Yrs</div>
                        <div style="font-size: 0.78rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Excellence</div>
                    </div>
                </div>
            </div>

            <!-- Right Hero Card Graphic -->
            <div style="position: relative;" class="hero-image-col">
                <div style="background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(20px); border-radius: 30px; padding: 35px; border: 1px solid rgba(255, 255, 255, 0.15); box-shadow: 0 25px 50px rgba(0,0,0,0.3);">
                    <div style="display: flex; align-items: center; gap: 18px; margin-bottom: 25px;">
                        <img src="assets/logo.png" alt="ABSS Logo" style="height: 75px;">
                        <div>
                            <h3 style="margin: 0; font-size: 1.4rem; color: #ffffff; font-weight: 800;">ABSS Educational Trust</h3>
                            <p style="margin: 0; color: #94a3b8; font-size: 0.88rem; font-weight: 600;">Lok Kala Bhavan, Imamganj, Gaya</p>
                        </div>
                    </div>

                    <div style="background: rgba(15, 23, 42, 0.6); padding: 20px; border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 20px;">
                        <h4 style="margin: 0 0 10px 0; color: #38bdf8; font-size: 0.95rem; font-weight: 800;"><i class="fas fa-fire"></i> Why Choose ABSS?</h4>
                        <ul style="margin: 0; padding-left: 20px; color: #cbd5e1; font-size: 0.9rem; line-height: 1.7; font-weight: 600;">
                            <li>Specialized Mental Ability & Logical Reasoning Training</li>
                            <li>Weekly OMR Evaluation & National Level Test Series</li>
                            <li>Strict 24/7 Security & Hygienic Organic Hostel Food</li>
                            <li>Dedicated Faculty for Individual Student Attention</li>
                        </ul>
                    </div>

                    <a href="admission.php" style="display: block; text-align: center; background: #ffffff; color: #0f172a; padding: 14px; border-radius: 14px; font-weight: 900; text-decoration: none; font-size: 0.95rem; transition: all 0.2s ease;">
                        <i class="fas fa-edit"></i> Register Student Online →
                    </a>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Live Notice Marquee Bar -->
<section id="notices" style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 12px 0;">
    <div class="container" style="display: flex; align-items: center; gap: 15px;">
        <div style="background: #1d4ed8; color: #ffffff; padding: 7px 16px; border-radius: 50px; font-weight: 800; font-size: 0.82rem; white-space: nowrap; box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25); flex-shrink: 0;">
            <i class="fas fa-bullhorn"></i> Live Updates
        </div>
        <div style="overflow: hidden; flex: 1;">
            <marquee behavior="scroll" direction="left" scrollamount="6" onmouseover="this.stop()" onmouseout="this.start()" style="font-size: 0.95rem; color: #0f172a; font-weight: 700; vertical-align: middle;">
                <?php
                $notices_query = $conn->query("SELECT * FROM notices WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5");
                if ($notices_query && $notices_query->num_rows > 0):
                    while ($notice = $notices_query->fetch_assoc()): ?>
                        <span style="display: inline-block; margin-right: 60px;">
                            <strong style="color:#2563eb;">[<?php echo strtoupper($notice['type']); ?>]</strong>
                            <?php echo htmlspecialchars($notice['title']); ?>: <?php echo htmlspecialchars($notice['content']); ?>
                        </span>
                    <?php endwhile;
                else: ?>
                    <span style="display: inline-block; margin-right: 60px;">
                        Welcome to ABSS - Online Admissions for Session 2026-27 are currently open for Netarhat, Sainik & Navodaya Preparation.
                    </span>
                <?php endif; ?>
            </marquee>
        </div>
    </div>
</section>

<!-- Target Competitive Exams Badges -->
<section id="exams" style="padding: 70px 0; background: #f8fafc;">
    <div class="container">
        <div style="text-align: center; margin-bottom: 45px;">
            <span style="color: #2563eb; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;">
                <?php echo htmlspecialchars($settings['coaching_section_subtitle'] ?? 'Competitive Preparation'); ?>
            </span>
            <h2 style="font-size: 2.2rem; color: #0f172a; font-weight: 900; margin-top: 5px;">
                <?php echo htmlspecialchars($settings['coaching_section_title'] ?? 'Entrance Coaching Programs'); ?>
            </h2>
            <?php if (!empty($settings['coaching_section_desc'])): ?>
                <p style="color: #64748b; font-size: 0.95rem; max-width: 650px; margin: 10px auto 0; font-weight: 500;">
                    <?php echo htmlspecialchars($settings['coaching_section_desc']); ?>
                </p>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
            <?php
            $schools_query = $conn->query("SELECT * FROM schools WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
            if ($schools_query && $schools_query->num_rows > 0) {
                while ($exam = $schools_query->fetch_assoc()) {
                    $icon_class = !empty($exam['icon']) ? $exam['icon'] : 'fas fa-graduation-cap';
                    $badge = !empty($exam['badge_text']) ? $exam['badge_text'] : '';
                    ?>
                    <div style="background: #ffffff; border-radius: 20px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: transform 0.25s ease; position: relative;" class="exam-card">
                        <?php if (!empty($badge)): ?>
                            <span style="position: absolute; top: 18px; right: 18px; background: #eff6ff; color: #2563eb; font-size: 0.72rem; font-weight: 800; padding: 3px 10px; border-radius: 50px; border: 1px solid #dbeafe;">
                                <?php echo htmlspecialchars($badge); ?>
                            </span>
                        <?php endif; ?>
                        <div style="width: 48px; height: 48px; border-radius: 14px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 15px;">
                            <i class="<?php echo htmlspecialchars($icon_class); ?>"></i>
                        </div>
                        <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0 0 6px 0;"><?php echo htmlspecialchars($exam['school_name']); ?></h3>
                        <div style="color: #2563eb; font-size: 0.84rem; font-weight: 800; margin-bottom: 2px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-user-graduate" style="font-size: 0.75rem;"></i> <?php echo htmlspecialchars(!empty($exam['description']) ? $exam['description'] : 'Class 6 / Entrance Batch'); ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                $exams_list = [
                    ["name" => "Netarhat Residential", "desc" => "Class 6 / Entrance Batch", "icon" => "fas fa-graduation-cap"],
                    ["name" => "Sainik School (AISSEE)", "desc" => "All India Sainik School", "icon" => "fas fa-shield-alt"],
                    ["name" => "Navodaya Vidyalaya", "desc" => "JNVST Entrance Batch", "icon" => "fas fa-award"],
                    ["name" => "Simultala Residential", "desc" => "State Merit Batch", "icon" => "fas fa-book-reader"],
                    ["name" => "BHU CHS Entrance", "desc" => "Banaras Hindu University", "icon" => "fas fa-university"],
                    ["name" => "Rashtriya Military School", "desc" => "RMS Entrance Batch", "icon" => "fas fa-medal"]
                ];
                foreach ($exams_list as $exam) {
                    ?>
                    <div style="background: #ffffff; border-radius: 20px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: transform 0.25s ease;" class="exam-card">
                        <div style="width: 48px; height: 48px; border-radius: 14px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-bottom: 15px;">
                            <i class="<?php echo $exam['icon']; ?>"></i>
                        </div>
                        <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0 0 6px 0;"><?php echo $exam['name']; ?></h3>
                        <div style="color: #2563eb; font-size: 0.84rem; font-weight: 800; margin-bottom: 2px; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-user-graduate" style="font-size: 0.75rem;"></i> <?php echo htmlspecialchars($exam['desc']); ?>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</section>

<!-- Secretary Vision Section -->
<section id="about" style="padding: 80px 0; background: #ffffff;">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 50px; align-items: center;" class="vision-grid-resp">
            
            <!-- Left Image Card -->
            <div style="position: relative;">
                <div style="background: linear-gradient(135deg, #0d47a1, #1e1b4b); border-radius: 30px; padding: 15px; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                    <?php $director_img = !empty($settings['director_image_path']) ? $settings['director_image_path'] : 'assets/Secratery.png'; ?>
                    <img src="<?php echo htmlspecialchars($director_img); ?>" alt="Suman Kumar" style="width: 100%; border-radius: 20px; display: block; object-fit: cover; max-height: 420px;">
                    <div style="padding: 20px 10px 10px 10px; text-align: center; color: #ffffff;">
                        <h3 style="margin: 0; font-size: 1.3rem; font-weight: 900;">Suman Kumar</h3>
                        <p style="margin: 4px 0 0 0; color: #38bdf8; font-size: 0.88rem; font-weight: 700;">Secretary, Lok Kala Vikas Manch</p>
                    </div>
                </div>
            </div>

            <!-- Right Content -->
            <div>
                <span style="color: #2563eb; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;">Secretary's Message</span>
                <h2 style="font-size: 2.3rem; color: #0f172a; font-weight: 900; margin: 6px 0 20px 0; line-height: 1.2;">
                    Nurturing Character & Intellectual Excellence
                </h2>
                <p style="font-size: 1.1rem; color: #475569; font-style: italic; border-left: 4px solid #2563eb; padding-left: 18px; margin-bottom: 20px; font-weight: 600;">
                    "Success is not just about marks; it's about the courage to compete and the character to win."
                </p>
                <p style="color: #475569; line-height: 1.7; font-size: 0.95rem; margin-bottom: 30px; font-weight: 500;">
                    Welcome to <b>Awasiya Bal Shikshan Sansthan</b>. We bridge the gap between standard schooling and competitive brilliance. Our specialized curriculum and residential environment are designed to foster disciplined growth and intellectual curiosity.
                </p>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="background: #f8fafc; padding: 16px; border-radius: 14px; border: 1px solid #e2e8f0; font-weight: 800; color: #0f172a; font-size: 0.88rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-shield-alt" style="color: #2563eb; font-size: 1.1rem;"></i> 100% Safety & Security
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 14px; border: 1px solid #e2e8f0; font-weight: 800; color: #0f172a; font-size: 0.88rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-brain" style="color: #7c3aed; font-size: 1.1rem;"></i> Mental Ability Training
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 14px; border: 1px solid #e2e8f0; font-weight: 800; color: #0f172a; font-size: 0.88rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-flask" style="color: #059669; font-size: 1.1rem;"></i> Research Pedagogy
                    </div>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 14px; border: 1px solid #e2e8f0; font-weight: 800; color: #0f172a; font-size: 0.88rem; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-trophy" style="color: #d97706; font-size: 1.1rem;"></i> Proven Selections
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Facilities Grid -->
<section id="facilities" style="padding: 80px 0; background: #f8fafc;">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <span style="color: #2563eb; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;">World-Class Amenities</span>
            <h2 style="font-size: 2.2rem; color: #0f172a; font-weight: 900; margin-top: 5px;">Campus Facilities</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px;">
            <div style="background: #ffffff; padding: 30px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                <div style="width: 52px; height: 52px; border-radius: 16px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 18px;">
                    <i class="fas fa-building"></i>
                </div>
                <h3 style="font-size: 1.2rem; color: #0f172a; font-weight: 800; margin: 0 0 10px 0;">Residential Hostel</h3>
                <p style="color: #64748b; font-size: 0.9rem; line-height: 1.6; margin: 0; font-weight: 500;">
                    Secure, comfortable hostel facilities for students with 24/7 warden supervision and quiet study rooms.
                </p>
            </div>

            <div style="background: #ffffff; padding: 30px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                <div style="width: 52px; height: 52px; border-radius: 16px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 18px;">
                    <i class="fas fa-utensils"></i>
                </div>
                <h3 style="font-size: 1.2rem; color: #0f172a; font-weight: 800; margin: 0 0 10px 0;">Hygienic Organic Dining</h3>
                <p style="color: #64748b; font-size: 0.9rem; line-height: 1.6; margin: 0; font-weight: 500;">
                    Nutritious, balanced meals prepared under strict hygiene standards along with pure RO drinking water.
                </p>
            </div>

            <div style="background: #ffffff; padding: 30px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                <div style="width: 52px; height: 52px; border-radius: 16px; background: #f3e8ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 18px;">
                    <i class="fas fa-pencil-ruler"></i>
                </div>
                <h3 style="font-size: 1.2rem; color: #0f172a; font-weight: 900; margin: 0 0 10px 0;">OMR Test Drills</h3>
                <p style="color: #64748b; font-size: 0.9rem; line-height: 1.6; margin: 0; font-weight: 500;">
                    Weekly real-time exam practice sessions with detailed performance evaluation and rank publication.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Hall of Excellence: Achievers Showcase -->
<section id="achievers" style="padding: 80px 0; background: #ffffff;">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <span style="color: #2563eb; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;">Hall of Excellence</span>
            <h2 style="font-size: 2.2rem; color: #0f172a; font-weight: 900; margin-top: 5px;">Our Proud Selections</h2>
            <p style="max-width: 600px; margin: 8px auto 0 auto; color: #64748b; font-size: 0.95rem; font-weight: 500;">
                Celebrating student scholars who cracked top entrance examinations under ABSS mentorship.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px;">
            <?php
            $achievers_query = $conn->query("SELECT * FROM achievers ORDER BY created_at DESC LIMIT 6");
            if ($achievers_query && $achievers_query->num_rows > 0):
                while ($achiever = $achievers_query->fetch_assoc()): ?>
                    <div style="background: #ffffff; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); text-align: center; padding-bottom: 20px; transition: transform 0.3s ease;">
                        <div style="height: 220px; overflow: hidden; background: #f1f5f9; position: relative;">
                            <a href="<?php echo htmlspecialchars($achiever['image_path']); ?>" class="glightbox" data-gallery="achievers" data-title="<?php echo htmlspecialchars($achiever['name']); ?> - <?php echo htmlspecialchars($achiever['target_school']); ?> (Batch <?php echo htmlspecialchars($achiever['batch_year']); ?>)" style="display: block; width: 100%; height: 100%;">
                                <img src="<?php echo htmlspecialchars($achiever['image_path']); ?>" alt="<?php echo htmlspecialchars($achiever['name']); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease;">
                                <div style="position: absolute; inset: 0; background: rgba(15, 23, 42, 0.35); opacity: 0; transition: opacity 0.3s ease; display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 1.5rem;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                                    <i class="fas fa-search-plus"></i>
                                </div>
                            </a>
                        </div>
                        <div style="padding: 15px 15px 0 15px;">
                            <h3 style="margin: 0 0 4px 0; font-size: 1.1rem; color: #0f172a; font-weight: 800;"><?php echo htmlspecialchars($achiever['name']); ?></h3>
                            <div style="color: #2563eb; font-weight: 800; font-size: 0.85rem; margin-bottom: 4px;"><?php echo htmlspecialchars($achiever['target_school']); ?></div>
                            <small style="color: #64748b; font-weight: 700;">Batch <?php echo htmlspecialchars($achiever['batch_year']); ?></small>
                        </div>
                    </div>
                <?php endwhile;
            else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #94a3b8; font-weight: 600;">
                    Achievers list is being updated for session 2026.
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Gallery Section -->
<section id="gallery" style="padding: 80px 0; background: #f8fafc;">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <span style="color: #2563eb; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;">Campus Life</span>
            <h2 style="font-size: 2.2rem; color: #0f172a; font-weight: 900; margin-top: 5px;">Photos & Moments</h2>
            <p style="color: #64748b; font-weight: 600; font-size: 0.95rem; max-width: 600px; margin: 8px auto 0;">Glimpses of daily life, academic excellence, student activities, and achievements at ABSS.</p>
        </div>

        <?php
        // Fetch up to 8 photos from database
        $gallery_items = [];
        $gallery_query = $conn->query("SELECT * FROM gallery ORDER BY created_at DESC LIMIT 8");
        if ($gallery_query && $gallery_query->num_rows > 0) {
            while ($row = $gallery_query->fetch_assoc()) {
                $gallery_items[] = [
                    'image' => $row['image_path'],
                    'caption' => !empty($row['caption']) ? $row['caption'] : 'Campus Life at ABSS',
                    'category' => !empty($row['category']) ? $row['category'] : 'Campus Activity'
                ];
            }
        }

        // Fallback curated photos to guarantee minimum 8 photos if DB has fewer records
        $fallback_gallery = [
            ['image' => 'assets/gallery/1776881454_1600.jpg', 'caption' => 'Competitive Entrance Coaching & Classroom Session', 'category' => 'Academics'],
            ['image' => 'assets/gallery/1780112974_9409.png', 'caption' => 'Morning Assembly & Physical Fitness Training', 'category' => 'Campus Discipline'],
            ['image' => 'assets/gallery/1787299395_6788.png', 'caption' => 'OMR Weekly Test Series & Evaluation', 'category' => 'Examinations'],
            ['image' => 'assets/gallery/1787299429_3090.png', 'caption' => 'Annual Merit Awards & Felicitation Ceremony', 'category' => 'Excellence'],
            ['image' => 'images/home.jpeg', 'caption' => 'ABSS Campus Entrance & Learning Facility', 'category' => 'Campus Life'],
            ['image' => 'assets/achievers/1776881528_9857.jpg', 'caption' => 'Netarhat & Navodaya Qualifying Scholars', 'category' => 'Achievers'],
            ['image' => 'assets/achievers/1776882403_7564.jpg', 'caption' => 'Sainik School & Simultala Selected Candidates', 'category' => 'Pride of ABSS'],
            ['image' => 'assets/Secratery.png', 'caption' => 'Mentorship & Student Guidance Session', 'category' => 'Leadership']
        ];

        foreach ($fallback_gallery as $fb) {
            if (count($gallery_items) >= 8) break;
            $already_present = false;
            foreach ($gallery_items as $gi) {
                if ($gi['image'] === $fb['image']) {
                    $already_present = true;
                    break;
                }
            }
            if (!$already_present && file_exists(__DIR__ . '/' . $fb['image'])) {
                $gallery_items[] = $fb;
            }
        }

        // Strictly cap at min/max 8 items
        $gallery_items = array_slice($gallery_items, 0, 8);
        ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px;">
            <?php foreach ($gallery_items as $photo): ?>
                <div class="gallery-card-item" style="border-radius: 18px; overflow: hidden; height: 220px; border: 1px solid #e2e8f0; position: relative; background: #0f172a; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.3s ease, box-shadow 0.3s ease;">
                    <a href="<?php echo htmlspecialchars($photo['image']); ?>" class="glightbox" data-gallery="campus-life" data-title="<?php echo htmlspecialchars($photo['caption']); ?>" data-description="<?php echo htmlspecialchars($photo['category']); ?>" style="display: block; width: 100%; height: 100%; position: relative; overflow: hidden;">
                        <img src="<?php echo htmlspecialchars($photo['image']); ?>" alt="<?php echo htmlspecialchars($photo['caption']); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                        <div class="gallery-overlay" style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(15, 23, 42, 0.85) 0%, rgba(15, 23, 42, 0) 50%); opacity: 0; transition: opacity 0.3s ease; display: flex; flex-direction: column; justify-content: flex-end; padding: 16px;">
                            <span style="display: inline-block; background: #2563eb; color: #fff; font-size: 0.7rem; font-weight: 800; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; width: fit-content; margin-bottom: 4px;"><?php echo htmlspecialchars($photo['category']); ?></span>
                            <h4 style="color: #ffffff; font-size: 0.85rem; font-weight: 700; margin: 0; line-height: 1.3; text-shadow: 0 1px 3px rgba(0,0,0,0.5);"><?php echo htmlspecialchars($photo['caption']); ?></h4>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 35px;">
            <a href="gallery.php" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 800; color: #2563eb; text-decoration: none; font-size: 0.95rem; padding: 10px 24px; border-radius: 50px; background: #eff6ff; border: 1px solid #bfdbfe; transition: all 0.25s ease;">
                <i class="fas fa-images"></i> View Complete Campus Gallery & Videos →
            </a>
        </div>
    </div>
</section>

<style>
    .gallery-card-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(37, 99, 235, 0.18) !important;
    }
    .gallery-card-item:hover img {
        transform: scale(1.08);
    }
    .gallery-card-item:hover .gallery-overlay {
        opacity: 1 !important;
    }
</style>

<!-- Fee Structure & Admission Plan -->
<section id="admission" style="padding: 80px 0; background: #ffffff;">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <span style="color: #2563eb; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;">Transparent Fee Structure</span>
            <h2 style="font-size: 2.2rem; color: #0f172a; font-weight: 900; margin-top: 5px;">Investment in Your Child's Future</h2>
            <p style="color:#64748b; font-weight:600; font-size:0.95rem; max-width:600px; margin:8px auto 0;">Comprehensive pricing breakdown with dynamic features checklist for each admission category.</p>
        </div>

        <?php
        // 1. Fetch Tuition Modes dynamically from DB settings
        $tuition_modes = [];
        if (!empty($settings['tuition_modes'])) {
            $tuition_modes = json_decode($settings['tuition_modes'], true);
        } else {
            $tuition_modes = ['Hostler' => 5000, 'Day Scholar' => 3000, 'Tuition' => 1500];
        }

        // 2. Fetch Plan Features dynamically from DB settings
        $plan_features = [];
        if (!empty($settings['plan_features'])) {
            $loaded_features = json_decode($settings['plan_features'], true) ?: [];
            foreach ($loaded_features as $feat) {
                if (isset($feat['modes'])) {
                    $plan_features[] = $feat;
                } else {
                    $modes = [];
                    if (!empty($feat['res'])) { $modes[] = 'Hostler'; $modes[] = 'Residential Scholar'; }
                    if (!empty($feat['day'])) { $modes[] = 'Day Scholar'; }
                    $plan_features[] = [
                        'feature' => $feat['feature'],
                        'modes' => $modes
                    ];
                }
            }
        } else {
            // DB Fallback Default Features List
            $plan_features = [
                ['feature' => '24/7 Secure Residential Hostel Stay', 'modes' => ['Hostler', 'Residential Scholar']],
                ['feature' => 'Hygienic Organic Meals & RO Drinking Water', 'modes' => ['Hostler', 'Residential Scholar']],
                ['feature' => 'Full-Day Intensive Classroom Coaching', 'modes' => ['Hostler', 'Day Scholar', 'Residential Scholar']],
                ['feature' => 'Specialized Mental Ability & Reasoning Drills', 'modes' => ['Hostler', 'Day Scholar', 'Residential Scholar']],
                ['feature' => 'Weekly OMR Test Series & National Ranks', 'modes' => ['Hostler', 'Day Scholar', 'Residential Scholar']],
                ['feature' => '24/7 Warden & Medical Supervision', 'modes' => ['Hostler', 'Residential Scholar']],
                ['feature' => 'Core Subject Foundation & Tuition Classes', 'modes' => ['Hostler', 'Day Scholar', 'Tuition', 'Residential Scholar']],
                ['feature' => 'Daily Doubts Clearing & Concept Drills', 'modes' => ['Hostler', 'Day Scholar', 'Tuition', 'Residential Scholar']],
                ['feature' => 'Printed Worksheets & Practice Question Banks', 'modes' => ['Hostler', 'Day Scholar', 'Tuition', 'Residential Scholar']]
            ];
        }
        ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 50px; align-items: flex-start;">
            <?php foreach ($tuition_modes as $modeName => $modeFee): 
                $is_hostler = (strpos(strtolower($modeName), 'hostler') !== false) || (strpos(strtolower($modeName), 'res') !== false);
                $cardBorder = $is_hostler ? '2px solid #2563eb' : '1px solid #e2e8f0';
                $cardShadow = $is_hostler ? '0 15px 35px rgba(37, 99, 235, 0.15)' : '0 4px 20px rgba(0,0,0,0.02)';
            ?>
                <div style="background: #ffffff; border-radius: 28px; padding: 35px 28px; border: <?php echo $cardBorder; ?>; box-shadow: <?php echo $cardShadow; ?>; position: relative; display: flex; flex-direction: column;">
                    
                    <?php if ($is_hostler): ?>
                        <div style="position: absolute; top: -14px; left: 50%; transform: translateX(-50%); background: #2563eb; color: #ffffff; padding: 4px 16px; border-radius: 50px; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
                            ★ Recommended
                        </div>
                    <?php endif; ?>

                    <div style="text-align: center; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px;">
                        <h3 style="font-size: 1.5rem; color: #0f172a; font-weight: 900; margin: 0 0 10px 0;"><?php echo htmlspecialchars($modeName); ?></h3>
                        <div style="font-size: 2.5rem; font-weight: 900; color: #2563eb; line-height: 1;">
                            ₹ <?php echo number_format($modeFee); ?> <span style="font-size: 0.9rem; color: #64748b; font-weight: 700;">/ month</span>
                        </div>
                    </div>

                    <!-- Facilities & Features Checklist (Fetched Dynamically from DB Settings) -->
                    <div style="margin-bottom: 30px; flex-grow: 1;">
                        <h4 style="font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; font-weight: 800; margin: 0 0 15px 0;">Program Features & Amenities</h4>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php foreach ($plan_features as $feat): 
                                $has_feature = false;
                                if (isset($feat['modes']) && is_array($feat['modes'])) {
                                    $has_feature = in_array($modeName, $feat['modes']);
                                }
                            ?>
                                <li style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; font-size: 0.9rem; font-weight: 600; color: <?php echo $has_feature ? '#334155' : '#94a3b8'; ?>;">
                                    <?php if ($has_feature): ?>
                                        <i class="fas fa-check-circle" style="color: #16a34a; font-size: 1.1rem; margin-top: 2px; flex-shrink: 0;"></i>
                                        <span><?php echo htmlspecialchars($feat['feature']); ?></span>
                                    <?php else: ?>
                                        <i class="fas fa-times-circle" style="color: #ef4444; font-size: 1.1rem; margin-top: 2px; flex-shrink: 0; opacity: 0.7;"></i>
                                        <span style="text-decoration: line-through; opacity: 0.6;"><?php echo htmlspecialchars($feat['feature']); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <a href="admission.php" style="display: block; width: 100%; text-align: center; background: <?php echo $is_hostler ? 'linear-gradient(135deg, #2563eb, #1d4ed8)' : '#0f172a'; ?>; color: #ffffff; padding: 14px 0; border-radius: 14px; font-weight: 800; text-decoration: none; font-size: 0.95rem; box-shadow: <?php echo $is_hostler ? '0 8px 20px rgba(37, 99, 235, 0.3)' : 'none'; ?>;">
                        Apply for <?php echo htmlspecialchars($modeName); ?> →
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Online Admission CTA Card -->
        <div style="background: linear-gradient(135deg, #0f172a, #1d4ed8); color: #ffffff; padding: 50px 30px; border-radius: 30px; text-align: center; box-shadow: 0 20px 40px rgba(29, 78, 216, 0.25);">
            <h3 style="font-size: 2rem; font-weight: 900; margin: 0 0 15px 0;">Ready to secure your child's seat for 2026-27?</h3>
            <p style="font-size: 1.05rem; color: #cbd5e1; max-width: 600px; margin: 0 auto 30px auto; font-weight: 500;">
                Online Admission registrations are currently open. Complete the form online or contact our Gaya center directly.
            </p>
            <a href="admission.php" style="background: #ffffff; color: #0f172a; padding: 16px 36px; border-radius: 50px; font-weight: 900; text-decoration: none; font-size: 1.05rem; display: inline-flex; align-items: center; gap: 10px;">
                Online Admission Registration <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<style>
    /* Hero Action Buttons Styles */
    .hero-action-buttons {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: center;
        margin-bottom: 40px;
    }

    .hero-cta-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 16px 28px;
        border-radius: 50px;
        font-weight: 800;
        text-decoration: none;
        font-size: 1rem;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-admission {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
    }
    .btn-admission:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(37, 99, 235, 0.5);
        color: #ffffff;
    }

    .btn-prospectus-big {
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 10px 25px rgba(255, 255, 255, 0.15);
        font-size: 1.05rem;
        padding: 17px 32px;
    }
    .btn-prospectus-big:hover {
        background: #f8fafc;
        transform: translateY(-2px);
        color: #0f172a;
    }
    .btn-prospectus-big i {
        color: #dc2626;
        font-size: 1.25rem;
    }

    .btn-app-download {
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(12px);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.25);
    }
    .btn-app-download:hover {
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        transform: translateY(-2px);
    }
    .btn-app-download i {
        color: #4ade80;
        font-size: 1.2rem;
    }

    @media (max-width: 900px) {
        .hero-grid-resp { grid-template-columns: 1fr !important; gap: 30px !important; }
        .hero-image-col { display: none; }
        .vision-grid-resp { grid-template-columns: 1fr !important; gap: 30px !important; }
        
        .hero-action-buttons {
            flex-direction: column;
            width: 100%;
            gap: 12px;
        }
        .hero-cta-btn {
            width: 100%;
            padding: 15px 20px;
            font-size: 0.95rem;
        }
        .btn-prospectus-big {
            font-size: 1rem;
            padding: 16px 20px;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>