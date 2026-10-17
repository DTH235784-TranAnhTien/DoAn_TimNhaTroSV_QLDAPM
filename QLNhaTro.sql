-- 1. Tao co so du lieu
CREATE DATABASE QuanLyNhaTro
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE QuanLyNhaTro;


-- 2. Bang nguoi dung
CREATE TABLE NguoiDung (
    MaNguoiDung INT AUTO_INCREMENT PRIMARY KEY,
    HoTen VARCHAR(100) NOT NULL,
    SoDienThoai VARCHAR(15),
    Email VARCHAR(100) UNIQUE,
    MatKhau VARCHAR(255) NOT NULL,
    VaiTro ENUM('SinhVien', 'ChuTro', 'QuanTriVien')
        DEFAULT 'SinhVien'
);


-- 3. Bang nha tro
CREATE TABLE NhaTro (
    MaNhaTro INT AUTO_INCREMENT PRIMARY KEY,
    MaChuTro INT NOT NULL,
    TenNhaTro VARCHAR(150) NOT NULL,
    DiaChi VARCHAR(255) NOT NULL,
    MoTa TEXT,
    ViDo DECIMAL(10,7),
    KinhDo DECIMAL(10,7),

    FOREIGN KEY (MaChuTro)
        REFERENCES NguoiDung(MaNguoiDung)
);


-- 4. Bang phong tro
CREATE TABLE PhongTro (
    MaPhong INT AUTO_INCREMENT PRIMARY KEY,
    MaNhaTro INT NOT NULL,
    TenPhong VARCHAR(100),
    GiaThue DECIMAL(12,2) NOT NULL,
    DienTich DECIMAL(6,2),
    TienDien DECIMAL(10,2),
    TienNuoc DECIMAL(10,2),
    MoTa TEXT,
    TrangThai ENUM('ConPhong', 'DaThue')
        DEFAULT 'ConPhong',
    NgayDang DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (MaNhaTro)
        REFERENCES NhaTro(MaNhaTro)
        ON DELETE CASCADE
);