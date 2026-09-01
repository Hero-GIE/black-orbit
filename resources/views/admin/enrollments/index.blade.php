@extends('layouts.admin')

@section('title', 'Enrollment Management')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-user-graduate me-2"></i>Enrollment Management</h4>
            {{-- <small class="text-muted" id="recordCount">Loading...</small> --}}
        </div>
        <button class="btn btn-outline-dark btn-sm d-flex align-items-center" onclick="window.location.href='{{ route('admin.dashboard') }}'">
            <i class="fas fa-arrow-left me-2"></i>Back
        </button>
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
                       id="searchEnrollments"
                       placeholder="Search by student or course..."
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
            <div id="enrollments-skeleton">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="border-bottom-2">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 50px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 180px;">Student</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 160px;">Course</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Category</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 100px;">Level</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 150px;">Progress</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 100px;">Status</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Enrolled Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 6; $i++)
                                <tr>
                                    <td class="text-center">
                                        <div class="skeleton-box mx-auto" style="width: 20px; height: 16px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="skeleton-box mb-1" style="width: 120px; height: 16px; border-radius: 4px;"></div>
                                            <div class="skeleton-box" style="width: 150px; height: 12px; border-radius: 4px;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 100px; height: 16px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 80px; height: 20px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 60px; height: 20px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="skeleton-box" style="width: 80px; height: 6px; border-radius: 10px;"></div>
                                            <div class="skeleton-box" style="width: 30px; height: 14px; border-radius: 4px;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 70px; height: 24px; border-radius: 20px;"></div>
                                    </td>
                                    <td class="text-end">
                                        <div class="skeleton-box" style="width: 80px; height: 12px; border-radius: 4px; margin-left: auto;"></div>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ACTUAL CONTENT -->
            <div id="enrollments-content" style="display: none; animation: fadeIn 0.5s ease-in-out;">
                <div class="table-responsive" id="enrollmentsTableWrapper" style="max-height: 650px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="enrollmentsTable">
                        <thead class="border-bottom-2 sticky-top bg-white" style="top: 0; z-index: 10;">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 50px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 180px;">Student</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 160px;">Course</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Category</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 100px;">Level</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 150px;">Progress</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 100px;">Status</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Enrolled Date</th>
                            </tr>
                        </thead>
                        <tbody id="enrollmentsTableBody">
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

<style>
    /* Skeleton Pulse Animation */
    .skeleton-box {
        display: block;
        background-color: #e9ecef;
        border-radius: 4px;
        animation: skeleton-pulse 1.5s infinite ease-in-out;
    }
    @keyframes skeleton-pulse {
        0% { opacity: 0.6; }
        50% { opacity: 1; }
        100% { opacity: 0.6; }
    }

    /* Fade In Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

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
        padding: 0.75rem 0.75rem;
        vertical-align: middle;
    }

    #enrollmentsTableWrapper::-webkit-scrollbar {
        width: 6px;
    }
    #enrollmentsTableWrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    #enrollmentsTableWrapper::-webkit-scrollbar-thumb {
        background: #d1d1d1;
        border-radius: 10px;
    }
    #enrollmentsTableWrapper::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

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
let allEnrollments = [];
let filteredEnrollments = [];

// Load enrollments on page load
document.addEventListener('DOMContentLoaded', function() {
    loadEnrollments();
    setupSearch();
});

// Setup search functionality
function setupSearch() {
    const searchInput = document.getElementById('searchEnrollments');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            filterEnrollments(query);
        });
    }
}

// Filter enrollments based on search query
function filterEnrollments(query) {
    if (!query) {
        filteredEnrollments = allEnrollments;
    } else {
        filteredEnrollments = allEnrollments.filter(enrollment => {
            const username = (enrollment.username || '').toLowerCase();
            const email = (enrollment.email || '').toLowerCase();
            const courseName = (enrollment.course_name || '').toLowerCase();
            const category = (enrollment.category || '').toLowerCase();
            return username.includes(query) ||
                   email.includes(query) ||
                   courseName.includes(query) ||
                   category.includes(query);
        });
    }
    renderEnrollments(filteredEnrollments);
    updateFilteredCount(filteredEnrollments.length);
}

// Update filtered count display
function updateFilteredCount(count) {
    const countElement = document.getElementById('filteredCount');
    const recordCount = document.getElementById('recordCount');
    if (countElement) {
        if (count === 1) {
            countElement.textContent = '1 record found';
        } else {
            countElement.textContent = count + ' records found';
        }
    }
    if (recordCount) {
        recordCount.textContent = `(${allEnrollments.length} total records)`;
    }
}

// Load all enrollments
async function loadEnrollments() {
    const skeleton = document.getElementById('enrollments-skeleton');
    const content = document.getElementById('enrollments-content');
    const recordCount = document.getElementById('recordCount');

    try {
        const response = await fetch('/admin/api/enrollments');
        const data = await response.json();

        if (data.success) {
            allEnrollments = data.data || [];
            filteredEnrollments = allEnrollments;
            renderEnrollments(filteredEnrollments);
            updateFilteredCount(filteredEnrollments.length);
            recordCount.textContent = `(${allEnrollments.length} total records)`;
        } else {
            showToast('Failed to load enrollments', 'danger');
            renderEnrollments([]);
            updateFilteredCount(0);
            recordCount.textContent = '(0 records)';
        }
    } catch (error) {
        console.error('Error loading enrollments:', error);
        showToast('Error loading enrollments', 'danger');
        renderEnrollments([]);
        updateFilteredCount(0);
        recordCount.textContent = '(0 records)';
    } finally {
        // Hide skeleton, show content
        if (skeleton) skeleton.style.display = 'none';
        if (content) content.style.display = 'block';
    }
}

// Render enrollments in table
function renderEnrollments(enrollments) {
    const tbody = document.getElementById('enrollmentsTableBody');
    const scrollIndicator = document.getElementById('scrollIndicator');
    const tableWrapper = document.getElementById('enrollmentsTableWrapper');

    // Show/hide scroll indicator based on record count
    if (enrollments && enrollments.length > 10) {
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

    if (!enrollments || enrollments.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-user-graduate fa-2x mb-3 d-block opacity-50"></i>
                        <p class="mb-0 fw-bold">${allEnrollments.length > 0 ? 'No matching enrollments found' : 'No enrollments found'}</p>
                        <small>${allEnrollments.length > 0 ? 'Try a different search term' : 'Students will appear here when they enroll in courses'}</small>
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
