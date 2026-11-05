<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

// Kiểm tra đăng nhập
if (!daDangNhap()) {
    echo json_encode([
        'success'       => false,
        'require_login' => true,
        'message'       => 'Vui lòng đăng nhập để lưu phòng yêu thích.'
    ]);
    exit();
}

$nd   = nguoiDungHienTai();
$mand = (int)($nd['mannd'] ?? 0);

if ($mand <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Tài khoản không hợp lệ.'
    ]);
    exit();
}

// Lấy mã phòng từ POST (hỗ trợ cả form-data và json body)
$maPhong = (int)($_POST['ma_phong'] ?? 0);
if ($maPhong <= 0) {
    $input   = json_decode(file_get_contents('php://input'), true);
    $maPhong = (int)($input['ma_phong'] ?? 0);
}

if ($maPhong <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Mã phòng trọ không hợp lệ.'
    ]);
    exit();
}

// Kiểm tra phòng có tồn tại không
$stmtCheck = $conn->prepare("SELECT MaPhong FROM PhongTro WHERE MaPhong = ? LIMIT 1");
$stmtCheck->bind_param('i', $maPhong);
$stmtCheck->execute();
$checkRes = $stmtCheck->get_result();
if ($checkRes->num_rows === 0) {
    $stmtCheck->close();
    echo json_encode([
        'success' => false,
        'message' => 'Phòng trọ không tồn tại hoặc đã bị xóa.'
    ]);
    exit();
}
$stmtCheck->close();

// Kiểm tra phòng đã được lưu trong DanhSachYeuThich chưa
$stmtFav = $conn->prepare("SELECT MaYeuThich FROM DanhSachYeuThich WHERE MaSinhVien = ? AND MaPhong = ? LIMIT 1");
$stmtFav->bind_param('ii', $mand, $maPhong);
$stmtFav->execute();
$resFav = $stmtFav->get_result();
$daLuu  = $resFav->fetch_assoc();
$stmtFav->close();

if ($daLuu) {
    // Đã lưu -> Bỏ lưu (DELETE)
    $stmtDel = $conn->prepare("DELETE FROM DanhSachYeuThich WHERE MaSinhVien = ? AND MaPhong = ?");
    $stmtDel->bind_param('ii', $mand, $maPhong);
    $ok = $stmtDel->execute();
    $stmtDel->close();

    if ($ok) {
        echo json_encode([
            'success' => true,
            'action'  => 'removed',
            'message' => 'Đã bỏ lưu phòng khỏi danh sách yêu thích.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi máy chủ khi bỏ lưu phòng.'
        ]);
    }
} else {
    // Chưa lưu -> Thêm lưu (INSERT)
    $stmtIns = $conn->prepare("INSERT INTO DanhSachYeuThich (MaSinhVien, MaPhong, NgayLuu) VALUES (?, ?, NOW())");
    $stmtIns->bind_param('ii', $mand, $maPhong);
    $ok = $stmtIns->execute();
    $stmtIns->close();

    if ($ok) {
        echo json_encode([
            'success' => true,
            'action'  => 'added',
            'message' => 'Đã lưu phòng vào danh sách yêu thích!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi máy chủ khi lưu phòng.'
        ]);
    }
}
