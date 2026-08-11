@extends('layouts.admin')

@section('title', 'User Management')

@section('content')
<div class="container-fluid py-2">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-users me-2"></i>User Management</h4>

        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark btn-sm d-flex align-items-center" onclick="openAddModal()">
                <i class="fas fa-user-plus me-2"></i>Add User
            </button>
            <button class="btn btn-outline-dark btn-sm d-flex align-items-center" onclick="window.location.href='{{ route('admin.dashboard') }}'">
                <i class="fas fa-arrow-left me-2"></i>Back
            </button>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <div class="table-responsive table-scroll-wrapper">
    <table class="table table-hover align-middle mb-0" id="usersTable">
        <thead class="border-bottom-2">
            <tr>
                <th class="text-muted text-uppercase text-center fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">#</th>
                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">User</th>
                <th class="text-muted text-uppercase text-center fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Role</th>
                <th class="text-muted text-uppercase text-center fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Access Level</th>
                <th class="text-muted text-uppercase text-center fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Joined</th>
                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px;">Actions</th>
            </tr>
        </thead>
        <tbody id="usersTableBody">
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="text-muted">
                        <div class="spinner-border text-dark mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mb-0">Loading users...</p>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
        </div>
    </div>
</div>

{{-- Add/Edit User Modal --}}
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="userModalTitle">Add User</h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <form id="userForm">
                    <input type="hidden" id="userId" name="userId">
                    <div class="mb-3">
                        <label for="username" class="form-label text-muted small fw-bold">Username *</label>
                        <input type="text" class="form-control form-control-custom" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label text-muted small fw-bold">Email *</label>
                        <input type="email" class="form-control form-control-custom" id="email" name="email" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label text-muted small fw-bold">Role *</label>
                            <select class="form-select form-control-custom" id="role" name="role" required>
                                <option value="student">Student</option>
                                <option value="teacher">Teacher</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="accesslevel" class="form-label text-muted small fw-bold">Access Level *</label>
                            <select class="form-select form-control-custom" id="accesslevel" name="accesslevel" required>
                                <option value="user">User</option>
                                <option value="moderator">Moderator</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="interest" class="form-label text-muted small fw-bold">Interest</label>
                        <input type="text" class="form-control form-control-custom" id="interest" name="interest">
                    </div>
                    <div class="mb-3">
                        <label for="institution" class="form-label text-muted small fw-bold">Institution</label>
                        <input type="text" class="form-control form-control-custom" id="institution" name="institution">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark px-4" id="saveUserBtn">Save User</button>
            </div>
        </div>
    </div>
</div>

{{-- View User Modal --}}
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-circle me-2 text-dark"></i>User Profile</h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="viewUserContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-dark" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-dark px-4" id="editFromViewBtn">
                    <i class="fas fa-edit me-1"></i> Edit User
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
                <h4 class="fw-bold mb-2">Delete User?</h4>
                <p class="text-muted mb-4">Are you sure you want to delete this user? This action cannot be undone.</p>
                <input type="hidden" id="deleteUserId">
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

    .table-scroll-wrapper {
    max-height: 520px;
    overflow-y: auto;
    overflow-x: auto;
}

.table-scroll-wrapper thead th {
    position: sticky;
    top: 0;
    background-color: #fff;
    z-index: 10;
    box-shadow: inset 0 -1px 0 #dee2e6;
}


.table-scroll-wrapper::-webkit-scrollbar {
    width: 8px;
}

.table-scroll-wrapper::-webkit-scrollbar-thumb {
    background-color: #d1d5db;
    border-radius: 8px;
}

.table-scroll-wrapper::-webkit-scrollbar-track {
    background: transparent;
}
    .avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f1f1f1;
    color: #333;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.view-avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #f1f1f1;
    color: #333;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.5rem;
}

/* Center table content */
.table td, .table th {
    vertical-align: middle;
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

    /* Minimalist Action Buttons */
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

    /* Monochrome Role Badges */
    .role-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .role-badge.admin {
        background: #000000;
        color: #ffffff;
    }
    .role-badge.teacher {
        background: #6c757d;
        color: #ffffff;
    }
    .role-badge.student {
        background: #f1f1f1;
        color: #333333;
    }

    .table > :not(caption) > * > * {
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }
</style>
@endsection

@push('scripts')
<script>
let editingUserId = null;
let viewModalInstance = null;

// Get CSRF token from meta tag
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// Load users on page load
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
});

// Load all users
async function loadUsers() {
    try {
        const response = await fetch('/admin/api/users');
        const data = await response.json();

        if (data.success) {
            renderUsers(data.data);
        } else {
            showToast('Failed to load users', 'danger');
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showToast('Error loading users', 'danger');
    }
}

// Render users in table
function renderUsers(users) {
    const tbody = document.getElementById('usersTableBody');

    if (!users || users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-users-slash fa-2x mb-3 d-block opacity-50"></i>
                        <p class="mb-0 fw-bold">No users found</p>
                        <small>Click "Add User" to create one</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    users.forEach((user, index) => {
        const roleClass = user.role || 'student';
        // Fix: Handle both timestamp and string formats for createdAt
        let joinedDate = 'N/A';
        if (user.createdAt) {
            try {
                const date = new Date(user.createdAt);
                if (!isNaN(date.getTime())) {
                    joinedDate = date.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                }
            } catch (e) {
                joinedDate = 'N/A';
            }
        }

        const initials = (user.username || 'U').substring(0, 2).toUpperCase();

        html += `
            <tr>
                <td class="text-center text-muted">${index + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle me-3">${initials}</div>
                        <div>
                            <div class="fw-bold text-dark">${escapeHtml(user.username)}</div>
                            <div class="text-muted small">${escapeHtml(user.email)}</div>
                        </div>
                    </div>
                </td>
                <td class="text-center"><span class="role-badge ${roleClass}">${escapeHtml(user.role || 'student')}</span></td>
                <td class="text-center"><span class="badge bg-light text-dark border">${escapeHtml(user.accesslevel || 'user')}</span></td>
                <td class="text-center text-muted">${joinedDate}</td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <button class="action-btn" onclick="viewUser('${user.id}')" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn" onclick="editUser('${user.id}')" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="confirmDelete('${user.id}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// Open Add User Modal
function openAddModal() {
    editingUserId = null;
    document.getElementById('userModalTitle').textContent = 'Add New User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('saveUserBtn').textContent = 'Save User';

    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}

// Edit User
async function editUser(userId) {
    const modalBody = document.querySelector('#userModal .modal-body');
    const saveBtn = document.getElementById('saveUserBtn');

    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('userForm').style.display = 'none';
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-dark" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 mb-0 text-muted">Loading user data...</p>
        </div>
    `;
    saveBtn.disabled = true;

    if (viewModalInstance) {
        viewModalInstance.hide();
    }

    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();

    try {
        const response = await fetch(`/admin/api/users/${userId}`);
        const data = await response.json();

        if (data.success) {
            const user = data.data;
            editingUserId = userId;

            modalBody.innerHTML = `
                <form id="userForm">
                    <input type="hidden" id="userId" name="userId">
                    <div class="mb-3">
                        <label for="username" class="form-label text-muted small fw-bold">Username *</label>
                        <input type="text" class="form-control form-control-custom" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label text-muted small fw-bold">Email *</label>
                        <input type="email" class="form-control form-control-custom" id="email" name="email" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label text-muted small fw-bold">Role *</label>
                            <select class="form-select form-control-custom" id="role" name="role" required>
                                <option value="student">Student</option>
                                <option value="teacher">Teacher</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="accesslevel" class="form-label text-muted small fw-bold">Access Level *</label>
                            <select class="form-select form-control-custom" id="accesslevel" name="accesslevel" required>
                                <option value="user">User</option>
                                <option value="moderator">Moderator</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="interest" class="form-label text-muted small fw-bold">Interest</label>
                        <input type="text" class="form-control form-control-custom" id="interest" name="interest">
                    </div>
                    <div class="mb-3">
                        <label for="institution" class="form-label text-muted small fw-bold">Institution</label>
                        <input type="text" class="form-control form-control-custom" id="institution" name="institution">
                    </div>
                </form>
            `;

            document.getElementById('userId').value = userId;
            document.getElementById('username').value = user.username || '';
            document.getElementById('email').value = user.email || '';
            document.getElementById('role').value = user.role || 'student';
            document.getElementById('accesslevel').value = user.accesslevel || 'user';
            document.getElementById('interest').value = user.interest || '';
            document.getElementById('institution').value = user.institution || '';
            saveBtn.textContent = 'Update User';
            saveBtn.disabled = false;
        } else {
            showToast('Failed to load user data', 'danger');
            modal.hide();
        }
    } catch (error) {
        console.error('Error loading user:', error);
        showToast('Error loading user data', 'danger');
        modal.hide();
    }
}

// View User
async function viewUser(userId) {
    const content = document.getElementById('viewUserContent');

    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-dark" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    viewModalInstance = new bootstrap.Modal(document.getElementById('viewUserModal'));
    viewModalInstance.show();

    try {
        const response = await fetch(`/admin/api/users/${userId}`);
        const data = await response.json();

        if (data.success) {
            const user = data.data;
            const initials = (user.username || 'U').substring(0, 2).toUpperCase();

            let joinedDate = 'N/A';
            if (user.createdAt) {
                try {
                    const date = new Date(user.createdAt);
                    if (!isNaN(date.getTime())) {
                        joinedDate = date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        });
                    }
                } catch (e) {
                    joinedDate = 'N/A';
                }
            }
            content.innerHTML = `
                <div class="text-center mb-4">
                    <div class="view-avatar-circle mb-3">${initials}</div>
                    <h4 class="fw-bold mb-1">${escapeHtml(user.username)}</h4>
                    <p class="text-muted mb-2">${escapeHtml(user.email)}</p>
                    <span class="role-badge ${user.role || 'student'}">${escapeHtml(user.role || 'student')}</span>
                </div>
                <div class="bg-light p-3 rounded-3" style="border-radius: 12px !important;">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Access Level</small>
                            <span class="badge bg-dark mt-1">${escapeHtml(user.accesslevel || 'user')}</span>
                        </div>
                        <div class="col-6 mb-3">
                            <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Joined Date</small>
                            <span class="fw-bold mt-1 d-block" style="font-size: 0.85rem;">${joinedDate}</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Interest</small>
                            <span class="fw-bold mt-1 d-block" style="font-size: 0.85rem;">${escapeHtml(user.interest || 'N/A')}</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Institution</small>
                            <span class="fw-bold mt-1 d-block" style="font-size: 0.85rem;">${escapeHtml(user.institution || 'N/A')}</span>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('editFromViewBtn').onclick = () => editUser(userId);
        } else {
            content.innerHTML = `
                <div class="text-center text-danger py-5">
                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                    <p>Failed to load user details.</p>
                </div>
            `;
            showToast('Failed to load user data', 'danger');
        }
    } catch (error) {
        console.error('Error loading user:', error);
        content.innerHTML = `
            <div class="text-center text-danger py-5">
                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                <p>Error loading user details.</p>
            </div>
        `;
        showToast('Error loading user data', 'danger');
    }
}

document.getElementById('saveUserBtn').addEventListener('click', async function() {
    const form = document.getElementById('userForm');
    const formData = new FormData(form);
    const userId = document.getElementById('userId').value;

    const userData = {
        username: formData.get('username'),
        email: formData.get('email'),
        role: formData.get('role'),
        accesslevel: formData.get('accesslevel'),
        interest: formData.get('interest'),
        institution: formData.get('institution'),
    };

    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        let url = '/admin/api/users';
        let method = 'POST';

        if (userId) {
            url = `/admin/api/users/${userId}`;
            method = 'PUT';
        }

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(userData)
        });

        // Check if response is OK
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            showToast(data.message || 'User saved successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('userModal'));
            if (modal) modal.hide();
            loadUsers();
        } else {
            showToast(data.message || 'Failed to save user', 'danger');
        }
    } catch (error) {
        console.error('Error saving user:', error);
        showToast('Error saving user: ' + error.message, 'danger');
    } finally {
        this.disabled = false;
        this.innerHTML = userId ? 'Update User' : 'Save User';
    }
});

// Confirm Delete
function confirmDelete(userId) {
    document.getElementById('deleteUserId').value = userId;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Delete User
document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    const userId = document.getElementById('deleteUserId').value;

    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

        const response = await fetch(`/admin/api/users/${userId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Error response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            showToast('User deleted successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            if (modal) modal.hide();
            loadUsers();
        } else {
            showToast(data.message || 'Failed to delete user', 'danger');
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showToast('Error deleting user: ' + error.message, 'danger');
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-trash me-1"></i> Delete';
    }
});

// Toast notification
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

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            const instance = bootstrap.Modal.getInstance(modal);
            if (instance) instance.hide();
        });
    }
});
</script>
@endpush
