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
    $schedule_id = (int)$_POST['schedule_id'];
    $train_id = (int)$_POST['train_id'];
    $price = (float)$_POST['price'];
    
    mysqli_query($conn, "INSERT INTO schedule_train (schedule_id, train_id, price) VALUES ($schedule_id, $train_id, $price)");
    $_SESSION['success_message'] = "Assignment kereta ke jadwal berhasil ditambahkan!";
    header("Location: schedule_train.php");
    exit;
}

// HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    $schedule_id = (int)$_POST['schedule_id'];
    $train_id = (int)$_POST['train_id'];
    $price = (float)$_POST['price'];
    
    mysqli_query($conn, "UPDATE schedule_train SET schedule_id=$schedule_id, train_id=$train_id, price=$price WHERE id=$id");
    $_SESSION['success_message'] = "Assignment berhasil diupdate!";
    header("Location: schedule_train.php");
    exit;
}

// HANDLE DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM schedule_train WHERE id=$id");
    $_SESSION['success_message'] = "Assignment berhasil dihapus!";
    header("Location: schedule_train.php");
    exit;
}

// FETCH EDIT ITEM
$edit_st = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM schedule_train WHERE id=$id LIMIT 1");
    $edit_st = mysqli_fetch_assoc($res);
}

// STATISTICS
$total_assignments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM schedule_train"))['total'];
$total_schedules = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT schedule_id) as total FROM schedule_train"))['total'];
$total_trains = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT train_id) as total FROM schedule_train"))['total'];
$avg_price = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(price) as avg FROM schedule_train"))['avg'];

// FETCH SCHEDULES FOR DROPDOWN
$schedules = mysqli_query($conn, "
    SELECT s.id, r.origin, r.destination, s.departure_time, s.arrival_time
    FROM schedules s
    LEFT JOIN routes r ON s.route_id = r.id
    ORDER BY r.origin, s.departure_time
");

// FETCH TRAINS FOR DROPDOWN
$trains = mysqli_query($conn, "SELECT * FROM trains ORDER BY name_train");

// LIST SCHEDULE_TRAIN WITH FULL INFO
$schedule_trains = mysqli_query($conn, "
    SELECT 
        st.id,
        st.schedule_id,
        st.train_id,
        st.price,
        r.origin,
        r.destination,
        s.departure_time,
        s.arrival_time,
        t.name_train,
        t.type_train,
        t.amount_seat
    FROM schedule_train st
    LEFT JOIN schedules s ON st.schedule_id = s.id
    LEFT JOIN routes r ON s.route_id = r.id
    LEFT JOIN trains t ON st.train_id = t.id
    ORDER BY s.departure_time ASC, r.origin ASC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule-Train Assignment - GOTIX Admin</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/schedule_train.css">
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
            <a href="schedules.php" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Schedules</span>
            </a>
            <a href="schedule_train.php" class="menu-item active">
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
                <h1 class="page-title">Schedule-Train Assignment</h1>
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

            <!-- Info Box -->
            <div class="info-banner">
                <div class="info-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="info-content">
                    <h3>Tentang Schedule-Train Assignment</h3>
                    <p>Halaman ini untuk mengassign kereta ke jadwal yang sudah dibuat. Setiap jadwal bisa memiliki
                        beberapa kereta dengan harga berbeda sesuai kelas.</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid-st">
                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_assignments) ?></h3>
                        <p>Total Assignment</p>
                    </div>
                </div>

                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_schedules) ?></h3>
                        <p>Jadwal Terassign</p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-train"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_trains) ?></h3>
                        <p>Kereta Aktif</p>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Rp <?= number_format($avg_price, 0, ',', '.') ?></h3>
                        <p>Rata-rata Harga</p>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="content-card form-card">
                <div class="card-header">
                    <h2>
                        <i class="fas <?= $edit_st ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                        <?= $edit_st ? 'Edit Assignment' : 'Tambah Assignment Baru' ?>
                    </h2>
                    <?php if ($edit_st): ?>
                    <a href="schedule_train.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="st-form">
                        <?php if ($edit_st): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $edit_st['id'] ?>">
                        <?php else: ?>
                        <input type="hidden" name="action" value="create">
                        <?php endif; ?>

                        <div class="form-grid-st">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-calendar-alt"></i>
                                    Pilih Jadwal (Rute + Waktu)
                                    <span class="required">*</span>
                                </label>
                                <select name="schedule_id" required id="scheduleSelect">
                                    <option value="">-- Pilih Jadwal --</option>
                                    <?php 
                                    mysqli_data_seek($schedules, 0);
                                    while($schedule = mysqli_fetch_assoc($schedules)): 
                                    ?>
                                    <option value="<?= $schedule['id'] ?>"
                                        <?= ($edit_st && $edit_st['schedule_id'] == $schedule['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($schedule['origin']) ?> →
                                        <?= htmlspecialchars($schedule['destination']) ?> |
                                        <?= date('H:i', strtotime($schedule['departure_time'])) ?> -
                                        <?= date('H:i', strtotime($schedule['arrival_time'])) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="helper-text">
                                    <i class="fas fa-info-circle"></i>
                                    Pilih jadwal yang akan diassign kereta
                                </small>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-train"></i>
                                    Pilih Kereta
                                    <span class="required">*</span>
                                </label>
                                <select name="train_id" required id="trainSelect">
                                    <option value="">-- Pilih Kereta --</option>
                                    <?php 
                                    mysqli_data_seek($trains, 0);
                                    while($train = mysqli_fetch_assoc($trains)): 
                                    ?>
                                    <option value="<?= $train['id'] ?>"
                                        data-type="<?= htmlspecialchars($train['type_train']) ?>"
                                        data-seats="<?= $train['amount_seat'] ?>"
                                        <?= ($edit_st && $edit_st['train_id'] == $train['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($train['name_train']) ?>
                                        (<?= htmlspecialchars($train['type_train']) ?>
                                        - <?= $train['amount_seat'] ?> kursi)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="helper-text">
                                    <i class="fas fa-info-circle"></i>
                                    Kereta dengan kelas dan kapasitas
                                </small>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-money-bill-wave"></i>
                                    Harga Tiket (Rp)
                                    <span class="required">*</span>
                                </label>
                                <input type="number" name="price" placeholder="Contoh: 150000"
                                    value="<?= $edit_st ? htmlspecialchars($edit_st['price']) : '' ?>">
                                <small class="helper-text">
                                    <i class="fas fa-info-circle"></i>
                                    Harga tiket untuk assignment ini
                                </small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fas <?= $edit_st ? 'fa-save' : 'fa-plus-circle' ?>"></i>
                                <?= $edit_st ? 'Update Assignment' : 'Tambah Assignment' ?>
                            </button>
                            <?php if ($edit_st): ?>
                            <a href="schedule_train.php" class="btn-cancel-link">
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
                        Daftar Semua Assignment
                    </h2>
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Cari assignment...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table" id="stTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Rute</th>
                                    <th>Waktu</th>
                                    <th>Kereta</th>
                                    <th>Kelas</th>
                                    <th>Kapasitas</th>
                                    <th>Harga</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($schedule_trains) > 0): ?>
                                <?php while ($st = mysqli_fetch_assoc($schedule_trains)): ?>
                                <tr>
                                    <td>
                                        <span class="st-id">
                                            #<?= str_pad($st['id'], 4, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="route-display">
                                            <span class="route-origin"><?= htmlspecialchars($st['origin']) ?></span>
                                            <i class="fas fa-arrow-right route-arrow"></i>
                                            <span
                                                class="route-destination"><?= htmlspecialchars($st['destination']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="time-range">
                                            <span class="time-start">
                                                <i class="fas fa-clock"></i>
                                                <?= date('H:i', strtotime($st['departure_time'])) ?>
                                            </span>
                                            <span class="time-separator">—</span>
                                            <span class="time-end">
                                                <?= date('H:i', strtotime($st['arrival_time'])) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="train-info">
                                            <i class="fas fa-train train-icon"></i>
                                            <span class="train-name"><?= htmlspecialchars($st['name_train']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $class_color = '';
                                        if ($st['type_train'] === 'Eksekutif') $class_color = 'class-executive';
                                        elseif ($st['type_train'] === 'Bisnis') $class_color = 'class-business';
                                        else $class_color = 'class-economy';
                                        ?>
                                        <span class="class-badge <?= $class_color ?>">
                                            <?= htmlspecialchars($st['type_train']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="capacity-badge">
                                            <i class="fas fa-chair"></i>
                                            <?= $st['amount_seat'] ?> kursi
                                        </span>
                                    </td>
                                    <td>
                                        <div class="price-display">
                                            <span class="price-value">Rp
                                                <?= number_format($st['price'], 0, ',', '.') ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="schedule_train.php?edit=<?= $st['id'] ?>"
                                                class="btn-action btn-edit" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="schedule_train.php?delete=<?= $st['id'] ?>"
                                                class="btn-action btn-delete" title="Delete"
                                                onclick="return confirm('Yakin ingin menghapus assignment ini?\n\nRute: <?= htmlspecialchars($st['origin']) ?> → <?= htmlspecialchars($st['destination']) ?>\nKereta: <?= htmlspecialchars($st['name_train']) ?>')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <i class="fas fa-unlink"></i>
                                        Belum ada assignment
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
    const table = document.getElementById('stTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();

        for (let row of rows) {
            if (row.cells.length > 1) {
                const route = row.cells[1].textContent.toLowerCase();
                const train = row.cells[3].textContent.toLowerCase();
                const trainClass = row.cells[4].textContent.toLowerCase();

                if (route.includes(searchTerm) || train.includes(searchTerm) || trainClass.includes(
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

    // Price suggestion helper (visual hint only)
    const trainSelect = document.getElementById('trainSelect');
    const priceInput = document.querySelector('input[name="price"]');
    const priceHelperText = priceInput ? priceInput.nextElementSibling : null;

    if (trainSelect && priceInput && priceHelperText) {
        trainSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const trainType = selectedOption.getAttribute('data-type');

            // Show price suggestion as hint only
            let suggestedPrice = 0;
            if (trainType === 'Eksekutif') {
                suggestedPrice = 250000;
            } else if (trainType === 'Bisnis') {
                suggestedPrice = 150000;
            } else if (trainType === 'Ekonomi') {
                suggestedPrice = 75000;
            }

            if (suggestedPrice > 0) {
                // Update helper text with suggestion
                priceHelperText.innerHTML = `
                    <i class="fas fa-lightbulb"></i>
                    Saran harga untuk kelas ${trainType}: <strong>Rp ${suggestedPrice.toLocaleString('id-ID')}</strong> (Anda bebas menentukan harga)
                `;
                priceHelperText.style.color = '#f59e0b';
                priceHelperText.style.fontWeight = '600';

                // Visual highlight on input
                priceInput.style.borderColor = '#f59e0b';
                priceInput.style.background = '#fffbeb';

                // Reset after 3 seconds
                setTimeout(() => {
                    priceHelperText.innerHTML = `
                        <i class="fas fa-info-circle"></i>
                        Harga tiket untuk assignment ini
                    `;
                    priceHelperText.style.color = '';
                    priceHelperText.style.fontWeight = '';
                    priceInput.style.borderColor = '';
                    priceInput.style.background = '';
                }, 3000);
            }
        });

        // Optional: Click to use suggested price
        priceHelperText.addEventListener('click', function() {
            const match = this.textContent.match(/Rp ([\d.]+)/);
            if (match) {
                const suggestedValue = match[1].replace(/\./g, '');
                if (confirm('Gunakan harga yang disarankan?')) {
                    priceInput.value = suggestedValue;
                    priceInput.focus();
                }
            }
        });
    }
    </script>
</body>

</html>