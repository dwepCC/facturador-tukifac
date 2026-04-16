<?php

namespace App\Services;
use App\Models\Tenant\Item;


class TagsIntersect
{

    public function intersect($array, $item_id)
    {
        $itemId = (int) $item_id;
        if ($itemId <= 0) {
            return false;
        }

        $item = Item::query()->with('tags')->find($itemId);
        if (! $item) {
            return false;
        }

        $array2 = $item->tags->pluck('tag_id')->toArray();
        $result = array_intersect((array) $array, $array2);

        return count($result) > 0;
    }
    

}