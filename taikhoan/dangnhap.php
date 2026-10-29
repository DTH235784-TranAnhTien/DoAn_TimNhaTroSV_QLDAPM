<?php
require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

if (daDangNhap()) {
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/index.php');
    exit();
}

$loi = '';
$email = '';
$tbUrl = isset($_GET['tb']) ? htmlspecialchars(urldecode($_GET['tb'])) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = trim($_POST['email']   ?? '');
    $matkhau = $_POST['matkhau'] ?? '';

    if (!$email || !$matkhau) {
        $loi = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loi = 'Địa chỉ email không hợp lệ.';
    } else {
        $stmt = $conn->prepare(
            "SELECT MaNguoiDung, HoTen, Email, MatKhau, VaiTro, AnhDaiDien, TrangThai
             FROM NguoiDung WHERE Email = ? LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $loi = 'Email chưa được đăng ký trên hệ thống.';
        } elseif ($user['TrangThai'] === 'BiKhoa') {
            $loi = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ ban quản trị.';
        } elseif (!password_verify($matkhau, $user['MatKhau'])) {
            $loi = 'Mật khẩu không chính xác. Vui lòng thử lại.';
        } else {
            $_SESSION['nguoidung'] = [
                'mannd'      => $user['MaNguoiDung'],
                'hoten'      => $user['HoTen'],
                'email'      => $user['Email'],
                'vaitro'     => $user['VaiTro'],
                'anhdaidien' => $user['AnhDaiDien'] ?? '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/default-avatar.png',
            ];
            datThongBao('success', 'Chào mừng ' . $user['HoTen'] . ' đã đăng nhập thành công!');
            $dieuhuong = match($user['VaiTro']) {
                'QuanTriVien' => '/DoAn_TimNhaTroSV_QLDAPM/quantri/index.php',
                'ChuTro'      => '/DoAn_TimNhaTroSV_QLDAPM/chutro.php',
                default       => '/DoAn_TimNhaTroSV_QLDAPM/index.php',
            };
            header("Location: $dieuhuong");
            exit();
        }
    }
}

$tieuDeTrang = 'Đăng nhập — CampusNest AGU';
include_once __DIR__ . '/../dungchung/header.php';
?>

<div class="flex-1 flex items-center justify-center py-10 px-4 sm:px-6">
  <div class="max-w-md w-full bg-bg-main rounded-card p-6 sm:p-8 border border-border-warm shadow-md">
    <!-- Header -->
    <div class="flex items-center gap-3.5 mb-5 pb-4 border-b border-border-warm">
      <div class="w-12 h-12 rounded-card bg-accent text-white flex items-center justify-center font-bold text-lg shrink-0">
        AG
      </div>
      <div>
        <h1 class="text-xl font-bold text-text-main leading-tight">Đăng nhập CampusNest AGU</h1>
        <p class="text-xs text-text-sub mt-0.5">Xác thực tài khoản Sinh viên, Chủ trọ hoặc Quản trị AGU</p>
      </div>
    </div>

    <!-- Thông báo lỗi hoặc thông tin từ URL -->
    <?php if ($tbUrl): ?>
      <div class="mb-4 p-3 rounded-card bg-bg-sub border border-border-warm text-xs text-text-main flex items-center gap-2">
        <span class="material-symbols-outlined text-accent text-base">info</span>
        <span><?= $tbUrl ?></span>
      </div>
    <?php endif; ?>

    <?php if ($loi): ?>
      <div class="mb-4 p-3 rounded-card bg-status-red-bg border border-status-red-border text-xs text-status-red-text flex items-center gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars($loi) ?></span>
      </div>
    <?php endif; ?>

    <!-- 3 Tab chọn vai trò (UI gợi ý / điền nhanh) -->
    <div class="grid grid-cols-3 gap-1.5 p-1 bg-bg-sub rounded-card border border-border-warm mb-4" role="tablist">
      <button type="button" id="rtab-sv" onclick="chonTabVaiTro('sv')" class="py-1.5 px-2 rounded-card text-xs font-semibold bg-accent text-white transition-all duration-200 active:scale-[0.98]">
        Sinh viên
      </button>
      <button type="button" id="rtab-ct" onclick="chonTabVaiTro('ct')" class="py-1.5 px-2 rounded-card text-xs font-medium text-text-sub hover:text-text-main transition-all duration-200 active:scale-[0.98]">
        Chủ trọ
      </button>
      <button type="button" id="rtab-qt" onclick="chonTabVaiTro('qt')" class="py-1.5 px-2 rounded-card text-xs font-medium text-text-sub hover:text-text-main transition-all duration-200 active:scale-[0.98]">
        Quản trị
      </button>
    </div>

    <form method="POST" class="space-y-4" novalidate>
      <div>
        <label id="login-user-label" for="email" class="block text-xs font-semibold text-text-main mb-1.5">
          Email sinh viên / Tài khoản AGU <span class="text-rose-600">*</span>
        </label>
        <input id="email" name="email" type="email" required
               value="<?= htmlspecialchars($email) ?>"
               placeholder="VD: sv.k22@agu.edu.vn"
               class="w-full px-3.5 py-2.5 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none transition-colors">
      </div>

      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label for="matkhau" class="block text-xs font-semibold text-text-main">
            Mật khẩu xác thực <span class="text-rose-600">*</span>
          </label>
          <a href="#" onclick="showToast('Vui lòng liên hệ Văn phòng Đoàn AGU (0296.384.3144) để cấp lại mật khẩu.', 'info'); return false;" class="text-xs text-accent font-semibold hover:underline">
            Quên mật khẩu?
          </a>
        </div>
        <div class="relative">
          <input id="matkhau" name="matkhau" type="password" required
                 placeholder="••••••••"
                 class="w-full pl-3.5 pr-10 py-2.5 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none transition-colors">
          <button type="button" onclick="togglePasswordVisibility()" aria-label="Hiện hoặc ẩn mật khẩu"
                  class="absolute right-3 top-2.5 text-text-sub hover:text-text-main transition-all duration-200">
            <span id="pwd-eye-icon" class="material-symbols-outlined text-[20px]">visibility</span>
          </button>
        </div>
      </div>

      <!-- Khung tài khoản demo điền nhanh -->
      <div class="p-3 rounded-card bg-bg-sub border border-border-warm text-xs text-text-main flex items-center justify-between gap-2">
        <span class="truncate">Trực ca: <strong id="demo-account-email">sv.k22@agu.edu.vn</strong></span>
        <button type="button" onclick="dienDemoHienTai()" class="text-accent font-semibold underline shrink-0 hover:opacity-80">
          Điền nhanh
        </button>
      </div>

      <button type="submit" class="w-full py-2.5 rounded-btn bg-accent text-white font-semibold text-sm flex items-center justify-center gap-1.5 transition-all duration-200 active:scale-[0.98] hover:opacity-95">
        <span class="material-symbols-outlined">login</span>
        <span>Đăng nhập hệ thống</span>
      </button>
    </form>

    <div class="mt-5 pt-4 border-t border-border-warm text-center text-xs text-text-sub">
      Chưa có tài khoản?
      <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangky.php" class="text-accent font-semibold underline ml-1 hover:opacity-80">
        Đăng ký tài khoản mới
      </a>
    </div>
  </div>
</div>

<script>
const TAI_KHOAN_DEMO = {
  sv: {
    email: 'sv.k22@agu.edu.vn',
    pass: 'SinhVien@123',
    label: 'Email sinh viên / Tài khoản AGU',
    placeholder: 'VD: sv.k22@agu.edu.vn'
  },
  ct: {
    email: 'cobay@agu.edu.vn',
    pass: 'ChuTro@123',
    label: 'Email / Mã đối tác Chủ trọ AGU',
    placeholder: 'VD: cobay@agu.edu.vn'
  },
  qt: {
    email: 'admin@agu.edu.vn',
    pass: 'Admin@123',
    label: 'Mã cán bộ / Email công vụ AGU',
    placeholder: 'VD: admin@agu.edu.vn'
  }
};

let tabHienTai = 'sv';

function chonTabVaiTro(vaiTro) {
  tabHienTai = vaiTro;
  ['sv', 'ct', 'qt'].forEach(vt => {
    const btn = document.getElementById('rtab-' + vt);
    if (!btn) return;
    if (vt === vaiTro) {
      btn.className = 'py-1.5 px-2 rounded-card text-xs font-semibold bg-accent text-white transition-all duration-200 active:scale-[0.98]';
    } else {
      btn.className = 'py-1.5 px-2 rounded-card text-xs font-medium text-text-sub hover:text-text-main transition-all duration-200 active:scale-[0.98]';
    }
  });

  const cfg = TAI_KHOAN_DEMO[vaiTro];
  const lbl = document.getElementById('login-user-label');
  const inp = document.getElementById('email');
  const demoEmail = document.getElementById('demo-account-email');
  if (lbl) lbl.innerHTML = `${cfg.label} <span class="text-rose-600">*</span>`;
  if (inp) inp.placeholder = cfg.placeholder;
  if (demoEmail) demoEmail.textContent = cfg.email;
}

function dienDemoHienTai() {
  const cfg = TAI_KHOAN_DEMO[tabHienTai];
  document.getElementById('email').value = cfg.email;
  document.getElementById('matkhau').value = cfg.pass;
  showToast(`Đã điền tài khoản demo: ${cfg.email}`, 'info');
}

function togglePasswordVisibility() {
  const pwd = document.getElementById('matkhau');
  const icon = document.getElementById('pwd-eye-icon');
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