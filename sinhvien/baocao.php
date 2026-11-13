<?php
/**
 * sinhvien/baocao.php — Xử lý báo cáo vi phạm phòng trọ
 * CampusNest AGU — Đồ án QLDAPM
 */

require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

yeuCauDangNhap('Vui lòng đăng nhập để gửi báo cáo vi phạm.');

$nd   = nguoiDungHienTai();
$mand = (int)$nd['mannd'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/danhsach.php');
    exit();
}

$maPhong = (int)($_POST['ma_phong'] ?? 0);
$lyDo    = trim($_POST['ly_do'] ?? '');
$moTa    = trim($_POST['mo_ta'] ?? '');

$redirectUrl = '/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=' . $maPhong;

if ($maPhong <= 0 || empty($lyDo)) {
    datThongBao('warning', 'Vui lòng chọn lý do báo cáo vi phạm.');
    header('Location: ' . ($maPhong > 0 ? $redirectUrl : '/DoAn_TimNhaTroSV_QLDAPM/danhsach.php'));
    exit();
}

// Kiểm tra phòng có tồn tại không
$stmtCheck = $conn->prepare("SELECT MaPhong FROM PhongTro WHERE MaPhong = ? LIMIT 1");
$stmtCheck->bind_param('i', $maPhong);
$stmtCheck->execute();
$checkRes = $stmtCheck->get_result();
$tonTai = ($checkRes->num_rows > 0);
$stmtCheck->close();

if (!$tonTai) {
    datThongBao('danger', 'Phòng trọ được báo cáo không tồn tại.');
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/danhsach.php');
    exit();
}

// Lưu vào bảng BaoCaoViPham
$stmtIns = $conn->prepare(
    "INSERT INTO BaoCaoViPham (MaNguoiBaoCao, MaPhong, LyDo, ChiTiet, TrangThai, NgayBaoCao) VALUES (?, ?, ?, ?, 'ChoXuLy', NOW())"
);
$stmtIns->bind_param('iiss', $mand, $maPhong, $lyDo, $moTa);

if ($stmtIns->execute()) {
    datThongBao('success', 'Báo cáo vi phạm của bạn đã được ghi nhận và gửi đến Ban Quản trị kiểm duyệt.');
} else {
    datThongBao('danger', 'Có lỗi xảy ra khi gửi báo cáo vi phạm, vui lòng thử lại.');
}
$stmtIns->close();

header('Location: ' . $redirectUrl);
exit();

