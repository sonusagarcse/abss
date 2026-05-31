<!-- admin/includes/head_css.php -->
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="manifest" href="/abss/app/manifest.json">
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/abss/app/sw.js.php', {scope: '/abss/'});
    });
}
</script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    :root { 
        --portal-blue: #0d47a1;
        --portal-dark: #002171;
        --sidebar-width: 280px;
    }
    
    body { background: #f4f7fa; color: #333; font-family: 'Outfit', sans-serif; display: flex; min-height: 100vh; overflow-x: hidden; margin: 0; }
    
    /* Sidebar Redesign */
    .sidebar {
        width: var(--sidebar-width);
        background: #fff;
        padding: 40px 20px;
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100vh;
        box-shadow: 10px 0 30px rgba(0,0,0,0.02);
        z-index: 100;
        box-sizing: border-box;
    }

    .sidebar-brand { 
        display: flex; 
        align-items: center; 
        gap: 15px; 
        margin-bottom: 50px; 
        padding: 0 15px;
    }
    .sidebar-brand img { height: 45px; }
    .sidebar-brand span { font-weight: 800; color: var(--portal-blue); font-size: 1.4rem; letter-spacing: -0.02em; }

    .nav-menu { 
        list-style: none; 
        padding: 0; 
        margin: 0 0 20px 0; 
        overflow-y: auto; 
        flex-grow: 1;
        scrollbar-width: thin;
        scrollbar-color: rgba(13, 71, 161, 0.2) transparent;
    }
    .nav-menu::-webkit-scrollbar {
        width: 6px;
    }
    .nav-menu::-webkit-scrollbar-track {
        background: transparent;
    }
    .nav-menu::-webkit-scrollbar-thumb {
        background-color: rgba(13, 71, 161, 0.2);
        border-radius: 10px;
    }
    .nav-link { 
        display: flex; 
        align-items: center; 
        gap: 15px; 
        padding: 14px 20px; 
        border-radius: 16px; 
        color: #5c6bc0; 
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 4px;
        text-decoration: none;
        font-size: 0.95rem;
    }
    .nav-link:hover { background: #f8faff; color: var(--portal-blue); transform: translateX(5px); }
    .nav-link.active { 
        background: linear-gradient(135deg, var(--portal-blue) 0%, var(--portal-dark) 100%);
        color: #fff;
        box-shadow: 0 10px 20px rgba(13, 71, 161, 0.2);
    }
    .nav-link i { font-size: 1.1rem; width: 25px; text-align: center; }

    .logout-link { 
        margin-top: auto; 
        color: #d32f2f !important;
        border: 1px solid #feeef2;
    }
    .logout-link:hover { background: #feeef2; color: #d32f2f !important; }

    /* Main Content Area */
    .main-content {
        margin-left: var(--sidebar-width);
        flex: 1;
        padding: 50px;
        box-sizing: border-box;
    }

    /* Common Typography & Card UI */
    h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--portal-blue); }
    p { font-weight: 500; color: #5c6bc0; }

    .portal-card { background: #fff; padding: 40px; border-radius: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
    
    /* Deep Focus Forms */
    .portal-input-group { margin-bottom: 25px; }
    .portal-input-group label { display: block; color: var(--portal-blue); font-weight: 800; margin-bottom: 12px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }
    .portal-input-group input, .portal-input-group select, .portal-input-group textarea { 
        width: 100%; padding: 16px 22px; border-radius: 16px; border: 3px solid #f0f4f8; 
        font-family: inherit; font-size: 1rem; font-weight: 600; color: var(--portal-dark); 
        transition: 0.3s; background: #f8faff; box-sizing: border-box;
    }
    .portal-input-group input:focus { border-color: var(--portal-blue); background: #fff; outline: none; box-shadow: 0 10px 20px rgba(13, 71, 161, 0.05); }

    /* Buttons */
    .btn-portal { 
        background: var(--portal-blue); color: #fff; padding: 16px 30px; border-radius: 16px; 
        border: none; font-weight: 800; cursor: pointer; transition: 0.3s; font-family: 'Outfit';
    }
    .btn-portal:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(13, 71, 161, 0.2); }

    /* Mobile Header & Overlay Components */
    .mobile-header {
        display: none;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        padding: 15px 25px;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 30px rgba(13, 71, 161, 0.03);
        border-bottom: 1px solid rgba(13, 71, 161, 0.05);
        align-items: center;
        justify-content: space-between;
        width: 100%;
        box-sizing: border-box;
    }
    
    .mobile-brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .mobile-brand img { height: 35px; }
    .mobile-brand span { font-weight: 800; color: var(--portal-blue); font-size: 1.15rem; letter-spacing: -0.01em; }
    
    .hamburger-btn {
        background: transparent;
        border: none;
        color: var(--portal-blue);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
    }
    .hamburger-btn:hover { color: var(--portal-dark); }

    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 33, 113, 0.2);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 1900;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .sidebar-overlay.active {
        display: block;
        opacity: 1;
    }
    
    /* Close button on Mobile Sidebar */
    .close-sidebar-btn {
        display: none;
        background: #f8faff;
        border: 2px solid #eef2ff;
        color: #5c6bc0;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        position: absolute;
        top: 35px;
        right: 20px;
        z-index: 10;
        transition: 0.3s;
    }
    .close-sidebar-btn:hover { background: #feeef2; color: #d32f2f; border-color: #feeef2; }

    /* Responsive Utilities */
    @media (max-width: 1024px) {
        body { flex-direction: column; }
        .mobile-header { display: flex; }
        
        .sidebar {
            position: fixed;
            left: -280px;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2000;
            box-shadow: 15px 0 45px rgba(13, 71, 161, 0.15);
            padding: 50px 25px 30px !important;
        }
        .sidebar.open {
            transform: translateX(280px);
        }
        .close-sidebar-btn {
            display: block;
        }
        
        .main-content {
            margin-left: 0;
            padding: 30px 20px;
            width: 100%;
        }
        
        .form-cols, .list-cols, .stats-grid, .analytics-split {
            grid-template-columns: 1fr !important;
            gap: 25px !important;
        }
        
        /* Mobile Modal and Action Bar Overrides */
        .modal {
            padding: 15px;
            box-sizing: border-box;
        }
        .modal-content {
            padding: 30px !important;
            border-radius: 24px !important;
            max-width: 95% !important;
            margin: auto;
            box-sizing: border-box;
        }
        .modal-content h2, .modal-content h3 {
            font-size: 1.5rem !important;
            margin-bottom: 20px !important;
        }
        .action-bar {
            flex-direction: column;
            align-items: flex-start !important;
            justify-content: flex-start !important;
            gap: 15px;
        }
        .action-bar button, .action-bar .btn-portal {
            width: 100%;
            text-align: center;
        }
    }

    /* Table Responsive Container */
    .portal-table-container {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: 20px;
    }
    
    /* Responsive Form Row */
    .portal-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    @media (max-width: 768px) {
        .portal-form-row {
            grid-template-columns: 1fr;
            gap: 15px;
        }
    }
    
    /* Responsive Button Row */
    .portal-btn-row {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }
    @media (max-width: 576px) {
        .portal-btn-row {
            flex-direction: column;
            gap: 10px;
        }
    }
</style>
