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
    $route_id = (int)$_POST['route_id'];
    $departure = mysqli_real_escape_string($conn, $_POST['departure_time']);
    $arrival = mysqli_real_escape_string($conn, $_POST['arrival_time']);
    
    mysqli_query($conn, "INSERT INTO schedules (route_id, departure_time, arrival_time) VALUES ($route_id, '$departure', '$arrival')");
    $_SESSION['success_message'] = "Jadwal berhasil ditambahkan!";
    header("Location: schedules.php");
    exit;
}

// HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    $route_id = (int)$_POST['route_id'];
    $departure = mysqli_real_escape_string($conn, $_POST['departure_time']);
    $arrival = mysqli_real_escape_string($conn, $_POST['arrival_time']);
    
    mysqli_query($conn, "UPDATE schedules SET route_id=$route_id, departure_time='$departure', arrival_time='$arrival' WHERE id=$id");
    $_SESSION['success_message'] = "Jadwal berhasil diupdate!";
    header("Location: schedules.php");
    exit;
}

// HANDLE DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM schedules WHERE id=$id");
    $_SESSION['success_message'] = "Jadwal berhasil dihapus!";
    header("Location: schedules.php");
    exit;
}

// FETCH EDIT ITEM
$edit_schedule = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM schedules WHERE id=$id LIMIT 1");
    $edit_schedule = mysqli_fetch_assoc($res);
}

// STATISTICS
$total_schedules = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM schedules"))['total'];
$active_routes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT route_id) as total FROM schedules"))['total'];
$morning_schedules = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM schedules WHERE TIME(departure_time) < '12:00:00'"))['total'];
$evening_schedules = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM schedules WHERE TIME(departure_time) >= '12:00:00'"))['total'];

// FETCH ROUTES FOR DROPDOWN
$routes = mysqli_query($conn, "SELECT * FROM routes ORDER BY origin");

// LIST SCHEDULES WITH ROUTE INFO
$schedules = mysqli_query($conn, "
    SELECT s.*, r.origin, r.destination, r.time as duration_minutes
    FROM schedules s
    LEFT JOIN routes r ON s.route_id = r.id
    ORDER BY s.departure_time ASC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedules Management - GOTIX Admin</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/schedules.css">
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
            <a href="routes.php" class="menu-item">
                <i class="fas fa-route"></i>
                <span>Routes</span>
            </a>
            <a href="schedules.php" class="menu-item active">
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
                <h1 class="page-title">Schedules Management</h1>
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
            <div class="stats-grid-schedules">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_schedules) ?></h3>
                        <p>Total Jadwal</p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-route"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($active_routes) ?></h3>
                        <p>Rute Aktif</p>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-sun"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($morning_schedules) ?></h3>
                        <p>Jadwal Pagi</p>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fas fa-moon"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($evening_schedules) ?></h3>
                        <p>Jadwal Sore/Malam</p>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="content-card form-card">
                <div class="card-header">
                    <h2>
                        <i class="fas <?= $edit_schedule ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                        <?= $edit_schedule ? 'Edit Jadwal' : 'Tambah Jadwal Baru' ?>
                    </h2>
                    <?php if ($edit_schedule): ?>
                    <a href="schedules.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="schedule-form">
                        <?php if ($edit_schedule): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $edit_schedule['id'] ?>">
                        <?php else: ?>
                        <input type="hidden" name="action" value="create">
                        <?php endif; ?>

                        <div class="form-grid-schedules">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-route"></i>
                                    Pilih Rute
                                    <span class="required">*</span>
                                </label>
                                <select name="route_id" required>
                                    <option value="">-- Pilih Rute --</option>
                                    <?php 
                                    mysqli_data_seek($routes, 0);
                                    while($route = mysqli_fetch_assoc($routes)): 
                                    ?>
                                    <option value="<?= $route['id'] ?>"
                                        <?= ($edit_schedule && $edit_schedule['route_id'] == $route['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($route['origin']) ?> →
                                        <?= htmlspecialchars($route['destination']) ?>
                                        (<?= floor($route['time']/60) ?>j <?= $route['time']%60 ?>m)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-clock"></i>
                                    Waktu Keberangkatan
                                    <span class="required">*</span>
                                </label>
                                <input type="time" name="departure_time" required
                                    value="<?= $edit_schedule ? htmlspecialchars($edit_schedule['departure_time']) : '' ?>">
                                <small class="helper-text">
                                    <i class="fas fa-info-circle"></i>
                                    Format 24 jam (HH:MM)
                                </small>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-clock"></i>
                                    Waktu Tiba
                                    <span class="required">*</span>
                                </label>
                                <input type="time" name="arrival_time" required
                                    value="<?= $edit_schedule ? htmlspecialchars($edit_schedule['arrival_time']) : '' ?>">
                                <small class="helper-text">
                                    <i class="fas fa-info-circle"></i>
                                    Format 24 jam (HH:MM)
                                </small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fas <?= $edit_schedule ? 'fa-save' : 'fa-plus-circle' ?>"></i>
                                <?= $edit_schedule ? 'Update Jadwal' : 'Tambah Jadwal' ?>
                            </button>
                            <?php if ($edit_schedule): ?>
                            <a href="schedules.php" class="btn-cancel-link">
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
                        Daftar Semua Jadwal
                    </h2>
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Cari jadwal...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table" id="schedulesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Rute</th>
                                    <th>Keberangkatan</th>
                                    <th>Tiba</th>
                                    <th>Durasi</th>
                                    <th>Waktu</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($schedules) > 0): ?>
                                <?php while ($schedule = mysqli_fetch_assoc($schedules)): 
                                    $departure_hour = (int)date('H', strtotime($schedule['departure_time']));
                                    $time_period = $departure_hour < 12 ? 'morning' : ($departure_hour < 18 ? 'afternoon' : 'evening');
                                ?>
                                <tr>
                                    <td>
                                        <span class="schedule-id">
                                            #<?= str_pad($schedule['id'], 3, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="route-display">
                                            <span
                                                class="route-origin"><?= htmlspecialchars($schedule['origin']) ?></span>
                                            <i class="fas fa-arrow-right route-arrow"></i>
                                            <span
                                                class="route-destination"><?= htmlspecialchars($schedule['destination']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="time-display">
                                            <i class="fas fa-plane-departure"></i>
                                            <span><?= date('H:i', strtotime($schedule['departure_time'])) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="time-display">
                                            <i class="fas fa-plane-arrival"></i>
                                            <span><?= date('H:i', strtotime($schedule['arrival_time'])) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="duration-badge">
                                            <i class="fas fa-clock"></i>
                                            <?php
                                            $hours = floor($schedule['duration_minutes'] / 60);
                                            $minutes = $schedule['duration_minutes'] % 60;
                                            echo $hours . 'j ' . $minutes . 'm';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="time-badge time-<?= $time_period ?>">
                                            <i
                                                class="fas fa-<?= $time_period === 'morning' ? 'sun' : ($time_period === 'afternoon' ? 'cloud-sun' : 'moon') ?>"></i>
                                            <?= $time_period === 'morning' ? 'Pagi' : ($time_period === 'afternoon' ? 'Siang' : 'Malam') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="schedules.php?edit=<?= $schedule['id'] ?>"
                                                class="btn-action btn-edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="schedules.php?delete=<?= $schedule['id'] ?>"
                                                class="btn-action btn-delete" title="Delete"
                                                onclick="return confirm('Yakin ingin menghapus jadwal <?= htmlspecialchars($schedule['origin']) ?> → <?= htmlspecialchars($schedule['destination']) ?> (<?= date('H:i', strtotime($schedule['departure_time'])) ?>)?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <i class="fas fa-calendar-times"></i>
                                        Belum ada data jadwal
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
    const table = document.getElementById('schedulesTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();

        for (let row of rows) {
            if (row.cells.length > 1) {
                const route = row.cells[1].textContent.toLowerCase();
                const departure = row.cells[2].textContent.toLowerCase();
                const arrival = row.cells[3].textContent.toLowerCase();

                if (route.includes(searchTerm) || departure.includes(searchTerm) || arrival.includes(
                        searchTerm)) {
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

    // Form validation
    const scheduleForm = document.querySelector('.schedule-form');
    if (scheduleForm) {
        scheduleForm.addEventListener('submit', function(e) {
            const departureTime = document.querySelector('input[name="departure_time"]').value;
            const arrivalTime = document.querySelector('input[name="arrival_time"]').value;

            if (departureTime >= arrivalTime) {
                e.preventDefault();
                alert('⚠️ Waktu tiba harus lebih besar dari waktu keberangkatan!');
                return false;
            }
        });
    }
    </script>
</body>

</html>