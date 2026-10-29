<?php
$tieuDeTrang = 'Admin Portal — CampusNest AGU';
$trangHienTai = 'admin';
require_once __DIR__ . '/../cauhinh/auth.php';
require_once __DIR__ . '/../cauhinh/database.php';

if (!daDangNhap()) {
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php?tb=' . urlencode('Vui long dang nhap de tiep tuc.'));
    exit();
}

$nd = nguoiDungHienTai();
$nd_hoten  = $nd['hoten'] ?? 'Admin';
$nd_anh    = $nd['anhdaidien'] ?? '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/default-avatar.png';

// Chi QuanTriVien duoc vao admin portal
if (($nd['vaitro'] ?? '') !== 'QuanTriVien') {
    include_once __DIR__ . '/../dungchung/header.php';
    echo '<div class="max-w-[1280px] mx-auto px-4 py-16 text-center">';
    echo '<div class="w-12 h-12 rounded-full bg-status-red-bg border border-status-red-border text-status-red-text flex items-center justify-center mx-auto mb-4">';
    echo '<span class="material-symbols-outlined text-3xl text-rose-600">lock</span></div>';
    echo '<h1 class="text-xl font-bold text-text-main">Ban khong co quyen truy cap trang quan tri</h1>';
    echo '<a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="inline-block mt-4 px-5 py-2.5 rounded-btn bg-accent text-white font-semibold text-xs">Ve trang chinh</a>';
    echo '</div>';
    include_once __DIR__ . '/../dungchung/footer.php';
    exit();
}

// XU LY HANH DONG POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hanhDong = $_POST['hanh_dong'] ?? '';

    if ($hanhDong === 'duyet_tin') {
        $id = (int)($_POST['ma_phong'] ?? 0);
        if ($id > 0) {
            $conn->query("UPDATE PhongTro SET TrangThaiDuyet = 'DaDuyet' WHERE MaPhong = $id");
            datThongBao('success', "Da duyet tin tro #$id thanh cong!");
        }
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=moderation');
        exit();
    }

    if ($hanhDong === 'tuchoi_tin') {
        $id = (int)($_POST['ma_phong'] ?? 0);
        if ($id > 0) {
            $conn->query("UPDATE PhongTro SET TrangThaiDuyet = 'TuChoi' WHERE MaPhong = $id");
            datThongBao('info', "Da tu choi tin tro #$id.");
        }
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=moderation');
        exit();
    }

    if ($hanhDong === 'xoa_tin') {
        $id = (int)($_POST['ma_phong'] ?? 0);
        if ($id > 0) {
            $conn->query("DELETE FROM PhongTro WHERE MaPhong = $id");
            datThongBao('info', "Da xoa tin tro #$id khoi he thong.");
        }
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=moderation');
        exit();
    }

    if ($hanhDong === 'khoa_user') {
        $id = (int)($_POST['ma_user'] ?? 0);
        if ($id > 0) {
            $conn->query("UPDATE NguoiDung SET TrangThai = IF(TrangThai='HoatDong', 'BiKhoa', 'HoatDong') WHERE MaNguoiDung = $id");
            datThongBao('success', "Da thay doi trang thai tai khoan #$id.");
        }
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=users');
        exit();
    }

    if ($hanhDong === 'xoa_user') {
        $id = (int)($_POST['ma_user'] ?? 0);
        if ($id > 0) {
            if ($id === (int)$nd['mannd']) {
                datThongBao('danger', "Khong the xoa tai khoan cua chinh ban dang dang nhap!");
            } else {
                $conn->query("DELETE FROM NguoiDung WHERE MaNguoiDung = $id");
                datThongBao('info', "Da xoa tai khoan #$id.");
            }
        }
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=users');
        exit();
    }

    if ($hanhDong === 'luu_user') {
        $id = (int)($_POST['ma_user'] ?? 0);
        $hoten = trim($_POST['hoten'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $sdt = trim($_POST['sdt'] ?? '');
        $vaitro = $_POST['vaitro'] ?? 'SinhVien';
        $trangthai = $_POST['trangthai'] ?? 'HoatDong';

        if ($hoten && $email) {
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE NguoiDung SET HoTen=?, Email=?, SoDienThoai=?, VaiTro=?, TrangThai=? WHERE MaNguoiDung=?");
                $stmt->bind_param('sssssi', $hoten, $email, $sdt, $vaitro, $trangthai, $id);
                $stmt->execute();
                datThongBao('success', "Da cap nhat tai khoan $hoten!");
            } else {
                $mk = password_hash('123456', PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO NguoiDung (HoTen, Email, SoDienThoai, MatKhau, VaiTro, TrangThai, NgayTao) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param('ssssss', $hoten, $email, $sdt, $mk, $vaitro, $trangthai);
                $stmt->execute();
                datThongBao('success', "Da them tai khoan moi $hoten (Mat khau mac dinh: 123456)!");
            }
        }
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=users');
        exit();
    }
}

// QUERY DU LIEU THUC TU DATABASE
$kpiChoDuyet = (int)($conn->query("SELECT COUNT(*) AS c FROM PhongTro WHERE TrangThaiDuyet = 'ChoDuyet'")->fetch_assoc()['c'] ?? 0);
$kpiTongUser = (int)($conn->query("SELECT COUNT(*) AS c FROM NguoiDung")->fetch_assoc()['c'] ?? 0);
$kpiBaoCao = (int)($conn->query("SELECT COUNT(*) AS c FROM BaoCaoViPham WHERE TrangThai = 'ChoXuLy'")->fetch_assoc()['c'] ?? 0);
$kpiDaDuyet = (int)($conn->query("SELECT COUNT(*) AS c FROM PhongTro WHERE TrangThaiDuyet = 'DaDuyet'")->fetch_assoc()['c'] ?? 0);

// Danh sach tai khoan thuc tu DB
$resUsers = $conn->query("SELECT MaNguoiDung, HoTen, Email, SoDienThoai, VaiTro, TrangThai, DATE_FORMAT(NgayTao, '%d/%m/%Y') AS NgayTaoF FROM NguoiDung ORDER BY MaNguoiDung DESC");
$dsUsers = $resUsers ? $resUsers->fetch_all(MYSQLI_ASSOC) : [];

// Danh sach phong tro thuc tu DB
$sqlPhong = "
    SELECT p.*, n.TenNhaTro, n.DiaChi,
           u.HoTen AS TenChuTro, u.SoDienThoai,
           (SELECT h.DuongDan FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.LaAnhChinh = 1 LIMIT 1) AS AnhChinh
    FROM PhongTro p
    JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
    JOIN NguoiDung u ON n.MaChuTro = u.MaNguoiDung
    ORDER BY p.NgayDang DESC
";
$resPhong = $conn->query($sqlPhong);
$dsPhong = $resPhong ? $resPhong->fetch_all(MYSQLI_ASSOC) : [];

$dsChoDuyet = array_values(array_filter($dsPhong, fn($p) => $p['TrangThaiDuyet'] === 'ChoDuyet'));
$phongChoDuyetDauTien = $dsChoDuyet[0] ?? null;

// Danh sach bao cao vi pham thuc tu DB
$resBc = $conn->query("
    SELECT b.*, u.HoTen AS TenNguoiBaoCao, p.TenPhong
    FROM BaoCaoViPham b
    LEFT JOIN NguoiDung u ON b.MaNguoiBaoCao = u.MaNguoiDung
    LEFT JOIN PhongTro p ON b.MaPhong = p.MaPhong
    ORDER BY b.NgayBaoCao DESC
");
$dsBaoCao = $resBc ? $resBc->fetch_all(MYSQLI_ASSOC) : [];

$activeTab = $_GET['tab'] ?? 'dashboard';

include_once __DIR__ . '/../dungchung/header.php';
?>

<!-- ===== ADMIN HEADER RIÊNG ===== -->
<header class="w-full bg-bg-sub border-b border-border-warm py-3.5 px-4 sm:px-6 lg:px-8">
  <div class="max-w-[1560px] mx-auto flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-card bg-accent text-white flex items-center justify-center font-bold text-sm">AG</div>
      <span class="text-base font-bold text-text-main">CampusNest AGU</span>
      <span class="px-2.5 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold">Admin Portal</span>
    </div>

    <div class="flex items-center gap-3">
      <a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="px-3 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">Xem trang sinh vien</a>
      <div class="flex items-center gap-2 pl-2 border-l border-border-warm">
        <div class="relative overflow-hidden w-7 h-7 rounded-full border border-border-warm shrink-0">
          <img loading="lazy" onerror="this.onerror=null; this.style.display='none';" alt="Admin" class="w-full h-full object-cover" src="<?= htmlspecialchars($nd_anh) ?>">
        </div>
        <span class="text-xs font-semibold text-text-main hidden sm:inline"><?= htmlspecialchars($nd_hoten) ?></span>
      </div>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangxuat.php" class="px-3 py-1.5 rounded-btn border border-status-red-border bg-status-red-bg text-status-red-text font-semibold text-xs transition-all duration-200">Dang xuat</a>
    </div>
  </div>
</header>

<div class="max-w-[1440px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1">
  <div class="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 items-start">
    <!-- SIDEBAR TRÁI -->
    <aside class="lg:sticky lg:top-4 bg-bg-sub rounded-card border border-border-warm p-3 space-y-1 lg:self-start">
      <div class="pb-3 mb-2 border-b border-border-warm hidden lg:block">
        <p class="text-xs font-bold uppercase tracking-wider text-text-sub">Bang dieu khien</p>
        <p class="text-xs text-text-main font-semibold mt-1">Kiem dinh AGU</p>
      </div>

      <nav class="flex lg:flex-col gap-1 overflow-x-auto lg:overflow-visible -mx-1 px-1 lg:mx-0 lg:px-0">
        <button id="admin-menu-dashboard" onclick="switchAdminTab('dashboard')" class="admin-menu-item <?= $activeTab==='dashboard'?'active':'' ?> flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200 active:scale-[0.98]">
          <span class="material-symbols-outlined">dashboard</span><span>Tong quan</span>
        </button>
        <button id="admin-menu-moderation" onclick="switchAdminTab('moderation')" class="admin-menu-item <?= $activeTab==='moderation'?'active':'' ?> flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200 active:scale-[0.98]">
          <span class="material-symbols-outlined">fact_check</span><span>Kiem duyet tin</span>
          <?php if ($kpiChoDuyet > 0): ?>
          <span class="ml-auto px-1.5 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text text-[10px] font-bold tnum hidden lg:inline"><?= $kpiChoDuyet ?></span>
          <?php endif; ?>
        </button>
        <button id="admin-menu-fraud" onclick="switchAdminTab('fraud')" class="admin-menu-item <?= $activeTab==='fraud'?'active':'' ?> flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200 active:scale-[0.98]">
          <span class="material-symbols-outlined">report</span><span>To giac gian lan</span>
          <?php if ($kpiBaoCao > 0): ?>
          <span class="ml-auto px-1.5 py-0.5 rounded-full bg-status-red-bg text-status-red-text border border-status-red-border text-[10px] font-bold tnum hidden lg:inline"><?= $kpiBaoCao ?></span>
          <?php endif; ?>
        </button>
        <button id="admin-menu-users" onclick="switchAdminTab('users')" class="admin-menu-item <?= $activeTab==='users'?'active':'' ?> flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200 active:scale-[0.98]">
          <span class="material-symbols-outlined">manage_accounts</span><span>Quan ly tai khoan</span>
          <span class="ml-auto px-1.5 py-0.5 rounded-full bg-bg-main text-text-sub text-[10px] font-bold tnum hidden lg:inline"><?= count($dsUsers) ?></span>
        </button>
      </nav>
    </aside>

    <!-- CONTENT PHẢI -->
    <main class="min-w-0 space-y-5">
      <!-- TAB 1: TỔNG QUAN -->
      <section id="admin-section-dashboard" class="space-y-5 <?= $activeTab!=='dashboard'?'hidden':'' ?>">
        <div class="bg-bg-sub rounded-card border border-border-warm p-4">
          <div class="grid grid-cols-2 lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x divide-border-warm tnum">
            <div class="pb-3 lg:pb-0 lg:pr-6">
              <span class="text-xs text-text-sub block">Tin tro cho duyet</span>
              <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-text-main"><?= str_pad($kpiChoDuyet, 2, '0', STR_PAD_LEFT) ?></span>
                <?php if ($kpiChoDuyet > 0): ?><span class="text-xs text-status-amber-text font-semibold">Can xu ly</span><?php else: ?><span class="text-xs text-accent font-semibold">Da xong</span><?php endif; ?>
              </div>
            </div>
            <div class="pb-3 lg:pb-0 lg:px-6 pl-4">
              <span class="text-xs text-text-sub block">Tong tai khoan</span>
              <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-text-main"><?= str_pad($kpiTongUser, 2, '0', STR_PAD_LEFT) ?></span>
                <span class="text-xs text-text-sub">Chu tro + Sinh vien</span>
              </div>
            </div>
            <div class="pt-3 lg:pt-0 lg:px-6">
              <span class="text-xs text-text-sub block">Bao cao gian lan</span>
              <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold <?= $kpiBaoCao > 0 ? 'text-status-red-text' : 'text-text-main' ?>"><?= str_pad($kpiBaoCao, 2, '0', STR_PAD_LEFT) ?></span>
                <span class="text-xs text-text-sub"><?= $kpiBaoCao > 0 ? 'Chua xu ly' : 'He thong on dinh' ?></span>
              </div>
            </div>
            <div class="pt-3 lg:pt-0 lg:pl-6 pl-4">
              <span class="text-xs text-text-sub block">Phong tro da duyet</span>
              <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-accent"><?= str_pad($kpiDaDuyet, 2, '0', STR_PAD_LEFT) ?></span>
                <span class="text-xs text-text-sub">Dang hien thi</span>
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <!-- To giac gan nhat -->
          <div class="bg-bg-main rounded-card border border-border-warm p-5 space-y-3">
            <div class="flex items-center justify-between pb-2.5 border-b border-border-warm">
              <h3 class="text-sm font-bold text-text-main flex items-center gap-1.5">
                <span class="material-symbols-outlined text-status-red-text">report</span>To giac gian lan gan nhat
              </h3>
              <span class="px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text text-xs font-semibold tnum"><?= $kpiBaoCao ?></span>
            </div>
            <?php if (empty($dsBaoCao)): ?>
            <div class="py-8 text-center text-text-sub space-y-1">
              <span class="material-symbols-outlined text-3xl block mx-auto text-accent">verified_user</span>
              <p class="text-xs font-medium">Chua co bao cao gian lan nao tu sinh vien.</p>
            </div>
            <?php else: ?>
            <div class="space-y-2">
              <?php foreach (array_slice($dsBaoCao, 0, 3) as $bc): ?>
              <div class="p-3 rounded-card bg-status-red-bg/50 border border-status-red-border text-xs">
                <strong class="text-text-main block">#BC-<?= $bc['MaBaoCao'] ?> . <?= htmlspecialchars($bc['LyDo']) ?></strong>
                <span class="text-text-sub block mt-1"><?= htmlspecialchars($bc['TenNguoiBaoCao'] ?? 'Sinh vien') ?> . <?= htmlspecialchars($bc['ChiTiet']) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <button type="button" onclick="switchAdminTab('fraud')" class="w-full py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">Xem tat ca to giac -> </button>
          </div>

          <!-- Tai khoan moi nhat -->
          <div class="bg-bg-main rounded-card border border-border-warm p-5 space-y-3">
            <div class="flex items-center justify-between pb-2.5 border-b border-border-warm">
              <h3 class="text-sm font-bold text-text-main flex items-center gap-1.5">
                <span class="material-symbols-outlined text-accent">manage_accounts</span>Tai khoan moi nhat
              </h3>
              <span class="text-xs text-text-sub tnum"><?= count($dsUsers) ?> tai khoan</span>
            </div>
            <div class="space-y-2">
              <?php foreach (array_slice($dsUsers, 0, 3) as $u):
                $vtLabel = ($u['VaiTro']==='QuanTriVien')?'Quan tri vien':(($u['VaiTro']==='ChuTro')?'Chu tro':'Sinh vien');
                $ttBadge = $u['TrangThai']==='HoatDong'
                  ? '<span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent text-[10px] font-semibold">Hoat dong</span>'
                  : '<span class="px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text text-[10px] font-semibold">Khoa</span>';
              ?>
              <div class="p-3 rounded-card bg-bg-sub border border-border-warm text-xs flex items-center justify-between">
                <div>
                  <strong class="text-text-main block"><?= htmlspecialchars($u['HoTen']) ?></strong>
                  <span class="text-text-sub"><?= htmlspecialchars($u['Email']) ?> . <?= $vtLabel ?></span>
                </div>
                <?= $ttBadge ?>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" onclick="switchAdminTab('users')" class="w-full py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">Quan ly tat ca tai khoan -> </button>
          </div>
        </div>
      </section>

      <!-- TAB 2: KIỂM DUYỆT TIN TRỌ -->
      <section id="admin-section-moderation" class="space-y-5 <?= $activeTab!=='moderation'?'hidden':'' ?>">
        <?php if ($phongChoDuyetDauTien):
          $pGia = number_format($phongChoDuyetDauTien['GiaThue'], 0, ',', '.');
          $pDien = $phongChoDuyetDauTien['DienTich'] ? $phongChoDuyetDauTien['DienTich'] . 'm2' : '--';
          $pAnh = $phongChoDuyetDauTien['AnhChinh'] ?: '';
        ?>
        <!-- CARD THẨM ĐỊNH CHI TIẾT PHÒNG ĐẦU TIÊN ĐANG CHỜ DUYỆT -->
        <div class="bg-bg-main rounded-card border border-border-warm overflow-hidden">
          <div class="bg-bg-sub px-5 py-3 border-b border-border-warm flex flex-wrap items-center justify-between gap-2 text-xs tnum">
            <div class="flex items-center gap-3">
              <span class="font-bold text-text-main">Ma tin: #P-<?= $phongChoDuyetDauTien['MaPhong'] ?></span>
              <span class="text-text-sub">.</span>
              <span class="text-text-sub">Ngay dang: <?= date('d/m/Y', strtotime($phongChoDuyetDauTien['NgayDang'])) ?></span>
            </div>
            <span class="px-2.5 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text font-semibold inline-flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-status-amber-text"></span><span>Dang cho duyet</span>
            </span>
          </div>

          <div class="p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
              <div>
                <h2 class="text-lg font-bold text-text-main"><?= htmlspecialchars($phongChoDuyetDauTien['TenPhong']) ?></h2>
                <p class="text-xs text-text-sub mt-1 tnum">
                  Nha tro: <strong><?= htmlspecialchars($phongChoDuyetDauTien['TenNhaTro']) ?></strong> .
                  Dia chi: <?= htmlspecialchars($phongChoDuyetDauTien['DiaChi']) ?> .
                  Chu tro: <strong><?= htmlspecialchars($phongChoDuyetDauTien['TenChuTro']) ?></strong> (<?= htmlspecialchars($phongChoDuyetDauTien['SoDienThoai']) ?>)
                </p>
              </div>
              <div class="text-left sm:text-right shrink-0 tnum">
                <span class="text-xl font-bold text-text-main block"><?= $pGia ?> d/thang</span>
                <span class="text-xs text-text-sub">DT: <?= $pDien ?></span>
              </div>
            </div>

            <?php if ($phongChoDuyetDauTien['MoTa']): ?>
            <p class="text-xs text-text-main bg-bg-sub p-3 rounded-card leading-relaxed"><?= nl2br(htmlspecialchars($phongChoDuyetDauTien['MoTa'])) ?></p>
            <?php endif; ?>

            <div class="pt-3 border-t border-border-warm flex flex-wrap items-center justify-between gap-2">
              <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $phongChoDuyetDauTien['MaPhong'] ?>" target="_blank"
                 class="px-3.5 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">visibility</span>Xem trang chi tiet
              </a>
              <div class="flex items-center gap-2">
                <form method="POST">
                  <input type="hidden" name="hanh_dong" value="tuchoi_tin">
                  <input type="hidden" name="ma_phong" value="<?= $phongChoDuyetDauTien['MaPhong'] ?>">
                  <button type="submit" class="px-3.5 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">Tu choi</button>
                </form>
                <form method="POST">
                  <input type="hidden" name="hanh_dong" value="duyet_tin">
                  <input type="hidden" name="ma_phong" value="<?= $phongChoDuyetDauTien['MaPhong'] ?>">
                  <button type="submit" class="px-5 py-2 rounded-btn bg-accent text-white font-semibold text-xs">Duyet tin & Cap phep</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <?php else: ?>
        <!-- EMPTY STATE KHI KHÔNG CÓ TIN NÀO CHỜ DUYỆT -->
        <div class="py-12 text-center space-y-3 bg-bg-main rounded-card border border-border-warm border-dashed">
          <span class="material-symbols-outlined text-4xl text-accent block mx-auto">task_alt</span>
          <p class="text-base font-bold text-text-main">Khong co tin dang nao cho tham dinh</p>
          <p class="text-xs text-text-sub">Tat ca tin dang phong tro deu da duoc xu ly hoac chu tro chua dang tin moi.</p>
        </div>
        <?php endif; ?>

        <!-- BẢNG QUẢN LÝ TOÀN BỘ TIN TRỌ THỰC -->
        <div class="bg-bg-main rounded-card border border-border-warm p-5 space-y-4">
          <div class="flex items-center justify-between gap-2">
            <h3 class="text-base font-bold text-text-main">Quan ly toan bo tin tro</h3>
            <span class="px-2.5 py-0.5 rounded-full bg-bg-sub border border-border-warm text-text-main text-xs font-semibold tnum"><?= count($dsPhong) ?> tin</span>
          </div>

          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <input id="admin-search" type="text" oninput="filterAdminListings()" placeholder="Tim theo ten phong, dia chi, chu tro..." class="w-full sm:w-2/3 px-3 py-2 border border-border-warm bg-bg-main rounded-card text-sm text-text-main focus:border-accent outline-none">
            <select id="admin-status-filter" onchange="filterAdminListings()" class="w-full sm:w-1/3 px-3 py-2 border border-border-warm bg-bg-main rounded-card text-sm text-text-main focus:border-accent outline-none">
              <option value="all">Tat ca trang thai</option>
              <option value="ChoDuyet">Cho duyet</option>
              <option value="DaDuyet">Da duyet</option>
              <option value="TuChoi">Tu choi</option>
            </select>
          </div>

          <?php if (empty($dsPhong)): ?>
          <div class="py-12 text-center text-text-sub space-y-2">
            <span class="material-symbols-outlined text-4xl block mx-auto">apartment</span>
            <p class="text-sm font-semibold text-text-main">Chua co tin tro nao tren he thong</p>
            <p class="text-xs">Khi chu tro dang tin, danh sach se hien thi tai day de quan tri vien kiem duyet.</p>
          </div>
          <?php else: ?>
          <div class="overflow-x-auto border border-border-warm rounded-card">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-bg-sub text-xs font-bold text-text-sub border-b border-border-warm">
                  <th class="py-2.5 px-3">Ma</th>
                  <th class="py-2.5 px-3">Tieu de phong</th>
                  <th class="py-2.5 px-3 hidden md:table-cell">Dia chi</th>
                  <th class="py-2.5 px-3 tnum">Gia thue</th>
                  <th class="py-2.5 px-3 hidden md:table-cell">Chu tro</th>
                  <th class="py-2.5 px-3">Duyet</th>
                  <th class="py-2.5 px-3 text-right">Hanh dong</th>
                </tr>
              </thead>
              <tbody id="listings-tbody">
              <?php foreach ($dsPhong as $p):
                $pF = number_format($p['GiaThue'], 0, ',', '.');
                $dBadge = $p['TrangThaiDuyet'] === 'DaDuyet'
                  ? '<span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold">Da duyet</span>'
                  : ($p['TrangThaiDuyet'] === 'TuChoi'
                    ? '<span class="px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text text-xs font-semibold">Tu choi</span>'
                    : '<span class="px-2 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text text-xs font-semibold">Cho duyet</span>');
              ?>
              <tr class="listing-row border-t border-border-warm hover:bg-bg-sub/40 text-xs"
                  data-search="<?= strtolower(htmlspecialchars($p['TenPhong'] . ' ' . $p['DiaChi'] . ' ' . $p['TenChuTro'])) ?>"
                  data-status="<?= $p['TrangThaiDuyet'] ?>">
                <td class="py-2.5 px-3 font-mono font-bold text-text-main">#<?= $p['MaPhong'] ?></td>
                <td class="py-2.5 px-3 font-semibold text-text-main">
                  <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $p['MaPhong'] ?>" class="hover:text-accent"><?= htmlspecialchars($p['TenPhong']) ?></a>
                </td>
                <td class="py-2.5 px-3 text-text-sub hidden md:table-cell"><?= htmlspecialchars($p['DiaChi']) ?></td>
                <td class="py-2.5 px-3 tnum text-text-main font-semibold"><?= $pF ?> d</td>
                <td class="py-2.5 px-3 text-text-sub hidden md:table-cell"><?= htmlspecialchars($p['TenChuTro']) ?></td>
                <td class="py-2.5 px-3"><?= $dBadge ?></td>
                <td class="py-2.5 px-3 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <?php if ($p['TrangThaiDuyet'] !== 'DaDuyet'): ?>
                    <form method="POST" class="inline">
                      <input type="hidden" name="hanh_dong" value="duyet_tin">
                      <input type="hidden" name="ma_phong" value="<?= $p['MaPhong'] ?>">
                      <button type="submit" title="Duyet tin" class="w-7 h-7 rounded-btn bg-accent text-white flex items-center justify-center">
                        <span class="material-symbols-outlined text-[15px]">check</span>
                      </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($p['TrangThaiDuyet'] !== 'TuChoi'): ?>
                    <form method="POST" class="inline">
                      <input type="hidden" name="hanh_dong" value="tuchoi_tin">
                      <input type="hidden" name="ma_phong" value="<?= $p['MaPhong'] ?>">
                      <button type="submit" title="Tu choi" class="w-7 h-7 rounded-btn border border-border-warm bg-bg-main text-text-sub flex items-center justify-center hover:text-text-main">
                        <span class="material-symbols-outlined text-[15px]">close</span>
                      </button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Ban co chac muon xoa tin tro nay?')">
                      <input type="hidden" name="hanh_dong" value="xoa_tin">
                      <input type="hidden" name="ma_phong" value="<?= $p['MaPhong'] ?>">
                      <button type="submit" title="Xoa tin" class="w-7 h-7 rounded-btn border border-status-red-border bg-status-red-bg text-status-red-text flex items-center justify-center">
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
          <div class="pb-3 mb-4 border-b border-border-warm">
            <h3 class="text-base font-bold text-text-main">To giac gian lan tien coc</h3>
            <p class="text-xs text-text-sub mt-0.5"><?= count($dsBaoCao) ?> ticket to giac tu sinh vien</p>
          </div>

          <?php if (empty($dsBaoCao)): ?>
          <div class="py-16 text-center space-y-3 bg-bg-main rounded-card border border-border-warm border-dashed">
            <span class="material-symbols-outlined text-4xl text-text-sub block mx-auto">verified_user</span>
            <p class="text-base font-bold text-text-main">Chua co bao cao vi pham nao</p>
            <p class="text-xs text-text-sub">Khi sinh vien gui to giac phong tro hoac gian lan tien coc, thong tin se hien thi tai day de xu ly.</p>
          </div>
          <?php else: ?>
          <div class="space-y-3">
            <?php foreach ($dsBaoCao as $bc): ?>
            <div class="p-4 rounded-card bg-status-red-bg/50 border border-status-red-border space-y-2">
              <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text border border-status-red-border text-[10px] font-bold">#BC-<?= $bc['MaBaoCao'] ?></span>
                  <strong class="text-xs text-text-main"><?= htmlspecialchars($bc['LyDo']) ?></strong>
                </div>
                <span class="text-xs text-text-sub tnum"><?= $bc['NgayBaoCao'] ?></span>
              </div>
              <p class="text-xs text-text-main leading-relaxed"><strong>Nguoi bao cao: <?= htmlspecialchars($bc['TenNguoiBaoCao'] ?? 'Sinh vien') ?>:</strong> <?= htmlspecialchars($bc['ChiTiet']) ?></p>
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
                <input id="user-search" type="search" placeholder="Ten, email, vai tro..." class="bg-transparent outline-none text-xs text-text-main w-40" oninput="filterAdminUsers()">
              </div>
              <select id="user-role-filter" onchange="filterAdminUsers()" class="text-xs px-2 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main outline-none">
                <option value="all">Tat ca vai tro</option>
                <option value="ChuTro">Chu tro</option>
                <option value="SinhVien">Sinh vien</option>
                <option value="QuanTriVien">Quan tri vien</option>
              </select>
              <select id="user-status-filter" onchange="filterAdminUsers()" class="text-xs px-2 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main outline-none">
                <option value="all">Tat ca trang thai</option>
                <option value="HoatDong">Hoat dong</option>
                <option value="BiKhoa">Bi khoa</option>
              </select>
            </div>
            <div class="flex items-center gap-2">
              <span id="users-count" class="text-xs text-text-sub tnum"><?= count($dsUsers) ?> tai khoan</span>
              <button onclick="openUserModal()" type="button" class="flex items-center gap-1.5 px-3 py-1.5 rounded-btn bg-accent text-white font-semibold text-xs transition-all duration-200 active:scale-[0.98]">
                <span class="material-symbols-outlined text-[16px]">person_add</span>Them tai khoan
              </button>
            </div>
          </div>

          <!-- Bảng Tài khoản -->
          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead class="bg-bg-sub/60">
                <tr class="text-[11px] font-bold uppercase tracking-wider text-text-sub">
                  <th class="py-2.5 px-3">Ho ten</th>
                  <th class="py-2.5 px-3">Email</th>
                  <th class="py-2.5 px-3">Vai tro</th>
                  <th class="py-2.5 px-3 hidden md:table-cell">Ngay tao</th>
                  <th class="py-2.5 px-3">Trang thai</th>
                  <th class="py-2.5 px-3 text-right">Hanh dong</th>
                </tr>
              </thead>
              <tbody id="users-tbody">
              <?php foreach ($dsUsers as $u):
                $vt = $u['VaiTro'];
                $vtLabel = ($vt === 'QuanTriVien') ? 'Quan tri vien' : (($vt === 'ChuTro') ? 'Chu tro' : 'Sinh vien');
                $vtBadge = ($vt === 'QuanTriVien')
                  ? 'bg-status-green-bg text-accent'
                  : (($vt === 'ChuTro') ? 'bg-status-amber-bg text-status-amber-text' : 'bg-bg-sub text-text-sub border border-border-warm');
                $ttBadge = ($u['TrangThai'] === 'HoatDong')
                  ? '<span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold">Hoat dong</span>'
                  : '<span class="px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text border border-status-red-border text-xs font-semibold">Bi khoa</span>';
              ?>
              <tr class="user-row border-t border-border-warm hover:bg-bg-sub/40 text-xs"
                  data-search="<?= strtolower(htmlspecialchars($u['HoTen'] . ' ' . $u['Email'] . ' ' . $vtLabel)) ?>"
                  data-role="<?= $u['VaiTro'] ?>"
                  data-status="<?= $u['TrangThai'] ?>">
                <td class="py-2.5 px-3 font-semibold text-text-main"><?= htmlspecialchars($u['HoTen']) ?></td>
                <td class="py-2.5 px-3 text-text-sub"><?= htmlspecialchars($u['Email']) ?></td>
                <td class="py-2.5 px-3"><span class="px-2 py-0.5 rounded-full font-semibold <?= $vtBadge ?>"><?= $vtLabel ?></span></td>
                <td class="py-2.5 px-3 text-text-sub tnum hidden md:table-cell"><?= $u['NgayTaoF'] ?></td>
                <td class="py-2.5 px-3"><?= $ttBadge ?></td>
                <td class="py-2.5 px-3">
                  <div class="flex items-center justify-end gap-1.5">
                    <form method="POST" class="inline">
                      <input type="hidden" name="hanh_dong" value="khoa_user">
                      <input type="hidden" name="ma_user" value="<?= $u['MaNguoiDung'] ?>">
                      <button type="submit" title="<?= $u['TrangThai']==='HoatDong'?'Khoa tai khoan':'Mo khoa tai khoan' ?>"
                        class="w-7 h-7 rounded-btn border <?= $u['TrangThai']==='HoatDong'?'border-status-amber-text bg-status-amber-bg text-status-amber-text':'border-status-green-bg bg-status-green-bg text-accent' ?> flex items-center justify-center">
                        <span class="material-symbols-outlined text-[16px]"><?= $u['TrangThai']==='HoatDong'?'lock':'lock_open' ?></span>
                      </button>
                    </form>
                    <button type="button" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)" title="Sua tai khoan"
                      class="w-7 h-7 rounded-btn border border-border-warm bg-bg-main text-text-main flex items-center justify-center hover:border-accent">
                      <span class="material-symbols-outlined text-[16px]">edit</span>
                    </button>
                    <?php if ($u['MaNguoiDung'] != $nd['mannd']): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Ban co chac muon xoa tai khoan nay?')">
                      <input type="hidden" name="hanh_dong" value="xoa_user">
                      <input type="hidden" name="ma_user" value="<?= $u['MaNguoiDung'] ?>">
                      <button type="submit" title="Xoa tai khoan"
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

        <!-- Modal them/sua tai khoan -->
        <div id="user-edit-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
          <div class="bg-bg-main rounded-card border border-border-warm shadow-lg w-full max-w-md">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-border-warm">
              <h2 id="user-modal-title" class="text-sm font-bold text-text-main">Them tai khoan</h2>
              <button onclick="closeUserModal()" type="button" class="w-7 h-7 rounded-btn border border-border-warm flex items-center justify-center text-text-sub hover:text-text-main">
                <span class="material-symbols-outlined text-[18px]">close</span>
              </button>
            </div>
            <form id="user-edit-form" method="POST" class="p-5 space-y-3">
              <input type="hidden" name="hanh_dong" value="luu_user">
              <input type="hidden" id="user-id" name="ma_user" value="0">
              <div class="grid grid-cols-2 gap-3">
                <div class="col-span-2">
                  <label class="text-xs font-semibold text-text-main block mb-1">Ho va ten <span class="text-rose-500">*</span></label>
                  <input id="user-hoten" name="hoten" type="text" required class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
                </div>
                <div class="col-span-2">
                  <label class="text-xs font-semibold text-text-main block mb-1">Email <span class="text-rose-500">*</span></label>
                  <input id="user-email" name="email" type="email" required class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
                </div>
                <div>
                  <label class="text-xs font-semibold text-text-main block mb-1">Vai tro <span class="text-rose-500">*</span></label>
                  <select id="user-vaitro" name="vaitro" required class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
                    <option value="SinhVien">Sinh vien</option>
                    <option value="ChuTro">Chu tro</option>
                    <option value="QuanTriVien">Quan tri vien</option>
                  </select>
                </div>
                <div>
                  <label class="text-xs font-semibold text-text-main block mb-1">Trang thai</label>
                  <select id="user-trangthai" name="trangthai" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
                    <option value="HoatDong">Hoat dong</option>
                    <option value="BiKhoa">Bi khoa</option>
                  </select>
                </div>
                <div class="col-span-2">
                  <label class="text-xs font-semibold text-text-main block mb-1">So dien thoai</label>
                  <input id="user-sdt" name="sdt" type="text" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
                </div>
              </div>
              <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeUserModal()" class="px-4 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs">Huy</button>
                <button type="submit" class="px-4 py-2 rounded-btn bg-accent text-white font-semibold text-xs">Luu tai khoan</button>
              </div>
            </form>
          </div>
        </div>
      </section>
    </main>
  </div>
</div>

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
    // Cap nhat query parameter tren URL
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);
  }

  function filterAdminListings() {
    const q = (document.getElementById('admin-search')?.value || '').toLowerCase();
    const st = document.getElementById('admin-status-filter')?.value || 'all';
    const rows = document.querySelectorAll('.listing-row');
    rows.forEach(r => {
      const text = r.getAttribute('data-search') || '';
      const status = r.getAttribute('data-status') || '';
      const matchQ = !q || text.includes(q);
      const matchSt = st === 'all' || status === st;
      r.style.display = (matchQ && matchSt) ? '' : 'none';
    });
  }

  function filterAdminUsers() {
    const q = (document.getElementById('user-search')?.value || '').toLowerCase();
    const role = document.getElementById('user-role-filter')?.value || 'all';
    const st = document.getElementById('user-status-filter')?.value || 'all';
    const rows = document.querySelectorAll('.user-row');
    rows.forEach(r => {
      const text = r.getAttribute('data-search') || '';
      const rRole = r.getAttribute('data-role') || '';
      const rSt = r.getAttribute('data-status') || '';
      const matchQ = !q || text.includes(q);
      const matchRole = role === 'all' || rRole === role;
      const matchSt = st === 'all' || rSt === st;
      r.style.display = (matchQ && matchRole && matchSt) ? '' : 'none';
    });
  }

  function openUserModal() {
    document.getElementById('user-modal-title').textContent = 'Them tai khoan';
    document.getElementById('user-id').value = '0';
    document.getElementById('user-hoten').value = '';
    document.getElementById('user-email').value = '';
    document.getElementById('user-sdt').value = '';
    document.getElementById('user-vaitro').value = 'SinhVien';
    document.getElementById('user-trangthai').value = 'HoatDong';
    document.getElementById('user-edit-modal').classList.remove('hidden');
  }

  function editUser(u) {
    document.getElementById('user-modal-title').textContent = 'Sua tai khoan -- ' + u.HoTen;
    document.getElementById('user-id').value = u.MaNguoiDung;
    document.getElementById('user-hoten').value = u.HoTen;
    document.getElementById('user-email').value = u.Email;
    document.getElementById('user-sdt').value = u.SoDienThoai || '';
    document.getElementById('user-vaitro').value = u.VaiTro;
    document.getElementById('user-trangthai').value = u.TrangThai;
    document.getElementById('user-edit-modal').classList.remove('hidden');
  }

  function closeUserModal() {
    document.getElementById('user-edit-modal').classList.add('hidden');
  }
</script>

<?php include_once __DIR__ . '/../dungchung/footer.php'; ?>\n