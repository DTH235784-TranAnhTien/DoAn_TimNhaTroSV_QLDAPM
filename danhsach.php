<?php
$tieuDeTrang = 'Bản đồ & Danh sách phòng trọ — CampusNest AGU';
$trangHienTai = 'explore';
require_once __DIR__ . '/cauhinh/database.php';
require_once __DIR__ . '/cauhinh/auth.php';

// Các bộ lọc từ URL
$filterCoso      = $_GET['coso']      ?? ($_GET['kv'] ?? '');
$filterLoaiphong = $_GET['loaiphong'] ?? '';
$filterGia       = $_GET['ngansach']  ?? ($_GET['gia'] ?? '');
$filterDientich  = $_GET['dientich']  ?? '';
$filterTag       = $_GET['tag']       ?? '';
$filterSapxep    = $_GET['sapxep']    ?? 'moi-nhat';
$q               = trim($_GET['q']    ?? '');

// Xây dựng câu truy vấn an toàn
$where  = ["p.TrangThaiDuyet = 'DaDuyet'", "p.TrangThai = 'ConPhong'"];
$params = [];
$types  = '';

// 1. Tìm kiếm nhiều từ khóa & mô tả yêu cầu tự nhiên
$tuKhoaList = [];
if ($q !== '') {
    $qLower = mb_strtolower($q, 'UTF-8');

    // Tự động nhận diện mức giá tối đa (vd: "dưới 2 triệu", "duoi 1.8tr", "tầm 1tr5", "800k")
    if (preg_match('/(?:dưới|duoi|tối đa|toi da|tầm|tam|<=|<)?\s*(\d+(?:[.,]\d+)?)\s*(?:triệu|trieu|tr)\b/u', $qLower, $mGia)) {
        $giaMax = (float)str_replace(',', '.', $mGia[1]) * 1000000;
        if ($giaMax > 0) {
            $where[] = "p.GiaThue <= ?";
            $params[] = $giaMax;
            $types .= 'd';
        }
    } elseif (preg_match('/(?:dưới|duoi|tầm|tam|<=|<)?\s*(\d+)\s*k\b/u', $qLower, $mGiaK)) {
        $giaMax = (float)$mGiaK[1] * 1000;
        if ($giaMax >= 300000) {
            $where[] = "p.GiaThue <= ?";
            $params[] = $giaMax;
            $types .= 'd';
        }
    }

    // Tự động nhận diện diện tích tối thiểu (vd: "trên 20m2", "từ 18 m2")
    if (preg_match('/(?:trên|tren|từ|tu|>=|>)?\s*(\d+(?:[.,]\d+)?)\s*m(?:2|²)\b/u', $qLower, $mDt)) {
        $dtMin = (float)str_replace(',', '.', $mDt[1]);
        if ($dtMin > 0) {
            $where[] = "p.DienTich >= ?";
            $params[] = $dtMin;
            $types .= 'd';
        }
    }

    // Làm sạch chuỗi khỏi cụm giá/diện tích và stop-words để tách từ khóa thực
    $qClean = preg_replace('/\b(?:\d+(?:[.,]\d+)?\s*(?:triệu|trieu|tr|k|m2|m²)|dưới|duoi|trên|tren|từ|tu|tối đa|toi da|tầm|tam|phòng|phong|trọ|tro|tìm|tim|muốn|muon|có|co|gần|gan|giá|gia|đồng|dong|ở|o|khu|vực|vuc|và|va|hay|với|voi|cho|cần|can)\b/u', ' ', $qLower);
    $rawTokens = preg_split('/[\s,;.\-\/]+/u', trim($qClean), -1, PREG_SPLIT_NO_EMPTY);
    foreach ($rawTokens as $tok) {
        if (mb_strlen($tok, 'UTF-8') >= 2) {
            $tuKhoaList[] = $tok;
        }
    }
    $tuKhoaList = array_values(array_unique($tuKhoaList));

    if (!empty($tuKhoaList)) {
        $orParts = [];
        foreach ($tuKhoaList as $kw) {
            $likeKw = "%$kw%";
            $orParts[] = "(p.TenPhong LIKE ? OR n.DiaChi LIKE ? OR n.TenNhaTro LIKE ? OR p.MoTa LIKE ? OR EXISTS (SELECT 1 FROM PhongTro_TienIch pt JOIN TienIch ti ON pt.MaTienIch = ti.MaTienIch WHERE pt.MaPhong = p.MaPhong AND ti.TenTienIch LIKE ?))";
            $params = array_merge($params, [$likeKw, $likeKw, $likeKw, $likeKw, $likeKw]);
            $types .= 'sssss';
        }
        $where[] = '(' . implode(' OR ', $orParts) . ')';
    }
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

// 4b. Lọc theo diện tích (Thành viên 4)
if ($filterDientich === 'duoi-15') {
    $where[] = "p.DienTich > 0 AND p.DienTich < 15";
} elseif ($filterDientich === '15-25') {
    $where[] = "p.DienTich BETWEEN 15 AND 25";
} elseif ($filterDientich === 'tren-25') {
    $where[] = "p.DienTich > 25";
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

// Xử lý sắp xếp kết quả
$orderBy = "p.NgayDang DESC";
if ($filterSapxep === 'gia-tang') {
    $orderBy = "p.GiaThue ASC";
} elseif ($filterSapxep === 'gia-giam') {
    $orderBy = "p.GiaThue DESC";
}

$sql = "
    SELECT p.MaPhong, p.TenPhong, p.GiaThue, p.DienTich, p.TrangThai, p.NgayDang, p.MoTa,
           n.TenNhaTro, n.DiaChi, n.ViDo, n.KinhDo,
           u.HoTen AS TenChuTro, u.SoDienThoai,
           (SELECT h.DuongDan FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.LaAnhChinh = 1 LIMIT 1) AS AnhChinh,
           (SELECT GROUP_CONCAT(ti.TenTienIch SEPARATOR ', ') FROM PhongTro_TienIch pt JOIN TienIch ti ON pt.MaTienIch = ti.MaTienIch WHERE pt.MaPhong = p.MaPhong) AS DsTienIch
    FROM PhongTro p
    JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
    JOIN NguoiDung u ON n.MaChuTro = u.MaNguoiDung
    WHERE " . implode(' AND ', $where) . "
    ORDER BY {$orderBy}
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

// Xếp hạng & lọc thông minh khi nhập nhiều từ khóa (ưu tiên phòng khớp đủ tất cả từ khóa)
if (!empty($tuKhoaList) && !empty($dsPhong)) {
    $totalKw = count($tuKhoaList);
    $exactMatches = [];
    foreach ($dsPhong as &$pItem) {
        $hay = mb_strtolower(
            ($pItem['TenPhong'] ?? '') . ' ' .
            ($pItem['TenNhaTro'] ?? '') . ' ' .
            ($pItem['DiaChi'] ?? '') . ' ' .
            ($pItem['MoTa'] ?? '') . ' ' .
            ($pItem['DsTienIch'] ?? ''),
            'UTF-8'
        );
        $matched = 0;
        foreach ($tuKhoaList as $kw) {
            if (str_contains($hay, $kw)) $matched++;
        }
        $pItem['_kw_match'] = $matched;
        if ($matched === $totalKw) {
            $exactMatches[] = $pItem;
        }
    }
    unset($pItem);

    if (!empty($exactMatches) && $totalKw >= 2) {
        $dsPhong = $exactMatches;
    } else {
        usort($dsPhong, function ($a, $b) {
            return ($b['_kw_match'] ?? 0) <=> ($a['_kw_match'] ?? 0);
        });
    }
}

$tongPhong = count($dsPhong);

// Phân trang danh sách phòng (Thành viên 4)
$soPhongMoiTrang = 6;
$tongTrang = max(1, (int)ceil($tongPhong / $soPhongMoiTrang));
$trangSo = max(1, min($tongTrang, (int)($_GET['trang'] ?? 1)));
$viTriBatDau = ($trangSo - 1) * $soPhongMoiTrang;
$dsPhongHienThi = array_slice($dsPhong, $viTriBatDau, $soPhongMoiTrang);

// KPI tổng phòng đã duyệt trên toàn hệ thống
$resKpi = $conn->query("SELECT COUNT(*) AS c FROM PhongTro WHERE TrangThaiDuyet = 'DaDuyet' AND TrangThai = 'ConPhong'");
$kpiTong = $resKpi ? (int)($resKpi->fetch_assoc()['c'] ?? 0) : 0;

// Danh sách phòng đã lưu của sinh viên (nếu đã đăng nhập)
$phongDaLuuIds = [];
if (daDangNhap()) {
    $ndHienTai = nguoiDungHienTai();
    $mandHienTai = (int)($ndHienTai['mannd'] ?? 0);
    if ($mandHienTai > 0) {
        $stmtFav = $conn->prepare("SELECT MaPhong FROM DanhSachYeuThich WHERE MaSinhVien = ?");
        $stmtFav->bind_param('i', $mandHienTai);
        $stmtFav->execute();
        $resFav = $stmtFav->get_result();
        while ($rowFav = $resFav->fetch_assoc()) {
            $phongDaLuuIds[] = (int)$rowFav['MaPhong'];
        }
        $stmtFav->close();
    }
}

include_once __DIR__ . '/dungchung/header.php';
?>

<!-- Leaflet.js: Thư viện Bản đồ thực tế (OpenStreetMap) hoàn toàn miễn phí & mượt mà -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
  /* Đảm bảo tile Leaflet không bị Tailwind CSS làm méo hoặc ẩn */
  #real-map .leaflet-pane img,
  #real-map .leaflet-tile {
    max-width: none !important;
    max-height: none !important;
  }
  /* Tùy chỉnh marker & popup bản đồ Leaflet */
  .leaflet-popup-content-wrapper {
    background-color: #FFFFFF;
    color: #0F172A;
    border-radius: 12px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
    font-family: 'Be Vietnam Pro', sans-serif;
  }
  .leaflet-popup-tip {
    background-color: #FFFFFF;
  }
  .custom-room-pin {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: #2563EB;
    color: #FFFFFF;
    font-weight: 700;
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.35);
    white-space: nowrap;
    cursor: pointer;
    transition: transform 0.15s ease, background-color 0.15s ease;
  }
  .custom-room-pin::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 50%;
    transform: translateX(-50%);
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 6px solid #2563EB;
    transition: border-top-color 0.15s ease;
  }
  .custom-room-pin:hover {
    transform: scale(1.1);
    background-color: #1D4ED8;
    z-index: 999 !important;
  }
  .custom-room-pin:hover::after {
    border-top-color: #1D4ED8;
  }
  .custom-school-pin {
    background-color: #0F172A;
    color: #FFFFFF;
    font-weight: 700;
    font-size: 11px;
    padding: 4px 9px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.3);
    border: 2px solid #FFFFFF;
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
  }
</style>

<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-5">

  <!-- ================= BỐ CỤC 2 CỘT: TRÁI DANH SÁCH (7) + PHẢI BẢN ĐỒ NHỎ GỌN (5) ================= -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

    <!-- CỘT TRÁI: TIÊU ĐỀ, THANH TÌM KIẾM, NÚT LỌC PILL, DANH SÁCH PHÒNG (lg:col-span-7) -->
    <div id="explore-list-col" class="lg:col-span-7 space-y-4">

      <!-- Hàng tiêu đề + Toggle Ở ghép (giống hệt ảnh mẫu) -->
      <div class="flex items-start justify-between gap-3">
        <div>
          <h1 class="text-xl sm:text-2xl font-bold text-text-main tracking-tight">Phòng trọ quanh ĐH An Giang</h1>
          <p class="text-xs text-text-sub mt-0.5 tnum">
            <?= $tongPhong ?> kết quả · <?= $kpiTong ?> phòng trống quanh AGU
            <?php if ($tongTrang > 1): ?> · Trang <?= $trangSo ?>/<?= $tongTrang ?><?php endif; ?>
          </p>
        </div>

        <div class="flex items-center gap-3 shrink-0 pt-0.5">
          <!-- Toggle Ở ghép (giống toggle Roommates màu xanh) -->
          <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php" class="flex items-center gap-2 text-xs font-semibold text-text-main hover:text-accent transition-colors" title="Chuyển sang tìm bạn ở ghép">
            <span>Ở ghép</span>
            <span class="w-10 h-5 bg-accent rounded-full p-0.5 flex items-center justify-end transition-all shadow-inner">
              <span class="w-4 h-4 bg-white rounded-full shadow-sm"></span>
            </span>
          </a>
          <button type="button" onclick="toggleExploreMap()" class="lg:hidden px-2.5 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main text-xs font-semibold flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px] text-accent">map</span>
            <span id="btn-toggle-map-text">Bản đồ</span>
          </button>
        </div>
      </div>

      <!-- Thanh tìm kiếm nhiều từ khóa / mô tả yêu cầu đặt phía dưới tiêu đề -->
      <form action="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" method="GET" class="flex items-center gap-2 bg-bg-main border border-border-warm rounded-btn pl-3.5 pr-1.5 py-1.5 shadow-sm focus-within:border-accent focus-within:ring-2 focus-within:ring-accent/15 transition-all">
        <?php if ($filterGia !== ''): ?><input type="hidden" name="ngansach" value="<?= htmlspecialchars($filterGia) ?>"><?php endif; ?>
        <?php if ($filterLoaiphong !== ''): ?><input type="hidden" name="loaiphong" value="<?= htmlspecialchars($filterLoaiphong) ?>"><?php endif; ?>
        <?php if ($filterDientich !== ''): ?><input type="hidden" name="dientich" value="<?= htmlspecialchars($filterDientich) ?>"><?php endif; ?>
        <?php if ($filterCoso !== ''): ?><input type="hidden" name="coso" value="<?= htmlspecialchars($filterCoso) ?>"><?php endif; ?>
        <?php if ($filterTag !== ''): ?><input type="hidden" name="tag" value="<?= htmlspecialchars($filterTag) ?>"><?php endif; ?>
        <?php if ($filterSapxep !== 'moi-nhat'): ?><input type="hidden" name="sapxep" value="<?= htmlspecialchars($filterSapxep) ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= htmlspecialchars($q) ?>"
               placeholder="Nhập từ khóa hoặc mô tả (VD: gác lửng máy lạnh ung văn khiêm dưới 2 triệu)..."
               class="w-full bg-transparent text-xs sm:text-sm text-text-main placeholder-text-sub focus:outline-none">
        <button type="submit"
                class="px-4 py-2 rounded-btn bg-accent text-white text-xs font-semibold shrink-0 hover:bg-blue-700 transition-colors">
          Tìm phòng
        </button>
      </form>

      <!-- Hàng nút lọc Pill + View Switcher + Sắp xếp (giống ảnh mẫu) -->
      <form action="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" method="GET" class="flex flex-wrap items-center justify-between gap-2 pb-1">
        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
        <?php if ($filterTag !== ''): ?><input type="hidden" name="tag" value="<?= htmlspecialchars($filterTag) ?>"><?php endif; ?>

        <div class="flex flex-wrap items-center gap-1.5">
          <!-- Nút lọc Mức giá -->
          <label class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-btn border <?= $filterGia ? 'border-accent bg-status-green-bg text-accent' : 'border-border-warm bg-bg-main text-text-main hover:border-slate-300' ?> text-xs font-medium cursor-pointer transition-colors shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
            <span class="material-symbols-outlined text-[15px] text-text-sub">sell</span>
            <select name="ngansach" onchange="this.form.submit()" class="bg-transparent focus:outline-none cursor-pointer font-medium pr-1">
              <option value="">Mức giá</option>
              <option value="duoi-1m2" <?= $filterGia==='duoi-1m2'?'selected':'' ?>>&lt; 1.2 triệu</option>
              <option value="1m2-1m8" <?= $filterGia==='1m2-1m8'?'selected':'' ?>>1.2 – 1.8tr</option>
              <option value="tren-1m8" <?= $filterGia==='tren-1m8'?'selected':'' ?>>&gt; 1.8 triệu</option>
            </select>
          </label>

          <!-- Nút lọc Loại phòng -->
          <label class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-btn border <?= $filterLoaiphong ? 'border-accent bg-status-green-bg text-accent' : 'border-border-warm bg-bg-main text-text-main hover:border-slate-300' ?> text-xs font-medium cursor-pointer transition-colors shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
            <span class="material-symbols-outlined text-[15px] text-text-sub">apartment</span>
            <select name="loaiphong" onchange="this.form.submit()" class="bg-transparent focus:outline-none cursor-pointer font-medium pr-1">
              <option value="">Loại phòng</option>
              <option value="gac-duc" <?= $filterLoaiphong==='gac-duc'?'selected':'' ?>>Gác đúc</option>
              <option value="khep-kin" <?= $filterLoaiphong==='khep-kin'?'selected':'' ?>>Khép kín</option>
              <option value="ktx-tu-nhan" <?= $filterLoaiphong==='ktx-tu-nhan'?'selected':'' ?>>KTX / Ghép</option>
              <option value="studio" <?= $filterLoaiphong==='studio'?'selected':'' ?>>Studio</option>
            </select>
          </label>

          <!-- Nút lọc Diện tích -->
          <label class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-btn border <?= $filterDientich ? 'border-accent bg-status-green-bg text-accent' : 'border-border-warm bg-bg-main text-text-main hover:border-slate-300' ?> text-xs font-medium cursor-pointer transition-colors shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
            <span class="material-symbols-outlined text-[15px] text-text-sub">square_foot</span>
            <select name="dientich" onchange="this.form.submit()" class="bg-transparent focus:outline-none cursor-pointer font-medium pr-1">
              <option value="">Diện tích</option>
              <option value="duoi-15" <?= $filterDientich==='duoi-15'?'selected':'' ?>>&lt; 15 m²</option>
              <option value="15-25" <?= $filterDientich==='15-25'?'selected':'' ?>>15 – 25 m²</option>
              <option value="tren-25" <?= $filterDientich==='tren-25'?'selected':'' ?>>&gt; 25 m²</option>
            </select>
          </label>

          <!-- Nút lọc Khu vực -->
          <label class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-btn border <?= $filterCoso ? 'border-accent bg-status-green-bg text-accent' : 'border-border-warm bg-bg-main text-text-main hover:border-slate-300' ?> text-xs font-medium cursor-pointer transition-colors shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
            <span class="material-symbols-outlined text-[15px] text-text-sub">tune</span>
            <select name="coso" onchange="this.form.submit()" class="bg-transparent focus:outline-none cursor-pointer font-medium pr-1">
              <option value="">Khu vực</option>
              <option value="khu-a" <?= $filterCoso==='khu-a'?'selected':'' ?>>Khu A (Đông Xuyên)</option>
              <option value="khu-b" <?= $filterCoso==='khu-b'?'selected':'' ?>>Khu B (Mỹ Xuyên)</option>
              <option value="ktx" <?= $filterCoso==='ktx'?'selected':'' ?>>Ký túc xá AGU</option>
              <option value="Binh Khanh" <?= $filterCoso==='Binh Khanh'?'selected':'' ?>>P. Bình Khánh</option>
            </select>
          </label>

          <?php if ($q || $filterCoso || $filterLoaiphong || $filterGia || $filterDientich || $filterTag): ?>
          <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-btn bg-status-red-bg text-status-red-text border border-status-red-border text-xs font-semibold">
            <span class="material-symbols-outlined text-[14px]">close</span>Đặt lại
          </a>
          <?php endif; ?>
        </div>

        <!-- View Mode & Sort By (bên phải giống ảnh mẫu) -->
        <div class="flex items-center gap-2.5 ml-auto text-xs">
          <div class="hidden sm:flex items-center gap-1 border-r border-border-warm pr-2.5">
            <button type="button" id="btn-view-grid" onclick="setListViewMode('grid')" title="Xem dạng lưới" class="p-1 rounded text-text-sub hover:text-text-main transition-colors">
              <span class="material-symbols-outlined text-[18px]">grid_view</span>
            </button>
            <button type="button" id="btn-view-list" onclick="setListViewMode('list')" title="Xem dạng danh sách" class="p-1 rounded text-accent transition-colors">
              <span class="material-symbols-outlined text-[18px]">view_list</span>
            </button>
          </div>
          <div class="flex items-center gap-1">
            <span class="text-text-sub">Sắp xếp:</span>
            <select name="sapxep" onchange="this.form.submit()" class="bg-transparent text-accent font-semibold focus:outline-none cursor-pointer">
              <option value="moi-nhat" <?= $filterSapxep === 'moi-nhat' ? 'selected' : '' ?>>Mới nhất</option>
              <option value="gia-tang" <?= $filterSapxep === 'gia-tang' ? 'selected' : '' ?>>Giá tăng</option>
              <option value="gia-giam" <?= $filterSapxep === 'gia-giam' ? 'selected' : '' ?>>Giá giảm</option>
            </select>
          </div>
        </div>
      </form>

      <?php if (empty($dsPhong)): ?>
      <!-- EMPTY STATE KHI KHÔNG CÓ PHÒNG PHÙ HỢP -->
      <div class="py-20 text-center space-y-4 bg-bg-main rounded-2xl border border-border-warm border-dashed">
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
      <!-- DANH SÁCH PHÒNG THỰC TỪ DATABASE (Card ngang hiện đại giống ảnh mẫu) -->
      <div id="room-list-grid" class="grid grid-cols-1 gap-3.5">
        <?php foreach ($dsPhongHienThi as $p):
          $gia    = number_format($p['GiaThue'], 0, ',', '.');
          $dien   = $p['DienTich'] ? $p['DienTich'] . ' m²' : '—';
          $diachi = $p['DiaChi'] ?? '—';
          $anh    = $p['AnhChinh'] ?: '';
          $sdt    = $p['SoDienThoai'] ?? '';
          $ngay   = $p['NgayDang'] ? date('d/m/Y', strtotime($p['NgayDang'])) : '—';
          $mota   = trim(strip_tags($p['MoTa'] ?? ''));
          $daLuu  = in_array((int)$p['MaPhong'], $phongDaLuuIds, true);
        ?>
        <article class="bg-bg-main rounded-2xl border border-border-warm card-hover p-3.5 flex flex-col sm:flex-row gap-4 group cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md"
                 onclick="focusRoomOnMap(<?= $p['MaPhong'] ?>)">
          <!-- Ảnh đại diện bên trái -->
          <div class="relative overflow-hidden w-full sm:w-48 h-44 sm:h-40 rounded-xl bg-bg-sub shrink-0">
            <?php if ($anh): ?>
            <img loading="lazy" src="<?= htmlspecialchars($anh) ?>" alt="<?= htmlspecialchars($p['TenPhong']) ?>"
                 onerror="this.style.display='none'"
                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.04]">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center text-text-sub">
              <span class="material-symbols-outlined text-4xl">image_not_supported</span>
            </div>
            <?php endif; ?>
            <!-- Overlay trạng thái & diện tích phía dưới ảnh -->
            <div class="absolute bottom-2 left-2 right-2 px-2.5 py-1 rounded-lg bg-slate-900/65 backdrop-blur-sm text-white text-[11px] flex items-center justify-between">
              <span class="font-semibold flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Còn phòng
              </span>
              <span class="tnum opacity-90"><?= $dien ?></span>
            </div>
          </div>

          <!-- Thông tin chi tiết bên phải -->
          <div class="flex-1 min-w-0 flex flex-col justify-between gap-2">
            <div>
              <!-- Dòng tiêu đề + Nút Yêu thích -->
              <div class="flex items-start justify-between gap-2">
                <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $p['MaPhong'] ?>" class="min-w-0 flex-1" onclick="event.stopPropagation()">
                  <h2 class="text-base font-bold text-text-main group-hover:text-accent transition-colors line-clamp-1"><?= htmlspecialchars($p['TenPhong']) ?></h2>
                </a>
                <button type="button"
                        onclick="event.stopPropagation(); toggleYeuThich(<?= (int)$p['MaPhong'] ?>, this)"
                        title="<?= $daLuu ? 'Bỏ lưu phòng' : 'Lưu vào phòng yêu thích' ?>"
                        class="w-8 h-8 rounded-full hover:bg-slate-100 flex items-center justify-center transition-colors shrink-0 -mt-1 -mr-1">
                  <span class="material-symbols-outlined text-[20px] <?= $daLuu ? 'text-red-500 fill-1' : 'text-text-sub' ?>">
                    <?= $daLuu ? 'favorite' : 'favorite_border' ?>
                  </span>
                </button>
              </div>

              <!-- Địa chỉ -->
              <p class="text-xs text-text-sub flex items-center gap-1 mt-0.5 line-clamp-1">
                <span class="material-symbols-outlined text-[14px] shrink-0">location_on</span>
                <span class="truncate"><?= htmlspecialchars($diachi) ?></span>
              </p>

              <!-- Mô tả / Tiện ích -->
              <p class="text-xs text-text-sub mt-1.5 line-clamp-2 leading-relaxed">
                <?= $mota ? htmlspecialchars($mota) : 'Chủ trọ: ' . htmlspecialchars($p['TenChuTro']) . ' · Đăng ngày ' . $ngay ?>
              </p>

              <!-- Nhãn pill mềm (giống ảnh mẫu) -->
              <div class="flex flex-wrap items-center gap-1.5 mt-2">
                <span class="px-2 py-0.5 rounded-md bg-status-green-bg text-status-green-text text-[11px] font-semibold">
                  Đã kiểm duyệt
                </span>
                <?php if ((float)$p['GiaThue'] <= 1300000): ?>
                <span class="px-2 py-0.5 rounded-md bg-status-amber-bg text-status-amber-text text-[11px] font-semibold">
                  Giá tốt sinh viên
                </span>
                <?php endif; ?>
                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-text-sub text-[11px] font-medium">
                  Chủ: <?= htmlspecialchars($p['TenChuTro']) ?>
                </span>
              </div>
            </div>

            <!-- Hàng dưới: Đánh giá sao + Giá tiền nổi bật -->
            <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100 mt-1" onclick="event.stopPropagation()">
              <div class="flex items-center gap-2">
                <div class="flex items-center gap-0.5 text-accent">
                  <span class="text-xs font-bold text-text-main mr-1 tnum">5.0</span>
                  <span class="material-symbols-outlined text-[14px]">star</span>
                  <span class="material-symbols-outlined text-[14px]">star</span>
                  <span class="material-symbols-outlined text-[14px]">star</span>
                  <span class="material-symbols-outlined text-[14px]">star</span>
                  <span class="material-symbols-outlined text-[14px]">star</span>
                </div>
                <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $p['MaPhong'] ?>"
                   class="text-xs font-semibold text-accent hover:underline ml-1">
                  Chi tiết →
                </a>
              </div>
              <div class="text-right shrink-0">
                <span class="text-lg font-bold text-text-main tnum"><?= $gia ?>đ</span>
                <span class="text-xs text-text-sub">/tháng</span>
              </div>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>

      <?php if ($tongTrang > 1):
        $queryBase = $_GET;
        $buildPageUrl = function (int $p) use ($queryBase): string {
            $queryBase['trang'] = $p;
            return '/DoAn_TimNhaTroSV_QLDAPM/danhsach.php?' . http_build_query($queryBase);
        };
      ?>
      <!-- PHÂN TRANG (Thành viên 4) -->
      <nav aria-label="Phân trang danh sách phòng" class="flex flex-wrap items-center justify-center gap-1.5 pt-2">
        <?php if ($trangSo > 1): ?>
        <a href="<?= htmlspecialchars($buildPageUrl($trangSo - 1)) ?>"
           class="px-3 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main text-xs font-semibold card-hover inline-flex items-center gap-1">
          <span class="material-symbols-outlined text-[15px]">chevron_left</span>Trước
        </a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $tongTrang; $i++): ?>
        <a href="<?= htmlspecialchars($buildPageUrl($i)) ?>"
           class="w-8 h-8 rounded-btn text-xs font-semibold inline-flex items-center justify-center transition-all tnum <?= $i === $trangSo ? 'bg-accent text-white shadow-sm' : 'border border-border-warm bg-bg-main text-text-main card-hover' ?>">
          <?= $i ?>
        </a>
        <?php endfor; ?>

        <?php if ($trangSo < $tongTrang): ?>
        <a href="<?= htmlspecialchars($buildPageUrl($trangSo + 1)) ?>"
           class="px-3 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main text-xs font-semibold card-hover inline-flex items-center gap-1">
          Sau<span class="material-symbols-outlined text-[15px]">chevron_right</span>
        </a>
        <?php endif; ?>
      </nav>
      <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- CỘT PHẢI: BẢN ĐỒ THỰC TẾ GỌN GÀNG CHỪA KHOẢNG THỞ (lg:col-span-5) -->
    <aside id="explore-map-panel" class="lg:col-span-5 lg:sticky lg:top-20">
      <div class="relative w-full h-[440px] lg:h-[520px] rounded-2xl border border-border-warm overflow-hidden shadow-sm bg-slate-100" style="min-height: 440px;">
        <!-- Thanh nổi phía trên bản đồ -->
        <div class="absolute top-3 left-1/2 -translate-x-1/2 z-[400] bg-white/95 backdrop-blur-sm border border-slate-200 rounded-btn px-3 py-1.5 shadow-md flex items-center gap-2 text-xs whitespace-nowrap">
          <span class="text-text-main font-medium">Định vị nhanh:</span>
          <button type="button" onclick="flyToAGU()" class="px-2.5 py-0.5 rounded-lg bg-status-green-bg text-accent font-semibold hover:bg-blue-100 transition-colors">Trường ĐH An Giang</button>
        </div>

        <!-- Cụm nút công cụ nổi góc dưới trái -->
        <div class="absolute bottom-4 left-3 z-[400] flex flex-col gap-1.5">
          <button type="button" onclick="flyToAGU()" title="Tới Trường Đại Học An Giang" class="w-9 h-9 rounded-xl bg-white border border-slate-200 shadow-md flex items-center justify-center text-text-sub hover:text-accent transition-colors">
            <span class="material-symbols-outlined text-[18px]">school</span>
          </button>
          <button type="button" onclick="if(map){map.setView(AGU_CAMPUS, 15);}" title="Đưa bản đồ về mặc định" class="w-9 h-9 rounded-xl bg-white border border-slate-200 shadow-md flex items-center justify-center text-text-sub hover:text-accent transition-colors">
            <span class="material-symbols-outlined text-[18px]">my_location</span>
          </button>
        </div>

        <!-- Leaflet Map Container -->
        <div id="real-map" class="w-full h-full z-0" style="width: 100%; height: 100%; min-height: 440px;"></div>
      </div>
    </aside>

  </div>
</div>

<!-- ================= SCRIPT KHỞI TẠO BẢN ĐỒ THỰC TẾ LEAFLET ================= -->
<script>
  // Tọa độ Trường Đại Học An Giang
  const AGU_CAMPUS = [10.3713741, 105.4324531]; // 18 Ung Văn Khiêm, P. Đông Xuyên, TP. Long Xuyên

  // Danh sách phòng trọ thực từ PHP truyền sang JS
  const danhSachPhong = <?= json_encode($dsPhong, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

  let map;
  let roomMarkers = {};

  function initRealMap() {
    const mapContainer = document.getElementById('real-map');
    if (!mapContainer || map || typeof L === 'undefined') return;

    // 1. Khởi tạo bản đồ trung tâm tại Trường Đại Học An Giang
    map = L.map('real-map', {
      center: AGU_CAMPUS,
      zoom: 14,
      zoomControl: true,
      scrollWheelZoom: true
    });

    // 2. Tải bản đồ Google Maps (Tiếng Việt) kèm fallback OpenStreetMap
    const tileLayer = L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&hl=vi&x={x}&y={y}&z={z}', {
      maxZoom: 20,
      subdomains: ['0', '1', '2', '3'],
      attribution: '&copy; Google Maps'
    }).addTo(map);
    tileLayer.on('tileerror', function () {
      if (!window._osmFallbackAdded) {
        window._osmFallbackAdded = true;
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap'
        }).addTo(map);
      }
    });

    // 3. Ghim Trường Đại Học An Giang
    const iconAGU = L.divIcon({
      className: 'custom-school-pin',
      html: '<span>🎓 Trường Đại Học An Giang</span>',
      iconSize: [185, 30],
      iconAnchor: [92, 30]
    });
    L.marker(AGU_CAMPUS, { icon: iconAGU }).addTo(map)
      .bindPopup(`
        <div class="p-1">
          <strong class="text-sm font-bold text-gray-900 block">Trường Đại Học An Giang</strong>
          <p class="text-xs text-gray-600 mt-1">Số 18 Ung Văn Khiêm, P. Đông Xuyên, TP. Long Xuyên</p>
        </div>
      `);

    // 4. Ghim các phòng trọ thực từ Database lên bản đồ
    danhSachPhong.forEach((p, idx) => {
      let lat = parseFloat(p.ViDo);
      let lng = parseFloat(p.KinhDo);

      if (!lat || !lng || isNaN(lat) || isNaN(lng)) {
        const offsetLat = (Math.sin(idx * 1.5) * 0.0042);
        const offsetLng = (Math.cos(idx * 1.5) * 0.0042);
        lat = AGU_CAMPUS[0] + offsetLat;
        lng = AGU_CAMPUS[1] + offsetLng;
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
          <div class="flex items-center justify-between gap-2 pt-1 border-t border-gray-200">
            <span class="text-xs font-bold text-accent">${giaF} đ/tháng</span>
            <div class="flex items-center gap-2">
              <a href="https://www.google.com/maps/search/?api=1&query=${lat},${lng}" target="_blank" rel="noopener" class="text-[11px] font-semibold text-text-sub hover:text-accent underline">Google Maps</a>
              <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=${p.MaPhong}" class="text-xs font-bold text-accent underline">Xem phòng →</a>
            </div>
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

  // Định vị nhanh tới Trường Đại Học An Giang
  function flyToAGU() {
    if (map) map.flyTo(AGU_CAMPUS, 16, { duration: 1 });
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

  // Xử lý lưu / bỏ lưu phòng yêu thích qua AJAX (Thành viên 4)
  async function toggleYeuThich(maPhong, btn) {
    try {
      const formData = new FormData();
      formData.append('ma_phong', maPhong);

      const res = await fetch('/DoAn_TimNhaTroSV_QLDAPM/sinhvien/yeuthich_toggle.php', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      if (data.require_login) {
        if (typeof showToast === 'function') {
          showToast(data.message, 'error');
        } else {
          alert(data.message);
        }
        setTimeout(() => {
          window.location.href = '/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php';
        }, 1200);
        return;
      }

      if (data.success) {
        const icon = btn.querySelector('.material-symbols-outlined');
        if (data.action === 'added') {
          icon.textContent = 'favorite';
          icon.classList.remove('text-text-sub');
          icon.classList.add('text-red-500');
          btn.title = 'Bỏ lưu phòng';
          if (typeof showToast === 'function') showToast(data.message, 'success');
        } else {
          icon.textContent = 'favorite_border';
          icon.classList.remove('text-red-500');
          icon.classList.add('text-text-sub');
          btn.title = 'Lưu vào phòng yêu thích';
          if (typeof showToast === 'function') showToast(data.message, 'info');
        }
      } else {
        if (typeof showToast === 'function') {
          showToast(data.message || 'Đã có lỗi xảy ra', 'error');
        }
      }
    } catch (err) {
      console.error('Lỗi toggle yêu thích:', err);
      if (typeof showToast === 'function') showToast('Không thể kết nối máy chủ.', 'error');
    }
  }

  // Chuyển đổi chế độ xem Danh sách / Lưới (giống ảnh mẫu)
  function setListViewMode(mode) {
    const grid = document.getElementById('room-list-grid');
    const btnGrid = document.getElementById('btn-view-grid');
    const btnList = document.getElementById('btn-view-list');
    if (!grid) return;
    if (mode === 'grid') {
      grid.className = 'grid grid-cols-1 sm:grid-cols-2 gap-3.5';
      grid.querySelectorAll('article').forEach(card => {
        card.classList.remove('sm:flex-row');
        const imgBox = card.firstElementChild;
        if (imgBox) {
          imgBox.classList.remove('sm:w-48', 'sm:h-40');
        }
      });
      if (btnGrid) btnGrid.classList.replace('text-text-sub', 'text-accent');
      if (btnList) btnList.classList.replace('text-accent', 'text-text-sub');
    } else {
      grid.className = 'grid grid-cols-1 gap-3.5';
      grid.querySelectorAll('article').forEach(card => {
        card.classList.add('sm:flex-row');
        const imgBox = card.firstElementChild;
        if (imgBox) {
          imgBox.classList.add('sm:w-48', 'sm:h-40');
        }
      });
      if (btnList) btnList.classList.replace('text-text-sub', 'text-accent');
      if (btnGrid) btnGrid.classList.replace('text-accent', 'text-text-sub');
    }
  }

  // Khởi tạo bản đồ khi tải trang xong + cập nhật kích thước sau khi Tailwind render
  document.addEventListener('DOMContentLoaded', () => {
    initRealMap();
    setTimeout(() => { if (map) map.invalidateSize(); }, 350);
  });
  window.addEventListener('load', () => {
    if (!map) initRealMap();
    if (map) map.invalidateSize();
  });
</script>

<?php include_once __DIR__ . '/dungchung/footer.php'; ?>\n