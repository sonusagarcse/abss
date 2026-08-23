<!-- teacher/includes/head_css.php - Glassmorphic Faculty Design System Engine -->
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="manifest" href="<?php echo (defined('APP_URL') ? rtrim(APP_URL, '/') : '/abss'); ?>/app/manifest.json">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { 
        --teacher-purple: #7c3aed;
        --teacher-purple-light: #8b5cf6;
        --teacher-dark: #4c1d95;
        --teacher-indigo: #312e81;
        --sidebar-width: 275px;
        --glass-bg: rgba(255, 255, 255, 0.88);
        --glass-border: rgba(255, 255, 255, 0.85);
        --glass-shadow: 0 10px 30px rgba(124, 58, 237, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.7);
        --radius-lg: 24px;
        --radius-md: 16px;
        --radius-sm: 12px;
    }
    
    * { box-sizing: border-box; margin: 0; padding: 0; }
    
    body { 
        background: radial-gradient(circle at 10% 10%, rgba(124, 58, 237, 0.08) 0%, transparent 40%),
                    radial-gradient(circle at 90% 85%, rgba(99, 102, 241, 0.08) 0%, transparent 40%),
                    #f5f3ff;
        color: #1e1b4b; 
        font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif; 
        display: flex; 
        min-height: 100vh; 
        overflow-x: hidden; 
    }

    a { text-decoration: none; color: inherit; }
    
    /* Mobile Top Header */
    .mobile-header {
        display: none;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        padding: 12px 20px;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 20px rgba(124, 58, 237, 0.06);
        border-bottom: 1px solid rgba(237, 233, 254, 0.8);
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }
    .mobile-brand { display: flex; align-items: center; gap: 10px; }
    .mobile-brand img { height: 38px; }
    .mobile-brand span { font-weight: 800; color: var(--teacher-dark); font-size: 1.15rem; letter-spacing: -0.01em; }

    .hamburger-btn {
        background: #ede9fe;
        border: none;
        color: var(--teacher-purple);
        width: 42px;
        height: 42px;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        transition: 0.2s;
    }
    .hamburger-btn:hover { background: var(--teacher-purple); color: #ffffff; }

    .sidebar-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 1400;
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .sidebar-overlay.active { display: block !important; opacity: 1; }

    /* Frosted Glass Sidebar Navigation */
    .sidebar {
        width: var(--sidebar-width);
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        padding: 32px 20px;
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100vh;
        box-shadow: 6px 0 30px rgba(124, 58, 237, 0.05);
        z-index: 1500;
        border-right: 1px solid var(--glass-border);
        transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        box-sizing: border-box;
    }

    .close-sidebar-btn {
        display: none;
        position: absolute;
        top: 20px;
        right: 18px;
        background: #ede9fe;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        color: var(--teacher-purple);
        font-size: 1rem;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
    }
    .close-sidebar-btn:hover { background: var(--teacher-purple); color: #ffffff; }

    .sidebar-brand { 
        display: flex; 
        align-items: center; 
        gap: 12px; 
        margin-bottom: 35px; 
        padding: 0 8px;
    }
    .sidebar-brand img { height: 46px; }
    .sidebar-brand span { font-weight: 900; color: var(--teacher-dark); font-size: 1.35rem; letter-spacing: -0.02em; }
    .sidebar-brand small { display: block; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--teacher-purple); font-weight: 800; }

    .nav-menu { 
        list-style: none; 
        overflow-y: auto; 
        flex-grow: 1;
        scrollbar-width: thin;
        scrollbar-color: rgba(124, 58, 237, 0.2) transparent;
        padding: 0;
        margin: 0 0 15px 0;
    }
    .nav-menu::-webkit-scrollbar { width: 4px; }
    .nav-menu::-webkit-scrollbar-thumb { background-color: rgba(124, 58, 237, 0.2); border-radius: 10px; }

    .nav-item { margin-bottom: 6px; }
    .nav-link { 
        display: flex; 
        align-items: center; 
        gap: 14px; 
        padding: 12px 18px; 
        color: #64748b; 
        text-decoration: none; 
        font-weight: 700; 
        border-radius: var(--radius-md); 
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        font-size: 0.93rem;
    }
    .nav-link:hover { 
        background: rgba(237, 233, 254, 0.7); 
        color: var(--teacher-purple); 
        transform: translateX(4px);
    }
    .nav-link.active { 
        background: linear-gradient(135deg, var(--teacher-purple), var(--teacher-dark)); 
        color: #ffffff; 
        box-shadow: 0 8px 20px rgba(124, 58, 237, 0.25);
    }
    .nav-link i { font-size: 1.1rem; width: 24px; text-align: center; }

    .logout-link { 
        color: #ef4444 !important; 
        margin-top: auto; 
        border: 1px solid #fee2e2;
        background: rgba(254, 242, 242, 0.8);
        border-radius: var(--radius-md);
    }
    .logout-link:hover { background: #fee2e2; color: #dc2626 !important; transform: translateY(-2px); }

    /* Main Container */
    .main-content { 
        margin-left: var(--sidebar-width); 
        flex-grow: 1; 
        padding: 35px 40px; 
        max-width: calc(100vw - var(--sidebar-width)); 
        box-sizing: border-box;
    }

    h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--teacher-dark); margin-top: 0; }
    p { color: #64748b; font-weight: 500; }

    /* Cards */
    .page-card, .card, .portal-card, .card-panel { 
        background: var(--glass-bg); 
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        padding: 28px; 
        border-radius: var(--radius-lg); 
        box-shadow: var(--glass-shadow); 
        border: 1px solid var(--glass-border); 
        margin-bottom: 25px;
        min-width: 0;
        word-wrap: break-word;
        overflow-wrap: break-word;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    /* KPI Stats Counter Grid */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { 
        background: var(--glass-bg); 
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        padding: 22px; 
        border-radius: var(--radius-lg); 
        border: 1px solid var(--glass-border); 
        display: flex; 
        align-items: center; 
        gap: 16px; 
        box-shadow: var(--glass-shadow); 
        transition: transform 0.25s, box-shadow 0.25s; 
    }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(124, 58, 237, 0.1); }
    .stat-icon { width: 52px; height: 52px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
    .icon-purple { background: #ede9fe; color: var(--teacher-purple); }
    .icon-green { background: #dcfce7; color: #166534; }
    .icon-blue { background: #dbeafe; color: #1e40af; }
    .icon-amber { background: #fef3c7; color: #92400e; }
    .stat-val { font-size: 1.6rem; font-weight: 900; color: #1e1b4b; margin-top: 2px; }
    .stat-lbl { font-size: 0.78rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }

    /* Inputs & Selects */
    .form-group, .portal-input-group { margin-bottom: 20px; }
    .form-group label, .portal-input-group label { 
        display: block; 
        color: var(--teacher-dark); 
        font-weight: 800; 
        margin-bottom: 8px; 
        font-size: 0.82rem; 
        text-transform: uppercase; 
        letter-spacing: 0.05em; 
    }
    .form-control, .portal-input-group input, .portal-input-group select, .portal-input-group textarea { 
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
    .form-control:focus, .portal-input-group input:focus, .portal-input-group select:focus, .portal-input-group textarea:focus { 
        border-color: var(--teacher-purple); 
        background: #ffffff; 
        box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.12); 
    }

    /* Buttons */
    .btn-purple, .btn-portal { 
        background: linear-gradient(135deg, var(--teacher-purple), var(--teacher-dark)); 
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
        box-shadow: 0 8px 20px rgba(124, 58, 237, 0.2);
    }
    .btn-purple:hover, .btn-portal:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(124, 58, 237, 0.3); }

    /* Tables */
    .table-responsive, .portal-table-container {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: 15px;
        min-width: 0;
    }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { padding: 14px 18px; color: var(--teacher-dark); font-weight: 800; font-size: 0.8rem; text-transform: uppercase; background: rgba(245, 243, 255, 0.8); border-bottom: 2px solid #ede9fe; }
    td { padding: 16px 18px; border-bottom: 1px solid #f1f5f9; color: #475569; font-weight: 600; font-size: 0.92rem; }

    /* Badges */
    .badge { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; display: inline-block; }
    .badge-success, .badge-approved { background: #dcfce7; color: #166534; }
    .badge-danger, .badge-rejected { background: #fee2e2; color: #991b1b; }
    .badge-purple { background: #ede9fe; color: #5b21b6; }
    /* Mobile Bottom Navigation Bar */
    .mobile-bottom-nav {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-top: 1px solid #ede9fe;
        box-shadow: 0 -10px 30px rgba(124, 58, 237, 0.08);
        z-index: 1500;
        padding: 6px 10px 10px;
    }
    .mobile-bottom-nav ul {
        display: flex;
        justify-content: space-around;
        align-items: center;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .mobile-bottom-nav a {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 3px;
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 6px 10px;
        border-radius: 12px;
        transition: 0.2s;
    }
    .mobile-bottom-nav a.active {
        color: var(--teacher-purple);
        background: rgba(124, 58, 237, 0.1);
    }
    .mobile-bottom-nav a i { font-size: 1.15rem; }

    @media (max-width: 992px) {
        body { flex-direction: column; padding-bottom: 70px; }
        .mobile-header { display: flex; }
        .mobile-bottom-nav { display: block; }
        .sidebar { transform: translateX(-100%); width: 280px; }
        .sidebar.open { transform: translateX(0); }
        .close-sidebar-btn { display: flex; }
        .main-content { margin-left: 0; max-width: 100vw; padding: 20px 15px; }
        .form-control, input, select, textarea {
            font-size: 16px !important; /* Prevents auto-zoom on iOS Safari */
            padding: 12px 14px !important;
        }
        .page-card, .card, .portal-card, .card-panel {
            padding: 18px 14px !important;
            border-radius: 16px !important;
        }
    }
</style>
