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
$schedule_train_id = isset($_GET['schedule_train_id']) ? (int)$_GET['schedule_train_id'] : 0;
$passengers = isset($_GET['passengers']) ? (int)$_GET['passengers'] : 1;
$date = isset($_GET['date']) ? $_GET['date'] : '';

// VALIDASI INPUT
if ($schedule_train_id === 0 || $passengers < 1 || $passengers > 8 || empty($date)) {
    $_SESSION['error'] = 'Data pemesanan tidak valid!';
    header("Location: index.php");
    exit;
}

// QUERY DETAIL JADWAL
$stmt = $conn->prepare("
    SELECT 
        st.id as schedule_train_id,
        st.price,
        s.id as schedule_id,
        s.departure_time,
        s.arrival_time,
        r.id as route_id,
        r.origin,
        r.destination,
        r.time as duration_minutes,
        t.id as train_id,
        t.name_train,
        t.type_train,
        t.amount_seat,
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
    WHERE st.id = ?
    GROUP BY st.id
");

$stmt->bind_param("ssi", $date, $date, $schedule_train_id);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();

// CEK APAKAH JADWAL DITEMUKAN
if (!$schedule) {
    $_SESSION['error'] = 'Jadwal tidak ditemukan!';
    header("Location: index.php");
    exit;
}

// CEK KETERSEDIAAN KURSI
if ($schedule['available_seats'] < $passengers) {
    $_SESSION['error'] = 'Kursi tidak mencukupi! Tersedia: ' . $schedule['available_seats'] . ' kursi';
    header("Location: search-schedule.php?" . http_build_query($_SESSION['last_search']));
    exit;
}

// HITUNG TOTAL HARGA
$total_price = $schedule['price'] * $passengers;

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
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $split = explode('-', date('Y-n-j', strtotime($date)));
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

function formatDateShort($date) {
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    $split = explode('-', date('Y-n-j', strtotime($date)));
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Tiket - GOTIX</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/booking.css">
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

    <!-- Booking Header -->
    <section class="booking-header">
        <div class="container">
            <div class="breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Beranda</a>
                <span><i class="fas fa-chevron-right"></i></span>
                <a href="search-schedule.php?<?= http_build_query($_SESSION['last_search']) ?>">Cari Jadwal</a>
                <span><i class="fas fa-chevron-right"></i></span>
                <span>Booking Tiket</span>
            </div>

            <h1 class="booking-title">
                <i class="fas fa-ticket-alt"></i> Lengkapi Data Pemesanan
            </h1>

            <!-- Progress Indicator -->
            <div class="progress-steps">
                <div class="progress-step active">
                    <div class="step-circle">1</div>
                    <span>Data Penumpang</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step">
                    <div class="step-circle">2</div>
                    <span>Pembayaran</span>
                </div>
                <div class="progress-line"></div>
                <div class="progress-step">
                    <div class="step-circle">3</div>
                    <span>E-Ticket</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="booking-content">
        <div class="container">
            <div class="booking-grid">
                <!-- LEFT COLUMN: FORM -->
                <div class="booking-form-section">
                    <form id="bookingForm" action="process-booking.php" method="POST">
                        <!-- Hidden Inputs -->
                        <input type="hidden" name="schedule_train_id" value="<?= $schedule_train_id ?>">
                        <input type="hidden" name="passengers" value="<?= $passengers ?>">
                        <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
                        <input type="hidden" name="total_price" value="<?= $total_price ?>">

                        <!-- DATA PEMESAN -->
                        <div class="form-card">
                            <div class="form-card-header">
                                <i class="fas fa-user-circle"></i>
                                <h3>Data Pemesan (Penanggungjawab)</h3>
                            </div>
                            <div class="form-card-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Nama Lengkap <span class="required">*</span></label>
                                        <input type="text" name="contact_name"
                                            value="<?= htmlspecialchars($_SESSION['name']) ?>" readonly
                                            class="readonly-input">
                                        <small class="form-text">Sesuai dengan data akun Anda</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Email <span class="required">*</span></label>
                                        <input type="email" name="contact_email"
                                            value="<?= htmlspecialchars($_SESSION['email']) ?>" readonly
                                            class="readonly-input">
                                        <small class="form-text">E-ticket akan dikirim ke email ini</small>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>No. Handphone <span class="required">*</span></label>
                                    <input type="tel" name="contact_phone" id="contactPhone"
                                        placeholder="Contoh: 081234567890" pattern="^08[0-9]{9,11}$" required>
                                    <small class="form-text">Format: 08xxxxxxxxxx (10-13 digit)</small>
                                    <span class="error-message" id="phoneError"></span>
                                </div>
                            </div>
                        </div>

                        <!-- DATA PENUMPANG -->
                        <div class="form-card">
                            <div class="form-card-header">
                                <i class="fas fa-users"></i>
                                <h3>Data Penumpang (<?= $passengers ?> Orang)</h3>
                            </div>
                            <div class="form-card-body">
                                <?php for($i = 1; $i <= $passengers; $i++): ?>
                                <div class="passenger-card">
                                    <div class="passenger-header">
                                        <span class="passenger-number">Penumpang <?= $i ?></span>
                                    </div>
                                    <div class="passenger-body">
                                        <div class="form-group">
                                            <label>Nama Lengkap (Sesuai KTP/Passport) <span
                                                    class="required">*</span></label>
                                            <input type="text" name="passenger_name[]" id="passengerName<?= $i ?>"
                                                placeholder="Nama sesuai identitas" minlength="3" required>
                                            <span class="error-message" id="nameError<?= $i ?>"></span>
                                        </div>

                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>No. Identitas (KTP/Passport) <span
                                                        class="required">*</span></label>
                                                <input type="text" name="passenger_id_number[]"
                                                    id="passengerID<?= $i ?>" placeholder="16 digit nomor identitas"
                                                    pattern="[0-9]{16}" maxlength="16" required>
                                                <span class="error-message" id="idError<?= $i ?>"></span>
                                            </div>
                                            <div class="form-group">
                                                <label>Tanggal Lahir <span class="required">*</span></label>
                                                <input type="date" name="passenger_birthdate[]"
                                                    id="passengerBirthdate<?= $i ?>" max="<?= date('Y-m-d') ?>"
                                                    required>
                                                <span class="error-message" id="birthdateError<?= $i ?>"></span>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Jenis Kelamin <span class="required">*</span></label>
                                            <div class="radio-group">
                                                <label class="radio-label">
                                                    <input type="radio" name="passenger_gender[<?= $i-1 ?>]"
                                                        value="Laki-laki" required>

                                                    <i class="fas fa-mars"></i> Laki-laki
                                                </label>
                                                <label class="radio-label">
                                                    <input type="radio" name="passenger_gender[<?= $i-1 ?>]"
                                                        value="Perempuan" required>

                                                    <i class="fas fa-venus"></i> Perempuan
                                                </label>
                                            </div>
                                            <span class="error-message" id="genderError<?= $i ?>"></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- SYARAT & KETENTUAN -->
                        <div class="form-card">
                            <div class="form-card-header">
                                <i class="fas fa-file-contract"></i>
                                <h3>Syarat & Ketentuan</h3>
                            </div>
                            <div class="form-card-body">
                                <div class="checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="agree_terms" id="agreeTerms" required>
                                        <span class="checkbox-custom"></span>
                                        <span>Saya menyetujui <a href="#" class="link-terms">Syarat dan Ketentuan</a>
                                            yang berlaku</span>
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="agree_data_correct" id="agreeDataCorrect" required>
                                        <span class="checkbox-custom"></span>
                                        <span>Saya menjamin data yang dimasukkan sudah <strong>benar dan
                                                sesuai</strong></span>
                                    </label>
                                </div>
                                <span class="error-message" id="agreementError"></span>
                            </div>
                        </div>

                        <!-- ACTION BUTTONS -->
                        <div class="form-actions">
                            <a href="search-schedule.php?<?= http_build_query($_SESSION['last_search']) ?>"
                                class="btn-back">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn-submit" id="btnSubmit">
                                Lanjut ke Pembayaran <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- RIGHT COLUMN: STICKY SUMMARY -->
                <div class="booking-summary-section">
                    <div class="summary-card ">
                        <div class="summary-header">
                            <i class="fas fa-receipt"></i>
                            <h3>Ringkasan Pemesanan</h3>
                        </div>

                        <div class="summary-body">
                            <!-- Journey Info -->
                            <div class="summary-journey">
                                <div class="journey-route">
                                    <h4><?= htmlspecialchars($schedule['origin']) ?></h4>
                                    <div class="route-arrow">
                                        <i class="fas fa-arrow-right"></i>
                                    </div>
                                    <h4><?= htmlspecialchars($schedule['destination']) ?></h4>
                                </div>
                                <div class="journey-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?= formatDate($date) ?>
                                </div>
                            </div>

                            <div class="summary-divider"></div>

                            <!-- Train Info -->
                            <div class="summary-train">
                                <div class="train-icon-wrap">
                                    <i class="fas fa-train"></i>
                                </div>
                                <div class="train-info">
                                    <h4><?= htmlspecialchars($schedule['name_train']) ?></h4>
                                    <span class="train-class"><?= htmlspecialchars($schedule['type_train']) ?></span>
                                </div>
                            </div>

                            <div class="summary-time">
                                <div class="time-item">
                                    <span class="time-label">Berangkat</span>
                                    <span
                                        class="time-value"><?= date('H:i', strtotime($schedule['departure_time'])) ?></span>
                                </div>
                                <div class="time-duration">
                                    <i class="fas fa-clock"></i>
                                    <?= formatDuration($schedule['duration_minutes']) ?>
                                </div>
                                <div class="time-item">
                                    <span class="time-label">Tiba</span>
                                    <span
                                        class="time-value"><?= date('H:i', strtotime($schedule['arrival_time'])) ?></span>
                                </div>
                            </div>

                            <div class="summary-divider"></div>

                            <!-- Price Details -->
                            <div class="summary-price">
                                <div class="price-row">
                                    <span>Penumpang</span>
                                    <span><?= $passengers ?> orang</span>
                                </div>
                                <div class="price-row">
                                    <span>Harga per orang</span>
                                    <span><?= formatRupiah($schedule['price']) ?></span>
                                </div>
                                <div class="price-row total">
                                    <span>Total Harga</span>
                                    <span class="total-amount"><?= formatRupiah($total_price) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="summary-footer">
                            <div class="info-box">
                                <i class="fas fa-info-circle"></i>
                                <p>Harga sudah termasuk biaya admin dan asuransi perjalanan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Custom Confirmation Modal -->
    <div class="custom-modal-overlay" id="confirmModal">
        <div class="custom-modal">
            <div class="custom-modal-header">
                <div class="modal-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h3>Konfirmasi Pemesanan</h3>
                <p>Pastikan data Anda sudah benar</p>
            </div>
            <div class="custom-modal-body">
                <div class="confirmation-details">
                    <div class="detail-row">
                        <span class="detail-label">
                            <i class="fas fa-route"></i>
                            Rute Perjalanan
                        </span>
                        <span class="detail-value" id="modalRoute"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">
                            <i class="fas fa-calendar-alt"></i>
                            Tanggal Keberangkatan
                        </span>
                        <span class="detail-value" id="modalDate"></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">
                            <i class="fas fa-users"></i>
                            Jumlah Penumpang
                        </span>
                        <span class="detail-value" id="modalPassengers"></span>
                    </div>
                    <div class="detail-row total">
                        <span class="detail-label">
                            <i class="fas fa-money-bill-wave"></i>
                            Total Pembayaran
                        </span>
                        <span class="detail-value" id="modalTotal"></span>
                    </div>
                </div>
                <div class="warning-box">
                    <i class="fas fa-info-circle"></i>
                    <p><strong>Penting:</strong> Pastikan semua data yang Anda masukkan sudah benar. Data yang salah
                        dapat menyebabkan pembatalan tiket.</p>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button class="modal-btn modal-btn-cancel" onclick="closeConfirmModal()">
                    <i class="fas fa-times"></i>
                    Periksa Lagi
                </button>
                <button class="modal-btn modal-btn-confirm" onclick="confirmBooking()">
                    <i class="fas fa-check"></i>
                    Ya, Lanjutkan
                </button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-train">
            <div class="train-smoke">
                <div class="smoke"></div>
                <div class="smoke"></div>
                <div class="smoke"></div>
            </div>
            <div class="train-body">
                <div class="train-window"></div>
                <div class="train-window"></div>
                <div class="train-window"></div>
            </div>
            <div class="train-wheels">
                <div class="wheel"></div>
                <div class="wheel"></div>
            </div>
        </div>
        <div class="loading-text">Memproses Pemesanan...</div>
        <div class="loading-subtext">Mohon tunggu sebentar</div>
        <div class="loading-spinner">
            <div class="spinner-dot"></div>
            <div class="spinner-dot"></div>
            <div class="spinner-dot"></div>
        </div>
    </div>

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

    // FORM VALIDATION
    const bookingForm = document.getElementById('bookingForm');
    const contactPhone = document.getElementById('contactPhone');
    const agreeTerms = document.getElementById('agreeTerms');
    const agreeDataCorrect = document.getElementById('agreeDataCorrect');
    const btnSubmit = document.getElementById('btnSubmit');

    // Validasi No. Handphone
    contactPhone.addEventListener('input', function() {
        const phoneError = document.getElementById('phoneError');
        const phonePattern = /^08[0-9]{9,11}$/;

        if (this.value && !phonePattern.test(this.value)) {
            phoneError.textContent = 'Format nomor tidak valid. Gunakan 08xxxxxxxxxx';
            this.classList.add('error');
        } else {
            phoneError.textContent = '';
            this.classList.remove('error');
        }
    });

    // Validasi Nama Penumpang (hanya huruf dan spasi)
    <?php for($i = 1; $i <= $passengers; $i++): ?>
    document.getElementById('passengerName<?= $i ?>').addEventListener('input', function() {
        const nameError = document.getElementById('nameError<?= $i ?>');
        const namePattern = /^[a-zA-Z\s]+$/;

        if (this.value && !namePattern.test(this.value)) {
            nameError.textContent = 'Nama hanya boleh berisi huruf dan spasi';
            this.classList.add('error');
        } else if (this.value.length > 0 && this.value.length < 3) {
            nameError.textContent = 'Nama minimal 3 karakter';
            this.classList.add('error');
        } else {
            nameError.textContent = '';
            this.classList.remove('error');
        }
    });

    // Validasi No. Identitas (hanya angka, 16 digit)
    document.getElementById('passengerID<?= $i ?>').addEventListener('input', function() {
        const idError = document.getElementById('idError<?= $i ?>');
        this.value = this.value.replace(/\D/g, ''); // Hanya angka

        if (this.value && this.value.length !== 16) {
            idError.textContent = 'No. identitas harus 16 digit';
            this.classList.add('error');
        } else {
            idError.textContent = '';
            this.classList.remove('error');
        }
    });
    <?php endfor; ?>

    // CUSTOM MODAL FUNCTIONS
    function showConfirmModal() {
        // Set data ke modal
        document.getElementById('modalRoute').textContent =
            '<?= htmlspecialchars($schedule['origin']) ?> → <?= htmlspecialchars($schedule['destination']) ?>';
        document.getElementById('modalDate').textContent = '<?= formatDate($date) ?>';
        document.getElementById('modalPassengers').textContent = '<?= $passengers ?> Orang';
        document.getElementById('modalTotal').textContent = '<?= formatRupiah($total_price) ?>';

        // Show modal
        const modal = document.getElementById('confirmModal');
        modal.classList.add('show');

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    function closeConfirmModal() {
        const modal = document.getElementById('confirmModal');
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
    }

    function confirmBooking() {
        // Hide confirmation modal
        closeConfirmModal();

        // Show loading overlay
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.classList.add('show');

        // Disable button
        btnSubmit.disabled = true;

        // Submit form setelah 1.5 detik
        setTimeout(() => {
            bookingForm.submit();
        }, 1500);
    }

    // Close modal ketika klik di luar modal
    document.getElementById('confirmModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeConfirmModal();
        }
    });

    // Close modal dengan ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('confirmModal');
            if (modal.classList.contains('show')) {
                closeConfirmModal();
            }
        }
    });

    // Form Submit Validation
    bookingForm.addEventListener('submit', function(e) {
        e.preventDefault();

        let isValid = true;
        const errors = [];

        // Validasi Phone
        const phonePattern = /^08[0-9]{9,11}$/;
        if (!phonePattern.test(contactPhone.value)) {
            errors.push('Nomor handphone tidak valid');
            document.getElementById('phoneError').textContent = 'Format nomor tidak valid';
            contactPhone.classList.add('error');
            isValid = false;
        }



        // Validasi Agreement
        if (!agreeTerms.checked || !agreeDataCorrect.checked) {
            errors.push('Anda harus menyetujui semua syarat dan ketentuan');
            document.getElementById('agreementError').textContent = 'Centang semua persetujuan';
            isValid = false;
        }

        if (!isValid) {
            alert('Mohon lengkapi semua data dengan benar:\n\n' + errors.join('\n'));
            // Scroll ke error pertama
            const firstError = document.querySelector('.error');
            if (firstError) {
                firstError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                firstError.focus();
            }
            return false;
        }

        // TAMPILKAN CUSTOM MODAL (menggantikan confirm() default)
        showConfirmModal();
    });

    // Sticky Summary on Scroll
    window.addEventListener('scroll', function() {
        const summary = document.querySelector('.summary-card.sticky');
        const summarySection = document.querySelector('.booking-summary-section');
        const footer = document.querySelector('.footer');

        if (!summary || !summarySection || !footer) return;

        const footerTop = footer.offsetTop;
        const summaryHeight = summary.offsetHeight;
        const scrollY = window.scrollY;
        const windowHeight = window.innerHeight;
        const sectionTop = summarySection.offsetTop;
        const navbarHeight = 80;

        // Calculate when summary should stop being sticky
        const stopPoint = footerTop - summaryHeight - 40;

        if (scrollY + navbarHeight + 20 > sectionTop && scrollY < stopPoint) {
            summary.style.position = 'sticky';
            summary.style.top = `${navbarHeight + 20}px`;
        } else if (scrollY >= stopPoint) {
            summary.style.position = 'absolute';
            summary.style.top = `${stopPoint - sectionTop}px`;
        } else {
            summary.style.position = 'relative';
            summary.style.top = '0';
        }
    });

    // Clear error on input focus
    document.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('focus', function() {
            this.classList.remove('error');
            const errorSpans = document.querySelectorAll('.error-message');
            errorSpans.forEach(span => {
                if (span.id && this.id && span.id.includes(this.id)) {
                    span.textContent = '';
                }
            });
        });
    });

    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    // Handle back button with confirmation
    let formIsDirty = false;
    const formInputs = bookingForm.querySelectorAll('input:not([readonly]):not([type="hidden"]), select, textarea');

    formInputs.forEach(input => {
        input.addEventListener('change', () => {
            formIsDirty = true;
        });
    });

    const backButton = document.querySelector('.btn-back');
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            if (formIsDirty) {
                if (!confirm('Data yang Anda masukkan akan hilang. Yakin ingin kembali?')) {
                    e.preventDefault();
                }
            }
        });
    }

    // Smooth scroll to top on page load
    window.addEventListener('load', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Add animation to form cards on scroll
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

    document.querySelectorAll('.form-card, .passenger-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(el);
    });
    </script>
</body>

</html>