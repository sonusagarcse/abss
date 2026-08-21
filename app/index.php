<?php
require_once '../config/db.php';
$conn = getDB();
$settings = getAllSettings();
include '../includes/header.php';
?>

<!-- APP HERO SECTION -->
<section style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0d47a1 100%); padding: 80px 0 90px; color: #ffffff; position: relative; overflow: hidden;">
    <div style="position: absolute; top: -120px; right: -120px; width: 400px; height: 400px; background: rgba(56, 189, 248, 0.15); filter: blur(100px); border-radius: 50%;"></div>
    <div style="position: absolute; bottom: -120px; left: -120px; width: 400px; height: 400px; background: rgba(124, 58, 237, 0.15); filter: blur(100px); border-radius: 50%;"></div>

    <div class="container" style="position: relative; z-index: 2;">
        <div class="app-hero-grid">
            
            <!-- HERO TEXT CONTENT -->
            <div>
                <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(56, 189, 248, 0.15); border: 1px solid #38bdf8; color: #38bdf8; padding: 6px 18px; border-radius: 50px; font-size: 0.82rem; font-weight: 800; text-transform: uppercase; margin-bottom: 18px;">
                    <i class="fas fa-mobile-alt"></i> Official Parent Mobile App
                </div>

                <h1 style="font-size: clamp(2.2rem, 3.5vw, 3.2rem); font-weight: 900; line-height: 1.15; margin: 0 0 16px 0; letter-spacing: -0.02em;">
                    Experience ABSS <br>
                    <span style="background: linear-gradient(135deg, #38bdf8, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Right On Your Phone</span>
                </h1>

                <p style="font-size: 1.05rem; color: #cbd5e1; line-height: 1.6; margin-bottom: 30px; max-width: 540px; font-weight: 500;">
                    Get real-time push notifications for attendance, fee invoices, entrance marksheet rankings, and official announcements on your Android device.
                </p>

                <!-- DOWNLOAD & INSTALL BUTTONS -->
                <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center; margin-bottom: 25px;">
                    <button id="install-pwa-btn" class="btn-app-install">
                        <i class="fas fa-download"></i> Install Web App
                    </button>
                    <a href="ABSS_v1.2.1.APK" class="btn-app-apk" download>
                        <i class="fab fa-android"></i> Download APK File (v1.2.1)
                    </a>
                </div>

                <div id="install-status-text" style="font-size: 0.88rem; color: #38bdf8; font-weight: 700; margin-bottom: 25px;"></div>

                <!-- PORTAL QUICK LINKS -->
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; border-top: 1px solid rgba(255,255,255,0.12); padding-top: 20px;">
                    <span style="font-size: 0.82rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Direct Login:</span>
                    <a href="../parent/login.php" style="color: #38bdf8; font-weight: 800; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; background: rgba(255,255,255,0.06); padding: 6px 14px; border-radius: 50px;">
                        <i class="fas fa-user-friends"></i> Parent Portal
                    </a>
                    <a href="../teacher/login.php" style="color: #c084fc; font-weight: 800; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; background: rgba(255,255,255,0.06); padding: 6px 14px; border-radius: 50px;">
                        <i class="fas fa-chalkboard-teacher"></i> Teacher Portal
                    </a>
                    <a href="../admin/login.php" style="color: #f43f5e; font-weight: 800; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; background: rgba(255,255,255,0.06); padding: 6px 14px; border-radius: 50px;">
                        <i class="fas fa-user-shield"></i> Admin Panel
                    </a>
                </div>
            </div>

            <!-- HERO MOBILE MOCKUP DISPLAY -->
            <div style="display: flex; justify-content: center;">
                <div class="phone-mockup">
                    <div class="phone-notch"></div>
                    <div class="phone-screen">
                        
                        <!-- APP PREVIEW HEADER -->
                        <div style="background: linear-gradient(135deg, #1e1b4b, #2563eb); padding: 22px 18px 18px; color: #fff;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <img src="assets/logo.png" style="height: 28px; background: #fff; padding: 2px; border-radius: 6px;">
                                    <strong style="font-size: 0.85rem;">ABSS Portal</strong>
                                </div>
                                <span style="background: rgba(255,255,255,0.2); padding: 3px 8px; border-radius: 20px; font-size: 0.68rem; font-weight: 700;">Live</span>
                            </div>
                            <h4 style="margin: 0; font-size: 1rem; font-weight: 900;">Welcome, Parent!</h4>
                            <p style="margin: 2px 0 0 0; font-size: 0.72rem; opacity: 0.85;">Sonu Sagar • Roll #104</p>
                        </div>

                        <!-- APP CARDS SIMULATION -->
                        <div style="padding: 15px; display: flex; flex-direction: column; gap: 12px; background: #f8fafc; flex: 1;">
                            
                            <div style="background: #fff; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <small style="color: #64748b; font-size: 0.68rem; font-weight: 700;">ATTENDANCE TODAY</small>
                                    <div style="color: #16a34a; font-weight: 900; font-size: 0.88rem; margin-top: 2px;">Present (In Class)</div>
                                </div>
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>

                            <div style="background: #fff; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <small style="color: #64748b; font-size: 0.68rem; font-weight: 700;">EXAM MARKSHEET</small>
                                    <div style="color: #2563eb; font-weight: 900; font-size: 0.88rem; margin-top: 2px;">Navodaya Rank #1</div>
                                </div>
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                    <i class="fas fa-trophy"></i>
                                </div>
                            </div>

                            <div style="background: #fff; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <small style="color: #64748b; font-size: 0.68rem; font-weight: 700;">TUTION FEES</small>
                                    <div style="color: #0f172a; font-weight: 900; font-size: 0.88rem; margin-top: 2px;">₹3,000 / Paid</div>
                                </div>
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                            </div>

                            <div style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; padding: 12px; border-radius: 12px; text-align: center; margin-top: 5px;">
                                <div style="font-weight: 800; font-size: 0.8rem;">Tap to Open Parent Portal</div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- APP FEATURES GRID SECTION -->
<section style="padding: 80px 0; background: #ffffff;">
    <div class="container">
        
        <div style="text-align: center; margin-bottom: 50px;">
            <span style="color: #2563eb; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;">App Highlights</span>
            <h2 style="font-size: 2.2rem; color: #0f172a; font-weight: 900; margin-top: 5px;">Built for Parent Convenience</h2>
            <p style="max-width: 600px; margin: 8px auto 0; color: #64748b; font-size: 0.95rem; font-weight: 500;">
                All student updates delivered instantly to your device without visiting school offices.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px;">
            
            <div class="app-feat-card">
                <div class="app-feat-icon" style="background: #eff6ff; color: #2563eb;"><i class="fas fa-bell"></i></div>
                <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0 0 8px 0;">Instant Push Alerts</h3>
                <p style="color: #64748b; font-size: 0.88rem; line-height: 1.6; margin: 0; font-weight: 500;">Get real-time mobile notifications for emergency school notices, holidays, and exam dates.</p>
            </div>

            <div class="app-feat-card">
                <div class="app-feat-icon" style="background: #f0fdf4; color: #16a34a;"><i class="fas fa-chart-line"></i></div>
                <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0 0 8px 0;">Rank & Marksheets</h3>
                <p style="color: #64748b; font-size: 0.88rem; line-height: 1.6; margin: 0; font-weight: 500;">Track entrance test rankings for Netarhat, Sainik School, and Navodaya with detailed subject scores.</p>
            </div>

            <div class="app-feat-card">
                <div class="app-feat-icon" style="background: #fffbeb; color: #d97706;"><i class="fas fa-file-invoice-dollar"></i></div>
                <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0 0 8px 0;">Fee Receipts & Ledgers</h3>
                <p style="color: #64748b; font-size: 0.88rem; line-height: 1.6; margin: 0; font-weight: 500;">View monthly tuition invoices, download printable cash receipts, and monitor dues ledger online.</p>
            </div>

            <div class="app-feat-card">
                <div class="app-feat-icon" style="background: #f3e8ff; color: #7c3aed;"><i class="fas fa-user-check"></i></div>
                <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0 0 8px 0;">Attendance Monitor</h3>
                <p style="color: #64748b; font-size: 0.88rem; line-height: 1.6; margin: 0; font-weight: 500;">Daily attendance tracking ensures full safety and visibility over your child's presence in class.</p>
            </div>

            <div class="app-feat-card">
                <div class="app-feat-icon" style="background: #ffe4e6; color: #e11d48;"><i class="fas fa-headset"></i></div>
                <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0 0 8px 0;">Helpdesk Support</h3>
                <p style="color: #64748b; font-size: 0.88rem; line-height: 1.6; margin: 0; font-weight: 500;">Raise support tickets directly to the school principal and track query resolution online.</p>
            </div>

            <div class="app-feat-card">
                <div class="app-feat-icon" style="background: #ecfeff; color: #0891b2;"><i class="fas fa-bolt"></i></div>
                <h3 style="font-size: 1.15rem; color: #0f172a; font-weight: 800; margin: 0 0 8px 0;">Fast & Lightweight</h3>
                <p style="color: #64748b; font-size: 0.88rem; line-height: 1.6; margin: 0; font-weight: 500;">Requires less than 5MB storage. Works smoothly even on low 3G/4G networks in rural areas.</p>
            </div>

        </div>
    </div>
</section>

<!-- INSTALLATION PROCESS STEPS -->
<section style="padding: 80px 0; background: #0f172a; color: #ffffff;">
    <div class="container">
        
        <div style="text-align: center; margin-bottom: 50px;">
            <span style="color: #38bdf8; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;">Simple 3-Step Setup</span>
            <h2 style="font-size: 2.2rem; color: #ffffff; font-weight: 900; margin-top: 5px;">How to Install & Use</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; max-width: 1000px; margin: 0 auto;">
            
            <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 30px; position: relative;">
                <div style="width: 45px; height: 45px; border-radius: 50%; background: #38bdf8; color: #0f172a; font-weight: 900; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; margin-bottom: 18px;">1</div>
                <h3 style="color: #ffffff; font-size: 1.1rem; font-weight: 800; margin: 0 0 8px 0;">Click Install / Download</h3>
                <p style="color: #cbd5e1; font-size: 0.88rem; line-height: 1.6; margin: 0;">Tap the "Install Web App" or "Download APK" button at top of this page on your Android smartphone.</p>
            </div>

            <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 30px; position: relative;">
                <div style="width: 45px; height: 45px; border-radius: 50%; background: #818cf8; color: #0f172a; font-weight: 900; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; margin-bottom: 18px;">2</div>
                <h3 style="color: #ffffff; font-size: 1.1rem; font-weight: 800; margin: 0 0 8px 0;">Add to Home Screen</h3>
                <p style="color: #cbd5e1; font-size: 0.88rem; line-height: 1.6; margin: 0;">Confirm "Add to Home Screen" prompt. An ABSS app icon will immediately appear on your phone screen.</p>
            </div>

            <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 30px; position: relative;">
                <div style="width: 45px; height: 45px; border-radius: 50%; background: #4ade80; color: #0f172a; font-weight: 900; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; margin-bottom: 18px;">3</div>
                <h3 style="color: #ffffff; font-size: 1.1rem; font-weight: 800; margin: 0 0 8px 0;">Sign In & Track</h3>
                <p style="color: #cbd5e1; font-size: 0.88rem; line-height: 1.6; margin: 0;">Open the app icon and sign in using your registered Parent Mobile Number and Password.</p>
            </div>

        </div>

        <div style="text-align: center; margin-top: 45px;">
            <a href="../parent/login.php" class="btn" style="background: linear-gradient(135deg, #f59e0b, #ea580c); color: #ffffff; padding: 14px 35px; border-radius: 50px; font-weight: 900; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 10px 25px rgba(234, 88, 12, 0.4);">
                <i class="fas fa-sign-in-alt"></i> Login to Parent Portal Now
            </a>
        </div>

    </div>
</section>

<style>
    .app-hero-grid {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 50px;
        align-items: center;
    }

    .btn-app-install {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: #ffffff;
        border: none;
        padding: 14px 28px;
        border-radius: 50px;
        font-weight: 900;
        font-size: 0.95rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
        transition: all 0.25s ease;
    }
    .btn-app-install:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(37, 99, 235, 0.55);
    }

    .btn-app-apk {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.25);
        padding: 14px 28px;
        border-radius: 50px;
        font-weight: 800;
        font-size: 0.95rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.25s ease;
    }
    .btn-app-apk:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    .phone-mockup {
        width: 270px;
        height: 520px;
        background: #0f172a;
        border: 8px solid #334155;
        border-radius: 40px;
        box-shadow: 0 25px 60px rgba(0,0,0,0.5);
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .phone-notch {
        width: 120px;
        height: 18px;
        background: #334155;
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
        z-index: 10;
    }
    .phone-screen {
        flex: 1;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .app-feat-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 28px 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 6px 20px rgba(0,0,0,0.03);
        transition: all 0.3s ease;
    }
    .app-feat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.07);
    }
    .app-feat-icon {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        margin-bottom: 16px;
    }

    @media (max-width: 900px) {
        .app-hero-grid {
            grid-template-columns: 1fr;
            gap: 40px;
            text-align: center;
        }
        .app-hero-grid p {
            margin-left: auto;
            margin-right: auto;
        }
        .btn-app-install, .btn-app-apk {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<!-- DEDICATED MODULAR PWA INSTALLATION SCRIPT -->
<script src="app/pwa.js"></script>

<?php include '../includes/footer.php'; ?>
