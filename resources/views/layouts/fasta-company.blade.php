<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', 'Fasta Deliveries connects Kenyan shoppers, local businesses and delivery partners.')">
    <title>@yield('title', 'Fasta Deliveries')</title>
    <link rel="stylesheet" href="{{ asset('assets/fasta/company.css') }}">
</head>
<body>
    <a class="skip" href="#content">Skip to content</a>
    <header class="site-header">
        <div class="shell bar">
            <a class="wordmark" href="{{ route('home') }}"><span class="mark" aria-hidden="true">F</span> Fasta<span class="muted">Deliveries</span></a>
            <nav aria-label="Main navigation">
                <a href="{{ route('about-us') }}" @if(request()->routeIs('about-us')) aria-current="page" @endif>About</a>
                <a href="{{ route('careers') }}" @if(request()->routeIs('careers')) aria-current="page" @endif>Careers</a>
                @if($storefrontUrl)<a class="pill nav-order" href="{{ $storefrontUrl }}">Order online <span aria-hidden="true">↗</span></a>@endif
            </nav>
        </div>
    </header>
    <main class="shell" id="content">@yield('content')</main>
    <footer class="shell footer">
        <div><strong>Fasta Deliveries</strong><p>Built for everyday commerce in Kenya.</p></div>
        <nav aria-label="Footer navigation">
            <a href="{{ route('contact-us') }}">Contact</a>
            <a href="{{ route('privacy-policy') }}">Privacy</a>
            @if($vendorLogin)<a href="{{ $vendorLogin }}">Seller sign in</a>@endif
            @if($adminLogin)<a href="{{ $adminLogin }}">Admin sign in</a>@endif
        </nav>
    </footer>
</body>
</html>
