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

$user_id = (int)$_SESSION['user_id'];

// Ambil data user dari database
$stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    // Jika user tidak ditemukan, paksa logout
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

// Label role untuk tampilan
$role_label = $user['role'] === 'admin' ? 'Administrator' : 'Pengguna';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - GOTIX</title>
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/profile.css">
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
                <li><a href="index.php#home" class="nav-link">Beranda</a></li>
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
                <span>Profil Saya</span>
            </div>

            <div class="profile-header">
                <h1><i class="fas fa-user"></i> Profil Saya</h1>
                <p>Lihat informasi akun dan detail profil Anda.</p>
            </div>
        </div>
    </section>

    <!-- Konten Profil -->
    <section class="profile-page">
        <div class="container">
            <div class="profile-card-wrapper">
                <div class="profile-card">
                    <div class="profile-main">
                        <div class="profile-avatar">
                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                        </div>
                        <div class="profile-info">
                            <h2><?= htmlspecialchars($user['name']) ?></h2>
                            <div class="profile-role">
                                <i class="fas fa-id-badge"></i>
                                <span><?= htmlspecialchars($role_label) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="profile-meta">
                        <div class="profile-row">
                            <i class="fas fa-envelope"></i>
                            <div class="profile-label">Email</div>
                            <div class="profile-value"><?= htmlspecialchars($user['email']) ?></div>
                        </div>

                        <div class="profile-row">
                            <i class="fas fa-hashtag"></i>
                            <div class="profile-label">User ID</div>
                            <div class="profile-value">#<?= str_pad($user['id'], 5, '0', STR_PAD_LEFT) ?></div>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <a href="logout.php" class="btn-logout-inline">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2024 GOTIX. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
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
    </script>
</body>

</html>