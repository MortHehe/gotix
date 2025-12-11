<?php
session_start();

// HARUS LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// CEK ROLE JANGAN ADMIN MASUK USER PAGE
if ($_SESSION['role'] === "admin") {
    header("Location: admin/dashboard.php");
    exit;
}

// Include database connection
require_once 'includes/db.php';

// AMBIL DATA UNTUK POPULAR ROUTES (Top 6)
$popular_routes_query = $conn->query("
    SELECT r.id, r.origin, r.destination, r.time, 
           MIN(st.price) as min_price,
           COUNT(b.id) as booking_count
    FROM routes r
    LEFT JOIN schedules s ON r.id = s.route_id
    LEFT JOIN schedule_train st ON s.id = st.schedule_id
    LEFT JOIN book b ON st.id = b.schedule_train_id
    GROUP BY r.id
    ORDER BY booking_count DESC
    LIMIT 6
");

// AMBIL DATA TRAINS (Semua kereta)
$trains_query = $conn->query("
    SELECT id, name_train, type_train, amount_seat
    FROM trains
    ORDER BY type_train DESC
");

// AMBIL SEMUA ROUTES UNTUK DROPDOWN
$routes_dropdown = $conn->query("SELECT DISTINCT origin FROM routes ORDER BY origin");
$destinations_dropdown = $conn->query("SELECT DISTINCT destination FROM routes ORDER BY destination");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GOTIX - Pemesanan Tiket Kereta Online</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <!-- Logo -->
            <div class="navbar-logo">
                <i class="fas fa-train"></i>
                <h2>GO<span>TIX</span></h2>
            </div>

            <!-- Nav Menu (Center) -->
            <ul class="nav-menu">
                <li><a href="#home" class="nav-link active">Beranda</a></li>
                <li><a href="#routes" class="nav-link">Rute</a></li>
                <li><a href="#trains" class="nav-link">Kereta</a></li>
                <li><a href="#how-it-works" class="nav-link">Cara Pesan</a></li>
                <li><a href="#contact" class="nav-link">Kontak</a></li>
            </ul>

            <!-- User Profile Dropdown -->
            <div class="user-profile">
                <button class="profile-btn" id="profileBtn">
                    <i class="fas fa-user-circle"></i>
                    <span><?= htmlspecialchars($_SESSION['name']) ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="profile.php"><i class="fas fa-user"></i> Profil Saya</a>
                    <a href="my-tickets.php"><i class="fas fa-ticket-alt"></i> Tiket Saya</a>
                    <hr>
                    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">Pesan Tiket Kereta <span>Cepat & Mudah</span></h1>
            <p class="hero-subtitle">Nikmati perjalanan nyaman ke seluruh Indonesia dengan GOTIX</p>

            <!-- Search Box -->
            <div class="search-box">
                <form action="search-schedule.php" method="GET" class="search-form">
                    <div class="search-row">
                        <div class="search-field">
                            <label><i class="fas fa-map-marker-alt"></i> Stasiun Asal</label>
                            <select name="origin" required>
                                <option value="">Pilih Stasiun Keberangkatan</option>
                                <?php 
                                $routes_dropdown->data_seek(0);
                                while($route = $routes_dropdown->fetch_assoc()): 
                                ?>
                                <option value="<?= htmlspecialchars($route['origin']) ?>">
                                    <?= htmlspecialchars($route['origin']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="search-swap">
                            <button type="button" class="swap-btn">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                        </div>

                        <div class="search-field">
                            <label><i class="fas fa-map-marker-alt"></i> Stasiun Tujuan</label>
                            <select name="destination" required>
                                <option value="">Pilih Stasiun Tujuan</option>
                                <?php 
                                $destinations_dropdown->data_seek(0);
                                while($dest = $destinations_dropdown->fetch_assoc()): 
                                ?>
                                <option value="<?= htmlspecialchars($dest['destination']) ?>">
                                    <?= htmlspecialchars($dest['destination']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="search-field">
                            <label><i class="fas fa-calendar-alt"></i> Tanggal Keberangkatan</label>
                            <input type="date" name="date" required min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="search-field">
                            <label><i class="fas fa-user"></i> Penumpang</label>
                            <input type="number" name="passengers" value="1" min="1" max="8" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i> Cari Jadwal
                    </button>
                </form>
            </div>

            <!-- Stats -->
            <div class="hero-stats">
                <div class="stat-item">
                    <i class="fas fa-ticket-alt"></i>
                    <h3>1000+</h3>
                    <p>Tiket Terjual</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-route"></i>
                    <h3><?= $popular_routes_query->num_rows ?>+</h3>
                    <p>Rute Tersedia</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-users"></i>
                    <h3>500+</h3>
                    <p>Pengguna Aktif</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <h2 class="section-title">Cara Pesan Tiket</h2>
            <p class="section-subtitle">Booking tiket kereta hanya dalam 4 langkah mudah</p>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Cari Jadwal</h3>
                    <p>Pilih rute dan tanggal keberangkatan sesuai kebutuhan Anda</p>
                </div>

                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-icon">
                        <i class="fas fa-train"></i>
                    </div>
                    <h3>Pilih Kereta</h3>
                    <p>Lihat ketersediaan kursi dan pilih kelas yang Anda inginkan</p>
                </div>

                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Bayar</h3>
                    <p>Pilih metode pembayaran yang mudah dan aman</p>
                </div>

                <div class="step-card">
                    <div class="step-number">4</div>
                    <div class="step-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3>Dapatkan Tiket</h3>
                    <p>E-ticket langsung dikirim dan siap digunakan</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Routes -->
    <section class="popular-routes" id="routes">
        <div class="container">
            <h2 class="section-title">Rute Populer</h2>
            <p class="section-subtitle">Destinasi favorit pilihan pelanggan kami</p>

            <div class="routes-grid">
                <?php 
            $popular_routes_query->data_seek(0);
            // Set default date (hari ini)
            $default_date = date('Y-m-d');
            $default_passengers = 1;
            
            while($route = $popular_routes_query->fetch_assoc()): 
            ?>
                <div class="route-card">
                    <div class="route-header">
                        <i class="fas fa-map-marker-alt route-icon"></i>
                    </div>
                    <div class="route-body">
                        <h3><?= htmlspecialchars($route['origin']) ?></h3>
                        <div class="route-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        <h3><?= htmlspecialchars($route['destination']) ?></h3>
                    </div>
                    <div class="route-footer">
                        <div class="route-info">
                            <span class="route-duration">
                                <i class="fas fa-clock"></i> <?= $route['time'] ?> menit
                            </span>
                            <span class="route-price">
                                Mulai dari <strong>Rp <?= number_format($route['min_price'], 0, ',', '.') ?></strong>
                            </span>
                        </div>
                        <a href="search-schedule.php?<?= http_build_query([
                        'origin' => $route['origin'],
                        'destination' => $route['destination'],
                        'date' => $default_date,
                        'passengers' => $default_passengers
                    ]) ?>" class="btn-view-schedule">
                            Lihat Jadwal
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Available Trains -->
    <section class="available-trains" id="trains">
        <div class="container">
            <h2 class="section-title">Armada Kereta Kami</h2>
            <p class="section-subtitle">Kereta modern dengan fasilitas terbaik</p>

            <div class="trains-slider">
                <button class="slider-btn prev-btn" id="prevBtn">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="trains-container" id="trainsContainer">
                    <?php 
                    $trains_query->data_seek(0);
                    while($train = $trains_query->fetch_assoc()): 
                    ?>
                    <div class="train-card">
                        <div class="train-image">
                            <i class="fas fa-train"></i>
                        </div>
                        <h3><?= htmlspecialchars($train['name_train']) ?></h3>
                        <span class="train-type"><?= htmlspecialchars($train['type_train']) ?></span>
                        <div class="train-details">
                            <p><i class="fas fa-chair"></i> <?= $train['amount_seat'] ?> Kursi</p>
                            <p><i class="fas fa-wifi"></i> WiFi Gratis</p>
                            <p><i class="fas fa-snowflake"></i> AC</p>
                            <p><i class="fas fa-charging-station"></i> Charging Port</p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="why-choose-us">
        <div class="container">
            <h2 class="section-title">Mengapa Pilih GOTIX?</h2>
            <p class="section-subtitle">Keunggulan yang membuat kami berbeda</p>

            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>Harga Terjangkau</h3>
                    <p>Dapatkan harga terbaik dengan berbagai promo menarik setiap bulan</p>
                </div>

                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Pembayaran Aman</h3>
                    <p>Transaksi dilindungi dengan enkripsi SSL 256-bit</p>
                </div>

                <div class="feature-card">
                    <i class="fas fa-bolt"></i>
                    <h3>Booking Cepat</h3>
                    <p>Proses pemesanan hanya dalam hitungan menit</p>
                </div>

                <div class="feature-card">
                    <i class="fas fa-mobile-alt"></i>
                    <h3>Mobile Friendly</h3>
                    <p>Pesan tiket dari smartphone kapan saja, di mana saja</p>
                </div>

                <div class="feature-card">
                    <i class="fas fa-ticket-alt"></i>
                    <h3>E-Ticket Instant</h3>
                    <p>Tiket digital langsung dikirim setelah pembayaran</p>
                </div>

                <div class="feature-card">
                    <i class="fas fa-headset"></i>
                    <h3>24/7 Support</h3>
                    <p>Customer service siap membantu Anda setiap saat</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta">
        <div class="cta-overlay"></div>
        <div class="container">
            <h2>Siap Untuk Perjalanan Anda?</h2>
            <p>Pesan tiket sekarang dan nikmati perjalanan nyaman bersama GOTIX</p>
            <div class="cta-buttons">
                <a href="#home" class="btn-cta primary">Pesan Sekarang</a>
                <a href="#routes" class="btn-cta secondary">Lihat Jadwal</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <div class="footer-logo">
                        <i class="fas fa-train"></i>
                        <h3>GO<span>TIX</span></h3>
                    </div>
                    <p>Sistem pemesanan tiket kereta online terpercaya di Indonesia</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home">Beranda</a></li>
                        <li><a href="#routes">Rute</a></li>
                        <li><a href="#trains">Kereta</a></li>
                        <li><a href="my-tickets.php">Tiket Saya</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Bantuan</h4>
                    <ul>
                        <li><a href="#">Cara Pesan</a></li>
                        <li><a href="#">Syarat & Ketentuan</a></li>
                        <li><a href="#">Kebijakan Privasi</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Hubungi Kami</h4>
                    <ul class="contact-info">
                        <li><i class="fas fa-map-marker-alt"></i> Jakarta, Indonesia</li>
                        <li><i class="fas fa-phone"></i> +62 812 3456 7890</li>
                        <li><i class="fas fa-envelope"></i> support@gotix.com</li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2024 GOTIX. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
    // Profile Dropdown Toggle
    const profileBtn = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdown');

    profileBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown.classList.toggle('show');
        profileBtn.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
            profileDropdown.classList.remove('show');
            profileBtn.classList.remove('active');
        }
    });

    // Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navMenu = document.querySelector('.nav-menu');

    mobileMenuToggle.addEventListener('click', () => {
        navMenu.classList.toggle('active');
        const icon = mobileMenuToggle.querySelector('i');

        if (navMenu.classList.contains('active')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    });

    // Swap Origin and Destination
    const swapBtn = document.querySelector('.swap-btn');
    if (swapBtn) {
        swapBtn.addEventListener('click', () => {
            const originSelect = document.querySelector('select[name="origin"]');
            const destinationSelect = document.querySelector('select[name="destination"]');

            const tempValue = originSelect.value;
            originSelect.value = destinationSelect.value;
            destinationSelect.value = tempValue;
        });
    }

    // Smooth Scroll for Navigation Links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));

            if (target) {
                const navbarHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = target.offsetTop - navbarHeight;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });

                // Close mobile menu if open
                navMenu.classList.remove('active');
                const icon = mobileMenuToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    });

    // Active Navigation Link on Scroll
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');

    function updateActiveNavLink() {
        const scrollPosition = window.scrollY + 100;

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            const sectionId = section.getAttribute('id');

            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${sectionId}`) {
                        link.classList.add('active');
                    }
                });
            }
        });
    }

    window.addEventListener('scroll', updateActiveNavLink);

    // Navbar Background on Scroll
    const navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 4px 30px rgba(0, 0, 0, 0.15)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
        }
    });

    // Train Slider Navigation
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const trainsContainer = document.getElementById('trainsContainer');

    if (prevBtn && nextBtn && trainsContainer) {
        const scrollStep = 320; // Width of one card + gap

        nextBtn.addEventListener('click', () => {
            trainsContainer.scrollBy({
                left: scrollStep,
                behavior: 'smooth'
            });
        });

        prevBtn.addEventListener('click', () => {
            trainsContainer.scrollBy({
                left: -scrollStep,
                behavior: 'smooth'
            });
        });

        // Hide/Show navigation buttons based on scroll position
        trainsContainer.addEventListener('scroll', () => {
            const maxScroll = trainsContainer.scrollWidth - trainsContainer.clientWidth;

            if (trainsContainer.scrollLeft <= 0) {
                prevBtn.style.opacity = '0.3';
                prevBtn.style.cursor = 'not-allowed';
            } else {
                prevBtn.style.opacity = '1';
                prevBtn.style.cursor = 'pointer';
            }

            if (trainsContainer.scrollLeft >= maxScroll - 10) {
                nextBtn.style.opacity = '0.3';
                nextBtn.style.cursor = 'not-allowed';
            } else {
                nextBtn.style.opacity = '1';
                nextBtn.style.cursor = 'pointer';
            }
        });
    }

    // Form Validation for Search
    const searchForm = document.querySelector('.search-form');

    if (searchForm) {
        searchForm.addEventListener('submit', (e) => {
            const origin = document.querySelector('select[name="origin"]').value;
            const destination = document.querySelector('select[name="destination"]').value;
            const date = document.querySelector('input[name="date"]').value;
            const passengers = document.querySelector('input[name="passengers"]').value;

            if (!origin || !destination || !date || !passengers) {
                e.preventDefault();
                alert('Mohon lengkapi semua field pencarian!');
                return false;
            }

            if (origin === destination) {
                e.preventDefault();
                alert('Stasiun asal dan tujuan tidak boleh sama!');
                return false;
            }

            if (parseInt(passengers) < 1 || parseInt(passengers) > 8) {
                e.preventDefault();
                alert('Jumlah penumpang harus antara 1-8 orang!');
                return false;
            }

            // Check if date is not in the past
            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                e.preventDefault();
                alert('Tanggal keberangkatan tidak boleh di masa lalu!');
                return false;
            }
        });
    }

    // Animation on Scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all cards and sections for animation
    const animatedElements = document.querySelectorAll(
        '.route-card, .train-card, .feature-card, .step-card'
    );

    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    // Set minimum date for date picker
    const dateInput = document.querySelector('input[name="date"]');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
    </script>
</body>

</html>