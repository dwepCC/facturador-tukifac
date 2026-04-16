@php
    $configurationModel = \App\Models\Tenant\Configuration::first();
    $defaultImage = $configurationModel->product_default_image ?? 'imagen-no-disponible.jpg';
    $defaultImagePath = $defaultImage === 'imagen-no-disponible.jpg'
        ? asset('logo/imagen-no-disponible.jpg')
        : asset('storage/defaults/' . $defaultImage);
    $mainImagePath = ($record->image && $record->image !== 'imagen-no-disponible.jpg')
        ? asset('storage/uploads/items/'.$record->image)
        : $defaultImagePath;

    $galleryImages = [];
    $seen = [];
    $push = function ($url) use (&$galleryImages, &$seen) {
        if (! $url || isset($seen[$url])) {
            return;
        }
        $seen[$url] = true;
        $galleryImages[] = $url;
    };
    $push($mainImagePath);
    foreach ($record->images ?? [] as $row) {
        $u = ($row->image && $row->image !== 'imagen-no-disponible.jpg')
            ? asset('storage/uploads/items/'.$row->image)
            : $defaultImagePath;
        $push($u);
    }

    $categoryName = optional($record->category)->name;
@endphp
<div class="tuki_quickview">
    <div class="tuki_quickview__layout">
        <div class="tuki_quickview__media">
            <div class="tuki_quickview__stage">
                <img
                    class="tuki_quickview__mainimg"
                    id="tuki_qv_main_img"
                    src="{{ $galleryImages[0] ?? $mainImagePath }}"
                    alt="{{ $record->description }}"
                    width="640"
                    height="640"
                    decoding="async"
                >
            </div>
            @if(count($galleryImages) > 1)
                <div class="tuki_quickview__thumbs" role="tablist" aria-label="Imágenes del producto">
                    @foreach($galleryImages as $idx => $url)
                        <button
                            type="button"
                            class="tuki_quickview__thumb{{ $idx === 0 ? ' is-active' : '' }}"
                            data-tuki-qv-src="{{ $url }}"
                            aria-label="Ver imagen {{ $idx + 1 }}"
                            aria-pressed="{{ $idx === 0 ? 'true' : 'false' }}"
                        >
                            <img src="{{ $url }}" alt="" loading="lazy" width="72" height="72" decoding="async">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="tuki_quickview__body">
            <div class="tuki_quickview__body-inner">
                <header class="tuki_quickview__header">
                    <h2 class="tuki_quickview__title">{{ $record->description }}</h2>
                    <div class="tuki_quickview__pricebox">
                        @include('ecommerce::layouts.partials_ecommerce.tuki_price_display', ['model' => $record, 'inline' => true])
                    </div>
                </header>

                <div class="tuki_quickview__meta-bar">
                    <dl class="tuki_quickview__meta">
                        @if($categoryName)
                            <div class="tuki_quickview__meta-row">
                                <dt>Categoría</dt>
                                <dd>{{ $categoryName }}</dd>
                            </div>
                        @endif
                        <div class="tuki_quickview__meta-row">
                            <dt>Disponible</dt>
                            <dd>{{ number_format($record->stock ?? 0, 0) }} u.</dd>
                        </div>
                    </dl>
                    <div class="tuki_quickview__badge-slot">
                        @if($record->stock > 0)
                            <span class="tuki_quickview__badge tuki_quickview__badge--ok">En stock</span>
                        @else
                            <span class="tuki_quickview__badge tuki_quickview__badge--out">Sin stock</span>
                        @endif
                    </div>
                </div>

                @if(filled($record->name))
                    <p class="tuki_quickview__desc">{{ $record->name }}</p>
                @endif

                <div class="tuki_quickview__actions">
                    <a href="{{ route('tenant.ecommerce.item', ['id' => $record->id]) }}" class="tuki_quickview__btn tuki_quickview__btn--secondary">
                        Ver detalle
                    </a>
                    <a
                        href="#"
                        class="tuki_quickview__btn tuki_quickview__btn--primary add-cart tuki_add_cart_btn"
                        data-product='@json($record)'
                        title="Agregar al carrito"
                    >
                        <i class="fas fa-cart-plus" aria-hidden="true"></i>
                        <span>Agregar al carrito</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
