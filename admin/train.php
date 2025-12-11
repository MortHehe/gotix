<?php
session_start();
include '../includes/db.php';

// CEK LOGIN & ROLE ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// HANDLE CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = mysqli_real_escape_string($conn, $_POST['name_train']);
    $type = mysqli_real_escape_string($conn, $_POST['type_train']);
    $seats = (int)$_POST['amount_seat'];
    
    mysqli_query($conn, "INSERT INTO trains (name_train, type_train, amount_seat) VALUES ('$name', '$type', $seats)");
    $_SESSION['success_message'] = "Kereta berhasil ditambahkan!";
    header("Location: train.php");
    exit;
}

// HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    $name = mysqli_real_escape_string($conn, $_POST['name_train']);
    $type = mysqli_real_escape_string($conn, $_POST['type_train']);
    $seats = (int)$_POST['amount_seat'];
    
    mysqli_query($conn, "UPDATE trains SET name_train='$name', type_train='$type', amount_seat=$seats WHERE id=$id");
    $_SESSION['success_message'] = "Kereta berhasil diupdate!";
    header("Location: train.php");
    exit;
}

// HANDLE DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM trains WHERE id=$id");
    $_SESSION['success_message'] = "Kereta berhasil dihapus!";
    header("Location: train.php");
    exit;
}

// FETCH EDIT ITEM
$edit_train = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM trains WHERE id=$id LIMIT 1");
    $edit_train = mysqli_fetch_assoc($res);
}

// STATISTICS
$total_trains = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM trains"))['total'];
$total_eksekutif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM trains WHERE type_train='Eksekutif'"))['total'];
$total_bisnis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM trains WHERE type_train='Bisnis'"))['total'];
$total_ekonomi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM trains WHERE type_train='Ekonomi'"))['total'];
$total_seats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount_seat) as total FROM trains"))['total'];

// LIST TRAINS
$trains = mysqli_query($conn, "SELECT * FROM trains ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trains Management - GOTIX Admin</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/train.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-train"></i>
                <h2>GO<span>TIX</span></h2>
            </div>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="users.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="train.php" class="menu-item active">
                <i class="fas fa-train"></i>
                <span>Trains</span>
            </a>
            <a href="routes.php" class="menu-item">
                <i class="fas fa-route"></i>
                <span>Routes</span>
            </a>
            <a href="schedules.php" class="menu-item ">
                <i class="fas fa-calendar-alt"></i>
                <span>Schedules</span>
            </a>
            <a href="schedule_train.php" class="menu-item ">
                <i class="fa-solid fa-calendar-week"></i>
                <span>Schedule-Train</span>
            </a>
            <a href="book.php" class="menu-item">
                <i class="fas fa-ticket-alt"></i>
                <span>Bookings</span>
            </a>
            <a href="payments.php" class="menu-item">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
            </a>
            <a href="tickets.php" class="menu-item">
                <i class="fas fa-receipt"></i>
                <span>Tickets</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="../logout.php" class="menu-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Trains Management</h1>
            </div>
            <div class="topbar-right">
                <div class="admin-info">
                    <div class="admin-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="admin-details">
                        <span class="admin-name"><?= htmlspecialchars($_SESSION['name']) ?></span>
                        <span class="admin-role">Administrator</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <div class="dashboard-container">
            <!-- Success Message -->
            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= $_SESSION['success_message'] ?></span>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php unset($_SESSION['success_message']); endif; ?>

            <!-- Stats -->
            <div class="stats-grid-train">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-train"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_trains) ?></h3>
                        <p>Total Kereta</p>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_eksekutif) ?></h3>
                        <p>Kelas Eksekutif</p>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_bisnis) ?></h3>
                        <p>Kelas Bisnis</p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_ekonomi) ?></h3>
                        <p>Kelas Ekonomi</p>
                    </div>
                </div>

                <div class="stat-card-wide">
                    <div class="stat-icon">
                        <i class="fas fa-chair"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_seats) ?></h3>
                        <p>Total Kapasitas Kursi</p>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="content-card form-card">
                <div class="card-header">
                    <h2>
                        <i class="fas <?= $edit_train ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                        <?= $edit_train ? 'Edit Kereta' : 'Tambah Kereta Baru' ?>
                    </h2>
                    <?php if ($edit_train): ?>
                    <a href="train.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="train-form">
                        <?php if ($edit_train): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $edit_train['id'] ?>">
                        <?php else: ?>
                        <input type="hidden" name="action" value="create">
                        <?php endif; ?>

                        <div class="form-grid-train">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-train"></i>
                                    Nama Kereta
                                    <span class="required">*</span>
                                </label>
                                <input type="text" name="name_train" required placeholder="Contoh: Argo Parahyangan"
                                    value="<?= $edit_train ? htmlspecialchars($edit_train['name_train']) : '' ?>">
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-tag"></i>
                                    Tipe/Kelas Kereta
                                    <span class="required">*</span>
                                </label>
                                <select name="type_train" required>
                                    <option value="">Pilih Tipe Kereta</option>
                                    <option value="Eksekutif" <?= ($edit_train && $edit_train['type_train']=='Eksekutif')
                                        ? 'selected' : '' ?>>
                                        Eksekutif
                                    </option>
                                    <option value="Bisnis" <?= ($edit_train && $edit_train['type_train']=='Bisnis') ?
                                        'selected' : '' ?>>
                                        Bisnis
                                    </option>
                                    <option value="Ekonomi" <?= ($edit_train && $edit_train['type_train']=='Ekonomi') ?
                                        'selected' : '' ?>>
                                        Ekonomi
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-chair"></i>
                                    Jumlah Kursi
                                    <span class="required">*</span>
                                </label>
                                <input type="number" name="amount_seat" required min="1" placeholder="Contoh: 300"
                                    value="<?= $edit_train ? htmlspecialchars($edit_train['amount_seat']) : '' ?>">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fas <?= $edit_train ? 'fa-save' : 'fa-plus-circle' ?>"></i>
                                <?= $edit_train ? 'Update Kereta' : 'Tambah Kereta' ?>
                            </button>
                            <?php if ($edit_train): ?>
                            <a href="train.php" class="btn-cancel-link">
                                <i class="fas fa-times"></i>
                                Batal
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table Card -->
            <div class="content-card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-list"></i>
                        Daftar Semua Kereta
                    </h2>
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Cari kereta...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table" id="trainsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Kereta</th>
                                    <th>Tipe/Kelas</th>
                                    <th>Kapasitas Kursi</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($trains) > 0): ?>
                                <?php while ($train = mysqli_fetch_assoc($trains)): ?>
                                <tr>
                                    <td><span class="train-id">#<?= str_pad($train['id'], 3, '0', STR_PAD_LEFT)
                                            ?></span></td>
                                    <td>
                                        <div class="train-info">
                                            <div class="train-icon-small">
                                                <i class="fas fa-train"></i>
                                            </div>
                                            <span
                                                class="train-name"><?= htmlspecialchars($train['name_train']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = '';
                                        $badge_icon = '';
                                        if ($train['type_train'] === 'Eksekutif') {
                                            $badge_class = 'badge-executive';
                                            $badge_icon = 'fa-star';
                                        } elseif ($train['type_train'] === 'Bisnis') {
                                            $badge_class = 'badge-business';
                                            $badge_icon = 'fa-gem';
                                        } else {
                                            $badge_class = 'badge-economy';
                                            $badge_icon = 'fa-wallet';
                                        }
                                        ?>
                                        <span class="badge <?= $badge_class ?>">
                                            <i class="fas <?= $badge_icon ?>"></i>
                                            <?= htmlspecialchars($train['type_train']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="seat-count">
                                            <i class="fas fa-chair"></i>
                                            <?= number_format($train['amount_seat']) ?> Kursi
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="train.php?edit=<?= $train['id'] ?>" class="btn-action btn-edit"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="train.php?delete=<?= $train['id'] ?>" class="btn-action btn-delete"
                                                title="Delete"
                                                onclick="return confirm('Yakin ingin menghapus kereta <?= htmlspecialchars($train['name_train']) ?>?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <i class="fas fa-inbox"></i>
                                        Belum ada data kereta
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    // Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
    });

    // Search Functionality
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('trainsTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();

        for (let row of rows) {
            const name = row.cells[1].textContent.toLowerCase();
            const type = row.cells[2].textContent.toLowerCase();

            if (name.includes(searchTerm) || type.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });

    // Auto-hide alert
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'slideOut 0.5s ease forwards';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    </script>
</body>

</html>