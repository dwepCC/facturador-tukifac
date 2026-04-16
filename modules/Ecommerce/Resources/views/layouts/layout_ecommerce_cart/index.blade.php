<!DOCTYPE html>
<html lang="es">

<head>
    <title>@yield('title', 'Carrito')</title>
    <meta name="keywords" content="ecommerce" />
    <meta name="description" content="Carrito eCommerce">
    @include('ecommerce::layouts.partials_ecommerce.tuki_head', ['tukiRatingCss' => true, 'tukiElementUi' => false])
</head>

<body class="tuki_body">
    <div class="tuki_page page-wrapper">
        @include('ecommerce::layouts.partials_ecommerce.header')
        @include('ecommerce::layouts.partials_ecommerce.header_bottom_sticky')
        <main class="tuki_main main">
            {{-- Mismo ancho que la vista principal: .tuki_storefront aplica max-width a .container (shim CSS) --}}
            <div class="tuki_storefront tuki_storefront--cart">
                <nav aria-label="Migas de pan" class="breadcrumb-nav tuki_breadcrumb">
                    <div class="container">
                        <ol class="breadcrumb tuki_breadcrumb__list">
                            <li class="breadcrumb-item">
                                <a href="{{ route('tenant.ecommerce.index') }}" class="tuki_breadcrumb__link"><i class="fas fa-home" aria-hidden="true"></i><span class="sr-only">Inicio</span></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Carrito</li>
                        </ol>
                    </div>
                </nav>

                <div class="container tuki_cart-page-wrap">
                    @yield('content')
                </div>
            </div>

            <div class="mb-6"></div>
        </main>

        <footer class="footer">
            @include('ecommerce::layouts.partials_ecommerce.footer')
        </footer>
    </div>

    <div class="mobile-menu-overlay"></div>

    <div class="mobile-menu-container">
        @include('ecommerce::layouts.partials_ecommerce.mobile_menu')
    </div>

    <a id="scroll-top" href="#top" title="Top" role="button" class="tuki_scroll_top"><i class="fas fa-chevron-up"></i></a>

    @include('ecommerce::layouts.partials_ecommerce.tuki_scripts', [
        'tukiIncludeCart' => true,
        'tukiIncludeNouislider' => false,
        'tukiIncludeRatingJs' => false,
        'tukiIncludeCulqi' => true,
        'tukiIncludeSweetalert' => true,
        'tukiIncludeMoment' => true,
        'tukiIncludeAxios' => true,
        'tukiVueFull' => true,
    ])

    @stack('scripts')
</body>

</html>
