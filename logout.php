<?php
session_start();

// Hapus semua session USER
session_unset();
session_destroy();

// Redirect ke halaman login user
header("Location: login.php");
exit;
?>