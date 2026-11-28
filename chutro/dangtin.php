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
$stmtNT = $conn->prepare("SELECT MaNhaTro, TenNhaTro, DiaChi, ViDo, KinhDo FROM NhaTro WHERE MaChuTro = ?");
$stmtNT->bind_param('i', $mand);
$stmtNT->execute();
$dsNhaTro = $stmtNT->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtNT->close();

// Lấy danh sách toàn bộ tiện ích có trong hệ thống
$dsTienIchAll = [];
$resTI = $conn->query("SELECT MaTienIch, TenTienIch, Icon FROM TienIch ORDER BY MaTienIch ASC");
if ($resTI) {
    $dsTienIchAll = $resTI->fetch_all(MYSQLI_ASSOC);
}

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

    if ($maNhaTro > 0 && $viDo !== null && $kinhDo !== null) {
        $stmtUpGeo = $conn->prepare("UPDATE NhaTro SET ViDo = ?, KinhDo = ? WHERE MaNhaTro = ? AND MaChuTro = ?");
        $stmtUpGeo->bind_param('ddii', $viDo, $kinhDo, $maNhaTro, $mand);
        $stmtUpGeo->execute();
        $stmtUpGeo->close();
    }

    // Nếu chủ trọ chưa có nhà trọ hoặc tạo nhà trọ mới
    if ($maNhaTro <= 0) {
        if (!$tenNhaTro || !$diaChi) {
            $loi[] = 'Vui lòng nhập tên nhà trọ và địa chỉ.';
        } else {
            // Đảm bảo MaChuTro tồn tại trong bảng NguoiDung
            $chkND = $conn->prepare("SELECT MaNguoiDung FROM NguoiDung WHERE MaNguoiDung = ? LIMIT 1");
            $chkND->bind_param('i', $mand);
            $chkND->execute();
            $resND = $chkND->get_result();
            $chkND->close();

            if (!$resND || $resND->num_rows === 0) {
                $loi[] = 'Tài khoản chủ trọ không hợp lệ hoặc đã bị thay đổi trong CSDL. Vui lòng đăng xuất và đăng nhập lại.';
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
                    $loi[] = 'Không thể tạo nhà trọ mới: ' . $conn->error;
                }
                $stmtNewNT->close();
            }
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
                    $viTriAnhChinh = (int)($_POST['vi_tri_anh_chinh'] ?? 0);
                    if ($viTriAnhChinh < 0 || $viTriAnhChinh >= count($dsAnhDaLuu)) {
                        $viTriAnhChinh = 0;
                    }
                    $stmtAnh = $conn->prepare("INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh, NgayTaiLen) VALUES (?, ?, ?, NOW())");
                    foreach ($dsAnhDaLuu as $idx => $duongDan) {
                        $laChinh = ($idx === $viTriAnhChinh) ? 1 : 0;
                        $stmtAnh->bind_param('isi', $maPhongMoi, $duongDan, $laChinh);
                        $stmtAnh->execute();
                    }
                    $stmtAnh->close();
                }
            }

            // Lưu tiện ích phòng trọ
            $tienIchChon = $_POST['tien_ich'] ?? [];
            if (!empty($tienIchChon) && is_array($tienIchChon)) {
                $stmtTI = $conn->prepare("INSERT INTO PhongTro_TienIch (MaPhong, MaTienIch) VALUES (?, ?)");
                foreach ($tienIchChon as $mti) {
                    $mti = (int)$mti;
                    if ($mti > 0) {
                        $stmtTI->bind_param('ii', $maPhongMoi, $mti);
                        $stmtTI->execute();
                    }
                }
                $stmtTI->close();
            }

            datThongBao('success', 'Đăng tin phòng trọ thành công! ' . ($trangThaiDuyet === 'ChoDuyet' ? 'Tin đăng đang chờ Ban quản trị duyệt.' : ''));
            header('Location: /DoAn_TimNhaTroSV_QLDAPM/chutro/danhsachphong.php');
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
    <a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="hover:text-text-main">Trang chủ</a>
    <span class="text-slate-300">/</span>
    <a href="/DoAn_TimNhaTroSV_QLDAPM/chutro.php" class="hover:text-text-main">Quản lý phòng trọ</a>
    <span class="text-slate-300">/</span>
    <span class="text-text-main font-semibold">Đăng tin phòng mới</span>
  </nav>

  <div class="bg-bg-main rounded-card border border-border-warm p-6 space-y-5">
    <div>
      <h1 class="text-xl font-bold text-text-main">Đăng tin phòng trọ mới</h1>
      <p class="text-xs text-text-sub mt-1">Điền thông tin chi tiết về phòng trọ để sinh viên dễ dàng tìm kiếm và đặt lịch hẹn.</p>
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
            <option value="<?= $nt['MaNhaTro'] ?>"
                    data-vido="<?= htmlspecialchars($nt['ViDo'] ?? '10.3728000') ?>"
                    data-kinhdo="<?= htmlspecialchars($nt['KinhDo'] ?? '105.4335000') ?>">
              <?= htmlspecialchars($nt['TenNhaTro']) ?> (<?= htmlspecialchars($nt['DiaChi']) ?>)
            </option>
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
        </div>

        <!-- Vị trí bản đồ (hiển thị cho cả nhà trọ đã có và nhà trọ mới) -->
        <?php
          $initLat = !empty($dsNhaTro[0]['ViDo']) ? $dsNhaTro[0]['ViDo'] : '10.3728000';
          $initLng = !empty($dsNhaTro[0]['KinhDo']) ? $dsNhaTro[0]['KinhDo'] : '105.4335000';
        ?>
        <div class="space-y-2.5 pt-1">
          <div class="grid grid-cols-2 gap-3 tnum">
            <div>
              <label class="block text-xs font-semibold text-text-main mb-1">Vĩ độ (Latitude)</label>
              <input type="number" step="0.0000001" id="in_vido" name="vi_do" value="<?= htmlspecialchars($initLat) ?>" placeholder="VD: 10.3728" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
            </div>
            <div>
              <label class="block text-xs font-semibold text-text-main mb-1">Kinh độ (Longitude)</label>
              <input type="number" step="0.0000001" id="in_kinhdo" name="kinh_do" value="<?= htmlspecialchars($initLng) ?>" placeholder="VD: 105.4335" class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent">
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="text-text-sub font-semibold">Chọn nhanh:</span>
            <button type="button" onclick="setGeo(10.3713741, 105.4324531)" class="px-2.5 py-1 rounded-btn border border-border-warm bg-bg-main hover:border-accent text-xs font-medium">Gần ĐH An Giang</button>
            <button type="button" onclick="dungGPS()" class="px-2.5 py-1 rounded-btn border border-accent/40 bg-status-green-bg text-accent hover:bg-blue-100 text-xs font-semibold">Lấy vị trí GPS hiện tại</button>
            <span class="text-[11px] text-text-sub">(Hoặc bấm / kéo ghim trực tiếp trên bản đồ bên dưới)</span>
          </div>
          <div id="picker-map" class="w-full h-60 rounded-card border border-border-warm overflow-hidden bg-slate-100" style="height: 240px;"></div>
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
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <label class="block text-xs font-semibold text-text-main">
              Hình ảnh phòng trọ (Tải ảnh từ máy tính / điện thoại)
            </label>
            <span class="text-[11px] text-text-sub">Bấm vào ảnh để đặt làm <strong class="text-accent">Ảnh chính</strong></span>
          </div>

          <div id="dropzone" onclick="document.getElementById('file_input_temp').click()"
               class="border-2 border-dashed border-border-warm hover:border-accent rounded-card p-6 text-center cursor-pointer transition-colors bg-bg-sub/40 hover:bg-bg-sub group">
            <span class="material-symbols-outlined text-4xl text-accent group-hover:scale-110 transition-transform mb-1.5">add_photo_alternate</span>
            <p class="text-sm font-semibold text-text-main">Nhấp để chọn ảnh (có thể chọn nhiều ảnh cùng lúc hoặc thêm từng ảnh)</p>
            <p class="text-xs text-text-sub mt-1">Hỗ trợ JPG, PNG, WEBP. Chọn thêm ảnh sẽ cộng dồn chứ không bị mất ảnh cũ.</p>
            <!-- Input file tạm để người dùng chọn nhiều lần -->
            <input type="file" id="file_input_temp" multiple accept="image/png, image/jpeg, image/jpg, image/webp" class="hidden" onchange="handleAddImages(this)">
          </div>

          <!-- Input file thật được submit lên server qua DataTransfer -->
          <input type="file" id="anh_phong" name="anh_phong[]" multiple accept="image/png, image/jpeg, image/jpg, image/webp" class="hidden">
          <input type="hidden" id="vi_tri_anh_chinh" name="vi_tri_anh_chinh" value="0">

          <div id="preview-container" class="hidden space-y-3 pt-2">
            <div class="flex items-center justify-between">
              <span id="preview-count" class="text-xs font-semibold text-text-main"></span>
              <button type="button" onclick="document.getElementById('file_input_temp').click()" class="text-xs text-accent font-semibold hover:underline flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">add_photo_alternate</span> + Chọn thêm ảnh khác
              </button>
            </div>
            <div id="preview-grid" class="grid grid-cols-2 sm:grid-cols-4 gap-3"></div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-text-main mb-1">Mô tả thêm về phòng</label>
          <textarea name="mo_ta" rows="3" placeholder="Ghi chú thêm về tiện nghi, giờ giấc, an ninh, nội quy..." class="w-full px-3 py-2 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent resize-none"></textarea>
        </div>

        <!-- Tiện ích phòng trọ -->
        <?php if (!empty($dsTienIchAll)): ?>
        <div class="space-y-2 pt-2">
          <label class="block text-xs font-semibold text-text-main">
            Tiện ích kèm theo <span class="text-text-sub text-[11px] font-normal">— Chọn các tiện nghi có sẵn của phòng</span>
          </label>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
            <?php foreach ($dsTienIchAll as $ti): ?>
            <label class="flex items-center gap-2 p-2.5 rounded-btn border border-border-warm hover:border-accent bg-bg-sub/50 hover:bg-bg-sub cursor-pointer transition-colors text-xs text-text-main">
              <input type="checkbox" name="tien_ich[]" value="<?= (int)$ti['MaTienIch'] ?>" class="rounded accent-accent w-4 h-4 cursor-pointer">
              <span class="flex items-center gap-1.5 font-medium">
                <?php if (!empty($ti['Icon'])): ?>
                <span class="material-symbols-outlined text-[16px] text-accent"><?= htmlspecialchars($ti['Icon']) ?></span>
                <?php endif; ?>
                <?= htmlspecialchars($ti['TenTienIch']) ?>
              </span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
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
      if (typeof window.setGeo === 'function') window.setGeo(10.3728, 105.4335);
    } else {
      box.classList.add('hidden');
      const sel = document.getElementById('sel_nha_tro');
      const opt = sel ? sel.options[sel.selectedIndex] : null;
      if (opt && typeof window.setGeo === 'function') {
        const la = parseFloat(opt.dataset.vido), ln = parseFloat(opt.dataset.kinhdo);
        if (!isNaN(la) && !isNaN(ln)) window.setGeo(la, ln);
      }
    }
  }
}

// Quản lý danh sách file ảnh bằng DataTransfer để hỗ trợ cộng dồn và xóa từng ảnh
let selectedFiles = [];
let viTriChinh = 0;

function handleAddImages(input) {
  if (!input.files || input.files.length === 0) return;

  // Cộng dồn các file mới vào mảng
  Array.from(input.files).forEach(file => {
    // Tránh trùng lặp tên và kích thước file
    const exists = selectedFiles.some(f => f.name === file.name && f.size === file.size);
    if (!exists) {
      selectedFiles.push(file);
    }
  });

  // Reset input tạm để lần sau chọn tiếp vẫn kích hoạt onchange
  input.value = '';

  syncFilesAndRender();
}

function removeImageAt(index, event) {
  if (event) event.stopPropagation();
  selectedFiles.splice(index, 1);
  if (viTriChinh >= selectedFiles.length) {
    viTriChinh = Math.max(0, selectedFiles.length - 1);
  }
  syncFilesAndRender();
}

function setMainImage(index) {
  viTriChinh = index;
  document.getElementById('vi_tri_anh_chinh').value = viTriChinh;
  renderPreviews();
}

function syncFilesAndRender() {
  const dt = new DataTransfer();
  selectedFiles.forEach(f => dt.items.add(f));
  const mainInput = document.getElementById('anh_phong');
  mainInput.files = dt.files;

  document.getElementById('vi_tri_anh_chinh').value = viTriChinh;
  renderPreviews();
}

function renderPreviews() {
  const container = document.getElementById('preview-container');
  const grid = document.getElementById('preview-grid');
  const countLabel = document.getElementById('preview-count');

  if (selectedFiles.length === 0) {
    container.classList.add('hidden');
    grid.innerHTML = '';
    return;
  }

  container.classList.remove('hidden');
  countLabel.textContent = `Đã chọn ${selectedFiles.length} ảnh:`;
  grid.innerHTML = '';

  selectedFiles.forEach((file, index) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const isMain = (index === viTriChinh);
      const card = document.createElement('div');
      card.className = `relative rounded-card overflow-hidden border-2 cursor-pointer transition-all aspect-[4/3] group shadow-sm ${isMain ? 'border-accent ring-2 ring-accent/30' : 'border-border-warm hover:border-accent/60'}`;
      card.title = 'Bấm vào để chọn làm Ảnh chính';
      card.onclick = () => setMainImage(index);

      card.innerHTML = `
        <img src="${e.target.result}" class="w-full h-full object-cover">
        
        <!-- Nhãn trạng thái ảnh chính -->
        <span class="absolute top-1.5 left-1.5 text-[10px] font-bold px-2 py-0.5 rounded-full ${isMain ? 'bg-accent text-white shadow' : 'bg-black/60 text-white'}">
          ${isMain ? '★ Ảnh chính' : '#' + (index + 1)}
        </span>

        <!-- Nút xóa ảnh -->
        <button type="button" onclick="removeImageAt(${index}, event)" title="Xóa ảnh này" class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-red-600/90 text-white flex items-center justify-center hover:bg-red-700 transition-colors shadow">
          <span class="material-symbols-outlined text-[14px]">close</span>
        </button>

        <!-- Tên file -->
        <div class="absolute inset-x-0 bottom-0 bg-black/60 text-white text-[10px] px-1.5 py-1 truncate text-center">
          ${file.name}
        </div>
      `;
      grid.appendChild(card);
    };
    reader.readAsDataURL(file);
  });
}
</script>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>#picker-map .leaflet-pane img, #picker-map .leaflet-tile { max-width: none !important; max-height: none !important; }</style>
<script>
(function() {
  const mapEl = document.getElementById('picker-map');
  const inLat = document.getElementById('in_vido');
  const inLng = document.getElementById('in_kinhdo');
  if (!mapEl || typeof L === 'undefined') return;

  const startLat = parseFloat(inLat?.value) || 10.3728;
  const startLng = parseFloat(inLng?.value) || 105.4335;
  const pMap = L.map('picker-map').setView([startLat, startLng], 15);
  L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&hl=vi&x={x}&y={y}&z={z}', {
    maxZoom: 20, subdomains: ['0','1','2','3'], attribution: '&copy; Google Maps'
  }).addTo(pMap);

  const marker = L.marker([startLat, startLng], { draggable: true }).addTo(pMap);
  function syncInputs(lat, lng) {
    if (inLat) inLat.value = Number(lat).toFixed(7);
    if (inLng) inLng.value = Number(lng).toFixed(7);
  }
  marker.on('dragend', () => {
    const pos = marker.getLatLng();
    syncInputs(pos.lat, pos.lng);
  });
  pMap.on('click', (e) => {
    marker.setLatLng(e.latlng);
    syncInputs(e.latlng.lat, e.latlng.lng);
  });
  window.setGeo = function(lat, lng) {
    syncInputs(lat, lng);
    marker.setLatLng([lat, lng]);
    pMap.setView([lat, lng], 16);
  };
  window.dungGPS = function() {
    if (!navigator.geolocation) return alert('Trình duyệt không hỗ trợ GPS.');
    navigator.geolocation.getCurrentPosition(
      (pos) => window.setGeo(pos.coords.latitude, pos.coords.longitude),
      () => alert('Không thể lấy vị trí GPS. Hãy bấm trực tiếp lên bản đồ.')
    );
  };
  if (inLat && inLng) {
    [inLat, inLng].forEach(el => el.addEventListener('change', () => {
      const la = parseFloat(inLat.value), ln = parseFloat(inLng.value);
      if (!isNaN(la) && !isNaN(ln)) { marker.setLatLng([la, ln]); pMap.panTo([la, ln]); }
    }));
  }
  if (typeof ResizeObserver !== 'undefined') {
    new ResizeObserver(() => pMap.invalidateSize()).observe(mapEl);
  }
  window.addEventListener('load', () => pMap.invalidateSize());
  setTimeout(() => pMap.invalidateSize(), 200);
  setTimeout(() => pMap.invalidateSize(), 600);
})();
</script>

<?php include_once __DIR__ . '/../dungchung/footer.php'; ?>\n