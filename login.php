<?php
session_start();

// Jika sudah login, redirect ke halaman yang sesuai
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === "admin") {
        header("Location: admin/dashboard.php");
        exit;
    } else {
        header("Location: index.php");
        exit;
    }
}

include 'includes/db.php';

$msg = "";
$msg_type = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass  = trim($_POST['password']);

    // CARI EMAIL - PERBAIKAN: gunakan kolom 'id' bukan 'user_id'
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // COCOKKAN PASSWORD
        if (password_verify($pass, $user['password'])) {

            // SET SESSION BENAR
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            // DEBUG: Uncomment baris di bawah untuk cek session
            // echo "Session set: " . print_r($_SESSION, true); exit;

            // ARAHKAN SESUAI ROLE
            if ($user['role'] === "admin") {
                header("Location: admin/dashboard.php");
                exit;
            } else {
                header("Location: index.php");
                exit;
            }

        } else {
            $msg = "Password salah! Silakan coba lagi.";
            $msg_type = "error";
        }
    } else {
        $msg = "Email tidak ditemukan! Pastikan email Anda benar.";
        $msg_type = "error";
    }
    
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GOTIX | Booking Tiket Kereta Online</title>
    <link rel="stylesheet" href="assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Back to Home Button -->
    <div class="back-home">
        <a href="index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            <span>Kembali ke Beranda</span>
        </a>
    </div>

    <!-- Auth Wrapper -->
    <div class="auth-wrapper">
        <!-- Floating Shapes -->
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>

        <!-- Auth Card -->
        <div class="auth-card">
            <!-- Auth Header -->
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-train"></i>
                    <h1>GO<span>TIX</span></h1>
                </div>
                <p class="auth-subtitle">Sistem Pemesanan Tiket Kereta Online Terpercaya</p>
            </div>

            <!-- Auth Body -->
            <div class="auth-body">
                <h2 class="auth-title">Selamat Datang Kembali!</h2>
                <p class="auth-description">Login untuk melanjutkan perjalanan Anda</p>

                <!-- Alert Message -->
                <?php if ($msg != ""): ?>
                <div class="auth-alert <?= $msg_type ?>">
                    <i class="fas fa-<?= $msg_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
                    <span><?= htmlspecialchars($msg) ?></span>
                </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" id="loginForm">
                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            Email
                        </label>
                        <div class="form-input-wrapper">
                            <input type="email" id="email" name="email" class="form-input"
                                placeholder="Masukkan email Anda" required autofocus
                                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div class="form-input-wrapper">
                            <input type="password" id="password" name="password" class="form-input"
                                placeholder="Masukkan password Anda" required>
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" name="login" class="btn-submit" id="submitBtn">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Masuk Sekarang</span>
                    </button>
                </form>
            </div>

            <!-- Auth Footer -->
            <div class="auth-footer">
                <p>
                    Belum punya akun?
                    <a href="regist.php" class="auth-link">Daftar sekarang</a>
                </p>
            </div>
        </div>
    </div>

    <script>
    // Toggle Password Visibility
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }

    // Auto-hide alert after 5 seconds
    const alert = document.querySelector('.auth-alert');
    if (alert) {
        setTimeout(() => {
            alert.style.animation = 'slideUp 0.3s ease reverse';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    }
    </script>
</body>

</html>