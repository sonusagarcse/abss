document.addEventListener('DOMContentLoaded', () => {
    // Header Scroll Effect
    const header = document.querySelector('.main-header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Reveal Intersection Observer
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

    // Smooth scroll for anchors
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const target = document.querySelector(targetId);
            if (target) {
                e.preventDefault();
                // Close mobile menu if open
                document.getElementById('mobile-drawer').classList.remove('open');

                window.scrollTo({
                    top: target.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });

    // Mobile Menu Drawer Logic
    const openBtn = document.getElementById('mobile-menu-open');
    const closeBtn = document.getElementById('mobile-menu-close');
    const drawer = document.getElementById('mobile-drawer');

    if (openBtn && drawer) {
        openBtn.addEventListener('click', () => {
            drawer.classList.add('open');
        });
    }

    if (closeBtn && drawer) {
        closeBtn.addEventListener('click', () => {
            drawer.classList.remove('open');
        });
    }

    // Close drawer on clicking outside
    document.addEventListener('click', (e) => {
        if (drawer.classList.contains('open') && !drawer.contains(e.target) && !openBtn.contains(e.target)) {
            drawer.classList.remove('open');
        }
    });
});
