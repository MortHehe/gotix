<?php
$conn = new mysqli("localhost", "root", "", "gotix");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>