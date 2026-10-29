<?php
$tieuDeTrang = 'Bản đồ & Danh sách phòng trọ — CampusNest AGU';
$trangHienTai = 'explore';
require_once __DIR__ . '/cauhinh/database.php';
require_once __DIR__ . '/cauhinh/auth.php';

// Các bộ lọc từ URL
$filterCoso      = $_GET['coso']      ?? ($_GET['kv'] ?? '');
$filterLoaiphong = $_GET['loaiphong'] ?? '';
$filterGia       = $_GET['ngansach']  ?? ($_GET['gia'] ?? '');
$filterTag       = $_GET['tag']       ?? '';
$q               = trim($_GET['q']    ?? '');

// Xây dựng câu truy vấn an toàn
$where  = ["p.TrangThaiDuyet = 'DaDuyet'", "p.TrangThai = 'ConPhong'"];
$params = [];
$types  = '';

// 1. Tìm kiếm từ khóa
if ($q !== '') {
    $like = "%$q%";
    $where[] = "(p.TenPhong LIKE ? OR n.DiaChi LIKE ? OR n.TenNhaTro LIKE ? OR p.MoTa LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= 'ssss';
}

// 2. Lọc theo cơ sở / khu vực
if ($filterCoso === 'khu-a' || $filterCoso === 'Dong Xuyen') {
    $where[] = "(n.DiaChi LIKE '%Đông Xuyên%' OR n.DiaChi LIKE '%Dong Xuyen%' OR n.DiaChi LIKE '%Ung Văn Khiêm%' OR n.DiaChi LIKE '%Ung Van Khiem%')";
} elseif ($filterCoso === 'khu-b' || $filterCoso === 'My Xuyen') {
    $where[] = "(n.DiaChi LIKE '%Mỹ Xuyên%' OR n.DiaChi LIKE '%My Xuyen%' OR n.DiaChi LIKE '%Võ Thị Sáu%' OR n.DiaChi LIKE '%Vo Thi Sau%')";
} elseif ($filterCoso === 'ktx') {
    $where[] = "(n.DiaChi LIKE '%Ký túc xá%' OR n.TenNhaTro LIKE '%KTX%' OR n.DiaChi LIKE '%Ky tuc xa%')";
} elseif ($filterCoso === 'dinh-my') {
    $where[] = "(n.DiaChi LIKE '%Định Mỹ%' OR n.DiaChi LIKE '%Thoại Sơn%' OR n.DiaChi LIKE '%Dinh My%')";
} elseif ($filterCoso === 'Binh Khanh') {
    $where[] = "(n.DiaChi LIKE '%Bình Khánh%' OR n.DiaChi LIKE '%Binh Khanh%')";
}

// 3. Lọc theo loại phòng
if ($filterLoaiphong === 'gac-duc') {
    $where[] = "(p.TenPhong LIKE '%gác%' OR p.TenPhong LIKE '%gac%' OR p.MoTa LIKE '%gác%' OR p.MoTa LIKE '%gac%')";
} elseif ($filterLoaiphong === 'khep-kin') {
    $where[] = "(p.TenPhong LIKE '%khép kín%' OR p.TenPhong LIKE '%khep kin%' OR p.MoTa LIKE '%khép kín%' OR p.MoTa LIKE '%khep kin%' OR p.MoTa LIKE '%wc riêng%')";
} elseif ($filterLoaiphong === 'ktx-tu-nhan') {
    $where[] = "(p.TenPhong LIKE '%ktx%' OR p.TenPhong LIKE '%ghép%' OR p.TenPhong LIKE '%ghep%' OR p.MoTa LIKE '%ký túc xá%')";
} elseif ($filterLoaiphong === 'studio') {
    $where[] = "(p.TenPhong LIKE '%studio%' OR p.TenPhong LIKE '%ban công%' OR p.MoTa LIKE '%studio%' OR p.MoTa LIKE '%ban cong%')";
}

// 4. Lọc theo ngân sách
if ($filterGia === 'duoi-1m2') {
    $where[] = "p.GiaThue < 1200000";
} elseif ($filterGia === '1m2-1m8') {
    $where[] = "p.GiaThue BETWEEN 1200000 AND 1800000";
} elseif ($filterGia === 'tren-1m8') {
    $where[] = "p.GiaThue > 1800000";
}

// 5. Lọc nhanh theo tags
if ($filterTag === 'gan-truong') {
    $where[] = "(n.DiaChi LIKE '%Ung Văn Khiêm%' OR n.DiaChi LIKE '%Võ Thị Sáu%' OR n.DiaChi LIKE '%Ung Van Khiem%')";
} elseif ($filterTag === 'tu-do') {
    $where[] = "(p.MoTa LIKE '%tự do%' OR p.MoTa LIKE '%tu do%' OR p.MoTa LIKE '%24/7%')";
} elseif ($filterTag === 'may-lanh') {
    $where[] = "(p.MoTa LIKE '%máy lạnh%' OR p.MoTa LIKE '%may lanh%' OR p.MoTa LIKE '%điều hòa%')";
} elseif ($filterTag === 'nu-rieng') {
    $where[] = "(p.MoTa LIKE '%nữ%' OR p.MoTa LIKE '%nu%')";
}

$sql = "
    SELECT p.MaPhong, p.TenPhong, p.GiaThue, p.DienTich, p.TrangThai, p.NgayDang, p.MoTa,
           n.TenNhaTro, n.DiaChi, n.ViDo, n.KinhDo,
           u.HoTen AS TenChuTro, u.SoDienThoai,
           (SELECT h.DuongDan FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.LaAnhChinh = 1 LIMIT 1) AS AnhChinh
    FROM PhongTro p
    JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
    JOIN NguoiDung u ON n.MaChuTro = u.MaNguoiDung
    WHERE " . implode(' AND ', $where) . "
    ORDER BY p.NgayDang DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}

$dsPhong = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$tongPhong = count($dsPhong);

// KPI tổng phòng đã duyệt trên toàn hệ thống
$kpiTong = (int)($conn->query("SELECT COUNT(*) AS c FROM PhongTro WHERE TrangThaiDuyet = 'DaDuyet' AND TrangThai = 'ConPhong'")->fetch_assoc()['c'] ?? 0);

include_once __DIR__ . '/dungchung/header.php';
?>

<!-- Leaflet.js: Thư viện Bản đồ thực tế (OpenStreetMap) hoàn toàn miễn phí & mượt mà -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
  /* Tùy chỉnh marker & popup bản đồ Leaflet */
  .leaflet-popup-content-wrapper {
    background-color: #F7F5F0;
    color: #1E1B16;
    border-radius: 8px;
    border: 1px solid #DDD6C9;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    font-family: 'Be Vietnam Pro', sans-serif;
  }
  .leaflet-popup-tip {
    background-color: #F7F5F0;
  }
  .custom-room-pin {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #00685F;
    color: #FFFFFF;
    font-weight: 700;
    font-size: 11px;
    padding: 3px 6px;
    border-radius: 9999px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    border: 2px solid #FFFFFF;
    white-space: nowrap;
    cursor: pointer;
    transition: transform 0.15s ease;
  }
  .custom-room-pin:hover {
    transform: scale(1.1);
    background-color: #004d46;
  }
  .custom-school-pin {
    background-color: #A84232;
    color: #FFFFFF;
    font-weight: 700;
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.35);
    border: 2px solid #FFFFFF;
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
  }
</style>

<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

  <!-- Header tiêu đề trang -->
  <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
      <h1 class="font-serif text-2xl sm:text-3xl font-bold text-text-main">Bản đồ &amp; Danh sách nhà trọ ĐH An Giang</h1>
      <p class="text-xs sm:text-sm text-text-sub mt-1 tnum">
        <?php if ($tongPhong > 0): ?>
          Hiển thị <strong><?= $tongPhong ?></strong> phòng trọ phù hợp (Tổng <?= $kpiTong ?> phòng còn trống trên hệ thống)
        <?php else: ?>
          Chưa tìm thấy phòng trọ phù hợp với tiêu chí lọc của bạn
        <?php endif; ?>
      </p>
    </div>

    <!-- Nút Bật / Tắt Bản đồ Thực tế -->
    <button type="button" onclick="toggleExploreMap()"
      class="px-4 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover flex items-center gap-2 self-start sm:self-auto shadow-sm transition-all duration-200 active:scale-[0.98]">
      <span class="material-symbols-outlined text-accent">map</span>
      <span id="btn-toggle-map-text">Bật / Tắt Bản đồ</span>
    </button>
  </div>

  <!-- ================= BỘ LỌC VÀ THANH TÌM KIẾM CŨ ĐẦY ĐỦ ================= -->
  <form action="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" method="GET" class="bg-bg-sub p-4 sm:p-5 rounded-card border border-border-warm shadow-sm space-y-3">
    <!-- Hàng tìm kiếm chính -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
      
      <!-- 1. Cơ sở học tập / Khu vực -->
      <div class="md:col-span-3">
        <label class="block text-xs font-semibold text-text-main mb-1">Cơ sở / Khu vực</label>
        <select name="coso" class="w-full bg-bg-main border border-border-warm rounded-card px-3 py-2.5 text-sm text-text-main focus:border-accent outline-none">
          <option value="">Tất cả khu vực quanh AGU</option>
          <option value="khu-a" <?= $filterCoso==='khu-a'?'selected':'' ?>>Khu A — 18 Ung Văn Khiêm (P. Đông Xuyên)</option>
          <option value="khu-b" <?= $filterCoso==='khu-b'?'selected':'' ?>>Khu B — 25 Võ Thị Sáu (P. Mỹ Xuyên)</option>
          <option value="ktx" <?= $filterCoso==='ktx'?'selected':'' ?>>Ký túc xá AGU (Đông Xuyên)</option>
          <option value="dinh-my" <?= $filterCoso==='dinh-my'?'selected':'' ?>>Cơ sở Định Mỹ (Thoại Sơn)</option>
          <option value="Dong Xuyen" <?= $filterCoso==='Dong Xuyen'?'selected':'' ?>>P. Đông Xuyên</option>
          <option value="My Xuyen" <?= $filterCoso==='My Xuyen'?'selected':'' ?>>P. Mỹ Xuyên</option>
          <option value="Binh Khanh" <?= $filterCoso==='Binh Khanh'?'selected':'' ?>>P. Bình Khánh</option>
        </select>
      </div>

      <!-- 2. Loại phòng -->
      <div class="md:col-span-3">
        <label class="block text-xs font-semibold text-text-main mb-1">Loại phòng</label>
        <select name="loaiphong" class="w-full bg-bg-main border border-border-warm rounded-card px-3 py-2.5 text-sm text-text-main focus:border-accent outline-none">
          <option value="">Tất cả loại hình phòng</option>
          <option value="gac-duc" <?= $filterLoaiphong==='gac-duc'?'selected':'' ?>>Phòng trọ có gác đúc kiên cố</option>
          <option value="khep-kin" <?= $filterLoaiphong==='khep-kin'?'selected':'' ?>>Phòng trọ khép kín (WC riêng)</option>
          <option value="ktx-tu-nhan" <?= $filterLoaiphong==='ktx-tu-nhan'?'selected':'' ?>>Ký túc xá tư nhân / Phòng ghép</option>
          <option value="studio" <?= $filterLoaiphong==='studio'?'selected':'' ?>>Phòng rộng có ban công / Studio</option>
        </select>
      </div>

      <!-- 3. Mức giá / Ngân sách -->
      <div class="md:col-span-2">
        <label class="block text-xs font-semibold text-text-main mb-1">Mức giá</label>
        <select name="ngansach" class="w-full bg-bg-main border border-border-warm rounded-card px-3 py-2.5 text-sm text-text-main focus:border-accent outline-none tnum">
          <option value="">Mọi mức giá</option>
          <option value="duoi-1m2" <?= $filterGia==='duoi-1m2'?'selected':'' ?>>Dưới 1.2 triệu</option>
          <option value="1m2-1m8" <?= $filterGia==='1m2-1m8'?'selected':'' ?>>1.2 – 1.8 triệu</option>
          <option value="tren-1m8" <?= $filterGia==='tren-1m8'?'selected':'' ?>>Trên 1.8 triệu</option>
        </select>
      </div>

      <!-- 4. Ô tìm kiếm từ khóa -->
      <div class="md:col-span-3">
        <label class="block text-xs font-semibold text-text-main mb-1">Tìm từ khóa</label>
        <input name="q" type="search" value="<?= htmlspecialchars($q) ?>"
               placeholder="Tên phòng, hẻm, đường..."
               class="w-full bg-bg-main border border-border-warm rounded-card px-3 py-2.5 text-sm text-text-main focus:border-accent outline-none">
      </div>

      <!-- 5. Nút submit -->
      <div class="md:col-span-1">
        <button type="submit" class="w-full h-[42px] bg-accent text-white font-semibold text-sm rounded-btn flex items-center justify-center gap-1.5 transition-all duration-200 active:scale-[0.98]">
          <span class="material-symbols-outlined text-[18px]">search</span>
          <span class="hidden md:inline">Tìm</span>
        </button>
      </div>
    </div>

    <!-- Dải lọc nhanh (Quick filters) -->
    <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-border-warm text-xs">
      <span class="text-text-sub font-semibold">Lọc nhanh:</span>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php?tag=gan-truong"
         class="px-2.5 py-1 rounded-card border <?= $filterTag==='gan-truong'?'bg-accent text-white border-accent':'bg-bg-main border-border-warm text-text-main card-hover' ?> transition-all duration-200">
        Cách cổng trường &lt; 500m
      </a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php?tag=tu-do"
         class="px-2.5 py-1 rounded-card border <?= $filterTag==='tu-do'?'bg-accent text-white border-accent':'bg-bg-main border-border-warm text-text-main card-hover' ?> transition-all duration-200">
        Giờ giấc tự do 24/7
      </a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php?tag=may-lanh"
         class="px-2.5 py-1 rounded-card border <?= $filterTag==='may-lanh'?'bg-accent text-white border-accent':'bg-bg-main border-border-warm text-text-main card-hover' ?> transition-all duration-200">
        Có máy lạnh
      </a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php?tag=nu-rieng"
         class="px-2.5 py-1 rounded-card border <?= $filterTag==='nu-rieng'?'bg-accent text-white border-accent':'bg-bg-main border-border-warm text-text-main card-hover' ?> transition-all duration-200">
        Khu trọ nữ riêng
      </a>

      <?php if ($q || $filterCoso || $filterLoaiphong || $filterGia || $filterTag): ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" class="px-3 py-1 rounded-card bg-status-red-bg text-status-red-text border border-status-red-border font-semibold flex items-center gap-1 ml-auto">
        <span class="material-symbols-outlined text-[14px]">close</span>Đặt lại bộ lọc
      </a>
      <?php else: ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php" class="ml-auto text-accent font-semibold underline">Tìm bạn ở ghép bằng AI →</a>
      <?php endif; ?>
    </div>
  </form>

  <!-- ================= BỐ CỤC 2 CỘT: DANH SÁCH + BẢN ĐỒ THỰC TẾ ================= -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

    <!-- CỘT TRÁI: DANH SÁCH PHÒNG TRỌ (lg:col-span-7 khi mở map, lg:col-span-12 khi đóng map) -->
    <div id="explore-list-col" class="lg:col-span-7 space-y-5">

      <?php if (empty($dsPhong)): ?>
      <!-- EMPTY STATE KHI KHÔNG CÓ PHÒNG PHÙ HỢP -->
      <div class="py-20 text-center space-y-4 bg-bg-main rounded-card border border-border-warm border-dashed">
        <span class="material-symbols-outlined text-5xl text-text-sub block mx-auto">search_off</span>
        <div>
          <p class="text-base font-bold text-text-main">Không tìm thấy phòng trọ phù hợp</p>
          <p class="text-xs text-text-sub mt-1">Hãy thử xóa bớt điều kiện lọc hoặc quay lại sau khi chủ trọ đăng tin mới.</p>
        </div>
        <div class="pt-2 flex items-center justify-center gap-3">
          <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php"
             class="px-4 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">
            Xem tất cả phòng
          </a>
          <?php if (daDangNhap() && coVaiTro('ChuTro')): ?>
          <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro/dangtin.php"
             class="inline-flex items-center gap-1.5 px-4 py-2 rounded-btn bg-accent text-white font-semibold text-xs">
            <span class="material-symbols-outlined text-[16px]">post_add</span>Đăng tin phòng trọ
          </a>
          <?php endif; ?>
        </div>
      </div>

      <?php else: ?>
      <!-- DANH SÁCH PHÒNG THỰC TỪ DATABASE -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php foreach ($dsPhong as $p):
          $gia    = number_format($p['GiaThue'], 0, ',', '.');
          $dien   = $p['DienTich'] ? $p['DienTich'] . ' m²' : '—';
          $diachi = $p['DiaChi'] ?? '—';
          $anh    = $p['AnhChinh'] ?: '';
          $sdt    = $p['SoDienThoai'] ?? '';
          $ngay   = $p['NgayDang'] ? date('d/m/Y', strtotime($p['NgayDang'])) : '—';
        ?>
        <article class="bg-bg-main rounded-card border border-border-warm card-hover overflow-hidden flex flex-col group cursor-pointer transition-all duration-200"
                 onclick="focusRoomOnMap(<?= $p['MaPhong'] ?>)">
          <!-- Ảnh đại diện -->
          <div class="relative overflow-hidden aspect-[16/10] bg-bg-sub">
            <?php if ($anh): ?>
            <img loading="lazy" src="<?= htmlspecialchars($anh) ?>" alt="<?= htmlspecialchars($p['TenPhong']) ?>"
                 onerror="this.style.display='none'"
                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center text-text-sub">
              <span class="material-symbols-outlined text-4xl">image_not_supported</span>
            </div>
            <?php endif; ?>
            <span class="absolute top-2.5 left-2.5 px-2.5 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold inline-flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>Còn phòng
            </span>
            <span class="absolute bottom-2.5 right-2.5 px-2 py-0.5 rounded bg-black/60 text-white text-[10px] font-semibold flex items-center gap-1 backdrop-blur-sm">
              <span class="material-symbols-outlined text-[13px]">location_on</span>Bấm định vị
            </span>
          </div>

          <!-- Chi tiết phòng -->
          <div class="p-4 flex flex-col gap-2 flex-1">
            <div class="flex items-baseline justify-between">
              <span class="text-base font-bold text-text-main tnum"><?= $gia ?> đ/tháng</span>
              <span class="text-xs text-text-sub tnum"><?= $dien ?></span>
            </div>

            <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $p['MaPhong'] ?>" class="group-hover:text-accent transition-colors" onclick="event.stopPropagation()">
              <h2 class="text-sm font-bold text-text-main line-clamp-1"><?= htmlspecialchars($p['TenPhong']) ?></h2>
            </a>

            <p class="text-xs text-text-sub line-clamp-2"><?= htmlspecialchars($diachi) ?></p>
            <p class="text-xs text-text-sub tnum">Chủ trọ: <?= htmlspecialchars($p['TenChuTro']) ?> · <?= $ngay ?></p>

            <div class="flex items-center gap-2 pt-2 border-t border-border-warm mt-auto" onclick="event.stopPropagation()">
              <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $p['MaPhong'] ?>"
                 class="w-1/2 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover text-center">
                Chi tiết
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
      <?php endif; ?>
    </div>

    <!-- CỘT PHẢI: BẢN ĐỒ THỰC TẾ (Leaflet.js + OpenStreetMap - TP. Long Xuyên) -->
    <aside id="explore-map-panel" class="lg:col-span-5 lg:sticky lg:top-20 space-y-3">
      <div class="bg-bg-main rounded-card border border-border-warm p-4 shadow-sm space-y-3">
        <!-- Map header toolbar -->
        <div class="flex items-center justify-between gap-2">
          <div>
            <h2 class="text-sm font-bold text-text-main flex items-center gap-1.5">
              <span class="material-symbols-outlined text-accent text-[18px]">explore</span>Bản đồ thực tế TP. Long Xuyên
            </h2>
            <p class="text-xs text-text-sub mt-0.5">Dữ liệu địa lý vệ tinh &amp; giao thông ĐH An Giang</p>
          </div>
          <span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent text-[10px] font-semibold inline-flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span>Trực quan
          </span>
        </div>

        <!-- Quick campus focus buttons -->
        <div class="flex items-center gap-2 text-xs">
          <span class="text-text-sub font-semibold shrink-0">Định vị nhanh:</span>
          <button type="button" onclick="flyToKhuA()" class="px-2.5 py-1 rounded-card bg-bg-sub border border-border-warm text-text-main hover:border-accent transition-all font-medium">
            Khu A (Ung Văn Khiêm)
          </button>
          <button type="button" onclick="flyToKhuB()" class="px-2.5 py-1 rounded-card bg-bg-sub border border-border-warm text-text-main hover:border-accent transition-all font-medium">
            Khu B (Võ Thị Sáu)
          </button>
        </div>

        <!-- Leaflet Map Container -->
        <div id="real-map" class="w-full h-[540px] rounded-card border border-border-warm overflow-hidden relative z-0"></div>

        <!-- Chú thích bản đồ -->
        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-text-sub pt-2 border-t border-border-warm">
          <span class="inline-flex items-center gap-1">
            <span class="w-3 h-3 rounded-full bg-[#A84232] inline-block"></span>Cơ sở ĐH An Giang
          </span>
          <span class="inline-flex items-center gap-1">
            <span class="w-3 h-3 rounded-full bg-accent inline-block"></span>Vị trí phòng trọ
          </span>
          <span class="text-text-sub ml-auto">Bấm vào ghim để xem giá</span>
        </div>
      </div>
    </aside>

  </div>
</div>

<!-- ================= SCRIPT KHỞI TẠO BẢN ĐỒ THỰC TẾ LEAFLET ================= -->
<script>
  // Dữ liệu tọa độ 2 cơ sở chính của Trường Đại học An Giang
  const AGU_KHU_A = [10.3715, 105.4326]; // 18 Ung Văn Khiêm, P. Đông Xuyên, TP. Long Xuyên
  const AGU_KHU_B = [10.3789, 105.4385]; // 25 Võ Thị Sáu, P. Mỹ Xuyên, TP. Long Xuyên
  const AGU_KTX   = [10.3698, 105.4312]; // Ký túc xá ĐH An Giang

  // Danh sách phòng trọ thực từ PHP truyền sang JS
  const danhSachPhong = <?= json_encode($dsPhong, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  let map;
  let roomMarkers = {};

  function initRealMap() {
    const mapContainer = document.getElementById('real-map');
    if (!mapContainer || map) return;

    // 1. Khởi tạo bản đồ trung tâm tại ĐH An Giang (Khu A)
    map = L.map('real-map', {
      center: AGU_KHU_A,
      zoom: 15,
      zoomControl: true,
      scrollWheelZoom: true
    });

    // 2. Tải bản đồ thực tế từ OpenStreetMap Tile Server
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a>'
    }).addTo(map);

    // 3. Ghim 2 cơ sở ĐH An Giang với Icon đặc biệt
    const iconKhuA = L.divIcon({
      className: 'custom-school-pin',
      html: '<span>🎓 ĐH An Giang (Khu A)</span>',
      iconSize: [150, 30],
      iconAnchor: [75, 30]
    });
    L.marker(AGU_KHU_A, { icon: iconKhuA }).addTo(map)
      .bindPopup(`
        <div class="p-1">
          <strong class="text-sm font-bold text-gray-900 block">Trường Đại học An Giang — Khu A</strong>
          <p class="text-xs text-gray-600 mt-1">Số 18 Ung Văn Khiêm, P. Đông Xuyên, TP. Long Xuyên</p>
        </div>
      `);

    const iconKhuB = L.divIcon({
      className: 'custom-school-pin',
      html: '<span>🎓 ĐH An Giang (Khu B)</span>',
      iconSize: [150, 30],
      iconAnchor: [75, 30]
    });
    L.marker(AGU_KHU_B, { icon: iconKhuB }).addTo(map)
      .bindPopup(`
        <div class="p-1">
          <strong class="text-sm font-bold text-gray-900 block">Trường Đại học An Giang — Khu B</strong>
          <p class="text-xs text-gray-600 mt-1">Số 25 Võ Thị Sáu, P. Mỹ Xuyên, TP. Long Xuyên</p>
        </div>
      `);

    // 4. Ghim các phòng trọ thực từ Database lên bản đồ
    danhSachPhong.forEach((p, idx) => {
      // Nếu phòng có tọa độ thực trong DB thì dùng, nếu chưa có tạo vị trí quanh khu vực Ung Văn Khiêm / Võ Thị Sáu
      let lat = parseFloat(p.ViDo);
      let lng = parseFloat(p.KinhDo);

      if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
        // Tọa độ giả định sát quanh hẻm Ung Văn Khiêm / Võ Thị Sáu dựa trên chỉ số index
        const offsetLat = (Math.sin(idx * 1.5) * 0.0035);
        const offsetLng = (Math.cos(idx * 1.5) * 0.0035);
        lat = (p.DiaChi && p.DiaChi.includes('Võ Thị Sáu')) ? (AGU_KHU_B[0] + offsetLat) : (AGU_KHU_A[0] + offsetLat);
        lng = (p.DiaChi && p.DiaChi.includes('Võ Thị Sáu')) ? (AGU_KHU_B[1] + offsetLng) : (AGU_KHU_A[1] + offsetLng);
      }

      const giaF = Number(p.GiaThue || 0).toLocaleString('vi-VN');
      const giaTrieu = (Number(p.GiaThue || 0) / 1000000).toFixed(1) + ' tr';

      const roomIcon = L.divIcon({
        className: 'custom-room-pin',
        html: `<span>🏠 ${giaTrieu}</span>`,
        iconSize: [70, 24],
        iconAnchor: [35, 24]
      });

      const marker = L.marker([lat, lng], { icon: roomIcon }).addTo(map);

      const popupHtml = `
        <div class="p-1 space-y-2" style="min-width: 190px;">
          ${p.AnhChinh ? `<img src="${p.AnhChinh}" alt="${p.TenPhong}" class="w-full h-24 object-cover rounded" onerror="this.style.display='none'">` : ''}
          <div>
            <strong class="text-xs font-bold text-gray-900 block leading-tight">${p.TenPhong}</strong>
            <p class="text-[11px] text-gray-600 mt-0.5">${p.DiaChi || ''}</p>
          </div>
          <div class="flex items-center justify-between pt-1 border-t border-gray-200">
            <span class="text-xs font-bold text-emerald-800">${giaF} đ/tháng</span>
            <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=${p.MaPhong}" class="text-xs font-bold text-emerald-700 underline">Xem phòng →</a>
          </div>
        </div>
      `;
      marker.bindPopup(popupHtml);

      roomMarkers[p.MaPhong] = marker;
    });

    window.exploreMap = map;
  }

  // Chức năng bấm định vị phòng trên bản đồ từ danh sách
  function focusRoomOnMap(maPhong) {
    const marker = roomMarkers[maPhong];
    const mapPanel = document.getElementById('explore-map-panel');

    // Nếu bản đồ đang ẩn thì mở lên
    if (mapPanel && mapPanel.classList.contains('hidden')) {
      toggleExploreMap();
    }

    if (marker && map) {
      const latlng = marker.getLatLng();
      map.flyTo(latlng, 17, { duration: 1.2 });
      setTimeout(() => {
        marker.openPopup();
      }, 1200);
    }
  }

  // Định vị nhanh tới Khu A
  function flyToKhuA() {
    if (map) map.flyTo(AGU_KHU_A, 16, { duration: 1 });
  }

  // Định vị nhanh tới Khu B
  function flyToKhuB() {
    if (map) map.flyTo(AGU_KHU_B, 16, { duration: 1 });
  }

  // Bật/tắt bản đồ
  function toggleExploreMap() {
    const panel = document.getElementById('explore-map-panel');
    const listCol = document.getElementById('explore-list-col');
    const btnText = document.getElementById('btn-toggle-map-text');

    if (panel && listCol) {
      const isHidden = panel.classList.toggle('hidden');
      if (isHidden) {
        listCol.classList.remove('lg:col-span-7');
        listCol.classList.add('lg:col-span-12');
        if (btnText) btnText.textContent = 'Mở bản đồ thực tế';
      } else {
        listCol.classList.remove('lg:col-span-12');
        listCol.classList.add('lg:col-span-7');
        if (btnText) btnText.textContent = 'Thu gọn bản đồ';
        if (map) {
          setTimeout(() => map.invalidateSize(), 200);
        } else {
          setTimeout(() => initRealMap(), 100);
        }
      }
    }
  }

  // Khởi tạo bản đồ khi tải trang xong
  document.addEventListener('DOMContentLoaded', () => {
    initRealMap();
  });
</script>

<?php include_once __DIR__ . '/dungchung/footer.php'; ?>\n