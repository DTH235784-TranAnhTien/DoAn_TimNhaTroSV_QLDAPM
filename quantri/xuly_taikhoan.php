<?php
require_once __DIR__ . '/../cauhinh/auth.php';
require_once __DIR__ . '/../cauhinh/database.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Không thể kết nối cơ sở dữ liệu.');
}

yeuCauVaiTro('QuanTriVien');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=users');
    exit();
}

if (!kiemTraCsrfToken($_POST['csrf_token'] ?? '')) {
    datThongBao('danger', 'Phiên làm việc không hợp lệ hoặc đã hết hạn (lỗi CSRF). Vui lòng thử lại.');
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=users');
    exit();
}

$nd = nguoiDungHienTai();
$adminId = (int)($nd['mannd'] ?? 0);
$hanhDong = $_POST['hanh_dong'] ?? '';

// 1. Khóa / Mở khóa tài khoản (HoatDong <-> BiKhoa)
if ($hanhDong === 'khoa_user') {
    $id = (int)($_POST['ma_user'] ?? 0);
    if ($id <= 0) {
        datThongBao('danger', 'Mã tài khoản không hợp lệ.');
    } elseif ($id === $adminId) {
        datThongBao('danger', 'Bạn không thể tự khóa tài khoản quản trị đang đăng nhập!');
    } else {
        $stmt = $conn->prepare("UPDATE NguoiDung SET TrangThai = IF(TrangThai = 'HoatDong', 'BiKhoa', 'HoatDong') WHERE MaNguoiDung = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        datThongBao('success', "Đã cập nhật trạng thái hoạt động cho tài khoản #$id.");
    }
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=users');
    exit();
}

// 2. Xác thực / Hủy xác thực chủ trọ chính chủ (DaXacThucChinhChu)
if ($hanhDong === 'xac_thuc_chu_tro') {
    $id = (int)($_POST['ma_user'] ?? 0);
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE NguoiDung SET DaXacThucChinhChu = NOT DaXacThucChinhChu WHERE MaNguoiDung = ? AND VaiTro = 'ChuTro'");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            datThongBao('success', "Đã cập nhật trạng thái xác thực chính chủ cho chủ trọ #$id.");
        } else {
            datThongBao('warning', "Không thể cập nhật (tài khoản không phải là Chủ trọ).");
        }
    }
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=users');
    exit();
}

// 3. Xóa tài khoản
if ($hanhDong === 'xoa_user') {
    $id = (int)($_POST['ma_user'] ?? 0);
    if ($id <= 0) {
        datThongBao('danger', 'Mã tài khoản không hợp lệ.');
    } elseif ($id === $adminId) {
        datThongBao('danger', 'Không thể xóa tài khoản của chính bạn đang đăng nhập!');
    } else {
        $stmt = $conn->prepare("DELETE FROM NguoiDung WHERE MaNguoiDung = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        datThongBao('info', "Đã xóa tài khoản #$id khỏi hệ thống.");
    }
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=users');
    exit();
}

// 4. Thêm mới hoặc Cập nhật tài khoản
if ($hanhDong === 'luu_user') {
    $id        = (int)($_POST['ma_user'] ?? 0);
    $hoten     = trim($_POST['hoten'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $sdt       = trim($_POST['sdt'] ?? '');
    $vaitro    = $_POST['vaitro'] ?? 'SinhVien';
    $trangthai = $_POST['trangthai'] ?? 'HoatDong';
    $xacthuc   = isset($_POST['da_xac_thuc']) ? 1 : 0;

    $dsVaiTroHopLe = ['SinhVien', 'ChuTro', 'QuanTriVien'];
    $dsTrangThaiHopLe = ['HoatDong', 'BiKhoa'];

    if ($hoten === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        datThongBao('danger', 'Vui lòng nhập đầy đủ họ tên và địa chỉ email hợp lệ.');
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=users');
        exit();
    }

    if (!in_array($vaitro, $dsVaiTroHopLe, true)) {
        $vaitro = 'SinhVien';
    }
    if (!in_array($trangthai, $dsTrangThaiHopLe, true)) {
        $trangthai = 'HoatDong';
    }
    if ($vaitro !== 'ChuTro') {
        $xacthuc = 0;
    }

    // Kiểm tra trùng Email
    $chk = $conn->prepare("SELECT MaNguoiDung FROM NguoiDung WHERE Email = ? AND MaNguoiDung != ?");
    $chk->bind_param('si', $email, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        datThongBao('danger', "Email '$email' đã được sử dụng bởi tài khoản khác.");
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=users');
        exit();
    }

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE NguoiDung SET HoTen = ?, Email = ?, SoDienThoai = ?, VaiTro = ?, TrangThai = ?, DaXacThucChinhChu = ? WHERE MaNguoiDung = ?");
        $stmt->bind_param('sssssii', $hoten, $email, $sdt, $vaitro, $trangthai, $xacthuc, $id);
        $stmt->execute();
        datThongBao('success', "Đã cập nhật thông tin tài khoản $hoten!");
    } else {
        $mk = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO NguoiDung (HoTen, Email, SoDienThoai, MatKhau, VaiTro, TrangThai, DaXacThucChinhChu, NgayTao) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('ssssssi', $hoten, $email, $sdt, $mk, $vaitro, $trangthai, $xacthuc);
        $stmt->execute();
        datThongBao('success', "Đã tạo tài khoản $hoten (Mật khẩu mặc định: 123456).");
    }

    header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=users');
    exit();
}

header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=users');
exit();