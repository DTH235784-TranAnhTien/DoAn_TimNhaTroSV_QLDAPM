<?php if (!isset($trangHienTai) || $trangHienTai !== 'admin'): ?>
  <!-- ================= FOOTER ================= -->
  <footer class="w-full bg-bg-sub border-t border-border-warm py-8 mt-12 text-xs text-text-sub">
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pb-6 border-b border-border-warm">
        <div class="space-y-2">
          <span class="text-sm font-bold text-text-main block">CampusNest AGU</span>
          <p class="leading-relaxed">
            Bảng tin nhà trọ và ghép bạn cùng phòng dành cho sinh viên Trường Đại học An Giang tại TP. Long Xuyên.
          </p>
        </div>
        <div class="space-y-1.5">
          <span class="text-sm font-bold text-text-main block">Danh mục bảng tin</span>
          <ul class="space-y-1">
            <li><a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="hover:text-accent">Trang chính (Phòng nổi bật)</a></li>
            <li><a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php" class="hover:text-accent">Bản đồ &amp; Toàn bộ phòng trọ</a></li>
            <li><a href="/DoAn_TimNhaTroSV_QLDAPM/ghepban.php" class="hover:text-accent">Ghép bạn cùng phòng (AGU-Match)</a></li>
            <li><a href="/DoAn_TimNhaTroSV_QLDAPM/camnang.php" class="hover:text-accent">Tải hợp đồng mẫu &amp; Review</a></li>
          </ul>
        </div>
        <?php if (daDangNhap() && (coVaiTro('ChuTro') || coVaiTro('QuanTriVien'))): ?>
        <div class="space-y-1.5">
          <span class="text-sm font-bold text-text-main block">Dành cho đối tác &amp; Cán bộ</span>
          <ul class="space-y-1">
            <li><a href="/DoAn_TimNhaTroSV_QLDAPM/chutro.php" class="hover:text-accent">Bảng quản lý dành cho Chủ trọ</a></li>
            <?php if (coVaiTro('QuanTriVien')): ?>
            <li><a href="/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php" class="text-accent font-semibold underline">Cổng Kiểm định Đoàn – Hội SV (Admin)</a></li>
            <?php endif; ?>
          </ul>
        </div>
        <?php endif; ?>
        <div class="space-y-1.5 tnum">
          <span class="text-sm font-bold text-text-main block">Liên hệ trực ban hỗ trợ</span>
          <p>Văn phòng Đoàn – Hội Sinh viên ĐH An Giang</p>
          <p>Số 18 Ung Văn Khiêm, P. Đông Xuyên, TP. Long Xuyên</p>
          <p class="font-bold text-text-main">Điện thoại: 0296.384.3144</p>
        </div>
      </div>
      <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-2 tnum">
        <span>© <?= date('Y') ?> Bảng tin Nhà trọ Sinh viên ĐH An Giang. Không thu phí trung gian.</span>
        <span>Cập nhật dữ liệu khảo sát thực địa HK1 <?= date('Y') ?></span>
      </div>
    </div>
  </footer>

  <?php endif; ?>

  <?php if (!isset($trangHienTai) || $trangHienTai !== 'admin'): ?>
  </div><!-- /wrapper -->
  <?php endif; ?>

  <script src="/DoAn_TimNhaTroSV_QLDAPM/giaodien/js/main.js"></script>
</body>
</html>