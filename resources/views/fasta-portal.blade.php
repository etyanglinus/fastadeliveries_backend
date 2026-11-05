<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">
    <title>Fasta Deliveries | Portal</title>
    <style>
        :root { color-scheme: light; font: 16px/1.55 system-ui, sans-serif; color: #153f32; background: #f8f7f1; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; }
        .shell { width: min(100% - 36px, 1060px); margin-inline: auto; }
        header { padding-block: 23px; border-bottom: 1px solid #153f3220; }
        .brand { color: inherit; text-decoration: none; font-size: 1.25rem; font-weight: 800; letter-spacing: -.035em; }
        .brand span { color: #026034; }
        main { padding-block: clamp(36px, 7vw, 85px); }
        .intro { background: #153f32; color: #fff; padding: clamp(25px, 5.5vw, 60px); border-radius: 28px; }
        .eyebrow { margin: 0; color: #c0f0d4; font-size: .8rem; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; }
        h1 { max-width: 15ch; margin: 12px 0; font-size: clamp(2rem, 6vw, 4rem); line-height: 1.04; letter-spacing: -.055em; }
        .intro p:not(.eyebrow) { max-width: 51ch; color: #e0efe6; }
        .button { display: inline-block; padding: 11px 19px; border-radius: 100px; font-weight: 700; text-decoration: none; }
        .primary { color: #153f32; background: #c0f0d4; margin-top: 9px; }
        .secondary { color: #026034; background: #eaf4ed; }
        a:hover { text-decoration: underline; }
        a:focus-visible { outline: 3px solid #9b5d16; outline-offset: 4px; }
        .portals { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-top: 14px; }
        .card { background: white; border: 1px solid #153f3220; padding: clamp(18px, 3vw, 28px); border-radius: 20px; }
        h2 { margin: 0; font-size: 1.3rem; }
        .card p { color: #465b51; margin: 7px 0 15px; }
        footer { border-top: 1px solid #153f3220; padding-block: 18px; color: #465b51; font-size: .9rem; }
        @media (max-width: 580px) { .portals { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header><div class="shell"><a class="brand" href="{{ $storefrontUrl ?: route('home') }}">Fasta <span>Deliveries</span></a></div></header>
    <main class="shell">
        <section class="intro" aria-labelledby="portal-title">
            <p class="eyebrow">Fasta Deliveries</p>
            <h1 id="portal-title">Shop, sell and manage on Fasta</h1>
            <p>Find local shops and place orders on the Fasta storefront. If you sell on Fasta or manage the platform, sign in to your workspace below.</p>
            @if ($storefrontUrl)
                <a class="button primary" href="{{ $storefrontUrl }}">Open storefront</a>
            @endif
        </section>
        <div class="portals">
            @if ($vendorLogin)
                <section class="card">
                    <h2>Seller workspace</h2>
                    <p>Manage products, fulfil orders and edit your BrandSite.</p>
                    <a class="button secondary" href="{{ $vendorLogin }}">Seller sign in</a>
                </section>
            @endif
            @if ($adminLogin)
                <section class="card">
                    <h2>Fasta administration</h2>
                    <p>Manage sellers, modules and marketplace operations.</p>
                    <a class="button secondary" href="{{ $adminLogin }}">Admin sign in</a>
                </section>
            @endif
        </div>
    </main>
    <footer><div class="shell">Fasta Deliveries</div></footer>
</body>
</html>
