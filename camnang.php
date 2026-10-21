<?php
$tieuDeTrang = 'Cẩm nang & Review — CampusNest AGU';
$trangHienTai = 'guide';
include_once __DIR__ . '/dungchung/header.php';
?>

<div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
  <section class="bg-accent text-white rounded-card p-6 sm:p-8 border-0">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
      <div class="lg:col-span-8 space-y-2.5">
        <span class="inline-block px-2.5 py-0.5 rounded-full bg-white/15 text-white text-xs font-semibold">Ban Công tác Sinh viên &amp; Hội Sinh viên ĐH An Giang</span>
        <h1 class="font-serif text-2xl sm:text-3xl font-bold text-white">Cẩm nang ký hợp đồng thuê trọ &amp; Bảo vệ quyền lợi tiền cọc</h1>
        <p class="text-sm text-white leading-relaxed max-w-2xl">Sinh viên tuyệt đối không chuyển khoản đặt cọc qua mạng khi chưa đến xem phòng thực tế. Sử dụng bộ hợp đồng mẫu dưới đây để ràng buộc rõ giá điện nước và điều kiện hoàn trả tiền cọc.</p>
      </div>
      <div class="lg:col-span-4 flex flex-col sm:flex-row lg:flex-col gap-2.5 lg:items-end">
        <a href="tel:02963843144" class="px-5 py-2.5 rounded-btn bg-white text-accent font-bold text-xs text-center tnum transition-all duration-200 active:scale-[0.98]">Đường dây nóng Đoàn trường: 0296.384.3144</a>
      </div>
    </div>
  </section>

  <section>
    <div class="mb-4">
      <h2 class="text-xl font-bold text-text-main">Bộ biểu mẫu &amp; Tài liệu hướng dẫn chính thức</h2>
      <p class="text-xs text-text-sub mt-0.5">Phát hành miễn phí cho sinh viên các khóa Đại học An Giang</p>
    </div>

    <div class="divide-y divide-border-warm border border-border-warm rounded-card bg-bg-main">
      <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-start gap-3">
          <span class="material-symbols-outlined text-accent mt-0.5">description</span>
          <div>
            <h3 class="text-sm font-bold text-text-main">1. Mẫu hợp đồng thuê phòng trọ sinh viên chuẩn AGU (Bản 2025)</h3>
            <p class="text-xs text-text-sub">Có điều khoản cố định giá điện nước 12 tháng và hoàn cọc 100% khi báo trước 30 ngày</p>
          </div>
        </div>
        <button onclick="showToast('Đang tải: Mau_Hop_Dong_Thue_Tro_AGU_2025.pdf', 'success')" type="button" class="px-4 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs tnum shrink-0 self-start sm:self-center card-hover flex items-center gap-1">
          <span class="material-symbols-outlined">download</span> Tải PDF · 1.2 MB
        </button>
      </div>

      <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-start gap-3">
          <span class="material-symbols-outlined text-accent mt-0.5">receipt_long</span>
          <div>
            <h3 class="text-sm font-bold text-text-main">2. Bảng quy định định mức giá điện sinh hoạt cho sinh viên thuê nhà</h3>
            <p class="text-xs text-text-sub">Hướng dẫn cách tính bậc thang điện lực An Giang khi chủ nhà đăng ký định mức sinh viên</p>
          </div>
        </div>
        <button onclick="showToast('Đang tải: Quy_Dinh_Gia_Dien_Sinh_Vien_An_Giang.pdf', 'success')" type="button" class="px-4 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs tnum shrink-0 self-start sm:self-center card-hover flex items-center gap-1">
          <span class="material-symbols-outlined">download</span> Tải PDF · 480 KB
        </button>
      </div>

      <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-start gap-3">
          <span class="material-symbols-outlined text-accent mt-0.5">how_to_reg</span>
          <div>
            <h3 class="text-sm font-bold text-text-main">3. Hướng dẫn khai báo tạm trú trực tuyến trên VNeID tại TP. Long Xuyên</h3>
            <p class="text-xs text-text-sub">Quy trình 4 bước dành cho sinh viên ngoại tỉnh tạm trú tại P. Đông Xuyên &amp; P. Mỹ Xuyên</p>
          </div>
        </div>
        <button onclick="showToast('Đang tải: Huong_Dan_Tam_Tru_VNeID_Long_Xuyen.pdf', 'success')" type="button" class="px-4 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs tnum shrink-0 self-start sm:self-center card-hover flex items-center gap-1">
          <span class="material-symbols-outlined">download</span> Tải PDF · 2.1 MB
        </button>
      </div>

      <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-start gap-3">
          <span class="material-symbols-outlined text-accent mt-0.5">contact_phone</span>
          <div>
            <h3 class="text-sm font-bold text-text-main">4. Danh bạ điện thoại trực ban Công an Phường &amp; Tổ tự quản sinh viên ngoài nội trú</h3>
            <p class="text-xs text-text-sub">Số điện thoại hỗ trợ trực tiếp khi xảy ra mất an ninh trật tự hoặc tranh chấp hợp đồng</p>
          </div>
        </div>
        <button onclick="showToast('Đang tải: Danh_Ba_Khan_Cap_Ngoai_Tru_AGU.pdf', 'success')" type="button" class="px-4 py-1.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs tnum shrink-0 self-start sm:self-center card-hover flex items-center gap-1">
          <span class="material-symbols-outlined">download</span> Tải PDF · 320 KB
        </button>
      </div>
    </div>
  </section>

  <section>
    <div class="mb-4">
      <h2 class="text-xl font-bold text-text-main">Chia sẻ thực tế từ sinh viên &amp; chủ nhà trọ</h2>
    </div>

    <!-- DANH GIA THUC TE TU DB HOAC EMPTY STATE -->
    <?php
    require_once __DIR__ . '/cauhinh/database.php';
    $resDg = $conn->query("
        SELECT d.*, u.HoTen, p.TenPhong, n.TenNhaTro
        FROM DanhGia d
        JOIN NguoiDung u ON d.MaSinhVien = u.MaNguoiDung
        JOIN PhongTro p ON d.MaPhong = p.MaPhong
        JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
        ORDER BY d.NgayDanhGia DESC
        LIMIT 6
    ");
    $dsDanhGia = $resDg ? $resDg->fetch_all(MYSQLI_ASSOC) : [];
    ?>

    <?php if (empty($dsDanhGia)): ?>
    <div class="py-12 px-4 text-center space-y-3 bg-bg-main rounded-card border border-border-warm border-dashed">
      <span class="material-symbols-outlined text-4xl text-text-sub block mx-auto">rate_review</span>
      <p class="text-sm font-bold text-text-main">Chưa có bài đánh giá thực tế nào</p>
      <p class="text-xs text-text-sub max-w-md mx-auto">Sinh viên và chủ trọ sau khi hoàn tất thuê phòng có thể gửi nhận xét, đánh giá thực tế tại đây.</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($dsDanhGia as $dg): ?>
      <blockquote class="p-5 rounded-card bg-bg-main border border-border-warm flex flex-col justify-between">
        <p class="text-sm text-text-main leading-relaxed">“<?= nl2br(htmlspecialchars($dg['NoiDung'])) ?>”</p>
        <footer class="pt-3 mt-3 border-t border-border-warm flex items-center justify-between text-xs">
          <div>
            <strong class="text-text-main block"><?= htmlspecialchars($dg['HoTen']) ?></strong>
            <span class="text-text-sub"><?= htmlspecialchars($dg['TenPhong']) ?> — <?= htmlspecialchars($dg['TenNhaTro']) ?></span>
          </div>
          <span class="text-accent font-semibold tnum"><?= (int)$dg['SoSao'] ?> ★</span>
        </footer>
      </blockquote>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</div>

<?php include_once __DIR__ . '/dungchung/footer.php'; ?>