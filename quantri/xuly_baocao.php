<?php
require_once __DIR__ . '/../cauhinh/auth.php';
require_once __DIR__ . '/../cauhinh/database.php';

yeuCauVaiTro('QuanTriVien');

$redirectUrl = '/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=fraud';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirectUrl");
    exit();
}

if (!kiemTraCsrfToken($_POST['csrf_token'] ?? '')) {
    datThongBao('danger', 'Phiên làm việc không hợp lệ hoặc đã hết hạn (lỗi CSRF). Vui lòng thử lại.');
    header("Location: $redirectUrl");
    exit();
}

$nd = nguoiDungHienTai();
$adminId  = (int)($nd['mannd'] ?? 0);
$maBaoCao = (int)($_POST['ma_baocao'] ?? 0);
$hanhDong = trim($_POST['hanh_dong'] ?? '');

if ($maBaoCao <= 0 || $hanhDong === '') {
    datThongBao('warning', 'Dữ liệu báo cáo gửi lên không hợp lệ.');
    header("Location: $redirectUrl");
    exit();
}

// Truy vấn thông tin báo cáo kèm MaPhong và MaChuTro liên quan
$stmtLay = $conn->prepare("
    SELECT b.MaBaoCao, b.MaPhong, b.TrangThai, n.MaChuTro
    FROM BaoCaoViPham b
    LEFT JOIN PhongTro p ON b.MaPhong = p.MaPhong
    LEFT JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
    WHERE b.MaBaoCao = ?
    LIMIT 1
");
$stmtLay->bind_param('i', $maBaoCao);
$stmtLay->execute();
$baoCao = $stmtLay->get_result()->fetch_assoc();

if (!$baoCao) {
    datThongBao('danger', "Không tìm thấy báo cáo vi phạm #$maBaoCao trong hệ thống.");
    header("Location: $redirectUrl");
    exit();
}

$maPhong  = (int)($baoCao['MaPhong'] ?? 0);
$maChuTro = (int)($baoCao['MaChuTro'] ?? 0);

// 1. Bỏ qua báo cáo không hợp lệ
if ($hanhDong === 'bo_qua') {
    $stmt = $conn->prepare("UPDATE BaoCaoViPham SET TrangThai = 'BoQua' WHERE MaBaoCao = ?");
    $stmt->bind_param('i', $maBaoCao);
    $stmt->execute();

    datThongBao('info', "Đã bỏ qua báo cáo vi phạm #$maBaoCao.");
    header("Location: $redirectUrl");
    exit();
}

// 2. Đánh dấu đã xử lý báo cáo
if ($hanhDong === 'da_xu_ly') {
    $stmt = $conn->prepare("UPDATE BaoCaoViPham SET TrangThai = 'DaXuLy' WHERE MaBaoCao = ?");
    $stmt->bind_param('i', $maBaoCao);
    $stmt->execute();

    datThongBao('success', "Đã đánh dấu xử lý xong báo cáo vi phạm #$maBaoCao.");
    header("Location: $redirectUrl");
    exit();
}

// 3. Xử lý báo cáo + Gỡ tin phòng vi phạm + Khóa tài khoản chủ trọ
if ($hanhDong === 'khoa_chu_tro') {
    if ($maChuTro > 0 && $maChuTro === $adminId) {
        datThongBao('danger', 'Không thể tự khóa tài khoản quản trị đang đăng nhập!');
        header("Location: $redirectUrl");
        exit();
    }

    $conn->begin_transaction();
    try {
        // Cập nhật trạng thái báo cáo thành DaXuLy
        $stmtBc = $conn->prepare("UPDATE BaoCaoViPham SET TrangThai = 'DaXuLy' WHERE MaBaoCao = ?");
        $stmtBc->bind_param('i', $maBaoCao);
        $stmtBc->execute();

        // Gỡ hiển thị phòng trọ bị báo cáo (chuyển TrangThaiDuyet -> TuChoi)
        if ($maPhong > 0) {
            $stmtPhong = $conn->prepare("UPDATE PhongTro SET TrangThaiDuyet = 'TuChoi' WHERE MaPhong = ?");
            $stmtPhong->bind_param('i', $maPhong);
            $stmtPhong->execute();
        }

        // Khóa tài khoản chủ trọ vi phạm
        if ($maChuTro > 0) {
            $stmtUser = $conn->prepare("UPDATE NguoiDung SET TrangThai = 'BiKhoa' WHERE MaNguoiDung = ? AND VaiTro = 'ChuTro'");
            $stmtUser->bind_param('i', $maChuTro);
            $stmtUser->execute();
        }

        $conn->commit();
        datThongBao('success', "Đã xử lý báo cáo #$maBaoCao, gỡ tin phòng #$maPhong và khóa tài khoản chủ trọ #$maChuTro.");
    } catch (Throwable $e) {
        $conn->rollback();
        datThongBao('danger', 'Có lỗi xảy ra khi xử lý báo cáo: ' . $e->getMessage());
    }

    header("Location: $redirectUrl");
    exit();
}

datThongBao('warning', 'Hành động xử lý báo cáo không được hỗ trợ.');
header("Location: $redirectUrl");
exit();