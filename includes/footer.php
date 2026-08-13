    <footer class="main-footer" style="background: #0f172a; color: #94a3b8; padding-top: 60px; position: relative; border-top: 1px solid rgba(255,255,255,0.08);">
        
        <!-- App Download Banner -->
        <div class="container" style="margin-bottom: 50px;">
            <div style="background: linear-gradient(135deg, #1e1b4b 0%, #1e40af 50%, #0d47a1 100%); border-radius: 24px; padding: 35px 30px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15);">
                <div style="flex: 1; min-width: 280px;">
                    <div style="display:inline-flex; align-items:center; gap:6px; background:rgba(56, 189, 248, 0.15); border:1px solid #38bdf8; color:#38bdf8; padding:4px 14px; border-radius:50px; font-size:0.75rem; font-weight:800; text-transform:uppercase; margin-bottom:10px;">
                        <i class="fas fa-mobile-alt"></i> ABSS Android App
                    </div>
                    <h2 style="color: #ffffff; margin: 0 0 6px 0; font-size: clamp(1.4rem, 2vw, 2rem); font-weight: 900;">Take ABSS Everywhere You Go</h2>
                    <p style="color: #cbd5e1; font-size: 0.92rem; max-width: 520px; margin: 0; font-weight: 500;">Download our mobile app for instant access to student attendance, report cards, fees receipt, and notices.</p>
                </div>
                <div>
                    <a href="app/index.php" class="btn" style="background: linear-gradient(135deg, #f59e0b, #ea580c); color: #ffffff; font-weight: 900; padding: 14px 28px; font-size: 0.95rem; border-radius: 50px; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 10px 25px rgba(234, 88, 12, 0.4); text-decoration: none; transition: all 0.3s ease;">
                        <i class="fab fa-google-play" style="font-size:1.1rem;"></i> Install App Now
                    </a>
                </div>
            </div>
        </div>

        <div class="container" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1.3fr; gap: 40px; padding-bottom: 50px; border-bottom: 1px solid rgba(255,255,255,0.08);" class="footer-grid-resp">
            
            <!-- COL 1: ABOUT INSTITUTION -->
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 18px;">
                    <img src="assets/logo.png" alt="Logo" style="height: 48px; background:#fff; padding:4px; border-radius:10px;">
                    <div>
                        <strong style="display:block; color:#ffffff; font-size:1.05rem; font-weight:900; line-height:1.1;">आवासीय बाल शिक्षण संस्थान</strong>
                        <small style="color:#38bdf8; font-weight:700; font-size:0.72rem; letter-spacing:0.05em; text-transform:uppercase;">ABSS Imamganj • Est. 2011</small>
                    </div>
                </div>
                <p style="color: #94a3b8; font-size: 0.88rem; line-height: 1.7; margin-bottom: 20px; font-weight: 500;">
                    Awasiya Bal Shikshan Sansthan (ABSS) is a premier competitive residential school dedicated to preparing young scholars for Netarhat, Sainik School, Navodaya Vidyalaya, and Simultala entrances.
                </p>
                <div class="social-links" style="display: flex; gap: 10px;">
                    <a href="<?php echo htmlspecialchars($settings['facebook'] ?? '#'); ?>" style="width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,0.06); color: #38bdf8; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s ease;"><i class="fab fa-facebook-f"></i></a>
                    <a href="<?php echo htmlspecialchars($settings['twitter'] ?? '#'); ?>" style="width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,0.06); color: #38bdf8; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s ease;"><i class="fab fa-twitter"></i></a>
                    <a href="<?php echo htmlspecialchars($settings['instagram'] ?? '#'); ?>" style="width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,0.06); color: #ec4899; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s ease;"><i class="fab fa-instagram"></i></a>
                    <a href="https://wa.me/919523012888" target="_blank" style="width: 36px; height: 36px; border-radius: 10px; background: rgba(255,255,255,0.06); color: #22c55e; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.2s ease;"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>

            <!-- COL 2: QUICK LINKS -->
            <div>
                <h3 style="color: #ffffff; font-size: 0.95rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 20px; border-left: 3px solid #38bdf8; padding-left: 10px;">Quick Links</h3>
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.88rem; display: flex; flex-direction: column; gap: 10px;">
                    <li><a href="index.php#home" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s ease;"><i class="fas fa-chevron-right" style="font-size:0.7rem; color:#38bdf8; margin-right:6px;"></i> Home Page</a></li>
                    <li><a href="index.php#about" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s ease;"><i class="fas fa-chevron-right" style="font-size:0.7rem; color:#38bdf8; margin-right:6px;"></i> About Institution</a></li>
                    <li><a href="index.php#facilities" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s ease;"><i class="fas fa-chevron-right" style="font-size:0.7rem; color:#38bdf8; margin-right:6px;"></i> Campus Facilities</a></li>
                    <li><a href="index.php#achievers" style="color: #cbd5e1; text-decoration: none; transition: color 0.2s ease;"><i class="fas fa-chevron-right" style="font-size:0.7rem; color:#38bdf8; margin-right:6px;"></i> Hall of Excellence</a></li>
                    <li><a href="admission.php" style="color: #38bdf8; text-decoration: none; font-weight:700;"><i class="fas fa-chevron-right" style="font-size:0.7rem; color:#38bdf8; margin-right:6px;"></i> Apply Admission 2026</a></li>
                </ul>
            </div>

            <!-- COL 3: PORTAL ACCESS -->
            <div>
                <h3 style="color: #ffffff; font-size: 0.95rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 20px; border-left: 3px solid #818cf8; padding-left: 10px;">Portal Access</h3>
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.88rem; display: flex; flex-direction: column; gap: 10px;">
                    <li><a href="parent/login.php" style="color: #cbd5e1; text-decoration: none;"><i class="fas fa-user-friends" style="color:#38bdf8; margin-right:6px;"></i> Parent Portal</a></li>
                    <li><a href="teacher/login.php" style="color: #cbd5e1; text-decoration: none;"><i class="fas fa-chalkboard-teacher" style="color:#a78bfa; margin-right:6px;"></i> Teacher Portal</a></li>
                    <li><a href="admin/login.php" style="color: #cbd5e1; text-decoration: none;"><i class="fas fa-user-shield" style="color:#f43f5e; margin-right:6px;"></i> Admin Control Panel</a></li>
                    <li><a href="app/index.php" style="color: #cbd5e1; text-decoration: none;"><i class="fas fa-mobile-alt" style="color:#f59e0b; margin-right:6px;"></i> Download Web App</a></li>
                </ul>
            </div>

            <!-- COL 4: CAMPUS HELPDESK & LOCATION -->
            <div>
                <h3 style="color: #ffffff; font-size: 0.95rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 20px; border-left: 3px solid #f59e0b; padding-left: 10px;">Campus Helpdesk</h3>
                <div style="font-size: 0.88rem; line-height: 1.8; color: #cbd5e1;">
                    <p style="margin: 0 0 8px 0; display: flex; gap: 10px; align-items: flex-start;">
                        <i class="fas fa-map-marker-alt" style="color: #38bdf8; margin-top: 4px;"></i>
                        <span>Lok Kala Bhavan, Main Road, Imamganj, Gaya, Bihar 824206</span>
                    </p>
                    <p style="margin: 0 0 8px 0; display: flex; gap: 10px; align-items: center;">
                        <i class="fas fa-phone-alt" style="color: #38bdf8;"></i>
                        <a href="tel:+919523012888" style="color: #38bdf8; font-weight: 800; text-decoration: none;">+91 9523012888</a>
                    </p>
                    <p style="margin: 0 0 8px 0; display: flex; gap: 10px; align-items: center;">
                        <i class="fas fa-envelope" style="color: #38bdf8;"></i>
                        <a href="mailto:abssimamganj@gmail.com" style="color: #cbd5e1; text-decoration: none;">abssimamganj@gmail.com</a>
                    </p>
                    <p style="margin: 0; display: flex; gap: 10px; align-items: center; color: #94a3b8; font-size: 0.82rem;">
                        <i class="fas fa-clock" style="color: #f59e0b;"></i>
                        <span>Mon - Sat: 8:00 AM - 7:00 PM</span>
                    </p>
                </div>
            </div>

        </div>

        <!-- COPYRIGHT BOTTOM RIBBON -->
        <div style="padding: 20px 0; background: #0b1324; font-size: 0.82rem; color: #64748b;">
            <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    &copy; <?php echo date('Y'); ?> <strong>Awasiya Bal Shikshan Sansthan</strong>. All Rights Reserved.
                </div>
                <div style="display: flex; gap: 18px; align-items: center;">
                    <a href="index.php#home" style="color: #94a3b8; text-decoration: none;">Back to Top <i class="fas fa-arrow-up" style="font-size:0.7rem; margin-left:4px;"></i></a>
                    <span style="opacity:0.3;">|</span>
                    <a href="admin/login.php" style="color: #94a3b8; text-decoration: none;">Staff Login</a>
                </div>
            </div>
        </div>
    </footer>

    <style>
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }

        .footer-grid-resp {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.3fr;
            gap: 40px;
        }

        @media (max-width: 992px) {
            .footer-grid-resp {
                grid-template-columns: 1fr 1fr !important;
                gap: 30px !important;
            }
        }

        @media (max-width: 640px) {
            .footer-grid-resp {
                grid-template-columns: 1fr !important;
                gap: 25px !important;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Initialize Gallery Slider
        if (document.querySelector('.gallery-swiper')) {
            const gallerySwiper = new Swiper('.gallery-swiper', {
                slidesPerView: 1,
                spaceBetween: 20,
                loop: false,
                centerInsufficientSlides: true,
                autoplay: {
                    delay: 3000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                breakpoints: {
                    640: { slidesPerView: 2, spaceBetween: 20 },
                    768: { slidesPerView: 3, spaceBetween: 30 },
                    1024: { slidesPerView: 4, spaceBetween: 40 },
                }
            });
        }
        
        // Initialize GLightbox for all images with class glightbox
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof GLightbox !== 'undefined') {
                const lightbox = GLightbox({
                    selector: '.glightbox',
                    touchNavigation: true,
                    loop: true,
                    zoomable: true
                });
            }
        });
    </script>

    <!-- ABSS Real-time Notification Polling Script -->
    <script src="notifications/polling.js"></script>
</body>
</html>
