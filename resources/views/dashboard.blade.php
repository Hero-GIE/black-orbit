@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')

@if (session('success'))
    <div id="loginToast" style="
        position: fixed; top: 20px; right: 20px; z-index: 99999;
        padding: 10px 18px;
        border-radius: 10px;
        background: #1a1a1a;
        color: white;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.06);
        animation: slideInRight 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        max-width: 400px;
        font-family: 'Segoe UI', system-ui, sans-serif;
        display: flex;
        align-items: center;
        gap: 10px;
    ">
        <div style="
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        ">
            <i class="fas fa-check-circle" style="font-size: 14px; color: #4ade80;"></i>
        </div>

        <!-- Message -->
        <div style="flex: 1; font-size: 13px; font-weight: 400; color: white; letter-spacing: 0.2px; line-height: 1.4;">
            {{ session('success') }}
        </div>

        <!-- Close Button -->
        <button type="button" onclick="dismissToast()" style="
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.4);
            cursor: pointer;
            font-size: 14px;
            padding: 2px 6px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        " onmouseover="this.style.color='rgba(255,255,255,0.8)'" onmouseout="this.style.color='rgba(255,255,255,0.4)'">
            <i class="fas fa-times"></i>
        </button>
    </div>
@endif

<style>
    /* Toast Animations */
    @keyframes slideInRight {
        from { transform: translateX(120%) scale(0.9); opacity: 0; }
        to { transform: translateX(0) scale(1); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0) scale(1); opacity: 1; }
        to { transform: translateX(120%) scale(0.9); opacity: 0; }
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes skeleton-pulse {
        0% { opacity: 0.6; }
        50% { opacity: 1; }
        100% { opacity: 0.6; }
    }

    /* Skeleton Loading */
    .skeleton-box {
        display: block;
        background-color: #e9ecef;
        border-radius: 4px;
        animation: skeleton-pulse 1.5s infinite ease-in-out;
    }

    /* Hover Cards */
    .hover-card {
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1) !important;
    }

    /* Gradient Background */
    .bg-gradient-dark {
        background: linear-gradient(135deg, #000000 0%, #333333 100%);
    }
    .bg-gradient-dark .btn-light {
        background: rgba(255,255,255,0.9);
        border: none;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .bg-gradient-dark .btn-light:hover {
        background: white;
        transform: scale(1.02);
    }

    .card-body.d-flex {
        min-height: 180px;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0,0,0,0.02);
    }

    .activity-scroll {
        max-height: 600px;
        overflow-y: auto;
    }

    .activity-scroll thead th {
        position: sticky;
        top: 0;
        background-color: #ffffff;
        z-index: 10;
        box-shadow: inset 0 -1px 0 #dee2e6;
    }

    .activity-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .activity-scroll::-webkit-scrollbar-thumb {
        background-color: #d1d5db;
        border-radius: 6px;
    }
    .activity-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
</style>

<div id="dashboard-skeleton" class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="skeleton-box" style="width: 200px; height: 24px; margin-bottom: 8px;"></div>
            <div class="skeleton-box" style="width: 150px; height: 16px;"></div>
        </div>
        <div class="skeleton-box" style="width: 180px; height: 35px; border-radius: 8px;"></div>
    </div>

    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        @for($i = 0; $i < 5; $i++)
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="w-75">
                                <div class="skeleton-box" style="width: 80px; height: 12px; margin-bottom: 8px;"></div>
                                <div class="skeleton-box" style="width: 50px; height: 24px; margin-bottom: 8px;"></div>
                                <div class="skeleton-box" style="width: 60px; height: 12px;"></div>
                            </div>
                            <div class="skeleton-box" style="width: 40px; height: 40px; border-radius: 12px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    <div class="row g-3 mb-4">
        @for($i = 0; $i < 6; $i++)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4 d-flex flex-column justify-content-center align-items-center" style="min-height: 180px;">
                        <div class="skeleton-box" style="width: 60px; height: 60px; border-radius: 50%; margin-bottom: 16px;"></div>
                        <div class="skeleton-box" style="width: 120px; height: 20px; margin-bottom: 12px;"></div>
                        <div class="skeleton-box" style="width: 80px; height: 16px; border-radius: 20px;"></div>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="skeleton-box" style="width: 100%; height: 350px; border-radius: 8px;"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="skeleton-box" style="width: 100%; height: 350px; border-radius: 8px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="skeleton-box" style="width: 100%; height: 300px; border-radius: 8px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="dashboard-content" class="container-fluid py-2" style="display: none; animation: fadeIn 0.5s ease-in-out;">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Admin Dashboard</h4>
            <p class="text-muted mb-0">Welcome back, Administrator!</p>
        </div>
        <div>
            <span class="badge bg-dark p-2">
                <i class="fas fa-calendar me-2"></i>{{ now()->format('l, F j, Y') }}
            </span>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted text-uppercase mb-1" style="font-size: 0.7rem; font-weight: 600;">Chats</p>
                            <h4 class="mb-0 fw-bold" id="totalChats">0</h4>
                            <small class="text-dark" id="newChatsToday">+0 today</small>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-2 rounded-3">
                            <i class="fas fa-comment-dots fa-lg text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted text-uppercase mb-1" style="font-size: 0.7rem; font-weight: 600;">Word Search</p>
                            <h4 class="mb-0 fw-bold" id="wordSearchProgress">0%</h4>
                            <small class="text-muted" id="wordsFound">0 found</small>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-2 rounded-3">
                            <i class="fas fa-search fa-lg text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted text-uppercase mb-1" style="font-size: 0.7rem; font-weight: 600;">Courses</p>
                            <h4 class="mb-0 fw-bold" id="totalCourses">0</h4>
                            <small class="text-dark" id="newCoursesToday">+0 today</small>
                        </div>
                        <div class="bg-success bg-opacity-10 p-2 rounded-3">
                            <i class="fas fa-book fa-lg text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted text-uppercase mb-1" style="font-size: 0.7rem; font-weight: 600;">Enrollments</p>
                            <h4 class="mb-0 fw-bold" id="totalEnrollments">0</h4>
                            <small class="text-dark" id="newEnrollmentsToday">+0 today</small>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-2 rounded-3">
                            <i class="fas fa-user-graduate fa-lg text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted text-uppercase mb-1" style="font-size: 0.7rem; font-weight: 600;">Users</p>
                            <h4 class="mb-0 fw-bold" id="totalUsers">0</h4>
                            <small class="text-dark" id="newUsersToday">+0 today</small>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-2 rounded-3">
                            <i class="fas fa-users fa-lg text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 hover-card" onclick="navigateTo('chats')">
                <div class="card-body text-center p-4 d-flex flex-column justify-content-center align-items-center">
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="fas fa-comment-dots fa-lg text-danger"></i>
                    </div>
                    <h6 class="fw-bold mb-2">Chats</h6>
                    <span class="badge bg-dark" id="chatBadge">0 conversations</span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 hover-card" onclick="navigateTo('cosmic')">
                <div class="card-body text-center p-4 d-flex flex-column justify-content-center align-items-center">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="fas fa-search fa-lg text-warning"></i>
                    </div>
                    <h6 class="fw-bold mb-3">Word Search Progress</h6>
                    <div class="progress w-100" style="height: 8px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 0%;" id="wordSearchBar"></div>
                    </div>
                    <small class="mt-2 text-muted" id="wordSearchPercent">0%</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 hover-card" onclick="navigateTo('courses')">
                <div class="card-body text-center p-4 d-flex flex-column justify-content-center align-items-center">
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="fas fa-book fa-lg text-success"></i>
                    </div>
                    <h6 class="fw-bold mb-2">Courses</h6>
                    <span class="badge bg-dark" id="courseBadge">0 courses</span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 hover-card" onclick="navigateTo('enrollments')">
                <div class="card-body text-center p-4 d-flex flex-column justify-content-center align-items-center">
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="fas fa-user-graduate fa-lg text-danger"></i>
                    </div>
                    <h6 class="fw-bold mb-2">Enrollments</h6>
                    <span class="badge bg-dark" id="enrollmentBadge">0 enrollments</span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 hover-card" onclick="navigateTo('users')">
                <div class="card-body text-center p-4 d-flex flex-column justify-content-center align-items-center">
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle d-inline-block mb-3">
                        <i class="fas fa-users fa-lg text-warning"></i>
                    </div>
                    <h6 class="fw-bold mb-2">Users</h6>
                    <span class="badge bg-dark" id="userBadge">0 users</span>
                </div>
            </div>
        </div>

        {{-- Quick Actions Card  --}}
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body text-center text-dark p-4 d-flex flex-column justify-content-center align-items-center">
                    <div class="p-3 rounded-circle d-inline-block mb-3 bg-warning bg-opacity-10">
                        <i class="fas fa-bolt fa-lg text-warning"></i>
                    </div>
                    <h6 class="fw-bold mb-3 text-dark">Quick Actions</h6>
                    <div class="d-grid gap-2 w-100">
                        <!-- Light Red Button -->
                        <button class="btn btn-sm" style="background-color: #fdecec; color: #dc3545; border: 1px solid #f8c8c8; font-weight: 600;" onclick="navigateTo('courses')">
                            <i class="fas fa-plus me-2"></i>Add Course
                        </button>
                        <!-- Light Green Button -->
                        <button class="btn btn-sm" style="background-color: #e6f7ed; color: #198754; border: 1px solid #b7e4c7; font-weight: 600;" onclick="navigateTo('users')">
                            <i class="fas fa-user-plus me-2"></i>Add User
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2"></i>Platform Analytics</h6>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm w-auto" id="analyticsType">
                            <option value="all" selected>All</option>
                            <option value="chats">Chats</option>
                            <option value="courses">Courses</option>
                            <option value="users">Users</option>
                        </select>
                        <select class="form-select form-select-sm w-auto" id="analyticsPeriod">
                            <option value="7">Last 7 days</option>
                            <option value="30" selected>Last 30 days</option>
                            <option value="90">Last 90 days</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="analyticsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-pie-chart me-2"></i>Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="distributionChart" height="200"></canvas>
                    <div class="d-flex justify-content-center gap-3 mt-3 flex-wrap" id="distributionLegend">
                        <div>
                            <span class="badge bg-danger bg-opacity-50 me-1">&nbsp;</span>
                            <span class="small">Chats</span>
                        </div>
                        <div>
                            <span class="badge bg-warning bg-opacity-50 me-1">&nbsp;</span>
                            <span class="small">Courses</span>
                        </div>
                        <div>
                            <span class="badge bg-success bg-opacity-50 me-1">&nbsp;</span>
                            <span class="small">Users</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

 <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-clock me-2"></i>Recent Activity</h6>
                    <button class="btn btn-sm btn-outline-dark" onclick="refreshActivity()">
                        <i class="fas fa-sync-alt me-1"></i>Refresh
                    </button>
                </div>
                <div class="card-body p-0">
                    <!-- Added activity-scroll class here -->
                    <div class="table-responsive activity-scroll">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Type</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody id="recentActivityTable">
                                <tr>
                                    <td colspan="3" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-lg mb-3"></i>
                                            <p class="mb-0">No recent updates</p>
                                            <small class="text-muted">Loading activity...</small>
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
</div>

<script>

function dismissToast() {
    const toast = document.getElementById('loginToast');
    if (toast) {
        toast.style.animation = 'slideOutRight 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards';
        setTimeout(function() {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 400);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const toast = document.getElementById('loginToast');
    if (toast) {
        setTimeout(function() {
            toast.style.animation = 'slideOutRight 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards';
            setTimeout(function() {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 500);
        }, 5000);
    }
});

let analyticsChart = null;
let distributionChart = null;
let refreshInterval = null;
let isDashboardVisible = false;

function navigateTo(page) {
    window.location.href = `/admin/${page}`;
}

function revealDashboard() {
    if (isDashboardVisible) return;
    isDashboardVisible = true;
    const skeleton = document.getElementById('dashboard-skeleton');
    const content = document.getElementById('dashboard-content');
    if (skeleton) skeleton.style.display = 'none';
    if (content) content.style.display = 'block';

    requestAnimationFrame(() => {
        if (analyticsChart) analyticsChart.resize();
        if (distributionChart) distributionChart.resize();
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initAnalyticsChart();
    initDistributionChart();

    const skeletonTimeout = setTimeout(revealDashboard, 2500);

    Promise.all([
        loadDashboardStats(),
        loadRecentActivity(),
        updateAnalyticsChart(30, 'all'),
        updateDistributionChart()
    ]).then(() => {
        clearTimeout(skeletonTimeout);
        revealDashboard();
    }).catch((err) => {
        console.error('Dashboard data failed to load:', err);
        clearTimeout(skeletonTimeout);
        revealDashboard();
    });

    refreshInterval = setInterval(function() {
        loadDashboardStats();
        loadRecentActivity();
    }, 60000);

    const typeSelect = document.getElementById('analyticsType');
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            const period = document.getElementById('analyticsPeriod').value;
            updateAnalyticsChart(period, this.value);
        });
    }

    const periodSelect = document.getElementById('analyticsPeriod');
    if (periodSelect) {
        periodSelect.addEventListener('change', function() {
            const type = document.getElementById('analyticsType').value;
            updateAnalyticsChart(this.value, type);
        });
    }
});

window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

async function loadDashboardStats() {
    try {
        const response = await fetch('/admin/api/dashboard/stats');
        const data = await response.json();

        if (data.code === 200 && data.data) {
            updateStatsUI(data.data);
        } else {
            console.error('Invalid API response:', data);
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

function updateStatsUI(stats) {
    try {
        document.getElementById('totalChats').textContent = formatNumber(stats.chats?.total || 0);
        document.getElementById('totalCourses').textContent = formatNumber(stats.courses?.total || 0);
        document.getElementById('totalEnrollments').textContent = formatNumber(stats.enrollments?.total || 0);
        document.getElementById('totalUsers').textContent = formatNumber(stats.users?.total || 0);

        document.getElementById('newChatsToday').innerHTML = `+${formatNumber(stats.chats?.today || 0)} today`;
        document.getElementById('newCoursesToday').innerHTML = `+${formatNumber(stats.courses?.today || 0)} today`;
        document.getElementById('newEnrollmentsToday').innerHTML = `+${formatNumber(stats.enrollments?.today || 0)} today`;
        document.getElementById('newUsersToday').innerHTML = `+${formatNumber(stats.users?.today || 0)} today`;

        document.getElementById('chatBadge').textContent = `${formatNumber(stats.chats?.total || 0)} conversations`;
        document.getElementById('courseBadge').textContent = `${formatNumber(stats.courses?.total || 0)} courses`;
        document.getElementById('enrollmentBadge').textContent = `${formatNumber(stats.enrollments?.total || 0)} enrollments`;
        document.getElementById('userBadge').textContent = `${formatNumber(stats.users?.total || 0)} users`;

        const wordsFound = stats.wordSearch?.found || 0;
        const totalWords = stats.wordSearch?.total || 100;
        const percentage = Math.min((wordsFound / totalWords) * 100, 100);

        document.getElementById('wordSearchProgress').textContent = `${percentage.toFixed(0)}%`;
        document.getElementById('wordsFound').textContent = `${formatNumber(wordsFound)} found`;
        document.getElementById('wordSearchBar').style.width = `${percentage}%`;
        document.getElementById('wordSearchPercent').textContent = `${percentage.toFixed(0)}% Complete`;

    } catch (error) {
        console.error('Error updating stats UI:', error);
    }
}

function initAnalyticsChart() {
    const canvas = document.getElementById('analyticsChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    analyticsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Chats',
                    data: [],
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.4,
                    fill: true,
                    hidden: false
                },
                {
                    label: 'Courses',
                    data: [],
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.4,
                    fill: true,
                    hidden: false
                },
                {
                    label: 'Users',
                    data: [],
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.4,
                    fill: true,
                    hidden: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 6
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

async function updateAnalyticsChart(days, type = 'all') {
    if (!analyticsChart) return;

    try {
        const response = await fetch(`/admin/api/analytics?days=${days}&type=${type}`);
        const data = await response.json();

        if (data.code === 200 && data.data) {
            analyticsChart.data.labels = data.data.labels || [];

            if (type === 'chats') {
                analyticsChart.data.datasets[0].hidden = false;
                analyticsChart.data.datasets[1].hidden = true;
                analyticsChart.data.datasets[2].hidden = true;
                analyticsChart.data.datasets[0].data = data.data.chats || [];
            } else if (type === 'courses') {
                analyticsChart.data.datasets[0].hidden = true;
                analyticsChart.data.datasets[1].hidden = false;
                analyticsChart.data.datasets[2].hidden = true;
                analyticsChart.data.datasets[1].data = data.data.courses || [];
            } else if (type === 'users') {
                analyticsChart.data.datasets[0].hidden = true;
                analyticsChart.data.datasets[1].hidden = true;
                analyticsChart.data.datasets[2].hidden = false;
                analyticsChart.data.datasets[2].data = data.data.users || [];
            } else {
                analyticsChart.data.datasets[0].hidden = false;
                analyticsChart.data.datasets[1].hidden = false;
                analyticsChart.data.datasets[2].hidden = false;
                analyticsChart.data.datasets[0].data = data.data.chats || [];
                analyticsChart.data.datasets[1].data = data.data.courses || [];
                analyticsChart.data.datasets[2].data = data.data.users || [];
            }

            analyticsChart.update();
        }
    } catch (error) {
        console.error('Error loading analytics:', error);
    }
}

function initDistributionChart() {
    const canvas = document.getElementById('distributionChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    distributionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Chats', 'Courses', 'Users'],
            datasets: [{
                data: [0, 0, 0],

                backgroundColor: ['#f69595', '#ffcc66', '#75c594'],
                hoverBackgroundColor: ['#dc3545', '#ffc107', '#198754'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '65%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return `${label}: ${formatNumber(value)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

async function updateDistributionChart() {
    if (!distributionChart) return;

    try {
        const response = await fetch('/admin/api/distribution');
        const data = await response.json();

        if (data.code === 200 && data.data) {
            distributionChart.data.datasets[0].data = [
                data.data.chats || 0,
                data.data.courses || 0,
                data.data.users || 0
            ];
            distributionChart.update();
        }
    } catch (error) {
        console.error('Error loading distribution:', error);
    }
}

async function loadRecentActivity() {
    try {
        const response = await fetch('/admin/api/dashboard/activity');
        const data = await response.json();

        if (data.code === 200) {
            renderRecentActivity(data.data || []);
        }
    } catch (error) {
        console.error('Error loading activity:', error);
    }
}

function renderRecentActivity(activities) {
    const tableBody = document.getElementById('recentActivityTable');
    if (!tableBody) return;

    if (!activities || activities.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="3" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-inbox fa-lg mb-3"></i>
                        <p class="mb-0">No recent updates</p>
                        <small class="text-muted">Changes will appear here</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    activities.slice(0, 10).forEach(activity => {
        const typeInfo = activity.typeInfo || { icon: 'fas fa-bell', color: '#6b7280', bgColor: '#f3f4f6', label: 'Activity' };

        const actionMessage = activity.title || 'Activity occurred';
        const description = activity.description || '';
        const timeAgo = activity.timeAgo || timeSince(new Date(activity.createdAt));

        html += `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="p-2 rounded-circle me-3" style="background-color: ${typeInfo.bgColor};">
                            <i class="${typeInfo.icon}" style="color: ${typeInfo.color};"></i>
                        </div>
                        <div>
                            <p class="mb-0 fw-bold text-dark">${escapeHtml(actionMessage)}</p>
                            ${description ? `<small class="text-muted">${escapeHtml(description)}</small>` : ''}
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-dark">${escapeHtml(typeInfo.label || activity.type)}</span>
                </td>
                <td>
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>${escapeHtml(timeAgo)}
                    </small>
                </td>
            </tr>
        `;
    });

    tableBody.innerHTML = html;
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getActivityIcon(type) {
    const icons = {
        'chat': 'fas fa-comment',
        'course': 'fas fa-book',
        'enrollment': 'fas fa-user-graduate',
        'user': 'fas fa-user',
        'wordsearch': 'fas fa-search'
    };
    return icons[type] || 'fas fa-circle';
}

function getActivityColor(type) {

    const colors = {
        'chat': 'danger',
        'course': 'success',
        'enrollment': 'warning',
        'user': 'success',
        'wordsearch': 'warning'
    };
    return colors[type] || 'dark';
}

function timeSince(date) {
    if (!(date instanceof Date) || isNaN(date)) {
        return 'Invalid date';
    }

    const seconds = Math.floor((new Date() - date) / 1000);

    const intervals = [
        { label: 'year', seconds: 31536000 },
        { label: 'month', seconds: 2592000 },
        { label: 'day', seconds: 86400 },
        { label: 'hour', seconds: 3600 },
        { label: 'minute', seconds: 60 },
        { label: 'second', seconds: 1 }
    ];

    for (const interval of intervals) {
        const count = Math.floor(seconds / interval.seconds);
        if (count > 0) {
            return `${count} ${interval.label}${count !== 1 ? 's' : ''} ago`;
        }
    }

    return 'just now';
}

function refreshActivity() {
    loadRecentActivity();
    showToast('Activity feed refreshed', 'success');
}

function refreshAll() {
    loadDashboardStats();
    loadRecentActivity();
    updateDistributionChart();
    showToast('Dashboard refreshed', 'success');
}

function showToast(message, type = 'dark') {
    const toastId = 'toast-' + Date.now();
    const toast = `
        <div id="${toastId}" class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
            <div class="toast show align-items-center text-white bg-dark border-0">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>${escapeHtml(message)}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', toast);

    setTimeout(() => {
        const toastElement = document.getElementById(toastId);
        if (toastElement) {
            toastElement.remove();
        }
    }, 5000);
}

window.navigateTo = navigateTo;
window.refreshActivity = refreshActivity;
window.refreshAll = refreshAll;
window.showToast = showToast;
window.loadDashboardStats = loadDashboardStats;
window.loadRecentActivity = loadRecentActivity;
window.dismissToast = dismissToast;
</script>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
