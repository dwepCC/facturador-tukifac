@extends('tenant.layouts.auth')

@section('content')
<meta charset="UTF-8">
<title>TUKIFAC - Facturación Electrónica</title>
<link rel="icon" href="https://erp.tukifac.pe/favicon.ico" type="image/x-icon" sizes="256x256">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        color: #1f2937;
        background-color: #ffffff !important;
    }

    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .login-card {
        width: 100%;
        max-width: 875px;
        background: #ffffff;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        overflow: hidden;
    }

    .login-grid {
        display: grid;
        grid-template-columns: 1fr;
        min-height: 100vh;
    }

    /* Sección de imagen - oculta en móvil */
    .login-banner {
        display: none;
        background: url('https://tnyywwxijfmggxfzrlex.supabase.co/storage/v1/object/public/public_images/img_tukifac_login_bg.webp') center/cover no-repeat;
        padding: 1.75rem;
        position: relative;
    }

    .banner-content {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }

    .banner-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #ffffff;
        padding-top: 2.5rem;
        line-height: 1.3;
    }

    .banner-highlight {
        color: #16a34a;
    }

    .banner-text {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 500;
        margin-top: 0.5rem;
    }

    /* Sección del formulario */
    .login-form-section {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2.5rem 1.5rem;
    }

    .form-wrapper {
        width: 100%;
        max-width: 20rem;
    }

    .logo-container {
        margin-bottom: 1.5rem;
    }

    .logo-container img {
        width: 192px;
        height: 44px;
    }

    .welcome-section {
        margin-bottom: 1.5rem;
    }

    .welcome-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }

    .welcome-text {
        font-size: 0.75rem;
        font-weight: 500;
        color: #1f2937;
        line-height: 1.5;
    }

    /* Formulario */
    .login-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #1f2937;
        margin-bottom: 0.25rem;
    }

    .form-input {
        width: 100%;
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        transition: all 0.15s;
    }

    .form-input:focus {
        outline: none;
        border-color: #16a34a;
        box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.1);
    }

    .form-input.error {
        border-color: #ef4444;
    }

    .input-wrapper {
        position: relative;
    }

    .input-wrapper .form-input {
        padding-right: 2.5rem;
    }

    .toggle-password {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: #6b7280;
        cursor: pointer;
        padding: 0.25rem;
        transition: color 0.15s;
    }

    .toggle-password:hover {
        color: #374151;
    }

    .error-message {
        font-size: 0.75rem;
        color: #ef4444;
        margin-top: 0.25rem;
    }

    .submit-btn {
        width: 100%;
        background-color: #16a34a;
        color: #ffffff;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.15s;
    }

    .submit-btn:hover {
        background-color: #15803d;
    }

    .forgot-password {
        text-align: center;
        padding: 1rem;
    }

    .forgot-password a {
        font-size: 0.875rem;
        color: #16a34a;
        text-decoration: none;
        transition: text-decoration 0.15s;
    }

    .forgot-password a:hover {
        text-decoration: underline;
    }

    /* Responsive - Tablet y Desktop */
    @media (min-width: 768px) {
        .login-card {
            border-radius: 1rem;
        }

        .login-grid {
            grid-template-columns: 2fr 3fr;
            min-height: auto;
        }

        .login-banner {
            display: flex;
        }

        .login-form-section {
            padding: 2.5rem;
        }
    }

    /* Responsive - Desktop grande */
    @media (min-width: 1024px) {
        .form-wrapper {
            max-width: 22rem;
        }
    }
</style>

<section class="login-container">
    <div class="login-card">
        <div class="login-grid">
            <!-- Banner lateral -->
            <div class="login-banner">
                <div class="banner-content">
                    <h2 class="banner-title">
                        Una nueva forma<br>
                        <span class="banner-highlight">de vender</span>
                    </h2>
                    <p class="banner-text">
                        Suscríbete a Tukifac y realiza tus ventas o facturas de la forma
                        más fácil, cómoda y moderna.
                    </p>
                </div>
            </div>

            <!-- Sección del formulario -->
            <div class="login-form-section">
                <div class="form-wrapper">
                    <div class="logo-container">
                        <img src="https://tnyywwxijfmggxfzrlex.supabase.co/storage/v1/object/public/public_images/Tukifac-large-ver-2.webp" 
                             alt="logo tukifac" 
                             loading="lazy" 
                             decoding="async">
                    </div>

                    <div class="welcome-section">
                        <h2 class="welcome-title">Iniciar Sesión 👋</h2>
                        <p class="welcome-text">
                            Bienvenido a TUKIFAC, tu facturador y punto de venta de
                            confianza, ingresa tus credenciales e inicia.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="login-form" accept-charset="UTF-8">
                        @csrf
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input 
                                type="email" 
                                name="email" 
                                id="email" 
                                class="form-input {{ $errors->has('email') ? 'error' : '' }}" 
                                value="{{ old('email') }}" 
                                autofocus
                                required>
                            @if ($errors->has('email'))
                                <span class="error-message">{{ $errors->first('email') }}</span>
                            @endif
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-wrapper">
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password" 
                                    class="form-input {{ $errors->has('password') ? 'error' : '' }}"
                                    required>
                                <button type="button" class="toggle-password" id="btnEye" aria-label="Mostrar contraseña">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                            @if ($errors->has('password'))
                                <span class="error-message">{{ $errors->first('password') }}</span>
                            @endif
                        </div>

                        <button type="submit" class="submit-btn">
                            INICIAR SESIÓN
                        </button>

                        <div class="forgot-password">
                            <a href="{{ url('password/reset') }}">
                                ¿Has olvidado tu contraseña?
                            </a>
                        </div>

                        @include('tenant.auth.partials.socials')
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    (function() {
        const inputPassword = document.getElementById('password');
        const btnEye = document.getElementById('btnEye');
        
        if (inputPassword && btnEye) {
            btnEye.addEventListener('click', function() {
                const isPassword = inputPassword.type === 'password';
                inputPassword.type = isPassword ? 'text' : 'password';
                btnEye.innerHTML = isPassword 
                    ? '<i class="fa fa-eye-slash"></i>' 
                    : '<i class="fa fa-eye"></i>';
            });
        }
    })();
</script>
@endpush