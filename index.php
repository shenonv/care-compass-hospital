<?php
require_once 'includes/config.php';
$page_title = "Welcome to Care Compass Hospitals";
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <h1 class="hero-title">Welcome to Care Compass Hospitals</h1>
        <p class="hero-subtitle">Your Health, Our Priority - Leading the Way in Healthcare Excellence</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="mt-4">
                <a href="register.php" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-user-plus me-2"></i>Register Now
                </a>
                <a href="login.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
            </div>
        <?php else: ?>
            <div class="mt-4">
                <?php if ($_SESSION['user_type'] === 'patient'): ?>
                    <a href="patient/dashboard.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="admin/dashboard.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-tachometer-alt me-2"></i>Go to Admin Panel
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Services Section -->
<section class="services-section" id="services">
    <div class="container">
        <div class="text-center mb-5">
            <h2>Our Services</h2>
            <p class="lead">Comprehensive Healthcare Solutions for You and Your Family</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="service-card">
                    <i class="fas fa-heartbeat service-icon"></i>
                    <h3 class="service-title">Cardiology</h3>
                    <p>Expert heart care with state-of-the-art diagnostic and treatment facilities.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <i class="fas fa-brain service-icon"></i>
                    <h3 class="service-title">Neurology</h3>
                    <p>Specialized care for neurological conditions with advanced treatment options.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <i class="fas fa-bone service-icon"></i>
                    <h3 class="service-title">Orthopedics</h3>
                    <p>Comprehensive care for bone, joint, and muscle conditions.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <i class="fas fa-stethoscope service-icon"></i>
                    <h3 class="service-title">General Medicine</h3>
                    <p>Primary healthcare services for all your medical needs.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <i class="fas fa-tooth service-icon"></i>
                    <h3 class="service-title">Dental Care</h3>
                    <p>Complete dental care services for a healthy and beautiful smile.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <i class="fas fa-eye service-icon"></i>
                    <h3 class="service-title">Ophthalmology</h3>
                    <p>Expert eye care services with modern diagnostic equipment.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 col-6 mb-4">
                <div class="stats-item">
                    <i class="fas fa-ambulance fa-3x mb-3"></i>
                    <h2 class="stat-number" data-target="24/7">24/7</h2>
                    <p>Emergency Care</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stats-item">
                    <i class="fas fa-user-md fa-3x mb-3"></i>
                    <h2 class="stat-number" data-target="50">0</h2>
                    <p>Expert Doctors</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stats-item">
                    <i class="fas fa-procedures fa-3x mb-3"></i>
                    <h2 class="stat-number" data-target="247">0</h2>
                    <p>Medical Services</p>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-4">
                <div class="stats-item">
                    <i class="fas fa-smile fa-3x mb-3"></i>
                    <h2 class="stat-number" data-target="1000">0</h2>
                    <p>Happy Patients</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Expert Doctors Section -->
<section class="doctors-section py-5" id="doctors">
    <div class="container">
        <div class="text-center mb-5">
            <h2>Our Expert Doctors</h2>
            <p class="lead">Meet Our Professional and Expert Doctors</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="doctor-card">
                    <div class="doctor-image">
                        <img src="images/doctors/bearded-doctor-glasses.jpg" alt="Dr. John Anderson" class="img-fluid">
                    </div>
                    <div class="doctor-info">
                        <h4>Dr. John Anderson</h4>
                        <p class="specialty">Cardiologist</p>
                        <p class="description">Specializing in cardiovascular health with over 15 years of experience in treating heart conditions.</p>
                        <div class="consultation-fee">
                            <span>Consultation Fee: Rs. 48,000</span>
                        </div>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'patient'): ?>
                            <a href="patient/book_appointment.php?doctor_id=1" class="btn btn-primary mt-3">Book Appointment</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary mt-3">Login to Book</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="doctor-card">
                    <div class="doctor-image">
                        <img src="images/doctors/female-doctor-hospital-with-stethoscope.jpg" alt="Dr. Sarah Miller" class="img-fluid">
                    </div>
                    <div class="doctor-info">
                        <h4>Dr. Sarah Miller</h4>
                        <p class="specialty">Pediatrician</p>
                        <p class="description">Dedicated to providing comprehensive care for children with a gentle and caring approach.</p>
                        <div class="consultation-fee">
                            <span>Consultation Fee: Rs. 38,400</span>
                        </div>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'patient'): ?>
                            <a href="patient/book_appointment.php?doctor_id=2" class="btn btn-primary mt-3">Book Appointment</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary mt-3">Login to Book</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="doctor-card">
                    <div class="doctor-image">
                        <img src="images/doctors/portrait-health-worker-special-equipment.jpg" alt="Dr. Michael Roberts" class="img-fluid">
                    </div>
                    <div class="doctor-info">
                        <h4>Dr. Michael Roberts</h4>
                        <p class="specialty">Neurologist</p>
                        <p class="description">Expert in neurological disorders with a focus on innovative treatment approaches.</p>
                        <div class="consultation-fee">
                            <span>Consultation Fee: Rs. 57,600</span>
                        </div>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'patient'): ?>
                            <a href="patient/book_appointment.php?doctor_id=3" class="btn btn-primary mt-3">Book Appointment</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary mt-3">Login to Book</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section bg-light" id="features">
    <div class="container">
        <div class="text-center mb-5">
            <h2>Why Choose Us?</h2>
            <p class="lead">Experience Healthcare Excellence</p>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="fas fa-clock feature-icon"></i>
                    <h4 class="feature-title">24/7 Emergency Care</h4>
                    <p>Round-the-clock emergency services with rapid response teams.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="fas fa-user-md feature-icon"></i>
                    <h4 class="feature-title">Expert Medical Team</h4>
                    <p>Highly qualified and experienced healthcare professionals.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <i class="fas fa-microscope feature-icon"></i>
                    <h4 class="feature-title">Modern Technology</h4>
                    <p>State-of-the-art medical equipment and facilities.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section" id="testimonials">
    <div class="container">
        <div class="text-center mb-5">
            <h2>Patient Testimonials</h2>
            <p class="lead">What Our Patients Say About Us</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-left mb-3"></i>
                        <p>"The care I received at Care Compass was exceptional. The doctors and staff were not only professional but also very caring and supportive throughout my treatment."</p>
                        <div class="testimonial-author">
                            <h5>John Smith</h5>
                            <p class="text-muted">Cardiac Patient</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-left mb-3"></i>
                        <p>"I'm grateful for the excellent pediatric care my child received. The pediatricians here are amazing with kids and really know how to make them comfortable."</p>
                        <div class="testimonial-author">
                            <h5>Sarah Johnson</h5>
                            <p class="text-muted">Parent</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <i class="fas fa-quote-left mb-3"></i>
                        <p>"The emergency response team was quick and efficient. Their 24/7 service and professional approach helped save my life. Forever thankful!"</p>
                        <div class="testimonial-author">
                            <h5>Michael Brown</h5>
                            <p class="text-muted">Emergency Care Patient</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section" id="cta">
    <div class="container">
        <h2 class="mb-4">Ready to Experience Better Healthcare?</h2>
        <p class="lead mb-4">Join Care Compass Hospitals today and get access to world-class healthcare services.</p>
        <a href="register.php" class="btn btn-primary btn-lg">
            <i class="fas fa-user-plus me-2"></i>Register Now
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<!-- Scripts -->
<script src="js/main.js"></script>
<script>
// Counter animation with improved handling
function animateCounter(element) {
    const target = element.getAttribute('data-target');
    
    // Special handling for 24/7
    if (target === '24/7') {
        element.textContent = '24/7';
        return;
    }

    let start = 0;
    const end = parseInt(target);
    const duration = 2000;
    const increment = end / (duration / 16);

    const timer = setInterval(() => {
        start += increment;
        if (start >= end) {
            clearInterval(timer);
            element.textContent = target;
            return;
        }
        element.textContent = Math.floor(start);
    }, 16);
}

// Intersection Observer for triggering animations
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const counter = entry.target;
            animateCounter(counter);
            observer.unobserve(counter);
        }
    });
}, { threshold: 0.5 });

// Initialize counters when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.stat-number').forEach(counter => {
        observer.observe(counter);
    });
});
</script>
