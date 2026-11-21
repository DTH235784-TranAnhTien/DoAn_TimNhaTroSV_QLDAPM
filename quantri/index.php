<?php
$tieuDeTrang = 'Cổng Quản Trị — CampusNest AGU';
$trangHienTai = 'admin';
require_once __DIR__ . '/../cauhinh/auth.php';
require_once __DIR__ . '/../cauhinh/database.php';

if (!daDangNhap()) {
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php?tb=' . urlencode('Vui lòng đăng nhập để tiếp tục.'));
    exit();
}

$nd = nguoiDungHienTai();
$nd_hoten = $nd['hoten'] ?? 'Admin';
$nd_anh   = $nd['anhdaidien'] ?? '/DoAn_TimNhaTroSV_QLDAPM/assets/images/default-avatar.png';
$csrfToken = taoCsrfToken();

// Hàm chuẩn hóa đường dẫn ảnh hiển thị từ thư mục /quantri/
function chuanHoaAnh(?string $path, string $macDinh = '/DoAn_TimNhaTroSV_QLDAPM/assets/images/default-avatar.png'): string
{
    if (empty($path)) {
        return $macDinh;
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
        return $path;
    }
    return '/DoAn_TimNhaTroSV_QLDAPM/' . ltrim($path, './');
}

// Chỉ QuanTriVien được vào Admin Portal
if (($nd['vaitro'] ?? '') !== 'QuanTriVien') {
    include_once __DIR__ . '/../dungchung/header.php';
    echo '<div class="max-w-[1280px] mx-auto px-4 py-16 text-center">';
    echo '<div class="w-12 h-12 rounded-full bg-status-red-bg border border-status-red-border text-status-red-text flex items-center justify-center mx-auto mb-4">';
    echo '<span class="material-symbols-outlined text-3xl text-rose-600">lock</span></div>';
    echo '<h1 class="text-xl font-bold text-text-main">Bạn không có quyền truy cập trang quản trị</h1>';
    echo '<a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="inline-block mt-4 px-5 py-2.5 rounded-btn bg-accent text-white font-semibold text-xs">Về trang chủ</a>';
    echo '</div>';
    include_once __DIR__ . '/../dungchung/footer.php';
    exit();
}

// XỬ LÝ HÀNH ĐỘNG POST KIỂM DUYỆT TIN TRỌ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kiemTraCsrfToken($_POST['csrf_token'] ?? '')) {
        datThongBao('danger', 'Phiên làm việc không hợp lệ (lỗi CSRF). Vui lòng thử lại.');
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=moderation');
        exit();
    }

    $hanhDong = $_POST['hanh_dong'] ?? '';
    $id = (int)($_POST['ma_phong'] ?? 0);

    if ($id > 0) {
        if ($hanhDong === 'duyet_tin') {
            $stmt = $conn->prepare("UPDATE PhongTro SET TrangThaiDuyet = 'DaDuyet' WHERE MaPhong = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            datThongBao('success', "Đã phê duyệt tin phòng trọ #$id thành công!");
        } elseif ($hanhDong === 'tuchoi_tin') {
            $stmt = $conn->prepare("UPDATE PhongTro SET TrangThaiDuyet = 'TuChoi' WHERE MaPhong = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            datThongBao('warning', "Đã từ chối tin phòng trọ #$id.");
        } elseif ($hanhDong === 'xoa_tin') {
            $stmt = $conn->prepare("DELETE FROM PhongTro WHERE MaPhong = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            datThongBao('info', "Đã xóa tin phòng trọ #$id khỏi hệ thống.");
        }
    }

    header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=moderation');
    exit();
}

// ================= TRUY VẤN DỮ LIỆU THỐNG KÊ & QUẢN LÝ =================
$kpiChoDuyet = (int)($conn->query("SELECT COUNT(*) AS c FROM PhongTro WHERE TrangThaiDuyet = 'ChoDuyet'")->fetch_assoc()['c'] ?? 0);
$kpiTongUser = (int)($conn->query("SELECT COUNT(*) AS c FROM NguoiDung")->fetch_assoc()['c'] ?? 0);
$kpiBaoCao   = (int)($conn->query("SELECT COUNT(*) AS c FROM BaoCaoViPham WHERE TrangThai = 'ChoXuLy'")->fetch_assoc()['c'] ?? 0);
$kpiDaDuyet  = (int)($conn->query("SELECT COUNT(*) AS c FROM PhongTro WHERE TrangThaiDuyet = 'DaDuyet'")->fetch_assoc()['c'] ?? 0);

// Dữ liệu biểu đồ 1: Số phòng mới theo tháng (6 tháng gần nhất)
$labelsThang = [];
$dataPhongThang = [];
$resChartThang = $conn->query("
    SELECT DATE_FORMAT(NgayDang, '%m/%Y') AS Thang, COUNT(*) AS SoLuong
    FROM PhongTro
    GROUP BY YEAR(NgayDang), MONTH(NgayDang)
    ORDER BY YEAR(NgayDang) ASC, MONTH(NgayDang) ASC
    LIMIT 6
");
if ($resChartThang) {
    while ($row = $resChartThang->fetch_assoc()) {
        $labelsThang[] = 'Tháng ' . $row['Thang'];
        $dataPhongThang[] = (int)$row['SoLuong'];
    }
}
if (empty($labelsThang)) {
    $labelsThang = ['Tháng ' . date('m/Y')];
    $dataPhongThang = [0];
}

// Dữ liệu biểu đồ 2: Tài khoản đăng ký theo tuần (6 tuần gần nhất)
$labelsTuan = [];
$dataUserTuan = [];
$resChartTuan = $conn->query("
    SELECT CONCAT('Tuần ', WEEK(NgayTao, 1), '/', YEAR(NgayTao)) AS Tuan, COUNT(*) AS SoLuong
    FROM NguoiDung
    GROUP BY YEARWEEK(NgayTao, 1)
    ORDER BY YEARWEEK(NgayTao, 1) ASC
    LIMIT 6
");
if ($resChartTuan) {
    while ($row = $resChartTuan->fetch_assoc()) {
        $labelsTuan[] = $row['Tuan'];
        $dataUserTuan[] = (int)$row['SoLuong'];
    }
}
if (empty($labelsTuan)) {
    $labelsTuan = ['Tuần hiện tại'];
    $dataUserTuan = [0];
}

// Dữ liệu biểu đồ 3: Tỉ lệ tình trạng phòng (ConPhong / DaThue / DangBaoTri)
$phongConTrong = (int)($conn->query("SELECT COUNT(*) AS c FROM PhongTro WHERE TrangThai = 'ConPhong'")->fetch_assoc()['c'] ?? 0);
$phongDaThue   = (int)($conn->query("SELECT COUNT(*) AS c FROM PhongTro WHERE TrangThai = 'DaThue'")->fetch_assoc()['c'] ?? 0);
$phongBaoTri   = (int)($conn->query("SELECT COUNT(*) AS c FROM PhongTro WHERE TrangThai = 'DangBaoTri'")->fetch_assoc()['c'] ?? 0);

// Danh sách tài khoản (kèm thông tin xác thực chủ trọ)
$resUsers = $conn->query("
    SELECT MaNguoiDung, HoTen, Email, SoDienThoai, VaiTro, TrangThai,
           SoCCCD, AnhXacThucCCCD, DaXacThucChinhChu,
           DATE_FORMAT(NgayTao, '%d/%m/%Y') AS NgayTaoF
    FROM NguoiDung
    ORDER BY MaNguoiDung DESC
");
$dsUsers = $resUsers ? $resUsers->fetch_all(MYSQLI_ASSOC) : [];

// Danh sách phòng trọ (kèm thông tin chủ trọ & trạng thái xác thực)
$sqlPhong = "
    SELECT p.*, n.TenNhaTro, n.DiaChi,
           u.MaNguoiDung AS MaChuTro, u.HoTen AS TenChuTro, u.SoDienThoai,
           u.SoCCCD, u.AnhXacThucCCCD, u.DaXacThucChinhChu,
           (SELECT h.DuongDan FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.LaAnhChinh = 1 LIMIT 1) AS AnhChinh
    FROM PhongTro p
    JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
    JOIN NguoiDung u ON n.MaChuTro = u.MaNguoiDung
    ORDER BY FIELD(p.TrangThaiDuyet, 'ChoDuyet', 'DaDuyet', 'TuChoi'), p.NgayDang DESC
";
$resPhong = $conn->query($sqlPhong);
$dsPhong = $resPhong ? $resPhong->fetch_all(MYSQLI_ASSOC) : [];

$dsChoDuyet = array_values(array_filter($dsPhong, fn($p) => $p['TrangThaiDuyet'] === 'ChoDuyet'));
$phongChoDuyetDauTien = $dsChoDuyet[0] ?? null;

// Lấy toàn bộ ảnh thực tế của phòng chờ duyệt đầu tiên
$dsAnhPhongDauTien = [];
if ($phongChoDuyetDauTien) {
    $maPhongDau = (int)$phongChoDuyetDauTien['MaPhong'];
    $resHinh = $conn->query("SELECT DuongDan, LaAnhChinh FROM HinhAnhPhongTro WHERE MaPhong = $maPhongDau ORDER BY LaAnhChinh DESC, MaHinh ASC");
    if ($resHinh) {
        $dsAnhPhongDauTien = $resHinh->fetch_all(MYSQLI_ASSOC);
    }
}

// Danh sách báo cáo vi phạm (kèm thông tin phòng & chủ trọ)
$resBc = $conn->query("
    SELECT b.*,
           u.HoTen AS TenNguoiBaoCao, u.Email AS EmailNguoiBaoCao,
           p.TenPhong, p.TrangThaiDuyet AS TrangThaiPhong,
           ct.MaNguoiDung AS MaChuTro, ct.HoTen AS TenChuTro, ct.TrangThai AS TrangThaiChuTro
    FROM BaoCaoViPham b
    LEFT JOIN NguoiDung u ON b.MaNguoiBaoCao = u.MaNguoiDung
    LEFT JOIN PhongTro p ON b.MaPhong = p.MaPhong
    LEFT JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
    LEFT JOIN NguoiDung ct ON n.MaChuTro = ct.MaNguoiDung
    ORDER BY FIELD(b.TrangThai, 'ChoXuLy', 'DaXuLy', 'BoQua'), b.NgayBaoCao DESC
");
$dsBaoCao = $resBc ? $resBc->fetch_all(MYSQLI_ASSOC) : [];

$activeTab = $_GET['tab'] ?? 'dashboard';
$flashMsg = layThongBao();

include_once __DIR__ . '/../dungchung/header.php';
?>

<!-- ===== ADMIN HEADER RIÊNG ===== -->
<header class="w-full bg-bg-sub border-b border-border-warm py-3.5 px-4 sm:px-6 lg:px-8">
  <div class="max-w-[1560px] mx-auto flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-card bg-accent text-white flex items-center justify-center font-bold text-sm">AG</div>
      <span class="text-base font-bold text-text-main">CampusNest AGU</span>
      <span class="px-2.5 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold">Cổng Quản Trị</span>
    </div>

    <div class="flex items-center gap-3">
      <a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="px-3 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">Xem trang sinh viên</a>
      <div class="flex items-center gap-2 pl-2 border-l border-border-warm">
        <div class="relative overflow-hidden w-7 h-7 rounded-full border border-border-warm shrink-0">
          <img loading="lazy" onerror="this.onerror=null; this.style.display='none';" alt="Admin" class="w-full h-full object-cover" src="<?= htmlspecialchars(chuanHoaAnh($nd_anh)) ?>">
        </div>
        <span class="text-xs font-semibold text-text-main hidden sm:inline"><?= htmlspecialchars($nd_hoten) ?></span>
      </div>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangxuat.php" class="px-3 py-1.5 rounded-btn border border-status-red-border bg-status-red-bg text-status-red-text font-semibold text-xs transition-all duration-200">Đăng xuất</a>
    </div>
  </div>
</header>

<div class="max-w-[1440px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1">
  <div class="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 items-start">
    <!-- SIDEBAR TRÁI -->
    <aside class="lg:sticky lg:top-4 bg-bg-sub rounded-card border border-border-warm p-3 space-y-1 lg:self-start">
      <div class="pb-3 mb-2 border-b border-border-warm hidden lg:block">
        <p class="text-xs font-bold uppercase tracking-wider text-text-sub">Bảng điều khiển</p>
        <p class="text-xs text-text-main font-semibold mt-1">Kiểm định CampusNest</p>
      </div>

      <nav class="flex lg:flex-col gap-1 overflow-x-auto lg:overflow-visible -mx-1 px-1 lg:mx-0 lg:px-0">
        <button id="admin-menu-dashboard" onclick="switchAdminTab('dashboard')" class="admin-menu-item <?= $activeTab==='dashboard'?'active':'' ?> flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200 active:scale-[0.98]">
          <span class="material-symbols-outlined">dashboard</span><span>Tổng quan</span>
        </button>
        <button id="admin-menu-moderation" onclick="switchAdminTab('moderation')" class="admin-menu-item <?= $activeTab==='moderation'?'active':'' ?> flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200 active:scale-[0.98]">
          <span class="material-symbols-outlined">fact_check</span><span>Kiểm duyệt tin</span>
          <?php if ($kpiChoDuyet > 0): ?>
          <span class="ml-auto px-1.5 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text text-[10px] font-bold tnum hidden lg:inline"><?= $kpiChoDuyet ?></span>
          <?php endif; ?>
        </button>
        <button id="admin-menu-fraud" onclick="switchAdminTab('fraud')" class="admin-menu-item <?= $activeTab==='fraud'?'active':'' ?> flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200 active:scale-[0.98]">
          <span class="material-symbols-outlined">report</span><span>Tố giác gian lận</span>
          <?php if ($kpiBaoCao > 0): ?>
          <span class="ml-auto px-1.5 py-0.5 rounded-full bg-status-red-bg text-status-red-text border border-status-red-border text-[10px] font-bold tnum hidden lg:inline"><?= $kpiBaoCao ?></span>
          <?php endif; ?>
        </button>
        <button id="admin-menu-users" onclick="switchAdminTab('users')" class="admin-menu-item <?= $activeTab==='users'?'active':'' ?> flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200 active:scale-[0.98]">
          <span class="material-symbols-outlined">manage_accounts</span><span>Quản lý tài khoản</span>
          <span class="ml-auto px-1.5 py-0.5 rounded-full bg-bg-main text-text-sub text-[10px] font-bold tnum hidden lg:inline"><?= count($dsUsers) ?></span>
        </button>
        <a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/quanly_tienich.php" class="admin-menu-item flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200 hover:bg-bg-main">
          <span class="material-symbols-outlined">category</span><span>Tiện ích & Nhãn</span>
        </a>
      </nav>
    </aside>

    <!-- CONTENT PHẢI -->
    <main class="min-w-0 space-y-5">
      <?php if ($flashMsg):
        $fLoai = $flashMsg['loai'] ?? 'info';
        $fClass = $fLoai === 'success'
          ? 'bg-status-green-bg text-accent border-accent/30'
          : ($fLoai === 'danger'
            ? 'bg-status-red-bg text-status-red-text border-status-red-border'
            : 'bg-status-amber-bg text-status-amber-text border-status-amber-text/30');
      ?>
      <div class="px-4 py-3 rounded-card border <?= $fClass ?> text-xs font-semibold flex items-center justify-between gap-2">
        <span><?= htmlspecialchars($flashMsg['noidung']) ?></span>
        <button type="button" onclick="this.parentElement.remove()" class="opacity-70 hover:opacity-100">✕</button>
      </div>
      <?php endif; ?>

      <!-- TAB 1: TỔNG QUAN & BIỂU ĐỒ THỐNG KÊ -->
      <section id="admin-section-dashboard" class="space-y-5 <?= $activeTab!=='dashboard'?'hidden':'' ?>">
        <div class="bg-bg-sub rounded-card border border-border-warm p-4">
          <div class="grid grid-cols-2 lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x divide-border-warm tnum">
            <div class="pb-3 lg:pb-0 lg:pr-6">
              <span class="text-xs text-text-sub block">Tin trọ chờ duyệt</span>
              <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-text-main"><?= str_pad($kpiChoDuyet, 2, '0', STR_PAD_LEFT) ?></span>
                <?php if ($kpiChoDuyet > 0): ?><span class="text-xs text-status-amber-text font-semibold">Cần xử lý</span><?php else: ?><span class="text-xs text-accent font-semibold">Đã xong</span><?php endif; ?>
              </div>
            </div>
            <div class="pb-3 lg:pb-0 lg:px-6 pl-4">
              <span class="text-xs text-text-sub block">Tổng tài khoản</span>
              <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-text-main"><?= str_pad($kpiTongUser, 2, '0', STR_PAD_LEFT) ?></span>
                <span class="text-xs text-text-sub">Chủ trọ + Sinh viên</span>
              </div>
            </div>
            <div class="pt-3 lg:pt-0 lg:px-6">
              <span class="text-xs text-text-sub block">Báo cáo gian lận</span>
              <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold <?= $kpiBaoCao > 0 ? 'text-status-red-text' : 'text-text-main' ?>"><?= str_pad($kpiBaoCao, 2, '0', STR_PAD_LEFT) ?></span>
                <span class="text-xs text-text-sub"><?= $kpiBaoCao > 0 ? 'Chưa xử lý' : 'Hệ thống ổn định' ?></span>
              </div>
            </div>
            <div class="pt-3 lg:pt-0 lg:pl-6 pl-4">
              <span class="text-xs text-text-sub block">Phòng trọ đã duyệt</span>
              <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-accent"><?= str_pad($kpiDaDuyet, 2, '0', STR_PAD_LEFT) ?></span>
                <span class="text-xs text-text-sub">Đang hiển thị</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 3 BIỂU ĐỒ THỐNG KÊ TRỰC QUAN (Chart.js) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
          <div class="bg-bg-main rounded-card border border-border-warm p-4 space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-text-sub">Phòng mới đăng theo tháng</h3>
            <div class="h-52">
              <canvas id="chartPhongTheoThang"></canvas>
            </div>
          </div>
          <div class="bg-bg-main rounded-card border border-border-warm p-4 space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-text-sub">Tài khoản đăng ký theo tuần</h3>
            <div class="h-52">
              <canvas id="chartUserTheoTuan"></canvas>
            </div>
          </div>
          <div class="bg-bg-main rounded-card border border-border-warm p-4 space-y-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-text-sub">Tỉ lệ tình trạng phòng trọ</h3>
            <div class="h-52 flex items-center justify-center">
              <canvas id="chartTyLePhong"></canvas>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <!-- Tố giác gần nhất -->
          <div class="bg-bg-main rounded-card border border-border-warm p-5 space-y-3">
            <div class="flex items-center justify-between pb-2.5 border-b border-border-warm">
              <h3 class="text-sm font-bold text-text-main flex items-center gap-1.5">
                <span class="material-symbols-outlined text-status-red-text">report</span>Tố giác gian lận gần nhất
              </h3>
              <span class="px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text text-xs font-semibold tnum"><?= $kpiBaoCao ?></span>
            </div>
            <?php if (empty($dsBaoCao)): ?>
            <div class="py-8 text-center text-text-sub space-y-1">
              <span class="material-symbols-outlined text-3xl block mx-auto text-accent">verified_user</span>
              <p class="text-xs font-medium">Chưa có báo cáo gian lận nào từ sinh viên.</p>
            </div>
            <?php else: ?>
            <div class="space-y-2">
              <?php foreach (array_slice($dsBaoCao, 0, 3) as $bc): ?>
              <div class="p-3 rounded-card bg-status-red-bg/50 border border-status-red-border text-xs">
                <strong class="text-text-main block">#BC-<?= $bc['MaBaoCao'] ?> · <?= htmlspecialchars($bc['LyDo']) ?></strong>
                <span class="text-text-sub block mt-1"><?= htmlspecialchars($bc['TenNguoiBaoCao'] ?? 'Sinh viên') ?> · <?= htmlspecialchars($bc['ChiTiet']) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <button type="button" onclick="switchAdminTab('fraud')" class="w-full py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">Xem tất cả tố giác →</button>
          </div>

          <!-- Tài khoản mới nhất -->
          <div class="bg-bg-main rounded-card border border-border-warm p-5 space-y-3">
            <div class="flex items-center justify-between pb-2.5 border-b border-border-warm">
              <h3 class="text-sm font-bold text-text-main flex items-center gap-1.5">
                <span class="material-symbols-outlined text-accent">manage_accounts</span>Tài khoản mới nhất
              </h3>
              <span class="text-xs text-text-sub tnum"><?= count($dsUsers) ?> tài khoản</span>
            </div>
            <div class="space-y-2">
              <?php foreach (array_slice($dsUsers, 0, 3) as $u):
                $vtLabel = ($u['VaiTro']==='QuanTriVien')?'Quản trị viên':(($u['VaiTro']==='ChuTro')?'Chủ trọ':'Sinh viên');
                $ttBadge = $u['TrangThai']==='HoatDong'
                  ? '<span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent text-[10px] font-semibold">Hoạt động</span>'
                  : '<span class="px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text text-[10px] font-semibold">Bị khóa</span>';
              ?>
              <div class="p-3 rounded-card bg-bg-sub border border-border-warm text-xs flex items-center justify-between">
                <div>
                  <strong class="text-text-main block"><?= htmlspecialchars($u['HoTen']) ?></strong>
                  <span class="text-text-sub"><?= htmlspecialchars($u['Email']) ?> · <?= $vtLabel ?></span>
                </div>
                <?= $ttBadge ?>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" onclick="switchAdminTab('users')" class="w-full py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">Quản lý tất cả tài khoản →</button>
          </div>
        </div>
      </section>

      <!-- TAB 2: KIỂM DUYỆT TIN TRỌ -->
      <section id="admin-section-moderation" class="space-y-5 <?= $activeTab!=='moderation'?'hidden':'' ?>">
        <?php if ($phongChoDuyetDauTien):
          $pGia  = number_format($phongChoDuyetDauTien['GiaThue'], 0, ',', '.');
          $pDien = $phongChoDuyetDauTien['DienTich'] ? $phongChoDuyetDauTien['DienTich'] . 'm²' : '--';
          $daXacThuc = !empty($phongChoDuyetDauTien['DaXacThucChinhChu']);
        ?>
        <!-- CARD THẨM ĐỊNH CHI TIẾT PHÒNG ĐẦU TIÊN ĐANG CHỜ DUYỆT -->
        <div class="bg-bg-main rounded-card border border-border-warm overflow-hidden">
          <div class="bg-bg-sub px-5 py-3 border-b border-border-warm flex flex-wrap items-center justify-between gap-2 text-xs tnum">
            <div class="flex items-center gap-3">
              <span class="font-bold text-text-main">Mã tin: #P-<?= $phongChoDuyetDauTien['MaPhong'] ?></span>
              <span class="text-text-sub">·</span>
              <span class="text-text-sub">Ngày đăng: <?= date('d/m/Y H:i', strtotime($phongChoDuyetDauTien['NgayDang'])) ?></span>
            </div>
            <span class="px-2.5 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text font-semibold inline-flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-status-amber-text"></span><span>Đang chờ duyệt (Còn <?= count($dsChoDuyet) ?> tin)</span>
            </span>
          </div>

          <div class="p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-bold text-text-main"><?= htmlspecialchars($phongChoDuyetDauTien['TenPhong']) ?></h2>
                <p class="text-xs text-text-sub mt-1 tnum">
                  Nhà trọ: <strong><?= htmlspecialchars($phongChoDuyetDauTien['TenNhaTro']) ?></strong> ·
                  Địa chỉ: <?= htmlspecialchars($phongChoDuyetDauTien['DiaChi']) ?>
                </p>
              </div>
              <div class="text-left sm:text-right shrink-0 tnum">
                <span class="text-xl font-bold text-text-main block"><?= $pGia ?> đ/tháng</span>
                <span class="text-xs text-text-sub">Diện tích: <?= $pDien ?></span>
              </div>
            </div>

            <!-- Thông tin chi phí dịch vụ -->
            <div class="grid grid-cols-3 gap-2 p-3 rounded-card bg-bg-sub border border-border-warm text-xs tnum">
              <div><span class="text-text-sub block">Tiền điện</span><strong class="text-text-main"><?= number_format((float)$phongChoDuyetDauTien['TienDien'], 0, ',', '.') ?> đ/kWh</strong></div>
              <div><span class="text-text-sub block">Tiền nước</span><strong class="text-text-main"><?= number_format((float)$phongChoDuyetDauTien['TienNuoc'], 0, ',', '.') ?> đ/m³</strong></div>
              <div><span class="text-text-sub block">Wifi</span><strong class="text-text-main"><?= number_format((float)$phongChoDuyetDauTien['TienWifi'], 0, ',', '.') ?> đ/tháng</strong></div>
            </div>

            <!-- Hồ sơ kiểm tra chủ trọ trước khi duyệt -->
            <div class="p-3.5 rounded-card bg-bg-sub border border-border-warm flex flex-wrap items-center justify-between gap-3 text-xs">
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-text-main">Chủ trọ: <?= htmlspecialchars($phongChoDuyetDauTien['TenChuTro']) ?></span>
                  <?php if ($daXacThuc): ?>
                    <span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent font-semibold">✓ Đã xác thực chính chủ</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text font-semibold">Chưa xác thực chính chủ</span>
                  <?php endif; ?>
                </div>
                <p class="text-text-sub tnum">
                  SĐT: <strong><?= htmlspecialchars($phongChoDuyetDauTien['SoDienThoai'] ?: 'Chưa cập nhật') ?></strong> ·
                  Số CCCD: <strong><?= htmlspecialchars($phongChoDuyetDauTien['SoCCCD'] ?: 'Chưa nộp CCCD') ?></strong>
                </p>
              </div>
              <?php if (!empty($phongChoDuyetDauTien['AnhXacThucCCCD'])): ?>
                <a href="<?= htmlspecialchars(chuanHoaAnh($phongChoDuyetDauTien['AnhXacThucCCCD'])) ?>" target="_blank"
                   class="px-3 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold hover:border-accent">
                  Xem ảnh CCCD
                </a>
              <?php endif; ?>
            </div>

            <!-- Hình ảnh thực tế phòng trọ -->
            <div>
              <p class="text-xs font-bold text-text-main mb-2">Hình ảnh đính kèm (<?= count($dsAnhPhongDauTien) ?> ảnh)</p>
              <?php if (!empty($dsAnhPhongDauTien)): ?>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                  <?php foreach ($dsAnhPhongDauTien as $hinh):
                    $urlHinh = chuanHoaAnh($hinh['DuongDan']);
                  ?>
                  <a href="<?= htmlspecialchars($urlHinh) ?>" target="_blank" class="block relative aspect-video rounded-card overflow-hidden border border-border-warm bg-bg-sub group">
                    <img src="<?= htmlspecialchars($urlHinh) ?>" alt="Ảnh phòng trọ" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                    <?php if (!empty($hinh['LaAnhChinh'])): ?>
                      <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded bg-black/70 text-white text-[10px] font-semibold">Ảnh chính</span>
                    <?php endif; ?>
                  </a>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <p class="text-xs text-status-amber-text italic">Phòng trọ này chưa tải lên hình ảnh thực tế.</p>
              <?php endif; ?>
            </div>

            <?php if ($phongChoDuyetDauTien['MoTa']): ?>
            <p class="text-xs text-text-main bg-bg-sub p-3 rounded-card leading-relaxed"><?= nl2br(htmlspecialchars($phongChoDuyetDauTien['MoTa'])) ?></p>
            <?php endif; ?>

            <div class="pt-3 border-t border-border-warm flex flex-wrap items-center justify-between gap-2">
              <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $phongChoDuyetDauTien['MaPhong'] ?>" target="_blank"
                 class="px-3.5 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">visibility</span>Xem trang chi tiết
              </a>
              <div class="flex items-center gap-2">
                <form method="POST">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="hanh_dong" value="tuchoi_tin">
                  <input type="hidden" name="ma_phong" value="<?= $phongChoDuyetDauTien['MaPhong'] ?>">
                  <button type="submit" class="px-3.5 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">Từ chối</button>
                </form>
                <form method="POST">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="hanh_dong" value="duyet_tin">
                  <input type="hidden" name="ma_phong" value="<?= $phongChoDuyetDauTien['MaPhong'] ?>">
                  <button type="submit" class="px-5 py-2 rounded-btn bg-accent text-white font-semibold text-xs">Duyệt tin & Cấp phép</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="py-12 text-center space-y-3 bg-bg-main rounded-card border border-border-warm border-dashed">
          <span class="material-symbols-outlined text-4xl text-accent block mx-auto">task_alt</span>
          <p class="text-base font-bold text-text-main">Không có tin đăng nào chờ thẩm định</p>
          <p class="text-xs text-text-sub">Tất cả tin đăng phòng trọ đều đã được xử lý hoặc chủ trọ chưa đăng tin mới.</p>
        </div>
        <?php endif; ?>

        <!-- BẢNG QUẢN LÝ TOÀN BỘ TIN TRỌ -->
        <div class="bg-bg-main rounded-card border border-border-warm p-5 space-y-4">
          <div class="flex items-center justify-between gap-2">
            <h3 class="text-base font-bold text-text-main">Quản lý toàn bộ tin trọ</h3>
            <span class="px-2.5 py-0.5 rounded-full bg-bg-sub border border-border-warm text-text-main text-xs font-semibold tnum"><?= count($dsPhong) ?> tin</span>
          </div>

          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <input id="admin-search" type="text" oninput="filterAdminListings()" placeholder="Tìm theo tên phòng, địa chỉ, chủ trọ..." class="w-full sm:w-2/3 px-3 py-2 border border-border-warm bg-bg-main rounded-card text-sm text-text-main focus:border-accent outline-none">
            <select id="admin-status-filter" onchange="filterAdminListings()" class="w-full sm:w-1/3 px-3 py-2 border border-border-warm bg-bg-main rounded-card text-sm text-text-main focus:border-accent outline-none">
              <option value="all">Tất cả trạng thái</option>
              <option value="ChoDuyet">Chờ duyệt</option>
              <option value="DaDuyet">Đã duyệt</option>
              <option value="TuChoi">Từ chối</option>
            </select>
          </div>

          <?php if (empty($dsPhong)): ?>
          <div class="py-12 text-center text-text-sub space-y-2">
            <span class="material-symbols-outlined text-4xl block mx-auto">apartment</span>
            <p class="text-sm font-semibold text-text-main">Chưa có tin trọ nào trên hệ thống</p>
            <p class="text-xs">Khi chủ trọ đăng tin, danh sách sẽ hiển thị tại đây để quản trị viên kiểm duyệt.</p>
          </div>
          <?php else: ?>
          <div class="overflow-x-auto border border-border-warm rounded-card">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-bg-sub text-xs font-bold text-text-sub border-b border-border-warm">
                  <th class="py-2.5 px-3">Mã</th>
                  <th class="py-2.5 px-3">Tiêu đề phòng</th>
                  <th class="py-2.5 px-3 hidden md:table-cell">Địa chỉ</th>
                  <th class="py-2.5 px-3 tnum">Giá thuê</th>
                  <th class="py-2.5 px-3 hidden md:table-cell">Chủ trọ</th>
                  <th class="py-2.5 px-3">Duyệt</th>
                  <th class="py-2.5 px-3 text-right">Hành động</th>
                </tr>
              </thead>
              <tbody id="listings-tbody">
              <?php foreach ($dsPhong as $p):
                $pF = number_format($p['GiaThue'], 0, ',', '.');
                $dBadge = $p['TrangThaiDuyet'] === 'DaDuyet'
                  ? '<span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold">Đã duyệt</span>'
                  : ($p['TrangThaiDuyet'] === 'TuChoi'
                    ? '<span class="px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text text-xs font-semibold">Từ chối</span>'
                    : '<span class="px-2 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text text-xs font-semibold">Chờ duyệt</span>');
              ?>
              <tr class="listing-row border-t border-border-warm hover:bg-bg-sub/40 text-xs"
                  data-search="<?= strtolower(htmlspecialchars($p['TenPhong'] . ' ' . $p['DiaChi'] . ' ' . $p['TenChuTro'])) ?>"
                  data-status="<?= $p['TrangThaiDuyet'] ?>">
                <td class="py-2.5 px-3 font-mono font-bold text-text-main">#<?= $p['MaPhong'] ?></td>
                <td class="py-2.5 px-3 font-semibold text-text-main">
                  <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $p['MaPhong'] ?>" target="_blank" class="hover:text-accent"><?= htmlspecialchars($p['TenPhong']) ?></a>
                </td>
                <td class="py-2.5 px-3 text-text-sub hidden md:table-cell"><?= htmlspecialchars($p['DiaChi']) ?></td>
                <td class="py-2.5 px-3 tnum text-text-main font-semibold"><?= $pF ?> đ</td>
                <td class="py-2.5 px-3 text-text-sub hidden md:table-cell"><?= htmlspecialchars($p['TenChuTro']) ?></td>
                <td class="py-2.5 px-3"><?= $dBadge ?></td>
                <td class="py-2.5 px-3 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <?php if ($p['TrangThaiDuyet'] !== 'DaDuyet'): ?>
                    <form method="POST" class="inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                      <input type="hidden" name="hanh_dong" value="duyet_tin">
                      <input type="hidden" name="ma_phong" value="<?= $p['MaPhong'] ?>">
                      <button type="submit" title="Duyệt tin" class="w-7 h-7 rounded-btn bg-accent text-white flex items-center justify-center">
                        <span class="material-symbols-outlined text-[15px]">check</span>
                      </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($p['TrangThaiDuyet'] !== 'TuChoi'): ?>
                    <form method="POST" class="inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                      <input type="hidden" name="hanh_dong" value="tuchoi_tin">
                      <input type="hidden" name="ma_phong" value="<?= $p['MaPhong'] ?>">
                      <button type="submit" title="Từ chối" class="w-7 h-7 rounded-btn border border-border-warm bg-bg-main text-text-sub flex items-center justify-center hover:text-text-main">
                        <span class="material-symbols-outlined text-[15px]">close</span>
                      </button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa tin trọ này?')">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                      <input type="hidden" name="hanh_dong" value="xoa_tin">
                      <input type="hidden" name="ma_phong" value="<?= $p['MaPhong'] ?>">
                      <button type="submit" title="Xóa tin" class="w-7 h-7 rounded-btn border border-status-red-border bg-status-red-bg text-status-red-text flex items-center justify-center">
                        <span class="material-symbols-outlined text-[15px]">delete</span>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </section>

      <!-- TAB 3: TỐ GIÁC GIAN LẬN -->
      <section id="admin-section-fraud" class="space-y-5 <?= $activeTab!=='fraud'?'hidden':'' ?>">
        <div class="bg-bg-main rounded-card border border-border-warm p-5">
          <div class="pb-3 mb-4 border-b border-border-warm flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-text-main">Tố giác gian lận & Báo cáo vi phạm</h3>
              <p class="text-xs text-text-sub mt-0.5"><?= count($dsBaoCao) ?> báo cáo từ sinh viên</p>
            </div>
          </div>

          <?php if (empty($dsBaoCao)): ?>
          <div class="py-16 text-center space-y-3 bg-bg-main rounded-card border border-border-warm border-dashed">
            <span class="material-symbols-outlined text-4xl text-text-sub block mx-auto">verified_user</span>
            <p class="text-base font-bold text-text-main">Chưa có báo cáo vi phạm nào</p>
            <p class="text-xs text-text-sub">Khi sinh viên gửi tố giác phòng trọ hoặc gian lận tiền cọc, thông tin sẽ hiển thị tại đây để xử lý.</p>
          </div>
          <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($dsBaoCao as $bc):
              $ttBc = $bc['TrangThai'];
              $bcBadge = $ttBc === 'DaXuLy'
                ? '<span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent text-[10px] font-bold">Đã xử lý</span>'
                : ($ttBc === 'BoQua'
                  ? '<span class="px-2 py-0.5 rounded-full bg-bg-sub text-text-sub border border-border-warm text-[10px] font-bold">Đã bỏ qua</span>'
                  : '<span class="px-2 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text text-[10px] font-bold">Chờ xử lý</span>');
            ?>
            <div class="p-4 rounded-card <?= $ttBc === 'ChoXuLy' ? 'bg-status-red-bg/40 border-status-red-border' : 'bg-bg-sub border-border-warm' ?> border space-y-2.5">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text border border-status-red-border text-[10px] font-bold">#BC-<?= $bc['MaBaoCao'] ?></span>
                  <strong class="text-xs text-text-main"><?= htmlspecialchars($bc['LyDo']) ?></strong>
                  <?= $bcBadge ?>
                </div>
                <span class="text-xs text-text-sub tnum"><?= date('d/m/Y H:i', strtotime($bc['NgayBaoCao'])) ?></span>
              </div>

              <p class="text-xs text-text-main leading-relaxed">
                <strong>Phòng bị báo cáo:</strong>
                <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= (int)$bc['MaPhong'] ?>" target="_blank" class="text-accent underline"><?= htmlspecialchars($bc['TenPhong'] ?? ('#' . $bc['MaPhong'])) ?></a>
                · <strong>Chủ trọ:</strong> <?= htmlspecialchars($bc['TenChuTro'] ?? 'Không rõ') ?>
              </p>
              <p class="text-xs text-text-main leading-relaxed">
                <strong>Người báo cáo (<?= htmlspecialchars($bc['TenNguoiBaoCao'] ?? 'Sinh viên') ?>):</strong>
                <?= htmlspecialchars($bc['ChiTiet'] ?: 'Không có mô tả chi tiết.') ?>
              </p>

              <?php if ($ttBc === 'ChoXuLy'): ?>
              <div class="pt-2 border-t border-border-warm/60 flex flex-wrap items-center justify-end gap-2">
                <form action="/DoAn_TimNhaTroSV_QLDAPM/quantri/xuly_baocao.php" method="POST" class="inline">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="ma_baocao" value="<?= $bc['MaBaoCao'] ?>">
                  <input type="hidden" name="hanh_dong" value="bo_qua">
                  <button type="submit" class="px-3 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">Bỏ qua</button>
                </form>
                <form action="/DoAn_TimNhaTroSV_QLDAPM/quantri/xuly_baocao.php" method="POST" class="inline">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="ma_baocao" value="<?= $bc['MaBaoCao'] ?>">
                  <input type="hidden" name="hanh_dong" value="da_xu_ly">
                  <button type="submit" class="px-3 py-1.5 rounded-btn bg-accent text-white font-semibold text-xs">Đánh dấu đã xử lý</button>
                </form>
                <?php if (!empty($bc['MaChuTro'])): ?>
                <form action="/DoAn_TimNhaTroSV_QLDAPM/quantri/xuly_baocao.php" method="POST" class="inline" onsubmit="return confirm('Xử lý báo cáo này đồng thời KHÓA tài khoản chủ trọ và gỡ tin phòng?')">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="ma_baocao" value="<?= $bc['MaBaoCao'] ?>">
                  <input type="hidden" name="hanh_dong" value="khoa_chu_tro">
                  <button type="submit" class="px-3 py-1.5 rounded-btn border border-status-red-border bg-status-red-bg text-status-red-text font-semibold text-xs">Xử lý & Khóa chủ trọ</button>
                </form>
                <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </section>

      <!-- TAB 4: QUẢN LÝ TÀI KHOẢN -->
      <section id="admin-section-users" class="space-y-5 <?= $activeTab!=='users'?'hidden':'' ?>">
        <div class="bg-bg-main rounded-card border border-border-warm overflow-hidden">
          <!-- Toolbar -->
          <div class="bg-bg-sub px-5 py-3 border-b border-border-warm flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-wrap">
              <div class="flex items-center gap-1.5 bg-bg-main border border-border-warm rounded-btn px-3 py-1.5">
                <span class="material-symbols-outlined text-text-sub text-[18px]">search</span>
                <input id="user-search" type="search" placeholder="Tên, email, vai trò..." class="bg-transparent outline-none text-xs text-text-main w-40" oninput="filterAdminUsers()">
              </div>
              <select id="user-role-filter" onchange="filterAdminUsers()" class="text-xs px-2 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main outline-none">
                <option value="all">Tất cả vai trò</option>
                <option value="ChuTro">Chủ trọ</option>
                <option value="SinhVien">Sinh viên</option>
                <option value="QuanTriVien">Quản trị viên</option>
              </select>
              <select id="user-status-filter" onchange="filterAdminUsers()" class="text-xs px-2 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main outline-none">
                <option value="all">Tất cả trạng thái</option>
                <option value="HoatDong">Hoạt động</option>
                <option value="BiKhoa">Bị khóa</option>
              </select>
            </div>
            <div class="flex items-center gap-2">
              <span id="users-count" class="text-xs text-text-sub tnum"><?= count($dsUsers) ?> tài khoản</span>
              <button onclick="openUserModal()" type="button" class="flex items-center gap-1.5 px-3 py-1.5 rounded-btn bg-accent text-white font-semibold text-xs transition-all duration-200 active:scale-[0.98]">
                <span class="material-symbols-outlined text-[16px]">person_add</span>Thêm tài khoản
              </button>
            </div>
          </div>

          <!-- Bảng Tài khoản -->
          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead class="bg-bg-sub/60">
                <tr class="text-[11px] font-bold uppercase tracking-wider text-text-sub">
                  <th class="py-2.5 px-3">Họ tên</th>
                  <th class="py-2.5 px-3">Email</th>
                  <th class="py-2.5 px-3">Vai trò</th>
                  <th class="py-2.5 px-3 hidden md:table-cell">Xác thực chủ trọ</th>
                  <th class="py-2.5 px-3 hidden lg:table-cell">Ngày tạo</th>
                  <th class="py-2.5 px-3">Trạng thái</th>
                  <th class="py-2.5 px-3 text-right">Hành động</th>
                </tr>
              </thead>
              <tbody id="users-tbody">
              <?php foreach ($dsUsers as $u):
                $vt = $u['VaiTro'];
                $vtLabel = ($vt === 'QuanTriVien') ? 'Quản trị viên' : (($vt === 'ChuTro') ? 'Chủ trọ' : 'Sinh viên');
                $vtBadge = ($vt === 'QuanTriVien')
                  ? 'bg-status-green-bg text-accent'
                  : (($vt === 'ChuTro') ? 'bg-status-amber-bg text-status-amber-text' : 'bg-bg-sub text-text-sub border border-border-warm');
                $ttBadge = ($u['TrangThai'] === 'HoatDong')
                  ? '<span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold">Hoạt động</span>'
                  : '<span class="px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text border border-status-red-border text-xs font-semibold">Bị khóa</span>';
              ?>
              <tr class="user-row border-t border-border-warm hover:bg-bg-sub/40 text-xs"
                  data-search="<?= strtolower(htmlspecialchars($u['HoTen'] . ' ' . $u['Email'] . ' ' . $vtLabel)) ?>"
                  data-role="<?= $u['VaiTro'] ?>"
                  data-status="<?= $u['TrangThai'] ?>">
                <td class="py-2.5 px-3 font-semibold text-text-main"><?= htmlspecialchars($u['HoTen']) ?></td>
                <td class="py-2.5 px-3 text-text-sub"><?= htmlspecialchars($u['Email']) ?></td>
                <td class="py-2.5 px-3"><span class="px-2 py-0.5 rounded-full font-semibold <?= $vtBadge ?>"><?= $vtLabel ?></span></td>
                <td class="py-2.5 px-3 hidden md:table-cell">
                  <?php if ($vt === 'ChuTro'): ?>
                    <div class="flex items-center gap-1.5">
                      <?php if (!empty($u['DaXacThucChinhChu'])): ?>
                        <span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent font-semibold">✓ Chính chủ</span>
                      <?php else: ?>
                        <span class="px-2 py-0.5 rounded-full bg-bg-sub text-text-sub border border-border-warm">Chưa duyệt</span>
                      <?php endif; ?>
                      <?php if (!empty($u['AnhXacThucCCCD'])): ?>
                        <a href="<?= htmlspecialchars(chuanHoaAnh($u['AnhXacThucCCCD'])) ?>" target="_blank" class="text-accent underline">Ảnh CCCD</a>
                      <?php endif; ?>
                    </div>
                  <?php else: ?>
                    <span class="text-text-sub">—</span>
                  <?php endif; ?>
                </td>
                <td class="py-2.5 px-3 text-text-sub tnum hidden lg:table-cell"><?= $u['NgayTaoF'] ?></td>
                <td class="py-2.5 px-3"><?= $ttBadge ?></td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center justify-end gap-1.5">
                    <?php if ($vt === 'ChuTro'): ?>
                    <form action="/DoAn_TimNhaTroSV_QLDAPM/quantri/xuly_taikhoan.php" method="POST" class="inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                      <input type="hidden" name="hanh_dong" value="xac_thuc_chu_tro">
                      <input type="hidden" name="ma_user" value="<?= $u['MaNguoiDung'] ?>">
                      <button type="submit" title="<?= !empty($u['DaXacThucChinhChu']) ? 'Hủy xác thực chính chủ' : 'Xác thực chính chủ' ?>"
                        class="w-7 h-7 rounded-btn border <?= !empty($u['DaXacThucChinhChu']) ? 'border-accent bg-status-green-bg text-accent' : 'border-border-warm bg-bg-main text-text-sub hover:text-accent' ?> flex items-center justify-center">
                        <span class="material-symbols-outlined text-[16px]">verified</span>
                      </button>
                    </form>
                    <?php endif; ?>

                    <form action="/DoAn_TimNhaTroSV_QLDAPM/quantri/xuly_taikhoan.php" method="POST" class="inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                      <input type="hidden" name="hanh_dong" value="khoa_user">
                      <input type="hidden" name="ma_user" value="<?= $u['MaNguoiDung'] ?>">
                      <button type="submit" title="<?= $u['TrangThai']==='HoatDong'?'Khóa tài khoản':'Mở khóa tài khoản' ?>"
                        class="w-7 h-7 rounded-btn border <?= $u['TrangThai']==='HoatDong'?'border-status-amber-text bg-status-amber-bg text-status-amber-text':'border-status-green-bg bg-status-green-bg text-accent' ?> flex items-center justify-center">
                        <span class="material-symbols-outlined text-[16px]"><?= $u['TrangThai']==='HoatDong'?'lock':'lock_open' ?></span>
                      </button>
                    </form>

                    <button type="button" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)" title="Sửa tài khoản"
                      class="w-7 h-7 rounded-btn border border-border-warm bg-bg-main text-text-main flex items-center justify-center hover:border-accent">
                      <span class="material-symbols-outlined text-[16px]">edit</span>
                    </button>

                    <?php if ($u['MaNguoiDung'] != $nd['mannd']): ?>
                    <form action="/DoAn_TimNhaTroSV_QLDAPM/quantri/xuly_taikhoan.php" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa tài khoản này?')">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                      <input type="hidden" name="hanh_dong" value="xoa_user">
                      <input type="hidden" name="ma_user" value="<?= $u['MaNguoiDung'] ?>">
                      <button type="submit" title="Xóa tài khoản"
                        class="w-7 h-7 rounded-btn border border-status-red-border bg-status-red-bg text-status-red-text flex items-center justify-center">
                        <span class="material-symbols-outlined text-[16px]">delete</span>
                      </button>
                    </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Modal thêm/sửa tài khoản -->
        <div id="user-edit-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
          <div class="bg-bg-main rounded-card border border-border-warm shadow-lg w-full max-w-md">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-border-warm">
              <h2 id="user-modal-title" class="text-sm font-bold text-text-main">Thêm tài khoản</h2>
              <button onclick="closeUserModal()" type="button" class="w-7 h-7 rounded-btn border border-border-warm flex items-center justify-center text-text-sub hover:text-text-main">
                <span class="material-symbols-outlined text-[18px]">close</span>
              </button>
            </div>
            <form id="user-edit-form" action="/DoAn_TimNhaTroSV_QLDAPM/quantri/xuly_taikhoan.php" method="POST" class="p-5 space-y-3">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
              <input type="hidden" name="hanh_dong" value="luu_user">
              <input type="hidden" id="user-id" name="ma_user" value="0">
              <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                  <label class="text-xs font-semibold text-text-main block mb-1">Họ và tên <span class="text-rose-500">*</span></label>
                  <input id="user-hoten" name="hoten" type="text" required class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
                </div>
                <div class="col-span-2">
                  <label class="text-xs font-semibold text-text-main block mb-1">Email <span class="text-rose-500">*</span></label>
                  <input id="user-email" name="email" type="email" required class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
                </div>
                <div>
                  <label class="text-xs font-semibold text-text-main block mb-1">Vai trò <span class="text-rose-500">*</span></label>
                  <select id="user-vaitro" name="vaitro" required class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
                    <option value="SinhVien">Sinh viên</option>
                    <option value="ChuTro">Chủ trọ</option>
                    <option value="QuanTriVien">Quản trị viên</option>
                  </select>
                </div>
                <div>
                  <label class="text-xs font-semibold text-text-main block mb-1">Trạng thái</label>
                  <select id="user-trangthai" name="trangthai" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
                    <option value="HoatDong">Hoạt động</option>
                    <option value="BiKhoa">Bị khóa</option>
                  </select>
                </div>
                <div class="col-span-2">
                  <label class="text-xs font-semibold text-text-main block mb-1">Số điện thoại</label>
                  <input id="user-sdt" name="sdt" type="text" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
                </div>
                <div class="col-span-2 flex items-center gap-2 pt-1">
                  <input id="user-xacthuc" name="da_xac_thuc" type="checkbox" value="1" class="rounded border-border-warm">
                  <label for="user-xacthuc" class="text-xs text-text-main">Đã xác thực chính chủ (áp dụng cho Chủ trọ)</label>
                </div>
              </div>
              <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeUserModal()" class="px-4 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs">Hủy</button>
                <button type="submit" class="px-4 py-2 rounded-btn bg-accent text-white font-semibold text-xs">Lưu tài khoản</button>
              </div>
            </form>
          </div>
        </div>
      </section>
    </main>
  </div>
</div>

<!-- Thư viện Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  function switchAdminTab(tab) {
    const tabs = ['dashboard', 'moderation', 'fraud', 'users'];
    tabs.forEach(t => {
      const section = document.getElementById('admin-section-' + t);
      const menu = document.getElementById('admin-menu-' + t);
      if (section) section.classList.toggle('hidden', t !== tab);
      if (menu) {
        if (t === tab) { menu.classList.add('active'); }
        else { menu.classList.remove('active'); }
      }
    });
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);
  }

  function filterAdminListings() {
    const q = (document.getElementById('admin-search')?.value || '').toLowerCase();
    const st = document.getElementById('admin-status-filter')?.value || 'all';
    document.querySelectorAll('.listing-row').forEach(r => {
      const text = r.getAttribute('data-search') || '';
      const status = r.getAttribute('data-status') || '';
      r.style.display = ((!q || text.includes(q)) && (st === 'all' || status === st)) ? '' : 'none';
    });
  }

  function filterAdminUsers() {
    const q = (document.getElementById('user-search')?.value || '').toLowerCase();
    const role = document.getElementById('user-role-filter')?.value || 'all';
    const st = document.getElementById('user-status-filter')?.value || 'all';
    document.querySelectorAll('.user-row').forEach(r => {
      const text = r.getAttribute('data-search') || '';
      const rRole = r.getAttribute('data-role') || '';
      const rSt = r.getAttribute('data-status') || '';
      r.style.display = ((!q || text.includes(q)) && (role === 'all' || rRole === role) && (st === 'all' || rSt === st)) ? '' : 'none';
    });
  }

  function openUserModal() {
    document.getElementById('user-modal-title').textContent = 'Thêm tài khoản mới';
    document.getElementById('user-id').value = '0';
    document.getElementById('user-hoten').value = '';
    document.getElementById('user-email').value = '';
    document.getElementById('user-sdt').value = '';
    document.getElementById('user-vaitro').value = 'SinhVien';
    document.getElementById('user-trangthai').value = 'HoatDong';
    document.getElementById('user-xacthuc').checked = false;
    document.getElementById('user-edit-modal').classList.remove('hidden');
  }

  function editUser(u) {
    document.getElementById('user-modal-title').textContent = 'Sửa tài khoản — ' + u.HoTen;
    document.getElementById('user-id').value = u.MaNguoiDung;
    document.getElementById('user-hoten').value = u.HoTen;
    document.getElementById('user-email').value = u.Email;
    document.getElementById('user-sdt').value = u.SoDienThoai || '';
    document.getElementById('user-vaitro').value = u.VaiTro;
    document.getElementById('user-trangthai').value = u.TrangThai;
    document.getElementById('user-xacthuc').checked = Number(u.DaXacThucChinhChu) === 1;
    document.getElementById('user-edit-modal').classList.remove('hidden');
  }

  function closeUserModal() {
    document.getElementById('user-edit-modal').classList.add('hidden');
  }

  // Khởi tạo 3 biểu đồ Chart.js ở Tab Tổng quan
  document.addEventListener('DOMContentLoaded', function () {
    const ctxThang = document.getElementById('chartPhongTheoThang');
    if (ctxThang) {
      new Chart(ctxThang, {
        type: 'bar',
        data: {
          labels: <?= json_encode($labelsThang, JSON_UNESCAPED_UNICODE) ?>,
          datasets: [{
            label: 'Số phòng mới',
            data: <?= json_encode($dataPhongThang) ?>,
            backgroundColor: '#16a34a',
            borderRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
      });
    }

    const ctxTuan = document.getElementById('chartUserTheoTuan');
    if (ctxTuan) {
      new Chart(ctxTuan, {
        type: 'line',
        data: {
          labels: <?= json_encode($labelsTuan, JSON_UNESCAPED_UNICODE) ?>,
          datasets: [{
            label: 'Tài khoản mới',
            data: <?= json_encode($dataUserTuan) ?>,
            borderColor: '#0284c7',
            backgroundColor: 'rgba(2, 132, 199, 0.12)',
            fill: true,
            tension: 0.3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
      });
    }

    const ctxTyLe = document.getElementById('chartTyLePhong');
    if (ctxTyLe) {
      new Chart(ctxTyLe, {
        type: 'doughnut',
        data: {
          labels: ['Còn phòng', 'Đã thuê', 'Đang bảo trì'],
          datasets: [{
            data: [<?= $phongConTrong ?>, <?= $phongDaThue ?>, <?= $phongBaoTri ?>],
            backgroundColor: ['#16a34a', '#f59e0b', '#94a3b8']
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
        }
      });
    }
  });
</script>

<?php include_once __DIR__ . '/../dungchung/footer.php'; ?>