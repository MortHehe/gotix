<?php
session_start();

// HARUS LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// CEK ROLE JANGAN ADMIN MASUK USER PAGE
if (isset($_SESSION['role']) && $_SESSION['role'] === "admin") {
    header("Location: admin/dashboard.php");
    exit;
}

require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];

// Helper Functions
function formatDate($date)
{
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $split = explode('-', date('Y-n-j', strtotime($date)));
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

function formatDateTimeLong($datetime)
{
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $split = explode('-', date('Y-n-j H:i', strtotime($datetime)));
    $time = explode(' ', $split[2]);
    return $time[0] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0] . ', ' . $time[1] . ' WIB';
}

function formatRupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Ambil parameter aksi
$action = isset($_GET['action']) ? $_GET['action'] : '';
$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;

// =============================================================
// MODE DOWNLOAD / PRINT VIEW TIKET
// =============================================================
if ($action === 'download' && $ticket_id > 0) {
    $stmt = $conn->prepare("
        SELECT 
            t.id AS ticket_id,
            t.ticket_code,
            t.issued_at,
            b.id AS book_id,
            b.date_book,
            b.amount_ticket,
            b.amount_price,
            b.payment_status,
            st.id AS schedule_train_id,
            st.price,
            s.departure_time,
            s.arrival_time,
            r.origin,
            r.destination,
            r.time AS duration_minutes,
            tr.name_train,
            tr.type_train,
            u.name AS user_name,
            u.email AS user_email,
            p.payment_gateway_ref
        FROM ticket t
        JOIN book b ON t.book_id = b.id
        JOIN schedule_train st ON b.schedule_train_id = st.id
        JOIN schedules s ON st.schedule_id = s.id
        JOIN routes r ON s.route_id = r.id
        JOIN trains tr ON st.train_id = tr.id
        JOIN users u ON b.user_id = u.id
        LEFT JOIN payment p ON b.id = p.book_id
        WHERE t.id = ? AND b.user_id = ?
    ");
    $stmt->bind_param('ii', $ticket_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ticket = $result->fetch_assoc();
    $stmt->close();

    if (!$ticket) {
        echo "Tiket tidak ditemukan atau bukan milik Anda.";
        exit;
    }

    // PDF/Print view akan ditampilkan disini
    include 'ticket-print.php';
    exit;
}

// =============================================================
// MODE LIST & DETAIL TIKET (halaman utama)
// =============================================================

// Ambil semua tiket milik user
$stmt = $conn->prepare("
    SELECT 
        t.id AS ticket_id,
        t.ticket_code,
        t.issued_at,
        b.id AS book_id,
        b.date_book,
        b.amount_ticket,
        b.amount_price,
        b.payment_status,
        st.id AS schedule_train_id,
        st.price,
        s.departure_time,
        s.arrival_time,
        r.origin,
        r.destination,
        r.time AS duration_minutes,
        tr.name_train,
        tr.type_train,
        p.payment_gateway_ref
    FROM ticket t
    JOIN book b ON t.book_id = b.id
    JOIN schedule_train st ON b.schedule_train_id = st.id
    JOIN schedules s ON st.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    JOIN trains tr ON st.train_id = tr.id
    LEFT JOIN payment p ON b.id = p.book_id
    WHERE b.user_id = ?
    ORDER BY t.issued_at DESC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$ticketsResult = $stmt->get_result();
$tickets = [];
while ($row = $ticketsResult->fetch_assoc()) {
    $tickets[] = $row;
}
$stmt->close();

// Hitung statistik
$totalTickets = count($tickets);
$paidTickets = 0;
$pendingTickets = 0;

foreach ($tickets as $t) {
    if ($t['payment_status'] === 'paid') {
        $paidTickets++;
    } elseif ($t['payment_status'] === 'pending') {
        $pendingTickets++;
    }
}

$selectedTicket = null;
if ($ticket_id > 0) {
    foreach ($tickets as $t) {
        if ((int)$t['ticket_id'] === $ticket_id) {
            $selectedTicket = $t;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Saya - GOTIX</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/profile.css">
    <link rel="stylesheet" href="assets/css/my-tickets.css">
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

    <!-- Header -->
    <section class="payment-header">
        <div class="container">
            <div class="breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Beranda</a>
                <span><i class="fas fa-chevron-right"></i></span>
                <span>Tiket Saya</span>
            </div>

            <div class="profile-header">
                <h1><i class="fas fa-ticket-alt"></i> Tiket Saya</h1>
                <p>Kelola dan lihat semua tiket kereta yang telah Anda pesan</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="my-tickets-content">
        <div class="container">
            <!-- Statistics Cards -->
            <div class="tickets-stats">
                <div class="stat-card-mini total">
                    <div class="stat-icon-mini">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-info-mini">
                        <h3><?= $totalTickets ?></h3>
                        <p>Total Tiket</p>
                    </div>
                </div>

                <div class="stat-card-mini paid">
                    <div class="stat-icon-mini">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info-mini">
                        <h3><?= $paidTickets ?></h3>
                        <p>Tiket Aktif</p>
                    </div>
                </div>

                <div class="stat-card-mini pending">
                    <div class="stat-icon-mini">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info-mini">
                        <h3><?= $pendingTickets ?></h3>
                        <p>Menunggu Bayar</p>
                    </div>
                </div>
            </div>

            <!-- Tickets List -->
            <div class="tickets-section">
                <div class="section-header">
                    <h2>
                        <i class="fas fa-list"></i>
                        Daftar Tiket
                    </h2>
                </div>

                <div class="section-body">
                    <?php if (empty($tickets)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <h3>Belum Ada Tiket</h3>
                        <p>Anda belum memiliki tiket perjalanan. Mulai pesan tiket kereta Anda sekarang dan nikmati
                            perjalanan yang nyaman!</p>
                        <a href="index.php" class="btn-primary-large">
                            <i class="fas fa-search"></i>
                            Cari Tiket Sekarang
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="tickets-table">
                            <thead>
                                <tr>
                                    <th>Kode Tiket</th>
                                    <th>Perjalanan</th>
                                    <th>Kereta</th>
                                    <th>Tanggal & Waktu</th>
                                    <th>Penumpang</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $t): ?>
                                <tr>
                                    <td>
                                        <span class="ticket-code-cell"><?= htmlspecialchars($t['ticket_code']) ?></span>
                                    </td>
                                    <td>
                                        <div class="journey-cell">
                                            <span class="journey-origin"><?= htmlspecialchars($t['origin']) ?></span>
                                            <i class="fas fa-arrow-right"></i>
                                            <span
                                                class="journey-destination"><?= htmlspecialchars($t['destination']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($t['name_train']) ?></strong><br>
                                        <small style="color: #6b7280;"><?= htmlspecialchars($t['type_train']) ?></small>
                                    </td>
                                    <td>
                                        <div class="date-cell">
                                            <span class="date-main"><?= formatDate($t['date_book']) ?></span>
                                            <span class="date-sub">
                                                <i class="fas fa-clock"></i>
                                                <?= date('H:i', strtotime($t['departure_time'])) ?> -
                                                <?= date('H:i', strtotime($t['arrival_time'])) ?> WIB
                                            </span>
                                        </div>
                                    </td>
                                    <td><?= (int)$t['amount_ticket'] ?> orang</td>
                                    <td><strong><?= formatRupiah($t['amount_price']) ?></strong></td>
                                    <td>
                                        <?php
                                                $status = $t['payment_status'];
                                                $badgeClass = 'pending';
                                                $label = 'Pending';
                                                $icon = 'fa-clock';
                                                if ($status === 'paid') {
                                                    $badgeClass = 'paid';
                                                    $label = 'Paid';
                                                    $icon = 'fa-check-circle';
                                                } elseif ($status === 'failed') {
                                                    $badgeClass = 'failed';
                                                    $label = 'Failed';
                                                    $icon = 'fa-times-circle';
                                                }
                                                ?>
                                        <span class="status-badge <?= $badgeClass ?>">
                                            <i class="fas <?= $icon ?>"></i>
                                            <?= $label ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($t['payment_status'] === 'paid'): ?>
                                            <a href="my-tickets.php?ticket_id=<?= (int)$t['ticket_id'] ?>"
                                                class="btn-action btn-view">
                                                <i class="fas fa-eye"></i>
                                                Detail
                                            </a>
                                            <?php endif; ?>

                                            <?php if ($t['payment_status'] === 'paid'): ?>
                                            <a href="my-tickets.php?action=download&ticket_id=<?= (int)$t['ticket_id'] ?>"
                                                target="_blank" class="btn-action btn-download">
                                                <i class="fas fa-download"></i>
                                                Download
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ticket Detail Card -->
            <?php if ($selectedTicket): ?>
            <div class="ticket-detail-card">
                <div class="ticket-detail-header">
                    <div class="ticket-detail-title">
                        <i class="fas fa-receipt"></i>
                        <h3>Detail E-Ticket</h3>
                    </div>
                    <span class="ticket-status-label">
                        <?= htmlspecialchars($selectedTicket['ticket_code']) ?>
                    </span>
                </div>

                <div class="ticket-detail-body">
                    <!-- QR Code Section -->
                    <div class="qr-section">
                        <div class="qr-wrapper">
                            <img class="qr-image"
                                src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode($selectedTicket['ticket_code']) ?>"
                                alt="QR Code">
                        </div>
                        <div class="qr-code-text"><?= htmlspecialchars($selectedTicket['ticket_code']) ?></div>
                    </div>

                    <!-- Details Section -->
                    <div class="details-section">
                        <!-- Journey Info -->
                        <div class="detail-group">
                            <h4><i class="fas fa-route"></i> Informasi Perjalanan</h4>
                            <div class="detail-item">
                                <span class="detail-label">Kereta</span>
                                <span class="detail-value">
                                    <?= htmlspecialchars($selectedTicket['name_train']) ?>
                                    (<?= htmlspecialchars($selectedTicket['type_train']) ?>)
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Rute</span>
                                <span class="detail-value">
                                    <?= htmlspecialchars($selectedTicket['origin']) ?> →
                                    <?= htmlspecialchars($selectedTicket['destination']) ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tanggal Keberangkatan</span>
                                <span class="detail-value"><?= formatDate($selectedTicket['date_book']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Waktu</span>
                                <span class="detail-value">
                                    <?= date('H:i', strtotime($selectedTicket['departure_time'])) ?> -
                                    <?= date('H:i', strtotime($selectedTicket['arrival_time'])) ?> WIB
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Durasi</span>
                                <span class="detail-value">
                                    <?= floor($selectedTicket['duration_minutes'] / 60) ?> jam
                                    <?= $selectedTicket['duration_minutes'] % 60 ?> menit
                                </span>
                            </div>
                        </div>

                        <!-- Booking Info -->
                        <div class="detail-group">
                            <h4><i class="fas fa-info-circle"></i> Informasi Pemesanan</h4>
                            <div class="detail-item">
                                <span class="detail-label">Kode Booking</span>
                                <span class="detail-value">
                                    <?= htmlspecialchars($selectedTicket['payment_gateway_ref'] ?? ('BOOK-' . $selectedTicket['book_id'])) ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Jumlah Penumpang</span>
                                <span class="detail-value"><?= (int)$selectedTicket['amount_ticket'] ?> orang</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Total Pembayaran</span>
                                <span class="detail-value"><?= formatRupiah($selectedTicket['amount_price']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status Pembayaran</span>
                                <span class="detail-value">
                                    <?= ucfirst($selectedTicket['payment_status']) ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tiket Diterbitkan</span>
                                <span
                                    class="detail-value"><?= formatDateTimeLong($selectedTicket['issued_at']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Print Section -->
                    <?php if ($selectedTicket['payment_status'] === 'paid'): ?>
                    <div class="print-section">
                        <p><i class="fas fa-info-circle"></i> Tunjukkan QR Code atau kode tiket ini saat boarding</p>
                        <div class="print-buttons">
                            <a href="my-tickets.php?action=download&ticket_id=<?= (int)$selectedTicket['ticket_id'] ?>"
                                target="_blank" class="btn-print">
                                <i class="fas fa-print"></i>
                                Cetak / Download PDF
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
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

    if (profileBtn && profileDropdown) {
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
    }

    // Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navMenu = document.querySelector('.nav-menu');

    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            const icon = mobileMenuToggle.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
    }

    // Smooth scroll to detail when ticket_id is present
    <?php if ($selectedTicket): ?>
    window.addEventListener('load', () => {
        const detailCard = document.querySelector('.ticket-detail-card');
        if (detailCard) {
            setTimeout(() => {
                detailCard.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 300);
        }
    });
    <?php endif; ?>

    // Animation on scroll
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

    document.querySelectorAll('.stat-card-mini, .tickets-section, .ticket-detail-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    </script>
</body>

</html>