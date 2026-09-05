/**
 * js/invoice-share-bridge.js - PhonePe / Google Pay Style WhatsApp Invoice Sharing Engine
 * Built specifically for Web-to-App Wrappers (WebView / PWA / Web2App / Android Browsers)
 * 
 * Flow:
 * 1. Captures invoice DOM at 2x Retina resolution with html2canvas.
 * 2. Creates a clean PNG image Blob/File.
 * 3. Uses Android Web Share API (Level 2) to share Image + Message directly to WhatsApp.
 * 4. Fallback: Automatically downloads the invoice image & launches WhatsApp with the pre-filled text.
 */

(function(window) {
    'use strict';

    // Inject required styles for smooth loading spinner and toast
    const styleEl = document.createElement('style');
    styleEl.innerHTML = `
        .abss-share-toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: rgba(15, 23, 42, 0.95);
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 0.88rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 999999;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
            backdrop-filter: blur(8px);
        }
        .abss-share-toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
    `;
    document.head.appendChild(styleEl);

    function showToast(msg, icon) {
        let toast = document.getElementById('abssShareToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'abssShareToast';
            toast.className = 'abss-share-toast';
            document.body.appendChild(toast);
        }
        toast.innerHTML = `<i class="${icon || 'fas fa-info-circle'}" style="color:#22c55e;"></i> <span>${msg}</span>`;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    /**
     * Main Sharing Function
     */
    window.shareInvoiceOnWhatsApp = async function(options) {
        options = options || {};
        const containerId = options.containerId || 'receiptContainer';
        const container = document.getElementById(containerId) || document.querySelector('.receipt-container');

        if (!container) {
            alert('Invoice content not found for sharing.');
            return;
        }

        const studentName = options.studentName || 'Student';
        const invoiceNo   = options.invoiceNo || 'INV-001';
        const amount      = options.amount || '0.00';
        const dateStr     = options.date || new Date().toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        const parentPhone = (options.phone || '').replace(/[^0-9]/g, '');

        // Pre-filled WhatsApp message matching requirement
        const shareMessage = 
`Dear Parent,

Please find attached the Fee Invoice/Payment Receipt of ${studentName}.

Receipt No: ${invoiceNo}
Amount: ₹${amount}
Date: ${dateStr}

Thank you,
ABSS – Aawasiye Bal Sikshan Sansthan`;

        const btn = options.btnId ? document.getElementById(options.btnId) : null;
        let originalBtnHtml = '';
        if (btn) {
            originalBtnHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing Invoice...';
            btn.disabled = true;
            btn.style.opacity = '0.85';
        }

        showToast('Generating invoice image...', 'fas fa-camera');

        try {
            // Ensure html2canvas is loaded
            if (typeof html2canvas === 'undefined') {
                await loadHtml2Canvas();
            }

            // Scroll container into view temporarily to prevent partial canvas cut
            window.scrollTo({ top: 0, behavior: 'instant' });

            // Render high-res crisp invoice
            const canvas = await html2canvas(container, {
                scale: 2.5, // Ultra sharp 2.5x retina resolution
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                logging: false,
                scrollX: 0,
                scrollY: 0,
                windowWidth: document.documentElement.offsetWidth,
                onclone: (clonedDoc) => {
                    const el = clonedDoc.getElementById(containerId) || clonedDoc.querySelector('.receipt-container');
                    if (el) {
                        el.style.boxShadow = 'none';
                        el.style.margin = '0 auto';
                        el.style.transform = 'none';
                    }
                }
            });

            const fileName = `ABSS_Receipt_${invoiceNo.replace(/[^a-zA-Z0-9_-]/g, '_')}.png`;
            const base64Data = canvas.toDataURL('image/png', 0.95);

            // ── 1. Android Native Web-to-App Bridge (if present) ──
            if (window.AndroidBridge && typeof window.AndroidBridge.shareInvoiceImage === 'function') {
                showToast('Launching WhatsApp...', 'fab fa-whatsapp');
                window.AndroidBridge.shareInvoiceImage(base64Data, fileName, shareMessage, 'image/png');
                restoreButton(btn, originalBtnHtml);
                return;
            }

            // ── 2. Web-to-App & Mobile Web Share API (Level 2 with File) ──
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png', 0.95));
            const file = new File([blob], fileName, { type: 'image/png' });

            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                showToast('Opening Share Menu...', 'fas fa-share-alt');
                try {
                    await navigator.share({
                        title: `Fee Receipt - ${studentName}`,
                        text: shareMessage,
                        files: [file]
                    });
                    showToast('Shared successfully!', 'fas fa-check-circle');
                    restoreButton(btn, originalBtnHtml);
                    return;
                } catch (shareErr) {
                    // User cancelled share dialog
                    if (shareErr.name === 'AbortError') {
                        restoreButton(btn, originalBtnHtml);
                        return;
                    }
                    console.warn('[InvoiceShare] Web Share File failed, falling to web fallback:', shareErr);
                }
            }

            // ── 3. Universal Web Fallback (Auto-save Image + WhatsApp Direct) ──
            showToast('Invoice image downloaded! Opening WhatsApp...', 'fab fa-whatsapp');
            
            // Download image to phone storage / gallery
            const downloadLink = document.createElement('a');
            downloadLink.href = base64Data;
            downloadLink.download = fileName;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);

            // Launch WhatsApp with pre-filled message
            const cleanPhone = parentPhone.length === 10 ? '91' + parentPhone : parentPhone;
            const waUrl = cleanPhone 
                ? `https://api.whatsapp.com/send?phone=${cleanPhone}&text=${encodeURIComponent(shareMessage)}`
                : `https://api.whatsapp.com/send?text=${encodeURIComponent(shareMessage)}`;

            setTimeout(() => {
                window.open(waUrl, '_blank');
            }, 500);

        } catch (err) {
            console.error('[InvoiceShare] Share Error:', err);
            alert('Could not generate invoice image: ' + err.message);
        } finally {
            restoreButton(btn, originalBtnHtml);
        }
    };

    function restoreButton(btn, originalHtml) {
        if (btn) {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    }

    function loadHtml2Canvas() {
        return new Promise((resolve, reject) => {
            if (window.html2canvas) {
                resolve(window.html2canvas);
                return;
            }
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
            script.onload = () => resolve(window.html2canvas);
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

})(window);
