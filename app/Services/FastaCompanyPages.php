<?php

namespace App\Services;

use App\Models\DataSetting;
use App\CentralLogics\Helpers;

class FastaCompanyPages
{
    private const TYPE = 'fasta_company_page';

    public const ACTIONS = [
        'order' => 'Order online',
        'seller' => 'Apply to sell',
        'delivery' => 'Apply to deliver',
        'seller_login' => 'Seller sign in',
        'contact' => 'Contact Fasta',
    ];

    public function all(): array
    {
        $pages = config('fasta-company');
        $saved = DataSetting::withoutGlobalScopes()->where('type', self::TYPE)->get()->keyBy('key');

        foreach ($pages as $slug => $page) {
            $record = $saved->get($slug);
            $overrides = $record ? json_decode($record->getRawOriginal('value'), true) : null;
            $pages[$slug] = is_array($overrides) ? array_replace($page, $overrides) : $page;
        }

        return $pages;
    }

    public function save(string $slug, array $page): void
    {
        DataSetting::withoutGlobalScopes()->updateOrCreate(
            ['type' => self::TYPE, 'key' => $slug],
            ['value' => json_encode($page, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]
        );
    }

    public function url(string $slug): string
    {
        return match ($slug) {
            'home' => route('home'),
            'about' => route('about-us'),
            'careers' => route('careers'),
            'campus' => route('campus'),
            default => route('company-page', $slug),
        };
    }

    public function action(string $action): ?string
    {
        return match ($action) {
            'order' => config('app.storefront_url'),
            'seller' => Helpers::get_business_settings('toggle_store_registration') ? route('restaurant.create') : null,
            'delivery' => Helpers::get_business_settings('toggle_dm_registration') ? route('deliveryman.create') : null,
            'seller_login' => $this->sellerLogin(),
            'contact' => route('contact-us'),
            default => null,
        };
    }

    private function sellerLogin(): ?string
    {
        $slug = DataSetting::withoutGlobalScopes()->where('key', 'store_login_url')->value('value');

        return $slug ? route('login', $slug) : null;
    }
}
