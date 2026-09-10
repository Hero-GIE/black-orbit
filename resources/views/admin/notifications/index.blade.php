@extends('layouts.admin')

@section('title', 'Notifications Management')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-bell me-2"></i>Notifications Management</h4>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark btn-sm d-flex align-items-center" onclick="addNotif()">
                <i class="fas fa-paper-plane me-2"></i>Send Notification
            </button>
            <button class="btn btn-outline-dark btn-sm d-flex align-items-center" onclick="window.location.href='{{ route('admin.dashboard') }}'">
                <i class="fas fa-arrow-left me-2"></i>Back
            </button>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text"
                       class="form-control border-start-0"
                       id="searchNotifs"
                       placeholder="Search by title or message..."
                       style="border-left: none; border-radius: 0 10px 10px 0;">
            </div>
        </div>
        <div class="col-md-6 col-lg-8 text-md-end">
            <span class="text-muted small" id="recordCount">Loading...</span>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <!-- SKELETON LOADING -->
            <div id="notifs-skeleton">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="border-bottom-2 sticky-top bg-white" style="top: 0; z-index: 10;">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 50px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 220px;">Title & Message</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Target User</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 100px;">Status</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 150px;">Created At</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 6; $i++)
                                <tr>
                                    <td class="text-center">
                                        <div class="skeleton-box mx-auto" style="width: 20px; height: 16px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="skeleton-box me-3" style="width: 40px; height: 40px; border-radius: 8px;"></div>
                                            <div>
                                                <div class="skeleton-box mb-1" style="width: 140px; height: 16px; border-radius: 4px;"></div>
                                                <div class="skeleton-box" style="width: 100px; height: 12px; border-radius: 4px;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 80px; height: 20px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 60px; height: 16px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 100px; height: 16px; border-radius: 4px;"></div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <div class="skeleton-box" style="width: 32px; height: 32px; border-radius: 8px;"></div>
                                            <div class="skeleton-box" style="width: 32px; height: 32px; border-radius: 8px;"></div>
                                            <div class="skeleton-box" style="width: 32px; height: 32px; border-radius: 8px;"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ACTUAL CONTENT -->
            <div id="notifs-content" style="display: none; animation: fadeIn 0.5s ease-in-out;">
                <div class="table-responsive" id="notifsTableWrapper" style="max-height: 650px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="border-bottom-2 sticky-top bg-white" style="top: 0; z-index: 10;">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 50px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 220px;">Title & Message</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Target User</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 100px;">Status</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 150px;">Created At</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="notifsTableBody"></tbody>
                    </table>
                </div>

                {{-- Scroll indicator --}}
                <div id="scrollIndicator" class="text-center text-muted small mt-2" style="display: none;">
                    <i class="fas fa-chevron-down me-1"></i> Scroll for more records
                </div>
            </div>
        </div>
    </div>
</div>

{{-- View Notification Modal --}}
<div class="modal fade" id="viewNotifModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-info-circle me-2 text-dark"></i>Notification Details</h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="viewNotifContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-dark" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-dark px-4" id="editFromViewBtn">
                    <i class="fas fa-edit me-1"></i> Edit
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Add/Edit Notification Modal --}}
<div class="modal fade" id="editNotifModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="editNotifTitle">Send Notification</h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <form id="editNotifForm">
                    <input type="hidden" id="editNotifId" name="notifId">

                    <div class="mb-3">
                        <label for="editTitle" class="form-label text-muted small fw-bold">Title *</label>
                        <input type="text" class="form-control form-control-custom" id="editTitle" name="title" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editUserid" class="form-label text-muted small fw-bold">Target User *</label>
                            <select class="form-control form-control-custom" id="editUserid" name="userid" required>
                                <option value="all">All Users (Broadcast)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editRoute" class="form-label text-muted small fw-bold">App Route (Optional)</label>
                            <input type="text" class="form-control form-control-custom" id="editRoute" name="route" placeholder="e.g., /details">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Image (Optional)</label>
                        <div class="mb-2">
                            <div id="imagePreviewWrapper" class="position-relative d-inline-block" style="display: none;">
                                <img id="imagePreview" src="" alt="Preview" class="rounded border bg-light" style="width: 88px; height: 88px; object-fit: cover;">
                                <button type="button" id="removeImageBtn" title="Remove image" class="btn btn-sm btn-light border rounded-circle position-absolute top-0 end-0" style="width: 22px; height: 22px; padding: 0; font-size: 0.7rem; line-height: 1;">&times;</button>
                            </div>
                            <div id="imageEmptyState" class="text-muted small">
                                <i class="fas fa-image me-1 opacity-50"></i>No image selected yet
                            </div>
                        </div>
                        <input type="url" class="form-control form-control-custom mb-2" id="editImage" name="image" placeholder="https://res.cloudinary.com/...">
                        <div class="d-flex align-items-center gap-2">
                            <input type="file" id="imageFile" accept="image/*" class="d-none">
                            <button type="button" class="btn btn-outline-dark btn-sm" id="uploadImageBtn">
                                <i class="fas fa-cloud-upload-alt me-1"></i>Upload from device
                            </button>
                            <span id="imageUploadStatus" class="small text-muted"></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editMessage" class="form-label text-muted small fw-bold">Message *</label>
                        <textarea class="form-control form-control-custom" id="editMessage" name="message" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark px-4" id="saveNotifBtn">Send Notification</button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-body text-center p-5">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex p-4 mb-3">
                    <i class="fas fa-trash-alt fa-2x"></i>
                </div>
                <h4 class="fw-bold mb-2">Delete Notification?</h4>
                <p class="text-muted mb-4">Are you sure you want to delete this notification? This action cannot be undone.</p>
                <input type="hidden" id="deleteNotifId">
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Skeleton Pulse Animation */
    @keyframes skeleton-pulse {
        0% { opacity: 0.6; }
        50% { opacity: 1; }
        100% { opacity: 0.6; }
    }

    .skeleton-box {
        display: block;
        background-color: #e9ecef;
        border-radius: 4px;
        animation: skeleton-pulse 1.5s infinite ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .form-control-custom {
        border: 1px solid #e9ecef;
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    .form-control-custom:focus {
        background-color: #fff;
        border-color: #000;
        box-shadow: 0 0 0 3px rgba(0,0,0,0.08);
        outline: none;
    }

    .action-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: #6c757d;
        transition: all 0.2s ease;
    }
    .action-btn:hover {
        background: #f1f1f1;
        color: #000;
    }
    .action-btn.delete:hover {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .table > :not(caption) > * > * {
        padding: 0.75rem 0.75rem;
        vertical-align: middle;
    }

    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    .modal-desc-scroll {
        max-height: 200px;
        overflow-y: auto;
        border-radius: 12px;
    }
    .modal-desc-scroll::-webkit-scrollbar { width: 6px; }
    .modal-desc-scroll::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .modal-desc-scroll::-webkit-scrollbar-thumb { background: #d1d1d1; border-radius: 10px; }

    #notifsTableWrapper::-webkit-scrollbar {
        width: 6px;
    }
    #notifsTableWrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    #notifsTableWrapper::-webkit-scrollbar-thumb {
        background: #d1d1d1;
        border-radius: 10px;
    }
    #notifsTableWrapper::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    .input-group .form-control:focus {
        border-color: #dee2e6;
        box-shadow: none;
    }
</style>
@endsection

@push('scripts')
<script>
let editingNotifId = null;
let viewModalInstance = null;
let allNotifs = [];
let filteredNotifs = [];
let allUsers = [];

// Image Upload Logic
const imageInputEl = document.getElementById('editImage');
const imageFileInput = document.getElementById('imageFile');
const imagePreviewEl = document.getElementById('imagePreview');
const imagePreviewWrapper = document.getElementById('imagePreviewWrapper');
const imageEmptyState = document.getElementById('imageEmptyState');
const imageUploadStatus = document.getElementById('imageUploadStatus');
const uploadImageBtn = document.getElementById('uploadImageBtn');
const IMAGE_UPLOAD_URL = '/admin/api/images/upload';

uploadImageBtn.addEventListener('click', () => imageFileInput.click());
document.getElementById('removeImageBtn').addEventListener('click', () => { imageInputEl.value = ''; clearImagePreview(); });
imageInputEl.addEventListener('change', () => showImagePreview(imageInputEl.value));
imagePreviewEl.addEventListener('error', () => clearImagePreview());

function showImagePreview(url) {
    if (!url) { clearImagePreview(); return; }
    imagePreviewEl.src = url; imagePreviewWrapper.style.display = 'inline-block'; imageEmptyState.style.display = 'none';
}
function clearImagePreview() {
    imagePreviewEl.removeAttribute('src'); imagePreviewWrapper.style.display = 'none'; imageEmptyState.style.display = 'block';
}

imageFileInput.addEventListener('change', async function () {
    const file = this.files[0]; this.value = '';
    if (!file) return;
    if (!file.type.startsWith('image/')) { showToast('Please choose an image file', 'danger'); return; }
    if (file.size > 10240 * 1024) { showToast('Image is too large (max 10 MB)', 'danger'); return; }

    const reader = new FileReader();
    reader.onload = (e) => showImagePreview(e.target.result);
    reader.readAsDataURL(file);

    imageUploadStatus.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Uploading…';
    uploadImageBtn.disabled = true;

    const fd = new FormData();
    fd.append('image', file);
    fd.append('folder', 'notifications');

    try {
        const response = await fetch(IMAGE_UPLOAD_URL, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: fd,
        });
        const data = await response.json();

        if (data.success && data.data && data.data.url) {
            imageInputEl.value = data.data.url;
            showImagePreview(data.data.url);
            imageUploadStatus.innerHTML = '<i class="fas fa-check-circle text-success"></i> Uploaded';
            showToast('Image uploaded', 'success');
        } else {
            clearImagePreview(); imageUploadStatus.textContent = '';
            showToast(data.message || 'Upload failed', 'danger');
        }
    } catch (err) {
        console.error('Image upload error:', err);
        clearImagePreview(); imageUploadStatus.textContent = '';
        showToast('Error uploading image', 'danger');
    } finally {
        uploadImageBtn.disabled = false;
        setTimeout(() => { imageUploadStatus.textContent = ''; }, 5000);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    loadNotifs();
    loadUsersForDropdown();
    setupSearch();
});

async function loadUsersForDropdown() {
    try {
        const response = await fetch('/admin/api/users');
        const data = await response.json();
        if (data.success) {
            allUsers = data.data || [];
            const select = document.getElementById('editUserid');
            // Keep the broadcast option, add users
            allUsers.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = `${user.username || user.email || 'Unknown'} (${user.id.substring(0, 8)}...)`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

function setupSearch() {
    const searchInput = document.getElementById('searchNotifs');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            filterNotifs(query);
        });
    }
}

function filterNotifs(query) {
    if (!query) {
        filteredNotifs = allNotifs;
    } else {
        filteredNotifs = allNotifs.filter(n => {
            const title = (n.title || '').toLowerCase();
            const message = (n.message || '').toLowerCase();
            return title.includes(query) || message.includes(query);
        });
    }
    renderNotifs(filteredNotifs);
    updateFilteredCount(filteredNotifs.length);
}

function updateFilteredCount(count) {
    const countElement = document.getElementById('filteredCount');
    const recordCount = document.getElementById('recordCount');
    if (countElement) {
        countElement.textContent = count === 1 ? '1 record found' : count + ' records found';
    }
    if (recordCount) {
        recordCount.textContent = `(${allNotifs.length} total records)`;
    }
}

async function loadNotifs() {
    const skeleton = document.getElementById('notifs-skeleton');
    const content = document.getElementById('notifs-content');
    const recordCount = document.getElementById('recordCount');

    try {
        const response = await fetch('/admin/api/notifications');
        const data = await response.json();

        if (data.success) {
            allNotifs = data.data || [];
            filteredNotifs = allNotifs;
            renderNotifs(filteredNotifs);
            updateFilteredCount(filteredNotifs.length);
        } else {
            showToast('Failed to load notifications', 'danger');
            renderNotifs([]);
            updateFilteredCount(0);
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        showToast('Error loading notifications', 'danger');
        renderNotifs([]);
        updateFilteredCount(0);
    } finally {
        if (skeleton) skeleton.style.display = 'none';
        if (content) content.style.display = 'block';
    }
}

function getTargetDisplay(userid) {
    if (!userid || userid === 'all') return `<span class="badge bg-secondary">Broadcast</span>`;
    const user = allUsers.find(u => u.id === userid);
    if (user) {
        return `<span class="text-muted small">${escapeHtml(user.username || user.email || 'Specific User')}</span>`;
    }
    return `<span class="text-muted small" title="${escapeHtml(userid)}">${escapeHtml(userid.substring(0, 12))}...</span>`;
}

function renderNotifs(notifs) {
    const tbody = document.getElementById('notifsTableBody');
    const scrollIndicator = document.getElementById('scrollIndicator');
    const tableWrapper = document.getElementById('notifsTableWrapper');

    if (notifs && notifs.length > 10) {
        if (tableWrapper) {
            tableWrapper.style.maxHeight = '650px';
            tableWrapper.style.overflowY = 'auto';
        }
        if (scrollIndicator) scrollIndicator.style.display = 'block';
    } else {
        if (tableWrapper) {
            tableWrapper.style.maxHeight = 'none';
            tableWrapper.style.overflowY = 'visible';
        }
        if (scrollIndicator) scrollIndicator.style.display = 'none';
    }

    if (!notifs || notifs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-bell-slash fa-2x mb-3 d-block opacity-50"></i>
                        <p class="mb-0 fw-bold">${allNotifs.length > 0 ? 'No matching notifications found' : 'No notifications sent yet'}</p>
                        <small>${allNotifs.length > 0 ? 'Try a different search term' : 'Click "Send Notification" to create one'}</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    notifs.forEach((n, index) => {
        const imgHtml = n.image
            ? `<img src="${escapeHtml(n.image)}" alt="${escapeHtml(n.title)}" class="rounded bg-light" style="width: 40px; height: 40px; object-fit: cover;">`
            : `<div class="rounded bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fas fa-bell text-muted"></i></div>`;

        const statusHtml = n.isRead
            ? `<span class="badge bg-success">Read</span>`
            : `<span class="badge bg-warning text-dark">Unread</span>`;

        let createdDate = 'N/A';
        if (n.createdAt) {
            try {
                createdDate = new Date(n.createdAt).toLocaleString();
            } catch (e) {}
        }

        html += `
            <tr>
                <td class="text-muted">${index + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        ${imgHtml}
                        <div class="ms-3">
                            <div class="fw-bold text-dark text-truncate" style="max-width: 200px;">${escapeHtml(n.title)}</div>
                            <small class="text-muted" style="font-size: 0.75rem; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">${escapeHtml(n.message)}</small>
                        </div>
                    </div>
                </td>
                <td>${getTargetDisplay(n.userid)}</td>
                <td>${statusHtml}</td>
                <td><span class="text-muted small">${createdDate}</span></td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <button class="action-btn" onclick="viewNotif('${n.id}')" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn" onclick="editNotif('${n.id}')" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="confirmDelete('${n.id}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

async function viewNotif(id) {
    const content = document.getElementById('viewNotifContent');
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-dark" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    viewModalInstance = new bootstrap.Modal(document.getElementById('viewNotifModal'));
    viewModalInstance.show();

    try {
        const response = await fetch(`/admin/api/notifications/${id}`);
        const data = await response.json();

        if (data.success) {
            const n = data.data;
            let targetHtmlView = n.userid && n.userid !== 'all' ? 'All Users' : 'Specific User';
            if (n.userid && n.userid !== 'all') {
                const user = allUsers.find(u => u.id === n.userid);
                targetHtmlView = user ? (user.username || user.email) : n.userid;
            }

            content.innerHTML = `
                <div class="text-center mb-4">
                    ${n.image ? `<img src="${escapeHtml(n.image)}" class="rounded shadow-sm mb-3" style="height: 180px; object-fit: cover;"><br>` : `<div class="rounded bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;"><i class="fas fa-bell fa-3x text-muted"></i></div>`}
                    <h4 class="fw-bold mb-1">${escapeHtml(n.title)}</h4>
                    <div class="d-flex justify-content-center gap-2 mb-2">
                        ${n.isRead ? `<span class="badge bg-success">Read</span>` : `<span class="badge bg-warning text-dark">Unread</span>`}
                        ${n.route ? `<span class="badge bg-secondary">Route: ${escapeHtml(n.route)}</span>` : ''}
                    </div>
                    <small class="text-muted">Target: ${escapeHtml(targetHtmlView)}</small>
                </div>

                <div class="bg-light p-3 rounded-3 modal-desc-scroll">
                    <small class="text-muted d-block text-uppercase mb-2 fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Message</small>
                    <p class="mb-0 text-dark fw-medium" style="font-size: 1rem; line-height: 1.6;">${escapeHtml(n.message)}</p>
                </div>
            `;

            document.getElementById('editFromViewBtn').onclick = () => editNotif(id);
        } else {
            content.innerHTML = `
                <div class="text-center text-danger py-5">
                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                    <p>Failed to load notification details.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
        content.innerHTML = `
            <div class="text-center text-danger py-5">
                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                <p>Error loading notification details.</p>
            </div>
        `;
    }
}

function addNotif() {
    editingNotifId = null;
    document.getElementById('editNotifTitle').textContent = 'Send Notification';
    document.getElementById('editNotifForm').reset();
    clearImagePreview(); imageUploadStatus.textContent = '';
    document.getElementById('editNotifId').value = '';
    document.getElementById('editUserid').value = 'all'; // Default to broadcast
    document.getElementById('saveNotifBtn').textContent = 'Send Notification';

    if (viewModalInstance) viewModalInstance.hide();
    const modal = new bootstrap.Modal(document.getElementById('editNotifModal'));
    modal.show();
}

async function editNotif(id) {
    try {
        const response = await fetch(`/admin/api/notifications/${id}`);
        const data = await response.json();

        if (data.success) {
            const n = data.data;
            editingNotifId = id;

            document.getElementById('editNotifTitle').textContent = 'Edit Notification';
            document.getElementById('editNotifId').value = id;
            document.getElementById('editTitle').value = n.title || '';
            document.getElementById('editMessage').value = n.message || '';
            document.getElementById('editRoute').value = n.route || '';
            document.getElementById('editUserid').value = n.userid || 'all';
            document.getElementById('editImage').value = n.image || '';
            showImagePreview(n.image || ''); imageUploadStatus.textContent = '';
            document.getElementById('saveNotifBtn').textContent = 'Update Notification';

            if (viewModalInstance) viewModalInstance.hide();
            const modal = new bootstrap.Modal(document.getElementById('editNotifModal'));
            modal.show();
        } else {
            showToast('Failed to load data', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error loading data', 'danger');
    }
}

document.getElementById('saveNotifBtn').addEventListener('click', async function() {
    const id = document.getElementById('editNotifId').value;
    const payload = {
        title: document.getElementById('editTitle').value,
        message: document.getElementById('editMessage').value,
        route: document.getElementById('editRoute').value,
        userid: document.getElementById('editUserid').value,
        image: document.getElementById('editImage').value,
    };

    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        let url = '/admin/api/notifications'; let method = 'POST';
        if (id) { url = `/admin/api/notifications/${id}`; method = 'PUT'; }

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('editNotifModal'));
            modal.hide();
            loadNotifs();
        } else {
            showToast(data.message || 'Failed to save', 'danger');
        }
    } catch (error) {
        console.error('Error updating:', error);
        showToast('Error saving notification', 'danger');
    } finally {
        this.disabled = false;
        this.innerHTML = id ? 'Update Notification' : 'Send Notification';
    }
});

function confirmDelete(id) {
    document.getElementById('deleteNotifId').value = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    const id = document.getElementById('deleteNotifId').value;

    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

        const response = await fetch(`/admin/api/notifications/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            }
        });

        const data = await response.json();

        if (data.success) {
            showToast('Notification deleted successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            modal.hide();
            loadNotifs();
        } else {
            showToast('Failed to delete notification', 'danger');
        }
    } catch (error) {
        console.error('Error deleting:', error);
        showToast('Error deleting notification', 'danger');
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-trash me-1"></i> Delete';
    }
});

function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast show align-items-center text-white bg-${type === 'danger' ? 'danger' : 'dark'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${escapeHtml(message)}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    document.getElementById('toastContainer').insertAdjacentHTML('beforeend', toastHtml);

    setTimeout(() => {
        const toast = document.getElementById(toastId);
        if (toast) toast.remove();
    }, 5000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush
