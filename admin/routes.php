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
    $origin = mysqli_real_escape_string($conn, $_POST['origin']);
    $destination = mysqli_real_escape_string($conn, $_POST['destination']);
    $time = (int)$_POST['time'];
    
    mysqli_query($conn, "INSERT INTO routes (origin, destination, time) VALUES ('$origin', '$destination', $time)");
    $_SESSION['success_message'] = "Rute berhasil ditambahkan!";
    header("Location: routes.php");
    exit;
}

// HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    $origin = mysqli_real_escape_string($conn, $_POST['origin']);
    $destination = mysqli_real_escape_string($conn, $_POST['destination']);
    $time = (int)$_POST['time'];
    
    mysqli_query($conn, "UPDATE routes SET origin='$origin', destination='$destination', time=$time WHERE id=$id");
    $_SESSION['success_message'] = "Rute berhasil diupdate!";
    header("Location: routes.php");
    exit;
}

// HANDLE DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM routes WHERE id=$id");
    $_SESSION['success_message'] = "Rute berhasil dihapus!";
    header("Location: routes.php");
    exit;
}

// FETCH EDIT ITEM
$edit_route = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM routes WHERE id=$id LIMIT 1");
    $edit_route = mysqli_fetch_assoc($res);
}

// STATISTICS
$total_routes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM routes"))['total'];
$total_cities_origin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT origin) as total FROM routes"))['total'];
$total_cities_destination = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT destination) as total FROM routes"))['total'];
$avg_duration = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(time) as avg FROM routes"))['avg'];

// LIST ROUTES
$routes = mysqli_query($conn, "SELECT * FROM routes ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Routes Management - GOTIX Admin</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/routes.css">
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
            <a href="train.php" class="menu-item">
                <i class="fas fa-train"></i>
                <span>Trains</span>
            </a>
            <a href="routes.php" class="menu-item active">
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
                <h1 class="page-title">Routes Management</h1>
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
            <div class="stats-grid-routes">
                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fas fa-route"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_routes) ?></h3>
                        <p>Total Rute</p>
                    </div>
                </div>

                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_cities_origin) ?></h3>
                        <p>Kota Keberangkatan</p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_cities_destination) ?></h3>
                        <p>Kota Tujuan</p>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($avg_duration, 0) ?></h3>
                        <p>Rata-rata Durasi (menit)</p>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="content-card form-card">
                <div class="card-header">
                    <h2>
                        <i class="fas <?= $edit_route ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                        <?= $edit_route ? 'Edit Rute' : 'Tambah Rute Baru' ?>
                    </h2>
                    <?php if ($edit_route): ?>
                    <a href="routes.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="route-form">
                        <?php if ($edit_route): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $edit_route['id'] ?>">
                        <?php else: ?>
                        <input type="hidden" name="action" value="create">
                        <?php endif; ?>

                        <div class="form-grid-routes">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-map-marker-alt"></i>
                                    Stasiun Keberangkatan
                                    <span class="required">*</span>
                                </label>
                                <input type="text" name="origin" required placeholder="Contoh: Jakarta"
                                    value="<?= $edit_route ? htmlspecialchars($edit_route['origin']) : '' ?>">
                            </div>

                            <div class="route-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-flag-checkered"></i>
                                    Stasiun Tujuan
                                    <span class="required">*</span>
                                </label>
                                <input type="text" name="destination" required placeholder="Contoh: Bandung"
                                    value="<?= $edit_route ? htmlspecialchars($edit_route['destination']) : '' ?>">
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-clock"></i>
                                    Durasi Perjalanan (menit)
                                    <span class="required">*</span>
                                </label>
                                <input type="number" name="time" required min="1" placeholder="Contoh: 180"
                                    value="<?= $edit_route ? htmlspecialchars($edit_route['time']) : '' ?>">
                                <small class="helper-text">
                                    <i class="fas fa-info-circle"></i>
                                    Masukkan dalam satuan menit (contoh: 180 menit = 3 jam)
                                </small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fas <?= $edit_route ? 'fa-save' : 'fa-plus-circle' ?>"></i>
                                <?= $edit_route ? 'Update Rute' : 'Tambah Rute' ?>
                            </button>
                            <?php if ($edit_route): ?>
                            <a href="routes.php" class="btn-cancel-link">
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
                        Daftar Semua Rute
                    </h2>
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Cari rute...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table" id="routesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Stasiun Keberangkatan</th>
                                    <th></th>
                                    <th>Stasiun Tujuan</th>
                                    <th>Durasi</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($routes) > 0): ?>
                                <?php while ($route = mysqli_fetch_assoc($routes)): ?>
                                <tr>
                                    <td><span class="route-id">#<?= str_pad($route['id'], 3, '0', STR_PAD_LEFT)
                                            ?></span></td>
                                    <td>
                                        <div class="location-info">
                                            <div class="location-icon origin">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </div>
                                            <span class="location-name"><?= htmlspecialchars($route['origin']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="route-arrow-cell">
                                            <i class="fas fa-arrow-right"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="location-info">
                                            <div class="location-icon destination">
                                                <i class="fas fa-flag-checkered"></i>
                                            </div>
                                            <span class="location-name"><?= htmlspecialchars($route['destination'])
                                                ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="duration-badge">
                                            <i class="fas fa-clock"></i>
                                            <?php
                                            $hours = floor($route['time'] / 60);
                                            $minutes = $route['time'] % 60;
                                            echo $hours . 'j ' . $minutes . 'm';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="routes.php?edit=<?= $route['id'] ?>" class="btn-action btn-edit"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="routes.php?delete=<?= $route['id'] ?>"
                                                class="btn-action btn-delete" title="Delete"
                                                onclick="return confirm('Yakin ingin menghapus rute <?= htmlspecialchars($route['origin']) ?> → <?= htmlspecialchars($route['destination']) ?>?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <i class="fas fa-inbox"></i>
                                        Belum ada data rute
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
    const table = document.getElementById('routesTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();

        for (let row of rows) {
            if (row.cells.length > 1) {
                const origin = row.cells[1].textContent.toLowerCase();
                const destination = row.cells[3].textContent.toLowerCase();

                if (origin.includes(searchTerm) || destination.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
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