@extends('ecommerce::layouts.layout_ecommerce_item.record')

@section('title', strip_tags($record->description ?? 'Producto'))

@section('breadcrumb')
    <nav aria-label="Migas de pan" class="breadcrumb-nav tuki_breadcrumb">
        <div class="container">
            <ol class="breadcrumb tuki_breadcrumb__list">
                <li class="breadcrumb-item">
                    <a href="{{ route('tenant.ecommerce.index') }}" class="tuki_breadcrumb__link"><i class="fas fa-home" aria-hidden="true"></i><span class="sr-only">Inicio</span></a>
                </li>
                @if(!empty($record->category))
                    @php
                        $ecomCategoryBreadcrumbHref = null;
                        if (!empty($record->category->id)) {
                            $ecomCategoryBreadcrumbHref = route('tenant.ecommerce.category', ['category' => $record->category->id]);
                        } elseif (!empty($record->category->name)) {
                            $ecomCategorySlug = \Illuminate\Support\Str::slug($record->category->name, '-');
                            if ($ecomCategorySlug !== '') {
                                $ecomCategoryBreadcrumbHref = route('tenant.ecommerce.category', ['category' => $ecomCategorySlug]);
                            }
                        }
                    @endphp
                    @if(!empty($ecomCategoryBreadcrumbHref))
                        <li class="breadcrumb-item">
                            <a href="{{ $ecomCategoryBreadcrumbHref }}" class="tuki_breadcrumb__link">{{ $record->category->name }}</a>
                        </li>
                    @endif
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit(strip_tags($record->description), 52) }}</li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')

@php
    $configurationModel = \App\Models\Tenant\Configuration::first();
    $ecommerceConfiguration = \App\Models\Tenant\ConfigurationEcommerce::first();
    $phoneWhatsapp = $ecommerceConfiguration->phone_whatsapp ?? $configurationModel->phone_whatsapp ?? null;
    $defaultImage = $configurationModel->product_default_image ?? 'imagen-no-disponible.jpg';
    $defaultImagePath = $defaultImage === 'imagen-no-disponible.jpg'
        ? asset('logo/imagen-no-disponible.jpg')
        : asset('storage/defaults/' . $defaultImage);
    $mainImagePath = ($record->image && $record->image !== 'imagen-no-disponible.jpg')
        ? asset('storage/uploads/items/'.$record->image)
        : $defaultImagePath;
@endphp

<div class="tuki_product_page">
<div class="product-single-container product-single-default tuki_product_page__shell">
    <div class="row tuki_product_page__hero-row g-2 g-lg-3">
        <div class="col-12 col-lg-7 col-md-6 product-single-gallery tuki_product_page__gallery">
            <div class="tuki_product_page__gallery-card">
            <div class="product-slider-container product-item">
                <div class="product-single-carousel owl-carousel owl-theme">
                    <div class="product-item">
                        <img class="product-single-image" src="{{ $mainImagePath }}"
                            data-zoom-image="{{ $mainImagePath }}" />
                            
                    </div>
                    @foreach($record->images as $row)

                        <div class="product-item">
                            @php
                                $loopImagePath = ($row->image && $row->image !== 'imagen-no-disponible.jpg')
                                    ? asset('storage/uploads/items/'.$row->image)
                                    : $defaultImagePath;
                            @endphp
                            <img class="product-single-image" src="{{ $loopImagePath }}"
                                 data-zoom-image="{{ $loopImagePath }}" alt="{{ $record->description }}" />
                        </div>

                    @endforeach
                    <!--<div class="product-item">
                        <img class="product-single-image" src="assets/images/products/zoom/product-2.jpg"
                            data-zoom-image="assets/images/products/zoom/product-2-big.jpg" />
                    </div>
                    <div class="product-item">
                        <img class="product-single-image" src="assets/images/products/zoom/product-3.jpg"
                            data-zoom-image="assets/images/products/zoom/product-3-big.jpg" />
                    </div>
                    <div class="product-item">
                        <img class="product-single-image" src="assets/images/products/zoom/product-4.jpg"
                            data-zoom-image="assets/images/products/zoom/product-4-big.jpg" />
                    </div>-->
                </div>
                <!-- End .product-single-carousel -->
                <button type="button" class="prod-full-screen tuki_product_page__zoom" title="Ver imagen en grande" aria-label="Ver imagen en grande">
                    <span class="tuki_product_page__zoom-ic fas fa-search-plus" aria-hidden="true"></span>
                    <span class="tuki_product_page__zoom-txt">Ver en grande</span>
                </button>
            </div>
            <div class="prod-thumbnail row owl-dots tuki_product_page__thumbs" id='carousel-custom-dots'>
                <div class="col-3 owl-dot">
                    <img src="{{ $mainImagePath }}" alt="{{ $record->description }}" />
                </div>
                @foreach($record->images as $row)
                    <div class="col-3 owl-dot">
                        @php
                            $thumbImagePath = ($row->image && $row->image !== 'imagen-no-disponible.jpg')
                                ? asset('storage/uploads/items/'.$row->image)
                                : $defaultImagePath;
                        @endphp
                        <img src="{{ $thumbImagePath }}" alt="{{ $record->description }}" />
                    </div>
                @endforeach
                <!--<div class="col-3 owl-dot">
                    <img src="assets/images/products/zoom/product-2.jpg" />
                </div>
                <div class="col-3 owl-dot">
                    <img src="assets/images/products/zoom/product-3.jpg" />
                </div>
                <div class="col-3 owl-dot">
                    <img src="assets/images/products/zoom/product-4.jpg" />
                </div> -->
            </div>
            </div>
        </div><!-- End .col-lg-7 -->

        <div class="col-12 col-lg-5 col-md-6 tuki_product_page__info">
            <div class="product-single-details tuki_product_page__details">
                <h1 class="product-title tuki_product_page__title">{{ $record->description }}</h1>

                <div class="tuki_product_page__meta">
                    <div class="ratings-container tuki_product_page__ratings">
                        <div class="product-ratings">
                            <span class="ratings" style="width:60%" aria-hidden="true"></span>
                        </div>
                        <a href="#product-reviews-content" class="rating-link tuki_product_page__reviews-link" role="button"
                            onclick="if (window.jQuery && jQuery.fn.tab) { jQuery('#product-tab-reviews').tab('show'); } if (typeof getRating === 'function') { getRating('{{ $record->id }}'); } return false;">Valoraciones</a>
                    </div>
                </div>

                <div class="tuki_product_page__price price-box">
                    @include('ecommerce::layouts.partials_ecommerce.tuki_price_display', ['model' => $record, 'inline' => false])
                </div>

                <div class="product-desc tuki_product_page__desc">
                    @if(!empty($record->category))
                        <p class="product-category tuki_product_page__category">Categoría: <span>{{ $record->category->name }}</span></p>
                    @endif
                    <p class="product-stock tuki_product_page__stock">
                        Disponible: <strong>{{ number_format($record->stock, 0) }}</strong>
                        @if($record->stock > 0)
                            <span class="tuki_product_page__badge tuki_product_page__badge--ok" role="status">En stock</span>
                        @else
                            <span class="tuki_product_page__badge tuki_product_page__badge--out" role="alert">Sin stock</span>
                        @endif
                    </p>
                    @if(filled($record->name))
                        <p class="tuki_product_page__lead">{{ $record->name }}</p>
                    @endif
                </div>

                @if($record->attributes && count($record->attributes))
                    <dl class="tuki_product_page__attrs">
                        @foreach($record->attributes as $at)
                            <div class="tuki_product_page__attr-row">
                                <dt>{{ $at->description }}</dt>
                                <dd>{{ $at->value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

                <div class="product-filters-container">

                </div>

                <div class="product-action product-all-icons tuki_product_page__actions">
                    <a href="#" class="paction add-cart tuki_add_cart_btn tuki_product_page__add-cart" data-product='@json($record)' title="Agregar al carrito" aria-label="Agregar al carrito">
                        <i class="fas fa-cart-plus" aria-hidden="true"></i>
                        <span>Agregar al carrito</span>
                    </a>

                    @php
                        $showWhatsapp = ($configurationModel->enable_whatsapp ?? false) && !empty($phoneWhatsapp);
                    @endphp
                    @if($showWhatsapp)
                        @php
                            $waPhoneRaw = preg_replace('/\D+/', '', $phoneWhatsapp);
                            $waPhone = (strlen($waPhoneRaw) == 9 && str_starts_with($waPhoneRaw, '9')) ? '51'.$waPhoneRaw : $waPhoneRaw;
                            $waSym = $record->currency_type_symbol ?? data_get($record->currency_type, 'symbol', 'S/');
                            $waText = rawurlencode("Buenas, deseo consultar acerca del producto *{$record->description}*, con precio de {$waSym}{$record->sale_unit_price}. ¿Podrían brindarme más información?");
                            $waLink = "https://wa.me/{$waPhone}?text={$waText}";
                        @endphp
                        <a href="{{ $waLink }}" class="btn-whatsapp tuki_product_page__wa" target="_blank" rel="noopener" title="Consultar por WhatsApp">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9" /><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1" /></svg>
                            <span>WhatsApp</span>
                        </a>
                    @endif
                </div>

                <div class="product-single-share tuki_product_page__share">
                    <div class="addthis_inline_share_toolbox"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="product-single-tabs tuki_product_page__tabs">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active"  id="product-tab-desc" data-toggle="tab" href="#product-desc-content" role="tab"
                aria-controls="product-desc-content" aria-selected="true">Descripcion</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" onclick="getRating('{{ $record->id}}')" id="product-tab-reviews" data-toggle="tab" href="#product-reviews-content" role="tab"
                aria-controls="product-reviews-content" aria-selected="false">Reviews</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="product-tab-especTecn" data-toggle="tab" href="#product-especTecn-content" role="tab" aria-controls="product-especTecn-content" aria-selected="true">Especificaciones Técnicas</a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="product-desc-content" role="tabpanel"
            aria-labelledby="product-tab-desc">
            <div class="product-desc-content">
                <p> {{ $record->description}} </p>
                <p> {{ $record->name}} </p>
            </div><!-- End .product-desc-content -->
        </div><!-- End .tab-pane -->

        <div class="tab-pane fade" id="product-reviews-content" role="tabpanel" aria-labelledby="product-tab-reviews">
            <div class="product-reviews-content">
                <div class="collateral-box">

                    <div class="page">
                        <div class="page__demo">

                            <div class="page__group">
                                <div class="rating">
                                    <input type="radio" name="rating-star2" class="rating__control" id="rc6" onclick="sendRating(1,{{$record->id}})">
                                    <input type="radio" name="rating-star2" class="rating__control" id="rc7" onclick="sendRating(2,{{$record->id}})">
                                    <input type="radio" name="rating-star2" class="rating__control" id="rc8" onclick="sendRating(3,{{$record->id}})">
                                    <input type="radio" name="rating-star2" class="rating__control" id="rc9" onclick="sendRating(4,{{$record->id}})">
                                    <input type="radio" name="rating-star2" class="rating__control" id="rc10" onclick="sendRating(5,{{$record->id}})" >
                                    <label for="rc6" class="rating__item">
                                        <svg class="rating__star">
                                            <use xlink:href="#star"></use>
                                        </svg>
                                        <span class="rating__label">1</span>
                                    </label>
                                    <label for="rc7" class="rating__item">
                                        <svg class="rating__star">
                                            <use xlink:href="#star"></use>
                                        </svg>
                                        <span class="rating__label">2</span>
                                    </label>
                                    <label for="rc8" class="rating__item">
                                        <svg class="rating__star">
                                            <use xlink:href="#star"></use>
                                        </svg>
                                        <span class="rating__label">3</span>
                                    </label>
                                    <label for="rc9" class="rating__item">
                                        <svg class="rating__star">
                                            <use xlink:href="#star"></use>
                                        </svg>
                                        <span class="rating__label">4</span>
                                    </label>
                                    <label for="rc10" class="rating__item">
                                        <svg class="rating__star">
                                            <use xlink:href="#star"></use>
                                        </svg>
                                        <span class="rating__label">5</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" style="display: none">
                        <symbol id="star" viewBox="0 0 26 28">
                            <path
                                d="M26 10.109c0 .281-.203.547-.406.75l-5.672 5.531 1.344 7.812c.016.109.016.203.016.313 0 .406-.187.781-.641.781a1.27 1.27 0 0 1-.625-.187L13 21.422l-7.016 3.687c-.203.109-.406.187-.625.187-.453 0-.656-.375-.656-.781 0-.109.016-.203.031-.313l1.344-7.812L.39 10.859c-.187-.203-.391-.469-.391-.75 0-.469.484-.656.875-.719l7.844-1.141 3.516-7.109c.141-.297.406-.641.766-.641s.625.344.766.641l3.516 7.109 7.844 1.141c.375.063.875.25.875.719z" />
                        </symbol>
                    </svg>

                </div>

            </div>
        </div>

        <div class="tab-pane fade" id="product-especTecn-content" role="tabpanel" aria-labelledby="product-tab-especTecn">
            <div class="product-especTecn-content">
                <p> {!! $record->technical_specifications !!} </p>
            </div><!-- End .product-desc-content -->
        </div><!-- End .tab-pane -->
    </div>
</div>

</div>

@push('scripts')
<script>
(function ($) {
    function tukiPdpTeardownElevateZoom() {
        var $root = $('.tuki_product_page');
        if (!$root.length) {
            return;
        }
        $root.find('.product-single-carousel img.product-single-image').each(function () {
            $(this).removeData('elevateZoom');
        });
        $root.find('.zoomContainer').remove();
    }
    $(function () {
        setTimeout(tukiPdpTeardownElevateZoom, 0);
        setTimeout(tukiPdpTeardownElevateZoom, 400);
    });
    $(document).on('initialized.owl.carousel refreshed.owl.carousel', '.tuki_product_page .product-single-carousel', function () {
        setTimeout(tukiPdpTeardownElevateZoom, 50);
    });
})(jQuery);
</script>
@endpush

@endsection
