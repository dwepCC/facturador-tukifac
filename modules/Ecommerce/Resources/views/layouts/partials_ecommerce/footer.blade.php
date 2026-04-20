@php
    $__tuki_inf = $information ?? null;
    $__tuki_footer_phone = $__tuki_inf ? trim((string) ($__tuki_inf->information_contact_phone ?? '')) : '';
    $__tuki_footer_tel_href = $__tuki_footer_phone !== '' ? preg_replace('/\s+/', '', $__tuki_footer_phone) : '';
    /** URLs tal cual en BD (trim); no usar solo filled() por compatibilidad con espacios / tipos legacy */
    $__tuki_footer_fb = $__tuki_inf ? trim((string) ($__tuki_inf->link_facebook ?? '')) : '';
    $__tuki_footer_tw = $__tuki_inf ? trim((string) ($__tuki_inf->link_twitter ?? '')) : '';
    $__tuki_footer_tt = $__tuki_inf ? trim((string) ($__tuki_inf->link_tiktok ?? '')) : '';
    $__tuki_footer_ig = $__tuki_inf ? trim((string) ($__tuki_inf->link_instagram ?? '')) : '';
    $__tuki_footer_has_social = $__tuki_footer_fb !== '' || $__tuki_footer_tw !== '' || $__tuki_footer_tt !== '' || $__tuki_footer_ig !== '';
@endphp

<div class="footer-middle tuki_footer__middle">
    <div class="container tuki_footer__container">
        <div class="row tuki_footer__grid {{ $__tuki_footer_has_social ? '' : 'justify-content-lg-between' }}">
            <div class="col-12 mb-4 mb-lg-0 {{ $__tuki_footer_has_social ? 'col-lg-4' : 'col-lg-5' }}">
                <section class="tuki_footer__panel" aria-labelledby="tuki-footer-contact-heading">
                    <h4 id="tuki-footer-contact-heading" class="tuki_footer__heading">Contáctanos</h4>
                    <ul class="tuki_footer__contact-list">
                        @if (filled($__tuki_footer_phone))
                            <li class="tuki_footer__contact-row">
                                <span class="tuki_footer__contact-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2" /></svg>
                                </span>
                                <a class="tuki_footer__link tuki_footer__link--strong" href="tel:{{ $__tuki_footer_tel_href }}" rel="nofollow">{{ $__tuki_footer_phone }}</a>
                            </li>
                        @endif
                        @if ($__tuki_inf && $information->information_contact_address)
                            <li class="tuki_footer__contact-row">
                                <span class="tuki_footer__contact-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z" /></svg>
                                </span>
                                <span class="tuki_footer__text">{{ $information->information_contact_address }}</span>
                            </li>
                        @endif
                        @if ($__tuki_inf && $information->information_contact_email)
                            <li class="tuki_footer__contact-row">
                                <span class="tuki_footer__contact-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" /><path d="M3 7l9 6l9 -6" /></svg>
                                </span>
                                <a class="tuki_footer__link" href="mailto:{{ $information->information_contact_email }}">{{ $information->information_contact_email }}</a>
                            </li>
                        @endif
                    </ul>
                </section>
            </div>

            <div class="col-12 mb-4 mb-lg-0 {{ $__tuki_footer_has_social ? 'col-lg-4' : 'col-lg-5' }}">
                <section class="tuki_footer__panel tuki_footer__panel--links" aria-labelledby="tuki-footer-links-heading">
                    <h4 id="tuki-footer-links-heading" class="tuki_footer__heading tuki_footer__heading--center-lg">Enlaces de interés</h4>
                    <nav class="tuki_footer__nav" aria-label="Enlaces de interés">
                        <ul class="links tuki_footer__links">
                            <li><a href="{{ route('tenant.ecommerce.index') }}">Inicio</a></li>
                            <li><a href="{{ route('tenant_detail_cart') }}">Ver carrito</a></li>
                            @guest
                                <li><a href="{{ route('tenant_ecommerce_login') }}" class="login-link">Iniciar sesión</a></li>
                            @else
                                <li>
                                    <a role="menuitem" href="{{ route('logout') }}" class="login-link"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        Salir
                                    </a>
                                </li>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            @endguest
                        </ul>
                    </nav>
                </section>
            </div>

            @if ($__tuki_footer_has_social)
                <div class="col-12 mb-lg-0 col-lg-4">
                    <section class="tuki_footer__panel tuki_footer__panel--social" aria-labelledby="tuki-footer-social-heading">
                        <h4 id="tuki-footer-social-heading" class="tuki_footer__heading tuki_footer__heading--end-lg">Redes sociales</h4>
                        <div class="tuki_footer__socials">
                            @if ($__tuki_footer_fb !== '')
                                <a href="{{ $__tuki_footer_fb }}" class="tuki_footer__social-btn" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 10v4h3v7h4v-7h3l1 -4h-4v-2a1 1 0 0 1 1 -1h3v-4h-3a5 5 0 0 0 -5 5v2h-3" /></svg>
                                </a>
                            @endif
                            @if ($__tuki_footer_tw !== '')
                                <a href="{{ $__tuki_footer_tw }}" class="tuki_footer__social-btn" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 4l11.733 16h4.267l-11.733 -16z" /><path d="M4 20l6.768 -6.768m2.46 -2.46l6.772 -6.772" /></svg>
                                </a>
                            @endif
                            @if ($__tuki_footer_tt !== '')
                                <a href="{{ $__tuki_footer_tt }}" class="tuki_footer__social-btn" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M21 7.917v4.034a9.948 9.948 0 0 1 -5 -1.951v4.5a6.5 6.5 0 1 1 -8 -6.326v4.326a2.5 2.5 0 1 0 4 2v-11.5h4.083a6.005 6.005 0 0 0 4.917 4.917z" /></svg>
                                </a>
                            @endif
                            @if ($__tuki_footer_ig !== '')
                                <a href="{{ $__tuki_footer_ig }}" class="tuki_footer__social-btn" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 8a4 4 0 0 1 4 -4h8a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-8a4 4 0 0 1 -4 -4z" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M16.5 7.5v.01" /></svg>
                                </a>
                            @endif
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="container tuki_footer__legal">
    <div class="tuki_footer__legal-inner">
        <p class="tuki_footer__copyright">&copy; {{ date('Y') }} {{ $company->name }}. Todos los derechos reservados.</p>
        <div class="tuki_footer__payments">
            <img src="{{ asset('porto-ecommerce/assets/images/payments.svg') }}" alt="Medios de pago aceptados" class="footer-payments" width="280" height="24" loading="lazy" decoding="async">
        </div>
    </div>
</div>

@if ($information->phone_whatsapp)
    @if (strlen($information->phone_whatsapp) > 0)
        <a class="ws-flotante tuki_footer__wa" href="https://wa.me/{{ $information->phone_whatsapp }}" target="_blank" rel="noopener noreferrer"
            aria-label="Contactar por WhatsApp"
            style="background-image: url('{{ asset('logo/ws.png') }}');"></a>
    @endif
@endif

<div class="modal fade" id="moda-succes-add-product" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">

                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check"></i> Tu producto se agregó al carrito
                </div>
                <div class="row">
                    <div id="product_added_image" class="col-md-4">


                    </div>
                    <div class="col-md-8">
                        <div id="product_added" class="product-single-details-ecommerce">

                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('tenant_detail_cart') }}" class="btn btn-primary text-white">Ir a Carrito</a>
                <button type="button" class="btn btn-warning" data-dismiss="modal">Seguir Comprando</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modal-already-product" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-body">

                <div style="font-size: 2em;" class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation"></i> Tu Producto ya está agregado al carrito.
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('tenant_detail_cart') }}" class="btn btn-primary text-white">Ir al Carrito</a>
                <button type="button" class="btn btn-warning" data-dismiss="modal">Seguir Comprando</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade tuki_login_register_modal" id="login_register_modal" tabindex="-1" role="dialog"
    aria-labelledby="loginRegisterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered tuki_login_register_modal__dialog" role="document">
        <div class="modal-content tuki_login_register_modal__content">
            <div class="modal-header tuki_login_register_modal__bar align-items-center">
                <h5 class="modal-title mb-0" id="loginRegisterModalLabel">Iniciar sesión o registrarse</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div id="tony" class="modal-body modal-body-restaurant tuki_login_register_modal__body p-0">
                    <div class="contenedor-form" id="contenedor-form">
                        <!-- contenedor de login -->
                         <!-- <div class="contenedor-column-form"> -->
                        <div id="first-column" class="first-column">
            <form action="#" id="form_login" class="iniciar-sesion" data-login-url="{{ route('tenant_ecommerce_login') }}">
                <h4 class="title mb-2">Iniciar sesión</h4>
                <div id="msg_login" class="alert alert-danger" role="alert" style="display: none;">
                                    Usuario o Contraseña Incorrectos.
                                </div>
                                <div class="form-group">
                                    <label for="email">Correo Electronico:</label>
                                    <input type="email" required class="form-control" id="email"
                                        placeholder="Enter email" name="email">
                                </div>
                                <div class="form-group">
                                    <label for="pwd">Contraseña:</label>
                                    <input type="password" required class="form-control" id="pwd"
                                        placeholder="Enter password" name="password">
                                </div>
                                <button type="submit" class="btn btn-primary">Ingresar</button>
                                <div class="forgot-password-container">
                                    <span class="forgot-password-title">
                                        ¿Olvidaste tu contraseña?
                                    </span>
                                    <p class="forgot-password-text">
                                        Ponte en contacto con tu administrador o proveedor para que te genere una nueva clave de acceso.
                                    </p>
                                </div>
                            </form>
                        </div>
                        <!-- contenedor de registro -->
                        <div id="second-column" class="second-column">
            <form autocomplete="off" action="#" id="form_register" class="registrarse" data-register-url="{{ route('tenant_ecommerce_store_user') }}">
                                <h4 class="title mb-2">Nuevo Registro</h4>
                <div id="msg_register" class="alert alert-danger" role="alert" style="display: none;">
                                    <p id="msg_register_p"></p>
                                </div>
                                <div class="form-group">
                                    <label for="name_reg">Nombres o razón social:</label>
                                    <input type="text" required autocomplete="name" class="form-control" id="name_reg"
                                        placeholder="Ej. Juan Pérez" name="name" minlength="2" maxlength="255">
                                </div>
                                <div class="form-group">
                                    <label for="email_reg">Correo electrónico:</label>
                                    <input type="email" required autocomplete="email" class="form-control" id="email_reg"
                                        placeholder="correo@ejemplo.com" name="email">
                                </div>
                                <div class="form-group">
                                    <label for="pwd_reg">Contraseña:</label>
                                    <input type="password" required autocomplete="new-password" class="form-control" id="pwd_reg"
                                        placeholder="Mínimo 6 caracteres" name="pswd" minlength="6">
                                </div>
                                <div class="form-group">
                                    <label for="pwd_repeat_reg">Repita la contraseña:</label>
                                    <input type="password" required autocomplete="new-password" class="form-control"
                                        id="pwd_repeat_reg" placeholder="Repita contraseña" name="pswd_rpt" minlength="6">
                                </div>
                                <button type="submit" class="btn btn-primary">Registrarse</button>
                            </form>
                        </div>
                        <!-- </div> -->
                        <!-- contenedor overlay -->
                        <div class="terceary-column">
                            <div class="contenedor-iniciar-sesion">
                                <h3>Hola!</h3>
                                <p>Por favor ingrese sus datos para registrarse</p>
                                <button id="iniciar-sesion" class="btn-iniciar-sesion">Iniciar Sesión</button>
                            </div>
                            <div class="contenedor-registro">
                                <h3>Bienvenido!</h3>
                                <p>Por favor ingrese sus credenciales para iniciar sesión</p>
                                <button id="registrarse" class="btn-registrarse">Registrarse!</button>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>

</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const firstColumn = document.getElementById("contenedor-form");
    const btnIniciarSesion = document.getElementById("iniciar-sesion");
    const btnRegistrarse = document.getElementById("registrarse");
    if (btnIniciarSesion && firstColumn) {
        btnIniciarSesion.addEventListener("click", () => {
            firstColumn.classList.remove("active");
        });
    }
    if (btnRegistrarse && firstColumn) {
        btnRegistrarse.addEventListener("click", () => {
            firstColumn.classList.add("active");
        });
    }
});
</script>



@push('scripts')
<!-- <script type="text/javascript" src="{{ asset('porto-ecommerce/assets/js/cart.js') }}"></script> -->
<script type="text/javascript">
    matchPassword();
    submitLogin();
    submitRegister();

    function matchPassword() {
        var password = document.getElementById("pwd_reg"),
            confirm_password = document.getElementById("pwd_repeat_reg");

        function validatePassword() {
            if (password.value != confirm_password.value) {
                confirm_password.setCustomValidity("El Password no coincide.");
            } else {
                confirm_password.setCustomValidity('');
            }
        }

        password.onchange = validatePassword;
        confirm_password.onkeyup = validatePassword;
    }

    function submitLogin() {
        $('#msg_login').hide();

        $('#form_login').submit(function (e) {
            e.preventDefault()
            $.ajax({
                type: "POST",
                dataType: 'JSON',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{route('tenant_ecommerce_login')}}",
                data: $(this).serialize(),
                success: function (data) {
                    if (data.success) {
                        location.reload();
                    } else {
                        $('#msg_login').show();
                    }
                },
                error: function (error_data) {
                    console.log(error_data)
                }
            });
        })

    }

    function submitRegister() {
        $('#msg_register').hide();

        $('#form_register').submit(function (e) {
            e.preventDefault()
            $.ajax({
                type: "POST",
                dataType: 'JSON',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{route('tenant_ecommerce_store_user')}}",
                data: $(this).serialize(),
                success: function (data) {
                    if (data.success) {
                        location.reload();
                    } else {
                        $('#msg_register').show();
                        $('#msg_register_p').text(data.message)
                    }
                },
                error: function (error_data) {
                    console.log(error_data)
                }
            });
        })
    }
</script>
@endpush
