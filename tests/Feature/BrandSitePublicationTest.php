<?php

namespace Tests\Feature;

use App\Models\BrandSite;
use App\Models\Admin;
use App\Models\Store;
use App\Models\Vendor;
use App\Services\BrandSiteAccess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BrandSitePublicationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_vendors_publish_isolated_revisions_and_editor_codes_cannot_be_replayed(): void
    {
        $slugs = [getenv('BRANDSITE_TEST_STORE_A'), getenv('BRANDSITE_TEST_STORE_B')];
        if (getenv('BRANDSITE_RUN_INTEGRATION') !== '1') {
            $this->markTestSkipped('Opt in with BRANDSITE_RUN_INTEGRATION=1 against a disposable staging clone.');
        }

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->assertNotEmpty($slugs[0], 'Set BRANDSITE_TEST_STORE_A.');
        $this->assertNotEmpty($slugs[1], 'Set BRANDSITE_TEST_STORE_B.');
        $first = Store::query()->where('slug', $slugs[0])->sole();
        $second = Store::query()->where('slug', $slugs[1])->sole();
        $this->assertNotEquals($first->vendor_id, $second->vendor_id);
        $this->assertNotNull($first->store_sub, 'The first test store needs an active subscription.');
        $this->assertNotNull($second->store_sub, 'The second test store needs an active subscription.');
        DB::table('subscription_packages')->whereIn('id', [$first->store_sub->package_id, $second->store_sub->package_id])
            ->update(['brandsite_enabled' => null]);
        config([
            'brandsite.pilot_stores' => $slugs,
            'brandsite.package_ids' => [(int) $first->store_sub->package_id, (int) $second->store_sub->package_id],
            'brandsite.web_origin' => 'https://web.example.test',
        ]);
        $this->assertTrue(app(BrandSiteAccess::class)->canPublish($first));
        $this->assertTrue(app(BrandSiteAccess::class)->canPublish($second));

        $this->signInFor($first);
        $this->get(route('vendor.brandsite.show'))->assertOk();
        $revision = $this->getJson(route('vendor.brandsite.show'))->assertOk()->json('revision');
        $settings = ['title' => 'First vendor published', 'accent' => '#039D55', 'sections' => ['hero', 'catalog']];
        $published = $this->postJson(route('vendor.brandsite.save'), $settings + [
            'revision' => $revision, 'publish' => true,
        ])->assertOk()->json('revision');
        $this->postJson(route('vendor.brandsite.save'), $settings + ['revision' => $revision])->assertStatus(409);
        $this->postJson(route('vendor.brandsite.save'), array_replace($settings, [
            'revision' => $published, 'title' => 'First vendor private draft',
        ]))->assertOk();
        $this->getJson('/api/v1/brandsite/'.$first->slug)->assertOk()
            ->assertJsonPath('content.theme.title', 'First vendor published')
            ->assertJsonPath('content.store.id', $first->id);
        $hostname = 'brandsite-'.bin2hex(random_bytes(8)).'.example.com';
        $this->post(route('vendor.brandsite.domain.register'), ['hostname' => $hostname])->assertRedirect();
        $domainId = DB::table('brandsite_domains')->where('hostname', $hostname)->value('id');
        $this->assertNotNull($domainId);
        $secret = str_repeat('x', 40);
        config([
            'brandsite.ingress_host' => 'edge.fasta-staging.com',
            'brandsite.ingress_ip' => '8.8.8.8',
            'brandsite.ingress_secret' => $secret,
        ]);
        $auth = ['Authorization' => 'Bearer '.$secret];
        $domainEndpoint = '/api/v1/brandsite-host/'.$hostname;
        $this->getJson($domainEndpoint.'?purpose=certificate', $auth)->assertNotFound();
        DB::table('brandsite_domains')->where('id', $domainId)->update(['verified_at' => now(), 'routing_verified_at' => now()]);
        $this->getJson($domainEndpoint.'?purpose=certificate')->assertNotFound();
        $this->getJson($domainEndpoint.'?purpose=certificate', $auth)->assertOk();
        $this->getJson($domainEndpoint.'?purpose=serve', $auth)->assertNotFound();
        DB::table('brandsite_domains')->where('id', $domainId)->update(['activated_at' => now()]);
        $this->getJson($domainEndpoint.'?purpose=serve', $auth)->assertOk()
            ->assertJsonPath('content.store.id', $first->id)
            ->assertJsonPath('content.theme.title', 'First vendor published');
        DB::table('brandsite_domains')->where('id', $domainId)->update(['routing_verified_at' => now()->subHours(49)]);
        $this->getJson($domainEndpoint.'?purpose=serve', $auth)->assertNotFound();

        $launch = $this->post(route('vendor.brandsite.launch'))->assertRedirect();
        $fragment = parse_url($launch->headers->get('Location'), PHP_URL_FRAGMENT);
        parse_str($fragment, $query);
        $grant = ['code' => $query['code']];
        $token = $this->postJson('/api/v1/brandsite/editor/session', $grant)->assertOk()->json('content.token');
        $this->postJson('/api/v1/brandsite/editor/session', $grant)->assertUnauthorized();

        $this->signInFor($second);
        $this->delete(route('vendor.brandsite.domain.remove', $domainId))->assertNotFound();
        $secondRevision = $this->getJson(route('vendor.brandsite.show'))->assertOk()->json('revision');
        $this->postJson(route('vendor.brandsite.save'), array_replace($settings, [
            'revision' => $secondRevision, 'title' => 'Second vendor published',
            'publish' => true, 'store_id' => $first->id,
        ]))->assertOk();
        $this->getJson('/api/v1/brandsite/'.$second->slug)->assertOk()
            ->assertJsonPath('content.theme.title', 'Second vendor published')
            ->assertJsonPath('content.store.id', $second->id);
        $this->assertSame('First vendor private draft', BrandSite::query()->where('store_id', $first->id)->sole()->draft['title']);
        $this->withToken($token)->getJson('/api/v1/brandsite/editor/state')->assertOk()
            ->assertJsonPath('content.store.slug', $first->slug);
        $this->withToken($token)->postJson('/api/v1/brandsite/editor/state', [
            'revision' => $published + 1, 'restore_revision' => $published,
        ])->assertOk()->assertJsonPath('content.draft.title', 'First vendor published')
            ->assertJsonPath('content.published_revision', $published);

        DB::table('brandsite_editor_sessions')->where('session_hash', hash('sha256', $token))
            ->update(['expires_at' => now()->subMinute()]);
        $this->withToken($token)->getJson('/api/v1/brandsite/editor/state')->assertUnauthorized();
        config(['brandsite.package_ids' => []]);
        $this->getJson('/api/v1/brandsite/'.$second->slug)->assertNotFound();
        $this->postJson(route('vendor.brandsite.save'), $settings + ['revision' => $secondRevision + 1])
            ->assertForbidden();

        config(['brandsite.package_ids' => [(int) $first->store_sub->package_id, (int) $second->store_sub->package_id]]);
        $admin = Admin::query()->where('role_id', 1)->where('is_logged_in', 1)->firstOrFail();
        $this->actingAs($admin, 'admin')->withSession(['login_remember_token' => $admin->login_remember_token]);
        $this->get(route('admin.brandsite.index'))->assertOk();
        $secondSite = BrandSite::query()->where('store_id', $second->id)->sole();
        $this->post(route('admin.brandsite.moderate', $secondSite->id), ['suspended' => true])->assertRedirect();
        $this->getJson('/api/v1/brandsite/'.$second->slug)->assertNotFound();
        $this->assertDatabaseHas('brandsite_events', ['site_id' => $secondSite->id, 'action' => 'suspend', 'actor_id' => $admin->id]);
        $this->post(route('admin.brandsite.moderate', $secondSite->id), ['suspended' => false])->assertRedirect();
        $this->getJson('/api/v1/brandsite/'.$second->slug)->assertOk()
            ->assertJsonPath('content.theme.title', 'Second vendor published');
        DB::table('subscription_packages')->where('id', $second->store_sub->package_id)->update(['brandsite_enabled' => false]);
        $this->getJson('/api/v1/brandsite/'.$second->slug)->assertNotFound();
        DB::table('subscription_packages')->where('id', $second->store_sub->package_id)->update(['brandsite_enabled' => true]);
        $this->getJson('/api/v1/brandsite/'.$second->slug)->assertOk();
        $this->postJson(route('admin.brandsite.launch', $second->id), ['support_reason' => 'short'])->assertUnprocessable();
        $supportLaunch = $this->post(route('admin.brandsite.launch', $second->id), ['support_reason' => 'Seller asked for help completing their site.'])->assertRedirect();
        parse_str(parse_url($supportLaunch->headers->get('Location'), PHP_URL_FRAGMENT), $supportGrant);
        $supportToken = $this->postJson('/api/v1/brandsite/editor/session', $supportGrant)->assertOk()->json('content.token');
        $supportState = $this->withToken($supportToken)->getJson('/api/v1/brandsite/editor/state')->assertOk()
            ->assertJsonPath('content.support_mode', true)->assertJsonPath('content.store.slug', $second->slug)->json('content');
        $this->withToken($supportToken)->postJson('/api/v1/brandsite/editor/state', array_replace($settings, [
            'title' => 'Finished with admin support', 'revision' => $supportState['revision'], 'publish' => true,
            'store_id' => $first->id, 'admin_id' => 999999,
        ]))->assertOk();
        $this->assertDatabaseHas('brandsite_events', ['site_id' => $secondSite->id, 'actor_type' => 'admin', 'actor_id' => $admin->id, 'action' => 'publish']);
        $this->getJson('/api/v1/brandsite/'.$second->slug)->assertOk()->assertJsonPath('content.theme.title', 'Finished with admin support');
        $this->post(route('admin.brandsite.unpublish', $secondSite->id))->assertRedirect();
        $this->getJson('/api/v1/brandsite/'.$second->slug)->assertNotFound();
        $this->assertDatabaseHas('brandsite_events', ['site_id' => $secondSite->id, 'actor_type' => 'admin', 'action' => 'unpublish']);
        $this->withToken($supportToken)->postJson('/api/v1/brandsite/editor/state', $settings + ['revision' => $supportState['revision'] + 1, 'publish' => true])->assertStatus(409);
        $admin->role_id = 2; $admin->save();
        $this->withToken($supportToken)->getJson('/api/v1/brandsite/editor/state')->assertForbidden();
        $this->post(route('admin.brandsite.launch', $second->id), ['support_reason' => 'Unauthorized support request'])->assertForbidden();
        $admin->role_id = 1; $admin->save();
    }

    public function test_package_forms_persist_brandsite_as_an_available_feature(): void
    {
        if (getenv('BRANDSITE_RUN_INTEGRATION') !== '1') $this->markTestSkipped('Disposable local database only.');
        $admin = Admin::query()->where('role_id', 1)->where('is_logged_in', 1)->firstOrFail();
        $this->actingAs($admin, 'admin')->withSession(['login_remember_token' => $admin->login_remember_token]);
        $this->get(route('admin.business-settings.subscriptionackage.create', ['module' => 'all']))->assertOk()->assertSee('name="brandsite_enabled"', false);
        $name = 'BrandSite test '.bin2hex(random_bytes(4));
        $payload = ['module' => 'all', 'lang' => ['default'], 'package_name' => [$name], 'text' => ['For local verification'],
            'package_price' => 100, 'package_validity' => 30, 'minimum_order_limit' => 'on', 'maximum_item_limit' => 'on',
            'brandsite_enabled' => '1', 'colour' => '#039D55'];
        $this->post(route('admin.business-settings.subscriptionackage.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $packageId = DB::table('subscription_packages')->where('package_name', $name)->value('id');
        $this->assertNotNull($packageId);
        $this->assertDatabaseHas('subscription_packages', ['id' => $packageId, 'brandsite_enabled' => 1]);
        $this->get(route('admin.business-settings.subscriptionackage.edit', $packageId))->assertOk()->assertSee('name="brandsite_enabled"', false);
        unset($payload['brandsite_enabled']);
        $this->put(route('admin.business-settings.subscriptionackage.update', $packageId), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('subscription_packages', ['id' => $packageId, 'brandsite_enabled' => 0]);
    }

    public function test_repeatable_blocks_and_scoped_image_uploads_publish_as_one_document(): void
    {
        if (getenv('BRANDSITE_RUN_INTEGRATION') !== '1') $this->markTestSkipped('Disposable local database only.');
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $store = Store::query()->where('slug', getenv('BRANDSITE_TEST_STORE_A'))->sole();
        config(['brandsite.pilot_stores' => [$store->slug], 'brandsite.web_origin' => 'https://web.example.test']);
        DB::table('subscription_packages')->where('id', $store->store_sub->package_id)->update(['brandsite_enabled' => true]);
        $this->signInFor($store);
        $revision = $this->getJson(route('vendor.brandsite.show'))->assertOk()->json('revision');
        $launch = $this->post(route('vendor.brandsite.launch'))->assertRedirect();
        parse_str(parse_url($launch->headers->get('Location'), PHP_URL_FRAGMENT), $grant);
        $token = $this->postJson('/api/v1/brandsite/editor/session', $grant)->assertOk()->json('content.token');
        \Illuminate\Support\Facades\Storage::fake('public');
        $upload = $this->withToken($token)->withHeaders(['Accept' => 'application/json'])->post('/api/v1/brandsite/editor/media', [
            'image' => \Illuminate\Http\UploadedFile::fake()->image('collection.jpg', 800, 600), 'store_id' => 999999,
        ])->assertOk()->json('content.url');
        $this->assertStringContainsString('/brandsite/'.$store->id.'/', $upload);
        $this->withToken($token)->post('/api/v1/brandsite/editor/media', [
            'image' => \Illuminate\Http\UploadedFile::fake()->createWithContent('unsafe.svg', '<svg onload="alert(1)"></svg>'),
        ])->assertUnprocessable();
        $theme = json_decode(file_get_contents(base_path('tests/Fixtures/brandsite-v2.json')), true, 512, JSON_THROW_ON_ERROR);
        $theme['sections'][0]['props']['image'] = $upload;
        $repeat = $theme['sections'][0]; $repeat['id'] = 'another-hero'; $repeat['props']['heading'] = 'A second independent hero';
        $theme['sections'][] = $repeat;
        $result = $this->withToken($token)->postJson('/api/v1/brandsite/editor/state', $theme + ['revision' => $revision, 'publish' => true])
            ->assertOk()->assertJsonPath('content.draft.version', 2)->json('content');
        $this->getJson('/api/v1/brandsite/'.$store->slug)->assertOk()->assertJsonCount(17, 'content.theme.sections')
            ->assertJsonPath('content.theme.sections.0.props.image', $upload)->assertJsonPath('content.theme.sections.16.props.heading', 'A second independent hero');
        $bad = $theme; $bad['sections'][0]['props']['href'] = 'javascript:alert(1)';
        $this->withToken($token)->postJson('/api/v1/brandsite/editor/state', $bad + ['revision' => $result['revision']])->assertUnprocessable();
        $this->assertSame($result['revision'], BrandSite::query()->where('store_id', $store->id)->value('revision'));
        $this->withToken(str_repeat('0', 64))->post('/api/v1/brandsite/editor/media', [
            'image' => \Illuminate\Http\UploadedFile::fake()->image('unauthorized.jpg'),
        ])->assertUnauthorized();
    }

    private function signInFor(Store $store): void
    {
        $vendor = Vendor::query()->findOrFail($store->vendor_id);
        $this->assertEquals($store->id, $vendor->stores->first()?->id, 'Use the vendor dashboard’s primary store.');
        $this->actingAs($vendor, 'vendor')->withSession([
            'login_remember_token' => $vendor->login_remember_token,
        ]);
    }
}
