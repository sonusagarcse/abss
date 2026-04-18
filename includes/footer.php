    <footer class="main-footer">
        <div class="container footer-grid">
            <div class="footer-info">
                <img src="assets/logo.png" alt="Logo" style="height: 50px;">
                <p><?php echo $settings['school_name']; ?> is dedicated to nurturing the next generation of leaders through competitive excellence and moral values.</p>
                <div class="social-links" style="display: flex; flex-direction: row; gap: 15px;">
                    <a href="<?php echo $settings['facebook']; ?>"><i class="fab fa-facebook-f"></i></a>
                    <a href="<?php echo $settings['twitter']; ?>"><i class="fab fa-twitter"></i></a>
                    <a href="<?php echo $settings['instagram']; ?>"><i class="fab fa-instagram"></i></a>
                    <a href="<?php echo $settings['linkedin']; ?>"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <h3>Academics</h3>
                <ul>
                    <li><a href="#home">Home</a></li>
                    <li><a href="#about">About Center</a></li>
                    <li><a href="#programs">Programs</a></li>
                    <li><a href="#admission">Fees Structure</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h3>Portals</h3>
                <ul>
                    <li><a href="admin/login.php?role=admin">Admin Login</a></li>
                    <li><a href="admin/login.php?role=teacher">Teacher Portal</a></li>
                    <li><a href="admin/login.php?role=parent">Parent Portal</a></li>
                    <li><a href="admin/login.php">Staff Login</a></li>
                </ul>
            </div>
            <div class="footer-contact" id="contact">
                <h3>Contact Info</h3>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo $settings['address']; ?></p>
                <p><i class="fas fa-phone"></i> <?php echo $settings['phone']; ?></p>
                <p><i class="fas fa-envelope"></i> <?php echo $settings['email']; ?></p>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container bottom-flex">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $settings['school_name']; ?>. All Rights Reserved.</p>
                <div class="bottom-links">
                    <a href="#">Privacy Policy</a>
                    <span class="sep">|</span>
                    <a href="#">Terms</a>
                    <span class="sep">|</span>
                    <a href="admin/login.php">Staff Login</a>
                </div>
            </div>
        </div>
    </footer>

<style>
.bottom-flex { display: flex; justify-content: space-between; align-items: center; }
.bottom-links { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; }
.bottom-links a:hover { color: var(--secondary); }
.sep { opacity: 0.3; }

@media (max-width: 768px) {
    .bottom-flex { flex-direction: column; text-align: center; gap: 15px; }
}

<style>
.social-links { display: flex; gap: 15px; margin-top: 25px; }
.social-links a { 
    width: 40px; 
    height: 40px; 
    background: rgba(255,255,255,0.1); 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: var(--white);
    transition: var(--ease-in-out);
}
.social-links a:hover { background: var(--secondary); color: var(--primary-dark); transform: translateY(-5px); }
</style>

    <script src="js/main.js"></script>
</body>
</html>
