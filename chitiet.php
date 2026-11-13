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

      <?php
        // --- Danh gia ---
        $stmtDG = $conn->prepare("SELECT dg.*, u.HoTen, u.AnhDaiDien FROM DanhGia dg JOIN NguoiDung u ON dg.MaSinhVien = u.MaNguoiDung WHERE dg.MaPhong = ? ORDER BY dg.NgayDanhGia ASC");
        $stmtDG->bind_param('i', $id);
        $stmtDG->execute();
        $dsDG = $stmtDG->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtDG->close();

        $trungBinhSao = 0;
        if (count($dsDG) > 0) {
          $trungBinhSao = array_sum(array_column($dsDG, 'SoSao')) / count($dsDG);
        }

        // Kiem tra quyen danh gia (SinhVien co lich hen DaXacNhan hoac HoanThanh)
        $coQuyen = false;
        if (daDangNhap() && coVaiTro('SinhVien')) {
          $ndHienTai = nguoiDungHienTai();
          $maSV = (int)($ndHienTai['mannd'] ?? 0);
          $stmtLH = $conn->prepare("SELECT MaLichHen FROM LichHen WHERE MaSinhVien = ? AND MaPhong = ? AND TrangThai IN ('DaXacNhan', 'HoanThanh') LIMIT 1");
          $stmtLH->bind_param('ii', $maSV, $id);
          $stmtLH->execute();
          $coQuyen = ($stmtLH->get_result()->num_rows > 0);
          $stmtLH->close();
        }

        function renderSao(int $n, int $max = 5): string {
          $out = '';
          for ($i = 1; $i <= $max; $i++) {
            $out .= $i <= $n ? '<span class="text-yellow-400">★</span>' : '<span class="text-text-sub">☆</span>';
          }
          return $out;
        }
      ?>

      <!-- Danh gia & Binh luan -->
      <div class="bg-bg-main rounded-card border border-border-warm p-5 space-y-5">
        <div class="flex items-center justify-between">
          <h2 class="text-base font-bold text-text-main flex items-center gap-2">
            <span class="material-symbols-outlined text-accent">star</span>Đánh giá phòng
          </h2>
          <?php if (count($dsDG) > 0): ?>
          <span class="text-sm font-semibold text-text-main tnum">
            <?= number_format($trungBinhSao, 1) ?> / 5
            <span class="text-yellow-400">★</span>
            <span class="text-text-sub text-xs">(<?= count($dsDG) ?> đánh giá)</span>
          </span>
          <?php endif; ?>
        </div>

        <?php if (count($dsDG) === 0): ?>
        <p class="text-sm text-text-sub italic">Chưa có đánh giá nào cho phòng này.</p>
        <?php else: ?>
        <div class="space-y-4 divide-y divide-border-warm">
          <?php foreach ($dsDG as $dg): ?>
          <div class="pt-4 first:pt-0 space-y-2">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full overflow-hidden bg-bg-sub border border-border-warm shrink-0">
                <?php if ($dg['AnhDaiDien']): ?>
                <img src="<?= htmlspecialchars($dg['AnhDaiDien']) ?>" alt="<?= htmlspecialchars($dg['HoTen']) ?>" class="w-full h-full object-cover">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center">
                  <span class="material-symbols-outlined text-text-sub text-xl">person</span>
                </div>
                <?php endif; ?>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-text-main truncate"><?= htmlspecialchars($dg['HoTen']) ?></p>
                <p class="text-xs text-text-sub"><?= $dg['NgayDanhGia'] ? date('d/m/Y', strtotime($dg['NgayDanhGia'])) : '' ?></p>
              </div>
              <div class="text-base shrink-0"><?= renderSao((int)$dg['SoSao']) ?></div>
            </div>
            <div class="flex flex-wrap gap-3 text-xs text-text-sub pl-12">
              <span>An ninh: <?= renderSao((int)($dg['DiemAnNinh'] ?? 5)) ?></span>
              <span>Giá cả: <?= renderSao((int)($dg['DiemGiaCa'] ?? 5)) ?></span>
              <span>Thái độ: <?= renderSao((int)($dg['DiemThaiDoChuTro'] ?? 5)) ?></span>
            </div>
            <?php if (!empty($dg['NoiDung'])): ?>
            <p class="text-sm text-text-main pl-12 leading-relaxed"><?= nl2br(htmlspecialchars($dg['NoiDung'])) ?></p>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Form danh gia -->
        <?php if (!daDangNhap()): ?>
        <div class="mt-2 p-4 bg-bg-sub rounded-btn border border-border-warm text-center space-y-2">
          <p class="text-xs text-text-sub">Đăng nhập để đánh giá phòng này</p>
          <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php"
             class="inline-flex items-center gap-1.5 px-4 py-2 rounded-btn bg-accent text-white font-semibold text-xs">
            <span class="material-symbols-outlined text-sm">login</span>Đăng nhập
          </a>
        </div>
        <?php elseif ($coQuyen): ?>
        <div class="mt-2 pt-4 border-t border-border-warm space-y-3">
          <h3 class="text-sm font-bold text-text-main">Viết đánh giá của bạn</h3>
          <form method="POST" action="/DoAn_TimNhaTroSV_QLDAPM/sinhvien/danhgia.php" class="space-y-3">
            <input type="hidden" name="ma_phong" value="<?= $id ?>">
            <!-- Tong sao -->
            <div>
              <label class="text-xs text-text-sub block mb-1">Điểm tổng thể <span class="text-status-red-text">*</span></label>
              <div class="flex gap-1" id="star-wrapper">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                <label class="cursor-pointer text-2xl text-text-sub hover:text-yellow-400 transition-colors" data-val="<?= $s ?>">
                  <input type="radio" name="so_sao" value="<?= $s ?>" class="sr-only" required>
                  ★
                </label>
                <?php endfor; ?>
              </div>
            </div>
            <!-- Diem con -->
            <div class="grid grid-cols-3 gap-3">
              <?php foreach (['an_ninh' => 'An ninh', 'gia_ca' => 'Giá cả', 'thai_do' => 'Thái độ'] as $k => $lbl): ?>
              <div>
                <label class="text-xs text-text-sub block mb-1"><?= $lbl ?></label>
                <select name="<?= $k ?>" required
                  class="w-full px-2 py-1.5 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
                  <option value="">--</option>
                  <?php for ($n = 1; $n <= 5; $n++): ?>
                  <option value="<?= $n ?>"><?= $n ?> sao</option>
                  <?php endfor; ?>
                </select>
              </div>
              <?php endforeach; ?>
            </div>
            <!-- Nhan xet -->
            <div>
              <label class="text-xs text-text-sub block mb-1">Nhận xét</label>
              <textarea name="nhan_xet" rows="3" placeholder="Chia sẻ trải nghiệm của bạn về phòng này..."
                class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent resize-none"></textarea>
            </div>
            <button type="submit"
              class="w-full py-2.5 rounded-btn bg-accent text-white font-semibold text-sm hover:opacity-90 transition-opacity">
              Gửi đánh giá
            </button>
          </form>
        </div>
        <?php else: ?>
        <div class="mt-2 p-3 bg-bg-sub rounded-btn border border-border-warm text-center">
          <p class="text-xs text-text-sub">Chỉ sinh viên đã xem phòng mới được đánh giá</p>
        </div>
        <?php endif; ?>
      </div>

      <!-- Bao cao vi pham -->
      <div class="bg-bg-main rounded-card border border-border-warm p-5">
        <button type="button" onclick="document.getElementById('baocao-form').classList.toggle('hidden')"
          class="flex items-center gap-1.5 text-xs text-status-red-text hover:underline font-semibold transition-colors">
          <span class="material-symbols-outlined text-sm">flag</span>Báo cáo vi phạm
        </button>
        <div id="baocao-form" class="hidden mt-4 space-y-3">
          <?php if (!daDangNhap()): ?>
          <p class="text-xs text-text-sub">Bạn cần <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php" class="text-accent underline">đăng nhập</a> để báo cáo vi phạm.</p>
          <?php else: ?>
          <form method="POST" action="/DoAn_TimNhaTroSV_QLDAPM/sinhvien/baocao.php" class="space-y-3">
            <input type="hidden" name="ma_phong" value="<?= $id ?>">
            <div>
              <label class="text-xs text-text-sub block mb-1">Lý do báo cáo <span class="text-status-red-text">*</span></label>
              <select name="ly_do" required
                class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
                <option value="">-- Chọn lý do --</option>
                <option value="Thong tin sai lech">Thông tin sai lệch</option>
                <option value="Lua dao">Lừa đảo</option>
                <option value="Noi dung khong phu hop">Nội dung không phù hợp</option>
                <option value="Phong khong ton tai">Phòng không tồn tại</option>
                <option value="Khac">Khác</option>
              </select>
            </div>
            <div>
              <label class="text-xs text-text-sub block mb-1">Mô tả thêm (không bắt buộc)</label>
              <textarea name="mo_ta" rows="3" placeholder="Mô tả chi tiết vi phạm..."
                class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent resize-none"></textarea>
            </div>
            <button type="submit"
              class="px-4 py-2 rounded-btn bg-status-red-bg text-status-red-text border border-status-red-text text-xs font-semibold hover:bg-status-red-text hover:text-white transition-all duration-200">
              Gửi báo cáo
            </button>
          </form>
          <?php endif; ?>
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

<script>
(function () {
  var wrapper = document.getElementById('star-wrapper');
  if (!wrapper) return;
  var labels = wrapper.querySelectorAll('label');
  function paint(val) {
    labels.forEach(function (lbl) {
      lbl.style.color = parseInt(lbl.dataset.val) <= val ? '#facc15' : '';
    });
  }
  labels.forEach(function (lbl) {
    lbl.addEventListener('mouseenter', function () { paint(parseInt(lbl.dataset.val)); });
    lbl.addEventListener('click', function () {
      lbl.querySelector('input').checked = true;
      paint(parseInt(lbl.dataset.val));
    });
  });
  wrapper.addEventListener('mouseleave', function () {
    var checked = wrapper.querySelector('input:checked');
    paint(checked ? parseInt(checked.value) : 0);
  });
})();
</script>

<?php include_once __DIR__ . '/dungchung/footer.php'; ?>
