@php
    $systemConfiguration = \App\Models\Tenant\Configuration::first();
    $defaultImage = $systemConfiguration->product_default_image ?? 'imagen-no-disponible.jpg';
    $defaultImagePath = $defaultImage === 'imagen-no-disponible.jpg'
        ? asset('logo/imagen-no-disponible.jpg')
        : asset('storage/defaults/' . $defaultImage);

    $stockControlEnabled = (bool) $configuration;
@endphp

@foreach ($dataPaginate as $item)
    @php
        $imagePath = $item->image !== 'imagen-no-disponible.jpg'
            ? asset('storage/uploads/items/' . $item->image)
            : $defaultImagePath;

        $outOfStock = false;
        if ($stockControlEnabled) {
            $totalStock = $item->warehouses ? $item->warehouses->sum('stock') : 0;
            $outOfStock = $totalStock <= 0;
        }
    @endphp

    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <div class="product product-style tuki_product_tile {{ $outOfStock ? 'productdisabled' : '' }}">
            <figure class="product-image-container product-image-container-ecommerce tuki_product_tile__figure">
                <a href="/ecommerce/item/{{ $item->id }}" class="product-image product-image-list tuki_product_tile__media">
                    <img src="{{ $imagePath }}" class="image" alt="{{ $item->description }}" loading="lazy"
                        onerror="this.onerror=null;this.src={{ json_encode($defaultImagePath) }};">
                </a>
                <a href="{{ route('item_partial', ['id' => $item->id]) }}" class="btn-quickview tuki_product_tile__quickview">Vista rápida</a>
                @if(json_encode($item->is_new) == 1)
                    <span class="product-label label-hot">Nuevo</span>
                @endif
                @if($outOfStock)
                    <span class="product-label product-danger">AGOTADO</span>
                @endif
            </figure>

            <div class="product-details-ecommerce tuki_product_tile__content">
                <div class="product-information">
                    <h2 class="product-title-ecommerce">
                        <a href="/ecommerce/item/{{ $item->id }}">{{ $item->description }}</a>
                    </h2>

                    @if(isset($preferences['show_description']) && $preferences['show_description'] == 1)
                        <p class="text-muted product-description {{ $item->name ? '' : 'product-description--empty' }}">
                            {{ $item->name ? $item->name : 'Sin descripción disponible.' }}
                        </p>
                    @endif

                    @if(isset($preferences['show_stock']) && $preferences['show_stock'] == 1)
                        @if($item->stock > 0)
                            <div class="product-stock">Disponible: <span>{{ number_format($item->getStockByWarehouseMain(), 0) }}</span></div>
                        @else
                            <div class="product-stock text-danger">Sin stock</div>
                        @endif
                    @endif
                </div>

                <div class="product-price-ecommerce tuki_product_tile__buyrow">
                    <div class="price-box-ecommerce">
                        @include('ecommerce::layouts.partials_ecommerce.tuki_price_display', ['model' => $item, 'inline' => true])
                    </div>
                    <div class="product-action">
                        <a href="#" class="paction add-cart tuki_add_cart_btn" data-product='@json($item)' title="Agregar al carrito" aria-label="Agregar al carrito">
                            <i class="fas fa-cart-plus" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach
