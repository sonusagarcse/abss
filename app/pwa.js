// app/pwa.js - Dedicated ABSS Modular PWA Manager & Installer
(function() {
    'use strict';

    let deferredPrompt = null;
    let installBtn = null;
    let statusText = null;

    const getBasePath = function() {
        const baseEl = document.querySelector('base');
        if (baseEl && baseEl.getAttribute('href')) {
            return baseEl.getAttribute('href');
        }
        return '/abss/';
    };

    const initPWA = function() {
        installBtn = document.getElementById('install-pwa-btn');
        statusText = document.getElementById('install-status-text');

        // Check if running in standalone PWA app mode
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                             window.navigator.standalone === true ||
                             document.referrer.includes('android-app://');

        if (isStandalone) {
            if (installBtn) installBtn.style.display = 'none';
            if (statusText) statusText.innerHTML = '<i class="fas fa-check-circle" style="color:#22c55e;"></i> ABSS App is installed & running in Standalone mode.';
        }

        // Register Service Worker
        if ('serviceWorker' in navigator) {
            const basePath = getBasePath();
            const swPath = basePath.replace(/\/$/, '') + '/app/sw.js';
            const scopePath = basePath.replace(/\/$/, '') + '/app/';

            navigator.serviceWorker.register(swPath, { scope: scopePath })
                .then(function(reg) {
                    console.log('ABSS PWA ServiceWorker registered with scope:', reg.scope);
                })
                .catch(function(err) {
                    console.warn('ABSS PWA ServiceWorker registration notice:', err);
                });
        }

        // Intercept native PWA Installation Event
        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            deferredPrompt = e;
            if (statusText && !isStandalone) {
                statusText.innerHTML = '<i class="fas fa-arrow-circle-down" style="color:#38bdf8;"></i> App is ready to install! Click button above.';
            }
        });

        // Handle Installation Trigger
        if (installBtn) {
            installBtn.addEventListener('click', async function() {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const choiceResult = await deferredPrompt.userChoice;
                    if (choiceResult.outcome === 'accepted') {
                        if (installBtn) installBtn.style.display = 'none';
                        if (statusText) statusText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Installing ABSS App to your phone...';
                    } else {
                        if (statusText) statusText.innerHTML = '<i class="fas fa-info-circle"></i> Installation deferred.';
                    }
                    deferredPrompt = null;
                } else {
                    showInstallGuideModal();
                }
            });
        }

        // Handle App Installed Event
        window.addEventListener('appinstalled', function() {
            if (installBtn) installBtn.style.display = 'none';
            if (statusText) statusText.innerHTML = '<i class="fas fa-check-circle" style="color:#22c55e;"></i> ABSS App installed successfully!';
            deferredPrompt = null;
        });
    };

    // Responsive Installation Instructions Modal for iOS / Desktop / Fallbacks
    function showInstallGuideModal() {
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        let msgHtml = '';

        if (isIOS) {
            msgHtml = 'To install ABSS App on iOS:<br>1. Tap the Share icon (<i class="fas fa-share-square" style="color:#2563eb;"></i>) in Safari.<br>2. Scroll down and tap <strong>"Add to Home Screen"</strong>.';
        } else {
            msgHtml = 'To install ABSS App:<br>1. Tap your Browser Menu (⋮ or ⚙️).<br>2. Select <strong>"Add to Home screen"</strong> or <strong>"Install App"</strong>.';
        }

        let modal = document.getElementById('pwa-guide-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'pwa-guide-modal';
            modal.style.cssText = 'position:fixed; inset:0; background:rgba(15,23,42,0.75); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); z-index:999999; display:flex; align-items:center; justify-content:center; padding:20px;';
            modal.innerHTML = `
                <div style="background:#ffffff; color:#0f172a; border-radius:24px; padding:30px; max-width:420px; width:100%; text-align:center; box-shadow:0 25px 50px rgba(0,0,0,0.3); border:1px solid #e2e8f0; font-family:'Outfit', sans-serif;">
                    <div style="width:60px; height:60px; border-radius:50%; background:#eff6ff; color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:1.8rem; margin:0 auto 16px;">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 style="margin:0 0 10px 0; font-size:1.2rem; font-weight:900;">Install ABSS Mobile App</h3>
                    <p style="font-size:0.9rem; color:#475569; line-height:1.6; margin-bottom:22px;">${msgHtml}</p>
                    <button onclick="document.getElementById('pwa-guide-modal').remove()" style="background:#2563eb; color:#fff; border:none; padding:12px 30px; border-radius:50px; font-weight:800; cursor:pointer; width:100%; font-size:0.95rem;">Got It!</button>
                </div>
            `;
            document.body.appendChild(modal);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPWA);
    } else {
        initPWA();
    }
})();
