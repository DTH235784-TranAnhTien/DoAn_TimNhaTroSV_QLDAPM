<?php
$tieuDeTrang = 'Tìm bạn cùng phòng (AGU-Match) — CampusNest AGU';
$trangHienTai = 'roommate';
require_once __DIR__ . '/cauhinh/database.php';
require_once __DIR__ . '/cauhinh/auth.php';

$nd = nguoiDungHienTai();
$mand = $nd ? (int)$nd['mannd'] : 0;
$daDangNhapSV = daDangNhap() && coVaiTro('SinhVien');

// ================= XỬ LÝ ĐĂNG / SỬA TIN TÌM BẠN GHÉP (POST) =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hanhDong = $_POST['hanh_dong'] ?? '';

    if ($hanhDong === 'dang_tin_ghep') {
        if (!daDangNhap()) {
            datThongBao('warning', 'Vui lòng đăng nhập tài khoản sinh viên để đăng tin tìm bạn ghép.');
            header('Location: /DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php');
            exit();
        }

        $gioiTinh    = $_POST['gioi_tinh'] ?? 'Nam';
        $nganSach    = (float)($_POST['ngan_sach'] ?? 0);
        $khuVuc      = trim($_POST['khu_vuc'] ?? '');
        $gioGiac     = $_POST['gio_giac'] ?? 'TuDo';
        $hutThuoc    = isset($_POST['hut_thuoc']) ? 1 : 0;
        $nuoiThuCung = isset($_POST['nuoi_thu_cung']) ? 1 : 0;
        $moTa        = trim($_POST['mo_ta'] ?? '');
        $sdt         = trim($_POST['so_dien_thoai'] ?? '');

        if ($nganSach <= 0) $nganSach = 1000000;

        // Cập nhật SĐT vào NguoiDung nếu có
        if ($sdt) {
            $stmtU = $conn->prepare("UPDATE NguoiDung SET SoDienThoai = ? WHERE MaNguoiDung = ?");
            $stmtU->bind_param('si', $sdt, $mand);
            $stmtU->execute();
            $stmtU->close();
            $_SESSION['nguoidung']['sdt'] = $sdt;
        }

        // Cập nhật ảnh đại diện từ máy tính nếu sinh viên tải ảnh mới
        if (isset($_FILES['anh_dai_dien']) && $_FILES['anh_dai_dien']['error'] !== UPLOAD_ERR_NO_FILE) {
            require_once __DIR__ . '/cauhinh/upload_helper.php';
            $resAvt = xuLyUploadAvatar($_FILES['anh_dai_dien'], $mand);
            if ($resAvt['ok']) {
                $duongDanAvt = $resAvt['path'];
                $stmtAvt = $conn->prepare("UPDATE NguoiDung SET AnhDaiDien = ? WHERE MaNguoiDung = ?");
                $stmtAvt->bind_param('si', $duongDanAvt, $mand);
                $stmtAvt->execute();
                $stmtAvt->close();
                $_SESSION['nguoidung']['anhdaidien'] = $duongDanAvt;
                if ($nd) $nd['anhdaidien'] = $duongDanAvt;
            }
        }

        // Kiểm tra xem sinh viên đã có hồ sơ chưa
        $chk = $conn->prepare("SELECT MaHoSo FROM HoSoGhepPhong WHERE MaSinhVien = ? LIMIT 1");
        $chk->bind_param('i', $mand);
        $chk->execute();
        $hoSoCu = $chk->get_result()->fetch_assoc();
        $chk->close();

        if ($hoSoCu) {
            $stmtUpd = $conn->prepare("
                UPDATE HoSoGhepPhong
                SET GioiTinh=?, NganSachToiDa=?, KhuVucUuTien=?, GioGiac=?, HutThuoc=?, NuoiThuCung=?, MoTaBanThan=?, TrangThai='DangTim'
                WHERE MaSinhVien=?
            ");
            $stmtUpd->bind_param('sdsiiisi', $gioiTinh, $nganSach, $khuVuc, $gioGiac, $hutThuoc, $nuoiThuCung, $moTa, $mand);
            $stmtUpd->execute();
            $stmtUpd->close();
            datThongBao('success', 'Đã cập nhật hồ sơ tìm bạn ở ghép thành công!');
        } else {
            $stmtIns = $conn->prepare("
                INSERT INTO HoSoGhepPhong (MaSinhVien, GioiTinh, NganSachToiDa, KhuVucUuTien, GioGiac, HutThuoc, NuoiThuCung, MoTaBanThan, TrangThai, NgayTao)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'DangTim', NOW())
            ");
            $stmtIns->bind_param('isdsiiis', $mand, $gioiTinh, $nganSach, $khuVuc, $gioGiac, $hutThuoc, $nuoiThuCung, $moTa);
            $stmtIns->execute();
            $stmtIns->close();
            datThongBao('success', 'Đã đăng tin tìm bạn ghép thành công!');
        }

        header('Location: /DoAn_TimNhaTroSV_QLDAPM/ghepban.php');
        exit();
    }

    if ($hanhDong === 'doi_trang_thai_ghep') {
        if (daDangNhap()) {
            $stmtTT = $conn->prepare("
                UPDATE HoSoGhepPhong
                SET TrangThai = IF(TrangThai='DangTim', 'DaTimDuoc', 'DangTim')
                WHERE MaSinhVien = ?
            ");
            $stmtTT->bind_param('i', $mand);
            $stmtTT->execute();
            $stmtTT->close();
            datThongBao('info', 'Đã thay đổi trạng thái tìm kiếm bạn cùng phòng!');
        }
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/ghepban.php');
        exit();
    }
}

// Lấy hồ sơ của sinh viên đang đăng nhập (nếu có)
$hoSoCuaToi = null;
if ($mand > 0) {
    $stmtMe = $conn->prepare("SELECT * FROM HoSoGhepPhong WHERE MaSinhVien = ? LIMIT 1");
    $stmtMe->bind_param('i', $mand);
    $stmtMe->execute();
    $hoSoCuaToi = $stmtMe->get_result()->fetch_assoc();
    $stmtMe->close();
}

// ================= BỘ LỌC TÌM KIẾM TỪ URL =================
$filterGioiTinh = $_GET['gioitinh'] ?? '';
$filterNganSach = $_GET['ngansach'] ?? '';
$filterGioGiac  = $_GET['giogiac']  ?? '';
$filterKhuVuc   = $_GET['khuvuc']   ?? '';
$filterTag      = $_GET['tag']      ?? '';
$q              = trim($_GET['q']   ?? '');

$where = ["h.TrangThai = 'DangTim'"];
$params = [];
$types  = '';

if ($q !== '') {
    $like = "%$q%";
    $where[] = "(u.HoTen LIKE ? OR h.KhuVucUuTien LIKE ? OR h.MoTaBanThan LIKE ?)";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}

if ($filterGioiTinh === 'Nam' || $filterGioiTinh === 'Nu' || $filterGioiTinh === 'Khac') {
    $where[] = "h.GioiTinh = ?";
    $params[] = $filterGioiTinh;
    $types .= 's';
}

if ($filterNganSach === 'duoi-800k') {
    $where[] = "h.NganSachToiDa <= 800000";
} elseif ($filterNganSach === '800k-1m2') {
    $where[] = "h.NganSachToiDa BETWEEN 800000 AND 1200000";
} elseif ($filterNganSach === '1m2-1m8') {
    $where[] = "h.NganSachToiDa BETWEEN 1200000 AND 1800000";
} elseif ($filterNganSach === 'tren-1m8') {
    $where[] = "h.NganSachToiDa > 1800000";
}

if ($filterGioGiac === 'Som' || $filterGioGiac === 'Khuya' || $filterGioGiac === 'TuDo') {
    $where[] = "h.GioGiac = ?";
    $params[] = $filterGioGiac;
    $types .= 's';
}

if ($filterKhuVuc !== '') {
    $likeKv = "%$filterKhuVuc%";
    $where[] = "h.KhuVucUuTien LIKE ?";
    $params[] = $likeKv;
    $types .= 's';
}

// Lọc theo Quick Tags
if ($filterTag === 'khong-hut-thuoc') {
    $where[] = "h.HutThuoc = 0";
} elseif ($filterTag === 'khong-thu-cung') {
    $where[] = "h.NuoiThuCung = 0";
} elseif ($filterTag === 'nuoi-thu-cung') {
    $where[] = "h.NuoiThuCung = 1";
} elseif ($filterTag === 'ngu-som') {
    $where[] = "h.GioGiac = 'Som'";
} elseif ($filterTag === 'duoi-1m') {
    $where[] = "h.NganSachToiDa <= 1000000";
} elseif ($filterTag === 'khu-a') {
    $where[] = "(h.KhuVucUuTien LIKE '%Khu A%' OR h.KhuVucUuTien LIKE '%Ung Văn Khiêm%' OR h.KhuVucUuTien LIKE '%Đông Xuyên%')";
} elseif ($filterTag === 'khu-b') {
    $where[] = "(h.KhuVucUuTien LIKE '%Khu B%' OR h.KhuVucUuTien LIKE '%Võ Thị Sáu%' OR h.KhuVucUuTien LIKE '%Mỹ Xuyên%')";
}

$sql = "
    SELECT h.*, u.HoTen, u.AnhDaiDien, u.Email, u.SoDienThoai
    FROM HoSoGhepPhong h
    JOIN NguoiDung u ON h.MaSinhVien = u.MaNguoiDung
    WHERE " . implode(' AND ', $where) . "
    ORDER BY h.NgayTao DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}

$dsHoSo = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$tongHoSo = count($dsHoSo);

// Tổng hồ sơ toàn hệ thống
$kpiTong = (int)($conn->query("SELECT COUNT(*) AS c FROM HoSoGhepPhong WHERE TrangThai='DangTim'")->fetch_assoc()['c'] ?? 0);

include_once __DIR__ . '/dungchung/header.php';
?>

<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

  <!-- ================= BANNER & NÚT ĐĂNG TIN TÌM BẠN ================= -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-bg-sub p-6 rounded-card border border-border-warm">
    <div class="max-w-2xl space-y-1.5">
      <span class="inline-block px-2.5 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold">Cộng đồng sinh viên AGU-Match</span>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-text-main">
        Tìm bạn cùng phòng <span class="underline decoration-accent decoration-2 underline-offset-4">hợp tính cách &amp; giờ giấc</span>
      </h1>
      <p class="text-xs sm:text-sm text-text-sub leading-relaxed">
        Kết nối sinh viên Đại học An Giang cùng chia sẻ chi phí tiền phòng, hợp lối sống, không lo phát sinh mâu thuẫn.
      </p>
    </div>

    <!-- NÚT ĐĂNG TIN TÌM BẠN GHÉP RÕ RÀNG -->
    <div class="shrink-0 flex items-center gap-3">
      <?php if ($daDangNhapSV): ?>
      <button type="button" onclick="openRoommateModal()"
              class="px-5 py-3 rounded-btn bg-accent text-white font-semibold text-sm flex items-center gap-2 shadow-sm transition-all duration-200 active:scale-[0.98] hover:opacity-95">
        <span class="material-symbols-outlined text-[20px]">person_add</span>
        <span><?= $hoSoCuaToi ? 'Chỉnh sửa tin của tôi' : '+ Đăng tin tìm bạn ghép' ?></span>
      </button>
      <?php elseif (!daDangNhap()): ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php?tb=<?= urlencode('Vui long dang nhap de dang tin tim ban o ghep.') ?>"
         class="px-5 py-3 rounded-btn bg-accent text-white font-semibold text-sm flex items-center gap-2 shadow-sm transition-all duration-200 active:scale-[0.98] hover:opacity-95">
        <span class="material-symbols-outlined text-[20px]">login</span>
        <span>Đăng nhập để đăng tin tìm bạn</span>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- NẾU SINH VIÊN ĐÃ CÓ HỒ SƠ: HIỂN THỊ TRẠNG THÁI HỒ SƠ CỦA BẢN THÂN -->
  <?php if ($hoSoCuaToi):
    $nsMe = number_format($hoSoCuaToi['NganSachToiDa'], 0, ',', '.');
    $dangTim = $hoSoCuaToi['TrangThai'] === 'DangTim';
  ?>
  <div class="p-4 rounded-card border <?= $dangTim ? 'bg-status-green-bg/40 border-accent/40' : 'bg-bg-sub border-border-warm' ?> flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
    <div class="flex items-center gap-3">
      <span class="material-symbols-outlined text-2xl <?= $dangTim ? 'text-accent' : 'text-text-sub' ?>">account_box</span>
      <div>
        <p class="font-bold text-text-main">
          Hồ sơ của bạn: <span class="<?= $dangTim ? 'text-accent' : 'text-text-sub' ?>"><?= $dangTim ? 'Đang hiển thị tìm bạn' : 'Đã tạm dừng tìm bạn' ?></span>
        </p>
        <p class="text-text-sub mt-0.5">
          Ngân sách: <strong><?= $nsMe ?> đ</strong> · Khu vực: <?= htmlspecialchars($hoSoCuaToi['KhuVucUuTien'] ?: 'Chưa đặt') ?> · Giờ giấc: <?= $hoSoCuaToi['GioGiac'] ?>
        </p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <button type="button" onclick="openRoommateModal()" class="px-3.5 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold card-hover">
        Sửa thông tin
      </button>
      <form method="POST" class="inline">
        <input type="hidden" name="hanh_dong" value="doi_trang_thai_ghep">
        <button type="submit" class="px-3.5 py-1.5 rounded-btn <?= $dangTim ? 'border border-status-amber-text text-status-amber-text bg-status-amber-bg' : 'bg-accent text-white' ?> font-semibold">
          <?= $dangTim ? 'Đã tìm được (Ẩn tin)' : 'Mở lại tìm bạn' ?>
        </button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- ================= BỘ LỌC VÀ THANH TÌM KIẾM ĐẦY ĐỦ ================= -->
  <form action="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php" method="GET" class="bg-bg-sub p-4 sm:p-5 rounded-card border border-border-warm shadow-sm space-y-3">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">

      <!-- 1. Ô tìm kiếm từ khóa -->
      <div class="lg:col-span-4">
        <label class="block text-xs font-semibold text-text-main mb-1">Tìm từ khóa</label>
        <input name="q" type="search" value="<?= htmlspecialchars($q) ?>"
               placeholder="Tên sinh viên, khoa, thói quen, khu vực..."
               class="w-full bg-bg-main border border-border-warm rounded-card px-3 py-2 text-sm text-text-main focus:border-accent outline-none">
      </div>

      <!-- 2. Giới tính -->
      <div class="lg:col-span-2">
        <label class="block text-xs font-semibold text-text-main mb-1">Giới tính</label>
        <select name="gioitinh" class="w-full bg-bg-main border border-border-warm rounded-card px-3 py-2 text-sm text-text-main focus:border-accent outline-none">
          <option value="">Tất cả giới tính</option>
          <option value="Nam" <?= $filterGioiTinh==='Nam'?'selected':'' ?>>Nam</option>
          <option value="Nu" <?= $filterGioiTinh==='Nu'?'selected':'' ?>>Nữ</option>
          <option value="Khac" <?= $filterGioiTinh==='Khac'?'selected':'' ?>>Khác</option>
        </select>
      </div>

      <!-- 3. Mức ngân sách -->
      <div class="lg:col-span-2">
        <label class="block text-xs font-semibold text-text-main mb-1">Ngân sách tối đa</label>
        <select name="ngansach" class="w-full bg-bg-main border border-border-warm rounded-card px-3 py-2 text-sm text-text-main focus:border-accent outline-none tnum">
          <option value="">Mọi mức ngân sách</option>
          <option value="duoi-800k" <?= $filterNganSach==='duoi-800k'?'selected':'' ?>>Dưới 800.000 đ</option>
          <option value="800k-1m2" <?= $filterNganSach==='800k-1m2'?'selected':'' ?>>800.000 – 1.2 triệu</option>
          <option value="1m2-1m8" <?= $filterNganSach==='1m2-1m8'?'selected':'' ?>>1.2 – 1.8 triệu</option>
          <option value="tren-1m8" <?= $filterNganSach==='tren-1m8'?'selected':'' ?>>Trên 1.8 triệu</option>
        </select>
      </div>

      <!-- 4. Giờ giấc sinh hoạt -->
      <div class="lg:col-span-2">
        <label class="block text-xs font-semibold text-text-main mb-1">Giờ giấc sinh hoạt</label>
        <select name="giogiac" class="w-full bg-bg-main border border-border-warm rounded-card px-3 py-2 text-sm text-text-main focus:border-accent outline-none">
          <option value="">Tất cả giờ giấc</option>
          <option value="Som" <?= $filterGioGiac==='Som'?'selected':'' ?>>Ngủ sớm (&lt; 23h)</option>
          <option value="Khuya" <?= $filterGioGiac==='Khuya'?'selected':'' ?>>Hay thức khuya</option>
          <option value="TuDo" <?= $filterGioGiac==='TuDo'?'selected':'' ?>>Tự do linh hoạt</option>
        </select>
      </div>

      <!-- 5. Nút tìm kiếm -->
      <div class="lg:col-span-2">
        <button type="submit" class="w-full h-[38px] bg-accent text-white font-semibold text-sm rounded-btn flex items-center justify-center gap-1.5 transition-all duration-200 active:scale-[0.98]">
          <span class="material-symbols-outlined text-[18px]">search</span>
          <span>Tìm bạn</span>
        </button>
      </div>
    </div>

    <!-- DẢI THẺ LỌC NHANH (QUICK TAGS) -->
    <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-border-warm text-xs">
      <span class="text-text-sub font-semibold">Lọc nhanh theo thói quen:</span>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php?tag=khong-hut-thuoc"
         class="px-2.5 py-1 rounded-card border <?= $filterTag==='khong-hut-thuoc'?'bg-accent text-white border-accent':'bg-bg-main border-border-warm text-text-main card-hover' ?> transition-all duration-200">
        🚭 Không hút thuốc
      </a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php?tag=khong-thu-cung"
         class="px-2.5 py-1 rounded-card border <?= $filterTag==='khong-thu-cung'?'bg-accent text-white border-accent':'bg-bg-main border-border-warm text-text-main card-hover' ?> transition-all duration-200">
        🚫 Không nuôi thú cưng
      </a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php?tag=nuoi-thu-cung"
         class="px-2.5 py-1 rounded-card border <?= $filterTag==='nuoi-thu-cung'?'bg-accent text-white border-accent':'bg-bg-main border-border-warm text-text-main card-hover' ?> transition-all duration-200">
        🐾 Được nuôi thú cưng
      </a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php?tag=ngu-som"
         class="px-2.5 py-1 rounded-card border <?= $filterTag==='ngu-som'?'bg-accent text-white border-accent':'bg-bg-main border-border-warm text-text-main card-hover' ?> transition-all duration-200">
        ⏰ Ngủ sớm dậy sớm
      </a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php?tag=duoi-1m"
         class="px-2.5 py-1 rounded-card border <?= $filterTag==='duoi-1m'?'bg-accent text-white border-accent':'bg-bg-main border-border-warm text-text-main card-hover' ?> transition-all duration-200">
        💰 Ngân sách &le; 1 triệu
      </a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php?tag=khu-a"
         class="px-2.5 py-1 rounded-card border <?= $filterTag==='khu-a'?'bg-accent text-white border-accent':'bg-bg-main border-border-warm text-text-main card-hover' ?> transition-all duration-200">
        📍 Gần Khu A (Ung Văn Khiêm)
      </a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php?tag=khu-b"
         class="px-2.5 py-1 rounded-card border <?= $filterTag==='khu-b'?'bg-accent text-white border-accent':'bg-bg-main border-border-warm text-text-main card-hover' ?> transition-all duration-200">
        📍 Gần Khu B (Võ Thị Sáu)
      </a>

      <?php if ($q || $filterGioiTinh || $filterNganSach || $filterGioGiac || $filterKhuVuc || $filterTag): ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php" class="px-3 py-1 rounded-card bg-status-red-bg text-status-red-text border border-status-red-border font-semibold flex items-center gap-1 ml-auto">
        <span class="material-symbols-outlined text-[14px]">close</span>Đặt lại
      </a>
      <?php endif; ?>
    </div>
  </form>

  <!-- ================= DANH SÁCH HỒ SƠ SINH VIÊN ================= -->
  <div class="space-y-4">
    <div class="flex items-center justify-between text-xs text-text-sub">
      <span>Tìm thấy <strong><?= $tongHoSo ?></strong> bạn sinh viên phù hợp tiêu chí</span>
      <span class="tnum">Hệ thống có <?= $kpiTong ?> bạn đang tìm người ở ghép</span>
    </div>

    <?php if (empty($dsHoSo)): ?>
    <!-- EMPTY STATE KHI CHƯA CÓ AI ĐĂNG / KHÔNG TÌM THẤY -->
    <div class="py-16 text-center space-y-4 bg-bg-main rounded-card border border-border-warm border-dashed">
      <span class="material-symbols-outlined text-5xl text-text-sub block mx-auto">group_off</span>
      <div>
        <p class="text-base font-bold text-text-main">Chưa có hồ sơ tìm bạn ghép phù hợp tiêu chí</p>
        <p class="text-xs text-text-sub mt-1">Hãy thử bỏ bớt bộ lọc hoặc là người đầu tiên đăng tin tìm bạn cùng phòng!</p>
      </div>
      <div class="pt-2">
        <?php if ($daDangNhapSV): ?>
        <button type="button" onclick="openRoommateModal()"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-btn bg-accent text-white font-semibold text-sm">
          <span class="material-symbols-outlined text-base">person_add</span>Tạo hồ sơ ghép phòng ngay
        </button>
        <?php else: ?>
        <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-btn bg-accent text-white font-semibold text-sm">
          <span class="material-symbols-outlined text-base">login</span>Đăng nhập để đăng tin
        </a>
        <?php endif; ?>
      </div>
    </div>

    <?php else: ?>
    <!-- GRID THẺ BẠN GHÉP THỰC TỪ DATABASE -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($dsHoSo as $hs):
        $ns = number_format($hs['NganSachToiDa'], 0, ',', '.');
        $sdt = $hs['SoDienThoai'] ?? '';
        $ngay = date('d/m/Y', strtotime($hs['NgayTao']));
        $gioGiacText = ($hs['GioGiac'] === 'Som') ? 'Ngủ sớm (< 23h)' : (($hs['GioGiac'] === 'Khuya') ? 'Hay thức khuya' : 'Giờ giấc tự do');
        $gioGiacBadge = ($hs['GioGiac'] === 'Som') ? 'bg-status-green-bg text-accent' : 'bg-bg-sub text-text-sub';
      ?>
      <article class="bg-bg-main rounded-card border border-border-warm p-5 space-y-3.5 card-hover flex flex-col justify-between">
        <div class="space-y-3">
          <!-- Thông tin cá nhân -->
          <div class="flex items-start gap-3">
            <div class="w-12 h-12 rounded-full overflow-hidden bg-bg-sub border border-border-warm shrink-0">
              <?php if ($hs['AnhDaiDien']): ?>
              <img src="<?= htmlspecialchars($hs['AnhDaiDien']) ?>" alt="<?= htmlspecialchars($hs['HoTen']) ?>" class="w-full h-full object-cover">
              <?php else: ?>
              <div class="w-full h-full flex items-center justify-center">
                <span class="material-symbols-outlined text-text-sub text-2xl">person</span>
              </div>
              <?php endif; ?>
            </div>
            <div class="min-w-0 flex-1">
              <h3 class="text-sm font-bold text-text-main truncate"><?= htmlspecialchars($hs['HoTen']) ?></h3>
              <p class="text-xs text-text-sub">
                Giới tính: <strong><?= $hs['GioiTinh'] ?></strong> · <span class="tnum"><?= $ngay ?></span>
              </p>
            </div>
          </div>

          <!-- Tags thói quen -->
          <div class="flex flex-wrap gap-1.5 text-[11px]">
            <span class="px-2 py-0.5 rounded-full <?= $gioGiacBadge ?> font-semibold"><?= $gioGiacText ?></span>
            <?php if (!$hs['HutThuoc']): ?>
            <span class="px-2 py-0.5 rounded-full bg-bg-sub text-text-sub">🚭 Không hút thuốc</span>
            <?php else: ?>
            <span class="px-2 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text">🚬 Có hút thuốc</span>
            <?php endif; ?>

            <?php if ($hs['NuoiThuCung']): ?>
            <span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent">🐾 Nuôi thú cưng</span>
            <?php else: ?>
            <span class="px-2 py-0.5 rounded-full bg-bg-sub text-text-sub">Không thú cưng</span>
            <?php endif; ?>
          </div>

          <!-- Mô tả bản thân -->
          <?php if ($hs['MoTaBanThan']): ?>
          <p class="text-xs text-text-main leading-relaxed bg-bg-sub/60 p-3 rounded-card">
            <?= nl2br(htmlspecialchars($hs['MoTaBanThan'])) ?>
          </p>
          <?php endif; ?>
        </div>

        <div class="pt-3 border-t border-border-warm space-y-2.5">
          <div class="flex items-center justify-between text-xs tnum">
            <span class="text-text-sub">Ngân sách: <strong class="text-text-main text-sm font-bold"><?= $ns ?> đ</strong></span>
            <span class="text-text-sub truncate max-w-[140px]" title="<?= htmlspecialchars($hs['KhuVucUuTien'] ?: 'Không cố định') ?>">
              📍 <?= htmlspecialchars($hs['KhuVucUuTien'] ?: 'Quanh AGU') ?>
            </span>
          </div>

          <!-- Nút liên hệ -->
          <?php if ($sdt): ?>
          <a href="tel:<?= htmlspecialchars($sdt) ?>"
             class="w-full py-2 rounded-btn bg-accent text-white font-semibold text-xs flex items-center justify-center gap-1.5 transition-all duration-200 active:scale-[0.98]">
            <span class="material-symbols-outlined text-[16px]">call</span>
            <span>Liên hệ: <?= htmlspecialchars($sdt) ?></span>
          </a>
          <?php else: ?>
          <a href="mailto:<?= htmlspecialchars($hs['Email']) ?>"
             class="w-full py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs flex items-center justify-center gap-1.5 card-hover">
            <span class="material-symbols-outlined text-[16px]">mail</span>
            <span>Gửi email trao đổi</span>
          </a>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- ================= MODAL ĐĂNG / SỬA TIN TÌM BẠN GHÉP ================= -->
<div id="roommate-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
  <div class="bg-bg-main rounded-card border border-border-warm shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between p-5 border-b border-border-warm sticky top-0 bg-bg-main z-10">
      <div>
        <h2 class="text-base font-bold text-text-main"><?= $hoSoCuaToi ? 'Cập nhật hồ sơ tìm bạn ghép' : 'Đăng tin tìm bạn cùng phòng' ?></h2>
        <p class="text-xs text-text-sub mt-0.5">Thông tin của bạn sẽ hiển thị công khai trên bảng tin AGU-Match</p>
      </div>
      <button onclick="closeRoommateModal()" type="button" class="w-8 h-8 rounded-btn border border-border-warm flex items-center justify-center text-text-sub hover:text-text-main">
        <span class="material-symbols-outlined text-[18px]">close</span>
      </button>
    </div>

    <form method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
      <input type="hidden" name="hanh_dong" value="dang_tin_ghep">

      <!-- Ảnh đại diện sinh viên (chọn từ máy tính) -->
      <div class="flex items-center gap-3.5 p-3 rounded-card bg-bg-sub border border-border-warm">
        <div class="w-14 h-14 rounded-full border-2 border-border-warm overflow-hidden bg-bg-main shrink-0 shadow-2xs">
          <img id="roommate-preview-avatar" src="<?= htmlspecialchars($nd['anhdaidien'] ?? '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/default-avatar.png') ?>" alt="Ảnh đại diện" class="w-full h-full object-cover">
        </div>
        <div class="flex-1 min-w-0">
          <label class="block text-xs font-semibold text-text-main">Ảnh đại diện của bạn (Tải từ máy tính / điện thoại)</label>
          <p class="text-[11px] text-text-sub mb-1.5">Ảnh rõ mặt giúp tăng cơ hội tìm được bạn cùng phòng phù hợp</p>
          <input type="file" name="anh_dai_dien" id="roommate-avatar-input" accept="image/png, image/jpeg, image/jpg, image/webp"
                 onchange="previewRoommateAvatar(this)"
                 class="block w-full text-xs text-text-sub file:mr-2 file:py-1 file:px-2.5 file:rounded-btn file:border-0 file:text-xs file:font-semibold file:bg-accent file:text-white hover:file:opacity-90 cursor-pointer">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <!-- Giới tính -->
        <div>
          <label class="block text-xs font-semibold text-text-main mb-1">Giới tính <span class="text-rose-500">*</span></label>
          <select name="gioi_tinh" required class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
            <option value="Nam" <?= ($hoSoCuaToi['GioiTinh'] ?? '')==='Nam'?'selected':'' ?>>Nam</option>
            <option value="Nu" <?= ($hoSoCuaToi['GioiTinh'] ?? '')==='Nu'?'selected':'' ?>>Nữ</option>
            <option value="Khac" <?= ($hoSoCuaToi['GioiTinh'] ?? '')==='Khac'?'selected':'' ?>>Khác</option>
          </select>
        </div>

        <!-- Ngân sách -->
        <div>
          <label class="block text-xs font-semibold text-text-main mb-1">Ngân sách tối đa (đ/tháng) <span class="text-rose-500">*</span></label>
          <input type="number" name="ngan_sach" required min="300000" step="50000"
                 value="<?= $hoSoCuaToi['NganSachToiDa'] ?? 800000 ?>"
                 placeholder="VD: 800000"
                 class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent tnum">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <!-- Giờ giấc -->
        <div>
          <label class="block text-xs font-semibold text-text-main mb-1">Giờ giấc sinh hoạt</label>
          <select name="gio_giac" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
            <option value="Som" <?= ($hoSoCuaToi['GioGiac'] ?? '')==='Som'?'selected':'' ?>>Ngủ sớm (trước 23h)</option>
            <option value="Khuya" <?= ($hoSoCuaToi['GioGiac'] ?? '')==='Khuya'?'selected':'' ?>>Hay thức khuya</option>
            <option value="TuDo" <?= ($hoSoCuaToi['GioGiac'] ?? '')==='TuDo'?'selected':'' ?>>Tự do linh hoạt</option>
          </select>
        </div>

        <!-- Số điện thoại -->
        <div>
          <label class="block text-xs font-semibold text-text-main mb-1">Số điện thoại liên hệ <span class="text-rose-500">*</span></label>
          <input type="tel" name="so_dien_thoai" required
                 value="<?= htmlspecialchars($nd['sdt'] ?? '') ?>"
                 placeholder="VD: 09xxxxxxxx"
                 class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
        </div>
      </div>

      <!-- Khu vực mong muốn -->
      <div>
        <label class="block text-xs font-semibold text-text-main mb-1">Khu vực ưu tiên tìm trọ</label>
        <input type="text" name="khu_vuc"
               value="<?= htmlspecialchars($hoSoCuaToi['KhuVucUuTien'] ?? '') ?>"
               placeholder="VD: Hẻm 12 Ung Văn Khiêm, P. Đông Xuyên (cách cổng Khu A < 500m)..."
               class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
      </div>

      <!-- Thói quen -->
      <div class="bg-bg-sub p-3.5 rounded-card border border-border-warm space-y-2">
        <label class="block text-xs font-semibold text-text-main">Thói quen &amp; Lối sống</label>
        <div class="flex items-center gap-6 text-xs text-text-main">
          <label class="inline-flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="hut_thuoc" value="1" <?= !empty($hoSoCuaToi['HutThuoc'])?'checked':'' ?> class="rounded border-border-warm text-accent">
            <span>Có hút thuốc</span>
          </label>
          <label class="inline-flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="nuoi_thu_cung" value="1" <?= !empty($hoSoCuaToi['NuoiThuCung'])?'checked':'' ?> class="rounded border-border-warm text-accent">
            <span>Nuôi / Thích thú cưng</span>
          </label>
        </div>
      </div>

      <!-- Mô tả bản thân -->
      <div>
        <label class="block text-xs font-semibold text-text-main mb-1">Mô tả bản thân &amp; Yêu cầu bạn cùng phòng</label>
        <textarea name="mo_ta" rows="4" required
                  placeholder="VD: Mình là sinh viên năm 2 Khoa CNTT, tính tình vui vẻ, sống ngăn nắp, cần tìm 1 bạn nam cùng khoa để thuê chung phòng trọ gác đúc..."
                  class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent resize-none"><?= htmlspecialchars($hoSoCuaToi['MoTaBanThan'] ?? '') ?></textarea>
      </div>

      <div class="flex items-center justify-end gap-3 pt-2 border-t border-border-warm">
        <button type="button" onclick="closeRoommateModal()" class="px-4 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">Hủy</button>
        <button type="submit" class="px-5 py-2 rounded-btn bg-accent text-white font-semibold text-xs shadow-sm transition-all duration-200 active:scale-[0.98]">
          <?= $hoSoCuaToi ? 'Lưu thay đổi' : 'Đăng tin tìm bạn' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openRoommateModal() {
  document.getElementById('roommate-modal').classList.remove('hidden');
}
function closeRoommateModal() {
  document.getElementById('roommate-modal').classList.add('hidden');
}
function previewRoommateAvatar(input) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) {
    alert('Ảnh quá lớn (tối đa 5 MB).');
    input.value = '';
    return;
  }
  const reader = new FileReader();
  reader.onload = e => {
    const prev = document.getElementById('roommate-preview-avatar');
    if (prev) prev.src = e.target.result;
  };
  reader.readAsDataURL(file);
}
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeRoommateModal();
});
</script>

<?php include_once __DIR__ . '/dungchung/footer.php'; ?>\n