@extends('layouts.base')

@section('user')

<div class="topbar d-print-none">
    <div class="topbar-inner">
        {{-- Hamburger --}}
        <button class="topbar-icon-btn" id="togglemenu" type="button" title="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>

        {{-- Right controls --}}
        <div class="d-flex align-items-center gap-1">

            <a class="topbar-icon-btn" href="javascript:void(0);" id="light-dark-mode" title="Toggle theme">
                <i class="fas fa-moon dark-icon"></i>
                <i class="fas fa-sun light-icon"></i>
            </a>

            {{-- User button --}}
            <button class="user-toggle" type="button" id="userMenuBtn">
                <div class="user-avatar-circle">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-toggle-text d-none d-md-flex">
                    <span class="user-toggle-name">
                        {{ ucfirst(session('firebase_username', 'Administrator')) }}
                    </span>
                    <span class="user-toggle-role">
                        {{ session('firebase_role', 'Administrator') }}
                    </span>
                </div>
                <i class="fas fa-chevron-down caret-icon d-none d-md-inline-block"></i>
            </button>
        </div>
    </div>
</div>

<div class="udp-panel" id="userMenuPanel" role="menu" aria-hidden="true">
    <div class="udp-header">
        <div class="udp-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="udp-info">
            <div class="udp-email">
                <i class="fas fa-envelope me-2"></i>
                {{ session('firebase_email', 'admin@example.com') }}
            </div>
            <span class="udp-badge">
                <i class="fas fa-user-shield me-1"></i>
                {{ ucfirst(session('firebase_role', 'Administrator')) }}
            </span>
        </div>
    </div>

    <hr class="udp-divider">

    <div class="udp-actions">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="udp-logout-btn">
                <i class="fas fa-sign-out-alt me-2"></i>
                Sign Out
            </button>
        </form>
    </div>
</div>

<div id="main-wrapper">

    {{-- Sidebar --}}
    <nav class="startbar d-print-none">
        <div class="sb-brand">
            <a href="{{ route('admin.dashboard') }}">
                <img src="{{ asset('assets/img/black-orbit2.png') }}" alt="MABN" class="sb-logo-full">
                <img src="{{ asset('assets/img/black-orbit2.png') }}" alt="MABN" class="sb-logo-icon">
            </a>
        </div>

        <div class="sb-scroll">
            <ul class="sb-nav">
                <li class="sb-label"><span>Main Menu</span></li>

                {{-- Dashboard --}}
                <li>
                    <a class="sb-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                       href="{{ route('admin.dashboard') }}" data-label="Dashboard">
                        <i class="fas fa-chart-line"></i><span>Dashboard</span>
                    </a>
                </li>


                   {{-- Users --}}
                <li>
                    <a class="sb-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                       href="{{ route('admin.users.index') }}" data-label="Users">
                        <i class="fas fa-users"></i><span>Users</span>
                    </a>
                </li>


                  {{-- Personalities --}}
                <li>
                    <a class="sb-link {{ request()->routeIs('admin.personalities.*') ? 'active' : '' }}"
                       href="{{ route('admin.personalities.index') }}" data-label="Personalities">
                        <i class="fas fa-user-tie"></i><span>Personalities</span>
                    </a>
                </li>

                {{-- Chats --}}
                <li>
                    <a class="sb-link {{ request()->routeIs('admin.chats.*') ? 'active' : '' }}"
                       href="{{ route('admin.chats.index') }}" data-label="Chats">
                        <i class="fas fa-comment-dots"></i><span>Chats</span>
                    </a>
                </li>

                     {{-- Articles --}}
                <li>
                    <a class="sb-link {{ request()->routeIs('admin.articles.*') ? 'active' : '' }}"
                       href="{{ route('admin.articles.index') }}" data-label="Articles">
                        <i class="fas fa-newspaper"></i><span>Articles</span>
                    </a>
                </li>

                <li class="sb-divider"></li>
                <li class="sb-label"><span>Management</span></li>

                      <li>
                    <a class="sb-link {{ request()->routeIs('admin.facts.*') ? 'active' : '' }}"
                       href="{{ route('admin.facts.index') }}" data-label="Facts">
                        <i class="fas fa-lightbulb"></i><span>Facts</span>
                    </a>
                </li>

                {{-- Courses --}}
                <li>
                    <a class="sb-link {{ request()->routeIs('admin.courses.*') ? 'active' : '' }}"
                       href="{{ route('admin.courses.index') }}" data-label="Courses">
                        <i class="fas fa-book"></i><span>Courses</span>
                    </a>
                </li>

                  {{-- Cosmic Word Search Progress --}}
                <li>
                    <a class="sb-link {{ request()->routeIs('admin.cosmic.*') ? 'active' : '' }}"
                       href="{{ route('admin.cosmic.index') }}" data-label="Cosmic Word Search">
                        <i class="fas fa-search"></i><span>Word Search Progress</span>
                    </a>
                </li>

                {{-- Enrollments --}}
                <li>
                    <a class="sb-link {{ request()->routeIs('admin.enrollments.*') ? 'active' : '' }}"
                       href="{{ route('admin.enrollments.index') }}" data-label="Enrollments">
                        <i class="fas fa-user-graduate"></i><span>Enrollments</span>
                    </a>
                </li>

                {{-- Personalities --}}
                <li>
                    <a class="sb-link {{ request()->routeIs('admin.players.*') ? 'active' : '' }}"
                       href="{{ route('admin.players.index') }}" data-label="Players">
                        <i class="fas fa-user-tie"></i><span>Players</span>
                    </a>
                </li>
                {{-- Notifications --}}
                  <li>
                    <a class="sb-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}"
                       href="{{ route('admin.notifications.index') }}" data-label="Notifications">
                        <i class="fas fa-bell"></i><span>Notifications</span>
                    </a>
                </li>


            </ul>
        </div>
    </nav>

    {{-- Mobile overlay --}}
    <div class="startbar-overlay"></div>

    {{-- Page content --}}
    <div class="content-wrapper">
        <div class="page-content">
            @yield('content')
        </div>
    </div>

</div>

@stack('scripts')
@yield('scripts')

@endsection

@push('styles')
<style>
    /* Sidebar Badges */
    .sb-badge {
        margin-left: auto;
        background: #000000;
        color: #ffffff;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        min-width: 24px;
        text-align: center;
    }

    .sb-link.active .sb-badge {
        background: #ffffff;
        color: #000000;
    }

    [data-bs-theme="dark"] .sb-badge {
        background: #ffffff;
        color: #000000;
    }

    [data-bs-theme="dark"] .sb-link.active .sb-badge {
        background: #000000;
        color: #ffffff;
    }

    :root {
        --sb-width:        260px;
        --sb-collapsed:    68px;
        --topbar-h:        64px;
        --sb-bg:           #ffffff;
        --sb-border:       #e8e8e8;
        --topbar-bg:       #ffffff;
        --topbar-border:   #e8e8e8;
        --page-bg:         #f4f4f4;
        --link-color:      #555555;
        --link-hover-bg:   #f0f0f0;
        --link-active-bg:  #000000;
        --link-active-clr: #ffffff;
        --label-clr:       #999999;
        --text:            #111111;
        --muted:           #777777;
        --accent:          #000000;
        --accent-lt:       #f5f5f5;
        --dd-bg:           #ffffff;
        --dd-border:       #e8e8e8;
        --dd-shadow:       0 12px 40px rgba(0,0,0,.14);
    }

    [data-bs-theme="dark"] {
        --sb-bg:           #0a0a0a;
        --sb-border:       #222222;
        --topbar-bg:       #0a0a0a;
        --topbar-border:   #222222;
        --page-bg:         #000000;
        --link-color:      #aaaaaa;
        --link-hover-bg:   #1a1a1a;
        --link-active-bg:  #ffffff;
        --link-active-clr: #000000;
        --label-clr:       #555555;
        --text:            #e0e0e0;
        --muted:           #888888;
        --accent:          #ffffff;
        --accent-lt:       #1a1a1a;
        --dd-bg:           #111111;
        --dd-border:       #222222;
        --dd-shadow:       0 12px 40px rgba(0,0,0,.8);
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
        margin: 0;
        background: var(--page-bg);
        font-family: 'Segoe UI', system-ui, sans-serif;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    #main-wrapper { display: flex; min-height: 100vh; }

    .topbar {
        position: fixed;
        top: 0; right: 0;
        left: var(--sb-width);
        height: var(--topbar-h);
        background: var(--topbar-bg);
        border-bottom: 1px solid var(--topbar-border);
        z-index: 900;
        transition: left .3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.3s ease;
    }

    body.sidebar-collapsed .topbar { left: var(--sb-collapsed); }

    @media (max-width: 992px) { .topbar { left: 0 !important; } }

    .topbar-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 100%;
        padding: 0 16px;
    }

    .topbar-icon-btn {
        width: 40px; height: 40px;
        border-radius: 10px;
        background: none; border: none;
        display: inline-flex; align-items: center; justify-content: center;
        color: var(--link-color);
        font-size: 1.05rem;
        cursor: pointer;
        text-decoration: none;
        transition: background .2s, color .2s;
    }
    .topbar-icon-btn:hover { background: var(--link-hover-bg); color: var(--accent); }

    .light-icon { display: none; }
    .dark-icon  { display: inline-block; }
    [data-bs-theme="dark"] .light-icon { display: inline-block; }
    [data-bs-theme="dark"] .dark-icon  { display: none; }

    .user-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 10px;
        background: none; border: none;
        cursor: pointer;
        color: var(--text);
        transition: background .2s;
    }
    .user-toggle:hover,
    .user-toggle.udp-open { background: var(--link-hover-bg); }
    .user-toggle:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

    .user-avatar-circle {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: var(--accent-lt);
        color: var(--accent);
        border: 2px solid var(--accent);
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem;
        font-weight: 600;
        flex-shrink: 0;
    }

    .user-toggle-text {
        flex-direction: column;
        line-height: 1;
        text-align: left;
    }
    .user-toggle-name { font-size: .84rem; font-weight: 600; color: var(--text); }
    .user-toggle-role { font-size: .7rem; color: var(--muted); margin-top: 2px; }

    .caret-icon {
        font-size: .6rem; color: var(--muted);
        transition: transform .2s;
    }
    .user-toggle.udp-open .caret-icon { transform: rotate(180deg); }

    .udp-panel {
        position: absolute;
        min-width: 255px;
        background: var(--dd-bg);
        border: 1px solid var(--dd-border);
        border-radius: 14px;
        box-shadow: var(--dd-shadow);
        overflow: hidden;
        z-index: 9999;

        opacity: 0;
        transform: translateY(-8px);
        pointer-events: none;
        transition: opacity .15s ease, transform .15s ease;
    }

    .udp-panel.udp-visible {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }

    .udp-header {
        display: flex; align-items: center; gap: 12px;
        padding: 16px;
        background: var(--accent-lt);
    }

    .udp-avatar {
        width: 44px; height: 44px;
        border-radius: 50%;
        background: var(--accent); color: var(--sb-bg);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; font-weight: 600; flex-shrink: 0;
    }

    .udp-info { flex: 1; min-width: 0; }

    .udp-name {
        font-size: .9rem; font-weight: 700; color: var(--text);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .udp-email {
        font-size: .73rem; color: var(--muted);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        margin-top: 2px;
    }
    .udp-badge {
        display: inline-block;
        font-size: .63rem; font-weight: 600;
        padding: 2px 8px;
        background: var(--accent); color: var(--sb-bg);
        border-radius: 20px; margin-top: 5px; letter-spacing: .3px;
    }

    .udp-divider { margin: 0; border-color: var(--dd-border); }

    .udp-actions { padding: 8px; }

    .udp-logout-btn {
        display: flex; align-items: center; gap: 8px;
        width: 100%; padding: 10px 12px;
        background: none; border: none;
        border-radius: 8px;
        font-size: .875rem; font-weight: 500;
        color: #e53e3e; cursor: pointer;
        transition: background .2s, color .2s;
        text-align: left;
    }
    .udp-logout-btn:hover { background: #fff5f5; color: #c53030; }
    [data-bs-theme="dark"] .udp-logout-btn:hover {
        background: rgba(229,62,62,.12); color: #fc8181;
    }

    .startbar {
        position: fixed;
        top: 0; left: 0; bottom: 0;
        width: var(--sb-width);
        background: var(--sb-bg);
        border-right: 1px solid var(--sb-border);
        z-index: 1050;
        display: flex; flex-direction: column;
        transition: width .3s cubic-bezier(0.4, 0, 0.2, 1), transform .3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.3s ease;
    }

    @media (min-width: 993px) {
        body.sidebar-collapsed .startbar         { width: var(--sb-collapsed); }
        body.sidebar-collapsed .sb-link span,
        body.sidebar-collapsed .sb-label,
        body.sidebar-collapsed .sb-divider       { opacity: 0; pointer-events: none; width: 0; overflow: hidden; }
        body.sidebar-collapsed .sb-link          { justify-content: center; padding: 10px 0; margin: 0 8px; }
        body.sidebar-collapsed .sb-logo-full     { display: none; }
        body.sidebar-collapsed .sb-logo-icon     { display: block !important; }
    }

    @media (max-width: 992px) {
        .startbar              { transform: translateX(-100%); }
        .startbar.mobile-open  { transform: translateX(0); }
    }

    .sb-brand {
        height: 112px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px 20px;
        text-align: center;
        border-bottom: 1px solid var(--sb-border);
        flex-shrink: 0; overflow: hidden; white-space: nowrap;
    }

    .sb-logo-full { height: 200px; width: auto; max-width: 100%; max-height: 100%; object-fit: contain; }
    .sb-logo-icon { height: 40px; width: 40px; object-fit: contain; display: none; }

    .sb-scroll {
        flex: 1; overflow-y: auto; overflow-x: hidden; padding: 8px 0;
        scrollbar-width: thin; scrollbar-color: var(--sb-border) transparent;
    }
    .sb-scroll::-webkit-scrollbar       { width: 4px; }
    .sb-scroll::-webkit-scrollbar-thumb { background: var(--sb-border); border-radius: 4px; }

    .sb-nav { list-style: none; margin: 0; padding: 8px 0; }
    .sb-nav li { margin: 2px 0; }

    .sb-label { padding: 14px 22px 5px; overflow: hidden; }
    .sb-label span {
        font-size: .66rem; font-weight: 700; letter-spacing: .8px;
        color: var(--label-clr); text-transform: uppercase;
    }

    .sb-divider { margin: 10px 16px; border-top: 1px solid var(--sb-border); }

    .sb-link {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 16px; margin: 0 10px;
        border-radius: 10px;
        color: var(--link-color); text-decoration: none;
        font-size: .875rem; font-weight: 500;
        white-space: nowrap;
        transition: background .2s, color .2s, transform .15s, margin .3s, padding .3s;
    }
    .sb-link i { width: 20px; font-size: 1rem; text-align: center; flex-shrink: 0; }
    .sb-link span { transition: opacity .2s; }
    .sb-link:hover  { background: var(--link-hover-bg); color: var(--accent); transform: translateX(3px); }
    .sb-link.active { background: var(--link-active-bg); color: var(--link-active-clr); }
    .sb-link.active i { color: var(--link-active-clr); }

    @media (min-width: 993px) {
        body.sidebar-collapsed .sb-link { position: relative; }
        body.sidebar-collapsed .sb-link:hover::after {
            content: attr(data-label);
            position: absolute;
            left: calc(var(--sb-collapsed) + 4px);
            top: 50%; transform: translateY(-50%);
            background: #000000; color: #ffffff;
            font-size: .78rem; font-weight: 500;
            padding: 5px 10px; border-radius: 6px;
            white-space: nowrap; z-index: 9998;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(0,0,0,.2);
        }
        body.sidebar-collapsed .sb-link:hover::before {
            content: '';
            position: absolute;
            left: calc(var(--sb-collapsed) - 2px);
            top: 50%; transform: translateY(-50%);
            border: 5px solid transparent;
            border-right-color: #000000;
            z-index: 9999; pointer-events: none;
        }
    }

    .startbar-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.45);
        z-index: 1040; backdrop-filter: blur(2px);
    }
    .startbar-overlay.show { display: block; }

    .content-wrapper {
        margin-left: var(--sb-width);
        padding-top: var(--topbar-h);
        flex: 1; min-width: 0;
        transition: margin-left .3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    body.sidebar-collapsed .content-wrapper { margin-left: var(--sb-collapsed); }

    @media (max-width: 992px) { .content-wrapper { margin-left: 0 !important; } }

    .page-content {
        padding: 24px;
        min-height: calc(100vh - var(--topbar-h));
        background: var(--page-bg);
    }
</style>
@endpush
