<?php
require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

yeuCauVaiTro('SinhVien');
$nd   = nguoiDungHienTai();
$mand = (int)$nd['mannd'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maPhong = (int)($_POST['ma_phong'] ?? 0);
    $ngay    = trim($_POST['ngay'] ?? '');
    $gio     = trim($_POST['gio'] ?? '');
    $ghiChu  = trim($_POST['ghi_chu'] ?? '');

    // 1. Kiểm tra dữ liệu đầu vào cơ bản
    if ($maPhong <= 0 || $ngay === '' || $gio === '') {
        datThongBao('danger', 'Vui lòng chọn ngày và giờ hẹn hợp lệ.');
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=' . $maPhong);
        exit();
    }

    // 2. Kiểm tra định dạng ngày (YYYY-MM-DD)
    $dtNgay = DateTime::createFromFormat('Y-m-d', $ngay);
    if (!$dtNgay || $dtNgay->format('Y-m-d') !== $ngay) {
        datThongBao('danger', 'Định dạng ngày hẹn không hợp lệ.');
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=' . $maPhong);
        exit();
    }

    // 3. Kiểm tra ngày hẹn không phải ngày quá khứ
    if ($ngay < date('Y-m-d')) {
        datThongBao('danger', 'Ngày hẹn không được là ngày trong quá khứ.');
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=' . $maPhong);
        exit();
    }

    // 4. Kiểm tra định dạng giờ hẹn (HH:MM)
    $dtGio = DateTime::createFromFormat('H:i', $gio);
    if (!$dtGio || $dtGio->format('H:i') !== $gio) {
        datThongBao('danger', 'Định dạng giờ hẹn không hợp lệ. Vui lòng nhập theo dạng HH:MM.');
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=' . $maPhong);
        exit();
    }

    // 5. Kiểm tra phòng tồn tại & lấy trạng thái
    $stmt = $conn->prepare("SELECT MaPhong, TrangThai FROM PhongTro WHERE MaPhong = ?");
    $stmt->bind_param('i', $maPhong);
    $stmt->execute();
    $result = $stmt->get_result();
    $phong  = $result->fetch_assoc();
    $stmt->close();

    if (!$phong) {
        datThongBao('danger', 'Phòng trọ không tồn tại hoặc đã bị xóa.');
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/danhsach.php');
        exit();
    }

    // 6. Kiểm tra phòng còn trống (ConPhong)
    if ($phong['TrangThai'] === 'DaThue') {
        datThongBao('danger', 'Phòng này hiện đã có người thuê, không thể đặt lịch.');
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=' . $maPhong);
        exit();
    }

    // 7. Kiểm tra trùng lịch (ChoDuyet hoặc DaXacNhan)
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS SoLuong FROM LichHen
         WHERE MaSinhVien = ? AND MaPhong = ? AND TrangThai IN ('ChoDuyet', 'DaXacNhan')"
    );
    $stmt->bind_param('ii', $mand, $maPhong);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int)$row['SoLuong'] > 0) {
        datThongBao('danger', 'Bạn đã có một lịch hẹn đang chờ duyệt hoặc được xác nhận với phòng này.');
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=' . $maPhong);
        exit();
    }

    // 8. Tất cả hợp lệ → INSERT lịch hẹn
    $stmt = $conn->prepare(
        "INSERT INTO LichHen (MaSinhVien, MaPhong, NgayHen, GioHen, GhiChu, TrangThai, NgayTao)
         VALUES (?, ?, ?, ?, ?, 'ChoDuyet', NOW())"
    );
    $stmt->bind_param('iisss', $mand, $maPhong, $ngay, $gio, $ghiChu);
    $stmt->execute();
    $stmt->close();

    datThongBao('success', 'Đặt lịch xem phòng thành công! Vui lòng chờ chủ trọ xác nhận.');
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=' . $maPhong);
    exit();
}

// Không phải POST → về danh sách
header('Location: /DoAn_TimNhaTroSV_QLDAPM/danhsach.php');
exit();
