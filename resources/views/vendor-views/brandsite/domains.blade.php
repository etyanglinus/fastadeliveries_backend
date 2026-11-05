@extends('layouts.vendor.app')

@section('title', 'BrandSite domains')
@push('css_or_js')
<style>
    .bs-domain-status { display: inline-flex; align-items: center; gap: .55rem; font-weight: 600; }
    .bs-domain-light { width: .65rem; height: .65rem; border-radius: 50%; background: currentColor; }
    .bs-domain-online { color: #087443; }
    .bs-domain-pending { color: #805800; }
    .bs-domain-offline { color: #b42318; }
    @media (prefers-reduced-motion: no-preference) {
        .bs-domain-online .bs-domain-light { animation: bs-domain-pulse 2.5s ease-out infinite; }
        @keyframes bs-domain-pulse { 0% { box-shadow: 0 0 0 0 #08744360; } 70%, 100% { box-shadow: 0 0 0 7px #08744300; } }
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <h1>BrandSite domains</h1>
    <a href="{{ route('vendor.brandsite.show') }}">My BrandSite</a>
    <details class="card p-3 my-3" open>
        <summary class="font-weight-bold">How to connect your domain</summary>
        <p class="mt-3">Give your shop its own address, like <code>yourshop.example</code>. This is a sample address. Use the domain you bought. Fasta Deliveries hosts the shop and provides HTTPS, so you can buy the domain by itself.</p>

        <h2 class="h5 mt-3">1. Publish your BrandSite and add your domain</h2>
        <p>Publish your storefront, then enter your domain below and click <strong>Add domain</strong>. Enter just the name, without <code>https://</code> or a page path.</p>

        <h2 class="h5 mt-3">2. Find or set your nameservers</h2>
        <p>Nameservers tell the internet where your domain’s records are managed. Sign in where you bought your domain and open <strong>Nameservers</strong> or <strong>Manage domain</strong>.</p>
        <ul>
            <li><strong>Your registrar already manages DNS.</strong> If nameservers are assigned and you can open <strong>Manage DNS</strong> or <strong>DNS Records</strong> for your domain there, keep them and go to step 3.</li>
            <li><strong>Another provider already manages DNS.</strong> Open that provider’s dashboard to add your records. Your registrar’s nameserver screen can help you identify the provider.</li>
            <li><strong>You just bought the domain and have nowhere to edit records.</strong> Choose a DNS provider, add your domain to its dashboard, and copy the exact nameserver names it assigns. Go back to your registrar, choose <strong>Custom nameservers</strong>, paste the assigned nameservers, and save. Add your records in the DNS provider’s dashboard and wait for it to confirm the domain is active. If the registrar has no nameserver editor, ask its support team to set them for you.</li>
        </ul>
        <p>If your domain already has email or other services, copy their existing records to the new DNS provider before switching nameservers.</p>

        <h2 class="h5 mt-3">3. Prove you own the domain</h2>
        <p>Add the <strong>TXT</strong> record from your domain card below. For the main domain, use <code>_fasta-verification</code> if your DNS panel adds the domain name for you. If it asks for the full name, copy the TXT name shown on the card. Paste the full value, including <code>fasta-verify=</code>, and keep the record after verification.</p>

        <h2 class="h5 mt-3">4. Point the main domain to Fasta</h2>
        <p>Add an <strong>A</strong> record named <code>@</code> with the IP shown on your domain card. <code>@</code> means your main domain. Some dashboards use a blank Name field or ask for the full domain instead.</p>
        <p>Replace any conflicting A or AAAA records for that same website address. Keep unrelated records, including MX, SPF, DKIM and DMARC for email. If your provider offers proxying, choose <strong>DNS only</strong>.</p>

        <h2 class="h5 mt-3">5. Verify and open your shop</h2>
        <p>Save your records, return here and click <strong>Verify DNS and check HTTPS</strong>. DNS changes can take anywhere from a few minutes to 48 hours. If the status is amber, check the records and retry after they have had time to update. Once it turns green, open your domain in a new browser tab. If it later turns red, check that your nameservers and records are still in place, then run the check again.</p>

        <h2 class="h5 mt-3">6. Set up www if you need it</h2>
        <p>The <code>www</code> address is separate from your main domain. If you want both to work, ask Fasta Support about setting up the additional address or an HTTPS redirect before sharing it.</p>

        <h2 class="h5 mt-3">Need help?</h2>
        <p class="mb-0">Contact Fasta Support with your domain name, your registrar or DNS provider, and the status shown below. A screenshot of the nameserver or DNS screen can help. Leave out passwords and account recovery codes.</p>
    </details>
    @error('hostname')<p role="alert">{{ $message }}</p>@enderror
    @if($can_connect)
        <form method="POST" action="{{ route('vendor.brandsite.domain.register') }}">
            @csrf
            <label for="brandsite-domain">Domain name</label>
            <input id="brandsite-domain" name="hostname" maxlength="253" placeholder="yourshop.example" required>
            <button type="submit" class="btn btn-primary">Add domain</button>
        </form>
    @endif
    @foreach($domains as $domain)
        <section class="card p-3 my-3">
            <h2>{{ $domain->hostname }}</h2>
            @php
                $connected = $can_connect && $published && $ingress_ready && $domain->activated_at && $domain->routing_verified_at
                    && \Illuminate\Support\Carbon::parse($domain->routing_verified_at)->gte(now()->subHours(48));
                $wasConnected = $domain->last_connected_at ?? $domain->activated_at;
            @endphp
            <p class="bs-domain-status bs-domain-{{ $connected ? 'online' : ($wasConnected ? 'offline' : 'pending') }}">
                <span class="bs-domain-light" aria-hidden="true"></span>
                {{ $connected ? 'Connected over HTTPS' : ($wasConnected ? 'Connection unavailable' : ($domain->verified_at ? 'Ownership verified · HTTPS pending' : 'Awaiting DNS verification')) }}
            </p>
            @if($wasConnected)
                <p class="small text-muted">Last successful check {{ \Illuminate\Support\Carbon::parse($wasConnected)->timezone('Africa/Nairobi')->format('j M Y, H:i') }} EAT.</p>
            @endif
            @if($wasConnected && !$connected)
                <p>Check that the storefront is published and that the DNS records still match, then run the connection check again.</p>
            @endif
            @if($ingress_ready)<p>A record <code>{{ $domain->hostname }}</code> → <code>{{ config('brandsite.ingress_ip') }}</code> (use <code>@</code> for the main domain).</p>
                <p class="small text-muted">For a subdomain, you can also use a CNAME pointing to <code>{{ config('brandsite.ingress_host') }}</code>.</p>@else<p>Custom domain hosting is awaiting Fasta ingress setup.</p>@endif
        <p>TXT name <code>_fasta-verification.{{ $domain->hostname }}</code></p>
            <p>TXT value <code>fasta-verify={{ $domain->verification_token }}</code></p>
            @if($can_connect)
                <form method="POST" action="{{ route('vendor.brandsite.domain.verify', $domain->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Verify DNS and check HTTPS</button>
                </form>
            @endif
            <form method="POST" action="{{ route('vendor.brandsite.domain.remove', $domain->id) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-secondary">Remove domain</button>
            </form>
        </section>
    @endforeach
</div>
@endsection
