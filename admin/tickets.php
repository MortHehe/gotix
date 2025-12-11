<?php
session_start();
include '../includes/db.php';

// CEK LOGIN & ROLE ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// HANDLE DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM ticket WHERE id=$id");
    $_SESSION['success_message'] = "Tiket berhasil dihapus!";
    header("Location: tickets.php");
    exit;
}

// HANDLE REGENERATE CODE
if (isset($_GET['regen'])) {
    $id = (int)$_GET['regen'];
    $code = 'TCK' . date('Ymd') . rand(1000, 9999);
    $issued_at = date('Y-m-d H:i:s');
    mysqli_query($conn, "UPDATE ticket SET ticket_code='$code', issued_at='$issued_at' WHERE id=$id");
    $_SESSION['success_message'] = "Kode tiket berhasil di-regenerate!";
    header("Location: tickets.php");
    exit;
}

// STATISTICS
$total_tickets = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM ticket"))['total'];
$today_tickets = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM ticket WHERE DATE(issued_at) = CURDATE()"))['total'];
$total_passengers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(b.amount_ticket) as total FROM ticket t LEFT JOIN book b ON t.book_id=b.id"))['total'] ?? 0;
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(b.amount_price) as total FROM ticket t LEFT JOIN book b ON t.book_id=b.id"))['total'] ?? 0;

// LIST TICKETS WITH DETAILS
$tickets = mysqli_query($conn, "
    SELECT t.*, 
           b.user_id,
           b.amount_ticket,
           b.amount_price,
           b.date_book,
           u.name AS user_name,
           u.email AS user_email,
           r.origin,
           r.destination,
           tr.name_train,
           tr.type_train,
           s.departure_time
    FROM ticket t
    LEFT JOIN book b ON t.book_id = b.id
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN schedule_train st ON b.schedule_train_id = st.id
    LEFT JOIN schedules s ON st.schedule_id = s.id
    LEFT JOIN routes r ON s.route_id = r.id
    LEFT JOIN trains tr ON st.train_id = tr.id
    ORDER BY t.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets Management - GOTIX Admin</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/ticket.css">
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
            <a href="book.php" class="menu-item">
                <i class="fas fa-ticket-alt"></i>
                <span>Bookings</span>
            </a>
            <a href="payments.php" class="menu-item">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
            </a>
            <a href="tickets.php" class="menu-item active">
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
                <h1 class="page-title">Tickets Management</h1>
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
            <div class="stats-grid-tickets">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_tickets) ?></h3>
                        <p>Total Tiket</p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($today_tickets) ?></h3>
                        <p>Tiket Hari Ini</p>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_passengers) ?></h3>
                        <p>Total Penumpang</p>
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

            <!-- Table Card -->
            <div class="content-card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-list"></i>
                        Daftar Semua Tiket
                    </h2>
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Cari tiket...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table" id="ticketsTable">
                            <thead>
                                <tr>
                                    <th>Ticket ID</th>
                                    <th>Kode Tiket</th>
                                    <th>User</th>
                                    <th>Rute</th>
                                    <th>Kereta</th>
                                    <th>Keberangkatan</th>
                                    <th>Jumlah</th>
                                    <th>Total Harga</th>
                                    <th>Tanggal Terbit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($tickets) > 0): ?>
                                <?php while ($ticket = mysqli_fetch_assoc($tickets)): ?>
                                <tr>
                                    <td>
                                        <span class="ticket-id">
                                            #<?= str_pad($ticket['id'], 4, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="ticket-code-display">
                                            <i class="fas fa-qrcode"></i>
                                            <span
                                                class="ticket-code"><?= htmlspecialchars($ticket['ticket_code']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <span class="user-name"><?= htmlspecialchars($ticket['user_name']) ?></span>
                                            <span
                                                class="user-email"><?= htmlspecialchars($ticket['user_email']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="route-display">
                                            <span class="route-origin"><?= htmlspecialchars($ticket['origin']) ?></span>
                                            <i class="fas fa-arrow-right route-arrow"></i>
                                            <span
                                                class="route-destination"><?= htmlspecialchars($ticket['destination']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="train-info">
                                            <span
                                                class="train-name"><?= htmlspecialchars($ticket['name_train']) ?></span>
                                            <span
                                                class="train-type"><?= htmlspecialchars($ticket['type_train']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="departure-display">
                                            <i class="fas fa-plane-departure"></i>
                                            <span><?= date('d M Y', strtotime($ticket['departure_time'])) ?></span>
                                            <span
                                                class="departure-time"><?= date('H:i', strtotime($ticket['departure_time'])) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="passenger-badge">
                                            <i class="fas fa-user"></i>
                                            <?= $ticket['amount_ticket'] ?> orang
                                        </span>
                                    </td>
                                    <td>
                                        <span class="price-amount">Rp
                                            <?= number_format($ticket['amount_price'], 0, ',', '.') ?></span>
                                    </td>
                                    <td>
                                        <div class="date-display">
                                            <i class="fas fa-calendar"></i>
                                            <span><?= date('d M Y', strtotime($ticket['issued_at'])) ?></span>
                                        </div>
                                        <div class="time-display">
                                            <i class="fas fa-clock"></i>
                                            <span><?= date('H:i', strtotime($ticket['issued_at'])) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="tickets.php?regen=<?= $ticket['id'] ?>"
                                                class="btn-action btn-regen" title="Regenerate Code"
                                                onclick="return confirm('Regenerate kode tiket untuk <?= htmlspecialchars($ticket['user_name']) ?>?')">
                                                <i class="fas fa-sync-alt"></i>
                                            </a>
                                            <a href="tickets.php?delete=<?= $ticket['id'] ?>"
                                                class="btn-action btn-delete" title="Delete"
                                                onclick="return confirm('Yakin ingin menghapus tiket <?= htmlspecialchars($ticket['ticket_code']) ?>?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">
                                        <i class="fas fa-receipt"></i>
                                        Belum ada data tiket
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
    const table = document.getElementById('ticketsTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();

        for (let row of rows) {
            if (row.cells.length > 1) {
                const ticketId = row.cells[0].textContent.toLowerCase();
                const ticketCode = row.cells[1].textContent.toLowerCase();
                const user = row.cells[2].textContent.toLowerCase();
                const route = row.cells[3].textContent.toLowerCase();
                const train = row.cells[4].textContent.toLowerCase();

                if (ticketId.includes(searchTerm) || ticketCode.includes(searchTerm) || user.includes(
                        searchTerm) || route.includes(searchTerm) || train.includes(searchTerm)) {
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