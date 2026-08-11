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

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="border-bottom-2">
                        <tr>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">#</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Name</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Bio</th>
                            <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="personalitiesTableBody">
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="text-muted">
                                    <div class="spinner-border text-dark mb-3" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mb-0">Loading personalities...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                    <div class="mb-3">
                        <label for="image" class="form-label text-muted small fw-bold">Image URL</label>
                        <input type="url" class="form-control form-control-custom" id="image" name="image" placeholder="https://res.cloudinary.com/...">
                    </div>
                    <div class="mb-3">
                        <label for="bio" class="form-label text-muted small fw-bold">Biography *</label>
                        <textarea class="form-control form-control-custom" id="bio" name="bio" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="achievements" class="form-label text-muted small fw-bold">Achievements (One per line)</label>
                        <textarea class="form-control form-control-custom" id="achievements" name="achievements" rows="3" placeholder="Founded the Famous Association of Gents&#10;Won Nobel Prize in 2024"></textarea>
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
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }

    .bio-truncate {
        max-width: 400px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
</style>
@endsection

@push('scripts')
<script>
let editingPersonalityId = null;
let viewModalInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    loadPersonalities();
});

async function loadPersonalities() {
    try {
        const response = await fetch('/admin/api/personalities');
        const data = await response.json();

        if (data.success) {
            renderPersonalities(data.data);
        } else {
            showToast('Failed to load personalities', 'danger');
        }
    } catch (error) {
        console.error('Error loading personalities:', error);
        showToast('Error loading personalities', 'danger');
    }
}

function renderPersonalities(personalities) {
    const tbody = document.getElementById('personalitiesTableBody');

    if (!personalities || personalities.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-user-slash fa-2x mb-3 d-block opacity-50"></i>
                        <p class="mb-0 fw-bold">No personalities found</p>
                        <small>Click "Add Personality" to create one</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    personalities.forEach((p, index) => {
        html += `
            <tr>
                <td class="text-muted">${index + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="${escapeHtml(p.image || 'https://via.placeholder.com/50')}" alt="${escapeHtml(p.name)}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                        <div class="fw-bold text-dark">${escapeHtml(p.name)}</div>
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
            document.getElementById('bio').value = p.bio || '';
            document.getElementById('image').value = p.image || '';
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
        bio: formData.get('bio'),
        image: formData.get('image'),
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
