<?php
require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

yeuCauDangNhap();
$nd = nguoiDungHienTai();
$mand = (int)$nd['mannd'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maLich = (int)($_POST['ma_lich'] ?? 0);
    $hanhDong = $_POST['hanh_dong'] ?? '';

    $ttMoi = ($hanhDong === 'XacNhan') ? 'DaXacNhan' : 'TuChoi';

    $stmt = $conn->prepare("
        UPDATE LichHen lh
        JOIN PhongTro p ON lh.MaPhong = p.MaPhong
        JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
        SET lh.TrangThai = ?
        WHERE lh.MaLichHen = ? AND n.MaChuTro = ?
    ");
    $stmt->bind_param('sii', $ttMoi, $maLich, $mand);
    $stmt->execute();
    $stmt->close();

    $tb = ($ttMoi === 'DaXacNhan') ? 'Đã xác nhận lịch hẹn xem phòng!' : 'Đã từ chối lịch hẹn.';
    datThongBao('success', $tb);
}

header('Location: /DoAn_TimNhaTroSV_QLDAPM/chutro.php');
exit();
