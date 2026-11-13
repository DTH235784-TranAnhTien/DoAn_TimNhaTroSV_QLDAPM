<?php
$tieuDeTrang  = 'Lịch hẹn của tôi — CampusNest AGU';
$trangHienTai = 'my-schedule';

require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

yeuCauVaiTro('SinhVien');

$nd   = nguoiDungHienTai();
$mand = (int)$nd['mannd'];

// =====================================================================
// XỬ LÝ POST: Hủy lịch hẹn
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maLichHen = (int)($_POST['ma_lich_hen'] ?? 0);

    if ($maLichHen > 0) {
        // Lấy trạng thái hiện tại — đảm bảo lịch hẹn thuộc về sinh viên này
        $stmtChk = $conn->prepare(
            "SELECT TrangThai FROM LichHen WHERE MaLichHen = ? AND MaSinhVien = ? LIMIT 1"
        );
        $stmtChk->bind_param('ii', $maLichHen, $mand);
        $stmtChk->execute();
        $resultChk = $stmtChk->get_result();
        $row       = $resultChk->fetch_assoc();
        $stmtChk->close();

        if ($row && in_array($row['TrangThai'], ['ChoDuyet', 'DaXacNhan'])) {
            $stmtHuy = $conn->prepare(
                "UPDATE LichHen SET TrangThai = 'DaHuy' WHERE MaLichHen = ? AND MaSinhVien = ?"
            );
            $stmtHuy->bind_param('ii', $maLichHen, $mand);
            $stmtHuy->execute();
            $stmtHuy->close();
            datThongBao('success', 'Đã hủy lịch hẹn thành công.');
        } else {
            datThongBao('danger', 'Không thể hủy lịch hẹn này (trạng thái không hợp lệ hoặc không thuộc về bạn).');
        }
    } else {
        datThongBao('danger', 'Yêu cầu không hợp lệ.');
    }

    header('Location: /DoAn_TimNhaTroSV_QLDAPM/sinhvien/lichhencuatoi.php');
    exit();
}

// =====================================================================
// LẤY DANH SÁCH LỊCH HẸN CỦA SINH VIÊN
// =====================================================================
$sql = "
    SELECT
        lh.MaLichHen,
        lh.NgayHen,
        lh.GioHen,
        lh.GhiChu,
        lh.TrangThai,
        lh.NgayTao,
        pt.TenPhong,
        nt.TenNhaTro,
        nt.DiaChi,
        nd.HoTen  AS TenChuTro,
        nd.SoDienThoai
    FROM LichHen lh
    JOIN PhongTro  pt ON lh.MaPhong   = pt.MaPhong
    JOIN NhaTro    nt ON pt.MaNhaTro  = nt.MaNhaTro
    JOIN NguoiDung nd ON nt.MaChuTro  = nd.MaNguoiDung
    WHERE lh.MaSinhVien = ?
    ORDER BY lh.NgayHen DESC, lh.GioHen DESC
";

$stmtList = $conn->prepare($sql);
$stmtList->bind_param('i', $mand);
$stmtList->execute();
$danhSach = $stmtList->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtList->close();

// Nhóm theo trạng thái để đếm badge tab
$demTrangThai = [
    'TatCa'     => count($danhSach),
    'ChoDuyet'  => 0,
    'DaXacNhan' => 0,
    'TuChoi'    => 0,
    'DaHuy'     => 0,
];
foreach ($danhSach as $item) {
    if (isset($demTrangThai[$item['TrangThai']])) {
        $demTrangThai[$item['TrangThai']]++;
    }
}

// Tab đang hiển thị (mặc định: TatCa)
$tabHienTai = $_GET['tab'] ?? 'TatCa';
if (!array_key_exists($tabHienTai, $demTrangThai)) {
    $tabHienTai = 'TatCa';
}

$danhSachLoc = ($tabHienTai === 'TatCa')
    ? $danhSach
    : array_values(array_filter($danhSach, fn($r) => $r['TrangThai'] === $tabHienTai));

// Hàm helper: trả về classes & nhãn badge cho từng trạng thái
function badgeTrangThai(string $ts): array
{
    return match ($ts) {
        'ChoDuyet'  => ['bg-status-amber-bg text-status-amber-text border border-amber-200',  'Chờ duyệt',   'schedule'],
        'DaXacNhan' => ['bg-status-green-bg text-status-green-text border border-green-200',  'Đã xác nhận', 'check_circle'],
        'TuChoi'    => ['bg-status-red-bg text-status-red-text border border-status-red-border', 'Từ chối',  'cancel'],
        'DaHuy'     => ['bg-status-gray-bg text-status-gray-text border border-border-warm',  'Đã hủy',      'do_not_disturb_on'],
        default     => ['bg-status-gray-bg text-status-gray-text border border-border-warm',  $ts,           'help'],
    };
}

include_once __DIR__ . '/../dungchung/header.php';
?>

<!-- ============================================================
     TRANG: LỊCH HẸN CỦA TÔI
     ============================================================ -->
<main class="max-w-[1280px] mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 page-view flex-1">

  <!-- Tiêu đề trang -->
  <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
      <h1 class="text-2xl font-bold text-text-main">Lịch hẹn của tôi</h1>
      <p class="text-sm text-text-sub mt-1">
        Danh sách tất cả yêu cầu đặt lịch xem phòng bạn đã gửi.
      </p>
    </div>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php"
       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-btn bg-accent text-white text-sm font-semibold transition-all duration-200 active:scale-[0.98] self-start sm:self-auto">
      <span class="material-symbols-outlined text-[16px]">search</span>
      Tìm phòng mới
    </a>
  </div>

  <!-- TABS TRẠNG THÁI -->
  <div class="flex gap-1 flex-wrap mb-6 border-b border-border-warm pb-0">
    <?php
    $tabs = [
        'TatCa'     => ['Tất cả',      'list_alt'],
        'ChoDuyet'  => ['Chờ duyệt',   'schedule'],
        'DaXacNhan' => ['Đã xác nhận', 'check_circle'],
        'TuChoi'    => ['Từ chối',     'cancel'],
        'DaHuy'     => ['Đã hủy',      'do_not_disturb_on'],
    ];
    foreach ($tabs as $key => [$label, $icon]):
        $isActive = ($tabHienTai === $key);
        $count    = $demTrangThai[$key];
        $activeClass = $isActive
            ? 'border-b-2 border-accent text-accent font-semibold'
            : 'text-text-sub hover:text-text-main';
    ?>
    <a href="?tab=<?= $key ?>"
       class="inline-flex items-center gap-1.5 px-3 py-2.5 text-sm transition-colors duration-150 <?= $activeClass ?>">
      <span class="material-symbols-outlined text-[16px]"><?= $icon ?></span>
      <?= $label ?>
      <?php if ($count > 0): ?>
        <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full text-[11px] font-bold
          <?= $isActive ? 'bg-accent text-white' : 'bg-bg-sub text-text-sub' ?>">
          <?= $count ?>
        </span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($danhSachLoc)): ?>
  <!-- =================== EMPTY STATE =================== -->
  <div class="flex flex-col items-center justify-center py-20 text-center">
    <div class="w-20 h-20 rounded-full bg-bg-sub border border-border-warm flex items-center justify-center mb-5">
      <span class="material-symbols-outlined text-4xl text-text-sub">event_busy</span>
    </div>
    <h2 class="text-lg font-bold text-text-main mb-2">Chưa có lịch hẹn nào</h2>
    <p class="text-sm text-text-sub max-w-xs leading-relaxed mb-6">
      <?php if ($tabHienTai === 'TatCa'): ?>
        Bạn chưa đặt lịch xem phòng nào. Hãy tìm phòng và đặt lịch ngay!
      <?php else: ?>
        Không có lịch hẹn nào có trạng thái này.
      <?php endif; ?>
    </p>
    <?php if ($tabHienTai === 'TatCa'): ?>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/danhsach.php"
       class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-btn bg-accent text-white text-sm font-semibold transition-all duration-200 active:scale-[0.98]">
      <span class="material-symbols-outlined text-[16px]">search</span>
      Khám phá phòng trọ
    </a>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <!-- =================== DANH SÁCH LỊCH HẸN =================== -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php foreach ($danhSachLoc as $lh):
        [$badgeClass, $badgeLabel, $badgeIcon] = badgeTrangThai($lh['TrangThai']);
        $coTheHuy = in_array($lh['TrangThai'], ['ChoDuyet', 'DaXacNhan']);
        $ngayHen  = $lh['NgayHen']  ? date('d/m/Y', strtotime($lh['NgayHen'])) : '—';
        $gioHen   = $lh['GioHen']   ? substr($lh['GioHen'], 0, 5)             : '—';
        $ngayTao  = $lh['NgayTao']  ? date('d/m/Y H:i', strtotime($lh['NgayTao'])) : '—';
    ?>
    <div class="bg-white border border-border-warm rounded-card card-hover overflow-hidden flex flex-col">
      <!-- Card header -->
      <div class="flex items-start justify-between gap-3 px-5 pt-5 pb-3">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap mb-1">
            <span class="text-base font-bold text-text-main truncate"><?= htmlspecialchars($lh['TenPhong']) ?></span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold <?= $badgeClass ?>">
              <span class="material-symbols-outlined text-[13px]"><?= $badgeIcon ?></span>
              <?= $badgeLabel ?>
            </span>
          </div>
          <p class="text-xs text-text-sub truncate"><?= htmlspecialchars($lh['TenNhaTro']) ?></p>
        </div>
      </div>

      <!-- Divider -->
      <div class="border-t border-border-warm mx-5"></div>

      <!-- Card body: thông tin chi tiết -->
      <div class="px-5 py-4 flex-1 space-y-2.5">

        <!-- Địa chỉ -->
        <div class="flex items-start gap-2 text-sm">
          <span class="material-symbols-outlined text-[16px] text-text-sub mt-0.5 shrink-0">location_on</span>
          <span class="text-text-sub leading-snug"><?= htmlspecialchars($lh['DiaChi']) ?></span>
        </div>

        <!-- Ngày & Giờ hẹn -->
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-1.5 text-sm tnum">
            <span class="material-symbols-outlined text-[16px] text-text-sub shrink-0">calendar_today</span>
            <span class="text-text-main font-semibold"><?= $ngayHen ?></span>
          </div>
          <div class="flex items-center gap-1.5 text-sm tnum">
            <span class="material-symbols-outlined text-[16px] text-text-sub shrink-0">schedule</span>
            <span class="text-text-main font-semibold"><?= $gioHen ?></span>
          </div>
        </div>

        <!-- Chủ trọ -->
        <div class="flex items-center gap-2 text-sm">
          <span class="material-symbols-outlined text-[16px] text-text-sub shrink-0">person</span>
          <span class="text-text-sub">Chủ trọ:</span>
          <span class="text-text-main font-medium"><?= htmlspecialchars($lh['TenChuTro']) ?></span>
        </div>

        <!-- SĐT chủ trọ -->
        <?php if (!empty($lh['SoDienThoai'])): ?>
        <div class="flex items-center gap-2 text-sm tnum">
          <span class="material-symbols-outlined text-[16px] text-text-sub shrink-0">call</span>
          <a href="tel:<?= htmlspecialchars($lh['SoDienThoai']) ?>"
             class="text-accent font-medium hover:underline">
            <?= htmlspecialchars($lh['SoDienThoai']) ?>
          </a>
        </div>
        <?php endif; ?>

        <!-- Ghi chú -->
        <?php if (!empty($lh['GhiChu'])): ?>
        <div class="flex items-start gap-2 text-sm">
          <span class="material-symbols-outlined text-[16px] text-text-sub mt-0.5 shrink-0">notes</span>
          <span class="text-text-sub italic leading-snug"><?= htmlspecialchars($lh['GhiChu']) ?></span>
        </div>
        <?php endif; ?>

      </div>

      <!-- Card footer -->
      <div class="flex items-center justify-between gap-3 px-5 py-3 bg-bg-sub border-t border-border-warm">
        <span class="text-[11px] text-text-sub tnum">Đặt lịch: <?= $ngayTao ?></span>

        <?php if ($coTheHuy): ?>
        <form method="POST"
              action="/DoAn_TimNhaTroSV_QLDAPM/sinhvien/lichhencuatoi.php"
              class="inline">
          <input type="hidden" name="ma_lich_hen" value="<?= (int)$lh['MaLichHen'] ?>">
          <button type="submit"
                  onclick="return confirm('Bạn có chắc muốn hủy lịch hẹn này?')"
                  class="inline-flex items-center gap-1 px-3 py-1.5 rounded-btn
                         border border-status-red-border bg-status-red-bg text-status-red-text
                         text-xs font-semibold transition-all duration-150 active:scale-[0.98]
                         hover:brightness-95">
            <span class="material-symbols-outlined text-[14px]">event_busy</span>
            Hủy lịch hẹn
          </button>
        </form>
        <?php else: ?>
        <span class="text-[11px] text-text-sub">#<?= (int)$lh['MaLichHen'] ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>

<?php include_once __DIR__ . '/../dungchung/footer.php'; ?>
