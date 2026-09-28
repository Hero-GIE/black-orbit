@extends('layouts.admin')

@section('title', 'Video Management')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-video me-2"></i>Video Management</h4>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark btn-sm d-flex align-items-center" onclick="openAddModal()">
                <i class="fas fa-plus me-2"></i>Upload Video
            </button>
            <button class="btn btn-outline-dark btn-sm d-flex align-items-center" onclick="window.location.href='{{ route('admin.dashboard') }}'">
                <i class="fas fa-arrow-left me-2"></i>Back
            </button>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="row mb-4">
        <div class="col-md-4 col-lg-3">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text"
                       class="form-control border-start-0"
                       id="searchVideos"
                       placeholder="Search by title, course, or lesson..."
                       style="border-left: none; border-radius: 0 10px 10px 0;">
            </div>
        </div>
        <div class="col-md-3 col-lg-2">
            <select id="courseFilter" class="form-select form-control-custom" style="border-radius: 10px;">
                <option value="">All Courses</option>
            </select>
        </div>
        <div class="col-md-3 col-lg-2">
            <select id="lessonFilter" class="form-select form-control-custom" style="border-radius: 10px;">
                <option value="">All Lessons</option>
            </select>
        </div>
        <div class="col-md-2 col-lg-5 text-md-end">
            <span class="text-muted small" id="recordCount">Loading...</span>
        </div>
    </div>

    <!-- SKELETON LOADING -->
    <div id="videos-skeleton" class="row g-4">
        @for($i = 0; $i < 6; $i++)
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="skeleton-box w-100" style="height: 320px; border-radius: 12px;"></div>
            </div>
        @endfor
    </div>

    <!-- ACTUAL CONTENT -->
    <div id="videos-content" class="row g-4" style="display: none; animation: fadeIn 0.5s ease-in-out;">
        <!-- Cards will be injected here via JS -->
    </div>

    <!-- LOAD MORE -->
    <div class="text-center mt-4">
        <button id="loadMoreBtn" class="btn btn-outline-dark d-none align-items-center gap-2" onclick="loadVideos(false)">
            <i class="fas fa-plus"></i>
            <span>Load more</span>
        </button>
        <div id="loadingMoreSpinner" class="text-muted small mt-2 d-none">
            <span class="spinner-border spinner-border-sm me-1"></span> Loading…
        </div>
    </div>
</div>

{{-- Upload / Edit Video Drawer --}}
<div class="offcanvas offcanvas-end video-drawer" tabindex="-1" id="videoModal" aria-labelledby="videoModalTitle">
    <div class="offcanvas-header border-0 pb-0">
        <h5 class="offcanvas-title fw-bold" id="videoModalTitle">Upload Video</h5>
        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body pt-2">
        <form id="videoForm">
            <input type="hidden" id="videoId" name="videoId">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="courseid" class="form-label text-muted small fw-bold">Course ID *</label>
                    <input type="text" class="form-control form-control-custom" id="courseid" name="courseid" placeholder="course_ent_101" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="lessonid" class="form-label text-muted small fw-bold">Lesson ID *</label>
                    <input type="text" class="form-control form-control-custom" id="lessonid" name="lessonid" placeholder="lesson_001" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="title" class="form-label text-muted small fw-bold">Title *</label>
                <input type="text" class="form-control form-control-custom" id="title" name="title" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label text-muted small fw-bold">Description</label>
                <textarea class="form-control form-control-custom" id="description" name="description" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label for="video" class="form-label text-muted small fw-bold">Video File *</label>
                <div id="videoPreviewWrapper" class="mb-2" style="display: none;">
                    <video id="videoPreview" controls class="w-100 rounded border bg-light" style="max-height: 220px;"></video>
                </div>
                <input type="file" id="videoFile" accept="video/mp4,video/quicktime,video/x-msvideo,video/webm" class="form-control form-control-custom">
                <small class="text-muted d-block mt-1">MP4, MOV, AVI, WEBM — max 512 MB</small>
                <div id="videoUploadStatus" class="small text-muted mt-1"></div>
            </div>

            <div class="mb-3">
                <label for="prerequisites" class="form-label text-muted small fw-bold">Prerequisites (comma-separated lesson IDs)</label>
                <input type="text" class="form-control form-control-custom" id="prerequisites" name="prerequisites" placeholder="lesson_000, lesson_002">
            </div>

            <div class="mb-3">
                <label for="resources" class="form-label text-muted small fw-bold">Resources (comma-separated URLs)</label>
                <textarea class="form-control form-control-custom" id="resources" name="resources" rows="2" placeholder="https://.../slides.pdf, https://.../worksheet.pdf"></textarea>
            </div>
        </form>
    </div>
    <div class="offcanvas-footer border-0 pt-0 px-4 pb-4">
        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="button" class="btn btn-dark px-4" id="saveVideoBtn">Upload Video</button>
        </div>
    </div>
</div>

{{-- View Video Drawer --}}
<div class="offcanvas offcanvas-end video-drawer" tabindex="-1" id="viewVideoModal" aria-labelledby="viewVideoTitle">
    <div class="offcanvas-header border-0 pb-0">
        <h5 class="offcanvas-title fw-bold" id="viewVideoTitle"><i class="fas fa-info-circle me-2 text-dark"></i>Video Details</h5>
        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body pt-2" id="viewVideoContent">
        <div class="text-center py-5">
            <div class="spinner-border text-dark" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
    <div class="offcanvas-footer border-0 pt-0 px-4 pb-4">
        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Close</button>
            <button type="button" class="btn btn-outline-dark px-4" id="replaceFromViewBtn">
                <i class="fas fa-sync me-1"></i> Replace video
            </button>
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
                <h4 class="fw-bold mb-2">Delete Video?</h4>
                <p class="text-muted mb-4">Are you sure you want to delete this video? This action cannot be undone.</p>
                <input type="hidden" id="deleteVideoId">
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

    .video-card {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        background: #f8f9fa;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    .video-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 3rem rgba(0,0,0,0.175) !important;
    }

    .video-thumb {
        position: relative;
        width: 100%;
        padding-top: 56.25%; /* 16:9 */
        background: #000;
        overflow: hidden;
    }
    .video-thumb video, .video-thumb img {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        object-fit: cover;
    }

    .video-body {
        padding: 1rem 1.25rem 1.25rem 1.25rem;
        background: #fff;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        flex: 1;
    }

    .video-cat-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.3rem 0.55rem;
        border-radius: 6px;
        line-height: 1.2;
        background: #f1f3f5;
        color: #212529;
    }

    .video-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1f2937;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        overflow-wrap: anywhere;
        margin: 0;
    }

    .video-desc {
        font-size: 0.8rem;
        color: #6b7280;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        overflow-wrap: anywhere;
        margin: 0;
    }

    .video-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.35rem;
        margin-top: auto;
        padding-top: 0.5rem;
        border-top: 1px solid #f1f3f5;
    }

    .video-action-btn {
        width: 32px; height: 32px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 8px;
        border: none;
        background: #f8f9fa;
        color: #495057;
        transition: all 0.2s ease;
    }
    .video-action-btn:hover { background: #e9ecef; color: #000; }
    .video-action-btn.delete:hover { background: #dc3545; color: #fff; }

    .video-drawer {
        width: 520px !important;
        max-width: 90vw;
        border-left: none !important;
        box-shadow: -8px 0 30px rgba(0,0,0,0.12);
    }
    .video-drawer .offcanvas-header { padding: 1.5rem 1.5rem 0.5rem 1.5rem; }
    .video-drawer .offcanvas-body {
        padding: 0.5rem 1.5rem 1.5rem 1.5rem;
        flex-grow: 1;
        overflow-y: auto;
    }
    .video-drawer .offcanvas-footer {
        background-color: #fff;
        border-top: 1px solid #f1f1f1;
        padding-top: 1rem !important;
    }
</style>
@endsection

@push('scripts')
<script>
let editingVideoId = null;
let viewDrawerInstance = null;
let allVideos = [];
let currentPageToken = null;
let isLoadingMore = false;
let hasMore = false;
let debounceTimer = null;

const PAGE_SIZE = 24;

// ── Video preview on file select ──
const videoFileEl = document.getElementById('videoFile');
const videoPreviewEl = document.getElementById('videoPreview');
const videoPreviewWrapper = document.getElementById('videoPreviewWrapper');
const videoUploadStatus = document.getElementById('videoUploadStatus');

videoFileEl.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) {
        videoPreviewWrapper.style.display = 'none';
        return;
    }
    if (!file.type.startsWith('video/')) {
        showToast('Please choose a video file', 'danger');
        this.value = '';
        return;
    }
    if (file.size > 512 * 1024 * 1024) {
        showToast('Video is too large (max 512 MB)', 'danger');
        this.value = '';
        return;
    }

    const url = URL.createObjectURL(file);
    videoPreviewEl.src = url;
    videoPreviewWrapper.style.display = 'block';
});

// ── Page init ──
document.addEventListener('DOMContentLoaded', function () {
    loadCourses();
    loadVideos(true);
    setupFilters();
});

function setupFilters() {
    const searchInput = document.getElementById('searchVideos');
    const courseFilter = document.getElementById('courseFilter');
    const lessonFilter = document.getElementById('lessonFilter');

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadVideos(true), 350);
        });
    }
    if (courseFilter) courseFilter.addEventListener('change', () => loadVideos(true));
    if (lessonFilter) lessonFilter.addEventListener('change', () => loadVideos(true));
}

// ── Load distinct courses (builds the dropdown) ──
async function loadCourses() {
    try {
        const response = await fetch('/admin/api/videos/courses');
        const data = await response.json();
        if (data.success && Array.isArray(data.data)) {
            const select = document.getElementById('courseFilter');
            const current = select.value;
            select.innerHTML = '<option value="">All Courses</option>' +
                data.data.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
            select.value = current;
        }
    } catch (err) {
        console.error('Failed to load courses', err);
    }
}

// ── Load videos (paginated) ──
async function loadVideos(reset = true) {
    if (isLoadingMore) return;
    if (!reset && !hasMore) return;

    isLoadingMore = true;

    const skeleton = document.getElementById('videos-skeleton');
    const content = document.getElementById('videos-content');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const loadingMoreSpinner = document.getElementById('loadingMoreSpinner');

    if (reset) {
        currentPageToken = null;
        allVideos = [];
        hasMore = true;
        if (skeleton) skeleton.style.display = 'flex';
        if (content) { content.style.display = 'none'; content.innerHTML = ''; }
        if (loadMoreBtn) loadMoreBtn.classList.add('d-none');
    } else {
        if (loadMoreBtn) loadMoreBtn.classList.add('d-none');
        if (loadingMoreSpinner) loadingMoreSpinner.classList.remove('d-none');
    }

    try {
        const params = new URLSearchParams();
        params.set('limit', String(PAGE_SIZE));
        if (currentPageToken) params.set('page_token', currentPageToken);

        const q = document.getElementById('searchVideos')?.value?.trim() || '';
        const course = document.getElementById('courseFilter')?.value || '';
        const lesson = document.getElementById('lessonFilter')?.value || '';
        if (q) params.set('q', q);
        if (course) params.set('courseid', course);
        if (lesson) params.set('lessonid', lesson);

        const response = await fetch('/admin/api/videos?' + params.toString());
        const data = await response.json();

        if (data.success) {
            allVideos = reset ? data.data : [...allVideos, ...data.data];
            currentPageToken = data.nextPageToken || null;
            hasMore = !!currentPageToken;

            renderVideos(allVideos);
            updateCount(allVideos.length);
        } else {
            showToast(data.message || 'Failed to load videos', 'danger');
            if (reset) renderVideos([]);
        }
    } catch (error) {
        console.error('Error loading videos:', error);
        showToast('Error loading videos', 'danger');
        if (reset) renderVideos([]);
    } finally {
        isLoadingMore = false;
        if (skeleton) skeleton.style.display = 'none';
        if (content) content.style.display = 'flex';
        if (loadingMoreSpinner) loadingMoreSpinner.classList.add('d-none');
        if (loadMoreBtn) {
            if (hasMore) loadMoreBtn.classList.remove('d-none');
            else loadMoreBtn.classList.add('d-none');
        }
    }
}

function updateCount(count) {
    const el = document.getElementById('recordCount');
    if (!el) return;
    el.textContent = count === 1 ? '1 video found' : count + ' videos found';
}

function renderVideos(videos) {
    const grid = document.getElementById('videos-content');

    if (!videos || videos.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="text-muted">
                    <i class="fas fa-video fa-2x mb-3 d-block opacity-50"></i>
                    <p class="mb-0 fw-bold">No videos found</p>
                    <small>Try adjusting your search or filter</small>
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    videos.forEach((v) => {
        const chip = v.lessonid
            ? `<span class="video-cat-chip" title="${escapeHtml(v.courseid)} / ${escapeHtml(v.lessonid)}">
                 <i class="fas fa-book"></i>${escapeHtml(v.courseid)} <span class="text-muted">/</span> ${escapeHtml(v.lessonid)}
               </span>`
            : '';

        // Show a real <video> tag with preload=metadata so the browser shows the first frame
        const thumb = v.videourl
            ? `<video src="${escapeHtml(v.videourl)}" preload="metadata" muted playsinline></video>`
            : `<div class="d-flex align-items-center justify-content-center h-100"><i class="fas fa-video-slash fa-2x text-muted"></i></div>`;

        html += `
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="video-card">
                    <div class="video-thumb">${thumb}</div>
                    <div class="video-body">
                        ${chip}
                        <p class="video-title">${escapeHtml(v.title || '(untitled)')}</p>
                        ${v.description ? `<p class="video-desc">${escapeHtml(v.description)}</p>` : ''}
                        <div class="video-actions">
                            <button class="video-action-btn" onclick="viewVideo('${v.id}')" title="View"><i class="fas fa-eye"></i></button>
                            <button class="video-action-btn" onclick="editVideo('${v.id}')" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="video-action-btn delete" onclick="confirmDelete('${v.id}')" title="Delete"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    grid.innerHTML = html;
}

function openAddModal() {
    editingVideoId = null;
    document.getElementById('videoModalTitle').textContent = 'Upload Video';
    document.getElementById('videoForm').reset();
    videoPreviewWrapper.style.display = 'none';
    videoPreviewEl.removeAttribute('src');
    videoUploadStatus.textContent = '';
    document.getElementById('videoId').value = '';
    document.getElementById('saveVideoBtn').textContent = 'Upload Video';
    const drawer = new bootstrap.Offcanvas(document.getElementById('videoModal'));
    drawer.show();
}

async function editVideo(id) {
    try {
        const response = await fetch(`/admin/api/videos/${id}`);
        const data = await response.json();

        if (data.success) {
            const v = data.data;
            editingVideoId = id;

            document.getElementById('videoModalTitle').textContent = 'Edit Video';
            document.getElementById('videoId').value = id;
            document.getElementById('courseid').value = v.courseid || '';
            document.getElementById('lessonid').value = v.lessonid || '';
            document.getElementById('title').value = v.title || '';
            document.getElementById('description').value = v.description || '';
            document.getElementById('prerequisites').value = (v.prerequisites || []).join(', ');
            document.getElementById('resources').value = (v.resources || []).join(', ');
            videoPreviewWrapper.style.display = 'none';
            videoPreviewEl.removeAttribute('src');
            videoUploadStatus.textContent = '';
            document.getElementById('saveVideoBtn').textContent = 'Save Changes';

            if (viewDrawerInstance) viewDrawerInstance.hide();
            const drawer = new bootstrap.Offcanvas(document.getElementById('videoModal'));
            drawer.show();
        } else {
            showToast('Failed to load video', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error loading video', 'danger');
    }
}

async function viewVideo(id) {
    const content = document.getElementById('viewVideoContent');
    content.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-dark" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
    viewDrawerInstance = new bootstrap.Offcanvas(document.getElementById('viewVideoModal'));
    viewDrawerInstance.show();

    try {
        const response = await fetch(`/admin/api/videos/${id}`);
        const data = await response.json();

        if (data.success) {
            const v = data.data;
            content.innerHTML = `
                <div class="mb-4">
                    ${v.videourl ? `<video src="${escapeHtml(v.videourl)}" controls class="w-100 mb-3 shadow-sm" style="max-height: 280px; border-radius: 12px; background: #000;"></video>` : `<div class="d-flex align-items-center justify-content-center bg-light mb-3 rounded" style="height: 220px;"><i class="fas fa-video-slash fa-2x text-muted"></i></div>`}
                    <div class="text-center">
                        <h4 class="fw-bold mb-1">${escapeHtml(v.title || '(untitled)')}</h4>
                        ${v.courseid || v.lessonid ? `<p class="text-muted mb-1"><i class="fas fa-book me-2"></i>${escapeHtml(v.courseid)} <span class="text-muted">/</span> ${escapeHtml(v.lessonid)}</p>` : ''}
                    </div>
                </div>

                ${v.description ? `
                <div class="bg-white border-start border-4 border-dark p-3 rounded-3 mb-3 shadow-sm">
                    <small class="text-muted d-block text-uppercase mb-2 fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Description</small>
                    <p class="mb-0 text-dark fw-medium" style="font-size: 0.95rem; line-height: 1.6;">${escapeHtml(v.description)}</p>
                </div>` : ''}

                ${v.prerequisites && v.prerequisites.length ? `
                <div class="bg-light p-3 rounded-3 mb-3">
                    <small class="text-muted d-block text-uppercase mb-2 fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Prerequisites</small>
                    <div class="d-flex flex-wrap gap-1">
                        ${v.prerequisites.map(p => `<span class="badge bg-dark">${escapeHtml(p)}</span>`).join('')}
                    </div>
                </div>` : ''}

                ${v.resources && v.resources.length ? `
                <div class="bg-light p-3 rounded-3 mb-3">
                    <small class="text-muted d-block text-uppercase mb-2 fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Resources</small>
                    <ul class="mb-0 ps-3">
                        ${v.resources.map(r => `<li><a href="${escapeHtml(r)}" target="_blank" rel="noopener" class="text-decoration-none">${escapeHtml(r)}</a></li>`).join('')}
                    </ul>
                </div>` : ''}
            `;
            document.getElementById('replaceFromViewBtn').onclick = () => replaceVideo(id);
        } else {
            content.innerHTML = `<div class="text-center text-danger py-5"><i class="fas fa-exclamation-circle fa-2x mb-2"></i><p>Failed to load video details.</p></div>`;
        }
    } catch (error) {
        content.innerHTML = `<div class="text-center text-danger py-5"><i class="fas fa-exclamation-circle fa-2x mb-2"></i><p>Error loading video details.</p></div>`;
    }
}

async function replaceVideo(id) {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'video/mp4,video/quicktime,video/x-msvideo,video/webm';
    input.onchange = async () => {
        const file = input.files?.[0];
        if (!file) return;

        const fd = new FormData();
        fd.append('video', file);

        showToast('Uploading replacement…', 'info');

        try {
            const res = await fetch(`/admin/api/videos/${id}/replace`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: fd,
            });
            const data = await res.json();
            if (data.success) {
                showToast('Video replaced', 'success');
                if (viewDrawerInstance) viewDrawerInstance.hide();
                loadVideos(true);
            } else {
                showToast(data.message || 'Replace failed', 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('Error replacing video', 'danger');
        }
    };
    input.click();
}

document.getElementById('saveVideoBtn').addEventListener('click', async function () {
    const form = document.getElementById('videoForm');
    const id = document.getElementById('videoId').value;
    const file = videoFileEl.files?.[0];

    if (!id && !file) {
        showToast('Please choose a video file', 'danger');
        return;
    }

    const fd = new FormData();
    fd.append('courseid', document.getElementById('courseid').value);
    fd.append('lessonid', document.getElementById('lessonid').value);
    fd.append('title', document.getElementById('title').value);
    fd.append('description', document.getElementById('description').value);

    const pre = document.getElementById('prerequisites').value
        .split(',').map(s => s.trim()).filter(Boolean);
    pre.forEach(p => fd.append('prerequisites[]', p));

    const res = document.getElementById('resources').value
        .split(',').map(s => s.trim()).filter(Boolean);
    res.forEach(r => fd.append('resources[]', r));

    if (file) fd.append('video', file);

    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';

        let url = '/admin/api/videos/upload';
        let method = 'POST';

        const response = await fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: fd,
        });
        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('videoModal'));
            drawer.hide();
            loadCourses();
            loadVideos(true);
        } else {
            showToast(data.message || 'Failed to save', 'danger');
        }
    } catch (error) {
        console.error(error);
        showToast('Error saving video', 'danger');
    } finally {
        this.disabled = false;
        this.innerHTML = id ? 'Save Changes' : 'Upload Video';
    }
});

function confirmDelete(id) {
    document.getElementById('deleteVideoId').value = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
    const id = document.getElementById('deleteVideoId').value;
    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
        const response = await fetch(`/admin/api/videos/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
        });
        const data = await response.json();
        if (data.success) {
            showToast('Video deleted', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            modal.hide();
            loadCourses();
            loadVideos(true);
        } else {
            showToast('Failed to delete', 'danger');
        }
    } catch (error) {
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
