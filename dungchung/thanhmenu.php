<?php
$nd = nguoiDungHienTai();
$nd_hoten   = $nd['hoten'] ?? '';
$nd_email   = $nd['email'] ?? '';
$nd_vaitro  = $nd['vaitro'] ?? '';
$nd_anh     = $nd['anhdaidien'] ?? '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/default-avatar.png';
$laAdmin  = $nd_vaitro === 'QuanTriVien';
$laChuTro = $nd_vaitro === 'ChuTro';

// Thông báo thực tế theo vai trò (Thành viên 4)
$dsThongBao = [];
$soThongBao = 0;
$mandHienTaiMenu = (int)($nd['mannd'] ?? 0);

if ($nd && $mandHienTaiMenu > 0 && isset($conn) && $conn instanceof mysqli) {
    if ($laChuTro) {
        $stmtTb = $conn->prepare("
            SELECT l.MaLichHen, l.NgayHen, l.GioHen, p.TenPhong, u.HoTen AS TenSV
            FROM LichHen l
            JOIN PhongTro p ON l.MaPhong = p.MaPhong
            JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
            JOIN NguoiDung u ON l.MaSinhVien = u.MaNguoiDung
            WHERE n.MaChuTro = ? AND l.TrangThai = 'ChoDuyet'
            ORDER BY l.NgayTao DESC
            LIMIT 5
        ");
        if ($stmtTb) {
            $stmtTb->bind_param('i', $mandHienTaiMenu);
            $stmtTb->execute();
            $resTb = $stmtTb->get_result();
            while ($rTb = $resTb->fetch_assoc()) {
                $dsThongBao[] = [
                    'tieuDe' => 'Lịch hẹn chờ duyệt: ' . $rTb['TenPhong'],
                    'moTa'   => $rTb['TenSV'] . ' · ' . date('d/m', strtotime($rTb['NgayHen'])) . ' ' . substr($rTb['GioHen'], 0, 5),
                    'url'    => '/DoAn_TimNhaTroSV_QLDAPM/chutro/lichhen.php',
                    'icon'   => 'event_upcoming'
                ];
            }
            $stmtTb->close();
        }
        $stmtCnt = $conn->prepare("
            SELECT COUNT(*) AS c
            FROM LichHen l
            JOIN PhongTro p ON l.MaPhong = p.MaPhong
            JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
            WHERE n.MaChuTro = ? AND l.TrangThai = 'ChoDuyet'
        ");
        if ($stmtCnt) {
            $stmtCnt->bind_param('i', $mandHienTaiMenu);
            $stmtCnt->execute();
            $soThongBao = (int)($stmtCnt->get_result()->fetch_assoc()['c'] ?? 0);
            $stmtCnt->close();
        }
    } elseif ($laAdmin) {
        $resPhongCho = $conn->query("SELECT COUNT(*) AS c FROM PhongTro WHERE TrangThaiDuyet = 'ChoDuyet'");
        $cntPhongCho = $resPhongCho ? (int)($resPhongCho->fetch_assoc()['c'] ?? 0) : 0;
        $resBaoCao   = $conn->query("SELECT COUNT(*) AS c FROM BaoCaoViPham WHERE TrangThai = 'ChoXuLy'");
        $cntBaoCao   = $resBaoCao ? (int)($resBaoCao->fetch_assoc()['c'] ?? 0) : 0;
        $soThongBao  = $cntPhongCho + $cntBaoCao;
        if ($cntPhongCho > 0) {
            $dsThongBao[] = [
                'tieuDe' => $cntPhongCho . ' tin phòng trọ chờ duyệt',
                'moTa'   => 'Bấm để vào kiểm duyệt tin đăng mới',
                'url'    => '/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=moderation',
                'icon'   => 'fact_check'
            ];
        }
        if ($cntBaoCao > 0) {
            $dsThongBao[] = [
                'tieuDe' => $cntBaoCao . ' báo cáo vi phạm chờ xử lý',
                'moTa'   => 'Kiểm tra phản ánh từ sinh viên',
                'url'    => '/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php?tab=reports',
                'icon'   => 'flag'
            ];
        }
    } else {
        $stmtTb = $conn->prepare("
            SELECT l.NgayHen, l.GioHen, p.TenPhong
            FROM LichHen l
            JOIN PhongTro p ON l.MaPhong = p.MaPhong
            WHERE l.MaSinhVien = ? AND l.TrangThai = 'DaXacNhan' AND l.NgayHen >= CURDATE()
            ORDER BY l.NgayHen ASC, l.GioHen ASC
            LIMIT 5
        ");
        if ($stmtTb) {
            $stmtTb->bind_param('i', $mandHienTaiMenu);
            $stmtTb->execute();
            $resTb = $stmtTb->get_result();
            while ($rTb = $resTb->fetch_assoc()) {
                $dsThongBao[] = [
                    'tieuDe' => 'Lịch hẹn đã xác nhận: ' . $rTb['TenPhong'],
                    'moTa'   => 'Ngày ' . date('d/m/Y', strtotime($rTb['NgayHen'])) . ' lúc ' . substr($rTb['GioHen'], 0, 5),
                    'url'    => '/DoAn_TimNhaTroSV_QLDAPM/sinhvien/lichhencuatoi.php',
                    'icon'   => 'event_available'
                ];
            }
            $stmtTb->close();
            $soThongBao = count($dsThongBao);
        }
    }
}
?>
<header id="standard-header" class="w-full bg-bg-main border-b border-border-warm sticky top-0 z-50">
  <div class="h-16 max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
    <!-- Brand Identity (Phong cách Roommates) -->
    <a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="flex items-center gap-2.5 shrink-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-accent focus-visible:outline-offset-2">
      <div class="w-9 h-9 rounded-xl bg-accent text-white flex items-center justify-center shadow-sm">
        <span class="material-symbols-outlined text-[21px]">home</span>
      </div>
      <div>
        <span class="text-lg font-bold text-text-main tracking-tight block leading-none">CampusNest</span>
        <span class="text-[11px] text-text-sub block mt-0.5 font-medium">Trọ sinh viên AGU</span>
      </div>
    </a>

    <!-- Thanh tìm kiếm trung tâm dạng Pill (giống ảnh mẫu) -->
    <form action="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" method="GET" class="hidden md:flex items-center flex-1 max-w-sm bg-slate-100 hover:bg-slate-100/90 focus-within:bg-white focus-within:ring-2 focus-within:ring-accent/20 focus-within:border-accent border border-transparent rounded-full pl-4 pr-1.5 py-1 transition-all">
      <input type="search" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
             placeholder="Tìm phòng, Ung Văn Khiêm, Mỹ Xuyên..."
             class="w-full bg-transparent text-xs sm:text-sm text-text-main placeholder-text-sub focus:outline-none">
      <button type="submit" aria-label="Tìm kiếm"
              class="w-8 h-8 rounded-full bg-accent text-white flex items-center justify-center shrink-0 hover:bg-blue-700 transition-colors shadow-sm">
        <span class="material-symbols-outlined text-[18px]">search</span>
      </button>
    </form>

    <!-- Nav Links gọn gàng trên 1 hàng (Desktop) -->
    <nav class="hidden xl:flex items-center gap-0.5 shrink-0">
      <a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="px-2.5 py-1.5 rounded-full text-xs <?= ($trangHienTai ?? 'home') === 'home' ? 'bg-status-green-bg text-accent font-semibold' : 'text-text-sub hover:text-text-main hover:bg-slate-100 font-medium' ?> transition-colors">Trang chính</a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" class="px-2.5 py-1.5 rounded-full text-xs <?= ($trangHienTai ?? '') === 'explore' ? 'bg-status-green-bg text-accent font-semibold' : 'text-text-sub hover:text-text-main hover:bg-slate-100 font-medium' ?> transition-colors">Tìm phòng</a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php" class="px-2.5 py-1.5 rounded-full text-xs <?= ($trangHienTai ?? '') === 'roommate' ? 'bg-status-green-bg text-accent font-semibold' : 'text-text-sub hover:text-text-main hover:bg-slate-100 font-medium' ?> transition-colors">Ghép bạn</a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/camnang.php" class="px-2.5 py-1.5 rounded-full text-xs <?= ($trangHienTai ?? '') === 'guide' ? 'bg-status-green-bg text-accent font-semibold' : 'text-text-sub hover:text-text-main hover:bg-slate-100 font-medium' ?> transition-colors">Cẩm nang</a>
      <?php if ($laChuTro || $laAdmin): ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro.php" class="px-2.5 py-1.5 rounded-full text-xs <?= ($trangHienTai ?? '') === 'landlord' ? 'bg-status-green-bg text-accent font-semibold' : 'text-text-sub hover:text-text-main hover:bg-slate-100 font-medium' ?> transition-colors">Chủ trọ</a>
      <?php endif; ?>
      <?php if ($laAdmin): ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php" class="px-2.5 py-1.5 rounded-full text-xs <?= ($trangHienTai ?? '') === 'admin' ? 'bg-status-green-bg text-accent font-semibold' : 'text-text-sub hover:text-text-main hover:bg-slate-100 font-medium' ?> transition-colors">Quản trị</a>
      <?php endif; ?>
    </nav>

    <!-- Right Action Controls -->
    <div class="flex items-center gap-2 sm:gap-3 shrink-0">
      <?php if ($laChuTro || $laAdmin): ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro/dangtin.php" class="hidden items-center gap-1.5 bg-accent hover:bg-blue-700 text-white font-semibold text-xs px-3.5 py-2 rounded-full md:inline-flex transition-all duration-200 active:scale-[0.98] shadow-sm" type="button">
        <span class="material-symbols-outlined text-[16px]">add</span>
        <span>Đăng tin trọ</span>
      </a>
      <?php endif; ?>

      <!-- Icon Yêu thích (giống ảnh mẫu) -->
      <a href="/DoAn_TimNhaTroSV_QLDAPM/sinhvien/phongdaluu.php" aria-label="Phòng đã lưu" title="Phòng đã lưu"
         class="p-2 rounded-full hover:bg-slate-100 text-text-sub hover:text-text-main transition-colors hidden sm:inline-flex items-center justify-center">
        <span class="material-symbols-outlined text-[21px]">favorite_border</span>
      </a>

      <!-- Chuông & Dropdown Thông báo -->
      <div id="notif-wrapper" class="relative">
        <button id="notif-btn" onclick="toggleNotifDropdown(event)" aria-label="Xem thông báo" class="p-2 rounded-full hover:bg-slate-100 text-text-sub hover:text-text-main relative transition-all duration-200 active:scale-[0.98]" type="button">
          <span class="material-symbols-outlined text-[21px]">notifications_none</span>
          <?php if ($soThongBao > 0): ?>
          <span class="absolute top-0.5 right-0.5 min-w-[17px] h-[17px] px-1 rounded-full bg-accent text-white text-[10px] font-bold flex items-center justify-center tnum shadow-sm ring-2 ring-white">
            <?= $soThongBao > 9 ? '9+' : $soThongBao ?>
          </span>
          <?php endif; ?>
        </button>

        <div id="notif-dropdown" class="hidden absolute top-full right-0 mt-2 w-72 sm:w-80 bg-bg-main border border-border-warm rounded-card shadow-lg z-50 overflow-hidden">
          <div class="px-4 py-2.5 bg-bg-sub/70 border-b border-border-warm flex items-center justify-between">
            <span class="text-xs font-bold text-text-main">Thông báo</span>
            <?php if ($soThongBao > 0): ?>
            <span class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent text-[10px] font-semibold tnum"><?= $soThongBao ?> mới</span>
            <?php endif; ?>
          </div>
          <div class="max-h-64 overflow-y-auto divide-y divide-border-warm">
            <?php if (empty($dsThongBao)): ?>
            <div class="px-4 py-6 text-center text-xs text-text-sub">
              <span class="material-symbols-outlined text-2xl block mx-auto mb-1 opacity-60">notifications_none</span>
              Hiện chưa có thông báo mới.
            </div>
            <?php else: ?>
              <?php foreach ($dsThongBao as $tb): ?>
              <a href="<?= htmlspecialchars($tb['url']) ?>" class="px-4 py-2.5 hover:bg-bg-sub flex items-start gap-2.5 transition-colors">
                <span class="material-symbols-outlined text-accent text-[18px] mt-0.5 shrink-0"><?= htmlspecialchars($tb['icon']) ?></span>
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-text-main line-clamp-1"><?= htmlspecialchars($tb['tieuDe']) ?></p>
                  <p class="text-[11px] text-text-sub mt-0.5 line-clamp-1"><?= htmlspecialchars($tb['moTa']) ?></p>
                </div>
              </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if (!$nd): ?>
      <!-- Guest Login Button -->
      <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php" type="button" class="px-4 py-2 rounded-full bg-accent text-white font-semibold text-xs hover:bg-blue-700 transition-all duration-200 active:scale-[0.98] shadow-sm">
        Đăng nhập
      </a>
      <?php else: ?>
      <!-- Logged-in Avatar & Name (giống ảnh mẫu) -->
      <div id="user-avatar-wrapper" class="relative pl-1 sm:border-l sm:border-border-warm">
        <button id="user-avatar-btn" onclick="toggleAvatarDropdown(event)" type="button" class="flex items-center gap-2 pl-1.5 py-1 pr-2 rounded-full hover:bg-slate-100 transition-all duration-200 active:scale-[0.98]">
          <div class="relative overflow-hidden w-8 h-8 rounded-full border border-border-warm shrink-0">
            <img id="header-user-avatar" loading="lazy" onerror="this.onerror=null; this.style.display='none'; this.insertAdjacentHTML('afterend','<span class=\'material-symbols-outlined text-text-sub text-2xl absolute inset-0 flex items-center justify-center bg-bg-sub\'>person</span>');" alt="Tài khoản" class="w-full h-full object-cover" src="<?= htmlspecialchars($nd_anh) ?>">
          </div>
          <div class="hidden sm:block text-left leading-tight">
            <span class="text-xs font-bold text-text-main block max-w-[110px] truncate"><?= htmlspecialchars($nd_hoten) ?></span>
            <span class="text-[11px] text-text-sub block"><?= $nd_vaitro === 'QuanTriVien' ? 'Quản trị' : ($nd_vaitro === 'ChuTro' ? 'Chủ trọ' : 'Sinh viên') ?></span>
          </div>
          <span class="material-symbols-outlined text-text-sub text-[18px]">expand_more</span>
        </button>

        <div id="avatar-dropdown" class="absolute top-full right-0 mt-2 w-64 bg-bg-main border border-border-warm rounded-card shadow-lg z-50 hidden overflow-hidden">
          <div class="p-3.5 bg-bg-sub/60 flex items-center gap-3">
            <div class="relative overflow-hidden w-10 h-10 rounded-full border border-border-warm shrink-0">
              <img id="dropdown-user-avatar" loading="lazy" onerror="this.onerror=null; this.style.display='none'; this.insertAdjacentHTML('afterend','<span class=\'material-symbols-outlined text-text-sub text-4xl absolute inset-0 flex items-center justify-center bg-bg-sub\'>image</span>');" alt="Avatar" class="w-full h-full object-cover" src="<?= htmlspecialchars($nd_anh) ?>">
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-1.5 flex-wrap">
                <span id="dropdown-user-name" class="text-xs font-bold text-text-main truncate"><?= htmlspecialchars($nd_hoten) ?></span>
                <span id="dropdown-user-role-badge" class="px-2 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold"><?= $nd_vaitro === 'QuanTriVien' ? 'Quản trị viên' : ($nd_vaitro === 'ChuTro' ? 'Chủ trọ' : 'Sinh viên') ?></span>
              </div>
              <p id="dropdown-user-email" class="text-xs text-text-sub truncate mt-0.5"><?= htmlspecialchars($nd_email) ?></p>
            </div>
          </div>
          <div class="border-t border-border-warm divide-y divide-border-warm">
            <div id="dropdown-role-items" class="py-1">
              <?php if ($nd_vaitro === 'SinhVien'): ?>
              <a href="/DoAn_TimNhaTroSV_QLDAPM/sinhvien/phongdaluu.php" class="w-full px-4 py-2 text-left text-xs font-medium text-text-main hover:bg-bg-sub flex items-center gap-2">
                <span class="material-symbols-outlined text-text-sub">bookmark</span><span>Phòng đã lưu</span>
              </a>
              <a href="/DoAn_TimNhaTroSV_QLDAPM/sinhvien/lichhencuatoi.php" class="w-full px-4 py-2 text-left text-xs font-medium text-text-main hover:bg-bg-sub flex items-center gap-2">
                <span class="material-symbols-outlined text-text-sub">event</span><span>Lịch hẹn của tôi</span>
              </a>
              <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php" class="w-full px-4 py-2 text-left text-xs font-medium text-text-main hover:bg-bg-sub flex items-center gap-2">
                <span class="material-symbols-outlined text-text-sub">person</span><span>Trang cá nhân</span>
              </a>
              <?php elseif ($nd_vaitro === 'ChuTro'): ?>
              <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro.php" class="w-full px-4 py-2 text-left text-xs font-medium text-text-main hover:bg-bg-sub flex items-center gap-2">
                <span class="material-symbols-outlined text-text-sub">apartment</span><span>Bảng quản lý của tôi</span>
              </a>
              <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro/dangtin.php" class="w-full px-4 py-2 text-left text-xs font-medium text-text-main hover:bg-bg-sub flex items-center gap-2">
                <span class="material-symbols-outlined text-text-sub">post_add</span><span>Đăng tin trọ mới</span>
              </a>
              <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro/danhsachphong.php" class="w-full px-4 py-2 text-left text-xs font-medium text-text-main hover:bg-bg-sub flex items-center gap-2">
                <span class="material-symbols-outlined text-text-sub">view_list</span><span>Quản lý phòng trọ</span>
              </a>
              <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro/duyetlichhen.php" class="w-full px-4 py-2 text-left text-xs font-medium text-text-main hover:bg-bg-sub flex items-center gap-2">
                <span class="material-symbols-outlined text-text-sub">calendar_check</span><span>Duyệt lịch hẹn</span>
              </a>
              <?php else: ?>
              <a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php" class="w-full px-4 py-2 text-left text-xs font-medium text-text-main hover:bg-bg-sub flex items-center gap-2">
                <span class="material-symbols-outlined text-text-sub">admin_panel_settings</span><span>Vào bảng quản trị</span>
              </a>
              <a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/duyettin.php" class="w-full px-4 py-2 text-left text-xs font-medium text-text-main hover:bg-bg-sub flex items-center gap-2">
                <span class="material-symbols-outlined text-text-sub">fact_check</span><span>Kiểm duyệt tin</span>
              </a>
              <?php endif; ?>
            </div>
            <div class="py-1 border-t border-border-warm">
              <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/hoso.php" class="w-full px-4 py-2 text-left text-xs font-medium text-text-main hover:bg-bg-sub flex items-center gap-2 transition-all duration-200">
                <span class="material-symbols-outlined text-text-sub">manage_accounts</span>
                <span>Thông tin cá nhân</span>
              </a>
            </div>
            <div class="py-1">
              <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangxuat.php" class="w-full px-4 py-2 text-left text-xs font-semibold text-rose-600 hover:bg-bg-sub flex items-center gap-2 transition-all duration-200">
                <span class="material-symbols-outlined text-rose-600">logout</span>
                <span>Đăng xuất</span>
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Mobile Navigation Strip (chỉ hiện trên màn hình nhỏ) -->
  <div class="xl:hidden bg-bg-main border-t border-slate-100">
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 flex items-center gap-1.5 overflow-x-auto py-2">
      <a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="shrink-0 px-3 py-1 rounded-full text-xs <?= ($trangHienTai ?? 'home') === 'home' ? 'bg-status-green-bg text-accent font-semibold' : 'text-text-sub hover:text-text-main hover:bg-slate-100 font-medium' ?> transition-all duration-200">Trang chính</a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" class="shrink-0 px-3 py-1 rounded-full text-xs <?= ($trangHienTai ?? '') === 'explore' ? 'bg-status-green-bg text-accent font-semibold' : 'text-text-sub hover:text-text-main hover:bg-slate-100 font-medium' ?> transition-all duration-200">Bản đồ &amp; Toàn bộ trọ</a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php" class="shrink-0 px-3 py-1 rounded-full text-xs <?= ($trangHienTai ?? '') === 'roommate' ? 'bg-status-green-bg text-accent font-semibold' : 'text-text-sub hover:text-text-main hover:bg-slate-100 font-medium' ?> transition-all duration-200">Ghép bạn cùng phòng (AI)</a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/camnang.php" class="shrink-0 px-3 py-1 rounded-full text-xs <?= ($trangHienTai ?? '') === 'guide' ? 'bg-status-green-bg text-accent font-semibold' : 'text-text-sub hover:text-text-main hover:bg-slate-100 font-medium' ?> transition-all duration-200">Cẩm nang &amp; Review</a>
      <?php if ($laChuTro || $laAdmin): ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro.php" class="shrink-0 px-3 py-1 rounded-full text-xs <?= ($trangHienTai ?? '') === 'landlord' ? 'bg-status-green-bg text-accent font-semibold' : 'text-text-sub hover:text-text-main hover:bg-slate-100 font-medium' ?> transition-all duration-200">Quản lý Chủ trọ</a>
      <?php endif; ?>
      <?php if ($laAdmin): ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php" class="shrink-0 px-3 py-1 rounded-full text-xs inline-flex items-center gap-1 <?= ($trangHienTai ?? '') === 'admin' ? 'bg-status-green-bg text-accent font-semibold' : 'text-text-sub hover:text-text-main hover:bg-slate-100 font-medium' ?> transition-all duration-200">
        <span class="material-symbols-outlined text-[14px]">admin_panel_settings</span>Quản trị
      </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Admin Dual-Mode Bar -->
  <?php if ($laAdmin && ($trangHienTai ?? '') !== 'admin'): ?>
  <div id="admin-dual-mode-bar" class="w-full bg-status-amber-bg border-b border-status-amber-text/30 py-1.5 px-4 text-xs text-status-amber-text flex items-center justify-between gap-2">
    <div class="max-w-[1280px] w-full mx-auto flex flex-wrap items-center justify-between gap-2">
      <span>Bạn đang đăng nhập với vai trò Quản trị viên — có thể vừa xem web vừa quản lý</span>
      <div class="flex items-center gap-3 ml-auto">
        <a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php" class="font-bold text-accent underline transition-all duration-200 active:scale-[0.98]">Vào bảng quản trị →</a>
        <button type="button" onclick="dismissAdminDualBar()" aria-label="Đóng thanh thông báo quản trị" class="w-5 h-5 rounded-full hover:bg-black/5 flex items-center justify-center text-status-amber-text transition-all duration-200">
          <span class="material-symbols-outlined text-[15px]">close</span>
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>
</header>

<script>
function toggleNotifDropdown(e) {
  if (e) e.stopPropagation();
  const dd = document.getElementById('notif-dropdown');
  if (dd) dd.classList.toggle('hidden');
  if (typeof closeAvatarDropdown === 'function') closeAvatarDropdown();
}
function closeNotifDropdown() {
  const dd = document.getElementById('notif-dropdown');
  if (dd) dd.classList.add('hidden');
}

document.addEventListener('click', (e) => {
  const wrapper = document.getElementById('user-avatar-wrapper');
  if (wrapper && !wrapper.contains(e.target)) {
    closeAvatarDropdown();
  }
  const notifWrapper = document.getElementById('notif-wrapper');
  if (notifWrapper && !notifWrapper.contains(e.target)) {
    closeNotifDropdown();
  }
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeAvatarDropdown();
    closeNotifDropdown();
  }
});
</script>
