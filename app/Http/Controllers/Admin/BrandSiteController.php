<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandSite;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandSiteController extends Controller
{
    public function index(\App\Services\BrandSiteAccess $access)
    {
        abort_unless((int) auth('admin')->user()?->role_id === 1, 403);

        return view('admin-views.brandsite.index', [
            'sites' => DB::table('stores')->leftJoin('brandsite_sites', 'stores.id', '=', 'brandsite_sites.store_id')
                ->select('stores.id as store_id', 'stores.slug', 'stores.name', 'brandsite_sites.id', 'brandsite_sites.revision', 'brandsite_sites.published_revision', 'brandsite_sites.suspended_at')
                ->orderBy('stores.name')->simplePaginate(25)->through(function ($site) use ($access) {
                    $store = Store::query()->find($site->store_id);
                    $site->can_edit = $store && $access->canPublish($store);
                    return $site;
                }),
            'domains' => DB::table('brandsite_domains')->orderBy('hostname')->simplePaginate(25, ['*'], 'domains_page')->withQueryString(),
            'events' => DB::table('brandsite_events')->orderByDesc('id')->limit(30)->get(),
        ]);
    }

    public function launch(Request $request, int $storeId, \App\Services\BrandSiteAccess $access)
    {
        abort_unless((int) auth('admin')->user()?->role_id === 1, 403);
        $request->validate(['support_reason' => ['required', 'string', 'min:8', 'max:300']]);
        $store = Store::query()->findOrFail($storeId);
        abort_unless($access->canPublish($store), 403, 'Enable the store package and reinstate its site before editing.');
        $origin = rtrim((string) config('brandsite.web_origin'), '/');
        $local = app()->environment('local') && preg_match('~^http://(?:localhost|127\.0\.0\.1)(?::[0-9]+)?$~D', $origin);
        abort_unless($local || preg_match('~^https://[a-z0-9.-]+(?::[0-9]+)?$~iD', $origin), 503);
        $code = bin2hex(random_bytes(32));
        DB::transaction(function () use ($store, $code, $request, $access) {
            $locked = Store::query()->whereKey($store->id)->lockForUpdate()->firstOrFail();
            abort_unless($access->canPublish($locked), 403);
            $site = BrandSite::query()->firstOrCreate(['store_id' => $locked->id]);
            DB::table('brandsite_editor_sessions')->insert([
                'vendor_id' => $locked->vendor_id, 'store_id' => $locked->id, 'admin_id' => auth('admin')->id(),
                'launch_hash' => hash('sha256', $code), 'expires_at' => now()->addMinute(),
            ]);
            $site->recordEvent('admin', (int) auth('admin')->id(), 'support_open', $site->revision);
            // The reason stays in the authenticated application log, never the redirect URL.
            \Illuminate\Support\Facades\Log::info('BrandSite support session', ['admin_id' => auth('admin')->id(), 'store_id' => $locked->id, 'reason' => $request->input('support_reason')]);
        }, 3);
        return redirect()->away($origin.'/brandsite/manage/editor#code='.$code)->header('Cache-Control', 'no-store');
    }

    public function unpublish(Request $request, int $id)
    {
        abort_unless((int) auth('admin')->user()?->role_id === 1, 403);
        DB::transaction(function () use ($id) {
            $storeId = BrandSite::query()->whereKey($id)->value('store_id');
            Store::query()->whereKey($storeId)->lockForUpdate()->firstOrFail();
            $site = BrandSite::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $site->published_revision = null;
            $site->revision = (int) $site->revision + 1;
            $site->save();
            $site->recordEvent('admin', (int) auth('admin')->id(), 'unpublish', $site->revision);
        }, 3);
        return redirect()->route('admin.brandsite.index');
    }

    public function removeDomain(int $id)
    {
        abort_unless((int) auth('admin')->user()?->role_id === 1, 403);
        DB::transaction(function () use ($id) {
            $domain = DB::table('brandsite_domains')->where('id', $id)->first();
            abort_unless($domain, 404);
            $site = BrandSite::query()->findOrFail($domain->site_id);
            DB::table('brandsite_domains')->where('id', $id)->delete();
            $site->recordEvent('admin', (int) auth('admin')->id(), 'remove_domain', $site->revision);
        });
        return redirect()->route('admin.brandsite.index');
    }

    public function update(Request $request, int $id)
    {
        abort_unless((int) auth('admin')->user()?->role_id === 1, 403);
        $input = $request->validate(['suspended' => ['required', 'boolean']]);

        DB::transaction(function () use ($id, $input) {
            $storeId = BrandSite::query()->whereKey($id)->value('store_id');
            abort_unless($storeId, 404);
            // Use the same store lock as publishing.
            Store::query()->whereKey($storeId)->lockForUpdate()->firstOrFail();
            $site = BrandSite::query()->whereKey($id)->lockForUpdate()->firstOrFail();
            $suspend = (bool) $input['suspended'];
            if ($suspend === ($site->suspended_at !== null)) {
                return;
            }
            $site->suspended_at = $suspend ? now() : null;
            $site->save();
            if ($suspend) {
                DB::table('brandsite_editor_sessions')->where('store_id', $storeId)->delete();
            }
            $site->recordEvent('admin', (int) auth('admin')->id(), $suspend ? 'suspend' : 'reinstate');
        }, 3);

        return redirect()->route('admin.brandsite.index');
    }
}
