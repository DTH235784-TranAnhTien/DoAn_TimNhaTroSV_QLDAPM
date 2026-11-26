<?php
/**
 * chutro/doitrangthai.php — Chuyển nhanh trạng thái phòng (Còn phòng / Đã thuê / Bảo trì)
 * Phân hệ Chủ trọ — CampusNest AGU
 */
require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

// Xác định đường dẫn gốc linh hoạt
$base_url = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
if ($base_url === '' || $base_url === '/') {
    $base_url = strpos($_SERVER['REQUEST_URI'], '/QLNhaTro') !== false ? '/QLNhaTro' : '/DoAn_TimNhaTroSV_QLDAPM';
}

if (!daDangNhap()) {
    header("Location: {$base_url}/taikhoan/dangnhap.php");
    exit();
}

$nd = nguoiDungHienTai();
$mand = (int)($nd['mannd'] ?? 0);
$vaitro = $nd['vaitro'] ?? '';

if (!in_array($vaitro, ['ChuTro', 'QuanTriVien'])) {
    header("Location: {$base_url}/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maPhong = (int)($_POST['ma_phong'] ?? 0);
    $trangThaiMoi = trim($_POST['trang_thai'] ?? '');

    $hopLe = ['ConPhong', 'DaThue', 'DangBaoTri'];
    if (!in_array($trangThaiMoi, $hopLe)) {
        datThongBao('danger', 'Trạng thái không hợp lệ.');
        header("Location: {$base_url}/chutro/danhsachphong.php");
        exit();
    }

    // Kiểm tra quyền sở hữu
    if ($vaitro === 'QuanTriVien') {
        $stmtCheck = $conn->prepare("SELECT p.MaPhong, p.TenPhong FROM PhongTro p WHERE p.MaPhong = ?");
        $stmtCheck->bind_param('i', $maPhong);
    } else {
        $stmtCheck = $conn->prepare("
            SELECT p.MaPhong, p.TenPhong 
            FROM PhongTro p 
            JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro 
            WHERE p.MaPhong = ? AND n.MaChuTro = ?
        ");
        $stmtCheck->bind_param('ii', $maPhong, $mand);
    }

    $stmtCheck->execute();
    $phong = $stmtCheck->get_result()->fetch_assoc();
    $stmtCheck->close();

    if (!$phong) {
        datThongBao('danger', 'Bạn không có quyền chỉnh sửa phòng trọ này.');
        header("Location: {$base_url}/chutro/danhsachphong.php");
        exit();
    }

    $stmtUpdate = $conn->prepare("UPDATE PhongTro SET TrangThai = ?, NgayCapNhat = NOW() WHERE MaPhong = ?");
    $stmtUpdate->bind_param('si', $trangThaiMoi, $maPhong);

    if ($stmtUpdate->execute()) {
        $tenTT = [
            'ConPhong'   => 'Còn phòng trống',
            'DaThue'     => 'Đã cho thuê',
            'DangBaoTri' => 'Đang bảo trì'
        ];
        datThongBao('success', "Đã đổi trạng thái phòng \"{$phong['TenPhong']}\" sang \"{$tenTT[$trangThaiMoi]}\"!");
    } else {
        datThongBao('danger', 'Lỗi khi cập nhật trạng thái: ' . $conn->error);
    }
    $stmtUpdate->close();
}

// Chuyển hướng về trang trước hoặc trang danh sách phòng
$redirect = $_SERVER['HTTP_REFERER'] ?? "{$base_url}/chutro/danhsachphong.php";
header("Location: {$redirect}");
exit();
