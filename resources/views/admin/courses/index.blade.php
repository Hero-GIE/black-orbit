@extends('layouts.admin')

@section('title', 'Course Management')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-book me-2"></i>Course Management</h4>
            {{-- <small class="text-muted" id="recordCount">Loading...</small> --}}
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark btn-sm d-flex align-items-center" onclick="openAddModal()">
                <i class="fas fa-plus me-2"></i>Add Course
            </button>
            <button class="btn btn-outline-dark btn-sm d-flex align-items-center" onclick="window.location.href='{{ route('admin.dashboard') }}'">
                <i class="fas fa-arrow-left me-2"></i>Back
            </button>
        </div>
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
                       id="searchCourses"
                       placeholder="Search by course name or category..."
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
            <div id="courses-skeleton">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="border-bottom-2">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Course</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Category</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Level</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Students</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Lessons</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Certificate</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 6; $i++)
                                <tr>
                                    <td class="text-center">
                                        <div class="skeleton-box mx-auto" style="width: 20px; height: 16px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="skeleton-box" style="width: 50px; height: 50px; border-radius: 8px;"></div>
                                            <div>
                                                <div class="skeleton-box mb-1" style="width: 120px; height: 16px; border-radius: 4px;"></div>
                                                <div class="skeleton-box" style="width: 80px; height: 12px; border-radius: 4px;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 80px; height: 20px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box mx-auto" style="width: 80px; height: 24px; border-radius: 20px;"></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="skeleton-box mx-auto" style="width: 40px; height: 20px; border-radius: 4px;"></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="skeleton-box mx-auto" style="width: 40px; height: 20px; border-radius: 4px;"></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="skeleton-box mx-auto" style="width: 50px; height: 16px; border-radius: 4px;"></div>
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
            <div id="courses-content" style="display: none; animation: fadeIn 0.5s ease-in-out;">
                <div class="table-responsive" id="coursesTableWrapper" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="coursesTable">
                        <thead class="border-bottom-2 sticky-top bg-white" style="top: 0; z-index: 10;">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 50px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 220px;">Course</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Category</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 100px;">Level</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 80px;">Students</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 80px;">Lessons</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 100px;">Certificate</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 130px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="coursesTableBody">
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

{{-- Add/Edit Course Modal --}}
<div class="modal fade" id="courseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="courseModalTitle">Add Course</h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <form id="courseForm">
                    <input type="hidden" id="courseId" name="courseId">
                    <div class="mb-3">
                        <label for="coursename" class="form-label text-muted small fw-bold">Course Name *</label>
                        <input type="text" class="form-control form-control-custom" id="coursename" name="coursename" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label text-muted small fw-bold">Category *</label>
                            <input type="text" class="form-control form-control-custom" id="category" name="category" placeholder="e.g., Space Exploration" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="courseLevel" class="form-label text-muted small fw-bold">Level *</label>
                            <select class="form-select form-control-custom" id="courseLevel" name="courseLevel" required>
                                <option value="Beginner">Beginner</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Advanced">Advanced</option>
                                <option value="Expert">Expert</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="coursedescription" class="form-label text-muted small fw-bold">Description</label>
                        <textarea class="form-control form-control-custom" id="coursedescription" name="coursedescription" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="image" class="form-label text-muted small fw-bold">Image URL</label>
                            <input type="url" class="form-control form-control-custom" id="image" name="image" placeholder="https://example.com/image.jpg">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="lessoncount" class="form-label text-muted small fw-bold">Lessons</label>
                            <input type="number" class="form-control form-control-custom" id="lessoncount" name="lessoncount" value="0" min="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="certificate" class="form-label text-muted small fw-bold">Certificate</label>
                            <select class="form-select form-control-custom" id="certificate" name="certificate">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark px-4" id="saveCourseBtn">Save Course</button>
            </div>
        </div>
    </div>
</div>

{{-- Lesson Modal (for managing lessons within a course) --}}
<div class="modal fade" id="lessonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="lessonModalTitle">
                    <i class="fas fa-book-open me-2"></i>Manage Lessons
                </h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <!-- Course Info Header -->
                <div class="bg-light p-3 rounded-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-0" id="lessonCourseName">Course: Loading...</h6>
                            <small class="text-muted" id="lessonCourseId">Course ID: -</small>
                        </div>
                        <span class="badge bg-dark" id="lessonCountBadge">0 lessons</span>
                    </div>
                </div>

                <div class="bg-light p-3 rounded-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0"><i class="fas fa-plus-circle me-1"></i>Add New Lesson</h6>
                    </div>
                    <form id="lessonForm" class="row g-2">
                        <input type="hidden" id="lessonId" name="lessonId">
                        <input type="hidden" id="lessonCourseIdField" name="courseid">
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-custom" id="lessonTitle" name="title" placeholder="Lesson Title *" required>
                        </div>
                        <div class="col-md-6">
                            <input type="url" class="form-control form-control-custom" id="videourl" name="videourl" placeholder="Video URL">
                        </div>
                        <div class="col-12">
                            <textarea class="form-control form-control-custom" id="lessonDescription" name="description" rows="2" placeholder="Lesson Description"></textarea>
                        </div>
                        <div class="col-12">
                            <textarea class="form-control form-control-custom" id="resources" name="resources" rows="2" placeholder="Resources (one per line)"></textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-dark btn-sm" id="saveLessonBtn">
                                <i class="fas fa-save me-1"></i> Add Lesson
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="cancelEditLessonBtn" onclick="cancelEditLesson()">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
                <!-- Lessons List -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="border-bottom-2">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; width: 40px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Lesson Title</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Description</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Video</th>
                                <th class="text-muted text-uppercase fs-6 text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Resources</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="lessonsTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <div class="spinner-border text-dark mb-2" role="status" style="width: 30px; height: 30px;">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mb-0">Loading lessons...</p>
                                    </div>
                                </td>
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

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-body text-center p-5">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex p-4 mb-3">
                    <i class="fas fa-trash-alt fa-2x"></i>
                </div>
                <h4 class="fw-bold mb-2">Delete Course?</h4>
                <p class="text-muted mb-4">Are you sure you want to delete this course? This action cannot be undone.</p>
                <input type="hidden" id="deleteCourseId">
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
    /* Skeleton Loading Animation */
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
    .form-control-custom::placeholder {
        color: #adb5bd;
    }
    textarea.form-control-custom {
        resize: vertical;
        min-height: 60px;
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
    .action-btn.lessons:hover {
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }
    .action-btn.delete:hover {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .course-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
        background: #f1f1f1;
    }

    .table > :not(caption) > * > * {
        padding: 0.75rem 0.75rem;
        vertical-align: middle;
    }

    .level-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .level-badge.beginner {
        background: #d4edda;
        color: #155724;
    }
    .level-badge.intermediate {
        background: #fff3cd;
        color: #856404;
    }
    .level-badge.advanced {
        background: #cce5ff;
        color: #004085;
    }
    .level-badge.expert {
        background: #f8d7da;
        color: #721c24;
    }

    .resource-badge {
        display: inline-block;
        padding: 2px 8px;
        background: #f1f1f1;
        border-radius: 4px;
        font-size: 0.65rem;
        margin: 2px;
        max-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #coursesTableWrapper::-webkit-scrollbar {
        width: 6px;
    }
    #coursesTableWrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    #coursesTableWrapper::-webkit-scrollbar-thumb {
        background: #d1d1d1;
        border-radius: 10px;
    }
    #coursesTableWrapper::-webkit-scrollbar-thumb:hover {
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
let editingCourseId = null;
let editingLessonId = null;
let currentCourseId = null;
let allCourses = [];
let filteredCourses = [];

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

document.addEventListener('DOMContentLoaded', function() {
    loadCourses();
    setupSearch();
});

// Setup search functionality
function setupSearch() {
    const searchInput = document.getElementById('searchCourses');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            filterCourses(query);
        });
    }
}

// Filter courses based on search query
function filterCourses(query) {
    if (!query) {
        filteredCourses = allCourses;
    } else {
        filteredCourses = allCourses.filter(course => {
            const name = (course.coursename || '').toLowerCase();
            const category = (course.category || '').toLowerCase();
            const level = (course.courseLevel || '').toLowerCase();
            return name.includes(query) || category.includes(query) || level.includes(query);
        });
    }
    renderCourses(filteredCourses);
    updateFilteredCount(filteredCourses.length);
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
        recordCount.textContent = `(${allCourses.length} total records)`;
    }
}

// Load all courses
async function loadCourses() {
    const skeleton = document.getElementById('courses-skeleton');
    const content = document.getElementById('courses-content');
    const recordCount = document.getElementById('recordCount');

    try {
        const response = await fetch('/admin/api/courses');
        const data = await response.json();

        if (data.success) {
            allCourses = data.data || [];
            filteredCourses = allCourses;
            renderCourses(filteredCourses);
            updateFilteredCount(filteredCourses.length);
            recordCount.textContent = `(${allCourses.length} total records)`;
        } else {
            showToast('Failed to load courses', 'danger');
            renderCourses([]);
            updateFilteredCount(0);
            recordCount.textContent = '(0 records)';
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        showToast('Error loading courses', 'danger');
        renderCourses([]);
        updateFilteredCount(0);
        recordCount.textContent = '(0 records)';
    } finally {
        // Hide skeleton, show content
        if (skeleton) skeleton.style.display = 'none';
        if (content) content.style.display = 'block';
    }
}

function renderCourses(courses) {
    const tbody = document.getElementById('coursesTableBody');
    const scrollIndicator = document.getElementById('scrollIndicator');
    const tableWrapper = document.getElementById('coursesTableWrapper');

    // Show/hide scroll indicator based on record count
    if (courses && courses.length > 10) {
        if (tableWrapper) {
            tableWrapper.style.maxHeight = '600px';
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

    if (!courses || courses.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-book fa-2x mb-3 d-block opacity-50"></i>
                        <p class="mb-0 fw-bold">${allCourses.length > 0 ? 'No matching courses found' : 'No courses found'}</p>
                        <small>${allCourses.length > 0 ? 'Try a different search term' : 'Click "Add Course" to create one'}</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    courses.forEach((course, index) => {
        const levelClass = (course.courseLevel || 'Beginner').toLowerCase();
        const studentCount = course.enrolledUsers?.length || 0;
        const lessonCount = course.lessoncount || 0;
        const hasCertificate = course.certificate ? '✅ Yes' : '❌ No';
        const imageHtml = course.image ?
            `<img src="${escapeHtml(course.image)}" alt="${escapeHtml(course.coursename)}" class="course-image">` :
            `<div class="course-image d-flex align-items-center justify-content-center bg-light text-muted">
                <i class="fas fa-image"></i>
             </div>`;

        html += `
            <tr>
                <td class="text-center text-muted">${index + 1}</td>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        ${imageHtml}
                        <div>
                            <div class="fw-bold text-dark">${escapeHtml(course.coursename)}</div>
                            <div class="text-muted small">${escapeHtml(course.courseid || 'N/A')}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge bg-light text-dark border">${escapeHtml(course.category || 'General')}</span></td>
                <td><span class="level-badge ${levelClass}">${escapeHtml(course.courseLevel || 'Beginner')}</span></td>
                <td class="text-center"><span class="badge bg-dark">${studentCount}</span></td>
                <td class="text-center"><span class="badge bg-secondary">${lessonCount}</span></td>
                <td class="text-center">${hasCertificate}</td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <button class="action-btn lessons" onclick="openLessonModal('${course.id}')" title="Manage Lessons">
                            <i class="fas fa-book-open"></i>
                        </button>
                        <button class="action-btn" onclick="editCourse('${course.id}')" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="confirmDelete('${course.id}')" title="Delete">
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
    editingCourseId = null;
    document.getElementById('courseModalTitle').textContent = 'Add New Course';
    document.getElementById('courseForm').reset();
    document.getElementById('courseId').value = '';
    document.getElementById('saveCourseBtn').textContent = 'Save Course';
    document.getElementById('certificate').value = '0';

    const modal = new bootstrap.Modal(document.getElementById('courseModal'));
    modal.show();
}

// Edit Course
async function editCourse(courseId) {
    try {
        const response = await fetch(`/admin/api/courses/${courseId}`);
        const data = await response.json();

        if (data.success) {
            const course = data.data;
            editingCourseId = courseId;

            document.getElementById('courseModalTitle').textContent = 'Edit Course';
            document.getElementById('courseId').value = courseId;
            document.getElementById('coursename').value = course.coursename || '';
            document.getElementById('category').value = course.category || '';
            document.getElementById('courseLevel').value = course.courseLevel || 'Beginner';
            document.getElementById('coursedescription').value = course.coursedescription || '';
            document.getElementById('image').value = course.image || '';
            document.getElementById('lessoncount').value = course.lessoncount || 0;
            document.getElementById('certificate').value = course.certificate ? '1' : '0';
            document.getElementById('saveCourseBtn').textContent = 'Update Course';

            const modal = new bootstrap.Modal(document.getElementById('courseModal'));
            modal.show();
        } else {
            showToast('Failed to load course data', 'danger');
        }
    } catch (error) {
        console.error('Error loading course:', error);
        showToast('Error loading course data', 'danger');
    }
}

// Save Course
document.getElementById('saveCourseBtn').addEventListener('click', async function() {
    const form = document.getElementById('courseForm');
    const formData = new FormData(form);
    const courseId = document.getElementById('courseId').value;

    const courseData = {
        coursename: formData.get('coursename'),
        category: formData.get('category'),
        courseLevel: formData.get('courseLevel'),
        coursedescription: formData.get('coursedescription'),
        image: formData.get('image'),
        lessoncount: parseInt(formData.get('lessoncount')) || 0,
        certificate: formData.get('certificate') === '1',
    };

    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        let url = '/admin/api/courses';
        let method = 'POST';

        if (courseId) {
            url = `/admin/api/courses/${courseId}`;
            method = 'PUT';
        }

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(courseData)
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message || 'Course saved successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('courseModal'));
            if (modal) modal.hide();
            loadCourses();
        } else {
            showToast(data.message || 'Failed to save course', 'danger');
        }
    } catch (error) {
        console.error('Error saving course:', error);
        showToast('Error saving course: ' + error.message, 'danger');
    } finally {
        this.disabled = false;
        this.innerHTML = courseId ? 'Update Course' : 'Save Course';
    }
});

// Open Lesson Modal
async function openLessonModal(courseId) {
    currentCourseId = courseId;
    editingLessonId = null;

    document.getElementById('lessonForm').reset();
    document.getElementById('lessonId').value = '';
    document.getElementById('saveLessonBtn').innerHTML = '<i class="fas fa-save me-1"></i> Add Lesson';
    document.getElementById('cancelEditLessonBtn').classList.add('d-none');
    document.getElementById('lessonCourseIdField').value = courseId;
    document.getElementById('lessonModalTitle').textContent = '📚 Manage Lessons';

    try {
        const response = await fetch(`/admin/api/courses/${courseId}`);
        const data = await response.json();
        if (data.success) {
            document.getElementById('lessonCourseName').textContent = `Course: ${data.data.coursename}`;
            document.getElementById('lessonCourseId').textContent = `Course ID: ${data.data.courseid || data.data.id}`;
            document.getElementById('lessonCountBadge').textContent = `${data.data.lessoncount || 0} lessons`;
        }
    } catch (error) {
        console.error('Error loading course:', error);
    }

    await loadLessons(courseId);

    const modal = new bootstrap.Modal(document.getElementById('lessonModal'));
    modal.show();
}

async function loadLessons(courseId) {
    try {
        const response = await fetch(`/admin/api/courses/${courseId}/lessons`);
        const data = await response.json();

        if (data.success) {
            renderLessons(data.data);

            document.getElementById('lessonCountBadge').textContent = `${data.data.length || 0} lessons`;
        } else {
            showToast('Failed to load lessons', 'danger');
        }
    } catch (error) {
        console.error('Error loading lessons:', error);
        showToast('Error loading lessons', 'danger');
    }
}

function renderLessons(lessons) {
    const tbody = document.getElementById('lessonsTableBody');

    if (!lessons || lessons.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4">
                    <div class="text-muted">
                        <i class="fas fa-book-open fa-2x mb-2 d-block opacity-50"></i>
                        <p class="mb-0">No lessons added yet</p>
                        <small>Add your first lesson using the form above</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    lessons.forEach((lesson, index) => {
        const hasVideo = lesson.videourl ? '✅' : '❌';
        const videoTitle = lesson.videourl ? lesson.videourl : 'No video';
        const resourceCount = lesson.resources?.length || 0;
        const hasResources = resourceCount > 0;

        const description = lesson.description || '';
        const shortDesc = description.length > 60 ? description.substring(0, 60) + '...' : description;

        let resourcesHtml = '';
        if (lesson.resources && lesson.resources.length > 0) {
            resourcesHtml = lesson.resources.slice(0, 2).map(r =>
                `<span class="resource-badge" title="${escapeHtml(r)}">${escapeHtml(r.substring(0, 25))}${r.length > 25 ? '...' : ''}</span>`
            ).join(' ');
            if (lesson.resources.length > 2) {
                resourcesHtml += `<span class="resource-badge">+${lesson.resources.length - 2} more</span>`;
            }
        } else {
            resourcesHtml = '<span class="text-muted">-</span>';
        }

        html += `
            <tr>
                <td class="text-center text-muted">${index + 1}</td>
                <td>
                    <div>
                        <div class="fw-bold text-dark">${escapeHtml(lesson.title)}</div>
                        <div class="text-muted small">ID: ${escapeHtml(lesson.lessonid || lesson.id)}</div>
                    </div>
                </td>
                <td>
                    ${description ? `<span class="text-muted small">${escapeHtml(shortDesc)}</span>` : '<span class="text-muted small">-</span>'}
                </td>
                <td class="text-center" title="${escapeHtml(videoTitle)}">
                    <span class="${lesson.videourl ? 'text-success' : 'text-muted'}">
                        ${hasVideo}
                    </span>
                </td>
                <td>
                    <div style="max-width: 150px;">
                        ${resourcesHtml}
                    </div>
                </td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <button class="action-btn" onclick="editLesson('${lesson.id}')" title="Edit Lesson">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="confirmDeleteLesson('${lesson.id}')" title="Delete Lesson">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// Cancel Edit
function cancelEditLesson() {
    editingLessonId = null;
    document.getElementById('lessonForm').reset();
    document.getElementById('lessonId').value = '';
    document.getElementById('saveLessonBtn').innerHTML = '<i class="fas fa-save me-1"></i> Add Lesson';
    document.getElementById('cancelEditLessonBtn').classList.add('d-none');
    document.getElementById('lessonModalTitle').textContent = '📚 Manage Lessons';
}

// Edit Lesson
async function editLesson(lessonId) {
    try {
        const response = await fetch(`/admin/api/lessons/${lessonId}`);
        const data = await response.json();

        if (data.success) {
            const lesson = data.data;
            editingLessonId = lessonId;

            document.getElementById('lessonId').value = lessonId;
            document.getElementById('lessonTitle').value = lesson.title || '';
            document.getElementById('lessonDescription').value = lesson.description || '';
            document.getElementById('videourl').value = lesson.videourl || '';
            document.getElementById('resources').value = (lesson.resources || []).join('\n');
            document.getElementById('lessonCourseIdField').value = lesson.courseid || currentCourseId;
            document.getElementById('saveLessonBtn').innerHTML = '<i class="fas fa-save me-1"></i> Update Lesson';
            document.getElementById('cancelEditLessonBtn').classList.remove('d-none');
            document.getElementById('lessonModalTitle').textContent = '✏️ Edit Lesson';

            document.getElementById('lessonForm').scrollIntoView({ behavior: 'smooth' });
        } else {
            showToast('Failed to load lesson data', 'danger');
        }
    } catch (error) {
        console.error('Error loading lesson:', error);
        showToast('Error loading lesson data', 'danger');
    }
}

// Save Lesson (Add or Update)
document.getElementById('saveLessonBtn').addEventListener('click', async function() {
    const form = document.getElementById('lessonForm');
    const formData = new FormData(form);
    const lessonId = document.getElementById('lessonId').value;

    const lessonData = {
        title: formData.get('title'),
        description: formData.get('description'),
        courseid: formData.get('courseid'),
        videourl: formData.get('videourl'),
        resources: formData.get('resources') || '',
    };

    if (!lessonData.title) {
        showToast('Lesson title is required', 'danger');
        return;
    }

    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        let url = '/admin/api/lessons';
        let method = 'POST';

        if (lessonId) {
            url = `/admin/api/lessons/${lessonId}`;
            method = 'PUT';
        }

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(lessonData)
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message || 'Lesson saved successfully', 'success');

            // Reset form
            document.getElementById('lessonForm').reset();
            document.getElementById('lessonId').value = '';
            document.getElementById('saveLessonBtn').innerHTML = '<i class="fas fa-save me-1"></i> Add Lesson';
            document.getElementById('cancelEditLessonBtn').classList.add('d-none');
            document.getElementById('lessonModalTitle').textContent = '📚 Manage Lessons';
            editingLessonId = null;

            await loadLessons(currentCourseId);

            loadCourses();
        } else {
            showToast(data.message || 'Failed to save lesson', 'danger');
        }
    } catch (error) {
        console.error('Error saving lesson:', error);
        showToast('Error saving lesson: ' + error.message, 'danger');
    } finally {
        this.disabled = false;
        this.innerHTML = lessonId ? '<i class="fas fa-save me-1"></i> Update Lesson' : '<i class="fas fa-save me-1"></i> Add Lesson';
    }
});

function confirmDeleteLesson(lessonId) {
    if (confirm('Are you sure you want to delete this lesson? This action cannot be undone.')) {
        deleteLesson(lessonId);
    }
}

// Delete Lesson
async function deleteLesson(lessonId) {
    try {
        const response = await fetch(`/admin/api/lessons/${lessonId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            }
        });

        const data = await response.json();

        if (data.success) {
            showToast('Lesson deleted successfully', 'success');
            await loadLessons(currentCourseId);
            loadCourses();
        } else {
            showToast(data.message || 'Failed to delete lesson', 'danger');
        }
    } catch (error) {
        console.error('Error deleting lesson:', error);
        showToast('Error deleting lesson: ' + error.message, 'danger');
    }
}

function confirmDelete(courseId) {
    document.getElementById('deleteCourseId').value = courseId;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Delete Course
document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    const courseId = document.getElementById('deleteCourseId').value;

    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

        const response = await fetch(`/admin/api/courses/${courseId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            }
        });

        const data = await response.json();

        if (data.success) {
            showToast('Course deleted successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            if (modal) modal.hide();
            loadCourses();
        } else {
            showToast(data.message || 'Failed to delete course', 'danger');
        }
    } catch (error) {
        console.error('Error deleting course:', error);
        showToast('Error deleting course: ' + error.message, 'danger');
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
