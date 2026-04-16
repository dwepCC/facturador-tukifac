<!DOCTYPE html>
<html lang="es">

<head>
    <title>@yield('title', 'Producto')</title>
    <meta name="keywords" content="Ecommerce, Tienda virtual" />
    <meta name="description" content="Sistema para venta de productos">
    @include('ecommerce::layouts.partials_ecommerce.tuki_head', ['tukiRatingCss' => true, 'tukiElementUi' => false])
</head>

<body class="tuki_body">
    <div class="tuki_page page-wrapper">

        @include('ecommerce::layouts.partials_ecommerce.header')
        @include('ecommerce::layouts.partials_ecommerce.header_bottom_sticky')

        <main class="tuki_main main">
            <div class="tuki_storefront tuki_storefront--product">
                @hasSection('breadcrumb')
                    @yield('breadcrumb')
                @else
                    <nav aria-label="Migas de pan" class="breadcrumb-nav tuki_breadcrumb">
                        <div class="container">
                            <ol class="breadcrumb tuki_breadcrumb__list">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('tenant.ecommerce.index') }}" class="tuki_breadcrumb__link"><i class="fas fa-home" aria-hidden="true"></i><span class="sr-only">Inicio</span></a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Producto</li>
                            </ol>
                        </div>
                    </nav>
                @endif

                <div class="container tuki_product_page__container tuki_pdp_stack">
                    <div class="row tuki_product_page__row tuki_product_page__row--pdp">
                        <div class="col-lg-9 tuki_product_page__main-col">
                            @yield('content')
                        </div>

                        @php
                            $tukiPdpFeaturedFullWidth = true;
                        @endphp
                        @include('ecommerce::layouts.partials_ecommerce.sidebar_product_right')
                    </div>

                    <section class="tuki_pdp_featured_wide d-none d-lg-block" aria-labelledby="tuki-pdp-featured-heading">
                        @include('ecommerce::layouts.partials_ecommerce.widget_products')
                    </section>

                    <section class="featured-section tuki_featured_below" aria-labelledby="tuki-related-products-title">
                        @include('ecommerce::layouts.partials_ecommerce.featured_products_bottom')
                    </section>
                </div>

                {{-- Fuera del .container: position:fixed respecto al viewport (evita recortes por overflow del row/carousel) --}}
                <div class="tuki_pdp_mobile_chrome d-lg-none" data-tuki-pdp-mobile-chrome>
                    <div class="tuki_pdp_sidebar_overlay" data-tuki-pdp-sidebar-overlay aria-hidden="true"></div>
                    <button type="button" class="tuki_pdp_sidebar_open" data-tuki-pdp-sidebar-open aria-controls="tuki-pdp-sidebar" aria-expanded="false" title="Información y destacados">
                        <i class="fas fa-sliders-h" aria-hidden="true"></i>
                        <span class="sr-only">Abrir panel lateral</span>
                    </button>
                </div>
            </div>
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
        'tukiIncludeRatingJs' => true,
        'tukiIncludeCulqi' => false,
        'tukiIncludeSweetalert' => false,
        'tukiIncludeMoment' => false,
        'tukiIncludeAxios' => false,
        'tukiVueFull' => false,
    ])

    <script>
    (function ($) {
        $(function () {
            var $sf = $('.tuki_storefront--product');
            if (!$sf.length) return;
            var $main = $('main.main, main.tuki_main').first();
            var $open = $sf.find('[data-tuki-pdp-sidebar-open]');
            var $overlay = $sf.find('[data-tuki-pdp-sidebar-overlay]');
            if (!$open.length || !$main.length) return;

            function setOpen(open) {
                $main.toggleClass('tuki_pdp_sidebar_panel_open', !!open);
                $('body').toggleClass('tuki_pdp_sidebar_panel_open', !!open);
                $open.attr('aria-expanded', open ? 'true' : 'false');
                $overlay.attr('aria-hidden', open ? 'false' : 'true');
            }

            function closeIfDesktop() {
                if (window.matchMedia('(min-width: 992px)').matches) {
                    setOpen(false);
                }
            }

            $open.on('click', function (e) {
                e.preventDefault();
                setOpen(!$main.hasClass('tuki_pdp_sidebar_panel_open'));
            });
            $overlay.on('click', function () {
                setOpen(false);
            });
            $(document).on('keydown.tukiPdpSidebar', function (e) {
                if (e.key === 'Escape' && $main.hasClass('tuki_pdp_sidebar_panel_open')) {
                    setOpen(false);
                }
            });
            $(window).on('resize.tukiPdpSidebar', closeIfDesktop);
            closeIfDesktop();
        });
    })(jQuery);
    </script>

    @stack('scripts')
</body>

</html>
