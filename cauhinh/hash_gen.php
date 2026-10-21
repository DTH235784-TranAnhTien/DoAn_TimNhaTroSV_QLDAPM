<?php
// hash_gen.php — Chay 1 lan de lay hash mat khau, sau do xoa file nay
// Truy cap: http://localhost/DoAn_TimNhaTroSV_QLDAPM/cauhinh/hash_gen.php
$passwords = [
    'Admin@123'    => 'QuanTriVien',
    'ChuTro@123'   => 'ChuTro',
    'SinhVien@123' => 'SinhVien',
];
echo '<h3>Hash mat khau demo:</h3><table border="1" cellpadding="8">';
echo '<tr><th>Mat khau</th><th>Vai tro</th><th>Hash (dan vao seed_demo.sql)</th></tr>';
foreach ($passwords as $pwd => $role) {
    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    echo "<tr><td>$pwd</td><td>$role</td><td><code style='font-size:11px'>$hash</code></td></tr>";
}
echo '</table>';
echo '<p style="color:red"><b>XOA file nay sau khi su dung!</b></p>';
