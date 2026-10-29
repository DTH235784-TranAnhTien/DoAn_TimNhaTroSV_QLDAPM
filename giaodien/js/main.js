/* ======================================================
   CampusNest AGU — JS chung cho toan bo giao dien
   ====================================================== */

function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const styles = {
    success: 'bg-bg-main text-text-main border-accent text-accent',
    info: 'bg-bg-main text-text-main border-border-warm',
    error: 'bg-status-red-bg text-status-red-text border-status-red-border'
  };

  const toast = document.createElement('div');
  toast.className = `pointer-events-auto overflow-hidden rounded-card border-2 shadow-lg text-xs font-semibold animate-[slideIn_0.25s_ease-out] ${styles[type] || styles.info}`;
  toast.innerHTML = `
    <div class="flex items-center gap-2.5 px-4 py-3">
      <span class="material-symbols-outlined shrink-0">${type === 'success' ? 'check_circle' : type === 'error' ? 'error' : 'info'}</span>
      <span class="flex-1 ${type === 'success' ? 'text-text-main' : ''}">${message}</span>
    </div>
    <div class="w-full h-[3px] bg-accent rounded-full" style="animation: toastProgress 3s linear forwards;"></div>
  `;
  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add('opacity-0', 'transition-opacity', 'duration-200');
    setTimeout(() => toast.remove(), 200);
  }, 3200);
}

function toggleAvatarDropdown(e) {
  if (e) e.stopPropagation();
  const dropdown = document.getElementById('avatar-dropdown');
  if (dropdown) dropdown.classList.toggle('hidden');
}
function closeAvatarDropdown() {
  const dropdown = document.getElementById('avatar-dropdown');
  if (dropdown) dropdown.classList.add('hidden');
}

let isAdminDualBarDismissed = false;
function dismissAdminDualBar() {
  isAdminDualBarDismissed = true;
  const bar = document.getElementById('admin-dual-mode-bar');
  if (bar) bar.classList.add('hidden');
}

/* ---------- Trang chu / Danh sach ---------- */
let bookmarkOn = false;
function toggleBookmark(id, name) {
  bookmarkOn = !bookmarkOn;
  const icons = document.querySelectorAll('.bookmark-toggle-icon');
  icons.forEach(ic => {
    ic.textContent = bookmarkOn ? 'bookmark' : 'bookmark_border';
  });
  showToast(bookmarkOn
    ? `Đã lưu "${name || 'Phòng'}" vào danh sách yêu thích.`
    : 'Đã bỏ lưu khỏi danh sách yêu thích.', 'info');
}

function copyShareLink() {
  const url = window.location.href;
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url).then(() => showToast('Đã sao chép liên kết chia sẻ.', 'success'));
  } else {
    showToast('Liên kết: ' + url, 'info');
  }
}

function toggleExploreMap() {
  const panel = document.getElementById('explore-map-panel');
  const listCol = document.getElementById('explore-list-col');
  if (panel && listCol) {
    const isHidden = panel.classList.toggle('hidden');
    if (isHidden) {
      listCol.classList.remove('lg:col-span-7');
      listCol.classList.add('lg:col-span-12');
    } else {
      listCol.classList.remove('lg:col-span-12');
      listCol.classList.add('lg:col-span-7');
    }
  }
}

function updateBudgetText(v) {
  const el = document.getElementById('budget-value');
  if (el) el.textContent = Number(v).toLocaleString('vi-VN') + ' đ/tháng';
}

function setDetailImage(img) {
  const main = document.getElementById('main-detail-img');
  if (main && img) main.src = img.src;
}

/* ---------- Trang chi tiet ---------- */
function toggleBookingFormBox() {
  const form = document.getElementById('detail-booking-form');
  if (form) form.classList.toggle('hidden');
}
function showBookingSuccess() {
  const alertBox = document.getElementById('bookingConfirmationAlert');
  if (alertBox) alertBox.classList.remove('hidden');
  showToast('Đã gửi lịch hẹn!', 'success');
}

/* ---------- Trang chu tro ---------- */
function updateBudgetTextX(v) {
  const el = document.getElementById('budget-value');
  if (el) el.textContent = Number(v).toLocaleString('vi-VN') + ' đ/tháng';
}

function toggleRoomStatus(checkbox, badgeId) {
  const badge = document.getElementById(badgeId);
  if (!badge) return;
  if (checkbox.checked) {
    badge.className = 'px-2.5 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold inline-flex items-center gap-1.5';
    badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-accent"></span><span>Còn phòng</span>';
  } else {
    badge.className = 'px-2.5 py-0.5 rounded-full bg-status-gray-bg text-status-gray-text text-xs font-semibold inline-flex items-center gap-1.5';
    badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-text-sub"></span><span>Đã thuê</span>';
  }
  showToast('Đã cập nhật trạng thái phòng.', 'success');
}

function acceptApt(cardId) {
  const card = document.getElementById(cardId);
  if (!card) return;
  const badge = card.querySelector('.apt-badge');
  if (badge) {
    badge.className = 'apt-badge px-2.5 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold';
    badge.textContent = 'Đã xác nhận';
  }
  showToast('Đã xác nhận lịch hẹn xem phòng.', 'success');
}

function rejectApt(cardId) {
  const card = document.getElementById(cardId);
  if (card) card.remove();
  showToast('Đã chuyển yêu cầu sang dạng đổi giờ.', 'info');
}

/* ---------- Trang ghep ban ---------- */
function updateBudgetTextRange(v) {
  const el = document.getElementById('budget-value');
  if (el) el.textContent = Number(v).toLocaleString('vi-VN') + ' đ/tháng';
}

function loadSamplePrompt() {
  const ta = document.getElementById('ai-roommate-desc');
  if (ta) {
    ta.value = 'Mình là nữ sinh viên năm 2 Khoa Sư phạm AGU, sống ngăn nắp, ngủ trước 23h và không thích ồn ào. Cần tìm 1 bạn nữ ở ghép khu vực Ung Văn Khiêm...';
  }
  showToast('Đã điền mẫu ví dụ. Hãy nhấn Quét AI & Tìm bạn ghép.', 'info');
}

function triggerAiScan() {
  const btn = document.querySelector('button[type="submit"]');
  if (btn) btn.disabled = true;
  showToast('Đang tìm kiếm hồ sơ phù hợp với tiêu chí...', 'info');
  setTimeout(() => {
    if (btn) btn.disabled = false;
    showToast('Đã hoàn tất tìm kiếm!', 'info');
  }, 1200);
}

/* ---------- Modal dang tin tim ban ghep ---------- */
function openRoommatePostModal() {
  const modal = document.getElementById('roommate-post-modal');
  if (modal) modal.classList.remove('hidden');
}
function closeRoommatePostModal() {
  const modal = document.getElementById('roommate-post-modal');
  if (modal) modal.classList.add('hidden');
}
function handleRoommatePostSubmit(e) {
  e.preventDefault();
  const title = document.getElementById('rm-post-title').value.trim();
  const desc = document.getElementById('rm-post-desc').value.trim();
  const budget = document.getElementById('rm-post-budget').value;
  const phone = document.getElementById('rm-post-phone').value.trim();
  const area = document.getElementById('rm-post-area').value.trim();

  if (!title || !desc || !budget || !phone || !area) {
    showToast('Vui lòng điền đầy đủ thông tin.', 'error');
    return false;
  }

  const card = document.createElement('div');
  card.className = 'bg-bg-main p-4 rounded-card border border-border-warm space-y-2.5';
  card.innerHTML = `
    <div class="flex items-start justify-between gap-2">
      <div class="flex-1 min-w-0">
        <h4 class="text-sm font-bold text-text-main">${title}</h4>
        <span class="text-xs text-text-sub block mt-0.5">${area}</span>
      </div>
      <span class="px-2.5 py-0.5 rounded-full bg-status-green-bg text-accent text-xs font-semibold shrink-0">Tin mới</span>
    </div>
    <p class="text-xs text-text-main bg-bg-sub p-2.5 rounded-card">${desc}</p>
    <div class="flex items-center justify-between text-xs tnum">
      <span class="text-text-sub">Ngân sách: <strong class="text-text-main">${Number(budget).toLocaleString('vi-VN')} đ/tháng</strong></span>
      <span class="text-text-sub">SĐT: <strong class="text-tnum">${phone}</strong></span>
    </div>
  `;
  const target = document.getElementById('user-custom-roommate-posts');
  if (target) target.prepend(card);

  closeRoommatePostModal();
  document.getElementById('roommate-post-form').reset();
  showToast('Đã đăng tin tìm bạn ghép thành công!', 'success');
  return false;
}

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeAvatarDropdown();
    closeRoommatePostModal();
    closeListingModal();
    closeDeleteModal();
  }
});