@php
    $tukiRatingCss = $tukiRatingCss ?? true;
    $tukiElementUi = $tukiElementUi ?? false;
@endphp
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('porto-ecommerce/assets/images/icons/favicon.ico') }}">

    <link rel="stylesheet" href="{{ asset('porto-ecommerce/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    {{-- Magnific Popup: necesario para modal “Vista rápida” (main.js); el JS ya viene en plugins.min.js --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    @include('ecommerce::layouts.partials_ecommerce.tuki_theme_variables')

    <link rel="stylesheet" href="{{ asset('css/ecommerce_taukifac.css') }}?v={{ @filemtime(public_path('css/ecommerce_taukifac.css')) }}" />
    <link rel="stylesheet" href="{{ asset('css/ecommerce_tuki_shim.css') }}?v={{ @filemtime(public_path('css/ecommerce_tuki_shim.css')) }}" />

    @if($tukiRatingCss)
    <link rel="stylesheet" href="{{ asset('porto-ecommerce/assets/css/rating.css') }}">
    @endif

    <link rel="stylesheet" href="{{ asset('porto-ecommerce/assets/font-awesome/css/fontawesome-all.min.css') }}">

    @if($tukiElementUi)
    <link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-chalk/index.css">
    @endif

    @if (file_exists(public_path('theme/custom_styles_ecommerce.css')))
    <link rel="stylesheet" href="{{ asset('theme/custom_styles_ecommerce.css') }}" />
    @endif

    @stack('tuki_extra_styles')
    @stack('styles')
