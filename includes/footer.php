    <footer>
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h4 class="footer-title">Care Compass Hospitals</h4>
                <p>Providing quality healthcare services with compassion and excellence.</p>
                <div class="social-links mt-3">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo getBaseUrl(); ?>/#services">Our Services</a></li>
                    <li><a href="<?php echo getBaseUrl(); ?>/#doctors">Our Doctors</a></li>
                    <li><a href="<?php echo getBaseUrl(); ?>/#features">Why Choose Us</a></li>
                    <li><a href="<?php echo getBaseUrl(); ?>/#testimonials">Testimonials</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h4 class="footer-title">Contact Info</h4>
                <ul class="footer-links">
                    <li><i class="fas fa-map-marker-alt me-2"></i> 123 Hospital Street, Medical District</li>
                    <li><i class="fas fa-phone me-2"></i> (123) 456-7890</li>
                    <li><i class="fas fa-envelope me-2"></i> info@carecompass.com</li>
                    <li><i class="fas fa-clock me-2"></i> 24/7 Emergency Services</li>
                </ul>
            </div>
        </div>
        <div class="text-center mt-4">
            <p>&copy; <?php echo date('Y'); ?> Care Compass Hospitals. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
<script src="<?php echo getBaseUrl(); ?>/js/main.js"></script>
<?php endif; ?>
