<?php
$nd = nguoiDungHienTai();
$nd_hoten   = $nd['hoten'] ?? '';
$nd_email   = $nd['email'] ?? '';
$nd_vaitro  = $nd['vaitro'] ?? '';
$nd_anh     = $nd['anhdaidien'] ?? '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/default-avatar.png';
$laAdmin  = $nd_vaitro === 'QuanTriVien';
$laChuTro = $nd_vaitro === 'ChuTro';
?>
<header id="standard-header" class="w-full bg-bg-main border-b border-border-warm sticky top-0 z-50">
  <div class="h-16 max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
    <!-- Brand Identity -->
    <a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="flex items-center gap-2.5 shrink-0 focus-visible:outline focus-visible:outline-2 focus-visible:outline-accent focus-visible:outline-offset-2">
      <div class="w-8 h-8 rounded-card bg-accent text-white flex items-center justify-center font-bold text-base">AG</div>
      <div>
        <span class="text-base font-bold text-text-main block leading-none">CampusNest AGU</span>
        <span class="text-xs text-text-sub block mt-0.5">Bảng tin trọ ĐH An Giang</span>
      </div>
    </a>

    <!-- Desktop Navigation Links -->
    <nav class="hidden xl:flex items-center gap-1">
      <a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" data-nav="home" class="nav-link text-sm font-semibold px-3 py-1.5 rounded-btn <?= ($trangHienTai ?? 'home') === 'home' ? 'text-accent bg-bg-sub' : 'text-text-sub hover:text-text-main' ?> transition-all duration-200 active:scale-[0.98]">
        Trang chính
      </a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" data-nav="explore" class="nav-link text-sm font-medium px-3 py-1.5 rounded-btn <?= ($trangHienTai ?? '') === 'explore' ? 'text-accent bg-bg-sub font-semibold' : 'text-text-sub hover:text-text-main' ?> transition-all duration-200 active:scale-[0.98]">
        Bản đồ &amp; Toàn bộ trọ
      </a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php" data-nav="roommate" class="nav-link text-sm font-medium px-3 py-1.5 rounded-btn <?= ($trangHienTai ?? '') === 'roommate' ? 'text-accent bg-bg-sub font-semibold' : 'text-text-sub hover:text-text-main' ?> transition-all duration-200 active:scale-[0.98]">
        Ghép bạn cùng phòng (AI)
      </a>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/camnang.php" data-nav="guide" class="nav-link text-sm font-medium px-3 py-1.5 rounded-btn <?= ($trangHienTai ?? '') === 'guide' ? 'text-accent bg-bg-sub font-semibold' : 'text-text-sub hover:text-text-main' ?> transition-all duration-200 active:scale-[0.98]">
        Cẩm nang &amp; Review
      </a>
      <?php if ($laChuTro || $laAdmin): ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro.php" data-nav="landlord" class="nav-link text-sm font-medium px-3 py-1.5 rounded-btn <?= ($trangHienTai ?? '') === 'landlord' ? 'text-accent bg-bg-sub font-semibold' : 'text-text-sub hover:text-text-main' ?> transition-all duration-200 active:scale-[0.98]">
        Chủ trọ
      </a>
      <?php endif; ?>
      <?php if ($laAdmin): ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php" data-nav="admin" class="nav-link text-sm font-medium px-3 py-1.5 rounded-btn flex items-center gap-1 <?= ($trangHienTai ?? '') === 'admin' ? 'text-accent bg-bg-sub font-semibold' : 'text-text-sub hover:text-text-main' ?> transition-all duration-200 active:scale-[0.98]">
        <span class="material-symbols-outlined text-text-main" id="admin-lock-icon">lock_open</span>
        <span>Quản trị</span>
      </a>
      <?php endif; ?>
    </nav>

    <!-- Right Action Controls -->
    <div class="flex items-center gap-2.5 shrink-0">
      <?php if ($laChuTro || $laAdmin): ?>
      <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro.php#form-dang-tin" class="hidden items-center gap-1.5 bg-accent text-white font-semibold text-xs px-3.5 py-2 rounded-btn md:inline-flex transition-all duration-200 active:scale-[0.98]" type="button">
        <span class="material-symbols-outlined">add</span>
        <span>Đăng tin trọ</span>
      </a>
      <?php endif; ?>
      <button onclick="showToast('Hiện chưa có thông báo mới.', 'info')" aria-label="Xem thông báo" class="p-2 rounded-btn bg-bg-sub border border-border-warm text-text-main card-hover relative transition-all duration-200 active:scale-[0.98]" type="button">
        <span class="material-symbols-outlined">notifications</span>
      </button>

      <?php if (!$nd): ?>
      <!-- Guest Login Button -->
      <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php" type="button" class="px-3.5 py-2 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover transition-all duration-200 active:scale-[0.98]">
        Đăng nhập
      </a>
      <?php else: ?>
      <!-- Logged-in Avatar & Dropdown -->
      <div id="user-avatar-wrapper" class="relative">
        <button id="user-avatar-btn" onclick="toggleAvatarDropdown(event)" type="button" class="flex items-center gap-1.5 pl-1 py-1 pr-1.5 rounded-full hover:bg-bg-sub transition-all duration-200 active:scale-[0.98]">
          <div class="relative overflow-hidden w-8 h-8 rounded-full border border-border-warm shrink-0">
            <img id="header-user-avatar" loading="lazy" onerror="this.onerror=null; this.style.display='none'; this.insertAdjacentHTML('afterend','<span class=\'material-symbols-outlined text-text-sub text-4xl absolute inset-0 flex items-center justify-center bg-bg-sub\'>image</span>');" alt="Tài khoản" class="w-full h-full object-cover" src="<?= htmlspecialchars($nd_anh) ?>">
          </div>
          <span class="material-symbols-outlined text-text-sub">expand_more</span>
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

  <!-- Mobile Navigation Strip -->
  <div class="xl:hidden flex items-center gap-1.5 overflow-x-auto px-4 py-2 bg-bg-sub border-t border-border-warm">
    <a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="nav-link-mobile shrink-0 px-3 py-1 rounded-full text-xs <?= ($trangHienTai ?? 'home') === 'home' ? 'bg-accent text-white font-semibold' : 'bg-bg-main text-text-sub border border-border-warm font-medium' ?> transition-all duration-200">Trang chính</a>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" class="nav-link-mobile shrink-0 px-3 py-1 rounded-full text-xs <?= ($trangHienTai ?? '') === 'explore' ? 'bg-accent text-white font-semibold' : 'bg-bg-main text-text-sub border border-border-warm font-medium' ?> transition-all duration-200">Bản đồ &amp; Toàn bộ trọ</a>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php" class="nav-link-mobile shrink-0 px-3 py-1 rounded-full text-xs <?= ($trangHienTai ?? '') === 'roommate' ? 'bg-accent text-white font-semibold' : 'bg-bg-main text-text-sub border border-border-warm font-medium' ?> transition-all duration-200">Ghép bạn AI</a>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/camnang.php" class="nav-link-mobile shrink-0 px-3 py-1 rounded-full text-xs <?= ($trangHienTai ?? '') === 'guide' ? 'bg-accent text-white font-semibold' : 'bg-bg-main text-text-sub border border-border-warm font-medium' ?> transition-all duration-200">Cẩm nang &amp; Review</a>
    <?php if ($laChuTro || $laAdmin): ?>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro.php" class="nav-link-mobile shrink-0 px-3 py-1 rounded-full text-xs <?= ($trangHienTai ?? '') === 'landlord' ? 'bg-accent text-white font-semibold' : 'bg-bg-main text-text-sub border border-border-warm font-medium' ?> transition-all duration-200">Chủ trọ</a>
    <?php endif; ?>
    <?php if ($laAdmin): ?>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php" class="nav-link-mobile shrink-0 px-3 py-1 rounded-full text-xs <?= ($trangHienTai ?? '') === 'admin' ? 'bg-accent text-white font-semibold' : 'bg-bg-main text-text-sub border border-border-warm font-medium' ?> transition-all duration-200">Quản trị</a>
    <?php endif; ?>
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
document.addEventListener('click', (e) => {
  const wrapper = document.getElementById('user-avatar-wrapper');
  if (wrapper && !wrapper.contains(e.target)) {
    closeAvatarDropdown();
  }
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeAvatarDropdown();
});
</script>