<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\BrandSite;
use App\Services\BrandSiteAccess;
use App\Services\BrandSiteCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandSiteController extends Controller
{
    public function show(Request $request, string $slug, BrandSiteAccess $access, BrandSiteCatalog $catalog): JsonResponse
    {
        $input = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:500']]);

        $stores = Store::query()->where('slug', $slug)->limit(2)->get();
        abort_unless($stores->count() === 1, 404);
        $store = $stores->first();

        return $this->published($store, (int) ($input['page'] ?? 1), $access, $catalog);
    }

    public function domain(Request $request, string $hostname, BrandSiteAccess $access, BrandSiteCatalog $catalog)
    {
        $connection = app(\App\Services\BrandSiteDomainConnection::class);
        abort_unless($connection->configured() && hash_equals(config('brandsite.ingress_secret'), (string) $request->bearerToken()), 404);
        $input = $request->validate(['purpose' => 'required|in:serve,certificate,probe', 'page' => 'sometimes|integer|min:1|max:500']);
        $domain = DB::table('brandsite_domains')->where('hostname', $hostname)
            ->whereNotNull('verified_at')->where('routing_verified_at', '>=', now()->subHours(48))
            ->when($input['purpose'] === 'serve', fn ($query) => $query->whereNotNull('activated_at'))->first();
        abort_unless($domain, 404);
        $site = BrandSite::query()->findOrFail($domain->site_id);
        $store = Store::query()->findOrFail($site->store_id);
        abort_unless($site->published_revision !== null && $access->canPublish($store), 404);
        if ($input['purpose'] !== 'serve') return response()->json(['allowed' => true])->header('Cache-Control', 'private, no-store');
        return $this->published($store, (int) ($input['page'] ?? 1), $access, $catalog);
    }

    private function published(Store $store, int $page, BrandSiteAccess $access, BrandSiteCatalog $catalog): JsonResponse
    {
        abort_unless($access->canPublish($store), 404);
        $site = BrandSite::query()->where('store_id', $store->id)->whereNotNull('published_revision')->firstOrFail();
        $publication = DB::table('brandsite_publications')
            ->where('site_id', $site->id)->where('revision', $site->published_revision)->first();
        abort_unless($publication, 404);

        $items = $catalog->page($store, $page);

        return response()->json([
            'theme' => json_decode($publication->configuration, true, 512, JSON_THROW_ON_ERROR),
            'store' => [
                'name' => $store->name,
                'slug' => $store->slug,
                'logo' => $store->logo_full_url,
                'cover' => $store->cover_photo_full_url,
                'id' => $store->id,
                'module_id' => $store->module_id,
            ],
            'items' => $items->items(),
            'has_more' => $items->hasMorePages(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
