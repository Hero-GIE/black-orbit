@extends('layouts.admin')

@section('title', 'Cosmic Word Search Progress')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-search me-2"></i>Cosmic Word Search Progress</h4>
        <button class="btn btn-outline-dark btn-sm" onclick="window.location.href='{{ route('admin.dashboard') }}'">
            <i class="fas fa-arrow-left me-1"></i>Back
        </button>
    </div>

    <!-- Skeleton Loading -->
    <div id="cosmic-skeleton">
        <!-- Stats Skeleton -->
        <div class="row g-3 mb-4">
            @for($i = 0; $i < 4; $i++)
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center py-4">
                            <div class="skeleton-box" style="width: 80px; height: 12px; margin: 0 auto 12px;"></div>
                            <div class="skeleton-box" style="width: 60px; height: 30px; margin: 0 auto;"></div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>

        <!-- Progress Bar Skeleton -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="skeleton-box" style="width: 120px; height: 16px;"></div>
                    <div class="skeleton-box" style="width: 40px; height: 16px;"></div>
                </div>
                <div class="skeleton-box" style="width: 100%; height: 12px; border-radius: 10px;"></div>
            </div>
        </div>

        <!-- Table Skeleton -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="skeleton-box" style="width: 40px; height: 16px;"></th>
                                <th class="skeleton-box" style="width: 150px; height: 16px;"></th>
                                <th class="skeleton-box" style="width: 60px; height: 16px;"></th>
                                <th class="skeleton-box" style="width: 80px; height: 16px;"></th>
                                <th class="skeleton-box" style="width: 60px; height: 16px;"></th>
                                <th class="skeleton-box" style="width: 100px; height: 16px;"></th>
                                <th class="skeleton-box" style="width: 60px; height: 16px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 5; $i++)
                                <tr>
                                    <td><div class="skeleton-box" style="width: 30px; height: 20px;"></div></td>
                                    <td>
                                        <div class="skeleton-box" style="width: 120px; height: 20px; margin-bottom: 4px;"></div>
                                        <div class="skeleton-box" style="width: 150px; height: 14px;"></div>
                                    </td>
                                    <td><div class="skeleton-box" style="width: 40px; height: 24px; margin: 0 auto;"></div></td>
                                    <td><div class="skeleton-box" style="width: 40px; height: 24px; margin: 0 auto;"></div></td>
                                    <td><div class="skeleton-box" style="width: 50px; height: 24px; margin: 0 auto;"></div></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="skeleton-box" style="width: 80px; height: 6px; border-radius: 10px;"></div>
                                            <div class="skeleton-box" style="width: 30px; height: 14px;"></div>
                                        </div>
                                    </td>
                                    <td><div class="skeleton-box" style="width: 32px; height: 32px; border-radius: 8px; margin-left: auto;"></div></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Real Content (hidden initially) -->
    <div id="cosmic-content" style="display: none; animation: fadeIn 0.5s ease-in-out;">
        <!-- Progress Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total Users</h6>
                        <h3 class="mb-0 fw-bold" id="totalUsers">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total Stars</h6>
                        <h3 class="mb-0 fw-bold" id="totalStars">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Completion</h6>
                        <h3 class="mb-0 fw-bold" id="completionPercentage">0%</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">Levels Completed</h6>
                        <h3 class="mb-0 fw-bold" id="completedLevels">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small">Overall Progress</span>
                    <span class="fw-bold" id="progressPercent">0%</span>
                </div>
                <div class="progress" style="height: 12px;">
                    <div class="progress-bar bg-dark" role="progressbar" style="width: 0%;" id="progressBar"></div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="border-bottom-2">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">User</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Levels</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Completed</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Stars</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Progress</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="progressTableBody">
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <div class="spinner-border text-dark mb-3" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mb-0">Loading progress data...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- User Progress Detail Modal --}}
<div class="modal fade" id="userProgressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-user me-2"></i>
                    <span id="detailUsername">User Progress</span>
                </h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="bg-light p-3 rounded-3 mb-3">
                    <div class="row text-center">
                        <div class="col-4">
                            <small class="text-muted d-block">Levels</small>
                            <span class="fw-bold" id="detailTotalLevels">0</span>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Completed</small>
                            <span class="fw-bold text-success" id="detailCompletedLevels">0</span>
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Stars</small>
                            <span class="fw-bold text-warning" id="detailTotalStars">0</span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="border-bottom-2">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Level</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Stars</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Best Time</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Played</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Status</th>
                            </tr>
                        </thead>
                        <tbody id="detailLevelsBody">
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">No levels data available</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
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

    .star-icon {
        color: #ffc107;
        margin: 0 1px;
    }
    .star-icon.empty {
        color: #e9ecef;
    }
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-badge.completed {
        background: #d4edda;
        color: #155724;
    }
    .status-badge.in-progress {
        background: #fff3cd;
        color: #856404;
    }
    .status-badge.not-started {
        background: #f8f9fa;
        color: #6c757d;
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
    .table > :not(caption) > * > * {
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }
    .progress-mini {
        height: 6px;
        border-radius: 10px;
    }
</style>
@endsection

@push('scripts')
<script>
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

let isCosmicVisible = false;

function revealCosmic() {
    if (isCosmicVisible) return;
    isCosmicVisible = true;
    const skeleton = document.getElementById('cosmic-skeleton');
    const content = document.getElementById('cosmic-content');
    if (skeleton) skeleton.style.display = 'none';
    if (content) content.style.display = 'block';
}

// Load progress data on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set a timeout to reveal content even if API is slow
    const skeletonTimeout = setTimeout(revealCosmic, 3000);

    loadProgressData()
        .then(() => {
            clearTimeout(skeletonTimeout);
            revealCosmic();
        })
        .catch((err) => {
            console.error('Failed to load progress data:', err);
            clearTimeout(skeletonTimeout);
            revealCosmic();
        });
});

// Load all progress data
async function loadProgressData() {
    try {
        const response = await fetch('/admin/api/cosmic/progress');
        const data = await response.json();

        if (data.success) {
            updateStats(data.data.stats);
            renderUsers(data.data.users);
        } else {
            showToast('Failed to load progress data', 'danger');
        }
    } catch (error) {
        console.error('Error loading progress:', error);
        showToast('Error loading progress data', 'danger');
        throw error;
    }
}

// Update stats
function updateStats(stats) {
    document.getElementById('totalUsers').textContent = stats.total_users || 0;
    document.getElementById('totalStars').textContent = stats.total_stars || 0;
    document.getElementById('completionPercentage').textContent = `${stats.completion_percentage || 0}%`;
    document.getElementById('completedLevels').textContent = stats.total_completed_levels || 0;

    const progress = stats.completion_percentage || 0;
    document.getElementById('progressPercent').textContent = `${progress}%`;
    document.getElementById('progressBar').style.width = `${progress}%`;
}

// Render users
function renderUsers(users) {
    const tbody = document.getElementById('progressTableBody');

    if (!users || users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-search fa-2x mb-3 d-block opacity-50"></i>
                        <p class="mb-0 fw-bold">No progress data found</p>
                        <small>Users will appear here when they start playing</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    users.forEach((user, index) => {
        const progress = user.totalLevels > 0 ? Math.round((user.completedLevels / user.totalLevels) * 100) : 0;
        const status = progress === 100 ? 'completed' : progress > 0 ? 'in-progress' : 'not-started';
        const statusLabels = {
            'completed': '✅ Completed',
            'in-progress': '🔄 In Progress',
            'not-started': '⏳ Not Started'
        };

        html += `
            <tr>
                <td class="text-center text-muted">${index + 1}</td>
                <td>
                    <div>
                        <div class="fw-bold text-dark">${escapeHtml(user.username)}</div>
                        <div class="text-muted small">${escapeHtml(user.email)}</div>
                    </div>
                </td>
                <td class="text-center">
                    <span class="badge bg-secondary">${user.totalLevels || 0}</span>
                </td>
                <td class="text-center">
                    <span class="badge bg-success">${user.completedLevels || 0}</span>
                </td>
                <td class="text-center">
                    <span class="fw-bold text-warning">${user.totalStars || 0}</span>
                    <span class="text-muted">⭐</span>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress progress-mini flex-grow-1" style="width: 80px;">
                            <div class="progress-bar bg-dark" role="progressbar"
                                 style="width: ${progress}%;"></div>
                        </div>
                        <span class="text-muted small">${progress}%</span>
                    </div>
                </td>
                <td class="text-end">
                    <button class="action-btn" onclick="viewUserProgress('${user.user_id}')" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

async function viewUserProgress(userId) {
    document.getElementById('detailUsername').textContent = 'Loading...';
    document.getElementById('detailTotalLevels').textContent = '0';
    document.getElementById('detailCompletedLevels').textContent = '0';
    document.getElementById('detailTotalStars').textContent = '0';

    document.getElementById('detailLevelsBody').innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-5">
                <div class="spinner-border text-dark" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0 text-muted">Fetching levels...</p>
            </td>
        </tr>
    `;

    const modal = new bootstrap.Modal(document.getElementById('userProgressModal'));
    modal.show();

    try {
        const response = await fetch(`/admin/api/cosmic/progress/${userId}`);
        const data = await response.json();

        if (data.success) {
            const user = data.data;

            document.getElementById('detailUsername').textContent = user.username;
            document.getElementById('detailTotalLevels').textContent = user.totalLevels || 0;
            document.getElementById('detailCompletedLevels').textContent = user.completedLevels || 0;
            document.getElementById('detailTotalStars').textContent = user.totalStars || 0;

            const levelsBody = document.getElementById('detailLevelsBody');

            if (!user.levels || user.levels.length === 0) {
                levelsBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-3 text-muted">No levels data available</td>
                    </tr>
                `;
            } else {
                let html = '';
                user.levels.forEach(level => {
                    const starsHtml = renderStars(level.stars || 0);
                    const status = level.completed ? 'completed' : 'not-started';
                    const timeDisplay = level.bestTimeSeconds ?
                        `${Math.floor(level.bestTimeSeconds / 60)}:${String(level.bestTimeSeconds % 60).padStart(2, '0')}` :
                        '--:--';

                    html += `
                        <tr>
                            <td class="fw-bold">Level ${level.levelId}</td>
                            <td class="text-center">${starsHtml}</td>
                            <td class="text-center">${timeDisplay}</td>
                            <td class="text-center">${level.timesPlayed || 0}</td>
                            <td class="text-center">
                                <span class="status-badge ${status}">
                                    ${status === 'completed' ? '✅ Completed' : '⏳ Not Started'}
                                </span>
                            </td>
                        </tr>
                    `;
                });
                levelsBody.innerHTML = html;
            }
        } else {
            document.getElementById('detailUsername').textContent = 'Error';
            document.getElementById('detailLevelsBody').innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-3 text-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>Failed to load user progress.
                    </td>
                </tr>
            `;
            showToast('Failed to load user progress', 'danger');
        }
    } catch (error) {
        console.error('Error loading user progress:', error);
        document.getElementById('detailUsername').textContent = 'Error';
        document.getElementById('detailLevelsBody').innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-3 text-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>Error loading user progress.
                </td>
            </tr>
        `;
        showToast('Error loading user progress', 'danger');
    }
}

function renderStars(count) {
    let html = '';
    for (let i = 1; i <= 3; i++) {
        html += `<span class="star-icon ${i <= count ? '' : 'empty'}">★</span>`;
    }
    return html;
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

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush
