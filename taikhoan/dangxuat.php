<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}
session_destroy();
header('Location: /DoAn_TimNhaTroSV_QLDAPM/taikhoan/dangnhap.php?tb='
    . urlencode('Ban da dang xuat thanh cong. Hen gap lai!'));
exit();
