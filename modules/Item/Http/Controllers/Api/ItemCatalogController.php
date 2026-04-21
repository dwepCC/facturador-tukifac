<?php

namespace Modules\Item\Http\Controllers\Api;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Item\Http\Requests\ItemCatalogIndexRequest;
use Modules\Item\Http\Resources\ItemCatalogResource;
use Modules\Item\Services\ItemCatalogListService;

class ItemCatalogController extends Controller
{
    public function index(ItemCatalogIndexRequest $request, ItemCatalogListService $catalogListService): AnonymousResourceCollection
    {
        $paginator = $catalogListService->paginate($request->validated());

        return ItemCatalogResource::collection($paginator);
    }
}
