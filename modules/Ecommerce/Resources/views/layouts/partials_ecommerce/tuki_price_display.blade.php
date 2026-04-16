{{--
    Precio tienda: siempre se cobra/muestra sale_unit_price en BD.
    - Si existe suggested_price > venta: se muestra como “antes” (dato real).
    - Si no: “antes” = venta × TUKI_PRICE_VISUAL_MARKUP (solo impresión de oferta; el precio real no cambia).
--}}
@php
    $TUKI_PRICE_VISUAL_MARKUP = 1.20;

    $sale = (float) ($model->sale_unit_price ?? 0);
    $sym = data_get($model->currency_type, 'symbol', 'S/');

    $suggested = (float) (data_get($model, 'suggested_price', 0) ?? 0);

    if ($suggested > ($sale + 0.0001)) {
        $before = $suggested;
    } elseif ($sale > 0) {
        $before = round($sale * $TUKI_PRICE_VISUAL_MARKUP, 2);
    } else {
        $before = null;
    }

    $showBefore = $before !== null && $before > ($sale + 0.0001);
@endphp
<div class="tuki_price_stack{{ !empty($inline) ? ' tuki_price_stack--inline' : '' }}">
    @if($showBefore)
        <span class="tuki_price_prev">{{ $sym }} {{ number_format($before, 2) }}</span>
    @endif
    <span class="tuki_price_amount">{{ $sym }} {{ number_format($sale, 2) }}</span>
</div>
