@extends('layouts.admin')

@section('title', 'Facts Management')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-lightbulb me-2"></i>Facts Management</h4>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark btn-sm d-flex align-items-center" onclick="openAddModal()">
                <i class="fas fa-plus me-2"></i>Add Fact
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
                       id="searchFacts"
                       placeholder="Search by title, category, or author..."
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
            <div id="facts-skeleton">
                <div class="table-responsive" style="max-height: 900px; overflow-y: auto;">
                    <table class="table align-middle mb-0">
                        <thead class="border-bottom-2 sticky-top bg-white" style="top: 0; z-index: 10;">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 50px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 200px;">Title</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 160px;">Category</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Author</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 250px;">Description</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 6; $i++)
                                <tr>
                                    <td><div class="skeleton-box mx-auto" style="width: 20px; height: 16px; border-radius: 4px;"></div></td>
                                    <td><div class="skeleton-box" style="width: 160px; height: 16px; border-radius: 4px;"></div></td>
                                    <td><div class="skeleton-box" style="width: 100px; height: 14px; border-radius: 4px;"></div></td>
                                    <td><div class="skeleton-box" style="width: 80px; height: 14px; border-radius: 4px;"></div></td>
                                    <td><div class="skeleton-box" style="width: 200px; height: 14px; border-radius: 4px;"></div></td>
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
            <div id="facts-content" style="display: none; animation: fadeIn 0.5s ease-in-out;">
                <div class="table-responsive" id="factsTableWrapper" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="border-bottom-2 sticky-top bg-white" style="top: 0; z-index: 10;">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 50px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 200px;">Title</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 160px;">Category</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Author</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 250px;">Description</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="factsTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add/Edit Fact Modal --}}
<div class="modal fade" id="factModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="factModalTitle">Add Fact</h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <form id="factForm">
                    <input type="hidden" id="factId" name="factId">
                    <div class="mb-3">
                        <label for="title" class="form-label text-muted small fw-bold">Title *</label>
                        <input type="text" class="form-control form-control-custom" id="title" name="title" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label text-muted small fw-bold">Category</label>
                            <input type="text" class="form-control form-control-custom" id="category" name="category" placeholder="e.g., Space Travel">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="author" class="form-label text-muted small fw-bold">Author</label>
                            <input type="text" class="form-control form-control-custom" id="author" name="author" placeholder="e.g., dev">
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
                        <label for="description" class="form-label text-muted small fw-bold">Description *</label>
                        <textarea class="form-control form-control-custom" id="description" name="description" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark px-4" id="saveFactBtn">Save Fact</button>
            </div>
        </div>
    </div>
</div>

{{-- View Fact Modal --}}
<div class="modal fade" id="viewFactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-info-circle me-2 text-dark"></i>Fact Details</h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="viewFactContent">
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
                <h4 class="fw-bold mb-2">Delete Fact?</h4>
                <p class="text-muted mb-4">Are you sure you want to delete this fact? This action cannot be undone.</p>
                <input type="hidden" id="deleteFactId">
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
    .action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: none; background: transparent; color: #6c757d; transition: all 0.2s ease; }
    .action-btn:hover { background: #f1f1f1; color: #000; }
    .action-btn.delete:hover { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
    .table > :not(caption) > * > * { padding: 0.75rem 0.75rem; vertical-align: middle; }
    .desc-truncate { max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sticky-top { position: sticky; top: 0; z-index: 10; background-color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    #factsTableWrapper::-webkit-scrollbar { width: 6px; }
    #factsTableWrapper::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    #factsTableWrapper::-webkit-scrollbar-thumb { background: #d1d1d1; border-radius: 10px; }
    #factsTableWrapper::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
    .input-group .form-control:focus { border-color: #dee2e6; box-shadow: none; }
    .input-group .form-control:focus + .input-group-text { border-color: #dee2e6; }
</style>
@endsection

@push('scripts')
<script>
let editingFactId = null;
let viewModalInstance = null;
let allFacts = [];
let filteredFacts = [];

// Image Upload Logic
const imageInputEl = document.getElementById('image');
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
    fd.append('folder', 'facts');

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
    loadFacts();
    setupFilters();
});

function setupFilters() {
    const searchInput = document.getElementById('searchFacts');
    const categoryFilter = document.getElementById('categoryFilter');
    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (categoryFilter) categoryFilter.addEventListener('change', applyFilters);
}

function applyFilters() {
    const query = document.getElementById('searchFacts').value.toLowerCase().trim();
    const selectedCategory = document.getElementById('categoryFilter').value;

    filteredFacts = allFacts.filter(f => {
        const title = (f.title || '').toLowerCase();
        const category = (f.category || '').toLowerCase();
        const author = (f.author || '').toLowerCase();

        const matchesQuery = !query || title.includes(query) || category.includes(query) || author.includes(query);
        const matchesCategory = !selectedCategory || (f.category || '') === selectedCategory;

        return matchesQuery && matchesCategory;
    });

    renderFacts(filteredFacts);
    updateFilteredCount(filteredFacts.length);
}

function updateFilteredCount(count) {
    const countElement = document.getElementById('recordCount');
    if (countElement) countElement.textContent = count === 1 ? '1 record found' : count + ' records found';
}

async function loadFacts() {
    const skeleton = document.getElementById('facts-skeleton');
    const content = document.getElementById('facts-content');

    try {
        const response = await fetch('/admin/api/facts');
        const data = await response.json();

        if (data.success) {
            allFacts = data.data || [];
            filteredFacts = allFacts;

            const categories = [...new Set(allFacts.map(f => f.category).filter(Boolean))];
            const categoryFilter = document.getElementById('categoryFilter');
            categoryFilter.innerHTML = '<option value="">All Categories</option>' +
                categories.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');

            renderFacts(filteredFacts);
            updateFilteredCount(filteredFacts.length);
        } else {
            showToast('Failed to load facts', 'danger');
            renderFacts([]); updateFilteredCount(0);
        }
    } catch (error) {
        console.error('Error loading facts:', error);
        showToast('Error loading facts', 'danger');
        renderFacts([]); updateFilteredCount(0);
    } finally {
        if (skeleton) skeleton.style.display = 'none';
        if (content) content.style.display = 'block';
    }
}

function renderFacts(facts) {
    const tbody = document.getElementById('factsTableBody');

    if (!facts || facts.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-lightbulb fa-2x mb-3 d-block opacity-50"></i>
                        <p class="mb-0 fw-bold">${allFacts.length > 0 ? 'No matching facts found' : 'No facts found'}</p>
                        <small>${allFacts.length > 0 ? 'Try adjusting your search or filter' : 'Click "Add Fact" to create one'}</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    facts.forEach((f, index) => {
        const categoryHtml = f.category ? `<span class="badge bg-light text-dark border">${escapeHtml(f.category)}</span>` : '<span class="text-muted">—</span>';

        html += `
            <tr>
                <td class="text-muted">${index + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${escapeHtml(f.image || 'https://via.placeholder.com/50')}" alt="${escapeHtml(f.title)}" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                        <div class="fw-bold text-dark">${escapeHtml(f.title)}</div>
                    </div>
                </td>
                <td>${categoryHtml}</td>
                <td class="text-muted small">${escapeHtml(f.author || '—')}</td>
                <td class="text-muted small desc-truncate">${escapeHtml(f.description)}</td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <button class="action-btn" onclick="viewFact('${f.id}')" title="View"><i class="fas fa-eye"></i></button>
                        <button class="action-btn" onclick="editFact('${f.id}')" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="action-btn delete" onclick="confirmDelete('${f.id}')" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function openAddModal() {
    editingFactId = null;
    document.getElementById('factModalTitle').textContent = 'Add Fact';
    document.getElementById('factForm').reset();
    clearImagePreview(); imageUploadStatus.textContent = '';
    document.getElementById('factId').value = '';
    document.getElementById('saveFactBtn').textContent = 'Save Fact';
    const modal = new bootstrap.Modal(document.getElementById('factModal'));
    modal.show();
}

async function editFact(id) {
    try {
        const response = await fetch(`/admin/api/facts/${id}`);
        const data = await response.json();

        if (data.success) {
            const f = data.data;
            editingFactId = id;

            document.getElementById('factModalTitle').textContent = 'Edit Fact';
            document.getElementById('factId').value = id;
            document.getElementById('title').value = f.title || '';
            document.getElementById('category').value = f.category || '';
            document.getElementById('author').value = f.author || '';
            document.getElementById('description').value = f.description || '';
            document.getElementById('image').value = f.image || '';
            showImagePreview(f.image || ''); imageUploadStatus.textContent = '';
            document.getElementById('saveFactBtn').textContent = 'Update Fact';

            if (viewModalInstance) viewModalInstance.hide();
            const modal = new bootstrap.Modal(document.getElementById('factModal'));
            modal.show();
        } else { showToast('Failed to load data', 'danger'); }
    } catch (error) { console.error('Error:', error); showToast('Error loading data', 'danger'); }
}

async function viewFact(id) {
    const content = document.getElementById('viewFactContent');
    content.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-dark" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
    viewModalInstance = new bootstrap.Modal(document.getElementById('viewFactModal'));
    viewModalInstance.show();

    try {
        const response = await fetch(`/admin/api/facts/${id}`);
        const data = await response.json();

        if (data.success) {
            const f = data.data;
            content.innerHTML = `
                <div class="text-center mb-4">
                    <img src="${escapeHtml(f.image || 'https://via.placeholder.com/120')}" class="rounded mb-3" style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #f8f9fa;">
                    <h4 class="fw-bold mb-1">${escapeHtml(f.title)}</h4>
                    ${f.category ? `<p class="text-muted mb-1"><i class="fas fa-tag me-2"></i>${escapeHtml(f.category)}</p>` : ''}
                    ${f.author ? `<p class="text-muted mb-3"><i class="fas fa-user-edit me-2"></i>By ${escapeHtml(f.author)}</p>` : ''}
                </div>
                <div class="bg-light p-3 rounded-3">
                    <small class="text-muted d-block text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">Description</small>
                    <p class="mb-0" style="font-size: 0.9rem; line-height: 1.6;">${escapeHtml(f.description)}</p>
                </div>
            `;
            document.getElementById('editFromViewBtn').onclick = () => editFact(id);
        } else {
            content.innerHTML = `<div class="text-center text-danger py-5"><i class="fas fa-exclamation-circle fa-2x mb-2"></i><p>Failed to load fact details.</p></div>`;
        }
    } catch (error) {
        content.innerHTML = `<div class="text-center text-danger py-5"><i class="fas fa-exclamation-circle fa-2x mb-2"></i><p>Error loading fact details.</p></div>`;
    }
}

document.getElementById('saveFactBtn').addEventListener('click', async function() {
    const form = document.getElementById('factForm');
    const formData = new FormData(form);
    const id = document.getElementById('factId').value;

    const payload = {
        title: formData.get('title'),
        category: formData.get('category'),
        author: formData.get('author'),
        description: formData.get('description'),
        image: formData.get('image'),
    };

    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        let url = '/admin/api/facts'; let method = 'POST';
        if (id) { url = `/admin/api/facts/${id}`; method = 'PUT'; }

        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: JSON.stringify(payload)
        });
        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('factModal'));
            modal.hide();
            loadFacts();
        } else { showToast(data.message || 'Failed to save', 'danger'); }
    } catch (error) { showToast('Error saving fact', 'danger'); }
    finally {
        this.disabled = false;
        this.innerHTML = id ? 'Update Fact' : 'Save Fact';
    }
});

function confirmDelete(id) {
    document.getElementById('deleteFactId').value = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    const id = document.getElementById('deleteFactId').value;
    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
        const response = await fetch(`/admin/api/facts/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
        });
        const data = await response.json();
        if (data.success) {
            showToast('Fact deleted', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            modal.hide();
            loadFacts();
        } else { showToast('Failed to delete', 'danger'); }
    } catch (error) { showToast('Error deleting', 'danger'); }
    finally {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-trash me-1"></i> Delete';
    }
});

function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toastContainer'; container.className = 'position-fixed bottom-0 end-0 p-3'; container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast show align-items-center text-white bg-${type === 'danger' ? 'danger' : 'dark'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body"><i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${escapeHtml(message)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;
    document.getElementById('toastContainer').insertAdjacentHTML('beforeend', toastHtml);
    setTimeout(() => { const toast = document.getElementById(toastId); if (toast) toast.remove(); }, 5000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush
