<?php
require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

if (daDangNhap()) {
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/index.php');
    exit();
}

$loi  = [];
$data = ['hoten' => '', 'sdt' => '', 'email' => '', 'vaitro' => 'SinhVien'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['hoten']  = trim($_POST['hoten']  ?? '');
    $data['sdt']    = trim($_POST['sdt']    ?? '');
    $data['email']  = trim($_POST['email']  ?? '');
    $data['vaitro'] = in_array($_POST['vaitro'] ?? '', ['SinhVien', 'ChuTro'])
                      ? $_POST['vaitro'] : 'SinhVien';
    $matkhau = $_POST['matkhau'] ?? '';
    $xacnhan = $_POST['xacnhan'] ?? '';

    if (!$data['hoten']) {
        $loi[] = 'Vui lòng nhập họ và tên.';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $loi[] = 'Địa chỉ email không hợp lệ.';
    }
    if (strlen($matkhau) < 8) {
        $loi[] = 'Mật khẩu phải có tối thiểu 8 ký tự.';
    }
    if ($matkhau !== $xacnhan) {
        $loi[] = 'Xác nhận mật khẩu không khớp.';
    }

    if (!$loi) {
        $chk = $conn->prepare("SELECT MaNguoiDung FROM NguoiDung WHERE Email = ? LIMIT 1");
        $chk->bind_param('s', $data['email']);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $loi[] = 'Email này đã được đăng ký. Vui lòng đăng nhập.';
        }
        $chk->close();
    }

    if (!$loi) {
        $hash = password_hash($matkhau, PASSWORD_DEFAULT);
        $ins  = $conn->prepare(
            "INSERT INTO NguoiDung (HoTen, SoDienThoai, Email, MatKhau, VaiTro)
             VALUES (?, ?, ?, ?, ?)"
        );
        $ins->bind_param('sssss', $data['hoten'], $data['sdt'], $data['email'], $hash, $data['vaitro']);
        if ($ins->execute()) {
            $ins->close();
            $tenVaiTro = ($data['vaitro'] === 'ChuTro') ? 'Chủ trọ' : 'Sinh viên';
            header('Location: /DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php?tb='
                . urlencode("Đăng ký tài khoản $tenVaiTro thành công! Vui lòng đăng nhập."));
            exit();
        } else {
            $loi[] = 'Lỗi hệ thống khi tạo tài khoản. Vui lòng thử lại sau.';
            $ins->close();
        }
    }
}

$tieuDeTrang = 'Đăng ký tài khoản — CampusNest AGU';
include_once __DIR__ . '/../dungchung/header.php';
?>

<div class="flex-1 flex items-center justify-center py-10 px-4 sm:px-6">
  <div class="max-w-lg w-full bg-bg-main rounded-card p-6 sm:p-8 border border-border-warm shadow-md">
    <!-- Header -->
    <div class="flex items-center gap-3.5 mb-5 pb-4 border-b border-border-warm">
      <div class="w-12 h-12 rounded-card bg-accent text-white flex items-center justify-center font-bold text-lg shrink-0">
        AG
      </div>
      <div>
        <h1 class="text-xl font-bold text-text-main leading-tight">Tạo tài khoản CampusNest AGU</h1>
        <p class="text-xs text-text-sub mt-0.5">Dành cho Sinh viên và Chủ trọ tại TP. Long Xuyên</p>
      </div>
    </div>

    <!-- Thông báo lỗi nếu có -->
    <?php if ($loi): ?>
      <div class="mb-4 p-3.5 rounded-card bg-status-red-bg border border-status-red-border text-xs text-status-red-text space-y-1">
        <div class="flex items-center gap-1.5 font-bold">
          <span class="material-symbols-outlined text-base">warning</span>
          <span>Vui lòng kiểm tra lại:</span>
        </div>
        <ul class="list-disc pl-5 space-y-0.5">
          <?php foreach ($loi as $l): ?>
            <li><?= htmlspecialchars($l) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4" novalidate>
      <!-- Chọn vai trò -->
      <div>
        <label class="block text-xs font-semibold text-text-main mb-2">
          Bạn đăng ký với tư cách <span class="text-rose-600">*</span>
        </label>
        <div class="grid grid-cols-2 gap-2.5">
          <label class="cursor-pointer">
            <input type="radio" name="vaitro" value="SinhVien" class="peer sr-only"
                   <?= $data['vaitro'] === 'SinhVien' ? 'checked' : '' ?>>
            <div class="p-3 rounded-card border border-border-warm bg-white text-center transition-all peer-checked:border-accent peer-checked:bg-status-green-bg peer-checked:text-accent hover:border-accent/50">
              <span class="material-symbols-outlined text-2xl block mx-auto mb-1">school</span>
              <span class="text-xs font-bold block text-text-main">Sinh viên AGU</span>
              <span class="text-[11px] text-text-sub block mt-0.5">Tìm phòng &amp; ghép bạn</span>
            </div>
          </label>
          <label class="cursor-pointer">
            <input type="radio" name="vaitro" value="ChuTro" class="peer sr-only"
                   <?= $data['vaitro'] === 'ChuTro' ? 'checked' : '' ?>>
            <div class="p-3 rounded-card border border-border-warm bg-white text-center transition-all peer-checked:border-accent peer-checked:bg-status-green-bg peer-checked:text-accent hover:border-accent/50">
              <span class="material-symbols-outlined text-2xl block mx-auto mb-1">apartment</span>
              <span class="text-xs font-bold block text-text-main">Chủ nhà trọ</span>
              <span class="text-[11px] text-text-sub block mt-0.5">Đăng tin &amp; quản lý</span>
            </div>
          </label>
        </div>
      </div>

      <!-- Họ và tên -->
      <div>
        <label for="hoten" class="block text-xs font-semibold text-text-main mb-1.5">
          Họ và tên <span class="text-rose-600">*</span>
        </label>
        <input id="hoten" name="hoten" type="text" required
               value="<?= htmlspecialchars($data['hoten']) ?>"
               placeholder="VD: Nguyễn Văn Nam"
               class="w-full px-3.5 py-2.5 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none transition-colors">
      </div>

      <!-- Số điện thoại -->
      <div>
        <label for="sdt" class="block text-xs font-semibold text-text-main mb-1.5">
          Số điện thoại liên hệ
        </label>
        <input id="sdt" name="sdt" type="tel"
               value="<?= htmlspecialchars($data['sdt']) ?>"
               placeholder="VD: 0918345678"
               class="w-full px-3.5 py-2.5 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none transition-colors tnum">
      </div>

      <!-- Email -->
      <div>
        <label for="email" class="block text-xs font-semibold text-text-main mb-1.5">
          Địa chỉ Email <span class="text-rose-600">*</span>
        </label>
        <input id="email" name="email" type="email" required
               value="<?= htmlspecialchars($data['email']) ?>"
               placeholder="VD: sinhvien.k22@agu.edu.vn"
               class="w-full px-3.5 py-2.5 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none transition-colors">
      </div>

      <!-- Mật khẩu -->
      <div>
        <label for="matkhau" class="block text-xs font-semibold text-text-main mb-1.5">
          Mật khẩu <span class="text-rose-600">*</span>
        </label>
        <div class="relative">
          <input id="matkhau" name="matkhau" type="password" required minlength="8"
                 placeholder="Tối thiểu 8 ký tự"
                 class="w-full pl-3.5 pr-10 py-2.5 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none transition-colors">
          <button type="button" onclick="togglePasswordVisibility('matkhau', 'eye-1')" aria-label="Hiện hoặc ẩn mật khẩu"
                  class="absolute right-3 top-2.5 text-text-sub hover:text-text-main transition-all duration-200">
            <span id="eye-1" class="material-symbols-outlined text-[20px]">visibility</span>
          </button>
        </div>
        <p class="text-[11px] text-text-sub mt-1">Dùng chữ hoa, chữ thường và số để tăng bảo mật.</p>
      </div>

      <!-- Xác nhận mật khẩu -->
      <div>
        <label for="xacnhan" class="block text-xs font-semibold text-text-main mb-1.5">
          Xác nhận lại mật khẩu <span class="text-rose-600">*</span>
        </label>
        <div class="relative">
          <input id="xacnhan" name="xacnhan" type="password" required
                 placeholder="Nhập lại mật khẩu ở trên"
                 class="w-full pl-3.5 pr-10 py-2.5 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none transition-colors">
          <button type="button" onclick="togglePasswordVisibility('xacnhan', 'eye-2')" aria-label="Hiện hoặc ẩn mật khẩu"
                  class="absolute right-3 top-2.5 text-text-sub hover:text-text-main transition-all duration-200">
            <span id="eye-2" class="material-symbols-outlined text-[20px]">visibility</span>
          </button>
        </div>
      </div>

      <button type="submit" class="w-full py-2.5 rounded-btn bg-accent text-white font-semibold text-sm flex items-center justify-center gap-1.5 transition-all duration-200 active:scale-[0.98] hover:opacity-95 mt-2">
        <span class="material-symbols-outlined">person_add</span>
        <span>Tạo tài khoản</span>
      </button>
    </form>

    <div class="mt-5 pt-4 border-t border-border-warm text-center text-xs text-text-sub">
      Đã có tài khoản?
      <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php" class="text-accent font-semibold underline ml-1 hover:opacity-80">
        Đăng nhập ngay
      </a>
    </div>
  </div>
</div>

<script>
function togglePasswordVisibility(fieldId, iconId) {
  const pwd = document.getElementById(fieldId);
  const icon = document.getElementById(iconId);
  if (!pwd || !icon) return;
  if (pwd.type === 'password') {
    pwd.type = 'text';
    icon.textContent = 'visibility_off';
  } else {
    pwd.type = 'password';
    icon.textContent = 'visibility';
  }
}
</script>

<?php include_once __DIR__ . '/../dungchung/footer.php'; ?>