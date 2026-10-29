<?php
require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

yeuCauDangNhap();
$nd = nguoiDungHienTai();
$mand = (int)$nd['mannd'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maPhong = (int)($_POST['ma_phong'] ?? 0);
    $ngay    = trim($_POST['ngay'] ?? '');
    $gio     = trim($_POST['gio'] ?? '');
    $ghiChu  = trim($_POST['ghi_chu'] ?? '');

    if ($maPhong > 0 && $ngay && $gio) {
        $stmt = $conn->prepare("INSERT INTO LichHen (MaSinhVien, MaPhong, NgayHen, GioHen, GhiChu, TrangThai, NgayTao) VALUES (?, ?, ?, ?, ?, 'ChoDuyet', NOW())");
        $stmt->bind_param('iisss', $mand, $maPhong, $ngay, $gio, $ghiChu);
        $stmt->execute();
        $stmt->close();
        datThongBao('success', 'Dat lich xem phong thanh cong! Vui long doi chu tro xac nhan.');
    } else {
        datThongBao('danger', 'Vui long chon ngay va gio hen hop le.');
    }
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=' . $maPhong);
    exit();
}
header('Location: /DoAn_TimNhaTroSV_QLDAPM/danhsach.php');
exit();
