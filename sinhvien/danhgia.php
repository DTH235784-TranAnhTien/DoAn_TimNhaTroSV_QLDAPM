<?php
/**
 * sinhvien/danhgia.php — Xu ly POST danh gia phong tro
 * CampusNest AGU — Do an QLDAPM
 *
 * Chi nhan POST, khong render HTML.
 * Sau moi ket qua: datThongBao() + redirect ve chitiet.php?id=MaPhong
 */

require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

// Yeu cau phai la SinhVien
yeuCauVaiTro('SinhVien');

// Neu khong phai POST, chuyen ve danh sach
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/sinhvien/danhsach.php');
    exit();
}

// -----------------------------------------------------------------------
// Lay thong tin sinh vien dang dang nhap
// -----------------------------------------------------------------------
$nguoiDung = nguoiDungHienTai();
$mand      = (int) $nguoiDung['mannd'];

// -----------------------------------------------------------------------
// Nhan va lam sach du lieu POST
// -----------------------------------------------------------------------
$maPhong = isset($_POST['ma_phong']) ? (int) $_POST['ma_phong'] : 0;
$soSao   = isset($_POST['so_sao'])   ? (int) $_POST['so_sao']   : 0;
$anNinh  = isset($_POST['an_ninh'])  ? (int) $_POST['an_ninh']  : 0;
$giaCa   = isset($_POST['gia_ca'])   ? (int) $_POST['gia_ca']   : 0;
$thaiDo  = isset($_POST['thai_do'])  ? (int) $_POST['thai_do']  : 0;
$nhanXet = isset($_POST['nhan_xet']) ? trim($_POST['nhan_xet']) : '';

// URL redirect fallback neu maPhong khong hop le
$redirectUrl = '/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=' . $maPhong;

// -----------------------------------------------------------------------
// Validate du lieu co ban
// -----------------------------------------------------------------------
if ($maPhong <= 0 || $nhanXet === '') {
    datThongBao('warning', 'Vui lòng điền đầy đủ thông tin đánh giá.');
    header('Location: ' . $redirectUrl);
    exit();
}

$validRange = [1, 2, 3, 4, 5];

if (!in_array($soSao, $validRange, true)
    || !in_array($anNinh, $validRange, true)
    || !in_array($giaCa,  $validRange, true)
    || !in_array($thaiDo, $validRange, true)
) {
    datThongBao('warning', 'Điểm đánh giá không hợp lệ.');
    header('Location: ' . $redirectUrl);
    exit();
}

if (mb_strlen($nhanXet, 'UTF-8') > 1000) {
    datThongBao('warning', 'Vui lòng điền đầy đủ thông tin đánh giá.');
    header('Location: ' . $redirectUrl);
    exit();
}

// -----------------------------------------------------------------------
// Kiem tra sinh vien co lich hen DaXacNhan hoac HoanThanh voi phong nay khong
// -----------------------------------------------------------------------
$stmtLH = $conn->prepare(
    "SELECT COUNT(*) FROM LichHen WHERE MaSinhVien = ? AND MaPhong = ? AND TrangThai IN ('DaXacNhan', 'HoanThanh')"
);
$stmtLH->bind_param('ii', $mand, $maPhong);
$stmtLH->execute();
$stmtLH->bind_result($soLichHen);
$stmtLH->fetch();
$stmtLH->close();

if ($soLichHen < 1) {
    datThongBao('warning', 'Bạn cần có lịch hẹn xem phòng đã xác nhận hoặc hoàn thành để đánh giá.');
    header('Location: ' . $redirectUrl);
    exit();
}

// -----------------------------------------------------------------------
// Kiem tra da danh gia phong nay chua (chong duplicate)
// -----------------------------------------------------------------------
$stmtDup = $conn->prepare(
    'SELECT COUNT(*) FROM DanhGia WHERE MaSinhVien = ? AND MaPhong = ?'
);
$stmtDup->bind_param('ii', $mand, $maPhong);
$stmtDup->execute();
$stmtDup->bind_result($soDanhGia);
$stmtDup->fetch();
$stmtDup->close();

if ($soDanhGia > 0) {
    datThongBao('info', 'Bạn đã đánh giá phòng này trước đây.');
    header('Location: ' . $redirectUrl);
    exit();
}

// -----------------------------------------------------------------------
// INSERT danh gia moi (chuẩn hóa đúng tên cột trong DB QuanLyNhaTro)
// -----------------------------------------------------------------------
$stmtIns = $conn->prepare(
    'INSERT INTO DanhGia (MaPhong, MaSinhVien, SoSao, DiemAnNinh, DiemGiaCa, DiemThaiDoChuTro, NoiDung, NgayDanhGia)
     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
);
$stmtIns->bind_param(
    'iiiiiss',
    $maPhong,
    $mand,
    $soSao,
    $anNinh,
    $giaCa,
    $thaiDo,
    $nhanXet
);

if ($stmtIns->execute()) {
    datThongBao('success', 'Đánh giá của bạn đã được ghi nhận. Cảm ơn!');
} else {
    datThongBao('danger', 'Vui lòng điền đầy đủ thông tin đánh giá.');
}
$stmtIns->close();

header('Location: ' . $redirectUrl);
exit();
