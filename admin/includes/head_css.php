<!-- admin/includes/head_css.php - Glassmorphic Admin Design Engine -->
<link rel="icon" type="image/png" href="<?php echo (defined('APP_URL') ? rtrim(APP_URL, '/') : '/abss'); ?>/assets/logo.png">
<link rel="manifest" href="<?php echo (defined('APP_URL') ? rtrim(APP_URL, '/') : '/abss'); ?>/app/manifest.json">
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/abss/app/sw.js.php', {scope: '/abss/'});
    });
}
</script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { 
        --portal-blue: #2563eb;
        --portal-blue-dark: #1d4ed8;
        --portal-dark: #0f172a;
        --sidebar-width: 280px;
        --glass-bg: rgba(255, 255, 255, 0.82);
        --glass-border: rgba(255, 255, 255, 0.8);
        --glass-shadow: 0 10px 30px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.6);
        --radius-lg: 24px;
        --radius-md: 16px;
        --radius-sm: 12px;
    }
    
    * { box-sizing: border-box; }

    body { 
        background: radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.07) 0%, transparent 40%),
                    radial-gradient(circle at 90% 80%, rgba(124, 58, 237, 0.07) 0%, transparent 40%),
                    #f8fafc;
        color: #334155; 
        font-family: 'Outfit', sans-serif; 
        display: flex; 
        min-height: 100vh; 
        overflow-x: hidden; 
        margin: 0; 
    }
    
    /* Frosted Glass Sidebar */
    .sidebar {
        width: var(--sidebar-width);
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        padding: 35px 20px;
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100vh;
        border-right: 1px solid var(--glass-border);
        box-shadow: 4px 0 25px rgba(15, 23, 42, 0.03);
        z-index: 100;
        box-sizing: border-box;
    }

    .sidebar-brand { 
        display: flex; 
        align-items: center; 
        gap: 12px; 
        margin-bottom: 30px; 
        padding: 0 10px;
    }
    .sidebar-brand img { height: 44px; }
    .sidebar-brand span { font-weight: 800; color: var(--portal-dark); font-size: 1.35rem; letter-spacing: -0.02em; }
    .sidebar-brand small { display: block; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--portal-blue); font-weight: 800; }

    .nav-menu { 
        list-style: none; 
        padding: 0; 
        margin: 0 0 15px 0; 
        overflow-y: auto; 
        flex-grow: 1;
        scrollbar-width: thin;
        scrollbar-color: rgba(37, 99, 235, 0.2) transparent;
    }
    .nav-menu::-webkit-scrollbar { width: 4px; }
    .nav-menu::-webkit-scrollbar-thumb { background-color: rgba(37, 99, 235, 0.2); border-radius: 10px; }

    .nav-link { 
        display: flex; 
        align-items: center; 
        gap: 14px; 
        padding: 12px 18px; 
        border-radius: var(--radius-md); 
        color: #64748b; 
        font-weight: 700;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 4px;
        text-decoration: none;
        font-size: 0.92rem;
    }
    .nav-link:hover { background: rgba(239, 246, 255, 0.8); color: var(--portal-blue); transform: translateX(4px); }
    .nav-link.active { 
        background: linear-gradient(135deg, var(--portal-blue), var(--portal-blue-dark));
        color: #ffffff;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
    }
    .nav-link i { font-size: 1.1rem; width: 24px; text-align: center; }

    .logout-link { 
        margin-top: auto; 
        color: #ef4444 !important;
        background: rgba(254, 242, 242, 0.8);
        border: 1px solid #fee2e2;
    }
    .logout-link:hover { background: #fee2e2; color: #dc2626 !important; transform: translateY(-2px); }

    /* Main Content Area */
    .main-content {
        margin-left: var(--sidebar-width);
        flex: 1;
        padding: 40px;
        box-sizing: border-box;
        max-width: 1400px;
    }

    h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--portal-dark); margin-top: 0; }
    p { color: #64748b; font-weight: 500; }

    /* Frosted Glass Card Container */
    .portal-card, .card, .glass-card { 
        background: var(--glass-bg); 
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        padding: 30px; 
        border-radius: var(--radius-lg); 
        box-shadow: var(--glass-shadow); 
        border: 1px solid var(--glass-border); 
        margin-bottom: 25px;
        min-width: 0;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    /* Stats Counter Cards */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { 
        background: var(--glass-bg); 
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        padding: 24px; 
        border-radius: var(--radius-lg); 
        border: 1px solid var(--glass-border); 
        display: flex; 
        align-items: center; 
        gap: 18px; 
        box-shadow: var(--glass-shadow); 
        transition: transform 0.25s, box-shadow 0.25s; 
    }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(37, 99, 235, 0.08); }
    .stat-icon { width: 56px; height: 56px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
    .icon-blue { background: #dbeafe; color: var(--portal-blue); }
    .icon-orange { background: #ffedd5; color: #ea580c; }
    .icon-green { background: #dcfce7; color: #166534; }
    .icon-purple { background: #f3e8ff; color: #7c3aed; }
    .stat-info h3 { margin: 0; font-size: 1.65rem; font-weight: 800; color: var(--portal-dark); }
    .stat-info p, .stat-info span { margin: 2px 0 0; color: #64748b; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }

    /* Frosted Form Inputs */
    .portal-input-group { margin-bottom: 20px; }
    .portal-input-group label { display: block; color: var(--portal-dark); font-weight: 800; margin-bottom: 8px; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.05em; }
    .portal-input-group input, 
    .portal-input-group select, 
    .portal-input-group textarea, 
    .form-control { 
        width: 100%; 
        padding: 14px 18px; 
        border-radius: var(--radius-md); 
        border: 2px solid #cbd5e1; 
        font-family: inherit; 
        font-size: 0.95rem; 
        font-weight: 600; 
        color: #1e293b; 
        transition: all 0.25s ease; 
        background: rgba(248, 250, 252, 0.8); 
        outline: none;
        box-sizing: border-box;
    }
    .portal-input-group input:focus, 
    .portal-input-group select:focus, 
    .portal-input-group textarea:focus, 
    .form-control:focus { 
        border-color: var(--portal-blue); 
        background: #ffffff; 
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); 
    }

    /* Buttons */
    .btn-portal { 
        background: linear-gradient(135deg, var(--portal-blue), var(--portal-blue-dark)); 
        color: #ffffff; 
        padding: 14px 28px; 
        border-radius: var(--radius-md); 
        border: none; 
        font-weight: 800; 
        font-size: 0.95rem;
        cursor: pointer; 
        transition: all 0.25s ease; 
        font-family: 'Outfit';
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.2);
    }
    .btn-portal:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(37, 99, 235, 0.3); }

    /* Tables & Responsive Wrapper */
    .portal-table-container, .table-responsive {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: 15px;
        min-width: 0;
    }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { padding: 14px 18px; color: var(--portal-dark); font-weight: 800; font-size: 0.8rem; text-transform: uppercase; background: rgba(248, 250, 252, 0.8); border-bottom: 2px solid #e2e8f0; }
    td { padding: 16px 18px; border-bottom: 1px solid #f1f5f9; color: #475569; font-weight: 600; font-size: 0.92rem; }

    /* Mobile Headers & Overlay Components */
    .mobile-header {
        display: none;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        padding: 12px 20px;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
        border-bottom: 1px solid rgba(255, 255, 255, 0.8);
        align-items: center;
        justify-content: space-between;
        width: 100%;
        box-sizing: border-box;
    }
    .mobile-brand { display: flex; align-items: center; gap: 10px; }
    .mobile-brand img { height: 36px; }
    .mobile-brand span { font-weight: 800; color: var(--portal-dark); font-size: 1.1rem; }

    .hamburger-btn {
        background: #f1f5f9;
        border: none;
        color: var(--portal-dark);
        width: 40px;
        height: 40px;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(4px);
        z-index: 1900;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .sidebar-overlay.active { display: block; opacity: 1; }
    
    .close-sidebar-btn {
        display: none;
        background: #f1f5f9;
        border: none;
        color: #475569;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        position: absolute;
        top: 25px;
        right: 20px;
    }

    /* Responsive Media Queries */
    @media (max-width: 1024px) {
        body { flex-direction: column; }
        .mobile-header { display: flex; }
        
        .sidebar {
            position: fixed;
            left: -280px; top: 0; bottom: 0;
            width: var(--sidebar-width);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2000;
            padding: 35px 20px !important;
        }
        .sidebar.open { transform: translateX(280px); }
        .close-sidebar-btn { display: flex; }
        
        .main-content {
            margin-left: 0;
            padding: 20px 16px;
            width: 100%;
        }
        
        .form-cols, .list-cols, .stats-grid, .analytics-split, .dashboard-row, .results-layout-grid {
            grid-template-columns: 1fr !important;
            gap: 20px !important;
        }
    }

    @media (max-width: 640px) {
        .main-content {
            padding: 16px 12px !important;
        }
        .portal-card, .card, .glass-card {
            padding: 18px 14px !important;
            border-radius: var(--radius-md) !important;
        }
        .portal-input-group input, 
        .portal-input-group select, 
        .portal-input-group textarea, 
        .form-control {
            font-size: 16px !important; /* Prevents auto-zoom on iOS Safari */
            padding: 12px 14px !important;
        }
        .btn-portal, .btn {
            width: 100% !important;
            padding: 12px 20px !important;
        }
        .stat-card {
            padding: 16px !important;
            gap: 12px !important;
        }
        .stat-info h3 {
            font-size: 1.3rem !important;
        }
    }
</style>
