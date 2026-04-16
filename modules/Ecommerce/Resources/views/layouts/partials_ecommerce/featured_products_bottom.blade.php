@inject('intersectTag', 'App\Services\TagsIntersect')

@php
    $currentItemId = (int) (request()->route('id') ?? 0);
    if ($currentItemId <= 0 && request()->route()) {
        $rid = request()->route()->parameter('id');
        if ($rid !== null && is_numeric($rid)) {
            $currentItemId = (int) $rid;
        }
    }
    if ($currentItemId <= 0) {
        $segments = request()->segments();
        foreach ($segments as $i => $segment) {
            if ($segment === 'item' && isset($segments[$i + 1]) && is_numeric($segments[$i + 1])) {
                $currentItemId = (int) $segments[$i + 1];
                break;
            }
        }
    }

    $pool = $items->filter(function ($item) {
        return (int) ($item->id ?? 0) > 0;
    })->values();

    $excludeCurrent = function ($item) use ($currentItemId) {
        return (int) ($item->id ?? 0) !== $currentItemId;
    };

    $byTags = $pool->filter(function ($item) use ($excludeCurrent, $currentItemId, $intersectTag) {
        if (! $excludeCurrent($item)) {
            return false;
        }
        if ($currentItemId <= 0) {
            return false;
        }

        return $intersectTag->intersect($item->tags ?? [], $currentItemId);
    })->values();

    $relatedItems = $byTags;
    $relatedSource = $byTags->isNotEmpty() ? 'tags' : null;

    if ($relatedItems->isEmpty() && $currentItemId > 0) {
        $currentRow = $pool->first(function ($row) use ($currentItemId) {
            return (int) ($row->id ?? 0) === $currentItemId;
        });
        $currentCategoryId = $currentRow ? ($currentRow->category_id ?? null) : null;
        if ($currentCategoryId === null || $currentCategoryId === '') {
            $currentCategoryId = optional(\App\Models\Tenant\Item::query()->find($currentItemId))->category_id;
        }
        if ($currentCategoryId !== null && $currentCategoryId !== '') {
            $relatedItems = $pool->filter(function ($item) use ($excludeCurrent, $currentCategoryId) {
                if (! $excludeCurrent($item)) {
                    return false;
                }

                return (int) ($item->category_id ?? 0) === (int) $currentCategoryId;
            })->values();
            if ($relatedItems->isNotEmpty()) {
                $relatedSource = 'category';
            }
        }
    }

    if ($relatedItems->isEmpty() && $currentItemId > 0) {
        $relatedItems = $pool->filter($excludeCurrent)->take(12)->values();
        if ($relatedItems->isNotEmpty()) {
            $relatedSource = 'store';
        }
    }

    if ($relatedItems->isEmpty() && $currentItemId <= 0) {
        $relatedItems = $pool->take(12)->values();
        $relatedSource = $relatedItems->isNotEmpty() ? 'store' : null;
    }

    $configurationModel = \App\Models\Tenant\Configuration::first();
    $defaultImage = $configurationModel->product_default_image ?? 'imagen-no-disponible.jpg';
    $defaultImagePath = $defaultImage === 'imagen-no-disponible.jpg'
        ? asset('logo/imagen-no-disponible.jpg')
        : asset('storage/defaults/' . $defaultImage);
@endphp

<div class="tuki_featured_below__inner">
    <div class="tuki_featured_below__head">
        <h2 id="tuki-related-products-title" class="tuki_featured_below__title">También te puede interesar</h2>
        <p class="tuki_featured_below__subtitle">
            @if($relatedSource === 'tags')
                Productos que comparten etiquetas con el que estás viendo.
            @elseif($relatedSource === 'category')
                Otros productos de la misma categoría.
            @elseif($relatedSource === 'store')
                Más productos disponibles en la tienda.
            @else
                Productos relacionados.
            @endif
        </p>
    </div>

    @if($relatedItems->isEmpty())
        <p class="tuki_featured_below__empty" role="status">No hay productos para mostrar en este bloque.</p>
    @else
        <div class="row tuki_related_strip mx-n1 mx-md-n2">
            @foreach ($relatedItems as $item)
                @php
                    $itemImagePath = ($item->image && $item->image !== 'imagen-no-disponible.jpg')
                        ? asset('storage/uploads/items/'.$item->image)
                        : $defaultImagePath;
                @endphp
                <div class="col-6 col-sm-4 col-md-3 col-lg-2 px-1 px-md-2 mb-3 tuki_related_strip__col">
                    <div class="product product-style tuki_product_tile tuki_related_strip__tile h-100">
                        <figure class="product-image-container product-image-container-ecommerce tuki_product_tile__figure">
                            <a href="{{ route('tenant.ecommerce.item', ['id' => $item->id]) }}" class="product-image product-image-list tuki_product_tile__media">
                                <img src="{{ $itemImagePath }}" class="image" alt="{{ $item->description }}" loading="lazy"
                                    onerror="this.onerror=null;this.src={{ json_encode($defaultImagePath) }};">
                            </a>
                            <a href="{{ route('item_partial', ['id' => $item->id]) }}" class="btn-quickview tuki_product_tile__quickview">Vista rápida</a>
                        </figure>
                        <div class="product-details-ecommerce tuki_product_tile__content">
                            <div class="product-information">
                                <h2 class="product-title-ecommerce">
                                    <a href="{{ route('tenant.ecommerce.item', ['id' => $item->id]) }}">{{ \Illuminate\Support\Str::limit($item->description, 48) }}</a>
                                </h2>
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
        </div>
    @endif
</div>
