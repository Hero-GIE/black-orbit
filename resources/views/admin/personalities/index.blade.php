@extends('layouts.admin')

@section('title', 'Personalities Management')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-users me-2"></i>Personalities Management</h4>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark btn-sm d-flex align-items-center" onclick="openAddDrawer()">
                <i class="fas fa-plus me-2"></i>Add Personality
            </button>
            <button class="btn btn-outline-dark btn-sm d-flex align-items-center" onclick="window.location.href='{{ route('admin.dashboard') }}'">
                <i class="fas fa-arrow-left me-2"></i>Back
            </button>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="row mb-4">
        <div class="col-md-5 col-lg-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text"
                       class="form-control border-start-0"
                       id="searchPersonalities"
                       placeholder="Search by name, occupation, or category..."
                       style="border-left: none; border-radius: 0 10px 10px 0;">
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <select id="categoryFilter" class="form-select form-control-custom" style="border-radius: 10px;">
                <option value="">All Categories</option>
            </select>
        </div>
        <div class="col-md-3 col-lg-5 text-md-end">
            <span class="text-muted small" id="recordCount">Loading...</span>
        </div>
    </div>

    <!-- SKELETON LOADING -->
    <div id="personalities-skeleton" class="row g-4">
        @for($i = 0; $i < 8; $i++)
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="skeleton-box w-100" style="height: 350px; border-radius: 10px;"></div>
            </div>
        @endfor
    </div>

    <!-- ACTUAL CONTENT -->
    <div id="personalities-content" class="row g-4" style="display: none; animation: fadeIn 0.5s ease-in-out;">
    </div>
</div>

{{-- Add/Edit Personality Drawer (Right Side) --}}
<div class="offcanvas offcanvas-end personality-drawer" tabindex="-1" id="personalityDrawer" aria-labelledby="personalityDrawerLabel">
    <div class="offcanvas-header border-0 pb-0">
        <h5 class="offcanvas-title fw-bold" id="personalityDrawerTitle">Add Personality</h5>
        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body pt-2">
        <form id="personalityForm" class="flex-grow-1">
            <input type="hidden" id="personalityId" name="personalityId">
            <div class="mb-3">
                <label for="name" class="form-label text-muted small fw-bold">Name *</label>
                <input type="text" class="form-control form-control-custom" id="name" name="name" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="occupation" class="form-label text-muted small fw-bold">Occupation</label>
                    <input type="text" class="form-control form-control-custom" id="occupation" name="occupation" placeholder="e.g., Engineer & Inventor">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="category" class="form-label text-muted small fw-bold">Category</label>
                    <input type="text" class="form-control form-control-custom" id="category" name="category" placeholder="e.g., Inventor">
                </div>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label text-muted small fw-bold">Image</label>
                <div class="mb-2">
                    <div id="imagePreviewWrapper" class="position-relative d-inline-block" style="display: none;">
                        <img id="imagePreview" src="" alt="Preview" class="rounded border bg-light" style="width: 88px; height: 88px; object-fit: cover;">
                        <button type="button" id="removeImageBtn" title="Remove image" class="btn btn-sm btn-light border rounded-circle position-absolute top-0 end-0" style="width: 22px; height: 22px; padding: 0; font-size: 0.7rem; line-height: 1;">&times;</button>
                    </div>
                    <div id="imageEmptyState" class="text-muted small">
                        <i class="fas fa-image me-1 opacity-50"></i>No image selected yet
                    </div>
                </div>
                <input type="url" class="form-control form-control-custom mb-2" id="image" name="image" placeholder="https://res.cloudinary.com/...">
                <div class="d-flex align-items-center gap-2">
                    <input type="file" id="imageFile" accept="image/*" class="d-none">
                    <button type="button" class="btn btn-outline-dark btn-sm" id="uploadImageBtn">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Upload from device
                    </button>
                    <span id="imageUploadStatus" class="small text-muted"></span>
                </div>
                <small class="text-muted">Paste a URL, or upload a file (JPG, PNG, GIF, WebP — max 10 MB)</small>
            </div>
            <div class="mb-3">
                <label for="bio" class="form-label text-muted small fw-bold">Biography *</label>
                <textarea class="form-control form-control-custom" id="bio" name="bio" rows="4" required></textarea>
            </div>
            <div class="mb-3">
                <label for="achievements" class="form-label text-muted small fw-bold">Achievements (One per line or comma separated)</label>
                <textarea class="form-control form-control-custom" id="achievements" name="achievements" rows="3" placeholder="Founded the Famous Association of Gents&#10;Won Nobel Prize in 2024"></textarea>
                <small class="text-muted">Enter each achievement on a new line, or separate with commas</small>
            </div>
        </form>
    </div>
    <div class="offcanvas-footer border-0 pt-0 px-4 pb-4">
        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="button" class="btn btn-dark px-4" id="savePersonalityBtn">Save Personality</button>
        </div>
    </div>
</div>

{{-- View Personality Drawer (Right Side) --}}
<div class="offcanvas offcanvas-end personality-drawer" tabindex="-1" id="viewPersonalityDrawer" aria-labelledby="viewPersonalityDrawerLabel">
    <div class="offcanvas-header border-0 pb-0">
        <h5 class="offcanvas-title fw-bold"><i class="fas fa-user-circle me-2 text-dark"></i>Personality Details</h5>
        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body pt-2" id="viewPersonalityContent">
        <div class="text-center py-5">
            <div class="spinner-border text-dark" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
    <div class="offcanvas-footer border-0 pt-0 px-4 pb-4">
        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Close</button>
            <button type="button" class="btn btn-outline-dark px-4" id="editFromViewBtn">
                <i class="fas fa-edit me-1"></i> Edit
            </button>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal (Stays Centered) --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-body text-center p-5">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex p-4 mb-3">
                    <i class="fas fa-trash-alt fa-2x"></i>
                </div>
                <h4 class="fw-bold mb-2">Delete Personality?</h4>
                <p class="text-muted mb-4">Are you sure you want to delete this personality? This action cannot be undone.</p>
                <input type="hidden" id="deletePersonalityId">
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
    @keyframes skeleton-pulse { 0% { opacity: 0.6; } 50% { opacity: 1; } 100% { opacity: 0.6; } }
    .skeleton-box { display: block; background-color: #e9ecef; border-radius: 4px; animation: skeleton-pulse 1.5s infinite ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .form-control-custom { border: 1px solid #e9ecef; background-color: #f8f9fa; border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.9rem; transition: all 0.2s ease; }
    .form-control-custom:focus { background-color: #fff; border-color: #000; box-shadow: 0 0 0 3px rgba(0,0,0,0.08); outline: none; }

    /* Card Styles - Image covers full card */
    .personality-card {
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        height: 350px;
        background: #f8f9fa;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .personality-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 3rem rgba(0,0,0,0.175) !important;
    }

    .card-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        top: 0; left: 0;
        z-index: 1;
        transition: transform 0.4s ease;
    }
    .personality-card:hover .card-img {
        transform: scale(1.08);
    }

    .card-actions-overlay {
        position: absolute;
        top: 15px;
        right: 15px;
        display: flex;
        gap: 8px;
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 10;
    }
    .personality-card:hover .card-actions-overlay {
        opacity: 1;
    }
    .card-action-btn {
        width: 36px; height: 36px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        border: none;
        background: rgba(255, 255, 255, 0.9);
        color: #333;
        backdrop-filter: blur(4px);
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .card-action-btn:hover { background: #fff; color: #000; transform: scale(1.1); }
    .card-action-btn.delete:hover { background: #dc3545; color: #fff; }

    .card-overlay-content {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 1.25rem;
        padding-top: 3rem;
        z-index: 2;
        background: linear-gradient(0deg, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.6) 50%, rgba(0,0,0,0) 100%);
        color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }

    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        min-height: 1.4rem;
        max-width: 100%;
    }

    .text-truncate-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }

    /* Scrollbar for View Drawer Bio */
    .drawer-bio-scroll {
        max-height: 200px;
        overflow-y: auto;
        border-radius: 12px;
    }
    .drawer-bio-scroll::-webkit-scrollbar { width: 6px; }
    .drawer-bio-scroll::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .drawer-bio-scroll::-webkit-scrollbar-thumb { background: #d1d1d1; border-radius: 10px; }

    /* === Right Drawer Styles === */
    .personality-drawer {
        width: 480px !important;
        max-width: 90vw;
        border-left: none !important;
        box-shadow: -8px 0 30px rgba(0,0,0,0.12);
    }
    .personality-drawer .offcanvas-header {
        padding: 1.5rem 1.5rem 0.5rem 1.5rem;
    }
    .personality-drawer .offcanvas-body {
        padding: 0.5rem 1.5rem 1.5rem 1.5rem;
        flex-grow: 1;
        overflow-y: auto;
    }
    .personality-drawer .offcanvas-footer {
        background-color: #fff;
        border-top: 1px solid #f1f1f1;
        padding-top: 1rem !important;
    }
</style>
@endsection

@push('scripts')
<script>
let editingPersonalityId = null;
let viewDrawerInstance = null;
let allPersonalities = [];
let filteredPersonalities = [];

// ==========================
// IMAGE UPLOAD
// ==========================
const imageInputEl = document.getElementById('image');
const imageFileInput = document.getElementById('imageFile');
const imagePreviewEl = document.getElementById('imagePreview');
const imagePreviewWrapper = document.getElementById('imagePreviewWrapper');
const imageEmptyState = document.getElementById('imageEmptyState');
const imageUploadStatus = document.getElementById('imageUploadStatus');
const uploadImageBtn = document.getElementById('uploadImageBtn');

const IMAGE_UPLOAD_URL = '/admin/api/images/upload';

uploadImageBtn.addEventListener('click', () => imageFileInput.click());

document.getElementById('removeImageBtn').addEventListener('click', () => {
    imageInputEl.value = '';
    clearImagePreview();
});

imageInputEl.addEventListener('change', () => showImagePreview(imageInputEl.value));
imagePreviewEl.addEventListener('error', () => clearImagePreview());

function showImagePreview(url) {
    if (!url) { clearImagePreview(); return; }
    imagePreviewEl.src = url;
    imagePreviewWrapper.style.display = 'inline-block';
    imageEmptyState.style.display = 'none';
}

function clearImagePreview() {
    imagePreviewEl.removeAttribute('src');
    imagePreviewWrapper.style.display = 'none';
    imageEmptyState.style.display = 'block';
}

imageFileInput.addEventListener('change', async function () {
    const file = this.files[0];
    this.value = '';
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        showToast('Please choose an image file', 'danger');
        return;
    }
    if (file.size > 10240 * 1024) {
        showToast('Image is too large (max 10 MB)', 'danger');
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => showImagePreview(e.target.result);
    reader.readAsDataURL(file);

    imageUploadStatus.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Uploading…';
    uploadImageBtn.disabled = true;

    const fd = new FormData();
    fd.append('image', file);
    fd.append('folder', 'personalities');

    try {
        const response = await fetch(IMAGE_UPLOAD_URL, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: fd,
        });

        const data = await response.json();

        if (data.success && data.data && data.data.url) {
            imageInputEl.value = data.data.url;
            showImagePreview(data.data.url);
            imageUploadStatus.innerHTML = '<i class="fas fa-check-circle text-success"></i> Uploaded';
            showToast('Image uploaded', 'success');
        } else {
            const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : null) || 'Upload failed';
            clearImagePreview();
            imageUploadStatus.textContent = '';
            showToast(msg, 'danger');
        }
    } catch (err) {
        console.error('Image upload error:', err);
        clearImagePreview();
        imageUploadStatus.textContent = '';
        showToast('Error uploading image', 'danger');
    } finally {
        uploadImageBtn.disabled = false;
        setTimeout(() => { imageUploadStatus.textContent = ''; }, 5000);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    loadPersonalities();
    setupFilters();
});

function setupFilters() {
    const searchInput = document.getElementById('searchPersonalities');
    const categoryFilter = document.getElementById('categoryFilter');

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (categoryFilter) categoryFilter.addEventListener('change', applyFilters);
}

function applyFilters() {
    const query = document.getElementById('searchPersonalities').value.toLowerCase().trim();
    const selectedCategory = document.getElementById('categoryFilter').value;

    filteredPersonalities = allPersonalities.filter(p => {
        const name = (p.name || '').toLowerCase();
        const occupation = (p.occupation || '').toLowerCase();
        const category = (p.category || '').toLowerCase();

        const matchesQuery = !query || name.includes(query) || occupation.includes(query) || category.includes(query);
        const matchesCategory = !selectedCategory || (p.category || '') === selectedCategory;

        return matchesQuery && matchesCategory;
    });

    renderPersonalities(filteredPersonalities);
    updateFilteredCount(filteredPersonalities.length);
}

function updateFilteredCount(count) {
    const countElement = document.getElementById('recordCount');
    if (countElement) countElement.textContent = count === 1 ? '1 record found' : count + ' records found';
}

async function loadPersonalities() {
    const skeleton = document.getElementById('personalities-skeleton');
    const content = document.getElementById('personalities-content');

    try {
        const response = await fetch('/admin/api/personalities');
        const data = await response.json();

        if (data.success) {
            allPersonalities = data.data || [];
            filteredPersonalities = allPersonalities;

            // Populate Category Filter
            const categories = [...new Set(allPersonalities.map(p => p.category).filter(Boolean))];
            const categoryFilter = document.getElementById('categoryFilter');
            categoryFilter.innerHTML = '<option value="">All Categories</option>' +
                categories.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');

            renderPersonalities(filteredPersonalities);
            updateFilteredCount(filteredPersonalities.length);
        } else {
            showToast('Failed to load personalities', 'danger');
            renderPersonalities([]);
            updateFilteredCount(0);
        }
    } catch (error) {
        console.error('Error loading personalities:', error);
        showToast('Error loading personalities', 'danger');
        renderPersonalities([]);
        updateFilteredCount(0);
    } finally {
        if (skeleton) skeleton.style.display = 'none';
        if (content) content.style.display = 'flex';
    }
}

function renderPersonalities(personalities) {
    const grid = document.getElementById('personalities-content');

    if (!personalities || personalities.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="text-muted">
                    <i class="fas fa-user-slash fa-2x mb-3 d-block opacity-50"></i>
                    <p class="mb-0 fw-bold">${allPersonalities.length > 0 ? 'No matching personalities found' : 'No personalities found'}</p>
                    <small>${allPersonalities.length > 0 ? 'Try adjusting your search or filter' : 'Click "Add Personality" to create one'}</small>
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    personalities.forEach((p) => {
        let achievementsHtml = '';
        if (p.achievements && p.achievements.length > 0) {
            const displayAchievements = p.achievements.slice(0, 2);
            const remaining = p.achievements.length - 2;

            achievementsHtml = displayAchievements.map(a =>
                `<span class="badge bg-light text-dark me-1 mb-1">${escapeHtml(a)}</span>`
            ).join('');

            if (remaining > 0) {
                achievementsHtml += `<span class="badge bg-dark text-white mb-1">+${remaining} more</span>`;
            }
        } else {
            achievementsHtml = '<span class="badge bg-secondary text-white mb-1">No achievements listed</span>';
        }

        const categoryHtml = p.category ? `<span class="badge bg-light text-dark mb-2 align-self-start"><i class="fas fa-tag me-1"></i>${escapeHtml(p.category)}</span>` : '';
        const imgSrc = p.image || 'https://via.placeholder.com/400x300?text=No+Image';

        html += `
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="personality-card">
                    <img src="${escapeHtml(imgSrc)}" alt="${escapeHtml(p.name)}" class="card-img">

                    <div class="card-actions-overlay">
                        <button class="card-action-btn" onclick="viewPersonality('${p.id}')" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="card-action-btn" onclick="editPersonality('${p.id}')" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="card-action-btn delete" onclick="confirmDelete('${p.id}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>

                    <div class="card-overlay-content">
                        ${categoryHtml}
                        <h5 class="fw-bold mb-1 text-white text-truncate-1">${escapeHtml(p.name)}</h5>
                        <p class="small mb-2 text-white text-truncate-1"><i class="fas fa-briefcase me-1"></i> ${escapeHtml(p.occupation || 'N/A')}</p>
                        <p class="card-text small text-white text-truncate-2 mb-2">${escapeHtml(p.bio)}</p>
                        <div class="d-flex flex-wrap">
                            ${achievementsHtml}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    grid.innerHTML = html;
}

function openAddDrawer() {
    editingPersonalityId = null;
    document.getElementById('personalityDrawerTitle').textContent = 'Add Personality';
    document.getElementById('personalityForm').reset();
    clearImagePreview(); imageUploadStatus.textContent = '';
    document.getElementById('personalityId').value = '';
    document.getElementById('savePersonalityBtn').textContent = 'Save Personality';

    const offcanvas = new bootstrap.Offcanvas(document.getElementById('personalityDrawer'));
    offcanvas.show();
}

async function editPersonality(id) {
    try {
        const response = await fetch(`/admin/api/personalities/${id}`);
        const data = await response.json();

        if (data.success) {
            const p = data.data;
            editingPersonalityId = id;

            document.getElementById('personalityDrawerTitle').textContent = 'Edit Personality';
            document.getElementById('personalityId').value = id;
            document.getElementById('name').value = p.name || '';
            document.getElementById('occupation').value = p.occupation || '';
            document.getElementById('category').value = p.category || '';
            document.getElementById('bio').value = p.bio || '';
            document.getElementById('image').value = p.image || '';
            showImagePreview(p.image || '');imageUploadStatus.textContent = '';
            document.getElementById('achievements').value = (p.achievements || []).join('\n');
            document.getElementById('savePersonalityBtn').textContent = 'Update Personality';

            if (viewDrawerInstance) viewDrawerInstance.hide();

            const offcanvas = new bootstrap.Offcanvas(document.getElementById('personalityDrawer'));
            offcanvas.show();
        } else {
            showToast('Failed to load data', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error loading data', 'danger');
    }
}

async function viewPersonality(id) {
    const content = document.getElementById('viewPersonalityContent');
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-dark" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    viewDrawerInstance = new bootstrap.Offcanvas(document.getElementById('viewPersonalityDrawer'));
    viewDrawerInstance.show();

    try {
        const response = await fetch(`/admin/api/personalities/${id}`);
        const data = await response.json();

        if (data.success) {
            const p = data.data;

            const achievementsHtml = p.achievements && p.achievements.length > 0
                ? p.achievements.map(a => `<li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>${escapeHtml(a)}</li>`).join('')
                : '<li class="text-muted">No achievements listed</li>';

            content.innerHTML = `
                <div class="mb-4">
                    <img src="${escapeHtml(p.image || 'https://via.placeholder.com/480x300?text=No+Image')}" class="w-100 mb-4 shadow-sm" style="height: 300px; object-fit: cover; border-radius: 12px;">
                    <div class="text-center">
                        <h4 class="fw-bold mb-1">${escapeHtml(p.name)}</h4>
                        ${p.occupation ? `<p class="text-muted mb-1"><i class="fas fa-briefcase me-2"></i>${escapeHtml(p.occupation)}</p>` : ''}
                        ${p.category ? `<p class="text-muted mb-3"><i class="fas fa-tag me-2"></i>${escapeHtml(p.category)}</p>` : ''}
                    </div>
                </div>

                <div class="bg-light p-3 rounded-3 mb-3 drawer-bio-scroll">
                    <small class="text-muted d-block text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Biography</small>
                    <p class="mb-0" style="font-size: 0.9rem; line-height: 1.6;">${escapeHtml(p.bio)}</p>
                </div>

                <div class="bg-light p-3 rounded-3">
                    <small class="text-muted d-block text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Achievements</small>
                    <ul class="list-unstyled mb-0" style="font-size: 0.9rem;">
                        ${achievementsHtml}
                    </ul>
                </div>
            `;

            document.getElementById('editFromViewBtn').onclick = () => editPersonality(id);
        } else {
            content.innerHTML = `
                <div class="text-center text-danger py-5">
                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                    <p>Failed to load personality details.</p>
                </div>
            `;
            showToast('Failed to load details', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        content.innerHTML = `
            <div class="text-center text-danger py-5">
                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                <p>Error loading personality details.</p>
            </div>
        `;
        showToast('Error loading details', 'danger');
    }
}

document.getElementById('savePersonalityBtn').addEventListener('click', async function() {
    const form = document.getElementById('personalityForm');
    const formData = new FormData(form);
    const id = document.getElementById('personalityId').value;

    const payload = {
        name: formData.get('name'),
        occupation: formData.get('occupation'),
        bio: formData.get('bio'),
        image: formData.get('image'),
        category: formData.get('category'),
        achievements: formData.get('achievements'),
    };

    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        let url = '/admin/api/personalities';
        let method = 'POST';

        if (id) {
            url = `/admin/api/personalities/${id}`;
            method = 'PUT';
        }

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
            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('personalityDrawer'));
            offcanvas.hide();
            loadPersonalities();
        } else {
            showToast(data.message || 'Failed to save', 'danger');
        }
    } catch (error) {
        console.error('Error saving:', error);
        showToast('Error saving personality', 'danger');
    } finally {
        this.disabled = false;
        this.innerHTML = id ? 'Update Personality' : 'Save Personality';
    }
});

function confirmDelete(id) {
    document.getElementById('deletePersonalityId').value = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    const id = document.getElementById('deletePersonalityId').value;

    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

        const response = await fetch(`/admin/api/personalities/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            }
        });

        const data = await response.json();

        if (data.success) {
            showToast('Personality deleted', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            modal.hide();
            loadPersonalities();
        } else {
            showToast('Failed to delete', 'danger');
        }
    } catch (error) {
        console.error('Error deleting:', error);
        showToast('Error deleting', 'danger');
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
