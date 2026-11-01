<!-- counselor/includes/head_css.php - Glassmorphic Counselor Design Engine -->
<link rel="icon" type="image/png" href="<?php echo (defined('APP_URL') ? rtrim(APP_URL, '/') : '/abss'); ?>/assets/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { 
        --portal-teal: #0d9488;
        --portal-teal-dark: #0f766e;
        --portal-blue: #0d9488;
        --portal-blue-dark: #0f766e;
        --portal-dark: #0f172a;
        --sidebar-width: 280px;
        --glass-bg: rgba(255, 255, 255, 0.85);
        --glass-border: rgba(255, 255, 255, 0.8);
        --glass-shadow: 0 10px 30px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.6);
        --radius-lg: 24px;
        --radius-md: 16px;
        --radius-sm: 12px;
    }
    
    * { box-sizing: border-box; }

    body { 
        background: radial-gradient(circle at 10% 20%, rgba(13, 148, 136, 0.07) 0%, transparent 40%),
                    radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.07) 0%, transparent 40%),
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
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        padding: 30px 18px;
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
        margin-bottom: 24px; 
        padding: 0 8px;
    }
    .sidebar-brand img { height: 44px; }
    .sidebar-brand span { font-weight: 800; color: var(--portal-dark); font-size: 1.25rem; letter-spacing: -0.02em; }
    .sidebar-brand small { display: block; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--portal-teal); font-weight: 800; }

    .nav-menu { 
        list-style: none; 
        padding: 0; 
        margin: 0 0 15px 0; 
        overflow-y: auto; 
        flex-grow: 1;
        scrollbar-width: thin;
        scrollbar-color: rgba(13, 148, 136, 0.2) transparent;
    }
    .nav-menu::-webkit-scrollbar { width: 4px; }
    .nav-menu::-webkit-scrollbar-thumb { background-color: rgba(13, 148, 136, 0.2); border-radius: 10px; }

    .nav-section-title {
        font-size: 0.65rem;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 10px 14px 4px;
        margin-top: 4px;
        display: block;
    }

    .nav-link { 
        display: flex; 
        align-items: center; 
        gap: 12px; 
        padding: 11px 16px; 
        border-radius: var(--radius-md); 
        color: #64748b; 
        font-weight: 700;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 3px;
        text-decoration: none;
        font-size: 0.88rem;
    }
    .nav-link:hover { 
        background: rgba(240, 253, 250, 0.9); 
        color: var(--portal-teal); 
        transform: translateX(3px); 
    }
    .nav-link.active { 
        background: linear-gradient(135deg, var(--portal-teal), var(--portal-teal-dark));
        color: #ffffff;
        box-shadow: 0 8px 18px rgba(13, 148, 136, 0.25);
    }
    .nav-link i { font-size: 1rem; width: 22px; text-align: center; }

    /* Summary & KPI Cards */
    .stat-card {
        background: #ffffff;
        border-radius: var(--radius-lg, 18px);
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 18px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
        position: relative;
        overflow: hidden;
        transition: transform 0.25s, box-shadow 0.25s;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    }
    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: var(--radius-md, 14px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    .icon-red { background: #fee2e2; color: #dc2626; }
    .icon-orange { background: #ffedd5; color: #ea580c; }
    .icon-green { background: #dcfce7; color: #166534; }
    .icon-purple { background: #f3e8ff; color: #7c3aed; }
    .icon-blue, .icon-teal { background: #e0f2fe; color: #0284c7; }

    .stat-info { flex: 1; min-width: 0; }
    .stat-info h3 {
        margin: 0;
        font-size: 1.65rem;
        font-weight: 800;
        color: var(--portal-dark, #0f172a);
        line-height: 1.2;
    }
    .stat-info p, .stat-info span {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    /* Content Area */
    .main-content {
        margin-left: var(--sidebar-width);
        flex: 1;
        min-height: 100vh;
        width: calc(100% - var(--sidebar-width));
        box-sizing: border-box;
        padding: 30px 36px 60px;
    }

    /* Mobile Headers */
    .mobile-header {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 64px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid #e2e8f0;
        padding: 0 20px;
        align-items: center;
        justify-content: space-between;
        z-index: 99;
    }
    .mobile-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 800;
        font-size: 1.1rem;
        color: var(--portal-dark);
    }
    .mobile-brand img { height: 34px; }
    .hamburger-btn {
        background: none;
        border: none;
        font-size: 1.3rem;
        color: #334155;
        cursor: pointer;
        padding: 8px;
    }
    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(4px);
        z-index: 99;
    }
    .close-sidebar-btn {
        display: none;
        position: absolute;
        top: 20px;
        right: 20px;
        background: #f1f5f9;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        font-size: 1rem;
        color: #475569;
        cursor: pointer;
        align-items: center;
        justify-content: center;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar.open {
            transform: translateX(0);
        }
        .sidebar.open + .sidebar-overlay {
            display: block;
        }
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
            padding: 84px 18px 60px !important;
        }
        .mobile-header {
            display: flex;
        }
        .close-sidebar-btn {
            display: flex;
        }
    }
        }
        .close-sidebar-btn {
            display: flex;
        }
    }

    /* Utility UI Components */
    .portal-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        margin-bottom: 24px;
    }
    .btn-portal {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 12px;
        font-size: 0.88rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .btn-teal {
        background: linear-gradient(135deg, var(--portal-teal), var(--portal-teal-dark));
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
    }
    .btn-teal:hover { opacity: 0.94; transform: translateY(-1px); }
    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }
    .btn-secondary:hover { background: #e2e8f0; color: #0f172a; }
</style>
<script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        if (sidebar) {
            sidebar.classList.toggle('open');
            if (overlay) {
                overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
            }
        }
    }
</script>

