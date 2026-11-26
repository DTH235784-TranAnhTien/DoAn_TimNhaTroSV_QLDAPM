<?php
/**
 * chutro/suaphong.php — Chỉnh sửa thông tin phòng trọ
 * Phân hệ Chủ trọ — CampusNest AGU
 */
$tieuDeTrang = 'Chỉnh sửa phòng trọ — CampusNest AGU';
$trangHienTai = 'landlord';

require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';
require_once __DIR__ . '/../cauhinh/upload_helper.php';

$base_url = '/DoAn_TimNhaTroSV_QLDAPM';

if (!daDangNhap()) {
    header("Location: {$base_url}/taikhoan/dangnhap.php?tb=" . urlencode('Vui lòng đăng nhập để chỉnh sửa phòng.'));
    exit();
}

$nd = nguoiDungHienTai();
$mand = (int)($nd['mannd'] ?? 0);
$vaitro = $nd['vaitro'] ?? '';

if (!in_array($vaitro, ['ChuTro', 'QuanTriVien'])) {
    header("Location: {$base_url}/index.php");
    exit();
}

// 1. Nhận mã phòng trọ
$maPhong = (int)($_GET['id'] ?? ($_POST['ma_phong'] ?? 0));
if ($maPhong <= 0) {
    datThongBao('danger', 'Không tìm thấy thông tin phòng trọ.');
    header("Location: {$base_url}/chutro/danhsachphong.php");
    exit();
}

// 2. Kiểm tra quyền sở hữu phòng trọ
if ($vaitro === 'QuanTriVien') {
    $stmtCheck = $conn->prepare("
        SELECT p.*, n.TenNhaTro, n.DiaChi, n.MaChuTro 
        FROM PhongTro p 
        JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro 
        WHERE p.MaPhong = ?
    ");
    $stmtCheck->bind_param('i', $maPhong);
} else {
    $stmtCheck = $conn->prepare("
        SELECT p.*, n.TenNhaTro, n.DiaChi, n.MaChuTro 
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
    datThongBao('danger', 'Bạn không có quyền sửa phòng trọ này hoặc phòng không tồn tại.');
    header("Location: {$base_url}/chutro/danhsachphong.php");
    exit();
}

// 3. Lấy danh sách dãy nhà trọ của chủ này
if ($vaitro === 'QuanTriVien') {
    $stmtNT = $conn->query("SELECT MaNhaTro, TenNhaTro, DiaChi FROM NhaTro ORDER BY TenNhaTro ASC");
    $dsNhaTro = $stmtNT ? $stmtNT->fetch_all(MYSQLI_ASSOC) : [];
} else {
    $stmtNT = $conn->prepare("SELECT MaNhaTro, TenNhaTro, DiaChi FROM NhaTro WHERE MaChuTro = ? ORDER BY TenNhaTro ASC");
    $stmtNT->bind_param('i', $mand);
    $stmtNT->execute();
    $dsNhaTro = $stmtNT->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtNT->close();
}

// 4. Lấy danh sách tiện ích hệ thống
$dsTienIch = [];
$resTI = $conn->query("SELECT MaTienIch, TenTienIch, Icon FROM TienIch ORDER BY MaTienIch ASC");
if ($resTI) {
    $dsTienIch = $resTI->fetch_all(MYSQLI_ASSOC);
}

// 5. Lấy danh sách tiện ích hiện tại của phòng này
$stmtPTI = $conn->prepare("SELECT MaTienIch FROM PhongTro_TienIch WHERE MaPhong = ?");
$stmtPTI->bind_param('i', $maPhong);
$stmtPTI->execute();
$tienIchHienTai = array_column($stmtPTI->get_result()->fetch_all(MYSQLI_ASSOC), 'MaTienIch');
$stmtPTI->close();

// 6. Lấy danh sách ảnh hiện tại của phòng
$stmtAnh = $conn->prepare("SELECT MaHinh, DuongDan, LaAnhChinh FROM HinhAnhPhongTro WHERE MaPhong = ? ORDER BY LaAnhChinh DESC, MaHinh ASC");
$stmtAnh->bind_param('i', $maPhong);
$stmtAnh->execute();
$dsAnh = $stmtAnh->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtAnh->close();

$loi = [];

// ==========================================
// 7. XỬ LÝ KHI GỬI FORM CẬP NHẬT (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenPhong   = trim($_POST['ten_phong'] ?? '');
    $maNhaTro   = (int)($_POST['ma_nha_tro'] ?? $phong['MaNhaTro']);
    $giaThue    = (float)($_POST['gia_thue'] ?? 0);
    $dienTich   = (float)($_POST['dien_tich'] ?? 0);
    $tienDien   = (float)($_POST['tien_dien'] ?? 0);
    $tienNuoc   = (float)($_POST['tien_nuoc'] ?? 0);
    $tienWifi   = (float)($_POST['tien_wifi'] ?? 0);
    $chiPhiKhac = trim($_POST['chi_phi_khac'] ?? '');
    $moTa       = trim($_POST['mo_ta'] ?? '');
    $trangThai  = trim($_POST['trang_thai'] ?? 'ConPhong');

    $anhChinhChon = (int)($_POST['anh_chinh'] ?? 0);
    $xoaAnhIds    = $_POST['xoa_anh'] ?? [];
    $tienIchChon  = $_POST['tien_ich'] ?? [];

    // Kiểm tra dữ liệu
    if (!$tenPhong) {
        $loi[] = 'Vui lòng nhập tên phòng trọ.';
    }
    if ($giaThue <= 0) {
        $loi[] = 'Giá thuê phòng phải lớn hơn 0 đồng.';
    }
    if (!in_array($trangThai, ['ConPhong', 'DaThue', 'DangBaoTri'])) {
        $trangThai = 'ConPhong';
    }

    if (empty($loi)) {
        // A. Cập nhật thông tin phòng trọ trong bảng PhongTro
        $stmtUp = $conn->prepare("
            UPDATE PhongTro SET
                MaNhaTro    = ?,
                TenPhong    = ?,
                GiaThue     = ?,
                DienTich    = ?,
                TienDien    = ?,
                TienNuoc    = ?,
                TienWifi    = ?,
                ChiPhiKhac  = ?,
                MoTa        = ?,
                TrangThai   = ?,
                NgayCapNhat = NOW()
            WHERE MaPhong   = ?
        ");
        $stmtUp->bind_param(
            'isdddddsssi',
            $maNhaTro,
            $tenPhong,
            $giaThue,
            $dienTich,
            $tienDien,
            $tienNuoc,
            $tienWifi,
            $chiPhiKhac,
            $moTa,
            $trangThai,
            $maPhong
        );
        $stmtUp->execute();
        $stmtUp->close();

        // B. Xóa ảnh được chọn xóa (nếu có)
        if (!empty($xoaAnhIds) && is_array($xoaAnhIds)) {
            foreach ($xoaAnhIds as $idHinh) {
                $idHinh = (int)$idHinh;
                if ($idHinh <= 0) continue;

                // Lấy đường dẫn file để xóa trên đĩa
                $stmtGetF = $conn->prepare("SELECT DuongDan FROM HinhAnhPhongTro WHERE MaHinh = ? AND MaPhong = ?");
                $stmtGetF->bind_param('ii', $idHinh, $maPhong);
                $stmtGetF->execute();
                $rowF = $stmtGetF->get_result()->fetch_assoc();
                $stmtGetF->close();

                if ($rowF && !empty($rowF['DuongDan'])) {
                    $localPath = __DIR__ . '/../' . ltrim(str_replace('/DoAn_TimNhaTroSV_QLDAPM/', '', $rowF['DuongDan']), '/');
                    if (file_exists($localPath)) {
                        @unlink($localPath);
                    }
                }

                // Xóa khỏi CSDL
                $stmtDelH = $conn->prepare("DELETE FROM HinhAnhPhongTro WHERE MaHinh = ? AND MaPhong = ?");
                $stmtDelH->bind_param('ii', $idHinh, $maPhong);
                $stmtDelH->execute();
                $stmtDelH->close();
            }
        }

        // C. Tải lên thêm ảnh mới (nếu chủ trọ chọn tải thêm)
        $coTepMoi = isset($_FILES['anh_phong_moi']) && (
            (is_array($_FILES['anh_phong_moi']['name']) && !empty($_FILES['anh_phong_moi']['name'][0])) ||
            (!is_array($_FILES['anh_phong_moi']['name']) && !empty($_FILES['anh_phong_moi']['name']))
        );

        if ($coTepMoi) {
            $anhMoiPaths = xuLyUploadAnhPhongTro($_FILES['anh_phong_moi'], $maPhong);
            if (!empty($anhMoiPaths)) {
                // Kiểm tra xem phòng đã có ảnh chính chưa
                $chkMain = $conn->query("SELECT MaHinh FROM HinhAnhPhongTro WHERE MaPhong = $maPhong AND LaAnhChinh = 1 LIMIT 1");
                $daCoAnhChinh = ($chkMain && $chkMain->num_rows > 0);

                foreach ($anhMoiPaths as $idx => $pImg) {
                    $laChinh = (!$daCoAnhChinh && $idx === 0) ? 1 : 0;
                    $stmtInsImg = $conn->prepare("INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh, NgayTaiLen) VALUES (?, ?, ?, NOW())");
                    $stmtInsImg->bind_param('isi', $maPhong, $pImg, $laChinh);
                    $stmtInsImg->execute();
                    $stmtInsImg->close();
                }
            }
        }

        // D. Cập nhật ảnh chính nếu chủ trọ đổi
        if ($anhChinhChon > 0) {
            $conn->query("UPDATE HinhAnhPhongTro SET LaAnhChinh = 0 WHERE MaPhong = $maPhong");
            $conn->query("UPDATE HinhAnhPhongTro SET LaAnhChinh = 1 WHERE MaHinh = $anhChinhChon AND MaPhong = $maPhong");
        } else {
            // Đảm bảo luôn có ít nhất 1 ảnh chính
            $conn->query("UPDATE HinhAnhPhongTro SET LaAnhChinh = 1 WHERE MaPhong = $maPhong ORDER BY MaHinh ASC LIMIT 1");
        }

        // E. Cập nhật tiện ích (xóa cũ, chèn mới)
        $conn->query("DELETE FROM PhongTro_TienIch WHERE MaPhong = $maPhong");
        if (!empty($tienIchChon) && is_array($tienIchChon)) {
            $stmtInsTI = $conn->prepare("INSERT INTO PhongTro_TienIch (MaPhong, MaTienIch) VALUES (?, ?)");
            foreach ($tienIchChon as $mti) {
                $mti = (int)$mti;
                if ($mti > 0) {
                    $stmtInsTI->bind_param('ii', $maPhong, $mti);
                    $stmtInsTI->execute();
                }
            }
            $stmtInsTI->close();
        }

        datThongBao('success', "Đã cập nhật thông tin phòng trọ \"{$tenPhong}\" thành công!");
        header("Location: {$base_url}/chutro/danhsachphong.php");
        exit();
    }
}

include_once __DIR__ . '/../dungchung/header.php';
?>

<div class="max-w-[1000px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

  <!-- Breadcrumb điều hướng -->
  <nav class="flex items-center gap-2 text-xs text-text-sub">
    <a href="<?= $base_url ?>/index.php" class="hover:text-accent transition-colors">Trang chủ</a>
    <span>/</span>
    <a href="<?= $base_url ?>/chutro.php" class="hover:text-accent transition-colors">Chủ trọ</a>
    <span>/</span>
    <a href="<?= $base_url ?>/chutro/danhsachphong.php" class="hover:text-accent transition-colors">Quản lý phòng trọ</a>
    <span>/</span>
    <span class="text-text-main font-semibold">Chỉnh sửa phòng <?= htmlspecialchars($phong['TenPhong']) ?></span>
  </nav>

  <!-- Thông báo lỗi nếu có -->
  <?php if (!empty($loi)): ?>
    <div class="p-4 rounded-card border border-status-red-border bg-status-red-bg text-status-red-text text-xs space-y-1">
      <div class="font-bold flex items-center gap-1.5 text-sm">
        <span class="material-symbols-outlined text-base">error</span>
        <span>Có lỗi xảy ra, vui lòng kiểm tra lại:</span>
      </div>
      <ul class="list-disc list-inside space-y-0.5">
        <?php foreach ($loi as $item): ?>
          <li><?= htmlspecialchars($item) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <!-- Tiêu đề trang -->
  <div class="bg-bg-main p-6 rounded-card border border-border-warm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="w-12 h-12 rounded-card bg-accent/10 text-accent flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-3xl">edit_note</span>
      </div>
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-text-main">Chỉnh sửa phòng trọ</h1>
        <p class="text-xs text-text-sub mt-0.5">Cập nhật giá thuê, ảnh thực tế, tiện nghi và trạng thái phòng.</p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <a href="<?= $base_url ?>/chitiet.php?id=<?= $maPhong ?>" target="_blank" class="px-3.5 py-2 rounded-btn border border-border-warm bg-bg-sub text-text-main font-semibold text-xs hover:bg-border-warm/50 transition-all flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">visibility</span>
        <span>Xem bài đăng</span>
      </a>
      <a href="<?= $base_url ?>/chutro/danhsachphong.php" class="px-3.5 py-2 rounded-btn border border-border-warm bg-bg-sub text-text-sub hover:text-text-main font-semibold text-xs transition-all">
        Quay lại
      </a>
    </div>
  </div>

  <!-- Form chính cập nhật phòng -->
  <form method="POST" action="<?= $base_url ?>/chutro/suaphong.php?id=<?= $maPhong ?>" enctype="multipart/form-data" class="space-y-6">
    <input type="hidden" name="ma_phong" value="<?= $maPhong ?>">

    <!-- KHỐI 1: THÔNG TIN CƠ BẢN -->
    <div class="bg-bg-main p-6 rounded-card border border-border-warm space-y-4">
      <h2 class="text-base font-bold text-text-main flex items-center gap-2 border-b border-border-warm pb-3">
        <span class="material-symbols-outlined text-accent text-lg">info</span>
        <span>1. Thông tin cơ bản</span>
      </h2>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Thuộc dãy nhà trọ -->
        <div>
          <label class="block text-xs font-semibold text-text-sub mb-1.5">Thuộc dãy nhà trọ <span class="text-red-500">*</span></label>
          <select name="ma_nha_tro" class="w-full text-xs px-3.5 py-2.5 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent font-medium">
            <?php foreach ($dsNhaTro as $nt): ?>
              <option value="<?= $nt['MaNhaTro'] ?>" <?= (int)$phong['MaNhaTro'] === (int)$nt['MaNhaTro'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($nt['TenNhaTro']) ?> (<?= htmlspecialchars($nt['DiaChi']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Tên phòng trọ -->
        <div>
          <label class="block text-xs font-semibold text-text-sub mb-1.5">Tên phòng trọ <span class="text-red-500">*</span></label>
          <input type="text" name="ten_phong" value="<?= htmlspecialchars($_POST['ten_phong'] ?? $phong['TenPhong']) ?>" 
                 placeholder="Ví dụ: Phòng 101, Phòng lầu 1 có gác..." required
                 class="w-full text-xs px-3.5 py-2.5 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent font-semibold text-text-main">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Diện tích -->
        <div>
          <label class="block text-xs font-semibold text-text-sub mb-1.5">Diện tích (m²)</label>
          <div class="relative">
            <input type="number" step="0.5" min="5" max="200" name="dien_tich" 
                   value="<?= htmlspecialchars($_POST['dien_tich'] ?? $phong['DienTich']) ?>" 
                   placeholder="18" class="w-full text-xs px-3.5 py-2.5 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent font-medium">
            <span class="absolute right-3.5 top-2.5 text-xs text-text-sub pointer-events-none">m²</span>
          </div>
        </div>

        <!-- Trạng thái phòng -->
        <div>
          <label class="block text-xs font-semibold text-text-sub mb-1.5">Trạng thái phòng</label>
          <select name="trang_thai" class="w-full text-xs px-3.5 py-2.5 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent font-semibold">
            <option value="ConPhong" <?= ($phong['TrangThai'] === 'ConPhong') ? 'selected' : '' ?>>🟢 Còn phòng trống</option>
            <option value="DaThue" <?= ($phong['TrangThai'] === 'DaThue') ? 'selected' : '' ?>>🔴 Đã cho thuê</option>
            <option value="DangBaoTri" <?= ($phong['TrangThai'] === 'DangBaoTri') ? 'selected' : '' ?>>🟡 Đang bảo trì / Sửa chữa</option>
          </select>
        </div>
      </div>
    </div>

    <!-- KHỐI 2: GIÁ THUÊ & CHI PHÍ DỊCH VỤ -->
    <div class="bg-bg-main p-6 rounded-card border border-border-warm space-y-4">
      <h2 class="text-base font-bold text-text-main flex items-center gap-2 border-b border-border-warm pb-3">
        <span class="material-symbols-outlined text-accent text-lg">payments</span>
        <span>2. Giá thuê & Chi phí dịch vụ</span>
      </h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Giá thuê tháng -->
        <div>
          <label class="block text-xs font-semibold text-text-sub mb-1.5">Giá thuê/tháng (đ) <span class="text-red-500">*</span></label>
          <input type="number" step="50000" min="100000" name="gia_thue" 
                 value="<?= htmlspecialchars($_POST['gia_thue'] ?? $phong['GiaThue']) ?>" required
                 class="w-full text-xs px-3.5 py-2.5 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent font-bold text-accent">
        </div>

        <!-- Tiền điện -->
        <div>
          <label class="block text-xs font-semibold text-text-sub mb-1.5">Tiền điện (đ/kWh)</label>
          <input type="number" step="500" min="0" name="tien_dien" 
                 value="<?= htmlspecialchars($_POST['tien_dien'] ?? $phong['TienDien']) ?>" 
                 placeholder="3500" class="w-full text-xs px-3.5 py-2.5 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent font-medium">
        </div>

        <!-- Tiền nước -->
        <div>
          <label class="block text-xs font-semibold text-text-sub mb-1.5">Tiền nước (đ/khối hoặc người)</label>
          <input type="number" step="5000" min="0" name="tien_nuoc" 
                 value="<?= htmlspecialchars($_POST['tien_nuoc'] ?? $phong['TienNuoc']) ?>" 
                 placeholder="30000" class="w-full text-xs px-3.5 py-2.5 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent font-medium">
        </div>

        <!-- Tiền Wifi -->
        <div>
          <label class="block text-xs font-semibold text-text-sub mb-1.5">Tiền Wifi (đ/tháng)</label>
          <input type="number" step="10000" min="0" name="tien_wifi" 
                 value="<?= htmlspecialchars($_POST['tien_wifi'] ?? $phong['TienWifi']) ?>" 
                 placeholder="0 (miễn phí)" class="w-full text-xs px-3.5 py-2.5 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent font-medium">
        </div>
      </div>

      <!-- Chi phí khác -->
      <div>
        <label class="block text-xs font-semibold text-text-sub mb-1.5">Chi phí khác (nếu có)</label>
        <input type="text" name="chi_phi_khac" value="<?= htmlspecialchars($_POST['chi_phi_khac'] ?? $phong['ChiPhiKhac']) ?>" 
               placeholder="Ví dụ: Rác 20.000đ/tháng, Giữ xe miễn phí..." 
               class="w-full text-xs px-3.5 py-2.5 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent">
      </div>
    </div>

    <!-- KHỐI 3: QUẢN LÝ HÌNH ẢNH PHÒNG TRỌ -->
    <div class="bg-bg-main p-6 rounded-card border border-border-warm space-y-4">
      <h2 class="text-base font-bold text-text-main flex items-center gap-2 border-b border-border-warm pb-3">
        <span class="material-symbols-outlined text-accent text-lg">photo_library</span>
        <span>3. Quản lý hình ảnh thực tế</span>
      </h2>

      <!-- Danh sách ảnh cũ -->
      <?php if (!empty($dsAnh)): ?>
        <div>
          <p class="text-xs font-semibold text-text-sub mb-2">Ảnh hiện có của phòng (Chọn ảnh đại diện chính hoặc tick chọn để xóa):</p>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            <?php foreach ($dsAnh as $anh): 
              $src = $base_url . '/' . ltrim(str_replace('/DoAn_TimNhaTroSV_QLDAPM/', '', $anh['DuongDan']), '/');
            ?>
              <div class="relative group rounded-card border border-border-warm overflow-hidden bg-bg-sub p-2 space-y-2">
                <img src="<?= htmlspecialchars($src) ?>" alt="Ảnh phòng" class="w-full h-28 object-cover rounded-md">
                
                <div class="space-y-1">
                  <!-- Radio chọn làm ảnh chính -->
                  <label class="flex items-center gap-1.5 text-[11px] text-text-main font-semibold cursor-pointer">
                    <input type="radio" name="anh_chinh" value="<?= $anh['MaHinh'] ?>" <?= $anh['LaAnhChinh'] ? 'checked' : '' ?> class="accent-accent">
                    <span><?= $anh['LaAnhChinh'] ? '★ Ảnh chính' : 'Đặt làm chính' ?></span>
                  </label>

                  <!-- Checkbox xóa ảnh -->
                  <label class="flex items-center gap-1.5 text-[11px] text-status-red-text font-medium cursor-pointer">
                    <input type="checkbox" name="xoa_anh[]" value="<?= $anh['MaHinh'] ?>" class="accent-red-600">
                    <span>Xóa ảnh này</span>
                  </label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <p class="text-xs text-text-sub italic">Phòng này hiện chưa có ảnh thực tế nào.</p>
      <?php endif; ?>

      <!-- Tải thêm ảnh mới -->
      <div class="pt-2">
        <label class="block text-xs font-semibold text-text-sub mb-1.5">Tải thêm ảnh mới (có thể chọn nhiều ảnh cùng lúc)</label>
        <input type="file" name="anh_phong_moi[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" 
               class="w-full text-xs px-3.5 py-2.5 rounded-btn border border-border-warm border-dashed bg-bg-sub/30 focus:outline-accent cursor-pointer file:mr-3 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-accent file:text-white hover:file:opacity-90">
        <p class="text-[11px] text-text-sub mt-1">Chấp nhận định dạng: JPG, PNG, WEBP (mỗi ảnh tối đa 10MB).</p>
      </div>
    </div>

    <!-- KHỐI 4: TIỆN ÍCH PHÒNG TRỌ -->
    <?php if (!empty($dsTienIch)): ?>
    <div class="bg-bg-main p-6 rounded-card border border-border-warm space-y-4">
      <h2 class="text-base font-bold text-text-main flex items-center gap-2 border-b border-border-warm pb-3">
        <span class="material-symbols-outlined text-accent text-lg">check_box</span>
        <span>4. Tiện nghi & Tiện ích đi kèm</span>
      </h2>

      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        <?php foreach ($dsTienIch as $ti): 
          $isChecked = in_array((int)$ti['MaTienIch'], $tienIchHienTai);
        ?>
          <label class="flex items-center gap-2 p-2.5 rounded-card border border-border-warm bg-bg-sub/30 hover:bg-bg-sub cursor-pointer transition-colors">
            <input type="checkbox" name="tien_ich[]" value="<?= $ti['MaTienIch'] ?>" <?= $isChecked ? 'checked' : '' ?> class="accent-accent w-4 h-4 rounded">
            <span class="text-xs text-text-main font-medium"><?= htmlspecialchars($ti['TenTienIch']) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- KHỐI 5: MÔ TẢ CHI TIẾT -->
    <div class="bg-bg-main p-6 rounded-card border border-border-warm space-y-4">
      <h2 class="text-base font-bold text-text-main flex items-center gap-2 border-b border-border-warm pb-3">
        <span class="material-symbols-outlined text-accent text-lg">description</span>
        <span>5. Mô tả chi tiết & Giới thiệu</span>
      </h2>

      <div>
        <label class="block text-xs font-semibold text-text-sub mb-1.5">Mô tả đặc điểm phòng, quy định giờ giấc, an ninh</label>
        <textarea name="mo_ta" rows="5" placeholder="Ví dụ: Phòng mới sơn sửa sạch sẽ, giờ giấc tự do không chung chủ, có camera an ninh 24/7, gần cổng phụ ĐH An Giang..." 
                  class="w-full text-xs p-3.5 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent leading-relaxed"><?= htmlspecialchars($_POST['mo_ta'] ?? $phong['MoTa']) ?></textarea>
      </div>
    </div>

    <!-- NÚT BẤM HÀNH ĐỘNG -->
    <div class="flex items-center justify-end gap-3 pt-2">
      <a href="<?= $base_url ?>/chutro/danhsachphong.php" class="px-5 py-2.5 rounded-btn border border-border-warm bg-bg-sub text-text-main text-xs font-semibold hover:bg-border-warm/50 transition-all">
        Hủy bỏ & Quay lại
      </a>
      <button type="submit" class="px-6 py-2.5 rounded-btn bg-accent text-white text-xs font-bold hover:opacity-90 transition-all flex items-center gap-1.5 shadow-sm">
        <span class="material-symbols-outlined text-base">save</span>
        <span>Lưu thay đổi</span>
      </button>
    </div>

  </form>

</div>

<?php include_once __DIR__ . '/../dungchung/footer.php'; ?>
