<!DOCTYPE html>
@php
    $path = explode('/', request()->path());
    $path[1] = (array_key_exists(1, $path) > 0) ? $path[1] : '';
    $path[2] = (array_key_exists(2, $path) > 0) ? $path[2] : '';
    $path[0] = ($path[0] === '') ? 'documents' : $path[0];
    $visual->sidebar_theme = property_exists($visual, 'sidebar_theme') ? $visual->sidebar_theme : ''
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="fixed no-mobile-device custom-scroll
        sidebar-white sidebar-light
        {{--$vc_compact_sidebar->compact_sidebar == true
    || $path[0] === 'pos'
    || $path[0] === 'pos' && $path[1] === 'fast'
    || $path[0] === 'documents' && $path[1] === 'create' ? 'sidebar-left-collapsed' : ''--}}
        {{-- header-{{$visual->navbar ?? 'fixed'}} --}}
        {{-- {{$visual->header == 'dark' ? 'header-dark' : ''}} --}}
        {{-- {{$visual->sidebars == 'dark' ? '' : 'sidebar-light'}} --}}
        {{$visual->bg == 'dark' ? 'dark' : ''}}
        {{ ($path[0] === 'documents' && $path[1] === 'create'
    || $path[0] === 'documents' && $path[1] === 'note'
    || $path[0] === 'quotations' && $path[1] === 'create'
    || $path[0] === 'sale-opportunities' && $path[1] === 'create'
    || $path[0] === 'order-notes' && $path[1] === 'create'
    || $path[0] === 'sale-notes' && $path[1] === 'create'
    || $path[0] === 'purchase-quotations' && $path[1] === 'create'
    || $path[0] === 'purchase-orders' && $path[1] === 'create'
    || $path[0] === 'dispatches' && $path[1] === 'create'
    || $path[0] === 'purchases' && $path[1] === 'create') ? 'newinvoice' : ''}}
        ">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>TUKIFAC - Facturación Electrónica</title>
    <meta name="googlebot" content="noindex">
    <meta name="robots" content="noindex">

    <!-- Pixel Code - https://foculus.work/ -->
    {{--<script defer src="https://foculus.work/pixel/oKIA68Lt0e5WSnMf"></script>--}}
    <!-- END Pixel Code -->

    <script>
        window.vc_visual = window.vc_visual || {};
        window.vc_visual.sidebar_theme = @json($visual->sidebar_theme);
    </script>

    @vite(['resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">

    {{-- <link rel="stylesheet" href="{{ asset('porto-light/vendor/bootstrap/css/bootstrap.css') }}" /> --}}
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/animate/animate.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/font-awesome/5.11/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/meteocons/css/meteocons.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/select2/css/select2.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/select2-bootstrap-theme/select2-bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('porto-light/vendor/datatables/media/css/dataTables.bootstrap4.css') }}" />
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.26.29/sweetalert2.min.css" />
    <link rel="stylesheet" href="{{asset('porto-light/vendor/bootstrap-datepicker/css/bootstrap-datepicker3.css')}}" />

    <link rel="stylesheet" href="{{asset('porto-light/vendor/jquery-ui/jquery-ui.css')}}" />
    <link rel="stylesheet" href="{{asset('porto-light/vendor/jquery-ui/jquery-ui.theme.css')}}" />
    <link rel="stylesheet" href="{{asset('porto-light/vendor/select2/css/select2.css')}}" />
    <link rel="stylesheet" href="{{asset('porto-light/vendor/select2-bootstrap-theme/select2-bootstrap.min.css')}}" />

    <link href="{{ asset('porto-light/vendor/bootstrap-timepicker/css/bootstrap-timepicker.css') }}" rel="stylesheet">
    <link href="{{ asset('porto-light/vendor/bootstrap-daterangepicker/daterangepicker.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="{{asset('porto-light/vendor/bootstrap-timepicker/css/bootstrap-timepicker.css')}}" />

    <link rel="stylesheet" href="{{asset('porto-light/vendor/jquery-loading/dist/jquery.loading.css')}}" />

    <link rel="stylesheet" type="text/css" href="{{ asset('porto-light/master/style-switcher/style-switcher.css')}}">

    <link rel="stylesheet" href="{{ asset('porto-light/css/tukifac.css') }}?v=0.0.12" /><!--tukifac-->
    <link rel="stylesheet" href="{{ asset('css/tukifac-inicio.css') }}?v=0.0.07" /><!--tukifac-->
    <link rel="stylesheet" href="{{ asset('css/tukifac-soporte.css') }}?v=0.0.04" /><!--tukifac-->
    {{--<link rel="stylesheet" href="{{ asset('porto-light/css/theme.css') }}" />--}}
    {{--<link rel="stylesheet" href="{{ asset('porto-light/css/custom.css') }}" />

    @if (file_exists(public_path('theme/custom_styles.css')))
        <link rel="stylesheet" href="{{ asset('theme/custom_styles.css') }}" />
    @endif

    @if($vc_compact_sidebar->skin)
        @if (file_exists(storage_path('app/public/skins/' . $vc_compact_sidebar->skin->filename)))
            <link rel="stylesheet" href="{{ asset('storage/skins/' . $vc_compact_sidebar->skin->filename) }}" />
        @endif
    @endif--}}


    @stack('styles')


    <script src="{{ asset('porto-light/vendor/modernizr/modernizr.js') }}"></script>

    <style>
        body {
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            transition: overflow 0.3s;
        }

        html.sidebar-left-opened,
        html.options-user-mobile-opened {
            overflow: hidden !important;
        }

        body.visible {
            opacity: 1;
        }

        .descarga {
            color: black;
            padding: 5px;
        }

        .el-checkbox__label {
            font-size: 13px;
        }

        .center-el-checkbox {
            display: flex;
            align-items: center;
        }

        .center-el-checkbox .el-checkbox {
            margin-bottom: 0
        }

        .logo-light {
            display: block;
        }

        .logo-dark {
            display: none;
        }

        html.dark .logo-light {
            display: var(--show-light-logo, none);
        }

        html.dark .logo-dark {
            display: var(--show-dark-logo, block);
        }

        /* Spinner de carga global TUKIFAC */
/* Spinner de carga global TUKIFAC */
.global-loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    /*transition: opacity 0.3s ease;*/
    /*backdrop-filter: blur(10px);*/
}

.global-loader .loader-content {
    text-align: center;
    /*background: white;*/
    padding: 3rem;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0);
    /*border: 1px solid #e0e0e0;*/
}

/* Estilos para la imagen del loader */
.global-loader .loader-image {
    width: 150px;
    /*height: 80px;*/
    object-fit: contain;
    animation: pulse 1.5s ease-in-out infinite;
    margin-bottom: 0.2rem;
}

/* Animación de pulso para la imagen */
@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.global-loader p {
    margin-top: 1rem;
    color: #333;
    /*font-weight: 600;*/
    font-size: 1rem;
    font-family: 'Montserrat', sans-serif;
}

.global-loader.hidden {
    opacity: 0;
    pointer-events: none;
}

/* Dark mode support */
html.dark .global-loader {
    background: rgba(0, 0, 0, 0.95);
}

html.dark .global-loader .loader-content {
    background: #2d2d2d;
    border-color: #404040;
}

html.dark .global-loader p {
    color: white;
}
        /* Spinner de carga global TUKIFAC */
    </style>

    @if ($vc_company->favicon)
        <link rel="shortcut icon" type="image/png" href="{{ asset($vc_company->favicon) }}" />
    @endif

    {{--<script async src="https://social.buho.la/pixel/y9nonmie9j8dkwha20ct2ua7nwsywi2m"></script>--}}
</head>

<body class="pr-0"
    data-tenant="true"
    data-company-title="{{ $vc_company->title_web }}">

<!-- Spinner de carga global TUKIFAC-->
<div id="global-loader" class="global-loader">
    <div class="loader-content">
        <img src="{{ asset('storage/tuki-load.webp') }}" alt="TUKIFAC" class="loader-image">
        <p class="">Cargando...</p>
    </div>
</div>

    <section class="body">
        @php
            $firstLevel = $path[0] ?? null;
        @endphp
        <!-- start: header -->
        {{-- @include('tenant.layouts.partials.header') --}}
        <!-- end: header -->
        <div class="header-background" style="background: #f4f4f4 url('{{ asset('storage/top_nav_s445.png') }}');"></div>
        <div class="inner-wrapper">
            <!-- start: sidebar -->
            @include('tenant.layouts.partials.sidebar')
            <!-- end: sidebar -->
            <section role="main" class="content-body" id="{{ ($firstLevel === 'inicio' || $firstLevel === 'soporte') ? 'main-inicio' : 'main-wrapper' }}">
                @include('tenant.layouts.partials.header')
                @yield('content')
                @include('tenant.layouts.partials.sidebar_styles')
                {{-- @include('tenant.layouts.partials.sidebar_establishment') --}}

                @include('tenant.layouts.partials.check_last_password_update')

            </section>

            @yield('package-contents')
        </div>
    </section>
    {{--@if($show_ws)
        @if(strlen($phone_whatsapp) > 0)
            <a class='ws-flotante d-flex align-items-center justify-content-center' href='https://wa.me/{{$phone_whatsapp}}'
                target="BLANK"
                style="font-size: 45px; color: #fff !important; background-color: #0074ff; text-decoration: none; border-radius: 30% !important;">
                <i class="fab fa-whatsapp"></i>
            </a>
        @endif
    @endif--}}


    <!-- Vendor -->
    <script src="{{ asset('porto-light/vendor/jquery/jquery.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/jquery-browser-mobile/jquery.browser.mobile.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/jquery-cookie/jquery-cookie.js')}}"></script>
    {{--
    <script src="{{ asset('porto-light/master/style-switcher/style.switcher.js')}}"></script> --}}
    <script src="{{ asset('porto-light/vendor/popper/umd/popper.min.js')}}"></script>
    {{-- <script src="{{ asset('porto-light/vendor/bootstrap/js/bootstrap.js')}}"></script> --}}
    {{--
    <script src="{{ asset('porto-light/vendor/common/common.js')}}"></script> --}}
    <script src="{{ asset('porto-light/vendor/nanoscroller/nanoscroller.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/magnific-popup/jquery.magnific-popup.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/jquery-placeholder/jquery-placeholder.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/select2/js/select2.js') }}"></script>
    <script src="{{ asset('porto-light/vendor/datatables/media/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('porto-light/vendor/datatables/media/js/dataTables.bootstrap4.min.js')}}"></script>

    {{-- Specific Page Vendor --}}
    <script src="{{asset('porto-light/vendor/jquery-ui/jquery-ui.js')}}"></script>
    <script src="{{asset('porto-light/vendor/jqueryui-touch-punch/jqueryui-touch-punch.js')}}"></script>
    <!--<script src="{{asset('porto-light/vendor/select2/js/select2.js')}}"></script>-->

    <script src="{{asset('porto-light/vendor/jquery-loading/dist/jquery.loading.js')}}"></script>

    <!--<script src="assets/vendor/select2/js/select2.js"></script>-->
    {{--
    <script src="{{asset('porto-light/vendor/bootstrap-multiselect/bootstrap-multiselect.js')}}"></script>--}}

    <!-- Moment -->
    {{--
    <script src="{{ asset('porto-light/vendor/moment/moment.js') }}"></script>--}}

    <!-- DatePicker -->
    {{--
    <script src="{{asset('porto-light/vendor/bootstrap-datepicker/js/bootstrap-datepicker.js')}}"></script>--}}

    <!-- Date range Plugin JavaScript -->
    {{--
    <script src="{{ asset('porto-light/vendor/bootstrap-timepicker/bootstrap-timepicker.js') }}"></script>--}}
    {{--
    <script src="{{ asset('porto-light/vendor/bootstrap-daterangepicker/daterangepicker.js') }}"></script>--}}

    <!-- Theme Initialization Files -->
    {{--
    <script src="{{asset('porto-light/js/theme.init.js')}}"></script> --}}

    {{--
    <script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>--}}
    {{--
    <script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>--}}

    @stack('scripts')

    {{-- <script src="{{ asset('js/manifest.js') }}"></script> --}}
    {{-- <script src="{{ asset('js/vendor.js') }}"></script> --}}
    <!-- Theme Base, Components and Settings -->
    <script src="{{asset('porto-light/js/theme.js')}}"></script>

    <!-- Theme Custom -->
    <script src="{{asset('porto-light/js/custom.js')}}"></script>
    <script src="{{asset('porto-light/js/jquery.xml2json.js')}}"></script>

    <script>

        function parseXMLToJSON(source) {
            let transform = $.xml2json(source);
            return transform
        }

        $(document).ready(function () {
            $('#dropdown-notifications').click(function (e) {
                $('#dropdown-notifications').toggleClass('showed');
                $('#dn-toggle').toggleClass('show');
                $('#dn-menu').toggleClass('show');
                e.stopPropagation();
            });
        });

        $(document).click(function () {
            $('#dropdown-notifications').removeClass('showed');
            $('#dn-toggle').removeClass('show');
            $('#dn-menu').removeClass('show');
        });

    </script>
    <!-- <script src="//code.tidio.co/1vliqewz9v7tfosw5wxiktpkgblrws5w.js"></script> -->

    <!--tukifac-->
    <script>
        // Control del spinner global
        document.addEventListener('DOMContentLoaded', function() {
            const globalLoader = document.getElementById('global-loader');
            
            // Ocultar spinner cuando la página esté completamente cargada
            window.addEventListener('load', function() {
                setTimeout(() => {
                    if(globalLoader) {
                        globalLoader.classList.add('hidden');
                        // Remover completamente después de la animación
                        setTimeout(() => {
                            globalLoader.remove();
                        }, 300);
                    }
                }, 500);
            });

            // Mostrar body cuando el spinner se oculte
            document.body.classList.add('visible');
        });

        // Función global para mostrar/ocultar spinner
        window.showLoader = function(show = true) {
            let loader = document.getElementById('global-loader');
            
            if (!loader && show) {
                // Crear loader si no existe
                loader = document.createElement('div');
                loader.id = 'global-loader';
                loader.className = 'global-loader';
                loader.innerHTML = `
                    <div class="loader-content">
                        <img src="{{ asset('img/tukifac-logo-loader.png') }}" alt="TUKIFAC" class="loader-image">
                        <p class="mt-2">Procesando TUKIFAC...</p>
                    </div>
                `;
                document.body.prepend(loader);
            }
            
            if (loader) {
                loader.classList.toggle('hidden', !show);
            }
        };
</script>
<!--scripT TUKIFAC-->
</body>

</html>