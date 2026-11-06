@extends('layouts.landing.app')
@section('title', $companyPage['title'] . ' | Fasta Deliveries')

@section('content')
<section class="page-hero">
    <div class="container">
        <h1>{{ $companyPage['title'] }}</h1>
        @if(filled($companyPage['intro']))<p>{{ $companyPage['intro'] }}</p>@endif
    </div>
</section>
<section class="page-content"><div class="container">
    @include('company.sections', ['entry' => $companyPage, 'pageService' => $pageService])
</div></section>
@endsection
