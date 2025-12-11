<?php
session_start();
include '../includes/db.php';

// CEK LOGIN
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// CEK ROLE ADMIN
if ($_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit;
}

// AMBIL DATA STATISTIK
$stats = [];

// Total Users
$users_query = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stats['users'] = $users_query->fetch_assoc()['total'];

// Total Trains
$trains_query = $conn->query("SELECT COUNT(*) as total FROM trains");
$stats['trains'] = $trains_query->fetch_assoc()['total'];

// Total Routes
$routes_query = $conn->query("SELECT COUNT(*) as total FROM routes");
$stats['routes'] = $routes_query->fetch_assoc()['total'];

// Total Bookings
$bookings_query = $conn->query("SELECT COUNT(*) as total FROM book");
$stats['bookings'] = $bookings_query->fetch_assoc()['total'];

// Pending Payments
$pending_query = $conn->query("SELECT COUNT(*) as total FROM payment WHERE payment_status = 'pending'");
$stats['pending_payments'] = $pending_query->fetch_assoc()['total'];

// Success Payments
$success_query = $conn->query("SELECT COUNT(*) as total FROM payment WHERE payment_status = 'success'");
$stats['success_payments'] = $success_query->fetch_assoc()['total'];

// Total Revenue
$revenue_query = $conn->query("SELECT SUM(b.amount_price) as total FROM book b 
                               INNER JOIN payment p ON b.id = p.book_id 
                               WHERE p.payment_status = 'success'");
$revenue_result = $revenue_query->fetch_assoc();
$stats['revenue'] = $revenue_result['total'] ? $revenue_result['total'] : 0;

// Recent Bookings (5 latest)
$recent_bookings = $conn->query("
    SELECT b.id, u.name as user_name, b.date_book, b.amount_ticket, 
           b.amount_price, b.payment_status, st.price,
           s.departure_time, r.origin, r.destination, t.name_train
    FROM book b
    INNER JOIN users u ON b.user_id = u.id
    INNER JOIN schedule_train st ON b.schedule_train_id = st.id
    INNER JOIN schedules s ON st.schedule_id = s.id
    INNER JOIN routes r ON s.route_id = r.id
    INNER JOIN trains t ON st.train_id = t.id
    ORDER BY b.date_book DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - GOTIX</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-train"></i>
                <h2>GO<span>TIX</span></h2>
            </div>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
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
                <h1 class="page-title">Dashboard Overview</h1>
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

        <!-- Dashboard Content -->
        <div class="dashboard-container">
            <!-- Stats Grid -->
            <div class="stats-grid">
                <!-- Total Users -->
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($stats['users']) ?></h3>
                        <p>Total Users</p>
                    </div>
                    <div class="stat-badge">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                </div>

                <!-- Total Trains -->
                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-train"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($stats['trains']) ?></h3>
                        <p>Total Trains</p>
                    </div>
                    <div class="stat-badge">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>

                <!-- Total Routes -->
                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fas fa-route"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($stats['routes']) ?></h3>
                        <p>Total Routes</p>
                    </div>
                    <div class="stat-badge">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                </div>

                <!-- Total Bookings -->
                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($stats['bookings']) ?></h3>
                        <p>Total Bookings</p>
                    </div>
                    <div class="stat-badge">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>

            <!-- Payment Stats -->
            <div class="payment-stats">
                <div class="stat-card-wide success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($stats['success_payments']) ?></h3>
                        <p>Success Payments</p>
                    </div>
                </div>

                <div class="stat-card-wide warning">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($stats['pending_payments']) ?></h3>
                        <p>Pending Payments</p>
                    </div>
                </div>

                <div class="stat-card-wide revenue">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Rp <?= number_format($stats['revenue'], 0, ',', '.') ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="content-card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Recent Bookings</h2>
                    <a href="book.php" class="btn-view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Route</th>
                                    <th>Train</th>
                                    <th>Date</th>
                                    <th>Tickets</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_bookings->num_rows > 0): ?>
                                <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $booking['id'] ?></td>
                                    <td><?= htmlspecialchars($booking['user_name']) ?></td>
                                    <td><?= htmlspecialchars($booking['origin']) ?> →
                                        <?= htmlspecialchars($booking['destination']) ?></td>
                                    <td><?= htmlspecialchars($booking['name_train']) ?></td>
                                    <td><?= date('d M Y', strtotime($booking['date_book'])) ?></td>
                                    <td><?= $booking['amount_ticket'] ?> pcs</td>
                                    <td>Rp <?= number_format($booking['amount_price'], 0, ',', '.') ?></td>
                                    <td>
                                        <?php if ($booking['payment_status'] === 'paid'): ?>
                                        <span class="badge success">Paid</span>
                                        <?php elseif ($booking['payment_status'] === 'pending'): ?>
                                        <span class="badge warning">Pending</span>
                                        <?php else: ?>
                                        <span class="badge danger">Failed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No bookings found</td>
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

    // Auto-update time
    function updateTime() {
        const now = new Date();
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        document.querySelectorAll('.current-time').forEach(el => {
            el.textContent = now.toLocaleDateString('id-ID', options);
        });
    }
    updateTime();
    setInterval(updateTime, 60000);
    </script>
</body>

</html>