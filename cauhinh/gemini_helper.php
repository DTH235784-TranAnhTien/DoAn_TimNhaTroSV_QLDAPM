<?php
/**
 * gemini_helper.php — Tích hợp Gemini API cho CampusNest AGU
 * Model: gemini-4-hydrogen (REST API, không dùng SDK)
 * Thay YOUR_GEMINI_API_KEY bằng API key thực tế tại https://aistudio.google.com
 */

define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'YOUR_GEMINI_API_KEY');
define('GEMINI_MODEL', 'gemini-4-hydrogen');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY);

/**
 * Phân tích mô tả phòng trọ và gợi ý nhãn phù hợp.
 * @param string $moTa  Mô tả phòng do chủ trọ nhập
 * @param array  $danhSachNhan  Mảng ['MaNhan' => int, 'TenNhan' => string]
 * @return array  Mảng MaNhan được gợi ý (tối đa 5)
 */
function geminiGanNhan(string $moTa, array $danhSachNhan): array {
    if (empty($moTa) || empty($danhSachNhan)) return [];

    // Nếu đã cấu hình API key thật và máy có bật cURL thì gọi Gemini REST API
    if (GEMINI_API_KEY !== '' && GEMINI_API_KEY !== 'YOUR_GEMINI_API_KEY' && function_exists('curl_init')) {
        $tenNhanList = array_map(fn($n) => $n['MaNhan'] . ': ' . $n['TenNhan'], $danhSachNhan);
        $prompt = "Bạn là AI hỗ trợ phân loại phòng trọ sinh viên Việt Nam.\n"
            . "Dựa trên mô tả phòng sau, hãy chọn TỐI ĐA 5 nhãn phù hợp nhất từ danh sách bên dưới.\n"
            . "Chỉ trả về danh sách MaNhan (số nguyên) cách nhau bằng dấu phẩy, không giải thích.\n\n"
            . "MÔ TẢ: " . $moTa . "\n\n"
            . "DANH SÁCH NHÃN:\n" . implode("\n", $tenNhanList) . "\n\n"
            . "KẾT QUẢ (chỉ liệt kê số MaNhan, VD: 1,3,5):";

        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['maxOutputTokens' => 50, 'temperature' => 0.1]
        ]);

        $ch = curl_init(GEMINI_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $text = preg_replace('/[^\d,]/', '', trim($text));
            $ids = array_filter(array_map('intval', explode(',', $text)));
            $validIds = array_column($danhSachNhan, 'MaNhan');
            $matched = array_values(array_intersect($ids, $validIds));
            if (!empty($matched)) {
                return array_slice($matched, 0, 5);
            }
        }
    }

    // Fallback cục bộ (khi chưa có API key hoặc mất mạng) — khớp cả TuKhoaNhanDien lẫn TenNhan
    $moTaLower = mb_strtolower($moTa, 'UTF-8');
    $ketQua = [];
    foreach ($danhSachNhan as $nhan) {
        $diem = 0;
        // 1. Khớp theo danh sách cụm từ khóa chuẩn trong NhanDanhMuc.TuKhoaNhanDien
        if (!empty($nhan['TuKhoaNhanDien'])) {
            $cumTu = array_filter(array_map('trim', explode(',', mb_strtolower($nhan['TuKhoaNhanDien'], 'UTF-8'))));
            foreach ($cumTu as $kw) {
                if ($kw !== '' && mb_strpos($moTaLower, $kw, 0, 'UTF-8') !== false) {
                    $diem += 2;
                }
            }
        }
        // 2. Khớp theo từng từ trong TenNhan
        $tenLower = mb_strtolower($nhan['TenNhan'], 'UTF-8');
        $tuKhoa = array_filter(preg_split('/[\s,\/\-]+/u', $tenLower), fn($w) => mb_strlen($w, 'UTF-8') >= 2);
        foreach ($tuKhoa as $kw) {
            if (mb_strpos($moTaLower, $kw, 0, 'UTF-8') !== false) {
                $diem++;
            }
        }
        if ($diem > 0) {
            $ketQua[$nhan['MaNhan']] = $diem;
        }
    }
    arsort($ketQua);
    return array_slice(array_keys($ketQua), 0, 5);
}
