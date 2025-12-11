<?php
session_start();
include '../includes/db.php';

// CEK LOGIN & ROLE ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// HANDLE UPDATE STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    $status = in_array($_POST['payment_status'], ['pending', 'success', 'failed']) ? $_POST['payment_status'] : 'pending';
    $gateway_ref = mysqli_real_escape_string($conn, $_POST['payment_gateway_ref']);
    
    // Set payment date if status is success
    if ($status === 'success') {
        $payment_date = date('Y-m-d H:i:s');
        mysqli_query($conn, "UPDATE payment SET payment_status='$status', payment_date='$payment_date', payment_gateway_ref='$gateway_ref' WHERE id=$id");
        
        // Update booking status to 'paid'
        $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM payment WHERE id=$id"));
        if ($p) {
            $b_id = (int)$p['book_id'];
            mysqli_query($conn, "UPDATE book SET payment_status='paid' WHERE id=$b_id");
            
            // Generate ticket if not exists
            $exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM ticket WHERE book_id=$b_id"))['c'];
            if (!$exists) {
                $code = 'TCK' . date('Ymd') . rand(1000, 9999);
                $issued_at = date('Y-m-d H:i:s');
                mysqli_query($conn, "INSERT INTO ticket (book_id, ticket_code, issued_at) VALUES ($b_id, '$code', '$issued_at')");
            }
        }
    } else {
        mysqli_query($conn, "UPDATE payment SET payment_status='$status', payment_gateway_ref='$gateway_ref' WHERE id=$id");
    }
    
    $_SESSION['success_message'] = "Payment berhasil diupdate!";
    header("Location: payments.php");
    exit;
}

// FETCH EDIT ITEM
$edit_payment = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM payment WHERE id=$id LIMIT 1");
    $edit_payment = mysqli_fetch_assoc($res);
}

// STATISTICS
$total_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM payment"))['total'];
$pending_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM payment WHERE payment_status='pending'"))['total'];
$success_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM payment WHERE payment_status='success'"))['total'];
$failed_payments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM payment WHERE payment_status='failed'"))['total'];
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(b.amount_price) as total FROM payment p LEFT JOIN book b ON p.book_id=b.id WHERE p.payment_status='success'"))['total'] ?? 0;

// LIST PAYMENTS WITH DETAILS
$payments = mysqli_query($conn, "
    SELECT p.*, 
           b.user_id, 
           b.amount_price,
           b.amount_ticket,
           b.date_book,
           u.name AS user_name,
           u.email AS user_email,
           r.origin,
           r.destination,
           t.name_train,
           s.departure_time
    FROM payment p
    LEFT JOIN book b ON p.book_id = b.id
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN schedule_train st ON b.schedule_train_id = st.id
    LEFT JOIN schedules s ON st.schedule_id = s.id
    LEFT JOIN routes r ON s.route_id = r.id
    LEFT JOIN trains t ON st.train_id = t.id
    ORDER BY p.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments Management - GOTIX Admin</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/payment.css">
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
            <a href="payments.php" class="menu-item active">
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
                <h1 class="page-title">Payments Management</h1>
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
            <div class="stats-grid-payments">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_payments) ?></h3>
                        <p>Total Payment</p>
                    </div>
                </div>

                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($pending_payments) ?></h3>
                        <p>Pending</p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($success_payments) ?></h3>
                        <p>Success</p>
                    </div>
                </div>

                <div class="stat-card red">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($failed_payments) ?></h3>
                        <p>Failed</p>
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

            <!-- Form Card (Only show when editing) -->
            <?php if ($edit_payment): ?>
            <div class="content-card form-card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-edit"></i>
                        Update Payment #<?= str_pad($edit_payment['id'], 4, '0', STR_PAD_LEFT) ?>
                    </h2>
                    <a href="payments.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
                <div class="card-body">
                    <form method="POST" class="payment-form">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $edit_payment['id'] ?>">

                        <div class="form-grid-payments">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-credit-card"></i>
                                    Status Pembayaran
                                    <span class="required">*</span>
                                </label>
                                <select name="payment_status" required>
                                    <option value="pending"
                                        <?= $edit_payment['payment_status'] == 'pending' ? 'selected' : '' ?>>
                                        <i class="fas fa-clock"></i> Pending
                                    </option>
                                    <option value="success"
                                        <?= $edit_payment['payment_status'] == 'success' ? 'selected' : '' ?>>
                                        <i class="fas fa-check"></i> Success
                                    </option>
                                    <option value="failed"
                                        <?= $edit_payment['payment_status'] == 'failed' ? 'selected' : '' ?>>
                                        <i class="fas fa-times"></i> Failed
                                    </option>
                                </select>
                                <small class="helper-text">
                                    <i class="fas fa-info-circle"></i>
                                    Status 'Success' akan otomatis membuat tiket
                                </small>
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-receipt"></i>
                                    Gateway Reference
                                </label>
                                <input type="text" name="payment_gateway_ref"
                                    value="<?= htmlspecialchars($edit_payment['payment_gateway_ref']) ?>"
                                    placeholder="e.g., TRX123456789">
                                <small class="helper-text">
                                    <i class="fas fa-info-circle"></i>
                                    Nomor referensi dari payment gateway
                                </small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-save"></i>
                                Update Payment
                            </button>
                            <a href="payments.php" class="btn-cancel-link">
                                <i class="fas fa-times"></i>
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Table Card -->
            <div class="content-card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-list"></i>
                        Daftar Semua Payment
                    </h2>
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Cari payment...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table" id="paymentsTable">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Booking ID</th>
                                    <th>User</th>
                                    <th>Rute</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th>Tanggal Bayar</th>
                                    <th>Gateway Ref</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($payments) > 0): ?>
                                <?php while ($payment = mysqli_fetch_assoc($payments)): ?>
                                <tr>
                                    <td>
                                        <span class="payment-id">
                                            #<?= str_pad($payment['id'], 4, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="booking-id">
                                            #<?= str_pad($payment['book_id'], 4, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <span
                                                class="user-name"><?= htmlspecialchars($payment['user_name']) ?></span>
                                            <span
                                                class="user-email"><?= htmlspecialchars($payment['user_email']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="route-display">
                                            <span
                                                class="route-origin"><?= htmlspecialchars($payment['origin']) ?></span>
                                            <i class="fas fa-arrow-right route-arrow"></i>
                                            <span
                                                class="route-destination"><?= htmlspecialchars($payment['destination']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="price-display">
                                            <span class="price-amount">Rp
                                                <?= number_format($payment['amount_price'], 0, ',', '.') ?></span>
                                            <span class="price-tickets"><?= $payment['amount_ticket'] ?> tiket</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($payment['payment_status']) ?>">
                                            <i
                                                class="fas fa-<?= $payment['payment_status'] == 'success' ? 'check-circle' : ($payment['payment_status'] == 'pending' ? 'clock' : 'times-circle') ?>"></i>
                                            <?= ucfirst($payment['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($payment['payment_date']): ?>
                                        <div class="date-display">
                                            <i class="fas fa-calendar"></i>
                                            <span><?= date('d M Y', strtotime($payment['payment_date'])) ?></span>
                                        </div>
                                        <div class="time-display">
                                            <i class="fas fa-clock"></i>
                                            <span><?= date('H:i', strtotime($payment['payment_date'])) ?></span>
                                        </div>
                                        <?php else: ?>
                                        <span class="no-data">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($payment['payment_gateway_ref']): ?>
                                        <span class="gateway-ref">
                                            <i class="fas fa-receipt"></i>
                                            <?= htmlspecialchars($payment['payment_gateway_ref']) ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="no-data">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="payments.php?edit=<?= $payment['id'] ?>"
                                                class="btn-action btn-edit" title="Edit Status">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">
                                        <i class="fas fa-credit-card"></i>
                                        Belum ada data payment
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
    const table = document.getElementById('paymentsTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();

        for (let row of rows) {
            if (row.cells.length > 1) {
                const paymentId = row.cells[0].textContent.toLowerCase();
                const bookingId = row.cells[1].textContent.toLowerCase();
                const user = row.cells[2].textContent.toLowerCase();
                const route = row.cells[3].textContent.toLowerCase();
                const status = row.cells[5].textContent.toLowerCase();
                const gateway = row.cells[7].textContent.toLowerCase();

                if (paymentId.includes(searchTerm) || bookingId.includes(searchTerm) || user.includes(
                        searchTerm) || route.includes(searchTerm) || status.includes(searchTerm) ||
                    gateway.includes(searchTerm)) {
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