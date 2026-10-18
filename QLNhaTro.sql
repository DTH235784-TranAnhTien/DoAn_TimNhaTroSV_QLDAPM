-- ====================================================================
-- HỆ THỐNG QUẢN LÝ WEBSITE TÌM KIẾM NHÀ TRỌ SINH VIÊN (ĐH AN GIANG)
-- File DDL tạo toàn bộ Cơ sở dữ liệu và các Bảng
-- ====================================================================

-- 1. Tạo cơ sở dữ liệu chuẩn UTF-8 Tiếng Việt
DROP DATABASE IF EXISTS QuanLyNhaTro;
CREATE DATABASE QuanLyNhaTro
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE QuanLyNhaTro;

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