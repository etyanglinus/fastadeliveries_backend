@extends('layouts.fasta-company')
@section('title', 'About us | Fasta Deliveries')

@section('content')
<section class="page-intro">
    <p class="eyebrow"><span></span> About Fasta</p>
    <h1>Local business.<br><em>Within reach.</em></h1>
    <p>Fasta Deliveries connects customers, independent sellers and delivery partners through a marketplace built for Kenya.</p>
</section>
<section class="tiles about-tiles" aria-label="What guides Fasta">
    <article class="tile tile-order">
        <span class="index">01 / WHERE WE’RE GOING</span>
        <h2>Our vision</h2>
        <p>A Kenya where every neighbourhood business can reach customers online, and every customer can shop locally with confidence.</p>
    </article>
    <article class="tile">
        <span class="index">02 / WHAT WE DO</span>
        <h2>Our mission</h2>
        <p>Make it easier to discover local shops, place orders and get them delivered. Give sellers practical tools to manage their products, orders and storefronts.</p>
    </article>
</section>
@if(filled($data))
    <section class="tile story">
        <h2>{{ $data_title ?: 'Our story' }}</h2>
        <div>{!! $data !!}</div>
    </section>
@endif
@endsection
