<?php
require_once 'config/db.php';
$settings = getAllSettings();
// We don't include header.php here because we want a specialized full-screen experience
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Inauguration | Awasiya Bal Shikshan Sansthan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Confetti Library -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
            background: #050505;
        }

        .inauguration-container {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            /* Using the background image */
            background: url('images/home.jpeg') center/cover no-repeat;
        }

        /* Glassmorphism Overlay (makes image visible but content readable) */
        .bg-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            /* Only 40% darkness so the house is clearly visible */
            z-index: 1;
        }

        /* High-Tech Educational Panels */
        .digital-panel {
            position: absolute;
            top: 0;
            width: 50vw;
            height: 100vh;
            background: #0d1b2a;
            background-image:
                radial-gradient(circle at 50% 50%, rgba(212, 175, 55, 0.05) 0%, transparent 60%),
                linear-gradient(rgba(13, 27, 42, 0.95), rgba(13, 27, 42, 0.95));
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.9);
            transition: transform 3s cubic-bezier(0.77, 0, 0.175, 1);
            z-index: 50;
        }

        .panel-left {
            left: 0;
            transform-origin: left;
            border-right: 6px solid #d4af37;
            box-shadow: inset -15px 0 30px rgba(212, 175, 55, 0.2);
        }

        .panel-right {
            right: 0;
            transform-origin: right;
            border-left: 6px solid #d4af37;
            box-shadow: inset 15px 0 30px rgba(212, 175, 55, 0.2);
        }

        /* Glowing Center Line */
        .center-laser {
            position: absolute;
            top: 0;
            left: 50%;
            width: 4px;
            height: 100%;
            background: #d4af37;
            transform: translateX(-50%);
            box-shadow: 0 0 20px #d4af37, 0 0 40px #fff;
            z-index: 51;
            transition: opacity 0.5s;
        }

        /* Open Classes */
        .digital-panel.open-left {
            transform: translateX(-100%);
        }

        .digital-panel.open-right {
            transform: translateX(100%);
        }

        .laser-off {
            opacity: 0;
        }

        /* Tech Launch Trigger */
        .launch-trigger {
            position: absolute;
            z-index: 100;
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .launch-btn {
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, #f3e5ab 0%, #d4af37 100%);
            border: 4px solid #fff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 0 40px rgba(212, 175, 55, 0.6), inset 0 0 20px rgba(255, 255, 255, 0.5);
            animation: pulse-glow-edu 2s infinite;
            position: relative;
            cursor: pointer;
        }

        .launch-btn i {
            font-size: 4rem;
            color: #0d1b2a;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
        }

        .launch-text {
            margin-top: 25px;
            color: #d4af37;
            font-size: 1.6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 0 15px rgba(212, 175, 55, 0.8);
            background: rgba(13, 27, 42, 0.8);
            padding: 15px 30px;
            border-radius: 12px;
            border: 1px solid rgba(212, 175, 55, 0.4);
            text-align: center;
        }

        /* Main Content Revealed behind curtain */
        .main-content {
            position: absolute;
            z-index: 10;
            text-align: center;
            color: #fff;
            opacity: 0;
            transform: scale(0.9) translateY(40px);
            transition: all 1.5s cubic-bezier(0.25, 1, 0.5, 1);
            padding: 30px 40px;
            border-radius: 24px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6), inset 0 0 0 1px rgba(255, 255, 255, 0.1);
            max-width: 900px;
            width: 90%;
        }

        .main-content.revealed {
            opacity: 1;
            transform: scale(1) translateY(0);
        }

        .main-content h1 {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            margin-bottom: 5px;
            color: #ffffff;
            text-shadow: 0 5px 20px rgba(0, 0, 0, 0.8);
            line-height: 1.2;
            font-weight: 800;
        }

        .hi-text {
            font-size: 1.8rem;
            color: #fdb931;
            text-shadow: 0 0 20px rgba(253, 185, 49, 0.3);
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .contact-pill {
            display: inline-block;
            margin-bottom: 5px;
        }

        .contact-pill i {
            color: #66fcf1;
            font-size: 2.5rem;
            margin-right: 15px;
            vertical-align: middle;
            text-shadow: 0 0 15px rgba(102, 252, 241, 0.8);
        }

        .contact-pill span {
            color: #d4af37;
            font-size: 2.8rem;
            font-weight: 800;
            letter-spacing: 2px;
            vertical-align: middle;
            text-shadow: 0 0 20px rgba(212, 175, 55, 0.9);
            display: inline-block;
            animation: text-pulse 2s infinite;
        }

        @keyframes text-pulse {
            0% {
                text-shadow: 0 0 20px rgba(212, 175, 55, 0.6);
                transform: scale(1);
            }

            50% {
                text-shadow: 0 0 40px rgba(212, 175, 55, 1);
                transform: scale(1.05);
            }

            100% {
                text-shadow: 0 0 20px rgba(212, 175, 55, 0.6);
                transform: scale(1);
            }
        }

        .main-content p {
            font-size: 1.5rem;
            margin-bottom: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(253, 185, 49, 0.3);
            color: #e3f2fd;
            font-weight: 600;
        }

        .school-tags {
            margin: 15px 0 10px 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .tag-blue {
            background: linear-gradient(45deg, #0d47a1, #1565c0);
            color: #fff;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(13, 71, 161, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.2);
            letter-spacing: 1px;
        }

        .floating-controls {
            position: absolute;
            bottom: 30px;
            right: 30px;
            display: flex;
            gap: 15px;
            z-index: 100;
            opacity: 0;
            pointer-events: none;
            transition: opacity 1s ease 0.5s;
        }

        .floating-controls.revealed {
            opacity: 1;
            pointer-events: auto;
        }

        .control-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(13, 27, 42, 0.8);
            border: 2px solid #d4af37;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #d4af37;
            font-size: 1.5rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }

        .control-btn:hover {
            background: #d4af37;
            color: #0d1b2a;
            transform: scale(1.1);
        }

        .enter-btn {
            background: linear-gradient(45deg, #d4af37, #fdb931);
            color: #0b0c10;
            border: none;
        }

        .enter-btn:hover {
            background: linear-gradient(45deg, #fdb931, #fff);
            color: #0b0c10;
        }

        .logo-img {
            max-width: 200px;
            filter: drop-shadow(0 0 15px rgba(255, 255, 255, 0.4));
        }

        @keyframes pulse-glow-edu {
            0% {
                box-shadow: 0 0 20px rgba(212, 175, 55, 0.4), inset 0 0 20px rgba(255, 255, 255, 0.4);
                transform: scale(1);
            }

            50% {
                box-shadow: 0 0 50px rgba(212, 175, 55, 0.8), inset 0 0 30px rgba(255, 255, 255, 0.8);
                transform: scale(1.05);
            }

            100% {
                box-shadow: 0 0 20px rgba(212, 175, 55, 0.4), inset 0 0 20px rgba(255, 255, 255, 0.4);
                transform: scale(1);
            }
        }
    </style>
</head>

<body>

    <div class="inauguration-container">
        <div class="bg-overlay"></div>

        <!-- Cybernetic sliding panels -->
        <div class="digital-panel panel-left"></div>
        <div class="digital-panel panel-right"></div>
        <div class="center-laser" id="centerLaser"></div>

        <!-- Educational Trigger: Book Open icon -->
        <div class="launch-trigger" id="inaugurateBtn">
            <div class="launch-btn">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="launch-text">ज्ञान दीप प्रज्वलित करें <br><span
                    style="font-size: 1rem; color: #fff; font-weight: 400; opacity: 0.8;">(Click to Inaugurate)</span>
            </div>
        </div>

        <!-- Inside Content Overlay -->
        <div class="main-content" id="mainContent">
            <img src="assets/logo.png" alt="ABSS Logo" class="logo-img">
            <h1>
                <span class="hi-text">Welcome To</span>
                आवासीय बाल शिक्षण संस्थान
            </h1>
            <p>एक नये युग का मंगल शुभारंभ!</p>

            <div class="contact-pill">
                <i class="fas fa-phone-alt"></i>
                <span><?php echo !empty($settings['phone']) ? $settings['phone'] : '+91 8544 321 XXX'; ?></span>
            </div>

            <!-- School Names Tags -->
            <div class="school-tags">
                <span class="tag-blue">Navodaya</span>
                <span class="tag-blue">Netarhat</span>
                <span class="tag-blue">Sainik School</span>
                <span class="tag-blue">Simultala</span>
            </div>
        </div>

        <!-- Floating Buttons Container -->
        <div class="floating-controls" id="floatingControls">
            <button id="muteBtn" class="control-btn" title="Mute/Unmute Music">
                <i class="fas fa-volume-up"></i>
            </button>
            <a href="index.php" class="control-btn enter-btn" title="Enter Website">
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- High Tech Audio Effects -->
    <!-- Background Educational Ambient Music -->
    <audio id="bgMusic" src="https://cdn.pixabay.com/download/audio/2022/01/18/audio_d0a13f69d2.mp3" loop preload="auto"
        crossorigin="anonymous"></audio>
    <!-- Whoosh/Power Up for door opening -->
    <audio id="cutSound" src="https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3" preload="auto"
        crossorigin="anonymous"></audio>
    <!-- Success Fanfare/Cheering -->
    <audio id="cheerSound" src="https://assets.mixkit.co/active_storage/sfx/2000/2000-preview.mp3" preload="auto"
        crossorigin="anonymous"></audio>

    <script>
        document.getElementById('inaugurateBtn').addEventListener('click', function () {
            // 1. Play sounds
            const cutAudio = document.getElementById('cutSound');
            const cheerAudio = document.getElementById('cheerSound');

            const bgAudio = document.getElementById('bgMusic');

            cutAudio.volume = 1.0;
            cheerAudio.volume = 0.6;
            bgAudio.volume = 0.15; // Low educational background sound

            cutAudio.play().catch(e => console.log('Audio blocked:', e));
            setTimeout(() => {
                cheerAudio.play().catch(e => console.log('Audio blocked:', e));
                bgAudio.play().catch(e => console.log('BgMusic blocked:', e));
            }, 800);

            // 2. Hide trigger & turn off laser beam
            this.style.opacity = '0';
            this.style.pointerEvents = 'none';
            document.getElementById('centerLaser').classList.add('laser-off');

            // 3. Open Digital Panel doors
            setTimeout(() => {
                document.querySelector('.panel-left').classList.add('open-left');
                document.querySelector('.panel-right').classList.add('open-right');
            }, 400);

            // 4. Reveal Glassmorphism Content after curtains fully open + 2 seconds clear view
            setTimeout(() => {
                document.getElementById('mainContent').classList.add('revealed');
                document.getElementById('floatingControls').classList.add('revealed');
            }, 3500);

            // 5. Fire Neon Cyber Confetti
            setTimeout(() => {
                var duration = 8 * 1000;
                var animationEnd = Date.now() + duration;
                var defaults = { startVelocity: 30, spread: 360, ticks: 100, zIndex: 100 };

                function randomInRange(min, max) {
                    return Math.random() * (max - min) + min;
                }

                var interval = setInterval(function () {
                    var timeLeft = animationEnd - Date.now();

                    if (timeLeft <= 0) {
                        return clearInterval(interval);
                    }

                    var particleCount = 60 * (timeLeft / duration);
                    // Fire from two sides
                    confetti(Object.assign({}, defaults, {
                        particleCount,
                        origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 },
                        colors: ['#d4af37', '#66fcf1', '#ffffff'] // Gold, Cyan, White
                    }));
                    confetti(Object.assign({}, defaults, {
                        particleCount,
                        origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 },
                        colors: ['#d4af37', '#66fcf1', '#ffffff']
                    }));
                }, 250);
            }, 4000);
        });

        document.getElementById('muteBtn').addEventListener('click', function () {
            const bgAudio = document.getElementById('bgMusic');
            const icon = this.querySelector('i');
            if (bgAudio.muted) {
                bgAudio.muted = false;
                icon.classList.remove('fa-volume-mute');
                icon.classList.add('fa-volume-up');
            } else {
                bgAudio.muted = true;
                icon.classList.remove('fa-volume-up');
                icon.classList.add('fa-volume-mute');
            }
        });
    </script>

</body>

</html>