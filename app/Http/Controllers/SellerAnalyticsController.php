<?php
namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SellerAnalyticsController extends Controller
{
    public function collect(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'uuid'],
            'store_id' => ['required', 'integer'],
            'visitor_id' => ['required', 'uuid'],
            'session_id' => ['required', 'uuid'],
            'channel' => ['required', Rule::in(['brandsite', 'web', 'android', 'ios'])],
            'event' => ['required', Rule::in(['page_view', 'product_view', 'add_to_cart', 'checkout_start', 'vehicle_view', 'booking_start'])],
            'device' => ['required', Rule::in(['mobile', 'desktop', 'tablet'])],
            'referrer' => ['nullable', 'url', 'max:2048'],
        ]);
        $store = Store::query()->whereKey($data['store_id'])->where('status', 1)->firstOrFail();
        if ($data['channel'] === 'brandsite') {
            abort_unless(DB::table('brandsite_sites')->where('store_id', $store->id)->whereNotNull('published_revision')->whereNull('suspended_at')->exists(), 404);
        }
        // Trust geolocation only from an explicitly allowlisted immediate ingress peer.
        $peers = array_filter(array_map('trim', explode(',', (string) config('seller_analytics.geo_proxies'))));
        $peer = (string) $request->server('REMOTE_ADDR');
        $trustedGeo = $peers && \Symfony\Component\HttpFoundation\IpUtils::checkIp($peer, $peers);
        $country = $trustedGeo ? strtoupper((string) $request->header('CF-IPCountry')) : '';
        $city = $trustedGeo ? mb_substr((string) $request->header('CF-IPCity'), 0, 120) : '';
        if (!preg_match('/^[A-Z]{2}$/D', $country) || in_array($country, ['XX', 'T1'], true)) $country = '';
        $digest = fn ($value) => hash_hmac('sha256', $store->id.':'.$value, config('app.key'));
        DB::table('seller_analytics_events')->insertOrIgnore([
            'event_id' => $data['event_id'],
            'store_id' => $store->id,
            'country' => $country ?: null,
            'city' => $city ?: null,
            'channel' => $data['channel'],
            'event' => $data['event'],
            'visitor' => $digest($data['visitor_id']),
            'session' => $digest($data['session_id']),
            'device' => $data['device'],
            'referrer' => isset($data['referrer']) ? parse_url($data['referrer'], PHP_URL_HOST) : null,
            'created_at' => now(),
        ]);
        return response()->noContent();
    }

    public function report(Request $request)
    {
        $data = $request->validate(['days' => ['nullable', 'integer', 'min:1', 'max:90'], 'store_id' => ['nullable', 'integer']]);
        $stores = Store::query()->where('vendor_id', auth('vendor')->id())->get(['id', 'name']);
        $store = $stores->firstWhere('id', (int) ($data['store_id'] ?? $stores->first()?->id));
        abort_unless($store && auth('vendor')->check() && !auth('vendor_employee')->check(), 403);
        $days = (int) ($data['days'] ?? 30);
        $query = DB::table('seller_analytics_events')->where('store_id', $store->id)->where('created_at', '>=', now()->subDays($days));
        $summary = (clone $query)->selectRaw("COUNT(*) as events, COUNT(DISTINCT visitor) as visitors, COUNT(DISTINCT session) as sessions, SUM(event = 'page_view') as views")->first();
        $channels = (clone $query)->select('channel')->selectRaw('COUNT(*) as total')->groupBy('channel')->get();
        $daily = (clone $query)->selectRaw('DATE(created_at) as day, COUNT(*) as total')->groupByRaw('DATE(created_at)')->orderBy('day')->get();
        $locations = (clone $query)->select('country', 'city')->selectRaw('COUNT(DISTINCT visitor) as visitors')
            ->groupBy('country', 'city')->orderByDesc('visitors')->limit(30)->get();
        $orders = DB::table('orders')->where('store_id', $store->id)->where('created_at', '>=', now()->subDays($days));
        $completedOrders = (clone $orders)->where('order_status', 'delivered')->count();
        $brandsiteOrders = (clone $orders)->where('order_status', 'delivered')->whereNotNull('brandsite_site_id')->count();
        return view('vendor-views.analytics', compact('stores', 'store', 'days', 'summary', 'channels', 'daily', 'locations', 'completedOrders', 'brandsiteOrders'));
    }
}
