<!DOCTYPE html>
<html lang="es">

<head>
    <title>@yield('title', 'Tienda')</title>
    <meta name="keywords" content="ecommerce" />
    <meta name="description" content="eCommerce">
    @include('ecommerce::layouts.partials_ecommerce.tuki_head', ['tukiRatingCss' => true, 'tukiElementUi' => false])
</head>

<body class="tuki_body">
    <div class="tuki_page page-wrapper">

        @include('ecommerce::layouts.partials_ecommerce.header')
        <main class="tuki_main main">
        @yield('content')
        </main>

        <footer class="footer">
            @include('ecommerce::layouts.partials_ecommerce.footer')
        </footer>
    </div>

    <div class="mobile-menu-overlay"></div>

    <div class="mobile-menu-container">
        @include('ecommerce::layouts.partials_ecommerce.mobile_menu')
    </div>

    <div class="newsletter-popup mfp-hide" id="newsletter-popup-form">
        <div class="newsletter-popup-content">
            <img src="{{ asset('porto-ecommerce/assets/images/logo-black.png') }}" alt="Logo" class="logo-newsletter">
            <h2>BE THE FIRST TO KNOW</h2>
            <p>Subscribe to the Porto eCommerce newsletter to receive timely updates from your favorite products.</p>
            <form action="#">
                <div class="input-group">
                    <input type="email" class="form-control" id="newsletter-email" name="newsletter-email"
                        placeholder="Email address" required>
                    <input type="submit" class="btn" value="Go!">
                </div>
            </form>
            <div class="newsletter-subscribe">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" value="1">
                        Don't show this popup again
                    </label>
                </div>
            </div>
        </div>
    </div>

    <a id="scroll-top" href="#top" title="Top" role="button" class="tuki_scroll_top"><i class="fas fa-chevron-up"></i></a>

    @include('ecommerce::layouts.partials_ecommerce.tuki_scripts', [
        'tukiIncludeCart' => true,
        'tukiIncludeNouislider' => false,
        'tukiIncludeRatingJs' => false,
        'tukiIncludeCulqi' => false,
        'tukiIncludeSweetalert' => false,
        'tukiIncludeMoment' => false,
        'tukiIncludeAxios' => false,
        'tukiVueFull' => false,
    ])

    @stack('scripts')
</body>

</html>
