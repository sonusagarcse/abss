<!-- admin/includes/head_css.php -->
<link rel="icon" type="image/png" href="../assets/logo.png">
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

    @media (max-width: 1024px) {
        .sidebar { display: none; }
        .main-content { margin-left: 0; padding: 30px 20px; }
    }
</style>
