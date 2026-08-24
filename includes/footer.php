    <footer class="main-footer" style="background: #0f172a; color: #94a3b8; padding-top: 50px; position: relative; border-top: 1px solid rgba(255,255,255,0.08);">
        
        <!-- App Download Banner -->
        <div class="container" style="margin-bottom: 45px;">
            <div class="footer-app-banner">
                <div style="flex: 1; min-width: 250px;">
                    <div style="display:inline-flex; align-items:center; gap:6px; background:rgba(56, 189, 248, 0.15); border:1px solid #38bdf8; color:#38bdf8; padding:4px 14px; border-radius:50px; font-size:0.75rem; font-weight:800; text-transform:uppercase; margin-bottom:10px;">
                        <i class="fas fa-mobile-alt"></i> ABSS Android App
                    </div>
                    <h2 style="color: #ffffff; margin: 0 0 6px 0; font-size: clamp(1.3rem, 2vw, 1.8rem); font-weight: 900;">Take ABSS Everywhere You Go</h2>
                    <p style="color: #cbd5e1; font-size: 0.88rem; max-width: 520px; margin: 0; font-weight: 500; line-height: 1.5;">Download our mobile app for instant access to student attendance, report cards, fees receipt, and notices.</p>
                </div>
                <div class="banner-btn-wrapper">
                    <a href="app/index.php" class="btn btn-app-footer-cta">
                        <i class="fab fa-google-play" style="font-size:1.1rem;"></i> Install App Now
                    </a>
                </div>
            </div>
        </div>

        <!-- 4-COLUMN RESPONSIVE FOOTER GRID -->
        <div class="container footer-grid-resp">
            
            <!-- COL 1: ABOUT INSTITUTION -->
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                    <img src="assets/logo.png" alt="Logo" style="height: 44px; background:#fff; padding:3px; border-radius:10px;">
                    <div>
                        <strong style="display:block; color:#ffffff; font-size:1rem; font-weight:900; line-height:1.1;">आवासीय बाल शिक्षण संस्थान</strong>
                        <small style="color:#38bdf8; font-weight:700; font-size:0.7rem; letter-spacing:0.05em; text-transform:uppercase;">ABSS Imamganj • Est. 2011</small>
                    </div>
                </div>
                <p style="color: #94a3b8; font-size: 0.85rem; line-height: 1.65; margin-bottom: 18px; font-weight: 500;">
                    Awasiya Bal Shikshan Sansthan (ABSS) is a premier competitive residential school dedicated to preparing young scholars for Netarhat, Sainik School, Navodaya Vidyalaya, and Simultala entrances.
                </p>
                <div class="social-links" style="display: flex; gap: 10px;">
                    <a href="<?php echo htmlspecialchars($settings['facebook'] ?? '#'); ?>" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="<?php echo htmlspecialchars($settings['twitter'] ?? '#'); ?>" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="<?php echo htmlspecialchars($settings['instagram'] ?? '#'); ?>" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://wa.me/919523012888" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>

            <!-- COL 2: QUICK LINKS -->
            <div>
                <h3 class="footer-col-title" style="border-left-color: #38bdf8;">Quick Links</h3>
                <ul class="footer-link-list">
                    <li><a href="index.php#home"><i class="fas fa-chevron-right"></i> Home Page</a></li>
                    <li><a href="index.php#about"><i class="fas fa-chevron-right"></i> About Institution</a></li>
                    <li><a href="gallery.php"><i class="fas fa-chevron-right"></i> Campus Gallery & Videos</a></li>
                    <li><a href="index.php#facilities"><i class="fas fa-chevron-right"></i> Campus Facilities</a></li>
                    <li><a href="index.php#achievers"><i class="fas fa-chevron-right"></i> Hall of Excellence</a></li>
                    <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Contact & Map</a></li>
                    <li><a href="admission.php" style="color: #38bdf8; font-weight:700;"><i class="fas fa-chevron-right"></i> Apply Admission 2026</a></li>
                </ul>
            </div>

            <!-- COL 3: PORTAL ACCESS -->
            <div>
                <h3 class="footer-col-title" style="border-left-color: #818cf8;">Portal Access</h3>
                <ul class="footer-link-list">
                    <li><a href="parent/login.php"><i class="fas fa-user-friends" style="color:#38bdf8;"></i> Parent Portal</a></li>
                    <li><a href="teacher/login.php"><i class="fas fa-chalkboard-teacher" style="color:#a78bfa;"></i> Teacher Portal</a></li>
                    <li><a href="admin/login.php"><i class="fas fa-user-shield" style="color:#f43f5e;"></i> Admin Control Panel</a></li>
                    <li><a href="app/index.php"><i class="fas fa-mobile-alt" style="color:#f59e0b;"></i> Download Web App</a></li>
                </ul>
            </div>

            <!-- COL 4: CAMPUS HELPDESK & LOCATION -->
            <div>
                <h3 class="footer-col-title" style="border-left-color: #f59e0b;">Campus Helpdesk</h3>
                <div style="font-size: 0.85rem; line-height: 1.8; color: #cbd5e1;">
                    <p style="margin: 0 0 8px 0; display: flex; gap: 10px; align-items: flex-start;">
                        <i class="fas fa-map-marker-alt" style="color: #38bdf8; margin-top: 4px; flex-shrink:0;"></i>
                        <span>Lok Kala Bhavan, Main Road, Imamganj, Gaya, Bihar 824206</span>
                    </p>
                    <p style="margin: 0 0 8px 0; display: flex; gap: 10px; align-items: center;">
                        <i class="fas fa-phone-alt" style="color: #38bdf8; flex-shrink:0;"></i>
                        <a href="tel:+919523012888" style="color: #38bdf8; font-weight: 800; text-decoration: none;">+91 9523012888</a>
                    </p>
                    <p style="margin: 0 0 8px 0; display: flex; gap: 10px; align-items: center;">
                        <i class="fas fa-envelope" style="color: #38bdf8; flex-shrink:0;"></i>
                        <a href="mailto:abssimamganj@gmail.com" style="color: #cbd5e1; text-decoration: none; word-break: break-all;">abssimamganj@gmail.com</a>
                    </p>
                    <p style="margin: 0; display: flex; gap: 10px; align-items: center; color: #94a3b8; font-size: 0.8rem;">
                        <i class="fas fa-clock" style="color: #f59e0b; flex-shrink:0;"></i>
                        <span>Mon - Sat: 8:00 AM - 7:00 PM</span>
                    </p>
                </div>
            </div>

        </div>

        <!-- COPYRIGHT BOTTOM RIBBON -->
        <div style="padding: 18px 0; background: #0b1324; font-size: 0.8rem; color: #64748b;">
            <div class="container footer-bottom-flex">
                <div>
                    &copy; <?php echo date('Y'); ?> <strong>Awasiya Bal Shikshan Sansthan</strong>. All Rights Reserved.
                </div>
                <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <a href="index.php#home" style="color: #94a3b8; text-decoration: none;">Back to Top <i class="fas fa-arrow-up" style="font-size:0.7rem; margin-left:3px;"></i></a>
                    <span style="opacity:0.3;">|</span>
                    <a href="admin/login.php" style="color: #94a3b8; text-decoration: none;">Staff Login</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Mobile Sticky Quick Action Dock -->
    <div class="mobile-action-dock">
        <a href="tel:+919523012888" class="dock-item dock-call">
            <i class="fas fa-phone-alt"></i> Call Us
        </a>
        <a href="https://wa.me/919523012888" target="_blank" class="dock-item dock-whatsapp">
            <i class="fab fa-whatsapp"></i> WhatsApp
        </a>
    </div>

    <style>
        .mobile-action-dock {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            z-index: 99990;
            padding: 8px 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 -4px 20px rgba(0,0,0,0.25);
        }
        @media (max-width: 768px) {
            .mobile-action-dock {
                display: flex;
                align-items: center;
                justify-content: space-around;
                gap: 8px;
            }
            body {
                padding-bottom: 56px; /* Prevent footer clipping */
            }
        }
        .dock-item {
            flex: 1;
            text-align: center;
            padding: 8px 10px;
            border-radius: 12px;
            color: #ffffff;
            font-weight: 800;
            font-size: 0.76rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: 0.2s;
        }
        .dock-call { background: rgba(56, 189, 248, 0.2); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.4); }
        .dock-whatsapp { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.4); }
        .dock-apply { background: linear-gradient(135deg, #f59e0b, #ea580c); color: #ffffff; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.35); }
    </style>

    <style>
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }

        .footer-app-banner {
            background: linear-gradient(135deg, #1e1b4b 0%, #1e40af 50%, #0d47a1 100%);
            border-radius: 20px;
            padding: 30px 25px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.15);
        }

        .btn-app-footer-cta {
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: #ffffff;
            font-weight: 900;
            padding: 13px 26px;
            font-size: 0.92rem;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 25px rgba(234, 88, 12, 0.4);
            text-decoration: none;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .footer-grid-resp {
            display: grid !important;
            grid-template-columns: 2fr 1fr 1fr 1.3fr !important;
            gap: 40px !important;
            padding-bottom: 45px !important;
            border-bottom: 1px solid rgba(255,255,255,0.08) !important;
        }

        .footer-col-title {
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 18px 0;
            border-left: 3px solid #38bdf8;
            padding-left: 10px;
        }

        .footer-link-list {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 0.85rem;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .footer-link-list a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .footer-link-list a:hover {
            color: #38bdf8;
        }

        .footer-link-list i {
            font-size: 0.7rem;
            color: #38bdf8;
        }

        .social-links a {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(255,255,255,0.06);
            color: #38bdf8;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .social-links a:hover {
            background: #2563eb;
            color: #ffffff;
            transform: translateY(-2px);
        }

        .footer-bottom-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* MOBILE RESPONSIVE MEDIA QUERIES */
        @media (max-width: 992px) {
            .footer-grid-resp {
                grid-template-columns: 1fr 1fr !important;
                gap: 30px !important;
            }
        }

        @media (max-width: 640px) {
            .footer-app-banner {
                padding: 22px 18px !important;
                text-align: center !important;
                flex-direction: column !important;
                align-items: center !important;
            }

            .banner-btn-wrapper {
                width: 100% !important;
            }

            .btn-app-footer-cta {
                width: 100% !important;
                justify-content: center !important;
            }

            .footer-grid-resp {
                grid-template-columns: 1fr !important;
                gap: 25px !important;
                padding-bottom: 30px !important;
            }

            .footer-bottom-flex {
                flex-direction: column !important;
                text-align: center !important;
                gap: 10px !important;
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

    <!-- Firebase FCM Notification Client Script -->
    <script src="js/fcm-client.js"></script>
</body>
</html>
