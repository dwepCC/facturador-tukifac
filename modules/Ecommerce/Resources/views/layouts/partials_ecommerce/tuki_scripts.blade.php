@php
    $tukiIncludeCart = $tukiIncludeCart ?? true;
    $tukiIncludeNouislider = $tukiIncludeNouislider ?? false;
    $tukiIncludeRatingJs = $tukiIncludeRatingJs ?? false;
    $tukiIncludeCulqi = $tukiIncludeCulqi ?? false;
    $tukiIncludeSweetalert = $tukiIncludeSweetalert ?? false;
    $tukiIncludeMoment = $tukiIncludeMoment ?? false;
    $tukiIncludeAxios = $tukiIncludeAxios ?? false;
    $tukiVueFull = $tukiVueFull ?? false;
@endphp
    <script src="{{ asset('porto-ecommerce/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('porto-ecommerce/assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('porto-ecommerce/assets/js/plugins.min.js') }}"></script>
    @if($tukiIncludeNouislider)
    <script src="{{ asset('porto-ecommerce/assets/js/nouislider.min.js') }}"></script>
    @endif
    @if($tukiIncludeCart)
    <script src="{{ asset('porto-ecommerce/assets/js/cart.js') }}"></script>
    @endif
    @if($tukiIncludeCulqi)
    <script src="{{ asset('porto-ecommerce/assets/js/culqi_v3.js') }}"></script>
    @endif
    @if($tukiIncludeSweetalert)
    <script src="{{ asset('porto-ecommerce/assets/js/sweetalert2.all.min.js') }}"></script>
    @endif
    @if($tukiIncludeMoment)
    <script src="{{ asset('porto-ecommerce/assets/js/moment.min.js') }}"></script>
    @endif
    <script src="{{ asset('porto-ecommerce/assets/js/main.js') }}?v={{ @filemtime(public_path('porto-ecommerce/assets/js/main.js')) }}"></script>
    @if($tukiVueFull)
    <script src="{{ asset('porto-ecommerce/assets/js/vue.js') }}"></script>
    @else
    <script src="{{ asset('porto-ecommerce/assets/js/vue.min.js') }}"></script>
    @endif
    @if($tukiIncludeAxios)
    <script src="{{ asset('porto-ecommerce/assets/js/axios.min.js') }}"></script>
    @endif
    @if($tukiIncludeRatingJs)
    <script src="{{ asset('porto-ecommerce/assets/js/rating.js') }}"></script>
    @endif

    @stack('tuki_vendor_scripts')
