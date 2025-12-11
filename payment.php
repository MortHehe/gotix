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

// HANDLE AJAX REQUEST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    
    if ($book_id === 0) {
        echo json_encode(['success' => false, 'message' => 'ID Booking tidak valid!']);
        exit;
    }
    
    // Verifikasi ownership
    $stmt = $conn->prepare("SELECT user_id FROM book WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book_check = $result->fetch_assoc();
    
    if (!$book_check || $book_check['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Booking tidak ditemukan atau bukan milik Anda!']);
        exit;
    }
    
    // START TRANSACTION
    $conn->begin_transaction();
    
    try {
        if ($action === 'check_payment') {
            // UPDATE STATUS JADI PAID/SUCCESS
            
            // Update table book
            $stmt = $conn->prepare("UPDATE book SET payment_status = 'paid' WHERE id = ?");
            $stmt->bind_param("i", $book_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Gagal update status booking!');
            }
            
            // Update table payment
            $stmt = $conn->prepare("UPDATE payment SET payment_status = 'success' WHERE book_id = ?");
            $stmt->bind_param("i", $book_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Gagal update status payment!');
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Pembayaran berhasil dikonfirmasi! Tiket Anda sudah bisa digunakan.'
            ]);
            exit;
            
        } elseif ($action === 'cancel_booking') {
            // UPDATE STATUS JADI FAILED
            
            // Update table book
            $stmt = $conn->prepare("UPDATE book SET payment_status = 'failed' WHERE id = ?");
            $stmt->bind_param("i", $book_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Gagal membatalkan booking!');
            }
            
            // Update table payment
            $stmt = $conn->prepare("UPDATE payment SET payment_status = 'failed' WHERE book_id = ?");
            $stmt->bind_param("i", $book_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Gagal update status payment!');
            }
            
            $conn->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Booking berhasil dibatalkan.'
            ]);
            exit;
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Action tidak valid!']);
            exit;
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// GET BOOK ID
$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;

if ($book_id === 0) {
    $_SESSION['error'] = 'ID Booking tidak valid!';
    header("Location: index.php");
    exit;
}

// QUERY DETAIL BOOKING
$stmt = $conn->prepare("
    SELECT 
        b.id as book_id,
        b.user_id,
        b.date_book,
        b.amount_ticket,
        b.amount_price,
        b.payment_status as book_payment_status,
        st.id as schedule_train_id,
        st.price as ticket_price,
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
        p.id as payment_id,
        p.payment_status as payment_payment_status,
        p.payment_date,
        p.payment_gateway_ref,
        u.name as user_name,
        u.email as user_email
    FROM book b
    JOIN schedule_train st ON b.schedule_train_id = st.id
    JOIN schedules s ON st.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    JOIN trains t ON st.train_id = t.id
    LEFT JOIN payment p ON b.id = p.book_id
    JOIN users u ON b.user_id = u.id
    WHERE b.id = ? AND b.user_id = ?
");

$stmt->bind_param("ii", $book_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

// CEK APAKAH BOOKING DITEMUKAN
if (!$booking) {
    $_SESSION['error'] = 'Booking tidak ditemukan atau bukan milik Anda!';
    header("Location: index.php");
    exit;
}

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

function formatDateTime($datetime) {
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    $split = explode('-', date('Y-n-j H:i', strtotime($datetime)));
    $time = explode(' ', $split[2]);
    return $time[0] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0] . ', ' . $time[1];
}

// STATUS LABEL
$status_class = '';
$status_text = '';
$status_icon = '';

switch($booking['book_payment_status']) {
    case 'pending':
        $status_class = 'status-pending';
        $status_text = 'Menunggu Pembayaran';
        $status_icon = 'fa-clock';
        break;
    case 'paid':
        $status_class = 'status-success';
        $status_text = 'Pembayaran Berhasil';
        $status_icon = 'fa-check-circle';
        break;
    case 'failed':
        $status_class = 'status-failed';
        $status_text = 'Pembayaran Dibatalkan';
        $status_icon = 'fa-times-circle';
        break;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - GOTIX</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/payment.css">
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

    <!-- Payment Header -->
    <section class="payment-header">
        <div class="container">
            <div class="breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Beranda</a>
                <span><i class="fas fa-chevron-right"></i></span>
                <a href="my-tickets.php">Tiket Saya</a>
                <span><i class="fas fa-chevron-right"></i></span>
                <span>Pembayaran</span>
            </div>

            <h1 class="payment-title">
                <i class="fas fa-credit-card"></i> Pembayaran Booking
            </h1>

            <!-- Progress Indicator -->
            <div class="progress-steps">
                <div class="progress-step completed">
                    <div class="step-circle"><i class="fas fa-check"></i></div>
                    <span>Data Penumpang</span>
                </div>
                <div class="progress-line completed"></div>
                <div class="progress-step active">
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
    <section class="payment-content">
        <div class="container">
            <!-- Status Alert -->
            <div class="status-alert <?= $status_class ?>">
                <i class="fas <?= $status_icon ?>"></i>
                <div class="status-content">
                    <h3><?= $status_text ?></h3>
                    <p>Booking ID: <strong>#<?= str_pad($booking['book_id'], 6, '0', STR_PAD_LEFT) ?></strong></p>
                </div>
            </div>

            <div class="payment-grid">
                <!-- LEFT COLUMN: BOOKING DETAIL -->
                <div class="payment-detail-section">
                    <!-- Booking Summary -->
                    <div class="detail-card">
                        <div class="detail-card-header">
                            <i class="fas fa-ticket-alt"></i>
                            <h3>Detail Pemesanan</h3>
                        </div>
                        <div class="detail-card-body">
                            <!-- Journey Info -->
                            <div class="journey-info">
                                <div class="journey-route">
                                    <div class="route-point">
                                        <span class="route-label">Dari</span>
                                        <h4><?= htmlspecialchars($booking['origin']) ?></h4>
                                    </div>
                                    <div class="route-icon">
                                        <i class="fas fa-arrow-right"></i>
                                    </div>
                                    <div class="route-point">
                                        <span class="route-label">Ke</span>
                                        <h4><?= htmlspecialchars($booking['destination']) ?></h4>
                                    </div>
                                </div>

                                <div class="journey-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?= formatDate($booking['date_book']) ?></span>
                                </div>
                            </div>

                            <div class="divider"></div>

                            <!-- Train Info -->
                            <div class="train-info">
                                <div class="train-icon">
                                    <i class="fas fa-train"></i>
                                </div>
                                <div class="train-details">
                                    <h4><?= htmlspecialchars($booking['name_train']) ?></h4>
                                    <span class="train-class"><?= htmlspecialchars($booking['type_train']) ?></span>
                                </div>
                            </div>

                            <div class="time-info">
                                <div class="time-item">
                                    <span class="time-label">Berangkat</span>
                                    <span
                                        class="time-value"><?= date('H:i', strtotime($booking['departure_time'])) ?></span>
                                </div>
                                <div class="time-duration">
                                    <i class="fas fa-clock"></i>
                                    <span><?= formatDuration($booking['duration_minutes']) ?></span>
                                </div>
                                <div class="time-item">
                                    <span class="time-label">Tiba</span>
                                    <span
                                        class="time-value"><?= date('H:i', strtotime($booking['arrival_time'])) ?></span>
                                </div>
                            </div>

                            <div class="divider"></div>

                            <!-- Passenger Info -->
                            <div class="passenger-info">
                                <div class="info-row">
                                    <span class="info-label"><i class="fas fa-user"></i> Nama Pemesan</span>
                                    <span class="info-value"><?= htmlspecialchars($booking['user_name']) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                                    <span class="info-value"><?= htmlspecialchars($booking['user_email']) ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label"><i class="fas fa-users"></i> Jumlah Penumpang</span>
                                    <span class="info-value"><?= $booking['amount_ticket'] ?> Orang</span>
                                </div>
                            </div>

                            <div class="divider"></div>

                            <!-- Price Details -->
                            <div class="price-details">
                                <div class="price-row">
                                    <span>Harga Tiket (× <?= $booking['amount_ticket'] ?>)</span>
                                    <span><?= formatRupiah($booking['ticket_price']) ?></span>
                                </div>
                                <div class="price-row total">
                                    <span>Total Pembayaran</span>
                                    <span class="total-amount"><?= formatRupiah($booking['amount_price']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <div class="detail-card">
                        <div class="detail-card-header">
                            <i class="fas fa-info-circle"></i>
                            <h3>Informasi Pembayaran</h3>
                        </div>
                        <div class="detail-card-body">
                            <div class="info-row">
                                <span class="info-label">Referensi Pembayaran</span>
                                <span
                                    class="info-value payment-ref"><?= htmlspecialchars($booking['payment_gateway_ref']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Tanggal Dibuat</span>
                                <span class="info-value"><?= formatDateTime($booking['payment_date']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Status Pembayaran</span>
                                <span class="status-badge <?= $status_class ?>">
                                    <i class="fas <?= $status_icon ?>"></i>
                                    <?= $status_text ?>
                                </span>
                            </div>

                            <?php if ($booking['book_payment_status'] === 'pending'): ?>
                            <div class="warning-box">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div>
                                    <strong>Perhatian!</strong>
                                    <p>Silakan scan QR Code di samping untuk melakukan pembayaran. Setelah membayar,
                                        klik tombol "Cek Status Pembayaran".</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: QR CODE & ACTIONS -->
                <div class="payment-action-section">
                    <?php if ($booking['book_payment_status'] === 'pending'): ?>
                    <div class="qr-card">
                        <div class="qr-card-header">
                            <i class="fas fa-qrcode"></i>
                            <h3>Scan untuk Bayar</h3>
                        </div>
                        <div class="qr-card-body">
                            <div class="qr-code-wrapper">
                                <img id="qrCodeImage"
                                    src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= urlencode($booking['payment_gateway_ref']) ?>"
                                    alt="QR Code Payment" class="qr-code-image">
                            </div>
                            <p class="qr-instruction">
                                <i class="fas fa-mobile-alt"></i>
                                Gunakan aplikasi e-wallet atau mobile banking untuk scan QR Code ini
                            </p>
                            <div class="payment-amount-display">
                                <span>Total yang harus dibayar</span>
                                <strong><?= formatRupiah($booking['amount_price']) ?></strong>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <?php if ($booking['book_payment_status'] === 'pending'): ?>
                        <button class="btn-check-payment" id="btnCheckPayment">
                            <i class="fas fa-sync-alt"></i>
                            Cek Status Pembayaran
                        </button>
                        <button class="btn-cancel" id="btnCancelBooking">
                            <i class="fas fa-times"></i>
                            Batalkan Booking
                        </button>
                        <?php elseif ($booking['book_payment_status'] === 'paid'): ?>
                        <a href="my-tickets.php" class="btn-view-ticket">
                            <i class="fas fa-ticket-alt"></i>
                            Lihat E-Ticket Saya
                        </a>
                        <?php else: ?>
                        <a href="index.php" class="btn-back-home">
                            <i class="fas fa-home"></i>
                            Kembali ke Beranda
                        </a>
                        <?php endif; ?>
                        <?php if ($booking['book_payment_status'] === 'pending'): ?>
                        <!-- QR Code for Pending Payment -->
                        <div class="qr-card">
                            <div class="qr-card-header">
                                <i class="fas fa-qrcode"></i>
                                <h3>Scan untuk Bayar</h3>
                            </div>
                            <div class="qr-card-body">
                                <div class="qr-code-wrapper">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= urlencode($booking['payment_gateway_ref']) ?>"
                                        alt="QR Code Payment" class="qr-code-image">
                                </div>
                                <p class="qr-instruction">
                                    <i class="fas fa-mobile-alt"></i>
                                    Gunakan aplikasi e-wallet atau mobile banking untuk scan QR Code ini
                                </p>
                                <div class="payment-amount-display">
                                    <span>Total yang harus dibayar</span>
                                    <strong><?= formatRupiah($booking['amount_price']) ?></strong>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- E-Ticket for Paid Booking -->
                        <?php
    // Generate a random 8-character alphanumeric code
    $randomCode = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    $currentDateTime = date('Y-m-d H:i:s');
    ?>

                        <div class="ticket-card">
                            <div class="ticket-header">
                                <i class="fas fa-ticket-alt"></i>
                                <h3>E-Ticket</h3>
                                <span class="ticket-status"><?= $status_text ?></span>
                            </div>
                            <div class="ticket-body">
                                <div class="ticket-qr">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($randomCode) ?>"
                                        alt="Ticket QR Code">
                                    <div class="ticket-code"><?= $randomCode ?></div>
                                </div>

                                <div class="ticket-details">
                                    <div class="detail-section">
                                        <h4>Informasi Perjalanan</h4>
                                        <div class="detail-row">
                                            <span class="detail-label">Kereta:</span>
                                            <span
                                                class="detail-value"><?= htmlspecialchars($booking['name_train']) ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Rute:</span>
                                            <span class="detail-value"><?= htmlspecialchars($booking['origin']) ?> ke
                                                <?= htmlspecialchars($booking['destination']) ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Tanggal:</span>
                                            <span class="detail-value"><?= formatDate($booking['date_book']) ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Waktu Keberangkatan:</span>
                                            <span
                                                class="detail-value"><?= date('H:i', strtotime($booking['departure_time'])) ?></span>
                                        </div>
                                    </div>

                                    <div class="detail-section">
                                        <h4>Informasi Penumpang</h4>
                                        <div class="detail-row">
                                            <span class="detail-label">Nama:</span>
                                            <span
                                                class="detail-value"><?= htmlspecialchars($booking['user_name']) ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Jumlah Tiket:</span>
                                            <span class="detail-value"><?= $booking['amount_ticket'] ?> Kursi</span>
                                        </div>
                                    </div>

                                    <div class="ticket-footer">
                                        <div class="booking-ref">
                                            <span>Kode Booking:</span>
                                            <strong><?= $booking['payment_gateway_ref'] ?></strong>
                                        </div>
                                        <div class="issued-date">
                                            <span>Diterbitkan pada:</span>
                                            <span><?= formatDateTime($currentDateTime) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Help Box -->
                    <div class="help-box">
                        <i class="fas fa-headset"></i>
                        <div>
                            <h4>Butuh Bantuan?</h4>
                            <p>Hubungi Customer Service kami di:</p>
                            <a href="tel:+6281234567890">+62 812 3456 7890</a>
                        </div>
                    </div>
                </div>
            </div>
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

    <script>
    // ================================================
    // PAYMENT PAGE JAVASCRIPT
    // ================================================

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

    // ================================================
    // LOADING OVERLAY FUNCTIONS
    // ================================================

    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingText = document.querySelector('.loading-text');
    const loadingSubtext = document.querySelector('.loading-subtext');

    function showLoading(text = 'Memproses...', subtext = 'Mohon tunggu sebentar') {
        if (loadingText) loadingText.textContent = text;
        if (loadingSubtext) loadingSubtext.textContent = subtext;
        loadingOverlay.classList.add('show');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    function hideLoading() {
        loadingOverlay.classList.remove('show');
        document.body.style.overflow = ''; // Re-enable scrolling
    }

    // ================================================
    // ALERT FUNCTIONS (Modern Style)
    // ================================================

    function showAlert(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.custom-alert');
        existingAlerts.forEach(alert => alert.remove());

        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `custom-alert custom-alert-${type}`;

        let icon = 'fa-info-circle';
        if (type === 'success') icon = 'fa-check-circle';
        if (type === 'error') icon = 'fa-times-circle';
        if (type === 'warning') icon = 'fa-exclamation-triangle';

        alertDiv.innerHTML = `
        <div class="custom-alert-content">
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        </div>
        <button class="custom-alert-close"><i class="fas fa-times"></i></button>
    `;

        document.body.appendChild(alertDiv);

        // Show animation
        setTimeout(() => alertDiv.classList.add('show'), 10);

        // Close button
        const closeBtn = alertDiv.querySelector('.custom-alert-close');
        closeBtn.addEventListener('click', () => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 300);
        });

        // Auto close after 5 seconds
        setTimeout(() => {
            if (alertDiv.classList.contains('show')) {
                alertDiv.classList.remove('show');
                setTimeout(() => alertDiv.remove(), 300);
            }
        }, 5000);
    }

    // ================================================
    // CHECK PAYMENT STATUS BUTTON
    // ================================================

    const btnCheckPayment = document.getElementById('btnCheckPayment');
    if (btnCheckPayment) {
        btnCheckPayment.addEventListener('click', function() {
            const bookId = this.dataset.bookId || <?= $book_id ?>;

            // Tidak perlu menyembunyikan QR code di sini, karena sekarang dihandle oleh PHP

            // Langsung show loading tanpa konfirmasi
            showLoading('Mengecek status pembayaran...', 'Menghubungi sistem pembayaran');

            // Simulasi delay untuk efek loading (opsional, bisa dihapus jika tidak perlu)
            setTimeout(() => {
                // AJAX Request to update payment status
                fetch('payment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=check_payment&book_id=' + bookId
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();

                        if (data.success) {
                            // Success alert
                            showAlert(data.message, 'success');

                            // Reload page after 2 seconds
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        } else {
                            // Error alert
                            showAlert(data.message, 'error');
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        showAlert(
                            'Terjadi kesalahan saat mengecek status pembayaran. Silakan coba lagi.',
                            'error');
                        console.error('Error:', error);
                    });
            }, 800); // Delay 800ms untuk efek loading yang lebih terasa
        });
    }

    // ================================================
    // CANCEL BOOKING BUTTON
    // ================================================

    const btnCancelBooking = document.getElementById('btnCancelBooking');
    if (btnCancelBooking) {
        btnCancelBooking.addEventListener('click', function() {
            const bookId = this.dataset.bookId || <?= $book_id ?>;

            // Show custom confirmation modal
            showConfirmModal(
                'Batalkan Booking?',
                'Apakah Anda yakin ingin membatalkan booking ini? Tindakan ini tidak dapat dibatalkan.',
                'Batalkan',
                'Kembali',
                () => {
                    // User confirmed
                    showLoading('Membatalkan booking...', 'Mohon tunggu sebentar');

                    setTimeout(() => {
                        // AJAX Request to cancel booking
                        fetch('payment.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'action=cancel_booking&book_id=' + bookId
                            })
                            .then(response => response.json())
                            .then(data => {
                                hideLoading();

                                if (data.success) {
                                    showAlert(data.message, 'success');

                                    // Reload page after 2 seconds
                                    setTimeout(() => {
                                        window.location.href =
                                        'index.php'; // Redirect ke index.php
                                    }, 2000);
                                } else {
                                    showAlert(data.message, 'error');
                                }
                            })
                            .catch(error => {
                                hideLoading();
                                showAlert(
                                    'Terjadi kesalahan saat membatalkan booking. Silakan coba lagi.',
                                    'error');
                                console.error('Error:', error);
                            });
                    }, 800);
                }
            );
        });
    }

    // ================================================
    // CUSTOM CONFIRMATION MODAL
    // ================================================

    function showConfirmModal(title, message, confirmText, cancelText, onConfirm) {
        // Remove existing modals
        const existingModals = document.querySelectorAll('.custom-modal');
        existingModals.forEach(modal => modal.remove());

        // Create modal
        const modalDiv = document.createElement('div');
        modalDiv.className = 'custom-modal';
        modalDiv.innerHTML = `
        <div class="custom-modal-overlay"></div>
        <div class="custom-modal-content">
            <div class="custom-modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>${title}</h3>
            </div>
            <div class="custom-modal-body">
                <p>${message}</p>
            </div>
            <div class="custom-modal-footer">
                <button class="modal-btn modal-btn-cancel">${cancelText}</button>
                <button class="modal-btn modal-btn-confirm">${confirmText}</button>
            </div>
        </div>
    `;

        document.body.appendChild(modalDiv);

        // Show animation
        setTimeout(() => modalDiv.classList.add('show'), 10);

        // Button handlers
        const btnCancel = modalDiv.querySelector('.modal-btn-cancel');
        const btnConfirm = modalDiv.querySelector('.modal-btn-confirm');
        const overlay = modalDiv.querySelector('.custom-modal-overlay');

        function closeModal() {
            modalDiv.classList.remove('show');
            setTimeout(() => modalDiv.remove(), 300);
        }

        btnCancel.addEventListener('click', closeModal);
        overlay.addEventListener('click', closeModal);

        btnConfirm.addEventListener('click', () => {
            closeModal();
            if (onConfirm) onConfirm();
        });
    }

    // ================================================
    // ANIMATION ON SCROLL
    // ================================================

    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // Skip if this is the QR code that was hidden
            if (entry.target.querySelector('#qrCodeImage') && document.getElementById('qrCodeImage')
                .style.display === 'none') {
                return;
            }

            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Apply animation to all cards and buttons
    document.querySelectorAll('.detail-card, .qr-card, .action-buttons').forEach(el => {
        // Skip if element is QR card that should be hidden
        if (el.classList.contains('qr-card') && el.querySelector('.hidden')) {
            el.style.display = 'none';
            return;
        }

        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(el);
    });

    // Uncomment jika ingin auto-refresh status pembayaran setiap 30 detik
    /*
    const paymentStatus = '<?= $booking['book_payment_status'] ?>';
    if (paymentStatus === 'pending') {
        setInterval(() => {
            console.log('Auto checking payment status...');
            // Trigger check payment silently
            const bookId = <?= $book_id ?>;
            fetch('payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=check_payment&book_id=' + bookId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Pembayaran terdeteksi! Halaman akan direfresh...', 'success');
                    setTimeout(() => location.reload(), 2000);
                }
            })
            .catch(error => console.error('Auto check error:', error));
        }, 30000); // Check every 30 seconds
    }
    */

    // ================================================
    // PREVENT BACK BUTTON AFTER SUCCESSFUL PAYMENT
    // ================================================

    const paymentStatus = '<?= $booking['book_payment_status'] ?>';
    if (paymentStatus === 'paid') {
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, null, window.location.href);
            showAlert('Pembayaran sudah berhasil! Silakan lihat E-Ticket Anda.', 'info');
        };
    }

    // ================================================
    // COPY PAYMENT REFERENCE
    // ================================================

    const paymentRefElements = document.querySelectorAll('.payment-ref');
    paymentRefElements.forEach(el => {
        el.style.cursor = 'pointer';
        el.title = 'Klik untuk copy';

        el.addEventListener('click', function() {
            const text = this.textContent;

            // Copy to clipboard
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    showAlert('Referensi pembayaran berhasil dicopy!', 'success');

                    // Visual feedback
                    this.style.background = '#10b981';
                    this.style.color = 'white';
                    setTimeout(() => {
                        this.style.background = '';
                        this.style.color = '';
                    }, 1000);
                }).catch(err => {
                    console.error('Copy failed:', err);
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showAlert('Referensi pembayaran berhasil dicopy!', 'success');
            }
        });
    });

    // ================================================
    // QR CODE DOWNLOAD
    // ================================================

    const qrCodeImage = document.querySelector('.qr-code-image');
    if (qrCodeImage) {
        // Add download button
        const downloadBtn = document.createElement('button');
        downloadBtn.className = 'btn-download-qr';
        downloadBtn.innerHTML = '<i class="fas fa-download"></i> Download QR Code';

        const qrWrapper = qrCodeImage.parentElement;
        qrWrapper.style.position = 'relative';
        qrWrapper.appendChild(downloadBtn);

        downloadBtn.addEventListener('click', function() {
            const link = document.createElement('a');
            link.href = qrCodeImage.src;
            link.download = 'qr-code-pembayaran-<?= str_pad($book_id, 6, "0", STR_PAD_LEFT) ?>.png';
            link.click();

            showAlert('QR Code berhasil didownload!', 'success');
        });
    }

    // ================================================
    // CONSOLE LOG INFO
    // ================================================

    console.log('%c🚂 GOTIX Payment System', 'font-size: 20px; font-weight: bold; color: #3b82f6;');
    console.log('%cBooking ID: <?= str_pad($book_id, 6, "0", STR_PAD_LEFT) ?>', 'color: #10b981;');
    console.log('%cStatus: <?= $booking["book_payment_status"] ?>', 'color: #f59e0b;');
    </script>
</body>

</html>