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
        const receiptUrl  = options.receiptUrl || window.location.href;

        // Pre-filled structured WhatsApp message
        const shareMessage = 
`*ABSS – Fee Invoice / Payment Receipt*

Dear Parent,

Please find attached the official Fee Invoice / Payment Receipt of *${studentName}*.

📋 *Receipt No:* ${invoiceNo}
💰 *Total Amount:* ₹${amount}
📅 *Date:* ${dateStr}

Thank you,
*Aawasiye Bal Sikshan Sansthan (ABSS)*`;

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
                allowTaint: false,
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
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png', 0.95));
            const file = new File([blob], fileName, { type: 'image/png', lastModified: Date.now() });

            // Clean & format parent phone number
            const cleanPhone = parentPhone.length === 10 ? '91' + parentPhone : parentPhone;

            // ── Priority 1: Android Native Web-to-App Bridge (if exposed by wrapper) ──
            if (window.AndroidBridge && typeof window.AndroidBridge.shareInvoiceImage === 'function') {
                showToast('Launching WhatsApp...', 'fab fa-whatsapp');
                window.AndroidBridge.shareInvoiceImage(base64Data, fileName, shareMessage, cleanPhone);
                restoreButton(btn, originalBtnHtml);
                return;
            }

            // ── Priority 2: PhonePe / Google Pay Style (Web Share API - Image + Caption Attached) ──
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                showToast('Opening WhatsApp Share...', 'fab fa-whatsapp');
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
                    // If user manually pressed back / cancelled, smoothly exit
                    if (shareErr.name === 'AbortError') {
                        restoreButton(btn, originalBtnHtml);
                        return;
                    }
                    console.warn('[InvoiceShare] Web Share Level 2 File failed, falling back:', shareErr);
                }
            }

            // Try copying image to system clipboard (allows instant paste directly into WhatsApp)
            if (navigator.clipboard && window.ClipboardItem) {
                try {
                    navigator.clipboard.write([
                        new ClipboardItem({ 'image/png': blob })
                    ]);
                } catch (clipErr) {
                    // Clipboard write may require user gesture or focus, silently continue
                }
            }

            // ── Priority 3: Auto-Save Image + Direct WhatsApp Chat Fallback ──
            showToast('Saving Image & Opening WhatsApp...', 'fab fa-whatsapp');

            // Download image to phone gallery / downloads using Object URL
            try {
                const blobUrl = URL.createObjectURL(blob);
                const downloadLink = document.createElement('a');
                downloadLink.href = blobUrl;
                downloadLink.download = fileName;
                document.body.appendChild(downloadLink);
                downloadLink.click();
                setTimeout(() => {
                    document.body.removeChild(downloadLink);
                    URL.revokeObjectURL(blobUrl);
                }, 2000);
            } catch (dlErr) {
                console.warn('[InvoiceShare] Auto-download error:', dlErr);
            }

            // Launch WhatsApp
            const waUrl = (cleanPhone && cleanPhone.length >= 10)
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
