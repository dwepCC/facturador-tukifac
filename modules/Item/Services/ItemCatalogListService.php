<?php

namespace Modules\Item\Services;

use App\Models\Tenant\Item;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Consulta paginada del catálogo de productos para consumo externo (listados ligeros).
 */
class ItemCatalogListService
{
    public function paginate(array $input): LengthAwarePaginator
    {
        $page = max(1, (int) ($input['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($input['per_page'] ?? 15)));

        $includeInactive = $this->toBool($input['include_inactive'] ?? false);
        $applyStoreOnly = $this->toBool($input['apply_store'] ?? false);
        $includeServices = $this->toBool($input['include_services'] ?? false);
        $withGallery = $this->toBool($input['with_gallery'] ?? false);

        $query = Item::query()
            ->setEagerLoads([])
            ->select([
                'items.id',
                'items.name',
                'items.second_name',
                'items.description',
                'items.internal_id',
                'items.item_code',
                'items.item_code_gs1',
                'items.barcode',
                'items.sale_unit_price',
                'items.stock',
                'items.currency_type_id',
                'items.unit_type_id',
                'items.category_id',
                'items.image',
                'items.image_medium',
                'items.image_small',
                'items.active',
                'items.apply_store',
            ]);

        if (! $includeInactive) {
            $query->whereIsActive();
        }

        if (! $includeServices) {
            $query->whereNotService();
        }

        if ($applyStoreOnly) {
            $query->where('items.apply_store', true);
        }

        if (! empty($input['category_id'])) {
            $query->where('items.category_id', (int) $input['category_id']);
        }

        if (! empty($input['search'])) {
            $term = trim((string) $input['search']);
            $like = '%' . addcslashes($term, '%_\\') . '%';

            $query->where(function ($q) use ($like) {
                $q->where('items.name', 'like', $like)
                    ->orWhere('items.second_name', 'like', $like)
                    ->orWhere('items.description', 'like', $like)
                    ->orWhere('items.internal_id', 'like', $like)
                    ->orWhere('items.item_code', 'like', $like)
                    ->orWhere('items.item_code_gs1', 'like', $like)
                    ->orWhere('items.barcode', 'like', $like)
                    ->orWhereHas('item_unit_types', function ($uq) use ($like) {
                        $uq->where('item_unit_types.barcode', 'like', $like);
                    });
            });
        }

        $eager = [
            'category:id,name',
            'currency_type:id,symbol',
            // cat_unit_types: columnas reales id, active, symbol, description (no existe name)
            'unit_type:id,description,symbol,active',
        ];

        if ($withGallery) {
            $eager['images'] = static function ($relation) {
                $relation->select('id', 'item_id', 'image')->orderBy('id')->limit(16);
            };
        }

        $query->with($eager);

        return $query
            ->orderBy('items.description')
            ->orderBy('items.name')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    private function toBool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
