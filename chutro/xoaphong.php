<?php
/**
 * chutro/xoaphong.php — Xử lý xóa phòng trọ an toàn
 * Phân hệ Chủ trọ — CampusNest AGU
 */
require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

// Xác định đường dẫn gốc linh hoạt (tương thích cả /QLNhaTro lẫn /DoAn_TimNhaTroSV_QLDAPM)
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

    if ($maPhong <= 0) {
        datThongBao('danger', 'Mã phòng trọ không hợp lệ.');
        header("Location: {$base_url}/chutro/danhsachphong.php");
        exit();
    }

    // 1. Kiểm tra quyền sở hữu phòng trọ
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
        datThongBao('danger', 'Bạn không có quyền xóa phòng trọ này hoặc phòng không tồn tại.');
        header("Location: {$base_url}/chutro/danhsachphong.php");
        exit();
    }

    $tenPhong = $phong['TenPhong'];

    // 2. Lấy danh sách ảnh để xóa file vật lý trên đĩa
    $stmtAnh = $conn->prepare("SELECT DuongDan FROM HinhAnhPhongTro WHERE MaPhong = ?");
    $stmtAnh->bind_param('i', $maPhong);
    $stmtAnh->execute();
    $resAnh = $stmtAnh->get_result();
    while ($rowAnh = $resAnh->fetch_assoc()) {
        $duongDan = $rowAnh['DuongDan'];
        if ($duongDan && strpos($duongDan, 'uploads/') !== false) {
            $filePath = __DIR__ . '/../' . ltrim($duongDan, '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }
    $stmtAnh->close();

    // 3. Xóa phòng trọ (ràng buộc ON DELETE CASCADE sẽ tự động xóa ảnh, tiện ích, lịch hẹn, đánh giá)
    $stmtXoa = $conn->prepare("DELETE FROM PhongTro WHERE MaPhong = ?");
    $stmtXoa->bind_param('i', $maPhong);
    
    if ($stmtXoa->execute()) {
        datThongBao('success', "Đã xóa phòng trọ \"{$tenPhong}\" thành công!");
    } else {
        datThongBao('danger', 'Có lỗi xảy ra khi xóa phòng trọ: ' . $conn->error);
    }
    $stmtXoa->close();
}

header("Location: {$base_url}/chutro/danhsachphong.php");
exit();
