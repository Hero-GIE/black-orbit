@extends('layouts.user_type.guest')

@section('content')
<style>
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

    .alert-success i {
        font-size: 1.1rem;
    }

    /* Error message style */
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

    .alert-danger i {
        font-size: 1.1rem;
    }

    /* Input styles matching login page */
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
        border-color: #2F3DF0;
        box-shadow: 0 0 0 3px rgba(47, 61, 240, 0.08);
        outline: none;
    }

    .form-control-custom.is-invalid {
        border-color: #dc3545;
    }

    .btn-signin {
        background: #2F3DF0;
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
        background: #1A2BC4;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(47, 61, 240, 0.3);
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

    .btn-signin.loading .spinner {
        display: inline-block;
    }

    .btn-signin.loading .btn-text {
        display: none;
    }

    .btn-signin.loading .btn-text-loading {
        display: inline-block;
    }

    .btn-text-loading {
        display: none;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    label {
        color: #494a4d;
        font-size: 0.73rem !important;
        font-weight: 600 !important;
        margin-bottom: 4px !important;
    }

    .login-link {
        color: #2F3DF0;
        font-weight: 600;
        text-decoration: none;
    }

    .login-link:hover {
        color: #1A2BC4;
        text-decoration: underline;
    }

    .text-danger {
        font-size: 0.75rem !important;
        margin-top: 4px !important;
        color: #dc3545 !important;
    }

    .card-footer .text-muted {
        font-size: 0.85rem;
    }
</style>

<main class="main-content mt-0">
    <section>
        <div class="page-header min-vh-75">
            <div class="container">
                <div class="row">
                    <div class="col-xl-4 col-lg-5 col-md-6 d-flex flex-column mx-auto">
                        <div class="card card-plain mt-8">
                            <div class="card-header pb-0 text-center bg-transparent">
                                <div class="d-flex justify-content-center mb-3">
                                    <img src="{{ asset('assets/img/bisacare.webp') }}"
                                         alt="{{ config('app.name', 'Pharmacy') }}"
                                         style="height: 80px; width: auto;">
                                </div>
                                <h3 class="font-weight-bolder" style="color: #2F3DF0; font-size: 1.6rem;">Change Password</h3>
                                <p class="mb-0" style="font-size: 14px;">Enter your new password</p>
                            </div>
                            <div class="card-body">
                                @if($errors->any())
                                    <div class="alert alert-danger text-center" role="alert">
                                        <i class="fas fa-exclamation-circle me-2"></i> {{ $errors->first() }}
                                    </div>
                                @endif

                                @if(session('success'))
                                    <div class="alert alert-success text-center" role="alert">
                                        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                                    </div>
                                @endif

                                <form role="form" action="/reset-password" method="POST">
                                    @csrf
                                    <input type="hidden" name="token" value="{{ $token }}">

                                    <label style="font-weight: 600;">Email</label>
                                    <div class="mb-3">
                                        <input id="email" name="email" type="email"
                                               class="form-control form-control-custom @error('email') is-invalid @enderror"
                                               placeholder="Enter your email"
                                               value="{{ old('email') }}">
                                        @error('email')
                                            <p class="text-danger mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                                        @enderror
                                    </div>

                                    <label style="font-weight: 600;">New Password</label>
                                    <div class="mb-3">
                                        <input id="password" name="password" type="password"
                                               class="form-control form-control-custom @error('password') is-invalid @enderror"
                                               placeholder="Enter new password">
                                        @error('password')
                                            <p class="text-danger mt-1"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                                        @enderror
                                    </div>

                                    <label style="font-weight: 600;">Confirm Password</label>
                                    <div class="mb-3">
                                        <input id="password-confirmation" name="password_confirmation" type="password"
                                               class="form-control form-control-custom"
                                               placeholder="Confirm new password">
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn-signin w-100 mt-4 mb-0" id="resetBtn">
                                            <span class="spinner"></span>
                                            <span class="btn-text"><i class="fas fa-key me-2"></i> Reset Password</span>
                                            <span class="btn-text-loading">Resetting...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="card-footer text-center pt-0 px-lg-2 px-1">
                                <p class="mb-4 text-sm mx-auto mt-2">
                                    Remember your password?
                                    <a href="{{ route('login') }}" class="login-link">Sign in</a>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="oblique position-absolute top-0 h-100 d-md-block d-none me-n8">
                            <div class="oblique-image bg-cover position-absolute fixed-top ms-auto h-100 z-index-0 ms-n6"
                                 style="background-image:url('../assets/img/curved-images/1.svg'); background-size: cover; background-position: center;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const button = document.getElementById('resetBtn');

        @if ($errors->any())
            button.classList.remove('loading');
            button.disabled = false;
        @endif

        form.addEventListener('submit', function(e) {
            button.classList.add('loading');
            button.disabled = true;
        });
    });
</script>
@endsection
