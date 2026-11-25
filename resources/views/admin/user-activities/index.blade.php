@extends('layouts.admin')

@section('title', 'User Activity Report')

@section('content')
<div class="container-fluid py-2">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0">
                <i class="fas fa-chart-bar me-2"></i>User Activity Report
            </h4>
            <small class="text-muted">
                Per-user engagement across articles, facts, videos, games, personalities, quizzes, and course progress.
            </small>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-dark btn-sm d-flex align-items-center" id="btnExportExcel" onclick="exportReport('excel')">
                <i class="fas fa-file-excel me-2"></i>Export Excel
            </button>
            <button class="btn btn-outline-dark btn-sm d-flex align-items-center" id="btnExportPdf" onclick="exportReport('pdf')">
                <i class="fas fa-file-pdf me-2"></i>Export PDF
            </button>
            <button class="btn btn-dark btn-sm d-flex align-items-center" onclick="loadReport()">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
        </div>
    </div>

    {{-- Summary Stat Cards --}}
    <div class="row g-3 mb-4" id="statCards">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 stat-card" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 d-flex align-items-center justify-content-center me-3 stat-icon"
                         style="width: 48px; height: 48px; background: linear-gradient(135deg, #dbeafe, #bfdbfe);">
                        <i class="fas fa-newspaper fa-lg text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted text-uppercase stat-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Articles Read</div>
                        <div class="fw-bold fs-5 stat-value" id="stat-articles">—</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 stat-card" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 d-flex align-items-center justify-content-center me-3 stat-icon"
                         style="width: 48px; height: 48px; background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                        <i class="fas fa-lightbulb fa-lg text-success"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted text-uppercase stat-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Facts Read</div>
                        <div class="fw-bold fs-5 stat-value" id="stat-facts">—</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 stat-card" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 d-flex align-items-center justify-content-center me-3 stat-icon"
                         style="width: 48px; height: 48px; background: linear-gradient(135deg, #fef3c7, #fde68a);">
                        <i class="fas fa-play-circle fa-lg text-warning"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted text-uppercase stat-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Videos Watched</div>
                        <div class="fw-bold fs-5 stat-value" id="stat-videos">—</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 stat-card" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 d-flex align-items-center justify-content-center me-3 stat-icon"
                         style="width: 48px; height: 48px; background: linear-gradient(135deg, #fee2e2, #fecaca);">
                        <i class="fas fa-gamepad fa-lg text-danger"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted text-uppercase stat-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Games Completed</div>
                        <div class="fw-bold fs-5 stat-value" id="stat-games">—</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 stat-card" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 d-flex align-items-center justify-content-center me-3 stat-icon"
                         style="width: 48px; height: 48px; background: linear-gradient(135deg, #fef3c7, #fde68a);">
                        <i class="fas fa-user-tie fa-lg text-warning"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted text-uppercase stat-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Personalities Viewed</div>
                        <div class="fw-bold fs-5 stat-value" id="stat-personalities">—</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm h-100 stat-card" style="border-radius: 12px;">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-3 d-flex align-items-center justify-content-center me-3 stat-icon"
                         style="width: 48px; height: 48px; background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                        <i class="fas fa-check-circle fa-lg text-success"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-muted text-uppercase stat-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Quizzes Completed</div>
                        <div class="fw-bold fs-5 stat-value" id="stat-quizzes">—</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary stats --}}
    <div class="row g-3 mb-4" id="secondaryStatCards">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card" style="border-radius: 12px;">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted text-uppercase stat-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Active Users (with engagement)</div>
                            <div class="fw-bold fs-4 stat-value" id="stat-active">—</div>
                        </div>
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 stat-icon-lg"
                             style="width: 44px; height: 44px; background: linear-gradient(135deg, #dbeafe, #bfdbfe);">
                            <i class="fas fa-users text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card" style="border-radius: 12px;">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted text-uppercase stat-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Cosmic Stars Earned</div>
                            <div class="fw-bold fs-4 stat-value" id="stat-stars">—</div>
                        </div>
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 stat-icon-lg"
                             style="width: 44px; height: 44px; background: linear-gradient(135deg, #fef3c7, #fde68a);">
                            <i class="fas fa-star text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card" style="border-radius: 12px;">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted text-uppercase stat-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Course Enrollments</div>
                            <div class="fw-bold fs-4 stat-value" id="stat-enrollments">—</div>
                        </div>
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 stat-icon-lg"
                             style="width: 44px; height: 44px; background: linear-gradient(135deg, #fee2e2, #fecaca);">
                            <i class="fas fa-user-graduate text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card" style="border-radius: 12px;">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted text-uppercase stat-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Lessons Completed</div>
                            <div class="fw-bold fs-4 stat-value" id="stat-lessons">—</div>
                        </div>
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 stat-icon-lg"
                             style="width: 44px; height: 44px; background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                            <i class="fas fa-book-open text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card" style="border-radius: 12px;">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted text-uppercase stat-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Courses Completed</div>
                            <div class="fw-bold fs-4 stat-value" id="stat-courses-completed">—</div>
                        </div>
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 stat-icon-lg"
                             style="width: 44px; height: 44px; background: linear-gradient(135deg, #dbeafe, #bfdbfe);">
                            <i class="fas fa-graduation-cap text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-card" style="border-radius: 12px;">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted text-uppercase stat-label" style="font-size: 0.65rem; letter-spacing: 0.5px;">Course Quizzes Completed</div>
                            <div class="fw-bold fs-4 stat-value" id="stat-course-quizzes">—</div>
                        </div>
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 stat-icon-lg"
                             style="width: 44px; height: 44px; background: linear-gradient(135deg, #fef3c7, #fde68a);">
                            <i class="fas fa-clipboard-check text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Video Views --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3">
                    <span class="fw-bold"><i class="fas fa-fire me-2 text-danger"></i>Most Viewed Videos</span>
                    <small class="text-muted d-block">Top 4 by lesson view count</small>
                </div>
                <div class="card-body pt-0" id="topVideosContainer">
                    <div class="text-muted py-4 text-center">Loading…</div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3">
                    <span class="fw-bold"><i class="fas fa-video me-2 text-dark"></i>Views by Video</span>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="sticky-top">
                                <tr class="bg-white">
                                    <th class="text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">#</th>
                                    <th class="text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Video</th>
                                    <th class="text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Course</th>
                                    <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Views</th>
                                </tr>
                            </thead>
                            <tbody id="videoViewsTableBody">
                                <tr><td colspan="4" class="text-center text-muted py-4">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Article Views --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3">
                    <span class="fw-bold"><i class="fas fa-fire me-2 text-danger"></i>Most Read Articles</span>
                    <small class="text-muted d-block">Top 4 by article view count</small>
                </div>
                <div class="card-body pt-0" id="topArticlesContainer">
                    <div class="text-muted py-4 text-center">Loading…</div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3">
                    <span class="fw-bold"><i class="fas fa-newspaper me-2 text-dark"></i>Views by Article</span>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="sticky-top">
                                <tr class="bg-white">
                                    <th class="text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">#</th>
                                    <th class="text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Article</th>
                                    <th class="text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Category</th>
                                    <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Views</th>
                                </tr>
                            </thead>
                            <tbody id="articleViewsTableBody">
                                <tr><td colspan="4" class="text-center text-muted py-4">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3">
                    <span class="fw-bold"><i class="fas fa-chart-bar me-2 text-dark"></i>Engagement by Activity Type</span>
                </div>
                <div class="card-body pt-0">
                    <div style="height: 280px; position: relative;">
                        <canvas id="chartByType"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3">
                    <span class="fw-bold"><i class="fas fa-trophy me-2 text-dark"></i>Top 10 Most Engaged Users</span>
                </div>
                <div class="card-body pt-0">
                    <div style="height: 280px; position: relative;">
                        <canvas id="chartTopUsers"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3">
                    <span class="fw-bold"><i class="fas fa-chart-pie me-2 text-dark"></i>Engagement by Role</span>
                </div>
                <div class="card-body pt-0">
                    <div style="height: 280px; position: relative;">
                        <canvas id="chartByRole"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3">
                    <span class="fw-bold"><i class="fas fa-chart-line me-2 text-dark"></i>Activity Log Entries (Last 30 Days)</span>
                </div>
                <div class="card-body pt-0">
                    <div style="height: 280px; position: relative;">
                        <canvas id="chartOverTime"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="row mb-3 g-2">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0" style="border-radius: 10px 0 0 10px;">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" class="form-control border-start-0" id="searchInput"
                       placeholder="Search by username, email or institution..."
                       style="border-left: none; border-radius: 0 10px 10px 0;">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="roleFilter" style="border-radius: 10px;">
                <option value="all">All roles</option>
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" id="sortBy" style="border-radius: 10px;">
                <option value="engagement">Engagement Score</option>
                <option value="articles">Articles Read</option>
                <option value="facts">Facts Read</option>
                <option value="videos">Videos Watched</option>
                <option value="games">Games Completed</option>
                <option value="personalities">Personalities Viewed</option>
                <option value="quizzes">Quizzes Completed</option>
                <option value="stars">Cosmic Stars</option>
                <option value="xp">Space XP</option>
                <option value="courses">Course Enrollments</option>
                <option value="lessons">Lessons Completed</option>
                <option value="coursesdone">Courses Completed</option>
                <option value="username">Username</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" id="sortDir" style="border-radius: 10px;">
                <option value="desc">Descending</option>
                <option value="asc">Ascending</option>
            </select>
        </div>
    </div>

    {{-- User table --}}
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-body p-0">
            <div id="table-skeleton" class="p-4">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="border-bottom">
                            <tr>
                                <th class="text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px; width: 40px;">#</th>
                                <th class="text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">User</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px; width: 80px;">Role</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Articles</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Facts</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Videos</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Games</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Personalities</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Quizzes</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Stars</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">XP</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Courses</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Lessons Done</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Courses Done</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Last Active</th>
                                <th class="text-muted text-uppercase text-end" style="font-size: 0.7rem; letter-spacing: 0.5px;">Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 8; $i++)
                                <tr>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 16px; height: 14px;"></div></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="skeleton-box me-2" style="width: 32px; height: 32px; border-radius: 50%;"></div>
                                            <div>
                                                <div class="skeleton-box mb-1" style="width: 140px; height: 14px;"></div>
                                                <div class="skeleton-box" style="width: 180px; height: 10px;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 60px; height: 20px; border-radius: 10px;"></div></td>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 24px; height: 14px;"></div></td>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 24px; height: 14px;"></div></td>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 24px; height: 14px;"></div></td>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 24px; height: 14px;"></div></td>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 24px; height: 14px;"></div></td>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 24px; height: 14px;"></div></td>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 28px; height: 14px;"></div></td>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 28px; height: 14px;"></div></td>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 24px; height: 14px;"></div></td>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 24px; height: 14px;"></div></td>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 24px; height: 14px;"></div></td>
                                    <td class="text-center"><div class="skeleton-box mx-auto" style="width: 60px; height: 12px;"></div></td>
                                    <td class="text-end"><div class="skeleton-box ms-auto" style="width: 40px; height: 20px; border-radius: 12px;"></div></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="table-content" style="display: none;">
                <div class="table-responsive" style="max-height: 700px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="reportTable">
                        <thead class="sticky-top">
                            <tr class="bg-white">
                                <th class="text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">#</th>
                                <th class="text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">User</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Role</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Articles</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Facts</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Videos</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Games</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Personalities</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Quizzes</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Stars</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">XP</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Courses</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Lessons Done</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Courses Done</th>
                                <th class="text-muted text-uppercase text-center" style="font-size: 0.7rem; letter-spacing: 0.5px;">Last Active</th>
                                <th class="text-muted text-uppercase text-end" style="font-size: 0.7rem; letter-spacing: 0.5px;">Score</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .sticky-top th {
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 10;
        box-shadow: inset 0 -1px 0 #dee2e6;
    }
    .table > :not(caption) > * > * {
        padding: 0.75rem 0.6rem;
        vertical-align: middle;
    }
    .avatar-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #f1f1f1;
        color: #333;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.7rem;
        flex-shrink: 0;
    }
    .role-badge {
        padding: 4px 10px;
        border-radius: 10px;
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .role-badge.admin    { background: #000000; color: #ffffff; }
    .role-badge.teacher  { background: #6c757d; color: #ffffff; }
    .role-badge.student  { background: #f1f1f1; color: #333333; }
    .activity-cell {
        font-variant-numeric: tabular-nums;
        font-weight: 500;
    }
    .activity-zero { color: #d1d5db; }
    .score-pill {
        background: #000;
        color: #fff;
        padding: 4px 12px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.8rem;
        display: inline-block;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    .min-width-0 { min-width: 0; }

    [data-bs-theme="dark"] .sticky-top th { background: #0a0a0a; color: #aaa; }
    [data-bs-theme="dark"] .table-hover tbody tr:hover { background-color: #1a1a1a; }
    [data-bs-theme="dark"] .avatar-circle { background: #222; color: #ddd; }
    [data-bs-theme="dark"] .role-badge.student { background: #222; color: #ddd; }
    [data-bs-theme="dark"] .score-pill { background: #fff; color: #000; }

    @keyframes skeleton-pulse {
        0%   { opacity: 0.55; }
        50%  { opacity: 1; }
        100% { opacity: 0.55; }
    }
    .skeleton-box {
        display: block;
        background: linear-gradient(90deg, #eceff1 25%, #f5f6f8 37%, #eceff1 63%);
        background-size: 400% 100%;
        border-radius: 6px;
        animation: skeleton-pulse 1.5s infinite ease-in-out;
    }
    [data-bs-theme="dark"] .skeleton-box {
        background: linear-gradient(90deg, #1a1a1a 25%, #2a2a2a 37%, #1a1a1a 63%);
        background-size: 400% 100%;
    }

    .stat-card.is-loading .stat-label,
    .stat-card.is-loading .stat-value,
    .stat-card.is-loading .stat-icon,
    .stat-card.is-loading .stat-icon-lg {
        visibility: hidden;
    }
    .stat-card.is-loading .stat-label::after,
    .stat-card.is-loading .stat-value::after,
    .stat-card.is-loading .stat-icon::after,
    .stat-card.is-loading .stat-icon-lg::after {
        content: '';
        display: block;
        border-radius: 6px;
        background: linear-gradient(90deg, #eceff1 25%, #f5f6f8 37%, #eceff1 63%);
        background-size: 400% 100%;
        animation: skeleton-pulse 1.5s infinite ease-in-out;
        visibility: visible;
        position: absolute;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
    }
    .stat-card.is-loading .stat-label::after { width: 80px; height: 10px; }
    .stat-card.is-loading .stat-value::after { width: 48px; height: 20px; }
    .stat-card.is-loading .stat-icon::after,
    .stat-card.is-loading .stat-icon-lg::after {
        width: 100%;
        height: 100%;
        border-radius: 12px;
    }
    .stat-card.is-loading .stat-icon,
    .stat-card.is-loading .stat-icon-lg {
        position: relative;
        background: transparent !important;
        color: transparent !important;
    }
    [data-bs-theme="dark"] .stat-card.is-loading .stat-label::after,
    [data-bs-theme="dark"] .stat-card.is-loading .stat-value::after,
    [data-bs-theme="dark"] .stat-card.is-loading .stat-icon::after,
    [data-bs-theme="dark"] .stat-card.is-loading .stat-icon-lg::after {
        background: linear-gradient(90deg, #1a1a1a 25%, #2a2a2a 37%, #1a1a1a 63%);
        background-size: 400% 100%;
    }

    .table-refreshing { position: relative; }
    .table-refreshing::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,0.55);
        backdrop-filter: blur(1px);
        z-index: 5;
        pointer-events: none;
    }
    [data-bs-theme="dark"] .table-refreshing::after { background: rgba(10,10,10,0.55); }
</style>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let charts = { byType: null, topUsers: null, byRole: null, overTime: null };
let lastReportData = null;
let debounceTimer = null;
let currentRequest = null;

document.addEventListener('DOMContentLoaded', function() {
    loadReport();

    document.getElementById('searchInput').addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadReport, 350);
    });

    ['roleFilter', 'sortBy', 'sortDir'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', () => {
                console.log('[filter change]', id, '=', el.value);
                loadReport();
            });
        }
    });
});

function buildQuery() {
    const params = new URLSearchParams();
    const q    = document.getElementById('searchInput').value.trim();
    const role = document.getElementById('roleFilter').value;
    const sort = document.getElementById('sortBy').value;
    const dir  = document.getElementById('sortDir').value;
    if (q) params.set('q', q);
    if (role && role !== 'all') params.set('role', role);
    params.set('sort', sort);
    params.set('dir', dir);
    return params.toString();
}

function setStatCardsSkeleton(on) {
    document.querySelectorAll('.stat-card').forEach(card => {
        card.classList.toggle('is-loading', on);
    });
}

async function loadReport() {
    const skeleton  = document.getElementById('table-skeleton');
    const content   = document.getElementById('table-content');
    const tableWrap = content ? content.querySelector('.table-responsive') : null;
    const isFirstLoad = !lastReportData;

    if (currentRequest) {
        try { currentRequest.abort(); } catch (e) {}
    }
    const controller = new AbortController();
    currentRequest = controller;

    setStatCardsSkeleton(true);

    if (isFirstLoad) {
        if (skeleton) skeleton.style.display = 'block';
        if (content)  content.style.display  = 'none';
    } else {
        if (tableWrap) tableWrap.classList.add('table-refreshing');
    }

    const qs = buildQuery();
    console.log('[loadReport] fetching', qs);

    try {
        const res  = await fetch('/admin/api/user-activities/report?' + qs, {
            headers: { 'Accept': 'application/json' },
            signal: controller.signal,
        });
        const json = await res.json();

        if (!json.success) {
            showToast(json.message || 'Failed to load report', 'danger');
            return;
        }

        lastReportData = json.data;
        renderStats(json.data);
        renderTable(json.data.rows);
        renderCharts(json.data);
        renderVideoViews(json.data);
        renderArticleViews(json.data);
    } catch (e) {
        if (e.name === 'AbortError') {
            console.log('[loadReport] aborted previous request');
            return;
        }
        console.error(e);
        showToast('Error loading report', 'danger');
    } finally {
        if (currentRequest === controller) currentRequest = null;
        setStatCardsSkeleton(false);
        if (isFirstLoad) {
            if (skeleton) skeleton.style.display = 'none';
            if (content)  content.style.display  = 'block';
        } else {
            if (tableWrap) tableWrap.classList.remove('table-refreshing');
        }
    }
}

function renderStats(data) {
    const t = data.totals || {};

    document.getElementById('stat-articles').textContent      = fmt(data.article_views?.total ?? 0);
    document.getElementById('stat-videos').textContent        = fmt(data.video_views?.total ?? 0);
    document.getElementById('stat-facts').textContent         = fmt(t.factsRead ?? 0);
    document.getElementById('stat-games').textContent         = fmt(t.gamesCompleted ?? 0);
    document.getElementById('stat-personalities').textContent = fmt(t.personalityview ?? 0);
    document.getElementById('stat-quizzes').textContent       = fmt(t.quizzesCompleted ?? 0);
    document.getElementById('stat-active').textContent        = fmt(data.active_user_count ?? 0);
    document.getElementById('stat-stars').textContent         = fmt(t.cosmicStars ?? 0);
    document.getElementById('stat-enrollments').textContent   = fmt(t.courseEnrollments ?? 0);
    document.getElementById('stat-lessons').textContent       = fmt(t.completedLessons ?? 0);
    document.getElementById('stat-courses-completed').textContent = fmt(t.completedCourses ?? 0);
    document.getElementById('stat-course-quizzes').textContent = fmt(t.completedQuizzes ?? 0);
}

function renderTable(rows) {
    const tbody = document.getElementById('reportTableBody');

    if (!rows || rows.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="16" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-folder-open fa-2x mb-3 d-block opacity-50"></i>
                        <p class="mb-0 fw-bold">No user activity found</p>
                        <small>Try adjusting filters or check back later.</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    rows.forEach((u, idx) => {
        const initials   = (u.username || 'U').substring(0, 2).toUpperCase();
        const a          = u.activities || {};
        const prog       = u.progress || {};
        const lessonsDone = prog.total_completed_lessons ?? 0;
        const coursesDone = prog.completed_courses ?? 0;
        const lastActive = u.last_activity ? formatDate(u.last_activity) : '—';

        const cell = (val) => {
            if (!val || val === 0) return `<td class="text-center activity-cell activity-zero">0</td>`;
            return `<td class="text-center activity-cell">${escapeHtml(String(val))}</td>`;
        };

        html += `
            <tr>
                <td class="text-center text-muted">${idx + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle me-2">${initials}</div>
                        <div>
                            <div class="fw-bold text-dark">${escapeHtml(u.username || 'Unknown')}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">${escapeHtml(u.email || '—')}</div>
                        </div>
                    </div>
                </td>
                <td class="text-center"><span class="role-badge ${u.role || 'student'}">${escapeHtml(u.role || 'student')}</span></td>
                ${cell(a.articlesRead)}
                ${cell(a.factsRead)}
                ${cell(a.videosWatched)}
                ${cell(a.gamesCompleted)}
                ${cell(a.personalityview)}
                ${cell(a.quizzesCompleted)}
                <td class="text-center activity-cell">
                    ${u.cosmic?.stars ? `<i class="fas fa-star text-warning me-1"></i>${u.cosmic.stars}` : '<span class="activity-zero">0</span>'}
                </td>
                <td class="text-center activity-cell">${u.space?.xp ?? 0}</td>
                <td class="text-center activity-cell">${u.courses_enrolled ?? 0}</td>
                ${cell(lessonsDone)}
                ${cell(coursesDone)}
                <td class="text-center text-muted" style="font-size: 0.78rem;">${lastActive}</td>
                <td class="text-end"><span class="score-pill">${u.engagement_score ?? 0}</span></td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function renderVideoViews(data) {
    const vv = data.video_views || { total: 0, top_video: null, top_videos: [], videos: [] };

    const topContainer = document.getElementById('topVideosContainer');
    if (topContainer) {
        const top = vv.top_videos || [];
        if (top.length === 0) {
            topContainer.innerHTML = `<p class="text-muted text-center py-4 mb-0">No video views recorded yet.</p>`;
        } else {
            topContainer.innerHTML = top.map((v, i) => {
                const rankColor = ['#dc3545', '#fd7e14', '#ffc107', '#0d6efd'][i] || '#6c757d';
                return `
                    <div class="d-flex align-items-center gap-3 py-2 ${i < top.length - 1 ? 'border-bottom' : ''}">
                        <div class="d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:32px;height:32px;border-radius:50%;background:${rankColor};color:#fff;font-weight:700;font-size:0.85rem;">
                            ${i + 1}
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-semibold text-dark text-truncate" title="${escapeHtml(v.title)}">
                                ${escapeHtml(v.title)}
                            </div>
                            <div class="text-muted text-truncate" style="font-size: 0.72rem;" title="${escapeHtml(v.course || '')}">
                                ${escapeHtml(v.course || '—')}
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <span class="score-pill"><i class="fas fa-eye me-1"></i>${fmt(v.views)}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }

    const tbody = document.getElementById('videoViewsTableBody');
    if (!tbody) return;

    if (!vv.videos || vv.videos.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">No videos found.</td></tr>`;
        return;
    }

    tbody.innerHTML = vv.videos.map((v, i) => `
        <tr>
            <td class="text-muted">${i + 1}</td>
            <td>
                <div class="fw-semibold text-dark">${escapeHtml(v.title)}</div>
                <div class="text-muted" style="font-size: 0.7rem;">${escapeHtml(v.lesson_id)}</div>
            </td>
            <td class="text-muted" style="font-size: 0.8rem;">${escapeHtml(v.course || '—')}</td>
            <td class="text-center activity-cell">${fmt(v.views)}</td>
        </tr>
    `).join('');
}

function renderArticleViews(data) {
    const av = data.article_views || { total: 0, top_article: null, top_articles: [], articles: [] };

    const topContainer = document.getElementById('topArticlesContainer');
    if (topContainer) {
        const top = av.top_articles || [];
        if (top.length === 0) {
            topContainer.innerHTML = `<p class="text-muted text-center py-4 mb-0">No article views recorded yet.</p>`;
        } else {
            topContainer.innerHTML = top.map((a, i) => {
                const rankColor = ['#dc3545', '#fd7e14', '#ffc107', '#0d6efd'][i] || '#6c757d';
                return `
                    <div class="d-flex align-items-center gap-3 py-2 ${i < top.length - 1 ? 'border-bottom' : ''}">
                        <div class="d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:32px;height:32px;border-radius:50%;background:${rankColor};color:#fff;font-weight:700;font-size:0.85rem;">
                            ${i + 1}
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-semibold text-dark text-truncate" title="${escapeHtml(a.title)}">
                                ${escapeHtml(a.title)}
                            </div>
                            <div class="text-muted text-truncate" style="font-size: 0.72rem;" title="${escapeHtml(a.category || a.author || '')}">
                                ${escapeHtml(a.category || a.author || '—')}
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <span class="score-pill"><i class="fas fa-eye me-1"></i>${fmt(a.views)}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }

    const tbody = document.getElementById('articleViewsTableBody');
    if (!tbody) return;

    if (!av.articles || av.articles.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">No articles found.</td></tr>`;
        return;
    }

    tbody.innerHTML = av.articles.map((a, i) => `
        <tr>
            <td class="text-muted">${i + 1}</td>
            <td>
                <div class="fw-semibold text-dark">${escapeHtml(a.title)}</div>
                <div class="text-muted" style="font-size: 0.7rem;">${escapeHtml(a.slug)}</div>
            </td>
            <td class="text-muted" style="font-size: 0.8rem;">${escapeHtml(a.category || '—')}</td>
            <td class="text-center activity-cell">${fmt(a.views)}</td>
        </tr>
    `).join('');
}

function renderCharts(data) {
    const isDark    = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const textColor = isDark ? '#aaa' : '#666';
    const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
    Chart.defaults.color = textColor;
    Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";

    const lightPalette = ['#8ab4ff', '#75c594', '#ffcc66', '#f69595', '#b0b7bd', '#96e6c9'];

    const byType = data.engagement_by_type || [];
    destroyChart('byType');
    charts.byType = new Chart(document.getElementById('chartByType'), {
        type: 'bar',
        data: {
            labels: byType.map(x => x.label),
            datasets: [{
                data: byType.map(x => x.value),
                backgroundColor: byType.map(x => x.color),
                hoverBackgroundColor: byType.map(x => x.color),
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor } },
                x: { grid: { display: false } }
            }
        }
    });

    const top = data.top_users || [];
    destroyChart('topUsers');
    charts.topUsers = new Chart(document.getElementById('chartTopUsers'), {
        type: 'bar',
        data: {
            labels: top.map(x => x.username),
            datasets: [{
                data: top.map(x => x.engagement_score),
                backgroundColor: '#8ab4ff',
                hoverBackgroundColor: '#6ba3ff',
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { color: gridColor } },
                y: { grid: { display: false } }
            }
        }
    });

    const byRole = data.engagement_by_role || [];
    destroyChart('byRole');
    charts.byRole = new Chart(document.getElementById('chartByRole'), {
        type: 'doughnut',
        data: {
            labels: byRole.map(x => `${x.role} (${x.users} users)`),
            datasets: [{
                data: byRole.map(x => x.total),
                backgroundColor: lightPalette,
                hoverBackgroundColor: lightPalette,
                borderWidth: 2,
                borderColor: isDark ? '#0a0a0a' : '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 12 } } },
            cutout: '60%'
        }
    });

    const overTime = data.over_time || { labels: [], values: [] };
    destroyChart('overTime');
    charts.overTime = new Chart(document.getElementById('chartOverTime'), {
        type: 'line',
        data: {
            labels: overTime.labels,
            datasets: [{
                label: 'Activity log entries',
                data: overTime.values,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.10)',
                pointBackgroundColor: '#0d6efd',
                pointBorderColor: isDark ? '#0a0a0a' : '#fff',
                fill: true,
                tension: 0.3,
                pointRadius: 2,
                pointHoverRadius: 4,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor } },
                x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } }
            }
        }
    });
}

function destroyChart(name) {
    if (charts[name]) {
        charts[name].destroy();
        charts[name] = null;
    }
}

async function exportReport(type) {
    const btn = document.getElementById(type === 'pdf' ? 'btnExportPdf' : 'btnExportExcel');
    const originalHtml = btn ? btn.innerHTML : null;

    const qs  = buildQuery();
    const url = type === 'excel'
        ? '/admin/api/user-activities/export/excel?' + qs
        : '/admin/api/user-activities/export/pdf?' + qs;

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Generating ${type.toUpperCase()}...`;
    }

    try {
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });

        const contentType = (res.headers.get('Content-Type') || '').toLowerCase();

        if (contentType.includes('application/json')) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.message || `Server error (${res.status})`);
        }

        if (!res.ok) throw new Error(`HTTP ${res.status} ${res.statusText}`);

        if (contentType.includes('text/html')) {
            const blob = await res.blob();
            const htmlUrl = URL.createObjectURL(blob);
            window.open(htmlUrl, '_blank');
            setTimeout(() => URL.revokeObjectURL(htmlUrl), 60_000);
            showToast('PDF engine not installed — opened printable view. Use Ctrl+P → Save as PDF.', 'info');
            return;
        }

        const blob = await res.blob();
        const filename = getFilenameFromResponse(res)
            || ('user_activity_report_'
                + new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-')
                + (type === 'excel' ? '.xlsx' : '.pdf'));

        const objUrl = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = objUrl;
        a.download = filename;
        a.rel = 'noopener';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(objUrl), 1500);

        showToast(`${type.toUpperCase()} downloaded`, 'success');
    } catch (e) {
        console.error(`${type} export failed:`, e);
        showToast(`Export failed: ${e.message}`, 'danger');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
}

function getFilenameFromResponse(res) {
    const cd = res.headers.get('Content-Disposition') || '';
    const m = cd.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
    return m ? decodeURIComponent(m[1]) : null;
}

function fmt(n) {
    return new Intl.NumberFormat('en-US').format(n);
}

function formatDate(iso) {
    if (!iso) return '—';
    try {
        const d = new Date(iso);
        if (isNaN(d.getTime())) return '—';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    } catch { return '—'; }
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    const id = 'toast-' + Date.now();
    const html = `
        <div id="${id}" class="toast show align-items-center text-white bg-${type === 'danger' ? 'danger' : type === 'success' ? 'success' : 'dark'} border-0" role="alert" style="border-radius: 10px;">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${escapeHtml(message)}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    setTimeout(() => {
        const el = document.getElementById(id);
        if (el) el.remove();
    }, 5000);
}
</script>
@endpush
