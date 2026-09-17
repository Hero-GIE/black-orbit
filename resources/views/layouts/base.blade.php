<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light">

<head>
    <meta charset="utf-8" />
    <title>Black Orbit - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Join us to unite Africa by securing 10 million signatures for a borderless, free, and thriving continent.">
    <meta name="author" content="WOWLogbook ltd">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ asset('assets/img/black-orbit.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet" type="text/css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('head')
    @stack('styles')
</head>

<body>
    @yield('user')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const toggleBtn = document.getElementById('togglemenu');
            const startbar = document.querySelector('.startbar');
            const startbarOverlay = document.querySelector('.startbar-overlay');
            const MOBILE_BP = 992;

            function isMobile() {
                return window.innerWidth <= MOBILE_BP;
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (isMobile()) {
                        const isOpen = startbar.classList.contains('mobile-open');
                        startbar.classList.toggle('mobile-open', !isOpen);
                        if (startbarOverlay) {
                            startbarOverlay.classList.toggle('show', !isOpen);
                        }

                        const userPanel = document.getElementById('userMenuPanel');
                        if (userPanel) {
                            userPanel.classList.remove('udp-visible');
                        }
                    } else {
                        document.body.classList.toggle('sidebar-collapsed');
                    }
                });
            }

            if (startbarOverlay) {
                startbarOverlay.addEventListener('click', function () {
                    startbar.classList.remove('mobile-open');
                    startbarOverlay.classList.remove('show');
                });
            }

            window.addEventListener('resize', function () {
                if (!isMobile()) {
                    startbar.classList.remove('mobile-open');
                    if (startbarOverlay) {
                        startbarOverlay.classList.remove('show');
                    }
                }
            });
            const userBtn = document.getElementById('userMenuBtn');
            const userPanel = document.getElementById('userMenuPanel');

            if (userBtn && userPanel) {
                document.body.appendChild(userPanel);

                let isUserMenuOpen = false;

                function showUserMenu() {
                    const rect = userBtn.getBoundingClientRect();
                    userPanel.style.top = (rect.bottom + window.scrollY + 8) + 'px';
                    userPanel.style.right = (window.innerWidth - rect.right) + 'px';
                    userPanel.style.left = 'auto';
                    userPanel.classList.add('udp-visible');
                    userBtn.classList.add('udp-open');
                    userPanel.setAttribute('aria-hidden', 'false');
                    isUserMenuOpen = true;
                }

                function hideUserMenu() {
                    userPanel.classList.remove('udp-visible');
                    userBtn.classList.remove('udp-open');
                    userPanel.setAttribute('aria-hidden', 'true');
                    isUserMenuOpen = false;
                }

                userBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    isUserMenuOpen ? hideUserMenu() : showUserMenu();
                });

                document.addEventListener('click', function (e) {
                    if (isUserMenuOpen && !userPanel.contains(e.target) && e.target !== userBtn) {
                        hideUserMenu();
                    }
                });

                window.addEventListener('resize', function () {
                    if (isUserMenuOpen) showUserMenu();
                });
                window.addEventListener('scroll', function () {
                    if (isUserMenuOpen) showUserMenu();
                }, true);
            }
            const lightDarkBtn = document.getElementById('light-dark-mode');
            if (lightDarkBtn) {
                const savedTheme = localStorage.getItem('theme');
                if (savedTheme) {
                    document.documentElement.setAttribute('data-bs-theme', savedTheme);
                }

                lightDarkBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const html = document.documentElement;
                    const currentTheme = html.getAttribute('data-bs-theme');
                    const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    html.setAttribute('data-bs-theme', nextTheme);
                    localStorage.setItem('theme', nextTheme);
                });
            }

            document.querySelectorAll('img').forEach(img => img.classList.add('img-fluid'));

        });
    </script>

    @stack('scripts')
    @yield('scripts')
</body>
</html>
<script>
    (function () {

        function getToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        }

        // Patch fetch()
        const origFetch = window.fetch;
        window.fetch = function (url, opts = {}) {
            const method = (opts.method || 'GET').toUpperCase();
            if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
                opts.headers = Object.assign({}, opts.headers, {
                    'X-CSRF-TOKEN': getToken(),
                    'Accept': 'application/json',
                });
                opts.credentials = 'same-origin';
            }
            return origFetch.call(this, url, opts);
        };

        function patchAxios() {
            if (window.axios) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = getToken();
                window.axios.defaults.withCredentials = true;
                window.axios.interceptors.request.use(function (config) {
                    const m = (config.method || '').toUpperCase();
                    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(m)) {
                        config.headers['X-CSRF-TOKEN'] = getToken();
                    }
                    return config;
                });
            }
        }
        document.addEventListener('DOMContentLoaded', patchAxios);

        document.addEventListener('DOMContentLoaded', function () {
            if (window.jQuery) {
                jQuery.ajaxSetup({
                    headers: { 'X-CSRF-TOKEN': getToken() }
                });
            }
        });
    })();
</script>
