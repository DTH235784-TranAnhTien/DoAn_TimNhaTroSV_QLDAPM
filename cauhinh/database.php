<?php
/**
 * database.php — Kết nối MySQL cho CampusNest AGU
 * Host: 127.0.0.1 (dùng IP thay vì 'localhost' để tránh lỗi IPv6 trên Windows)
 * Hỗ trợ tự động kết nối cả cổng 3307 (máy cá nhân) lẫn 3306 (mặc định XAMPP)
 */

$host     = "127.0.0.1";
$dbname   = "QuanLyNhaTro";
$username = "root";
$password = "";

// Tự động thử kết nối các cổng phổ biến của XAMPP (3307 hoặc 3306)
$ports = [3307, 3306];
$conn = null;

foreach ($ports as $p) {
    try {
        $c = @new mysqli($host, $username, $password, $dbname, $p);
        if (!$c->connect_error) {
            $conn = $c;
            break;
        }
    } catch (Throwable $e) {
        // Thử cổng tiếp theo
    }
}

if (!$conn || $conn->connect_error) {
    die("Kết nối database thất bại: Không thể kết nối tới MySQL trên cổng 3306 hoặc 3307.");
}

$conn->set_charset("utf8mb4");
