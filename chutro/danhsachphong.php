<?php
/**
 * chutro/danhsachphong.php — Quản lý danh sách phòng trọ của Chủ trọ
 * Phân hệ Chủ trọ — CampusNest AGU
 */
$tieuDeTrang = 'Quản lý phòng trọ — CampusNest AGU';
$trangHienTai = 'landlord';

require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

// Xác định đường dẫn gốc linh hoạt
$base_url = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
if ($base_url === '' || $base_url === '/') {
    $base_url = strpos($_SERVER['REQUEST_URI'], '/QLNhaTro') !== false ? '/QLNhaTro' : '/DoAn_TimNhaTroSV_QLDAPM';
}

if (!daDangNhap()) {
    header("Location: {$base_url}/taikhoan/dangnhap.php?tb=" . urlencode('Vui lòng đăng nhập để quản lý phòng trọ.'));
    exit();
}

$nd = nguoiDungHienTai();
$mand = (int)($nd['mannd'] ?? 0);
$vaitro = $nd['vaitro'] ?? '';

if (!in_array($vaitro, ['ChuTro', 'QuanTriVien'])) {
    header("Location: {$base_url}/index.php");
    exit();
}

// 1. Lấy danh sách dãy nhà trọ của chủ trọ này để làm bộ lọc
if ($vaitro === 'QuanTriVien') {
    $stmtNT = $conn->query("SELECT MaNhaTro, TenNhaTro, DiaChi FROM NhaTro ORDER BY TenNhaTro ASC");
    $dsNhaTro = $stmtNT ? $stmtNT->fetch_all(MYSQLI_ASSOC) : [];
} else {
    $stmtNT = $conn->prepare("SELECT MaNhaTro, TenNhaTro, DiaChi FROM NhaTro WHERE MaChuTro = ? ORDER BY TenNhaTro ASC");
    $stmtNT->bind_param('i', $mand);
    $stmtNT->execute();
    $dsNhaTro = $stmtNT->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtNT->close();
}

// 2. Nhận các tham số lọc từ URL (GET)
$locNhaTro    = (int)($_GET['nha_tro'] ?? 0);
$locTrangThai = trim($_GET['trang_thai'] ?? '');
$locDuyet     = trim($_GET['duyet'] ?? '');
$tuKhoa       = trim($_GET['q'] ?? '');

// 3. Xây dựng câu truy vấn danh sách phòng trọ
$sql = "
    SELECT 
        p.*, 
        n.TenNhaTro, 
        n.DiaChi,
        (SELECT DuongDan FROM HinhAnhPhongTro WHERE MaPhong = p.MaPhong ORDER BY LaAnhChinh DESC, MaHinh ASC LIMIT 1) AS AnhDaiDien,
        (SELECT COUNT(*) FROM LichHen WHERE MaPhong = p.MaPhong AND TrangThai = 'ChoDuyet') AS SoLichHenCho
    FROM PhongTro p
    JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
    WHERE 1=1
";

$params = [];
$types  = '';

if ($vaitro !== 'QuanTriVien') {
    $sql .= " AND n.MaChuTro = ?";
    $params[] = $mand;
    $types .= 'i';
}

if ($locNhaTro > 0) {
    $sql .= " AND p.MaNhaTro = ?";
    $params[] = $locNhaTro;
    $types .= 'i';
}

if ($locTrangThai !== '' && in_array($locTrangThai, ['ConPhong', 'DaThue', 'DangBaoTri'])) {
    $sql .= " AND p.TrangThai = ?";
    $params[] = $locTrangThai;
    $types .= 's';
}

if ($locDuyet !== '' && in_array($locDuyet, ['ChoDuyet', 'DaDuyet', 'TuChoi'])) {
    $sql .= " AND p.TrangThaiDuyet = ?";
    $params[] = $locDuyet;
    $types .= 's';
}

if ($tuKhoa !== '') {
    $sql .= " AND (p.TenPhong LIKE ? OR n.TenNhaTro LIKE ?)";
    $like = '%' . $tuKhoa . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

$sql .= " ORDER BY p.NgayDang DESC";

$stmtP = $conn->prepare($sql);
if (!empty($params)) {
    $stmtP->bind_param($types, ...$params);
}
$stmtP->execute();
$dsPhong = $stmtP->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtP->close();

// 4. Thống kê tổng số phòng
$tongPhong = count($dsPhong);
$soConPhong = 0;
$soDaThue   = 0;
$soBaoTri   = 0;
$soChoDuyet = 0;

foreach ($dsPhong as $p) {
    if ($p['TrangThai'] === 'ConPhong')   $soConPhong++;
    if ($p['TrangThai'] === 'DaThue')     $soDaThue++;
    if ($p['TrangThai'] === 'DangBaoTri') $soBaoTri++;
    if ($p['TrangThaiDuyet'] === 'ChoDuyet') $soChoDuyet++;
}

include_once __DIR__ . '/../dungchung/header.php';
?>

<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

  <!-- Breadcrumb điều hướng -->
  <nav class="flex items-center gap-2 text-xs text-text-sub">
    <a href="<?= $base_url ?>/index.php" class="hover:text-accent transition-colors">Trang chủ</a>
    <span>/</span>
    <a href="<?= $base_url ?>/chutro.php" class="hover:text-accent transition-colors">Chủ trọ</a>
    <span>/</span>
    <span class="text-text-main font-semibold">Quản lý danh sách phòng trọ</span>
  </nav>

  <!-- Flash Notification -->
  <?php if (function_exists('hienThongBao')) hienThongBao(); ?>

  <!-- Tiêu đề & Nút hành động chính -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-bg-main p-6 rounded-card border border-border-warm shadow-sm">
    <div>
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-card bg-accent/10 text-accent flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-2xl">apartment</span>
        </div>
        <div>
          <h1 class="text-2xl font-bold text-text-main">Quản lý phòng trọ</h1>
          <p class="text-xs text-text-sub mt-0.5">Theo dõi tình trạng phòng, giá thuê, duyệt tin và đổi trạng thái cho thuê nhanh chóng.</p>
        </div>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <a href="<?= $base_url ?>/chutro.php" class="px-4 py-2.5 rounded-btn border border-border-warm bg-bg-sub text-text-main font-semibold text-xs hover:bg-border-warm/50 transition-all flex items-center gap-1.5">
        <span class="material-symbols-outlined text-base">dashboard</span>
        <span>Bảng điều khiển</span>
      </a>
      <a href="<?= $base_url ?>/chutro/dangtin.php" class="px-5 py-2.5 rounded-btn bg-accent text-white font-semibold text-xs hover:opacity-90 transition-all flex items-center gap-1.5 shadow-sm">
        <span class="material-symbols-outlined text-base">add_circle</span>
        <span>Đăng tin phòng mới</span>
      </a>
    </div>
  </div>

  <!-- Thống kê nhanh theo thẻ -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-bg-main p-4 rounded-card border border-border-warm">
      <p class="text-xs text-text-sub font-medium">Tổng số phòng</p>
      <p class="text-2xl font-bold text-text-main mt-1"><?= str_pad($tongPhong, 2, '0', STR_PAD_LEFT) ?></p>
      <span class="text-[11px] text-text-sub">Toàn bộ dãy trọ của bạn</span>
    </div>
    <div class="bg-bg-main p-4 rounded-card border border-border-warm">
      <p class="text-xs text-status-green-text font-medium flex items-center gap-1">
        <span class="w-2 h-2 rounded-full bg-accent inline-block"></span> Còn phòng trống
      </p>
      <p class="text-2xl font-bold text-accent mt-1"><?= str_pad($soConPhong, 2, '0', STR_PAD_LEFT) ?></p>
      <span class="text-[11px] text-text-sub">Sẵn sàng đón khách mới</span>
    </div>
    <div class="bg-bg-main p-4 rounded-card border border-border-warm">
      <p class="text-xs text-status-red-text font-medium flex items-center gap-1">
        <span class="w-2 h-2 rounded-full bg-[#7D2D24] inline-block"></span> Đã cho thuê
      </p>
      <p class="text-2xl font-bold text-[#7D2D24] mt-1"><?= str_pad($soDaThue, 2, '0', STR_PAD_LEFT) ?></p>
      <span class="text-[11px] text-text-sub">Đang có sinh viên ở</span>
    </div>
    <div class="bg-bg-main p-4 rounded-card border border-border-warm">
      <p class="text-xs text-status-amber-text font-medium flex items-center gap-1">
        <span class="w-2 h-2 rounded-full bg-[#785713] inline-block"></span> Chờ Admin duyệt
      </p>
      <p class="text-2xl font-bold text-[#785713] mt-1"><?= str_pad($soChoDuyet, 2, '0', STR_PAD_LEFT) ?></p>
      <span class="text-[11px] text-text-sub">Bài đăng mới gửi lên</span>
    </div>
  </div>

  <!-- Thanh lọc và Tìm kiếm -->
  <div class="bg-bg-main p-4 rounded-card border border-border-warm">
    <form method="GET" action="<?= $base_url ?>/chutro/danhsachphong.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
      
      <!-- Ô tìm kiếm tên phòng -->
      <div>
        <label class="block text-[11px] font-semibold text-text-sub mb-1 uppercase tracking-wider">Tìm kiếm phòng</label>
        <input type="text" name="q" value="<?= htmlspecialchars($tuKhoa) ?>" placeholder="Tên phòng hoặc tên trọ..." 
               class="w-full text-xs px-3 py-2 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent">
      </div>

      <!-- Lọc theo Nhà trọ -->
      <div>
        <label class="block text-[11px] font-semibold text-text-sub mb-1 uppercase tracking-wider">Dãy nhà trọ</label>
        <select name="nha_tro" class="w-full text-xs px-3 py-2 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent">
          <option value="0">-- Tất cả nhà trọ --</option>
          <?php foreach ($dsNhaTro as $nt): ?>
            <option value="<?= $nt['MaNhaTro'] ?>" <?= $locNhaTro === (int)$nt['MaNhaTro'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($nt['TenNhaTro']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Lọc theo Trạng thái phòng -->
      <div>
        <label class="block text-[11px] font-semibold text-text-sub mb-1 uppercase tracking-wider">Trạng thái phòng</label>
        <select name="trang_thai" class="w-full text-xs px-3 py-2 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent">
          <option value="">-- Tất cả trạng thái --</option>
          <option value="ConPhong" <?= $locTrangThai === 'ConPhong' ? 'selected' : '' ?>>Còn phòng trống</option>
          <option value="DaThue" <?= $locTrangThai === 'DaThue' ? 'selected' : '' ?>>Đã cho thuê</option>
          <option value="DangBaoTri" <?= $locTrangThai === 'DangBaoTri' ? 'selected' : '' ?>>Đang bảo trì</option>
        </select>
      </div>

      <!-- Lọc theo Kiểm duyệt -->
      <div>
        <label class="block text-[11px] font-semibold text-text-sub mb-1 uppercase tracking-wider">Kiểm duyệt Admin</label>
        <select name="duyet" class="w-full text-xs px-3 py-2 rounded-btn border border-border-warm bg-bg-sub/30 focus:outline-accent">
          <option value="">-- Tất cả kiểm duyệt --</option>
          <option value="DaDuyet" <?= $locDuyet === 'DaDuyet' ? 'selected' : '' ?>>Đã duyệt hiển thị</option>
          <option value="ChoDuyet" <?= $locDuyet === 'ChoDuyet' ? 'selected' : '' ?>>Đang chờ duyệt</option>
          <option value="TuChoi" <?= $locDuyet === 'TuChoi' ? 'selected' : '' ?>>Bị từ chối</option>
        </select>
      </div>

      <!-- Nút bấm Lọc -->
      <div class="flex items-end gap-2">
        <button type="submit" class="flex-1 bg-accent text-white font-semibold text-xs py-2 rounded-btn hover:opacity-90 transition-all flex items-center justify-center gap-1">
          <span class="material-symbols-outlined text-sm">filter_alt</span>
          <span>Lọc</span>
        </button>
        <?php if ($locNhaTro || $locTrangThai || $locDuyet || $tuKhoa): ?>
          <a href="<?= $base_url ?>/chutro/danhsachphong.php" class="px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-text-sub hover:text-text-main text-xs font-semibold flex items-center justify-center" title="Xóa bộ lọc">
            <span class="material-symbols-outlined text-sm">refresh</span>
          </a>
        <?php endif; ?>
      </div>

    </form>
  </div>

  <!-- Danh sách phòng trọ -->
  <?php if (empty($dsPhong)): ?>
    <div class="py-16 text-center space-y-3 bg-bg-main rounded-card border border-border-warm border-dashed">
      <span class="material-symbols-outlined text-5xl text-text-sub/50 block mx-auto">home_work</span>
      <h3 class="text-base font-bold text-text-main">Không tìm thấy phòng trọ nào phù hợp</h3>
      <p class="text-xs text-text-sub max-w-sm mx-auto">Bạn có thể thử xóa bớt điều kiện lọc hoặc bắt đầu đăng thêm phòng trọ mới lên hệ thống.</p>
      <div class="pt-2">
        <a href="<?= $base_url ?>/chutro/dangtin.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-btn bg-accent text-white text-xs font-semibold hover:opacity-90">
          <span class="material-symbols-outlined text-sm">add</span>Đăng tin phòng mới ngay
        </a>
      </div>
    </div>
  <?php else: ?>
    <div class="bg-bg-main rounded-card border border-border-warm overflow-hidden shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-bg-sub text-[11px] font-bold uppercase tracking-wider text-text-sub border-b border-border-warm">
            <tr>
              <th class="py-3 px-4">Thông tin phòng</th>
              <th class="py-3 px-4">Giá thuê & Chi phí</th>
              <th class="py-3 px-4">Diện tích</th>
              <th class="py-3 px-4">Trạng thái phòng</th>
              <th class="py-3 px-4">Kiểm duyệt</th>
              <th class="py-3 px-4 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border-warm">
            <?php foreach ($dsPhong as $p): 
              $giaF   = number_format($p['GiaThue'], 0, ',', '.');
              $dienF  = number_format($p['TienDien'], 0, ',', '.');
              $nuocF  = number_format($p['TienNuoc'], 0, ',', '.');
              $wifiF  = number_format($p['TienWifi'], 0, ',', '.');
              $dt     = (float)$p['DienTich'];
              $tt     = $p['TrangThai'];
              $duyet  = $p['TrangThaiDuyet'];
              $anhHienThi = $p['AnhDaiDien'] ? $base_url . '/' . ltrim($p['AnhDaiDien'], '/') : 'https://placehold.co/120x80/e2e8f0/64748b?text=Anh+Phong';
            ?>
            <tr class="hover:bg-bg-sub/30 transition-colors">
              
              <!-- 1. Cột thông tin phòng -->
              <td class="py-3.5 px-4">
                <div class="flex items-start gap-3">
                  <img src="<?= htmlspecialchars($anhHienThi) ?>" 
                       alt="<?= htmlspecialchars($p['TenPhong']) ?>" 
                       class="w-16 h-14 object-cover rounded-md border border-border-warm shrink-0 bg-bg-sub">
                  <div class="space-y-0.5">
                    <a href="<?= $base_url ?>/chitiet.php?id=<?= $p['MaPhong'] ?>" class="font-bold text-text-main hover:text-accent transition-colors block text-sm">
                      <?= htmlspecialchars($p['TenPhong']) ?>
                    </a>
                    <p class="text-xs text-text-sub flex items-center gap-1">
                      <span class="material-symbols-outlined text-xs text-accent">storefront</span>
                      <?= htmlspecialchars($p['TenNhaTro']) ?>
                    </p>
                    <p class="text-[11px] text-text-sub/80 truncate max-w-xs" title="<?= htmlspecialchars($p['DiaChi']) ?>">
                      <?= htmlspecialchars($p['DiaChi']) ?>
                    </p>
                  </div>
                </div>
              </td>

              <!-- 2. Cột giá thuê & chi phí -->
              <td class="py-3.5 px-4">
                <p class="font-bold text-accent text-sm"><?= $giaF ?> đ<span class="text-[11px] text-text-sub font-normal">/tháng</span></p>
                <div class="text-[11px] text-text-sub space-y-0.5 mt-0.5">
                  <p>⚡ Điện: <?= $dienF ?> đ</p>
                  <p>💧 Nước: <?= $nuocF ?> đ</p>
                </div>
              </td>

              <!-- 3. Cột diện tích -->
              <td class="py-3.5 px-4 text-xs font-semibold text-text-main">
                <?= $dt ?> m²
              </td>

              <!-- 4. Cột trạng thái phòng & đổi nhanh -->
              <td class="py-3.5 px-4">
                <div class="space-y-1.5">
                  <?php if ($tt === 'ConPhong'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-status-green-bg text-status-green-text text-xs font-semibold">
                      <span class="w-1.5 h-1.5 rounded-full bg-accent"></span> Còn phòng
                    </span>
                  <?php elseif ($tt === 'DaThue'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-status-red-bg text-status-red-text text-xs font-semibold">
                      <span class="w-1.5 h-1.5 rounded-full bg-[#7D2D24]"></span> Đã cho thuê
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text text-xs font-semibold">
                      <span class="w-1.5 h-1.5 rounded-full bg-[#785713]"></span> Đang bảo trì
                    </span>
                  <?php endif; ?>

                  <!-- Form đổi nhanh trạng thái -->
                  <form method="POST" action="<?= $base_url ?>/chutro/doitrangthai.php" class="flex items-center">
                    <input type="hidden" name="ma_phong" value="<?= $p['MaPhong'] ?>">
                    <select name="trang_thai" onchange="this.form.submit()" class="text-[11px] py-1 px-1.5 rounded border border-border-warm bg-bg-main text-text-sub focus:outline-accent cursor-pointer">
                      <option value="ConPhong" <?= $tt === 'ConPhong' ? 'selected' : '' ?>>Đổi: Còn phòng</option>
                      <option value="DaThue" <?= $tt === 'DaThue' ? 'selected' : '' ?>>Đổi: Đã thuê</option>
                      <option value="DangBaoTri" <?= $tt === 'DangBaoTri' ? 'selected' : '' ?>>Đổi: Bảo trì</option>
                    </select>
                  </form>
                </div>
              </td>

              <!-- 5. Cột kiểm duyệt Admin -->
              <td class="py-3.5 px-4">
                <?php if ($duyet === 'DaDuyet'): ?>
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-status-green-bg text-status-green-text text-[11px] font-semibold" title="Bài đăng đã hiển thị trên trang chủ">
                    <span class="material-symbols-outlined text-xs">verified</span> Đã duyệt
                  </span>
                <?php elseif ($duyet === 'ChoDuyet'): ?>
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-status-amber-bg text-status-amber-text text-[11px] font-semibold" title="Đang đợi Admin xem xét duyệt">
                    <span class="material-symbols-outlined text-xs">hourglass_top</span> Chờ duyệt
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text text-[11px] font-semibold" title="Bài đăng không được duyệt">
                    <span class="material-symbols-outlined text-xs">cancel</span> Bị từ chối
                  </span>
                <?php endif; ?>
              </td>

              <!-- 6. Cột Thao tác -->
              <td class="py-3.5 px-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- Xem bài viết -->
                  <a href="<?= $base_url ?>/chitiet.php?id=<?= $p['MaPhong'] ?>" target="_blank" 
                     class="p-1.5 rounded-btn border border-border-warm bg-bg-main text-text-sub hover:text-accent hover:border-accent transition-all" title="Xem phòng trên web">
                    <span class="material-symbols-outlined text-base">visibility</span>
                  </a>

                  <!-- Sửa thông tin phòng -->
                  <a href="<?= $base_url ?>/chutro/suaphong.php?id=<?= $p['MaPhong'] ?>" 
                     class="p-1.5 rounded-btn border border-border-warm bg-bg-main text-text-sub hover:text-accent hover:border-accent transition-all" title="Sửa thông tin phòng">
                    <span class="material-symbols-outlined text-base">edit</span>
                  </a>

                  <!-- Xóa phòng (Kèm xác nhận) -->
                  <button type="button" 
                          onclick="xacNhanXoaPhong(<?= $p['MaPhong'] ?>, '<?= htmlspecialchars(addslashes($p['TenPhong'])) ?>')"
                          class="p-1.5 rounded-btn border border-status-red-border bg-status-red-bg text-status-red-text hover:bg-red-200 transition-all" title="Xóa phòng này">
                    <span class="material-symbols-outlined text-base">delete</span>
                  </button>
                </div>
              </td>

            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

</div>

<!-- Modal xác nhận xóa phòng an toàn -->
<div id="modalXoaPhong" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm hidden items-center justify-center p-4">
  <div class="bg-bg-main rounded-card border border-border-warm p-6 max-w-sm w-full space-y-4 shadow-xl">
    <div class="w-12 h-12 rounded-full bg-status-red-bg text-status-red-text flex items-center justify-center mx-auto">
      <span class="material-symbols-outlined text-2xl">warning</span>
    </div>
    <div class="text-center space-y-1">
      <h3 class="text-base font-bold text-text-main">Xác nhận xóa phòng trọ?</h3>
      <p class="text-xs text-text-sub">
        Bạn có chắc muốn xóa phòng <strong id="tenPhongXoa" class="text-text-main"></strong>? Toàn bộ ảnh và lịch hẹn liên quan sẽ bị xóa vĩnh viễn.
      </p>
    </div>
    <form id="formXoaPhong" method="POST" action="<?= $base_url ?>/chutro/xoaphong.php" class="grid grid-cols-2 gap-3 pt-2">
      <input type="hidden" name="ma_phong" id="inputMaPhongXoa" value="">
      <button type="button" onclick="dongModalXoa()" class="px-4 py-2 rounded-btn border border-border-warm bg-bg-sub text-text-main text-xs font-semibold hover:bg-border-warm/50">
        Hủy bỏ
      </button>
      <button type="submit" class="px-4 py-2 rounded-btn bg-[#7D2D24] text-white text-xs font-semibold hover:opacity-90">
        Xác nhận xóa
      </button>
    </form>
  </div>
</div>

<script>
function xacNhanXoaPhong(maPhong, tenPhong) {
  document.getElementById('inputMaPhongXoa').value = maPhong;
  document.getElementById('tenPhongXoa').innerText = '"' + tenPhong + '"';
  const modal = document.getElementById('modalXoaPhong');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function dongModalXoa() {
  const modal = document.getElementById('modalXoaPhong');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}

// Đóng modal khi bấm ra ngoài nền đen
window.onclick = function(event) {
  const modal = document.getElementById('modalXoaPhong');
  if (event.target === modal) {
    dongModalXoa();
  }
}
</script>

<?php include_once __DIR__ . '/../dungchung/footer.php'; ?>
