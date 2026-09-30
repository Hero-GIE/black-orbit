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
        @for($i = 0; $i < 8; $i++)
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="skeleton-box w-100" style="height: 350px; border-radius: 10px;"></div>
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
                <label class="form-label text-muted small fw-bold">Video File / URL *</label>
                <div id="videoPreviewWrapper" class="mb-2" style="display: none;">
                    <video id="videoPreview" controls class="w-100 rounded border bg-light" style="max-height: 220px;"></video>
                </div>

                <input type="url" id="videoUrl" name="videourl" class="form-control form-control-custom mb-2" placeholder="https://res.cloudinary.com/...">
                <small class="text-muted d-block mb-2">Paste a video URL above, OR upload a file below:</small>

                <input type="file" id="videoFile" accept="video/mp4,video/quicktime,video/x-msvideo,video/webm" class="form-control form-control-custom">
                <small class="text-muted d-block mt-1">MP4, MOV, AVI, WEBM — max 512 MB</small>

                <div id="videoUploadProgressWrap" class="mt-2" style="display:none;">
                    <div class="progress" style="height: 8px; border-radius: 6px;">
                        <!-- Added transition: width 0.3s ease; for smooth sliding -->
                        <div id="videoUploadProgressBar" class="progress-bar bg-dark" role="progressbar" style="width: 0%; transition: width 0.3s ease;"></div>
                    </div>
                </div>

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

{{-- Video Player Modal --}}
<div class="modal fade" id="videoPlayerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark border-0 rounded-3 overflow-hidden">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white fw-bold" id="videoPlayerTitle">Now Playing</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-black">
                <video id="modalVideoPlayer" controls autoplay class="w-100" style="max-height: 70vh; background: #000;"></video>
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
        border-radius: 10px;
        overflow: hidden;
        height: 350px;
        background: #000;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }
    .video-card:hover {
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
    .video-card:hover .card-img {
        transform: scale(1.08);
    }

    .play-icon-overlay {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        z-index: 3;
        width: 60px; height: 60px;
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.8);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        backdrop-filter: blur(4px);
        transition: all 0.3s ease;
        pointer-events: none;
    }
    .video-card:hover .play-icon-overlay {
        background: rgba(255, 255, 255, 0.4);
        transform: translate(-50%, -50%) scale(1.1);
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
    .video-card:hover .card-actions-overlay {
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
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 1.25rem;
        padding-top: 3rem;
        z-index: 2;
        background: linear-gradient(0deg, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.3) 40%, rgba(0,0,0,0.1) 100%);
        color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        min-width: 0;
        transition: opacity 0.3s ease;
    }

    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        min-height: 1.4rem;
        max-width: 100%;
        overflow-wrap: anywhere;
    }

    .text-truncate-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        overflow-wrap: anywhere;
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
        background: rgba(255, 255, 255, 0.92);
        color: #212529;
        margin-bottom: 0.5rem;
        width: fit-content;
    }

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

// ── Video preview elements ──
const videoFileEl = document.getElementById('videoFile');
const videoUrlEl = document.getElementById('videoUrl');
const videoPreviewEl = document.getElementById('videoPreview');
const videoPreviewWrapper = document.getElementById('videoPreviewWrapper');
const videoUploadStatus = document.getElementById('videoUploadStatus');
const videoUploadProgressWrap = document.getElementById('videoUploadProgressWrap');
const videoUploadProgressBar = document.getElementById('videoUploadProgressBar');

function showVideoPreview(src) {
    if (!src) {
        videoPreviewWrapper.style.display = 'none';
        videoPreviewEl.removeAttribute('src');
        return;
    }
    videoPreviewEl.src = src;
    videoPreviewWrapper.style.display = 'block';
}

videoFileEl.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

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

    videoUrlEl.value = '';
    const url = URL.createObjectURL(file);
    showVideoPreview(url);
});

videoUrlEl.addEventListener('input', function() {
    if (this.value) {
        videoFileEl.value = '';
        showVideoPreview(this.value);
    }
});

// ── Page init ──
document.addEventListener('DOMContentLoaded', function () {
    loadCourses();
    loadLessons(); // <--- ADD THIS LINE
    loadVideos(true);
    setupFilters();

    // Stop video playback when the View drawer is closed
    const viewVideoModalEl = document.getElementById('viewVideoModal');
    if (viewVideoModalEl) {
        viewVideoModalEl.addEventListener('hidden.bs.offcanvas', function () {
            const viewContent = document.getElementById('viewVideoContent');
            if (viewContent) {
                viewContent.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-dark" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
            }
        });
    }

    // Stop video playback when the Player Modal is closed
    const videoPlayerModalEl = document.getElementById('videoPlayerModal');
    if (videoPlayerModalEl) {
        videoPlayerModalEl.addEventListener('hidden.bs.modal', function () {
            const playerEl = document.getElementById('modalVideoPlayer');
            if (playerEl) {
                playerEl.pause();
                playerEl.removeAttribute('src');
                playerEl.load();
            }
        });
    }
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

async function loadCourses() {
    try {
        const response = await fetch('/admin/api/videos/courses', {
            headers: { 'Accept': 'application/json' }
        });
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

async function loadLessons() {
    try {
        const response = await fetch('/admin/api/videos/lessons', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success && Array.isArray(data.data)) {
            const select = document.getElementById('lessonFilter');
            const current = select.value;
            select.innerHTML = '<option value="">All Lessons</option>' +
                data.data.map(l => `<option value="${escapeHtml(l)}">${escapeHtml(l)}</option>`).join('');
            select.value = current;
        }
    } catch (err) {
        console.error('Failed to load lessons', err);
    }
}

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

        const response = await fetch('/admin/api/videos?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        });
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

// Helper: Generate a thumbnail image URL from a Cloudinary video URL
function getVideoThumb(url) {
    if (!url) return null;
    if (url.includes('res.cloudinary.com') && url.includes('/video/upload/')) {
        let thumbUrl = url.replace('/video/upload/', '/video/upload/f_jpg,so_2,w_600,q_auto/');
        return thumbUrl.replace(/\.(mp4|mov|avi|webm|m4v)$/i, '.jpg');
    }
    return null;
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
        const chip = (v.courseid || v.lessonid)
            ? `<span class="video-cat-chip" title="${escapeHtml(v.courseid || 'N/A')} / ${escapeHtml(v.lessonid || 'N/A')}">
                 <i class="fas fa-book"></i>${escapeHtml(v.courseid || 'N/A')} <span class="text-muted">/</span> ${escapeHtml(v.lessonid || 'N/A')}
               </span>`
            : '';

        const posterUrl = getVideoThumb(v.videourl);
        let thumb = '';
        if (v.videourl) {
            const preloadVal = posterUrl ? 'none' : 'metadata';
            thumb = `<video src="${escapeHtml(v.videourl)}" preload="${preloadVal}" poster="${escapeHtml(posterUrl || '')}" class="card-img" muted playsinline></video>`;
        } else {
            thumb = `<div class="card-img d-flex align-items-center justify-content-center"><i class="fas fa-video-slash fa-2x text-muted"></i></div>`;
        }

        const safeUrl = v.videourl ? escapeHtml(v.videourl) : '';
        const safeTitle = escapeHtml(v.title || '(untitled)');

        html += `
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="video-card" onclick="playVideoInModal('${safeUrl}', '${safeTitle}')">
                    ${thumb}

                    <div class="play-icon-overlay">
                        <i class="fas fa-play" style="font-size: 1.25rem; margin-left: 4px;"></i>
                    </div>

                    <div class="card-actions-overlay">
                        <button class="card-action-btn" onclick="event.stopPropagation(); viewVideo('${v.id}')" title="View Details"><i class="fas fa-eye"></i></button>
                        <button class="card-action-btn" onclick="event.stopPropagation(); editVideo('${v.id}')" title="Edit"><i class="fas fa-edit"></i></button>
                        <button class="card-action-btn delete" onclick="event.stopPropagation(); confirmDelete('${v.id}')" title="Delete"><i class="fas fa-trash"></i></button>
                    </div>

                    <div class="card-overlay-content">
                        ${chip}
                        <h5 class="fw-bold mb-1 text-white text-truncate-1">${safeTitle}</h5>
                        ${v.description ? `<p class="card-text small text-white text-truncate-2 mb-0">${escapeHtml(v.description)}</p>` : ''}
                    </div>
                </div>
            </div>
        `;
    });

    grid.innerHTML = html;
}

function playVideoInModal(url, title) {
    if (!url) {
        showToast('No video source available.', 'danger');
        return;
    }

    const modalEl = document.getElementById('videoPlayerModal');
    const videoEl = document.getElementById('modalVideoPlayer');
    const titleEl = document.getElementById('videoPlayerTitle');

    titleEl.textContent = title || 'Now Playing';
    videoEl.src = url;

    const playerModal = new bootstrap.Modal(modalEl);
    playerModal.show();

    videoEl.play().catch(err => console.error("Error playing video:", err));
}

function openAddModal() {
    editingVideoId = null;
    document.getElementById('videoModalTitle').textContent = 'Upload Video';
    document.getElementById('videoForm').reset();
    document.getElementById('videoId').value = '';
    document.getElementById('saveVideoBtn').textContent = 'Upload Video';

    videoUrlEl.value = '';
    videoFileEl.value = '';
    showVideoPreview('');
    videoUploadStatus.textContent = '';
    videoUploadProgressWrap.style.display = 'none';
    videoUploadProgressBar.style.width = '0%';

    const drawer = new bootstrap.Offcanvas(document.getElementById('videoModal'));
    drawer.show();
}

async function editVideo(id) {
    try {
        const response = await fetch(`/admin/api/videos/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
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

            videoUrlEl.value = v.videourl || '';
            videoFileEl.value = '';
            showVideoPreview(v.videourl || '');

            videoUploadStatus.textContent = '';
            videoUploadProgressWrap.style.display = 'none';
            videoUploadProgressBar.style.width = '0%';
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
        const response = await fetch(`/admin/api/videos/${id}`, {
            headers: { 'Accept': 'application/json' }
        });
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
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
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

// ── Upload / Save (XHR-based so we get real upload progress) ──
document.getElementById('saveVideoBtn').addEventListener('click', function () {
    const id = document.getElementById('videoId').value;
    const file = videoFileEl.files?.[0];
    const url = videoUrlEl.value.trim();

    if (!id && !file && !url) {
        showToast('Please provide a video file or URL', 'danger');
        return;
    }

    const fd = new FormData();
    fd.append('videoId', id);
    fd.append('courseid', document.getElementById('courseid').value);
    fd.append('lessonid', document.getElementById('lessonid').value);
    fd.append('title', document.getElementById('title').value);
    fd.append('description', document.getElementById('description').value);

    if (url) fd.append('videourl', url);

    document.getElementById('prerequisites').value
        .split(',').map(s => s.trim()).filter(Boolean)
        .forEach(p => fd.append('prerequisites[]', p));

    document.getElementById('resources').value
        .split(',').map(s => s.trim()).filter(Boolean)
        .forEach(r => fd.append('resources[]', r));

    if (file) fd.append('video', file);

    const btn = this;
    const originalLabel = id ? 'Save Changes' : 'Upload Video';

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Starting...';

    // --- SMOOTH PROGRESS BAR LOGIC ---
    videoUploadProgressWrap.style.display = 'block';
    videoUploadProgressBar.style.width = '0%';
    videoUploadStatus.textContent = 'Preparing upload…';

    let currentProgress = 0;

    // Fallback interval to ensure smooth visual progress in 1% increments
    // This handles cases where the browser doesn't emit progress events fast enough
    const progressInterval = setInterval(() => {
        if (currentProgress < 90) {
            currentProgress += 1; // Move by 1% for smooth sliding
            if (currentProgress > 90) currentProgress = 90;

            videoUploadProgressBar.style.width = currentProgress + '%';
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${currentProgress}%`;
            videoUploadStatus.textContent = `Uploading… ${currentProgress}%`;
        } else if (currentProgress === 90) {
            videoUploadStatus.textContent = 'Finalizing on server, please wait…';
        }
    }, 150); // Tick every 150ms
    // --------------------------

    const xhr = new XMLHttpRequest();
    xhr.timeout = 20 * 60 * 1000; // 20 minutes

    xhr.upload.addEventListener('progress', function (evt) {
        if (evt.lengthComputable && file) {
            // Cap real progress at 90% to reserve 10% for server-side processing
            let rawPct = Math.round((evt.loaded / evt.total) * 90);

            // Only update if the real progress is ahead of our fake interval
            if (rawPct > currentProgress) {
                currentProgress = rawPct;
                videoUploadProgressBar.style.width = currentProgress + '%';
                btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${currentProgress}%`;
                videoUploadStatus.textContent = `Uploading… ${currentProgress}%`;
            }
        }
    });

    xhr.addEventListener('load', function () {
        // Stop the interval from ticking up
        clearInterval(progressInterval);

        let data;
        try {
            data = JSON.parse(xhr.responseText);
        } catch (e) {
            console.error('Non-JSON response (status ' + xhr.status + '):', xhr.responseText.slice(0, 500));
            showToast('Server error. Check server logs.', 'danger');
            resetUploadUI();
            return;
        }

        // Catch server-level rejection (like 413 Content Too Large)
        if (xhr.status === 413) {
            showToast('The video is too large for the server to accept. Please compress it or upload a smaller file.', 'danger');
            resetUploadUI();
            return;
        }

        // Handle 422 Validation Errors specifically
        if (xhr.status === 422) {
            if (data.errors) {
                const firstError = Object.values(data.errors)[0][0];
                showToast(firstError, 'danger');
            } else {
                showToast(data.message || 'Validation failed. Please check your inputs.', 'danger');
            }
            resetUploadUI();
            return;
        }

        if (xhr.status >= 400) {
            showToast('Upload failed. Server returned an error (Status: ' + xhr.status + ').', 'danger');
            resetUploadUI();
            return;
        }

        if (data.success) {
            // Complete the progress bar visually
            videoUploadProgressBar.style.width = '100%';
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>100%`;
            videoUploadStatus.textContent = 'Upload complete!';

            showToast(data.message, 'success');

            // Small delay so the user sees 100% before the drawer closes
            setTimeout(() => {
                const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('videoModal'));
                drawer.hide();
                resetUploadUI();
                loadCourses();
                loadVideos(true);
            }, 800);
        } else if (data.errors) {
            const firstError = Object.values(data.errors)[0][0];
            showToast(firstError, 'danger');
            resetUploadUI();
        } else {
            showToast(data.message || 'Failed to save', 'danger');
            resetUploadUI();
        }
    });

    function resetUploadUI() {
        btn.disabled = false;
        btn.innerHTML = originalLabel;
        videoUploadProgressWrap.style.display = 'none';
        videoUploadStatus.textContent = '';
        videoUploadProgressBar.style.width = '0%';
    }

    xhr.addEventListener('error', function () {
        clearInterval(progressInterval);
        showToast('Network error during upload', 'danger');
        resetUploadUI();
    });

    xhr.addEventListener('timeout', function () {
        clearInterval(progressInterval);
        showToast('Upload timed out — file may be too large or connection too slow', 'danger');
        resetUploadUI();
    });

    xhr.open('POST', '/admin/api/videos/upload');
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]')?.content || '');
    xhr.send(fd);
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
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
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
