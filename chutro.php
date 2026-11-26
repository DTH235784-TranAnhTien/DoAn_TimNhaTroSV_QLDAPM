<?php
$tieuDeTrang = 'Quan ly phong tro -- CampusNest AGU';
$trangHienTai = 'landlord';
require_once __DIR__ . '/cauhinh/database.php';
require_once __DIR__ . '/cauhinh/auth.php';

// Xác định đường dẫn gốc linh hoạt
$base_url = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($base_url === '' || $base_url === '/') {
    $base_url = strpos($_SERVER['REQUEST_URI'], '/QLNhaTro') !== false ? '/QLNhaTro' : '/DoAn_TimNhaTroSV_QLDAPM';
}

// Phan quyen: chi ChuTro & Admin moi vao duoc
if (!daDangNhap()) {
    header("Location: {$base_url}/taikhoan/dangnhap.php");
    exit();
}
$nd   = nguoiDungHienTai();
$mand = (int)$nd['mannd'];
$vaitro = $nd['vaitro'] ?? '';

if (!in_array($vaitro, ['ChuTro', 'QuanTriVien'])) {
    header("Location: {$base_url}/index.php");
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
    <a href="<?= $base_url ?>/chutro/dangtin.php"
       class="flex items-center gap-2 px-5 py-2.5 rounded-btn bg-accent text-white font-semibold text-sm transition-all duration-200 hover:opacity-90">
      <span class="material-symbols-outlined text-base">post_add</span>Đăng tin phòng mới
    </a>
    <a href="<?= $base_url ?>/chutro/lichhen.php"
       class="flex items-center gap-2 px-5 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-sm card-hover transition-all duration-200">
      <span class="material-symbols-outlined text-base">calendar_month</span>Sổ tay lịch hẹn
      <?php if ($dsLichHen): ?><span class="ml-1 px-1.5 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text text-[10px] font-bold"><?= count($dsLichHen) ?></span><?php endif; ?>
    </a>
    <a href="<?= $base_url ?>/chutro/danhsachphong.php"
       class="flex items-center gap-2 px-5 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-sm card-hover transition-all duration-200">
      <span class="material-symbols-outlined text-base">apartment</span>Quản lý phòng
    </a>
  </div>

  <?php if (empty($dsPhong)): ?>
  <!-- EMPTY STATE -->
  <div class="py-20 text-center space-y-4 bg-bg-main rounded-card border border-border-warm border-dashed">
    <span class="material-symbols-outlined text-5xl text-text-sub block mx-auto">apartment</span>
    <p class="text-base font-bold text-text-main">Chưa có phòng trọ nào</p>
    <p class="text-xs text-text-sub">Hãy đăng tin phòng trọ để sinh viên tìm kiếm.</p>
    <a href="<?= $base_url ?>/chutro/dangtin.php"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-btn bg-accent text-white font-semibold text-sm">
      <span class="material-symbols-outlined text-base">post_add</span>Đăng tin ngay
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
              <div class="flex items-center justify-end gap-1.5">
                <a href="<?= $base_url ?>/chitiet.php?id=<?= $p['MaPhong'] ?>"
                   class="px-2.5 py-1 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">
                  Xem
                </a>
                <a href="<?= $base_url ?>/chutro/danhsachphong.php"
                   class="px-2.5 py-1 rounded-btn bg-accent text-white font-semibold text-xs hover:opacity-90">
                  Quản lý
                </a>
              </div>
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
  <section class="space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-base font-bold text-text-main flex items-center gap-2">
        <span class="material-symbols-outlined text-accent text-lg">calendar_month</span>
        <span>Lịch hẹn xem phòng chờ duyệt (<?= count($dsLichHen) ?>)</span>
      </h2>
      <span class="text-xs text-text-sub">Sinh viên đang chờ bạn phản hồi</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <?php foreach ($dsLichHen as $lh):
        $ngayHen = date('d/m/Y', strtotime($lh['NgayHen']));
        $gioHen  = substr($lh['GioHen'], 0, 5);
        $sdt     = preg_replace('/[^0-9]/', '', $lh['SdtSV'] ?? '');
      ?>
      <div class="bg-bg-main rounded-card border border-border-warm p-4 space-y-3 shadow-2xs hover:border-accent/40 transition-colors">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-sm font-bold text-text-main"><?= htmlspecialchars($lh['TenSV']) ?></p>
            <p class="text-xs font-semibold text-accent mt-0.5">Phòng: <?= htmlspecialchars($lh['TenPhong']) ?></p>
          </div>
          <span class="px-2 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text text-[11px] font-bold shrink-0">
            <?= $gioHen ?> ngày <?= $ngayHen ?>
          </span>
        </div>

        <!-- Thông tin liên hệ nhanh & Ghi chú sinh viên -->
        <div class="text-xs bg-bg-sub/60 p-2.5 rounded-card space-y-1.5 border border-border-warm/60">
          <div class="flex items-center justify-between gap-2">
            <span class="text-text-sub flex items-center gap-1">
              <span class="material-symbols-outlined text-[14px]">call</span> SĐT: <?= htmlspecialchars($lh['SdtSV']) ?>
            </span>
            <?php if ($sdt): ?>
            <div class="flex items-center gap-1.5">
              <a href="tel:<?= $sdt ?>" class="px-2 py-0.5 rounded bg-bg-main border border-border-warm hover:border-accent text-[11px] font-semibold text-text-main inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[12px] text-accent">phone_in_talk</span> Gọi
              </a>
              <a href="https://zalo.me/<?= $sdt ?>" target="_blank" rel="noopener noreferrer" class="px-2 py-0.5 rounded bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100 text-[11px] font-semibold inline-flex items-center gap-1">
                Zalo
              </a>
            </div>
            <?php endif; ?>
          </div>

          <?php if (!empty($lh['GhiChu'])): ?>
          <p class="text-text-sub border-t border-border-warm/40 pt-1">
            <strong class="text-text-main">Sinh viên nhắn:</strong> "<?= htmlspecialchars($lh['GhiChu']) ?>"
          </p>
          <?php endif; ?>
        </div>

        <!-- Nút mở popup Duyệt kèm Phản hồi -->
        <div class="flex items-center justify-end gap-2 pt-1">
          <button type="button" 
                  onclick="openModalLich(<?= $lh['MaLichHen'] ?>, 'TuChoi', '<?= htmlspecialchars(addslashes($lh['TenSV'])) ?>', '<?= htmlspecialchars(addslashes($lh['TenPhong'])) ?>')" 
                  class="px-3.5 py-1.5 rounded-btn border border-status-red-border bg-status-red-bg/50 hover:bg-status-red-bg text-status-red-text font-semibold text-xs transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-[14px]">close</span> Từ chối
          </button>
          <button type="button" 
                  onclick="openModalLich(<?= $lh['MaLichHen'] ?>, 'XacNhan', '<?= htmlspecialchars(addslashes($lh['TenSV'])) ?>', '<?= htmlspecialchars(addslashes($lh['TenPhong'])) ?>')" 
                  class="px-4 py-1.5 rounded-btn bg-accent text-white font-semibold text-xs hover:opacity-90 transition-opacity flex items-center gap-1 shadow-2xs">
            <span class="material-symbols-outlined text-[14px]">check</span> Xác nhận hẹn
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- MODAL XỬ LÝ LỊCH HẸN KÈM LỜI NHẮN PHẢN HỒI -->
  <div id="modal_duyet_lich" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-[2px] flex items-center justify-center p-4">
    <div class="bg-bg-main w-full max-w-md rounded-card border border-border-warm shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
      <div class="p-4 border-b border-border-warm flex items-center justify-between">
        <h3 id="modal_lich_title" class="text-sm font-bold text-text-main flex items-center gap-2"></h3>
        <button type="button" onclick="closeModalLich()" class="text-text-sub hover:text-text-main">
          <span class="material-symbols-outlined text-lg">close</span>
        </button>
      </div>

      <form method="POST" action="<?= $base_url ?>/chutro/duyetlichhen.php" class="p-5 space-y-4">
        <input type="hidden" id="modal_ma_lich" name="ma_lich" value="">
        <input type="hidden" id="modal_hanh_dong" name="hanh_dong" value="">

        <div class="text-xs text-text-sub" id="modal_lich_info"></div>

        <div>
          <label class="block text-xs font-semibold text-text-main mb-1.5">
            Lời nhắn / Lời dặn dò gửi đến sinh viên:
          </label>
          <textarea id="modal_phan_hoi" name="phan_hoi" rows="3" 
                    class="w-full text-xs p-3 rounded-btn border border-border-warm bg-bg-sub/40 focus:outline-accent resize-none placeholder:text-text-sub"
                    placeholder="VD: Nhớ đến đúng giờ nhé em / Cô có mặt ở cổng nhà trọ..."></textarea>
          <div class="flex items-center gap-1.5 mt-2 flex-wrap">
            <span class="text-[11px] text-text-sub font-semibold">Gợi ý nhanh:</span>
            <button type="button" onclick="setQuickMsg('Đã xác nhận. Em đến trước 10 phút bấm chuông nhé!')" class="text-[10px] px-2 py-0.5 rounded bg-bg-sub border border-border-warm hover:border-accent">Đến đúng giờ</button>
            <button type="button" onclick="setQuickMsg('Hôm đó cô bận việc đột xuất, em vui lòng chọn lịch hẹn khác giúp cô nhé!')" class="text-[10px] px-2 py-0.5 rounded bg-bg-sub border border-border-warm hover:border-accent">Bận việc</button>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-border-warm">
          <button type="button" onclick="closeModalLich()" class="px-4 py-2 rounded-btn border border-border-warm text-xs font-semibold text-text-main hover:bg-bg-sub">Hủy bỏ</button>
          <button type="submit" id="modal_submit_btn" class="px-5 py-2 rounded-btn text-white text-xs font-semibold transition-opacity"></button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function openModalLich(id, action, tenSV, tenPhong) {
    document.getElementById('modal_ma_lich').value = id;
    document.getElementById('modal_hanh_dong').value = action;
    const titleEl = document.getElementById('modal_lich_title');
    const infoEl = document.getElementById('modal_lich_info');
    const submitBtn = document.getElementById('modal_submit_btn');
    const phanHoi = document.getElementById('modal_phan_hoi');

    if (action === 'XacNhan') {
      titleEl.innerHTML = '<span class="material-symbols-outlined text-accent text-lg">check_circle</span> Xác nhận lịch hẹn';
      infoEl.innerHTML = `Xác nhận lịch xem phòng <strong>${tenPhong}</strong> của bạn sinh viên <strong>${tenSV}</strong>.`;
      submitBtn.textContent = 'Xác nhận lịch hẹn';
      submitBtn.className = 'px-5 py-2 rounded-btn bg-accent text-white text-xs font-semibold hover:opacity-90';
      phanHoi.value = 'Đã xác nhận lịch hẹn. Em đến đúng giờ nhé!';
    } else {
      titleEl.innerHTML = '<span class="material-symbols-outlined text-rose-600 text-lg">cancel</span> Từ chối lịch hẹn';
      infoEl.innerHTML = `Từ chối lịch xem phòng <strong>${tenPhong}</strong> của bạn sinh viên <strong>${tenSV}</strong>.`;
      submitBtn.textContent = 'Từ chối lịch';
      submitBtn.className = 'px-5 py-2 rounded-btn bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700';
      phanHoi.value = 'Rất tiếc hiện tại phòng đã có khách cọc / Cô bận vào khung giờ này.';
    }

    document.getElementById('modal_duyet_lich').classList.remove('hidden');
  }

  function closeModalLich() {
    document.getElementById('modal_duyet_lich').classList.add('hidden');
  }

  function setQuickMsg(msg) {
    document.getElementById('modal_phan_hoi').value = msg;
  }
  </script>
  <?php endif; ?>

  <?php endif; ?>
</div>

<?php include_once __DIR__ . '/dungchung/footer.php'; ?>
