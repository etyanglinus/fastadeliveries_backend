@extends('layouts.landing.app')
@section('title', 'Careers | Fasta Deliveries')

@section('content')
    <section class="page-hero">
        <div class="container">
            <h1>Careers at Fasta</h1>
            <p>Help Kenyan businesses reach more customers.</p>
        </div>
    </section>
    <section class="page-content">
        <div class="container">
            <div class="fasta-company-grid">
                <article class="content-card"><h2>Product and engineering</h2><p>Build the storefront and tools that keep orders moving.</p></article>
                <article class="content-card"><h2>Seller support</h2><p>Help local businesses set up and manage their stores.</p></article>
                <article class="content-card"><h2>Delivery operations</h2><p>Improve how orders get from shops to customers.</p></article>
            </div>
            <div class="fasta-order-card">
                <div><h2>Hiring enquiries</h2><p>Tell us which area interests you and a little about your experience.</p></div>
                <a class="fasta-order-link" href="{{ route('contact-us') }}">Contact Fasta</a>
            </div>
        </div>
    </section>
@endsection
