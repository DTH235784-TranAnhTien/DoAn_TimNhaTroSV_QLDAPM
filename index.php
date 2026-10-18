<?php
// 1. Nhúng đầu trang (Tự động kèm kết nối Menu và Bootstrap)
$tieuDeTrang = "Trang Chủ - Tìm Kiếm Nhà Trọ Sinh Viên ĐH An Giang";
include_once __DIR__ . '/dungchung/header.php';
?>

<!-- PHẦN BANNER TÌM KIẾM TRỌ -->
<div class="bg-primary text-white py-5 shadow-sm" style="background: linear-gradient(135deg, #0d6efd 0%, #0a4296 100%);">
    <div class="container text-center py-4">
        <h1 class="fw-bold mb-3">Tìm Phòng Trọ Gần Trường Đại Học An Giang</h1>
        <p class="lead mb-4 text-white-50">Hàng trăm phòng trọ giá rẻ, an ninh, đầy đủ tiện nghi dành riêng cho sinh viên</p>
        
        <!-- Ô tìm kiếm nhanh -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <form class="d-flex gap-2 bg-white p-2 rounded shadow">
                    <input class="form-control border-0" type="search" placeholder="Nhập tên đường, khu vực hoặc tên trọ (vd: Ung Văn Khiêm, Đông Xuyên...)">
                    <button class="btn btn-warning fw-bold px-4" type="submit">
                        <i class="bi bi-search"></i> Tìm
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- PHẦN DANH SÁCH PHÒNG TRỌ MỚI NHẤT -->
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">
            <i class="bi bi-fire text-danger me-1"></i> Phòng Trọ Mới Nhất
        </h3>
        <span class="text-muted small">Cập nhật liên tục</span>
    </div>

    <!-- Khung thông báo tạm thời khi chưa có dữ liệu phòng -->
    <div class="alert alert-info border-0 shadow-sm text-center py-5">
        <i class="bi bi-house-slash fs-1 text-primary d-block mb-3"></i>
        <h5 class="fw-bold">Bộ khung website đã sẵn sàng hoạt động!</h5>
        <p class="text-muted mb-0">Bạn có thể bắt đầu tạo tài khoản và cho phép chủ trọ đăng tin phòng trọ lên hệ thống.</p>
    </div>
</div>

<?php
// 2. Nhúng chân trang
include_once __DIR__ . '/dungchung/footer.php';
?>