<?php
$tieuDeTrang = 'Trang Chính — CampusNest AGU | Bảng tin trọ ĐH An Giang';
$trangHienTai = 'home';
include_once __DIR__ . '/dungchung/header.php';
?>

<!-- ================= [1] TRANG HOME ================= -->
<div class="w-full bg-bg-sub border-b border-border-warm py-2 px-4 sm:px-6 lg:px-8">
  <div class="max-w-[1280px] mx-auto flex flex-wrap items-center justify-between gap-2 text-xs text-text-sub">
    <span>Phối hợp Hội SV AGU &amp; Tổ trật tự địa phương — HK1 <?= date('Y') ?></span>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/camnang.php" class="text-accent font-semibold underline">Tải mẫu hợp đồng thuê trọ chuẩn SV →</a>
  </div>
</div>

<section class="w-full border-b border-border-warm py-10 px-4 sm:px-6 lg:px-8 bg-bg-main">
  <div class="max-w-[1280px] mx-auto">
    <div class="max-w-3xl mb-5">
      <h1 class="font-serif text-3xl sm:text-4xl font-bold text-text-main tracking-tight">
        Tìm phòng trọ &amp; bạn cùng phòng quanh Đại học An Giang
      </h1>
      <p class="text-sm text-text-sub mt-1.5">
        Giá niêm yết trực tiếp từ chủ nhà tại P. Đông Xuyên, Mỹ Xuyên, Bình Khánh. Có kiểm tra PCCC và đồng hồ điện nước riêng.
      </p>
    </div>

    <form action="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" method="get" class="bg-bg-sub p-4 rounded-card border border-border-warm">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
        <div class="md:col-span-4">
          <label class="block text-xs font-semibold text-text-main mb-1">Cơ sở học tập</label>
          <select name="coso" class="w-full bg-bg-main border border-border-warm rounded-card px-3 py-2.5 text-sm text-text-main focus:border-accent outline-none">
            <option value="khu-a">Khu A — 18 Ung Văn Khiêm (P. Đông Xuyên)</option>
            <option value="khu-b">Khu B — 25 Võ Thị Sáu (P. Mỹ Xuyên)</option>
            <option value="ktx">Ký túc xá AGU (Đông Xuyên)</option>
            <option value="dinh-my">Cơ sở Định Mỹ (Thoại Sơn)</option>
          </select>
        </div>
        <div class="md:col-span-4">
          <label class="block text-xs font-semibold text-text-main mb-1">Loại phòng</label>
          <select name="loaiphong" class="w-full bg-bg-main border border-border-warm rounded-card px-3 py-2.5 text-sm text-text-main focus:border-accent outline-none">
            <option value="all">Tất cả loại hình phòng</option>
            <option value="gac-duc">Phòng trọ có gác đúc kiên cố</option>
            <option value="khep-kin">Phòng trọ khép kín (WC trong phòng)</option>
            <option value="ktx-tu-nhan">Ký túc xá tư nhân / Phòng ghép</option>
            <option value="studio">Phòng rộng có ban công riêng</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-xs font-semibold text-text-main mb-1">Ngân sách</label>
          <select name="ngansach" class="w-full bg-bg-main border border-border-warm rounded-card px-3 py-2.5 text-sm text-text-main focus:border-accent outline-none tnum">
            <option value="all">Mọi mức giá</option>
            <option value="duoi-1m2">Dưới 1.2 triệu</option>
            <option value="1m2-1m8">1.2 – 1.8 triệu</option>
            <option value="tren-1m8">Trên 1.8 triệu</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <button type="submit" class="w-full h-[42px] bg-accent text-white font-semibold text-sm rounded-btn flex items-center justify-center gap-1.5 transition-all duration-200 active:scale-[0.98]">
            <span class="material-symbols-outlined">search</span>
            <span>Tìm phòng</span>
          </button>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-border-warm text-xs">
        <span class="text-text-sub font-medium">Lọc nhanh:</span>
        <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" class="px-2.5 py-1 rounded-card bg-bg-main border border-border-warm text-text-main card-hover transition-all duration-200 active:scale-[0.98]">Cách cổng trường &lt; 500m</a>
        <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" class="px-2.5 py-1 rounded-card bg-bg-main border border-border-warm text-text-main card-hover transition-all duration-200 active:scale-[0.98]">Giờ giấc tự do 24/7</a>
        <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" class="px-2.5 py-1 rounded-card bg-bg-main border border-border-warm text-text-main card-hover transition-all duration-200 active:scale-[0.98]">Có máy lạnh</a>
        <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" class="px-2.5 py-1 rounded-card bg-bg-main border border-border-warm text-text-main card-hover transition-all duration-200 active:scale-[0.98]">Khu trọ nữ riêng</a>
        <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php" class="ml-auto text-accent font-semibold underline">Tìm bạn ở ghép bằng AI →</a>
      </div>
    </form>

    <p class="text-xs text-text-sub mt-3 tnum">
      Bảng tin nhà trọ ĐH An Giang · Miễn phí đăng tin · 0đ phí môi giới
    </p>
  </div>
</section>

<section class="w-full py-10 px-4 sm:px-6 lg:px-8">
  <div class="max-w-[1280px] mx-auto">
    <?php
    /* ── Query phòng trọ thực từ DB ── */
    require_once __DIR__ . '/cauhinh/database.php';
    require_once __DIR__ . '/cauhinh/auth.php';

    $sqlPhong = "
        SELECT p.MaPhong, p.TenPhong, p.GiaThue, p.DienTich, p.TrangThai, p.NgayDang,
               p.TienDien, p.MoTa,
               n.TenNhaTro, n.DiaChi,
               u.HoTen AS TenChuTro, u.SoDienThoai,
               (SELECT h.DuongDan FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.LaAnhChinh = 1 LIMIT 1) AS AnhChinh
        FROM PhongTro p
        JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
        JOIN NguoiDung u ON n.MaChuTro = u.MaNguoiDung
        WHERE p.TrangThaiDuyet = 'DaDuyet' AND p.TrangThai = 'ConPhong'
        ORDER BY p.NgayDang DESC
        LIMIT 6
    ";
    $resPhong  = $conn->query($sqlPhong);
    $dsPhong   = $resPhong ? $resPhong->fetch_all(MYSQLI_ASSOC) : [];

    /* KPI tổng phòng đã duyệt */
    $kpiRow = $conn->query("SELECT COUNT(*) AS tong FROM PhongTro WHERE TrangThaiDuyet='DaDuyet' AND TrangThai='ConPhong'");
    $tongPhong = $kpiRow ? (int)$kpiRow->fetch_assoc()['tong'] : 0;
    ?>

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2 mb-5">
      <div>
        <h2 class="text-xl font-bold text-text-main">Phòng trọ đang trống — đã được duyệt</h2>
        <p class="text-xs text-text-sub mt-0.5">
          <?= $tongPhong > 0 ? $tongPhong . ' phòng đang còn trống trên hệ thống' : 'Hệ thống đang cập nhật tin đăng từ chủ trọ' ?>
        </p>
      </div>
      <?php if ($tongPhong > 0): ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" class="text-sm font-semibold text-accent underline self-start sm:self-auto tnum">
        Xem tất cả <?= $tongPhong ?> phòng →
      </a>
      <?php endif; ?>
    </div>

    <?php if (empty($dsPhong)): ?>
    <!-- EMPTY STATE -->
    <div class="py-16 text-center space-y-4 bg-bg-main rounded-card border border-border-warm border-dashed">
      <span class="material-symbols-outlined text-5xl text-text-sub block mx-auto">apartment</span>
      <div>
        <p class="text-base font-bold text-text-main">Chưa có phòng trọ nào được đăng</p>
        <p class="text-xs text-text-sub mt-1">Chủ trọ hãy đăng nhập và đăng tin phòng trọ để sinh viên tìm kiếm.</p>
      </div>
      <?php if (daDangNhap() && coVaiTro('ChuTro')): ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro/dangtin.php"
         class="inline-flex items-center gap-2 px-5 py-2.5 rounded-btn bg-accent text-white font-semibold text-sm transition-all duration-200 hover:opacity-90">
        <span class="material-symbols-outlined text-base">post_add</span>Đăng tin phòng trọ ngay
      </a>
      <?php else: ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php"
         class="inline-flex items-center gap-2 px-5 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-sm transition-all duration-200 hover:bg-bg-sub">
        <span class="material-symbols-outlined text-base">login</span>Đăng nhập để bắt đầu
      </a>
      <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- GRID PHÒNG THỰC TỪ DB -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($dsPhong as $p):
        $gia     = number_format($p['GiaThue'], 0, ',', '.');
        $dien    = $p['DienTich'] ? $p['DienTich'] . 'm²' : '—';
        $diachi  = mb_strimwidth($p['DiaChi'], 0, 60, '…');
        $anh     = $p['AnhChinh'] ?: '';
        $ngayDang = $p['NgayDang'] ? date('d/m/Y', strtotime($p['NgayDang'])) : '—';
        $sdt     = $p['SoDienThoai'] ?? '';
      ?>
      <article class="bg-bg-main rounded-card border border-border-warm card-hover overflow-hidden flex flex-col">
        <!-- Ảnh -->
        <div class="relative overflow-hidden aspect-[4/3] w-full bg-bg-sub">
          <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $p['MaPhong'] ?>">
            <?php if ($anh): ?>
            <img loading="lazy" src="<?= htmlspecialchars($anh) ?>"
                 alt="<?= htmlspecialchars($p['TenPhong']) ?>"
                 onerror="this.onerror=null;this.parentNode.innerHTML='<span class=\'material-symbols-outlined text-text-sub text-4xl absolute inset-0 flex items-center justify-center bg-bg-sub\'>image</span>'"
                 class="w-full h-full object-cover transition-transform duration-300 hover:scale-[1.03]">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center bg-bg-sub">
              <span class="material-symbols-outlined text-text-sub text-4xl">image_not_supported</span>
            </div>
            <?php endif; ?>
          </a>
          <span class="absolute top-2.5 left-2.5 px-2.5 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold inline-flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>Còn phòng
          </span>
        </div>

        <!-- Info -->
        <div class="p-4 flex flex-col gap-2 flex-1">
          <div class="flex items-baseline justify-between">
            <span class="text-base font-bold text-text-main tnum"><?= $gia ?> đ/tháng</span>
            <span class="text-xs text-text-sub tnum"><?= $dien ?></span>
          </div>
          <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $p['MaPhong'] ?>">
            <h3 class="text-sm font-bold text-text-main hover:text-accent line-clamp-1"><?= htmlspecialchars($p['TenPhong']) ?></h3>
          </a>
          <p class="text-xs text-text-sub line-clamp-1"><?= htmlspecialchars($diachi) ?></p>
          <p class="text-xs text-text-sub tnum">Chủ: <?= htmlspecialchars($p['TenChuTro']) ?> · <?= $ngayDang ?></p>

          <div class="flex items-center gap-2 pt-2 border-t border-border-warm mt-auto">
            <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $p['MaPhong'] ?>"
               class="w-1/2 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover text-center">
              Xem chi tiết
            </a>
            <?php if ($sdt): ?>
            <a href="tel:<?= htmlspecialchars($sdt) ?>"
               class="w-1/2 py-2 rounded-btn bg-accent text-white font-semibold text-xs text-center flex items-center justify-center gap-1">
              <span class="material-symbols-outlined text-sm">call</span>Gọi chủ trọ
            </a>
            <?php else: ?>
            <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $p['MaPhong'] ?>"
               class="w-1/2 py-2 rounded-btn bg-accent text-white font-semibold text-xs text-center">
              Liên hệ
            </a>
            <?php endif; ?>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <?php if ($tongPhong > 6): ?>
    <div class="mt-6 text-center">
      <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php"
         class="inline-flex items-center gap-2 px-6 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-sm card-hover transition-all duration-200">
        Xem thêm <?= $tongPhong - 6 ?> phòng còn lại →
      </a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="mt-10 pt-6 border-t border-border-warm">
      <h3 class="text-xs font-bold uppercase tracking-wider text-text-sub mb-3">Tiện ích tra cứu bảng tin sinh viên</h3>
      <div class="divide-y divide-border-warm border-y border-border-warm">
        <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" class="w-full py-3.5 px-2 flex items-center justify-between text-left hover:bg-bg-sub transition-all duration-200 active:scale-[0.98] block">
          <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-accent">map</span>
            <div>
              <span class="text-sm font-bold text-text-main">Bản đồ cụm trọ &amp; Kho 280+ phòng quanh trường</span>
              <span class="text-xs text-text-sub block sm:inline sm:ml-2">Tra cứu theo hẻm Ung Văn Khiêm, Võ Thị Sáu và quét mô tả tự nhiên bằng AI</span>
            </div>
          </div>
          <span class="text-xs font-semibold text-accent underline shrink-0">Xem trên bản đồ →</span>
        </a>

        <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php" class="w-full py-3.5 px-2 flex items-center justify-between text-left hover:bg-bg-sub transition-all duration-200 active:scale-[0.98] block">
          <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-accent">group</span>
            <div>
              <span class="text-sm font-bold text-text-main">Ghép bạn cùng phòng sinh viên AGU (AGU-Match AI)</span>
              <span class="text-xs text-text-sub block sm:inline sm:ml-2">Tìm bạn cùng khoa, hợp giờ giấc sinh hoạt để chia đôi tiền phòng hàng tháng</span>
            </div>
          </div>
          <span class="text-xs font-semibold text-accent underline shrink-0">Tìm bạn ghép →</span>
        </a>

        <a href="/DoAn_TimNhaTroSV_QLDAPM/camnang.php" class="w-full py-3.5 px-2 flex items-center justify-between text-left hover:bg-bg-sub transition-all duration-200 active:scale-[0.98] block">
          <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-accent">description</span>
            <div>
              <span class="text-sm font-bold text-text-main">Cẩm nang pháp lý, Biểu mẫu hợp đồng chuẩn &amp; Review thực tế</span>
              <span class="text-xs text-text-sub block sm:inline sm:ml-2">Tải mẫu hợp đồng không mất cọc và đọc nhận xét từ sinh viên khóa trước</span>
            </div>
          </div>
          <span class="text-xs font-semibold text-accent underline shrink-0">Xem toàn bộ →</span>
        </a>
      </div>
    </div>
  </div>
</section>

<?php include_once __DIR__ . '/dungchung/footer.php'; ?>