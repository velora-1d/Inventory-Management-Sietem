<x-guest-layout title="Login">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    .auth-wrap {
        display: flex;
        min-height: 100vh;
        width: 100%;
    }

    /* ── LEFT PANEL ─────────────────────────────── */
    .auth-left {
        display: none;
        position: relative;
        flex: 1;
        overflow: hidden;
        background: #0a0a0f;
    }
    @media (min-width: 1024px) { .auth-left { display: flex; flex-direction: column; } }

    /* Geometric grid background */
    .auth-left::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(14,165,233,.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(14,165,233,.06) 1px, transparent 1px);
        background-size: 48px 48px;
    }

    /* Ambient glow blobs */
    .blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        opacity: .35;
        animation: drift 12s ease-in-out infinite alternate;
    }
    .blob-1 { width: 480px; height: 480px; background: radial-gradient(circle, #3b82f6, #0ea5e9); top: -120px; left: -120px; animation-delay: 0s; }
    .blob-2 { width: 360px; height: 360px; background: radial-gradient(circle, #0ea5e9, #0d9488); bottom: 80px; right: -80px; animation-delay: 4s; }
    .blob-3 { width: 240px; height: 240px; background: radial-gradient(circle, #10b981, #0d9488); top: 50%; left: 55%; transform: translate(-50%, -50%); animation-delay: 2s; }

    @keyframes drift {
        0%   { transform: translate(0, 0) scale(1); }
        100% { transform: translate(30px, -30px) scale(1.08); }
    }

    .auth-left-content {
        position: relative;
        z-index: 10;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 72px;
        height: 100%;
    }

    /* Brand mark */
    .brand-mark {
        display: inline-flex;
        align-items: center;
        gap: 18px;
    }
    .brand-icon {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        background: linear-gradient(135deg, #3b82f6 0%, #0ea5e9 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .brand-name {
        font-size: 1.875rem;
        font-weight: 700;
        color: #fff;
        letter-spacing: -.02em;
    }

    /* Hero section */
    .hero-section { max-width: 720px; }
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 9px 21px;
        border-radius: 999px;
        background: rgba(14,165,233,.15);
        border: 1px solid rgba(14,165,233,.3);
        color: #7dd3fc;
        font-size: 1.125rem;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
        margin-bottom: 42px;
    }
    .hero-badge span { width: 9px; height: 9px; border-radius: 50%; background: #0ea5e9; animation: pulse-dot 2s infinite; }
    @keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.8)} }

    .hero-title {
        font-size: clamp(3rem, 4.5vw, 4.125rem);
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -.04em;
        color: #fff;
        margin-bottom: 30px;
    }
    .hero-title .gradient-text {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 50%, #10b981 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .hero-desc {
        font-size: 1.4rem;
        line-height: 1.7;
        color: rgba(255,255,255,.5);
        max-width: 570px;
    }

    /* Stats row */
    .stats-row {
        display: flex;
        gap: 48px;
        margin-top: 72px;
    }
    .stat-item {}
    .stat-value {
        font-size: 2.25rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: -.03em;
    }
    .stat-label { font-size: 1.125rem; color: rgba(255,255,255,.4); margin-top: 2px; }
    .stat-divider { width: 1px; background: rgba(255,255,255,.1); }

    /* Bottom Footer in Left Panel */
    .auth-left-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 36px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    /* ── RIGHT PANEL ─────────────────────────────── */
    .auth-right {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 100%;
        padding: 32px 24px;
        background: #fff;
        position: relative;
    }
    @media (min-width: 1024px) {
        .auth-right { width: 480px; flex-shrink: 0; padding: 48px 56px; }
    }

    /* Top-right corner decoration */
    .auth-right::before {
        content: '';
        position: absolute;
        top: 0; right: 0;
        width: 200px; height: 200px;
        background: radial-gradient(circle at top right, rgba(14,165,233,.06), transparent 70%);
        pointer-events: none;
    }

    .form-wrap { width: 100%; max-width: 360px; }

    /* Mobile brand (shown only on small) */
    .mobile-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 40px;
    }
    @media (min-width: 1024px) { .mobile-brand { display: none; } }

    .form-header { margin-bottom: 36px; }
    .form-eyebrow {
        font-size: .6875rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: #0ea5e9;
        margin-bottom: 8px;
    }
    .form-title {
        font-size: 1.75rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -.03em;
        line-height: 1.2;
    }
    .form-subtitle {
        margin-top: 8px;
        font-size: .875rem;
        color: #64748b;
    }

    /* Field group */
    .field-group { display: flex; flex-direction: column; gap: 20px; }

    .field-item { display: flex; flex-direction: column; gap: 6px; }

    .field-label {
        font-size: .8125rem;
        font-weight: 600;
        color: #374151;
    }

    .field-input-wrap { position: relative; }

    .field-input {
        width: 100%;
        height: 48px;
        padding: 0 16px 0 44px;
        border-radius: 12px;
        border: 1.5px solid #e2e8f0;
        background: #f8fafc;
        font-size: .9375rem;
        color: #0f172a;
        outline: none;
        transition: border-color .2s, box-shadow .2s, background .2s;
        font-family: 'Inter', sans-serif;
    }
    .field-input:focus {
        border-color: #0ea5e9;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(14,165,233,.15);
    }
    .field-input::placeholder { color: #94a3b8; }

    .field-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        pointer-events: none;
    }
    .field-input:focus ~ .field-icon-after,
    .field-input:focus + .field-icon { color: #0ea5e9; }

    /* Password toggle */
    .pwd-toggle {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #94a3b8;
        padding: 4px;
        line-height: 1;
        transition: color .15s;
    }
    .pwd-toggle:hover { color: #0ea5e9; }

    /* Forgot link */
    .forgot-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .forgot-link {
        font-size: .8125rem;
        font-weight: 500;
        color: #0ea5e9;
        text-decoration: none;
        transition: opacity .15s;
    }
    .forgot-link:hover { opacity: .7; }

    /* Remember me */
    .remember-row { display: flex; align-items: center; gap: 8px; }
    .remember-check {
        width: 16px; height: 16px;
        border-radius: 4px;
        border: 1.5px solid #cbd5e1;
        accent-color: #0ea5e9;
        cursor: pointer;
    }
    .remember-label { font-size: .8125rem; color: #64748b; cursor: pointer; }

    /* Submit button */
    .btn-submit {
        width: 100%;
        height: 52px;
        border-radius: 14px;
        background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%);
        color: #fff;
        font-size: .9375rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        letter-spacing: -.01em;
        transition: opacity .2s, transform .15s, box-shadow .2s;
        box-shadow: 0 4px 20px rgba(37, 99, 235, .25);
        font-family: 'Inter', sans-serif;
        margin-top: 8px;
    }
    .btn-submit:hover:not(:disabled) {
        opacity: .92;
        transform: translateY(-1px);
        box-shadow: 0 8px 28px rgba(37, 99, 235, .3);
    }
    .btn-submit:active:not(:disabled) { transform: translateY(0); }
    .btn-submit:disabled { opacity: .6; cursor: not-allowed; }

    /* Spinner */
    @keyframes spin { to { transform: rotate(360deg); } }
    .spinner { animation: spin .8s linear infinite; }

    /* Divider */
    .divider {
        display: flex; align-items: center; gap: 12px;
        color: #cbd5e1; font-size: .75rem; margin: 8px 0;
    }
    .divider::before, .divider::after {
        content: ''; flex: 1;
        height: 1px; background: #e2e8f0;
    }

    /* Sign up row */
    .signup-row {
        text-align: center;
        font-size: .875rem;
        color: #64748b;
    }
    .signup-link {
        color: #0ea5e9;
        font-weight: 600;
        text-decoration: none;
        transition: opacity .15s;
    }
    .signup-link:hover { opacity: .7; }

    /* Session status */
    .session-status {
        padding: 12px 16px;
        border-radius: 10px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
        font-size: .875rem;
        font-weight: 500;
        margin-bottom: 20px;
    }

    /* Error messages */
    .field-error {
        font-size: .75rem;
        color: #ef4444;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Security badge at bottom */
    .security-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin-top: 28px;
        font-size: .6875rem;
        color: #94a3b8;
    }
</style>

<div class="auth-wrap">

    {{-- ── LEFT PANEL ── --}}
    <div class="auth-left">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>

        <div class="auth-left-content">
            {{-- Top: Brand --}}
            <div class="brand-mark">
                <div class="brand-icon">
                    <svg width="33" height="33" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                        <path d="m3.3 7 8.7 5 8.7-5"/>
                        <path d="M12 22V12"/>
                    </svg>
                </div>
                <span class="brand-name">{{ config('app.name', 'Inventory') }}</span>
            </div>

            {{-- Middle: Hero --}}
            <div class="hero-section">
                <div class="hero-badge">
                    <span></span>
                    Platform Manajemen Inventori
                </div>
                <h1 class="hero-title">
                    Kendalikan Stok<br>
                    dengan <span class="gradient-text">Presisi Penuh</span>
                </h1>
                <p class="hero-desc">
                    Lacak produk, kelola pembelian & penjualan, pantau keuangan — semuanya dalam satu platform yang cepat dan intuitif.
                </p>

                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-value">99.9%</div>
                        <div class="stat-label">Uptime</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-value">Real-time</div>
                        <div class="stat-label">Pembaruan data</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-value">SSL</div>
                        <div class="stat-label">Enkripsi penuh</div>
                    </div>
                </div>
            </div>

            {{-- Bottom: Footer Info --}}
            <div class="auth-left-footer">
                <span style="font-size: 1.2rem; color: rgba(255,255,255,0.45); display: flex; align-items: center; gap: 12px;">
                    <span style="width: 12px; height: 12px; border-radius: 50%; background: #10b981; display: inline-block; box-shadow: 0 0 12px #10b981;"></span>
                    Semua sistem operasional normal
                </span>
                <span style="font-size: 1.2rem; color: rgba(255,255,255,0.45);">
                    v1.0.0
                </span>
            </div>
        </div>
    </div>

    {{-- ── RIGHT PANEL ── --}}
    <div class="auth-right">
        <div class="form-wrap">

            {{-- Mobile Brand (hidden on desktop) --}}
            <div class="mobile-brand">
                <div class="brand-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                        <path d="m3.3 7 8.7 5 8.7-5"/>
                        <path d="M12 22V12"/>
                    </svg>
                </div>
                <span style="font-size:1.125rem;font-weight:800;color:#0f172a;letter-spacing:-.02em;">{{ config('app.name') }}</span>
            </div>

            {{-- Session Status --}}
            @if(session('status'))
                <div class="session-status">{{ session('status') }}</div>
            @endif

            {{-- Form Header --}}
            <div class="form-header">
                <p class="form-eyebrow">Selamat Datang</p>
                <h2 class="form-title">Masuk ke akun Anda</h2>
                <p class="form-subtitle">Masukkan kredensial untuk melanjutkan</p>
            </div>

            {{-- Login Form --}}
            <form method="POST" action="{{ route('login') }}" x-data="{ loading: false, showPwd: false }" @submit="loading = true">
                @csrf

                <div class="field-group">

                    {{-- Username --}}
                    <div class="field-item">
                        <label class="field-label" for="username">Username</label>
                        <div class="field-input-wrap">
                            <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 1 0-16 0"/>
                            </svg>
                            <input
                                id="username"
                                type="text"
                                name="username"
                                class="field-input"
                                value="{{ old('username') }}"
                                placeholder="Masukkan username"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                        @error('username')
                            <p class="field-error">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="field-item">
                        <div class="forgot-row">
                            <label class="field-label" for="password">Password</label>
                            @if (Route::has('password.request'))
                                <a class="forgot-link" href="{{ route('password.request') }}">Lupa password?</a>
                            @endif
                        </div>
                        <div class="field-input-wrap">
                            <svg class="field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input
                                id="password"
                                :type="showPwd ? 'text' : 'password'"
                                name="password"
                                class="field-input"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="pwd-toggle" @click="showPwd = !showPwd" tabindex="-1">
                                <svg x-show="!showPwd" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg x-show="showPwd" style="display:none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="field-error">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Remember Me --}}
                    <div class="remember-row">
                        <input id="remember_me" type="checkbox" class="remember-check" name="remember">
                        <label for="remember_me" class="remember-label">Ingat saya selama 30 hari</label>
                    </div>

                    {{-- Submit --}}
                    <button type="submit" class="btn-submit" :disabled="loading">
                        <svg x-show="loading" style="display:none" class="spinner" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                        </svg>
                        <span x-text="loading ? 'Memproses...' : 'Masuk Sekarang'">Masuk Sekarang</span>
                        <svg x-show="!loading" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14m-7-7 7 7-7 7"/>
                        </svg>
                    </button>

                </div>

            </form>

            {{-- Security Badge --}}
            <div class="security-badge">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Dilindungi enkripsi SSL 256-bit · {{ date('Y') }}
            </div>

        </div>
    </div>

</div>
</x-guest-layout>
