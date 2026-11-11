<?php
require_once __DIR__ . '/../cauhinh/database.php';
require_once __DIR__ . '/../cauhinh/auth.php';

if (daDangNhap()) {
    header('Location: /DoAn_TimNhaTroSV_QLDAPM/index.php');
    exit();
}

$loi = '';
$email = '';
$demoResetUrl = '';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$resetSession = $_SESSION['reset_password'] ?? null;
$cheDoDatLai = false;

if ($token !== '') {
    if (
        $resetSession &&
        isset($resetSession['token'], $resetSession['email'], $resetSession['expires']) &&
        $resetSession['expires'] >= time() &&
        hash_equals($resetSession['token'], $token)
    ) {
        $cheDoDatLai = true;
    } else {
        $loi = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.';
        $token = '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'dat_lai_mat_khau' && $cheDoDatLai) {
        $matKhauMoi = $_POST['matkhau_moi'] ?? '';
        $xacNhan    = $_POST['xacnhan_matkhau'] ?? '';

        if (strlen($matKhauMoi) < 8) {
            $loi = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
        } elseif ($matKhauMoi !== $xacNhan) {
            $loi = 'Mật khẩu xác nhận không khớp.';
        } else {
            $hash = password_hash($matKhauMoi, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE NguoiDung SET MatKhau = ? WHERE Email = ?");
            $stmt->bind_param('ss', $hash, $resetSession['email']);
            $stmt->execute();
            $stmt->close();

            unset($_SESSION['reset_password']);
            datThongBao('success', 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập bằng mật khẩu mới.');
            header('Location: /DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php');
            exit();
        }
    } else {
        $email = trim($_POST['email'] ?? '');
        if ($email === '') {
            $loi = 'Vui lòng nhập địa chỉ email đã đăng ký.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $loi = 'Địa chỉ email không hợp lệ.';
        } else {
            $stmt = $conn->prepare("SELECT MaNguoiDung, HoTen, TrangThai FROM NguoiDung WHERE Email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user) {
                $loi = 'Email này chưa được đăng ký trên hệ thống.';
            } elseif ($user['TrangThai'] === 'BiKhoa') {
                $loi = 'Tài khoản này đang bị khóa. Vui lòng liên hệ ban quản trị.';
            } else {
                $newToken = bin2hex(random_bytes(24));
                $_SESSION['reset_password'] = [
                    'token'   => $newToken,
                    'email'   => $email,
                    'expires' => time() + 900,
                ];
                $demoResetUrl = '/DoAn_TimNhaTroSV_QLDAPM/taikhoan/quenmatkhau.php?token=' . urlencode($newToken);
            }
        }
    }
}

$tieuDeTrang = 'Quên mật khẩu — CampusNest AGU';
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
        <h1 class="text-xl font-bold text-text-main leading-tight">
          <?= $cheDoDatLai ? 'Đặt lại mật khẩu' : 'Quên mật khẩu' ?>
        </h1>
        <p class="text-xs text-text-sub mt-0.5">
          <?= $cheDoDatLai ? 'Nhập mật khẩu mới cho tài khoản của bạn' : 'Nhập email để nhận liên kết khôi phục mật khẩu' ?>
        </p>
      </div>
    </div>

    <?php if ($loi): ?>
      <div class="mb-4 p-3 rounded-card bg-status-red-bg border border-status-red-border text-xs text-status-red-text flex items-center gap-2">
        <span class="material-symbols-outlined text-base">error</span>
        <span><?= htmlspecialchars($loi) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($demoResetUrl): ?>
      <div class="mb-4 p-4 rounded-card bg-status-green-bg border border-accent/30 text-xs text-text-main space-y-2.5">
        <p class="font-bold text-accent">Đã tạo liên kết đặt lại mật khẩu</p>
        <p class="text-text-sub leading-relaxed">
          Trong môi trường demo (không gửi email thật), bạn có thể nhấn trực tiếp vào nút bên dưới để đặt lại mật khẩu cho <strong><?= htmlspecialchars($email) ?></strong> (hiệu lực 15 phút):
        </p>
        <a href="<?= htmlspecialchars($demoResetUrl) ?>"
           class="inline-flex items-center justify-center w-full py-2.5 rounded-btn bg-accent text-white font-semibold text-xs hover:opacity-95 transition-opacity">
          Mở trang đặt lại mật khẩu
        </a>
      </div>
    <?php endif; ?>

    <?php if ($cheDoDatLai): ?>
      <form method="POST" class="space-y-4" novalidate>
        <input type="hidden" name="action" value="dat_lai_mat_khau">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="p-3 rounded-card bg-bg-sub border border-border-warm text-xs text-text-sub">
          Tài khoản: <strong class="text-text-main"><?= htmlspecialchars($resetSession['email']) ?></strong>
        </div>

        <div>
          <label for="matkhau_moi" class="block text-xs font-semibold text-text-main mb-1.5">
            Mật khẩu mới <span class="text-rose-600">*</span>
          </label>
          <input id="matkhau_moi" name="matkhau_moi" type="password" required minlength="8"
                 placeholder="Tối thiểu 8 ký tự"
                 class="w-full px-3.5 py-2.5 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none transition-colors">
        </div>

        <div>
          <label for="xacnhan_matkhau" class="block text-xs font-semibold text-text-main mb-1.5">
            Xác nhận mật khẩu mới <span class="text-rose-600">*</span>
          </label>
          <input id="xacnhan_matkhau" name="xacnhan_matkhau" type="password" required minlength="8"
                 placeholder="Nhập lại mật khẩu mới"
                 class="w-full px-3.5 py-2.5 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none transition-colors">
        </div>

        <button type="submit"
                class="w-full py-2.5 rounded-btn bg-accent text-white font-semibold text-sm transition-all duration-200 active:scale-[0.98] hover:opacity-95">
          Cập nhật mật khẩu
        </button>
      </form>
    <?php else: ?>
      <form method="POST" class="space-y-4" novalidate>
        <div>
          <label for="email" class="block text-xs font-semibold text-text-main mb-1.5">
            Email đã đăng ký <span class="text-rose-600">*</span>
          </label>
          <input id="email" name="email" type="email" required
                 value="<?= htmlspecialchars($email) ?>"
                 placeholder="VD: sv.k22@agu.edu.vn"
                 class="w-full px-3.5 py-2.5 rounded-card bg-white border border-border-warm text-sm text-text-main focus:border-accent outline-none transition-colors">
        </div>

        <button type="submit"
                class="w-full py-2.5 rounded-btn bg-accent text-white font-semibold text-sm transition-all duration-200 active:scale-[0.98] hover:opacity-95">
          Gửi yêu cầu khôi phục
        </button>
      </form>
    <?php endif; ?>

    <div class="mt-5 pt-4 border-t border-border-warm text-center text-xs text-text-sub">
      Nhớ mật khẩu rồi?
      <a href="/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php" class="text-accent font-semibold underline ml-1 hover:opacity-80">
        Quay lại đăng nhập
      </a>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../dungchung/footer.php'; ?>
