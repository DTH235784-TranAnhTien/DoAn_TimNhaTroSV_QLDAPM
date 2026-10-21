<?php
require_once __DIR__ . '/cauhinh/database.php';
require_once __DIR__ . '/cauhinh/auth.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("
        SELECT p.*, n.TenNhaTro, n.DiaChi, n.MoTa AS MoTaNhaTro,
               u.HoTen AS TenChuTro, u.SoDienThoai, u.Email AS EmailChuTro, u.AnhDaiDien AS AnhChuTro,
               u.MaNguoiDung AS MaChuTro
        FROM PhongTro p
        JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
        JOIN NguoiDung u ON n.MaChuTro = u.MaNguoiDung
        WHERE p.MaPhong = ? LIMIT 1
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $phong = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Anh phong
    $stmtAnh = $conn->prepare("SELECT DuongDan, LaAnhChinh FROM HinhAnhPhongTro WHERE MaPhong = ? ORDER BY LaAnhChinh DESC");
    $stmtAnh->bind_param('i', $id);
    $stmtAnh->execute();
    $dsAnh = $stmtAnh->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtAnh->close();
} else {
    $phong = null;
    $dsAnh = [];
}

$tieuDeTrang = $phong ? htmlspecialchars($phong['TenPhong']) . ' -- CampusNest AGU' : 'Chi tiet phong -- CampusNest AGU';
$trangHienTai = 'explore';
include_once __DIR__ . '/dungchung/header.php';
?>

<div class="max-w-[960px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Breadcrumb -->
  <nav class="flex items-center gap-1.5 text-xs text-text-sub mb-5">
    <a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="hover:text-text-main">Trang chinh</a>
    <span class="material-symbols-outlined text-[14px]">chevron_right</span>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" class="hover:text-text-main">Danh sach phong</a>
    <span class="material-symbols-outlined text-[14px]">chevron_right</span>
    <span class="text-text-main font-semibold"><?= $phong ? htmlspecialchars($phong['TenPhong']) : 'Phong khong ton tai' ?></span>
  </nav>

  <?php if (!$phong): ?>
  <!-- PHONG KHONG TON TAI -->
  <div class="py-20 text-center space-y-4 bg-bg-main rounded-card border border-border-warm border-dashed">
    <span class="material-symbols-outlined text-5xl text-text-sub block mx-auto">search_off</span>
    <p class="text-base font-bold text-text-main">Khong tim thay phong nay</p>
    <p class="text-xs text-text-sub">Phong co the da bi xoa hoac ma phong khong chinh xac.</p>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-sm">
      Xem danh sach phong
    </a>
  </div>

  <?php else:
    $gia    = number_format($phong['GiaThue'], 0, ',', '.');
    $dien   = $phong['DienTich'] ? $phong['DienTich'] . 'm2' : '--';
    $tdien  = $phong['TienDien'] ? number_format($phong['TienDien'], 0, ',', '.') . ' d/kWh' : '--';
    $tnuoc  = $phong['TienNuoc'] ? number_format($phong['TienNuoc'], 0, ',', '.') . ' d/khoi' : '--';
    $twifi  = $phong['TienWifi']  ? number_format($phong['TienWifi'], 0, ',', '.') . ' d/thang' : 'Khong co';
    $ngay   = $phong['NgayDang'] ? date('d/m/Y', strtotime($phong['NgayDang'])) : '--';
    $anhChinh = $dsAnh[0]['DuongDan'] ?? '';
  ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Cot trai: Hinh anh + mo ta -->
    <div class="lg:col-span-2 space-y-5">

      <!-- Anh chinh -->
      <div class="rounded-card overflow-hidden border border-border-warm bg-bg-sub aspect-[16/10] relative">
        <?php if ($anhChinh): ?>
        <img id="main-room-img" src="<?= htmlspecialchars($anhChinh) ?>" alt="<?= htmlspecialchars($phong['TenPhong']) ?>"
             onerror="this.src='/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/default-avatar.png'"
             class="w-full h-full object-cover transition-opacity duration-200">
        <?php else: ?>
        <div class="w-full h-full flex flex-col items-center justify-center text-text-sub space-y-2">
          <span class="material-symbols-outlined text-5xl">image_not_supported</span>
          <p class="text-xs">Chưa có hình ảnh phòng trọ</p>
        </div>
        <?php endif; ?>
      </div>

      <!-- Thumbnails gallery (neu co nhieu anh duoc chu tro tai tu may) -->
      <?php if (count($dsAnh) > 1): ?>
      <div class="flex items-center gap-2.5 overflow-x-auto pb-1">
        <?php foreach ($dsAnh as $idx => $img): ?>
        <button type="button" onclick="document.getElementById('main-room-img').src='<?= htmlspecialchars($img['DuongDan']) ?>'"
                class="w-20 h-14 rounded-btn overflow-hidden border-2 border-border-warm hover:border-accent focus:border-accent shrink-0 transition-all shadow-2xs">
          <img src="<?= htmlspecialchars($img['DuongDan']) ?>" class="w-full h-full object-cover">
        </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Mo ta -->
      <div class="bg-bg-main rounded-card border border-border-warm p-5 space-y-3">
        <h2 class="text-base font-bold text-text-main flex items-center gap-2">
          <span class="material-symbols-outlined text-accent">description</span>Mo ta phong
        </h2>
        <?php if ($phong['MoTa']): ?>
        <p class="text-sm text-text-main leading-relaxed"><?= nl2br(htmlspecialchars($phong['MoTa'])) ?></p>
        <?php else: ?>
        <p class="text-sm text-text-sub italic">Chu tro chua them mo ta cho phong nay.</p>
        <?php endif; ?>
      </div>

      <!-- Chi phi -->
      <div class="bg-bg-main rounded-card border border-border-warm p-5">
        <h2 class="text-base font-bold text-text-main flex items-center gap-2 pb-3 mb-3 border-b border-border-warm">
          <span class="material-symbols-outlined text-accent">payments</span>Chi phi hang thang
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 tnum text-center">
          <div class="space-y-0.5">
            <p class="text-xs text-text-sub">Gia phong</p>
            <p class="text-sm font-bold text-text-main"><?= $gia ?> d</p>
          </div>
          <div class="space-y-0.5">
            <p class="text-xs text-text-sub">Tien dien</p>
            <p class="text-sm font-bold text-text-main"><?= $tdien ?></p>
          </div>
          <div class="space-y-0.5">
            <p class="text-xs text-text-sub">Tien nuoc</p>
            <p class="text-sm font-bold text-text-main"><?= $tnuoc ?></p>
          </div>
          <div class="space-y-0.5">
            <p class="text-xs text-text-sub">Wifi</p>
            <p class="text-sm font-bold text-text-main"><?= $twifi ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Cot phai: thong tin chu tro + dat lich -->
    <div class="space-y-5">

      <!-- Gia + trang thai -->
      <div class="bg-bg-main rounded-card border border-border-warm p-5">
        <div class="flex items-baseline justify-between mb-1">
          <span class="text-2xl font-bold text-text-main tnum"><?= $gia ?> d/thang</span>
        </div>
        <p class="text-xs text-text-sub"><?= $dien ?> . Dang: <?= $ngay ?></p>
        <div class="mt-3 pt-3 border-t border-border-warm space-y-1 text-xs text-text-sub">
          <p class="font-semibold text-text-main"><?= htmlspecialchars($phong['TenNhaTro']) ?></p>
          <p><?= htmlspecialchars($phong['DiaChi']) ?></p>
        </div>
        <?php
          $trangThai = $phong['TrangThai'];
          $badge = $trangThai === 'ConPhong'
            ? '<span class="px-2.5 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold">Con phong</span>'
            : ($trangThai === 'DaThue'
              ? '<span class="px-2.5 py-0.5 rounded-full bg-status-red-bg text-status-red-text text-xs font-semibold">Da co nguoi thue</span>'
              : '<span class="px-2.5 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text text-xs font-semibold">Dang bao tri</span>');
        ?>
        <div class="mt-3"><?= $badge ?></div>
      </div>

      <!-- Chu tro -->
      <div class="bg-bg-main rounded-card border border-border-warm p-5 space-y-3">
        <h3 class="text-sm font-bold text-text-main">Thong tin chu tro</h3>
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-full overflow-hidden bg-bg-sub border border-border-warm shrink-0">
            <?php if ($phong['AnhChuTro']): ?>
            <img src="<?= htmlspecialchars($phong['AnhChuTro']) ?>" alt="Chu tro" class="w-full h-full object-cover">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center">
              <span class="material-symbols-outlined text-text-sub text-2xl">person</span>
            </div>
            <?php endif; ?>
          </div>
          <div>
            <p class="text-sm font-bold text-text-main"><?= htmlspecialchars($phong['TenChuTro']) ?></p>
            <p class="text-xs text-text-sub"><?= htmlspecialchars($phong['EmailChuTro']) ?></p>
          </div>
        </div>
        <?php if ($phong['SoDienThoai']): ?>
        <a href="tel:<?= htmlspecialchars($phong['SoDienThoai']) ?>"
           class="w-full py-2.5 rounded-btn bg-accent text-white font-semibold text-sm flex items-center justify-center gap-2 transition-all duration-200">
          <span class="material-symbols-outlined text-base">call</span>
          Goi <?= htmlspecialchars($phong['SoDienThoai']) ?>
        </a>
        <?php endif; ?>
      </div>

      <!-- Dat lich hen -->
      <?php if (daDangNhap() && coVaiTro('SinhVien') && $trangThai === 'ConPhong'): ?>
      <div class="bg-bg-main rounded-card border border-border-warm p-5 space-y-3">
        <h3 class="text-sm font-bold text-text-main flex items-center gap-2">
          <span class="material-symbols-outlined text-accent">calendar_add_on</span>Dat lich xem phong
        </h3>
        <form method="POST" action="/DoAn_TimNhaTroSV_QLDAPM/sinhvien/datlich.php" class="space-y-2">
          <input type="hidden" name="ma_phong" value="<?= $id ?>">
          <input type="date" name="ngay" min="<?= date('Y-m-d') ?>"
            class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent" required>
          <input type="time" name="gio"
            class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent" required>
          <textarea name="ghi_chu" rows="2" placeholder="Ghi chu them (khong bat buoc)..."
            class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent resize-none"></textarea>
          <button type="submit"
            class="w-full py-2.5 rounded-btn border border-accent text-accent font-semibold text-sm hover:bg-accent hover:text-white transition-all duration-200">
            Gui yeu cau hen xem
          </button>
        </form>
      </div>
      <?php elseif (!daDangNhap()): ?>
      <div class="bg-bg-sub rounded-card border border-border-warm p-4 text-center space-y-2">
        <p class="text-xs text-text-sub">Dang nhap de dat lich xem phong</p>
        <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php"
           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-btn bg-accent text-white font-semibold text-xs">
          <span class="material-symbols-outlined text-sm">login</span>Dang nhap
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include_once __DIR__ . '/dungchung/footer.php'; ?>
