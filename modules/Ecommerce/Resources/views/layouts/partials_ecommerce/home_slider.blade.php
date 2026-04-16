{{-- 
    - vista slider promociones
    - var items definida en Modules\Ecommerce\Http\ViewComposers\PromotionsViewComposer
--}}
@php
    $banners = $items->filter(fn($i) => $i->type !== 'spots');
@endphp

@if($banners->isNotEmpty())
<div class="tuki_slider_wrapper banner-slider-wrapper {{ $full_width_banner ? 'full-width-banner tuki_slider_wrapper--full' : '' }}" style="position: relative;">
    <div class="home-slider ecommerce owl-carousel owl-carousel-lazy owl-theme owl-theme-light {{ $full_width_banner ? 'full-width-banner' : '' }}">
        @foreach ($banners as $item)
            <div class="home-slide">
                @php
                    $bannerHref = !empty($item->item_id)
                        ? url('/ecommerce/item/'.$item->item_id.'/'.$item->id)
                        : null;
                @endphp

                @if($bannerHref)
                    <a href="{{ $bannerHref }}" class="banner-slide-link" aria-label="Ver producto">
                @endif

                {{-- Owl lazy carga imágenes con <img data-src>; un div con data-src no siempre aplica el fondo --}}
                <img
                    class="owl-lazy tuki_slide_image"
                    data-src="{{ asset('storage/uploads/promotions/'.$item->image) }}"
                    src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
                    alt="{{ $item->name ?? 'Promoción' }}"
                    width="1600"
                    height="640"
                    decoding="async"
                >
                <div class="home-slide-content text-white">
                    {{-- <h1>{{ $item->name }}</h1>
                    <p>{{ $item->description }}</p>
                    <a href="/ecommerce/item/{{ $item->item_id }}/{{ $item->id }}" class="btn btn-dark">
                        Comprar Ahora!
                    </a> --}}
                </div>

                @if($bannerHref)
                    </a>
                @endif
            </div>
        @endforeach
    </div>

    @if($banners->count() > 1)
    <button type="button" class="banner-nav-btn banner-nav-prev" onclick="window.navigateEcommerceBanner('prev')">
        <span>
            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg>
        </span>
    </button>
    <button type="button" class="banner-nav-btn banner-nav-next" onclick="window.navigateEcommerceBanner('next')">
        <span>
            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chevron-right"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg>
        </span>
    </button>
    @endif
</div>

@push('styles')
<style>
.banner-slider-wrapper {
    position: relative;
}

.banner-slide-link {
    display: block;
    width: 100%;
    height: 100%;
    color: inherit;
    text-decoration: none;
}
</style>
@endpush

@push('scripts')
<script>
window.navigateEcommerceBanner = function (direction) {
    if (typeof jQuery === 'undefined') return;
    var owl = jQuery('.home-slider.ecommerce');
    if (direction === 'next') {
        owl.trigger('next.owl.carousel');
    } else {
        owl.trigger('prev.owl.carousel');
    }
};

jQuery(function ($) {
    var $owl = $('.home-slider.ecommerce');
    if (!$owl.length) return;

    function numberOwlDots() {
        var $dots = $owl.find('.owl-dots .owl-dot span');
        if (!$dots.length) return;

        $owl.attr('data-dots-numbered', '1');
        $dots.each(function (index) {
            $(this).text(index + 1);
        });
    }

    $owl.on('initialized.owl.carousel refreshed.owl.carousel', numberOwlDots);
    /* main.js puede inicializar Owl antes que este script (al final del body) */
    setTimeout(numberOwlDots, 0);
    setTimeout(numberOwlDots, 200);
    setTimeout(numberOwlDots, 800);
});
</script>
@endpush
@endif
