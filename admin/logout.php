<?php
session_start();

// Hapus semua session ADMIN
session_unset();
session_destroy();

// Redirect ke halaman login admin / login biasa
header("Location: ../login.php");
exit;
?>