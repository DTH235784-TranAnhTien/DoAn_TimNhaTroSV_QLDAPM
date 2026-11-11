<?php
$tieuDeTrang = 'Quản lý Tiện ích & Nhãn — CampusNest AGU';
$trangHienTai = 'admin';
require_once __DIR__ . '/../cauhinh/auth.php';
require_once __DIR__ . '/../cauhinh/database.php';

/** @var mysqli|null $conn */
if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Không thể kết nối cơ sở dữ liệu.');
}

yeuCauVaiTro('QuanTriVien');

$nd = nguoiDungHienTai();
$nd_hoten = $nd['hoten'] ?? 'Admin';
$nd_anh   = $nd['anhdaidien'] ?? '/DoAn_TimNhaTroSV_QLDAPM/assets/images/default-avatar.png';
$csrfToken = taoCsrfToken();
$redirectUrl = '/DoAn_TimNhaTroSV_QLDAPM/quantri/quanly_tienich.php';

function chuanHoaAnhAdmin(?string $path, string $macDinh = '/DoAn_TimNhaTroSV_QLDAPM/assets/images/default-avatar.png'): string
{
    if (empty($path)) {
        return $macDinh;
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
        return $path;
    }
    return '/DoAn_TimNhaTroSV_QLDAPM/' . ltrim($path, './');
}

// ================= XỬ LÝ FORM POST (TIỆN ÍCH & NHÃN DANH MỤC) =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kiemTraCsrfToken($_POST['csrf_token'] ?? '')) {
        datThongBao('danger', 'Phiên làm việc không hợp lệ (lỗi CSRF). Vui lòng thử lại.');
        header("Location: $redirectUrl");
        exit();
    }

    $hanhDong = $_POST['hanh_dong'] ?? '';

    // 1. Thêm hoặc cập nhật Tiện ích (TienIch)
    if ($hanhDong === 'luu_tien_ich') {
        $maTienIch  = (int)($_POST['ma_tien_ich'] ?? 0);
        $tenTienIch = trim($_POST['ten_tien_ich'] ?? '');
        $icon       = trim($_POST['icon'] ?? '');
        if ($icon === '') {
            $icon = 'bi-check-circle';
        }

        if ($tenTienIch === '') {
            datThongBao('danger', 'Vui lòng nhập tên tiện ích.');
            header("Location: $redirectUrl");
            exit();
        }

        $chk = $conn->prepare("SELECT MaTienIch FROM TienIch WHERE TenTienIch = ? AND MaTienIch != ?");
        $chk->bind_param('si', $tenTienIch, $maTienIch);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            datThongBao('danger', "Tiện ích '$tenTienIch' đã tồn tại trong hệ thống.");
            header("Location: $redirectUrl");
            exit();
        }

        if ($maTienIch > 0) {
            $stmt = $conn->prepare("UPDATE TienIch SET TenTienIch = ?, Icon = ? WHERE MaTienIch = ?");
            $stmt->bind_param('ssi', $tenTienIch, $icon, $maTienIch);
            $stmt->execute();
            datThongBao('success', "Đã cập nhật tiện ích #$maTienIch ($tenTienIch) thành công!");
        } else {
            $stmt = $conn->prepare("INSERT INTO TienIch (TenTienIch, Icon) VALUES (?, ?)");
            $stmt->bind_param('ss', $tenTienIch, $icon);
            $stmt->execute();
            datThongBao('success', "Đã thêm tiện ích mới '$tenTienIch' thành công!");
        }

        header("Location: $redirectUrl");
        exit();
    }

    // 2. Xóa Tiện ích (TienIch)
    if ($hanhDong === 'xoa_tien_ich') {
        $maTienIch = (int)($_POST['ma_tien_ich'] ?? 0);
        if ($maTienIch > 0) {
            $stmt = $conn->prepare("DELETE FROM TienIch WHERE MaTienIch = ?");
            $stmt->bind_param('i', $maTienIch);
            $stmt->execute();
            datThongBao('info', "Đã xóa tiện ích #$maTienIch khỏi hệ thống.");
        }
        header("Location: $redirectUrl");
        exit();
    }

    // 3. Thêm hoặc cập nhật Nhãn danh mục (NhanDanhMuc)
    if ($hanhDong === 'luu_nhan') {
        $maNhan  = (int)($_POST['ma_nhan'] ?? 0);
        $tenNhan = trim($_POST['ten_nhan'] ?? '');
        $tuKhoa  = trim($_POST['tu_khoa_nhan_dien'] ?? '');

        if ($tenNhan === '') {
            datThongBao('danger', 'Vui lòng nhập tên nhãn danh mục.');
            header("Location: $redirectUrl");
            exit();
        }

        if (mb_strlen($tenNhan, 'UTF-8') > 50) {
            datThongBao('danger', 'Tên nhãn danh mục tối đa 50 ký tự.');
            header("Location: $redirectUrl");
            exit();
        }

        $chk = $conn->prepare("SELECT MaNhan FROM NhanDanhMuc WHERE TenNhan = ? AND MaNhan != ?");
        $chk->bind_param('si', $tenNhan, $maNhan);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            datThongBao('danger', "Nhãn danh mục '$tenNhan' đã tồn tại trong hệ thống.");
            header("Location: $redirectUrl");
            exit();
        }

        if ($maNhan > 0) {
            $stmt = $conn->prepare("UPDATE NhanDanhMuc SET TenNhan = ?, TuKhoaNhanDien = ? WHERE MaNhan = ?");
            $stmt->bind_param('ssi', $tenNhan, $tuKhoa, $maNhan);
            $stmt->execute();
            datThongBao('success', "Đã cập nhật nhãn #$maNhan ($tenNhan) thành công!");
        } else {
            $stmt = $conn->prepare("INSERT INTO NhanDanhMuc (TenNhan, TuKhoaNhanDien) VALUES (?, ?)");
            $stmt->bind_param('ss', $tenNhan, $tuKhoa);
            $stmt->execute();
            datThongBao('success', "Đã thêm nhãn danh mục '$tenNhan' thành công!");
        }

        header("Location: $redirectUrl");
        exit();
    }

    // 4. Xóa Nhãn danh mục (NhanDanhMuc)
    if ($hanhDong === 'xoa_nhan') {
        $maNhan = (int)($_POST['ma_nhan'] ?? 0);
        if ($maNhan > 0) {
            $stmt = $conn->prepare("DELETE FROM NhanDanhMuc WHERE MaNhan = ?");
            $stmt->bind_param('i', $maNhan);
            $stmt->execute();
            datThongBao('info', "Đã xóa nhãn danh mục #$maNhan khỏi hệ thống.");
        }
        header("Location: $redirectUrl");
        exit();
    }

    // [COMMIT_2_PHP_HANDLER]
}

// ================= TRUY VẤN DANH SÁCH TIỆN ÍCH & NHÃN DANH MỤC =================
$resTienIch = $conn->query("
    SELECT t.MaTienIch, t.TenTienIch, t.Icon,
           COUNT(pt.MaPhong) AS SoPhongSuDung
    FROM TienIch t
    LEFT JOIN PhongTro_TienIch pt ON t.MaTienIch = pt.MaTienIch
    GROUP BY t.MaTienIch, t.TenTienIch, t.Icon
    ORDER BY t.MaTienIch DESC
");
$dsTienIch = $resTienIch ? $resTienIch->fetch_all(MYSQLI_ASSOC) : [];

$resNhan = $conn->query("
    SELECT n.MaNhan, n.TenNhan, n.TuKhoaNhanDien,
           COUNT(pn.MaPhong) AS SoPhongGanNhan
    FROM NhanDanhMuc n
    LEFT JOIN PhongTro_Nhan pn ON n.MaNhan = pn.MaNhan
    GROUP BY n.MaNhan, n.TenNhan, n.TuKhoaNhanDien
    ORDER BY n.MaNhan DESC
");
$dsNhan = $resNhan ? $resNhan->fetch_all(MYSQLI_ASSOC) : [];

$flashMsg = layThongBao();
include_once __DIR__ . '/../dungchung/header.php';
?>

<!-- ===== ADMIN HEADER ===== -->
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
          <img loading="lazy" onerror="this.onerror=null; this.style.display='none';" alt="Admin" class="w-full h-full object-cover" src="<?= htmlspecialchars(chuanHoaAnhAdmin($nd_anh)) ?>">
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
        <a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=dashboard" class="admin-menu-item flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200 hover:bg-bg-main">
          <span class="material-symbols-outlined">dashboard</span><span>Tổng quan</span>
        </a>
        <a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=moderation" class="admin-menu-item flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200 hover:bg-bg-main">
          <span class="material-symbols-outlined">fact_check</span><span>Kiểm duyệt tin</span>
        </a>
        <a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=fraud" class="admin-menu-item flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200 hover:bg-bg-main">
          <span class="material-symbols-outlined">report</span><span>Tố giác gian lận</span>
        </a>
        <a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=users" class="admin-menu-item flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200 hover:bg-bg-main">
          <span class="material-symbols-outlined">manage_accounts</span><span>Quản lý tài khoản</span>
        </a>
        <a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/quanly_tienich.php" class="admin-menu-item active flex items-center gap-2 shrink-0 px-3 py-2 rounded-btn text-xs font-semibold transition-all duration-200">
          <span class="material-symbols-outlined">category</span><span>Tiện ích & Nhãn</span>
        </a>
      </nav>
    </aside>

    <!-- CONTENT PHẢI -->
    <main class="min-w-0 space-y-6">
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

      <!-- PHẦN 1: QUẢN LÝ DANH MỤC TIỆN ÍCH (TienIch) -->
      <section class="bg-bg-main rounded-card border border-border-warm overflow-hidden">
        <div class="bg-bg-sub px-5 py-3.5 border-b border-border-warm flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-base font-bold text-text-main">Danh mục Tiện ích phòng trọ</h1>
            <p class="text-xs text-text-sub mt-0.5">Quản lý các tiện ích chuẩn hóa hiển thị khi chủ trọ đăng tin và sinh viên lọc phòng</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-1 rounded-full bg-bg-main border border-border-warm text-xs font-semibold text-text-main tnum"><?= count($dsTienIch) ?> tiện ích</span>
            <button type="button" onclick="openTienIchModal()" class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-btn bg-accent text-white font-semibold text-xs transition-all duration-200 active:scale-[0.98]">
              <span class="material-symbols-outlined text-[16px]">add</span>Thêm tiện ích
            </button>
          </div>
        </div>

        <div class="p-5 space-y-4">
          <div class="flex items-center gap-2 bg-bg-sub border border-border-warm rounded-btn px-3 py-2 max-w-sm">
            <span class="material-symbols-outlined text-text-sub text-[18px]">search</span>
            <input id="search-tien-ich" type="search" oninput="filterTienIch()" placeholder="Tìm tên tiện ích hoặc mã icon..." class="bg-transparent outline-none text-xs text-text-main w-full">
          </div>

          <?php if (empty($dsTienIch)): ?>
          <div class="py-12 text-center text-text-sub space-y-2 border border-dashed border-border-warm rounded-card">
            <span class="material-symbols-outlined text-4xl block mx-auto text-accent">fact_check</span>
            <p class="text-sm font-semibold text-text-main">Chưa có tiện ích nào trong danh mục</p>
            <p class="text-xs">Bấm "Thêm tiện ích" để tạo các tiện ích như Wifi miễn phí, Máy lạnh, Gác lửng, Giữ xe...</p>
          </div>
          <?php else: ?>
          <div class="overflow-x-auto border border-border-warm rounded-card">
            <table class="w-full text-left border-collapse">
              <thead class="bg-bg-sub">
                <tr class="text-[11px] font-bold uppercase tracking-wider text-text-sub border-b border-border-warm">
                  <th class="py-2.5 px-3 w-20">Mã</th>
                  <th class="py-2.5 px-3">Tên tiện ích</th>
                  <th class="py-2.5 px-3">Class Icon</th>
                  <th class="py-2.5 px-3 tnum">Đang áp dụng</th>
                  <th class="py-2.5 px-3 text-right">Hành động</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dsTienIch as $ti):
                  $jsonTi = json_encode($ti, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
                ?>
                <tr class="tienich-row border-t border-border-warm hover:bg-bg-sub/40 text-xs"
                    data-search="<?= strtolower(htmlspecialchars($ti['TenTienIch'] . ' ' . $ti['Icon'], ENT_QUOTES, 'UTF-8')) ?>">
                  <td class="py-2.5 px-3 font-mono font-bold text-text-main">#<?= (int)$ti['MaTienIch'] ?></td>
                  <td class="py-2.5 px-3 font-semibold text-text-main"><?= htmlspecialchars($ti['TenTienIch'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="py-2.5 px-3 font-mono text-text-sub">
                    <span class="px-2 py-0.5 rounded bg-bg-sub border border-border-warm"><?= htmlspecialchars($ti['Icon'], ENT_QUOTES, 'UTF-8') ?></span>
                  </td>
                  <td class="py-2.5 px-3 tnum text-text-sub"><?= (int)$ti['SoPhongSuDung'] ?> phòng</td>
                  <td class="py-2.5 px-3">
                    <div class="flex items-center justify-end gap-1.5">
                      <button type="button" onclick='editTienIch(<?= $jsonTi ?>)' title="Sửa tiện ích"
                        class="w-7 h-7 rounded-btn border border-border-warm bg-bg-main text-text-main flex items-center justify-center hover:border-accent">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                      </button>
                      <form method="POST" class="inline" onsubmit="return confirm('Xóa tiện ích này sẽ gỡ liên kết khỏi các phòng trọ đang chọn. Bạn chắc chắn muốn xóa?')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="hanh_dong" value="xoa_tien_ich">
                        <input type="hidden" name="ma_tien_ich" value="<?= (int)$ti['MaTienIch'] ?>">
                        <button type="submit" title="Xóa tiện ích"
                          class="w-7 h-7 rounded-btn border border-status-red-border bg-status-red-bg text-status-red-text flex items-center justify-center">
                          <span class="material-symbols-outlined text-[16px]">delete</span>
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

      <!-- PHẦN 2: NHÃN DANH MỤC TỰ ĐỘNG (NhanDanhMuc) -->
      <section class="bg-bg-main rounded-card border border-border-warm overflow-hidden">
        <div class="bg-bg-sub px-5 py-3.5 border-b border-border-warm flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-base font-bold text-text-main">Nhãn danh mục & Từ khóa nhận diện tự động</h2>
            <p class="text-xs text-text-sub mt-0.5">Quản lý bộ nhãn và từ khóa đối chiếu với tiêu đề, mô tả phòng trọ</p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <span class="px-2.5 py-1 rounded-full bg-bg-main border border-border-warm text-xs font-semibold text-text-main tnum"><?= count($dsNhan) ?> nhãn</span>
            <!-- [COMMIT_2_HTML_BUTTON] -->
            <button type="button" onclick="openNhanModal()" class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-btn bg-accent text-white font-semibold text-xs transition-all duration-200 active:scale-[0.98]">
              <span class="material-symbols-outlined text-[16px]">new_label</span>Thêm nhãn
            </button>
          </div>
        </div>

        <div class="p-5 space-y-4">
          <div class="flex items-center gap-2 bg-bg-sub border border-border-warm rounded-btn px-3 py-2 max-w-sm">
            <span class="material-symbols-outlined text-text-sub text-[18px]">search</span>
            <input id="search-nhan" type="search" oninput="filterNhan()" placeholder="Tìm tên nhãn hoặc từ khóa nhận diện..." class="bg-transparent outline-none text-xs text-text-main w-full">
          </div>

          <?php if (empty($dsNhan)): ?>
          <div class="py-12 text-center text-text-sub space-y-2 border border-dashed border-border-warm rounded-card">
            <span class="material-symbols-outlined text-4xl block mx-auto text-accent">label</span>
            <p class="text-sm font-semibold text-text-main">Chưa có nhãn danh mục tự động nào</p>
            <p class="text-xs">Bấm "Thêm nhãn" để tạo các thẻ như Gác lửng, Giờ tự do, Không chung chủ, Gần ĐH An Giang...</p>
          </div>
          <?php else: ?>
          <div class="overflow-x-auto border border-border-warm rounded-card">
            <table class="w-full text-left border-collapse">
              <thead class="bg-bg-sub">
                <tr class="text-[11px] font-bold uppercase tracking-wider text-text-sub border-b border-border-warm">
                  <th class="py-2.5 px-3 w-20">Mã</th>
                  <th class="py-2.5 px-3 w-48">Tên nhãn</th>
                  <th class="py-2.5 px-3">Từ khóa nhận diện</th>
                  <th class="py-2.5 px-3 w-32 tnum">Phòng đã gắn</th>
                  <th class="py-2.5 px-3 w-28 text-right">Hành động</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dsNhan as $nh):
                  $jsonNh = json_encode($nh, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
                  $dsKwHienThi = array_filter(array_map('trim', preg_split('/[,;|]+/', (string)($nh['TuKhoaNhanDien'] ?? ''))));
                ?>
                <tr class="nhan-row border-t border-border-warm hover:bg-bg-sub/40 text-xs"
                    data-search="<?= strtolower(htmlspecialchars($nh['TenNhan'] . ' ' . ($nh['TuKhoaNhanDien'] ?? ''), ENT_QUOTES, 'UTF-8')) ?>">
                  <td class="py-2.5 px-3 font-mono font-bold text-text-main">#<?= (int)$nh['MaNhan'] ?></td>
                  <td class="py-2.5 px-3">
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-status-green-bg text-accent font-semibold">
                      <span class="material-symbols-outlined text-[13px]">sell</span>
                      <?= htmlspecialchars($nh['TenNhan'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </td>
                  <td class="py-2.5 px-3">
                    <?php if (!empty($dsKwHienThi)): ?>
                      <div class="flex flex-wrap gap-1">
                        <?php foreach ($dsKwHienThi as $kwItem): ?>
                          <span class="px-2 py-0.5 rounded bg-bg-sub border border-border-warm text-text-sub font-mono text-[11px]"><?= htmlspecialchars($kwItem, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <span class="text-text-sub italic">Chưa thiết lập từ khóa</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-2.5 px-3 tnum text-text-sub"><?= (int)$nh['SoPhongGanNhan'] ?> phòng</td>
                  <td class="py-2.5 px-3">
                    <div class="flex items-center justify-end gap-1.5">
                      <button type="button" onclick='editNhan(<?= $jsonNh ?>)' title="Sửa nhãn"
                        class="w-7 h-7 rounded-btn border border-border-warm bg-bg-main text-text-main flex items-center justify-center hover:border-accent">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                      </button>
                      <form method="POST" class="inline" onsubmit="return confirm('Xóa nhãn này sẽ gỡ thẻ khỏi các phòng trọ liên quan. Bạn chắc chắn muốn xóa?')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="hanh_dong" value="xoa_nhan">
                        <input type="hidden" name="ma_nhan" value="<?= (int)$nh['MaNhan'] ?>">
                        <button type="submit" title="Xóa nhãn"
                          class="w-7 h-7 rounded-btn border border-status-red-border bg-status-red-bg text-status-red-text flex items-center justify-center">
                          <span class="material-symbols-outlined text-[16px]">delete</span>
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
    </main>
  </div>
</div>

<!-- MODAL THÊM / SỬA TIỆN ÍCH -->
<div id="tienich-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
  <div class="bg-bg-main rounded-card border border-border-warm shadow-lg w-full max-w-md">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-border-warm">
      <h2 id="tienich-modal-title" class="text-sm font-bold text-text-main">Thêm tiện ích mới</h2>
      <button type="button" onclick="closeTienIchModal()" class="w-7 h-7 rounded-btn border border-border-warm flex items-center justify-center text-text-sub hover:text-text-main">
        <span class="material-symbols-outlined text-[18px]">close</span>
      </button>
    </div>
    <form method="POST" class="p-5 space-y-3.5">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="hanh_dong" value="luu_tien_ich">
      <input type="hidden" id="tienich-id" name="ma_tien_ich" value="0">

      <div>
        <label class="text-xs font-semibold text-text-main block mb-1">Tên tiện ích <span class="text-rose-500">*</span></label>
        <input id="tienich-ten" name="ten_tien_ich" type="text" required placeholder="VD: Wifi miễn phí, Máy lạnh, Gác lửng..."
          class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
      </div>

      <div>
        <label class="text-xs font-semibold text-text-main block mb-1">Mã Icon (Bootstrap / FontAwesome)</label>
        <input id="tienich-icon" name="icon" type="text" value="bi-check-circle" placeholder="VD: bi-wifi, bi-snow, bi-shield-check"
          class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm font-mono text-text-main outline-none focus:border-accent">
        <p class="text-[11px] text-text-sub mt-1">Gợi ý nhanh: <code class="font-mono">bi-wifi</code>, <code class="font-mono">bi-snow</code>, <code class="font-mono">bi-bicycle</code>, <code class="font-mono">bi-droplet</code>, <code class="font-mono">bi-check-circle</code></p>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button" onclick="closeTienIchModal()" class="px-4 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs">Hủy</button>
        <button type="submit" class="px-4 py-2 rounded-btn bg-accent text-white font-semibold text-xs">Lưu tiện ích</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL THÊM / SỬA NHÃN DANH MỤC -->
<div id="nhan-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
  <div class="bg-bg-main rounded-card border border-border-warm shadow-lg w-full max-w-md">
    <div class="flex items-center justify-between px-5 py-3.5 border-b border-border-warm">
      <h2 id="nhan-modal-title" class="text-sm font-bold text-text-main">Thêm nhãn danh mục mới</h2>
      <button type="button" onclick="closeNhanModal()" class="w-7 h-7 rounded-btn border border-border-warm flex items-center justify-center text-text-sub hover:text-text-main">
        <span class="material-symbols-outlined text-[18px]">close</span>
      </button>
    </div>
    <form method="POST" class="p-5 space-y-3.5">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="hanh_dong" value="luu_nhan">
      <input type="hidden" id="nhan-id" name="ma_nhan" value="0">

      <div>
        <label class="text-xs font-semibold text-text-main block mb-1">Tên nhãn hiển thị <span class="text-rose-500">*</span></label>
        <input id="nhan-ten" name="ten_nhan" type="text" maxlength="50" required placeholder="VD: Gác lửng, Giờ tự do, Không chung chủ..."
          class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
      </div>

      <div>
        <label class="text-xs font-semibold text-text-main block mb-1">Từ khóa nhận diện tự động</label>
        <textarea id="nhan-tukhoa" name="tu_khoa_nhan_dien" rows="3" maxlength="255"
          placeholder="Nhập các từ khóa cách nhau bằng dấu phẩy. VD: gác lửng, gác đúc, có gác"
          class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent"></textarea>
        <p class="text-[11px] text-text-sub mt-1">Phân tách nhiều từ khóa bằng dấu phẩy (<code class="font-mono">,</code>). Không phân biệt chữ hoa/thường khi quét.</p>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2">
        <button type="button" onclick="closeNhanModal()" class="px-4 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs">Hủy</button>
        <button type="submit" class="px-4 py-2 rounded-btn bg-accent text-white font-semibold text-xs">Lưu nhãn</button>
      </div>
    </form>
  </div>
</div>

<script>
  function filterTienIch() {
    const q = (document.getElementById('search-tien-ich')?.value || '').toLowerCase();
    document.querySelectorAll('.tienich-row').forEach(r => {
      const text = r.getAttribute('data-search') || '';
      r.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
  }

  function openTienIchModal() {
    document.getElementById('tienich-modal-title').textContent = 'Thêm tiện ích mới';
    document.getElementById('tienich-id').value = '0';
    document.getElementById('tienich-ten').value = '';
    document.getElementById('tienich-icon').value = 'bi-check-circle';
    document.getElementById('tienich-modal').classList.remove('hidden');
  }

  function editTienIch(ti) {
    document.getElementById('tienich-modal-title').textContent = 'Sửa tiện ích — ' + ti.TenTienIch;
    document.getElementById('tienich-id').value = ti.MaTienIch;
    document.getElementById('tienich-ten').value = ti.TenTienIch;
    document.getElementById('tienich-icon').value = ti.Icon || 'bi-check-circle';
    document.getElementById('tienich-modal').classList.remove('hidden');
  }

  function closeTienIchModal() {
    document.getElementById('tienich-modal').classList.add('hidden');
  }

  function filterNhan() {
    const q = (document.getElementById('search-nhan')?.value || '').toLowerCase();
    document.querySelectorAll('.nhan-row').forEach(r => {
      const text = r.getAttribute('data-search') || '';
      r.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
  }

  function openNhanModal() {
    document.getElementById('nhan-modal-title').textContent = 'Thêm nhãn danh mục mới';
    document.getElementById('nhan-id').value = '0';
    document.getElementById('nhan-ten').value = '';
    document.getElementById('nhan-tukhoa').value = '';
    document.getElementById('nhan-modal').classList.remove('hidden');
  }

  function editNhan(nh) {
    document.getElementById('nhan-modal-title').textContent = 'Sửa nhãn danh mục — ' + nh.TenNhan;
    document.getElementById('nhan-id').value = nh.MaNhan;
    document.getElementById('nhan-ten').value = nh.TenNhan;
    document.getElementById('nhan-tukhoa').value = nh.TuKhoaNhanDien || '';
    document.getElementById('nhan-modal').classList.remove('hidden');
  }

  function closeNhanModal() {
    document.getElementById('nhan-modal').classList.add('hidden');
  }
</script>

<?php include_once __DIR__ . '/../dungchung/footer.php'; ?>