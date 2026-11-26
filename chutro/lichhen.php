<?php
/**
 * chutro/lichhen.php — Quản lý toàn bộ lịch hẹn xem phòng của Chủ trọ
 * Phân hệ Chủ trọ — CampusNest AGU
 */
$tieuDeTrang = 'Quản lý lịch hẹn xem phòng — CampusNest AGU';
$trangHienTai = 'landlord';

require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

$base_url = '/DoAn_TimNhaTroSV_QLDAPM';

yeuCauDangNhap('Vui lòng đăng nhập để xem lịch hẹn.');
$nd = nguoiDungHienTai();
$mand = (int)$nd['mannd'];

if (!in_array($nd['vaitro'], ['ChuTro', 'QuanTriVien'])) {
    header("Location: {$base_url}/index.php");
    exit();
}

// 1. Nhận tab lọc từ URL: 'all' | 'ChoDuyet' | 'DaXacNhan' | 'TuChoi'
$tab = $_GET['tab'] ?? 'ChoDuyet';
if (!in_array($tab, ['all', 'ChoDuyet', 'DaXacNhan', 'TuChoi'])) {
    $tab = 'ChoDuyet';
}

$tuKhoa = trim($_GET['q'] ?? '');

// 2. Thống kê số lượng theo từng trạng thái để làm badge trên các Tab
$stmtCount = $conn->prepare("
    SELECT 
        COUNT(*) AS Tong,
        SUM(CASE WHEN lh.TrangThai = 'ChoDuyet' THEN 1 ELSE 0 END) AS ChoDuyet,
        SUM(CASE WHEN lh.TrangThai = 'DaXacNhan' THEN 1 ELSE 0 END) AS DaXacNhan,
        SUM(CASE WHEN lh.TrangThai = 'TuChoi' THEN 1 ELSE 0 END) AS TuChoi
    FROM LichHen lh
    JOIN PhongTro p ON lh.MaPhong = p.MaPhong
    JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
    WHERE n.MaChuTro = ?
");
$stmtCount->bind_param('i', $mand);
$stmtCount->execute();
$counts = $stmtCount->get_result()->fetch_assoc();
$stmtCount->close();

$cChoDuyet  = (int)($counts['ChoDuyet'] ?? 0);
$cDaXacNhan = (int)($counts['DaXacNhan'] ?? 0);
$cTuChoi    = (int)($counts['TuChoi'] ?? 0);
$cTong      = (int)($counts['Tong'] ?? 0);

// 3. Truy vấn danh sách toàn bộ lịch hẹn của chủ trọ này
$sql = "
    SELECT 
        lh.*, 
        sv.HoTen AS TenSV, 
        sv.SoDienThoai AS SdtSV, 
        sv.Email AS EmailSV,
        p.TenPhong, 
        p.GiaThue,
        n.TenNhaTro
    FROM LichHen lh
    JOIN NguoiDung sv ON lh.MaSinhVien = sv.MaNguoiDung
    JOIN PhongTro p ON lh.MaPhong = p.MaPhong
    JOIN NhaTro n ON p.MaNhaTro = n.MaNhaTro
    WHERE n.MaChuTro = ?
";
$params = [$mand];
$types  = 'i';

if ($tuKhoa !== '') {
    $sql .= " AND (sv.HoTen LIKE ? OR sv.SoDienThoai LIKE ? OR p.TenPhong LIKE ?)";
    $like = '%' . $tuKhoa . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

$sql .= " ORDER BY lh.NgayHen DESC, lh.GioHen DESC";

$stmtL = $conn->prepare($sql);
$stmtL->bind_param($types, ...$params);
$stmtL->execute();
$dsLich = $stmtL->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtL->close();

include_once __DIR__ . '/../dungchung/header.php';
?>

<div class="max-w-[1100px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 flex-1 min-h-[680px]">

  <!-- Breadcrumb -->
  <nav class="flex items-center gap-1.5 text-xs text-text-sub">
    <a href="<?= $base_url ?>/index.php" class="hover:text-accent transition-colors">Trang chủ</a>
    <span>/</span>
    <a href="<?= $base_url ?>/chutro.php" class="hover:text-accent transition-colors">Bảng điều khiển</a>
    <span>/</span>
    <span class="text-text-main font-semibold">Sổ tay lịch hẹn xem phòng</span>
  </nav>

  <!-- Flash Notification -->
  <?php if (function_exists('hienThongBao')) hienThongBao(); ?>

  <!-- Tiêu đề trang -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-bg-main p-6 rounded-card border border-border-warm shadow-sm">
    <div class="flex items-center gap-3">
      <div class="w-12 h-12 rounded-card bg-accent/10 text-accent flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-2xl">book_online</span>
      </div>
      <div>
        <h1 class="text-xl font-bold text-text-main">Sổ tay theo dõi lịch hẹn xem phòng</h1>
        <p class="text-xs text-text-sub mt-0.5">Theo dõi lịch hẹn sắp tới của sinh viên, duyệt hẹn và ghi chú lời dặn dò chu đáo.</p>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <a href="<?= $base_url ?>/chutro.php" class="px-4 py-2 rounded-btn border border-border-warm bg-bg-sub text-text-main font-semibold text-xs hover:bg-border-warm/50 transition-all flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">dashboard</span> Tổng quan
      </a>
      <a href="<?= $base_url ?>/chutro/danhsachphong.php" class="px-4 py-2 rounded-btn border border-border-warm bg-bg-sub text-text-main font-semibold text-xs hover:bg-border-warm/50 transition-all flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">meeting_room</span> Quản lý phòng
      </a>
    </div>
  </div>

  <!-- Thanh Tab phân loại trạng thái (Chuyển tab tức thì, không giật trang) -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-border-warm pb-1">
    <div class="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-none">
      <button type="button" onclick="switchTab('ChoDuyet')" id="btn_tab_ChoDuyet"
              class="tab-btn px-4 py-2 rounded-btn text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer bg-accent text-white shadow-sm">
        <span class="material-symbols-outlined text-sm">pending_actions</span>
        <span>Chờ bạn duyệt</span>
        <?php if ($cChoDuyet > 0): ?>
          <span class="tab-badge px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-white text-accent">
            <?= $cChoDuyet ?>
          </span>
        <?php endif; ?>
      </button>

      <button type="button" onclick="switchTab('DaXacNhan')" id="btn_tab_DaXacNhan"
              class="tab-btn px-4 py-2 rounded-btn text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer bg-bg-main text-text-sub hover:text-text-main border border-border-warm">
        <span class="material-symbols-outlined text-sm">event_available</span>
        <span>Lịch đã xác nhận</span>
        <?php if ($cDaXacNhan > 0): ?>
          <span class="tab-badge px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-status-green-bg text-accent">
            <?= $cDaXacNhan ?>
          </span>
        <?php endif; ?>
      </button>

      <button type="button" onclick="switchTab('TuChoi')" id="btn_tab_TuChoi"
              class="tab-btn px-4 py-2 rounded-btn text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer bg-bg-main text-text-sub hover:text-text-main border border-border-warm">
        <span class="material-symbols-outlined text-sm">event_busy</span>
        <span>Đã từ chối</span>
        <?php if ($cTuChoi > 0): ?>
          <span class="tab-badge px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-bg-sub text-text-sub">
            <?= $cTuChoi ?>
          </span>
        <?php endif; ?>
      </button>

      <button type="button" onclick="switchTab('all')" id="btn_tab_all"
              class="tab-btn px-3.5 py-2 rounded-btn text-xs font-semibold flex items-center gap-1 transition-colors cursor-pointer bg-bg-main text-text-sub hover:text-text-main border border-border-warm">
        <span>Tất cả (<?= $cTong ?>)</span>
      </button>
    </div>

    <!-- Ô tìm kiếm lịch hẹn -->
    <form method="GET" class="flex items-center gap-2">
      <div class="relative">
        <input type="text" name="q" value="<?= htmlspecialchars($tuKhoa) ?>" 
               placeholder="Tìm tên sinh viên, SĐT, phòng..." 
               class="w-64 text-xs pl-8 pr-3 py-2 rounded-btn border border-border-warm bg-bg-main focus:outline-accent text-text-main">
        <span class="material-symbols-outlined text-[16px] text-text-sub absolute left-2.5 top-1/2 -translate-y-1/2">search</span>
      </div>
      <button type="submit" class="px-3.5 py-2 rounded-btn bg-accent text-white text-xs font-semibold hover:opacity-90">Tìm</button>
      <?php if ($tuKhoa !== ''): ?>
      <a href="?" class="px-2.5 py-2 rounded-btn border border-border-warm bg-bg-sub text-xs text-text-sub hover:text-text-main" title="Xóa tìm kiếm">✕</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Khung danh sách lịch hẹn -->
  <div>
    <?php if (empty($dsLich)): ?>
      <div class="py-16 text-center space-y-3 bg-bg-main rounded-card border border-border-warm border-dashed">
        <span class="material-symbols-outlined text-5xl text-text-sub/40 block mx-auto">calendar_today</span>
        <p class="text-sm font-bold text-text-main">Không có lịch hẹn nào</p>
        <p class="text-xs text-text-sub max-w-sm mx-auto">Khi sinh viên đặt lịch xem phòng thực tế, yêu cầu sẽ xuất hiện ngay tại đây để bạn duyệt.</p>
      </div>
    <?php else: ?>
      <div class="space-y-3.5" id="lich_list_container">
        <?php foreach ($dsLich as $l): 
          $ngayHenF = date('d/m/Y', strtotime($l['NgayHen']));
          $gioHenF  = substr($l['GioHen'], 0, 5);
          $sdtRaw   = preg_replace('/[^0-9]/', '', $l['SdtSV'] ?? '');
          $isPast   = (strtotime($l['NgayHen'] . ' ' . $l['GioHen']) < time());
        ?>
        <div class="card-lich-item bg-bg-main rounded-card border border-border-warm p-5 shadow-2xs hover:border-accent/40 transition-colors space-y-4" data-status="<?= $l['TrangThai'] ?>">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-border-warm/60 pb-3">
          <div class="space-y-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-base font-bold text-text-main"><?= htmlspecialchars($l['TenSV']) ?></span>
              <span class="text-xs text-text-sub font-medium">• Sinh viên đặt lịch</span>

              <?php if ($sdtRaw): ?>
              <!-- Cụm liên hệ sinh viên đặt ngay cạnh tên sinh viên -->
              <div class="flex items-center gap-1.5 ml-1">
                <span class="text-xs text-text-sub font-semibold">(SĐT: <?= htmlspecialchars($l['SdtSV']) ?>)</span>
                <a href="tel:<?= $sdtRaw ?>" class="px-2 py-0.5 rounded bg-bg-sub border border-border-warm hover:border-accent text-[11px] font-semibold text-text-main inline-flex items-center gap-1 transition-colors" title="Gọi cho sinh viên">
                  <span class="material-symbols-outlined text-[12px] text-accent">phone</span> Gọi
                </a>
                <a href="https://zalo.me/<?= $sdtRaw ?>" target="_blank" rel="noopener noreferrer" class="px-2 py-0.5 rounded bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100 text-[11px] font-semibold inline-flex items-center gap-1 transition-colors" title="Nhắn Zalo cho sinh viên">
                  Chat Zalo
                </a>
              </div>
              <?php endif; ?>
            </div>

            <p class="text-xs text-accent font-semibold flex items-center gap-1">
              <span class="material-symbols-outlined text-[15px]">meeting_room</span>
              <span>Phòng: <?= htmlspecialchars($l['TenPhong']) ?></span>
              <span class="text-text-sub font-normal">(<?= htmlspecialchars($l['TenNhaTro']) ?> — <?= number_format($l['GiaThue'], 0, ',', '.') ?> đ/tháng)</span>
            </p>
          </div>

          <!-- Trạng thái hẹn -->
          <div class="flex items-center gap-2 shrink-0">
            <?php if ($l['TrangThai'] === 'ChoDuyet'): ?>
              <span class="px-3 py-1 rounded-full bg-status-amber-bg text-status-amber-text text-xs font-bold inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">schedule</span> Chờ duyệt
              </span>
            <?php elseif ($l['TrangThai'] === 'DaXacNhan'): ?>
              <span class="px-3 py-1 rounded-full bg-status-green-bg text-accent text-xs font-bold inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">check_circle</span> Đã xác nhận
              </span>
            <?php else: ?>
              <span class="px-3 py-1 rounded-full bg-status-red-bg text-status-red-text text-xs font-bold inline-flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">cancel</span> Đã từ chối
              </span>
            <?php endif; ?>

            <?php if ($isPast && $l['TrangThai'] === 'DaXacNhan'): ?>
              <span class="px-2 py-0.5 rounded bg-bg-sub border border-border-warm text-text-sub text-[10px] font-medium">Đã qua giờ hẹn</span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Chi tiết thời gian & Hai luồng lời nhắn riêng biệt -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
          <!-- Cột trái: Giờ hẹn & Lời nhắn của sinh viên -->
          <div class="bg-bg-sub/40 p-3 rounded-card border border-border-warm/60 space-y-2">
            <div class="flex items-center gap-2 text-text-main font-semibold">
              <span class="material-symbols-outlined text-accent text-base">alarm</span>
              <span>Khung giờ sinh viên hẹn đến:</span>
              <span class="text-accent text-sm font-bold tnum"><?= $gioHenF ?> — Ngày <?= $ngayHenF ?></span>
            </div>

            <div class="pt-1.5 border-t border-border-warm/40 text-text-sub">
              <?php if (!empty($l['GhiChu'])): ?>
                <strong class="text-text-main">Sinh viên nhắn:</strong> "<?= htmlspecialchars($l['GhiChu']) ?>"
              <?php else: ?>
                <span class="italic">Sinh viên không để lại ghi chú thêm.</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Cột phải: Lời dặn dò phản hồi của Chủ trọ -->
          <div class="bg-bg-sub/40 p-3 rounded-card border border-border-warm/60 space-y-2">
            <div class="flex items-center gap-2 text-text-main font-semibold">
              <span class="material-symbols-outlined text-accent text-base">mark_chat_read</span>
              <span>Phản hồi từ Chủ trọ:</span>
            </div>

            <div class="pt-1.5 border-t border-border-warm/40">
              <?php if (!empty($l['PhanHoiChuTro'])): ?>
                <p class="text-text-main">
                  <span class="text-accent font-semibold">Lời dặn của bạn:</span> "<?= htmlspecialchars($l['PhanHoiChuTro']) ?>"
                </p>
              <?php else: ?>
                <span class="text-text-sub italic">Bạn chưa gửi lời dặn dò riêng cho lịch hẹn này.</span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Các nút hành động nếu lịch hẹn còn ở trạng thái Chờ duyệt -->
        <?php if ($l['TrangThai'] === 'ChoDuyet'): ?>
        <div class="flex items-center justify-end gap-2.5 pt-1">
          <button type="button" 
                  onclick="openModalLich(<?= $l['MaLichHen'] ?>, 'TuChoi', '<?= htmlspecialchars(addslashes($l['TenSV'])) ?>', '<?= htmlspecialchars(addslashes($l['TenPhong'])) ?>')" 
                  class="px-4 py-2 rounded-btn border border-status-red-border bg-status-red-bg/50 hover:bg-status-red-bg text-status-red-text font-semibold text-xs transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-[14px]">close</span> Từ chối lịch
          </button>
          <button type="button" 
                  onclick="openModalLich(<?= $l['MaLichHen'] ?>, 'XacNhan', '<?= htmlspecialchars(addslashes($l['TenSV'])) ?>', '<?= htmlspecialchars(addslashes($l['TenPhong'])) ?>')" 
                  class="px-5 py-2 rounded-btn bg-accent text-white font-semibold text-xs hover:opacity-90 transition-opacity flex items-center gap-1 shadow-sm">
            <span class="material-symbols-outlined text-[14px]">check</span> Xác nhận hẹn & Gửi lời dặn
          </button>
        </div>
        <?php endif; ?>

      </div>
      <?php endforeach; ?>
    </div>

    <!-- Khối thông báo khi tab không có lịch hẹn nào -->
    <div id="tab_empty_notice" class="hidden py-14 text-center space-y-3 bg-bg-main rounded-card border border-border-warm border-dashed">
      <span class="material-symbols-outlined text-4xl text-text-sub/40 block mx-auto">event_available</span>
      <p class="text-sm font-bold text-text-main" id="empty_notice_text">Không có lịch hẹn nào trong mục này</p>
      <p class="text-xs text-text-sub max-w-sm mx-auto">Bạn có thể chuyển sang các tab khác để theo dõi tiến độ xem phòng của sinh viên.</p>
    </div>
  </div>
  <?php endif; ?>

</div>

<!-- MODAL DUYỆT / TỪ CHỐI KÈM LỜI DẶN DÒ -->
<div id="modal_duyet_lich" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-[2px] flex items-center justify-center p-4">
  <div class="bg-bg-main w-full max-w-md rounded-card border border-border-warm shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
    <div class="p-4 border-b border-border-warm flex items-center justify-between">
      <h3 id="modal_lich_title" class="text-sm font-bold text-text-main flex items-center gap-2"></h3>
      <button type="button" onclick="closeModalLich()" class="text-text-sub hover:text-text-main">
        <span class="material-symbols-outlined text-lg">close</span>
      </button>
    </div>

    <form method="POST" action="<?= $base_url ?>/chutro/duyetlichhen.php" class="p-5 space-y-4">
      <input type="hidden" id="modal_ma_lich" name="ma_lich" value="">
      <input type="hidden" id="modal_hanh_dong" name="hanh_dong" value="">

      <div class="text-xs text-text-sub" id="modal_lich_info"></div>

      <div>
        <label class="block text-xs font-semibold text-text-main mb-1.5">
          Lời nhắn / Lời dặn dò gửi đến sinh viên:
        </label>
        <textarea id="modal_phan_hoi" name="phan_hoi" rows="3" 
                  class="w-full text-xs p-3 rounded-btn border border-border-warm bg-bg-sub/40 focus:outline-accent resize-none"
                  placeholder="VD: Nhớ đến đúng giờ nhé em / Cô có mặt ở cổng nhà trọ..."></textarea>
        <div class="flex items-center gap-1.5 mt-2 flex-wrap">
          <span class="text-[11px] text-text-sub font-semibold">Gợi ý nhanh:</span>
          <button type="button" onclick="setQuickMsg('Đã xác nhận. Em đến trước 10 phút bấm chuông nhé!')" class="text-[10px] px-2 py-0.5 rounded bg-bg-sub border border-border-warm hover:border-accent">Đến đúng giờ</button>
          <button type="button" onclick="setQuickMsg('Hôm đó cô bận việc đột xuất, em vui lòng chọn lịch hẹn khác giúp cô nhé!')" class="text-[10px] px-2 py-0.5 rounded bg-bg-sub border border-border-warm hover:border-accent">Bận việc</button>
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-border-warm">
        <button type="button" onclick="closeModalLich()" class="px-4 py-2 rounded-btn border border-border-warm text-xs font-semibold text-text-main hover:bg-bg-sub">Hủy bỏ</button>
        <button type="submit" id="modal_submit_btn" class="px-5 py-2 rounded-btn text-white text-xs font-semibold transition-opacity"></button>
      </div>
    </form>
  </div>
</div>

<script>
// Chuyển Tab cực mượt bằng JS - Không reload trang, không giật màn hình
function switchTab(status) {
  const tabs = ['ChoDuyet', 'DaXacNhan', 'TuChoi', 'all'];
  tabs.forEach(t => {
    const btn = document.getElementById(`btn_tab_${t}`);
    if (!btn) return;
    const badge = btn.querySelector('.tab-badge');

    if (t === status) {
      btn.className = 'tab-btn px-4 py-2 rounded-btn text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer bg-accent text-white shadow-sm';
      if (badge) {
        badge.className = 'tab-badge px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-white text-accent';
      }
    } else {
      btn.className = 'tab-btn px-4 py-2 rounded-btn text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer bg-bg-main text-text-sub hover:text-text-main border border-border-warm';
      if (badge) {
        if (t === 'ChoDuyet') badge.className = 'tab-badge px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-status-amber-bg text-status-amber-text';
        if (t === 'DaXacNhan') badge.className = 'tab-badge px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-status-green-bg text-accent';
        if (t === 'TuChoi') badge.className = 'tab-badge px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-bg-sub text-text-sub';
      }
    }
  });

  const cards = document.querySelectorAll('.card-lich-item');
  let visibleCount = 0;

  cards.forEach(card => {
    const cardStatus = card.getAttribute('data-status');
    if (status === 'all' || cardStatus === status) {
      card.classList.remove('hidden');
      visibleCount++;
    } else {
      card.classList.add('hidden');
    }
  });

  const emptyNotice = document.getElementById('tab_empty_notice');
  const emptyText = document.getElementById('empty_notice_text');
  if (emptyNotice) {
    if (visibleCount === 0) {
      emptyNotice.classList.remove('hidden');
      if (status === 'ChoDuyet') emptyText.textContent = 'Hiện tại không có lịch hẹn nào đang chờ bạn duyệt.';
      else if (status === 'DaXacNhan') emptyText.textContent = 'Chưa có lịch hẹn nào đã xác nhận.';
      else if (status === 'TuChoi') emptyText.textContent = 'Không có lịch hẹn nào bị từ chối.';
      else emptyText.textContent = 'Không tìm thấy lịch hẹn nào phù hợp.';
    } else {
      emptyNotice.classList.add('hidden');
    }
  }
}

// Khởi chạy tab ban đầu (mặc định mở Chờ duyệt, hoặc nếu rỗng thì mở Đã xác nhận)
document.addEventListener('DOMContentLoaded', () => {
  const cChoDuyet = <?= $cChoDuyet ?>;
  const cDaXacNhan = <?= $cDaXacNhan ?>;
  if (cChoDuyet === 0 && cDaXacNhan > 0) {
    switchTab('DaXacNhan');
  } else {
    switchTab('ChoDuyet');
  }
});

function openModalLich(id, action, tenSV, tenPhong) {
  document.getElementById('modal_ma_lich').value = id;
  document.getElementById('modal_hanh_dong').value = action;
  const titleEl = document.getElementById('modal_lich_title');
  const infoEl = document.getElementById('modal_lich_info');
  const submitBtn = document.getElementById('modal_submit_btn');
  const phanHoi = document.getElementById('modal_phan_hoi');

  if (action === 'XacNhan') {
    titleEl.innerHTML = '<span class="material-symbols-outlined text-accent text-lg">check_circle</span> Xác nhận lịch hẹn';
    infoEl.innerHTML = `Xác nhận lịch xem phòng <strong>${tenPhong}</strong> của bạn sinh viên <strong>${tenSV}</strong>.`;
    submitBtn.textContent = 'Xác nhận lịch hẹn';
    submitBtn.className = 'px-5 py-2 rounded-btn bg-accent text-white text-xs font-semibold hover:opacity-90';
    phanHoi.value = 'Đã xác nhận lịch hẹn. Em đến đúng giờ nhé!';
  } else {
    titleEl.innerHTML = '<span class="material-symbols-outlined text-rose-600 text-lg">cancel</span> Từ chối lịch hẹn';
    infoEl.innerHTML = `Từ chối lịch xem phòng <strong>${tenPhong}</strong> của bạn sinh viên <strong>${tenSV}</strong>.`;
    submitBtn.textContent = 'Từ chối lịch';
    submitBtn.className = 'px-5 py-2 rounded-btn bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700';
    phanHoi.value = 'Rất tiếc hiện tại phòng đã có khách cọc / Cô bận vào khung giờ này.';
  }

  document.getElementById('modal_duyet_lich').classList.remove('hidden');
}

function closeModalLich() {
  document.getElementById('modal_duyet_lich').classList.add('hidden');
}

function setQuickMsg(msg) {
  document.getElementById('modal_phan_hoi').value = msg;
}
</script>

<?php include_once __DIR__ . '/../dungchung/footer.php'; ?>
