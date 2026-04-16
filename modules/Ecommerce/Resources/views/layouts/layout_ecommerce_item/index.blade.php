<!DOCTYPE html>
<html lang="es">

<head>
    <title>@yield('title', 'Productos')</title>
    <meta name="keywords" content="ecommerce" />
    <meta name="description" content="Tienda eCommerce">
    @include('ecommerce::layouts.partials_ecommerce.tuki_head', ['tukiRatingCss' => true, 'tukiElementUi' => false])
</head>

<body class="tuki_body">
    <div class="tuki_page page-wrapper">

        @include('ecommerce::layouts.partials_ecommerce.header')
        @include('ecommerce::layouts.partials_ecommerce.header_bottom_sticky')

        <main class="tuki_main main">
            <div class="banner banner-cat" style="background-image: url('{{ asset('/porto-ecommerce/assets/images/banners/banner-top.jpg') }}')">
                <div class="banner-content container">
                    <h2 class="banner-subtitle">check out over <span>200+</span></h2>
                    <h1 class="banner-title">INCREDIBLE deals</h1>
                    <a href="#" class="btn btn-dark">Shop Now</a>
                </div>
            </div>

            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('tenant.ecommerce.index') }}"><i class="fas fa-home"></i></a></li>
                        <li class="breadcrumb-item"><a href="#">Electronics</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Headsets</li>
                    </ol>
                </div>
            </nav>

            <div class="container">
                <div class="row">
                    <div class="col-lg-9">
                        <nav class="toolbox">
                            <div class="toolbox-left">
                                <div class="toolbox-item toolbox-sort">
                                    <div class="select-custom">
                                        <select name="orderby" class="form-control">
                                            <option value="menu_order" selected="selected">Default sorting</option>
                                            <option value="popularity">Sort by popularity</option>
                                            <option value="rating">Sort by average rating</option>
                                            <option value="date">Sort by newness</option>
                                            <option value="price">Sort by price: low to high</option>
                                            <option value="price-desc">Sort by price: high to low</option>
                                        </select>
                                    </div>
                                    <a href="#" class="sorter-btn" title="Set Ascending Direction"><span class="sr-only">Set Ascending Direction</span></a>
                                </div>
                            </div>
                            <div class="toolbox-item toolbox-show">
                                <label>Showing 1–9 of 60 results</label>
                            </div>
                            <div class="layout-modes">
                                <a href="category.html" class="layout-btn btn-grid active" title="Grid">
                                    <i class="fas fa-th"></i>
                                </a>
                            </div>
                        </nav>

                        <div class="row row-sm">
                            @yield('content')
                        </div>

                        <nav class="toolbox toolbox-pagination">
                            <div class="toolbox-item toolbox-show">
                                <label>Showing 1–9 of 60 results</label>
                            </div>
                            <ul class="pagination">
                                <li class="page-item disabled">
                                    <a class="page-link page-link-btn" href="#"><i class="fas fa-angle-left"></i></a>
                                </li>
                                <li class="page-item active">
                                    <a class="page-link" href="#">1 <span class="sr-only">(current)</span></a>
                                </li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">4</a></li>
                                <li class="page-item"><span>...</span></li>
                                <li class="page-item"><a class="page-link" href="#">15</a></li>
                                <li class="page-item">
                                    <a class="page-link page-link-btn" href="#"><i class="fas fa-angle-right"></i></a>
                                </li>
                            </ul>
                        </nav>
                    </div>

                    <aside class="sidebar-shop col-lg-3 order-lg-first">
                        <div class="sidebar-wrapper">
                            @include('ecommerce::layouts.partials_ecommerce.sidebar_filter')
                            <div class="widget widget-featured">
                                @include('ecommerce::layouts.partials_ecommerce.widget_products')
                            </div>
                        </div>
                    </aside>
                </div>
            </div>

            <div class="mb-5"></div>
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
        'tukiIncludeNouislider' => true,
        'tukiIncludeRatingJs' => true,
        'tukiIncludeCulqi' => false,
        'tukiIncludeSweetalert' => false,
        'tukiIncludeMoment' => false,
        'tukiIncludeAxios' => false,
        'tukiVueFull' => false,
    ])

    @stack('scripts')
</body>

</html>
