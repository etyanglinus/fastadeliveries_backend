<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Store;
use Illuminate\Contracts\Pagination\Paginator;

class BrandSiteCatalog
{
    public function page(Store $store, int $page = 1): Paginator
    {
        return Item::query()->active([(int) $store->zone_id], $store->module_id)
            ->where('store_id', $store->id)
            ->orderByDesc('id')
            ->simplePaginate(24, ['*'], 'page', $page)
            ->through(fn (Item $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'image' => $item->image_full_url,
            ]);
    }
}
