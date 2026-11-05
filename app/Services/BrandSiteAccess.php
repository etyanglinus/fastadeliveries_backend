<?php

namespace App\Services;

use App\Models\Store;
use App\Models\BrandSite;
use App\Models\SubscriptionPackage;

class BrandSiteAccess
{
    public function canPublish(Store $store): bool
    {
        return $store->status == 1
            && (! config('brandsite.pilot_stores', []) || in_array($store->slug, config('brandsite.pilot_stores', []), true))
            && ($subscription = $store->store_sub()->first())
            && $this->packageEnabled((int) $subscription->package_id)
            && $subscription->expiry_date
            && $subscription->expiry_date >= today()->toDateString()
            && ! BrandSite::query()->where('store_id', $store->id)->whereNotNull('suspended_at')->exists()
            && Store::query()->whereKey($store->id)->active()
                ->whereHas('vendor', fn ($vendor) => $vendor->where('status', 1))
                ->whereHas('module', fn ($module) => $module->where('status', 1))
                ->whereHas('zone', fn ($zone) => $zone->where('status', 1)
                    ->whereHas('modules', fn ($modules) => $modules->whereColumn('modules.id', 'stores.module_id')))
                ->exists();
    }

    public function packageEnabled(int $id): bool
    {
        $package = SubscriptionPackage::query()->whereKey($id)->toBase()->first(['brandsite_enabled', 'status']);
        if (! $package || ! $package->status) {
            return false;
        }

        return $package->brandsite_enabled === null
            ? in_array($id, config('brandsite.package_ids', []), true)
            : (bool) $package->brandsite_enabled;
    }
}
