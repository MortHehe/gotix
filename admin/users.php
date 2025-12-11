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
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = in_array($_POST['role'], ['admin', 'user']) ? $_POST['role'] : 'user';
    
    mysqli_query($conn, "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$pass', '$role')");
    $_SESSION['success_message'] = "User berhasil ditambahkan!";
    header("Location: users.php");
    exit;
}

// HANDLE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = in_array($_POST['role'], ['admin', 'user']) ? $_POST['role'] : 'user';
    
    mysqli_query($conn, "UPDATE users SET name='$name', email='$email', role='$role' WHERE id=$id");
    
    if (!empty($_POST['password'])) {
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password='$pass' WHERE id=$id");
    }
    
    $_SESSION['success_message'] = "User berhasil diupdate!";
    header("Location: users.php");
    exit;
}

// HANDLE DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE id=$id");
    $_SESSION['success_message'] = "User berhasil dihapus!";
    header("Location: users.php");
    exit;
}

// FETCH EDIT ITEM
$edit_user = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = mysqli_query($conn, "SELECT * FROM users WHERE id=$id LIMIT 1");
    $edit_user = mysqli_fetch_assoc($res);
}

// STATISTICS
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='user'"))['total'];
$total_admins = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='admin'"))['total'];

// LIST USERS
$users = mysqli_query($conn, "SELECT id, name, email, role FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - GOTIX Admin</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/users.css">
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
            <a href="users.php" class="menu-item active">
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
                <h1 class="page-title">Users Management</h1>
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
            <div class="stats-grid-small">
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_users) ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_admins) ?></h3>
                        <p>Total Admins</p>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?= number_format($total_users + $total_admins) ?></h3>
                        <p>Total All Users</p>
                    </div>
                </div>
            </div>

            <!-- Form Card -->
            <div class="content-card form-card">
                <div class="card-header">
                    <h2>
                        <i class="fas <?= $edit_user ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                        <?= $edit_user ? 'Edit User' : 'Tambah User Baru' ?>
                    </h2>
                    <?php if ($edit_user): ?>
                    <a href="users.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="user-form">
                        <?php if ($edit_user): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">
                        <?php else: ?>
                        <input type="hidden" name="action" value="create">
                        <?php endif; ?>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>
                                    <i class="fas fa-user"></i>
                                    Nama Lengkap
                                    <span class="required">*</span>
                                </label>
                                <input type="text" name="name" required placeholder="Masukkan nama lengkap"
                                    value="<?= $edit_user ? htmlspecialchars($edit_user['name']) : '' ?>">
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-envelope"></i>
                                    Email
                                    <span class="required">*</span>
                                </label>
                                <input type="email" name="email" required placeholder="contoh@email.com"
                                    value="<?= $edit_user ? htmlspecialchars($edit_user['email']) : '' ?>">
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-lock"></i>
                                    Password
                                    <?php if ($edit_user): ?>
                                    <span class="optional">(kosongkan jika tidak ingin mengubah)</span>
                                    <?php else: ?>
                                    <span class="required">*</span>
                                    <?php endif; ?>
                                </label>
                                <input type="password" name="password" <?= $edit_user ? '' : 'required' ?>
                                    placeholder="Minimal 6 karakter">
                            </div>

                            <div class="form-group">
                                <label>
                                    <i class="fas fa-user-tag"></i>
                                    Role
                                    <span class="required">*</span>
                                </label>
                                <select name="role" required>
                                    <option value="user" <?= (!$edit_user || $edit_user['role']=='user') ? 'selected'
                                        : '' ?>>
                                        User (Pengguna)
                                    </option>
                                    <option value="admin" <?= ($edit_user && $edit_user['role']=='admin') ? 'selected'
                                        : '' ?>>
                                        Admin (Administrator)
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fas <?= $edit_user ? 'fa-save' : 'fa-plus-circle' ?>"></i>
                                <?= $edit_user ? 'Update User' : 'Tambah User' ?>
                            </button>
                            <?php if ($edit_user): ?>
                            <a href="users.php" class="btn-cancel-link">
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
                        Daftar Semua Users
                    </h2>
                    <div class="header-actions">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Cari user...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($users) > 0): ?>
                                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td><span class="user-id">#<?= str_pad($user['id'], 3, '0', STR_PAD_LEFT) ?></span>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar-small">
                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                            </div>
                                            <span><?= htmlspecialchars($user['name']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge badge-admin">
                                            <i class="fas fa-user-shield"></i> Admin
                                        </span>
                                        <?php else: ?>
                                        <span class="badge badge-user">
                                            <i class="fas fa-user"></i> User
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="users.php?edit=<?= $user['id'] ?>" class="btn-action btn-edit"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="users.php?delete=<?= $user['id'] ?>" class="btn-action btn-delete"
                                                title="Delete"
                                                onclick="return confirm('Yakin ingin menghapus user <?= htmlspecialchars($user['name']) ?>?')">
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
                                        Belum ada data user
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
    const table = document.getElementById('usersTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();

        for (let row of rows) {
            const name = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();
            const role = row.cells[3].textContent.toLowerCase();

            if (name.includes(searchTerm) || email.includes(searchTerm) || role.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });

    // Auto-hide alert after 5 seconds
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