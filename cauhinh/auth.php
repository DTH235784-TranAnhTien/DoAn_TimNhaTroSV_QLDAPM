<?php
/**
 * auth.php — Ham xac thuc va phan quyen dung chung toan he thong
 * CampusNest AGU — Do an QLDAPM
 *
 * Cac ham cung cap:
 *   daDangNhap()           -> bool
 *   nguoiDungHienTai()     -> array|null
 *   coVaiTro(string)       -> bool
 *   yeuCauDangNhap(string) -> void  (redirect neu chua login)
 *   yeuCauVaiTro(string)   -> void  (redirect neu sai vai tro)
 *   datThongBao(string, string) -> void
 *   layThongBao()          -> array|null
 *   hienThongBao()         -> void  (render Bootstrap alert)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------------------------------
// Kiem tra da dang nhap chua
// -----------------------------------------------------------------------
function daDangNhap(): bool
{
    return isset($_SESSION['nguoidung']['mannd'])
        && !empty($_SESSION['nguoidung']['mannd']);
}

// -----------------------------------------------------------------------
// Lay thong tin nguoi dung hien tai tu session
// -----------------------------------------------------------------------
function nguoiDungHienTai(): ?array
{
    return isset($_SESSION['nguoidung']) ? $_SESSION['nguoidung'] : null;
}

// -----------------------------------------------------------------------
// Kiem tra vai tro: 'SinhVien' | 'ChuTro' | 'QuanTriVien'
// -----------------------------------------------------------------------
function coVaiTro(string $vaiTro): bool
{
    if (!daDangNhap()) {
        return false;
    }
    return $_SESSION['nguoidung']['vaitro'] === $vaiTro;
}

// -----------------------------------------------------------------------
// Yeu cau dang nhap — neu chua, redirect ve trang login
// -----------------------------------------------------------------------
function yeuCauDangNhap(string $thongBao = ''): void
{
    if (!daDangNhap()) {
        $url = '/DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php';
        if ($thongBao !== '') {
            $url .= '?tb=' . urlencode($thongBao);
        }
        header('Location: ' . $url);
        exit();
    }
}

// -----------------------------------------------------------------------
// Yeu cau dung vai tro — neu sai, ve trang chu
// -----------------------------------------------------------------------
function yeuCauVaiTro(string $vaiTro): void
{
    yeuCauDangNhap('Ban can dang nhap de truy cap trang nay.');
    if (!coVaiTro($vaiTro)) {
        header('Location: /DoAn_TimNhaTroSV_QLDAPM/index.php');
        exit();
    }
}

// -----------------------------------------------------------------------
// Luu flash message (thong bao 1 lan sau redirect)
// $loai: 'success' | 'danger' | 'warning' | 'info'
// -----------------------------------------------------------------------
function datThongBao(string $loai, string $noiDung): void
{
    $_SESSION['flash'] = array(
        'loai'    => $loai,
        'noidung' => $noiDung,
    );
}

// -----------------------------------------------------------------------
// Lay flash message va xoa khoi session
// -----------------------------------------------------------------------
function layThongBao(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $tb = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $tb;
}

// -----------------------------------------------------------------------
// Render Bootstrap 5 alert tu flash message
// -----------------------------------------------------------------------
function hienThongBao(): void
{
    $tb = layThongBao();
    if ($tb === null) {
        return;
    }

    // Map loai -> [bootstrap class, bootstrap icon]
    $map = array(
        'success' => array('success', 'check-circle-fill'),
        'danger'  => array('danger',  'exclamation-triangle-fill'),
        'warning' => array('warning', 'exclamation-circle-fill'),
        'info'    => array('info',    'info-circle-fill'),
    );

    $loai = isset($tb['loai']) ? $tb['loai'] : 'info';
    if (isset($map[$loai])) {
        $css  = $map[$loai][0];
        $icon = $map[$loai][1];
    } else {
        $css  = 'secondary';
        $icon = 'bell-fill';
    }

    $msg = htmlspecialchars($tb['noidung'], ENT_QUOTES, 'UTF-8');

    echo '<div class="alert alert-' . $css . ' alert-dismissible fade show'
       . ' d-flex align-items-center gap-2 shadow-sm" role="alert">' . "\n";
    echo '  <i class="bi bi-' . $icon . '"></i>' . "\n";
    echo '  <span>' . $msg . '</span>' . "\n";
    echo '  <button type="button" class="btn-close"'
       . ' data-bs-dismiss="alert" aria-label="Dong"></button>' . "\n";
    echo '</div>' . "\n";
}
