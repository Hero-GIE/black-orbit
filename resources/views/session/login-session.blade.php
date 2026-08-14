@extends('layouts.user_type.guest')

@section('content')
<style>
    /* Import Urbanist font */
    @import url('https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap');


    /* Network-specific error styling */
.alert-network {
    background-color: #fff3cd;
    border-color: #ffecb5;
    color: #856404;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    line-height: 1.5;
}

.alert-network i {
    font-size: 1.2rem;
    margin-top: 2px;
    flex-shrink: 0;
}

    #particle-canvas {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        pointer-events: none;
    }

    .login-layout {
        display: flex;
        width: 100%;
        min-height: 100vh;
        margin: 0;
        padding: 0;
        position: relative;
        z-index: 1;
    }
    .left-panel {
        display: flex;
        align-items: flex-start;
        justify-content: center;
        width: 100%;
        background: transparent;
        padding: 2.5rem 1rem 2rem 1rem;
        position: relative;
        z-index: 2;
        overflow-y: auto;
    }
    .right-panel {
        display: none;
        width: 50%;
        position: relative;
    }
    @media (min-width: 992px) {
        .login-layout { height: 100vh; overflow: hidden; }
        .left-panel { width: 50%; height: 100vh; }
        .right-panel { display: block; height: 100vh; }
    }

    .page-header {
        position: relative;
        z-index: 1;
    }

    .card-plain {
        position: relative;
        z-index: 2;
        background-color: #ffffff;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0,0,0,0.02);
        padding-top: 1.5rem;
        width: 100%;
        max-width: 420px;
    }

    /* Flex row for Logo and Astronaut */
    .brand-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-bottom: 0;
    }

    /* Success message style */
    .alert-success {
        background-color: #d1e7dd;
        border-color: #badbcc;
        color: #0f5132;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 0.9rem;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success i { font-size: 1.1rem; }

    .alert-danger {
        background-color: #f8d7da;
        border-color: #f5c2c7;
        color: #842029;
        padding: 10px 15px;
        border-radius: 8px;
        font-size: 0.85rem;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-danger i { font-size: 1.1rem; }

    .form-control-custom {
        border-width: 2px;
        border-color: #d1d5db;
        border-radius: 12px;
        height: 44px;
        padding: 10px 16px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        width: 100%;
    }

    .form-control-custom:focus {
        border-color: #000000;
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.08);
        outline: none;
    }

    .form-control-custom.is-invalid { border-color: #dc3545; }

    .btn-signin {
        background: #000000;
        border: none;
        border-radius: 12px;
        padding: 14px;
        font-weight: 600;
        height: 44px;
        transition: all 0.3s ease;
        color: white;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-signin:hover {
        background: #1a1a1a;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        color: white;
    }

    .btn-signin:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none !important;
    }

    .btn-signin .spinner {
        display: none;
        width: 20px;
        height: 20px;
        border: 3px dotted rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s linear infinite;
    }

    .btn-signin.loading .spinner { display: inline-block; }
    .btn-signin.loading .btn-text { display: none; }
    .btn-signin.loading .btn-text-loading { display: inline-block; }
    .btn-text-loading { display: none; }

    @keyframes spin { to { transform: rotate(360deg); } }

    label {
        color: #494a4d;
        font-size: 0.73rem !important;
        font-weight: 600 !important;
        margin-bottom: 4px !important;
    }

    .form-check-label {
        font-size: 0.85rem !important;
        color: #374151 !important;
        font-weight: 400 !important;
    }

    .login-link {
        color: #000000;
        font-weight: 600;
        text-decoration: none;
    }

    .login-link:hover {
        color: #1a1a1a;
        text-decoration: underline;
    }

    .text-danger {
        font-size: 0.75rem !important;
        margin-top: 4px !important;
        color: #dc3545 !important;
    }

    .card-footer .text-muted { font-size: 0.85rem; }

    .login-logo {
        height: 270px;
        width: auto;
        max-width: 100%;
        object-fit: contain;
        transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), filter 0.4s ease;
        filter: drop-shadow(0 15px 25px rgba(0, 0, 0, 0.2));
        animation: fadeInDown 0.8s ease-out;
    }

    .login-logo:hover {
        transform: scale(1.05) rotate(-2deg);
        filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.25));
    }

    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-40px) scale(0.9); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* --- Right Side Image & Dark Overlay --- */
    .image-panel {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('../assets/img/4.jpeg');
        background-size: cover;
        background-position: center;
    }

    .image-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7));
        z-index: 1;
    }

    /* --- Right Panel Text --- */
    .panel-content {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 4rem 3.5rem;
        z-index: 3;
        background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.4) 60%, transparent 100%);
    }

    .brand-heading {
        font-family: 'Urbanist', sans-serif !important;
        font-weight: 800;
        font-size: clamp(2.5rem, 4vw, 3.5rem);
        color: #ffffff;
        line-height: 1.1;
        margin-bottom: 1.5rem;
        text-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
    }

    .brand-subtext {
        font-family: 'Urbanist', sans-serif !important;
        font-size: 1rem;
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.6;
        max-width: 450px;
        font-weight: 400;
    }

    /* --- Animated Astronaut --- */
    .astronaut-wrapper {
        width: 110px;
        height: 130px;
        flex-shrink: 0;
        animation: astronaut-float 4s ease-in-out infinite;
        filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1)); /* Lighter shadow for white card background */
    }

    .astronaut-svg {
        width: 100%;
        height: 100%;
        overflow: visible;
    }

    .waving-arm {
        transform-box: fill-box;
        transform-origin: 50% 100%; /* Rotates from the shoulder */
        animation: astronaut-wave 1.5s ease-in-out infinite;
    }

    @keyframes astronaut-float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-15px) rotate(3deg); }
    }

    @keyframes astronaut-wave {
        0%, 100% { transform: rotate(-15deg); }
        50% { transform: rotate(35deg); }
    }

    @media (max-width: 575.98px) {
        .brand-row {
            flex-direction: column;
            gap: 1rem;
        }
        .login-logo {
            height: 180px;
        }
        .astronaut-wrapper {
            width: 90px;
            height: 110px;
        }
    }
</style>

<canvas id="particle-canvas"></canvas>

<main class="main-content mt-0 p-0">
    <div class="login-layout">

        <div class="left-panel">
            <div class="card card-plain">
                <div class="card-header pb-0 bg-transparent">
                    <!-- Flex row to hold both Logo and Astronaut -->
                    <div class="brand-row">
                        <img src="{{ asset('assets/img/black-orbit2.png') }}"
                             alt="{{ config('app.name', 'Pharmacy') }}"
                             class="login-logo">

                        <!-- Animated Astronaut SVG -->
                        <div class="astronaut-wrapper">
                            <svg class="astronaut-svg" viewBox="0 0 120 150">
                                <!-- Backpack -->
                                <rect x="25" y="65" width="15" height="35" rx="4" fill="#e0e0e0" stroke="#222" stroke-width="2"/>
                                <circle cx="32" cy="75" r="3" fill="#dc3545"/>
                                <circle cx="32" cy="85" r="3" fill="#198754"/>

                                <!-- Body -->
                                <rect x="40" y="60" width="40" height="50" rx="12" fill="#ffffff" stroke="#222" stroke-width="2"/>
                                <!-- Chest Panel -->
                                <rect x="50" y="75" width="20" height="15" rx="3" fill="#333" stroke="#555"/>
                                <rect x="54" y="79" width="4" height="4" fill="#dc3545"/>
                                <rect x="62" y="79" width="4" height="4" fill="#198754"/>

                                <!-- Left Arm -->
                                <rect x="30" y="65" width="12" height="30" rx="6" fill="#ffffff" stroke="#222" stroke-width="2" transform="rotate(10 36 80)"/>

                                <!-- Right Arm (Waving) -->
                                <g class="waving-arm">
                                    <rect x="72" y="45" width="12" height="30" rx="6" fill="#ffffff" stroke="#222" stroke-width="2" transform="rotate(15 78 72)"/>
                                    <!-- Hand/Glove -->
                                    <circle cx="78" cy="45" r="7" fill="#ffffff" stroke="#222" stroke-width="2"/>
                                </g>

                                <!-- Legs -->
                                <rect x="45" y="108" width="12" height="25" rx="6" fill="#ffffff" stroke="#222" stroke-width="2"/>
                                <rect x="63" y="108" width="12" height="25" rx="6" fill="#ffffff" stroke="#222" stroke-width="2"/>
                                <!-- Boots -->
                                <ellipse cx="51" cy="135" rx="8" ry="5" fill="#333" stroke="#222" stroke-width="2"/>
                                <ellipse cx="69" cy="135" rx="8" ry="5" fill="#333" stroke="#222" stroke-width="2"/>

                                <!-- Helmet -->
                                <circle cx="60" cy="35" r="25" fill="#ffffff" stroke="#222" stroke-width="2"/>
                                <!-- Visor -->
                                <path d="M45 35 A15 15 0 0 1 75 35 Z" fill="#111827"/>
                                <!-- Visor shine -->
                                <path d="M48 25 Q55 20 62 22" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" opacity="0.8"/>
                                <circle cx="68" cy="28" r="3" fill="#ffffff" opacity="0.6"/>
                            </svg>
                        </div>
                    </div>
                    <p class="mb-0 text-center mt-2" style="font-size: 14px;">Sign in with these credentials:</p>
                </div>

                <!-- Changed to pt-1 to keep the form close to the logo -->
                <div class="card-body pt-1">

                    @if (session('success'))
                        <div class="alert alert-success text-center" role="alert" style="background-color: #d1e7dd; border-color: #badbcc; color: #0f5132; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger text-center" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                        </div>
                    @endif

                    <form role="form" method="POST" action="{{ route('login.post') }}" id="loginForm">
                        @csrf
                        <label style="font-weight: 600;">Email</label>
                        <div class="mb-3">
                            <input type="email" class="form-control form-control-custom @error('email') is-invalid @enderror"
                                   name="email" id="email"
                                   placeholder="admin@gmail.com" value="{{ old('email') }}">
                          @error('email')
    @php
        $isNetwork = str_contains($message, 'internet')
                  || str_contains($message, 'connect')
                  || str_contains($message, 'timed out')
                  || str_contains($message, 'firewall')
                  || str_contains($message, 'slow');
    @endphp

    @if($isNetwork)
        <div class="alert-network" role="alert">
            <i class="fas fa-wifi"></i>
            <div>
                <strong>Connection Issue</strong><br>
                {{ $message }}
            </div>
        </div>
    @else
        <div class="alert-danger" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            {{ $message }}
        </div>
    @endif
@enderror
                        </div>

                        <label style="font-weight: 600;">Password</label>
                        <div class="mb-3">
                            <input type="password" class="form-control form-control-custom @error('password') is-invalid @enderror"
                                   name="password" id="password"
                                   placeholder="••••••••">
                            @error('password')
                                <p class="text-danger mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember" checked>
                            <label class="form-check-label" for="rememberMe">Remember me</label>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn-signin w-100 mt-4 mb-0" id="loginBtn">
                                <span class="spinner"></span>
                                <span class="btn-text"><i class="fas fa-sign-in-alt me-2"></i> Sign in</span>
                                <span class="btn-text-loading">Signing in...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!--<div class="card-footer text-center pt-0 px-lg-2 px-1">-->
                <!--    <small class="text-muted">Forgot your password? Reset it-->
                <!--        <a href="{{ route('password.request') }}" class="login-link">here</a>-->
                <!--    </small>-->
                <!--</div>-->
            </div>
        </div>

        <div class="right-panel">
            <div class="image-panel">
                <div class="image-overlay"></div>

                <div class="panel-content">
                    <h2 class="brand-heading">Build the future today</h2>
                    <p class="brand-subtext">Black Orbit Foundation inspires young Ghanaians aged 7-15 to become Africa's next generation of innovators, space explorers, and tech entrepreneurs.</p>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('loginForm');
        const button = document.getElementById('loginBtn');
        @if ($errors->any())
            button.classList.remove('loading');
            button.disabled = false;
        @endif

        form.addEventListener('submit', function(e) {
            button.classList.add('loading');
            button.disabled = true;
        });

        const canvas = document.getElementById('particle-canvas');
        const ctx = canvas.getContext('2d');
        let particles = [];
        let rockets = [];

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        function createParticles() {
            particles = [];
            const numberOfParticles = (window.innerWidth / 15);
            for (let i = 0; i < numberOfParticles; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    radius: Math.random() * 2 + 1,
                    speedX: (Math.random() - 0.5) * 0.5,
                    speedY: (Math.random() - 0.5) * 0.5
                });
            }
        }

        // ROCKET ANIMATION LOGIC
        class Rocket {
            constructor() {
                this.reset(true);
            }

            reset(initial = false) {
                this.x = initial ? Math.random() * canvas.width : -50;
                this.y = initial ? Math.random() * canvas.height : canvas.height + 50;

                this.speedX = Math.random() * 1.5 + 0.5;
                this.speedY = -(Math.random() * 1.5 + 0.5);

                this.size = Math.random() * 15 + 20;
                this.angle = Math.atan2(this.speedY, this.speedX) + Math.PI / 2;
                this.trail = [];
            }

            update() {
                this.trail.push({ x: this.x, y: this.y, alpha: 0.8 });
                if (this.trail.length > 15) this.trail.shift();

                this.x += this.speedX;
                this.y += this.speedY;

                if (this.x > canvas.width + 50 || this.y < -50) {
                    this.reset(false);
                }
            }

            draw(ctx) {

                for (let i = 0; i < this.trail.length; i++) {
                    let t = this.trail[i];
                    ctx.beginPath();
                    ctx.arc(t.x, t.y, this.size / 4, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(15, 15, 15, ${t.alpha * (i / this.trail.length)})`;
                    ctx.fill();
                    t.alpha -= 0.05;
                }
                ctx.save();
                ctx.translate(this.x, this.y);
                ctx.rotate(this.angle);
                ctx.font = `${this.size}px Arial`;
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";

                ctx.shadowColor = 'rgba(0, 0, 0, 0.6)';
                ctx.shadowBlur = 10;

                ctx.fillText('🚀', 0, 0);
                ctx.restore();
            }
        }

        function createRockets() {
            rockets = [];
            const numRockets = 3;
            for (let i = 0; i < numRockets; i++) {
                rockets.push(new Rocket());
            }
        }

        function drawParticles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            for (let i = 0; i < particles.length; i++) {
                let p = particles[i];

                p.x += p.speedX;
                p.y += p.speedY;

                if (p.x < 0 || p.x > canvas.width) p.speedX *= -1;
                if (p.y < 0 || p.y > canvas.height) p.speedY *= -1;

                ctx.beginPath();
                ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(0, 0, 0, 0.8)';
                ctx.fill();

                for (let j = i + 1; j < particles.length; j++) {
                    let p2 = particles[j];
                    let distance = Math.sqrt(Math.pow(p.x - p2.x, 2) + Math.pow(p.y - p2.y, 2));

                    if (distance < 120) {
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(p2.x, p2.y);
                        ctx.strokeStyle = `rgba(0, 0, 0, ${0.15 - (distance / 120) * 0.15})`;
                        ctx.lineWidth = 1;
                        ctx.stroke();
                    }
                }
            }

            // Draw and update rockets
            for (let k = 0; k < rockets.length; k++) {
                rockets[k].update();
                rockets[k].draw(ctx);
            }

            requestAnimationFrame(drawParticles);
        }

        window.addEventListener('resize', () => {
            resizeCanvas();
            createParticles();
            createRockets();
        });

        resizeCanvas();
        createParticles();
        createRockets();
        drawParticles();
    });
</script>
@endsection
