<?php
/**
 * upload_helper.php — Xử lý tải ảnh an toàn từ máy tính/thiết bị cục bộ
 * CampusNest AGU — Đồ án QLDAPM
 */

define('UPLOAD_DIR_AVATAR', __DIR__ . '/../giaodien/hinhanh/avatar/');
define('UPLOAD_DIR_PHONG', __DIR__ . '/../giaodien/hinhanh/phongtro/');
define('WEB_PATH_AVATAR', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatar/');
define('WEB_PATH_PHONG', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/');

$cacDuoiChoPhep = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$cacMimeChoPhep = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

/**
 * Xử lý lưu ảnh đại diện người dùng từ máy
 * @param array $file Phần tử trong $_FILES (ví dụ $_FILES['anh_dai_dien'])
 * @param int $maNguoiDung Mã người dùng
 * @return array ['ok' => bool, 'path' => string, 'error' => string]
 */
function xuLyUploadAvatar($file, int $maNguoiDung): array
{
    global $cacDuoiChoPhep, $cacMimeChoPhep;

    if (!isset($file) || !is_array($file) || empty($file['name'])) {
        return ['ok' => false, 'path' => '', 'error' => 'Chưa chọn tập tin ảnh.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errMap = [
            UPLOAD_ERR_INI_SIZE   => 'Kích thước ảnh vượt quá giới hạn cấu hình PHP.',
            UPLOAD_ERR_FORM_SIZE  => 'Kích thước ảnh vượt quá giới hạn form.',
            UPLOAD_ERR_PARTIAL    => 'Tập tin tải lên chưa trọn vẹn.',
            UPLOAD_ERR_NO_FILE    => 'Chưa có tập tin nào được chọn.',
            UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm trên máy chủ.',
            UPLOAD_ERR_CANT_WRITE => 'Không thể ghi tập tin vào bộ nhớ đĩa.',
        ];
        return ['ok' => false, 'path' => '', 'error' => $errMap[$file['error']] ?? 'Lỗi tải ảnh lên máy chủ.'];
    }

    // Giới hạn 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['ok' => false, 'path' => '', 'error' => 'Ảnh đại diện không được vượt quá 5MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $cacDuoiChoPhep)) {
        return ['ok' => false, 'path' => '', 'error' => 'Chỉ chấp nhận định dạng ảnh JPG, JPEG, PNG, WEBP, GIF.'];
    }

    // Kiểm tra tính hợp lệ của ảnh
    $imgInfo = @getimagesize($file['tmp_name']);
    if ($imgInfo === false || !in_array($imgInfo['mime'], $cacMimeChoPhep)) {
        return ['ok' => false, 'path' => '', 'error' => 'Tập tin tải lên không phải là ảnh hợp lệ.'];
    }

    if (!is_dir(UPLOAD_DIR_AVATAR)) {
        @mkdir(UPLOAD_DIR_AVATAR, 0777, true);
    }

    $safeName = 'avt_' . $maNguoiDung . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = UPLOAD_DIR_AVATAR . $safeName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['ok' => true, 'path' => WEB_PATH_AVATAR . $safeName, 'error' => ''];
    }

    return ['ok' => false, 'path' => '', 'error' => 'Không thể di chuyển tập tin vào thư mục lưu trữ ảnh.'];
}

/**
 * Xử lý lưu danh sách ảnh phòng trọ được tải lên từ máy tính
 * @param array $files Phần tử $_FILES['anh_phong'] (hỗ trợ multiple)
 * @param int $maPhong Mã phòng trọ
 * @return array Mảng các đường dẫn tương đối đã lưu thành công
 */
function xuLyUploadAnhPhongTro($files, int $maPhong): array
{
    global $cacDuoiChoPhep, $cacMimeChoPhep;

    $duongDanThanhCong = [];
    if (!isset($files) || !is_array($files) || empty($files['name'])) {
        return $duongDanThanhCong;
    }

    if (!is_dir(UPLOAD_DIR_PHONG)) {
        @mkdir(UPLOAD_DIR_PHONG, 0777, true);
    }

    // Chuẩn hóa danh sách file (trường hợp multiple file)
    $fileList = [];
    if (is_array($files['name'])) {
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK && !empty($files['name'][$i])) {
                $fileList[] = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                ];
            }
        }
    } else {
        if ($files['error'] === UPLOAD_ERR_OK && !empty($files['name'])) {
            $fileList[] = $files;
        }
    }

    foreach ($fileList as $idx => $f) {
        if ($f['size'] > 10 * 1024 * 1024) continue; // Bỏ qua nếu > 10MB
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $cacDuoiChoPhep)) continue;

        $imgInfo = @getimagesize($f['tmp_name']);
        if ($imgInfo === false || !in_array($imgInfo['mime'], $cacMimeChoPhep)) continue;

        $safeName = 'phong_' . $maPhong . '_' . time() . '_' . ($idx + 1) . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $target = UPLOAD_DIR_PHONG . $safeName;

        if (move_uploaded_file($f['tmp_name'], $target)) {
            $duongDanThanhCong[] = WEB_PATH_PHONG . $safeName;
        }
    }

    return $duongDanThanhCong;
}
