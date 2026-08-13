<!-- teacher/includes/head_css.php -->
<link rel="icon" type="image/png" href="../assets/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    :root { 
        --teacher-purple: #7c3aed;
        --teacher-dark: #5b21b6;
        --sidebar-width: 270px;
    }
    
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #f5f3ff; color: #1e1b4b; font-family: 'Outfit', sans-serif; display: flex; min-height: 100vh; overflow-x: hidden; }
    
    .sidebar {
        width: var(--sidebar-width);
        background: #ffffff;
        padding: 35px 20px;
        display: flex;
        flex-direction: column;
        position: fixed;
        height: 100vh;
        box-shadow: 10px 0 30px rgba(124, 58, 237, 0.04);
        z-index: 100;
        border-right: 1px solid #ede9fe;
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
        font-weight: 600; 
        border-radius: 14px; 
        transition: 0.3s; 
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
        .sidebar { transform: translateX(-100%); transition: 0.3s; }
        .sidebar.open { transform: translateX(0); }
        .main-content { margin-left: 0; max-width: 100vw; padding: 20px; }
    }
</style>
