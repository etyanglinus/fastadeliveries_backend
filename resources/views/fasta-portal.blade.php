@extends('layouts.fasta-company')

@section('content')
<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow"><span></span> Built for Kenya</p>
        <h1>Good things<br>are <em>closer.</em></h1>
        <p>Find meals, groceries and everyday essentials from businesses near you. Fasta brings local shops and delivery partners together in one place.</p>
        @if($storefrontUrl)<a class="pill" href="{{ $storefrontUrl }}">Start an order <span aria-hidden="true">↗</span></a>@endif
        <a class="text-link" href="{{ route('about-us') }}">Meet Fasta <span aria-hidden="true">→</span></a>
    </div>
    <div class="visual" aria-hidden="true">
        <div class="radar"><i></i><i></i><i></i><b>F</b></div>
        <span class="signal signal-one">01 / LOCAL SHOPS</span>
        <span class="signal signal-two">02 / DELIVERY</span>
        <span class="signal signal-three">03 / YOUR DOOR</span>
    </div>
</section>

<section class="tiles" aria-label="Explore Fasta">
    <article class="tile tile-order">
        <span class="index">01 / SHOP</span>
        <h2>What do you need today?</h2>
        <p>Open the storefront, set your delivery location and browse what’s available near you.</p>
        @if($storefrontUrl)<a class="text-link" href="{{ $storefrontUrl }}">Go to the storefront <span aria-hidden="true">↗</span></a>@endif
    </article>
    <article class="tile">
        <span class="index">02 / SELL</span>
        <h2>Run your shop on Fasta.</h2>
        <p>Manage your catalogue, fulfil orders and update your BrandSite from your seller workspace.</p>
        @if($vendorLogin)<a class="text-link" href="{{ $vendorLogin }}">Seller sign in <span aria-hidden="true">↗</span></a>@endif
    </article>
    <article class="tile">
        <span class="index">03 / BUILD</span>
        <h2>Help us build what’s next.</h2>
        <p>Explore the work behind Fasta, from product and engineering to seller support and delivery operations.</p>
        <a class="text-link" href="{{ route('careers') }}">Explore careers <span aria-hidden="true">→</span></a>
    </article>
</section>
@endsection
