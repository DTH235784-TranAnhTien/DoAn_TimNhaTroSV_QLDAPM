<?php
require_once __DIR__ . '/../cauhinh/auth.php';
require_once __DIR__ . '/../cauhinh/database.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?php echo isset($tieuDeTrang) ? htmlspecialchars($tieuDeTrang) : 'CampusNest AGU — Bảng tin trọ ĐH An Giang'; ?></title>
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Newsreader:opsz,wght@6..72,600;6..72,700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script id="tailwind-config">
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            "bg-main": "#F7F5F0",
            "bg-sub": "#EFEBE3",
            "text-main": "#1E1B16",
            "text-sub": "#6B6358",
            "accent": "#00685F",
            "border-warm": "#DDD6C9",
            "status-green-bg": "#E6F2EE",
            "status-green-text": "#00685F",
            "status-amber-bg": "#F5EFE0",
            "status-amber-text": "#785713",
            "status-gray-bg": "#E6E2DA",
            "status-gray-text": "#6B6358",
            "status-red-border": "#D9B8B4",
            "status-red-bg": "#F7ECEA",
            "status-red-text": "#7D2D24"
          },
          fontFamily: {
            "sans": ["Be Vietnam Pro", "sans-serif"],
            "serif": ["Newsreader", "Georgia", "serif"]
          },
          fontSize: {
            "xs": ["13px", { "lineHeight": "19px" }],
            "sm": ["14px", { "lineHeight": "21px" }],
            "base": ["16px", { "lineHeight": "24px" }],
            "lg": ["18px", { "lineHeight": "27px" }],
            "xl": ["22px", { "lineHeight": "30px" }],
            "2xl": ["28px", { "lineHeight": "36px" }],
            "3xl": ["36px", { "lineHeight": "44px" }],
            "4xl": ["48px", { "lineHeight": "56px" }]
          },
          borderRadius: {
            "card": "6px",
            "btn": "8px",
            "full": "9999px"
          }
        }
      }
    };
  </script>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      background-color: #F7F5F0;
      color: #1E1B16;
      font-family: 'Be Vietnam Pro', sans-serif;
      font-size: 14px;
      line-height: 1.6;
      scroll-behavior: smooth;
    }
    h1 {
      font-family: 'Newsreader', Georgia, serif;
      font-weight: 700;
      letter-spacing: -0.02em;
      line-height: 1.15;
    }
    h2 {
      font-family: 'Be Vietnam Pro', sans-serif;
      font-weight: 600;
      letter-spacing: -0.01em;
    }
    .uppercase {
      letter-spacing: 0.05em;
    }
    .tnum {
      font-variant-numeric: tabular-nums;
    }
    .card-hover {
      transition: border-color 0.15s ease;
    }
    .card-hover:hover {
      border-color: rgba(0, 104, 95, 0.45);
    }
    .material-symbols-outlined {
      font-size: 18px;
      vertical-align: middle;
    }
    input[type="range"] {
      accent-color: #00685F;
    }
    .page-view {
      animation: fadeIn 0.25s ease-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(4px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes toastProgress {
      from { width: 100%; }
      to { width: 0%; }
    }
    .admin-menu-item { color: #6B6358; }
    .admin-menu-item:hover { background-color: rgba(247, 245, 240, 0.6); color: #1E1B16; }
    .admin-menu-item.active {  background-color: #00685F;  color: #FFFFFF;}
    .admin-menu-item.active .material-symbols-outlined { color: #FFFFFF; }

    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
      }
    }
  </style>
</head>

<body class="bg-bg-main text-text-main antialiased min-h-screen flex flex-col">

  <div id="toast-container" class="fixed bottom-5 right-5 z-[100] flex flex-col gap-2 max-w-sm w-full pointer-events-none px-4 sm:px-0"></div>

  <?php if (isset($trangHienTai) && $trangHienTai === 'admin'): ?>
  <!-- ===== HEADER RIÊNG CHO TRANG QUẢN TRỊ (không dùng Standard Header) ===== -->
  <?php else: ?>
  <?php include_once __DIR__ . '/thanhmenu.php'; ?>
  <?php endif; ?>

  <?php
  $tbFlash = layThongBao();
  if ($tbFlash):
    $toastMap = [
      'success' => ['success', '✓'],
      'danger'  => ['error',   '⚠'],
      'warning' => ['info',    '⚠'],
      'info'    => ['info',    'ℹ'],
    ];
    [$loaiToast, $kyHieu] = $toastMap[$tbFlash['loai']] ?? ['info', 'ℹ'];
  ?>
  <div class="max-w-[1280px] mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4">
    <div onclick="this.remove()" class="pointer-events-auto overflow-hidden rounded-card border-2 shadow-lg text-xs font-semibold animate-[slideIn_0.25s_ease-out] bg-bg-main border-accent flex items-center gap-2.5 px-4 py-3 cursor-pointer">
      <span class="material-symbols-outlined shrink-0"><?= $loaiToast === 'success' ? 'check_circle' : ($loaiToast === 'error' ? 'error' : 'info') ?></span>
      <span class="flex-1"><?= htmlspecialchars($tbFlash['noidung']) ?></span>
    </div>
  </div>
  <?php endif; ?>

  <!-- MODAL ĐĂNG TIN TÌM BẠN GHÉP -->
  <div id="roommate-post-modal" class="fixed inset-0 z-[95] hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-bg-main rounded-card max-w-md w-full p-6 border border-border-warm shadow-lg relative">
      <button onclick="closeRoommatePostModal()" aria-label="Đóng" type="button" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-bg-sub hover:border hover:border-accent/40 flex items-center justify-center text-text-sub transition-all duration-200 active:scale-[0.98]">
        <span class="material-symbols-outlined">close</span>
      </button>
      <div class="mb-4 pb-3 border-b border-border-warm">
        <h3 class="text-lg font-bold text-text-main">Đăng tin tìm bạn ghép</h3>
        <p class="text-xs text-text-sub mt-0.5">Tin của bạn sẽ hiển thị trong danh sách sinh viên tìm người ở cùng</p>
      </div>
      <form id="roommate-post-form" onsubmit="handleRoommatePostSubmit(event)" class="space-y-3.5">
        <div>
          <label class="block text-xs font-semibold text-text-main mb-1">Tiêu đề</label>
          <input id="rm-post-title" type="text" required maxlength="100" placeholder="VD: Cần 1 bạn nam cùng khoa CNTT chia phòng gác đúc" class="w-full px-3 py-2 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none">
        </div>
        <div>
          <label class="block text-xs font-semibold text-text-main mb-1">Mô tả thói quen sinh hoạt</label>
          <textarea id="rm-post-desc" rows="3" required placeholder="VD: Đã thuê phòng có máy lạnh tại Hẻm 4 Ung Văn Khiêm, ngủ trước 23h, sạch sẽ, không hút thuốc..." class="w-full p-3 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none resize-none"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3 tnum">
          <div>
            <label class="block text-xs font-semibold text-text-main mb-1">Ngân sách đóng góp / tháng</label>
            <input id="rm-post-budget" type="number" required min="200000" max="5000000" step="50000" placeholder="800000" class="w-full px-3 py-2 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none">
          </div>
          <div>
            <label class="block text-xs font-semibold text-text-main mb-1">Số điện thoại liên hệ</label>
            <input id="rm-post-phone" type="tel" required placeholder="VD: 09xxxxxxxx" class="w-full px-3 py-2 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none">
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-text-main mb-1">Khu vực mong muốn</label>
          <input id="rm-post-area" type="text" required placeholder="VD: Hẻm 4 Ung Văn Khiêm, P. Đông Xuyên" class="w-full px-3 py-2 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none">
        </div>
        <div class="pt-2 flex items-center gap-2">
          <button type="button" onclick="closeRoommatePostModal()" class="w-1/3 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover transition-all duration-200 active:scale-[0.98]">Hủy</button>
          <button type="submit" class="w-2/3 py-2.5 rounded-btn bg-accent text-white font-semibold text-xs flex items-center justify-center gap-1.5 transition-all duration-200 active:scale-[0.98]">
            <span class="material-symbols-outlined">post_add</span><span>Đăng tin</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL THÊM / SỬA TIN TRỌ (Admin) -->
  <div id="listing-edit-modal" class="fixed inset-0 z-[95] hidden bg-black/50 flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-bg-main rounded-card p-6 border border-border-warm shadow-lg relative">
      <button onclick="closeListingModal()" aria-label="Đóng" type="button" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-bg-sub hover:border hover:border-accent/40 flex items-center justify-center text-text-sub transition-all duration-200 active:scale-[0.98]">
        <span class="material-symbols-outlined">close</span>
      </button>
      <div class="mb-4 pb-3 border-b border-border-warm">
        <h3 id="listing-modal-title" class="text-lg font-bold text-text-main">Thêm tin trọ mới</h3>
        <p class="text-xs text-text-sub mt-0.5">Cập nhật thông tin hồ sơ nhà trọ trên hệ thống kiểm định AGU</p>
      </div>
      <form id="listing-edit-form" onsubmit="event.preventDefault(); saveListing();" class="space-y-4">
        <div>
          <label for="listing-title" class="block text-xs font-semibold text-text-main mb-1">Tiêu đề</label>
          <input id="listing-title" type="text" required maxlength="100" placeholder="VD: Phòng trọ gác lửng gần ĐH An Giang" class="w-full px-3 py-2 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="listing-address" class="block text-xs font-semibold text-text-main mb-1">Địa chỉ</label>
            <input id="listing-address" type="text" required placeholder="VD: Hẻm 12 Ung Văn Khiêm, P. Đông Xuyên" class="w-full px-3 py-2 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none">
          </div>
          <div>
            <label for="listing-owner" class="block text-xs font-semibold text-text-main mb-1">Chủ trọ</label>
            <input id="listing-owner" type="text" required placeholder="VD: Nguyễn Văn Nam" class="w-full px-3 py-2 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none">
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 tnum">
          <div>
            <label for="listing-price" class="block text-xs font-semibold text-text-main mb-1">Giá (đ/tháng)</label>
            <input id="listing-price" type="number" required min="200000" step="50000" placeholder="1300000" class="w-full px-3 py-2 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none">
          </div>
          <div>
            <label for="listing-size" class="block text-xs font-semibold text-text-main mb-1">Diện tích (m²)</label>
            <input id="listing-size" type="number" required min="8" max="200" placeholder="22" class="w-full px-3 py-2 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none">
          </div>
          <div>
            <label for="listing-type" class="block text-xs font-semibold text-text-main mb-1">Loại phòng</label>
            <select id="listing-type" class="w-full px-3 py-2 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none">
              <option value="Gác lửng đúc">Gác lửng đúc</option>
              <option value="Khép kín">Khép kín</option>
              <option value="KTX tư nhân">KTX tư nhân</option>
              <option value="Studio">Studio</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 tnum">
          <div>
            <label for="listing-phone" class="block text-xs font-semibold text-text-main mb-1">SĐT chủ trọ</label>
            <input id="listing-phone" type="tel" required placeholder="VD: 09xxxxxxxx" class="w-full px-3 py-2 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none">
          </div>
          <div>
            <label for="listing-status" class="block text-xs font-semibold text-text-main mb-1">Trạng thái</label>
            <select id="listing-status" class="w-full px-3 py-2 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none">
              <option value="pending">Chờ duyệt</option>
              <option value="approved">Đã duyệt</option>
              <option value="rejected">Từ chối</option>
            </select>
          </div>
        </div>
        <div class="pt-2 flex items-center justify-end gap-2">
          <button type="button" onclick="closeListingModal()" class="px-5 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover transition-all duration-200 active:scale-[0.98]">Hủy</button>
          <button type="submit" class="px-6 py-2.5 rounded-btn bg-accent text-white font-semibold text-xs flex items-center justify-center gap-1.5 transition-all duration-200 active:scale-[0.98]">
            <span class="material-symbols-outlined">save</span><span>Lưu</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL XÁC NHẬN XÓA TIN TRỌ -->
  <div id="delete-confirm-modal" class="fixed inset-0 z-[96] hidden bg-black/50 flex items-center justify-center p-4">
    <div class="max-w-sm w-full bg-bg-main rounded-card p-6 border border-border-warm shadow-lg text-center space-y-4">
      <div class="w-12 h-12 rounded-full bg-status-red-bg border border-status-red-border text-status-red-text flex items-center justify-center mx-auto">
        <span class="material-symbols-outlined text-3xl text-rose-600">warning</span>
      </div>
      <div class="space-y-1.5">
        <h3 class="text-base font-bold text-text-main">Xác nhận xóa tin trọ</h3>
        <p class="text-xs text-text-sub leading-relaxed">
          Bạn có chắc muốn xóa tin <strong id="delete-listing-id" class="text-text-main font-mono"></strong>? Hành động này không thể hoàn tác.
        </p>
      </div>
      <div class="pt-1 flex items-center gap-2">
        <button type="button" onclick="closeDeleteModal()" class="w-1/2 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover transition-all duration-200 active:scale-[0.98]">Hủy</button>
        <button type="button" onclick="executeDeleteListing()" class="w-1/2 py-2.5 rounded-btn bg-status-red-bg text-status-red-text border border-status-red-border font-semibold text-xs transition-all duration-200 active:scale-[0.98]">Xóa vĩnh viễn</button>
      </div>
    </div>
  </div>

  <?php
  // Trang admin tự quản lý layout riêng nên không mở wrapper chung ở đây.
  if (!isset($trangHienTai) || $trangHienTai !== 'admin'):
  ?>
  <div class="w-full flex-1 flex flex-col">
  <?php endif; ?>