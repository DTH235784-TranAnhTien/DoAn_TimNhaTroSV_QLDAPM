<?php
$tieuDeTrang = 'Quan ly phong tro -- CampusNest AGU';
$trangHienTai = 'landlord';
require_once __DIR__ . '/cauhinh/database.php';
require_once __DIR__ . '/cauhinh/auth.php';

// Phan quyen: chi ChuTro & Admin moi vao duoc
if (!daDangNhap()) {
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php');
    exit();
}
$nd   = nguoiDungHienTai();
$mand = (int)$nd['mannd'];
$vaitro = $nd['vaitro'] ?? '';

if (!in_array($vaitro, ['ChuTro', 'QuanTriVien'])) {
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/index.php');
    exit();
}

// Lay danh sach nha tro cua chu tro nay
$stmtNT = $conn->prepare("SELECT MaNhaTro, TenNhaTro, DiaChi FROM NhaTro WHERE MaChuTro = ?");
$stmtNT->bind_param('i', $mand);
$stmtNT->execute();
$dsNhaTro = $stmtNT->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtNT->close();

$maNhaTros = array_column($dsNhaTro, 'MaNhaTro');

$tongPhong = 0; $conPhong = 0; $daThue = 0; $choDuyet = 0;
$dsPhong = [];

if ($maNhaTros) {
    $in = implode(',', array_fill(0, count($maNhaTros), '?'));
    $types = str_repeat('i', count($maNhaTros));

    $stmtP = $conn->prepare("SELECT p.*, n.TenNhaTro FROM PhongTro p JOIN NhaTro n ON p.MaNhaTro=n.MaNhaTro WHERE p.MaNhaTro IN ($in) ORDER BY p.NgayDang DESC");
    $stmtP->bind_param($types, ...$maNhaTros);
    $stmtP->execute();
    $dsPhong = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtP->close();

    foreach ($dsPhong as $p) {
        $tongPhong++;
        if ($p['TrangThai'] === 'ConPhong') $conPhong++;
        if ($p['TrangThai'] === 'DaThue')   $daThue++;
        if ($p['TrangThaiDuyet'] === 'ChoDuyet') $choDuyet++;
    }
}

// Lich hen cho duyet
$stmtLH = $conn->prepare("
    SELECT lh.*, sv.HoTen AS TenSV, sv.SoDienThoai AS SdtSV, p.TenPhong
    FROM LichHen lh
    JOIN NguoiDung sv ON lh.MaSinhVien = sv.MaNguoiDung
    JOIN PhongTro p ON lh.MaPhong = p.MaPhong
    JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
    WHERE n.MaChuTro = ? AND lh.TrangThai = 'ChoDuyet'
    ORDER BY lh.NgayHen ASC LIMIT 5
");
$stmtLH->bind_param('i', $mand);
$stmtLH->execute();
$dsLichHen = $stmtLH->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtLH->close();

include_once __DIR__ . '/dungchung/header.php';
?>

<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

  <!-- Header -->
  <section class="bg-bg-sub p-5 rounded-card border border-border-warm">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-full border border-border-warm overflow-hidden bg-bg-main shrink-0">
          <?php $anh = $nd['anhdaidien'] ?? ''; ?>
          <?php if ($anh): ?>
          <img src="<?= htmlspecialchars($anh) ?>" alt="Avatar" class="w-full h-full object-cover">
          <?php else: ?>
          <div class="w-full h-full flex items-center justify-center">
            <span class="material-symbols-outlined text-text-sub text-3xl">person</span>
          </div>
          <?php endif; ?>
        </div>
        <div>
          <h1 class="text-xl font-bold text-text-main"><?= htmlspecialchars($nd['hoten']) ?></h1>
          <p class="text-xs text-text-sub"><?= htmlspecialchars($nd['email']) ?></p>
          <?php if (empty($dsNhaTro)): ?>
          <p class="text-xs text-status-amber-text mt-0.5">Chua co nha tro nao. Hay dang tin phong dau tien!</p>
          <?php endif; ?>
        </div>
      </div>

      <dl class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-6 tnum border-t lg:border-t-0 pt-4 lg:pt-0">
        <div><dt class="text-xs text-text-sub">Tong phong</dt><dd class="text-xl font-bold text-text-main mt-0.5"><?= str_pad($tongPhong, 2, '0', STR_PAD_LEFT) ?></dd></div>
        <div><dt class="text-xs text-text-sub">Da thue</dt><dd class="text-xl font-bold text-text-main mt-0.5"><?= str_pad($daThue, 2, '0', STR_PAD_LEFT) ?></dd></div>
        <div><dt class="text-xs text-text-sub">Con trong</dt><dd class="text-xl font-bold text-accent mt-0.5"><?= str_pad($conPhong, 2, '0', STR_PAD_LEFT) ?></dd></div>
        <div><dt class="text-xs text-text-sub">Cho duyet lich</dt><dd class="text-xl font-bold text-status-amber-text mt-0.5"><?= str_pad(count($dsLichHen), 2, '0', STR_PAD_LEFT) ?></dd></div>
      </dl>
    </div>
  </section>

  <!-- Action buttons -->
  <div class="flex flex-wrap gap-3">
    <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro/dangtin.php"
       class="flex items-center gap-2 px-5 py-2.5 rounded-btn bg-accent text-white font-semibold text-sm transition-all duration-200 hover:opacity-90">
      <span class="material-symbols-outlined text-base">post_add</span>Dang tin phong moi
    </a>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro/duyetlichhen.php"
       class="flex items-center gap-2 px-5 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-sm card-hover transition-all duration-200">
      <span class="material-symbols-outlined text-base">calendar_check</span>Duyet lich hen
      <?php if ($dsLichHen): ?><span class="ml-1 px-1.5 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text text-[10px] font-bold"><?= count($dsLichHen) ?></span><?php endif; ?>
    </a>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro/danhsachphong.php"
       class="flex items-center gap-2 px-5 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-sm card-hover transition-all duration-200">
      <span class="material-symbols-outlined text-base">apartment</span>Quan ly phong
    </a>
  </div>

  <?php if (empty($dsPhong)): ?>
  <!-- EMPTY STATE -->
  <div class="py-20 text-center space-y-4 bg-bg-main rounded-card border border-border-warm border-dashed">
    <span class="material-symbols-outlined text-5xl text-text-sub block mx-auto">apartment</span>
    <p class="text-base font-bold text-text-main">Chua co phong tro nao</p>
    <p class="text-xs text-text-sub">Hay dang tin phong tro de sinh vien tim kiem.</p>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro/dangtin.php"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-btn bg-accent text-white font-semibold text-sm">
      <span class="material-symbols-outlined text-base">post_add</span>Dang tin ngay
    </a>
  </div>

  <?php else: ?>
  <!-- DANH SACH PHONG -->
  <section>
    <h2 class="text-lg font-bold text-text-main mb-4">Danh sach phong cua ban</h2>
    <div class="bg-bg-main rounded-card border border-border-warm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-bg-sub text-[11px] font-bold uppercase tracking-wider text-text-sub">
            <tr>
              <th class="py-2.5 px-4">Ten phong</th>
              <th class="py-2.5 px-4">Nha tro</th>
              <th class="py-2.5 px-4 tnum">Gia thue</th>
              <th class="py-2.5 px-4">Trang thai</th>
              <th class="py-2.5 px-4">Duyet</th>
              <th class="py-2.5 px-4 text-right">Hanh dong</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($dsPhong as $p):
            $giaF  = number_format($p['GiaThue'], 0, ',', '.');
            $tt    = $p['TrangThai'];
            $duyet = $p['TrangThaiDuyet'];
            $ttBadge = $tt === 'ConPhong'
              ? '<span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold">Con phong</span>'
              : ($tt === 'DaThue'
                ? '<span class="px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text text-xs font-semibold">Da thue</span>'
                : '<span class="px-2 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text text-xs font-semibold">Bao tri</span>');
            $duyetBadge = $duyet === 'DaDuyet'
              ? '<span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold">Da duyet</span>'
              : ($duyet === 'TuChoi'
                ? '<span class="px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text text-xs font-semibold">Tu choi</span>'
                : '<span class="px-2 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text text-xs font-semibold">Cho duyet</span>');
          ?>
          <tr class="border-t border-border-warm hover:bg-bg-sub/40">
            <td class="py-2.5 px-4 font-semibold text-text-main"><?= htmlspecialchars($p['TenPhong']) ?></td>
            <td class="py-2.5 px-4 text-xs text-text-sub"><?= htmlspecialchars($p['TenNhaTro']) ?></td>
            <td class="py-2.5 px-4 tnum text-text-main"><?= $giaF ?> d</td>
            <td class="py-2.5 px-4"><?= $ttBadge ?></td>
            <td class="py-2.5 px-4"><?= $duyetBadge ?></td>
            <td class="py-2.5 px-4 text-right">
              <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $p['MaPhong'] ?>"
                 class="px-3 py-1 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">
                Xem
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- Lich hen cho duyet -->
  <?php if ($dsLichHen): ?>
  <section>
    <h2 class="text-lg font-bold text-text-main mb-4">Lich hen cho duyet (<?= count($dsLichHen) ?>)</h2>
    <div class="space-y-3">
      <?php foreach ($dsLichHen as $lh):
        $ngayHen = date('d/m/Y', strtotime($lh['NgayHen']));
        $gioHen  = substr($lh['GioHen'], 0, 5);
      ?>
      <div class="bg-bg-main rounded-card border border-border-warm p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="space-y-0.5">
          <p class="text-sm font-bold text-text-main"><?= htmlspecialchars($lh['TenSV']) ?> - <?= htmlspecialchars($lh['TenPhong']) ?></p>
          <p class="text-xs text-text-sub tnum"><?= $gioHen ?> ngay <?= $ngayHen ?></p>
          <?php if ($lh['GhiChu']): ?><p class="text-xs text-text-sub"><?= htmlspecialchars($lh['GhiChu']) ?></p><?php endif; ?>
        </div>
        <div class="flex items-center gap-2">
          <form method="POST" action="/DoAn_TimNhaTroSV_QLDAPM/chutro/duyetlichhen.php">
            <input type="hidden" name="ma_lich" value="<?= $lh['MaLichHen'] ?>">
            <input type="hidden" name="hanh_dong" value="XacNhan">
            <button type="submit" class="px-3 py-1.5 rounded-btn bg-accent text-white font-semibold text-xs">Xac nhan</button>
          </form>
          <form method="POST" action="/DoAn_TimNhaTroSV_QLDAPM/chutro/duyetlichhen.php">
            <input type="hidden" name="ma_lich" value="<?= $lh['MaLichHen'] ?>">
            <input type="hidden" name="hanh_dong" value="TuChoi">
            <button type="submit" class="px-3 py-1.5 rounded-btn border border-status-red-border bg-status-red-bg text-status-red-text font-semibold text-xs">Tu choi</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php endif; ?>
</div>

<?php include_once __DIR__ . '/dungchung/footer.php'; ?>
