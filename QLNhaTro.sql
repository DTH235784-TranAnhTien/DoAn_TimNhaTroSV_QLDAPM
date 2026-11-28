-- ====================================================================
-- HỆ THỐNG QUẢN LÝ WEBSITE TÌM KIẾM NHÀ TRỌ SINH VIÊN (ĐH AN GIANG)
-- File DDL tạo toàn bộ Cơ sở dữ liệu và các Bảng
-- ====================================================================

-- 1. Tạo cơ sở dữ liệu chuẩn UTF-8 Tiếng Việt (an toàn trên Windows XAMPP, tránh lỗi #1010)
CREATE DATABASE IF NOT EXISTS QuanLyNhaTro
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE QuanLyNhaTro;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS BaoCaoViPham, HoSoGhepPhong, PhongTro_Nhan, NhanDanhMuc, DanhSachYeuThich, DanhGia, LichHen, PhongTro_TienIch, TienIch, HinhAnhPhongTro, PhongTro, NhaTro, NguoiDung;
SET FOREIGN_KEY_CHECKS = 1;

-- ====================================================================
-- 2. BẢNG NGƯỜI DÙNG (Sinh viên, Chủ trọ, Quản trị viên)
-- ====================================================================
CREATE TABLE NguoiDung (
    MaNguoiDung INT AUTO_INCREMENT PRIMARY KEY,
    HoTen VARCHAR(100) NOT NULL,
    SoDienThoai VARCHAR(15),
    Email VARCHAR(100) UNIQUE NOT NULL,
    MatKhau VARCHAR(255) NOT NULL,
    AnhDaiDien VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    VaiTro ENUM('SinhVien', 'ChuTro', 'QuanTriVien') DEFAULT 'SinhVien',
    
    -- Dành riêng cho Chủ trọ xác thực chính chủ (theo Báo cáo phạm vi)
    SoCCCD VARCHAR(20),
    AnhXacThucCCCD VARCHAR(255),
    DaXacThucChinhChu BOOLEAN DEFAULT FALSE,
    
    TrangThai ENUM('HoatDong', 'BiKhoa') DEFAULT 'HoatDong',
    NgayTao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ====================================================================
-- 3. BẢNG NHÀ TRỌ (Thông tin khu nhà trọ/dãy trọ của chủ trọ)
-- ====================================================================
CREATE TABLE NhaTro (
    MaNhaTro INT AUTO_INCREMENT PRIMARY KEY,
    MaChuTro INT NOT NULL,
    TenNhaTro VARCHAR(150) NOT NULL,
    DiaChi VARCHAR(255) NOT NULL,
    MoTa TEXT,
    ViDo DECIMAL(10,7),          -- Tọa độ bản đồ số
    KinhDo DECIMAL(10,7),        -- Tọa độ bản đồ số
    NgayTao DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (MaChuTro) 
        REFERENCES NguoiDung(MaNguoiDung) 
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ====================================================================
-- 4. BẢNG PHÒNG TRỌ (Chi tiết từng phòng cho thuê)
-- ====================================================================
CREATE TABLE PhongTro (
    MaPhong INT AUTO_INCREMENT PRIMARY KEY,
    MaNhaTro INT NOT NULL,
    TenPhong VARCHAR(100) NOT NULL,
    GiaThue DECIMAL(12,2) NOT NULL,
    DienTich DECIMAL(6,2),
    TienDien DECIMAL(10,2),
    TienNuoc DECIMAL(10,2),
    TienWifi DECIMAL(10,2) DEFAULT 0,
    ChiPhiKhac TEXT,
    MoTa TEXT,
    
    -- Tình trạng phòng thực tế
    TrangThai ENUM('ConPhong', 'DaThue', 'DangBaoTri') DEFAULT 'ConPhong',
    
    -- Trạng thái kiểm duyệt tin đăng từ Admin (chống tin rác, tin ảo)
    TrangThaiDuyet ENUM('ChoDuyet', 'DaDuyet', 'TuChoi') DEFAULT 'ChoDuyet',
    
    NgayDang DATETIME DEFAULT CURRENT_TIMESTAMP,
    NgayCapNhat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (MaNhaTro)
        REFERENCES NhaTro(MaNhaTro)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ====================================================================
-- 5. BẢNG HÌNH ẢNH PHÒNG TRỌ (Một phòng có nhiều ảnh thực tế)
-- ====================================================================
CREATE TABLE HinhAnhPhongTro (
    MaHinh INT AUTO_INCREMENT PRIMARY KEY,
    MaPhong INT NOT NULL,
    DuongDan VARCHAR(255) NOT NULL,
    LaAnhChinh BOOLEAN DEFAULT FALSE,
    NgayTaiLen DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (MaPhong)
        REFERENCES PhongTro(MaPhong)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ====================================================================
-- 6. BẢNG TIỆN ÍCH (Wifi, Gác lửng, Máy lạnh, Vệ sinh riêng,...)
-- ====================================================================
CREATE TABLE TienIch (
    MaTienIch INT AUTO_INCREMENT PRIMARY KEY,
    TenTienIch VARCHAR(100) NOT NULL UNIQUE,
    Icon VARCHAR(50) DEFAULT 'bi-check-circle' -- Tên icon FontAwesome/Bootstrap
) ENGINE=InnoDB;

-- Bảng liên kết nhiều - nhiều: Phòng trọ & Tiện ích
CREATE TABLE PhongTro_TienIch (
    MaPhong INT NOT NULL,
    MaTienIch INT NOT NULL,
    PRIMARY KEY (MaPhong, MaTienIch),
    FOREIGN KEY (MaPhong) REFERENCES PhongTro(MaPhong) ON DELETE CASCADE,
    FOREIGN KEY (MaTienIch) REFERENCES TienIch(MaTienIch) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ====================================================================
-- 7. BẢNG ĐẶT LỊCH HẸN XEM PHÒNG (Sinh viên đặt lịch với Chủ trọ)
-- ====================================================================
CREATE TABLE LichHen (
    MaLichHen INT AUTO_INCREMENT PRIMARY KEY,
    MaSinhVien INT NOT NULL,
    MaPhong INT NOT NULL,
    NgayHen DATE NOT NULL,
    GioHen TIME NOT NULL,
    GhiChu TEXT,
    PhanHoiChuTro TEXT,
    TrangThai ENUM('ChoDuyet', 'DaXacNhan', 'TuChoi', 'DaHuy', 'HoanThanh') DEFAULT 'ChoDuyet',
    NgayTao DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (MaSinhVien) REFERENCES NguoiDung(MaNguoiDung) ON DELETE CASCADE,
    FOREIGN KEY (MaPhong) REFERENCES PhongTro(MaPhong) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ====================================================================
-- 8. BẢNG ĐÁNH GIÁ & PHẢN HỒI (Review, chấm điểm sao từ sinh viên)
-- ====================================================================
CREATE TABLE DanhGia (
    MaDanhGia INT AUTO_INCREMENT PRIMARY KEY,
    MaSinhVien INT NOT NULL,
    MaPhong INT NOT NULL,
    SoSao INT NOT NULL CHECK (SoSao BETWEEN 1 AND 5),
    DiemAnNinh INT DEFAULT 5 CHECK (DiemAnNinh BETWEEN 1 AND 5),
    DiemGiaCa INT DEFAULT 5 CHECK (DiemGiaCa BETWEEN 1 AND 5),
    DiemThaiDoChuTro INT DEFAULT 5 CHECK (DiemThaiDoChuTro BETWEEN 1 AND 5),
    NoiDung TEXT,
    NgayDanhGia DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (MaSinhVien) REFERENCES NguoiDung(MaNguoiDung) ON DELETE CASCADE,
    FOREIGN KEY (MaPhong) REFERENCES PhongTro(MaPhong) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ====================================================================
-- 9. BẢNG TIN LƯU / YÊU THÍCH (Sinh viên lưu phòng trọ để xem lại)
-- ====================================================================
CREATE TABLE DanhSachYeuThich (
    MaYeuThich INT AUTO_INCREMENT PRIMARY KEY,
    MaSinhVien INT NOT NULL,
    MaPhong INT NOT NULL,
    NgayLuu DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_favorite (MaSinhVien, MaPhong),
    FOREIGN KEY (MaSinhVien) REFERENCES NguoiDung(MaNguoiDung) ON DELETE CASCADE,
    FOREIGN KEY (MaPhong) REFERENCES PhongTro(MaPhong) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ====================================================================
-- 10. BẢNG THẺ DANH MỤC / NHÃN TỰ ĐỘNG (Tính năng gắn tag tự động)
-- ====================================================================
CREATE TABLE NhanDanhMuc (
    MaNhan INT AUTO_INCREMENT PRIMARY KEY,
    TenNhan VARCHAR(50) NOT NULL UNIQUE,  -- VD: Gác lửng, Giờ tự do, Không chung chủ
    TuKhoaNhanDien VARCHAR(255)           -- Danh sách từ khóa để regex/match văn bản
) ENGINE=InnoDB;

-- Bảng liên kết nhiều - nhiều: Phòng trọ & Nhãn tự động
CREATE TABLE PhongTro_Nhan (
    MaPhong INT NOT NULL,
    MaNhan INT NOT NULL,
    PRIMARY KEY (MaPhong, MaNhan),
    FOREIGN KEY (MaPhong) REFERENCES PhongTro(MaPhong) ON DELETE CASCADE,
    FOREIGN KEY (MaNhan) REFERENCES NhanDanhMuc(MaNhan) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ====================================================================
-- 11. BẢNG HỒ SƠ GHÉP PHÒNG (Tính năng gợi ý bạn cùng phòng theo %)
-- ====================================================================
CREATE TABLE HoSoGhepPhong (
    MaHoSo INT AUTO_INCREMENT PRIMARY KEY,
    MaSinhVien INT NOT NULL UNIQUE,
    GioiTinh ENUM('Nam', 'Nu', 'Khac') NOT NULL,
    NganSachToiDa DECIMAL(12,2) NOT NULL,
    KhuVucUuTien VARCHAR(255),
    GioGiac ENUM('Som', 'Khuya', 'TuDo') DEFAULT 'TuDo',
    HutThuoc BOOLEAN DEFAULT FALSE,
    NuoiThuCung BOOLEAN DEFAULT FALSE,
    MoTaBanThan TEXT,
    TrangThai ENUM('DangTim', 'DaTimDuoc') DEFAULT 'DangTim',
    NgayTao DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (MaSinhVien) REFERENCES NguoiDung(MaNguoiDung) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ====================================================================
-- 12. BẢNG BÁO CÁO VI PHẠM (Kiểm duyệt tin giả, lừa đảo cọc - Admin)
-- ====================================================================
CREATE TABLE BaoCaoViPham (
    MaBaoCao INT AUTO_INCREMENT PRIMARY KEY,
    MaNguoiBaoCao INT NOT NULL,
    MaPhong INT NOT NULL,
    LyDo VARCHAR(255) NOT NULL,
    ChiTiet TEXT,
    TrangThai ENUM('ChoXuLy', 'DaXuLy', 'BoQua') DEFAULT 'ChoXuLy',
    NgayBaoCao DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (MaNguoiBaoCao) REFERENCES NguoiDung(MaNguoiDung) ON DELETE CASCADE,
    FOREIGN KEY (MaPhong) REFERENCES PhongTro(MaPhong) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO TienIch (TenTienIch, Icon) VALUES
('Wifi cáp quang', 'wifi'),
('Máy lạnh / Điều hòa', 'ac_unit'),
('Gác lửng', 'stairs'),
('Vệ sinh riêng khép kín', 'bathtub'),
('Camera an ninh 24/7', 'videocam'),
('Tủ lạnh', 'kitchen'),
('Máy giặt dùng chung', 'local_laundry_service'),
('Chỗ để xe an toàn', 'two_wheeler'),
('Giờ giấc tự do', 'schedule'),
('Không chung chủ', 'key');

-- seed_demo.sql -- Du lieu mau day du cho CampusNest AGU
-- Mat khau chung cho TAT CA tai khoan demo (Admin, ChuTro, SinhVien): 12345678
-- (Trong CSDL duoc ma hoa bcrypt theo chuan password_verify cua PHP)

-- 1. Cap nhat avatar & mat khau chung (12345678) cho tat ca tai khoan demo
INSERT INTO NguoiDung (HoTen, SoDienThoai, Email, MatKhau, AnhDaiDien, VaiTro, DaXacThucChinhChu, TrangThai) VALUES
('Nguyễn Mai Anh (BCH Đoàn)', '0296384311', 'admin@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatar/anh5.jpg', 'QuanTriVien', 0, 'HoatDong'),
('Cô Bảy Nguyễn Thị Bảy', '0918123899', 'cobay@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatarchutro/chutro1.jpg', 'ChuTro', 1, 'HoatDong'),
('Trần Thanh Hải', '0918345678', 'sv.k22@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatar/anh3.jpg', 'SinhVien', 0, 'HoatDong'),
('Chú Tư Lê Văn Tư', '0918456789', 'chutu@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatarchutro/chutro4.jpg', 'ChuTro', 1, 'HoatDong'),
('Cô Sáu Phạm Thị Sáu', '0919234567', 'cosau@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatarchutro/chutro2.jpg', 'ChuTro', 1, 'HoatDong'),
('Anh Minh Trần Văn Minh', '0939112233', 'anhminh@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatarchutro/chutro5.jpg', 'ChuTro', 0, 'HoatDong'),
('Chị Lan Trần Thị Lan', '0918555111', 'chilan@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatarchutro/chutro3.jpg', 'ChuTro', 1, 'HoatDong'),
('Nguyễn Hoàng Nam', '0987111222', 'nam.k23@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatar/anh2.jpg', 'SinhVien', 0, 'HoatDong'),
('Lê Thị Thảo Vy', '0978333444', 'vy.k22@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatar/anh5.jpg', 'SinhVien', 0, 'HoatDong'),
('Phạm Thị Ngọc Hân', '0966555666', 'khang.k24@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatar/anh9.jpg', 'SinhVien', 0, 'HoatDong'),
('Võ Ngọc Lan Anh', '0945777888', 'lananh.k23@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatar/anh6.jpg', 'SinhVien', 0, 'HoatDong'),
('Đặng Quốc Bảo', '0912999000', 'bao.k22@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatar/anh1.jpg', 'SinhVien', 0, 'HoatDong'),
('Trần Minh Thư', '0938111333', 'minhthu.k23@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatar/anh7.jpg', 'SinhVien', 0, 'HoatDong'),
('Lê Quốc Huy', '0917222444', 'quochuy.k24@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatar/anh4.jpg', 'SinhVien', 0, 'HoatDong'),
('Nguyễn Ngọc Huyền', '0976555777', 'ngochuyen.k22@agu.edu.vn', '12345678', '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/avatar/anh8.jpg', 'SinhVien', 0, 'HoatDong')
ON DUPLICATE KEY UPDATE
MatKhau = VALUES(MatKhau),
AnhDaiDien = VALUES(AnhDaiDien),
HoTen = VALUES(HoTen),
SoDienThoai = VALUES(SoDienThoai);

-- 2. Nhan danh muc tu dong
INSERT IGNORE INTO NhanDanhMuc (TenNhan, TuKhoaNhanDien) VALUES
('Gần ĐH An Giang', 'ung văn khiêm,đông xuyên,gần trường,cổng trường'),
('Giờ giấc tự do', 'tự do,không chung chủ,24/7'),
('Có gác lửng', 'gác lửng,có gác'),
('Full nội thất', 'máy lạnh,tủ lạnh,nội thất,giường'),
('Giá sinh viên', 'giá rẻ,sinh viên,tiết kiệm');

-- 3. Nha tro (10 dia chi & toa do thuc te khop duong tren Google Maps tai Long Xuyen)
INSERT INTO NhaTro (MaChuTro, TenNhaTro, DiaChi, MoTa, ViDo, KinhDo)
SELECT u.MaNguoiDung, 'Nhà trọ Cô Bảy', '25 Ung Văn Khiêm, Đông Xuyên, Long Xuyên', 'Cách cổng ĐH An Giang 200m, khu vực an ninh, có camera 24/7.', 10.3728000, 105.4335000
FROM NguoiDung u WHERE u.Email = 'cobay@agu.edu.vn'
AND NOT EXISTS (SELECT 1 FROM NhaTro WHERE TenNhaTro = 'Nhà trọ Cô Bảy');

INSERT INTO NhaTro (MaChuTro, TenNhaTro, DiaChi, MoTa, ViDo, KinhDo)
SELECT u.MaNguoiDung, 'Nhà trọ Cô Bảy (Cơ sở 2)', '68 Ung Văn Khiêm, Đông Xuyên, Long Xuyên', 'Gần Bệnh viện ĐK An Giang, dọc đường Ung Văn Khiêm.', 10.3708420, 105.4358930
FROM NguoiDung u WHERE u.Email = 'cobay@agu.edu.vn'
AND NOT EXISTS (SELECT 1 FROM NhaTro WHERE TenNhaTro = 'Nhà trọ Cô Bảy (Cơ sở 2)');

INSERT INTO NhaTro (MaChuTro, TenNhaTro, DiaChi, MoTa, ViDo, KinhDo)
SELECT u.MaNguoiDung, 'Nhà trọ Chú Tư', '45 Hà Hoàng Hổ, Mỹ Xuyên, Long Xuyên', 'Dãy trọ thoáng mát, đường lớn dễ đi lại, gần chợ Mỹ Xuyên.', 10.3765000, 105.4330000
FROM NguoiDung u WHERE u.Email = 'chutu@agu.edu.vn'
AND NOT EXISTS (SELECT 1 FROM NhaTro WHERE TenNhaTro = 'Nhà trọ Chú Tư');

INSERT INTO NhaTro (MaChuTro, TenNhaTro, DiaChi, MoTa, ViDo, KinhDo)
SELECT u.MaNguoiDung, 'Nhà trọ Chú Tư (Hẻm 2)', '112 Trần Hưng Đạo, Mỹ Xuyên, Long Xuyên', 'Hẻm xe máy rộng, an ninh, gần siêu thị Coopmart.', 10.3773590, 105.4402270
FROM NguoiDung u WHERE u.Email = 'chutu@agu.edu.vn'
AND NOT EXISTS (SELECT 1 FROM NhaTro WHERE TenNhaTro = 'Nhà trọ Chú Tư (Hẻm 2)');

INSERT INTO NhaTro (MaChuTro, TenNhaTro, DiaChi, MoTa, ViDo, KinhDo)
SELECT u.MaNguoiDung, 'Khu trọ Sinh Viên Cô Sáu', '102 Lý Thái Tổ, Mỹ Long, Long Xuyên', 'Phòng mới sạch sẽ, giờ giấc tự do, wifi mạnh.', 10.3750000, 105.4380000
FROM NguoiDung u WHERE u.Email = 'cosau@agu.edu.vn'
AND NOT EXISTS (SELECT 1 FROM NhaTro WHERE TenNhaTro = 'Khu trọ Sinh Viên Cô Sáu');

INSERT INTO NhaTro (MaChuTro, TenNhaTro, DiaChi, MoTa, ViDo, KinhDo)
SELECT u.MaNguoiDung, 'Nhà trọ Cô Sáu (Mỹ Quý)', '58 Phạm Cự Lượng, Mỹ Quý, Long Xuyên', 'Khu dân cư yên tĩnh, đi xe 5 phút tới ĐH An Giang.', 10.3660000, 105.4486000
FROM NguoiDung u WHERE u.Email = 'cosau@agu.edu.vn'
AND NOT EXISTS (SELECT 1 FROM NhaTro WHERE TenNhaTro = 'Nhà trọ Cô Sáu (Mỹ Quý)');

INSERT INTO NhaTro (MaChuTro, TenNhaTro, DiaChi, MoTa, ViDo, KinhDo)
SELECT u.MaNguoiDung, 'Dãy trọ Xanh AGU', '15 Nguyễn Huệ, Mỹ Long, Long Xuyên', 'Gần Co.opmart Long Xuyên, nhiều cây xanh, không chung chủ.', 10.3817000, 105.4400000
FROM NguoiDung u WHERE u.Email = 'chilan@agu.edu.vn'
AND NOT EXISTS (SELECT 1 FROM NhaTro WHERE TenNhaTro = 'Dãy trọ Xanh AGU');

INSERT INTO NhaTro (MaChuTro, TenNhaTro, DiaChi, MoTa, ViDo, KinhDo)
SELECT u.MaNguoiDung, 'Nhà trọ Chị Lan (Mỹ Xuyên)', '89 Lê Triệu Kiết, Mỹ Xuyên, Long Xuyên', 'Gần chợ Mỹ Xuyên, thuận tiện ăn uống và đi học.', 10.3891670, 105.4358330
FROM NguoiDung u WHERE u.Email = 'chilan@agu.edu.vn'
AND NOT EXISTS (SELECT 1 FROM NhaTro WHERE TenNhaTro = 'Nhà trọ Chị Lan (Mỹ Xuyên)');

INSERT INTO NhaTro (MaChuTro, TenNhaTro, DiaChi, MoTa, ViDo, KinhDo)
SELECT u.MaNguoiDung, 'Nhà trọ An Bình', '27 Nguyễn Thái Học, Mỹ Bình, Long Xuyên', 'Không gian yên tĩnh, phù hợp sinh viên học tập.', 10.3850000, 105.4300000
FROM NguoiDung u WHERE u.Email = 'anhminh@agu.edu.vn'
AND NOT EXISTS (SELECT 1 FROM NhaTro WHERE TenNhaTro = 'Nhà trọ An Bình');

INSERT INTO NhaTro (MaChuTro, TenNhaTro, DiaChi, MoTa, ViDo, KinhDo)
SELECT u.MaNguoiDung, 'Nhà trọ Minh Khang', '74 Thoại Ngọc Hầu, Mỹ Long, Long Xuyên', 'Khu trọ mới xây dựng, đầy đủ tiện nghi hiện đại.', 10.3756200, 105.4474400
FROM NguoiDung u WHERE u.Email = 'anhminh@agu.edu.vn'
AND NOT EXISTS (SELECT 1 FROM NhaTro WHERE TenNhaTro = 'Nhà trọ Minh Khang');

-- 4. Phong tro (10 phong tuong ung 10 bo anh trong giaodien/hinhanh/phongtro/)
INSERT INTO PhongTro (MaNhaTro, TenPhong, GiaThue, DienTich, TienDien, TienNuoc, TienWifi, MoTa, TrangThai, TrangThaiDuyet)
SELECT (SELECT MaNhaTro FROM NhaTro WHERE TenNhaTro='Nhà trọ Cô Bảy' LIMIT 1),
       'Phòng 101 - Gác lửng thoáng mát', 1800000, 22.0, 3500, 12000, 50000,
       'Phòng rộng rãi có gác lửng, cửa sổ thoáng, cách ĐH An Giang chỉ 3 phút đi bộ. Có kệ bếp và WC riêng.', 'ConPhong', 'DaDuyet'
WHERE NOT EXISTS (SELECT 1 FROM PhongTro WHERE TenPhong='Phòng 101 - Gác lửng thoáng mát');

INSERT INTO PhongTro (MaNhaTro, TenPhong, GiaThue, DienTich, TienDien, TienNuoc, TienWifi, MoTa, TrangThai, TrangThaiDuyet)
SELECT (SELECT MaNhaTro FROM NhaTro WHERE TenNhaTro='Nhà trọ Cô Bảy (Cơ sở 2)' LIMIT 1),
       'Phòng 202 - Full nội thất máy lạnh', 2400000, 25.0, 3500, 12000, 0,
       'Trang bị sẵn máy lạnh, tủ lạnh, giường nệm và bàn học. Miễn phí wifi cáp quang tốc độ cao.', 'ConPhong', 'DaDuyet'
WHERE NOT EXISTS (SELECT 1 FROM PhongTro WHERE TenPhong='Phòng 202 - Full nội thất máy lạnh');

INSERT INTO PhongTro (MaNhaTro, TenPhong, GiaThue, DienTich, TienDien, TienNuoc, TienWifi, MoTa, TrangThai, TrangThaiDuyet)
SELECT (SELECT MaNhaTro FROM NhaTro WHERE TenNhaTro='Nhà trọ Chú Tư' LIMIT 1),
       'Phòng trọ 10 - Giá sinh viên', 1350000, 16.0, 3000, 10000, 30000,
       'Phòng gọn gàng sạch sẽ, phù hợp 1-2 bạn sinh viên muốn tiết kiệm chi phí.', 'ConPhong', 'DaDuyet'
WHERE NOT EXISTS (SELECT 1 FROM PhongTro WHERE TenPhong='Phòng trọ 10 - Giá sinh viên');

INSERT INTO PhongTro (MaNhaTro, TenPhong, GiaThue, DienTich, TienDien, TienNuoc, TienWifi, MoTa, TrangThai, TrangThaiDuyet)
SELECT (SELECT MaNhaTro FROM NhaTro WHERE TenNhaTro='Nhà trọ Chú Tư (Hẻm 2)' LIMIT 1),
       'Phòng trọ 20 - Ban công rộng rãi', 1950000, 20.0, 3500, 12000, 50000,
       'Có ban công phơi đồ riêng, ánh sáng tự nhiên tốt, khu vực an ninh có khóa vân tay.', 'ConPhong', 'DaDuyet'
WHERE NOT EXISTS (SELECT 1 FROM PhongTro WHERE TenPhong='Phòng trọ 20 - Ban công rộng rãi');

INSERT INTO PhongTro (MaNhaTro, TenPhong, GiaThue, DienTich, TienDien, TienNuoc, TienWifi, MoTa, TrangThai, TrangThaiDuyet)
SELECT (SELECT MaNhaTro FROM NhaTro WHERE TenNhaTro='Khu trọ Sinh Viên Cô Sáu' LIMIT 1),
       'Phòng trọ 30 - Yên tĩnh gần cổng ĐH', 1500000, 18.0, 3500, 12000, 40000,
       'Khu trọ toàn sinh viên, giờ giấc tự do, có chỗ để xe miễn phí trong nhà.', 'ConPhong', 'DaDuyet'
WHERE NOT EXISTS (SELECT 1 FROM PhongTro WHERE TenPhong='Phòng trọ 30 - Yên tĩnh gần cổng ĐH');

INSERT INTO PhongTro (MaNhaTro, TenPhong, GiaThue, DienTich, TienDien, TienNuoc, TienWifi, MoTa, TrangThai, TrangThaiDuyet)
SELECT (SELECT MaNhaTro FROM NhaTro WHERE TenNhaTro='Nhà trọ Cô Sáu (Mỹ Quý)' LIMIT 1),
       'Phòng trọ 40 - Giờ giấc tự do', 1700000, 20.0, 3500, 12000, 0,
       'Không chung chủ, đi về thoải mái bằng thẻ từ. Phòng ốp gạch sạch sẽ, WC khép kín.', 'ConPhong', 'DaDuyet'
WHERE NOT EXISTS (SELECT 1 FROM PhongTro WHERE TenPhong='Phòng trọ 40 - Giờ giấc tự do');

INSERT INTO PhongTro (MaNhaTro, TenPhong, GiaThue, DienTich, TienDien, TienNuoc, TienWifi, MoTa, TrangThai, TrangThaiDuyet)
SELECT (SELECT MaNhaTro FROM NhaTro WHERE TenNhaTro='Dãy trọ Xanh AGU' LIMIT 1),
       'Phòng trọ 50 - Studio mini hiện đại', 2600000, 26.0, 3500, 15000, 0,
       'Thiết kế kiểu studio mới xây, đầy đủ máy lạnh, tủ đồ, kệ bếp và máy giặt dùng chung.', 'ConPhong', 'DaDuyet'
WHERE NOT EXISTS (SELECT 1 FROM PhongTro WHERE TenPhong='Phòng trọ 50 - Studio mini hiện đại');

INSERT INTO PhongTro (MaNhaTro, TenPhong, GiaThue, DienTich, TienDien, TienNuoc, TienWifi, MoTa, TrangThai, TrangThaiDuyet)
SELECT (SELECT MaNhaTro FROM NhaTro WHERE TenNhaTro='Nhà trọ Chị Lan (Mỹ Xuyên)' LIMIT 1),
       'Phòng trọ 60 - Gần chợ Mỹ Xuyên', 1850000, 21.0, 3500, 12000, 50000,
       'Thuận tiện đi chợ, siêu thị và các tuyến xe buýt. Khu dân cư văn minh, yên tĩnh.', 'ConPhong', 'DaDuyet'
WHERE NOT EXISTS (SELECT 1 FROM PhongTro WHERE TenPhong='Phòng trọ 60 - Gần chợ Mỹ Xuyên');

INSERT INTO PhongTro (MaNhaTro, TenPhong, GiaThue, DienTich, TienDien, TienNuoc, TienWifi, MoTa, TrangThai, TrangThaiDuyet)
SELECT (SELECT MaNhaTro FROM NhaTro WHERE TenNhaTro='Nhà trọ An Bình' LIMIT 1),
       'Phòng trọ 70 - Không chung chủ', 2100000, 23.0, 3500, 12000, 50000,
       'Phòng rộng rãi cho nhóm 2-3 bạn ở ghép, có gác và khu phơi đồ riêng.', 'DaThue', 'DaDuyet'
WHERE NOT EXISTS (SELECT 1 FROM PhongTro WHERE TenPhong='Phòng trọ 70 - Không chung chủ');

INSERT INTO PhongTro (MaNhaTro, TenPhong, GiaThue, DienTich, TienDien, TienNuoc, TienWifi, MoTa, TrangThai, TrangThaiDuyet)
SELECT (SELECT MaNhaTro FROM NhaTro WHERE TenNhaTro='Nhà trọ Minh Khang' LIMIT 1),
       'Phòng trọ 80 - Phòng mới xây chờ duyệt', 2200000, 24.0, 3500, 12000, 0,
       'Phòng mới hoàn thiện, nội thất cơ bản, đang chờ quản trị viên duyệt tin.', 'ConPhong', 'ChoDuyet'
WHERE NOT EXISTS (SELECT 1 FROM PhongTro WHERE TenPhong='Phòng trọ 80 - Phòng mới xây chờ duyệt');


-- 5. Hinh anh phong tro (khop theo bo so anh trong giaodien/hinhanh/phongtro/)
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/motchammot.jpg', 1 FROM PhongTro p WHERE p.TenPhong = 'Phòng 101 - Gác lửng thoáng mát' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%motchammot.jpg');
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/motchamhai.jpg', 0 FROM PhongTro p WHERE p.TenPhong = 'Phòng 101 - Gác lửng thoáng mát' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%motchamhai.jpg');
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/motchamba.jpg', 0 FROM PhongTro p WHERE p.TenPhong = 'Phòng 101 - Gác lửng thoáng mát' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%motchamba.jpg');

INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/phong_1_1795619458_1_69821c.jpg', 1 FROM PhongTro p WHERE p.TenPhong = 'Phòng 202 - Full nội thất máy lạnh' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%69821c.jpg');
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/phong_1_1795619458_2_59eb75.jpg', 0 FROM PhongTro p WHERE p.TenPhong = 'Phòng 202 - Full nội thất máy lạnh' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%59eb75.jpg');
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/phong_1_1795619458_3_c52c17.jpg', 0 FROM PhongTro p WHERE p.TenPhong = 'Phòng 202 - Full nội thất máy lạnh' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%c52c17.jpg');

INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro10.jpg', 1 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 10 - Giá sinh viên' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro10.jpg');

INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro20.jpg', 1 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 20 - Ban công rộng rãi' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro20.jpg');
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro21.jpg', 0 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 20 - Ban công rộng rãi' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro21.jpg');

INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro30.jpg', 1 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 30 - Yên tĩnh gần cổng ĐH' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro30.jpg');

INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro40.jpg', 1 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 40 - Giờ giấc tự do' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro40.jpg');
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro41.jpg', 0 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 40 - Giờ giấc tự do' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro41.jpg');

INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro50.jpg', 1 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 50 - Studio mini hiện đại' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro50.jpg');
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro51.jpg', 0 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 50 - Studio mini hiện đại' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro51.jpg');
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro52.jpg', 0 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 50 - Studio mini hiện đại' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro52.jpg');

INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro60.jpg', 1 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 60 - Gần chợ Mỹ Xuyên' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro60.jpg');
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro61.jpg', 0 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 60 - Gần chợ Mỹ Xuyên' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro61.jpg');
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro62.jpg', 0 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 60 - Gần chợ Mỹ Xuyên' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro62.jpg');

INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro70.jpg', 1 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 70 - Không chung chủ' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro70.jpg');
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro71.jpg', 0 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 70 - Không chung chủ' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro71.jpg');

INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro80.jpg', 1 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 80 - Phòng mới xây chờ duyệt' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro80.jpg');
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro81.jpg', 0 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 80 - Phòng mới xây chờ duyệt' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro81.jpg');
INSERT INTO HinhAnhPhongTro (MaPhong, DuongDan, LaAnhChinh)
SELECT p.MaPhong, '/DoAn_TimNhaTroSV_QLDAPM/giaodien/hinhanh/phongtro/tro82.jpg', 0 FROM PhongTro p WHERE p.TenPhong = 'Phòng trọ 80 - Phòng mới xây chờ duyệt' AND NOT EXISTS (SELECT 1 FROM HinhAnhPhongTro h WHERE h.MaPhong = p.MaPhong AND h.DuongDan LIKE '%tro82.jpg');

-- 6. Gan tien ich cho cac phong
INSERT IGNORE INTO PhongTro_TienIch (MaPhong, MaTienIch)
SELECT p.MaPhong, t.MaTienIch FROM PhongTro p JOIN TienIch t ON t.TenTienIch IN ('Wifi cáp quang', 'Vệ sinh riêng khép kín', 'Chỗ để xe an toàn');

INSERT IGNORE INTO PhongTro_TienIch (MaPhong, MaTienIch)
SELECT p.MaPhong, t.MaTienIch FROM PhongTro p JOIN TienIch t ON t.TenTienIch IN ('Gác lửng', 'Camera an ninh 24/7')
WHERE p.TenPhong IN ('Phòng 101 - Gác lửng thoáng mát', 'Phòng trọ 70 - Không chung chủ');

INSERT IGNORE INTO PhongTro_TienIch (MaPhong, MaTienIch)
SELECT p.MaPhong, t.MaTienIch FROM PhongTro p JOIN TienIch t ON t.TenTienIch IN ('Máy lạnh / Điều hòa', 'Tủ lạnh', 'Máy giặt dùng chung')
WHERE p.TenPhong IN ('Phòng 202 - Full nội thất máy lạnh', 'Phòng trọ 50 - Studio mini hiện đại');

INSERT IGNORE INTO PhongTro_TienIch (MaPhong, MaTienIch)
SELECT p.MaPhong, t.MaTienIch FROM PhongTro p JOIN TienIch t ON t.TenTienIch IN ('Giờ giấc tự do', 'Không chung chủ')
WHERE p.TenPhong IN ('Phòng trọ 30 - Yên tĩnh gần cổng ĐH', 'Phòng trọ 40 - Giờ giấc tự do', 'Phòng trọ 70 - Không chung chủ');

-- 7. Ho so ghep phong cho sinh vien
INSERT IGNORE INTO HoSoGhepPhong (MaSinhVien, GioiTinh, NganSachToiDa, KhuVucUuTien, GioGiac, HutThuoc, NuoiThuCung, MoTaBanThan, TrangThai)
SELECT MaNguoiDung, 'Nam', 1200000, 'Đông Xuyên - Gần ĐH An Giang', 'Som', 0, 0, 'Sinh viên năm 3 CNTT, sống gọn gàng, không hút thuốc, thích yên tĩnh để học bài.', 'DangTim'
FROM NguoiDung WHERE Email = 'sv.k22@agu.edu.vn';

INSERT IGNORE INTO HoSoGhepPhong (MaSinhVien, GioiTinh, NganSachToiDa, KhuVucUuTien, GioGiac, HutThuoc, NuoiThuCung, MoTaBanThan, TrangThai)
SELECT MaNguoiDung, 'Nam', 1500000, 'Mỹ Xuyên - Hà Hoàng Hổ', 'TuDo', 0, 0, 'Sinh viên năm 2 Kế toán, hòa đồng, nấu ăn chung được, chia đều tiền điện nước.', 'DangTim'
FROM NguoiDung WHERE Email = 'nam.k23@agu.edu.vn';

INSERT IGNORE INTO HoSoGhepPhong (MaSinhVien, GioiTinh, NganSachToiDa, KhuVucUuTien, GioGiac, HutThuoc, NuoiThuCung, MoTaBanThan, TrangThai)
SELECT MaNguoiDung, 'Nu', 1300000, 'Đông Xuyên - Ung Văn Khiêm', 'Som', 0, 0, 'Sinh viên Sư phạm năm 3, sạch sẽ, ngủ sớm trước 23h, mong tìm bạn nữ ở ghép lâu dài.', 'DangTim'
FROM NguoiDung WHERE Email = 'vy.k22@agu.edu.vn';

INSERT INTO HoSoGhepPhong (MaSinhVien, GioiTinh, NganSachToiDa, KhuVucUuTien, GioGiac, HutThuoc, NuoiThuCung, MoTaBanThan, TrangThai)
SELECT MaNguoiDung, 'Nu', 1100000, 'Mỹ Long - Lý Thái Tổ', 'Khuya', 0, 1, 'Sinh viên năm nhất Ngôn ngữ Anh, vui tính, thích mèo, hay học bài tối.', 'DangTim'
FROM NguoiDung WHERE Email = 'khang.k24@agu.edu.vn'
ON DUPLICATE KEY UPDATE GioiTinh = VALUES(GioiTinh), MoTaBanThan = VALUES(MoTaBanThan);

INSERT IGNORE INTO HoSoGhepPhong (MaSinhVien, GioiTinh, NganSachToiDa, KhuVucUuTien, GioGiac, HutThuoc, NuoiThuCung, MoTaBanThan, TrangThai)
SELECT MaNguoiDung, 'Nu', 1400000, 'Mỹ Xuyên - Võ Thị Sáu', 'TuDo', 0, 0, 'Sinh viên năm 2 Ngôn ngữ Anh, gọn gàng, thân thiện, tôn trọng không gian riêng.', 'DangTim'
FROM NguoiDung WHERE Email = 'lananh.k23@agu.edu.vn';

INSERT IGNORE INTO HoSoGhepPhong (MaSinhVien, GioiTinh, NganSachToiDa, KhuVucUuTien, GioGiac, HutThuoc, NuoiThuCung, MoTaBanThan, TrangThai)
SELECT MaNguoiDung, 'Nam', 1350000, 'Đông Xuyên - Ung Văn Khiêm', 'Som', 0, 0, 'Sinh viên năm 3 Quản trị kinh doanh, thích chạy bộ buổi sáng, sạch sẽ, không ồn ào.', 'DangTim'
FROM NguoiDung WHERE Email = 'bao.k22@agu.edu.vn';

INSERT IGNORE INTO HoSoGhepPhong (MaSinhVien, GioiTinh, NganSachToiDa, KhuVucUuTien, GioGiac, HutThuoc, NuoiThuCung, MoTaBanThan, TrangThai)
SELECT MaNguoiDung, 'Nu', 1500000, 'Mỹ Xuyên - Hà Hoàng Hổ', 'Som', 0, 1, 'Sinh viên năm 2 Tài chính ngân hàng, vui vẻ, thích nấu ăn và chăm cây xanh.', 'DangTim'
FROM NguoiDung WHERE Email = 'minhthu.k23@agu.edu.vn';

INSERT IGNORE INTO HoSoGhepPhong (MaSinhVien, GioiTinh, NganSachToiDa, KhuVucUuTien, GioGiac, HutThuoc, NuoiThuCung, MoTaBanThan, TrangThai)
SELECT MaNguoiDung, 'Nam', 1250000, 'Đông Xuyên - Gần cổng ĐH', 'TuDo', 0, 0, 'Sinh viên năm nhất Kỹ thuật phần mềm, dễ tính, hay học nhóm ở thư viện.', 'DangTim'
FROM NguoiDung WHERE Email = 'quochuy.k24@agu.edu.vn';

INSERT IGNORE INTO HoSoGhepPhong (MaSinhVien, GioiTinh, NganSachToiDa, KhuVucUuTien, GioGiac, HutThuoc, NuoiThuCung, MoTaBanThan, TrangThai)
SELECT MaNguoiDung, 'Nu', 1600000, 'Mỹ Bình - Nguyễn Thái Học', 'Khuya', 0, 0, 'Sinh viên năm 3 Thiết kế đồ họa, hay thức làm bài thiết kế, ngăn nắp, tôn trọng bạn cùng phòng.', 'DangTim'
FROM NguoiDung WHERE Email = 'ngochuyen.k22@agu.edu.vn';

-- 8. Lich hen xem phong mau
INSERT INTO LichHen (MaSinhVien, MaPhong, NgayHen, GioHen, GhiChu, PhanHoiChuTro, TrangThai)
SELECT (SELECT MaNguoiDung FROM NguoiDung WHERE Email='sv.k22@agu.edu.vn' LIMIT 1),
       (SELECT MaPhong FROM PhongTro WHERE TenPhong='Phòng 101 - Gác lửng thoáng mát' LIMIT 1),
       DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 'Em muốn xem phòng buổi sáng ạ.', NULL, 'ChoDuyet'
WHERE NOT EXISTS (SELECT 1 FROM LichHen WHERE GhiChu = 'Em muốn xem phòng buổi sáng ạ.');

INSERT INTO LichHen (MaSinhVien, MaPhong, NgayHen, GioHen, GhiChu, PhanHoiChuTro, TrangThai)
SELECT (SELECT MaNguoiDung FROM NguoiDung WHERE Email='nam.k23@agu.edu.vn' LIMIT 1),
       (SELECT MaPhong FROM PhongTro WHERE TenPhong='Phòng 202 - Full nội thất máy lạnh' LIMIT 1),
       DATE_ADD(CURDATE(), INTERVAL 2 DAY), '15:30:00', 'Em qua xem phòng 202 chiều thứ 5 nhé cô.', 'Cô ở nhà từ 15h, tới cổng gọi cô nha con.', 'DaXacNhan'
WHERE NOT EXISTS (SELECT 1 FROM LichHen WHERE GhiChu = 'Em qua xem phòng 202 chiều thứ 5 nhé cô.');

INSERT INTO LichHen (MaSinhVien, MaPhong, NgayHen, GioHen, GhiChu, PhanHoiChuTro, TrangThai)
SELECT (SELECT MaNguoiDung FROM NguoiDung WHERE Email='vy.k22@agu.edu.vn' LIMIT 1),
       (SELECT MaPhong FROM PhongTro WHERE TenPhong='Phòng trọ 30 - Yên tĩnh gần cổng ĐH' LIMIT 1),
       DATE_SUB(CURDATE(), INTERVAL 3 DAY), '10:00:00', 'Em đã xem phòng trọ 30.', 'Cảm ơn em đã ghé xem phòng.', 'HoanThanh'
WHERE NOT EXISTS (SELECT 1 FROM LichHen WHERE GhiChu = 'Em đã xem phòng trọ 30.');

-- 9. Danh gia mau
INSERT INTO DanhGia (MaSinhVien, MaPhong, SoSao, DiemAnNinh, DiemGiaCa, DiemThaiDoChuTro, NoiDung)
SELECT (SELECT MaNguoiDung FROM NguoiDung WHERE Email='vy.k22@agu.edu.vn' LIMIT 1),
       (SELECT MaPhong FROM PhongTro WHERE TenPhong='Phòng trọ 30 - Yên tĩnh gần cổng ĐH' LIMIT 1),
       5, 5, 5, 5, 'Phòng sạch sẽ, cô chủ trọ rất nhiệt tình và thân thiện, đi bộ tới trường rất gần.'
WHERE NOT EXISTS (SELECT 1 FROM DanhGia WHERE NoiDung LIKE 'Phòng sạch sẽ, cô chủ trọ%');

INSERT INTO DanhGia (MaSinhVien, MaPhong, SoSao, DiemAnNinh, DiemGiaCa, DiemThaiDoChuTro, NoiDung)
SELECT (SELECT MaNguoiDung FROM NguoiDung WHERE Email='sv.k22@agu.edu.vn' LIMIT 1),
       (SELECT MaPhong FROM PhongTro WHERE TenPhong='Phòng 101 - Gác lửng thoáng mát' LIMIT 1),
       4, 5, 4, 5, 'Khu vực an ninh tốt, có camera, gác lửng rộng rãi.'
WHERE NOT EXISTS (SELECT 1 FROM DanhGia WHERE NoiDung LIKE 'Khu vực an ninh tốt%');

-- 10. Yeu thich mau
INSERT IGNORE INTO DanhSachYeuThich (MaSinhVien, MaPhong)
SELECT u.MaNguoiDung, p.MaPhong FROM NguoiDung u, PhongTro p
WHERE u.Email = 'sv.k22@agu.edu.vn' AND p.TenPhong IN ('Phòng 101 - Gác lửng thoáng mát', 'Phòng 202 - Full nội thất máy lạnh', 'Phòng trọ 50 - Studio mini hiện đại');


-- 11. Bao cao vi pham mau cho Admin kiem duyet
INSERT INTO BaoCaoViPham (MaNguoiBaoCao, MaPhong, LyDo, ChiTiet, TrangThai)
SELECT (SELECT MaNguoiDung FROM NguoiDung WHERE Email='sv.k22@agu.edu.vn' LIMIT 1),
       (SELECT MaPhong FROM PhongTro WHERE TenPhong='Phòng trọ 80 - Phòng mới xây chờ duyệt' LIMIT 1),
       'Thông tin giá chưa rõ ràng', 'Sinh viên phản ánh tin đăng chưa ghi rõ chi phí giữ xe và tiền rác hàng tháng.', 'ChoXuLy'
WHERE NOT EXISTS (SELECT 1 FROM BaoCaoViPham WHERE LyDo = 'Thông tin giá chưa rõ ràng');

