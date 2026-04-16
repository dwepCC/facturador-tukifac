<div class="widget widget-featured tuki_sidebar_featured">
    <h3 id="tuki-pdp-featured-heading" class="widget-title tuki_sidebar_featured__heading">Productos destacados</h3>

    <div class="widget-body">
        @php
            $sidebarFeatured = $items->take(3)->merge($items->take(-3))->unique('id')->values();

            $configurationModel = \App\Models\Tenant\Configuration::first();
            $defaultImage = $configurationModel->product_default_image ?? 'imagen-no-disponible.jpg';
            $defaultImagePath = $defaultImage === 'imagen-no-disponible.jpg'
                ? asset('logo/imagen-no-disponible.jpg')
                : asset('storage/defaults/' . $defaultImage);
        @endphp

        <div class="tuki_sidebar_featured__grid" role="list">
            @foreach ($sidebarFeatured as $item)
                @php
                    $itemImagePath = ($item->image && $item->image !== 'imagen-no-disponible.jpg')
                        ? asset('storage/uploads/items/'.$item->image)
                        : $defaultImagePath;
                @endphp
                <article class="tuki_sidebar_featured__card" role="listitem">
                    <a href="{{ route('tenant.ecommerce.item', ['id' => $item->id]) }}" class="tuki_sidebar_featured__thumb">
                        <img src="{{ $itemImagePath }}" alt="{{ $item->description }}" loading="lazy"
                            onerror="this.onerror=null;this.src={{ json_encode($defaultImagePath) }};">
                    </a>
                    <div class="tuki_sidebar_featured__meta">
                        <h4 class="tuki_sidebar_featured__name">
                            <a href="{{ route('tenant.ecommerce.item', ['id' => $item->id]) }}">{{ \Illuminate\Support\Str::limit($item->description, 46) }}</a>
                        </h4>
                        <div class="tuki_sidebar_featured__price">
                            @include('ecommerce::layouts.partials_ecommerce.tuki_price_display', ['model' => $item, 'inline' => true])
                        </div>
                        <div class="tuki_sidebar_featured__action">
                            <a href="#" class="paction add-cart tuki_add_cart_btn tuki_sidebar_featured__cart" data-product='@json($item)' title="Agregar al carrito" aria-label="Agregar al carrito">
                                <i class="fas fa-cart-plus" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</div>
