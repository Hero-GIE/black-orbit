@extends('layouts.admin')

@section('title', 'Personalities Management')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-users me-2"></i>Personalities Management</h4>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark btn-sm d-flex align-items-center" onclick="openAddModal()">
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

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <!-- SKELETON LOADING -->
            <div id="personalities-skeleton">
                <div class="table-responsive" style="max-height: 900px; overflow-y: auto;">
                    <table class="table align-middle mb-0">
                        <thead class="border-bottom-2 sticky-top bg-white" style="top: 0; z-index: 10;">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 50px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 180px;">Name</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 160px;">Occupation</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Category</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 200px;">Achievements</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 200px;">Bio</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 6; $i++)
                                <tr>
                                    <td>
                                        <div class="skeleton-box mx-auto" style="width: 20px; height: 16px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="skeleton-box me-3" style="width: 40px; height: 40px; border-radius: 50%;"></div>
                                            <div class="skeleton-box" style="width: 120px; height: 16px; border-radius: 4px;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 100px; height: 14px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 80px; height: 14px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 150px; height: 20px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 160px; height: 14px; border-radius: 4px;"></div>
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
            <div id="personalities-content" style="display: none; animation: fadeIn 0.5s ease-in-out;">
                <div class="table-responsive" id="personalitiesTableWrapper" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="border-bottom-2 sticky-top bg-white" style="top: 0; z-index: 10;">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 50px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 180px;">Name</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 160px;">Occupation</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Category</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 200px;">Achievements</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 200px;">Bio</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="personalitiesTableBody">
                            <!-- Dynamic content -->
                        </tbody>
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

{{-- Add/Edit Personality Modal --}}
<div class="modal fade" id="personalityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="personalityModalTitle">Add Personality</h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <form id="personalityForm">
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
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark px-4" id="savePersonalityBtn">Save Personality</button>
            </div>
        </div>
    </div>
</div>

{{-- View Personality Modal --}}
<div class="modal fade" id="viewPersonalityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-circle me-2 text-dark"></i>Personality Details</h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="viewPersonalityContent">
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

{{-- Delete Confirmation Modal --}}
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

    .bio-truncate {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .achievements-truncate {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .achievements-truncate .badge {
        margin-right: 2px;
        font-size: 0.6rem;
        padding: 2px 6px;
    }

    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    #personalitiesTableWrapper::-webkit-scrollbar { width: 6px; }
    #personalitiesTableWrapper::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    #personalitiesTableWrapper::-webkit-scrollbar-thumb { background: #d1d1d1; border-radius: 10px; }
    #personalitiesTableWrapper::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

    .input-group .form-control:focus {
        border-color: #dee2e6;
        box-shadow: none;
    }

    .input-group .form-control:focus + .input-group-text {
        border-color: #dee2e6;
    }
</style>
@endsection

@push('scripts')
<script>
let editingPersonalityId = null;
let viewModalInstance = null;
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

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }
    if (categoryFilter) {
        categoryFilter.addEventListener('change', applyFilters);
    }
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
    if (countElement) {
        countElement.textContent = count === 1 ? '1 record found' : count + ' records found';
    }
}

async function loadPersonalities() {
    const skeleton = document.getElementById('personalities-skeleton');
    const content = document.getElementById('personalities-content');
    const recordCount = document.getElementById('recordCount');

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
        if (content) content.style.display = 'block';
    }
}

function renderPersonalities(personalities) {
    const tbody = document.getElementById('personalitiesTableBody');
    const scrollIndicator = document.getElementById('scrollIndicator');
    const tableWrapper = document.getElementById('personalitiesTableWrapper');

    if (personalities && personalities.length > 10) {
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

    if (!personalities || personalities.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-user-slash fa-2x mb-3 d-block opacity-50"></i>
                        <p class="mb-0 fw-bold">${allPersonalities.length > 0 ? 'No matching personalities found' : 'No personalities found'}</p>
                        <small>${allPersonalities.length > 0 ? 'Try adjusting your search or filter' : 'Click "Add Personality" to create one'}</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    personalities.forEach((p, index) => {
        let achievementsHtml = '';
        if (p.achievements && p.achievements.length > 0) {
            const displayAchievements = p.achievements.slice(0, 3);
            const remaining = p.achievements.length - 3;

            achievementsHtml = displayAchievements.map(a =>
                `<span class="badge bg-light text-dark border me-1">${escapeHtml(a)}</span>`
            ).join('');

            if (remaining > 0) {
                achievementsHtml += `<span class="badge bg-secondary text-white">+${remaining} more</span>`;
            }
        } else {
            achievementsHtml = '<span class="text-muted">—</span>';
        }

        const categoryHtml = p.category ? escapeHtml(p.category) : '<span class="text-muted">—</span>';

        html += `
            <tr>
                <td class="text-muted">${index + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${escapeHtml(p.image || 'https://via.placeholder.com/50')}" alt="${escapeHtml(p.name)}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                        <div class="fw-bold text-dark">${escapeHtml(p.name)}</div>
                    </div>
                </td>
                <td class="text-muted small">${escapeHtml(p.occupation || '—')}</td>
                <td class="small">${categoryHtml}</td>
                <td>
                    <div class="achievements-truncate">
                        ${achievementsHtml}
                    </div>
                </td>
                <td class="text-muted small bio-truncate">${escapeHtml(p.bio)}</td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <button class="action-btn" onclick="viewPersonality('${p.id}')" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn" onclick="editPersonality('${p.id}')" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="confirmDelete('${p.id}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function openAddModal() {
    editingPersonalityId = null;
    document.getElementById('personalityModalTitle').textContent = 'Add Personality';
    document.getElementById('personalityForm').reset();
    clearImagePreview(); imageUploadStatus.textContent = '';
    document.getElementById('personalityId').value = '';
    document.getElementById('savePersonalityBtn').textContent = 'Save Personality';

    const modal = new bootstrap.Modal(document.getElementById('personalityModal'));
    modal.show();
}

async function editPersonality(id) {
    try {
        const response = await fetch(`/admin/api/personalities/${id}`);
        const data = await response.json();

        if (data.success) {
            const p = data.data;
            editingPersonalityId = id;

            document.getElementById('personalityModalTitle').textContent = 'Edit Personality';
            document.getElementById('personalityId').value = id;
            document.getElementById('name').value = p.name || '';
            document.getElementById('occupation').value = p.occupation || '';
            document.getElementById('category').value = p.category || '';
            document.getElementById('bio').value = p.bio || '';
            document.getElementById('image').value = p.image || '';
            showImagePreview(p.image || '');imageUploadStatus.textContent = '';
            document.getElementById('achievements').value = (p.achievements || []).join('\n');
            document.getElementById('savePersonalityBtn').textContent = 'Update Personality';

            if (viewModalInstance) viewModalInstance.hide();

            const modal = new bootstrap.Modal(document.getElementById('personalityModal'));
            modal.show();
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

    viewModalInstance = new bootstrap.Modal(document.getElementById('viewPersonalityModal'));
    viewModalInstance.show();

    try {
        const response = await fetch(`/admin/api/personalities/${id}`);
        const data = await response.json();

        if (data.success) {
            const p = data.data;

            const achievementsHtml = p.achievements && p.achievements.length > 0
                ? p.achievements.map(a => `<li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>${escapeHtml(a)}</li>`).join('')
                : '<li class="text-muted">No achievements listed</li>';

            content.innerHTML = `
                <div class="text-center mb-4">
                    <img src="${escapeHtml(p.image || 'https://via.placeholder.com/120')}" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #f8f9fa;">
                    <h4 class="fw-bold mb-1">${escapeHtml(p.name)}</h4>
                    ${p.occupation ? `<p class="text-muted mb-1"><i class="fas fa-briefcase me-2"></i>${escapeHtml(p.occupation)}</p>` : ''}
                    ${p.category ? `<p class="text-muted mb-3"><i class="fas fa-tag me-2"></i>${escapeHtml(p.category)}</p>` : ''}
                </div>
                <div class="bg-light p-3 rounded-3 mb-3">
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('personalityModal'));
            modal.hide();
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
