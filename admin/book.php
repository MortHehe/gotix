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
    $user_id = (int)$_POST['user_id'];
    $schedule_train_id = (int)$_POST['schedule_train_id'];
    $date_book = date('Y-m-d H:i:s');
    $amount_ticket = (int)$_POST['amount_ticket'];
    
    // Get price from schedule_train
    $st = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price FROM schedule_train WHERE id=$schedule_train_id LIMIT 1"));
    $amount_price = $st ? $st['price'] * $amount_ticket : 0;
    
    mysqli_query($conn, "INSERT INTO book (user_id, schedule_train_id, date_book, amount_ticket, amount_price, payment_status) VALUES ($user_id, $schedule_train_id, '$date_book', $amount_ticket, $amount_price, 'pending')");
    $_SESSION['success_message'] = "Booking berhasil ditambahkan!";
    header("Location: book.php");
    exit;
}

// HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    $amount_ticket = (int)$_POST['amount_ticket'];
    $payment_status = in_array($_POST['payment_status'], ['pending', 'paid', 'failed']) ? $_POST['payment_status'] : 'pending';
    
    // Recalculate price
    $rec = mysqli_fetch_assoc(mysqli_query($conn, "SELECT schedule_train_id FROM book WHERE id=$id LIMIT 1"));
    $st_id = $rec['schedule_train_id'];
    $st = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price FROM schedule_train WHERE id=$st_id LIMIT 1"));
    $amount_price = $st ? $st['price'] * $amount_ticket : 0;
    
    mysqli_query($conn, "UPDATE book SET amount_ticket=$amount_ticket, amount_price=$amount_price, payment_status='$payment_status' WHERE id=$id");
    $_SESSION['success_message'] = "Booking berhasil diupdate!";
    header("Location: book.php");
    exit;
}

// HANDLE DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM book WHERE id=$id");
    $_SESSION['success_message'] = "Booking berhasil dihapus!";
    header("Location: book.php");
    exit;
}

// FETCH EDIT ITEM
$edit_booking = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM book WHERE id=$id LIMIT 1");
    $edit_booking = mysqli_fetch_assoc($res);
}

// STATISTICS
$total_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM book"))['total'];
$pending_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM book WHERE payment_status='pending'"))['total'];
$paid_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM book WHERE payment_status='paid'"))['total'];
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount_price) as total FROM book WHERE payment_status='paid'"))['total'] ?? 0;

// FETCH DATA FOR DROPDOWNS
$users = mysqli_query($conn, "SELECT id, name, email FROM users WHERE role='user' ORDER BY name");
$schedule_trains = mysqli_query($conn, "
    SELECT st.id, st.price, r.origin, r.destination, s.departure_time, t.name_train, t.type_train
    FROM schedule_train st
    LEFT JOIN schedules s ON st.schedule_id = s.id
    LEFT JOIN routes r ON s.route_id = r.id
    LEFT JOIN trains t ON st.train_id = t.id
    ORDER BY r.origin, s.departure_time
");

// LIST BOOKINGS WITH DETAILS
$bookings = mysqli_query($conn, "
    SELECT b.*, 
           u.name as user_name, 
           u.email as user_email,
           st.price as unit_price, 
           s.departure_time, 
           r.origin, 
           r.destination, 
           t.name_train,
           t.type_train
    FROM book b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN schedule_train st ON b.schedule_train_id = st.id
    LEFT JOIN schedules s ON st.schedule_id = s.id
    LEFT JOIN routes r ON s.route_id = r.id
    LEFT JOIN trains t ON st.train_id = t.id
    ORDER BY b.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings Management - GOTIX Admin</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/book.css">
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
            <a href="schedule_train.php" class="menu-item">
                <i class="fa-solid fa-calendar-week"></i>
                <span>Schedule-Train</span>
            </a>
            <a href="book.php" class="menu-item active">
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
                <h1 class="page-title">Bookings Management</h1>
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
            <div class="stats-grid-bookings">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_bookings) ?></h3>
                        <p>Total Booking</p>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($pending_bookings) ?></h3>
                        <p>Pending</p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($paid_bookings) ?></h3>
                        <p>Paid</p>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Rp <?= number_format($total_revenue, 0, ',', '.') ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="content-card form-card">
                <div class="card-header">
                    <h2>
                        <i class="fas <?= $edit_booking ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                        <?= $edit_booking ? 'Edit Booking' : 'Tambah Booking Baru' ?>
                    </h2>
                    <?php if ($edit_booking): ?>
                    <a href="book.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="booking-form">
                        <?php if ($edit_booking): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $edit_booking['id'] ?>">
                        <?php else: ?>
                        <input type="hidden" name="action" value="create">
                        <?php endif; ?>

                        <div class="form-grid-bookings">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-user"></i>
                                    Pilih User
                                    <span class="required">*</span>
                                </label>
                                <select name="user_id" required <?= $edit_booking ? 'disabled' : '' ?>>
                                    <option value="">-- Pilih User --</option>
                                    <?php 
                                    mysqli_data_seek($users, 0);
                                    while($user = mysqli_fetch_assoc($users)): 
                                    ?>
                                    <option value="<?= $user['id'] ?>"
                                        <?= ($edit_booking && $edit_booking['user_id'] == $user['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <?php if ($edit_booking): ?>
                                <input type="hidden" name="user_id" value="<?= $edit_booking['user_id'] ?>">
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-train"></i>
                                    Pilih Jadwal & Kereta
                                    <span class="required">*</span>
                                </label>
                                <select name="schedule_train_id" required <?= $edit_booking ? 'disabled' : '' ?>>
                                    <option value="">-- Pilih Jadwal & Kereta --</option>
                                    <?php 
                                    mysqli_data_seek($schedule_trains, 0);
                                    while($st = mysqli_fetch_assoc($schedule_trains)): 
                                    ?>
                                    <option value="<?= $st['id'] ?>"
                                        <?= ($edit_booking && $edit_booking['schedule_train_id'] == $st['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($st['origin']) ?> →
                                        <?= htmlspecialchars($st['destination']) ?>
                                        | <?= date('H:i', strtotime($st['departure_time'])) ?>
                                        | <?= htmlspecialchars($st['name_train']) ?>
                                        (<?= htmlspecialchars($st['type_train']) ?>)
                                        - Rp <?= number_format($st['price'], 0, ',', '.') ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <?php if ($edit_booking): ?>
                                <input type="hidden" name="schedule_train_id"
                                    value="<?= $edit_booking['schedule_train_id'] ?>">
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-hashtag"></i>
                                    Jumlah Tiket
                                    <span class="required">*</span>
                                </label>
                                <input type="number" name="amount_ticket" required min="1" max="10"
                                    value="<?= $edit_booking ? $edit_booking['amount_ticket'] : '1' ?>">
                                <small class="helper-text">
                                    <i class="fas fa-info-circle"></i>
                                    Minimal 1, maksimal 10 tiket
                                </small>
                            </div>

                            <?php if ($edit_booking): ?>
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-credit-card"></i>
                                    Status Pembayaran
                                    <span class="required">*</span>
                                </label>
                                <select name="payment_status" required>
                                    <option value="pending"
                                        <?= $edit_booking['payment_status'] == 'pending' ? 'selected' : '' ?>>
                                        Pending
                                    </option>
                                    <option value="paid"
                                        <?= $edit_booking['payment_status'] == 'paid' ? 'selected' : '' ?>>
                                        Paid
                                    </option>
                                    <option value="failed"
                                        <?= $edit_booking['payment_status'] == 'failed' ? 'selected' : '' ?>>
                                        Failed
                                    </option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fas <?= $edit_booking ? 'fa-save' : 'fa-plus-circle' ?>"></i>
                                <?= $edit_booking ? 'Update Booking' : 'Tambah Booking' ?>
                            </button>
                            <?php if ($edit_booking): ?>
                            <a href="book.php" class="btn-cancel-link">
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
                        Daftar Semua Booking
                    </h2>
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Cari booking...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table" id="bookingsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Rute</th>
                                    <th>Kereta</th>
                                    <th>Tanggal</th>
                                    <th>Tiket</th>
                                    <th>Total Harga</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($bookings) > 0): ?>
                                <?php while ($booking = mysqli_fetch_assoc($bookings)): ?>
                                <tr>
                                    <td>
                                        <span class="booking-id">
                                            #<?= str_pad($booking['id'], 4, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <span
                                                class="user-name"><?= htmlspecialchars($booking['user_name']) ?></span>
                                            <span
                                                class="user-email"><?= htmlspecialchars($booking['user_email']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="route-display">
                                            <span
                                                class="route-origin"><?= htmlspecialchars($booking['origin']) ?></span>
                                            <i class="fas fa-arrow-right route-arrow"></i>
                                            <span
                                                class="route-destination"><?= htmlspecialchars($booking['destination']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="train-info">
                                            <span
                                                class="train-name"><?= htmlspecialchars($booking['name_train']) ?></span>
                                            <span
                                                class="train-type"><?= htmlspecialchars($booking['type_train']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-display">
                                            <i class="fas fa-calendar"></i>
                                            <span><?= date('d M Y', strtotime($booking['date_book'])) ?></span>
                                        </div>
                                        <div class="time-display">
                                            <i class="fas fa-clock"></i>
                                            <span><?= date('H:i', strtotime($booking['date_book'])) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="ticket-badge">
                                            <i class="fas fa-ticket-alt"></i>
                                            <?= $booking['amount_ticket'] ?> tiket
                                        </span>
                                    </td>
                                    <td>
                                        <div class="price-display">
                                            <span class="price-amount">Rp
                                                <?= number_format($booking['amount_price'], 0, ',', '.') ?></span>
                                            <span class="price-unit">@ Rp
                                                <?= number_format($booking['unit_price'], 0, ',', '.') ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($booking['payment_status']) ?>">
                                            <i
                                                class="fas fa-<?= $booking['payment_status'] == 'paid' ? 'check-circle' : ($booking['payment_status'] == 'pending' ? 'clock' : 'times-circle') ?>"></i>
                                            <?= ucfirst($booking['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="book.php?edit=<?= $booking['id'] ?>" class="btn-action btn-edit"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="book.php?delete=<?= $booking['id'] ?>"
                                                class="btn-action btn-delete" title="Delete"
                                                onclick="return confirm('Yakin ingin menghapus booking #<?= $booking['id'] ?> atas nama <?= htmlspecialchars($booking['user_name']) ?>?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">
                                        <i class="fas fa-ticket-alt"></i>
                                        Belum ada data booking
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
    const table = document.getElementById('bookingsTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();

        for (let row of rows) {
            if (row.cells.length > 1) {
                const id = row.cells[0].textContent.toLowerCase();
                const user = row.cells[1].textContent.toLowerCase();
                const route = row.cells[2].textContent.toLowerCase();
                const train = row.cells[3].textContent.toLowerCase();
                const status = row.cells[7].textContent.toLowerCase();

                if (id.includes(searchTerm) || user.includes(searchTerm) || route.includes(searchTerm) ||
                    train.includes(searchTerm) || status.includes(searchTerm)) {
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