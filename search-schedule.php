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

// GET PARAMETERS
$origin = isset($_GET['origin']) ? $_GET['origin'] : '';
$destination = isset($_GET['destination']) ? $_GET['destination'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$passengers = isset($_GET['passengers']) ? (int)$_GET['passengers'] : 1;

// VALIDASI INPUT
$error = '';
if (empty($origin) || empty($destination) || empty($date)) {
    $error = 'Mohon lengkapi semua field pencarian!';
}

if ($origin === $destination) {
    $error = 'Stasiun asal dan tujuan tidak boleh sama!';
}

if ($passengers < 1 || $passengers > 8) {
    $error = 'Jumlah penumpang harus antara 1-8 orang!';
}

// SIMPAN PENCARIAN TERAKHIR DI SESSION
if (!$error) {
    $_SESSION['last_search'] = [
        'origin' => $origin,
        'destination' => $destination,
        'date' => $date,
        'passengers' => $passengers
    ];
}

// QUERY JADWAL JIKA TIDAK ADA ERROR
$schedules = [];
if (!$error) {
    // Simpan tanggal di session untuk digunakan di booking
    $_SESSION['selected_date'] = $date;
    
    $stmt = $conn->prepare("
        SELECT 
            st.id as schedule_train_id,
            r.origin, r.destination, r.time as duration_minutes,
            t.id as train_id, t.name_train, t.type_train, t.amount_seat,
            s.id as schedule_id, s.departure_time, s.arrival_time,
            st.price,
            COALESCE(SUM(CASE 
                WHEN DATE(b.date_book) = ? AND b.payment_status IN ('pending', 'paid')
                THEN b.amount_ticket 
                ELSE 0 
            END), 0) as booked_seats,
            (t.amount_seat - COALESCE(SUM(CASE 
                WHEN DATE(b.date_book) = ? AND b.payment_status IN ('pending', 'paid')
                THEN b.amount_ticket 
                ELSE 0 
            END), 0)) as available_seats
        FROM schedule_train st
        JOIN schedules s ON st.schedule_id = s.id
        JOIN routes r ON s.route_id = r.id
        JOIN trains t ON st.train_id = t.id
        LEFT JOIN book b ON st.id = b.schedule_train_id
        WHERE r.origin = ?
          AND r.destination = ?
        GROUP BY st.id
        HAVING available_seats >= ?
        ORDER BY s.departure_time ASC
    ");
    
    $stmt->bind_param("ssssi", $date, $date, $origin, $destination, $passengers);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
}

// AMBIL SEMUA ROUTES UNTUK DROPDOWN
$routes_dropdown = $conn->query("SELECT DISTINCT origin FROM routes ORDER BY origin");
$destinations_dropdown = $conn->query("SELECT DISTINCT destination FROM routes ORDER BY destination");

// FUNGSI HELPER
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatDuration($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . ' jam ' . $mins . ' menit';
}

function formatDate($date) {
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    $split = explode('-', date('Y-n-j', strtotime($date)));
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

function getClassBadge($type) {
    $badges = [
        'Eksekutif' => 'badge-executive',
        'Bisnis' => 'badge-business',
        'Ekonomi' => 'badge-economy'
    ];
    return isset($badges[$type]) ? $badges[$type] : 'badge-economy';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Jadwal - GOTIX</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/search-schedule.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-logo">
                <i class="fas fa-train"></i>
                <h2>GO<span>TIX</span></h2>
            </div>

            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link">Beranda</a></li>
                <li><a href="index.php#routes" class="nav-link">Rute</a></li>
                <li><a href="index.php#trains" class="nav-link">Kereta</a></li>
                <li><a href="index.php#how-it-works" class="nav-link">Cara Pesan</a></li>
                <li><a href="index.php#contact" class="nav-link">Kontak</a></li>
            </ul>

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

            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Search Header Section -->
    <section class="search-header">
        <div class="container">
            <div class="breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Beranda</a>
                <span><i class="fas fa-chevron-right"></i></span>
                <span>Hasil Pencarian</span>
            </div>

            <h1 class="search-title">
                <i class="fas fa-search"></i> Hasil Pencarian Jadwal
            </h1>

            <?php if (!$error): ?>
            <div class="search-info">
                <div class="search-route">
                    <strong><?= htmlspecialchars($origin) ?></strong>
                    <i class="fas fa-arrow-right"></i>
                    <strong><?= htmlspecialchars($destination) ?></strong>
                </div>
                <div class="search-details">
                    <span><i class="fas fa-calendar"></i> <?= formatDate($date) ?></span>
                    <span><i class="fas fa-user"></i> <?= $passengers ?> Penumpang</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Search Box (Editable) -->
            <div class="search-box-mini">
                <form action="search-schedule.php" method="GET" class="search-form">
                    <div class="search-row-mini">
                        <div class="search-field-mini">
                            <select name="origin" required>
                                <option value="">Pilih Asal</option>
                                <?php 
                                $routes_dropdown->data_seek(0);
                                while($route = $routes_dropdown->fetch_assoc()): 
                                ?>
                                <option value="<?= htmlspecialchars($route['origin']) ?>"
                                    <?= $route['origin'] === $origin ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($route['origin']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <button type="button" class="swap-btn-mini">
                            <i class="fas fa-exchange-alt"></i>
                        </button>

                        <div class="search-field-mini">
                            <select name="destination" required>
                                <option value="">Pilih Tujuan</option>
                                <?php 
                                $destinations_dropdown->data_seek(0);
                                while($dest = $destinations_dropdown->fetch_assoc()): 
                                ?>
                                <option value="<?= htmlspecialchars($dest['destination']) ?>"
                                    <?= $dest['destination'] === $destination ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dest['destination']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="search-field-mini">
                            <input type="date" name="date" required min="<?= date('Y-m-d') ?>"
                                value="<?= htmlspecialchars($date) ?>">
                        </div>

                        <div class="search-field-mini">
                            <input type="number" name="passengers" value="<?= $passengers ?>" min="1" max="8" required>
                        </div>

                        <button type="submit" class="btn-search-mini">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="search-results">
        <div class="container">
            <?php if ($error): ?>
            <!-- Error State -->
            <div class="error-state">
                <i class="fas fa-exclamation-circle"></i>
                <h2>Oops! Ada Kesalahan</h2>
                <p><?= htmlspecialchars($error) ?></p>
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                </a>
            </div>
            <?php elseif (empty($schedules)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-sad-tear"></i>
                <h2>Tidak Ada Jadwal Tersedia</h2>
                <p>Maaf, tidak ada jadwal kereta untuk:</p>
                <div class="empty-info">
                    <strong><?= htmlspecialchars($origin) ?> → <?= htmlspecialchars($destination) ?></strong>
                    <span>pada <?= formatDate($date) ?></span>
                </div>
                <div class="empty-tips">
                    <h4>Tips:</h4>
                    <ul>
                        <li>Coba ubah tanggal keberangkatan</li>
                        <li>Periksa ejaan nama stasiun</li>
                        <li>Kurangi jumlah penumpang</li>
                    </ul>
                </div>
                <a href="index.php" class="btn-back">
                    <i class="fas fa-search"></i> Cari Jadwal Lain
                </a>
            </div>
            <?php else: ?>
            <!-- Results Info -->
            <div class="results-info">
                <div class="results-count">
                    <i class="fas fa-check-circle"></i>
                    Ditemukan <strong><?= count($schedules) ?></strong> jadwal tersedia
                </div>
            </div>

            <!-- Schedule Cards -->
            <div class="schedules-list">
                <?php foreach ($schedules as $index => $schedule): 
                        $isPast = strtotime($schedule['departure_time']) < time();
                        $totalPrice = $schedule['price'] * $passengers;
                        $seatPercentage = ($schedule['available_seats'] / $schedule['amount_seat']) * 100;
                    ?>
                <div class="schedule-card <?= getClassBadge($schedule['type_train']) ?> <?= $isPast ? 'disabled' : '' ?>"
                    data-index="<?= $index ?>">
                    <div class="card-header">
                        <div class="train-info">
                            <h3 class="train-name">
                                <i class="fas fa-train"></i>
                                <?= htmlspecialchars($schedule['name_train']) ?>
                            </h3>
                            <span class="train-badge <?= getClassBadge($schedule['type_train']) ?>">
                                <?= htmlspecialchars($schedule['type_train']) ?>
                            </span>
                        </div>
                        <?php if ($isPast): ?>
                        <div class="status-badge past">
                            <i class="fas fa-clock"></i> Sudah Berangkat
                        </div>
                        <?php elseif ($seatPercentage < 20): ?>
                        <div class="status-badge limited">
                            <i class="fas fa-exclamation-triangle"></i> Kursi Terbatas
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <div class="schedule-route">
                            <div class="route-time">
                                <div class="time-point">
                                    <h4><?= date('H:i', strtotime($schedule['departure_time'])) ?></h4>
                                    <p><?= htmlspecialchars($schedule['origin']) ?></p>
                                </div>
                                <div class="route-duration">
                                    <div class="duration-line"></div>
                                    <span class="duration-text">
                                        <i class="fas fa-clock"></i>
                                        <?= formatDuration($schedule['duration_minutes']) ?>
                                    </span>
                                </div>
                                <div class="time-point">
                                    <h4><?= date('H:i', strtotime($schedule['arrival_time'])) ?></h4>
                                    <p><?= htmlspecialchars($schedule['destination']) ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="schedule-details">
                            <div class="detail-item">
                                <i class="fas fa-chair"></i>
                                <span>Kursi Tersedia:
                                    <strong><?= $schedule['available_seats'] ?>/<?= $schedule['amount_seat'] ?></strong></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-tag"></i>
                                <span class="price-per">
                                    <?= formatRupiah($schedule['price']) ?> /orang
                                </span>
                            </div>
                        </div>

                        <div class="card-footer">
                            <div class="total-price">
                                <span>Total Harga</span>
                                <h3><?= formatRupiah($totalPrice) ?></h3>
                                <small><?= $passengers ?> penumpang</small>
                            </div>
                            <div class="card-actions">
                                <button class="btn-detail" onclick="toggleDetail(<?= $index ?>)">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Lihat Detail</span>
                                </button>
                                <?php if ($isPast): ?>
                                <button class="btn-select" disabled>
                                    <i class="fas fa-ban"></i> Tidak Tersedia
                                </button>
                                <?php else: ?>
                                <a href="booking.php?schedule_train_id=<?= $schedule['schedule_train_id'] ?>&passengers=<?= $passengers ?>&date=<?= urlencode($date) ?>"
                                    class="btn-select">
                                    <i class="fas fa-arrow-right"></i> Pilih Kereta
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Expandable -->
                    <div class="card-detail" id="detail-<?= $index ?>">
                        <div class="detail-content">
                            <h4><i class="fas fa-info-circle"></i> Detail Kereta</h4>
                            <div class="detail-grid">
                                <div class="detail-col">
                                    <p><i class="fas fa-train"></i> <strong>Nama Kereta:</strong>
                                        <?= htmlspecialchars($schedule['name_train']) ?></p>
                                    <p><i class="fas fa-star"></i> <strong>Kelas:</strong>
                                        <?= htmlspecialchars($schedule['type_train']) ?></p>
                                    <p><i class="fas fa-chair"></i> <strong>Total Kursi:</strong>
                                        <?= $schedule['amount_seat'] ?> kursi</p>
                                </div>
                                <div class="detail-col">
                                    <p><i class="fas fa-wifi"></i> WiFi Gratis</p>
                                    <p><i class="fas fa-snowflake"></i> AC</p>
                                    <p><i class="fas fa-charging-station"></i> Charging Port</p>
                                    <p><i class="fas fa-utensils"></i> Makanan (Beli di Kereta)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
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
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="index.php#routes">Rute</a></li>
                        <li><a href="index.php#trains">Kereta</a></li>
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
    // Profile Dropdown
    const profileBtn = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdown');

    profileBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown.classList.toggle('show');
        profileBtn.classList.toggle('active');
    });

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
        icon.classList.toggle('fa-bars');
        icon.classList.toggle('fa-times');
    });

    // Swap Origin-Destination
    const swapBtn = document.querySelector('.swap-btn-mini');
    if (swapBtn) {
        swapBtn.addEventListener('click', () => {
            const originSelect = document.querySelector('select[name="origin"]');
            const destinationSelect = document.querySelector('select[name="destination"]');
            const temp = originSelect.value;
            originSelect.value = destinationSelect.value;
            destinationSelect.value = temp;
        });
    }

    // Toggle Detail Card
    function toggleDetail(index) {
        const detailDiv = document.getElementById('detail-' + index);
        const btn = document.querySelector(`[data-index="${index}"] .btn-detail`);

        if (detailDiv.classList.contains('show')) {
            detailDiv.classList.remove('show');
            btn.innerHTML = '<i class="fas fa-info-circle"></i><span>Lihat Detail</span>';
        } else {
            // Close all other details
            document.querySelectorAll('.card-detail').forEach(d => d.classList.remove('show'));
            document.querySelectorAll('.btn-detail').forEach(b => {
                b.innerHTML = '<i class="fas fa-info-circle"></i><span>Lihat Detail</span>';
            });

            detailDiv.classList.add('show');
            btn.innerHTML = '<i class="fas fa-times-circle"></i><span>Tutup Detail</span>';
        }
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

    document.querySelectorAll('.schedule-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    </script>
</body>

</html>