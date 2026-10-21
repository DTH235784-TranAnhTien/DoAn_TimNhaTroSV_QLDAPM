<?php
$tieuDeTrang = 'Dang tin phong tro moi -- CampusNest AGU';
$trangHienTai = 'landlord';
require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

yeuCauDangNhap('Vui long dang nhap de dang tin phong tro.');
$nd = nguoiDungHienTai();
$mand = (int)$nd['mannd'];

if (!in_array($nd['vaitro'], ['ChuTro', 'QuanTriVien'])) {
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/index.php');
    exit();
}

// Lay danh sach nha tro cua chu tro
$stmtNT = $conn->prepare("SELECT MaNhaTro, TenNhaTro, DiaChi FROM NhaTro WHERE MaChuTro = ?");
$stmtNT->bind_param('i', $mand);
$stmtNT->execute();
$dsNhaTro = $stmtNT->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtNT->close();

$loi = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maNhaTro = (int)($_POST['ma_nha_tro'] ?? 0);
    $tenNhaTro = trim($_POST['ten_nha_tro'] ?? '');
    $diaChi = trim($_POST['dia_chi'] ?? '');

    $tenPhong = trim($_POST['ten_phong'] ?? '');
    $giaThue = (float)($_POST['gia_thue'] ?? 0);
    $dienTich = (float)($_POST['dien_tich'] ?? 0);
    $tienDien = (float)($_POST['tien_dien'] ?? 0);
    $tienNuoc = (float)($_POST['tien_nuoc'] ?? 0);
    $tienWifi = (float)($_POST['tien_wifi'] ?? 0);
    $moTa = trim($_POST['mo_ta'] ?? '');

    if (!$tenPhong) $loi[] = 'Vui lòng nhập tên phòng trọ.';
    if ($giaThue <= 0) $loi[] = 'Giá thuê phải lớn hơn 0.';

    // Kiểm tra ảnh tải lên từ máy tính
    $coTepAnh = isset($_FILES['anh_phong']) && (
        (is_array($_FILES['anh_phong']['name']) && !empty($_FILES['anh_phong']['name'][0])) ||
        (!is_array($_FILES['anh_phong']['name']) && !empty($_FILES['anh_phong']['name']))
    );

    $viDo = !empty($_POST['vi_do']) ? (float)$_POST['vi_do'] : null;
    $kinhDo = !empty($_POST['kinh_do']) ? (float)$_POST['kinh_do'] : null;

    // Nếu chủ trọ chưa có nhà trọ hoặc tạo nhà trọ mới
    if ($maNhaTro <= 0) {
        if (!$tenNhaTro || !$diaChi) {
            $loi[] = 'Vui lòng nhập tên nhà trọ và địa chỉ.';
        } else {
            if ($viDo && $kinhDo) {
                $stmtNewNT = $conn->prepare("INSERT INTO NhaTro (MaChuTro, TenNhaTro, DiaChi, ViDo, KinhDo, NgayTao) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmtNewNT->bind_param('issdd', $mand, $tenNhaTro, $diaChi, $viDo, $kinhDo);
            } else {
                $stmtNewNT = $conn->prepare("INSERT INTO NhaTro (MaChuTro, TenNhaTro, DiaChi, NgayTao) VALUES (?, ?, ?, NOW())");
                $stmtNewNT->bind_param('iss', $mand, $tenNhaTro, $diaChi);
            }
            if ($stmtNewNT->execute()) {
                $maNhaTro = $stmtNewNT->insert_id;
            } else {
                $loi[] = 'Không thể tạo nhà trọ mới.';
            }
            $stmtNewNT->close();
        }
    }

    if (empty($loi) && $maNhaTro > 0) {
        $trangThaiDuyet = ($nd['vaitro'] === 'QuanTriVien') ? 'DaDuyet' : 'ChoDuyet';
        $stmtP = $conn->prepare("
            INSERT INTO PhongTro (MaNhaTro, TenPhong, GiaThue, DienTich, TienDien, TienNuoc, TienWifi, MoTa, TrangThai, TrangThaiDuyet, NgayDang)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ConPhong', ?, NOW())
        ");
        $stmtP->bind_param('isddddsss', $maNhaTro, $tenPhong, $giaThue, $dienTich, $tienDien, $tienNuoc, $tienWifi, $moTa, $trangThaiDuyet);
        if ($stmtP->execute()) {
            $maPhongMoi = $stmtP->insert_id;
            $stmtP->close();

            // Lưu các ảnh được tải lên từ máy tính của chủ trọ
            if ($coTepAnh) {
                require_once __DIR__ . '/../cauhinh/upload_helper.php';
                $dsAnhDaLuu = xuLyUploadAnhPhongTro($_FILES['anh_phong'], $maPhongMoi);
                if (!empty($dsAnhDaLuu)) {
                    $stmtAnh = $conn->prepare("INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh) VALUES (?, ?, ?)");
                    foreach ($dsAnhDaLuu as $idx => $duongDan) {
                        $laChinh = ($idx === 0) ? 1 : 0;
                        $stmtAnh->bind_param('isi', $maPhongMoi, $duongDan, $laChinh);
                        $stmtAnh->execute();
                    }
                    $stmtAnh->close();
                }
            }

            datThongBao('success', 'Đăng tin phòng trọ thành công! ' . ($trangThaiDuyet === 'ChoDuyet' ? 'Tin đăng đang chờ Ban quản trị duyệt.' : ''));
            header('Location: /DoAn_TimNhaTroSV_QLDAPM/chutro.php');
            exit();
        } else {
            $loi[] = 'Không thể lưu tin phòng trọ. Vui lòng thử lại.';
        }
    }
}

include_once __DIR__ . '/../dungchung/header.php';
?>

<div class="max-w-[760px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
  <!-- Breadcrumb -->
  <nav class="flex items-center gap-1.5 text-xs text-text-sub">
    <a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="hover:text-text-main">Trang chinh</a>
    <span class="material-symbols-outlined text-[14px]">chevron_right</span>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro.php" class="hover:text-text-main">Quan ly phong tro</a>
    <span class="material-symbols-outlined text-[14px]">chevron_right</span>
    <span class="text-text-main font-semibold">Dang tin phong moi</span>
  </nav>

  <div class="bg-bg-main rounded-card border border-border-warm p-6 space-y-5">
    <div>
      <h1 class="text-xl font-bold text-text-main">Dang tin phong tro moi</h1>
      <p class="text-xs text-text-sub mt-1">Dien thong tin chi tiet ve phong tro de sinh vien de dang tim kiem va dat lich hen.</p>
    </div>

    <?php if (!empty($loi)): ?>
    <div class="p-3.5 rounded-card bg-status-red-bg border border-status-red-border text-status-red-text text-xs space-y-1">
      <?php foreach ($loi as $l): ?>
      <p>. <?= htmlspecialchars($l) ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <!-- Thong tin Nha Tro -->
      <div class="space-y-3 pb-4 border-b border-border-warm">
        <h2 class="text-sm font-bold text-text-main flex items-center gap-1.5">
          <span class="material-symbols-outlined text-accent">apartment</span>Thông tin nhà trọ / dãy trọ
        </h2>

        <?php if (!empty($dsNhaTro)): ?>
        <div>
          <label class="block text-xs font-semibold text-text-main mb-1">Chọn nhà trọ đã có</label>
          <select name="ma_nha_tro" id="sel_nha_tro" onchange="toggleNewNhaTro(this.value)" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
            <?php foreach ($dsNhaTro as $nt): ?>
            <option value="<?= $nt['MaNhaTro'] ?>"><?= htmlspecialchars($nt['TenNhaTro']) ?> (<?= htmlspecialchars($nt['DiaChi']) ?>)</option>
            <?php endforeach; ?>
            <option value="0">+ Thêm nhà trọ mới...</option>
          </select>
        </div>
        <?php endif; ?>

        <div id="new_nha_tro_box" class="<?= !empty($dsNhaTro) ? 'hidden' : '' ?> space-y-3">
          <div>
            <label class="block text-xs font-semibold text-text-main mb-1">Tên nhà trọ / dãy trọ <span class="text-rose-500">*</span></label>
            <input type="text" name="ten_nha_tro" placeholder="VD: Nhà trọ Hướng Dương" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
          </div>
          <div>
            <label class="block text-xs font-semibold text-text-main mb-1">Địa chỉ <span class="text-rose-500">*</span></label>
            <input type="text" name="dia_chi" placeholder="VD: Hẻm 12 Ung Văn Khiêm, P. Đông Xuyên, TP. Long Xuyên" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
          </div>
          <div class="grid grid-cols-2 gap-3 tnum">
            <div>
              <label class="block text-xs font-semibold text-text-main mb-1">Tọa độ Vĩ độ (Vĩ độ bản đồ)</label>
              <input type="number" step="0.000001" id="in_vido" name="vi_do" placeholder="VD: 10.3720" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
            </div>
            <div>
              <label class="block text-xs font-semibold text-text-main mb-1">Tọa độ Kinh độ (Kinh độ bản đồ)</label>
              <input type="number" step="0.000001" id="in_kinhdo" name="kinh_do" placeholder="VD: 105.4326" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
            </div>
          </div>
          <div class="flex items-center gap-2 text-xs">
            <span class="text-text-sub font-semibold">Chọn nhanh vị trí trên bản đồ:</span>
            <button type="button" onclick="setGeo(10.3720, 105.4326)" class="px-2.5 py-0.5 rounded border border-border-warm bg-bg-main hover:border-accent text-xs">Gần Khu A (Đông Xuyên)</button>
            <button type="button" onclick="setGeo(10.3789, 105.4385)" class="px-2.5 py-0.5 rounded border border-border-warm bg-bg-main hover:border-accent text-xs">Gần Khu B (Mỹ Xuyên)</button>
          </div>
        </div>
      </div>

      <!-- Thong tin Phong -->
      <div class="space-y-3 pb-4 border-b border-border-warm">
        <h2 class="text-sm font-bold text-text-main flex items-center gap-1.5">
          <span class="material-symbols-outlined text-accent">meeting_room</span>Chi tiết phòng trọ
        </h2>

        <div>
          <label class="block text-xs font-semibold text-text-main mb-1">Tên / Số phòng <span class="text-rose-500">*</span></label>
          <input type="text" name="ten_phong" required placeholder="VD: Phòng 01 (Gác đúc kiên cố)" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 tnum">
          <div>
            <label class="block text-xs font-semibold text-text-main mb-1">Giá thuê (đ/tháng) <span class="text-rose-500">*</span></label>
            <input type="number" name="gia_thue" required min="100000" step="50000" placeholder="1200000" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
          </div>
          <div>
            <label class="block text-xs font-semibold text-text-main mb-1">Diện tích (m²)</label>
            <input type="number" name="dien_tich" min="5" max="200" step="0.5" placeholder="18" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 tnum">
          <div>
            <label class="block text-xs font-semibold text-text-main mb-1">Tiền điện (đ/kWh)</label>
            <input type="number" name="tien_dien" min="0" step="100" placeholder="3000" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
          </div>
          <div>
            <label class="block text-xs font-semibold text-text-main mb-1">Tiền nước (đ/khối)</label>
            <input type="number" name="tien_nuoc" min="0" step="500" placeholder="8000" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
          </div>
          <div>
            <label class="block text-xs font-semibold text-text-main mb-1">Tiền wifi (đ/tháng)</label>
            <input type="number" name="tien_wifi" min="0" step="10000" placeholder="50000" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
          </div>
        </div>

        <!-- Tải ảnh từ máy tính (không dùng URL) -->
        <div class="space-y-2">
          <label class="block text-xs font-semibold text-text-main">
            Hình ảnh phòng trọ (Tải ảnh từ máy tính / điện thoại) <span class="text-accent text-[11px] font-normal">— Ảnh đầu tiên sẽ là ảnh chính</span>
          </label>
          <div id="dropzone" onclick="document.getElementById('anh_phong').click()"
               class="border-2 border-dashed border-border-warm hover:border-accent rounded-card p-6 text-center cursor-pointer transition-colors bg-bg-sub/40 hover:bg-bg-sub group">
            <span class="material-symbols-outlined text-4xl text-accent group-hover:scale-110 transition-transform mb-1.5">add_photo_alternate</span>
            <p class="text-sm font-semibold text-text-main">Nhấp để chọn ảnh từ máy của bạn</p>
            <p class="text-xs text-text-sub mt-1">Hỗ trợ JPG, PNG, WEBP. Tối đa 10MB/ảnh. Có thể chọn cùng lúc nhiều ảnh chụp các góc phòng.</p>
            <input type="file" id="anh_phong" name="anh_phong[]" multiple accept="image/png, image/jpeg, image/jpg, image/webp" class="hidden" onchange="previewRoomImages(this)">
          </div>

          <div id="preview-container" class="hidden space-y-2 pt-2">
            <div class="flex items-center justify-between">
              <span id="preview-count" class="text-xs font-semibold text-text-main"></span>
              <button type="button" onclick="document.getElementById('anh_phong').click()" class="text-xs text-accent font-semibold hover:underline flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">add_photo_alternate</span> Chọn lại hoặc thêm ảnh
              </button>
            </div>
            <div id="preview-grid" class="grid grid-cols-2 sm:grid-cols-4 gap-3"></div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-text-main mb-1">Mô tả thêm về phòng</label>
          <textarea name="mo_ta" rows="3" placeholder="Ghi chú thêm về tiện nghi, giờ giấc, an ninh, nội quy..." class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent resize-none"></textarea>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 pt-2">
        <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro.php" class="px-5 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-xs card-hover">Hủy bỏ</a>
        <button type="submit" class="px-6 py-2.5 rounded-btn bg-accent text-white font-semibold text-sm transition-all duration-200 hover:opacity-90 shadow-sm active:scale-[0.98]">Đăng tin phòng trọ</button>
      </div>
    </form>
  </div>
</div>

<script>
function setGeo(lat, lng) {
  const inLat = document.getElementById('in_vido');
  const inLng = document.getElementById('in_kinhdo');
  if (inLat && inLng) {
    inLat.value = lat;
    inLng.value = lng;
  }
}
function toggleNewNhaTro(val) {
  const box = document.getElementById('new_nha_tro_box');
  if (box) {
    if (val === '0') {
      box.classList.remove('hidden');
    } else {
      box.classList.add('hidden');
    }
  }
}
function previewRoomImages(input) {
  const container = document.getElementById('preview-container');
  const grid = document.getElementById('preview-grid');
  const countLabel = document.getElementById('preview-count');
  if (!input.files || input.files.length === 0) {
    container.classList.add('hidden');
    grid.innerHTML = '';
    return;
  }

  grid.innerHTML = '';
  container.classList.remove('hidden');
  countLabel.textContent = `Đã chọn ${input.files.length} ảnh từ thiết bị:`;

  Array.from(input.files).forEach((file, index) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const card = document.createElement('div');
      card.className = 'relative rounded-card overflow-hidden border border-border-warm bg-bg-main aspect-[4/3] group shadow-2xs';
      card.innerHTML = `
        <img src="${e.target.result}" class="w-full h-full object-cover">
        <span class="absolute top-1.5 left-1.5 text-[10px] font-bold px-2 py-0.5 rounded-full ${index === 0 ? 'bg-accent text-white' : 'bg-black/60 text-white'}">
          ${index === 0 ? 'Ảnh chính' : '#' + (index + 1)}
        </span>
        <div class="absolute inset-x-0 bottom-0 bg-black/60 text-white text-[10px] p-1 truncate text-center">
          ${file.name}
        </div>
      `;
      grid.appendChild(card);
    };
    reader.readAsDataURL(file);
  });
}
</script>

<?php include_once __DIR__ . '/../dungchung/footer.php'; ?>\n