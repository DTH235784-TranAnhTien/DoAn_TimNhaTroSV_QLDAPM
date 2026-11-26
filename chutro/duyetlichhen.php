<?php
require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

yeuCauDangNhap();
$nd = nguoiDungHienTai();
$mand = (int)$nd['mannd'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maLich = (int)($_POST['ma_lich'] ?? 0);
    $hanhDong = $_POST['hanh_dong'] ?? '';
    $phanHoi = trim($_POST['phan_hoi'] ?? '');

    $ttMoi = ($hanhDong === 'XacNhan') ? 'DaXacNhan' : 'TuChoi';

    $stmt = $conn->prepare("
        UPDATE LichHen lh
        JOIN PhongTro p ON lh.MaPhong = p.MaPhong
        JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
        SET lh.TrangThai = ?, lh.PhanHoiChuTro = ?
        WHERE lh.MaLichHen = ? AND n.MaChuTro = ?
    ");
    $stmt->bind_param('ssii', $ttMoi, $phanHoi, $maLich, $mand);
    $stmt->execute();
    $stmt->close();

    $tb = ($ttMoi === 'DaXacNhan') ? 'Đã xác nhận lịch hẹn xem phòng!' : 'Đã từ chối lịch hẹn.';
    if (!empty($phanHoi)) {
        $tb .= ' Kèm phản hồi đến sinh viên.';
    }
    datThongBao('success', $tb);
}

$base_url = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
if ($base_url === '' || $base_url === '/') {
    $base_url = strpos($_SERVER['REQUEST_URI'], '/QLNhaTro') !== false ? '/QLNhaTro' : '/DoAn_TimNhaTroSV_QLDAPM';
}

$referer = $_SERVER['HTTP_REFERER'] ?? "{$base_url}/chutro.php";
header("Location: {$referer}");
exit();
