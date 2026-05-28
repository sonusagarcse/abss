<!-- parent/includes/head_css.php -->
<link rel="icon" type="image/png" href="../assets/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    :root { 
        --portal-purple: #3f51b5;
        --portal-indigo: #1a237e;
        --portal-accent: #eef2ff;
        --sidebar-width: 280px;
    }
    
    body { background: #f6f8fb; color: #2c3e50; font-family: 'Outfit', sans-serif; display: flex; min-height: 100vh; overflow-x: hidden; margin: 0; }
    
    /* Premium Indigo Sidebar */
    .sidebar {
        width: var(--sidebar-width);
        background: #fff;
        padding: 40px 20px;
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100vh;
        box-shadow: 10px 0 40px rgba(26, 35, 126, 0.02);
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
    .sidebar-brand span { font-weight: 800; color: var(--portal-indigo); font-size: 1.4rem; letter-spacing: -0.02em; }
    .sidebar-brand small { display: block; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--portal-purple); font-weight: 700; }

    .nav-menu { list-style: none; padding: 0; margin: 0; }
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
    .nav-link:hover { background: #f8faff; color: var(--portal-purple); transform: translateX(5px); }
    .nav-link.active { 
        background: linear-gradient(135deg, var(--portal-purple) 0%, var(--portal-indigo) 100%);
        color: #fff;
        box-shadow: 0 10px 20px rgba(63, 81, 181, 0.25);
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
    h1, h2, h3, h4 { font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--portal-indigo); }
    p { font-weight: 500; color: #5c6bc0; }

    .portal-card { background: #fff; padding: 40px; border-radius: 35px; box-shadow: 0 15px 40px rgba(0,0,0,0.01); border: 1px solid #f0f4f8; }
    
    /* Interactive Cards */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 30px; margin-bottom: 40px; }
    .stat-card { background: #fff; padding: 30px; border-radius: 30px; border: 1px solid #f0f4f8; display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.01); transition: 0.3s; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(26, 35, 126, 0.04); }
    .stat-icon { width: 60px; height: 60px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    .stat-info { display: flex; flex-direction: column; }
    .stat-info h3 { margin: 0; font-size: 1.8rem; font-weight: 800; color: var(--portal-indigo); }
    .stat-info span { font-size: 0.85rem; color: #9aa5ce; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }

    /* Tables */
    .portal-table-container { overflow-x: auto; margin-top: 20px; }
    table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
    th { text-align: left; padding: 15px 25px; color: var(--portal-indigo); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; }
    td { padding: 20px 25px; background: #fff; border-top: 1px solid #f0f4f8; border-bottom: 1px solid #f0f4f8; color: #5c6bc0; font-weight: 600; }
    td:first-child { border-left: 1px solid #f0f4f8; border-radius: 20px 0 0 20px; }
    td:last-child { border-right: 1px solid #f0f4f8; border-radius: 0 20px 20px 0; }

    /* Badges & Tags */
    .badge { padding: 6px 15px; border-radius: 100px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; display: inline-block; }
    .badge-success { background: #f0fdf4; color: #166534; }
    .badge-danger { background: #feeef2; color: #d32f2f; }
    .badge-primary { background: #eef2ff; color: var(--portal-purple); }
    .badge-warning { background: #fffbeb; color: #b45309; }

    /* Dropdowns / Controls */
    .child-selector { background: #fff; padding: 20px 30px; border-radius: 24px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #f0f4f8; margin-bottom: 40px; box-shadow: 0 5px 15px rgba(0,0,0,0.01); }
    .select-premium { padding: 12px 20px; border-radius: 12px; border: 2px solid #eef2ff; font-weight: 700; color: var(--portal-indigo); font-family: inherit; font-size: 0.95rem; background: #f8faff; outline: none; cursor: pointer; transition: 0.3s; }
    .select-premium:focus { border-color: var(--portal-purple); background: #fff; }

    /* Premium Form Styling */
    .portal-input-group { margin-bottom: 25px; text-align: left; }
    .portal-input-group label { display: block; color: var(--portal-indigo); font-weight: 800; margin-bottom: 12px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }
    .portal-input-group input, .portal-input-group select, .portal-input-group textarea { 
        width: 100%; padding: 16px 22px; border-radius: 16px; border: 3px solid #f0f4f8; 
        font-family: inherit; font-size: 1rem; font-weight: 600; color: var(--portal-indigo); 
        transition: 0.3s; background: #f8faff; box-sizing: border-box;
    }
    .portal-input-group input:focus, .portal-input-group select:focus, .portal-input-group textarea:focus { border-color: var(--portal-purple); background: #fff; outline: none; box-shadow: 0 10px 20px rgba(63, 81, 181, 0.05); }

    /* Buttons */
    .btn-portal { 
        background: linear-gradient(135deg, var(--portal-purple) 0%, var(--portal-indigo) 100%); color: #fff; padding: 16px 30px; border-radius: 16px; 
        border: none; font-weight: 800; cursor: pointer; transition: 0.3s; font-family: 'Outfit'; font-size: 1rem; box-shadow: 0 5px 15px rgba(63, 81, 181, 0.15); width: 100%; box-sizing: border-box;
    }
    .btn-portal:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(63, 81, 181, 0.3); }
    .w-100 { width: 100% !important; }

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
        box-shadow: 0 4px 30px rgba(26, 35, 126, 0.03);
        border-bottom: 1px solid rgba(26, 35, 126, 0.05);
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
    .mobile-brand span { font-weight: 800; color: var(--portal-indigo); font-size: 1.15rem; letter-spacing: -0.01em; }
    
    .hamburger-btn {
        background: transparent;
        border: none;
        color: var(--portal-indigo);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
    }
    .hamburger-btn:hover { color: var(--portal-purple); }

    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 21, 113, 0.2);
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
            box-shadow: 15px 0 45px rgba(26, 35, 126, 0.15);
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
    }

    @media (max-width: 900px) {
        .dashboard-grid {
            grid-template-columns: 1fr !important;
            gap: 30px;
        }
        .form-grid {
            grid-template-columns: 1fr !important;
            gap: 30px;
        }
        .performance-overview {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 600px) {
        .stats-grid {
            grid-template-columns: 1fr !important;
            gap: 20px;
        }
        .stat-card {
            padding: 20px;
            border-radius: 20px;
        }
        .portal-card {
            padding: 25px;
            border-radius: 25px;
        }
        .child-fee-card {
            padding: 25px;
            border-radius: 25px;
        }
        .child-section {
            padding: 25px;
            border-radius: 25px;
        }
        h1 { font-size: 1.8rem; }
    }
</style>
