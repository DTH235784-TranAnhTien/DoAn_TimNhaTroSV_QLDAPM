<?php
/**
 * ai_gannhan.php — AJAX endpoint: gọi Gemini API gợi ý nhãn cho phòng trọ
 */
require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';
require_once __DIR__ . '/../cauhinh/gemini_helper.php';

header('Content-Type: application/json; charset=utf-8');

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit();
}

// Kiểm tra đăng nhập
yeuCauDangNhap();
$nd = nguoiDungHienTai();

// Chỉ cho ChuTro hoặc QuanTriVien
if (!in_array($nd['vaitro'], ['ChuTro', 'QuanTriVien'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền sử dụng tính năng này.']);
    exit();
}

// Lấy mô tả phòng từ POST
$moTa = trim($_POST['mo_ta'] ?? '');
if (empty($moTa)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mô tả phòng.']);
    exit();
}

// Lấy toàn bộ nhãn danh mục (kèm từ khóa nhận diện) từ DB
$danhSachNhan = [];
$resNhan = $conn->query("SELECT MaNhan, TenNhan, TuKhoaNhanDien FROM NhanDanhMuc ORDER BY MaNhan ASC");
if ($resNhan) {
    $danhSachNhan = $resNhan->fetch_all(MYSQLI_ASSOC);
}

if (empty($danhSachNhan)) {
    echo json_encode(['success' => false, 'message' => 'Hệ thống chưa có nhãn danh mục nào.']);
    exit();
}

// Gọi Gemini
$maDeXuat = geminiGanNhan($moTa, $danhSachNhan);

if (empty($maDeXuat)) {
    echo json_encode(['success' => false, 'message' => 'AI không tìm được nhãn phù hợp. Hãy thử mô tả chi tiết hơn.']);
    exit();
}

// Trả về đầy đủ thông tin nhãn được đề xuất
$nhanDeXuat = array_values(array_filter($danhSachNhan, fn($n) => in_array($n['MaNhan'], $maDeXuat)));

echo json_encode([
    'success' => true,
    'nhan_de_xuat' => $nhanDeXuat,
]);
