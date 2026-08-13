<!-- teacher/includes/head_css.php -->
<link rel="icon" type="image/png" href="../assets/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { 
        --teacher-purple: #7c3aed;
        --teacher-dark: #5b21b6;
        --sidebar-width: 270px;
    }
    
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #f5f3ff; color: #1e1b4b; font-family: 'Outfit', sans-serif; display: flex; min-height: 100vh; overflow-x: hidden; }
    
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
        border-bottom: 1px solid #ede9fe;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }
    .mobile-brand { display: flex; align-items: center; gap: 10px; }
    .mobile-brand img { height: 36px; }
    .mobile-brand span { font-weight: 800; color: var(--teacher-dark); font-size: 1.1rem; }

    .hamburger-btn {
        background: #ede9fe;
        border: none;
        color: var(--teacher-dark);
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
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 1400;
        display: none;
    }
    .sidebar-overlay.active { display: block !important; }

    .sidebar {
        width: var(--sidebar-width);
        background: #ffffff;
        padding: 35px 20px;
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100vh;
        box-shadow: 10px 0 30px rgba(124, 58, 237, 0.04);
        z-index: 1500;
        border-right: 1px solid #ede9fe;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .close-sidebar-btn {
        display: none;
        position: absolute;
        top: 15px;
        right: 15px;
        background: #f1f5f9;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        color: #475569;
        font-size: 1rem;
        cursor: pointer;
        align-items: center;
        justify-content: center;
    }

    .sidebar-brand { 
        display: flex; 
        align-items: center; 
        gap: 12px; 
        margin-bottom: 40px; 
        padding: 0 10px;
    }
    .sidebar-brand img { height: 42px; }
    .sidebar-brand span { font-weight: 800; color: var(--teacher-dark); font-size: 1.3rem; }

    .nav-menu { list-style: none; overflow-y: auto; flex-grow: 1; }
    .nav-item { margin-bottom: 8px; }
    .nav-link { 
        display: flex; 
        align-items: center; 
        gap: 14px; 
        padding: 12px 18px; 
        color: #64748b; 
        text-decoration: none; 
        font-weight: 700; 
        border-radius: 14px; 
        transition: 0.25s; 
    }
    .nav-link:hover, .nav-link.active { 
        background: #ede9fe; 
        color: var(--teacher-purple); 
    }
    .nav-link i { font-size: 1.1rem; width: 22px; text-align: center; }

    .logout-link { color: #ef4444; margin-top: auto; border-top: 1px solid #ede9fe; padding-top: 15px; }
    .logout-link:hover { background: #fee2e2; color: #dc2626; }

    .main-content { 
        margin-left: var(--sidebar-width); 
        flex-grow: 1; 
        padding: 40px; 
        max-width: calc(100vw - var(--sidebar-width)); 
    }

    @media (max-width: 992px) {
        body { flex-direction: column; }
        .mobile-header { display: flex; }
        .sidebar { transform: translateX(-100%); width: 280px; }
        .sidebar.open { transform: translateX(0); }
        .close-sidebar-btn { display: flex; }
        .main-content { margin-left: 0; max-width: 100vw; padding: 20px 15px; }
    }
</style>
