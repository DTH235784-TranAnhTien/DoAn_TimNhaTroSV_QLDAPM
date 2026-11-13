<?php
require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

yeuCauVaiTro('SinhVien');

$nd   = nguoiDungHienTai();
$mand = (int)$nd['mannd'];

// ── Xử lý POST: Xóa khỏi yêu thích ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maYeuThich = (int)($_POST['ma_yeu_thich'] ?? 0);

    if ($maYeuThich > 0) {
        $stmt = $conn->prepare(
            "DELETE FROM DanhSachYeuThich WHERE MaYeuThich = ? AND MaSinhVien = ?"
        );
        $stmt->bind_param('ii', $maYeuThich, $mand);
        $stmt->execute();
        $stmt->close();
        datThongBao('success', 'Đã bỏ lưu phòng khỏi danh sách yêu thích.');
    } else {
        datThongBao('danger', 'Yêu cầu không hợp lệ.');
    }

    header('Location: /DoAn_TimNhaTroSV_QLDAPM/sinhvien/phongdaluu.php');
    exit();
}

// ── Lấy danh sách phòng yêu thích ───────────────────────────────────────────
$sql = "
    SELECT
        dy.MaYeuThich,
        dy.MaPhong,
        dy.NgayLuu,
        p.TenPhong,
        p.GiaThue,
        p.DienTich,
        p.TrangThai,
        n.TenNhaTro,
        n.DiaChi,
        COALESCE(
            (SELECT h1.DuongDan FROM HinhAnhPhongTro h1
             WHERE h1.MaPhong = p.MaPhong AND h1.LaAnhChinh = 1
             LIMIT 1),
            (SELECT h2.DuongDan FROM HinhAnhPhongTro h2
             WHERE h2.MaPhong = p.MaPhong
             ORDER BY h2.MaHinhAnh ASC
             LIMIT 1)
        ) AS AnhDaiDien
    FROM DanhSachYeuThich dy
    JOIN PhongTro p  ON p.MaPhong   = dy.MaPhong
    JOIN NhaTro   n  ON n.MaNhaTro  = p.MaNhaTro
    WHERE dy.MaSinhVien = ?
    ORDER BY dy.NgayLuu DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $mand);
$stmt->execute();
$result  = $stmt->get_result();
$dsPhong = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ── Metadata trang ──────────────────────────────────────────────────────────
$tieuDeTrang  = 'Phòng đã lưu — CampusNest AGU';
$trangHienTai = 'saved-rooms';

include_once __DIR__ . '/../dungchung/header.php';
?>

<main class="max-w-[1280px] mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 page-view flex-1">

  <!-- ── Tiêu đề trang ─────────────────────────────────────────────────────── -->
  <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
      <h1 class="text-2xl text-text-main">Phòng đã lưu</h1>
      <p class="text-sm text-text-sub mt-1">
        Danh sách phòng trọ bạn đã đánh dấu yêu thích
        <?php if (!empty($dsPhong)): ?>
          &mdash; <span class="font-semibold text-text-main tnum"><?= count($dsPhong) ?></span> phòng
        <?php endif; ?>
      </p>
    </div>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php"
       class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-btn bg-accent text-white text-xs font-semibold transition-all duration-200 active:scale-[0.98] self-start sm:self-auto">
      <span class="material-symbols-outlined" style="font-size:16px">search</span>
      Tìm thêm phòng
    </a>
  </div>

  <?php if (empty($dsPhong)): ?>
  <!-- ── Empty state ──────────────────────────────────────────────────────── -->
  <div class="flex flex-col items-center justify-center py-20 text-center gap-5">
    <div class="w-20 h-20 rounded-full bg-bg-sub border border-border-warm flex items-center justify-center">
      <span class="material-symbols-outlined text-text-sub" style="font-size:40px">bookmark_add</span>
    </div>
    <div class="space-y-2 max-w-xs">
      <p class="text-base font-semibold text-text-main">Chưa có phòng nào được lưu</p>
      <p class="text-sm text-text-sub leading-relaxed">
        Hãy khám phá danh sách phòng trọ và nhấn ❤ để lưu những phòng ưng ý — bạn có thể xem lại bất cứ lúc nào tại đây.
      </p>
    </div>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-btn bg-accent text-white text-sm font-semibold transition-all duration-200 active:scale-[0.98]">
      <span class="material-symbols-outlined" style="font-size:18px">apartment</span>
      Khám phá danh sách phòng trọ
    </a>
  </div>

  <?php else: ?>
  <!-- ── Grid phòng ───────────────────────────────────────────────────────── -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">

    <?php foreach ($dsPhong as $phong): ?>
    <?php
      $anhUrl = !empty($phong['AnhDaiDien'])
        ? '/DoAn_TimNhaTroSV_QLDAPM/' . ltrim($phong['AnhDaiDien'], '/')
        : '/DoAn_TimNhaTroSV_QLDAPM/giaodien/img/placeholder-room.jpg';

      $trangThai  = $phong['TrangThai'] ?? '';
      $giaFormat  = number_format((float)$phong['GiaThue'], 0, ',', '.');
      $maYeuThich = (int)$phong['MaYeuThich'];
      $maPhong    = (int)$phong['MaPhong'];
    ?>

    <div class="bg-white border border-border-warm rounded-card overflow-hidden card-hover flex flex-col">

      <!-- Ảnh thumbnail -->
      <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $maPhong ?>" class="block relative">
        <img src="<?= htmlspecialchars($anhUrl) ?>"
             alt="<?= htmlspecialchars($phong['TenPhong']) ?>"
             class="w-full h-44 object-cover"
             onerror="this.src='/DoAn_TimNhaTroSV_QLDAPM/giaodien/img/placeholder-room.jpg'">

        <!-- Badge trạng thái -->
        <?php if ($trangThai === 'ConPhong'): ?>
        <span class="absolute top-2.5 left-2.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-status-green-bg text-status-green-text text-xs font-semibold border border-status-green-text/20">
          <span class="w-1.5 h-1.5 rounded-full bg-status-green-text inline-block"></span>
          Còn phòng
        </span>
        <?php elseif ($trangThai === 'DaThue'): ?>
        <span class="absolute top-2.5 left-2.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-status-red-bg text-status-red-text text-xs font-semibold border border-status-red-border">
          <span class="w-1.5 h-1.5 rounded-full bg-status-red-text inline-block"></span>
          Đã thuê
        </span>
        <?php elseif ($trangThai === 'BaoTri'): ?>
        <span class="absolute top-2.5 left-2.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-status-gray-bg text-status-gray-text text-xs font-semibold">
          Bảo trì
        </span>
        <?php endif; ?>
      </a>

      <!-- Nội dung card -->
      <div class="p-4 flex flex-col flex-1 gap-3">
        <div class="flex-1 space-y-1">
          <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $maPhong ?>"
             class="block font-semibold text-sm text-text-main hover:text-accent transition-colors leading-snug line-clamp-2">
            <?= htmlspecialchars($phong['TenPhong']) ?>
          </a>
          <p class="text-xs text-text-sub truncate">
            <span class="material-symbols-outlined" style="font-size:13px;vertical-align:-2px">apartment</span>
            <?= htmlspecialchars($phong['TenNhaTro']) ?>
          </p>
          <p class="text-xs text-text-sub truncate">
            <span class="material-symbols-outlined" style="font-size:13px;vertical-align:-2px">location_on</span>
            <?= htmlspecialchars($phong['DiaChi']) ?>
          </p>
        </div>

        <!-- Giá thuê -->
        <div class="flex items-baseline gap-1 tnum">
          <span class="text-base font-bold text-accent"><?= $giaFormat ?>đ</span>
          <span class="text-xs text-text-sub font-normal">/tháng</span>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 pt-1 border-t border-border-warm">
          <a href="/DoAn_TimNhaTroSV_QLDAPM/chitiet.php?id=<?= $maPhong ?>"
             class="flex-1 text-center py-2 rounded-btn border border-accent text-accent text-xs font-semibold hover:bg-accent hover:text-white transition-all duration-200 active:scale-[0.98]">
            Xem chi tiết
          </a>

          <!-- Nút Xóa khỏi yêu thích -->
          <form method="POST" action="/DoAn_TimNhaTroSV_QLDAPM/sinhvien/phongdaluu.php" class="shrink-0">
            <input type="hidden" name="ma_yeu_thich" value="<?= $maYeuThich ?>">
            <button type="submit"
                    onclick="return confirm('Bỏ lưu phòng này khỏi danh sách yêu thích?')"
                    title="Xóa khỏi yêu thích"
                    class="w-9 h-9 flex items-center justify-center rounded-btn border border-status-red-border bg-status-red-bg text-status-red-text hover:bg-red-100 transition-all duration-200 active:scale-[0.98]">
              <span class="material-symbols-outlined" style="font-size:16px">bookmark_remove</span>
            </button>
          </form>
        </div>
      </div>

    </div><!-- /card -->
    <?php endforeach; ?>

  </div><!-- /grid -->
  <?php endif; ?>

</main>

<?php include_once __DIR__ . '/../dungchung/footer.php'; ?>
