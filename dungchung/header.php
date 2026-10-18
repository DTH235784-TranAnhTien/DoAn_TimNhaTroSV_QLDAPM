<?php
// Bật session để quản lý đăng nhập trên toàn hệ thống
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($tieuDeTrang) ? $tieuDeTrang : 'Nhà Trọ Sinh Viên AGU - ĐH An Giang'; ?></title>
    
    <!-- 1. CSS Bootstrap 5 (Giao diện chuẩn hiện đại) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- 2. Bootstrap Icons (Biểu tượng ngôi nhà, tìm kiếm, số điện thoại...) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- 3. CSS tự viết riêng của nhóm -->
    <link rel="stylesheet" href="/QLNhaTro/giaodien/css/style.css">
</head>
<body class="bg-light">

    <!-- Nhúng thanh menu điều hướng -->
    <?php include_once __DIR__ . '/thanhmenu.php'; ?>