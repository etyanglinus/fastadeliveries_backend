<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\BrandSite;
use App\Models\Store;
use App\Models\Vendor;
use App\Services\BrandSiteAccess;
use App\Services\BrandSiteCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class BrandSiteController extends Controller
{
    public function show(Request $request, BrandSiteAccess $access)
    {
        $store = $this->ownedStore();
        $state = $this->state($store, $access);

        return $request->expectsJson()
            ? response()->json($state)->header('Cache-Control', 'private, no-store')
            : view('vendor-views.brandsite.index', $state);
    }

    private function state(Store $store, BrandSiteAccess $access): array
    {
        $site = BrandSite::query()->where('store_id', $store->id)->first();
        return [
            'store' => ['name' => $store->name, 'slug' => $store->slug, 'cover' => $store->cover_photo_full_url],
            'revision' => $site?->revision ?? 0,
            'published_revision' => $site?->published_revision,
            'publications' => $site ? DB::table('brandsite_publications')
                ->where('site_id', $site->id)->orderByDesc('revision')->limit(20)->pluck('revision') : [],
            'draft' => $site?->draft ?? [
                'title' => $store->name, 'description' => '', 'accent' => '#039D55', 'sections' => ['hero', 'catalog'],
            ],
            'can_publish' => $access->canPublish($store),
        ];

    }

    public function save(Request $request, BrandSiteAccess $access)
    {
        $store = $this->ownedStore();
        $site = $this->saveForStore($request, $store, (int) auth('vendor')->id(), $access);

        return $request->expectsJson()
            ? response()->json(['revision' => $site->revision, 'published_revision' => $site->published_revision])
            : redirect()->route('vendor.brandsite.show');
    }

    private function saveForStore(Request $request, Store $store, int $vendorId, BrandSiteAccess $access, string $actorType = 'vendor', ?int $actorId = null): BrandSite
    {
        $input = $request->validate(['revision' => ['required', 'integer', 'min:0'], 'publish' => ['sometimes', 'boolean']]);
        $theme = $request->only(['version', 'title', 'description', 'accent', 'motion', 'sections', 'settings', 'seo']);
        $theme['version'] = $theme['version'] ?? 1;
        $theme['description'] = $theme['description'] ?? '';
        $theme['motion'] = $theme['motion'] ?? 'none';
        $input['theme'] = app(\App\Services\BrandSiteTheme::class)->validate($theme);
        $publish = $request->boolean('publish');

        return app(\App\Services\BrandSiteDraftService::class)->save(
            $store, $vendorId, $input, $publish, $actorType, $actorId ?? $vendorId
        );
    }

    private function ownedStore(): Store
    {
        abort_unless(auth('vendor')->check() && ! auth('vendor_employee')->check(), 403);
        $store = Helpers::get_store_data();
        abort_unless($store && (int) $store->vendor_id === (int) auth('vendor')->id(), 404);

        return $store;
    }

    public function domains(BrandSiteAccess $access)
    {
        $store = $this->ownedStore();
        $site = BrandSite::query()->where('store_id', $store->id)->first();

        return view('vendor-views.brandsite.domains', [
            'domains' => $site ? DB::table('brandsite_domains')->where('site_id', $site->id)->orderBy('hostname')->get() : [],
            'can_connect' => $site && $access->canPublish($store),
            'published' => $site?->published_revision !== null,
            'ingress_ready' => app(\App\Services\BrandSiteDomainConnection::class)->configured(),
        ]);
    }

    public function registerDomain(Request $request, BrandSiteAccess $access)
    {
        $store = $this->ownedStore();
        $input = $request->validate(['hostname' => ['required', 'string', 'max:253']]);
        $host = strtolower(rtrim(trim($input['hostname']), '.'));
        if (! preg_match('/^(?=.{1,233}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z][a-z0-9-]{0,61}[a-z0-9]$/D', $host)
            || preg_match('/\.(local|localhost|internal|test|invalid|example|onion)$/D', $host)) {
            throw ValidationException::withMessages(['hostname' => 'Enter a public hostname without a URL path or port.']);
        }
        $platformHosts = [
            parse_url((string) config('app.url'), PHP_URL_HOST),
            parse_url((string) config('brandsite.web_origin'), PHP_URL_HOST),
            config('app.host_domain'),
            config('brandsite.ingress_host'),
        ];
        foreach (array_filter($platformHosts) as $platformHost) {
            $platformHost = preg_replace('/^www\./', '', strtolower($platformHost));
            if ($host === $platformHost || str_ends_with($host, '.'.$platformHost)) {
                throw ValidationException::withMessages(['hostname' => 'This hostname is reserved for Fasta.']);
            }
        }

        try {
            DB::transaction(function () use ($store, $access, $host) {
                $locked = Store::query()->whereKey($store->id)->where('vendor_id', auth('vendor')->id())->lockForUpdate()->firstOrFail();
                abort_unless($access->canPublish($locked), 403);
                $site = BrandSite::query()->where('store_id', $locked->id)->firstOrFail();
                $domains = DB::table('brandsite_domains')->where('site_id', $site->id);
                if ($domains->count() >= 10) {
                    throw ValidationException::withMessages(['hostname' => 'Each site supports up to 10 domain requests.']);
                }
                DB::table('brandsite_domains')->insert([
                    'site_id' => $site->id, 'hostname' => $host, 'verification_token' => bin2hex(random_bytes(32)),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['hostname' => 'This hostname is already registered.']);
        }

        return redirect()->route('vendor.brandsite.domains');
    }

    public function verifyDomain(int $id, BrandSiteAccess $access)
    {
        $store = $this->ownedStore();
        abort_unless($access->canPublish($store), 403);
        $query = $this->ownedDomainQuery($store, $id);
        $domain = $query->first();
        abort_unless($domain, 404);

        $connection = app(\App\Services\BrandSiteDomainConnection::class);
        $query->where('verification_token', $domain->verification_token);
        $expected = 'fasta-verify='.$domain->verification_token;
        $records = @dns_get_record('_fasta-verification.'.$domain->hostname.'.', DNS_TXT) ?: [];
        if (! collect($records)->contains(fn ($record) => hash_equals($expected, $record['txt'] ?? ''))) {
            $query->update(['verified_at' => null, 'routing_verified_at' => null, 'activated_at' => null]);
            throw ValidationException::withMessages(['hostname' => 'The verification TXT record was not found. Check the record and retry.']);
        }
        $query->update(['verified_at' => now(), 'updated_at' => now()]);
        if (! $connection->configured()) return redirect()->route('vendor.brandsite.domains');
        if (! $connection->dnsReady($domain)) {
            $query->update(['routing_verified_at' => null, 'activated_at' => null]);
            throw ValidationException::withMessages(['hostname' => 'Ownership verified. Point your DNS record to the Fasta address shown on the Domains page, then retry.']);
        }
        $query->update(['routing_verified_at' => now()]);
        if (! $connection->httpsReady($domain->hostname)) {
            $query->update(['activated_at' => null]);
            throw ValidationException::withMessages(['hostname' => 'DNS is ready. HTTPS or storefront publication is still pending. Publish your storefront and retry shortly.']);
        }
        $query->where('routing_verified_at', '>=', now()->subHours(48))->update(['activated_at' => now(), 'last_connected_at' => now()]);
        return redirect()->route('vendor.brandsite.domains');
    }

    public function removeDomain(int $id)
    {
        abort_unless($this->ownedDomainQuery($this->ownedStore(), $id)->delete() === 1, 404);

        return redirect()->route('vendor.brandsite.domains');
    }

    private function ownedDomainQuery(Store $store, int $id): \Illuminate\Database\Query\Builder
    {
        return DB::table('brandsite_domains')->where('id', $id)
            ->whereIn('site_id', BrandSite::query()->select('id')->where('store_id', $store->id));
    }

    public function launch(BrandSiteAccess $access)
    {
        $store = $this->ownedStore();
        abort_unless($access->canPublish($store), 403);
        $origin = rtrim((string) config('brandsite.web_origin'), '/');
        $localOrigin = app()->environment('local')
            && preg_match('~^http://(?:localhost|127\.0\.0\.1)(?::[0-9]+)?$~D', $origin);
        abort_unless($localOrigin || preg_match('~^https://[a-z0-9.-]+(?::[0-9]+)?$~iD', $origin), 503);

        $code = bin2hex(random_bytes(32));
        DB::table('brandsite_editor_sessions')->where('expires_at', '<=', now())->delete();
        DB::table('brandsite_editor_sessions')->insert([
            'vendor_id' => auth('vendor')->id(),
            'store_id' => $store->id,
            'launch_hash' => hash('sha256', $code),
            'expires_at' => now()->addMinute(),
        ]);

        return redirect()->away($origin.'/brandsite/manage/editor#code='.$code)
            ->header('Cache-Control', 'no-store');
    }

    public function exchange(Request $request, BrandSiteAccess $access)
    {
        $input = $request->validate(['code' => ['required', 'regex:/^[a-f0-9]{64}$/D']]);
        $token = bin2hex(random_bytes(32));

        DB::transaction(function () use ($input, $token, $access) {
            $query = DB::table('brandsite_editor_sessions')
                ->where('launch_hash', hash('sha256', $input['code']))
                ->whereNull('session_hash')->where('expires_at', '>', now());
            $session = $query->lockForUpdate()->first();
            abort_unless($session, 401);
            $this->sessionStore($session, $access);
            $query->update(['session_hash' => hash('sha256', $token), 'expires_at' => now()->addMinutes(15)]);
        });

        return response()->json(['token' => $token])->header('Cache-Control', 'no-store');
    }

    public function editor(Request $request, BrandSiteAccess $access, BrandSiteCatalog $catalog)
    {
        $token = $request->bearerToken();
        abort_unless(is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token), 401);
        $session = DB::table('brandsite_editor_sessions')
            ->where('session_hash', hash('sha256', $token))->where('expires_at', '>', now())->first();
        abort_unless($session, 401);
        $store = $this->sessionStore($session, $access);
        $actorType = $session->admin_id ? 'admin' : 'vendor';
        $actorId = (int) ($session->admin_id ?: $session->vendor_id);

        if ($request->isMethod('POST') && $request->has('theme')) {
            $request->validate([
                'revision' => ['required', 'integer', 'min:0'],
                'restore_revision' => ['prohibited'],
                'publish' => ['prohibited'],
            ]);
            $theme = app(\App\Services\BrandSiteTheme::class)->validate($request->input('theme'));
            // Never take store IDs, entitlement, revision or publish flags from a package.
            $request->replace(array_merge($theme, ['revision' => $request->input('revision'), 'publish' => false]));
            $this->saveForStore($request, $store, (int) $session->vendor_id, $access, $actorType, $actorId);
        } elseif ($request->isMethod('POST') && $request->has('restore_revision')) {
            $this->restoreDraft($request, $store, (int) $session->vendor_id, $access, $actorType, $actorId);
        } elseif ($request->isMethod('POST')) {
            $this->saveForStore($request, $store, (int) $session->vendor_id, $access, $actorType, $actorId);
        }

        $state = $this->state($store, $access);
        $state['support_mode'] = $session->admin_id !== null;
        $state['dashboard_url'] = rtrim(config('app.url'), '/').route($session->admin_id ? 'admin.brandsite.index' : 'vendor.brandsite.show', [], false);
        $state['store']['logo'] = $store->logo_full_url;
        $state['items'] = $catalog->page($store)->items();

        return response()->json($state)->header('Cache-Control', 'private, no-store');
    }

    public function media(Request $request, BrandSiteAccess $access)
    {
        $token = $request->bearerToken();
        abort_unless(is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token), 401);
        $session = DB::table('brandsite_editor_sessions')->where('session_hash', hash('sha256', $token))->where('expires_at', '>', now())->first();
        abort_unless($session, 401);
        $store = $this->sessionStore($session, $access);
        $path = app(\App\Services\BrandSiteMediaService::class)->store(
            $store, $request->file('image'), $request->hasFile('image') ? '' : $request->getContent(), $request->header('Content-Type')
        );
        return response()->json(['url' => asset('storage/app/public/'.$path)])->header('Cache-Control', 'private, no-store');
    }

    private function restoreDraft(Request $request, Store $store, int $vendorId, BrandSiteAccess $access, string $actorType = 'vendor', ?int $actorId = null): void
    {
        $input = $request->validate([
            'revision' => ['required', 'integer', 'min:0'],
            'restore_revision' => ['required', 'integer', 'min:1'],
        ]);

        app(\App\Services\BrandSiteDraftService::class)->restore($store, $vendorId, $input, $actorType, $actorId ?? $vendorId);
    }

    private function sessionStore(object $session, BrandSiteAccess $access): Store
    {
        if ($session->admin_id) {
            abort_unless(\App\Models\Admin::query()->whereKey($session->admin_id)->where('role_id', 1)->where('is_logged_in', 1)->exists(), 403);
        }
        abort_unless(Vendor::query()->whereKey($session->vendor_id)->where('status', 1)->exists(), 403);
        $store = Store::query()->whereKey($session->store_id)
            ->where('vendor_id', $session->vendor_id)->firstOrFail();
        abort_unless($access->canPublish($store), 403);

        return $store;
    }
}
