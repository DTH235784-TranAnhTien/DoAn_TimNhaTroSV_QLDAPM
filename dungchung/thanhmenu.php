<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container">
        <!-- Logo & Tên Website -->
        <a class="navbar-brand fw-bold" href="/QLNhaTro/index.php">
            <i class="bi bi-house-door-fill me-1"></i> NHÀ TRỌ AGU
        </a>

        <!-- Nút 3 gạch mở menu trên điện thoại -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuChinh">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Danh sách các nút liên kết -->
        <div class="collapse navbar-collapse" id="menuChinh">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" href="/QLNhaTro/index.php">
                        <i class="bi bi-house"></i> Trang chủ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/QLNhaTro/sinhvien/timnguoioghep.php">
                        <i class="bi bi-people"></i> Ghép ở cùng
                    </a>
                </li>
            </ul>

            <!-- Phần hiển thị Đăng nhập / Đăng ký hoặc Tên người dùng -->
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (isset($_SESSION['nguoidung'])): ?>
                    <!-- KHI ĐÃ ĐĂNG NHẬP -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white fw-bold" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['nguoidung']['hoten']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <!-- Menu riêng cho CHỦ TRỌ -->
                            <?php if ($_SESSION['nguoidung']['vaitro'] === 'ChuTro'): ?>
                                <li><a class="dropdown-item" href="/QLNhaTro/chutro/dangtin.php"><i class="bi bi-plus-circle me-2"></i>Đăng tin trọ mới</a></li>
                                <li><a class="dropdown-item" href="/QLNhaTro/chutro/danhsachphong.php"><i class="bi bi-list-check me-2"></i>Quản lý phòng trọ</a></li>
                                <li><a class="dropdown-item" href="/QLNhaTro/chutro/duyetlichhen.php"><i class="bi bi-calendar-check me-2"></i>Duyệt lịch hẹn</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>

                            <!-- Menu riêng cho SINH VIÊN -->
                            <?php if ($_SESSION['nguoidung']['vaitro'] === 'SinhVien'): ?>
                                <li><a class="dropdown-item" href="/QLNhaTro/sinhvien/phongdaluu.php"><i class="bi bi-bookmark-heart me-2"></i>Phòng đã lưu</a></li>
                                <li><a class="dropdown-item" href="/QLNhaTro/sinhvien/lichhencuatoi.php"><i class="bi bi-clock-history me-2"></i>Lịch hẹn của tôi</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>

                            <!-- Menu riêng cho QUẢN TRỊ VIÊN -->
                            <?php if ($_SESSION['nguoidung']['vaitro'] === 'QuanTriVien'): ?>
                                <li><a class="dropdown-item" href="/QLNhaTro/quantri/duyettin.php"><i class="bi bi-shield-check me-2"></i>Duyệt bài đăng</a></li>
                                <li><a class="dropdown-item" href="/QLNhaTro/quantri/quanlytaikhoan.php"><i class="bi bi-people-fill me-2"></i>Quản lý tài khoản</a></li>
                                <li><a class="dropdown-item" href="/QLNhaTro/quantri/thongke.php"><i class="bi bi-bar-chart-line me-2"></i>Báo cáo thống kê</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>

                            <li><a class="dropdown-item text-danger" href="/QLNhaTro/taikhoan/dangxuat.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- KHI CHƯA ĐĂNG NHẬP -->
                    <li class="nav-item me-2">
                        <a class="btn btn-outline-light btn-sm" href="/QLNhaTro/taikhoan/dangnhap.php">
                            <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                        </a>
                    </li>
                    <li class="nav-item me-2">
                        <a class="btn btn-warning btn-sm fw-bold text-dark" href="/QLNhaTro/taikhoan/dangky.php">
                            Đăng ký
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light btn-sm fw-bold text-primary" href="/QLNhaTro/chutro/dangtin.php">
                            <i class="bi bi-plus-lg"></i> Đăng tin
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>