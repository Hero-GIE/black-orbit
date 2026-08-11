@extends('layouts.admin')

@section('title', 'Enrollment Management')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-user-graduate me-2"></i>Enrollment Management</h4>
        <button class="btn btn-outline-dark btn-sm" onclick="window.location.href='{{ route('admin.dashboard') }}'">
            <i class="fas fa-arrow-left me-1"></i>Back
        </button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="enrollmentsTable">
                    <thead class="border-bottom-2">
                        <tr>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">#</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Student</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Course</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Category</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Level</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Progress</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Status</th>
                            <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px;">Enrolled Date</th>
                        </tr>
                    </thead>
                    <tbody id="enrollmentsTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <div class="spinner-border text-dark mb-3" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mb-0">Loading enrollments...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Progress bar styles */
    .progress {
        height: 6px;
        border-radius: 10px;
        background-color: #f1f1f1;
        min-width: 80px;
    }
    .progress-bar {
        border-radius: 10px;
        transition: width 0.6s ease;
    }
    .progress-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: #6c757d;
    }
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-badge.active {
        background: #d4edda;
        color: #155724;
    }
    .status-badge.in-progress {
        background: #fff3cd;
        color: #856404;
    }
    .status-badge.completed {
        background: #cce5ff;
        color: #004085;
    }
    .status-badge.pending {
        background: #f8d7da;
        color: #721c24;
    }
    .status-badge.dropped {
        background: #e2e3e5;
        color: #383d41;
    }

    .table > :not(caption) > * > * {
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }
</style>
@endsection

@push('scripts')
<script>
// Load enrollments on page load
document.addEventListener('DOMContentLoaded', function() {
    loadEnrollments();
});

// Load all enrollments
async function loadEnrollments() {
    try {
        const response = await fetch('/admin/api/enrollments');
        const data = await response.json();

        if (data.success) {
            renderEnrollments(data.data);
        } else {
            showToast('Failed to load enrollments', 'danger');
        }
    } catch (error) {
        console.error('Error loading enrollments:', error);
        showToast('Error loading enrollments', 'danger');
    }
}

// Render enrollments in table
function renderEnrollments(enrollments) {
    const tbody = document.getElementById('enrollmentsTableBody');

    if (!enrollments || enrollments.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-user-graduate fa-2x mb-3 d-block opacity-50"></i>
                        <p class="mb-0 fw-bold">No enrollments found</p>
                        <small>Students will appear here when they enroll in courses</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    enrollments.forEach((enrollment, index) => {
        const progress = enrollment.progress || 0;
        const progressColor = progress >= 80 ? 'bg-success' :
                             progress >= 50 ? 'bg-primary' :
                             progress >= 25 ? 'bg-warning' : 'bg-danger';
        const statusClass = (enrollment.status || 'pending').toLowerCase().replace(' ', '-');
        const enrolledDate = enrollment.enrolled_date ? new Date(enrollment.enrolled_date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }) : 'N/A';

        html += `
            <tr>
                <td class="text-center text-muted">${index + 1}</td>
                <td>
                    <div>
                        <div class="fw-bold text-dark">${escapeHtml(enrollment.username)}</div>
                        <div class="text-muted small">${escapeHtml(enrollment.email)}</div>
                    </div>
                </td>
                <td>
                    <div class="fw-bold">${escapeHtml(enrollment.course_name)}</div>
                </td>
                <td><span class="badge bg-light text-dark border">${escapeHtml(enrollment.category)}</span></td>
                <td><span class="badge bg-secondary">${escapeHtml(enrollment.level)}</span></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1">
                            <div class="progress-bar ${progressColor}" role="progressbar"
                                 style="width: ${progress}%;"
                                 aria-valuenow="${progress}"
                                 aria-valuemin="0"
                                 aria-valuemax="100"></div>
                        </div>
                        <span class="progress-label">${progress}%</span>
                    </div>
                </td>
                <td><span class="status-badge ${statusClass}">${escapeHtml(enrollment.status || 'Pending')}</span></td>
                <td class="text-end text-muted">${enrolledDate}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

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

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush
