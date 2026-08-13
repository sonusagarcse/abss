<!-- parent/includes/head_css.php -->
<link rel="icon" type="image/png" href="../assets/logo.png">
<link rel="manifest" href="/abss/app/manifest.json">
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
        --portal-purple: #7c3aed;
        --portal-purple-dark: #6d28d9;
        --portal-indigo: #1e1b4b;
        --portal-accent: #f5f3ff;
        --sidebar-width: 280px;
        --bg-main: #f8fafc;
        --card-border: #ede9fe;
        --radius-lg: 24px;
        --radius-md: 16px;
        --radius-sm: 12px;
    }
    
    * { box-sizing: border-box; }

    body { 
        background: var(--bg-main); 
        color: #334155; 
        font-family: 'Outfit', sans-serif; 
        display: flex; 
        min-height: 100vh; 
        overflow-x: hidden; 
        margin: 0; 
    }
    
    /* Desktop Sidebar */
    .sidebar {
        width: var(--sidebar-width);
        background: #ffffff;
        padding: 35px 20px;
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100vh;
        box-shadow: 4px 0 25px rgba(124, 58, 237, 0.05);
        z-index: 100;
        box-sizing: border-box;
        border-right: 1px solid #f1f5f9;
    }

    .sidebar-brand { 
        display: flex; 
        align-items: center; 
        gap: 12px; 
        margin-bottom: 30px; 
        padding: 0 10px;
    }
    .sidebar-brand img { height: 44px; }
    .sidebar-brand span { font-weight: 800; color: var(--portal-indigo); font-size: 1.35rem; letter-spacing: -0.02em; }
    .sidebar-brand small { display: block; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--portal-purple); font-weight: 800; }

    .nav-menu { list-style: none; padding: 0; margin: 0; }
    .nav-link { 
        display: flex; 
        align-items: center; 
        gap: 14px; 
        padding: 13px 18px; 
        border-radius: var(--radius-md); 
        color: #64748b; 
        font-weight: 700;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 6px;
        text-decoration: none;
        font-size: 0.95rem;
    }
    .nav-link:hover { background: var(--portal-accent); color: var(--portal-purple); transform: translateX(4px); }
    .nav-link.active { 
        background: linear-gradient(135deg, var(--portal-purple), var(--portal-purple-dark));
        color: #ffffff;
        box-shadow: 0 8px 20px rgba(124, 58, 237, 0.25);
    }
    .nav-link i { font-size: 1.15rem; width: 24px; text-align: center; }

    .logout-link { 
        margin-top: auto; 
        color: #ef4444 !important;
        background: #fef2f2;
        border: 1px solid #fee2e2;
    }
    .logout-link:hover { background: #fee2e2; color: #dc2626 !important; transform: translateY(-2px); }

    /* Main Content */
    .main-content {
        margin-left: var(--sidebar-width);
        flex: 1;
        padding: 40px;
        box-sizing: border-box;
        max-width: 1400px;
    }

    h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--portal-indigo); margin-top: 0; }
    p { color: #64748b; font-weight: 500; }

    /* Hero Banner */
    .hero-welcome-card {
        background: linear-gradient(135deg, #1e1b4b 0%, #4338ca 50%, #7c3aed 100%);
        color: #ffffff;
        padding: 32px 35px;
        border-radius: var(--radius-lg);
        margin-bottom: 30px;
        box-shadow: 0 15px 35px rgba(124, 58, 237, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        position: relative;
        overflow: hidden;
    }
    .hero-welcome-card h1 { color: #ffffff; font-size: 1.8rem; margin-bottom: 6px; }
    .hero-welcome-card p { color: rgba(255,255,255,0.85); font-size: 0.95rem; margin: 0; }

    /* Stats Grid */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { 
        background: #ffffff; 
        padding: 24px; 
        border-radius: var(--radius-lg); 
        border: 1px solid var(--card-border); 
        display: flex; 
        align-items: center; 
        gap: 18px; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.02); 
        transition: transform 0.25s, box-shadow 0.25s; 
    }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(124, 58, 237, 0.08); }
    .stat-icon { width: 54px; height: 54px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
    .stat-info { display: flex; flex-direction: column; }
    .stat-info h3 { margin: 0; font-size: 1.6rem; font-weight: 800; color: var(--portal-indigo); }
    .stat-info span { font-size: 0.78rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 2px; }

    /* Shortcut Grid */
    .quick-shortcut-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 30px;
    }
    .shortcut-card {
        background: #ffffff;
        border-radius: var(--radius-md);
        padding: 18px 20px;
        border: 1px solid var(--card-border);
        text-decoration: none;
        color: var(--portal-indigo);
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.01);
        transition: all 0.25s ease;
    }
    .shortcut-card:hover {
        background: var(--portal-purple);
        color: #ffffff;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(124, 58, 237, 0.2);
    }
    .shortcut-card i {
        font-size: 1.25rem;
        color: var(--portal-purple);
        transition: color 0.25s;
    }
    .shortcut-card:hover i { color: #ffffff; }

    /* Portal Card UI & Responsiveness */
    .portal-card { 
        background: #ffffff; 
        padding: 30px; 
        border-radius: var(--radius-lg); 
        box-shadow: 0 4px 20px rgba(0,0,0,0.02); 
        border: 1px solid var(--card-border); 
        margin-bottom: 25px;
        min-width: 0;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    /* Form Controls Design */
    .portal-input-group {
        margin-bottom: 20px;
    }
    .portal-input-group label {
        display: block;
        font-size: 0.82rem;
        font-weight: 800;
        color: var(--portal-indigo);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .portal-input-group input, 
    .portal-input-group select, 
    .portal-input-group textarea,
    .form-control {
        width: 100%;
        padding: 14px 18px;
        border-radius: var(--radius-md);
        border: 2px solid #cbd5e1;
        background: #f8fafc;
        font-family: inherit;
        font-size: 0.95rem;
        font-weight: 600;
        color: #1e293b;
        outline: none;
        transition: all 0.25s ease;
        box-sizing: border-box;
    }
    .portal-input-group input:focus, 
    .portal-input-group select:focus, 
    .portal-input-group textarea:focus,
    .form-control:focus {
        border-color: var(--portal-purple);
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
    }
    .btn-portal {
        background: linear-gradient(135deg, var(--portal-purple), var(--portal-purple-dark));
        color: #ffffff;
        border: none;
        padding: 14px 28px;
        border-radius: var(--radius-md);
        font-weight: 800;
        font-size: 1rem;
        font-family: inherit;
        cursor: pointer;
        transition: all 0.25s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 8px 20px rgba(124, 58, 237, 0.2);
    }
    .btn-portal:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(124, 58, 237, 0.3);
    }
    .w-100 { width: 100% !important; }

    /* Tables & Scroll Wrapper */
    .table-responsive {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: 10px;
        min-width: 0;
    }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { padding: 14px 18px; color: var(--portal-indigo); font-weight: 800; font-size: 0.8rem; text-transform: uppercase; background: #f8fafc; border-bottom: 2px solid #ede9fe; }
    td { padding: 16px 18px; border-bottom: 1px solid #f1f5f9; color: #475569; font-weight: 600; font-size: 0.92rem; }

    /* Badges */
    .badge { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; display: inline-block; }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-purple { background: #ede9fe; color: #5b21b6; }
    .badge-warning { background: #fef3c7; color: #92400e; }

    /* Mobile Headers & Floating Bottom Navigation */
    .mobile-header {
        display: none;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        padding: 12px 20px;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        border-bottom: 1px solid #f1f5f9;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }
    .mobile-brand { display: flex; align-items: center; gap: 10px; }
    .mobile-brand img { height: 36px; }
    .mobile-brand span { font-weight: 800; color: var(--portal-indigo); font-size: 1.1rem; }

    .hamburger-btn {
        background: #f1f5f9;
        border: none;
        color: var(--portal-indigo);
        width: 40px;
        height: 40px;
        border-radius: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

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
        box-shadow: 0 -10px 30px rgba(0,0,0,0.05);
        z-index: 1500;
        padding: 6px 12px 10px;
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
        padding: 6px 12px;
        border-radius: 12px;
        transition: 0.2s;
    }
    .mobile-bottom-nav a.active {
        color: var(--portal-purple);
        background: var(--portal-accent);
    }
    .mobile-bottom-nav a i { font-size: 1.15rem; }

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
        body { flex-direction: column; padding-bottom: 70px; }
        .mobile-header { display: flex; }
        .mobile-bottom-nav { display: block; }
        
        .sidebar {
            position: fixed;
            left: -280px; top: 0; bottom: 0;
            width: var(--sidebar-width);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2000;
            padding: 40px 20px !important;
        }
        .sidebar.open { transform: translateX(280px); }
        .close-sidebar-btn { display: flex; }
        
        .main-content {
            margin-left: 0;
            padding: 20px 16px;
            width: 100%;
        }
    }

    @media (max-width: 600px) {
        .hero-welcome-card { padding: 24px 20px; border-radius: 20px; }
        .hero-welcome-card h1 { font-size: 1.5rem; }
        .quick-shortcut-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
        .shortcut-card { padding: 14px 16px; font-size: 0.88rem; }
        .portal-card { padding: 20px; border-radius: 20px; }
        .stats-grid { grid-template-columns: 1fr; gap: 14px; }
        .stat-card { padding: 18px 20px; }
    }
</style>
