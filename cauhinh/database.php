<?php
/**
 * database.php — Kết nối MySQL cho CampusNest AGU
 * Host: 127.0.0.1 (dùng IP thay vì 'localhost' để tránh lỗi IPv6 trên Windows)
 */

$host     = "127.0.0.1";
$port     = 3306;
$dbname   = "QuanLyNhaTro";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    // Hiển thị lỗi chi tiết khi debug, tắt ở production
    die("Kết nối database thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");