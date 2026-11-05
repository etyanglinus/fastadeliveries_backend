<?php
namespace App\Services;

use App\Models\BrandSite;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

/** Shared publication lifecycle; callers resolve authenticated actors and validate input. */
class BrandSiteDraftService
{
    public function save(Store $store, int $vendorId, array $input, bool $publish, string $actorType, int $actorId): BrandSite
    {
        $access = app(BrandSiteAccess::class);
        return DB::transaction(function () use ($store, $vendorId, $input, $publish, $access, $actorType, $actorId) {
            // Lock the parent before creating the first draft, when no site row exists yet.
            $lockedStore = Store::query()->whereKey($store->id)
                ->where('vendor_id', $vendorId)->lockForUpdate()->firstOrFail();
            abort_unless($access->canPublish($lockedStore), 403);
            $site = BrandSite::query()->firstOrNew(['store_id' => $lockedStore->id]);
            abort_unless((int) $site->revision === (int) $input['revision'], 409, 'Reload BrandSite before saving.');
            $site->draft = $input['theme'];
            $site->revision = (int) $site->revision + 1;
            if ($publish) {
                $site->published_revision = $site->revision;
            }
            $site->save();

            $site->recordEvent($actorType, $actorId, 'save_draft', $site->revision);
            if ($publish) {
                DB::table('brandsite_publications')->insert([
                    'site_id' => $site->id,
                    'revision' => $site->revision,
                    'configuration' => json_encode($site->draft, JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                ]);
                $site->recordEvent($actorType, $actorId, 'publish', $site->revision);
            }

            return $site;
        }, 3);

    }

    public function restore(Store $store, int $vendorId, array $input, string $actorType, int $actorId): void
    {
        $access = app(BrandSiteAccess::class);
        DB::transaction(function () use ($input, $store, $vendorId, $access, $actorType, $actorId) {
            $lockedStore = Store::query()->whereKey($store->id)
                ->where('vendor_id', $vendorId)->lockForUpdate()->firstOrFail();
            abort_unless($access->canPublish($lockedStore), 403);
            $site = BrandSite::query()->where('store_id', $lockedStore->id)->firstOrFail();
            abort_unless($site->revision === (int) $input['revision'], 409, 'Reload BrandSite before restoring.');
            $configuration = DB::table('brandsite_publications')->where('site_id', $site->id)
                ->where('revision', $input['restore_revision'])->value('configuration');
            abort_unless($configuration, 404);
            $site->draft = json_decode($configuration, true, 512, JSON_THROW_ON_ERROR);
            $site->revision++;
            $site->save();
            $site->recordEvent($actorType, $actorId, 'restore_draft', (int) $input['restore_revision']);
        }, 3);
    }
}
