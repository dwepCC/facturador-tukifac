<?php

namespace Modules\Ecommerce\Http\ViewComposers;

use App\Models\Tenant\Item;
use Modules\ApiPeruDev\Data\ServiceData;
use Carbon\Carbon;

class FeaturedProductsViewComposer
{
    public function compose($view)
    {
        $exchange_rate_sale = $this->getExchangeRateSale();
        $now = Carbon::now(); // ✅ Una sola instancia fuera del map

        $items = Item::query()
            ->with(['tags', 'currency_type'])
            ->where('apply_store', 1)
            ->whereNotNull('internal_id')
            ->get()
            ->map(function ($row) use ($exchange_rate_sale, $now) {

                // ✅ Lógica IGV corregida:
                // has_igv = true  → precio YA incluye IGV, no multiplicar
                // has_igv = false → precio SIN IGV, hay que agregar el 18%
                $sale_unit_price = $row->has_igv
                    ? $row->sale_unit_price
                    : $row->sale_unit_price * 1.18;

                // ✅ diffInMonths usando la instancia compartida
                $months = $now->diffInMonths($row->created_at);

                return (object) [
                    'id'                          => $row->id,
                    'category_id'                 => $row->category_id,
                    'internal_id'                 => $row->internal_id,
                    'unit_type_id'                => $row->unit_type_id,
                    'description'                 => $row->description,
                    'name'                        => $row->name,
                    'second_name'                 => $row->second_name,
                    'sale_unit_price'             => $row->currency_type_id === 'PEN'
                                                        ? $sale_unit_price
                                                        : $sale_unit_price * $exchange_rate_sale,
                    'sale_unit'                   => $sale_unit_price,
                    'currency_type_id'            => $row->currency_type_id,
                    'currency_type'               => $row->currency_type,
                    'has_igv'                     => (bool) $row->has_igv,
                    'sale_affectation_igv_type_id'=> $row->sale_affectation_igv_type_id,
                    'currency_type_symbol'        => optional($row->currency_type)->symbol ?? 'S/',
                    'suggested_price'             => (float) ($row->suggested_price ?? 0),
                    'image'                       => $row->image,
                    'image_medium'                => $row->image_medium,
                    'image_small'                 => $row->image_small,
                    // ✅ Usando optional() para evitar errores si tags es null
                    'tags'                        => optional($row->tags)->pluck('tag_id')->toArray() ?? [],
                    // ✅ Corregido: < 1 mes = nuevo producto
                    'is_new'                      => $months < 1 ? 1 : 0,
                ];
            });

        // ✅ Forma correcta de pasar variables a la vista en Laravel
        $view->with('items', $items);
    }

    private function getExchangeRateSale(): float
    {
        $exchange_rate = (new ServiceData())->exchange(date('Y-m-d'));

        return (is_array($exchange_rate) && array_key_exists('sale', $exchange_rate))
            ? (float) $exchange_rate['sale']
            : 1.0;
    }
}