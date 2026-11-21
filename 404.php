<?php
http_response_code(404);
$tieuDeTrang = '404 - Không tìm thấy trang — CampusNest AGU';
$trangHienTai = '404';

include_once __DIR__ . '/dungchung/header.php';
?>

<main class="min-h-[70vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
  <div class="max-w-2xl w-full text-center space-y-8 bg-bg-main p-8 sm:p-12 rounded-card border border-border-warm shadow-sm">
    
    <!-- Huy hiệu & Icon minh họa -->
    <div class="relative mx-auto w-24 h-24 rounded-full bg-status-amber-bg border border-border-warm flex items-center justify-center">
      <span class="material-symbols-outlined text-5xl text-accent">wrong_location</span>
      <span class="absolute -top-1 -right-1 flex h-4 w-4">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent opacity-75"></span>
        <span class="relative inline-flex rounded-full h-4 w-4 bg-accent"></span>
      </span>
    </div>

    <!-- Tiêu đề 404 & Thông điệp -->
    <div class="space-y-3">
      <span class="inline-block px-3 py-1 rounded-full bg-bg-sub border border-border-warm text-xs font-semibold text-text-sub uppercase tracking-wider">
        Mã lỗi: 404 Not Found
      </span>
      <h1 class="font-serif text-3xl sm:text-4xl font-bold text-text-main">
        Ối! Trang này không tồn tại
      </h1>
      <p class="text-sm sm:text-base text-text-sub max-w-lg mx-auto leading-relaxed">
        Địa chỉ web bạn đang tìm kiếm có thể đã bị xóa, đổi tên hoặc tạm thời không khả dụng trên hệ thống bảng tin nhà trọ <strong class="text-text-main">CampusNest AGU</strong>.
      </p>
    </div>

    <!-- Thanh tìm kiếm nhanh -->
    <form action="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" method="GET" class="max-w-md mx-auto">
      <div class="relative flex items-center">
        <span class="material-symbols-outlined absolute left-3.5 text-text-sub text-[20px] pointer-events-none">search</span>
        <input 
          type="text" 
          name="q" 
          placeholder="Tìm phòng theo tên, đường, khu vực..." 
          class="w-full pl-10 pr-24 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main placeholder-text-sub/70 text-xs sm:text-sm focus:outline-none focus:border-accent transition-colors"
        >
        <button 
          type="submit" 
          class="absolute right-1.5 px-3 py-1.5 rounded-btn bg-accent text-white font-semibold text-xs hover:opacity-95 transition-all">
          Tìm phòng
        </button>
      </div>
    </form>

    <!-- Các nút điều hướng nhanh -->
    <div class="pt-2 border-t border-border-warm/70">
      <p class="text-xs text-text-sub mb-4 font-medium">Bạn có thể tiếp tục hành trình khám phá qua các liên kết sau:</p>
      
      <div class="flex flex-wrap items-center justify-center gap-3">
        <a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" 
           class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-btn bg-accent text-white font-semibold text-xs shadow-sm hover:opacity-95 transition-all">
          <span class="material-symbols-outlined text-[16px]">home</span>
          Về trang chủ
        </a>

        <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" 
           class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover transition-all">
          <span class="material-symbols-outlined text-[16px]">explore</span>
          Bản đồ &amp; Phòng trọ
        </a>

        <a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php" 
           class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover transition-all">
          <span class="material-symbols-outlined text-[16px]">group_add</span>
          Tìm bạn ở ghép
        </a>

        <a href="/DoAn_TimNhaTroSV_QLDAPM/camnang.php" 
           class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover transition-all">
          <span class="material-symbols-outlined text-[16px]">menu_book</span>
          Cẩm nang thuê trọ
        </a>
      </div>
    </div>

    <!-- Hỗ trợ kỹ thuật / Hotline -->
    <div class="text-[11px] text-text-sub pt-2">
      Cần hỗ trợ khẩn cấp? Liên hệ Ban Hỗ trợ Sinh viên AGU: 
      <a href="tel:02963843144" class="text-accent font-semibold hover:underline">0296.384.3144</a>
    </div>

  </div>
</main>

<?php include_once __DIR__ . '/dungchung/footer.php'; ?>
