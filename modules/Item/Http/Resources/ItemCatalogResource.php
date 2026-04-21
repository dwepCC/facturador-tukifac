<?php

namespace Modules\Item\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Item\Support\ItemImageUrlResolver;

/**
 * @mixin \App\Models\Tenant\Item
 */
class ItemCatalogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'second_name' => $this->second_name,
            'description' => $this->description,
            'internal_id' => $this->internal_id,
            'item_code' => $this->item_code,
            'item_code_gs1' => $this->item_code_gs1,
            'barcode' => $this->barcode,
            'sale_unit_price' => $this->sale_unit_price !== null ? (float) $this->sale_unit_price : null,
            'stock' => $this->stock !== null ? (float) $this->stock : null,
            'currency_symbol' => optional($this->currency_type)->symbol,
            'unit_type' => $this->when(
                $this->relationLoaded('unit_type') && $this->unit_type,
                fn () => [
                    'id' => $this->unit_type->id,
                    'description' => $this->unit_type->description,
                    'symbol' => $this->unit_type->symbol,
                    'active' => (bool) $this->unit_type->active,
                ]
            ),
            'category' => $this->when(
                $this->relationLoaded('category') && $this->category,
                fn () => [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ]
            ),
            'active' => (bool) $this->active,
            'apply_store' => (bool) $this->apply_store,
            'image_url' => ItemImageUrlResolver::itemImage($this->image),
            'image_url_medium' => ItemImageUrlResolver::itemImage($this->image_medium),
            'image_url_small' => ItemImageUrlResolver::itemImage($this->image_small),
            'gallery' => $this->when(
                $this->relationLoaded('images'),
                function () {
                    return $this->images->map(static function ($row) {
                        return [
                            'id' => $row->id,
                            'image_url' => ItemImageUrlResolver::itemImage($row->image),
                        ];
                    })->values()->all();
                }
            ),
        ];
    }
}
