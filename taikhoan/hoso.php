<?php
require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

yeuCauDangNhap('Vui long dang nhap de xem thong tin ca nhan.');

$nd   = nguoiDungHienTai();
$mand = (int)$nd['mannd'];

$stmt = $conn->prepare(
    "SELECT MaNguoiDung, HoTen, Email, SoDienThoai, AnhDaiDien, VaiTro, TrangThai, NgayTao FROM NguoiDung WHERE MaNguoiDung = ? LIMIT 1"
);
$stmt->bind_param('i', $mand);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) { header('Location: /DoAn_TimNhaTroSV_QLDAPM/index.php'); exit(); }

$loi = array(); $ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoten  = trim($_POST['hoten']  ?? '');
    $sdt    = trim($_POST['sdt']    ?? '');
    $mk_cu  = $_POST['mk_cu']  ?? '';
    $mk_moi = $_POST['mk_moi'] ?? '';
    $mk_xn  = $_POST['mk_xn']  ?? '';

    if (!$hoten) $loi[] = 'Họ và tên không được để trống.';
    if ($sdt && !preg_match('/^[0-9]{9,11}$/', $sdt)) $loi[] = 'Số điện thoại không hợp lệ.';

    // Xử lý tải ảnh đại diện từ máy tính
    $coCapNhatAnh = false;
    if (isset($_FILES['anh_dai_dien']) && $_FILES['anh_dai_dien']['error'] !== UPLOAD_ERR_NO_FILE) {
        require_once __DIR__ . '/../cauhinh/upload_helper.php';
        $resAvt = xuLyUploadAvatar($_FILES['anh_dai_dien'], $mand);
        if ($resAvt['ok']) {
            $duongDanAvt = $resAvt['path'];
            $updAvt = $conn->prepare("UPDATE NguoiDung SET AnhDaiDien = ? WHERE MaNguoiDung = ?");
            $updAvt->bind_param('si', $duongDanAvt, $mand);
            $updAvt->execute();
            $updAvt->close();
            $_SESSION['nguoidung']['anhdaidien'] = $duongDanAvt;
            $user['AnhDaiDien'] = $duongDanAvt;
            $coCapNhatAnh = true;
        } else {
            $loi[] = $resAvt['error'];
        }
    }

    $capNhatMK = false;
    if ($mk_moi !== '') {
        $s2 = $conn->prepare("SELECT MatKhau FROM NguoiDung WHERE MaNguoiDung = ?");
        $s2->bind_param('i', $mand); $s2->execute();
        $hashRow = $s2->get_result()->fetch_assoc(); $s2->close();

        if (!password_verify($mk_cu, $hashRow['MatKhau'] ?? ''))
            $loi[] = 'Mật khẩu hiện tại không chính xác.';
        elseif (strlen($mk_moi) < 6)
            $loi[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        elseif ($mk_moi !== $mk_xn)
            $loi[] = 'Xác nhận mật khẩu mới không khớp.';
        else
            $capNhatMK = true;
    }

    if (empty($loi)) {
        if ($capNhatMK) {
            $newHash = password_hash($mk_moi, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE NguoiDung SET HoTen=?, SoDienThoai=?, MatKhau=? WHERE MaNguoiDung=?");
            $upd->bind_param('sssi', $hoten, $sdt, $newHash, $mand);
        } else {
            $upd = $conn->prepare("UPDATE NguoiDung SET HoTen=?, SoDienThoai=? WHERE MaNguoiDung=?");
            $upd->bind_param('ssi', $hoten, $sdt, $mand);
        }
        $upd->execute(); $upd->close();
        $_SESSION['nguoidung']['hoten'] = $hoten;

        $stmt2 = $conn->prepare("SELECT MaNguoiDung,HoTen,Email,SoDienThoai,AnhDaiDien,VaiTro,TrangThai,NgayTao FROM NguoiDung WHERE MaNguoiDung=?");
        $stmt2->bind_param('i', $mand); $stmt2->execute();
        $user = $stmt2->get_result()->fetch_assoc(); $stmt2->close();
        $ok = true;
        $thongBaoMsg = 'Cập nhật thông tin cá nhân' . ($coCapNhatAnh ? ' và ảnh đại diện' : '') . ' thành công!';
        datThongBao('success', $thongBaoMsg);
    }
}

$vaiTroHienThi = ($user['VaiTro'] === 'QuanTriVien') ? 'Quản trị viên'
               : (($user['VaiTro'] === 'ChuTro')     ? 'Chủ trọ' : 'Sinh viên');
$vaiTroBgClass = ($user['VaiTro'] === 'QuanTriVien')
    ? 'bg-status-green-bg text-accent'
    : (($user['VaiTro'] === 'ChuTro') ? 'bg-status-amber-bg text-status-amber-text' : 'bg-bg-sub text-text-sub border border-border-warm');
$ngayTao = $user['NgayTao'] ? date('d/m/Y', strtotime($user['NgayTao'])) : '--';
$anhHienThi = !empty($user['AnhDaiDien']) ? $user['AnhDaiDien'] : '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/default-avatar.png';

$tieuDeTrang = 'Thông tin cá nhân -- CampusNest AGU';
$trangHienTai = '';
include_once __DIR__ . '/../dungchung/header.php';
?>

<div class="max-w-[900px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

  <!-- Breadcrumb -->
  <nav class="flex items-center gap-1.5 text-xs text-text-sub" aria-label="Breadcrumb">
    <a href="/DoAn_TimNhaTroSV_QLDAPM/index.php" class="hover:text-text-main transition-colors">Trang chính</a>
    <span class="material-symbols-outlined text-[14px]">chevron_right</span>
    <span class="text-text-main font-semibold">Thông tin cá nhân</span>
  </nav>

  <!-- Hero Card -->
  <div class="bg-bg-sub rounded-card border border-border-warm p-5 sm:p-6 flex flex-col sm:flex-row items-center sm:items-start gap-5">
    <div class="relative shrink-0 flex flex-col items-center">
      <div class="w-24 h-24 rounded-full border-2 border-border-warm overflow-hidden bg-bg-main relative group shadow-sm">
        <img id="preview-avatar" src="<?= htmlspecialchars($anhHienThi) ?>" alt="Ảnh đại diện"
             onerror="this.onerror=null;this.src='/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/default-avatar.png'"
             class="w-full h-full object-cover">
        <div onclick="document.getElementById('avatar-file').click()"
             class="absolute inset-0 bg-black/40 text-white flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
          <span class="material-symbols-outlined text-xl">photo_camera</span>
          <span class="text-[10px] font-medium">Đổi ảnh</span>
        </div>
      </div>
      <button type="button" onclick="document.getElementById('avatar-file').click()"
        class="mt-2.5 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-btn bg-bg-main border border-border-warm text-xs font-semibold text-text-main hover:border-accent hover:text-accent transition-colors shadow-2xs">
        <span class="material-symbols-outlined text-[15px]">upload_file</span>
        <span>Chọn ảnh từ máy</span>
      </button>
      <input id="avatar-file" name="anh_dai_dien" type="file" accept="image/png, image/jpeg, image/jpg, image/webp" form="hoso-form" class="hidden" onchange="previewAvatar(this)">
      <p id="avatar-status" class="text-[11px] text-accent mt-1 text-center font-medium"></p>
    </div>
    <div class="flex-1 min-w-0 text-center sm:text-left">
      <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-1">
        <h1 class="text-xl font-bold text-text-main"><?= htmlspecialchars($user['HoTen']) ?></h1>
        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $vaiTroBgClass ?>"><?= $vaiTroHienThi ?></span>
      </div>
      <p class="text-xs text-text-sub"><?= htmlspecialchars($user['Email']) ?></p>
      <p class="text-xs text-text-sub mt-0.5">Thành viên từ <?= $ngayTao ?> &middot; Mã ID: #<?= str_pad($user['MaNguoiDung'], 4, '0', STR_PAD_LEFT) ?></p>
      <div class="mt-3 p-2.5 rounded-btn bg-bg-main/70 border border-border-warm text-[11px] text-text-sub inline-flex items-center gap-2 text-left">
        <span class="material-symbols-outlined text-accent text-base shrink-0">info</span>
        <span>Bạn có thể chọn ảnh chân dung từ máy tính hoặc điện thoại (JPG, PNG, WEBP, tối đa 5MB) rồi nhấn <strong>Lưu thay đổi</strong>.</span>
      </div>
    </div>
    <div class="shrink-0">
      <?php if ($user['TrangThai'] === 'HoatDong'): ?>
        <span class="px-2.5 py-1 rounded-full bg-status-green-bg text-accent text-xs font-semibold inline-flex items-center gap-1">
          <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>Hoạt động
        </span>
      <?php else: ?>
        <span class="px-2.5 py-1 rounded-full bg-status-red-bg text-status-red-text border border-status-red-border text-xs font-semibold inline-flex items-center gap-1">
          <span class="w-1.5 h-1.5 rounded-full bg-status-red-text"></span>Bị khóa
        </span>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($loi)): ?>
  <div class="p-4 rounded-card bg-status-red-bg border border-status-red-border text-xs text-status-red-text space-y-1">
    <div class="flex items-center gap-1.5 font-semibold"><span class="material-symbols-outlined text-base">error</span>Có lỗi cần sửa:</div>
    <ul class="list-disc list-inside space-y-0.5 ml-4">
      <?php foreach ($loi as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <?php if ($ok): ?>
  <div class="p-3 rounded-card bg-status-green-bg border border-accent/20 text-xs text-accent flex items-center gap-2">
    <span class="material-symbols-outlined text-base">check_circle</span>
    <span>Cập nhật thành công! Thông tin và ảnh đại diện đã được lưu.</span>
  </div>
  <?php endif; ?>

  <form id="hoso-form" method="POST" action="" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-5">

    <!-- COT TRAI: Thong tin co ban -->
    <section class="bg-bg-main rounded-card border border-border-warm p-5 space-y-4">
      <div class="pb-3 border-b border-border-warm flex items-center gap-2">
        <span class="material-symbols-outlined text-accent">person</span>
        <h2 class="text-sm font-bold text-text-main">Thong tin co ban</h2>
      </div>

      <div class="space-y-1">
        <label for="f-hoten" class="text-xs font-semibold text-text-main block">Ho va ten <span class="text-rose-500">*</span></label>
        <input id="f-hoten" name="hoten" type="text" required value="<?= htmlspecialchars($user['HoTen']) ?>"
          class="w-full px-3 py-2.5 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent transition-colors"
          placeholder="Nguyen Van A">
      </div>

      <div class="space-y-1">
        <label class="text-xs font-semibold text-text-main block">Email</label>
        <div class="flex items-center gap-2 px-3 py-2.5 rounded-btn border border-border-warm bg-bg-sub/60 text-sm text-text-sub">
          <span class="material-symbols-outlined text-text-sub text-base">lock</span>
          <span><?= htmlspecialchars($user['Email']) ?></span>
        </div>
        <p class="text-[10px] text-text-sub">Email khong the thay doi. Lien he admin neu can.</p>
      </div>

      <div class="space-y-1">
        <label for="f-sdt" class="text-xs font-semibold text-text-main block">So dien thoai</label>
        <div class="relative">
          <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-sub text-base pointer-events-none">phone</span>
          <input id="f-sdt" name="sdt" type="tel" value="<?= htmlspecialchars($user['SoDienThoai'] ?? '') ?>"
            class="w-full pl-9 pr-3 py-2.5 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent transition-colors"
            placeholder="09xxxxxxxx">
        </div>
      </div>

      <div class="space-y-1">
        <label class="text-xs font-semibold text-text-main block">Vai tro</label>
        <div class="flex items-center gap-2 px-3 py-2.5 rounded-btn border border-border-warm bg-bg-sub/60">
          <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $vaiTroBgClass ?>"><?= $vaiTroHienThi ?></span>
          <span class="text-xs text-text-sub">-- Lien he admin de thay doi vai tro</span>
        </div>
      </div>
    </section>

    <!-- COT PHAI -->
    <div class="space-y-5">

      <!-- Doi mat khau -->
      <section class="bg-bg-main rounded-card border border-border-warm p-5 space-y-4">
        <div class="pb-3 border-b border-border-warm flex items-center gap-2">
          <span class="material-symbols-outlined text-accent">lock_reset</span>
          <h2 class="text-sm font-bold text-text-main">Doi mat khau</h2>
        </div>
        <p class="text-xs text-text-sub">De trong neu khong muon doi mat khau.</p>

        <div class="space-y-1">
          <label for="f-mk-cu" class="text-xs font-semibold text-text-main block">Mat khau hien tai</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-sub text-base pointer-events-none">key</span>
            <input id="f-mk-cu" name="mk_cu" type="password"
              class="w-full pl-9 pr-10 py-2.5 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent transition-colors"
              placeholder="Mat khau hien tai">
            <button type="button" onclick="togglePwd('f-mk-cu','eye-cu')" class="absolute right-3 top-1/2 -translate-y-1/2 text-text-sub hover:text-text-main">
              <span id="eye-cu" class="material-symbols-outlined text-base">visibility</span>
            </button>
          </div>
        </div>

        <div class="space-y-1">
          <label for="f-mk-moi" class="text-xs font-semibold text-text-main block">Mat khau moi</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-sub text-base pointer-events-none">lock</span>
            <input id="f-mk-moi" name="mk_moi" type="password"
              class="w-full pl-9 pr-10 py-2.5 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent transition-colors"
              placeholder="It nhat 6 ky tu"
              oninput="checkStrength(this.value)">
            <button type="button" onclick="togglePwd('f-mk-moi','eye-moi')" class="absolute right-3 top-1/2 -translate-y-1/2 text-text-sub hover:text-text-main">
              <span id="eye-moi" class="material-symbols-outlined text-base">visibility</span>
            </button>
          </div>
          <div class="flex gap-1 mt-1">
            <div id="str-1" class="h-1 flex-1 rounded-full bg-border-warm transition-colors duration-300"></div>
            <div id="str-2" class="h-1 flex-1 rounded-full bg-border-warm transition-colors duration-300"></div>
            <div id="str-3" class="h-1 flex-1 rounded-full bg-border-warm transition-colors duration-300"></div>
            <div id="str-4" class="h-1 flex-1 rounded-full bg-border-warm transition-colors duration-300"></div>
          </div>
          <p id="str-label" class="text-[10px] text-text-sub"></p>
        </div>

        <div class="space-y-1">
          <label for="f-mk-xn" class="text-xs font-semibold text-text-main block">Xac nhan mat khau moi</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-sub text-base pointer-events-none">lock_check</span>
            <input id="f-mk-xn" name="mk_xn" type="password"
              class="w-full pl-9 pr-3 py-2.5 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent transition-colors"
              placeholder="Nhap lai mat khau moi">
          </div>
        </div>
      </section>

      <?php if ($user['VaiTro'] === 'ChuTro'): ?>
      <section class="bg-bg-main rounded-card border border-border-warm p-5 space-y-4">
        <div class="pb-3 border-b border-border-warm flex items-center gap-2">
          <span class="material-symbols-outlined text-accent">apartment</span>
          <h2 class="text-sm font-bold text-text-main">Thong tin chu tro</h2>
        </div>
        <div class="space-y-1">
          <label class="text-xs font-semibold text-text-main block">So CCCD / CMND</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-sub text-base pointer-events-none">badge</span>
            <input name="socccd" type="text"
              class="w-full pl-9 pr-3 py-2.5 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent transition-colors"
              placeholder="12 chu so CCCD">
          </div>
          <p class="text-[10px] text-text-sub">Dung de xac thuc chinh chu boi quan tri vien AGU.</p>
        </div>
        <div class="p-3 rounded-card bg-status-amber-bg border border-status-amber-text/20 text-xs text-status-amber-text flex items-start gap-2">
          <span class="material-symbols-outlined text-base shrink-0 mt-0.5">info</span>
          <span>Tinh nang tai anh CCCD va xac thuc chinh chu se duoc cap nhat trong phien ban tiep theo.</span>
        </div>
      </section>
      <?php endif; ?>

      <?php if ($user['VaiTro'] === 'SinhVien'): ?>
      <section class="bg-bg-main rounded-card border border-border-warm p-5 space-y-4">
        <div class="pb-3 border-b border-border-warm flex items-center gap-2">
          <span class="material-symbols-outlined text-accent">school</span>
          <h2 class="text-sm font-bold text-text-main">Thong tin sinh vien</h2>
        </div>
        <div class="space-y-1">
          <label class="text-xs font-semibold text-text-main block">Ma so sinh vien</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-sub text-base pointer-events-none">id_card</span>
            <input name="masv" type="text"
              class="w-full pl-9 pr-3 py-2.5 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent transition-colors"
              placeholder="VD: DKC220001">
          </div>
        </div>
        <div class="space-y-1">
          <label class="text-xs font-semibold text-text-main block">Khoa / Nganh</label>
          <select name="khoa" class="w-full px-3 py-2.5 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent transition-colors">
            <option value="">-- Chon khoa --</option>
            <option>Khoa Cong nghe thong tin</option>
            <option>Khoa Su pham</option>
            <option>Khoa Kinh te - Quan tri kinh doanh</option>
            <option>Khoa Ky thuat - Cong nghe - Moi truong</option>
            <option>Khoa Nong nghiep - Tai nguyen thien nhien</option>
            <option>Khoa Van hoa - Nghe thuat - Du lich</option>
            <option>Khoa Ly luan chinh tri</option>
          </select>
        </div>
        <div class="space-y-1">
          <label class="text-xs font-semibold text-text-main block">Khoa hoc</label>
          <select name="khoa_hoc" class="w-full px-3 py-2.5 rounded-btn border border-border-warm bg-bg-sub text-sm text-text-main outline-none focus:border-accent transition-colors">
            <option value="">-- Chon khoa --</option>
            <?php for ($k = 22; $k <= 27; $k++): ?>
            <option>K<?= $k ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </section>
      <?php endif; ?>

    </div>

    <!-- Nut luu -->
    <div class="md:col-span-2 flex items-center justify-between gap-4 pt-2">
      <a href="javascript:history.back()" class="px-4 py-2.5 rounded-btn border border-border-warm bg-bg-main text-text-main font-semibold text-sm card-hover transition-all duration-200">
        &larr; Quay lai
      </a>
      <button type="submit"
        class="flex items-center gap-2 px-6 py-2.5 rounded-btn bg-accent text-white font-semibold text-sm transition-all duration-200 hover:opacity-90 active:scale-[0.98] shadow-sm">
        <span class="material-symbols-outlined text-base">save</span>
        Luu thay doi
      </button>
    </div>
  </form>
</div>

<script>
function previewAvatar(input) {
  const file = input.files[0];
  const status = document.getElementById('avatar-status');
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) {
    if (typeof showToast === 'function') showToast('Ảnh quá lớn (tối đa 5 MB).', 'error');
    else alert('Ảnh quá lớn (tối đa 5 MB).');
    input.value = '';
    if (status) status.textContent = '';
    return;
  }
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('preview-avatar').src = e.target.result;
    if (status) {
      status.textContent = '✓ Đã chọn: ' + file.name + ' (nhấn "Lưu thay đổi" để hoàn tất)';
    }
  };
  reader.readAsDataURL(file);
}
function togglePwd(inputId, eyeId) {
  const inp = document.getElementById(inputId);
  const eye = document.getElementById(eyeId);
  if (!inp || !eye) return;
  inp.type = inp.type === 'password' ? 'text' : 'password';
  eye.textContent = inp.type === 'password' ? 'visibility' : 'visibility_off';
}
function checkStrength(val) {
  const bars = [1,2,3,4].map(i => document.getElementById('str-' + i));
  const label = document.getElementById('str-label');
  let score = 0;
  if (val.length >= 6)  score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
  if (/[0-9]/.test(val) && /[^A-Za-z0-9]/.test(val)) score++;
  const colors = ['bg-rose-400','bg-orange-400','bg-amber-400','bg-accent'];
  const labels = ['Qua yeu','Yeu','Trung binh','Manh'];
  bars.forEach((b, i) => {
    b.className = 'h-1 flex-1 rounded-full transition-colors duration-300 ' + (i < score ? colors[score-1] : 'bg-border-warm');
  });
  label.textContent = val.length ? labels[Math.max(0, score-1)] : '';
}
</script>

<?php include_once __DIR__ . '/../dungchung/footer.php'; ?>
